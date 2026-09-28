
        @foreach($complete as $i => $completes)
            @php
                $shipperAppointment = json_decode($completes->load_shipper_appointment, true);
                $firstAppointment = '';

                if (is_array($shipperAppointment) && !empty($shipperAppointment)) {
                reset($shipperAppointment);
                $firstItem = current($shipperAppointment);

                if (is_array($firstItem)) {
                // If first item is an array, get the first string value or a key like 'date'
                if (isset($firstItem['date'])) {
                $firstAppointment = $firstItem['date'];
                } else {
                // fallback: get first value of the array
                $firstAppointment = reset($firstItem);
                }
                } elseif (is_string($firstItem)) {
                // If first item is a string directly
                $firstAppointment = $firstItem;
                }
                }
            @endphp

            @php
                $consigneeAppointment = json_decode($completes->load_consignee_appointment, true);
                $lastAppointment = '';

                if (is_array($consigneeAppointment) && !empty($consigneeAppointment)) {
                    $lastItem = end($consigneeAppointment);

                    if (is_array($lastItem)) {
                        // If last item is an array, try to get 'date' key or first value
                        if (isset($lastItem['date'])) {
                            $lastAppointment = $lastItem['date'];
                        } else {
                            $lastAppointment = reset($lastItem);
                        }
                    } elseif (is_string($lastItem)) {
                        $lastAppointment = $lastItem;
                    }
                }
            @endphp
            <tr>
                <td>{{ $i + 1  }}</td>
                <td>
                    <a id="markAsPaidBtn_{{ $completes->id }}" title="Approved"
                    class="text-left {{ $completes->invoice_status === 'Paid' ? 'success' : 'danger' }} btn btn-primary btn-sm"
                    onclick="markAsPaid({{ $completes->id }})"><i class=" fas fa-check"></i></a>

                    <a class="btn btn-primary btn-sm" onclick="openUploadWindow('{{route('load.edit', $completes->id)}}')"><i class=" fas fa-edit"></i></a>
                    

                    <a href="{{route('CompletedPublicDoc',$completes->id)}}" class="btn btn-primary btn-sm" target="_blank" rel="noopener noreferrer"> <i class="fas fa-file"></i></a> 
                
                    @if(($showInvoiceAction ?? true) && ($completes->load_status == 'Delivered' || $completes->invoice_status == 'Completed'))
                        <a href="{{ route('print.invoice', $completes->id) }}" class="btn btn-success btn-sm">Invoice</a>
                    @endif
                    <a href="{{ route('accounts.view_loads_detail', $completes->id) }}" class="btn btn-primary btn-sm" title="logs"> <i class="fas fa-eye"></i></a>
                </td>
                <td>{{ $completes->load_number }}</td>
                <td>{{ optional($completes->customer)->customer_name }}</td>
                <td>{{ $completes->shipper_load_final_rate }}</td>
                <td>
                    <textarea name="internal_notes" row="10" col="5" style="width: 250px !important;height: 27px;" id="internal_notes_{{ $completes->id }}" data-id="{{ $completes->id }}" class="internal-notes" placeholder="Enter additional notes...">{{ nl2br(e($completes->internal_notes)) }}</textarea>
                </td>
                <td>
                    <input type="date" class="form-control peperworkdate_{{$completes->id}}" name="paper_work_date" id="invoice_date" value="{{ !empty($completes->paper_work_date) ? \Carbon\Carbon::parse($completes->paper_work_date)->format('m-d-Y') : '' }}" data-id="{{$completes->id}}">
                </td>
                <td>{{ optional($completes->user)->name }}</td>
                <td>{{ $completes->load_workorder }}</td>
                <td>{{ \Carbon\Carbon::parse($completes->created_at)->format('m-d-Y') }}</td>
                <td>{{ $firstAppointment ? \Carbon\Carbon::parse($firstAppointment)->format('m-d-Y') : '' }}</td>
                <td>{{ $lastAppointment ? \Carbon\Carbon::parse($lastAppointment)->format('m-d-Y') : '' }}</td>
                <td>{{ $completes->load_actual_delivery_date }}</td>
                <td>{{ $completes->load_carrier }}</td>
				<td>{{ $completes->load_mc_no }}</td>
                @php
    // helper to clean numeric strings like "1,537.50" or "$1,537.50"
    $cleanNumber = function($val) {
        if (is_null($val) || $val === '') return 0.0;
        // cast numeric directly
        if (is_numeric($val)) return (float) $val;
        // remove all characters except digits, dot and minus
        $onlyNum = preg_replace('/[^\d\.\-]/', '', (string) $val);
        return $onlyNum === '' ? 0.0 : (float) $onlyNum;
    };

    $baseRateRaw = $completes->load_carrier_fee ?? 0;
    $fscRaw = $completes->load_billing_fsc_rate ?? 0;
    $chargesJson = $completes->carrier_load_other_charge ?? '[]';

    $baseRate = $cleanNumber($baseRateRaw);
    // handle fsc like "10%" or "10.0" etc.
    $fscPercent = $cleanNumber($fscRaw);

    // decode charges
    $charges = json_decode($chargesJson, true);
    if (!is_array($charges)) $charges = [];

    $otherCharges = 0.0;
    foreach ($charges as $c) {
        $amount = $c['amount'] ?? 0;
        $otherCharges += $cleanNumber($amount);
    }

    // compute FSC amount (percentage of base)
    $fscAmount = ($fscPercent / 100.0) * $baseRate;

    // final total
    $total = $baseRate + $fscAmount + $otherCharges;
@endphp

<!-- Display breakdown -->
<td class="dynamic-data"> {{ number_format($baseRate, 2) }}</td>
<td class="dynamic-data"> {{ rtrim(rtrim(number_format($fscPercent,2), '0'), '.') }}%</td>
<td class="dynamic-data">
    
    {{ number_format($otherCharges, 2) }}
</td>
<td class="dynamic-data">
    {{ number_format($total, 2) }}
</td>
                   @php
                        $shipperLocations = json_decode($completes->load_shipper_location, true);
                        $consigneeLocations = json_decode($completes->load_consignee_location, true);

                        $firstShipper = is_array($shipperLocations) && isset($shipperLocations[0]['location']) ? $shipperLocations[0]['location'] : '';
                        
                        $lastConsignee = '';
                        if (is_array($consigneeLocations) && count($consigneeLocations) > 0) {
                            $lastConsignee = $consigneeLocations[count($consigneeLocations) - 1]['location'] ?? '';
                        }
                    @endphp

                <td>{{ $firstShipper }}</td>
                <td>{{ $lastConsignee }}</td>
                <td class="text-success">{{ $completes->load_status }}</td>
                <td class="dynamic-data">
                    @if($completes->load_status == 'Delivered' || $completes->invoice_status == 'Completed' )
                    @php
                    $deliveredDate = \Carbon\Carbon::parse($completes->created_at);
                    $currentDate = \Carbon\Carbon::now();
                    $differenceInDays = $deliveredDate->diffInDays($currentDate);
                    @endphp
                    {{ $differenceInDays }} days
                    @elseif($completes->invoice_status == 'Completed' || $completes->load_status == 'Delivered')
                    Aging Complete
                    @endif
                </td>
                
                
                
            </tr>

        @endforeach

<script>
    function openUploadWindow(url) {
        // Define the size of the new window
        var width = 1500;   // Width of the new window
        var height = 800;  // Height of the new window

        // Calculate the position to center the window
        var left = screen.width / 2 - width / 2;   // Center horizontally
        var top = screen.height / 2 - height / 2;  // Center vertically

        // Open the new window with the specified URL and properties
        var newWindow = window.open(url, 'UploadWindow', 'width=' + width + ',height=' + height + ',top=' + top + ',left=' + left + ',resizable=yes,scrollbars=yes');
        
        // Focus on the new window, if it was successfully opened
        if (newWindow) {
            newWindow.focus();
        }
    }
</script>
<script>
    $(document).ready(function () {
        // Auto-save internal notes on input change
        $('.internal-notes').on('input', function () {
            var notes = $(this).val(); // Get the value of the textarea
            var id = $(this).data('id'); // Get the record ID

            // Send AJAX request to save notes
            $.ajax({
                url: '/account/save-internal-notes', // Adjust the URL to your save route
                type: 'POST',
                data: {
                    id: id,
                    notes: notes,
                    _token: '{{ csrf_token() }}' // Include CSRF token for security
                },
                success: function (response) {
                    console.log('Notes saved successfully:', response);
                },
                error: function (xhr, status, error) {
                    console.error('Error saving notes:', error);
                }
            });
        });
    });


function markAsPaid(loadId) {
    const $perperworkdateInput = $(`.peperworkdate_${loadId}`).first();
    const perperworkdate = $perperworkdateInput.length ? $perperworkdateInput.val() : '';

    if (perperworkdate === '') {
        $('#mc-error-message').text('Please select the paperwork date').fadeIn();
        setTimeout(function() {
                    $('#mc-error-message').text('').fadeOut();
                }, 1000);
    } else {
        // Make an AJAX request to the Laravel route
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $.ajax({
            url: `/account/update-invoice-status/${loadId}`,
            method: 'POST',
            data: {
                status: 'Paid',
                perperworkdate: perperworkdate
            },
            success: function(response) {
                const $button = $(`#markAsPaidBtn_${loadId}`);
                if ($button.length) {
                    $button.text('Paid');
                    $button.removeClass().addClass('btn btn-success btn-sm');
                    $button.prop('disabled', true);
                }

                const $row = $(`#markAsPaidBtn_${loadId}`).closest('tr');
                if ($row.length) {
                    $row.remove(); // remove the row or update as needed
                }

                 $('#mc-success-message').text(response.message).fadeIn();

                 // Hide after 10 seconds
                setTimeout(function() {
                    $('#mc-success-message').text('').fadeOut();
                }, 1000);
                
            },
            error: function(xhr, status, error) {
                $('#mc-error-message').text('Failed to mark as Paid').fadeIn();
                // Hide after 10 seconds
                setTimeout(function() {
                    $('#mc-error-message').text('').fadeOut();
                }, 1000);
            }
        });
    }
}

</script>