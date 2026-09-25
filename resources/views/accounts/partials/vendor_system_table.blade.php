@foreach($vendormanagement as $i => $vendor)
@php
$rowClass = '';
if ($vendor->load_status == 'Open') {
$rowClass = 'row-open';
} elseif ($vendor->load_status == 'Delivered') {
$rowClass = 'row-delivered';
} elseif ($vendor->load_status == 'Covered') {
$rowClass = 'row-covered';
} elseif ($vendor->load_status == 'On Route') {
$rowClass = 'row-onroute';
} elseif ($vendor->load_status == 'Unloading') {
$rowClass = 'row-unloading';
} elseif ($vendor->load_status == 'Completed' && $vendor->invoice_status == 'Paid') {
$rowClass = 'row-completed-paid';
} elseif ($vendor->load_status == 'Completed' && $vendor->invoice_status == 'Paid Record') {
$rowClass = 'row-completed-paidrecord';
} elseif ($vendor->load_status == 'Completed') {
$rowClass = 'row-completed';
}
@endphp
<tr class="load-row {{ $rowClass }}" data-created-at="{{ $vendor->created_at->format('Y-m-d') }}">
    <td class="dynamic-data">
        {{ $i + 1 }}
        <span style="color:#000">
            ( @if($vendor->invoice_status == 'Paid')
            Invoiced
            @elseif($vendor->invoice_status == 'Paid Record')
            Paid
            @elseif(!empty($vendor->load_status))
            @if($vendor->load_status == 'Completed')
            Completed
            @elseif($vendor->load_status == 'Open')
            Open
            @elseif($vendor->load_status == 'Unloading')
            Unloading
            @else
            {{ $vendor->load_status }}
            @endif
            @else
            N/A
            @endif
            )
        </span>
    </td>

    <td class="dynamic-data" id="load_number">
        <a style="color:#000 !important;cursor: pointer;font-weight:bold;"
            onclick="openUploadWindow('{{route('load.edit', $vendor->load_number)}}')">
            {{ $vendor->load_number }} <span style="color:#000">(@if($vendor->user) {{ $vendor->user->name }}
                @endif)</span>
        </a>

    </td>

    <td class="dynamic-data">{{ $vendor->load_workorder }}</td>
    <td class="dynamic-data">{{ $vendor->load_carrier }}</td>
    <td class="dynamic-data">
    <input
        type="date"
        class="form-control carrier_invoice_date"
        name="carrier_invoice_date"
        data-id="{{ $vendor->id }}"
        value="{{ $vendor->carrier_invoice_date ? \Carbon\Carbon::parse($vendor->carrier_invoice_date)->format('Y-m-d') : '' }}"    >
    </td>
    <td class="dynamic-data">
        <span class="formatted_date due_date-{{ $vendor->id }}">
            @if($vendor->load_carrier_due_date)
                {{ \Carbon\Carbon::parse($vendor->load_carrier_due_date)->format('m/d/Y') }}
            @elseif($vendor->carrier_invoice_date)
                {{ \Carbon\Carbon::parse($vendor->carrier_invoice_date)->addDays(25)->format('m/d/Y') }}
            @endif
        </span>
    </td>
        <td class="dynamic-data">
        <select style="width: 100%;" class="form-control vendor-select ready_to_pay" name="ready_to_pay"
            data-id="{{ $vendor->id }}">
            <option value="">Please Select Ready to Pay</option>
            <option value="Yes" @if($vendor->ready_to_pay == 'Yes') selected @endif style="background-color: green;
                color: white;">Yes</option>
            <option @if($vendor->ready_to_pay == 'No') selected @endif value="No" style="background-color: red; color:
                white;">No</option>
            <option value="Hold/Dispute" @if($vendor->ready_to_pay == 'Hold/Dispute') selected @endif style="background-color: yellow;
                color: black;">Hold/Dispute</option>
        </select>
    </td>
        <td>
    <select class="form-control vendor-select load_priority"
            name="load_priority"
            data-id="{{ $vendor->id }}">

        <option value="">Please Select Invoice Through</option>

        <option value="DIRECT"
            {{ $vendor->invoice_through == 'DIRECT' ? 'selected' : '' }}>
            DIRECT
        </option>

        <option value="OTR"
            {{ $vendor->invoice_through == 'OTR' ? 'selected' : '' }}>
            OTR
        </option>

        <option value="Buyout"
            {{ $vendor->invoice_through == 'Buyout' ? 'selected' : '' }}>
            Buyout
        </option>

    </select>
</td>
    <td class="dynamic-data">
        <select
            class="form-control vendor-select carrier_documents"
            name="carrier_documents"
            data-id="{{ $vendor->id }}"
            data-saved-value="{{ $vendor->carrier_documents ?? '' }}"
        >
            <option value="">Select Document</option>

            <option value="NOA"
                {{ $vendor->carrier_documents == 'NOA' ? 'selected' : '' }}>
                NOA
            </option>

            <option value="Void Check"
                {{ $vendor->carrier_documents == 'Void Check' ? 'selected' : '' }}>
                Void Check
            </option>

            <option value="Pay by Check"
                {{ $vendor->carrier_documents == 'Pay by Check' ? 'selected' : '' }}>
                Pay by Check
            </option>
        </select>
    </td>
    <td class="dynamic-data">
        <select name="quick_pay" class="form-control vendor-select quick_pay"
            data-id="{{ $vendor->id }}">
            <option value="">Please Select Quick Pay</option>
            <option value="1%" @if($vendor->quick_pay == '1%') selected @endif>1%</option>
            <option value="2%" @if($vendor->quick_pay == '2%') selected @endif>2%</option>
            <option value="3%" @if($vendor->quick_pay == '3%') selected @endif>3%</option>
            <option value="4%" @if($vendor->quick_pay == '4%') selected @endif>4%</option>
            <option value="5%" @if($vendor->quick_pay == '5%') selected @endif>5%</option>
            <option value="6%" @if($vendor->quick_pay == '6%') selected @endif>6%</option>
        </select>
    </td>

    <td class="dynamic-data">
        <input type="file" class="carrierDoc" name="carrierDoc[]" multiple data-id="{{ $vendor->id }}">
    </td>

    <!-- Blade Table -->
    @if($vendor->carrierDoc)
    <td class="text-center dynamic-data">
        <a href="javascript:void(0)" class="view-files" data-bs-toggle="modal"
            data-bs-target="#filesModal{{ $vendor->id }}">
            <i class="fa fa-eye"></i>
        </a>
    </td>
    <!-- Move this outside of the table -->
    <div class="modal fade" id="filesModal{{ $vendor->id }}" tabindex="-1"
        aria-labelledby="filesModalLabel{{ $vendor->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg" style="max-width: 800px;">

            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header" style="padding-left: 14px;">
                    <h4 class="modal-title">View Documents</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @php
                    $alladoc = $vendor->carrierDoc;
                    $docs = json_decode($alladoc, true);
                    @endphp

                    @if(empty($docs))
                    <p>No documents found.</p>
                    @else
                    <div class="accordion" id="accordionExample">
                        @foreach($docs as $key => $all)
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading{{$key}}" style="display: flex;">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapse{{$key}}" aria-expanded="false"
                                    aria-controls="collapse{{$key}}">
                                    View document #{{$key + 1}} ({{ basename($all) }})
                                </button>
                                <button type="button" class="remove-file"
                                    data-load-id="{{ $vendor->load_id ?? $vendor->id }}"
                                    data-file-name="{{ basename($all) }}" onclick="deleteCarrierFile(this)"
                                    style="background: unset; border: none;">
                                    <i class="fa fa-trash" style="margin-top: 15px; color: red;"></i>
                                </button>

                            </h2>
                            <div id="collapse{{$key}}" class="accordion-collapse collapse"
                                aria-labelledby="heading{{$key}}" data-bs-parent="#accordionExample">
                                <div class="accordion-body">
                                    @php
                                    $file = $all;
                                    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                    @endphp

                                    <div style="margin-bottom: 20px;">
                                        @if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']))
                                        <!-- Image Preview -->
                                        <a href="{{ asset($file) }}" target="_blank">
                                            <img src="{{ asset($file) }}" alt="Image"
                                                style="max-width: 500px;">
                                        </a>
                                        @elseif($extension === 'pdf')
                                        <!-- PDF Preview -->
                                        <a href="{{ asset($file) }}" target="_blank">
                                            <embed src="{{ asset($file) }}" type="application/pdf" width="600"
                                                height="400" />
                                        </a>
                                        @elseif(in_array($extension, ['doc', 'docx']))
                                        <!-- Word Preview with Google Docs Viewer -->
                                        <iframe
                                            src="https://docs.google.com/gview?url={{ urlencode(asset($file)) }}&embedded=true"
                                            style="width:600px; height:500px;" frameborder="0"></iframe>
                                        <br>
                                        <a href="{{ asset($file) }}" target="_blank">Download Word
                                            Document</a>
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
    @else
    <td class="dynamic-data">
        <p style="font-size:7px;color:red">No files uploaded</p>
    </td>
    @endif

    <td class="dynamic-data">
        <select class="form-control vendor-select payment_method" name="payment_method"
            data-id="{{ $vendor->id }}">
            <option value="">Please Select Payment Method</option>
            <option value="ACH" @if($vendor->payment_method == 'ACH') selected @endif>ACH</option>
            <option value="Quick Pay" @if($vendor->payment_method == 'Quick Pay') selected @endif>Quick Pay</option>
            <option value="OTR" @if($vendor->payment_method == 'OTR') selected @endif>OTR</option>
            <option value="Zelle" @if($vendor->payment_method == 'Zelle') selected @endif>Zelle</option>
            <option value="Check" @if($vendor->payment_method == 'Check') selected @endif>Check</option>
            <option value="Wire" @if($vendor->payment_method == 'Wire') selected @endif>Wire</option>
            <option value="Buyout" @if($vendor->payment_method == 'Buyout') selected @endif>Buyout</option>
        </select>
    </td>

    <td class="dynamic-data">
        @if ($vendor->carrier_mark_as_paid != 'Paid')
        <input type="checkbox" class="carrier_mark_as_paid" data-id="{{ $vendor->id }}">
        @else
        Paid
        @endif
    </td>
    <td>{{ $vendor->load_carrier_due_date_on }}</td>


    <td class="dynamic-data">
        @if(!empty($vendor->invoice_date) && $vendor->invoice_date !== '0000-00-00')
        {{ \Carbon\Carbon::parse($vendor->invoice_date)->format('m/d/Y') }}
        @elseif(!empty($vendor->invoice_status_date) && $vendor->invoice_status_date !== '0000-00-00')
        {{ \Carbon\Carbon::parse($vendor->invoice_status_date)->format('m/d/Y') }}
        @else
        -
        @endif
    </td>
    <!-- <td>
        @if($vendor->public_file)

        @php
        // Define the path to the folder on the server
        $folderPath = storage_path('app/public/vendor_files/' . $vendor->id); // Adjust this to your folder path

        // Check if the folder exists
        if (file_exists($folderPath)) {
        // Get the folder creation time (metadata change time)
        $folderCreationTime = filectime($folderPath);

        // Format the folder creation time
        $folderCreationDate = date('Y-m-d H:i:s', $folderCreationTime); // Format the date
        } else {
        $folderCreationDate = 'Folder does not exist';
        }
        @endphp


        <span data-bs-toggle="modal" style="color: #0c7ce6; cursor:pointer"
            data-bs-target="#view-documents-{{ $vendor->id }}"> <i class="fa fa-eye"
                style="font-size: 15px;color: #000; margin-right: 6px;"></i></span>

        <p style="margin: 7px 0;">Folder Created At: {{ $folderCreationDate }}</p>

        @else

        <a style="padding: 0; font-size: 14px; background-color: unset; border: unset;"
            class="btn btn-primary text-white" href="javascript:void(0);" style="text-decoration:unset">
            <i class="fa fa-eye-slash" style="margin-right: 10px;color: red;"></i>
        </a>

        @endif
    </td> -->

    <td class="dynamic-data"><a href="{{ route('accounts.view_loads_detail', $vendor->id) }}"
            class="btn btn-primary btn-sm"> <i class="fas fa-eye"></i></a></td>
    <td>
        <textarea class="vendor-note" rows="4" cols="50" data-id="{{ $vendor->id }}"
            placeholder="Type note & click outside to save..." rows="2"></textarea>

        <button class="btn btn-sm btn-outline-primary mt-2 view-notes" data-id="{{ $vendor->id }}">
            View Notes
        </button>
    </td>
    <div class="modal fade" id="notesModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Vendor Notes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body" id="notesContainer">
                    <!-- Notes will load here -->
                </div>

            </div>
        </div>
    </div>
</tr>
<!-- File View Modal -->





<div class="modal fade" id="view-documents-{{ $vendor->id }}" tabindex="-1" aria-labelledby="view-documents"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" style="max-width: 800px;">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header" style="padding-left: 14px;">
                <h4 class="modal-title">View Documents</h4>
                <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Existing Credit Logs -->

                @php
                $alladoc = $vendor->public_file;
                $docs = json_decode($alladoc, true);
                @endphp

                @if(empty($docs))
                <p>No documents found.</p>
                @else
                @if(!empty($docs['carrer_rate_cnfrm_doc']))
                <div class="accordion" id="accordionExample">
                    @foreach($docs['carrer_rate_cnfrm_doc'] as $key => $all)

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                data-bs-target="#carrer_rate{{$key}}" aria-expanded="false"
                                aria-controls="collapse{{$key}}">
                                Carrier Rate document #{{$key + 1}} ({{basename($all)}})
                            </button>
                        </h2>
                        <div id="carrer_rate{{$key}}" class="accordion-collapse collapse" aria-labelledby="headingOne"
                            data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                @php
                                $file = 'public/'.$all; // Or $all['file'] depending on your data structure
                                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                @endphp

                                <div style="margin-bottom: 20px;">
                                    @if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']))
                                    <!-- Image Preview -->
                                    <a href="{{ asset($file) }}" target="_blank"><img src="{{ asset($file) }}"
                                            alt="Image" style="max-width: 500px;"></a>
                                    @elseif($extension === 'pdf')
                                    <!-- PDF Preview -->
                                    <a href="{{ asset($file) }}" target="_blank"><embed src="{{ asset($file) }}"
                                            type="application/pdf" width="600" height="400"></a>
                                    @elseif(in_array($extension, ['doc', 'docx']))
                                    <!-- Word Preview with Google Docs Viewer -->
                                    <iframe
                                        src="https://docs.google.com/gview?url={{ urlencode(asset($file)) }}&embedded=true"
                                        style="width:600px; height:500px;" frameborder="0"></iframe>
                                    <!-- Optional download link -->
                                    <br><a href="{{ asset($file) }}" target="_blank">Download Word Document</a>
                                    @else
                                    <!-- Unsupported file -->
                                    <p>Unsupported file type.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                @if(!empty($docs['pod_doc']))
                <div class="accordion" id="accordionExample">
                    @foreach($docs['pod_doc'] as $key => $all)

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                data-bs-target="#pod_doc{{$key}}" aria-expanded="false"
                                aria-controls="collapse{{$key}}">
                                POD document #{{$key + 1}} ({{basename($all)}})
                            </button>
                        </h2>
                        <div id="pod_doc{{$key}}" class="accordion-collapse collapse" aria-labelledby="headingOne"
                            data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                @php
                                $file = 'public/'.$all; // Or $all['file'] depending on your data structure
                                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                @endphp

                                <div style="margin-bottom: 20px;">
                                    @if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']))
                                    <!-- Image Preview -->
                                    <a href="{{ asset($file) }}" target="_blank"><img src="{{ asset($file) }}"
                                            alt="Image" style="max-width: 500px;"></a>
                                    @elseif($extension === 'pdf')
                                    <!-- PDF Preview -->
                                    <a href="{{ asset($file) }}" target="_blank"><embed src="{{ asset($file) }}"
                                            type="application/pdf" width="600" height="400"></a>
                                    @elseif(in_array($extension, ['doc', 'docx']))
                                    <!-- Word Preview with Google Docs Viewer -->
                                    <iframe
                                        src="https://docs.google.com/gview?url={{ urlencode(asset($file)) }}&embedded=true"
                                        style="width:600px; height:500px;" frameborder="0"></iframe>
                                    <!-- Optional download link -->
                                    <br><a href="{{ asset($file) }}" target="_blank">Download Word Document</a>
                                    @else
                                    <!-- Unsupported file -->
                                    <p>Unsupported file type.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                @if(!empty($docs['shipper_rate_approval_doc']))
                <div class="accordion" id="accordionExample">
                    @foreach($docs['shipper_rate_approval_doc'] as $key => $all)

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                data-bs-target="#shipper_rate{{$key}}" aria-expanded="false"
                                aria-controls="collapse{{$key}}">
                                Shipper Rate document #{{$key + 1}} ({{basename($all)}})
                            </button>
                        </h2>
                        <div id="shipper_rate{{$key}}" class="accordion-collapse collapse" aria-labelledby="headingOne"
                            data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                @php
                                $file = 'public/'.$all; // Or $all['file'] depending on your data structure
                                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                @endphp

                                <div style="margin-bottom: 20px;">
                                    @if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']))
                                    <!-- Image Preview -->
                                    <a href="{{ asset($file) }}" target="_blank"><img src="{{ asset($file) }}"
                                            alt="Image" style="max-width: 500px;"></a>
                                    @elseif($extension === 'pdf')
                                    <!-- PDF Preview -->
                                    <a href="{{ asset($file) }}" target="_blank"><embed src="{{ asset($file) }}"
                                            type="application/pdf" width="600" height="400"></a>
                                    @elseif(in_array($extension, ['doc', 'docx']))
                                    <!-- Word Preview with Google Docs Viewer -->
                                    <iframe
                                        src="https://docs.google.com/gview?url={{ urlencode(asset($file)) }}&embedded=true"
                                        style="width:600px; height:500px;" frameborder="0"></iframe>
                                    <!-- Optional download link -->
                                    <br><a href="{{ asset($file) }}" target="_blank">Download Word Document</a>
                                    @else
                                    <!-- Unsupported file -->
                                    <p>Unsupported file type.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif


                @if(!empty($docs['carrier_invoice_doc']))
                <div class="accordion" id="accordionExample">
                    @foreach($docs['carrier_invoice_doc'] as $key => $all)

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                data-bs-target="#carrier_invoice{{$key}}" aria-expanded="false"
                                aria-controls="collapse{{$key}}">
                                Carrier Invoice document #{{$key + 1}} ({{basename($all)}})
                            </button>
                        </h2>
                        <div id="carrier_invoice{{$key}}" class="accordion-collapse collapse"
                            aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                @php
                                $file = 'public/'.$all; // Or $all['file'] depending on your data structure
                                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                @endphp

                                <div style="margin-bottom: 20px;">
                                    @if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']))
                                    <!-- Image Preview -->
                                    <a href="{{ asset($file) }}" target="_blank"><img src="{{ asset($file) }}"
                                            alt="Image" style="max-width: 500px;"></a>
                                    @elseif($extension === 'pdf')
                                    <!-- PDF Preview -->
                                    <a href="{{ asset($file) }}" target="_blank"><embed src="{{ asset($file) }}"
                                            type="application/pdf" width="600" height="400"></a>
                                    @elseif(in_array($extension, ['doc', 'docx']))
                                    <!-- Word Preview with Google Docs Viewer -->
                                    <iframe
                                        src="https://docs.google.com/gview?url={{ urlencode(asset($file)) }}&embedded=true"
                                        style="width:600px; height:500px;" frameborder="0"></iframe>
                                    <!-- Optional download link -->
                                    <br><a href="{{ asset($file) }}" target="_blank">Download Word Document</a>
                                    @else
                                    <!-- Unsupported file -->
                                    <p>Unsupported file type.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                @if(!empty($docs['do']))
                <div class="accordion" id="accordionExample">
                    @foreach($docs['do'] as $key => $all)

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                data-bs-target="#do{{$key}}" aria-expanded="false" aria-controls="collapse{{$key}}">
                                DO document #{{$key + 1}} ({{basename($all)}})
                            </button>
                        </h2>
                        <div id="do{{$key}}" class="accordion-collapse collapse" aria-labelledby="headingOne"
                            data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                @php
                                $file = 'public/'.$all; // Or $all['file'] depending on your data structure
                                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                @endphp

                                <div style="margin-bottom: 20px;">
                                    @if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']))
                                    <!-- Image Preview -->
                                    <a href="{{ asset($file) }}" target="_blank"><img src="{{ asset($file) }}"
                                            alt="Image" style="max-width: 500px;"></a>
                                    @elseif($extension === 'pdf')
                                    <!-- PDF Preview -->
                                    <a href="{{ asset($file) }}" target="_blank"><embed src="{{ asset($file) }}"
                                            type="application/pdf" width="600" height="400"></a>
                                    @elseif(in_array($extension, ['doc', 'docx']))
                                    <!-- Word Preview with Google Docs Viewer -->
                                    <iframe
                                        src="https://docs.google.com/gview?url={{ urlencode(asset($file)) }}&embedded=true"
                                        style="width:600px; height:500px;" frameborder="0"></iframe>
                                    <!-- Optional download link -->
                                    <br><a href="{{ asset($file) }}" target="_blank">Download Word Document</a>
                                    @else
                                    <!-- Unsupported file -->
                                    <p>Unsupported file type.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                @if(!empty($docs['optional_docs']))
                <div class="accordion" id="accordionExample">
                    @foreach($docs['optional_docs'] as $key => $all)

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                data-bs-target="#optional_docs{{$key}}" aria-expanded="false"
                                aria-controls="collapse{{$key}}">
                                Optional document #{{$key + 1}} ({{basename($all)}})
                            </button>
                        </h2>
                        <div id="optional_docs{{$key}}" class="accordion-collapse collapse" aria-labelledby="headingOne"
                            data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                @php
                                $file = 'public/'.$all; // Or $all['file'] depending on your data structure
                                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                @endphp

                                <div style="margin-bottom: 20px;">
                                    @if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']))
                                    <!-- Image Preview -->
                                    <a href="{{ asset($file) }}" target="_blank"><img src="{{ asset($file) }}"
                                            alt="Image" style="max-width: 500px;"></a>
                                    @elseif($extension === 'pdf')
                                    <!-- PDF Preview -->
                                    <a href="{{ asset($file) }}" target="_blank"><embed src="{{ asset($file) }}"
                                            type="application/pdf" width="600" height="400"></a>
                                    @elseif(in_array($extension, ['doc', 'docx']))
                                    <!-- Word Preview with Google Docs Viewer -->
                                    <iframe
                                        src="https://docs.google.com/gview?url={{ urlencode(asset($file)) }}&embedded=true"
                                        style="width:600px; height:500px;" frameborder="0"></iframe>
                                    <!-- Optional download link -->
                                    <br><a href="{{ asset($file) }}" target="_blank">Download Word Document</a>
                                    @else
                                    <!-- Unsupported file -->
                                    <p>Unsupported file type.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                @endif
            </div>
        </div>

        <div>
        </div>
    </div>

    @endforeach



    <script>
        function deleteCarrierFile(button) {
            var loadId = button.getAttribute('data-load-id');
            var fileName = button.getAttribute('data-file-name');

            if (confirm('Are you sure you want to delete this file?')) {
                $.ajax({
                    url: '/account/delete-carrier-file', // Replace with your actual endpoint
                    method: 'POST',
                    data: {
                        load_id: loadId,
                        file_name: fileName
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        console.log('Success:', response);
                        button.closest('.accordion-item').remove();
                        //alert(response.message);
                        //location.reload(); // Reload the page to reflect changes
                    },
                    error: function (xhr, status, error) {
                        console.error('Error:', error);
                        alert('An error occurred while deleting the file.');
                    }
                });
            }
        }

        function openUploadWindow(url) {
            // Define the size of the new window
            var width = 1500; // Width of the new window
            var height = 800; // Height of the new window

            // Calculate the position to center the window
            var left = screen.width / 2 - width / 2; // Center horizontally
            var top = screen.height / 2 - height / 2; // Center vertically

            // Open the new window with the specified URL and properties
            var newWindow = window.open(url, 'UploadWindow', 'width=' + width + ',height=' + height + ',top=' + top +
                ',left=' + left + ',resizable=yes,scrollbars=yes');

            // Focus on the new window, if it was successfully opened
            if (newWindow) {
                newWindow.focus();
            }
        }
    </script>
    <script>
        function initVendorDataTable() {

            // Reinitialize
            $('#datatable-buttons-vendor').DataTable({
                responsive: true,
                dom: 'frtip',
                buttons: false,
                pageLength: 50,
            });
        }

        $(document).ready(function () {

            initVendorDataTable();
        });


$(document).on('change', '.carrier_invoice_date', function () {

    var invoiceDate = $(this).val();
    var vendorId = $(this).data('id');

    if (!invoiceDate) {
        return;
    }

    $.ajax({
        url: '/account/update-carrier-invoice-date',
        method: 'POST',

        data: {
            id: vendorId,
            carrier_invoice_date: invoiceDate
        },

        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },

        success: function (response) {

            if (response.success) {

                $('.due_date-' + vendorId).text(
                    response.formatted_due_date
                );

                $('#mc-success-message')
                    .text(response.message)
                    .fadeIn();

                setTimeout(function () {
                    $('#mc-success-message')
                        .text('')
                        .fadeOut();
                }, 1000);
            }
        },

        error: function (xhr) {

            console.error('Error:', xhr.responseText);

            $('#mc-error-message')
                .text(
                    xhr.responseJSON?.message ||
                    'Something went wrong.'
                )
                .fadeIn();

            setTimeout(function () {
                $('#mc-error-message')
                    .text('')
                    .fadeOut();
            }, 1000);
        }
    });
});

        $(document).ready(function () {
            $(document).on('change', '.quick_pay', function (e) {
                var load_id = $(this).data('id');
                var quick_pay = $(this).val();

                $.ajax({
                    url: '/account/quick_pay',
                    method: 'POST',
                    data: {
                        load_id: load_id,
                        quick_pay: quick_pay
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        console.log('Success:', response);

                        $('#mc-success-message').text(response.message).fadeIn();

                        // Hide after 10 seconds
                        setTimeout(function () {
                            $('#mc-success-message').text('').fadeOut();
                        }, 1000); // 10000ms = 10s
                    },
                    error: function (xhr, status, error) {
                        console.error('Error:', error);
                        $('#mc-error-message').text(error).fadeIn();

                        // Hide after 10 seconds
                        setTimeout(function () {
                            $('#mc-error-message').text('').fadeOut();
                        }, 1000);
                    }
                });
            });
        });


        $(document).ready(function () {
            $(document).on('change', '.payment_method', function (e) {
                var load_id = $(this).data('id');
                var payment_method = $(this).val();

                $.ajax({
                    url: '/account/payment_method',
                    method: 'POST',
                    data: {
                        load_id: load_id,
                        payment_method: payment_method
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        console.log('Success:', response);

                        $('#mc-success-message').text(response.message).fadeIn();

                        // Hide after 10 seconds
                        setTimeout(function () {
                            $('#mc-success-message').text('').fadeOut();
                        }, 1000); // 10000ms = 10s
                    },
                    error: function (xhr, status, error) {
                        console.error('Error:', error);
                        $('#mc-error-message').text(error).fadeIn();

                        // Hide after 10 seconds
                        setTimeout(function () {
                            $('#mc-error-message').text('').fadeOut();
                        }, 1000);
                    }
                });
            });
        });


        $(document).ready(function () {
            $(document).on('change', '.ready_to_pay', function (e) {
                var load_id = $(this).data('id');
                var ready_to_pay = $(this).val();

                $.ajax({
                    url: '/account/ready_to_pay',
                    method: 'POST',
                    data: {
                        load_id: load_id,
                        ready_to_pay: ready_to_pay
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        console.log('Success:', response);

                        $('#mc-success-message').text(response.message).fadeIn();

                        // Hide after 10 seconds
                        setTimeout(function () {
                            $('#mc-success-message').text('').fadeOut();
                        }, 1000); // 10000ms = 10s
                    },
                    error: function (xhr, status, error) {
                        console.error('Error:', error);
                        $('#mc-error-message').text(error).fadeIn();

                        // Hide after 10 seconds
                        setTimeout(function () {
                            $('#mc-error-message').text('').fadeOut();
                        }, 1000);
                    }
                });
            });
        });

<script>
$(document).off('change.carrierMarkPaid', '.carrier_mark_as_paid').on('change.carrierMarkPaid', '.carrier_mark_as_paid', function () {

    var checkbox = $(this);
    var loadId = checkbox.data('id');

    if (checkbox.is(':checked')) {
        $.ajax({
            url: '{{ route("carrier.mark.paid") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: loadId
            },
            success: function (response) {

                if (response.success) {
                    // Replace checkbox with text
                    checkbox.closest('td').html('<span style="color:green;font-weight:600;">Paid</span>');
                    
                    // Reload once if needed
                    // location.reload();
                } else {
                    alert('Error: ' + response.message);
                    checkbox.prop('checked', false);
                }
            },
            error: function () {
                alert('Something went wrong. Try again.');
                checkbox.prop('checked', false);
            }
        });
    }
});
</script>

    <script>
            $(document).off('change.carrierDoc', '.carrierDoc').on('change.carrierDoc', '.carrierDoc', function () {
                let input = $(this);
                let files = input[0].files;
                let id = input.data('id');

                if (files.length === 0) return;

                let formData = new FormData();

                for (let i = 0; i < files.length; i++) {
                    formData.append('carrierDoc[]', files[i]);
                }

                formData.append('id', id);

                $.ajax({
                    url: '{{ route("uploadCarrierDocs") }}',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function (res) {
                        if (res.success) {
                            alert('Files uploaded successfully!');
                            location.reload();
                        } else {
                            alert('Upload failed: ' + res.message);
                        }
                    },
                    error: function () {
                        alert('Upload error. Please check file type and size.');
                    }
                });
            });
    </script>

    <script>
        $(document).on('click', '.delete-file', function () {
            console.log('Delete button clicked'); // Add this line

            let loadId = $(this).data('id');
            let file = $(this).data('file');
            let button = $(this);

            if (confirm('Are you sure you want to delete this file?')) {
                $.ajax({
                    url: '{{ route("load.delete.file") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: loadId,
                        file: file
                    },
                    success: function (res) {
                        if (res.success) {
                            button.closest('li').remove();
                        } else {
                            alert('Delete failed: ' + res.message);
                        }
                    },
                    error: function (xhr) {
                        console.error(xhr.responseText);
                        alert('Error deleting file.');
                    }
                });
            }
        });
    </script>

    <script>
        $(document).off('change.vendorProcessedBy', '.load_priority').on('change.vendorProcessedBy', '.load_priority', function () {

            let $select = $(this);
            let load_id = $select.data('id');
            let value = $select.val();
            let storageKey = 'vendor_processed_by_' + load_id;

            localStorage.setItem(storageKey, value || '');

            $.ajax({
                url: "{{ route('update.invoice.through') }}",
                type: 'POST',
                async: false,
                data: {
                    _token: "{{ csrf_token() }}",
                    id: load_id,
                    invoice_through: value
                },
                success: function (res) {
                    if (res.success) {
                        $select.val(res.invoice_through || '').trigger('change.select2');
                        localStorage.removeItem(storageKey);
                    }
                },
                error: function () {
                    toastr.error("Something went wrong");
                }
            });

        });

        $(function () {
            if (window.vendorProcessedByRestoreInitialized) {
                return;
            }

            window.vendorProcessedByRestoreInitialized = true;
            $('.load_priority').each(function () {
                let $select = $(this);
                let load_id = $select.data('id');
                let storageKey = 'vendor_processed_by_' + load_id;
                let pendingValue = localStorage.getItem(storageKey);

                if (pendingValue !== null && $select.find('option[value="' + pendingValue + '"]').length) {
                    $select.val(pendingValue).trigger('change.select2');
                    $select.trigger('change');
                }
            });
        });
    </script>
    <script>
        $(document).on('blur', '.vendor-note', function () {

            let textarea = $(this);
            let note = textarea.val();
            let id = textarea.data('id');

            if (note.trim() === '') return;

            $.ajax({
                url: "{{ route('vendorsystem.internalnotes') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    id: id,
                    note: note
                },
                success: function () {

                    // Disable current textarea
                    textarea.prop('disabled', true);

                    // Append new disabled textarea showing saved note
                    textarea.after(`
                <textarea class="form-control mb-2" disabled>
${note} — {{ auth()->user()->name }} (Just now)
                </textarea>
            `);

                    // Clear and create fresh textarea for next note
                    textarea.val('');
                    textarea.prop('disabled', false);
                }
            });
        });
    </script>
    <script>
        $(document).on('click', '.view-notes', function () {

            let id = $(this).data('id');

            $.post("{{ url('account/vendorsystem/getnotes') }}", {
                _token: "{{ csrf_token() }}",
                id: id
            }, function (notes) {

                let html = '';

                if (notes && notes.length) {
                    notes.forEach(n => {
                        html += `
                                    <div class="note-box mb-2">
                                        <div class="note-text">
                                            <strong>${n.user}</strong>
                                            <small class="text-muted"> - ${n.date}</small>
                                            <span> (${n.note})</span>
                                        </div>
                                    </div>

                `;
                    });
                } else {
                    html = '<p class="text-muted">No notes found</p>';
                }

                $('#notesContainer').html(html);
                $('#notesModal').modal('show');
            });
        });
    </script>

    