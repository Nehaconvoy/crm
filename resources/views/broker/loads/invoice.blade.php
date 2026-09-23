@foreach($invoice as $loads)
                                @if($loads->invoice_status == 'Paid')
                                <tr>
                                    @if ($loads->load_status != 'Delivered')
                                    <td class="dynamic-data"><a style="color: rgb(40 122 7) !important;" href="#">{{ $loads->load_number }}</a></td>
                                    @else
                                    <td class="dynamic-data">{{ $loads->load_number }}</td>
                                    @endif
                                    <!-- <td>
                                        <a href="{{ route('load.editload', $loads->id) }}" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td> -->
                                    <td class="dynamic-data">{{ $loads->invoice_number }}</td>
                                    <td class="dynamic-data">{{ \Carbon\Carbon::parse($loads->invoice_date)->format('m-d-Y') }}</td>

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
                                            // Check if appointment exists and format it
                                            $appointmentDate = isset($lastAppointment['appointment']) ? \Carbon\Carbon::parse($lastAppointment['appointment'])->format('m-d-Y') : 'No appointments available';
                                        @endphp
                                        <td class="dynamic-data">{{ $appointmentDate }}</td>
                                    @else
                                        <td class="dynamic-data">No appointments available</td>
                                    @endif


                                    <td class="dynamic-data">{{ $loads->load_carrier }}</td>
                                    @php
                                        // Decode the JSON string to an associative array
                                        $shipper_location = json_decode($loads->load_shipper_location, true);

                                        // Debug to check the structure of the decoded JSON (for development)
                                        // dd($shipper_location); // Uncomment this to see the structure if you're unsure

                                        // Check if it's a valid array and has at least one element at index 0
                                        if (is_array($shipper_location) && array_key_exists(0, $shipper_location)) {
                                            $firstLocation = $shipper_location[0];
                                        } else {
                                            $firstLocation = null;
                                        }
                                    @endphp

                                    <td class="dynamic-data tooltip-container" onclick="copyToClipboard(this)">
                                        {{ \Illuminate\Support\Str::words($first_shipper_location['location'] ?? '', 3, '...') }}
                                        <span class="tooltip-text">{{ $first_shipper_location['location'] ?? '' }}</span>
                                    </td>

                                    <td class="dynamic-data tooltip-container" onclick="copyToClipboard(this)">
                                        {{ \Illuminate\Support\Str::words($last_consignee_location['location'] ?? '', 3, '...') }}
                                        <span class="tooltip-text">{{ $last_consignee_location['location'] ?? '' }}</span>
                                    </td>

                                    <td class="dynamic-data">{{ date('m-d-Y', strtotime($loads->load_actual_delivery_date)) }}</td>
                                    <td>Invoiced</td>
                                    @if(!empty($loads->shipper_load_final_rate))
                                        <td class="dynamic-data">{{ $loads->shipper_load_final_rate }}</td>
                                    @else
                                    <td class="dynamic-data"> - </td>
                                    @endif

                                    @if(!empty($loads->load_final_carrier_fee))
                                        <td class="dynamic-data">{{ $loads->load_final_carrier_fee }}</td>
                                    @else
                                        <td class="dynamic-data"> - </td>
                                    @endif
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
                                                $differenceInDays = $invoiceDate->diffInDays($currentDate);
                                            } elseif ($loads->invoice_status == 'Paid Record') {
                                                // If the invoice status is 'Paid Record', aging is complete
                                                $differenceInDays = 'Aging Complete';
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
                                               
                                                    <a href="#"><i
                                                            class="fa fa-upload dynamic-data" aria-hidden="true"
                                                            style="margin:0 10px; font-size: 20px;"></i>Upload</a>
                                               

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
                                @endif
                                @endforeach
