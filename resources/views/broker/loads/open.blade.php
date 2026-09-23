@foreach($open as $loads)
                            @if(in_array($loads->load_status, ['Open', 'On Route', 'Covered','Unloading']))
                            <tr>

                                @if ($loads->load_status != 'Delivered')
                                <td class="dynamic-data"><a style="color: rgb(40 122 7) !important;font-weight: 700;" href="#">{{ $loads->load_number }}</a></td>
                                @else
                                <td class="dynamic-data">{{ $loads->load_number }}</td>
                                @endif

                                <td>
                                    <a href="{{ route('load.editload', $loads->id) }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>

                                <td class="dynamic-data">{{ $loads->load_workorder }}</td>
                                <td class="dynamic-data">{{ $loads->customer_refrence_number }}</td>                            
                                <td class="dynamic-data">{{ $loads->load_bill_to }}</td>
                                <td class="dynamic-data">{{ $loads->created_at->format('m-d-Y') }}</td>
                                @php
                                $shipper_appointment =
                                json_decode($loads->load_shipper_appointment,true);
                                @endphp
                                <td class="dynamic-data">{{ isset($shipper_appointment[0]['appointment']) ? \Carbon\Carbon::parse($shipper_appointment[0]['appointment'])->format('m-d-Y') : '' }}
                                </td>
                                @php
                                $consignee_appointment =
                                json_decode($loads->load_consignee_appointment,true);
                                @endphp
                                <td class="dynamic-data">
                                    @php
                                        $appointmentDate = isset($consignee_appointment[0]['appointment']) ? $consignee_appointment[0]['appointment'] : null;
                                        $formattedDate = '-'; // Default if date is invalid or missing

                                        // Check if the date is not empty and has the expected format
                                        if ($appointmentDate && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/', $appointmentDate)) {
                                            try {
                                                $formattedDate = \Carbon\Carbon::parse($appointmentDate)->format('m-d-Y');
                                            } catch (\Exception $e) {
                                                // Keep default '-' if parsing fails
                                            }
                                        }
                                    @endphp
                                    {{ $formattedDate }}
                                </td>



                                <td class="dynamic-data">{{ $loads->load_carrier }}</td>
                

                                @php
                                    $shipper_location = json_decode($loads->load_shipper_location, true);
                                    $first_shipper_location = is_array($shipper_location) ? reset($shipper_location) : null;
                                @endphp

                                
                                <td class="dynamic-data tooltip-container"  onclick="copyToClipboard(this)">
                                {{ \Illuminate\Support\Str::words($first_shipper_location['location'] ?? '', 3, '...') }}
                                <span class="tooltip-text">{{ $first_shipper_location['location'] ?? '' }}</span>
                                </td>
                               
                                @php
                                $consignee_location = json_decode($loads->load_consignee_location, true);
								$last_consignee_location = is_array($consignee_location) ? end($consignee_location) : null;
                                @endphp

                                <td class="dynamic-data tooltip-container"  onclick="copyToClipboard(this)">
                                {{ \Illuminate\Support\Str::words($last_consignee_location['location'] ?? '', 3, '...') }}
                                <span class="tooltip-text">{{ $last_consignee_location['location'] ?? '' }}</span>
                                </td>
                                <td class="dynamic-data">
                                    @if($loads->cpr_check == 'Not Approved' || $loads->cpr_check == 'Not Verified' || $loads->cpr_check == 'Not Received')
                                        <select name="" id="" disabled>
                                            <option value="Open">Open</option>
                                        </select> 
                                        <div>
                                            <span style="color:red;font-size: 9px;">CPR Not Approved Kindly Wait</span>
                                        </div>   
                                    @else
                                        <select name="load_status"  class="form-control load_status load_status{{ $loads->id }}" data-load-id="{{ $loads->id }}" 
                                            @if($loads->load_status === 'Completed') disabled @endif >
                                            @php
                                                // Define the list of status options with associated colors
                                                $statusOptions = [
                                                    'Open' => '#74d1f0',
                                                    'Covered' => 'rgb(69 7 172 / 72%)',
                                                    'On Route' => 'green',
                                                    'Delivered' => '#7C2B1A',
                                                    'Unloading' => 'gray',
                                                    'Completed' => '#3597dc'
                                                ];
                                            @endphp
                                            @foreach($statusOptions as $status => $color)
                                                <option value="{{ $status }}"
                                                    @if($loads->load_status === $status) selected @endif>
                                                    {{ $status }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @endif
                                </td>
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


                                @if(in_array($loads->load_status, ['Open', 'On Route', 'Covered','Unloading']))
                                <td class="dynamic-data">{{ $loads->cpr_check }}</td>
                                @elseif($loads->load_status == "Delivered")
                                <td class="dynamic-data">Verified</td>
                                @endif
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
                                </td>
                                
                            </tr>
                            @endif
@endforeach
