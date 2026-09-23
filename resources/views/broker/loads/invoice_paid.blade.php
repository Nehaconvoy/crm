 @foreach($invoice_paid as $loads)
                                @if($loads->invoice_status == 'Paid Record')
                                <tr>
                            
                                    <td class="dynamic-data" style="color:#000">{{ $loads->load_number }}</td>
                                    <td class="dynamic-data" >{{ $loads->invoice_number }}</td>
                                    <td class="dynamic-data">{{ \Carbon\Carbon::parse($loads->invoice_date)->format('m-d-Y') }}</td>

                                    <td class="dynamic-data">{{ $loads->load_workorder }}</td>
                                    <td class="dynamic-data">{{ $loads->load_bill_to }}</td>
                                    <td class="dynamic-data">{{ $loads->created_at->format('m-d-Y') }}</td>
                                    @php
                                        $shipper_appointment_date = json_decode($loads->load_shipper_appointment ?? '', true);
                                        $lastShipperAppointment = is_array($shipper_appointment_date) ? end($shipper_appointment_date) : null;
                                    @endphp

                                    <td class="dynamic-data">
                                        {{ isset($lastShipperAppointment['appointment']) ? \Carbon\Carbon::parse($lastShipperAppointment['appointment'])->format('m-d-Y') : 'No appointments available' }}
                                    </td>

                                    @php
                                        $consignee_appointment_date = json_decode($loads->load_consignee_appointment ?? '', true);
                                    @endphp

                                    @if($consignee_appointment_date)
                                        @php
                                            $lastAppointment = end($consignee_appointment_date);
                                            $appointmentDate = isset($lastAppointment['appointment']) ? \Carbon\Carbon::parse($lastAppointment['appointment'])->format('m-d-Y') : 'No appointments available';
                                            $last_consignee_location = is_array($consignee_appointment_date) ? $lastAppointment : [];
                                        @endphp
                                        <td class="dynamic-data">{{ $appointmentDate }}</td>
                                    @else
                                        <td class="dynamic-data">No appointments available</td>
                                    @endif


                                    <td class="dynamic-data">{{ $loads->load_carrier }}</td>
                                    @php
                                        $shipper_location = json_decode($loads->load_shipper_location, true);
                                    @endphp

                                    @php
                                        // Decode the JSON string to an associative array
                                        $shipper_location = json_decode($loads->load_shipper_location, true);

                                        // Check if the array is valid, not empty, and contains the first element
                                        $firstLocation = (is_array($shipper_location) && !empty($shipper_location) && isset($shipper_location[0])) ? $shipper_location[0] : null;
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
                                    <td>Paid</td>
                                   

                                    @php
                                        $shipperRate = floatval($loads->shipper_load_final_rate);
                                        $carrierFee = floatval($loads->load_final_carrier_fee);
                                        $getMargin = $shipperRate - $carrierFee;
                                    @endphp

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
                                    <td class="dynamic-data">
                                        @if($loads->invoice_status == 'Paid Record')
                                            Paid
                                        @else
                                            -
                                        @endif
                                    </td>
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

                                    @if($loads->load_status)
                                    <td class="dynamic-data">

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
