@extends('layout.compact.app') <!-- This links to the app.blade.php layout -->

@section('content')

<style>
#mc-success-message{
    padding: 10px;
    background-color: rgb(212, 237, 218);
    color: rgb(21, 87, 36);
    margin-bottom: 10px;
    border: 1px solid rgb(195, 230, 203);
    border-radius: 4px;
    position: fixed;
    width: 20%;
    right: 10px;
    z-index: 9999;
    top: 10px;
}

#mc-error-message{
    padding: 10px;
    background-color: rgb(237, 212, 214);
    color: rgb(206, 10, 27);
    margin-bottom: 10px;
    border: 1px solid rgb(230, 195, 201);
    border-radius: 4px;
    position: fixed;
    width: 20%;
    right: 10px;
    z-index: 9999;
    top: 10px;
}

.vendor-select + .select2-container,
.vendor-select + .select2-container .select2-selection--single,
.vendor-select + .select2-container > .selection,
.vendor-select + .select2-container > .dropdown-wrapper {
    height: 32px;
    line-height: normal;
    padding: 0;
}

.vendor-select + .select2-container .select2-selection--single {
    padding: 6px 32px 6px 12px;
    display: flex;
    align-items: center;
}

.vendor-select + .select2-container .select2-selection__rendered {
    line-height: normal;
    padding: 0;
}

.vendor-select + .select2-container .select2-selection__arrow {
    height: 30px;
    width: 30px;
}

.vendor-select + .select2-container .select2-selection__clear {
    line-height: normal;
    padding: 0;
}
.row-open {
    background-color: #f9e79f !important;
}
.row-delivered-paid {
   background-color: #ffcccb !important;
} 
.row-delivered-paid-record {
   background-color: #82e0aa !important;
} 
 .load-row {
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    /* Open – Red */
    .table-striped>tbody>tr.row-open td {
        background-color: #fff8dc !important;
        color: #000 !important;
    }

    /* Delivered – Yellow */
    .table-striped>tbody>tr.row-delivered td {
        background-color: #f7f7f7 !important;
        color: #8a6d3b !important;
    }

    /* Covered – Light Purple */
    .table-striped>tbody>tr.row-covered td {
        background-color: #f3e8ff !important;
        color: #6b21a8 !important;
    }

    /* On Route – Light Blue */
    .table-striped>tbody>tr.row-onroute td {
        background-color: #e0f2fe !important;
        color: #075985 !important;
    }

    /* Unloading – Light Orange */
    .table-striped>tbody>tr.row-unloading td {
        background-color: #ffedd5 !important;
        color: #9a3412 !important;
    }

    /* Completed – Gray */
    .table-striped>tbody>tr.row-completed td {
        background-color: #fff !important;
        color: #374151 !important;
    }

    /* Completed & Paid – Green */
    .table-striped>tbody>tr.row-completed-paid td {
        background-color: #e6ffed !important;
        color: #2d6a4f !important;
    }

    /* Completed & Paid Record – Teal */
    .table-striped>tbody>tr.row-completed-paidrecord td {
        background-color: #e6ffed !important;
        color: #115e59 !important;
    }

    /* Hover */
    .load-row:hover td {
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.08);
        transform: scale(1.01);
    }
    .status-box {
    display: inline-block;
    width: 18px;
    height: 18px;
    border-radius: 4px;
    margin-right: 6px;
    border: 1px solid #ccc;
}
 div[style*="overflow-x"]::-webkit-scrollbar {
        height: 8px;
        width: 8px;
    }
    div[style*="overflow-x"]::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }
    div[style*="overflow-x"]::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
    .note-box {
    background: #f8f9fa;
    padding: 12px 15px;
    border-radius: 10px;
    border-left: 4px solid #0d6efd;
}

.note-user {
    font-size: 14px;
    margin-bottom: 5px;
}

.note-text {
    font-size: 15px;
    color: #333;
}
.form-control {
    display: block;
    width: unset;
    padding: .47rem .75rem;
    font-size: .9rem;
    font-weight: 400;
    line-height: 1.5;
    color: var(--bs-body-color);
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    background-color: var(--bs-secondary-bg);
    background-clip: padding-box;
    border: var(--bs-border-width) solid var(--bs-border-color);
    border-radius: var(--bs-border-radius);
    -webkit-transition: border-color .15s ease-in-out, -webkit-box-shadow .15s ease-in-out;
    transition: border-color .15s ease-in-out, -webkit-box-shadow .15s ease-in-out;
    transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
    transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out, -webkit-box-shadow .15s ease-in-out;
}
</style>

<div id="mc-success-message" style="display: none;"></div>
<div id="mc-error-message" style="display: none;"></div>

<div class="page-content">
                    <div class="container-fluid">

                        <!-- start page title -->
                        <div class="row">
                            <div class="col-12">
                                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                    <h4 class="mb-sm-0">Vendor System</h4>

                                    <div class="page-title-right">
                                        <ol class="breadcrumb m-0">
                                            <li class="breadcrumb-item"><a href="javascript: void(0);">CCI</a></li>
                                            <li class="breadcrumb-item active">Vendor System</li>
                                        </ol>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <!-- end page title -->
                        
        
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                <div class="my-4 d-flex justify-content-between align-items-center">
                                        <h4 class="card-title">Vendor System</h4>
                                                                       <!-- Status Legend -->
                                <!-- <ul class="list-inline m-0" style="display: flex;">
                                    <li class="list-inline-item d-flex align-items-center me-3">
                                        <span class="status-box" style="background:#ffe5e5;"></span> Open
                                    </li>
                                    <li class="list-inline-item d-flex align-items-center me-3">
                                        <span class="status-box" style="background:#fff8dc;"></span> Delivered
                                    </li>
                                    <li class="list-inline-item d-flex align-items-center me-3">
                                        <span class="status-box" style="background:#e6f0ff;"></span> Invoiced
                                    </li>
                                    <li class="list-inline-item d-flex align-items-center me-3">
                                        <span class="status-box" style="background:#e6ffed;"></span> Paid
                                    </li>
                                </ul> -->
</div>
        
<div style="overflow-x: auto; overflow-y: auto; max-height: 600px; border: 1px solid #ddd; border-radius: 8px;">
    <span>
        <a href="{{ route('vendorSystemExcel') }}">
            <button class="btn btn-primary waves-effect waves-light mb-3 exlbtn">Vendor System Excel</button>
        </a>
    </span>
    <table
        class="table table-striped table-bordered"
        style="border-collapse: collapse; border-spacing: 0; width: 100%;">
        <thead>
            <tr>
                <th>Sr No.</th>
                <th>Load#</th>
                <th>W/O #</th>
                <th>Carrier</th>
                <th>Carrier Invoice Date</th>
                <th>Carrier Due Date</th>
                <th>Ready to Pay</th>
                <th>Proccessed By</th>
                <th>Documents</th>
                <th>Quick Pay %</th>
                <th>Carrier Files Upload</th>
                <th>Carrier Files View</th>
                <th>Payment Method</th>
                <th>Carrier Payment Status</th>
                <th>Carrier Payment Date</th></th>
                <th>Customer Invoice Date</th>
                <!-- <th>Agent Files</th> -->
                <th>Logs Check</th>
                <th>Vendor Internal Notes</th>
            </tr>
        </thead>

        <tbody id="vendor-search">
            @include('accounts.partials.vendor_system_table')
        </tbody>
    </table>
</div>

<div class="custom-pagination mt-3">
    {!! render_pagination_links($vendormanagement) !!}
</div>

        <div id="modals-container">
            @foreach($vendormanagement as $i => $vendor)

            <!-- Blade Table -->
            @if($vendor->carrierDoc)
            
            <!-- Move this outside of the table -->
					<div class="modal fade" id="filesModal{{ $vendor->id }}" tabindex="-1" aria-labelledby="filesModalLabel{{ $vendor->id }}" aria-hidden="true">
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
                                                <h2 class="accordion-header" id="heading{{$key}}">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{$key}}" aria-expanded="false" aria-controls="collapse{{$key}}"> 
                                                        View document #{{$key + 1}} ({{ basename($all) }})
                                                    </button>
                                                     <button
                                                            type="button"
                                                            class="remove-file"
                                                            data-load-id="{{ $vendor->load_id ?? $vendor->id }}"
                                                            data-file-name="{{ basename($all) }}"
                                                            onclick="deleteCarrierFile(this)"
                                                            style="background: unset; border: none;"
                                                        >
                                                            <i class="fa fa-trash" style="margin-top: 15px; color: red;"></i>
                                                        </button>
                                                    
                                                </h2>
                                                <div id="collapse{{$key}}" class="accordion-collapse collapse" aria-labelledby="heading{{$key}}" data-bs-parent="#accordionExample">
                                                    <div class="accordion-body">
                                                        @php
                                                            $file = $all;
                                                            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                                        @endphp

                                                        <div style="margin-bottom: 20px;">
                                                            @if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']))
                                                                <!-- Image Preview -->
                                                                <a href="{{ asset('public/'.$file) }}" target="_blank">
                                                                    <img src="{{ asset('public/'.$file) }}" alt="Image" style="max-width: 500px;">
                                                                </a>
                                                            @elseif($extension === 'pdf')
                                                                <!-- PDF Preview -->
                                                                <a href="{{ asset('public/'.$file) }}" target="_blank">
                                                                    <embed src="{{ asset('public/'.$file) }}" type="application/pdf" width="600" height="400" />
                                                                 </a>
                                                            @elseif(in_array($extension, ['doc', 'docx']))
                                                                <!-- Word Preview with Google Docs Viewer -->
                                                                <iframe src="https://docs.google.com/gview?url={{ urlencode(asset('public/'.$file)) }}&embedded=true" 
                                                                        style="width:600px; height:500px;" frameborder="0"></iframe>
                                                                <br>
                                                                <a href="{{ asset('public/'.$file) }}" target="_blank">Download Word Document</a>
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
            
            @endif
        </div>
		
		
    


    <div class="modal fade" id="view-documents-{{ $vendor->id }}" tabindex="-1" aria-labelledby="view-documents" aria-hidden="true">
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
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#carrer_rate{{$key}}" aria-expanded="false" aria-controls="collapse{{$key}}">
                                            Carrier Rate document #{{$key + 1}} ({{basename($all)}})
                                            </button>
                                        </h2>
                                        <div id="carrer_rate{{$key}}" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                                            <div class="accordion-body">
                                            @php
                                                    $file = 'public/'.$all; // Or $all['file'] depending on your data structure
                                                    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                                @endphp

                                                <div style="margin-bottom: 20px;">
                                                    @if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']))
                                                        <!-- Image Preview -->
                                                        <a href="{{ asset($file) }}" target="_blank"><img src="{{ asset($file) }}" alt="Image" style="max-width: 500px;"></a>
                                                    @elseif($extension === 'pdf')
                                                        <!-- PDF Preview -->
                                                        <a href="{{ asset($file) }}" target="_blank"><embed src="{{ asset($file) }}" type="application/pdf" width="600" height="400"></a>
                                                    @elseif(in_array($extension, ['doc', 'docx']))
                                                        <!-- Word Preview with Google Docs Viewer -->
                                                        <iframe src="https://docs.google.com/gview?url={{ urlencode(asset($file)) }}&embedded=true" 
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
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#pod_doc{{$key}}" aria-expanded="false" aria-controls="collapse{{$key}}">
                                            POD document #{{$key + 1}} ({{basename($all)}})
                                            </button>
                                        </h2>
                                        <div id="pod_doc{{$key}}" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                                            <div class="accordion-body">
                                            @php
                                                    $file = 'public/'.$all; // Or $all['file'] depending on your data structure
                                                    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                                @endphp

                                                <div style="margin-bottom: 20px;">
                                                    @if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']))
                                                        <!-- Image Preview -->
                                                        <a href="{{ asset($file) }}" target="_blank"><img src="{{ asset($file) }}" alt="Image" style="max-width: 500px;"></a>
                                                    @elseif($extension === 'pdf')
                                                        <!-- PDF Preview -->
                                                        <a href="{{ asset($file) }}" target="_blank"><embed src="{{ asset($file) }}" type="application/pdf" width="600" height="400"></a>
                                                    @elseif(in_array($extension, ['doc', 'docx']))
                                                        <!-- Word Preview with Google Docs Viewer -->
                                                        <iframe src="https://docs.google.com/gview?url={{ urlencode(asset($file)) }}&embedded=true" 
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
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#shipper_rate{{$key}}" aria-expanded="false" aria-controls="collapse{{$key}}">
                                            Shipper Rate document #{{$key + 1}} ({{basename($all)}})
                                            </button>
                                        </h2>
                                        <div id="shipper_rate{{$key}}" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                                            <div class="accordion-body">
                                            @php
                                                    $file = 'public/'.$all; // Or $all['file'] depending on your data structure
                                                    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                                @endphp

                                                <div style="margin-bottom: 20px;">
                                                    @if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']))
                                                        <!-- Image Preview -->
                                                        <a href="{{ asset($file) }}" target="_blank"><img src="{{ asset($file) }}" alt="Image" style="max-width: 500px;"></a>
                                                    @elseif($extension === 'pdf')
                                                        <!-- PDF Preview -->
                                                        <a href="{{ asset($file) }}" target="_blank"><embed src="{{ asset($file) }}" type="application/pdf" width="600" height="400"></a>
                                                    @elseif(in_array($extension, ['doc', 'docx']))
                                                        <!-- Word Preview with Google Docs Viewer -->
                                                        <iframe src="https://docs.google.com/gview?url={{ urlencode(asset($file)) }}&embedded=true" 
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
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#carrier_invoice{{$key}}" aria-expanded="false" aria-controls="collapse{{$key}}">
                                            Carrier Invoice document #{{$key + 1}} ({{basename($all)}})
                                            </button>
                                        </h2>
                                        <div id="carrier_invoice{{$key}}" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                                            <div class="accordion-body">
                                            @php
                                                    $file = 'public/'.$all; // Or $all['file'] depending on your data structure
                                                    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                                @endphp

                                                <div style="margin-bottom: 20px;">
                                                    @if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']))
                                                        <!-- Image Preview -->
                                                        <a href="{{ asset($file) }}" target="_blank"><img src="{{ asset($file) }}" alt="Image" style="max-width: 500px;"></a>
                                                    @elseif($extension === 'pdf')
                                                        <!-- PDF Preview -->
                                                        <a href="{{ asset($file) }}" target="_blank"><embed src="{{ asset($file) }}" type="application/pdf" width="600" height="400"></a>
                                                    @elseif(in_array($extension, ['doc', 'docx']))
                                                        <!-- Word Preview with Google Docs Viewer -->
                                                        <iframe src="https://docs.google.com/gview?url={{ urlencode(asset($file)) }}&embedded=true" 
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
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#do{{$key}}" aria-expanded="false" aria-controls="collapse{{$key}}">
                                            DO document #{{$key + 1}} ({{basename($all)}})
                                            </button>
                                        </h2>
                                        <div id="do{{$key}}" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                                            <div class="accordion-body">
                                            @php
                                                    $file = 'public/'.$all; // Or $all['file'] depending on your data structure
                                                    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                                @endphp

                                                <div style="margin-bottom: 20px;">
                                                    @if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']))
                                                        <!-- Image Preview -->
                                                        <a href="{{ asset($file) }}" target="_blank"><img src="{{ asset($file) }}" alt="Image" style="max-width: 500px;"></a>
                                                    @elseif($extension === 'pdf')
                                                        <!-- PDF Preview -->
                                                        <a href="{{ asset($file) }}" target="_blank"><embed src="{{ asset($file) }}" type="application/pdf" width="600" height="400"></a>
                                                    @elseif(in_array($extension, ['doc', 'docx']))
                                                        <!-- Word Preview with Google Docs Viewer -->
                                                        <iframe src="https://docs.google.com/gview?url={{ urlencode(asset($file)) }}&embedded=true" 
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
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#optional_docs{{$key}}" aria-expanded="false" aria-controls="collapse{{$key}}">
                                            Optional document #{{$key + 1}} ({{basename($all)}})
                                            </button>
                                        </h2>
                                        <div id="optional_docs{{$key}}" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                                            <div class="accordion-body">
                                            @php
                                                    $file = 'public/'.$all; // Or $all['file'] depending on your data structure
                                                    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                                @endphp

                                                <div style="margin-bottom: 20px;">
                                                    @if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']))
                                                        <!-- Image Preview -->
                                                        <a href="{{ asset($file) }}" target="_blank"><img src="{{ asset($file) }}" alt="Image" style="max-width: 500px;"></a>
                                                    @elseif($extension === 'pdf')
                                                        <!-- PDF Preview -->
                                                        <a href="{{ asset($file) }}" target="_blank"><embed src="{{ asset($file) }}" type="application/pdf" width="600" height="400"></a>
                                                    @elseif(in_array($extension, ['doc', 'docx']))
                                                        <!-- Word Preview with Google Docs Viewer -->
                                                        <iframe src="https://docs.google.com/gview?url={{ urlencode(asset($file)) }}&embedded=true" 
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
</div>

@endforeach 

</div>										
										
                                  
                                    </div>
                                </div>
                            </div> <!-- end col -->
                        </div> <!-- end row -->



                    </div> <!-- container-fluid -->
                </div>
                <!-- End Page-content -->

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
                        button.closest('.accordion-item').remove(); // Remove the file entry from the UI
                       // alert(response.message);
                        //location.reload(); // Reload the page to reflect changes
                    },
                    error: function (xhr, status, error) {
                        console.error('Error:', error);
                        alert('An error occurred while deleting the file.');
                    }
                });
            }
        }
     $(document).on('click', '.custom-pagination a', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        if (!url) return;

        $('.loader-container').removeClass('hide');
        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                $('#vendor-search').html(response.html);
                $('#modals-container').html(response.modals);
                $('.custom-pagination').html(response.pagination || '').show();
                window.history.pushState({}, '', url);
                $('.loader-container').addClass('hide');
            },
            error: function(xhr) {
                console.error('Pagination AJAX error:', xhr.responseText);
                $('.loader-container').addClass('hide');
            }
        });
    });
</script>
<script>
$(document).ready(function() {
    $('input[name="query"]').on('keyup', function() {
        let query = $(this).val().trim();

        // Debounce logic
        clearTimeout($.data(this, 'timer'));
        let wait = setTimeout(() => {

            if (query.length > 0) {
                $('.loader-container').removeClass('hide');
                // Perform AJAX search
                $.ajax({
                    url: "{{ route('vendor_search') }}",
                    type: 'GET',
                    data: { query: query },
                    success: function(response) {
                        $('#vendor-search').html(response.rows); // Inject result HTML
						$('#modals-container').html(response.modals);
                        $('.custom-pagination').html(response.pagination || '').show();


                        $('.loader-container').addClass('hide');
                        
                    },
                    error: function(xhr) {
                        console.error("AJAX error:", xhr.responseText);
                    }
                });

            } else {
               
                // Optionally clear the results or reload original data
                $('#vendor-search').html('');
            }

        }, 300);

        $(this).data('timer', wait);
    });
});

</script>

<script>
    $(function () {
        $(document)
            .off('change.vendorProcessedBy', '.load_priority')
            .on('change.vendorProcessedBy', '.load_priority', function () {
                const $select = $(this);
                const loadId = $select.data('id');
                const value = $select.val();
                const storageKey = 'vendor_processed_by_' + loadId;

                localStorage.setItem(storageKey, value || '');

                $.ajax({
                    url: "{{ route('update.invoice.through') }}",
                    type: 'POST',
                    async: false,
                    data: {
                        _token: "{{ csrf_token() }}",
                        id: loadId,
                        invoice_through: value
                    },
                    success: function (response) {
                        if (response.success) {
                            $select.val(response.invoice_through || '').trigger('change.select2');
                            localStorage.removeItem(storageKey);
                        }
                    },
                    error: function () {
                        toastr.error('Unable to save Processed By.');
                    }
                });
            });

        $('.load_priority').each(function () {
            const $select = $(this);
            const pendingValue = localStorage.getItem('vendor_processed_by_' + $select.data('id'));

            if (pendingValue !== null && $select.find('option[value="' + pendingValue + '"]').length) {
                $select.val(pendingValue).trigger('change.select2');
                $select.trigger('change');
            }
        });
    });
</script>


<script>
$(function () {
    const vendorTableBody = $('#vendor-search');

    vendorTableBody.off('change.vendorDocuments', '.carrier_documents')
        .on('change.vendorDocuments', '.carrier_documents', function () {
            const select = $(this);
            const savedValue = select.data('saved-value') || '';
            const documentValue = select.val() || '';

            select.prop('disabled', true);

            $.ajax({
                url: "{{ route('account.update-carrier-documents') }}",
                method: 'POST',
                dataType: 'json',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    id: select.data('id'),
                    carrier_documents: documentValue
                },
                success: function (response) {
                    if (!response.success) {
                        select.val(savedValue);
                        return;
                    }

                    const persistedValue = response.carrier_documents || '';
                    select.data('saved-value', persistedValue);
                    select.val(persistedValue);

                    $('#mc-success-message').text(response.message).fadeIn();
                    setTimeout(function () {
                        $('#mc-success-message').text('').fadeOut();
                    }, 1500);
                },
                error: function (xhr) {
                    select.val(savedValue);
                    console.error('Carrier document save failed:', xhr.responseText);

                    $('#mc-error-message')
                        .text(xhr.responseJSON?.message || 'Unable to save document selection.')
                        .fadeIn();
                    setTimeout(function () {
                        $('#mc-error-message').text('').fadeOut();
                    }, 3000);
                },
                complete: function () {
                    select.prop('disabled', false);
                }
            });
        });
});
</script>
@endsection