<?php

use Illuminate\Support\Facades\Request;
use App\Models\Log;
use App\Models\Customer;
use App\Models\Load;
use Symfony\Component\Process\Process;

if (!function_exists('getmacaddress')) {

	function getmacaddress()
	{
		$os = PHP_OS_FAMILY;

		if ($os === 'Windows') {
			$command = ['getmac'];
		} elseif ($os === 'Linux' || $os === 'Darwin') {
			// Use 'ip link' for Linux/macOS
			$command = ['sh', '-c', "ip link show | grep ether | awk '{print \$2}'"];
		} else {
			return 'Unsupported OS';
		}

		$process = new Process($command);
		$process->run();

		if ($process->isSuccessful()) {
			return trim($process->getOutput());
		}

		return 'MAC address not found';
	}
}

/*
|--------------------------------------------------------------------------
| Customer Credit Summary
|--------------------------------------------------------------------------
|
| This is the single source of truth for calculating a customer's used
| amount and remaining credit. Both AccountController@editCustomer and
| AccountController@accountupdateCustomer rely on this function so the
| numbers shown on the view page and the numbers saved after an update
| always match.
|
| - usedAmount      = max(0, loadCreateAmount - receivingAmount)
| - remainingCredit = max(0, assignedCreditLimit - loadCreateAmount)
|
| NOTE: this function does not touch the database, it is pure calculation.
| The caller is responsible for deciding which loads to include (e.g.
| excluding Cancelled loads) and which credit-limit source to use.
|
*/
if (!function_exists('calculate_customer_credit_summary')) {

	function calculate_customer_credit_summary($customer, $assignedCreditLimit = null, $loadCreateAmount = null, $receivingAmount = null)
	{
		$assignedCreditLimit = $assignedCreditLimit !== null ? (float) $assignedCreditLimit : 0.0;
		$assignedCreditLimit = max(0.0, $assignedCreditLimit);

		$loadCreateAmount = $loadCreateAmount !== null ? (float) $loadCreateAmount : 0.0;
		$receivingAmount  = $receivingAmount !== null ? (float) $receivingAmount : 0.0;

		$usedAmount      = max(0.0, $loadCreateAmount - $receivingAmount);
		$remainingCredit = max(0.0, $assignedCreditLimit - $loadCreateAmount);

		return [
			'assigned_credit_limit' => $assignedCreditLimit,
			'used_amount'           => $usedAmount,
			'remaining_credit'      => $remainingCredit,
		];
	}
}

if (!function_exists('get_customer_display_remaining_credit')) {
	function get_customer_display_remaining_credit($customer)
	{
		if ($customer === null) {
			return 0.0;
		}

		if (is_numeric($customer)) {
			$customer = Customer::find($customer);
		}

		// If remaining_credit has ever been set (tracked), use it — even if it's 0.
		// Only fall back to assigned limit if remaining_credit is null (never been set).
		$remainingCredit = data_get($customer, 'remaining_credit');
		if ($remainingCredit !== null) {
			return normalize_customer_credit_value($remainingCredit);
		}

		$invoiceCreditLimit = normalize_customer_credit_value(data_get($customer, 'invoice_credit_limit', 0));
		$assignedCreditLimit = normalize_customer_credit_value(data_get($customer, 'adv_customer_credit_limit', 0));

		// Fall back only if remaining_credit was never set
		if ($assignedCreditLimit > 0) {
			return $assignedCreditLimit;
		}

		return $invoiceCreditLimit;
	}
}

if (!function_exists('get_customer_available_credit_limit')) {
	function get_customer_available_credit_limit($customer)
	{
		if ($customer === null) {
			return 0.0;
		}

		if (is_numeric($customer)) {
			$customer = Customer::find($customer);
		}

		if (!$customer) {
			return 0.0;
		}

		$creditLogs = json_decode($customer->remaining_credit_logs, true);
		if (!is_array($creditLogs) || count($creditLogs) === 0) {
			$creditLogs = json_decode($customer->credit_limit_log, true);
		}

		$assignedCredit = is_array($creditLogs)
			? array_sum(array_column($creditLogs, 'credit_limit'))
			: 0.0;

		if ($assignedCredit <= 0) {
			$assignedCredit = $customer->adv_customer_credit_limit ?: $customer->invoice_credit_limit;
		}

		$loadAmount = $customer->loads()
			->get(['shipper_load_final_rate', 'load_final_rate', 'shipper_load_other_charge'])
			->sum(function ($load) {
				$createdAmount = (float) ($load->shipper_load_final_rate ?: $load->load_final_rate ?: 0);
				$charges = json_decode($load->shipper_load_other_charge, true) ?: [];
				$hasInvoiceFlags = collect($charges)->contains(function ($charge) {
					return array_key_exists('for_invoice', $charge);
				});

				$invoiceCharges = collect($charges)->sum(function ($charge) use ($hasInvoiceFlags) {
					if ($hasInvoiceFlags && ($charge['for_invoice'] ?? 'off') !== 'on') {
						return 0;
					}

					if (!$hasInvoiceFlags && strtolower(trim($charge['type'] ?? '')) !== 'tyre') {
						return 0;
					}

					return (float) ($charge['amount'] ?? 0);
				});

				return max(0.0, $createdAmount - $invoiceCharges);
			});

		return normalize_customer_credit_value((float) $assignedCredit - $loadAmount);
	}
}

if (!function_exists('get_customers_available_credit_limits')) {
	function get_customers_available_credit_limits($customers)
	{
		$customers = collect($customers);
		$loadAmounts = Load::whereIn('customer_id', $customers->pluck('id')->filter())
			->get(['customer_id', 'shipper_load_final_rate', 'load_final_rate', 'shipper_load_other_charge'])
			->groupBy('customer_id')
			->map(function ($loads) {
				return $loads->sum(function ($load) {
					$createdAmount = (float) ($load->shipper_load_final_rate ?: $load->load_final_rate ?: 0);
					$charges = json_decode($load->shipper_load_other_charge ?? '', true) ?: [];
					$hasInvoiceFlags = collect($charges)->contains(fn ($charge) => array_key_exists('for_invoice', $charge));
					$invoiceCharges = collect($charges)->sum(function ($charge) use ($hasInvoiceFlags) {
						if ($hasInvoiceFlags && ($charge['for_invoice'] ?? 'off') !== 'on') {
							return 0;
						}
						return !$hasInvoiceFlags && strtolower(trim($charge['type'] ?? '')) !== 'tyre'
							? 0 : (float) ($charge['amount'] ?? 0);
					});
					return max(0.0, $createdAmount - $invoiceCharges);
				});
			});

		return $customers->mapWithKeys(function ($customer) use ($loadAmounts) {
			$creditLogs = json_decode($customer->remaining_credit_logs ?? '', true);
			if (!is_array($creditLogs) || count($creditLogs) === 0) {
				$creditLogs = json_decode($customer->credit_limit_log ?? '', true);
			}
			$assignedCredit = is_array($creditLogs) ? array_sum(array_column($creditLogs, 'credit_limit')) : 0;
			$assignedCredit = $assignedCredit ?: ($customer->adv_customer_credit_limit ?: $customer->invoice_credit_limit);
			return [$customer->id => normalize_customer_credit_value($assignedCredit - ($loadAmounts[$customer->id] ?? 0))];
		})->all();
	}
}

if (!function_exists('normalize_customer_credit_value')) {
	function normalize_customer_credit_value($value)
	{
		$normalized = is_numeric($value) ? (float) $value : 0.0;
		return max(0.0, round($normalized, 2));
	}
}

if (!function_exists('addToLog')) {

	function addToLog($customer_id = '', $load_id = '', $subject = '', $old_data = '', $new_data = '')
	{
		// Get the authenticated user
		$user = auth()->user();

		$log = [
			'load_id'     => $load_id,
			'customer_id' => $customer_id,
			'message'     => $subject,
			'user_name'   => $user ? $user->name : null,
			'user_id'     => $user ? $user->id : null,
			'user_email'  => $user ? $user->email : null,
			'old_json'    => $old_data,
			'new_json'    => $new_data,
			'ip'          => Request::ip(),
			'url'         => Request::fullUrl(),
		];

		Log::create($log);
	}
}

if (!function_exists('getdiffrance')) {

	function getdiffrance($old_data, $new_data)
	{
		$array1 = json_decode($old_data, true);
		$array2 = json_decode($new_data, true);
		$diff = [];

		$array1 = is_array($array1) ? $array1 : [];
		$array2 = is_array($array2) ? $array2 : [];

		if (!empty($array1) || !empty($array2)) {
			$allKeys = array_unique(array_merge(array_keys($array1), array_keys($array2)));
			foreach ($allKeys as $key) {
				if (in_array($key, ['_token', '_method'])) {
					continue;
				}

				$val1 = $array1[$key] ?? null;
				$val2 = $array2[$key] ?? null;
				if (!array_key_exists($key, $array1)) {
					$diff[$key] = ['olddata' => null, 'newdata' => $val2];
				} elseif (!array_key_exists($key, $array2)) {
					$diff[$key] = ['olddata' => $val1, 'newdata' => null];
				} elseif ($val1 !== $val2) {
					$diff[$key] = ['olddata' => $val1, 'newdata' => $val2];
				}
			}
		}

		$output = '';
		foreach ($diff as $key => $diffdata) {
			$rawOld = $diffdata['olddata'];
			$rawNew = $diffdata['newdata'];

			$oldDisplay = format_log_value($rawOld);
			$newDisplay = format_log_value($rawNew);
			if ($oldDisplay === $newDisplay) {
				continue;
			}

			$label = format_log_label($key);
			$output .= '<div class="mb-3 border-bottom pb-2">';
			$output .= '<div class="fw-semibold text-dark">' . e($label) . '</div>';
			$output .= '<div class="small text-muted mt-1"><span class="fw-semibold">From:</span> ' . e($oldDisplay) . '</div>';
			$output .= '<div class="small text-muted mt-1"><span class="fw-semibold">To:</span> ' . e($newDisplay) . '</div>';
			$output .= '</div>';
		}

		if (trim($output) === '') {
			return '<div class="text-muted">No details found.</div>';
		}

		return $output;
	}
}

if (!function_exists('format_log_label')) {

	function format_log_label($key)
	{
		$labels = [
			'load_workorder' => 'Work order number',
			'load_status' => 'Load status',
			'load_carrier' => 'Carrier',
			'load_bill_to' => 'Customer',
			'customer_id' => 'Customer',
			'load_id' => 'Load',
			'updated_at' => 'Last updated',
			'created_at' => 'Created',
		];

		if (isset($labels[$key])) {
			return $labels[$key];
		}

		$key = str_replace(['_', '-'], ' ', $key);
		$key = preg_replace('/\s+/', ' ', trim($key));
		$key = ucwords(strtolower($key));

		return $key;
	}
}

if (!function_exists('format_log_value')) {

	function format_log_value($value)
	{
		if ($value === null || $value === '') {
			return '—';
		}

		if (is_array($value)) {
			$items = [];
			foreach ($value as $item) {
				if (is_array($item) && isset($item['appointment'])) {
					$items[] = \Carbon\Carbon::parse($item['appointment'])->format('d-m-Y h:i A');
					continue;
				}

				if (is_array($item)) {
					foreach ($item as $key => $subValue) {
						if (is_string($subValue)) {
							$items[] = $subValue;
						}
					}
					continue;
				}

				if (is_string($item)) {
					$items[] = $item;
				}
			}

			if (!empty($items)) {
				return implode(', ', $items);
			}

			return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}

		if (is_string($value)) {
			if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $value)) {
				try {
					return \Carbon\Carbon::parse($value)->setTimezone('America/New_York')->format('m-d-Y h:i:s A T');
				} catch (\Exception $e) {
					return $value;
				}
			}

			return $value;
		}

		return (string) $value;
	}
}

if (!function_exists('format_activity_timestamp')) {

	function format_activity_timestamp($timestamp)
	{
		if (!$timestamp) {
			return 'Time not available';
		}

		return \Carbon\Carbon::parse($timestamp)
			->setTimezone('America/New_York')
			->format('m-d-Y h:i A T');
	}
}
if (!function_exists('render_pagination_links')) {

    function render_pagination_links($paginator)
    {
        if (!$paginator || !method_exists($paginator, 'hasPages') || !$paginator->hasPages()) {
            return '';
        }

		return $paginator->links('pagination::bootstrap-5')->toHtml();
    }
}

if (!function_exists('format_report_date')) {

	function format_report_date($date, $format = 'm/d/Y')
	{
		if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
			return '';
		}

		try {
			return \Carbon\Carbon::parse($date)->format($format);
		} catch (\Exception $e) {
			return '';
		}
	}
}

if (!function_exists('format_report_value')) {

	function format_report_value($value, $isCancelled = false)
	{
		if ($isCancelled) {
			return '-';
		}

		if ($value === null || $value === '') {
			return '';
		}

		return is_numeric($value) ? number_format((float) $value, 2) : $value;
	}
}
