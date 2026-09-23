@foreach($all_load as $loads)
@php
    $rowClass = '';
    if ($loads->load_status == 'Open') {
        $rowClass = 'row-open';
    } elseif ($loads->load_status == 'Delivered') {
        $rowClass = 'row-delivered';
    } elseif ($loads->load_status == 'Covered') {
        $rowClass = 'row-covered';
    } elseif ($loads->load_status == 'On Route') {
        $rowClass = 'row-onroute';
    } elseif ($loads->load_status == 'Unloading') {
        $rowClass = 'row-unloading';
    } elseif ($loads->load_status == 'Completed' && $loads->invoice_status == 'Paid') {
        $rowClass = 'row-completed-paid';
    } elseif ($loads->load_status == 'Completed' && $loads->invoice_status == 'Paid Record') {
        $rowClass = 'row-completed-paidrecord';
    } elseif ($loads->load_status == 'Completed') {
        $rowClass = 'row-completed';
    }  elseif ($loads->load_status == 'Cancelled') {
        $rowClass = 'row-cancelled';
    }
@endphp

<tr class="load-row {{ $rowClass }}" data-created-at="{{ $loads->created_at->format('Y-m-d') }}">



    <td class="dynamic-data">
        @if ($loads->load_status != 'Completed')
        <a style="font-weight: 700;color: #fff;" href="#">
            {{ $loads->load_number }}
        </a>
        @else
        {{ $loads->load_number }}
        @endif
    </td>
    <td id="edit_btn">
    @if ($loads->load_status != 'Completed')
        <a href="{{ route('load.editload', encrypt($loads->id)) }}" class="btn btn-primary btn-sm">
            <i class="fas fa-edit"></i>
        </a>
    @endif
</td>



    <td class="dynamic-data">{{ $loads->user?->name }}</td>
    <td class="dynamic-data">{{ $loads->load_workorder }}</td>
    <td class="dynamic-data">{{ $loads->customer_refrence_number }}</td>
    <td class="dynamic-data">{{ $loads->load_bill_to }}</td>
    <td class="dynamic-data">{{ $loads->created_at->format('m-d-Y') }}</td>
    @php
    $shipper_appointment =
    json_decode($loads->load_shipper_appointment,true);
    @endphp
    <td class="dynamic-data">
        {{ isset($shipper_appointment[0]['appointment']) ? \Carbon\Carbon::parse($shipper_appointment[0]['appointment'])->format('m-d-Y') : '' }}
    </td>
    @php
    $consignee_appointment =
    json_decode($loads->load_consignee_appointment,true);
    @endphp
    <td class="dynamic-data">
        @if ($consignee_appointment && isset($consignee_appointment[0]['appointment']))
        @php
        $appointment = $consignee_appointment[0]['appointment'];
        // Attempt to parse only if it's in a recognizable date format
        try {
        $formattedDate = \Carbon\Carbon::parse($appointment)->format('m-d-Y');
        } catch (\Exception $e) {
        $formattedDate = '-'; // Set to '-' if parsing fails
        }
        @endphp
        {{ $formattedDate }}
        @else
        -
        @endif
    </td>


    <td class="dynamic-data">
        @if($loads->load_actual_delivery_date)
        {{ \Carbon\Carbon::parse($loads->load_actual_delivery_date)->format('m-d-Y') }}
        @else
        -
        @endif

    </td>
    <td class="dynamic-data">{{ $loads->load_carrier }}</td>

    @php
    $shipper_location = json_decode($loads->load_shipperr, true);
    $first_shipper_name = is_array($shipper_location) ? reset($shipper_location) : null;
    @endphp

    <td class="dynamic-data tooltip-container" onclick="copyToClipboard(this)">
        {{ \Illuminate\Support\Str::words($first_shipper_name['name'] ?? '', 3, '...') }}
    </td>


    @php
    $shipper_location = json_decode($loads->load_shipper_location, true);
    $first_shipper_location = is_array($shipper_location) ? reset($shipper_location) : null;
    @endphp

    <td class="dynamic-data tooltip-container" onclick="copyToClipboard(this)">
        {{ \Illuminate\Support\Str::words($first_shipper_location['location'] ?? '', 3, '...') }}
        <span class="tooltip-text">{{ $first_shipper_location['location'] ?? '' }}</span>
    </td>

@php
    $consignee_name = json_decode($loads->load_consignee, true);
    $first_consignee_name = is_array($consignee_name) ? reset($consignee_name) : null;
@endphp

<td class="dynamic-data tooltip-container" onclick="copyToClipboard(this)">
    {{ \Illuminate\Support\Str::words($first_consignee_name['name'] ?? '', 3, '...') }}
</td>


        @php
    $consignee_location = json_decode($loads->load_consignee_location, true);
    $last_consignee_location = is_array($consignee_location) ? end($consignee_location) : null;
    @endphp


    <td class="dynamic-data tooltip-container" onclick="copyToClipboard(this)">
        {{ \Illuminate\Support\Str::words($last_consignee_location['location'] ?? '', 3, '...') }}
        <span class="tooltip-text">{{ $last_consignee_location['location'] ?? '' }}</span>
    </td>


<td class="dynamic-data">
    @if(
        $loads->cpr_check == 'Not Approved' ||
        $loads->cpr_check == 'Not Verified' ||
        $loads->cpr_check == 'Not Received'
    )
        <select disabled>
            <option value="Open">Open</option>
        </select>
        <div>
            <span style="color:red;font-size:9px;">
                CPR Not Approved Kindly Wait
            </span>
        </div>
    @else
        @php
            $statusOptions = [
                'Cancelled'  => '#ff4d4f',
                'Open'       => '#74d1f0',
                'Covered'    => 'rgb(69 7 172 / 72%)',
                'On Route'   => 'green',
                'Delivered'  => '#7C2B1A',
                'Unloading'  => 'gray',
                'Completed'  => '#3597dc',
            ];

            // Disable whole dropdown if already Completed or Cancelled
            $disableDropdown = in_array($loads->load_status, ['Completed','Cancelled']);
        @endphp

        <select
            name="load_status"
            class="form-control load_status"
            data-load-id="{{ $loads->id }}"
            @if($disableDropdown) disabled @endif
        >
            @foreach($statusOptions as $status => $color)

                @if($status === 'Completed')
                    <option value="Completed" 
                        @if($loads->load_status === 'Completed') selected @endif
                        @if($loads->load_status !== 'Delivered') disabled title="Please mark Delivered first" 
                        @endif
                    >
                        Completed
                    </option>
                @else
                    <option
                        value="{{ $status }}"
                        @if($loads->load_status === $status) selected @endif
                    >
                        {{ $status }}
                    </option>
                @endif

            @endforeach
        </select>
    @endif
</td>







@php
    $finalRate = (float) ($loads->load_shipper_rate ?? 0);

    $otherCharges = json_decode($loads->shipper_load_other_charge, true);

    // Ensure it's always an array
    if (!is_array($otherCharges)) {
        $otherCharges = [];
    }

    $totalOtherCharges = 0;
@endphp

<td class="dynamic-data">
    @php
        $baseRate = (float) $finalRate;
        $fscPercentage = (float) ($loads->load_fsc_rate ?? 0);
        $fscAmount = ($baseRate * $fscPercentage) / 100;

        $totalOtherCharges = 0;
    @endphp

    <div>
        <strong>Customer Base Rate:</strong>
        ${{ number_format($baseRate, 2) }}
    </div>

    @if($fscPercentage > 0)
        <div>
            F.S.C ({{ $fscPercentage }}%):
            <strong>${{ number_format($fscAmount, 2) }}</strong>
        </div>
    @endif

    @foreach($otherCharges as $charge)
        @php
            $amount = (float) ($charge['amount'] ?? 0);
            $totalOtherCharges += $amount;
        @endphp

        <div>
            {{ $charge['type'] ?? '-' }} :
            <strong>${{ number_format($amount, 2) }}</strong>
        </div>
    @endforeach

    <hr style="margin:5px 0;">

    <div>
        <strong>Final Customer Rate:</strong>
        ${{ number_format($baseRate + $fscAmount + $totalOtherCharges, 2) }}
    </div>
</td>

@php
    $carrierFee = (float) ($loads->load_carrier_fee ?? 0);
    $carrierFscPercentage = (float) ($loads->load_billing_fsc_rate ?? 0);
    $carrierFscAmount = ($carrierFee * $carrierFscPercentage) / 100;

    $carrierCharges = json_decode($loads->carrier_load_other_charge, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($carrierCharges)) {
        $carrierCharges = [];
    }

    $totalOtherCharges = 0;
@endphp

<td class="dynamic-data">
    <div>
        <strong>Carrier Base Rate:</strong>
        ${{ number_format($carrierFee, 2) }}
    </div>

    @if($carrierFscPercentage > 0)
        <div style="font-size:12px;color:#555;">
            F.S.C ({{ $carrierFscPercentage }}%) :
            <strong>${{ number_format($carrierFscAmount, 2) }}</strong>
        </div>
    @endif

    @foreach($carrierCharges as $charge)
        @php
            $amount = (float) ($charge['amount'] ?? 0);
            $totalOtherCharges += $amount;
        @endphp

        <div style="font-size:12px;color:#555;">
            {{ $charge['type'] ?? '-' }} :
            <strong>${{ number_format($amount, 2) }}</strong>
        </div>
    @endforeach

    <hr style="margin:4px 0;">

    <div>
        <strong>Total: ${{ number_format($carrierFee + $carrierFscAmount + $totalOtherCharges, 2) }}</strong>
    </div>
</td>

    @php
    $shipperRate = floatval($loads->shipper_load_final_rate);
    $carrierFee = floatval($loads->load_final_carrier_fee);
    $getMargin = $shipperRate - $carrierFee;
    @endphp
    <td class="dynamic-data">
        ${{ number_format($getMargin, 2) }}
    </td>
    @php
    $shipperRate = floatval($loads->shipper_load_final_rate);
    $carrierFee = floatval($loads->load_final_carrier_fee);
    $getMargin = $shipperRate - $carrierFee;

    // Calculate margin percentage
    if ($shipperRate > 0) {
    $marginPercent = ($getMargin / $shipperRate) * 100;
    } else {
    $marginPercent = 0; // Handle division by zero case
    }
    @endphp

    <td class="dynamic-data">
        {{ number_format($marginPercent, 2) }}%
    </td>





    <td class="dynamic-data">
        @php
        // Initialize the differenceInDays variable
        $differenceInDays = null;

        // Check if the invoice date is set
        if (isset($loads->invoice_date)) {
        // Parse the invoice date
        $invoiceDate = \Carbon\Carbon::parse($loads->invoice_date);
        $currentDate = \Carbon\Carbon::now();

        // Calculate the difference in days based on the invoice status
        if ($loads->invoice_status == 'Paid') {
        // Calculate days since the invoice was paid
        $differenceInDays = round($invoiceDate->diffInDays($currentDate));
        } elseif ($loads->invoice_status == 'Paid Record') {
        // If the invoice status is 'Paid Record', aging is complete
        $differenceInDays = 'Paid';
        }
        }

        // Check for empty or null invoice status
        $isInvoiceStatusEmpty = empty($loads->invoice_status);
        @endphp

        @if($isInvoiceStatusEmpty)
        <span>-</span>
        @elseif($differenceInDays !== null)
        @if($loads->invoice_status == 'Paid')
        <span style="color:red">{{ $differenceInDays }} days</span>
        @elseif($loads->invoice_status == 'Paid Record')
        <span style="color:green">{{ $differenceInDays }}</span>
        @endif
        @else
        <span>-</span>
        @endif
    </td>



    @if($loads->load_status == "Open" && $loads->cpr_check != 'Verified')
    <td class="dynamic-data">Not Verified</td>
    @else
    <td class="dynamic-data">{{ $loads->cpr_check }}</td>
    @endif
        <td class="dynamic-data">
        @if($loads->carrier_mark_as_paid == 'Paid')
            <span style="color:green">Paid</span>
        @else
            <span style="color:red"> Not Paid</span>
        @endif
    </td>

    @if($loads->load_status)
    <td class="dynamic-data" colspan="2">
               
                    <a href="{{ route('clone.load', encrypt($loads->load_number)) }}" target="_blank">
                        <i class="fas fa-clone dynamic-data"
                        style="margin:0 10px; font-size:20px;"></i>
                        Clone
                    </a>
@if(
    ($loads->load_final_carrier_fee == 0 || $loads->load_final_carrier_fee == null) ||
    !in_array($loads->cpr_check, ['Verified', 'Approved as Exception'])
)

    <a href="javascript:void(0);"
       style="color:#0c7ce6; cursor:not-allowed;"
       title="{{
           ($loads->load_final_carrier_fee == 0 || $loads->load_final_carrier_fee == null)
               ? 'Your Carrier Rate is 0'
               : 'CPR is not Verified'
       }}">

        <i class="fas fa-file-pdf dynamic-data"
           style="margin:0 10px; font-size:20px;"></i>

        Carrier RC

    </a>

@else

    <a href="{{ route('rc.download.pdf', $loads->load_number) }}"
       target="_blank">

        <i class="fas fa-file-pdf dynamic-data"
           style="margin:0 10px; font-size:20px;"></i>

        Carrier RC

    </a>

@endif
                    @if($loads->shipper_load_final_rate == 0 || $loads->shipper_load_final_rate == null)
                     <a href="javascript:void(0);" style="color: #0c7ce6; cursor:not-allowed" title="Your Customer Rate is 0">
                        <i class="fas fa-file-pdf dynamic-data" style="margin:0 10px; font-size: 20px;"></i> Shipper RC
                    </a>
                    @else
                    <a href="{{route('shipper.download.pdf', $loads->load_number)}}" target="_blank" class="clone-link">
                        <i class="fas fa-file-pdf dynamic-data" style="margin:0 10px; font-size: 20px;"></i> Shipper RC
                    </a>
                    @endif
                @if ($loads->load_status != 'Open')
              
                    <a href="javascript:void(0);">
                        <i class="fa fa-upload dynamic-data" aria-hidden="true"
                            style="margin:0 10px; font-size: 20px;"></i>Upload
                    </a>
               

                @endif
              
                    <a class="view-files"  data-bs-toggle="modal" style="color: #0c7ce6; cursor:pointer" data-bs-target="#view-documents-{{ $loads->id }}">
                        <i class="fa fa-file" style="margin:0 10px; font-size: 20px;"></i> AP Docs
                    </a>

    </td>
    @elseif($loads->invoice_status == 'Paid Record')
    <td class="dynamic-data"><span>Paid</span></td>
    @elseif($loads->invoice_status == 'Paid')
    <td class="dynamic-data"><span> Invoiced</span></td>
    @elseif($loads->invoice_status == 'Completed')
    <td class="dynamic-data"><span> Completed </span></td>
    @else
    <td class="dynamic-data"><span>Delivered</span></td>
    @endif
   

</tr>

<div class="modal fade" id="view-documents-{{ $loads->id }}" tabindex="-1" aria-labelledby="view-documents" aria-hidden="true">
        <div class="modal-dialog modal-lg" style="max-width: 800px;">
                <div class="modal-content">
                 
                    <div class="modal-header" style="padding-left: 14px;">
                    <h4 class="modal-title">View Documents</h4>
                    <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                    </div>


                    <div class="modal-body">
           

                    @php
					  
                        $alladoc = $loads->carrierDoc;
                        $docs = json_decode($alladoc, true);
                        
                    @endphp

                    @if(empty($docs))
                        <p>No documents found.</p>
                    @else

                        <div class="accordion" id="accordionExample">
                            @foreach($docs as $key => $all)
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingOne">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{$key}}" aria-expanded="false" aria-controls="collapse{{$key}}">
                                    view document #{{$key + 1}}
                                    </button>
                                </h2>
                                <div id="collapse{{$key}}" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                                    <div class="accordion-body">
                                       @php
                                            $file = $all; // Or $all['file'] depending on your data structure
                                            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                        @endphp

                                        <div style="margin-bottom: 20px;">
                                            @if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']))
                                                
                                                <img src="{{ asset('public/'.$file) }}" alt="Image" style="max-width: 500px;">
                                            @elseif($extension === 'pdf')
                                                
                                                <embed src="{{ asset('public/'.$file) }}" type="application/pdf" width="600" height="400">
                                            @elseif(in_array($extension, ['doc', 'docx']))
                                                <iframe src="https://docs.google.com/gview?url={{ urlencode(asset('public/'.$file)) }}&embedded=true" 
                                                        style="width:600px; height:500px;" frameborder="0"></iframe>
                                                
                                                <br><a href="{{ asset($file) }}" target="_blank">Download Word Document</a>
                                            @else
                                                
                                                <p>Unsupported file type.</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @endif

                    </div>
                </div>
        </div>
    </div>
@endforeach

