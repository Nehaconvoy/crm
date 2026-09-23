 @foreach($complete as $loads)
 @if($loads->load_status === 'Completed' && !in_array($loads->invoice_status, ['Paid', 'Paid Record']))
 <tr>

     <td class="dynamic-data">{{ $loads->load_number }}</td>
     <!-- <td>
                                        <a href="{{ route('load.editload', $loads->id) }}" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td> -->
     <td class="dynamic-data">{{ $loads->load_workorder }}</td>
     <td class="dynamic-data">{{ $loads->load_bill_to }}</td>
     <td class="dynamic-data">{{ $loads->created_at->format('m-d-Y') }}</td>
     @php
     $shipper_appointment_date = json_decode($loads->load_shipper_appointment, true);
     $shipperlastAppointment = '';

     if (is_array($shipper_appointment_date) && !empty($shipper_appointment_date)) {
     $shipperlastAppointment = end($shipper_appointment_date);
     }
     @endphp


     @if($shipperlastAppointment)

     <td class="dynamic-data">

         {{ isset($shipperlastAppointment['appointment'][0]) ? $shipperlastAppointment['appointment'][0] : '' }}</td>

     @else
     <td class="dynamic-data">No appointments available</td>
     @endif

     @php
     $consignee_appointment_date = json_decode($loads->load_consignee_appointment, true);
     @endphp
     @if($consignee_appointment_date)
     @php
     $lastAppointment = end($consignee_appointment_date);
     @endphp
     <td class="dynamic-data">
         {{ $lastAppointment['appointment'] ?? 'No appointments available' }}</td>
     @else
     <td class="dynamic-data">No appointments available</td>
     @endif


     <td class="dynamic-data">{{ $loads->load_carrier }}</td>

     @php
     $shipper_location = json_decode($loads->load_shipper_location, true);
     @endphp

     @php
     $firstLocation = (!empty($shipper_location) && isset($shipper_location[0])) ? $shipper_location[0] : null;
     @endphp

     <td class="dynamic-data tooltip-container" onclick="copyToClipboard(this)">
         {{ \Illuminate\Support\Str::words($firstLocation['location'] ?? '', 3, '...') }}
         <span class="tooltip-text">{{ $firstLocation['location'] ?? '' }}</span>
     </td>
     <td class="dynamic-data tooltip-container" onclick="copyToClipboard(this)">
         {{ \Illuminate\Support\Str::words($last_consignee_location['location'] ?? '', 3, '...') }}
         <span class="tooltip-text">{{ $last_consignee_location['location'] ?? '' }}</span>
     </td>

     <td class="dynamic-data">{{ date('m-d-Y', strtotime($loads->load_actual_delivery_date)) }}</td>
     @if($loads->load_status !== 'Delivered')
     <td class="dynamic-data">Completed</td>
     @endif
     @if(!empty($loads->shipper_load_final_rate))
     <td class="dynamic-data">{{ $loads->shipper_load_final_rate }}</td>
     @else
     <td class="dynamic-data"> - </td>
     @endif

<td>
    @if(!empty($shipperCharges))
        <table class="table table-sm table-bordered w-100 mb-0">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($shipperCharges as $shipperCharge)
                    <tr>
                        <td>{{ $shipperCharge['type'] ?? '-' }}</td>
                        <td>{{ $shipperCharge['amount'] ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        -
    @endif
</td>

     @if(!empty($loads->load_final_carrier_fee))
     <td class="dynamic-data">{{ $loads->load_final_carrier_fee }}</td>
     @else
     <td class="dynamic-data"> - </td>
     @endif
<td>
    @if(!empty($carrierCharges))
        <table class="table table-sm table-bordered w-100 mb-0">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($carrierCharges as $carrierCharge)
                    <tr>
                        <td>{{ $carrierCharge['type'] ?? '-' }}</td>
                        <td>{{ $carrierCharge['amount'] ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        -
    @endif
</td>

     @php
     // Convert values to float to ensure proper calculation
     $shipperRate = floatval($loads->shipper_load_final_rate);
     $carrierFee = floatval($loads->load_final_carrier_fee);
     $getMargin = $shipperRate - $carrierFee;

     // Calculate margin percentage
     $marginPercent = $shipperRate > 0 ? ($getMargin / $shipperRate) * 100 : 0; // Handle division by zero case
     @endphp

     <!-- Display margin amount with color -->
     <td class="dynamic-data" style="color: {{ $getMargin >= 0 ? 'green' : 'red' }};">
         ${{ number_format($getMargin, 2) }}
     </td>

     <!-- Display margin percentage with color -->
     <td class="dynamic-data" style="color: {{ $marginPercent >= 0 ? 'green' : 'red' }};">
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
         $differenceInDays = $invoiceDate->diffInDays($currentDate);
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
     @if($loads->load_status)

     <td class="dynamic-data" colspan="2">
         <a href="{{route('shipper.download.pdf', $loads->load_number)}}" target="_blank">
             <i class="fas fa-file-pdf dynamic-data" style="margin:0 10px; font-size: 20px;"></i>Shipper RC
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
         <a href="{{route('clone.load', $loads->load_number)}}" class="clone-link">
             <i class="fas fa-clone dynamic-data" style="margin:0 10px; font-size: 20px;"></i> Clone
         </a>

         <a href="#"><i class="fa fa-upload dynamic-data" aria-hidden="true"
                 style="margin:0 10px; font-size: 20px;"></i>Upload</a>

         </div>
         </div>

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


     @php
     $shipperCharges =
     json_decode($loads->shipper_load_other_charge, true);
     if (json_last_error() !== JSON_ERROR_NONE || !is_array($shipperCharges)) {
     $shipperCharges = [];
     }

     $carrierCharges =
     json_decode($loads->carrier_load_other_charge, true);
     if (json_last_error() !== JSON_ERROR_NONE ||
     !is_array($carrierCharges)) {
     // Handle JSON error or invalid data
     $carrierCharges = [];
     }
     @endphp


 </tr>
 @endif
 @endforeach