<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Customer;
use App\Models\Consignee;
use App\Models\Shipper;
use App\Models\External;
use App\Models\Load;
use App\Models\User;
use App\Models\Office;
use App\Models\Factoring;
use App\Models\TeamLeader;
use App\Models\Manger;
use App\Models\CarrierVerification;
use App\Models\CustomerApprovalForm;
use App\Models\Cmt;
use App\Models\Log as activity_log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Country;
use App\Models\State;
use Dompdf\Dompdf;
use Dompdf\Options;
use PDF;
use Illuminate\Support\Facades\File;
use Illuminate\Pagination\Paginator;
use PhpOffice\PhpSpreadsheet\Spreadsheet; 
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class AccountController extends Controller
{

    public function account_manager()
    {
        return view('accounts.account_manager');
    }


    public function load_search_by_load(Request $request)
    {
		$q = $request->input('load_id');
        if (!empty($q)) {
            // Split the query by commas to get multiple terms
			$searchTerms = array_filter(
				preg_split('/[\s,]+/', $q),
				fn($term) => !empty($term = str_replace(' ', '', trim($term)))
			);
			


            if (count($searchTerms) > 0) {
				if ($request->ajax()) {
					
					if($request->input('target') == '#open'){
						 $open = Load::where('load_status','Open')->with(['user','customer','carrier', 'user.officedata'])->where(function($query) use ($searchTerms) {
								foreach ($searchTerms as $term) {
                                $query->orWhere('load_number', 'like', "%{$term}%");
									 
								}
							})->orderBy("loads.id", "desc")->paginate(100)->setPageName('open');
						
						return view('accounts.partials.accounting_open',compact('open'))->render();
						
					}else if($request->input('target') == '#completed'){
						 $complete = Load::where('load_status', 'Completed')
							->where(function ($query) {
								$query->where('invoice_status', '')
									->orWhereNull('invoice_status');
							})->where(function($query) use ($searchTerms) {
                                foreach ($searchTerms as $term) {
                                    $query->orWhere('load_number', 'like', "%{$term}%");
						 
                                }
							})
							->with(['user', 'customer', 'carrier', 'user.officedata'])
							->orderBy("loads.id", "desc")
							->paginate(100)->setPageName('complete');
							
						return view('accounts.partials.accounting_complete',compact('complete'))->render();
						
					}else if($request->input('target') == '#invoiced'){
						$invoiced = Load::where('invoice_status', 'Paid')
							->with(['user', 'customer', 'carrier', 'user.officedata'])
							->where(function($query) use ($searchTerms) {
								foreach ($searchTerms as $term) {
									$query->orWhere('load_number', 'like', "%{$term}%");
								}
							})
							->orderBy("loads.id", "desc")
							->paginate(100)
							->setPageName('invoiced');
						
						return view('accounts.partials.accounting_invoiced', compact('invoiced'))->render();
					}else if($request->input('target') == '#invoiced_paid'){
						$paid = Load::whereIn('invoice_status', ['Paid', 'Paid Record'])
							->where(function($query) use ($searchTerms) {
								foreach ($searchTerms as $term) {
									$query->orWhere('load_number', 'like', "%{$term}%");
								}
							})
							->with(['user', 'customer', 'carrier', 'user.officedata'])
							->orderBy("loads.id", "desc")
							->paginate(100)
							->setPageName('paid');
						
						return view('accounts.partials.accounting_paid', compact('paid'))->render();
					}
                
			}
            }
               
        } 
        return view('accounts.accounting',compact('open', 'complete', 'invoiced', 'paid'));
    }

    public function accounting(Request $request)
    {
        $activeTab = match (true) {
            $request->filled('complete') => 'completed',
            $request->filled('invoiced') => 'invoiced',
            $request->filled('paid') => 'invoiced_paid',
            default => 'open',
        };

        // 🔎 Custom search by load_numbers (comma separated)
        $searchNumbers = $request->input('load_numbers');
        $numbersArray = [];
        if (!empty($searchNumbers)) {
            $numbersArray = array_map('trim', explode(',', $searchNumbers));
        }

        // Open tab query
        $openQuery = Load::where('load_status', 'Open')
            ->with(['user', 'customer', 'carrier', 'user.officedata'])
            ->orderBy("loads.id", "desc");

        if (!empty($numbersArray)) {
            $openQuery->whereIn('load_number', $numbersArray);
        }

        $open = $openQuery->paginate(50, ['*'], 'open');

        // Completed tab query
        $completeQuery = Load::where('load_status', 'Completed')
            ->where(function ($query) {
                $query->where('invoice_status', '')
                    ->orWhereNull('invoice_status');
            })
            ->with(['user', 'customer', 'carrier', 'user.officedata'])
            ->orderBy("loads.id", "desc");

        if (!empty($numbersArray)) {
            $completeQuery->whereIn('load_number', $numbersArray);
        }

        $complete = $completeQuery->paginate(50, ['*'], 'complete');

        // Invoiced tab query
        $invoicedQuery = Load::where('invoice_status', 'Paid')
            ->with(['user', 'customer', 'carrier', 'user.officedata'])
            ->orderBy("loads.id", "desc");

        if (!empty($numbersArray)) {
            $invoicedQuery->whereIn('load_number', $numbersArray);
        }

        $invoiced = $invoicedQuery->paginate(50, ['*'], 'invoiced');

        // Paid tab query
        $paidQuery = Load::whereIn('invoice_status', ['Paid', 'Paid Record'])
            ->with(['user', 'customer', 'carrier', 'user.officedata'])
            ->orderBy("loads.id", "desc");

        if (!empty($numbersArray)) {
            $paidQuery->whereIn('load_number', $numbersArray);
        }

        $paid = $paidQuery->paginate(50, ['*'], 'paid');

        // Handle AJAX pagination requests with the selected tab's pagination links.
        if ($request->ajax()) {
            $tabConfig = [
                '#open' => ['view' => 'accounts.partials.accounting_open', 'paginator' => $open],
                '#completed' => ['view' => 'accounts.partials.accounting_complete', 'paginator' => $complete],
                '#invoiced' => ['view' => 'accounts.partials.accounting_invoiced', 'paginator' => $invoiced],
                '#invoiced_paid' => ['view' => 'accounts.partials.accounting_paid', 'paginator' => $paid],
            ];

            $config = $tabConfig[$request->input('tab')] ?? $tabConfig['#open'];
            $html = view($config['view'], compact('open', 'complete', 'invoiced', 'paid'))->render();

            return response()->json([
                'html' => $html,
                'pagination' => render_pagination_links($config['paginator']),
            ]);
        }

        return view('accounts.accounting', compact('open', 'complete', 'invoiced', 'paid', 'activeTab'));
    }

	
	public function accountingCompletedPublicDoc(Request $request, $id)
    {
        
        $complete = Load::where('id', $id)->first();

        return view('accounts.partials.completed-public-doc',compact('complete'));
    }


    // public function loadspi(Request $request){
   
    //     $loadnumbers = $request->input('load_numbers');

    //     // Ensure we trim and sanitize input in case of spaces or unexpected characters
    //     $arr_loads = array_filter(array_map('trim', explode(',', $loadnumbers)));
        
    //     $invoice = [];
    //     $total_amount = 0;
	// 	$pre_advance_amount = 0;
        
    //     foreach ($arr_loads as $loadId) {
    //         // Consider using find instead of findOrFail to avoid full stop on error
    //         $loaddata = Load::find($loadId);
    //         $customer = Customer::find($loaddata->customer_id);
    //         if ($loaddata) {
    //             $invoice[] = [
    //                 'load_number' => $loaddata->load_number,
    //                 'total' => $loaddata->shipper_load_final_rate,
    //                 'load_workorder' => $loaddata->load_workorder,
    //             ];
    //             $total_amount += $loaddata->shipper_load_final_rate;
	// 			$pre_advance_amount += $loaddata->pre_advance;
    //         }
    //     }
		
		

    //     $subject = "Genrate the Load Invoice PI";
    //     $newData = json_encode($invoice, true);
       
       
        
    //     return view('invoices_pi', compact('invoice', 'total_amount', 'customer', 'loadnumbers', 'pre_advance_amount'));

    // }

public function loadspi(Request $request)
{
    $loadnumbers = $request->input('load_numbers');

    // Trim and sanitize input
    $arr_loads = array_filter(array_map('trim', explode(',', $loadnumbers)));

    $invoice = [];
    $total_amount = 0;
    $pre_advance_amount = 0;
    $customer = null;

    foreach ($arr_loads as $loadId) {
        $loaddata = Load::find($loadId);

        if ($loaddata) {
            $customer = Customer::find($loaddata->customer_id);

            $final_rate = (float) $loaddata->shipper_load_final_rate;
            $pre_advance = (float) $loaddata->pre_advance;

            $invoice[] = [
                'load_number' => $loaddata->load_number,
                'total' => $final_rate,
                'load_workorder' => $loaddata->load_workorder,
            ];

            $total_amount += $final_rate;
            $pre_advance_amount += $pre_advance;
        }
    }

   if (empty($invoice)) {
        return response("<script>alert('Load Number Not Found'); window.history.back();</script>");
    }

    return view('invoices_pi', compact('invoice', 'total_amount', 'customer', 'loadnumbers', 'pre_advance_amount'));
}



    public function loadsMultipalInvoice(Request $request){
   
        $loadnumbers = $request->input('load_numbers');

        // Ensure we trim and sanitize input in case of spaces or unexpected characters
        $arr_loads = array_filter(array_map('trim', explode(',', $loadnumbers)));
        
        $invoice = [];
        $total_amount = 0;
        
        foreach ($arr_loads as $loadId) {
            // Consider using find instead of findOrFail to avoid full stop on error
            $loaddata = Load::find($loadId);
            $customer = Customer::find($loaddata->customer_id);
            if ($loaddata) {
                $invoice[] = [
                    'load_number' => $loaddata->load_number,
                    'total' => $loaddata->shipper_load_final_rate,
                    'load_workorder' => $loaddata->load_workorder,
                ];
                $total_amount += $loaddata->shipper_load_final_rate;
            }
        }

        $subject = "Genrate the Load Multipal Invoice";
        $newData = json_encode($invoice, true);
       
        return view('multipal_invoices', compact('invoice', 'total_amount', 'customer', 'loadnumbers'));

    }

    public function compliance(Request $request)
    {
		$activeTab = $request->filled('cpr') ? 'cpr' : ($request->filled('block_carrier') ? 'block_carrier' : 'mc');
        $carriers = External::with('user')->orderBy("id", "desc")->select('id','carrier_mc_ff_input','carrier_dot','carrier_name','user_id','created_at','mc_check','carrier_file_upload','doc_upload')->paginate(50, ['*'], 'mc');
		$loads = Load::with(['user'])->orderBy("loads.id", "desc")->paginate(50, ['*'], 'cpr');
		$carrier_blocked = External::with('user')->where('carrier_block', 'Blocked')->paginate(50, ['*'], 'block_carrier');
		
		if ($request->ajax()) {
			
            $config = match ($request->input('tab')) {
                '#cpr' => ['view' => 'accounts.partials.compliance_cpr_table', 'paginator' => $loads],
                '#block_carrier' => ['view' => 'accounts.partials.compliance_block_carrier_table', 'paginator' => $carrier_blocked],
                default => ['view' => 'accounts.partials.compliance_mc_table', 'paginator' => $carriers],
            };
            $html = view($config['view'], compact('carriers', 'loads', 'carrier_blocked'))->render();
            return response()->json(['html' => $html, 'pagination' => render_pagination_links($config['paginator'])]);
			
		}
			
        return view('accounts.compliance', compact('carriers', 'loads','carrier_blocked', 'activeTab'));
    }


public function carrier_block(Request $request)
{
    $carrierId = $request->input('carrier_id');
    $isBlocked = $request->input('is_blocked');

    $carrier = External::find($carrierId);

    if ($carrier) {

        $oldStatus = $carrier->carrier_block ?? 'Unblocked';        
        $newStatus = $isBlocked ? 'Blocked' : 'Unblocked';          

        $carrier->carrier_block = $newStatus;
        $carrier->save();

        // Prepare log message
        $userName = Auth::user()->name ?? 'Unknown User';
        $subject = "Carrier Name {$carrier->carrier_name} was {$newStatus} by {$userName}";

        // Log the change
        addToLog(
            $customerId = '',
            $loadId = '',
            $subject,
            $oldData = $oldStatus,
            $newData = $newStatus
        );

        return response()->json([
            'status' => 'success',
            'message' => "Carrier {$newStatus} successfully.",
            'value' => $newStatus
        ]);
    }

    return response()->json([
        'status' => 'error',
        'message' => 'Carrier not found.'
    ], 404);
}



    public function reporting(Request $request)
    {
		
		if ($request->ajax()) {

			$tab = $request->input('tab');

			// Carrier Tab
			if ($tab == '#carrier') {
				$totalRevenueloadcarrier = Load::join('users', 'loads.user_id', '=', 'users.id')
					->select('loads.load_carrier', 'users.name as user_name')
					->selectRaw('SUM(loads.load_final_carrier_fee) AS total_revenue')
					->selectRaw('SUM(loads.load_final_carrier_fee - loads.load_carrier_fee) AS revenue_difference')
					->selectRaw('COUNT(loads.id) AS load_count')
					->selectRaw('SUM(CASE WHEN loads.load_status = "Open" THEN 1 ELSE 0 END) AS open_load_count')
					->selectRaw('SUM(CASE WHEN loads.load_status = "Delivered" THEN 1 ELSE 0 END) AS delivered_load_count')
					->selectRaw('SUM(CASE WHEN loads.invoice_status = "Completed" THEN 1 ELSE 0 END) AS completed_load_count')
					->groupBy('loads.load_carrier', 'users.name')
					->paginate(50, ['*'], 'carrier');

				return view('accounts.reporting.carrier', compact('totalRevenueloadcarrier'))->render();
			}

			// Customer Tab
			if ($tab == '#customer') {
                $totalRevenueCustomer = $this->customerReportQuery()
                    ->paginate(50, ['*'], 'customer');

				return view('accounts.reporting.customers', compact('totalRevenueCustomer'))->render();
			}

			// Customer Detail Tab
			if ($tab == '#customer_detail') {
				$get_customers = Customer::paginate(50, ['*'], 'get_customers');
				return view('accounts.reporting.customer_details', compact('get_customers'))->render();
			}

			// Dispatcher Tab
			if ($tab == '#dispatcher') {
				$totalRevenueCarrier = Load::join('users', 'loads.user_id', '=', 'users.id')
					->select('users.name')
					->selectRaw('SUM(loads.shipper_load_final_rate) AS total_revenue')
					->selectRaw('SUM(loads.load_final_carrier_fee) AS total_carrier_fee')
					->selectRaw('SUM(loads.shipper_load_final_rate - loads.load_final_carrier_fee) AS revenue_difference')
					->selectRaw('COUNT(loads.id) AS load_count')
					->selectRaw('SUM(CASE WHEN loads.load_status = "Open" THEN 1 ELSE 0 END) AS open_load_count')
					->selectRaw('SUM(CASE WHEN loads.load_status = "Delivered" THEN 1 ELSE 0 END) AS delivered_load_count')
					->selectRaw('SUM(CASE WHEN loads.invoice_status = "Paid" THEN 1 ELSE 0 END) AS invoiced_load_count')
					->selectRaw('SUM(loads.load_final_carrier_fee) AS sum_load_final_carrier_fee')
					->groupBy('users.name')
					->paginate(50, ['*'], 'dispatcher');

				return view('accounts.reporting.dispatchers', compact('totalRevenueCarrier'))->render();
			}

			// Load Tab
			if ($tab == '#load') {
				$dashboard = Load::with('user')->paginate(50, ['*'], 'load');
				return view('accounts.reporting.load', compact('dashboard'))->render();
			}

			// Sales Rep Tab
			if ($tab == '#sales_rep') {
				$totalRevenueBroker = Load::join('users', 'loads.user_id', '=', 'users.id')
					->select('users.name')
					->selectRaw('SUM(loads.load_shipper_rate) AS total_revenue')
					->selectRaw('SUM(loads.load_carrier_fee) AS total_carrier_fee')
					->selectRaw('SUM(loads.load_shipper_rate - loads.load_carrier_fee) AS revenue_difference')
					->selectRaw('COUNT(loads.id) AS load_count')
					->selectRaw('SUM(CASE WHEN loads.load_status = "Open" THEN 1 ELSE 0 END) AS open_load_count')
					->groupBy('users.name')
					->paginate(50, ['*'], 'sales_rep');

				return view('accounts.reporting.sales_reps', compact('totalRevenueBroker'))->render();
			}

			// Load Completed Log Tab
			if ($tab == '#load_completed_log') {
                $dashboard_logs = Load::with('user')
                    ->where('load_status', 'Completed')
                    ->latest('created_at')
                    ->simplePaginate(50, ['*'], 'logs');
				return view('accounts.reporting.load_completed_logs', compact('dashboard_logs'))->render();
			}

			// Aging Tab
			if ($tab == '#aging') {
				$customersData = Customer::paginate(50, ['*'], 'limits');
				return view('accounts.reporting.aging', compact('customersData'))->render();
			}
		}
	
	
        $totalRevenueloadcarrier = Load::join('users', 'loads.user_id', '=', 'users.id')
        ->select('loads.load_carrier', 'users.name as user_name')
        ->selectRaw('SUM(loads.load_final_carrier_fee) AS total_revenue')
        ->selectRaw('SUM(loads.load_final_carrier_fee - loads.load_carrier_fee) AS revenue_difference')
        ->selectRaw('COUNT(loads.id) AS load_count')
        ->selectRaw('SUM(CASE WHEN loads.load_status = "Open" THEN 1 ELSE 0 END) AS open_load_count')
        ->selectRaw('SUM(CASE WHEN loads.load_status = "Delivered" THEN 1 ELSE 0 END) AS delivered_load_count')
        ->selectRaw('SUM(CASE WHEN loads.invoice_status = "Completed" THEN 1 ELSE 0 END) AS completed_load_count')
        ->groupBy('loads.load_carrier', 'users.name')
        ->paginate(50, ['*'], 'carrier'); 

        $totalRevenueCustomer = $this->customerReportQuery()
        ->paginate(50, ['*'], 'customer');

        $totalRevenueCarrier = Load::join('users', 'loads.user_id', '=', 'users.id')
        ->select('users.name')
        ->selectRaw('SUM(loads.shipper_load_final_rate) AS total_revenue')
        ->selectRaw('SUM(loads.load_final_carrier_fee) AS total_carrier_fee')
        ->selectRaw('SUM(loads.shipper_load_final_rate - loads.load_final_carrier_fee) AS revenue_difference')
        ->selectRaw('COUNT(loads.id) AS load_count')
        ->selectRaw('SUM(CASE WHEN loads.load_status = "Open" THEN 1 ELSE 0 END) AS open_load_count')
        ->selectRaw('SUM(CASE WHEN loads.load_status = "Delivered" THEN 1 ELSE 0 END) AS delivered_load_count')
        ->selectRaw('SUM(CASE WHEN loads.invoice_status = "Paid" THEN 1 ELSE 0 END) AS invoiced_load_count')
        ->selectRaw('SUM(loads.load_final_carrier_fee) AS sum_load_final_carrier_fee')
        ->groupBy('users.name')
        ->paginate(50, ['*'], 'dispatcher');

        $totalRevenueBroker = Load::join('users', 'loads.user_id', '=', 'users.id')
        ->select('users.name')
        ->selectRaw('SUM(loads.load_shipper_rate) AS total_revenue')
        ->selectRaw('SUM(loads.load_carrier_fee) AS total_carrier_fee')
        ->selectRaw('SUM(loads.load_shipper_rate - loads.load_carrier_fee) AS revenue_difference')
        ->selectRaw('COUNT(loads.id) AS load_count')
        ->selectRaw('SUM(CASE WHEN loads.load_status = "Open" THEN 1 ELSE 0 END) AS open_load_count')
        ->groupBy('users.name')
        ->paginate(50, ['*'], 'sales_rep');
    

        $get_customers = Customer::paginate(50, ['*'], 'get_customers');
		$customersData = Customer::paginate(50, ['*'], 'limits');

        $dashboard = Load::with('user')->paginate(50, ['*'], 'load');
		$dashboard_logs = Load::with('user')->paginate(50, ['*'], 'logs');
        $revenueResult = Load::selectRaw("SUM(shipper_load_final_rate) AS total_revenue")->first();

        $revenue = $revenueResult->total_revenue ?? 0;

        $revenueValue = $revenueResult->total_revenue ?? 0; // Default to 0 if null
		$totalCarrierFee = Load::selectRaw("SUM(load_final_carrier_fee) AS total_revenue")->first();
		
        $carrierFeeValue = $totalCarrierFee->total_revenue ?? 0; // Default to 0 if null

        $finalTotal = $revenueValue - $carrierFeeValue;
        $yesterdayStart = Carbon::yesterday()->startOfDay();
        $yesterdayEnd = Carbon::yesterday()->endOfDay();

        $loadCount = Load::whereBetween('created_at', [$yesterdayStart, $yesterdayEnd])->count();
		
        $newCoustmerAdded = Customer::where('status', 'Approved')->count();

        $startDate = isset($_GET['startdate']) 
    ? Carbon::parse($_GET['startdate'])->startOfDay() 
    : Carbon::now()->subDays(7)->startOfDay();

		$endDate = isset($_GET['enddate']) 
			? Carbon::parse($_GET['enddate'])->endOfDay() 
			: Carbon::now()->endOfDay();

		// 2. Build query
	$query = DB::table('loads')
    ->join('users', 'loads.user_id', '=', 'users.id') // join with users table
    ->select(
        DB::raw('DATE(loads.created_at) as date'),
        DB::raw('SUM(shipper_load_final_rate) as total_shipper_rate'),
        DB::raw('SUM(load_final_carrier_fee) as total_carrier_fee'),
        DB::raw('SUM(shipper_load_final_rate - load_final_carrier_fee) as margin')
    )
    //->where('loads.cpr_check', '=', 'Verified') 
    ->whereBetween('loads.created_at', [$startDate, $endDate]);

	// Apply agent filter if provided
	if (!empty($_GET['agent'])) {
		$query->where('loads.user_id', $_GET['agent']);
	}

	// Apply office filter if provided
	if (!empty($_GET['office'])) {
		$query->where('users.office', $_GET['office']);
	}

	// Finalize and fetch results
	$salesData = $query
		->groupBy(DB::raw('DATE(loads.created_at)'))
		->orderBy('date', 'DESC')
		->get()
		->sortBy('date') // optional ascending sort
		->values();
		
		$agents = User::count();
		$count = Load::count();
        // Format data
        $labels = [];
        $shipperRates = [];
        $carrierFees = [];
		$margin = [];
		
		$office = Office::get();
		
		if (!empty($_GET['office'])) {
			$agents_data = User::where('role_id', 21)->where('office', $_GET['office'])->get();
		}else{
			$agents_data = User::where('role_id', 21)->get();
		}
		

        foreach ($salesData as $day) {
            $labels[] = $day->date;
            $shipperRates[] = round($day->total_shipper_rate, 2);
            $carrierFees[] = round($day->total_carrier_fee, 2);
			$margin[] = round($day->margin, 2);
        }
	
		$topMaximumLoadCustomers = Load::select('load_bill_to', DB::raw('COUNT(*) AS load_count'))
				->groupBy('load_bill_to')
				->orderByDesc('load_count')
				->limit(5)
				->get();
		
        return view('accounts.reporting',compact('dashboard_logs', 'topMaximumLoadCustomers', 'count','agents', 'customersData', 'totalRevenueloadcarrier','totalRevenueCustomer','get_customers','totalRevenueCarrier','dashboard','totalRevenueBroker','revenue','finalTotal','loadCount','newCoustmerAdded','labels', 'shipperRates', 'carrierFees','margin','office', 'agents_data'));
    }
	public function getByOfficereporting($officeId)
	{
	
		$agents = User::where('office', $officeId)->where('role_id', 21)->get(['id', 'name']);

		return response()->json([
			'agents' => $agents,
		]);
	}
	
	public function credit(Request $request)
    {
        $dashboard_logs = Load::with('user')->paginate(50, ['*'], 'logs');

		
		$sortedCustomers = Customer::paginate(50, ['*'], 'limits');

		
		if ($request->ajax()) {
			
			if($request->input('tab') == '#limit'){
				
				return view('accounts.reporting.limit',compact('sortedCustomers'))->render();
				
			}else if($request->input('tab') == '#load_completed_log'){
				
				return view('accounts.reporting.load_completed_logs',compact('dashboard_logs'))->render();
				
			}
			
		}

        return view('accounts.credit',compact('sortedCustomers','dashboard_logs'));
    }

    public function vendor_system(Request $request)
    {
        $vendormanagement = Load::with(['user', 'customer'])
        ->orderBy("loads.id", "desc")
        ->paginate(100);

    // echo "<pre>"; print_r($vendormanagement); die;
		 
		 if ($request->ajax()) {
                return response()->json([
                    'html' => view('accounts.partials.vendor_system_table', compact('vendormanagement'))->render(),
                    'modals' => view('accounts.partials.vendor_system_modals', compact('vendormanagement'))->render(),
                    'pagination' => render_pagination_links($vendormanagement),
                ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
			}
        return response()
            ->view('accounts.vendor_system', compact('vendormanagement'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function vendorSystemExcel()
    {
        $loads = Load::with(['user', 'customer'])
            ->orderByDesc('loads.id')
            ->get();

        $formatDate = static function ($value): string {
            if (empty($value) || $value === '0000-00-00') {
                return '';
            }

            try {
                return Carbon::parse($value)->format('m/d/Y');
            } catch (\Throwable) {
                return (string) $value;
            }
        };

        $headers = [
            'Sr No.', 'Load#', 'W/O #', 'Carrier', 'Carrier Invoice Date',
            'Carrier Due Date', 'Ready to Pay', 'Processed By', 'Documents',
            'Quick Pay %', 'Carrier Files Upload', 'Carrier Files View',
            'Payment Method', 'Carrier Payment Status', 'Carrier Payment Date',
            'Customer Invoice Date', 'Logs Check', 'Vendor Internal Notes',
        ];

        $rows = [$headers];
        foreach ($loads as $index => $load) {
            $carrierFiles = json_decode($load->carrierDoc ?? '', true);
            $hasCarrierFiles = is_array($carrierFiles) && $carrierFiles !== [];
            $processedBy = $load->invoice_through;
            $dueDate = $load->load_carrier_due_date;
            if (empty($dueDate) && !empty($load->carrier_invoice_date)) {
                try {
                    $dueDate = Carbon::parse($load->carrier_invoice_date)->addDays(25);
                } catch (\Throwable) {
                    $dueDate = null;
                }
            }

            $rows[] = [
                $index + 1,
                $load->load_number ?? '',
                $load->load_workorder ?? '',
                $load->load_carrier ?? '',
                $formatDate($load->carrier_invoice_date),
                $formatDate($dueDate),
                $load->ready_to_pay ?? '',
                $processedBy ?? '',
                $load->carrier_documents ?? '',
                $load->quick_pay ?? '',
                $hasCarrierFiles ? 'Yes' : 'No',
                $hasCarrierFiles ? 'Available' : 'Not available',
                $load->payment_method ?? '',
                $load->carrier_mark_as_paid ?? '',
                $formatDate($load->load_carrier_due_date_on),
                $formatDate($load->invoice_date ?: $load->invoice_status_date),
                $load->cpr_check ?? '',
                $load->vendorInternalNotes ?? '',
            ];
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Vendor System');
        $sheet->fromArray($rows, null, 'A1');
        $sheet->getStyle('A1:R1')->getFont()->setBold(true);
        $sheet->getStyle('A1:R1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        foreach (range('A', 'R') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'vendor-system-' . date('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($writer): void {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function vendor_search(Request $request){

        $q = $request->input('query');

		if (!empty($q)) {
			$searchTerms = array_filter(explode(',', $q), function($term) {
				return !empty(trim($term));
			});

			if (count($searchTerms) > 0) {
                $vendormanagement = Load::with(['user', 'customer'])
					->where(function($query) use ($searchTerms) {
						foreach ($searchTerms as $term) {
							$query->orWhere('load_number', 'like', "%$term%")
								  ->orWhere('load_workorder', 'like', "%$term%")
								  ->orWhere('customer_refrence_number', 'like', "%$term%")
								  ->orWhere('load_bill_to', 'like', "%$term%")
								  ->orWhere('invoice_number', 'like', "%$term%")
								  ->orWhere('load_dispatcher', 'like', "%$term%")
                                  ->orWhere('load_carrier', 'like', "%$term%");
						}
					})
					->orderBy('loads.id', 'desc')
					->paginate(50);
			} else {
				$vendormanagement = collect();
			}
		} else {
            $vendormanagement = Load::with(['user', 'customer'])
				->orderBy('loads.id', 'desc')
				->paginate(50);
		}

        $vendormanagement->appends($request->query());

		// Render the table rows
		$rowsHtml = view('accounts.partials.vendor_system_table', compact('vendormanagement'))->render();

		// Render the modals
		$modalsHtml = view('accounts.partials.vendor_system_modals', compact('vendormanagement'))->render();

		// Return JSON
        return response()->json([
			'rows' => $rowsHtml,
			'modals' => $modalsHtml,
            'pagination' => render_pagination_links($vendormanagement),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
		
    }


public function carrier_search(Request $request)
{
    $q = trim($request->input('query'));

    $loads = Load::query()
        ->leftJoin(
            'carrier_verification',
            'carrier_verification.load_id',
            '=',
            'loads.id'
        )
        ->select('loads.*')
        ->distinct();

    if (!empty($q)) {

        $searchTerms = array_filter(
            array_map('trim', explode(',', strtolower($q)))
        );

        $loads->where(function ($mainQuery) use ($searchTerms) {

            foreach ($searchTerms as $term) {

                // Normalize status keywords
                $statusMap = [
                    'verified'     => 'Verified',
                    'not verified' => 'Not Verified',
                    'unverified'   => 'Not Verified',
                    'pending'      => 'Pending',
                ];

                $mainQuery->where(function ($q) use ($term, $statusMap) {

                    /* -----------------------
                       1️⃣ SEARCH LOADS TABLE
                    ----------------------- */
                    $q->where('loads.load_number', 'like', "%{$term}%")
                      ->orWhere('loads.load_dispatcher', 'like', "%{$term}%")
                      ->orWhere('loads.load_carrier', 'like', "%{$term}%");

                    /* -----------------------
                       2️⃣ FALLBACK: STATUS SEARCH
                    ----------------------- */
                    if (array_key_exists($term, $statusMap)) {
                        $q->orWhere(
                            'carrier_verification.verification_factoring',
                            $statusMap[$term]
                        );
                    }
                });
            }
        });
    }

    $loads = $loads
        ->orderBy('loads.id', 'desc')
        ->paginate(50);

    return response()->json([
        'rows'   => view('accounts.partials.carrier_verification_table', compact('loads'))->render(),
        'modals' => view('accounts.partials.carrier_verification_table', compact('loads'))->render(),
    ]);
}



    public function compliance_search_cpr(Request $request){

        $q = $request->input('query');
        if (!empty($q)) {
            // Split the query by commas to get multiple terms
            $searchTerms = array_filter(explode(',', $q), function($term) {
                return !empty(trim($term)); // Only keep non-empty terms
            });

            if (count($searchTerms) > 0) {
                // Search for non-empty terms with 'orWhere'
                $loads = Load::with(['user'])
                    ->where(function($query) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $query->orWhere('load_number', 'like', "%$term%");
                            $query->orwhere('load_workorder', 'like', "%$term%");
                            $query->orwhere('customer_refrence_number', 'like', "%$term%");
                        }
                    })
                    ->orderBy('loads.id', 'desc')
                    ->get();
            } else {
                // If no valid terms, return an empty collection or handle accordingly
                $loads = collect();
            }
        } else {
            // If query is empty, return a paginated result without any filter
            $loads = Load::with(['user'])
                ->orderBy('loads.id', 'desc')
                ->paginate(50);
        }
        
        return view('accounts.partials.compliance_cpr_table', compact('loads'))->render();
    }

    public function compliance_search_mc(Request $request){

        $q = $request->input('query');
        if (!empty($q)) {
            // Split the query by commas to get multiple terms
            $searchTerms = array_filter(explode(',', $q), function($term) {
                return !empty(trim($term)); // Only keep non-empty terms
            });

            if (count($searchTerms) > 0) { 
                // Search for non-empty terms with 'orWhere'

                $carriers = External::with(['user'])
                    ->where(function($query) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
							$query->orWhere('carrier_mc_ff_input', "$term");
                            $query->orWhere('carrier_dot', "$term");
                            $query->orwhere('carrier_name', "$term");
                        }
                    })
                    ->orderBy('id', 'desc')
                    ->get();
            } else {
                // If no valid terms, return an empty collection or handle accordingly
                $carriers = collect();
            }
        } else {
            // If query is empty, return a paginated result without any filter
            $carriers = External::with(['user'])
                ->orderBy('id', 'desc')
                ->paginate(50);
        }
        
        return view('accounts.partials.compliance_mc_table', compact('carriers'))->render();
    }


public function mc_check(Request $request)
{
    $load = External::find($request->carrier_id);

    if (!$load) {
        return response()->json([
            'success' => false,
            'message' => 'MC not found.'
        ], 404);
    }

    $oldStatus = $load->mc_check;
    $newStatus = $request->mc_check ?? 'Not Approved';

    if ($newStatus === 'Approved as Exception') {

        $note = trim($request->compliance_exception_note ?? '');

        if ($note === '') {
            return response()->json([
                'success' => false,
                'message' => 'Please enter an exception note.'
            ], 422);
        }

        $username = Auth::user()->name;

        /*
         * Remove existing username prefix
         * before adding current username.
         */
        $note = preg_replace(
            '/^[^:]+:\s*/',
            '',
            $note
        );

        $load->compliance_exception_note =
            $username . ' : ' . $note;

    } else {

        $load->compliance_exception_note = null;
        // $load->mc_check_date = null;
    }

if ($oldStatus !== $newStatus) {

    $carrierName = $load->company_name
        ?? $load->carrier_name
        ?? 'N/A';

    $mcNumber = $load->carrier_mc_ff_input ?? 'N/A';

    $subject =
        'Changed Carrier MC Compliance Status | ' .
        'Carrier: "' . $carrierName . '" | ' .
        'MC Number: "' . $mcNumber . '" | ' .
        'Status: "' . ($oldStatus ?: 'Not Set') . '" → "' . $newStatus . '"';

    $newData =
        'Carrier: ' . $carrierName . "\n" .
        'MC Number: ' . $mcNumber . "\n" .
        'Previous Status: ' . ($oldStatus ?: 'Not Set') . "\n" .
        'New Status: ' . $newStatus;

    if ($newStatus === 'Approved as Exception') {

        $newData .= "\n" .
            'Exception Note: ' .
            ($load->compliance_exception_note ?? 'N/A');
    }

    addToLog(
        '',
        '',
        $subject,
        '',
        $newData
    );

    }

    $load->mc_check = $newStatus;

    $load->save();

    return response()->json([
        'success' => true,
        'message' => 'MC checks updated successfully.',
        'exception_note' =>
            $load->compliance_exception_note
    ]);
}
	
	public function mc_setup(Request $request){

        $load = External::find($request->setup_id);

        if ($load) {

            $subject = "Change the carrier MC setup status $load->setup to $request->mc_setup";
            addToLog($customerId ='', $loadId ='', $subject, $oldData ='', $newData ='');

            $load->setup = $request->mc_setup ?? '';
            $load->save();

            return response()->json(['success' => true, 'message' => 'MC Setup updated successfully.']);
        } else {
            return response()->json(['success' => false, 'message' => 'MC Setup not found.'], 404);
        }
    }


public function cpr_check(Request $request)
{
    $load = Load::find($request->load_id);

    if (!$load) {
        return response()->json([
            'success' => false,
            'message' => 'Load not found.'
        ], 404);
    }

    $oldStatus = $load->cpr_check;
    $oldNote   = $load->cpr_exception_note;

    $newStatus = $request->cpr_check;


    /*
     * Approved as Exception
     */
    if ($newStatus === 'Approved as Exception') {

        $newNote = trim(
            $request->cpr_exception_note ?? ''
        );

        if ($newNote === '') {

            return response()->json([
                'success' => false,
                'message' => 'Please enter an exception note.'
            ], 422);
        }

    } else {

        /*
         * Clear exception note
         */
        $newNote = null;
    }


    /*
     * Save
     */
    $load->cpr_check = $newStatus;

    $load->cpr_exception_note = $newNote;

    $load->save();


    /*
     * Log status change
     */
    if ($oldStatus !== $newStatus) {

        $subject =
            'Change the Load CPR status "' .
            ($oldStatus ?: 'Not Set') .
            '" to "' .
            $newStatus .
            '"';

        $newData =
            'Load ID: ' . $load->id . "\n" .
            'Previous CPR Status: ' .
            ($oldStatus ?: 'Not Set') . "\n" .
            'New CPR Status: ' .
            $newStatus;

        if ($newStatus === 'Approved as Exception') {

            $newData .=
                "\nCPR Exception Note: " .
                $newNote;
        }

        addToLog(
            '',
            $request->load_id,
            $subject,
            '',
            $newData
        );
    }


    /*
     * Log note change
     */
    elseif (
        $newStatus === 'Approved as Exception' &&
        $oldNote !== $newNote
    ) {

        $subject =
            'Updated Load CPR Exception Note';

        $newData =
            'Load ID: ' . $load->id . "\n" .
            'CPR Status: ' . $newStatus . "\n" .
            'Previous Note: ' .
            ($oldNote ?: 'N/A') . "\n" .
            'New Note: ' .
            $newNote;

        addToLog(
            '',
            $request->load_id,
            $subject,
            '',
            $newData
        );
    }


    return response()->json([
        'success' => true,
        'message' => 'CPR checks updated successfully.',
        'cpr_check' => $load->cpr_check,
        'cpr_exception_note' => $load->cpr_exception_note
    ]);
}

    public function macro(Request $request){

        $load = Load::find($request->load_id);

        if ($load) {

            $subject = "Change the Load macro $load->macro to $request->macro";
            addToLog($customerId ='', $request->load_id, $subject, $oldData ='', $newData ='');

            $load->macro = $request->macro;
            $load->save();
            return response()->json(['success' => true, 'message' => 'Macro updated successfully.']);
        } else {
            return response()->json(['success' => false, 'message' => 'Load not found.'], 404);
        }
    }

public function no_of_macro(Request $request)
{
    $request->validate([
        'load_id' => 'required|exists:loads,id',
        'no_of_macro' => 'required|integer|between:0,10',
    ]);

    $load = Load::find($request->load_id);

    $subject = "Change the Load no of macro {$load->no_of_macro} to {$request->no_of_macro}";
    addToLog('', $request->load_id, $subject, '', '');

    $load->no_of_macro = $request->no_of_macro;
    $load->save();

    return response()->json([
        'success' => true,
        'message' => 'No Of Macro updated successfully.'
    ]);
}

    public function quick_pay(Request $request){

        $load = Load::find($request->load_id);

        if ($load) {

            $subject = "Change the Load Carrier Payment status  $load->quick_pay to $request->quick_pay";
            addToLog($customerId ='', $request->load_id, $subject, $oldData ='', $newData ='');

            $load->quick_pay = $request->quick_pay;
            $load->save();
            return response()->json(['success' => true, 'message' => 'Quick Pay updated successfully.']);
        } else {
            return response()->json(['success' => false, 'message' => 'Load not found.'], 404);
        }
    }

    public function payment_method(Request $request){

        $load = Load::find($request->load_id);

        if ($load) {

            $subject = "Change the Load payment method  $load->payment_method to $request->payment_method";
            addToLog($customerId ='', $request->load_id, $subject, $oldData ='', $newData ='');

            $load->payment_method = $request->payment_method;
            $load->save();
            return response()->json(['success' => true, 'message' => 'Payment Method updated successfully.']);
        } else {
            return response()->json(['success' => false, 'message' => 'Load not found.'], 404);
        }
    }

    public function ready_to_pay(Request $request){

        $load = Load::find($request->load_id);

        if ($load) {

            $subject = "Change the Load Ready to Pay status  $load->ready_to_pay to $request->ready_to_pay";
            addToLog($customerId ='', $request->load_id, $subject, $oldData ='', $newData ='');

            $load->ready_to_pay = $request->ready_to_pay;
            $load->save();
            return response()->json(['success' => true, 'message' => 'Ready to pay updated successfully.']);
        } else {
            return response()->json(['success' => false, 'message' => 'Load not found.'], 404);
        }
    }

    public function updateCarrierInvoiceDate(Request $request)
    {
        $load = Load::find($request->id);

        if (!$load) {
            return response()->json([
                'success' => false,
                'message' => 'Load not found.'
            ], 404);
        }

        if (empty($request->carrier_invoice_date)) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice date is required.'
            ], 422);
        }

        try {

            // Save old values BEFORE updating
            $oldInvoiceDate = $load->carrier_invoice_date;
            $oldDueDate     = $load->load_carrier_due_date;

            /*
            |--------------------------------------------------------------------------
            | Incoming date from <input type="date">
            | Example: 2026-08-13
            |--------------------------------------------------------------------------
            */
            $invoiceDate = \Carbon\Carbon::createFromFormat(
                'Y-m-d',
                $request->carrier_invoice_date,
                'America/New_York'
            );

            /*
            |--------------------------------------------------------------------------
            | Add 25 days
            |--------------------------------------------------------------------------
            */
            $dueDate = $invoiceDate->copy()->addDays(25);

            /*
            |--------------------------------------------------------------------------
            | Log
            |--------------------------------------------------------------------------
            */
            $subject = "Carrier invoice date changed from {$oldInvoiceDate} to {$invoiceDate->format('m/d/Y')}";

            addToLog(
                '',
                $request->id,
                $subject,
                $oldInvoiceDate,
                $invoiceDate->format('Y-m-d')
            );

            /*
            |--------------------------------------------------------------------------
            | SAVE TO DATABASE
            |--------------------------------------------------------------------------
            | MySQL DATE format = Y-m-d
            |--------------------------------------------------------------------------
            */

            $load->carrier_invoice_date = $invoiceDate->format('Y-m-d');
            $load->load_carrier_due_date = $dueDate->format('Y-m-d');

            $load->save();

            return response()->json([
                'success' => true,

                'message' => 'Carrier invoice date and due date updated successfully by ' .
                            auth()->user()->name,

                // Old values
                'old_invoice_date' => $oldInvoiceDate,
                'old_due_date'     => $oldDueDate,

                // Database values
                'carrier_invoice_date' => $invoiceDate->format('Y-m-d'),
                'load_carrier_due_date' => $dueDate->format('Y-m-d'),

                // Frontend values
                'formatted_invoice_date' => $invoiceDate->format('m/d/Y'),
                'formatted_due_date'     => $dueDate->format('m/d/Y'),
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Invalid invoice date format.',
                'error' => $e->getMessage()
            ], 422);
        }
    }
    public function updateCarrierDocuments(Request $request)
    {
        $load = Load::find($request->id);

        if (!$load) {
            return response()->json([
                'success' => false,
                'message' => 'Load not found.'
            ], 404);
        }

        $allowedDocuments = [
            'NOA',
            'Void Check',
            'Pay by Check'
        ];

        $document = $request->carrier_documents;

        if ($document !== '' && !in_array($document, $allowedDocuments)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid document selected.'
            ], 422);
        }

        $oldDocument = $load->carrier_documents;

        $load->carrier_documents = $document ?: null;
        $load->save();

        $subject = "Carrier document changed from "
            . ($oldDocument ?: 'None')
            . " to "
            . ($document ?: 'None');

        addToLog(
            '',
            $request->id,
            $subject,
            $oldDocument ?: '',
            $document ?: ''
        );

        return response()->json([
            'success' => true,
            'message' => 'Carrier document updated successfully by ' . auth()->user()->name,
            'carrier_documents' => $load->carrier_documents
        ]);
    }

      public function getFiles(Request $request)
    {
        $load = Load::find($request->id);
    
        if (!$load || empty($load->carrierDoc)) {
            return response()->json([]);
        }
    
        $files = json_decode($load->carrierDoc, true) ?: [];
        $fileUrls = [];
    
        foreach ($files as $file) {
            $fileUrls[] = [
                'url' => asset('/public/'.$file), // Correct path without adding 'storage/'
                'name' => basename($file)
            ];
        }
    
        return response()->json($fileUrls);
    }

    // Helper function to sanitize the file name
    private function sanitizeFileName($filename)
    {
        // Remove any characters that are not alphanumeric, underscores, or dashes
        // Replace spaces with underscores
        return preg_replace('/[^a-zA-Z0-9-_\.]/', '_', str_replace(' ', '_', $filename));
    }
    


public function deleteCarrierDoc(Request $request)
{
    $id = $request->input('id');
    $filename = $request->input('filename');
    $load = Load::find($id);

    if (!$load) {
        return response()->json(['success' => false, 'message' => 'Load not found'], 404);
    }

    // Get existing files
    $existingFiles = json_decode($load->carrierDoc, true) ?: [];

    // Filter out the file to be deleted
    $remainingFiles = array_filter($existingFiles, function($file) use ($filename) {
        return basename($file) !== $filename;
    });

    // Update the Load record with the remaining files
    $load->carrierDoc = json_encode(array_values($remainingFiles));
    $load->save();

    // Delete the file from storage
    Storage::disk('public')->delete($filename);
	
    return response()->json(['success' => true, 'message' => 'File deleted successfully']);
}

public function editCustomer($id)
{
    $customer = customer::find($id);
    if (!$customer) {
        return redirect()->back()->with('error', 'Customer not found.');
    }

    // Fetch all users
    $users = User::with('role', 'department', 'managers', 'teamleader', 'office')->where('department', 3)->get();



    $credits = json_decode($customer->remaining_credit_logs, true);
    if (!is_array($credits) || count($credits) === 0) {
        $credits = json_decode($customer->credit_limit_log, true);
    }

    if (is_array($credits)) {
        $totalCreditLimit = max(0.0, (float) array_sum(array_column($credits, 'credit_limit')));
    } else {
        $totalCreditLimit = 0.0;
    }

    if ($totalCreditLimit <= 0) {
        $totalCreditLimit = max(
            0.0,
            (float) ($customer->adv_customer_credit_limit ?? $customer->invoice_credit_limit ?? 0)
        );
    }
    

    // Calculate totals using aggregates for better performance
    $totalFinalRate = Load::where('customer_id', $customer->id)->sum('shipper_load_final_rate');
    $recordPaidAmount = Load::where('customer_id', $customer->id)
                            ->where('invoice_status', 'Paid Record')
                            ->sum('shipper_load_final_rate');

    // Calculate sum of `shipper_load_final_rate` for loads with `invoice_status == 'Paid'`
    $customerAging = Load::where('customer_id', $customer->id)
                         ->where('invoice_status', 'Paid')
                         ->sum('shipper_load_final_rate');


    // Calculate sum of `shipper_load_final_rate` for the last 30 days where `invoice_status == 'Paid'`
    $last30Days = Load::where('customer_id', $customer->id)
                        ->where('invoice_status', 'Paid')
                        ->whereRaw('STR_TO_DATE(invoice_date, "%Y-%m-%d") BETWEEN ? AND ?', [
                            now()->subDays(30)->toDateString(),
                            now()->toDateString()
                            ])->sum('shipper_load_final_rate');

    $customerLoadScope = function ($query) use ($customer) {
        $query->where('customer_id', $customer->id)
              ->orWhereRaw('LOWER(TRIM(load_bill_to)) = LOWER(TRIM(?))', [$customer->customer_name]);
    };

    $creditLoadAmount = max(0.0, (float) Load::where($customerLoadScope)
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
        }));

    $loadcreateamount = max(0.0, (float) Load::where($customerLoadScope)
        ->get(['load_shipper_rate', 'shipper_load_final_rate', 'load_final_rate', 'shipper_load_other_charge'])
        ->sum(function ($load) {
            $createdAmount = (float) ($load->shipper_load_final_rate ?: $load->load_final_rate ?: 0);
            $baseAmount = (float) ($load->load_shipper_rate ?: 0);
            $charges = json_decode($load->shipper_load_other_charge, true) ?: [];
            $hasInvoiceFlags = collect($charges)->contains(function ($charge) {
                return array_key_exists('for_invoice', $charge);
            });
            $invoiceCharges = collect($charges)->sum(function ($charge) use ($hasInvoiceFlags) {
                if ($hasInvoiceFlags) {
                    return ($charge['for_invoice'] ?? 'off') === 'on'
                        ? (float) ($charge['amount'] ?? 0)
                        : 0;
                }

                return 0;
            });
            $allCharges = collect($charges)->sum(function ($charge) {
                return (float) ($charge['amount'] ?? 0);
            });

            if ($invoiceCharges > 0 && $createdAmount < $baseAmount + $allCharges) {
                $createdAmount += $invoiceCharges;
            }

            return max(0.0, $createdAmount);
        }));

    $receiving_amount = max(0.0, (float) Load::where($customerLoadScope)
        ->sum('receiving_amount'));

    $totalExhaustedLimit = max(0.0, $loadcreateamount - $receiving_amount);
    $remainingCreditLogs = json_decode($customer->remaining_credit_logs, true) ?? [];
    $loggedRemainingCredit = collect($remainingCreditLogs)->sum(function ($credit) {
        return (float) ($credit['credit_limit'] ?? 0);
    });
    $remainingCredit = $customer->remaining_credit !== null && $customer->remaining_credit !== ''
        ? max(0.0, (float) $customer->remaining_credit)
        : max(0.0, $loggedRemainingCredit);
    $usedAmount = $totalExhaustedLimit;
    $after_used_remaing_amount = $remainingCredit;
    $afterpaymentremaingamount = max(0.0, $after_used_remaing_amount + $receiving_amount);

                      
    $dailyInvoiceTotals = Load::select(
        DB::raw('DATE(invoice_status_date) as date'),
        DB::raw('SUM(receiving_amount) as total_amount')
    )
    ->where($customerLoadScope)
    ->groupByRaw('DATE(invoice_status_date)')
    ->get();


    // print_r($dailyInvoiceTotals); die;

	
    $pendingpayment = max(0.0, $loadcreateamount - $receiving_amount);

    $loads = Load::where('customer_id', $customer->id)->where('invoice_status','Paid')->get();
    $loadDatacustomeraging = $loads->sortByDesc(function ($load) {
        return now()->diffInDays($load->invoice_date);
    })->map(function ($load) {
        return [
            'load_number' => $load->load_number,
            'invoice_number' => $load->invoice_number,
            'invoice_date' => $load->invoice_date,
            'agent' => $load->user->name ?? 'N/A',
            'customer_payment' => number_format($load->shipper_load_final_rate, 2),
            'aging_days' => now()->diffInDays($load->invoice_date),
            'load_bill_to' => $load->load_bill_to,
        ];
    });
	
	$loads30days = Load::where('customer_id', $customer->id)
					->where('invoice_status', 'Paid')
					->whereRaw('STR_TO_DATE(invoice_date, "%Y-%m-%d") < ?', [
						now()->subDays(30)->toDateString()
					])->get();


	$loadDataabove30days = $loads30days->map(function ($load) {
    $customerPayment = is_numeric($load->shipper_load_final_rate) ? (float) $load->shipper_load_final_rate : 0;

    return [
        'load_number'       => $load->load_number,
        'invoice_number'    => $load->invoice_number,
        'invoice_date'      => $load->invoice_date ?? 'N/A',
        'agent'             => optional($load->user)->name ?? 'N/A',
        'customer_payment'  => $customerPayment, // keep raw number
        'aging_days'        => $load->invoice_date ? now()->diffInDays($load->invoice_date) : 0,
        'load_bill_to'      => $load->load_bill_to,
    ];
})->sortByDesc('aging_days')->values();

$totalCustomerPayment = $loadDataabove30days->sum('customer_payment');

	$allcountry = Country::get();
	$states = State::get();
	
	$state = json_decode($states, true);
    
    return view('accounts.customer_edit', compact('totalCustomerPayment', 'loadDatacustomeraging', 'loadDataabove30days', 'pendingpayment', 'dailyInvoiceTotals', 'customer', 'usedAmount', 'remainingCredit', 'totalFinalRate', 'users', 'customerAging','last30Days','loadcreateamount', 'receiving_amount', 'afterpaymentremaingamount', 'totalCreditLimit', 'allcountry', 'state'));
}


public function accountupdateCustomer(Request $request, $id)
{
     $validator = Validator::make($request->all(), [
        'customer_name' => 'required|string',
        'customer_address' => 'required',
        'customer_city' => 'required',
        'customer_state' => 'required',
        'customer_country' => 'required',
        'customer_zip' => 'required',
        'customer_telephone' => 'required',
    ]);
	
    if ($validator->fails()) {
        return redirect()->back()->withErrors($validator)->withInput();
    }
	


    // Find the customer by ID
    $customer = customer::find($id);

  
    $customerdata = customer::find($id);
 
     // Query all loads for the customer
    $loads = Load::where('customer_id', $customer->id)->get();

    // Initialize variables
    $totalFinalRate = 0;
    $recordPaidAmount = 0;

    foreach ($loads as $load) {
        // Add up the shipper_load_final_rate for all loads
        $totalFinalRate += (float) $load->shipper_load_final_rate;

        // Check for "Record Paid" status and calculate the adjustment
        if ($load->invoice_status == "Paid Record") {
            $recordPaidAmount += $load->shipper_load_final_rate;
        }
    }

    // Adjust used and remaining credit based on Record Paid loads
    $usedAmount = $totalFinalRate - $recordPaidAmount;
	
    if (!$customer) {
        return redirect()->back()->with('error', 'Customer not found.');
    }

    // Decode existing credit limit logs or initialize an empty array
    $existingCreditLogs = json_decode($customer->credit_limit_log, true);
    $existingCreditLogs = is_array($existingCreditLogs) ? $existingCreditLogs : [];

    // Prepare new credit limit logs
    $newCreditLimitLogs = [];
    $creditLimitLogData = $request->input('new_credit_limit', []);
    $creditTimes = $request->input('new_credit_time', []);
    if (!empty($creditLimitLogData) && !empty($creditTimes)) {
        foreach ($creditLimitLogData as $index => $creditLimit) {
            if (!empty($creditLimit) && isset($creditTimes[$index])) {
                $newCreditLimitLogs[] = [
                    'credit_limit' => $creditLimit,
                    'credit_time' => $creditTimes[$index],
                ];
            }
        }
    }
    $updatedCreditLogs = array_merge($existingCreditLogs, $newCreditLimitLogs);
    $existinginvoiceremaningCreditLogs = json_decode($customer->invoice_credit_limit_log, true);
    $existinginvoiceremaningCreditLogs = is_array($existinginvoiceremaningCreditLogs)
        ? $existinginvoiceremaningCreditLogs
        : [];
    $newinvoiceCreditLimitLogs = [];
    $remainingcreditLimitLogData = $request->input('invoice_credit_limits', []);
    $invoicecreditTimes = $request->input('invoice_credit_time', []);
    if (!empty($remainingcreditLimitLogData)) {
        foreach ($remainingcreditLimitLogData as $index => $creditLimit) {
            if (!empty($creditLimit)) {
                $newinvoiceCreditLimitLogs[] = [
                    'credit_limit' => $creditLimit,
                    'credit_time' => now()->format("Y-m-d\TH:i"),
                ];
            }
        }
    }
    $updatedinvoiceremaingCreditLogs = array_merge($existinginvoiceremaningCreditLogs, $newinvoiceCreditLimitLogs);
    $existingremaningCreditLogs = json_decode($customer->remaining_credit_logs, true);
    $existingremaningCreditLogs = is_array($existingremaningCreditLogs)
        ? $existingremaningCreditLogs
        : [];
    $newremaningCreditLimitLogs = [];
    $remainingcreditLimitLogData = $request->input('new_remaing_credit_limit', []);
    $creditTimes = $request->input('new_remaing_credit_time', []);

    if (is_array($remainingcreditLimitLogData)) {
        foreach ($remainingcreditLimitLogData as $index => $creditLimit) {
            if (is_numeric($creditLimit) && (float) $creditLimit > 0) {
                $newremaningCreditLimitLogs[] = [
                    'credit_limit' => $creditLimit,
                    'credit_time' => $creditTimes[$index] ?? now()->format('Y-m-d\TH:i'),
                ];
            }
        }
    }
    $updatedremaingCreditLogs = array_merge($existingremaningCreditLogs, $newremaningCreditLimitLogs);
    $newRemainingCredit = array_sum(array_column($newremaningCreditLimitLogs, 'credit_limit'));
    $existingRemainingCredit = max(
        0.0,
        (float) ($customer->remaining_credit ?? 0),
        array_sum(array_column($existingremaningCreditLogs, 'credit_limit'))
    );
    $calculatedRemainingCredit = $existingRemainingCredit + $newRemainingCredit;
    $submittedRemainingCredit = $request->input('remaining_credit');
    $remainingCredit = is_numeric($submittedRemainingCredit)
        ? max($calculatedRemainingCredit, (float) $submittedRemainingCredit)
        : $calculatedRemainingCredit;
    $customer->credit_limit_log = json_encode($updatedCreditLogs);
    $customer->remaining_credit_logs = json_encode($updatedremaingCreditLogs);
	$customer->invoice_credit_limit_log = json_encode($updatedinvoiceremaingCreditLogs);
    $customer->remaining_credit = max(0, $remainingCredit);
    $customer->invoice_credit_limit = $request->input('invoice_credit_limit');
	$customer->customer_country = $request->input('customer_country');
    $customer->customer_state = $request->input('customer_state');
    $customer->customer_name = $request->input('customer_name');
    $customer->customer_address = $request->input('customer_address');
    $customer->status = $request->input('status');
    $customer->customer_telephone = $request->input('customer_telephone');
    $customer->adv_customer_credit_limit = $request->input('adv_customer_credit_limit');
    $customer->user_id = $request->input('user_id');
    $customer->comment_notes = $request->input('comment_notes')[0] ?? null;
    $customer->private_comment_notes = $request->input('private_comment_notes')[0] ?? null;
    $customer->commenter_name = $request->input('commenter_name');
    $customer->approved_limit = $request->input('approved_limit');
    $customer->customer_hold_status = $request->has('customer_hold_status') ? 'hold' : 'unhold';
    $customer->invoice_through = $request->input('invoice_through');
//     echo '<pre>';
// print_r($customer);
// echo '</pre>';
// die;
    $customer->save();

    $subject = "Update the Customer info";
    addToLog($customer->id, $load_id ='', $subject, json_encode($customerdata, true), json_encode($request->all(), true));

    return redirect()->back()->with('success', 'Customer updated successfully');
}


public function saveInternalNotes(Request $request)
{
    // Validate the incoming request
    $request->validate([
        'id' => 'required|integer|exists:loads,id', // Change delivered_table to your actual table name
        'notes' => 'nullable|string',
    ]);

    // Find the record and update the notes
    $delivered = Load::find($request->id);

    $subject = "Save the internal notes for load";
    addToLog($customeid='', $request->id, $subject, $oldData ='', $newData ='');

    
    $delivered->internal_notes = $request->notes; // Make sure to have the 'internal_notes' field in your table
    $delivered->save();
    return response()->json(['message' => 'Notes saved successfully!']);
}


public function updateInvoiceStatus(Request $request, $id)
    {
        $load = Load::find($id);
    
        if ($load) {
            if ($load->invoice_status === 'Paid') {
                return response()->json(['message' => 'Load is already marked as Paid'], 400);
            }
    
            if (!$load->invoice_number) {
                // Generate a new invoice number starting from 2000
                //$lastInvoice = Load::whereNotNull('invoice_number')->orderBy('invoice_number', 'desc')->first();
                $lastInvoice = Load::whereNotNull('invoice_number')
                    ->orderByRaw('CAST(invoice_number AS UNSIGNED) DESC')
                    ->first();
                
                $lastInvoiceNumber = $lastInvoice ? intval($lastInvoice->invoice_number) : 7999; // Use 1999 so that the first number is 2000
                $newInvoiceNumber = $lastInvoiceNumber + 1;
                $load->invoice_number = $newInvoiceNumber;
            }
    
            $load->invoice_status = 'Paid';
            $load->paper_work_date = $request->input('perperworkdate');
            $load->invoice_date = now()->setTimezone('America/New_York')->format('Y-m-d H:i:s');
            $load->save();
			
			
			$invoice = DB::table('loads')
				->join('customers', 'loads.customer_id', '=', 'customers.id')
				->select(
					'loads.*', 
					'customers.customer_address', 
					'customers.customer_state', 
					'customers.customer_city', 
					'customers.customer_zip', 
					'customers.customer_country'
				)
				->where('loads.load_number', $id)
				->first();
			
			if (!$invoice) {
				abort(404, 'Invoice not found'); // Return a 404 error if no invoice is found
			}
			
			// Clean up the address data
			$invoice->customer_address = trim($invoice->customer_address);
			$invoice->customer_city = trim($invoice->customer_city);
			$invoice->customer_state = trim($invoice->customer_state);
			$invoice->customer_zip = trim($invoice->customer_zip);
			$invoice->customer_country = trim($invoice->customer_country);
			$invoice->customer_country = preg_replace('/^\d+\s*\|\s*/', '', $invoice->customer_country);
			
			// Parse the invoice date if it exists
			if ($invoice->invoice_date) {
				$invoice->invoice_date = Carbon::parse($invoice->invoice_date);
			}
		
			// Generate the custom file name
			$fileName = "Load_invoice_{$invoice->load_number}";
			
			
			$options = new Options();
			$options->set('defaultFont', 'DejaVu Sans'); // Optional
			$options->set('isRemoteEnabled', true); 
			$dompdf = new Dompdf($options);

			// Load HTML from a view manually if not using Laravel's Blade rendering
			$html = view('invoices_print_mail', [
				'fileName' => $fileName,
				'invoice' => $invoice,
			])->render();

			$dompdf->loadHtml($html);
			$customPaper = array(0, 0, 595.28, 841.89); // A4 in points: width x height
			$dompdf->setPaper($customPaper, 'portrait');
			$dompdf->render();

			$targetDir = public_path('uploads/delivery-order/' . $id . '/');

			if (!is_dir($targetDir)) {
				mkdir($targetDir, 0755, true);
			}

			// Save the output
			file_put_contents($targetDir . $fileName.'.pdf', $dompdf->output());
			
			$load = Load::findOrFail($id);
			$oldfiles = json_decode($load->load_delivery_do_file, false) ?? [];
			$targetPathnew = 'uploads/delivery-order/'.$id.'/' . $fileName.'.pdf';
	
			$newfile[] = $targetPathnew;
			
			$relativeFromAbsolute = array_map(function ($path) {
				return str_replace(public_path() . '/', '', $path);
			}, $oldfiles);

			// Merge both relative arrays
			$merged = array_merge($relativeFromAbsolute, $newfile);

			// Optional: Remove duplicates
			$merged = array_unique($merged);
			
			
			$load->load_delivery_do_file = json_encode($merged);
			$load->save();
		

            $subject = "Load Mark as invoice, invoice no : $load->invoice_number and invoice date : $load->invoice_date and paper work date: ".$request->input('perperworkdate');
            addToLog($customeid='', $id, $subject, $oldData ='', $newData ='');
          
            return response()->json([
                'message' => 'Mark as Invoice successfully',
                'invoice_number' => $load->invoice_number,
                'invoice_date' => $load->invoice_date, // Include invoice date in the response
            ], 200);
        }
    
        return response()->json(['message' => 'Load not found'], 404);
    }


    public function updateInvoiceStatusAsPaidRecord(Request $request, $id)
    {
        $load = Load::find($id);
        if ($load) {
            $request->validate([
                'payment_receiving_date' => 'required|date',
                'receiving_amount' => 'nullable|numeric|min:0',
                'remaining_amount' => 'nullable|numeric'
            ]);

            // Allow caller to specify the desired status (default to 'Paid Record')
            $desiredStatus = $request->input('status', 'Paid Record');
            $load->invoice_status = $desiredStatus;
            $load->payment_receiving_date = $request->input('payment_receiving_date');
            $load->invoice_status_date = now()->format('Y-m-d H:i:s');

            if ($request->filled('receiving_amount')) {
                $load->receiving_amount = $request->input('receiving_amount');
            }

            if ($request->filled('remaining_amount')) {
                $load->remaining_amount = $request->input('remaining_amount');
            } elseif ($request->filled('receiving_amount')) {
                $load->remaining_amount = floatval($load->shipper_load_final_rate) - floatval($load->receiving_amount);
            }

            $load->save();

            $subject = "Load Mark as Paid, payment receiving date :".$request->input('payment_receiving_date');
            addToLog($customeid='', $id, $subject, $oldData ='', $newData ='');

            return response()->json(['success' => true, 'message' => 'Marked as Paid successfully', 'status' => $desiredStatus], 200);
        }
    
        return response()->json(['success' => false, 'message' => 'Load not found'], 404);
    }

    /**
     * Mark a load as a short payment (partial payment) from Paid tab.
     * If payment_receiving_date is not provided, set it to now().
     */
    public function updateInvoiceStatusAsShort(Request $request, $id)
    {
        $load = Load::find($id);
        Log::info('updateInvoiceStatusAsShort called', ['id' => $id, 'request' => $request->all()]);

        if (! $load) {
            Log::warning('Load not found for short payment', ['id' => $id]);
            return response()->json(['success' => false, 'message' => 'Load not found'], 404);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'receiving_amount' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            Log::warning('Short payment validation failed', ['id' => $id, 'errors' => $validator->errors()->all()]);
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $receiving = floatval($request->input('receiving_amount'));

        // Update only payment fields; do NOT modify shipper_load_final_rate
        $this->applyPaymentAmounts($load, $receiving);
        $load->invoice_status = 'Paid Record';

        if ($request->filled('payment_receiving_date')) {
            $load->payment_receiving_date = $request->input('payment_receiving_date');
        } else {
            $load->payment_receiving_date = now()->format('Y-m-d H:i:s');
        }

        $load->invoice_status_date = now()->format('Y-m-d H:i:s');

        $saved = $load->save();

        Log::info('Short payment saved', ['id' => $id, 'saved' => $saved, 'receiving' => $receiving, 'remaining' => $load->remaining_amount]);

        $subject = "Load marked as Short Payment, receiving: {$receiving}";
        addToLog($customeid='', $id, $subject, $oldData ='', $newData ='');

        return response()->json(['success' => true, 'message' => 'Marked as Short Payment successfully'], 200);
    }

    public function updateReceivingAmount(Request $request)
    {
        $request->validate([
            'load_id' => 'required|integer',
            'receiving_amount' => 'required|numeric|min:0'
        ]);
    
        $load = Load::find($request->load_id);
    
        if ($load) {
            $receivingAmount = floatval($request->receiving_amount);
            $previousReceivingAmount = round(floatval($load->receiving_amount), 2);
            $previousRemainingAmount = round(floatval($load->remaining_amount), 2);

            $this->applyPaymentAmounts($load, $receivingAmount);
            $amountsChanged = $previousReceivingAmount !== round(floatval($load->receiving_amount), 2)
                || $previousRemainingAmount !== round(floatval($load->remaining_amount), 2);

            if ($amountsChanged) {
                $load->save();

                $subject = "update the load payment receiving amount receiving_amount ".$request->receiving_amount ." and remaining amount ".$load->remaining_amount;
                addToLog($customeid='', $request->load_id, $subject, $oldData ='', $newData ='');
            }
    
            return response()->json([
                'success' => true,
                'remaining_amount' => number_format($load->remaining_amount, 2),
                'load_advance_rec_amount' => number_format($load->load_advance_rec_amount, 2)
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Load not found'
            ]);
        }
    }
	
	public function updateadvReceivingAmount(Request $request)
    {
        $request->validate([
            'load_id' => 'required|integer',
            //'adv_receiving_amount' => 'required'
        ]);
    
        $load = Load::find($request->load_id);
    
        if ($load) {
            $advAmount = floatval($request->adv_receiving_amount ?? 0);

            // Only treat as advance if amount is greater than shipper final rate
            $shipperRate = floatval($load->shipper_load_final_rate ?? 0);
            $advanceToStore = 0;
            if ($advAmount > $shipperRate) {
                $advanceToStore = $advAmount - $shipperRate;
            }

            $load->load_advance_rec_amount = $advanceToStore;
            $saved = $load->save();
            Log::info('updateadvReceivingAmount', ['load_id' => $load->id, 'advAmount' => $advAmount, 'shipperRate' => $shipperRate, 'stored' => $advanceToStore, 'saved' => $saved]);

            //$subject = "update the load payment receiving amount advance receiving amount ".$request->adv_receiving_amount;
            //addToLog($customeid='', $request->load_id, $subject, $oldData ='', $newData ='');
    
            return response()->json([
                'success' => true,
                'load_advance_rec_amount' => $load->load_advance_rec_amount
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Load not found'
            ]);
        }
    }
	
	public function updateRemainingAmount(Request $request)
    {
        $request->validate([
            'load_id' => 'required|integer',
            //'invoice_internal_value' => 'required'
        ]);
    
        $load = Load::find($request->load_id);
    
        if ($load) {
            $load->invoice_internal_value = $request->invoice_internal_value;
            $load->save();

            //  $subject = "update the load invoice internal ".$request->invoice_internal_value;
            // addToLog($customeid='', $request->load_id, $subject, $oldData ='', $newData ='');
    
            return response()->json([
                'success' => true,
                'invoice_internal_value' => $load->invoice_internal_value
               ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Load not found'
            ]);
        }
    }

    protected function applyPaymentAmounts(Load $load, float $receiving)
    {
        $load->receiving_amount = $receiving;
        $shipperRate = floatval($load->shipper_load_final_rate ?? 0);

        if ($receiving >= $shipperRate) {
            $load->remaining_amount = 0;
            $load->load_advance_rec_amount = round($receiving - $shipperRate, 2);
        } else {
            $load->remaining_amount = round($shipperRate - $receiving, 2);
            $load->load_advance_rec_amount = 0;
        }
    }


    public function printInvoicePaid($id)
    {
        $invoice = Load::with('customer')->findOrFail($id);
        $invoice->invoice_date = Carbon::parse($invoice->invoice_date);
        return view('invoices_print', compact('invoice'));
    }

    public function printInvoice($id)
    {
		
        // Fetch the invoice based on load_number
        $invoice = DB::table('loads')
            ->join('customers', 'loads.load_bill_to', '=', 'customers.customer_name')
            ->select(
                'loads.*', 
                'customers.customer_address', 
                'customers.customer_state', 
                'customers.customer_city', 
                'customers.customer_zip', 
                'customers.customer_country',
                'customers.adv_customer_payment_terms'
            )
            ->where('loads.id', $id)
            ->first();

       
        if (!$invoice) {
            abort(404, 'Invoice not found'); // Return a 404 error if no invoice is found 
        }
        
        // Clean up the address data
        $invoice->customer_address = trim($invoice->customer_address);
        $invoice->customer_city = trim($invoice->customer_city);
        $invoice->customer_state = trim($invoice->customer_state);
        $invoice->customer_zip = trim($invoice->customer_zip);
        $invoice->customer_country = trim($invoice->customer_country);
        $invoice->customer_country = preg_replace('/^\d+\s*\|\s*/', '', $invoice->customer_country);
        $invoice->adv_customer_payment_terms = trim($invoice->adv_customer_payment_terms);
        
        // Parse the invoice date if it exists
        if ($invoice->invoice_date) {
            $invoice->invoice_date = Carbon::parse($invoice->invoice_date);
        }
    
        // Generate the custom file name
        $fileName = "Load #{$invoice->load_number} - Invoice#{$invoice->invoice_number}";
    
        // Render the view and pass the invoice data and file name
        return view('invoices_print', compact('invoice', 'fileName'));
    }


    public function markAsBackCompleteRecord($id)
    {
        $load = Load::find($id);
    
        if ($load) {
            $load->load_status = 'Completed';
            $load->invoice_status = '';
			$load->invoice_date = null;
			$load->payment_receiving_date = null;
            $load->paper_work_date = null;
            $load->save();

            $subject = "Load mark as back invoice to Complete";
            addToLog($customeid='', $id, $subject, $oldData ='', $newData ='');
			
			$filePath = public_path('uploads/delivery-order/' . $id . '/Load_invoice_'.$id.'.pdf');

			if (file_exists($filePath)) {
				unlink($filePath);
			}
    
            return response()->json(['success' => true, 'message' => 'Back to Complete successfully'], 200);
        }
    
        return response()->json(['success' => false, 'message' => 'Load not found'], 404);
    }
    

    public function markAsBackInvoiceRecord($id)
    {
        $load = Load::find($id);
       
        if ($load) {
            $load->load_status = 'Completed';
            $load->invoice_status = 'Paid';
            $load->payment_receiving_date = null;
            $load->save();
    
            $subject = "Load mark as back to Invoice";
            addToLog($customeid='', $id, $subject, $oldData ='', $newData ='');

            return response()->json(['success' => true, 'message' => 'Back to Invoice successfully'], 200);
        }    
        
        return response()->json(['success' => false, 'message' => 'Load not found'], 404);
    }

    public function accounting_open_search(Request $request){

        $q = $request->input('query');
        if (!empty($q)) {
            // Split the query by commas to get multiple terms
$searchTerms = array_filter(
    preg_split('/[\s,]+/', $q),
    fn($term) => !empty(trim($term))
);


            if (count($searchTerms) > 0) {
                // Search for non-empty terms with 'orWhere'
                $open = Load::where('load_status','Open')->with(['user','customer','carrier'])
                    ->where(function($query) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $query->orWhere('load_number', 'like', "%$term%");
                            $query->orwhere('load_workorder', 'like', "%$term%");
                            $query->orwhere('customer_refrence_number', 'like', "%$term%");
                            $query->orwhere('load_bill_to', 'like', "%$term%");
                            $query->orwhere('invoice_number', 'like', "%$term%");
                            $query->orwhere('load_dispatcher', 'like', "%$term%");

                        }
                    })
                    ->orderBy('loads.id', 'desc')
                    ->get();
            } else {
                // If no valid terms, return an empty collection or handle accordingly
                $open = collect();
            }
        } else {
            // If query is empty, return a paginated result without any filter
            $open = Load::where('load_status','Open')->with(['user','customer','carrier'])->orderBy("loads.id", "desc")->paginate(100);
  
        }
        
        return view('accounts.partials.accounting_open', compact('open'))->render();
    }

    public function accounting_completed_search(Request $request){

        $q = $request->input('query');
        if (!empty($q)) {
            // Split the query by commas to get multiple terms
			$searchTerms = array_filter(
				preg_split('/[\s,]+/', $q),
				fn($term) => !empty(trim($term))
			);


            if (count($searchTerms) > 0) {
                // Search for non-empty terms with 'orWhere'
                $complete =Load::where('load_status','Completed')->with(['user','customer','carrier'])->where(function($query) {
                    $query->where('invoice_status', '')
                          ->orWhereNull('invoice_status');
                    })->where(function($query) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $query->orWhere('load_number', 'like', "%$term%");
                            $query->orwhere('load_workorder', 'like', "%$term%");
                            $query->orwhere('customer_refrence_number', 'like', "%$term%");
                            $query->orwhere('loaD_bill_to', 'like', "%$term%");
                            $query->orwhere('invoice_number', 'like', "%$term%");
                            $query->orwhere('load_dispatcher', 'like', "%$term%");

                        }
                    })
                    ->orderBy('loads.id', 'desc')
                    ->get();
            } else {
                // If no valid terms, return an empty collection or handle accordingly
                $complete = collect();
            }
        } else {
            // If query is empty, return a paginated result without any filter
              $complete = Load::where('load_status','Completed')->with(['user','customer','carrier'])->where(function($query) {
                    $query->where('invoice_status', '')
                          ->orWhereNull('invoice_status');
                })->orderBy("loads.id", "desc")->paginate(100);
        }
        
        return view('accounts.partials.accounting_complete', compact('complete'))->render();
    }

    public function accounting_invoiced_search(Request $request){

        $q = $request->input('query');
        
        if (!empty($q)) {
            
                    // Split the query by commas to get multiple terms
        $searchTerms = array_filter(
            preg_split('/[\s,]+/', $q),
            fn($term) => !empty(trim($term))
        );

            if (count($searchTerms) > 0) {
                // Search for non-empty terms with 'orWhere'
                $invoiced = Load::where('invoice_status','Paid')->with(['user','customer','carrier'])
                    ->where(function($query) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $query->orWhere('load_number', 'like', "%$term%");
                            $query->orwhere('load_workorder', 'like', "%$term%");
                            $query->orwhere('customer_refrence_number', 'like', "%$term%");
                            $query->orwhere('load_bill_to', 'like', "%$term%");
                            $query->orwhere('invoice_number', 'like', "%$term%");
                            $query->orwhere('load_dispatcher', 'like', "%$term%");
                        }
                    })
                    ->orderBy('loads.id', 'desc')
                    ->get();
                    // print_r($invoiced); die;
            } else {
                // If no valid terms, return an empty collection or handle accordingly
                $invoiced = collect();
            }
        } else {
            
            // If query is empty, return a paginated result without any filter
             $invoiced = Load::where('invoice_status','Paid')->with(['user','customer','carrier'])->orderBy("loads.id", "desc")->paginate(100);
       
            }
        
        return view('accounts.partials.accounting_invoiced', compact('invoiced'))->render();
    }

    public function accounting_invoiced_paid_search(Request $request){

        $q = $request->input('query');
        if (!empty($q)) {
            // Split the query by commas to get multiple terms
       $searchTerms = array_filter(
    preg_split('/[\s,]+/', $q),
    fn($term) => !empty(trim($term))
);


            if (count($searchTerms) > 0) {
                // Search for non-empty terms with 'orWhere'
                $paid = Load::whereIn('invoice_status', ['Paid', 'Paid Record'])->with(['user','customer','carrier'])
                    ->where(function($query) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $query->orWhere('load_number', 'like', "%$term%");
                            $query->orwhere('load_workorder', 'like', "%$term%");
                            $query->orwhere('customer_refrence_number', 'like', "%$term%");
                            $query->orwhere('load_bill_to', 'like', "%$term%");
                            $query->orwhere('invoice_number', 'like', "%$term%");
                            $query->orwhere('load_dispatcher', 'like', "%$term%");

                        }
                    })
                    ->orderBy('loads.id', 'desc')
                    ->get();
            } else {
                // If no valid terms, return an empty collection or handle accordingly
                $paid = collect();
            }
        } else {
            // If query is empty, return a paginated result without any filter
            $paid = Load::whereIn('invoice_status', ['Paid', 'Paid Record'])->with(['user','customer','carrier'])->orderBy("loads.id", "desc")->paginate(100);
			
        }
        
        return view('accounts.partials.accounting_paid', compact('paid'))->render();
    }



    /**********Report search************/

    public function report_carrier_search(Request $request){
        $q = $request->input('query');
        if (!empty($q)) {
            // Split the query by commas to get multiple terms
            $searchTerms = array_filter(explode(',', $q), function($term) {
                return !empty(trim($term)); // Only keep non-empty terms
            });

            if (count($searchTerms) > 0) {
                // Search for non-empty terms with 'orWhere'
                $totalRevenueloadcarrier = Load::join('users', 'loads.user_id', '=', 'users.id')
                    ->select('loads.load_carrier', 'users.name as user_name')
                    ->selectRaw('SUM(loads.load_final_carrier_fee) AS total_revenue')
                    ->selectRaw('SUM(loads.load_final_carrier_fee - loads.load_carrier_fee) AS revenue_difference')
                    ->selectRaw('COUNT(loads.id) AS load_count')
                    ->selectRaw('SUM(CASE WHEN loads.load_status = "Open" THEN 1 ELSE 0 END) AS open_load_count')
                    ->selectRaw('SUM(CASE WHEN loads.load_status = "Delivered" THEN 1 ELSE 0 END) AS delivered_load_count')
                    ->selectRaw('SUM(CASE WHEN loads.invoice_status = "Completed" THEN 1 ELSE 0 END) AS completed_load_count')
					->selectRaw('MAX(loads.id) AS latest_id') 
                    ->groupBy('loads.load_carrier', 'users.name')
                    ->where(function($query) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $query->orWhere('loads.load_number', 'like', "%$term%");
                            $query->orwhere('loads.load_workorder', 'like', "%$term%");
                        }
                    })
                    ->orderBy('latest_id')
                    ->paginate(100);
            } else {
                // If no valid terms, return an empty collection or handle accordingly
                $totalRevenueloadcarrier = collect();
            }
        } else {
            // If query is empty, return a paginated result without any filter
           $totalRevenueloadcarrier = Load::join('users', 'loads.user_id', '=', 'users.id')
                ->select('loads.load_carrier', 'users.name as user_name')
                ->selectRaw('SUM(loads.load_final_carrier_fee) AS total_revenue')
                ->selectRaw('SUM(loads.load_final_carrier_fee - loads.load_carrier_fee) AS revenue_difference')
                ->selectRaw('COUNT(loads.id) AS load_count')
                ->selectRaw('SUM(CASE WHEN loads.load_status = "Open" THEN 1 ELSE 0 END) AS open_load_count')
                ->selectRaw('SUM(CASE WHEN loads.load_status = "Delivered" THEN 1 ELSE 0 END) AS delivered_load_count')
                ->selectRaw('SUM(CASE WHEN loads.invoice_status = "Completed" THEN 1 ELSE 0 END) AS completed_load_count')
                ->groupBy('loads.load_carrier', 'users.name')
                ->paginate(50); 
  
        }
        
        return view('accounts.reporting.carrier', compact('totalRevenueloadcarrier'))->render();
    }

    private function customerReportQuery()
    {
        return DB::table('customers')
            ->join('loads', 'customers.id', '=', 'loads.customer_id')
            ->where('customers.status', 'Approved')
            ->select(
                'customers.id as customer_id',
                'customers.customer_name',
                'customers.status',
                DB::raw('SUM(loads.shipper_load_final_rate) AS total_revenue'),
                DB::raw('SUM(loads.load_final_carrier_fee) AS total_carrier_cost'),
                DB::raw('SUM(loads.shipper_load_final_rate - loads.load_final_carrier_fee) AS margin'),
                DB::raw('COUNT(DISTINCT loads.id) AS load_count'),
                DB::raw('SUM(CASE WHEN loads.load_status = "Open" THEN 1 ELSE 0 END) AS open_load_count'),
                DB::raw('SUM(CASE WHEN loads.load_status = "Delivered" THEN 1 ELSE 0 END) AS delivered_load_count'),
                DB::raw('SUM(CASE WHEN loads.invoice_status = "Completed" THEN 1 ELSE 0 END) AS completed_load_count'),
                DB::raw('MAX(customers.remaining_credit_logs) AS remaining_credit_logs')
            )
            ->groupBy('customers.id', 'customers.customer_name', 'customers.status');
    }

    public function report_customer_search(Request $request){
        $searchTerms = array_filter(explode(',', (string) $request->input('query')), function ($term) {
            return trim($term) !== '';
        });

        $query = $this->customerReportQuery();
        if ($searchTerms) {
            $query->where(function ($query) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $query->orWhere('customers.customer_name', 'like', '%' . trim($term) . '%');
                }
            });
        }

        $totalRevenueCustomer = $query->paginate(50, ['*'], 'customer');

        return view('accounts.reporting.customers', compact('totalRevenueCustomer'))->render();
    }

    public function report_customer_detail_search(Request $request){
        $q = $request->input('query');
        if (!empty($q)) {
            // Split the query by commas to get multiple terms
            $searchTerms = array_filter(explode(',', $q), function($term) {
                return !empty(trim($term)); // Only keep non-empty terms
            });

            if (count($searchTerms) > 0) {
                // Search for non-empty terms with 'orWhere'
                  $get_customers = Customer::where(function($query) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $query->orWhere('customer_name', 'like', "%$term%");
                        }
                    })
                    ->paginate(50);
            } else {
                // If no valid terms, return an empty collection or handle accordingly
                $get_customers = collect();
            }
        } else {
            // If query is empty, return a paginated result without any filter
             $get_customers = Customer::paginate(50);
  
        }
        
        return view('accounts.reporting.customer_details', compact('get_customers'))->render();
    }

    public function report_dispatcher_search(Request $request){
        $q = $request->input('query');
        if (!empty($q)) {
            // Split the query by commas to get multiple terms
            $searchTerms = array_filter(explode(',', $q), function($term) {
                return !empty(trim($term)); // Only keep non-empty terms
            });

            if (count($searchTerms) > 0) {
                // Search for non-empty terms with 'orWhere'
                $totalRevenueCarrier = Load::join('users', 'loads.user_id', '=', 'users.id')
                        ->select('users.name')
                        ->selectRaw('SUM(loads.shipper_load_final_rate) AS total_revenue')
                        ->selectRaw('SUM(loads.load_final_carrier_fee) AS total_carrier_fee')
                        ->selectRaw('SUM(loads.shipper_load_final_rate - loads.load_final_carrier_fee) AS revenue_difference')
                        ->selectRaw('COUNT(loads.id) AS load_count')
                        ->selectRaw('SUM(CASE WHEN loads.load_status = "Open" THEN 1 ELSE 0 END) AS open_load_count')
                        ->selectRaw('SUM(CASE WHEN loads.load_status = "Delivered" THEN 1 ELSE 0 END) AS delivered_load_count')
                        ->selectRaw('SUM(CASE WHEN loads.invoice_status = "Paid" THEN 1 ELSE 0 END) AS invoiced_load_count')
                        ->selectRaw('SUM(loads.load_final_carrier_fee) AS sum_load_final_carrier_fee')
                        ->groupBy('users.name')
                    ->where(function($query) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $query->orWhere('loads.load_number', 'like', "%$term%");
                            $query->orwhere('loads.load_workorder', 'like', "%$term%");
                        }
                    })->paginate(50);
            } else {
                // If no valid terms, return an empty collection or handle accordingly
                $totalRevenueCarrier = collect();
            }
        } else {
            // If query is empty, return a paginated result without any filter
            $totalRevenueCarrier = Load::join('users', 'loads.user_id', '=', 'users.id')
                    ->select('users.name')
                    ->selectRaw('SUM(loads.shipper_load_final_rate) AS total_revenue')
                    ->selectRaw('SUM(loads.load_final_carrier_fee) AS total_carrier_fee')
                    ->selectRaw('SUM(loads.shipper_load_final_rate - loads.load_final_carrier_fee) AS revenue_difference')
                    ->selectRaw('COUNT(loads.id) AS load_count')
                    ->selectRaw('SUM(CASE WHEN loads.load_status = "Open" THEN 1 ELSE 0 END) AS open_load_count')
                    ->selectRaw('SUM(CASE WHEN loads.load_status = "Delivered" THEN 1 ELSE 0 END) AS delivered_load_count')
                    ->selectRaw('SUM(CASE WHEN loads.invoice_status = "Paid" THEN 1 ELSE 0 END) AS invoiced_load_count')
                    ->selectRaw('SUM(loads.load_final_carrier_fee) AS sum_load_final_carrier_fee')
                    ->groupBy('users.name')
                    ->paginate(50);
  
        }
        
        return view('accounts.reporting.dispatchers', compact('totalRevenueCarrier'))->render();
    }

    public function report_load_search(Request $request){
        $q = $request->input('query');
        if (!empty($q)) {
            // Split the query by commas to get multiple terms
            $searchTerms = array_filter(explode(',', $q), function($term) {
                return !empty(trim($term)); // Only keep non-empty terms
            });

            if (count($searchTerms) > 0) {
                // Search for non-empty terms with 'orWhere'
                $dashboard = Load::with('user')
                    ->where(function($query) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $query->orWhere('load_number', 'like', "%$term%");
                            $query->orwhere('load_workorder', 'like', "%$term%");
                            $query->orwhere('customer_refrence_number', 'like', "%$term%");
                        }
                    })
                    ->paginate(50);
            } else {
                // If no valid terms, return an empty collection or handle accordingly
                $dashboard = collect();
            }
        } else {
            // If query is empty, return a paginated result without any filter
            $dashboard = Load::with('user')->paginate(50);
  
        }
        return view('accounts.reporting.load', compact('dashboard'))->render();
    }
    public function report_sales_rep_search(Request $request){
        $q = $request->input('query');
        if (!empty($q)) {
            // Split the query by commas to get multiple terms
            $searchTerms = array_filter(explode(',', $q), function($term) {
                return !empty(trim($term)); // Only keep non-empty terms
            });

            if (count($searchTerms) > 0) {
                // Search for non-empty terms with 'orWhere'
                $totalRevenueBroker = Load::join('users', 'loads.user_id', '=', 'users.id')
                    ->select('users.name')
                    ->selectRaw('SUM(loads.load_shipper_rate) AS total_revenue')
                    ->selectRaw('SUM(loads.load_carrier_fee) AS total_carrier_fee')
                    ->selectRaw('SUM(loads.load_shipper_rate - loads.load_carrier_fee) AS revenue_difference')
                    ->selectRaw('COUNT(loads.id) AS load_count')
                    ->selectRaw('SUM(CASE WHEN loads.load_status = "Open" THEN 1 ELSE 0 END) AS open_load_count')
                    ->groupBy('users.name')
                    ->where(function($query) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $query->orWhere('loads.load_number', 'like', "%$term%");
                            $query->orwhere('loads.load_workorder', 'like', "%$term%");
                            $query->orwhere('loads.customer_refrence_number', 'like', "%$term%");
                        }
                    })->paginate(50);
            } else {
                // If no valid terms, return an empty collection or handle accordingly
                $totalRevenueBroker = collect(); 
            }
        } else {
            // If query is empty, return a paginated result without any filter 
            $totalRevenueBroker = Load::join('users', 'loads.user_id', '=', 'users.id')
                    ->select('users.name')
                    ->selectRaw('SUM(loads.load_shipper_rate) AS total_revenue')
                    ->selectRaw('SUM(loads.load_carrier_fee) AS total_carrier_fee')
                    ->selectRaw('SUM(loads.load_shipper_rate - loads.load_carrier_fee) AS revenue_difference')
                    ->selectRaw('COUNT(loads.id) AS load_count')
                    ->selectRaw('SUM(CASE WHEN loads.load_status = "Open" THEN 1 ELSE 0 END) AS open_load_count')
                    ->groupBy('users.name')
                    ->paginate(50);
  
        }
        
        return view('accounts.reporting.sales_reps', compact('totalRevenueBroker'))->render();
    }

    public function report_load_completed_log_search(Request $request){
         $q = $request->input('query');
        if (!empty($q)) {
            // Split the query by commas to get multiple terms
            $searchTerms = array_filter(explode(',', $q), function($term) {
                return !empty(trim($term)); // Only keep non-empty terms
            });

            if (count($searchTerms) > 0) {
                // Search for non-empty terms with 'orWhere'
                    $dashboard_logs = Load::with('user')
                        ->where('load_status', 'Completed')
                    ->where(function($query) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $query->orWhere('load_number', 'like', "{$term}");
                                //->orWhere('load_workorder', 'like', "%{$term}%")
                                //->orWhere('customer_refrence_number', 'like', "%{$term}%");
                        }
                        })->latest('created_at')->simplePaginate(50);
            } else {
                // If no valid terms, return an empty collection or handle accordingly
                $dashboard_logs = collect();
            }
        } else {
            // If query is empty, return a paginated result without any filter
                $dashboard_logs = Load::with('user')
                    ->where('load_status', 'Completed')
                    ->latest('created_at')
                    ->simplePaginate(50);
  
        }
        
        return view('accounts.reporting.load_completed_logs', compact('dashboard_logs'))->render();
    }

    public function report_limit_search(Request $request){
        $q = $request->input('query');
        if (!empty($q)) {
            // Split the query by commas to get multiple terms
            $searchTerms = array_filter(explode(',', $q), function($term) {
                return !empty(trim($term)); // Only keep non-empty terms
            });

            if (count($searchTerms) > 0) {
                // Search for non-empty terms with 'orWhere'
                $sortedCustomers = Customer::where(function($query) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $query->orWhere('customer_name', 'like', "%{$term}%");
                        }
                    })->paginate(50);

            } else {
                // If no valid terms, return an empty collection or handle accordingly
                $sortedCustomers = collect();
            }
        } else {
            // If query is empty, return a paginated result without any filter
            $sortedCustomers = Customer::paginate(50, ['*'], 'limits');
  
        }
        
        return view('accounts.reporting.limit', compact('sortedCustomers'))->render();
    }

    public function report_aging_search(Request $request){
        $q = $request->input('query');
        if (!empty($q)) {
            // Split the query by commas to get multiple terms
            $searchTerms = array_filter(explode(',', $q), function($term) {
                return !empty(trim($term)); // Only keep non-empty terms
            });

            if (count($searchTerms) > 0) {
                // Search for non-empty terms with 'orWhere'
                $customersData = Customer::with('user')->where(function($query) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $query->orWhere('customer_name', 'like', "%{$term}%");
                        }
                    })->paginate(50); // Eager load 'user' to avoid N+1

            } else {
                // If no valid terms, return an empty collection or handle accordingly
                $customersData = collect();
            }
        } else {
            // If query is empty, return a paginated result without any filter
            $customersData = Customer::all();
            
  
        }
        
        return view('accounts.reporting.aging', compact('customersData'))->render();
    }

    public function markCarrierAsPaid(Request $request){
        $load = Load::find($request->id);

        if (!$load) {
            return response()->json(['status' => 'error', 'message' => 'Load not found']);
        }

        $load->carrier_mark_as_paid = 'Paid';
        $load->load_carrier_due_date_on = now()->format('d-m-Y');
        $load->save();

        $subject = "Load mark carrier as Paid";
        addToLog($customeid='', $request->id, $subject, $oldData ='', $newData ='');

        return response()->json(['success' => true, 'message' => 'Marked as Paid successfully']);
    }
    
public function saverateChecks(Request $request)
    {
        // Find the load by ID
        $load = Load::find($request->id); 
      
        if ($load) {
            $subject = "Load Save the Rate Check :- $load->rate_check To $request->rate_check";
            // Update the cpr_check field, defaulting to 'Not Approved' if null
            $load->rate_check = $request->rate_check ?? '0';
            $load->save();

           
            addToLog($customeid='', $request->id, $subject, $oldData ='', $newData ='');
            
            return response()->json(['success' => true, 'message' => 'Rate checks updated successfully.']);
        } else {
            return response()->json(['success' => false, 'message' => 'CPR not found.'], 404);
        }
    }

public function uploadCarrierDocs(Request $request)
{
    $id = $request->input('id');
    $load = Load::find($id);

    if (!$load) {
        return response()->json(['success' => false, 'message' => 'Load not found'], 404);
    }

    $loadNumber = $load->load_number;
    $uploadedFiles = [];
    $existingFiles = json_decode($load->carrierDoc, true) ?: [];

    if ($request->hasFile('carrierDoc')) {
        $files = $request->file('carrierDoc');
        $maxSize = 50 * 1024 * 1024; // Max file size in bytes (50MB)

        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];

        foreach ($files as $file) {
            $extension = strtolower($file->getClientOriginalExtension());

            // Validate file type
            if (!in_array($extension, $allowedExtensions)) {
                return response()->json(['success' => false, 'message' => 'Only PDF and image files are allowed.'], 400);
            }

            // Validate file size
            if ($file->getSize() > $maxSize) {
                return response()->json(['success' => false, 'message' => 'File size exceeds 5MB'], 400);
            }

            // Sanitize file name
            $safeFileName = preg_replace('/[^A-Za-z0-9\.\-_]/', '_', $file->getClientOriginalName());

            // Create directory if not exists
            $directory = public_path("carrierFiles/{$loadNumber}");
            
            if (!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }

            // Move file
            $file->move($directory, $safeFileName);
            $uploadedFiles[] = "carrierFiles/{$loadNumber}/{$safeFileName}";
        }

        // Save files to DB
        $load->carrierDoc = json_encode(array_merge($existingFiles, $uploadedFiles));
        $load->save();
		
		$subject = "vendor upload the carrier doc. ".json_encode($uploadedFiles);
        addToLog($customerId ='', $loadNumber, $subject, $oldData ='', $newData ='');

        return response()->json(['success' => true, 'files' => $uploadedFiles]);
    }else{
		 return response()->json(['success' => false, 'message' => 'No files selected'], 400);
	} 
}


public function getCarrierFiles($id)
{
    $load = Load::find($id);
    if (!$load || !$load->carrierDoc) {
        return response()->json(['success' => false]);
    }

    $files = json_decode($load->carrierDoc, true);
    return response()->json(['success' => true, 'files' => $files]);
}


public function deleteCarrierFile(Request $request)
{
    $id = (int) $request->input('load_id');
    $file = 'carrierFiles/'.$id.'/'.$request->input('file_name');
   

    $load = Load::where('id', $id)->first();
    if (!$load) {
        return response()->json(['success' => false, 'message' => 'Load not found']);
    }

    $existingFiles = json_decode($load->carrierDoc, true) ?: [];

    // Remove file from DB
    $newFiles = array_filter($existingFiles, fn($f) => $f !== $file);
    $load->carrierDoc = json_encode(array_values($newFiles));
    $load->save();

    // Delete file from disk
    $fullPath = public_path($file);
    if (file_exists($fullPath)) {
        unlink($fullPath);
    }

    return response()->json(['success' => true]);
}


    public function viewLoadDetail($id)
    {
        $load = Load::findOrFail($id);
        $logs = activity_log::where('load_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        $seenMailMessages = [];
        $alllogs = $logs->reject(function ($log) use (&$seenMailMessages) {
            $message = (string) $log->message;
            if (!str_starts_with($message, 'mail send to customer ')) {
                return false;
            }

            if (isset($seenMailMessages[$message])) {
                return true;
            }

            $seenMailMessages[$message] = true;
            return false;
        })->values();

        return view('accounts.view_loads_detail', compact('load', 'alllogs'));
    }
	
	public function accountuploadRemittance(Request $request)
	{
		$request->validate([
			'customer_id' => 'required|exists:customers,id',
			'remittance.*' => 'file|mimes:pdf,jpg,jpeg,png|max:2048',
		]);

		$customer = Customer::findOrFail($request->customer_id);
		$existingFiles = json_decode($customer->remittance, true);
		$existingFiles = is_array($existingFiles) ? $existingFiles : [];
		$timezone = 'America/New_York';
		$timestamp = Carbon::now($timezone)->format('Y-m-d H:i:s');
		$allFiles = [];

		if ($request->hasFile('remittance')) {
			foreach ($request->file('remittance') as $file) {
				$originalName = $file->getClientOriginalName();

				// Remove special characters (keep letters, numbers, dots, underscores, hyphens)
				$sanitizedFilename = preg_replace('/[^A-Za-z0-9.\-_]/', '_', pathinfo($originalName, PATHINFO_FILENAME));

				// Add timestamp and keep original extension
				$extension = $file->getClientOriginalExtension();
				$filename = time() . '_' . $sanitizedFilename . '.' . $extension;

				$filePath = 'uploads/remittances/' . $filename;
				$file->move(public_path('uploads/remittances'), $filename);

				$allFiles[] = [
					'path' => $filePath,
					'uploaded_at' => $timestamp,
				];
			}

			$mergedFiles = array_merge($existingFiles, $allFiles);
			$customer->remittance = json_encode($mergedFiles);
			$customer->uploaded_at = $timestamp; // <-- Save in DB column
			$customer->save();
		}

		return response()->json([
			'status' => 'success',
			'files' => $mergedFiles ?? $existingFiles,
		]);
	}
	
	public function account_remittance(Request $request)
    {
       
		$customers = Customer::with('user.teamLeaderInfo','user.managerInfo')->orderBy("id", "desc")->paginate(50);
		$allcountry = Country::get();
            
		
		if ($request->ajax()) {
                return response()->json([
                    'html' => view('accounts.partials.remittance_table', compact('allcountry', 'customers'))->render(),
                    'pagination' => render_pagination_links($customers),
                ]);
			}
        
        return view('accounts.remittance', compact('allcountry','customers'));
    }
	
	public function customer_search(Request $request){
		
			
		$q = $request->input('query');
        if (!empty($q)) {
            // Split the query by commas to get multiple terms
            $searchTerms = array_filter(explode(',', $q), function($term) {
                return !empty(trim($term)); // Only keep non-empty terms
            });

            if (count($searchTerms) > 0) {
				
				// Search for non-empty terms with 'orWhere'
                $customers = Customer::with('user.teamLeaderInfo','user.managerInfo')
                    ->where(function($query) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $query->orWhere('customer_name', 'like', "%$term%");
                        }
                    })
                    ->orderBy('id', 'desc')
                    ->paginate(10);
				
                
					$allcountry = Country::get();
            } else {
                // If no valid terms, return an empty collection or handle accordingly
                $customers = collect();
				$allcountry = Country::get();
            }
        } else {
           
				$customers = Customer::with('user.teamLeaderInfo','user.managerInfo')->orderBy("id", "desc")->paginate(10);
				$allcountry = Country::get();
			
        }
        
		
		return view('accounts.partials.remittance_table', compact('allcountry','customers'))->render();
			
	}


	public function accountfilterRemittanceFiles(Request $request)
	{
		$request->validate([
			'customer_id' => 'required|exists:customers,id'
		]);

		$customer = Customer::findOrFail($request->customer_id);
		$allFiles = json_decode($customer->remittance ?? '[]', true);

		$startDate = $request->start_date;
		$endDate = $request->end_date;

		$filteredFiles = array_filter($allFiles, function ($file) use ($startDate, $endDate) {
			if (!isset($file['uploaded_at'])) return false;

			$fileTime = Carbon::parse($file['uploaded_at']);

			if ($startDate && $fileTime->lt(Carbon::parse($startDate)->startOfDay())) {
				return false;
			}

			if ($endDate && $fileTime->gt(Carbon::parse($endDate)->endOfDay())) {
				return false;
			}

			return true;
		});
        usort($filteredFiles, function ($a, $b) {
            return Carbon::parse($b['uploaded_at'])->timestamp <=> Carbon::parse($a['uploaded_at'])->timestamp;
        });

        foreach ($filteredFiles as &$file) {
            if (!isset($file['note'])) {
                $file['note'] = '';
            }
        }
		return response()->json([
			'files' => array_values($filteredFiles) // reindex
		]);
	}



	public function accountremittanceFiles(Request $request)
	{
		$request->validate([
			'customer_id' => 'required|exists:customers,id'
		]);

		$customer = Customer::findOrFail($request->customer_id);
		$files = json_decode($customer->remittance ?? '[]', true);
 rsort($files);
		return response()->json(['files' => $files]);
	}

	public function accountdeleteRemittanceFile(Request $request)
	{
		$request->validate([
			'customer_id' => 'required|exists:customers,id',
			'file' => 'required|string'
		]);

		
		$customer = Customer::findOrFail($request->customer_id);
		$files = json_decode($customer->remittance, true) ?? [];
		
		$pathsOnly = array_column($files, 'path'); // extract only the path values

		if (($key = array_search($request->file, $pathsOnly)) !== false) {
			// Remove the whole file record at that index
			unset($files[$key]);

			// Reindex and save
			$customer->remittance = json_encode(array_values($files));
			$customer->save();

			// Delete the file from disk
			$filePath = public_path($request->file);
			if (File::exists($filePath)) {
				File::delete($filePath);
			}
		}

		return response()->json(['status' => 'success', 'message' => 'File deleted.']);
	}
	
	
	/******excle files*********/
	
	public function loadsExcel($id)
    {
 

        if($id == 'Paid'){
            $loads = Load::with('user')->where('invoice_status', $id)->get(); 
        }elseif($id == 'Paid Record'){
            $loads = Load::with('user')->where('invoice_status', $id)->get(); 
        }else{
            $loads = Load::with('user')->where('load_status', $id)->get(); 
        }
        // Retrieve users from the database
        

        // Create a new Spreadsheet object
        $spreadsheet = new Spreadsheet();

        // Set the active sheet and title
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Loads');

        // Add headers
        $sheet->setCellValue('A1', 'Sr.no');
        $sheet->setCellValue('B1', 'Load');
        $sheet->setCellValue('C1', 'Agent');
        $sheet->setCellValue('D1', 'W/O');
        $sheet->setCellValue('E1', 'Customer Name');
        $sheet->setCellValue('F1', 'Office');
        $sheet->setCellValue('G1', 'Team Leader');
        $sheet->setCellValue('H1', 'Manager');
        $sheet->setCellValue('I1', 'Load Creation Date');
        $sheet->setCellValue('J1', 'Shipper Date');
        $sheet->setCellValue('K1', 'Delivered Date');
        $sheet->setCellValue('L1', 'Carrier Name');
        $sheet->setCellValue('M1', 'Pickup Location');
        $sheet->setCellValue('N1', 'Unloading Location');
        $sheet->setCellValue('O1', 'Load Status');

       
                               

        // Populate rows with user data
        $row = 2;
        foreach ($loads as $load) {


            $shipper_appointment = json_decode($load->load_shipper_appointment,true);
            $consignee_appointment = json_decode($load->load_consignee_appointment, true);
            $shipper_location = json_decode($load->load_shipper_location,true);
            $consignee_loaction = json_decode($load->load_consignee_location, true);
    
            if($load->load_status == 'Open'){
               $status = 'Open';
            }elseif($load->load_status == 'Delivered' && $load->invoice_status == 'Paid'){
                $status = 'Invoiced';
            }elseif($load->load_status == 'Delivered' && $load->invoice_status != 'Paid' && $load->invoice_status != 'Paid Record'){
                $status = 'Delivered';
            }elseif($load->load_status == 'Delivered' && $load->invoice_status == 'Paid Record'){
                $status = 'Paid';
            }else{
                $status = '';
            }
    
            $shipperRate = floatval($load->shipper_load_final_rate);
            $carrierFee = floatval($load->load_final_carrier_fee);
            $getMargin = $shipperRate - $carrierFee;
    
    
            if($load->load_status == "Open"){
                $cpr_status = $load->cpr_check;
            }elseif($load->load_status == "Delivered"){
                $cpr_status = "Verified";
            }elseif($load->load_status !== "Delivered"){
                $cpr_status = "Not Verified";
            }
         
            
            $invoicedate = Carbon::parse($load->invoice_date)->format('m-d-Y');
            $shipper_appointmentdate = isset($shipper_appointment[0]['appointment']) ? Carbon::parse($shipper_appointment[0]['appointment'])->format('m-d-Y') : '';
            $consignee_appointmentdate = isset($consignee_appointment[0]['appointment']) ? Carbon::parse($consignee_appointment[0]['appointment'])->format('m-d-Y') : '';
            $created_at = Carbon::parse($load->created_at)->format('m-d-Y');


            $shipper_locations = json_decode($load->load_shipper_location,true);
            $consignee_loactions = json_decode($load->load_consignee_location,true);

            $shipper_location = isset($shipper_locations[0]['location']) ? $shipper_locations[0]['location'] : '';
            $consignee_loaction = isset($consignee_loactions[0]['location']) ? $consignee_loactions[0]['location'] : '';

            $differenceInDays = null;
            $isInvoiceStatusEmpty = empty($load->invoice_status);
        
            if (isset($load->invoice_date)) {
                $invoiceDate = Carbon::parse($load->invoice_date);
                $currentDate = Carbon::now();
        
                if ($load->invoice_status == 'Paid') {
                    $differenceInDays = $invoiceDate->diffInDays($currentDate);
                } elseif ($load->invoice_status == 'Paid Record') {
                    // If the invoice status is 'Paid Record', aging is complete
                    $differenceInDays = 'Paid';
                }
            }

            if($isInvoiceStatusEmpty || $differenceInDays === null){
                $days = "-";
            }elseif($load->invoice_status == 'Paid'){
                $days = $differenceInDays .' Days';
            }elseif($load->invoice_status == 'Paid Record'){
                $days = $differenceInDays;
            }else{
                $days = "-";
            }
           
            $sheet->setCellValue('A' . $row, $row+1);
            $sheet->setCellValue('B' . $row, $load->id);
            $sheet->setCellValue('C' . $row, $load->user->name);
            $sheet->setCellValue('D' . $row, $load->load_workorder);
            $sheet->setCellValue('E' . $row, $load->load_bill_to);
            $sheet->setCellValue('F' . $row, $load->user->officedata?->office_name);
            $sheet->setCellValue('G' . $row, $load->user->teamLeaderInfo?->tl);
            $sheet->setCellValue('H' . $row, $load->user->managerInfo?->manager);
            $sheet->setCellValue('I' . $row, $created_at);
            $sheet->setCellValue('J' . $row, $shipper_appointmentdate);
            $sheet->setCellValue('K' . $row, $consignee_appointmentdate);
            $sheet->setCellValue('L' . $row, $load->load_carrier);
            $sheet->setCellValue('M' . $row, $shipper_location);
            $sheet->setCellValue('N' . $row, $consignee_loaction);
            $sheet->setCellValue('O' . $row, $status);
            $row++;
         }

        // Write the spreadsheet to a file in memory
        $writer = new Xlsx($spreadsheet);

        // Set headers for file download
        $filename = $id.'loads.xlsx';
        $file = $this->getFileStream($writer);

        // Return file as a download response
        return response()->stream(
            function () use ($file) {
                echo $file;
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment;filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0',
            ]
        );
    }


    private function getFileStream($writer)
    {
        // Create an output stream (no file, just in memory)
        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }


    public function loadsPdf($id)
    {
        // Fetch the data based on the status
        if ($id == 'Paid') {
            $loads = Load::with('user')->where('invoice_status', $id)->get();
        } elseif ($id == 'Paid Record') {
            $loads = Load::with('user')->where('invoice_status', $id)->get();
        } else {
            $loads = Load::with('user')->where('load_status', $id)->get();
        }

        // Initialize DOMPDF
        $dompdf = new Dompdf();

        // Create HTML content for the PDF
        $html = '<h1>Loads Data</h1>';
        $html .= '<table border="1" cellpadding="10" cellspacing="0">';
        $html .= '<thead>';
        $html .= '<tr><th>Sr.no</th><th>Load</th><th>Agent</th><th>W/O</th><th>Customer Name</th><th>Office</th><th>Team Leader</th><th>Manager</th><th>Load Creation Date</th><th>Shipper Date</th><th>Delivered Date</th><th>Carrier Name</th><th>Pickup Location</th><th>Unloading Location</th><th>Load Status</th></tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        // Loop through each load and generate the rows
        $row = 1;
        foreach ($loads as $load) {
            $shipper_appointment = json_decode($load->load_shipper_appointment, true);
            $consignee_appointment = json_decode($load->load_consignee_appointment, true);

            $shipper_locations = json_decode($load->load_shipper_location,true);
            $consignee_loactions = json_decode($load->load_consignee_location,true);

            $shipper_location = isset($shipper_locations[0]['location']) ? $shipper_locations[0]['location'] : '';
            $consignee_location = isset($consignee_loactions[0]['location']) ? $consignee_loactions[0]['location'] : '';

            $status = $this->getLoadStatus($load);

            $shipper_appointmentdate = isset($shipper_appointment[0]['appointment']) ? Carbon::parse($shipper_appointment[0]['appointment'])->format('m-d-Y') : '';
            $consignee_appointmentdate = isset($consignee_appointment[0]['appointment']) ? Carbon::parse($consignee_appointment[0]['appointment'])->format('m-d-Y') : '';
            $created_at = Carbon::parse($load->created_at)->format('m-d-Y');
            $invoicedate = Carbon::parse($load->invoice_date)->format('m-d-Y');

            $days = $this->getDaysDifference($load);  // A method to calculate the days difference for aging

            $html .= '<tr>';
            $html .= '<td>' . $row++ . '</td>';
            $html .= '<td>' . $load->id . '</td>';
            $html .= '<td>' . $load->user->name . '</td>';
            $html .= '<td>' . $load->load_workorder . '</td>';
            $html .= '<td>' . $load->load_bill_to . '</td>';
            $html .= '<td>' . $load->user->office . '</td>';
            $html .= '<td>' . $load->user->team_lead . '</td>';
            $html .= '<td>' . $load->user->manager . '</td>';
            $html .= '<td>' . $created_at . '</td>';
            $html .= '<td>' . $shipper_appointmentdate . '</td>';
            $html .= '<td>' . $consignee_appointmentdate . '</td>';
            $html .= '<td>' . $load->load_carrier . '</td>';
            $html .= '<td>' . $shipper_location . '</td>';
            $html .= '<td>' . $consignee_location . '</td>';
            $html .= '<td>' . $status . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        // Load HTML to DOMPDF
        $dompdf->loadHtml($html);

        // (Optional) Set paper size
        $width = 2000;  // Custom width (mm)
        $height = 2000; // Custom height (mm)
        
        // Set custom paper size with 'portrait' or 'landscape' orientation
        $dompdf->setPaper([0, 0, $width, $height], 'landscape');

        // Render the PDF
        $dompdf->render();

        // Output the generated PDF (force download)
        return $dompdf->stream($id.'loads.pdf', array("Attachment" => 1));
    }

    private function getLoadStatus($load)
    {
        if ($load->load_status == 'Open') {
            return 'Open';
        } elseif ($load->load_status == 'Delivered' && $load->invoice_status == 'Paid') {
            return 'Invoiced';
        } elseif ($load->load_status == 'Delivered' && $load->invoice_status != 'Paid' && $load->invoice_status != 'Paid Record') {
            return 'Delivered';
        } elseif ($load->load_status == 'Delivered' && $load->invoice_status == 'Paid Record') {
            return 'Paid';
        } else {
            return '';
        }
    }

    private function getDaysDifference($load)
    {
        $differenceInDays = null;
        if (isset($load->invoice_date)) {
            $invoiceDate = Carbon::parse($load->invoice_date);
            $currentDate = Carbon::now();

            if ($load->invoice_status == 'Paid') {
                $differenceInDays = $invoiceDate->diffInDays($currentDate);
            } elseif ($load->invoice_status == 'Paid Record') {
                $differenceInDays = 'Paid';
            }
        }

        if ($differenceInDays === null) {
            return '-';
        }

        if ($load->invoice_status == 'Paid') {
            return $differenceInDays . ' Days';
        } elseif ($load->invoice_status == 'Paid Record') {
            return $differenceInDays;
        }

        return '-';
    }

    public function allloadsPdf($id)
    {
        // Fetch the data based on the status
        if ($id == 'Customer') {
            $loads = customer::get();
        }
        
        if($id == 'Carriers'){
            $external = External::get();
        }

        if($id == 'Consignee'){
            $consignee = Consignee::get();
        }

        if($id == 'Shipper'){
            $shipper = Shipper::get();
        }

        if($id == 'Loads'){
            $load = Load::get();
        }

        // Initialize DOMPDF
        $dompdf = new Dompdf();

        // Create HTML content for the PDF
        if ($id == 'Customer') {
            $html = '<h1>Customer</h1>';
            $html .= '<table border="1" cellpadding="10" cellspacing="0">';
            $html .= '<thead>';
            $html .= '<tr>
                        <th>Sr.no</th>
                        <th>Agent</th>
                        <th>Company</th>
                        <th>Address</th>
                        <th>Phone No</th>
                        <th>Date Added</th>
                        <th>Team Leader</th>
                        <th>Manager</th>
                        <th>Office</th>
                        <th>Requested Credit</th>
                        <th>Credit Used</th>
                        <th>Remaining Limit</th>
                        <th>Approved Status</th>
                        </tr>';
            $html .= '</thead>';
            $html .= '<tbody>';

            // Loop through each load and generate the rows
            $row = 1;
            foreach ($loads as $load) {

                $html .= '<tr>';
                $html .= '<td>' . $row++ . '</td>';
                $html .= '<td>' . $load->user->name . '</td>';
                $html .= '<td>' . $load->customer_name . '</td>';
                $html .= '<td>' . $load->customer_address . ', ' . $load->customer_city . ', ' . $load->customer_state . ', ' . $load->customer_country . ', ' . $load->customer_zip . '</td>';
                $html .= '<td>' . $load->customer_telephone . '</td>';
                $html .= '<td>' . $load->created_at . '</td>';
                $html .= '<td>' . $load->user->team_lead . '</td>';
                $html .= '<td>' . $load->user->manager . '</td>';
                $html .= '<td>' . $load->user->office . '</td>';
                $html .= '<td>' . $load->remaining_credit_amount . '</td>';
                $html .= '<td>' . $load->remaining_credit_amount . '</td>';
                $html .= '<td>' . $load->remaining_credit . '</td>';
                $html .= '<td>' . $load->status . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';
        }

            if($id == 'Carriers'){
                $html = '<h1>Carrier List</h1>';
                $html .= '<table border="1" cellpadding="10" cellspacing="0">';
                $html .= '<thead>';
                $html .= '<tr>
                            <th>Sr.no</th>
                            <th>Carrier Name</th>
                            <th>MC#</th>
                            <th>DOT#</th>
                            <th>Address</th>
                            <th>Phone No</th>
                            <th>Added Date</th>
                            <th>Agent</th>
                            <th>Team Leader</th>
                            <th>Manager</th>
                            <th>Status</th>

                            </tr>';
                $html .= '</thead>';
                $html .= '<tbody>';

                // Loop through each load and generate the rows
                $row = 1;
                foreach ($external as $carrier) {

                    $html .= '<tr>';
                    $html .= '<td>' . $row++ . '</td>';
                    $html .= '<td>' . $carrier->carrier_name . '</td>';
                    $html .= '<td>' . $carrier->carrier_mc_ff_input . '</td>';
                    $html .= '<td>' . $carrier->carrier_dot . '</td>';
                    $html .= '<td>' . $carrier->carrier_address_two . ', ' . $carrier->carrier_city . ', ' . $carrier->carrier_state . ', ' . $carrier->carrier_country . ', ' . $carrier->carrier_zip . '</td>';
                    $html .= '<td>' . $carrier->carrier_telephone . '</td>';
                    $html .= '<td>' . $carrier->created_at . '</td>';
                    $html .= '<td>' . $carrier->user->name . '</td>';
                    $html .= '<td>' . $carrier->user->team_lead . '</td>';
                    $html .= '<td>' . $carrier->user->manager . '</td>';
                    $html .= '<td>' . $carrier->user->carrier_status . '</td>';
                    $html .= '</tr>';
                }

                $html .= '</tbody>';
                $html .= '</table>';
        }

            if($id == 'Consignee'){
                $html = '<h1>Consignee List</h1>';
                $html .= '<table border="1" cellpadding="10" cellspacing="0">';
                $html .= '<thead>';
                $html .= '<tr>
                            <th>Sr.no</th>
                            <th>Consignee Name</th>
                            <th>Address</th>
                            <th>Phone No</th>
                            <th>Added Date</th>
                            <th>Agent</th>
                            <th>Team Leader</th>
                            <th>Manager</th>
                            <th>Status</th>
                            </tr>';
                $html .= '</thead>';
                $html .= '<tbody>';

                // Loop through each load and generate the rows
                $row = 1;
                foreach ($consignee as $consignees) {

                    $html .= '<tr>';
                    $html .= '<td>' . $row++ . '</td>';
                    $html .= '<td>' . $consignees->consignee_name . '</td>';
                    $html .= '<td>' . $consignees->consignee_address . ', ' . $consignees->consignee_city . ', ' . $consignees->consignee_state . ', ' . $consignees->consignee_country . ', ' . $consignees->consignee_zip . '</td>';
                    $html .= '<td>' . $consignees->consignee_telephone . '</td>';
                    $html .= '<td>' . $consignees->created_at . '</td>';
                    $html .= '<td>' . $consignees->user->name . '</td>';
                    $html .= '<td>' . $consignees->user->team_lead . '</td>';
                    $html .= '<td>' . $consignees->user->manager . '</td>';
                    $html .= '<td>' . $consignees->user->consignee_status . '</td>';
                    $html .= '</tr>';
                }

                $html .= '</tbody>';
                $html .= '</table>';
        }

            if($id == 'Shipper'){
                $html = '<h1>Shipper List</h1>';
                $html .= '<table border="1" cellpadding="10" cellspacing="0">';
                $html .= '<thead>';
                $html .= '<tr>
                            <th>Sr.no</th>
                            <th>Shipper Name</th>
                            <th>Address</th>
                            <th>Phone No</th>
                            <th>Added Date</th>
                            <th>Agent</th>
                            <th>Team Leader</th>
                            <th>Manager</th>
                            <th>Status</th>
                            </tr>';
                $html .= '</thead>';
                $html .= '<tbody>';

                // Loop through each load and generate the rows
                $row = 1;
                foreach ($shipper as $shippers) {

                    $html .= '<tr>';
                    $html .= '<td>' . $row++ . '</td>';
                    $html .= '<td>' . $shippers->shipper_name . '</td>';
                    $html .= '<td>' . $shippers->shipper_address . ', ' . $shippers->shipper_city . ', ' . $shippers->shipper_state . ', ' . $shippers->shipper_country . ', ' . $shippers->shipper_zip . '</td>';
                    $html .= '<td>' . $shippers->shipper_telephone . '</td>';
                    $html .= '<td>' . $shippers->created_at . '</td>';
                    $html .= '<td>' . $shippers->user->name . '</td>';
                    $html .= '<td>' . $shippers->user->team_lead . '</td>';
                    $html .= '<td>' . $shippers->user->manager . '</td>';
                    $html .= '<td>' . $shippers->user->consignee_status . '</td>';
                    $html .= '</tr>';
                }

                $html .= '</tbody>';
                $html .= '</table>';
        }

            if($id == 'Loads'){
            $html = '<h1>Loads Data</h1>';
            $html .= '<table border="1" cellpadding="10" cellspacing="0">';
            $html .= '<thead>';
            $html .= '<tr><th>Sr.no</th><th>Load</th><th>Agent</th><th>W/O</th><th>Customer Name</th><th>Office</th><th>Team Leader</th><th>Manager</th><th>Load Creation Date</th><th>Shipper Date</th><th>Delivered Date</th><th>Carrier Name</th><th>Pickup Location</th><th>Unloading Location</th><th>Load Status</th></tr>';
            $html .= '</thead>';
            $html .= '<tbody>';

            // Loop through each load and generate the rows
            $row = 1;
            foreach ($load as $loads) {
                $shipper_appointment = json_decode($loads->load_shipper_appointment, true);
                $consignee_appointment = json_decode($loads->load_consignee_appointment, true);

                $shipper_locations = json_decode($loads->load_shipper_location,true);
                $consignee_loactions = json_decode($loads->load_consignee_location,true);

                $shipper_location = isset($shipper_locations[0]['location']) ? $shipper_locations[0]['location'] : '';
                $consignee_location = isset($consignee_loactions[0]['location']) ? $consignee_loactions[0]['location'] : '';

                $status = $this->getLoadStatus($loads);

                $shipper_appointmentdate = isset($shipper_appointment[0]['appointment']) ? Carbon::parse($shipper_appointment[0]['appointment'])->format('m-d-Y') : '';
                $consignee_appointmentdate = isset($consignee_appointment[0]['appointment']) ? Carbon::parse($consignee_appointment[0]['appointment'])->format('m-d-Y') : '';
                $created_at = Carbon::parse($loads->created_at)->format('m-d-Y');
                $invoicedate = Carbon::parse($loads->invoice_date)->format('m-d-Y');

                $days = $this->getDaysDifference($loads);  // A method to calculate the days difference for aging

                $html .= '<tr>';
                $html .= '<td>' . $row++ . '</td>';
                $html .= '<td>' . $loads->id . '</td>';
                $html .= '<td>' . $loads->user->name . '</td>';
                $html .= '<td>' . $loads->load_workorder . '</td>';
                $html .= '<td>' . $loads->load_bill_to . '</td>';
                $html .= '<td>' . $loads->user->office . '</td>';
                $html .= '<td>' . $loads->user->team_lead . '</td>';
                $html .= '<td>' . $loads->user->manager . '</td>';
                $html .= '<td>' . $created_at . '</td>';
                $html .= '<td>' . $shipper_appointmentdate . '</td>';
                $html .= '<td>' . $consignee_appointmentdate . '</td>';
                $html .= '<td>' . $loads->load_carrier . '</td>';
                $html .= '<td>' . $shipper_location . '</td>';
                $html .= '<td>' . $consignee_location . '</td>';
                $html .= '<td>' . $status . '</td>';
                $html .= '</tr>';
            }
        }
            // Load HTML to DOMPDF
        $dompdf->loadHtml($html);

        // (Optional) Set paper size
        $width = 2000;  // Custom width (mm)
        $height = 2000; // Custom height (mm)
        
        // Set custom paper size with 'portrait' or 'landscape' orientation
        $dompdf->setPaper([0, 0, $width, $height], 'landscape');

        // Render the PDF
        $dompdf->render();

        // Output the generated PDF (force download)
        return $dompdf->stream($id. 'loads.pdf', array("Attachment" => 1));
    }

    public function allloadsExcel($id)
{
    // default initializations
    $headers = [];
    $columns = [];
    $data = collect();

    if ($id == 'Customer') {
        $data = customer::get();
        $headers = [
            'Sr.no', 'Agent', 'Company', 'Address', 'Phone No',
            'Date Added', 'Team Leader', 'Manager', 'Office',
            'Requested Credit', 'Credit Used', 'Remaining Limit', 'Approved Status'
        ];
        $columns = [
            'user.name', 'customer_name', 'customer_address', 'customer_telephone', 'created_at',
            'user.teamLeaderInfo.tl', 'user.managerInfo.manager', 'user.office', 'remaining_credit_amount',
            'remaining_credit', 'remaining_credit', 'status'
        ];
    } elseif ($id == 'Carriers') {
        $data = External::get();
        $headers = [
            'Sr.no', 'Carrier Name', 'MC#', 'DOT#', 'Address',
            'Phone No', 'Added Date', 'Agent', 'Team Leader',
            'Manager', 'Status'
        ];
        $columns = [
            'carrier_name', 'carrier_mc_ff_input', 'carrier_dot', 'carrier_address_two',
            'carrier_telephone', 'created_at', 'user.name', 'user.teamLeaderInfo.tl', 'user.managerInfo.manager', 'user.carrier_status'
        ];
    } elseif ($id == 'Consignee') {
        $data = Consignee::get();
        $headers = [
            'Sr.no', 'Consignee Name', 'Address', 'Phone No',
            'Added Date', 'Agent', 'Team Leader', 'Manager', 'Status'
        ];
        $columns = [
            'consignee_name', 'consignee_address', 'consignee_telephone', 'created_at',
            'user.name', 'user.teamLeaderInfo.tl', 'user.managerInfo.manager', 'user.consignee_status'
        ];
    } elseif ($id == 'Shipper') {
        $data = Shipper::get();
        $headers = ['Sr.no', 'Shipper Name', 'Address', 'Phone No', 'Added Date', 'Team Leader', 'Manager', 'Status'];
        $columns = ['shipper_name', 'shipper_address', 'shipper_telephone', 'created_at', 'user.teamLeaderInfo.tl', 'user.managerInfo.manager', 'shipper_status'];
    } elseif ($id == 'Loads') {
        // === SPECIAL HANDLING FOR LOADS: reproduce loadCompleteReportingExcel behaviour ===
        $data = Load::with('user')->get();

        // compute max consignee count (for dynamic unloading columns)
        $maxConsignees = 0;
        foreach ($data as $item) {
            $consignee_location = json_decode($item->load_consignee_location, true);
            if (is_array($consignee_location)) {
                $maxConsignees = max($maxConsignees, count($consignee_location));
            }
        }

        // base headers (match your other export)
        $headers = ['Sr.no', 'load Number', 'Invoice No', 'Agent Name', 'Load Status', 'Invoice Status', 'Customer Reference #', 'Load Create Date', 'Customer Name', 'Carrier Name', 'Pickup Location'];

        // add dynamic unloading location columns
        for ($i = 1; $i <= $maxConsignees; $i++) {
            $headers[] = "Unloading Location $i";
        }

        // final headers
        $headers = array_merge($headers, ['Load Type','Carrier Advance Payment','Actual Delivery Date','Carrier Due Date','Carrier Mark Payment Date','Carrier Fee','Shipper Rate','Invoice Date','Paper work Received Date','Payment Receiving Date','Customer Payment Received Amount','Customer Payment Mark Date','Customer Rate','Carrier Rate','Margin','Work Order','CPR Check','Macro Sent','Delivery Date' ]);

        // create spreadsheet and fill (mirrors your loadCompleteReportingExcel)
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data');

        // write headers
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // populate rows
        $row = 2;
        foreach ($data as $index => $item) {
            $col = 'A';
            $sheet->setCellValue($col . $row, $index + 1);
            $col++;

            $shipper_location = [];
            $consignee_location = [];
            $shipper_appointment = [];
            $consignee_appointment = [];

            $shipper_location = json_decode($item->load_shipper_location, true) ?: [];
            $consignee_location = json_decode($item->load_consignee_location, true) ?: [];
            $shipper_appointment = json_decode($item->load_shipper_appointment, true) ?: [];
            $consignee_appointment = json_decode($item->load_consignee_appointment, true) ?: [];

            // core columns (keeping the same logic as your original export)
            $sheet->setCellValue($col . $row, $item->load_number ?? '');
            $col++;
            $sheet->setCellValue($col . $row, in_array($item->invoice_status, ['Paid Record', 'Paid']) ? ($item->invoice_number ?? '') : '');
            $col++;
            $sheet->setCellValue($col . $row, $item->user->name ?? '');
            $col++;
            $sheet->setCellValue($col . $row, $item->load_status ?? '');
            $col++;
            $sheet->setCellValue($col . $row, empty($item->invoice_status) ? '' : ($item->invoice_status == 'Paid' ? 'Invoiced' : $item->invoice_status));
            $col++;
            $sheet->setCellValue($col . $row, $item->customer_refrence_number ?? '');
            $col++;
            $sheet->setCellValue($col . $row, $item->created_at ? $item->created_at->setTimezone('America/New_York')->format('m/d/Y') : '');
            $col++;
            $sheet->setCellValue($col . $row, $item->load_bill_to ?? '');
            $col++;
            $sheet->setCellValue($col . $row, $item->load_carrier ?? '');
            $col++;
            $sheet->setCellValue($col . $row, $shipper_location[0]['location'] ?? '');
            $col++;

            // unloading locations
            if (is_array($consignee_location)) {
                foreach ($consignee_location as $idx => $loc) {
                    $sheet->setCellValue($col . $row, $loc['location'] ?? '');
                    $col++;
                }
            }

            // fill remaining empty unloading columns
            if (is_array($consignee_location)) {
                $remaining = $maxConsignees - count($consignee_location);
                for ($i = 0; $i < $remaining; $i++) {
                    $sheet->setCellValue($col . $row, '');
                    $col++;
                }
            } else {
                for ($i = 0; $i < $maxConsignees; $i++) {
                    $sheet->setCellValue($col . $row, '');
                    $col++;
                }
            }

            // remaining detailed columns
            $sheet->setCellValue($col . $row, $item->load_type_two ?? '');
            $col++;
            $sheet->setCellValue($col . $row, $item->load_advance_payment ?? '');
            $col++;
            $sheet->setCellValue($col . $row, $item->load_actual_delivery_date ? \Carbon\Carbon::parse($item->load_actual_delivery_date)->format('m/d/Y') : '');
            $col++;
            $sheet->setCellValue($col . $row, $item->load_carrier_due_date ? \Carbon\Carbon::parse($item->load_carrier_due_date)->format('m/d/Y') : '');
            $col++;
            $sheet->setCellValue($col . $row, $item->load_carrier_due_date_on ? \Carbon\Carbon::parse($item->load_carrier_due_date_on)->format('m/d/Y') : '');
            $col++;
            $sheet->setCellValue($col . $row, $item->load_final_carrier_fee ?? '');
            $col++;
            $sheet->setCellValue($col . $row, $item->shipper_load_final_rate ?? '');
            $col++;
            $sheet->setCellValue($col . $row, in_array($item->invoice_status, ['Paid Record', 'Paid']) ? ($item->invoice_date ? \Carbon\Carbon::parse($item->invoice_date)->format('m/d/Y') : '') : '');
            $col++;
            $sheet->setCellValue($col . $row, in_array($item->invoice_status, ['Paid Record', 'Paid']) ? ($item->paper_work_date ? \Carbon\Carbon::parse($item->paper_work_date)->format('m/d/Y') : '') : '');
            $col++;
            $sheet->setCellValue($col . $row, $item->payment_receiving_date ? \Carbon\Carbon::parse($item->payment_receiving_date)->format('m/d/Y') : '');
            $col++;
            $sheet->setCellValue($col . $row, $item->invoice_status == 'Paid Record' ? ($item->receiving_amount ?? '-') : '');
            $col++;
            $sheet->setCellValue($col . $row, $item->invoice_status_date ? \Carbon\Carbon::parse($item->invoice_status_date)->format('m/d/Y') : '');
            $col++;
            $sheet->setCellValue($col . $row, $item->shipper_load_final_rate ?? '');
            $col++;
            $sheet->setCellValue($col . $row, $item->load_final_carrier_fee ?? '');
            $col++;

            $shipperLoadFinalRate = $item->shipper_load_final_rate ?? 0;
            $loadFinalCarrierFee = $item->load_final_carrier_fee ?? 0;
            $shipperLoadFinalRate = is_numeric($shipperLoadFinalRate) ? $shipperLoadFinalRate : 0;
            $loadFinalCarrierFee = is_numeric($loadFinalCarrierFee) ? $loadFinalCarrierFee : 0;
            $margin = $shipperLoadFinalRate - $loadFinalCarrierFee;
            $sheet->setCellValue($col . $row, number_format($margin, 2));
            $col++;

            $sheet->setCellValue($col . $row, $item->load_workorder ?? '');
            $col++;
            $sheet->setCellValue($col . $row, $item->cpr_check ?? '');
            $col++;
            $sheet->setCellValue($col . $row, $item->no_of_macro ?? '');
            $col++;

            $lastAppointment = !empty($consignee_appointment) ? end($consignee_appointment)['appointment'] : null;
            $formattedAppointment = $lastAppointment ? \Carbon\Carbon::parse($lastAppointment)->format('m/d/Y') : '-';
            $sheet->setCellValue($col . $row, $formattedAppointment);
            $col++;

            $row++;
        }

        // write and return file (same response pattern as your other function)
        $writer = new Xlsx($spreadsheet);
        $filename = 'Load Complete Report ' . date('Y-m-d') . '.xlsx';
        $file = $this->getFileStream($writer);

        return response()->stream(
            function () use ($file) {
                echo $file;
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment;filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0',
            ]
        );
    } else {
        return response()->json(['message' => 'Invalid data type.'], 400);
    }

    // --- For non-Loads exports: generic population using $data + $columns ---
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Data');

    // Add headers
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $col++;
    }

    // Populate rows with data using the dot-notation $columns
    $row = 2;
    foreach ($data as $index => $item) {
        $col = 'A';
        $sheet->setCellValue($col . $row, $index + 1); // Sr.no starts at 1
        $col++;

        foreach ($columns as $column) {
            $value = $item;
            foreach (explode('.', $column) as $segment) {
                if (is_object($value) && isset($value->{$segment})) {
                    $value = $value->{$segment};
                } elseif (is_array($value) && isset($value[$segment])) {
                    $value = $value[$segment];
                } else {
                    $value = '-'; // default
                    break;
                }
            }
            $sheet->setCellValue($col . $row, $value ?? '-');
            $col++;
        }
        $row++;
    }

    // Write file
    $writer = new Xlsx($spreadsheet);
    $filename = $id . '-export.xlsx';
    $file = $this->getFileStream($writer);

    return response()->stream(
        function () use ($file) {
            echo $file;
        },
        200,
        [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]
    );
}


    

    public function allcomplianceloadsPdf($id)
    {
        if($id == 'Mc'){
            $external = External::OrderBy('id', 'DESC')->get();
        }

        if($id == 'Cpr'){
            $load = Load::OrderBy('load_number', 'DESC')->get();
        }

        // Initialize DOMPDF
        $dompdf = new Dompdf();

        // Create HTML content for the PDF
        if ($id == 'Mc') {
            $html = '<h1>MC List</h1>';
            $html .= '<table border="1" cellpadding="10" cellspacing="0">';
            $html .= '<thead>';
            $html .= '<tr>
                        <th>Sr.no</th>
                        <th>MC NO</th>
                        <th>DOT</th>
                        <th>Carrier Name</th>
                        <th>Agent</th>
                        <th>Added Date</th>
                        <th>MC Status</th>
                        </tr>';
            $html .= '</thead>';
            $html .= '<tbody>';

            // Loop through each load and generate the rows
            $row = 1;
            foreach ($external as $externals) {

                $html .= '<tr>';
                $html .= '<td>' . $row++ . '</td>';
                $html .= '<td>' . $externals->carrier_mc_ff_input . '</td>';
                $html .= '<td>' . $externals->carrier_dot . '</td>';
                $html .= '<td>' . $externals->carrier_name . '</td>';
                $html .= '<td>' . $externals->user->name . '</td>';
                $html .= '<td>' . $externals->created_at . '</td>';
                $html .= '<td>' . $externals->mc_check . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';
        }

        if ($id == 'Cpr') {
            $html = '<h1>CPR List</h1>';
            $html .= '<table border="1" cellpadding="10" cellspacing="0">';
            $html .= '<thead>';
            $html .= '<tr>
                        <th>Sr.no</th>
                        <th>Load #</th>
                        <th>W/O #</th>
                        <th>Agent</th>
                        <th>Customer #</th>
                        <th>Customer Final Rate</th> <!-- Fixed Syntax -->
                        <th>Office</th>
                        <th>Team Leader</th>
                        <th>Manager</th>
                        <th>Load Creation Date</th>
                        <th>Equipment Type</th>
                        <th>Carrier Name</th>
                        <th>Carrier Final Rate</th>
                        <th>Macro Point</th>
                        <th>Macro Count</th>
                      </tr>';
            $html .= '</thead>';
            $html .= '<tbody>';
        
            // Loop through each load and generate the rows
            $row = 1;
            foreach ($load as $loads) {
                $html .= '<tr>';
                $html .= '<td>' . $row++ . '</td>';
                $html .= '<td>' . $loads->load_number . '</td>';
                $html .= '<td>' . $loads->load_workorder . '</td>';
                $html .= '<td>' . $loads->user->name . '</td>';
                $html .= '<td>' . $loads->load_bill_to . '</td>';
                $html .= '<td>' . $loads->shipper_load_final_rate . '</td>';
                $html .= '<td>' . $loads->user->office . '</td>';
                $html .= '<td>' . $loads->user->team_lead . '</td>';
                $html .= '<td>' . $loads->user->manager . '</td>';
                $html .= '<td>' . $loads->created_at . '</td>';  // Fixed `$loads->user->created_at`
                $html .= '<td>' . $loads->load_equipment_type . '</td>';
                $html .= '<td>' . $loads->load_carrier . '</td>';
                $html .= '<td>' . $loads->load_final_rate . '</td>';
                $html .= '<td>' . $loads->macro . '</td>';
                $html .= '<td>' . $loads->no_of_macro . '</td>';
                $html .= '</tr>';
            }
        
            $html .= '</tbody>';
            $html .= '</table>';
        }
        

            // Load HTML to DOMPDF
        $dompdf->loadHtml($html);

        // (Optional) Set paper size
        $width = 2000;  // Custom width (mm)
        $height = 2000; // Custom height (mm)
        
        // Set custom paper size with 'portrait' or 'landscape' orientation
        $dompdf->setPaper([0, 0, $width, $height], 'landscape');

        // Render the PDF
        $dompdf->render();

        // Output the generated PDF (force download)
        return $dompdf->stream($id. 'loads.pdf', array("Attachment" => 1));
    }
    

    public function allcomplianceloadsExcel($id)
    {
        // Initialize the required data based on the $id
        if ($id == 'Mc') {
            $data = External::orderBy('id', 'DESC')->get();
            $headers = ['Sr.no', 'MC NO', 'DOT', 'Carrier Name', 'Agent', 'Date Added', 'MC Status','Setup Status', 'Exception Note'];
            $columns = ['carrier_mc_ff_input', 'carrier_dot', 'carrier_name', 'user.name', 'created_at', 'mc_check','setup', 'compliance_exception_note'];
    
        } elseif ($id == 'Cpr') {
            $data = Load::with('user')->orderByRaw('CAST(load_number AS UNSIGNED) DESC')->get();
            $headers = ['Sr.no', 'Load #', 'Agent Name', 'Customer #', 'Office', 'Manager', 'Team Leader', 'Load Creation Date', 'Shipper Date', 'Delivery Date', 'Equipment Type', 'Carrier Name', 'CPR Status', 'Micro Point', 'Number of Macropoint', 'CPR contact number', 'Note', 'MC Number'];
            $columns = ['load_number', 'user.name', 'load_bill_to', 'user.officedata.office_name', 'user.managerInfo.manager', 'user.teamLeaderInfo.tl',  'created_at', 'load_shipper_appointment', 'load_consignee_appointment', 'load_equipment_type', 'load_carrier', 'cpr_check', 'macro', 'no_of_macro', '', '', 'load_mc_no'];
         
        } else {
            return response()->json(['message' => 'Invalid data type.'], 400);
        }
    
        // Create a new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data');
    
        // Add headers
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }
    
        // Bold the header row
        $sheet->getStyle('A1:' . chr(ord('A') + count($headers) - 1) . '1')->getFont()->setBold(true);
    
        // Populate rows with data
        $row = 2;
        foreach ($data as $index => $item) {
            $col = 'A';
            $sheet->setCellValue($col . $row, $index + 1); // Sr.no starts at 1
            $col++;
    
            foreach ($columns as $column) {
                if (empty($column)) {
                    $sheet->setCellValue($col . $row, '-');
                    $col++;
                    continue;
                }
    
                $value = $item;
    
                // Custom handling for JSON-encoded appointment field
                if ($column === 'load_shipper_appointment') {
                    $json = $item->load_shipper_appointment;
                    if (!empty($json)) {
                        $decoded = json_decode($json, true);
                        if (is_array($decoded) && !empty($decoded)) {
                            $lastKey = array_key_last($decoded);
                            $appointment = $decoded[$lastKey]['appointment'] ?? null;
                            $value = $appointment ? date('m-d-Y', strtotime($appointment)) : '-';
                        } else {
                            $value = '-';
                        }
                    } else {
                        $value = '-';
                    }
    
                }else if($column === 'load_consignee_appointment'){

                    $json = $item->load_consignee_appointment;
                    if (!empty($json)) {
                        $decoded = json_decode($json, true);
                        if (is_array($decoded) && !empty($decoded)) {
                            $lastKey = array_key_last($decoded);
                            $appointment = $decoded[$lastKey]['appointment'] ?? null;
                            $value = $appointment ? date('m-d-Y', strtotime($appointment)) : '-';
                        } else {
                            $value = '-';
                        }
                    } else {
                        $value = '-';
                    }
                }else {
                    // Normal nested property access
                    foreach (explode('.', $column) as $segment) {
                        if (isset($value->{$segment})) {
                            $value = $value->{$segment};
                        } else {
                            $value = '-';
                            break;
                        }
                    }
                }
    
                $sheet->setCellValue($col . $row, $value ?? '-');
                $col++;
            }
            $row++;
        }
    
        // Write the spreadsheet to a file in memory
        $writer = new Xlsx($spreadsheet);
        $filename = $id . '_loads.xlsx';
        $file = $this->getFileStream($writer);
    
        // Return file as a download response
        return response()->stream(
            function () use ($file) {
                echo $file;
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment;filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0',
            ]
        );
    }

    public function allaccountingloadsPdf($id)
    {
        if($id == 'Open'){
            $open = Load::where('load_status', 'Open')->get();
        }

        if($id == 'Completed'){
            $completed = Load::where('load_status', 'Completed')->whereNotIn('invoice_status', ['Paid', 'Record Paid'])->get();
        }

        if($id == 'Paid'){
            $paid = Load::where('load_status', 'Completed')->where('invoice_status', 'Paid')->get();
        }

        if($id == 'Paid Record'){
            $paid_record = Load::where('load_status', 'Completed')->where('invoice_status', 'Paid Record')->get();
        }

        // Initialize DOMPDF
        $dompdf = new Dompdf();

        // Create HTML content for the PDF

        if ($id == 'Open') {
            $html = '<h1>Open List</h1>';
            $html .= '<table border="1" cellpadding="10" cellspacing="0">';
            $html .= '<thead>';
            $html .= '<tr>
                        <th>Sr.no</th>
                        <th>Load #</th>
                        <th>Load Status</th>
                        <th>W/O #</th>
                        <th>Agent</th>
                        <th>Customer #</th>
                        <th>Customer Final Rate</th> <!-- Fixed Syntax -->
                        <th>Office</th>
                        <th>Team Leader</th>
                        <th>Manager</th>
                        <th>Load Creation Date</th>
                        <th>Equipment Type</th>
                        <th>Carrier Name</th>
                        <th>Carrier Final Rate</th>
                      </tr>';
            $html .= '</thead>';
            $html .= '<tbody>';
        
            // Loop through each load and generate the rows
            $row = 1;
            foreach ($open as $opens) {
                $html .= '<tr>';
                $html .= '<td>' . $row++ . '</td>';
                $html .= '<td>' . $opens->load_number . '</td>';
                $html .= '<td>' . $opens->load_status . '</td>';
                $html .= '<td>' . $opens->load_workorder . '</td>';
                $html .= '<td>' . $opens->user->name . '</td>';
                $html .= '<td>' . $opens->load_bill_to . '</td>';
                $html .= '<td>' . $opens->shipper_load_final_rate . '</td>';
                $html .= '<td>' . $opens->user->office . '</td>';
                $html .= '<td>' . $opens->user->team_lead . '</td>';
                $html .= '<td>' . $opens->user->manager . '</td>';
                $html .= '<td>' . $opens->created_at . '</td>';
                $html .= '<td>' . $opens->load_equipment_type . '</td>';
                $html .= '<td>' . $opens->load_carrier . '</td>';
                $html .= '<td>' . $opens->load_final_rate . '</td>';
                $html .= '</tr>';
            }
        
            $html .= '</tbody>';
            $html .= '</table>';
        }

        if ($id == 'Completed') {
            $html = '<h1>Completed List</h1>';
            $html .= '<table border="1" cellpadding="10" cellspacing="0">';
            $html .= '<thead>';
            $html .= '<tr>
                        <th>Sr.no</th>
                        <th>Load #</th>
                        <th>Load Status</th>
                        <th>W/O #</th>
                        <th>Agent</th>
                        <th>Customer #</th>
                        <th>Customer Final Rate</th> <!-- Fixed Syntax -->
                        <th>Office</th>
                        <th>Team Leader</th>
                        <th>Manager</th>
                        <th>Load Creation Date</th>
                        <th>Equipment Type</th>
                        <th>Carrier Name</th>
                        <th>Carrier Final Rate</th>
                      </tr>';
            $html .= '</thead>';
            $html .= '<tbody>';
        
            // Loop through each load and generate the rows
            $row = 1;
            foreach ($completed as $completeds) {
                $html .= '<tr>';
                $html .= '<td>' . $row++ . '</td>';
                $html .= '<td>' . $completeds->load_number . '</td>';
                $html .= '<td>' . $completeds->load_status . '</td>';
                $html .= '<td>' . $completeds->load_workorder . '</td>';
                $html .= '<td>' . $completeds->user->name . '</td>';
                $html .= '<td>' . $completeds->load_bill_to . '</td>';
                $html .= '<td>' . $completeds->shipper_load_final_rate . '</td>';
                $html .= '<td>' . $completeds->user->office . '</td>';
                $html .= '<td>' . $completeds->user->team_lead . '</td>';
                $html .= '<td>' . $completeds->user->manager . '</td>';
                $html .= '<td>' . $completeds->created_at . '</td>';
                $html .= '<td>' . $completeds->load_equipment_type . '</td>';
                $html .= '<td>' . $completeds->load_carrier . '</td>';
                $html .= '<td>' . $completeds->load_final_rate . '</td>';
                $html .= '</tr>';
            }
        
            $html .= '</tbody>';
            $html .= '</table>';
        }

        if ($id == 'Paid') {
            
            $html = '<h1>Invoice List</h1>';
            $html .= '<table border="1" cellpadding="10" cellspacing="0">';
            $html .= '<thead>';
            $html .= '<tr>
                        <th>Sr.no</th>
                        <th>Load #</th>
                        <th>Load Status</th>
                        <th>Invoice Status</th>
                        <th>W/O #</th>
                        <th>Agent</th>
                        <th>Customer #</th>
                        <th>Customer Final Rate</th> <!-- Fixed Syntax -->
                        <th>Office</th>
                        <th>Team Leader</th>
                        <th>Manager</th>
                        <th>Load Creation Date</th>
                        <th>Equipment Type</th>
                        <th>Carrier Name</th>
                        <th>Carrier Final Rate</th>
                      </tr>';
            $html .= '</thead>';
            $html .= '<tbody>';
        
            // Loop through each load and generate the rows
            $row = 1;
            foreach ($paid as $paids) {
                $convertname = ($paids->invoice_status == "Paid") ? "Invoiced" : $paids->invoice_status; // Dynamic conversion
                
                $html .= '<tr>';
                $html .= '<td>' . $row++ . '</td>';
                $html .= '<td>' . $paids->load_number . '</td>';
                $html .= '<td>' . $paids->load_status . '</td>';
                $html .= '<td>' . $convertname . '</td>'; // Always populate invoice status
                $html .= '<td>' . $paids->load_workorder . '</td>';
                $html .= '<td>' . $paids->user->name . '</td>';
                $html .= '<td>' . $paids->load_bill_to . '</td>';
                $html .= '<td>' . $paids->shipper_load_final_rate . '</td>';
                $html .= '<td>' . $paids->user->office . '</td>';
                $html .= '<td>' . $paids->user->team_lead . '</td>';
                $html .= '<td>' . $paids->user->manager . '</td>';
                $html .= '<td>' . $paids->created_at . '</td>';
                $html .= '<td>' . $paids->load_equipment_type . '</td>';
                $html .= '<td>' . $paids->load_carrier . '</td>';
                $html .= '<td>' . $paids->load_final_rate . '</td>';
                $html .= '</tr>';
            }
        
            $html .= '</tbody>';
            $html .= '</table>';
        }

        if ($id == 'Record Paid') {
            $html = '<h1>Payment Paid List</h1>';
            $html .= '<table border="1" cellpadding="10" cellspacing="0">';
            $html .= '<thead>';
            $html .= '<tr>
                        <th>Sr.no</th>
                        <th>Load #</th>
                        <th>Load Status</th>
                        <th>Invoice Status</th>
                        <th>W/O #</th>
                        <th>Agent</th>
                        <th>Customer #</th>
                        <th>Customer Final Rate</th> <!-- Fixed Syntax -->
                        <th>Office</th>
                        <th>Team Leader</th>
                        <th>Manager</th>
                        <th>Load Creation Date</th>
                        <th>Equipment Type</th>
                        <th>Carrier Name</th>
                        <th>Carrier Final Rate</th>
                      </tr>';
            $html .= '</thead>';
            $html .= '<tbody>';
        
            // Loop through each load and generate the rows
            $row = 1;
            foreach ($paid_record as $paid_records) {
                $html .= '<tr>';
                $html .= '<td>' . $row++ . '</td>';
                $html .= '<td>' . $paid_records->load_number . '</td>';
                $html .= '<td>' . $paid_records->load_status . '</td>';
                $html .= '<td>' . $paid_records->invoice_status . '</td>';
                $html .= '<td>' . $paid_records->load_workorder . '</td>';
                $html .= '<td>' . $paid_records->user->name . '</td>';
                $html .= '<td>' . $paid_records->load_bill_to . '</td>';
                $html .= '<td>' . $paid_records->shipper_load_final_rate . '</td>';
                $html .= '<td>' . $paid_records->user->office . '</td>';
                $html .= '<td>' . $paid_records->user->team_lead . '</td>';
                $html .= '<td>' . $paid_records->user->manager . '</td>';
                $html .= '<td>' . $paid_records->created_at . '</td>';
                $html .= '<td>' . $paid_records->load_equipment_type . '</td>';
                $html .= '<td>' . $paid_records->load_carrier . '</td>';
                $html .= '<td>' . $paid_records->load_final_rate . '</td>';
                $html .= '</tr>';
            }
        
            $html .= '</tbody>';
            $html .= '</table>';
        }
            // Load HTML to DOMPDF
        $dompdf->loadHtml($html);

        // (Optional) Set paper size
        $width = 2000;  // Custom width (mm)
        $height = 2000; // Custom height (mm)
        
        // Set custom paper size with 'portrait' or 'landscape' orientation
        $dompdf->setPaper([0, 0, $width, $height], 'landscape');

        // Render the PDF
        $dompdf->render();

        // Output the generated PDF (force download)
        return $dompdf->stream($id. 'loads.pdf', array("Attachment" => 1));
    }

    public function allaccountingloadsExcel($id)
    {
        // Initialize the required data based on the $id
        if ($id == 'Open') {
            $data = Load::where('load_status', 'Open')->get();
            $headers = ['Sr.no', 'Name', 'Load Number', 'Load Status', 'WO#', 'Customer Name','Office', 'Team Leader', 'Manager', 'Added At', 'Carrier'];
            $columns = ['user.name','load_number','load_status','load_workorder','load_bill_to','user.office','user.team_lead','user.manager','user.created_at','load_carrier',];
        } elseif ($id == 'Completed') {
            $data = Load::where('load_status', 'Completed')->whereNotIn('invoice_status', ['Paid', 'Record Paid'])->get();
            $headers = ['Sr.no', 'Name', 'Load Number', 'Load Status', 'WO#', 'Customer Name','Office', 'Team Leader', 'Manager', 'Added At', 'Carrier'];
            $columns = ['user.name','load_number','load_status','load_workorder','load_bill_to','user.office','user.team_lead','user.manager','user.created_at','load_carrier',];
        } elseif ($id == 'Paid') {
            $data =  Load::where('load_status', 'Completed')->where('invoice_status', 'Paid')->get();
            $headers = ['Sr.no', 'Name', 'Load Number', 'Invoice Status', 'WO#', 'Customer Name','Office', 'Team Leader', 'Manager', 'Added At', 'Carrier'];
            $columns = ['user.name','load_number','invoice_status','load_workorder','load_bill_to','user.office','user.team_lead','user.manager','user.created_at','load_carrier',];       
        } elseif ($id == 'Record Paid') {
            $data = Load::where('load_status', 'Completed')->where('invoice_status', 'Paid Record')->get();
            $headers = ['Sr.no', 'Name', 'Load Number', 'Invoice Status', 'WO#', 'Customer Name','Office', 'Team Leader', 'Manager', 'Added At', 'Carrier'];
            $columns = ['user.name','load_number','invoice_status', 'load_workorder','load_bill_to','user.office','user.team_lead','user.manager','user.created_at','load_carrier',];
        }else {
            return response()->json(['message' => 'Invalid data type.'], 400);
        }
    
        // Create a new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data');
    
        // Add headers
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }
    
        // Populate rows with data
        $row = 2;
        foreach ($data as $index => $item) {
            $col = 'A';
            $sheet->setCellValue($col . $row, $index + 1); // Sr.no starts at 1
            $col++;
            
            foreach ($columns as $column) {
                $value = $item;
                foreach (explode('.', $column) as $segment) {
                    if (isset($value->{$segment})) {
                        $value = $value->{$segment};
                    } else {
                        $value = '-'; // default value for nulls or missing data
                        break;
                    }
                }
                $sheet->setCellValue($col . $row, $value ?? '-');
                $col++;
            }
            $row++;
        }
    
        // Write the spreadsheet to a file in memory
        $writer = new Xlsx($spreadsheet);
        
        // Set headers for file download
        $filename = $id . 'loads.xlsx';
        $file = $this->getFileStream($writer);
    
        // Return file as a download response
        return response()->stream(
            function () use ($file) {
                echo $file;
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment;filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0',
            ]
        );
    }

    public function allaccountmangerloadsPdf($id)
    {

        if($id == 'All'){
            $all = Load::get();
        }

        if($id == 'Delivered'){
            $open = Load::where('load_status', 'Delivered')->get();
        }

        if($id == 'Completed'){
            $completed = Load::where('load_status', 'Completed')->whereNotIn('invoice_status', ['Paid', 'Record Paid'])->get();
        }

        if($id == 'Paid'){
            $paid = Load::where('load_status', 'Completed')->where('invoice_status', 'Paid')->get();
        }

        if($id == 'Record Paid'){
            $paid_record = Load::where('load_status', 'Completed')->where('invoice_status', 'Paid Record')->get();
        }

        // Initialize DOMPDF
        $dompdf = new Dompdf();

        // Create HTML content for the PDF

        if ($id == 'All') {
            $html = '<h1>Open List</h1>';
            $html .= '<table border="1" cellpadding="10" cellspacing="0">';
            $html .= '<thead>';
            $html .= '<tr>
                        <th>Sr.no</th>
                        <th>Load #</th>
                        <th>Load Status</th>
                        <th>W/O #</th>
                        <th>Agent</th>
                        <th>Customer #</th>
                        <th>Customer Final Rate</th> <!-- Fixed Syntax -->
                        <th>Office</th>
                        <th>Team Leader</th>
                        <th>Manager</th>
                        <th>Load Creation Date</th>
                        <th>Equipment Type</th>
                        <th>Carrier Name</th>
                        <th>Carrier Final Rate</th>
                      </tr>';
            $html .= '</thead>';
            $html .= '<tbody>';
        
            // Loop through each load and generate the rows
            $row = 1;
            foreach ($all as $alls) {
                $html .= '<tr>';
                $html .= '<td>' . $row++ . '</td>';
                $html .= '<td>' . $alls->load_number . '</td>';
                $html .= '<td>' . $alls->load_status . '</td>';
                $html .= '<td>' . $alls->load_workorder . '</td>';
                $html .= '<td>' . $alls->user->name . '</td>';
                $html .= '<td>' . $alls->load_bill_to . '</td>';
                $html .= '<td>' . $alls->shipper_load_final_rate . '</td>';
                $html .= '<td>' . $alls->user->office . '</td>';
                $html .= '<td>' . $alls->user->team_lead . '</td>';
                $html .= '<td>' . $alls->user->manager . '</td>';
                $html .= '<td>' . $alls->created_at . '</td>';
                $html .= '<td>' . $alls->load_equipment_type . '</td>';
                $html .= '<td>' . $alls->load_carrier . '</td>';
                $html .= '<td>' . $alls->load_final_rate . '</td>';
                $html .= '</tr>';
            }
        
            $html .= '</tbody>';
            $html .= '</table>';
        }

        if ($id == 'Delivered') {
            $html = '<h1>Open List</h1>';
            $html .= '<table border="1" cellpadding="10" cellspacing="0">';
            $html .= '<thead>';
            $html .= '<tr>
                        <th>Sr.no</th>
                        <th>Load #</th>
                        <th>Load Status</th>
                        <th>W/O #</th>
                        <th>Agent</th>
                        <th>Customer #</th>
                        <th>Customer Final Rate</th> <!-- Fixed Syntax -->
                        <th>Office</th>
                        <th>Team Leader</th>
                        <th>Manager</th>
                        <th>Load Creation Date</th>
                        <th>Equipment Type</th>
                        <th>Carrier Name</th>
                        <th>Carrier Final Rate</th>
                      </tr>';
            $html .= '</thead>';
            $html .= '<tbody>';
        
            // Loop through each load and generate the rows
            $row = 1;
            foreach ($open as $opens) {
                $html .= '<tr>';
                $html .= '<td>' . $row++ . '</td>';
                $html .= '<td>' . $opens->load_number . '</td>';
                $html .= '<td>' . $opens->load_status . '</td>';
                $html .= '<td>' . $opens->load_workorder . '</td>';
                $html .= '<td>' . $opens->user->name . '</td>';
                $html .= '<td>' . $opens->load_bill_to . '</td>';
                $html .= '<td>' . $opens->shipper_load_final_rate . '</td>';
                $html .= '<td>' . $opens->user->office . '</td>';
                $html .= '<td>' . $opens->user->team_lead . '</td>';
                $html .= '<td>' . $opens->user->manager . '</td>';
                $html .= '<td>' . $opens->created_at . '</td>';
                $html .= '<td>' . $opens->load_equipment_type . '</td>';
                $html .= '<td>' . $opens->load_carrier . '</td>';
                $html .= '<td>' . $opens->load_final_rate . '</td>';
                $html .= '</tr>';
            }
        
            $html .= '</tbody>';
            $html .= '</table>';
        }

        if ($id == 'Completed') {
            $html = '<h1>Completed List</h1>';
            $html .= '<table border="1" cellpadding="10" cellspacing="0">';
            $html .= '<thead>';
            $html .= '<tr>
                        <th>Sr.no</th>
                        <th>Load #</th>
                        <th>Load Status</th>
                        <th>W/O #</th>
                        <th>Agent</th>
                        <th>Customer #</th>
                        <th>Customer Final Rate</th> <!-- Fixed Syntax -->
                        <th>Office</th>
                        <th>Team Leader</th>
                        <th>Manager</th>
                        <th>Load Creation Date</th>
                        <th>Equipment Type</th>
                        <th>Carrier Name</th>
                        <th>Carrier Final Rate</th>
                      </tr>';
            $html .= '</thead>';
            $html .= '<tbody>';
        
            // Loop through each load and generate the rows
            $row = 1;
            foreach ($completed as $completeds) {
                $html .= '<tr>';
                $html .= '<td>' . $row++ . '</td>';
                $html .= '<td>' . $completeds->load_number . '</td>';
                $html .= '<td>' . $completeds->load_status . '</td>';
                $html .= '<td>' . $completeds->load_workorder . '</td>';
                $html .= '<td>' . $completeds->user->name . '</td>';
                $html .= '<td>' . $completeds->load_bill_to . '</td>';
                $html .= '<td>' . $completeds->shipper_load_final_rate . '</td>';
                $html .= '<td>' . $completeds->user->office . '</td>';
                $html .= '<td>' . $completeds->user->team_lead . '</td>';
                $html .= '<td>' . $completeds->user->manager . '</td>';
                $html .= '<td>' . $completeds->created_at . '</td>';
                $html .= '<td>' . $completeds->load_equipment_type . '</td>';
                $html .= '<td>' . $completeds->load_carrier . '</td>';
                $html .= '<td>' . $completeds->load_final_rate . '</td>';
                $html .= '</tr>';
            }
        
            $html .= '</tbody>';
            $html .= '</table>';
        }

        if ($id == 'Paid') {
            
            $html = '<h1>Invoice List</h1>';
            $html .= '<table border="1" cellpadding="10" cellspacing="0">';
            $html .= '<thead>';
            $html .= '<tr>
                        <th>Sr.no</th>
                        <th>Load #</th>
                        <th>Load Status</th>
                        <th>Invoice Status</th>
                        <th>W/O #</th>
                        <th>Agent</th>
                        <th>Customer #</th>
                        <th>Customer Final Rate</th> <!-- Fixed Syntax -->
                        <th>Office</th>
                        <th>Team Leader</th>
                        <th>Manager</th>
                        <th>Load Creation Date</th>
                        <th>Equipment Type</th>
                        <th>Carrier Name</th>
                        <th>Carrier Final Rate</th>
                      </tr>';
            $html .= '</thead>';
            $html .= '<tbody>';
        
            // Loop through each load and generate the rows
            $row = 1;
            foreach ($paid as $paids) {
                $convertname = ($paids->invoice_status == "Paid") ? "Invoiced" : $paids->invoice_status; // Dynamic conversion
                
                $html .= '<tr>';
                $html .= '<td>' . $row++ . '</td>';
                $html .= '<td>' . $paids->load_number . '</td>';
                $html .= '<td>' . $paids->load_status . '</td>';
                $html .= '<td>' . $convertname . '</td>'; // Always populate invoice status
                $html .= '<td>' . $paids->load_workorder . '</td>';
                $html .= '<td>' . $paids->user->name . '</td>';
                $html .= '<td>' . $paids->load_bill_to . '</td>';
                $html .= '<td>' . $paids->shipper_load_final_rate . '</td>';
                $html .= '<td>' . $paids->user->office . '</td>';
                $html .= '<td>' . $paids->user->team_lead . '</td>';
                $html .= '<td>' . $paids->user->manager . '</td>';
                $html .= '<td>' . $paids->created_at . '</td>';
                $html .= '<td>' . $paids->load_equipment_type . '</td>';
                $html .= '<td>' . $paids->load_carrier . '</td>';
                $html .= '<td>' . $paids->load_final_rate . '</td>';
                $html .= '</tr>';
            }
        
            $html .= '</tbody>';
            $html .= '</table>';
        }

        if ($id == 'Record Paid') {
            $html = '<h1>Payment Paid List</h1>';
            $html .= '<table border="1" cellpadding="10" cellspacing="0">';
            $html .= '<thead>';
            $html .= '<tr>
                        <th>Sr.no</th>
                        <th>Load #</th>
                        <th>Load Status</th>
                        <th>Invoice Status</th>
                        <th>W/O #</th>
                        <th>Agent</th>
                        <th>Customer #</th>
                        <th>Customer Final Rate</th> <!-- Fixed Syntax -->
                        <th>Office</th>
                        <th>Team Leader</th>
                        <th>Manager</th>
                        <th>Load Creation Date</th>
                        <th>Equipment Type</th>
                        <th>Carrier Name</th>
                        <th>Carrier Final Rate</th>
                      </tr>';
            $html .= '</thead>';
            $html .= '<tbody>';
        
            // Loop through each load and generate the rows
            $row = 1;
            foreach ($paid_record as $paid_records) {
                $html .= '<tr>';
                $html .= '<td>' . $row++ . '</td>';
                $html .= '<td>' . $paid_records->load_number . '</td>';
                $html .= '<td>' . $paid_records->load_status . '</td>';
                $html .= '<td>' . $paid_records->invoice_status . '</td>';
                $html .= '<td>' . $paid_records->load_workorder . '</td>';
                $html .= '<td>' . $paid_records->user->name . '</td>';
                $html .= '<td>' . $paid_records->load_bill_to . '</td>';
                $html .= '<td>' . $paid_records->shipper_load_final_rate . '</td>';
                $html .= '<td>' . $paid_records->user->office . '</td>';
                $html .= '<td>' . $paid_records->user->team_lead . '</td>';
                $html .= '<td>' . $paid_records->user->manager . '</td>';
                $html .= '<td>' . $paid_records->created_at . '</td>';
                $html .= '<td>' . $paid_records->load_equipment_type . '</td>';
                $html .= '<td>' . $paid_records->load_carrier . '</td>';
                $html .= '<td>' . $paid_records->load_final_rate . '</td>';
                $html .= '</tr>';
            }
        
            $html .= '</tbody>';
            $html .= '</table>';
        }
            // Load HTML to DOMPDF
        $dompdf->loadHtml($html);

        // (Optional) Set paper size
        $width = 2000;  // Custom width (mm)
        $height = 2000; // Custom height (mm)
        
        // Set custom paper size with 'portrait' or 'landscape' orientation
        $dompdf->setPaper([0, 0, $width, $height], 'landscape');

        // Render the PDF
        $dompdf->render();

        // Output the generated PDF (force download)
        return $dompdf->stream($id. 'loads.pdf', array("Attachment" => 1));
    }

    public function allaccountmangerloadsloadsExcel($id)
    {
        // Initialize the required data based on the $id
        if ($id == 'All') {
            $data = Load::get();
            $headers = ['Sr.no', 'Name', 'Load Number', 'Load Status', 'WO#', 'Customer Name','Office', 'Team Leader', 'Manager', 'Added At', 'Carrier'];
            $columns = ['user.name','load_number','load_status','load_workorder','load_bill_to','user.office','user.team_lead','user.manager','user.created_at','load_carrier',];
        } elseif ($id == 'Delivered') {
            $data = Load::where('load_status', 'Delivered')->get();
            $headers = ['Sr.no', 'Name', 'Load Number', 'Load Status', 'WO#', 'Customer Name','Office', 'Team Leader', 'Manager', 'Added At', 'Carrier'];
            $columns = ['user.name','load_number','load_status','load_workorder','load_bill_to','user.office','user.team_lead','user.manager','user.created_at','load_carrier',];
        }elseif ($id == 'Completed') {
            $data = Load::where('load_status', 'Completed')->whereNotIn('invoice_status', ['Paid', 'Record Paid'])->get();
            $headers = ['Sr.no', 'Name', 'Load Number', 'Load Status', 'WO#', 'Customer Name','Office', 'Team Leader', 'Manager', 'Added At', 'Carrier'];
            $columns = ['user.name','load_number','load_status','load_workorder','load_bill_to','user.office','user.team_lead','user.manager','user.created_at','load_carrier',];
        } elseif ($id == 'Paid') {
            $data =  Load::where('load_status', 'Completed')->where('invoice_status', 'Paid')->get();
            $headers = ['Sr.no', 'Name', 'Load Number', 'Invoice Status', 'WO#', 'Customer Name','Office', 'Team Leader', 'Manager', 'Added At', 'Carrier'];
            $columns = ['user.name','load_number','invoice_status','load_workorder','load_bill_to','user.office','user.team_lead','user.manager','user.created_at','load_carrier',];       
        } elseif ($id == 'Record Paid') {
            $data = Load::where('load_status', 'Completed')->where('invoice_status', 'Paid Record')->get();
            $headers = ['Sr.no', 'Name', 'Load Number', 'Invoice Status', 'WO#', 'Customer Name','Office', 'Team Leader', 'Manager', 'Added At', 'Carrier'];
            $columns = ['user.name','load_number','invoice_status', 'load_workorder','load_bill_to','user.office','user.team_lead','user.manager','user.created_at','load_carrier',];
        }else {
            return response()->json(['message' => 'Invalid data type.'], 400);
        }
    
        // Create a new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data');
    
        // Add headers
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }
    
        // Populate rows with data
        $row = 2;
        foreach ($data as $index => $item) {
            $col = 'A';
            $sheet->setCellValue($col . $row, $index + 1); // Sr.no starts at 1
            $col++;
            
            foreach ($columns as $column) {
                $value = $item;
                foreach (explode('.', $column) as $segment) {
                    if (isset($value->{$segment})) {
                        $value = $value->{$segment};
                    } else {
                        $value = '-'; // default value for nulls or missing data
                        break;
                    }
                }
                $sheet->setCellValue($col . $row, $value ?? '-');
                $col++;
            }
            $row++;
        }
    
        // Write the spreadsheet to a file in memory
        $writer = new Xlsx($spreadsheet);
        
        // Set headers for file download
        $filename = $id . 'loads.xlsx';
        $file = $this->getFileStream($writer);
    
        // Return file as a download response
        return response()->stream(
            function () use ($file) {
                echo $file;
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment;filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0',
            ]
        );
    }


    public function allreportsPdf($id)
    {

        if($id == 'Customer'){
            $customer = customer::get();
        }

        if($id == 'Load Complete Log'){
            $load = Load::get();
        }



        // Initialize DOMPDF
        $dompdf = new Dompdf();

        // Create HTML content for the PDF

        if ($id == 'Customer') {
            $html = '<h1>Customer Detail List</h1>';
            $html .= '<table border="1" cellpadding="10" cellspacing="0">';
            $html .= '<thead>';
            $html .= '<tr>
                        <th>Sr.no</th>
                        <th>Customer Creation date</th>
                        <th>Customer Name</th>
                        <th>Customer Address</th>
                        <th>Billing Email</th>
                        <th>Customer Contact</th>
                        <th>Telephone</th>
                        <th>Ext. </th>
                        <th>Agent</th>
                        <th>Payment Terms</th>
                        <th>Remaing Credit</th>
                        <th>Approved Limit</th>
                        <th>Customer Status</th>
                      </tr>';
            $html .= '</thead>';
            $html .= '<tbody>';
        
            // Loop through each load and generate the rows
            $row = 1;
            foreach ($customer as $customers) {
                $html .= '<tr>';
                $html .= '<td>' . $row++ . '</td>';
                $html .= '<td>' . $customers->created_at . '</td>';
                $html .= '<td>' . $customers->customer_name . '</td>';
                $html .= '<td>' . $customers->customer_address . '</td>';
                $html .= '<td>' . $customers->customer_email . '</td>';
                $html .= '<td>' . $customers->customer_primary_contact . '</td>';
                $html .= '<td>' . $customers->customer_telephone . '</td>';
                $html .= '<td>' . $customers->customer_extn . '</td>';
                $html .= '<td>' . $customers->user->name . '</td>';
                $html .= '<td>' . $customers->adv_customer_payment_terms . '</td>';
                $html .= '<td>' . $customers->remaining_credit . '</td>';
                $html .= '<td>' . $customers->remaining_credit_amount . '</td>';
                $html .= '<td>' . $customers->status . '</td>';
                $html .= '</tr>';
            }
        
            $html .= '</tbody>';
            $html .= '</table>';
        }

        if ($id == 'Load Complete Log') {
            $html = '<h1>Load Complete Log List</h1>';
            $html .= '<table border="1" cellpadding="10" cellspacing="0">';
            $html .= '<thead>';
            $html .= '<tr>
                        <th>Sr.no</th>
                        <th>Load #</th>
                        <th>Agent Name</th>
                        <th>Load Status</th>
                        <th>Invoice Status</th>
                        <th>Customer Reference #</th>
                        <th>Load Create Date</th>
                        <th>Customer Name</th>
                        <th>Carrier Name</th>
                        <th>Load Type</th>
                        <th>Actual Delivery Date</th>
                        <th>Carrier Due Date</th>
                        <th>Carrier Mark Payment Date</th>
                        <th>Carrier Fee</th>
                        <th>Shipper Rate</th>
                        <th>Invoice Date</th>
                        <th>Customer Payment Received Amount</th>
                        <th>Margin</th>
                        <th>Work Order</th>
                      </tr>';
            $html .= '</thead>';
            $html .= '<tbody>';
        
            // Ensure $load is an array or collection
            $row = 1;
            foreach ($load as $loads) {
                // Safely handle missing or null values
                $margin = ((float) $loads->load_carrier_fee ?? 0) - ((float) $loads->load_shipper_rate ?? 0);
        
                $html .= '<tr>';
                $html .= '<td>' . $row++ . '</td>';
                $html .= '<td>' . htmlspecialchars($loads->load_number ?? 'N/A') . '</td>';
                $html .= '<td>' . htmlspecialchars($loads->user->name ?? 'N/A') . '</td>';
                $html .= '<td>' . htmlspecialchars($loads->load_status ?? 'N/A') . '</td>';
                $html .= '<td>' . htmlspecialchars($loads->invoice_status ?? 'N/A') . '</td>';
                $html .= '<td>' . htmlspecialchars($loads->customer_refrence_number ?? 'N/A') . '</td>';
                $html .= '<td>' . htmlspecialchars($loads->created_at ?? 'N/A') . '</td>';
                $html .= '<td>' . htmlspecialchars($loads->load_bill_to ?? 'N/A') . '</td>';
                $html .= '<td>' . htmlspecialchars($loads->load_carrier ?? 'N/A') . '</td>';
                $html .= '<td>' . htmlspecialchars($loads->load_type ?? 'N/A') . '</td>';
                $html .= '<td>' . htmlspecialchars($loads->actual_delivery_date ?? 'N/A') . '</td>';
                $html .= '<td>' . htmlspecialchars($loads->load_carrier_due_date ?? 'N/A') . '</td>';
                $html .= '<td>' . htmlspecialchars($loads->
				load_carrier_due_date_on ?? 'N/A') . '</td>';
                $html .= '<td>' . $loads->load_carrier_fee . '</td>';
                $html .= '<td>' . number_format($loads->load_shipper_rate ?? 0, 2) . '</td>';
                $html .= '<td>' . $loads->invoice_date . '</td>';
                $html .= '<td>' . $loads->receiving_amount . '</td>';
                $html .= '<td>' . $margin . '</td>';
                $html .= '<td>' . htmlspecialchars($loads->load_workorder ?? 'N/A') . '</td>';
                $html .= '</tr>';
            }
        
            $html .= '</tbody>';
            $html .= '</table>';
        }
        

            // Load HTML to DOMPDF
        $dompdf->loadHtml($html);

        // (Optional) Set paper size
        $width = 2000;  // Custom width (mm)
        $height = 2000; // Custom height (mm)
        
        // Set custom paper size with 'portrait' or 'landscape' orientation
        $dompdf->setPaper([0, 0, $width, $height], 'landscape');

        // Render the PDF
        $dompdf->render();

        // Output the generated PDF (force download)
        return $dompdf->stream($id. 'loads.pdf', array("Attachment" => 1));
    }
	
	public function CreditReportingExcel()
    {
		
		$sortedCustomers = Customer::get();
		 $customerTotals = Load::selectRaw("
			customer_id,
			SUM(shipper_load_final_rate) AS total_created,
			SUM(CASE WHEN invoice_status = 'Paid Record' THEN receiving_amount ELSE 0 END) AS total_received
		")
		->groupBy('customer_id')
		->get()
		->keyBy('customer_id');

        // Create a new Spreadsheet object
        $spreadsheet = new Spreadsheet();

        // Set the active sheet and title
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Limits');

        // Add headers
        $sheet->setCellValue('A1', 'Sr.no');
        $sheet->setCellValue('B1', 'Agent');
        $sheet->setCellValue('C1', 'Company');
        $sheet->setCellValue('D1', 'Address');
        $sheet->setCellValue('E1', 'Telephone');
        $sheet->setCellValue('F1', 'Team Leader');
        $sheet->setCellValue('G1', 'Manager');
        $sheet->setCellValue('H1', 'Office');
        $sheet->setCellValue('I1', 'Requested Credit');
        $sheet->setCellValue('J1', 'Exhausted limit');
        $sheet->setCellValue('K1', 'Remaining Limit');
		$sheet->setCellValue('L1', 'Assign Remaining limit');
        $sheet->setCellValue('M1', 'Approved Status');
        $sheet->setCellValue('N1', 'Last Load');
        $sheet->setCellValue('O1', 'Customer Creation Date');

       
                               

        // Populate rows with user data
        $row = 2;
        foreach ($sortedCustomers as $customer) {
			
			$credits = json_decode($customer->credit_limit_log, true);

			if (is_array($credits)) {
				$totalCreditLimit = array_sum(array_column($credits, 'credit_limit'));
			} else {
				$totalCreditLimit = 0;
			}
			
			$data = json_decode($customer->remaining_credit_logs, true);

			// Ensure $data is an array
			if (!is_array($data)) {
				$data = [];
			}

			// Filter only valid items with credit_limit
			$data = array_filter($data, function($item) {
				return is_array($item) && isset($item['credit_limit']);
			});

			// Sum the credit_limit values
			$totalremainingCredit = collect($data)->sum(function ($item) {
				return (float) $item['credit_limit'];
			});
			
			$totals = $customerTotals[$customer->id] ?? null;
			
			$pending_amount = ($totals->total_created ?? 0) - ($totals->total_received ?? 0);
                                            

            $sheet->setCellValue('A' . $row, $row+1);
            $sheet->setCellValue('B' . $row, $customer->user?->name);
            $sheet->setCellValue('C' . $row, $customer->customer_name);
            $sheet->setCellValue('D' . $row, $customer->customer_address.' '. $customer->customer_country.' '.$customer->customer_state.' '. $customer->customer_city.' '.$customer->customer_zip);
            $sheet->setCellValue('E' . $row, $customer->customer_telephone);
            $sheet->setCellValue('F' . $row, $customer->user?->teamLeaderInfo?->tl);
            $sheet->setCellValue('G' . $row, $customer->user?->managerInfo?->manager);
            $sheet->setCellValue('H' . $row, $customer->user?->officedata?->office_name);
            $sheet->setCellValue('I' . $row, '$'.$totalCreditLimit);
            $sheet->setCellValue('J' . $row, '$'.($pending_amount));
            $sheet->setCellValue('K' . $row, '$'.number_format(floatval($customer->remaining_credit), 2));
			$sheet->setCellValue('L' . $row, $totalremainingCredit);
            $sheet->setCellValue('M' . $row, $customer->status);
            $sheet->setCellValue('N' . $row, $customer->aging_days !== null ? $customer->aging_days . ' days' : 'N/A');
            $sheet->setCellValue('O'. $row, $customer->created_at->format('m-d-Y'));
            $row++;
         }

        // Write the spreadsheet to a file in memory
        $writer = new Xlsx($spreadsheet);

        // Set headers for file download
        $filename = 'limit.xlsx';
        $file = $this->getFileStream($writer);

        // Return file as a download response
        return response()->stream(
            function () use ($file) {
                echo $file;
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment;filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0',
            ]
        );
    }


public function customerReportingExcell()
{
    $data = $this->customerReportQuery()->get();


        $headers = [
            'Sr.no',
            'Customer Name',
            'Status',
            'Total Revenue',
            'Total Carrier Cost',
            'Margin',
            'Total Loads',
            'Open Loads',
            'Delivered Loads',
            'Completed Loads',
            'Remaining Credit Logs'
        ];

        $columns = [
            'customer_name',
            'status',
            'total_revenue',
            'total_carrier_cost',
            'margin',
            'load_count',
            'open_load_count',
            'delivered_load_count',
            'completed_load_count',
            'remaining_credit_logs'
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Customer Report');

        /** ---------- Headers ---------- */
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $col++;
        }

        /** ---------- Data ---------- */
        $row = 2;
        foreach ($data as $index => $item) {
            $col = 'A';

            // Sr No
            $sheet->setCellValue($col . $row, $index + 1);
            $col++;

            foreach ($columns as $column) {
                $value = $item->{$column} ?? '-';

                // Format currency columns
                if (in_array($column, ['total_revenue', 'total_carrier_cost', 'margin'])) {
                    $value = number_format((float) $value, 2);
                }

                // Empty credit logs
                if ($column === 'remaining_credit_logs' && empty($value)) {
                    $value = '-';
                }

                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            $row++;
        }

        /** ---------- Download ---------- */
        $writer = new Xlsx($spreadsheet);
        $filename = 'Customer_Report_' . date('Y-m-d') . '.xlsx';
        $file = $this->getFileStream($writer);

        return response()->stream(
            function () use ($file) {
                echo $file;
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0',
            ]
        );
}

public function customerDetailsReportingExcell()
{
    // Fetch customer data with user relationship (if needed)
    $data = Customer::with('user')->get();

    // Headers in correct order
    $headers = [
        'Sr.no',
        'Customer Creation Date',
        'Customer Name',
        'Customer Address',
        'City',
        'State',
        'Zip',
        'Country',
        'Complete Billing Address',
        'Billing Email',
        'Customer Contact',
        'Telephone',
        'Ext.',
        'Fax',
        'Email',
        'Sales Rep (Cargo)',
        'Payment Terms',
        'Remaining Credit Limit',
        'Approved Credit Limit',
        'Customer Status'
    ];

    // Columns mapped correctly in order
    $columns = [
        'created_at',
        'customer_name',
        'customer_address',
        'customer_city',
        'customer_state',
        'customer_zip',
        'customer_country',
        'full_billing_address',
        'customer_secondary_email',
        'customer_billing_telephone',
        'customer_telephone',
        'customer_extn',
        'customer_fax',
        'customer_email',
        'user->name',
        'adv_customer_payment_terms',
        'remaining_credit',
        'approved_limit',
        'customer_status',
    ];

    // Create the Spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Data');

    // Add headers to first row
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $col++;
    }

    // Insert data rows
    $row = 2;
    foreach ($data as $index => $item) {
        $col = 'A';
        $sheet->setCellValue($col . $row, $index + 1); // Sr.no
        $col++;

        foreach ($columns as $column) {
            $value = '-';

            switch ($column) {
                case 'created_at':
                    $value = $item->created_at ? $item->created_at->format('Y-m-d') : '-';
                    break;

                case 'full_billing_address':
                    $value = trim(
                        $item->customer_billing_address . " " .
                        $item->customer_billing_city . " " .
                        $item->customer_billing_state . " " .
                        $item->customer_billing_zip . " " .
                        $item->customer_billing_country
                    );
                    break;

                case 'customer_country':
                    // If stored as ID, replace with name (adjust as per table)
                    $value = $item->customer_country ?? '-';
                    break;

                case 'user->name':
                    $value = $item->user->name ?? '-';
                    break;

                    case 'customer_country':
                    $value = $item->country?->name ?? $item->customer_country;
                    break;

                    case 'customer_state':
                    $value = $item->state?->name ?? $item->customer_state;
                    break;



                default:
                    $value = $item->{$column} ?? '-';
            }

            $sheet->setCellValue($col . $row, $value);
            $col++;
        }

        $row++;
    }

    // Write to XLSX in-memory
    $writer = new Xlsx($spreadsheet);
    $filename = 'Customer Detail ' . date('Y-m-d') . '.xlsx';
    $file = $this->getFileStream($writer);

    return response()->stream(
        function () use ($file) {
            echo $file;
        },
        200,
        [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]
    );
}


    public function dispatcherReportingExcell()
    {
        // Initialize the required data based on the $id
        $data = Load::join('users', 'loads.user_id', '=', 'users.id')
		->select('loads.load_carrier', 'users.name as user_name')
		->selectRaw('SUM(loads.load_final_carrier_fee) AS total_revenue')
		->selectRaw('SUM(loads.load_final_carrier_fee - loads.load_carrier_fee) AS revenue_difference')
		->selectRaw('COUNT(loads.id) AS load_count')
		->selectRaw('SUM(CASE WHEN loads.load_status = "Open" THEN 1 ELSE 0 END) AS open_load_count')
		->selectRaw('SUM(CASE WHEN loads.load_status = "Delivered" THEN 1 ELSE 0 END) AS delivered_load_count')
		->selectRaw('SUM(CASE WHEN loads.invoice_status = "Completed" THEN 1 ELSE 0 END) AS completed_load_count')
		->groupBy('loads.load_carrier', 'users.name')
		->get();

        $headers = ['Sr.no', 'Dispatcher', 'No of Load', 'Revenue', 'Carrier Amount', 'Margin','Open Loads', 'Delivered Loads', 'Invoiced Loads.'];

        $columns = ['user_name','load_count','total_revenue','total_revenue','margin','open_load_count','delivered_load_count','completed_load_count'];

        
        // Create a new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data');

        // Add headers
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Populate rows with data
        $row = 2;
        foreach ($data as $index => $item) {
            $col = 'A';
            $sheet->setCellValue($col . $row, $index + 1); // Sr.no starts at 1
            $col++;
            
            foreach ($columns as $column) {
                if($column == "margin"){
                    $finalRate = $item->total_revenue - $item->revenue_difference;
                    $value = number_format($finalRate, 2);
                }else{
                    $value = $item;
                    foreach (explode('.', $column) as $segment) {
                        if (isset($value->{$segment})) {
                            $value = $value->{$segment};
                            if(in_array($column, ['total_revenue', 'revenue_difference', 'adv_customer_credit_limit'])){
                                $value = number_format($value, 2);
                            }
                        }else {
                            $value = '-';
                            break;
                        }
                    }
                }
                
                $sheet->setCellValue($col . $row, $value ?? '-');
                $col++;
            }
            $row++;
        }

        // Write the spreadsheet to a file in memory
        $writer = new Xlsx($spreadsheet);
        
        // Set headers for file download
        $filename = 'Dispatcher Detail ' . date('Y-m-d') .' .xlsx';
        $file = $this->getFileStream($writer);

        // Return file as a download response
        return response()->stream(
            function () use ($file) {
                echo $file;
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment;filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0',
            ]
        );
    }

    public function loadsDetailsReportingExcell()
    {
        $data = Load::with('user')->get();

        $headers = ['Sr No.','Load No', 'Status', 'Carrier', 'Created', 'Dispatcher', 'Customer','Shipper', 'Ship Date', 'Location','Consignee','Delivery Date','Delivery Location','Cpr Status','Equipment Type'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data');

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        $row = 2;
        foreach ($data as $index => $item) {
            $col = 'A';
            $sheet->setCellValue($col . $row, $index + 1);
            $col++;
            
            $sheet->setCellValue($col . $row, $item->load_number ?? '-');
            $col++;
            $sheet->setCellValue($col . $row, $item->load_status ?? '-');
            $col++;
            $sheet->setCellValue($col . $row, $item->load_carrier ?? '-');
            $col++;
            $sheet->setCellValue($col . $row, $item->created_at ? $item->created_at->format('m/d/Y') : '-');
            $col++;
            $sheet->setCellValue($col . $row, $item->user->name ?? '-');
            $col++;
            $sheet->setCellValue($col . $row, $item->load_bill_to ?? '-');
            $col++;
            
            $shipper = json_decode($item->load_shipperr, true);
            $sheet->setCellValue($col . $row, $shipper[0]['name'] ?? '-');
            $col++;
            
            $appointment = json_decode($item->load_shipper_appointment, true);
            $shipDate = isset($appointment[0]['appointment']) ? \Carbon\Carbon::parse($appointment[0]['appointment'])->format('m/d/Y') : '-';
            $sheet->setCellValue($col . $row, $shipDate);
            $col++;
            
            $location = json_decode($item->load_shipper_location, true);
            $sheet->setCellValue($col . $row, $location[0]['location'] ?? '-');
            $col++;
            
            $consignee = json_decode($item->load_consignee, true);
            $sheet->setCellValue($col . $row, $consignee[0]['name'] ?? '-');
            $col++;
            
            $consigneeAppointment = json_decode($item->load_consignee_appointment, true);
            $deliveryDate = isset($consigneeAppointment[0]['appointment']) ? \Carbon\Carbon::parse($consigneeAppointment[0]['appointment'])->format('m/d/Y') : '-';
            $sheet->setCellValue($col . $row, $deliveryDate);
            $col++;
            
            $consigneeLocation = json_decode($item->load_consignee_location, true);
            $sheet->setCellValue($col . $row, $consigneeLocation[0]['location'] ?? '-');
            $col++;
            
            $sheet->setCellValue($col . $row, $item->cpr_check ?? '-');
            $col++;
            $sheet->setCellValue($col . $row, $item->load_equipment_type ?? '-');

            
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'Loads Detail ' . date('Y-m-d') .'.xlsx';
        $file = $this->getFileStream($writer);

        return response()->stream(
            function () use ($file) {
                echo $file;
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment;filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0',
            ]
        );
    }


    public function salesReportingExcell()
    {
        // Initialize the required data based on the $id
        $data = Load::join('users', 'loads.user_id', '=', 'users.id')
		->select('users.name')
		->selectRaw('SUM(loads.load_shipper_rate ) AS total_revenue')
		->selectRaw('SUM(loads.load_carrier_fee) AS total_carrier_fee')
		->selectRaw('SUM(loads.load_shipper_rate  - loads.load_carrier_fee) AS revenue_difference')
		->selectRaw('COUNT(loads.id) AS load_count')
		->selectRaw('SUM(CASE WHEN loads.load_status = "Open" THEN 1 ELSE 0 END) AS open_load_count')
		->groupBy('users.name')
		->get();

        $headers = ['Sr.no', 'Sales Rep', 'No of Load', 'Gross Revenue', 'Carrier Pay', 'Margin','Open Loads'];

        $columns = ['name','load_count','total_revenue','finalRate','revenue_difference','open_load_count'];

        
        // Create a new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data');

        // Add headers
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Populate rows with data
        $row = 2;
        foreach ($data as $index => $item) {
            $col = 'A';
            $sheet->setCellValue($col . $row, $index + 1); // Sr.no starts at 1
            $col++;
            
            foreach ($columns as $column) {
                if($column == "finalRate"){
                    $finalRate = $item->total_revenue - $item->revenue_difference;
                    $value = number_format($finalRate, 2);
                }else{
                    $value = $item;
                    foreach (explode('.', $column) as $segment) {
                        if (isset($value->{$segment})) {
                            $value = $value->{$segment};
                            if(in_array($column, ['total_revenue', 'total_carrier_fee'])){
                                $value = number_format($value, 2);
                            }
                        }else {
                            $value = '-';
                            break;
                        }
                    }
                }
                
                $sheet->setCellValue($col . $row, $value ?? '-');
                $col++;
            }
            $row++;
        }

        // Write the spreadsheet to a file in memory
        $writer = new Xlsx($spreadsheet);
        
        // Set headers for file download
        $filename = 'Dispatcher Detail ' . date('Y-m-d') . '.xlsx';

        $file = $this->getFileStream($writer);

        // Return file as a download response
        return response()->stream(
            function () use ($file) {
                echo $file;
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment;filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0',
            ]
        );
    }

    public function loadCompleteReportingExcel()
    {
        set_time_limit(0);

        $loadQuery = Load::where('load_status', 'Completed');

        $maxConsignees = 0;
        $loadQuery->select('id', 'load_consignee_location')->cursor()->each(function ($item) use (&$maxConsignees) {
            $consignee_location = json_decode($item->load_consignee_location, true);
            if (is_array($consignee_location)) {
                $maxConsignees = max($maxConsignees, count($consignee_location));
            }
        });

        $headers = ['Sr.no', 'load Number', 'Invoice No', 'Agent Name', 'Load Status', 'Invoice Status', 'Customer Reference #','Load Create Date', 'Customer Name', 'Carrier Name','Pickup Location'];

            for ($i = 1; $i <= $maxConsignees; $i++) {
                $headers[] = "Unloading Location $i";
            }
        
         $headers = array_merge($headers, ['Load Type','Carrier Advance Payment','Actual Delivery Date','Carrier Due Date','Carrier Mark Payment Date','Carrier Payment Status','Carrier Fee','Shipper Rate','Invoice Date','Paper work Received Date','Payment Receiving Date','Customer Payment Received Amount','Customer Payment Mark Date','Customer Rate','Customer Fsc','Customer Other Charges','Customer Final Rate','Carrier Rate','Carrier Fsc','Carrier Other Charges','Carrier Final Rate','Margin','Margin %','Work Order','CPR Check','Macro Sent','Delivery Date','Shipper Date','Equipement Type','Shipment Type','CMT Agent','Currency' ]);

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        $sheet = new class($worksheet) {
            private int $currentRow = 0;
            private array $values = [];

            public function __construct(private $worksheet)
            {
            }

            public function setTitle(string $title): void
            {
                $this->worksheet->setTitle($title);
            }

            public function setCellValue(string $coordinate, mixed $value): void
            {
                preg_match('/^([A-Z]+)(\d+)$/', $coordinate, $matches);
                $row = (int) $matches[2];

                if ($this->currentRow !== 0 && $row !== $this->currentRow) {
                    $this->flushRow();
                }

                $this->currentRow = $row;
                $column = 0;
                foreach (str_split($matches[1]) as $letter) {
                    $column = ($column * 26) + ord($letter) - 64;
                }

                $this->values[$column - 1] = $value ?? '';
            }

            public function finish(): void
            {
                $this->flushRow();
            }

            private function flushRow(): void
            {
                if ($this->values !== []) {
                    $this->worksheet->fromArray([array_values($this->values)], null, 'A' . $this->currentRow);
                    $this->values = [];
                }
            }
        };
        $sheet->setTitle('Completed Loads');

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        $row = 2;
        $index = 0;
        $completedLoads = Load::query()
            ->leftJoin('users', 'users.id', '=', 'loads.user_id')
            ->select('loads.*', 'users.name as agent_name')
            ->where('loads.load_status', 'Completed')
            ->orderBy('loads.created_at', 'asc')
            ->orderBy('loads.id', 'asc')
            ->cursor();

        foreach ($completedLoads as $item) {
            $col = 'A';
            $sheet->setCellValue($col . $row, ++$index);
            $col++;
            
            $shipper = json_decode($item->load_shipperr, true);
            $consignee = json_decode($item->load_consignee, true);
            $shipper_appointment = json_decode($item->load_shipper_appointment, true);
            $shipper_location = json_decode($item->load_shipper_location, true);
            $appointment = isset($shipper_location[0]['appointment']) ? $shipper_location[0]['appointment'] : '';
            $consignee_location = json_decode($item->load_consignee_location, true);
            $consignee_appointment = json_decode($item->load_consignee_appointment, true);           
            $sheet->setCellValue($col . $row, $item->load_number ?? '');
            $col++;
            $sheet->setCellValue($col . $row, in_array($item->invoice_status, ['Paid Record', 'Paid']) ? ($item->invoice_number ?? '') : '');
            $col++;
            $sheet->setCellValue($col . $row, $item->agent_name ?? '');
            $col++;
            $sheet->setCellValue($col . $row, $item->load_status ?? '');
            $col++;
            $sheet->setCellValue($col . $row, empty($item->invoice_status) ? '' : ($item->invoice_status == 'Paid' ? 'Invoiced' : $item->invoice_status));
            $col++;
            $sheet->setCellValue($col . $row, $item->customer_refrence_number ?? '');
            $col++;
            $sheet->setCellValue($col . $row, $item->created_at ? $item->created_at->setTimezone('America/New_York')->format('m/d/Y') : '');
            $col++;
            $sheet->setCellValue($col . $row, $item->load_bill_to ?? '');
            $col++;
            $sheet->setCellValue($col . $row, $item->load_carrier ?? '');
            $col++;
            $sheet->setCellValue($col . $row, $shipper_location[0]['location'] ?? '');
            $col++;
            if (is_array($consignee_location)) {
                        foreach ($consignee_location as $idx => $loc) {
                            $sheet->setCellValue($col . $row, $loc['location'] ?? '');
                            $col++;
                        }
            }

            // Fill remaining empty consignee columns (if less than maxConsignees)
            if (is_array($consignee_location)) {
                $remaining = $maxConsignees - count($consignee_location);
                for ($i = 0; $i < $remaining; $i++) {
                    $sheet->setCellValue($col . $row, '');
                    $col++;
                }
            } else {
                for ($i = 0; $i < $maxConsignees; $i++) {
                    $sheet->setCellValue($col . $row, '');
                    $col++;
                }
            }

            $sheet->setCellValue($col . $row, $item->load_type_two ?? '');
            $col++;
            $sheet->setCellValue($col . $row, $item->load_advance_payment ?? '');
            $col++;
            $sheet->setCellValue($col . $row, $item->load_actual_delivery_date ? \Carbon\Carbon::parse($item->load_actual_delivery_date)->format('m/d/Y') : '');
            $col++;
            $sheet->setCellValue($col . $row, $item->load_carrier_due_date ? \Carbon\Carbon::parse($item->load_carrier_due_date)->format('m/d/Y') : '');
            $col++;
           $date = trim($item->load_carrier_due_date_on ?? '');
            $formatted = '';

            try {
                if (!empty($date)) {
                    $formatted = \Carbon\Carbon::parse($date)->format('m/d/Y');
                }
            } catch (\Exception $e) {
                $formatted = ''; // ignore invalid dates
            }

            $sheet->setCellValue($col.$row, $formatted);
            $col++;
            $sheet->setCellValue($col . $row, $item->carrier_mark_as_paid ?? '');
            $col++;
            $sheet->setCellValue($col . $row, $item->load_final_carrier_fee ?? '');
            $col++;
            $sheet->setCellValue($col . $row, $item->shipper_load_final_rate ?? '');
            $col++;
            $sheet->setCellValue(
            $col . $row,
            in_array($item->invoice_status, ['Paid Record', 'Paid'])
                ? (
                    $item->invoice_date
                        ? \Carbon\Carbon::parse($item->invoice_date)->format('m/d/Y')
                        : ($item->invoice_status_date
                            ? \Carbon\Carbon::parse($item->invoice_status_date)->format('m/d/Y')
                            : '')
                )
                : ''
            );
            $col++;

            $sheet->setCellValue($col . $row, in_array($item->invoice_status, ['Paid Record', 'Paid']) ? ($item->paper_work_date ? \Carbon\Carbon::parse($item->paper_work_date)->format('m/d/Y') : '') : '');
            $col++;
            $sheet->setCellValue($col . $row, $item->payment_receiving_date ? \Carbon\Carbon::parse($item->payment_receiving_date)->format('m/d/Y') : '');
            $col++;
            $sheet->setCellValue($col . $row, $item->invoice_status == 'Paid Record' ? ($item->receiving_amount ?? '-') : '');
            $col++;
            $sheet->setCellValue($col . $row, $item->invoice_status_date ? \Carbon\Carbon::parse($item->invoice_status_date)->format('m/d/Y') : '');
            $col++;
            $sheet->setCellValue($col . $row, $item->load_shipper_rate ?? '');
            $col++;
            $sheet->setCellValue($col . $row, $item->load_fsc_rate ?? '');
            $col++;
            $otherCharges = json_decode($item->shipper_load_other_charge, true);
            $totalAmount = 0;
            if (is_array($otherCharges)) {
                foreach ($otherCharges as $charge) {
                    if (isset($charge['amount'])) {
                        $amount = floatval(str_replace(',', '', $charge['amount']));
                        $totalAmount += $amount;
                    }
                }
            }
            $sheet->setCellValue($col . $row, $totalAmount);
            $col++;

            $sheet->setCellValue($col . $row, $item->shipper_load_final_rate ?? '');
            $col++;
            $sheet->setCellValue($col . $row, $item->load_carrier_fee ?? '');
            $col++;
            $sheet->setCellValue($col . $row, $item->load_billing_fsc_rate ?? '');
            $col++;
            $carrierCharges = json_decode($item->carrier_load_other_charge, true);
            $carrierTotal = 0;
            if (is_array($carrierCharges)) {
                foreach ($carrierCharges as $charge) {
                    if (isset($charge['amount'])) {
                        $amount = floatval(str_replace(',', '', $charge['amount']));
                        $carrierTotal += $amount;
                    }
                }
            }
            $sheet->setCellValue($col . $row, $carrierTotal);
            $col++;

            $sheet->setCellValue($col . $row, $item->load_final_carrier_fee ?? '');
            $col++;
            $shipperLoadFinalRate = $item->shipper_load_final_rate ?? 0;
            $loadFinalCarrierFee = $item->load_final_carrier_fee ?? 0;
            $shipperLoadFinalRate = is_numeric($shipperLoadFinalRate) ? $shipperLoadFinalRate : 0;
            $loadFinalCarrierFee = is_numeric($loadFinalCarrierFee) ? $loadFinalCarrierFee : 0;
            $margin = $shipperLoadFinalRate - abs($loadFinalCarrierFee);
            $sheet->setCellValue($col . $row, number_format($margin, 2));
            $col++;
            $shipperLoadFinalRate = floatval($shipperLoadFinalRate); 
            $carrierFee = floatval($loadFinalCarrierFee);
            $margin = $shipperLoadFinalRate - $carrierFee;

            $marginPercentage = $shipperLoadFinalRate > 0
                ? ($margin / $shipperLoadFinalRate) * 100
                : 0;

            $sheet->setCellValue(
                $col . $row,
                number_format($marginPercentage, 2) . '%'
            );

            $col++;
            $sheet->setCellValue($col . $row, $item->load_workorder ?? '');
            $col++;
            $sheet->setCellValue($col . $row, $item->cpr_check ?? '');
            $col++;
            $sheet->setCellValue($col . $row, $item->no_of_macro ?? '');
            $col++;
            // Safely get last consignee appointment
            $lastAppointment = null;
            if (!empty($consignee_appointment) && is_array($consignee_appointment)) {
                $lastItem = end($consignee_appointment);
                $lastAppointment = $lastItem['appointment'] ?? null;
            }

            // Format with Carbon
            $formattedAppointment = '-';
            if ($lastAppointment) {
                try {
                    $formattedAppointment = \Carbon\Carbon::parse($lastAppointment)->format('m/d/Y');
                } catch (\Exception $e) {
                    $formattedAppointment = '-';
                }
            }

            // Set value in Excel
            $sheet->setCellValue($col . $row, $formattedAppointment);
            $col++;

            // Safely get first shipper appointment
            $firstAppointment = null;
            if (!empty($shipper_appointment) && is_array($shipper_appointment)) {
                $firstItem = reset($shipper_appointment);
                if (isset($firstItem['appointment'])) {
                    $firstAppointment = $firstItem['appointment'];
                }
            }

            $formattedFirstAppointment = '-';
            if ($firstAppointment) {
                try {
                    $formattedFirstAppointment = \Carbon\Carbon::parse($firstAppointment)->format('m/d/Y');
                } catch (\Exception $e) {
                    $formattedFirstAppointment = '-';
                }
            }

            $sheet->setCellValue($col . $row, $formattedFirstAppointment);
            $col++;
            
            $sheet->setCellValue($col . $row, $item->load_equipment_type ?? '');
            $col++;

            $sheet->setCellValue($col . $row, $item->load_type ?? '');
            $col++;

            $sheet->setCellValue($col . $row, $item->cmt_agent ?? '');
            $col++;

            $sheet->setCellValue($col . $row, $item->load_currency ?? '');
            $col++;

            
            $row++;
        }

        $sheet->finish();
        $writer = new Xlsx($spreadsheet);
        $filename = 'Load Complete Report ' . date('Y-m-d') . '.xlsx';
        $outputPath = storage_path('app/load-complete-report-' . uniqid('', true) . '.xlsx');
        $writer->save($outputPath);

        return response()->download($outputPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }


    public function agingReportingExcel()
    {
        $data = Customer::with('user')->get()->map(function ($customer) {
            return [
                'id' => $customer->id,
                'customer_name' => $customer->customer_name,
                'agent' => $customer->user->name ?? 'N/A',
                'office' => $customer->user->office ?? 'N/A',
                'manager' => $customer->user->manager ?? 'N/A',
                'team_lead' => $customer->user->team_lead ?? 'N/A',
                'customerAging' => Load::where('customer_id', $customer->id)
                    ->where('invoice_status', 'Paid')
                    ->sum('shipper_load_final_rate'),
                'last30Days' => Load::where('customer_id', $customer->id)
                    ->where('invoice_status', 'Paid')
                    ->whereRaw('STR_TO_DATE(invoice_date, "%Y-%m-%d") BETWEEN ? AND ?', [
                        now()->subDays(30)->toDateString(),
                        now()->toDateString()
                    ])->sum('shipper_load_final_rate')
            ];
        });

        $headers = ['Sr.no', 'Customer Name', 'Team Lead', 'Manager', 'Office','Agent', 'Total Aging', 'Aging Above 30 Days'];
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data');

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        $row = 2;
        foreach ($data as $index => $item) {
            $col = 'A';
            $sheet->setCellValue($col . $row, $index + 1);
            $col++;
            
            $sheet->setCellValue($col . $row, $item['customer_name'] ?? '-');
            $col++;
            $sheet->setCellValue($col . $row, $item['team_lead'] ?? '-');
            $col++;
            $sheet->setCellValue($col . $row, $item['manager'] ?? '-');
            $col++;
            $sheet->setCellValue($col . $row, $item['office'] ?? '-');
            $col++;
            $sheet->setCellValue($col . $row, $item['agent'] ?? '-');
            $col++;
            $sheet->setCellValue($col . $row, $item['customerAging'] ? number_format($item['customerAging'], 2) : '-');
            $col++;
            $sheet->setCellValue($col . $row, $item['last30Days'] ? number_format($item['last30Days'], 2) : '-');
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'Aging Report ' . date('Y-m-d') .'.xlsx';
        $file = $this->getFileStream($writer);

        return response()->stream(
            function () use ($file) {
                echo $file;
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment;filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0',
            ]
        );
    }
	
	
	public function CarrierReportingExcel()
    {
        // Initialize the required data based on the $id
      $data =  Load::join('users', 'loads.user_id', '=', 'users.id')
		->select('loads.load_carrier', 'users.name as user_name')
		->selectRaw('SUM(loads.load_final_carrier_fee) AS total_revenue')
		->selectRaw('SUM(loads.load_final_carrier_fee - loads.load_carrier_fee) AS revenue_difference')
		->selectRaw('COUNT(loads.id) AS load_count')
		->selectRaw('SUM(CASE WHEN loads.load_status = "Open" THEN 1 ELSE 0 END) AS open_load_count')
		->selectRaw('SUM(CASE WHEN loads.load_status = "Delivered" THEN 1 ELSE 0 END) AS delivered_load_count')
		->selectRaw('SUM(CASE WHEN loads.invoice_status = "Completed" THEN 1 ELSE 0 END) AS completed_load_count')
		->groupBy('loads.load_carrier', 'users.name')
		->get();

		$headers = ['Sr.no', 'load carrier', '# of Load', 'Gross Revenue', 'Carrier Pay', 'Profit','Miles', 'Revenue / Mile', 'Pay / Mile'];
		$columns = ['load_carrier','load_count','total_revenue','revenue_difference', 'profit','','',''];
        
    
        // Create a new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data');
    
        // Add headers
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }
    
        // Populate rows with data
        $row = 2;
        foreach ($data as $index => $item) {
            $col = 'A';
            $sheet->setCellValue($col . $row, $index + 1); // Sr.no starts at 1
            $col++;
            
            foreach ($columns as $column) {
                $value = $item;
				if($column == "profit"){
					$finalrate = $item->total_revenue - $item->revenue_difference;
					$value = $finalrate;
				}else{
					foreach (explode('.', $column) as $segment) {
						if (isset($value->{$segment})) {
							$value = $value->{$segment};
						}else {
							$value = '-'; // default value for nulls or missing data
							break;
						}
					}
				}
                
                $sheet->setCellValue($col . $row, $value ?? '-');
                $col++;
            }
            $row++;
        }
    
        // Write the spreadsheet to a file in memory
        $writer = new Xlsx($spreadsheet);
        
        // Set headers for file download
        $filename = 'carrier.xlsx';
        $file = $this->getFileStream($writer);
    
        // Return file as a download response
        return response()->stream(
            function () use ($file) {
                echo $file;
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment;filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0',
            ]
        );
    }
	
	public function limitReportingExcel(){
		
	}
	
	
	public function getCustomerInfo($id)
    {
        $customer = Customer::with(['state', 'country'])->find($id);

        if ($customer) {
            return response()->json([
                'city' => $customer->customer_city,
                //'country'  => $customer->customer_country,
				'country' => is_numeric($customer->customer_country)
				? optional($customer->country)->name
				: $customer->customer_country,
				'state' => is_numeric($customer->customer_state)
				? optional($customer->state)->name
				: $customer->customer_state,
                //'state' => $customer->state->name,
                'zip' => $customer->customer_zip,
                'customer_telephone' => $customer->customer_telephone,
                'customer_billing_address' => $customer->customer_billing_address,
                'customer_billing_country' => $customer->customer_billing_country,
                'customer_billing_state' => $customer->customer_billing_state,
                'customer_billing_city' => $customer->customer_billing_city,
                'customer_billing_zip' => $customer->customer_billing_zip,
                'customer_name' => $customer->customer_name,
                'customer_address' => $customer->customer_address,


            ]);
        }

        return response()->json(['error' => 'Customer not found'], 404);
    }


        public function getCarrierInfo($id)
        {
            $carrier = External::find($id);

            if ($carrier) {
                return response()->json([
                    'city' => $carrier->carrier_city,
                    'state' => $carrier->carrier_state,
                    'country' => $carrier->carrier_country,
                    'zip' => $carrier->carrier_zip,
                    'telephone' => $carrier->carrier_telephone,
                    'carrier_address_two' => $carrier->carrier_address_two,
                    'carrier_name' => $carrier->carrier_name,
                ]);
            }

            return response()->json(['error' => 'Carrier not found'], 404);
        }

public function searchLoadsOnInvoice(Request $request)
{
    $searchNumbers = $request->input('load_numbers');
    $numbersArray = $searchNumbers ? array_map('trim', explode(',', $searchNumbers)) : [];

    $perPage = 50;

    $query = Load::with(['user','customer','carrier','user.officedata'])
        ->where('load_status', 'Completed')
        ->where('invoice_status', 'Paid Record')
        ->orderBy('loads.id', 'desc');

    if (!empty($numbersArray)) {
        $query->whereIn('load_number', $numbersArray);
    }

    $invoiced = $query->paginate($perPage);

    // if ajax, return only rows
    if ($request->ajax()) {
        return view('accounts.partials.accounting_invoiced', compact('invoiced'))->render();
    }

    return view('accounts.accounting', compact('invoiced'));
}

public function uploadmailDocument(Request $request)
{
    $id = $request->input('load_no');

    if ($request->hasFile('document')) {
        $files = $request->file('document');
        $uploadPaths = [];

        $targetDir = public_path('uploads/delivery-order/' . $id . '/');

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        foreach ($files as $file) {
			$originalName = $file->getClientOriginalName();
			$cleanName = preg_replace('/[^A-Za-z0-9\.\-_]/', '', $originalName);

			$cleanName = strtolower($cleanName);
            // Generate a unique file name to avoid conflicts
            $fileName = uniqid() . '_' . $cleanName;
            $file->move($targetDir, $fileName);
            $uploadPaths[] = '/uploads/delivery-order/' . $id . '/' . $fileName;
        }

        // Update the load record with merged document paths
        $load = Load::findOrFail($id);

        $oldFiles = json_decode($load->load_delivery_do_file, true) ?? [];
        $merged = array_merge($oldFiles, $uploadPaths);
        $merged = array_values(array_unique($merged)); // Ensure no duplicates & reindex

        $load->load_delivery_do_file = json_encode($merged);
        $load->save();

        return response()->json([
            'message' => 'Files uploaded successfully.',
            'files' => $uploadPaths
        ]);
    }

    return response()->json([
        'message' => 'No files uploaded.'
    ], 400);
}

public function carrierupdateInvoiceThrough(Request $request)
{
    $request->validate([
        'id' => 'required|integer|exists:loads,id',
        'invoice_through' => 'nullable|in:DIRECT,OTR,Buyout',
    ]);

    $update = Load::find($request->id);

    if($update){
        $update->invoice_through = $request->invoice_through;
        $update->save();

        return response()->json([
            'status' => 'success',
            'success' => true,
            'invoice_through' => $update->invoice_through,
        ]);
    }

    return response()->json(['status' => 'error'], 404);
}


public function carrier_verification()
{
    // eager load to avoid N+1
   
     $loads = Load::paginate(50);
     //echo"<pre>"; print_r($loads);  echo"<pre>"; die;
    return view('accounts.carrier_verification', compact('loads'));
}



public function carrier_verification_save(Request $request)
{
    if (!$request->load_id) {
        return response()->json([
            'error' => true,
            'message' => 'load_id missing!',
            'request' => $request->all()
        ], 422);
    }

    // OLD DATA
    $oldVerification = DB::table('carrier_verification')
        ->where('load_id', $request->load_id)
        ->first();

    // SAVE / UPDATE
    DB::table('carrier_verification')->updateOrInsert(
        ['load_id' => $request->load_id],
        [
            'user_id' => auth()->id(),
            'bank_information' => $request->bank_information,
            'factoring' => $request->factoring,
            'verification_factoring' => $request->verification_factoring,
            'verification_carrier_phone_number' => $request->phone_number,
            'verification_carrier_email' => $request->verification_carrier_email,
            'verification_remark' => $request->verification_remark,
            'follow_up_note' => $request->follow_up_note,
            'updated_at' => now(),
            'created_at' => now(),
        ]
    );

    // FILE UPLOAD
    if ($request->hasFile('carrier_bank_docs')) {

        $id = (int) $request->load_id; // ensure integer

        // Normalize files (handles single & multiple upload)
        $files = is_array($request->file('carrier_bank_docs'))
            ? $request->file('carrier_bank_docs')
            : [$request->file('carrier_bank_docs')];

        $uploadPaths = [];

        $targetDir = public_path("uploads/carrierbankdocs/{$id}");

        // Create directory if not exists
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        foreach ($files as $file) {

            // Original file name
            $originalName = $file->getClientOriginalName();

            // Clean filename
            $cleanName = preg_replace('/[^A-Za-z0-9.\-_]/', '', $originalName);
            $cleanName = strtolower($cleanName);

            // Unique file name
            $fileName = uniqid('', true) . '_' . $cleanName;

            // Move file
            $file->move($targetDir, $fileName);

            // Store relative public path
            $uploadPaths[] = "/uploads/carrierbankdocs/{$id}/{$fileName}";
        }

        // Fetch record
        $carrierveri = CarrierVerification::where('load_id', $id)->firstOrFail();

        // Merge old + new files
        $oldFiles = json_decode($carrierveri->file_path, true) ?? [];

        $mergedFiles = array_values(array_unique(array_merge($oldFiles, $uploadPaths)));

        // Save
        $carrierveri->carrier_bank_docs = json_encode($mergedFiles);
        $carrierveri->save();
    }


    // LOG EVERYTHING
    addToLog(
        '',
        $request->load_id,
        "Carrier Verification Saved | Load #{$request->load_id}",
        json_encode($oldVerification),
        json_encode($request->except(['_token', 'carrier_bank_docs']))
    );

    return response()->json(['success' => true]);
}


public function deleteDoc($id)
{
    $doc = CarrierVerification::findOrFail($id);


    Storage::disk('public')->delete($doc->file_path);
    $doc->delete();

    return response()->json(['success' => true]);
}

public function carrierverificationgetFiles(Request $request)
{
    $loadId = $request->load_id;
    $carrier = \App\Models\CarrierVerification::where('load_id', $loadId)->first();

    $files = [];

    if ($carrier && $carrier->carrier_bank_docs) {
        // Decode JSON stored in DB
        $carrierFiles = json_decode($carrier->carrier_bank_docs, true);

        if ($carrierFiles && is_array($carrierFiles)) {
            foreach ($carrierFiles as $filePath) {
                // Convert to full URL for browser
                $files[] = "/public{$filePath}";
            }
        }
    }
    // ✅ ADD LOG ONLY
    $user = auth()->user();
    $subject = "Carrier Verification Files Viewed | Load ID: {$loadId} | Viewed By: " . ($user->name ?? 'N/A');

    addToLog(
        $customerId = '',
        $id = $loadId,
        $subject,
        $oldData = '',
        $newData = json_encode($files)
    );

    return response()->json(['files' => $files]);
}

public function updatePrePayment(Request $request)
{
    Customer::where('id', $request->customer_id)
        ->update(['pre_payment' => $request->pre_payment]);

    return response()->json(['success' => true]);
}

public function updateVendorInternalNotes(Request $request)
{
    $load = Load::find($request->id);

    $oldNotes = $load->vendorInternalNotes
        ? json_decode($load->vendorInternalNotes, true)
        : [];

    $oldNotes[] = [
        'note' => $request->note,
        'user' => auth()->user()->name,
        'date' => now()->format('m-d-Y H:i A')
    ];

    $load->vendorInternalNotes = json_encode($oldNotes);
    $load->save();

    return response()->json(['status' => true]);
}
public function getVendorNotes(Request $request)
{
    $load = Load::find($request->id);
    $notes = json_decode($load->vendorInternalNotes, true);

    return response()->json($notes);
}




    public function customerApprovalFormAdmin(){
        $customers = CustomerApprovalForm::orderBy('created_at', 'desc')->get();
        return view('admin.customer_approval', compact('customers'));
    }


    public function customerApprovalFormExcel()
    {
        $data = CustomerApprovalForm::select(
            'agent_name',
            'agent_email',
            'customer_email',
            'company_name',
            'address',
            'country',
            'state',
            'city',
            'zip_code',
            'dispatcher_first_name',
            'dispatcher_last_name',
            'phone_number',
            'requested_credit_limit',
            'created_at'
        )
        ->orderBy('created_at', 'desc')
        ->get();

        // ---------------- Excel generation code (unchanged) ----------------
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Customer Approval Forms');

        $headers = [
            'Sr No','Agent Name','Agent Email','Customer Email','Company Name',
            'Address','Country','State','City','Zip Code',
            'Dispatcher First Name','Dispatcher Last Name',
            'Phone Number','Requested Credit Limit','Added Date'
        ];

        $columns = [
            'agent_name','agent_email','customer_email','company_name',
            'address','country','state','city','zip_code',
            'dispatcher_first_name','dispatcher_last_name',
            'phone_number','requested_credit_limit','created_at'
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col.'1', $header);
            $sheet->getStyle($col.'1')->getFont()->setBold(true);
            $col++;
        }

        $row = 2;
        foreach ($data as $index => $item) {
            $col = 'A';
            $sheet->setCellValue($col++.$row, $index + 1);

            foreach ($columns as $column) {
                $value = $item->{$column} ?? '-';

                if ($column === 'requested_credit_limit') {
                    $value = number_format((float) $value, 2);
                }

                if ($column === 'created_at' && $value !== '-') {
                    $value = \Carbon\Carbon::parse($value)
                        ->timezone('America/New_York')
                        ->format('m-d-Y h:i A');
                }

                $sheet->setCellValue($col++.$row, $value);
            }
            $row++;
        }

        // ✅ LOG ENTRY HERE
        $user = Auth::user();
        $subject = "Downloaded Customer Approval Form Excel, User Name: " . ($user->name ?? 'N/A');
        

        addToLog(
            $customerId = '',
            $id = '',
            $subject,
            $oldData = '',
            $newData = ''
        );

        // ---------------- Download ----------------
        $writer = new Xlsx($spreadsheet);
        $filename = 'Customer_Approval_Form_' . date('m-d-Y') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
    public function uploadCreditDocs(Request $request, $id)
    {
        $request->validate([
            'credit_doc_upload.*' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120'
        ]);

        $form = CustomerApprovalForm::findOrFail($id);

        $uploadPath = public_path("uploads/creditapplicationdocs/{$id}");

        // Create directory if not exists
        if (!File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0755, true);
        }

        $uploadedFiles = [];

        if ($request->hasFile('credit_doc_upload')) {
            foreach ($request->file('credit_doc_upload') as $file) {

                $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

                $file->move($uploadPath, $fileName);

                $uploadedFiles[] = "uploads/creditapplicationdocs/{$id}/{$fileName}";
            }
        }

        // Merge with existing files (if already uploaded)
        $existingFiles = $form->credit_doc_upload 
            ? json_decode($form->credit_doc_upload, true)
            : [];

        $form->credit_doc_upload = json_encode(array_merge($existingFiles, $uploadedFiles));
        $form->save();

        return back()->with('success', 'Credit documents uploaded successfully.');

    }

    public function creditdocsgetFiles(Request $request)
    {
        $id = $request->id;
        $path = public_path("uploads/creditapplicationdocs/{$id}");

        $files = [];

        if (File::exists($path)) {
            foreach (File::files($path) as $file) {
                $filename = $file->getFilename();
                $files[] = [
                    'name' => $file->getFilename(),
                    'url'  => url("public/uploads/creditapplicationdocs/{$id}/{$filename}")
                ];
            }
        }

        return response()->json([
            'success' => true,
            'files' => $files
        ]);
    }

public function factoring_add(Request $request)
{
    $request->validate([
        'factoring_name' => 'required|string|max:255|unique:factorings,factoring_name',
    ]);

    // Save factoring
    $factoring = Factoring::create([
        'user_id' => auth()->id(),
        'factoring_name' => $request->factoring_name,
    ]);

    // Add log
    $user = Auth::user();
    $subject = "Added Factoring Company: " . $request->factoring_name .
               ", User Name: " . ($user->name ?? 'N/A');

    addToLog(
        $customerId = '',
        $id = $factoring->id ?? '',
        $subject,
        $oldData = '',
        $newData = json_encode([
            'factoring_name' => $request->factoring_name,
            'user_id' => $user->id ?? null
        ])
    );

    return redirect()->back()->with('success', 'Factoring company added successfully.');
}

public function factoring()
{
    $factorings = Factoring::with('user')->orderBy('id', 'desc')->get();
    return view('accounts.factoring', compact('factorings'));
}

public function factoring_update(Request $request, $id)
{
    $factoring = Factoring::findOrFail($id);

    $request->validate([
        'factoring_name' => 'required|string|max:255|unique:factorings,factoring_name,' . $id,
    ]);

    $oldData = $factoring->toArray();

    $factoring->update([
        'factoring_name' => $request->factoring_name,
    ]);

    // Log
    $user = auth()->user();
    $subject = "Updated Factoring: {$request->factoring_name}, User: {$user->name}";

    addToLog('', $factoring->id, $subject, json_encode($oldData), json_encode($factoring->toArray()));

    return redirect()->back()->with('success', 'Factoring updated successfully.');
}


public function factoring_delete($id)
{
    $factoring = Factoring::findOrFail($id);

    $oldData = $factoring->toArray();

    $factoring->delete();

    // Log
    $user = auth()->user();
    $subject = "Deleted Factoring: {$oldData['factoring_name']}, User: {$user->name}";

    addToLog('', $id, $subject, json_encode($oldData), '');

    return redirect()->back()->with('success', 'Factoring deleted successfully.');
}

public function cmt_Data()
{
    $cmt = Cmt::with('user')->orderBy('id', 'desc')->get();
    return view('accounts.cmt_data', compact('cmt'));

}

public function assignCustomer(Request $request)
{
    $broker = CustomerApprovalForm::find($request->id);

 
    $customer = new Customer();
    // $customer->user_id = auth()->id();
    $customer->customer_name = $broker->company_name;
    $customer->customer_email = $broker->customer_email;
    $customer->customer_address = $broker->address;
    $customer->customer_country = $broker->country;
    $customer->customer_state = $broker->state;
    $customer->customer_city = $broker->city;
    $customer->customer_zip = $broker->zip_code;
    $customer->customer_primary_contact = $broker->dispatcher_first_name . ' ' . $broker->dispatcher_last_name;
    $customer->customer_telephone = $broker->phone_number;

    $customer->adv_customer_credit_limit = $broker->requested_credit_limit ?? 0;
    $customer->remaining_credit = $broker->requested_credit_limit ?? 0;

    $customer->status = 'Not Approved';
    $customer->commenter_name = '';
    $customer->save();

    $broker->duplicate = "Yes";
    $broker->save();

    return response()->json(['success' => true]);
}

public function customerApprovalupdateStatus(Request $request)
{
    $data = CustomerApprovalForm::find($request->id);

    if ($data) {
        $data->status = $request->status;
        $data->save();

        // Send email using core PHP mail() 
        if ($data->agent_email) {
            $to = $data->agent_email;
            $subject = "Customer Approval Status Updated - " . $request->status;

            // Email body
            $message = "
            <html>
            <head>
                <title>Approval Status Update</title>
            </head>
            <body>
                <h2>Approval Status Update</h2>
                <p>Dear User,</p>
                <p>Your customer approval status has been updated by the admin.</p>
                <table border='1' cellpadding='8' cellspacing='0'>
                    <tr>
                        <td><strong>Email:</strong></td>
                        <td>{$data->agent_email}</td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>{$request->status}</td>
                    </tr>
                </table>
                <br>
                <p>Regards,<br><strong>Admin Team</strong></p>
            </body>
            </html>
            ";

            // Headers
            $headers = "MIME-Version: 1.0" . "\r
";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r
";
            $headers .= "From: admin@yourdomain.com" . "\r
";
            $headers .= "Cc: sumit@geeshasolutions.com" . "\r
";

            mail($to, $subject, $message, $headers);
        }

        return response()->json(['success' => true]);
    }

    return response()->json(['success' => false], 404);
}


    public function generateBolPdf($id)
    {
        $load = Load::findOrFail($id);

        $options = new Options();
        $options->set('defaultFont', 'Arial'); // Use a common font
        $options->set('isRemoteEnabled', true); // Enable remote image loading for logo
        $dompdf = new Dompdf($options);

        $html = view('broker.bol_pdf', compact('load'))->render();
        $dompdf->loadHtml($html);

        // (Optional) Set paper size and orientation
        $dompdf->setPaper('letter', 'portrait');

        $dompdf->render();

        // Stream the file for download
        return $dompdf->stream("BOL-{$load->load_number}.pdf", ["Attachment" => true]);
    }

    public function uploadCarrierDocuments(Request $request)
{
    $request->validate([
        'carrier_id'    => 'required|integer',
        'doc_upload.*'  => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx,xlsx,xls|max:20480',
    ]);

    $carrier = External::find($request->carrier_id);

    if (!$carrier) {
        return response()->json([
            'success' => false,
            'message' => 'Carrier not found.'
        ]);
    }

    // Existing documents
    $documents = $carrier->doc_upload;

    if (!is_array($documents)) {
        $documents = json_decode($documents, true) ?: [];
    }

    // Upload new files
    if ($request->hasFile('doc_upload')) {

        foreach ($request->file('doc_upload') as $file) {

            $originalName = $file->getClientOriginalName();

            $fileName = time().'_'.uniqid().'_'.preg_replace('/\s+/', '_', $originalName);

            $destination = public_path('carrier_doc');

            if (!File::exists($destination)) {
                File::makeDirectory($destination, 0755, true);
            }

            $file->move($destination, $fileName);

            $documents[] = [
                'original_name' => $originalName,
                'file_name'     => $fileName,
                'file_path'     => 'carrier_doc/'.$fileName,
            ];
        }
    }

    // Save JSON
    $carrier->doc_upload = $documents;
    $carrier->save();

    // Build HTML
    $html = '';

    if (count($documents)) {

        foreach ($documents as $index => $doc) {

            $html .= '
            <div class="mb-2 d-flex align-items-center justify-content-between border-bottom pb-2">

                <span class="trim-file-name"
                      data-title="'.$doc['original_name'].'"
                      title="'.$doc['original_name'].'">
                      '.$doc['original_name'].'
                </span>

                <div>

                    <a href="'.asset('public/'.$doc['file_path']).'"
                       target="_blank"
                       class="btn btn-sm btn-primary">
                        View
                    </a>

                    <button type="button"
                            class="btn btn-sm btn-danger"
                            onclick="deleteCarrierDocument('.$carrier->id.', '.$index.')">
                        Delete
                    </button>

                </div>

            </div>';
        }

    } else {

        $html = '<span class="text-muted">No documents uploaded.</span>';

    }

    return response()->json([
        'success' => true,
        'html'    => $html,
        'message' => 'Documents uploaded successfully.'
    ]);
}

public function deleteCarrierDocument(Request $request)
{
    $request->validate([
        'carrier_id' => 'required|integer',
        'doc_index'  => 'required|integer',
    ]);

    $carrier = External::find($request->carrier_id);

    if (!$carrier) {
        return response()->json([
            'success' => false,
            'message' => 'Carrier not found.'
        ]);
    }

    // Get existing documents
    $documents = $carrier->doc_upload;

    if (!is_array($documents)) {
        $documents = json_decode($documents, true) ?: [];
    }

    $docIndex = $request->doc_index;

    if (!isset($documents[$docIndex])) {
        return response()->json([
            'success' => false,
            'message' => 'Document not found.'
        ]);
    }

    // Delete physical file
    $filePath = public_path($documents[$docIndex]['file_path']);

    if (file_exists($filePath)) {
        @unlink($filePath);
    }

    // Remove from array
    unset($documents[$docIndex]);

    // Re-index array
    $documents = array_values($documents);

    // Save updated JSON
    $carrier->doc_upload = $documents;
    $carrier->save();

    // Build updated HTML
    $html = '';

    if (count($documents)) {

        foreach ($documents as $index => $doc) {

            $html .= '
            <div class="mb-2 d-flex align-items-center justify-content-between border-bottom pb-2">

                <span class="trim-file-name"
                      data-title="'.$doc['original_name'].'"
                      title="'.$doc['original_name'].'">
                    '.$doc['original_name'].'
                </span>

                <div>

                    <a href="'.asset('public/'.$doc['file_path']).'"
                       target="_blank"
                       class="btn btn-sm btn-primary">
                        View
                    </a>

                    <button type="button"
                            class="btn btn-sm btn-danger"
                            onclick="deleteCarrierDocument('.$carrier->id.', '.$index.')">
                        Delete
                    </button>

                </div>

            </div>';
        }

    } else {

        $html = '<span class="text-muted">No documents uploaded.</span>';

    }

    return response()->json([
        'success' => true,
        'message' => 'Document deleted successfully.',
        'html'    => $html
    ]);
}

public function exportCreditLimitLog()
{
    $customers = Customer::select('customer_name', 'remaining_credit_logs')->get();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Headers
    $sheet->setCellValue('A1', 'Customer Name');
    $sheet->setCellValue('B1', 'Remaining Credit Logs');

    // Header Style
    $sheet->getStyle('A1:B1')->getFont()->setBold(true);

    $row = 2;

    foreach ($customers as $customer) {

        $sheet->setCellValue('A' . $row, $customer->customer_name);

        $logs = json_decode($customer->remaining_credit_logs, true);

        $logText = '';

        if (!empty($logs) && is_array($logs)) {

            foreach ($logs as $index => $log) {

                $amount = (float)($log['credit_limit'] ?? 0);

                // Currency format
                $creditLimit = $amount < 0
                    ? '-$' . number_format(abs($amount), 2)
                    : '$' . number_format($amount, 2);

                // Date format
                $creditTime = !empty($log['credit_time'])
                    ? date('M d Y', strtotime($log['credit_time']))
                    : '';

                $logText .= ($index + 1) . ". Credit Limit: {$creditLimit} | {$creditTime}" . PHP_EOL;
            }

        } else {

            $logText = 'No Logs';

        }

        $sheet->setCellValue('B' . $row, trim($logText));

        // Wrap text
        $sheet->getStyle('B' . $row)
              ->getAlignment()
              ->setWrapText(true);

        // Top align
        $sheet->getStyle('A' . $row . ':B' . $row)
              ->getAlignment()
              ->setVertical(Alignment::VERTICAL_TOP);

        $row++;
    }

    // Auto-size columns
    foreach (range('A', 'B') as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    // Freeze header
    $sheet->freezePane('A2');

    $filename = 'Remaining_Credit_Logs_' . date('Y-m-d_H-i-s') . '.xlsx';

    // Download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
public function all_load_status_ar(Request $request){
$tabs = ['all_load', 'open', 'delivered', 'completed', 'invoiced', 'invoiced_paid'];

		foreach ($tabs as $tab) {
			if ($request->has($tab)) {
				Paginator::currentPageResolver(function () use ($request, $tab) {
					return $request->input($tab);
				});
				break; // Stop after finding the matching tab
			}
		}
        $broker_status = Load::with('user')->orderBy("id", "desc")->paginate(50)->setPageName('all_load'); 
        $allagent = User::pluck('name');
        $open = Load::with('user')->where('load_status', 'Open')->orderBy("id", "desc")->paginate(50)->setPageName('open'); 
        $deliverd = Load::with('user')->where('load_status', 'Delivered')->orderBy("id", "desc")->paginate(50)->setPageName('delivered'); 
        $complete = Load::where('load_status', 'Completed')
                    ->where(function ($query) {
                        $query->where('invoice_status', '')
                            ->orWhereNull('invoice_status');
                    })
                    ->with(['user', 'customer', 'carrier'])
                    ->orderBy("loads.id", "desc")
                    ->paginate(50)->setPageName('completed');
        $invoice_paid = Load::with('user')->where('invoice_status', 'Paid')->orderBy("id", "desc")->paginate(50)->setPageName('invoiced'); 
        $paid_record = Load::with('user')->where('invoice_status', 'Paid Record')->orderBy("id", "desc")->paginate(50)->setPageName('invoiced_paid'); 
        $manager = Manger::get();
        $teamlead = TeamLeader::get();
        $office = Office::get();
		$agent = User::where('role_id', 21)->get();
		
		if ($request->ajax()) {
			
			if($request->input('tab') == '#all_load'){
				return view('admin.home.all_load', compact('broker_status', 'allagent', 'open', 'deliverd', 'complete', 'invoice_paid', 'paid_record', 'manager', 'teamlead', 'office','agent'))->render();
			}else if($request->input('tab') == '#open'){
				return view('admin.home.open_load', compact('broker_status', 'allagent', 'open', 'deliverd', 'complete', 'invoice_paid', 'paid_record', 'manager', 'teamlead', 'office','agent'))->render();
			}else if($request->input('tab') == '#delivered'){
				return view('admin.home.delivered', compact('broker_status', 'allagent', 'open', 'deliverd', 'complete', 'invoice_paid', 'paid_record', 'manager', 'teamlead', 'office','agent'))->render();
			}else if($request->input('tab') == '#completed'){
				return view('admin.home.completed', compact('broker_status', 'allagent', 'open', 'deliverd', 'complete', 'invoice_paid', 'paid_record', 'manager', 'teamlead', 'office','agent'))->render();
			}else if($request->input('tab') == '#invoiced'){
				return view('admin.home.invoiced', compact('broker_status', 'allagent', 'open', 'deliverd', 'complete', 'invoice_paid', 'paid_record', 'manager', 'teamlead', 'office','agent'))->render();
			}else if($request->input('tab') == '#invoiced_paid'){
				return view('admin.home.invoiced_paid', compact('broker_status', 'allagent', 'open', 'deliverd', 'complete', 'invoice_paid', 'paid_record', 'manager', 'teamlead', 'office','agent'))->render();
			}
				
		}
         return view('admin.home', compact('broker_status', 'allagent', 'open', 'deliverd', 'complete', 'invoice_paid', 'paid_record', 'manager', 'teamlead', 'office','agent'));

}

    public function updateArAgingClose(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:loads,id',
            'value' => 'nullable|in:Bank Charges Adjusted,Short Pay Adjusted Internally',
        ]);

        $load = Load::findOrFail($request->id);
        $load->ar_aging_close = $request->value;
        $load->save();

        return response()->json([
            'status' => true,
            'message' => 'Updated successfully.'
        ]);
    }
}