@extends('layout.compact.app')
<!-- This links to the app.blade.php layout -->

@section('content')
<style>
/* The Add Load form sits inside the XL modal (max-width:90%, centred), so the banner is
   pinned to the viewport and centred to the same width instead of the page content area.
   position: sticky cannot be used here: .main-content has overflow:hidden. */
#credit-limit-message {
    position: fixed;
    top: 82px;
    left: 50%;
    right: auto;
    transform: translateX(-50%);
    width: min(90vw, 1180px);
    max-width: calc(100vw - 48px);
    min-height: 38px;
    display: flex;
    align-items: center;
    border-radius: 4px;
    background-color: #fff3cd;
    border-color: #ffecb5;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    z-index: 1060;
    box-sizing: border-box;
    overflow-wrap: anywhere;
    white-space: normal;
}
#credit-limit-message.alert-danger {
    background-color: #f8d7da;
    border-color: #f5c2c7;
    color: #842029;
}

/* The other-charges pop-ups are toggled with jQuery .show(), so Bootstrap never adds a
   .modal-backdrop. Dim the overlay on the modal element itself instead. */
#myModal,
#otherChargesModal {
    background-color: rgba(0, 0, 0, 0.5);
}

/* Compact form controls for this page and its modals */
.page-content .form-control,
.page-content .form-select,
.modal .form-control,
.modal .form-select {
    padding: 2px 8px;
    font-size: 12px;
    line-height: 1.5;
}

.page-content .form-control:not(textarea):not([type="file"]),
.page-content .form-select,
.modal .form-control:not(textarea):not([type="file"]),
.modal .form-select {
    height: 30px;
    min-height: 30px;
}

/* The shipper/consignee autocomplete panels are <div class="form-control">, not inputs:
   keep them out of the fixed input height or the suggestions get clipped to one line */
.shipperList,
.consigneeList {
    height: auto !important;
    min-height: 0 !important;
    max-height: 180px;
    overflow-y: auto;
    padding: 4px 0 !important;
    background-color: #fff;
    z-index: 1070;
}

.shipperList .item,
.consigneeList .item {
    padding: 4px 8px !important;
    cursor: pointer;
}

.shipperList .item:hover,
.consigneeList .item:hover {
    background-color: #f1f3f5;
}

.page-content label,
.modal label {
    font-size: 12px;
    margin-bottom: 2px;
}

/* Keep the select2 controls the same size as the inputs beside them */
.select2-container--default .select2-selection--single {
    height: 30px !important;
    min-height: 30px !important;
    padding: 2px 26px 2px 8px !important;
}

.select2-container--default .select2-selection--single .select2-selection__rendered,
.select2-container--default .select2-selection--single .select2-selection__placeholder {
    font-size: 12px !important;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    width: 24px !important;
}

/* Dropdowns are appended to body / the modal, so they need their own rule */
.select2-dropdown,
.select2-results__option,
.select2-search__field {
    font-size: 12px !important;
}

/* Filter By Agents: its wrapper is an inline <span>, so the layout's width:100% resolves
   against .card-body and the control grows once DataTables widens the table. Pin it instead. */
#filteruser + .select2-container {
    width: 200px !important;
    vertical-align: middle;
}

#myFormLoad input[type="submit"].disabled,
#myFormLoad input[type="submit"]:disabled {
    background-color: #b8b8b8 !important;
    border-color: #b8b8b8 !important;
    color: #ffffff !important;
    cursor: not-allowed !important;
    opacity: 1 !important;
}

/* General */
.load-row {
    transition: background-color 0.3s ease, color 0.3s ease;
}
a.btn.btn-primary.btn-sm {
    background-color: unset;
    border: unset;
}

/* Open – Red */
.table-striped > tbody > tr.row-open td {
    /* background-color: #56b6c3 !important; */
    background-color: #7ea90ccc !important;
    color: #000 !important;
}

/* Delivered – Yellow */
.table-striped > tbody > tr.row-delivered td {
    background-color: #fff8dc !important;
    color: #8a6d3b !important;
}

/* Covered – Light Purple */
.table-striped > tbody > tr.row-covered td {
    background-color: #f3e8ff !important;
    color: #6b21a8 !important;
}

/* On Route – Light Blue */
.table-striped > tbody > tr.row-onroute td {
    background-color: #e0f2fe !important;
    color: #075985 !important;
}

/* Unloading – Light Orange */
.table-striped > tbody > tr.row-unloading td {
    background-color: #ffedd5 !important;
    color: #9a3412 !important;
}

/* Completed – Gray */
.table-striped > tbody > tr.row-completed td {
    background-color: #f3f4f6 !important;
    color: #374151 !important;
}

/* Completed & Paid – Green */
.table-striped > tbody > tr.row-completed-paid td {
    background-color: #e6ffed !important;
    color: #2d6a4f !important;
}

/* Completed & Paid Record – Teal */
.table-striped > tbody > tr.row-completed-paidrecord td {
    background-color: #e0fdfa !important;
    color: #115e59 !important;
}

/* Hover */
.load-row:hover td {
    box-shadow: 0 0 10px rgba(0,0,0,0.08);
    transform: scale(1.01);
}

    ul#navTabs,
    ul#navTabs1 {
        background: #a6ce3a;
        padding: 10px;
        margin-bottom: 10px;
    }

    button.close.close-modal-btn {
        background: red;
        color: #fff;
        border: navajowhite;
        border-radius: 10px;
        padding: 0px 10px;
    }
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
    background-color: rgb(243, 118, 129);
    color: rgb(87, 21, 26);
    margin-bottom: 10px;
    border: 1px solid rgb(243, 118, 129);
    border-radius: 4px;
    position: fixed;
    width: 20%;
    right: 10px;
    z-index: 9999;
    top: 10px;
}
.card-header {
    padding: 10px;
    background: #f8f9fa;
    margin: 10px 0px;
}
.status-box {
    display: inline-block;
    width: 18px;
    height: 18px;
    border-radius: 4px;
    margin-right: 6px;
    border: 1px solid #ccc;
}

/* Status colors */
.bg-open {
    background-color: #ffe5e5;   /* soft red */
}
.bg-delivered {
    background-color: #fff8dc;   /* light yellow */
}
.bg-invoiced {
    background-color: #e6f0ff;   /* soft blue */
}
.bg-paid {
    background-color: #e6ffed;   /* soft green */
}
div#datatable-buttons-all_filter {
    display: none;
}
div#datatable-buttons-open_filter,div#datatable-buttons-delivered_filter,div#datatable-buttons-complete_filter{
    display: none;
}
.table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #fff; /* match your table background */
}
.row-cancelled {
    background-color: #f8d7da; /* light red background */
    color: #721c24; /* dark red text */
}
.form-check-input[type=checkbox]
 {
    border-radius: .25em;
    border: 2px solid #000;
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
                    <h4 class="mb-sm-0">Load</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">CCI</a></li>
                            <li class="breadcrumb-item active">Load</li>
                        </ol>
                    </div>

                </div>
            </div>
        </div>
        <!-- end page title -->
@if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif


        @if(session('success'))
        <div class="alert alert-success" id="success-alert">
            {{ session('success') }}
        </div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card">

                    <div class="card-body">

                        <h4 class="card-title">Load</h4>
                        <span style="float: left;margin-right: 10px;">Filter By Agents</span>
                        <span><select style="width:200px;" name="filteruser" class="form-control" id="filteruser">
						<option value="{{Auth::id()}}">{{Auth::user()->name}}</option>
                            @foreach($userInfos as $key => $user)
								<option value="{{$key}}">{{$user}}</option>
                            @endforeach
                        </select></span>

                        <ul class="nav nav-tabs nav-tabs-custom nav-justified" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link active" data-bs-toggle="tab" href="#all" role="tab"
                                    aria-selected="true">
                                    <span class="d-block d-sm-none"><i class="fas fa-home"></i></span>
                                    <span class="d-none d-sm-block">All</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" data-bs-toggle="tab" href="#open" role="tab" aria-selected="false"
                                    tabindex="-1">
                                    <span class="d-block d-sm-none"><i class="far fa-user"></i></span>
                                    <span class="d-none d-sm-block">Open</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" data-bs-toggle="tab" href="#delivered" role="tab"
                                    aria-selected="false" tabindex="-1">
                                    <span class="d-block d-sm-none"><i class="far fa-envelope"></i></span>
                                    <span class="d-none d-sm-block">Delivered</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" data-bs-toggle="tab" href="#complete" role="tab"
                                    aria-selected="false" tabindex="-1">
                                    <span class="d-block d-sm-none"><i class="fas fa-cog"></i></span>
                                    <span class="d-none d-sm-block">Complete</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" data-bs-toggle="tab" href="#invoice" role="tab"
                                    aria-selected="false" tabindex="-1">
                                    <span class="d-block d-sm-none"><i class="fas fa-cog"></i></span>
                                    <span class="d-none d-sm-block">Invoice</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" data-bs-toggle="tab" href="#paid" role="tab" aria-selected="false"
                                    tabindex="-1">
                                    <span class="d-block d-sm-none"><i class="fas fa-cog"></i></span>
                                    <span class="d-none d-sm-block">Invoice / Paid</span>
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content p-3 text-muted">
                            <div class="tab-pane active show" id="all" role="tabpanel">
                                <div class="my-4 d-flex justify-content-between align-items-center">
                                    <!-- Add Load Button -->
                                    <button type="button" class="btn btn-primary waves-effect waves-light"
                                            data-bs-toggle="modal" data-bs-target=".bs-example-modal-xl">
                                        + Add Load
                                    </button>

                                    <!-- Status Legend -->
                                    <ul class="list-inline m-0 d-flex flex-wrap align-items-center">
                                        <li class="list-inline-item d-flex align-items-center me-3">
                                            <span class="status-box bg-open"></span> Open
                                        </li>
                                        <li class="list-inline-item d-flex align-items-center me-3">
                                            <span class="status-box bg-delivered"></span> Delivered
                                        </li>
                                        <li class="list-inline-item d-flex align-items-center me-3">
                                            <span class="status-box bg-invoiced"></span> Invoiced
                                        </li>
                                        <li class="list-inline-item d-flex align-items-center me-3">
                                            <span class="status-box bg-paid"></span> Paid
                                        </li>
                                    </ul>
                                </div>


                                <table id="datatable-buttons-all"
                                    class="table table-striped table-bordered dt-responsive nowrap"
                                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Load #</th>
                                            <th>Action</th>
                                            <th>Agent Name</th>
                                            <th>W/O #</th>
                                            <th>Customer Refrence #</th>
                                            <th>Customer Name</th>
                                            <th>Load Creation Date</th>
                                            <th>Shipper Date</th>
                                            <th>Delivered Date</th>
                                            <th>Actual Del Date</th>
                                            <th>Carrier</th>
                                            <th>Shipper Name</th>
                                            <th>Pickup Location</th>
                                            <th>Consignee Name</th>
                                            <th>Unloading Location</th>
                                            <th>Load Status</th>
                                            <th>Cus Rate</th>
                                            <th>Carrier Rate</th>
                                            <th>Margin</th>
                                            <th>Margin %</th>
                                            <th>Aging</th>
                                            <th>CPR Status</th>
                                            <th>Carrier Payment Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>


                                    <tbody id="all_search">
                                        @include('broker.loads.all_loads')
                                    </tbody>
                                </table>
                                <div class="custom-pagination">
                                    {{ $all_load->setPageName('all_loads')->links('pagination::bootstrap-5') }}
                                </div>
                            </div>
                            <div class="tab-pane" id="open" role="tabpanel">
                                <div class="my-4">
                                    <button type="button" class="btn btn-primary waves-effect waves-light"
                                        data-bs-toggle="modal" data-bs-target=".bs-example-modal-xl">+ Add Load</button>
                                </div>
                                <table id="datatable-buttons-open"
                                    class="table table-striped table-bordered dt-responsive nowrap"
                                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Load #</th>
                                            <th>Action</th>
                                            <th>W/O #</th>
                                            <th>Customer Refrence #</th>
                                            <th>Customer #</th>
                                            <th>Load Create Date</th>
                                            <th>Shipper Date</th>
                                            <th>Delivered Date</th>
                                            <th>Carrier</th>
                                            <th>Pickup Location</th>
                                            <th>Unloading Location</th>
                                            <th>Load Status</th>
                                            <th>Cus Rate</th>
                                            <th>Carrier Rate</th>
                                            <th>Margin</th>
                                            <th>Margin %</th>
                                            <th>CPR Status</th>
                                            <th>Action</th>

                                        </tr>
                                    </thead>


                                    <tbody id="open_search">

                                        @include('broker.loads.open')

                                    </tbody>
                                </table>
                                <div class="custom-pagination">
                                    {{ $open->setPageName('open')->links('pagination::bootstrap-5') }}
                                </div>
                            </div>
                            <div class="tab-pane" id="delivered" role="tabpanel">
                                <div class="my-4">
                                    <button type="button" class="btn btn-primary waves-effect waves-light"
                                        data-bs-toggle="modal" data-bs-target=".bs-example-modal-xl">+ Add Load</button>
                                </div>
                                <table id="datatable-buttons-delivered"
                                    class="table table-striped table-bordered dt-responsive nowrap"
                                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Load #</th>
                                            <th>W/O #</th>
                                            <th>Customer Refrence #</th>
                                            <th>Customer #</th>
                                            <th>Load Create Date</th>
                                            <th>Shipper Date</th>
                                            <th>Delivered Date</th>
                                            <th>Carrier</th>
                                            <th>Pickup Location</th>
                                            <th>Unloading Location</th>
                                            <th>Actual Del Date</th>
                                            <th>Load Status</th>
                                            <th>Cus Rate</th>
                                            <th>Carrier Rate</th>
                                            <th>Margin</th>
                                            <th>Margin %</th>
                                            <th>CPR Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>


                                    <tbody id="delivered_search">
                                        @include('broker.loads.delivered')
                                    </tbody>
                                </table>
                                <div class="custom-pagination">
                                    {{ $delivered->setPageName('delivered')->links('pagination::bootstrap-5') }}
                                </div>
                            </div>
                            <div class="tab-pane" id="complete" role="tabpanel">
                                <div class="my-4">
                                    <button type="button" class="btn btn-primary waves-effect waves-light"
                                        data-bs-toggle="modal" data-bs-target=".bs-example-modal-xl">+ Add Load</button>
                                </div>
                                <table id="datatable-buttons-complete"
                                    class="table table-striped table-bordered dt-responsive nowrap"
                                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Load #</th>
                                            <th>W/O #</th>
                                            <th>Customer #</th>
                                            <th>Load Create Date</th>
                                            <th>Shipper Date</th>
                                            <th>Delivered Date</th>
                                            <th>Carrier</th>
                                            <th>Pickup Location</th>
                                            <th>Unloading Location</th>
                                            <th>Actual Del Date</th>
                                            <th>Load Status</th>
                                            <th>Cus Rate</th>
                                            <th>Cus Biffercation</th>
                                            <th>Carrier Rate</th>
                                            <th>Carrier Biffercation</th>
                                            <th>Margin</th>
                                            <th>Margin %</th>
                                            <th>Aging</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>


                                    <tbody id="complete_search">
                                        @include('broker.loads.complete')
                                    </tbody>
                                </table>
                                <div class="custom-pagination">
                                    {{ $complete->setPageName('complete')->links('pagination::bootstrap-5') }}
                                </div>
                            </div>
                            <div class="tab-pane" id="invoice" role="tabpanel">
                                <div class="my-4">
                                    <button type="button" class="btn btn-primary waves-effect waves-light"
                                        data-bs-toggle="modal" data-bs-target=".bs-example-modal-xl">+ Add Load</button>
                                </div>
                                <table id="datatable-buttons-invoice"
                                    class="table table-striped table-bordered dt-responsive nowrap"
                                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Load #</th>
                                            <th>Invoice #</th>
                                            <th>Invoice Date</th>
                                            <th>W/O #</th>
                                            <th>Customer #</th>
                                            <th>Load Create Date</th>
                                            <th>Shipper Date</th>
                                            <th>Delivered Date</th>
                                            <th>Carrier</th>
                                            <th>Pickup Location</th>
                                            <th>Unloading Location</th>
                                            <th>Actual Del Date</th>
                                            <th>Load Status</th>
                                            <th>Cus Rate</th>
                                            <th>Carrier Rate</th>
                                            <th>Margin</th>
                                            <th>Margin %</th>
                                            <th>Aging</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>


                                    <tbody id="invoice_search">
                                        @include('broker.loads.invoice')
                                    </tbody>
                                </table>
                                <div class="custom-pagination">
                                    {{ $invoice->setPageName('invoice')->links('pagination::bootstrap-5') }}
                                </div>
                            </div>
                            <div class="tab-pane" id="paid" role="tabpanel">
                                <div class="my-4">
                                    <button type="button" class="btn btn-primary waves-effect waves-light"
                                        data-bs-toggle="modal" data-bs-target=".bs-example-modal-xl">+ Add Load</button>
                                </div>
                                <table id="datatable-buttons-paid"
                                    class="table table-striped table-bordered dt-responsive nowrap"
                                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Load #</th>
                                            <th>Invoice #</th>
                                            <th>Invoice Date</th>
                                            <th>W/O #</th>
                                            <th>Customer #</th>
                                            <th>Load Create Date</th>
                                            <th>Shipper Date</th>
                                            <th>Delivered Date</th>
                                            <th>Carrier</th>
                                            <th>Pickup Location</th>
                                            <th>Unloading Location</th>
                                            <th>Actual Del Date</th>
                                            <th>Load Status</th>
                                            <th>Cus Rate</th>
                                            <th>Carrier Rate</th>
                                            <th>Aging</th>
                                            <th>Margin</th>
                                            <th>Margin %</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>


                                    <tbody id="paid_search">
                                        @include('broker.loads.invoice_paid')
                                    </tbody>
                                </table>
                                <div class="custom-pagination">
                                    {{ $invoice_paid->setPageName('invoice_paid')->links('pagination::bootstrap-5') }}
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div> <!-- end col -->
        </div> <!-- end row -->
    </div>
    <!-- End Page-content -->



    <!--  Modal content for the above example -->
    <div class="modal fade bs-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl" style="max-width:90% !important;">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="{{ route('load.create') }}" id="myFormLoad"
                        enctype="multipart/form-data">
                        @csrf
                        <div id="credit-limit-message" class="alert alert-warning d-none mb-3"></div>
                        <div class="card-header">
                            <h3 class="card-title"
                                style="font-size: 18px;text-align: left;font-weight: 700;margin-left: 0;">Add Load</h3>
                        </div>
                        <div class="card-body text-left">
                            <div class="row">
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>Load Number
                                        </label>
                                        <input class="form-control" name="load_number" title="Load number generated automatically" readonly style="width: 100%;">
                                    </div>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>Bill To <code>*</code></label>
                                        <select id="load_bill_to" class="form-control mySelect2" name="load_bill_to" data-placeholder="Select Customer">
                                            <option value="">Select Customer</option>
                                            @foreach($customer as $cust)
                                            <option value="{{$cust->customer_name}}"
                                                data-customer-id="{{$cust->id}}"
                                                data-available-credit="{{ (float) ($availableCredits[$cust->id] ?? 0) }}"
                                                data-remaining-credit="{{ (float) ($cust->remaining_credit ?? 0) }}"
                                                data-invoice-credit-limit="{{ (float) ($cust->invoice_credit_limit ?? 0) }}">
                                                {{$cust->customer_name}}
                                            </option>
                                            @endforeach
                                        </select>
                                        <input type="hidden" id="customer_id" name="customer_id" value="" >
                                    </div>
                                </div>


                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>Dispatcher <code>*</code></label>
                                        
                                        <input class="form-control" name="load_dispatcher" required readonly
                                            style="width: 100%;" value="{{Auth::user()->name}}">
                                    </div>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>CMT Agent</label>
                                        
                                        <select class="form-control" name="cmt_agent" required readonly
                                            style="width: 100%;">
                                            <option value="None">None</option>
                                            <option value="Rachel">Rachel</option>
                                            <option value="Amelia">Amelia</option>
                                        </select>
                                </div>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>Status</label>
                                        <input class="form-control" name="load_status" id="load_status" value="Open"
                                            readonly style="width: 100%;">
                                    </div>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>W/O # </label>
                                        <input class="form-control" name="load_workorder" style="width: 100%;"
                                            autocomplete="off">
                                    </div>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>Customer r/f# </label>
                                        <input class="form-control" name="customer_refrence_number" style="width: 100%;"
                                            autocomplete="off">
                                    </div>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>Payment Type
                                            <code>*</code></label>
                                        <select class="form-control" required name="load_payment_type"
                                            style="width: 100%;">
                                            <option value="">Select Status</option>
                                            <option>Prepaid</option>
                                            <option>Postpaid</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>Load type<code>*</code></label>
                                        <div class="purple">
                                            <select class="form-control" required name="load_type_two"
                                                style="width: 100%;">
                                                <option value="">
                                                    Select Status
                                                </option>
                                                <option value="OTR">OTR</option>
                                                <option  value="DRAYAGE">DRAYAGE</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>Shipment Type<code>*</code></label>
                                        <select class="form-control" required name="load_type" style="width: 100%;">
                                            <option value="">Select Shipment Type</option>
                                            @foreach($shipmentType as $shipment)
                                            <option value="{{$shipment->name}}">{{$shipment->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>Currency</label>
                                        <select class="form-control" name="load_currency" style="width: 100%;">
                                            <option selected="selected">Select
                                                Currency
                                            </option>
                                            <option>$</option>
                                            <option>CAD</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="form-group">
                                        <label>Equipment Type
                                            <code>*</code></label>
                                        <select class="form-control mySelect2" name="load_equipment_type"
                                            id="load_equipment_type" style="width: 100%;" required data-placeholder="Select Equipment">

                                            <option value="">Select Equipment </option>
                                            @foreach($equipmentType as $equipment)
                                            <option value="{{$equipment->name}}">{{$equipment->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="card-header">
                            <h3 class="card-title" style="font-size: 16px; text-align: left; font-weight: 700; margin-left: 0; font-family: 'Poppins';">
                                Shipper/Customer </h3>
                        </div>
                         <div class="card-body row" id="customers">
                            <div class="col-md-3 mb-2">
                                    <div class="form-group" id="shipper_rate_div">
                                        <label>Shipper Rate
                                            <code>*</code></label>
                                        <input type="number" class="form-control number value" name="load_shipper_rate"
                                            autocomplete="off" id="load_shipper_rate" required style="width: 100%;">
                                        <!-- <input type="text" class="form-control number value" id="load_shipper_rate" name="load_shipper_rate"> -->
                                        <span id="error_load_shipper_rate"
                                            style="color: red; font-size: 9px !important; display: none;">Only numbers
                                            and decimals allowed</span>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="form-group">
                                        <label>F.S.C Rate % <input hidden type="checkbox"
                                                name="calculate_fsc_percentage" id="calculate_fsc_percentage"></label>
                                        <input class="form-control number percent" name="load_fsc_rate"
                                            autocomplete="off" id="load_fsc_rate" style="width: 100%;">
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="form-group">
                                        <label class="other_charge d-flex">Customer Other Charges &nbsp; <i
                                                class="fa fa-plus" style="color: #0c7ce6;" data-toggle="modal"
                                                data-target="#myModal" id="load_shipper_other_charges"></i>
                                        </label>
                                        <input id="totalChargeAmount" class="form-control number percent"
                                            style="width: 100%;" readonly>
                                    </div>

                                    <div class="modal close_shipper_other_charges_form p-0" id="myModal">
                                        <div class="modal-dialog" style="max-width: 700px;">
                                            <div class="modal-content">

                                                <!-- Modal Header -->
                                                <div class="modal-header">
                                                    <h4 class="card-header"
                                                        style="font-size: 17px;text-align: left;font-weight: 700;">
                                                        Customer Other Charges</h4>
                                                    <button type="button" class="close close-modal-btn"
                                                        style="font-size: 23px;top: 30px;">&times;</button>
                                                </div>
                                                <!-- Modal Body -->
                                                <div class="modal-body pt-0">
                                                    <div class="container">
                                          <div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="shipperchargeType">Charge Type:</label>
            <input type="text" class="form-control"
                name="shipperchargeType[]"
                placeholder="Enter Charge Type">
        </div>
    </div>

    <div class="col-md-2 for-invoice-field" style="display: none;">
        <div class="form-group mt-3">
            <label>For Invoice:</label><br>
            <input type="checkbox"
                class="form-check-input for_invoice">
            <input type="hidden" class="for_invoice_flag"
                name="for_invoice[]" value="off">
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label>Amount:</label>
            <input type="text"
                class="form-control shipperchargeAmount"
                name="shipperchargeAmount[]"
                placeholder="Enter Amount">
        </div>
    </div>

    <div class="col-md-2" style="margin-top: 27px;">
        <a type="button" class="remove-charge"
            name="shipperchargeAmountdelete[]">
            <i class="fa fa-trash"
                style="color:red;margin-top: 19px;"
                aria-hidden="true"></i>
        </a>
    </div>
</div>

                               <div class="row" id="chargeRowTemplate" style="display: none;">
    <div class="col-md-4" style="margin-top:20px;">
        <div class="form-group">
            <input type="text" class="form-control"
                name="shipperchargeType[]"
                placeholder="Enter Charge Type">
        </div>
    </div>

    <div class="col-md-2 for-invoice-field" style="display: none; margin-top:20px;">
        <div class="form-group">
            <input type="checkbox"
                class="form-check-input for_invoice">
            <input type="hidden" class="for_invoice_flag"
                name="for_invoice[]" value="off">
        </div>
    </div>

    <div class="col-md-4" style="margin-top:20px;">
        <div class="form-group">
            <input type="text"
                class="form-control shipper_other_charge"
                name="shipperchargeAmount[]"
                placeholder="Enter Amount">
        </div>
    </div>

    <div class="col-md-2" style="margin-top:17px;">
        <a type="button" class="remove-charge"
            name="shipperchargeAmountdelete[]">
            <i class="fa fa-trash"
                style="color:red;margin-top:19px;"
                aria-hidden="true"></i>
        </a>
    </div>
</div>
                                                    </div>
                                                </div>
                                                <div class="text-center mb-2 mt-2">
                                                    <button type="button" class="btn btn-success" id="addChargeBtn">Add
                                                        More Charges</button>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="form-group">
                                        <label>Final Shipper Rate <code>*</code></label>
                                        <input type="text" class="readonly form-control" name="shipper_load_final_rate"
                                            autocomplete="off" id="shipper_load_final_rate"
                                            style="background-color:#e9ecef;" required>
                                        <p id="creditlimitcheck"></p>
                                    </div>
                                </div>
                        </div>
                         <div class="card-header">
                            <h3 class="card-title" style="font-size: 16px; text-align: left; font-weight: 700; margin-left: 0; font-family: 'Poppins';">
                                Carrier </h3>
                        </div>
                         <div class="card-body row" id="carrier">
                            <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>Carrier <code>*</code></label>
                                        <input type="text" id="load_carrier" name="load_carrier" class="form-control"
                                            style="width: 100%;" autocomplete="off" placeholder="Select carrier">
                                        <input type="hidden"  name="carrier_id" id="carrier_id">
                                        <!-- Dropdown to show the carrier suggestions -->
                                        <ul id="carrier-list" class="list-group"
                                            style="position: absolute; z-index: 1000; width: 100%; display: none;"></ul>
                                    </div>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>MC No <code>*</code></label>
                                        <input class="form-control" required name="load_mc_no" id="carrier_mc_ff_input"
                                            style="width: 100%;" placeholder="Enter MC Number" autocomplete="off">
                                    </div>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>DOT No</label>
                                        <input class="form-control" name="carrier_dot" id="carrier_dot"
                                            style="width: 100%;" placeholder="Enter DOT Number" autocomplete="off">
                                    </div>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>Carrier Phone<code>*</code></label>
                                        <input type="text" id="load_carrier_phone" name="load_carrier_phone"
                                            class="form-control" style="width: 100%;" readonly>
                                    </div>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>Advance Payment</label>
                                        <input type="number" class="form-control" name="load_advance_payment"
                                            autocomplete="off" style="width: 100%;">
                                    </div>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>Billing Type</label>
                                        <select class="form-control" name="load_billing_type" style="width: 100%;">
                                            <option selected="selected">Select
                                                Billing
                                            </option>
                                            <option>Factoring</option>
                                            <option>Direct Billing</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>Carrier Fee
                                            <code>*</code></label>
                                        <!-- <input class="form-control" type="number" name="load_carrier_fee"
                                    id="load_carrier_fee" required style="width: 100%;"> -->
                                        <input type="text" class="form-control" id="load_carrier_fee"
                                            name="load_carrier_fee" required autocomplete="off">
                                        <span id="error_load_carrier_fee"
                                            style="color: red;font-size: 9px !important; display: none;">Only numbers
                                            and decimals allowed</span>
                                    </div>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>FSC Rate %</label>
                                        <input type="number" name="load_billing_fsc_rate" id="load_billing_fsc_rate"
                                            class="form-control" autocomplete="off" style="width: 100%;">
                                    </div>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label class="other_charge">Carrier Other Charges <i class="fa fa-plus"
                                                style="color: #0c7ce6;" id="load_other_charges" data-toggle="modal"
                                                data-target="#otherChargesModal"></i></label>
                                        <input class="form-control" type="number" id="totalShipperOtherChgarges" readonly name="load_other_charge"
                                            style="width: 100%;">
                                    </div>
                                    <!-- Modal -->
                                    <div class="modal" id="otherChargesModal">
                                        <div class="modal-dialog" style="max-width: 700px;">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h4 style="font-size: 17px;text-align: left;font-weight: 700;">
                                                        Carrier Other Charges</h4>
                                                    <button type="button" class="close close-modal-btn"
                                                        style="font-size: 23px;top: 30px;">&times;</button>
                                                </div>
                                                <div class="modal-body pt-0">
                                                    <div id="inputs">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label for="shipperchargeType">Charge Type:</label>
                                                                    <input class="w-100 form-control" type="text"
                                                                        name="shipper_type_charge[]"
                                                                        placeholder="Enter Charges Type">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-5">
                                                                <div class="form-group">
                                                                    <label>Amount:</label>
                                                                    <input class="w-100 form-control shipper_other_charge" type="text"
                                                                        name="shipper_other_charge[]"
                                                                        placeholder="Enter Amount" />
                                                                </div>
                                                            </div>
                                                            <div class="col-md-1" style="margin-top: 27px;">
                                                                <button type="button"
                                                                    style="color:red;border: none;background: unset;"
                                                                    class="remove-charge"
                                                                    name="shipperchargeAmountdelete[]">
                                                                    <i class="fa fa-trash"
                                                                        style="color:red;margin-top: 19px;"
                                                                        aria-hidden="true"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="text-center mb-2 mt-2">
                                                        <button type="button" class='create-input btn btn-success'>Add More
                                                            Charges</button>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <div class="form-group">
                                        <label>Final Carrier Fee</label>
                                        <input class="form-control" readonly name="load_final_carrier_fee"
                                            id="load_final_carrier_fee" style="width: 100%;">
                                    </div>
                                </div>
                        </div>
                        <div class="card-header">
                            <h3 class="card-title"
                                style="font-size: 16px; text-align: left; font-weight: 700; margin-left: 0; font-family: 'Poppins';">
                                Shipper <i id="addBtn" class="fa fa-plus"></i> <a href="{{route('shipper')}}" target="_blank" style="font-size: 12px;">Add Shipper</a></h3>
                        </div>
                        <div class="card-body" id="shipperForms">
                            <ul class="nav nav-tabs" id="navTabs">
                                <li class="nav-item">
                                    <a class="nav-link active" style="padding: 1px 11px; border-radius: 10px;"
                                        id="formTab1" data-bs-toggle="tab" href="#shipperForm1" role="tab"
                                        aria-controls="shipperForm1" aria-selected="true">Shipper 1</a>
                                </li>
                            </ul>
                            <div class="tab-content" id="tabContent">
                                <div class="tab-pane fade show active" id="shipperForm1" role="tabpanel"
                                    aria-labelledby="formTab1">
                                    <div class="row shipper-form">
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Shipper <code>*</code>
                                                    <!-- <a href="" target="blank" style="background: none; border: none;">
                                                        <i class="fa fa-plus"></i>Add New
                                                    </a> -->
                                                </label>
                                                <input class="form-control load_shipper" name="load_shipper" id="load_shipper"
                                                    required autocomplete="off" style="width: 100%;">
                                                <span class="customerErrorMessage"
                                                    style="color: red; display: none;">Select Shipper From the
                                                    List</span>
                                                <div id="shipperList" class="form-control shipperList" style="display: none;"
                                                    readonly></div>

                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Shipper Location</label>
                                                <input class="form-control load_shipper_location" readonly name="load_shipper_location"
                                                    id="load_shipper_location" autocomplete="off" style="width: 100%;">
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Pickup Date Appointment <code>*</code></label>
                                                <input class="form-control load_shipper_appointment" type="datetime-local" name="load_shipper_appointment" style="width: 100%;" required>
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Description</label>
                                                <input class="form-control load_shipper_description" name="load_shipper_description"
                                                    autocomplete="off" style="width: 100%;">
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Commodity Type</label>
                                                <input class="form-control load_shipper_commodity_type" name="load_shipper_commodity_type"
                                                    style="width: 100%;" autocomplete="off">
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Commodity Name <code>*</code></label>
                                                <input class="form-control load_shipper_commodity" id="load_shipper_commodity"
                                                    name="load_shipper_commodity" autocomplete="off" type="text" required
                                                     style="width: 100%;">
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Qty</label>
                                                <input type="number load_shipper_qty" class="form-control" autocomplete="off"
                                                    name="load_shipper_qty" id="load_shipper_qty" style="width: 100%;">
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Weight (lbs)</label>
                                                <input class="form-control load_shipper_weight" type="number" autocomplete="off"
                                                    name="load_shipper_weight" id="load_shipper_weight"
                                                    style="width: 100%;">
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Value($)<code>*</code></label>
                                                <input type="number" class="form-control load_shipper_value" id="load_shipper_value"
                                                    autocomplete="off" name="load_shipper_value" required
                                                    style="width: 100%;">
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Shipping Notes</label>
                                                <input class="form-control load_shipper_shipping_notes" name="load_shipper_shipping_notes"
                                                    autocomplete="off" style="width: 100%;">
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>PO Numbers</label>
                                                <input class="form-control load_shipper_po_numbers" name="load_shipper_po_numbers"
                                                    autocomplete="off" style="width: 100%;">
                                            </div>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <div class="form-group">
                                                <label>Contact Number</label>
                                                <input class="form-control load_shipper_contact" type="number" autocomplete="off"
                                                    name="load_shipper_contact" style="width: 100%;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-header pb-0">
                            <h3 class="card-title"
                                style="font-size: 16px; text-align: left; font-weight: 700; margin-left: 0; font-family: 'Poppins';">
                                Consignee <i id="addBtnconsignee" class="fa fa-plus"></i> <a href="{{route('Consignee')}}" target="_blank" style="font-size: 12px;">Add Consignee</a>
                            </h3>
                        </div>
                        <div class="card-body1" id="consigneeSections">
                            <ul class="nav nav-tabs" id="navTabs1">
                                <li class="nav-item">
                                    <a class="nav-link active" style="padding: 1px 11px; border-radius: 10px;"
                                        id="formTab1" data-bs-toggle="tab" href="#consigneeSections1" role="tab"
                                        aria-controls="consigneeSections1" aria-selected="true">Consignee 1</a>
                                </li>
                            </ul>
                            <div class="tab-content" id="tabContent1">
                                <div class="tab-pane fade show active" id="consigneeSections1" role="tabpanel"
                                    aria-labelledby="formTab1">
                                    <div class="consignee-section mt-3">
                                        <div class="row">
                                            <div class="col-md-2 mb-2">
                                                <div class="form-group">
                                                    <label>Consignee <code>*</code>
                                                        <!-- <a href="" target="blank"
                                                            style="background: none; border: none; font-size:13px;">
                                                            <i class="fa fa-plus"></i>Add New
                                                        </a> -->
                                                    </label>
                                                    <input class="form-control load_consignee" name="load_consignee" autocomplete="off"
                                                        id="load_consignee" required style="width: 100%;">
                                                    <span class="customerErrorMessage"
                                                        style="color: red; display: none;">Select Consignee From the
                                                        List</span>
                                                    <div id="consigneeList" class="form-control consigneeList" style="display: none;"
                                                        readonly></div>

                                                </div>
                                            </div>
                                            <div class="col-md-2 mb-2">
                                                <div class="form-group">
                                                    <label>Consignee Location</label>
                                                    <input class="form-control load_consignee_location" name="load_consignee_location"
                                                        autocomplete="off" id="load_consignee_location"
                                                        style="width: 100%;" readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-2 mb-2">
                                                <div class="form-group">
                                                    <label>Delivery Date Appointment <code>*</code></label>
                                                    <input class="form-control load_consignee_appointment" type="datetime-local" required name="load_consignee_appointment" style="width: 100%;">
                                                </div>
                                            </div>
                                            <div class="col-md-2 mb-2">
                                                <div class="form-group">
                                                    <label>Description</label>
                                                    <input class="form-control load_consignee_description" autocomplete="off"
                                                        name="load_consignee_description" style="width: 100%;">
                                                </div>
                                            </div>
                                            <div class="col-md-2 mb-2">
                                                <div class="form-group">
                                                    <label>Commodity Type </label>
                                                    <input class="form-control load_consignee_type" name="load_consignee_type"
                                                        autocomplete="off" style="width: 100%;">
                                                </div>
                                            </div>
                                            <div class="col-md-2 mb-2">
                                                <div class="form-group">
                                                    <label>Commodity Name <code>*</code></label>
                                                    <input class="form-control load_consignee_commodity" name="load_consignee_commodity"
                                                        id="load_consignee_commodity" autocomplete="off" type="text" required style="width: 100%;">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-2 mb-2">
                                                <div class="form-group">
                                                    <label>Qty</label>
                                                    <input type="number" class="form-control load_consignee_qty" name="load_consignee_qty"
                                                       id="load_consignee_qty"  autocomplete="off" style="width: 100%;">
                                                </div>
                                            </div>
                                            <div class="col-md-2 mb-2">
                                                <div class="form-group">
                                                    <label>Weight (lbs)</label>
                                                    <input class="form-control load_consignee_weight" type="number" id="load_consignee_weight"
                                                        autocomplete="off" name="load_consignee_weight"
                                                        style="width: 100%;">
                                                </div>
                                            </div>
                                            <div class="col-md-2 mb-2">
                                                <div class="form-group">
                                                    <label>Value($)<code>*</code></label>
                                                    <input type="number" class="form-control load_consignee_value"
                                                        name="load_consignee_value" autocomplete="off"
                                                        id="load_consignee_value" required style="width: 100%;">
                                                </div>
                                            </div>
                                            <div class="col-md-2 mb-2">
                                                <div class="form-group">
                                                    <label>Consignee
                                                        Notes</label>
                                                    <textarea class="form-control load_consignee_notes" name="load_consignee_notes"
                                                        autocomplete="off"
                                                        style="width: 100%; height: 31px !important;font-size: 12px;"></textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-2 mb-2">
                                                <div class="form-group">
                                                    <label>PO Numbers</label>
                                                    <input class="form-control load_consignee_po_numbers" name="load_consignee_po_numbers"
                                                        autocomplete="off" style="width: 100%;">
                                                </div>
                                            </div>
                                            <div class="col-md-2 mb-2">
                                                <div class="form-group">
                                                    <label>Contact Number</label>
                                                    <input class="form-control load_consignee_contact" type="number" autocomplete="off"
                                                        name="load_consignee_contact" style="width: 100%;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="modal-footer">
                            <input type="submit" class="btn btn-info keep-enabled" value="Save">
                            <input type="reset" style="font-size:14px !important;" class="btn btn-warning keep-enabled"
                                id="clearFormButton" Value="Clear Form">
                            <input type="button" class="btn btn-danger keep-enabled" data-bs-dismiss="modal" value="Cancel">
                        </div>
                    </form>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
	
	<script>
   $(document).on('click', '.custom-pagination a', function(e) {
    e.preventDefault();

    let href = $(this).attr('href');
    if (!href) return;

    // Get active tab (without the #)
    let activeTab = $('.nav-link.active').attr('href');
        let resultContainer = '';
        let tableSelector = '';
        let pageName = '';

        if (activeTab === '#all') {
            resultContainer = '#all_search'; pageName = 'all_loads';
            tableSelector = '#datatable-buttons-all';
        } else if (activeTab === '#open') {
            resultContainer = '#open_search'; pageName = 'open';
            tableSelector = '#datatable-buttons-open';
        } else if (activeTab === '#delivered') {
            resultContainer = '#delivered_search'; pageName = 'delivered';
            tableSelector = '#datatable-buttons-delivered';
        } else if (activeTab === '#complete') {
            resultContainer = '#complete_search'; pageName = 'complete';
            tableSelector = '#datatable-buttons-complete';
        } else if (activeTab === '#invoice') {
            resultContainer = '#invoice_search'; pageName = 'invoice';
            tableSelector = '#datatable-buttons-invoice';
        } else if (activeTab === '#paid') {
            resultContainer = '#paid_search'; pageName = 'invoice_paid';
            tableSelector = '#datatable-buttons-paid';
        } else {
            return; // Exit if it's not one of the expected tabs
        }
    let pageUrl = new URL(href, window.location.origin);
    let pageNumber = pageUrl.searchParams.get(pageName) || '1';

    $.ajax({
        url: '/broker/load',
        type: 'GET',
        data: {
            tab: activeTab,
            page: pageNumber,
            [pageName]: pageNumber
        },
        success: function(data) {
            if ($.fn.DataTable.isDataTable(tableSelector)) {
                $(tableSelector).DataTable().destroy();
            }

            $(resultContainer).html(data);

            $(tableSelector).DataTable({
                responsive: true,
                fixedHeader: true,
                dom: 'Bfrtip',
                search: false,
                info: false,
                paging: false,
                pageLength: 50,
				        stateSave: true,   // ✅ remembers column visibility, page, search, etc.
        buttons: [
            {
                extend: 'colvis',
                text: 'Select Columns'
            }
        ]
            });

            window.history.replaceState({}, '', '/broker/load');
        }
    });
</script>
    <script>
      
   
  $(document).on('change', '.load_status', function() {
    
      var load_id = $(this).data('load-id');
      var load_status = $(this).val();
	  if(load_status == "Completed"){
		  $(this).prop('disabled', true);
	  }

      $.ajax({
          url: '/broker/change-load-status', // Change this to your actual endpoint
          method: 'POST',
          data: {
              load_id: load_id,
              load_status: load_status
          },
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // If using Laravel or CSRF
          },
          success: function (response) {
              console.log('Success:', response);
			  const $row = $('.load_status'+load_id).closest('tr');
				if ($row.length) {
					$row.remove(); // remove the row or update as needed
				}
               $('#mc-success-message').text(response.message).fadeIn();
               location.reload();

              // Hide after 10 seconds
              setTimeout(function() {
                  $('#mc-success-message').text('').fadeOut();
              }, 1000);
			$('#edit_btn').hide();
          },
          error: function (xhr, status, error) {
              console.error('Error:', error);
              $('#mc-error-message').text(error).fadeIn();

              // Hide after 10 seconds
              setTimeout(function() {
                  $('#mc-error-message').text('').fadeOut();
              }, 1000); 
          }
      });
  });


        function formatCreditAmount(value) {
            var numericValue = Number(value);
            if (!isFinite(numericValue)) {
                return '$0.00';
            }
            return '$' + numericValue.toFixed(2);
        }

        // Note: customer other charges are deliberately left alone here. A charge marked
        // For Invoice is drawn from the invoicing limit, so a credit failure must not wipe
        // what the user typed - the message and the disabled Save button are the feedback.
        function zeroRateFields() {
            $('#shipper_load_final_rate').val(0);
            $('#load_shipper_rate').val(0);
            $('#load_fsc_rate').val(0);
            $('#load_final_carrier_fee').val(0);
            $('#totalShipperOtherChgarges').val(0);
        }

        function getSelectedCustomerCreditLimit() {
            var $select = $('#load_bill_to');
            if (!$select.length) {
                return { remaining: 0, invoice: 0 };
            }

            var selectedOption = $select.find('option:selected');
            var availableCredit = parseFloat(selectedOption.data('available-credit')) || 0;
            var remainingCredit = parseFloat(selectedOption.data('remaining-credit')) || 0;
            var invoiceCreditLimit = parseFloat(selectedOption.data('invoice-credit-limit')) || 0;

            var remaining = availableCredit > 0 ? availableCredit : remainingCredit;

            return { remaining: remaining, invoice: invoiceCreditLimit };
        }

        function isTONU() {
            var shipmentType = $('[name="load_type"]').val();
            return shipmentType && shipmentType.toUpperCase() === 'TONU';
        }

        function getInvoiceChargesTotal() {
            var total = 0;
            $('[name="shipperchargeAmount[]"]').each(function (index) {
                var amount = parseFloat($(this).val()) || 0;
                var isInvoice = $('.for_invoice').eq(index).is(':checked');
                if (isInvoice) {
                    total += amount;
                }
            });
            return total;
        }

        function getNonInvoiceChargesTotal() {
            var total = 0;
            $('[name="shipperchargeAmount[]"]').each(function (index) {
                var amount = parseFloat($(this).val()) || 0;
                var isInvoice = $('.for_invoice').eq(index).is(':checked');
                if (!isInvoice) {
                    total += amount;
                }
            });
            return total;
        }

        function validateCreditForLoad() {
            var $form = $('#myFormLoad');
            var $select = $('#load_bill_to');
            var $rate = $('#shipper_load_final_rate');
            var $message = $('#credit-limit-message');
            var $submitButton = $form.find('input[type="submit"]');

            if (!$form.length || !$select.length || !$rate.length) {
                return true;
            }

            if (!$select.val()) {
                $message.text('').addClass('d-none');
                $submitButton.prop('disabled', false).removeClass('disabled').prop('title', 'Save');
                $form.data('credit-valid', true);
                return true;
            }

            var credits = getSelectedCustomerCreditLimit();
            var remainingLimit = credits.remaining;
            var invoiceLimit = credits.invoice;
            var enteredAmount = parseFloat($rate.val()) || 0;

            if (enteredAmount < 0) {
                $message.removeClass('alert-warning alert-success').addClass('alert-danger')
                    .text('Shipper rate cannot be negative.').removeClass('d-none');
                $submitButton.prop('disabled', true).addClass('disabled');
                $form.data('credit-valid', false);
                return false;
            }

            if (enteredAmount > 0 && enteredAmount < 200) {
                var validationMessage = 'Final shipper rate is not less than 200.';

                $message.removeClass('alert-warning alert-success').addClass('alert-danger')
                    .text(validationMessage).removeClass('d-none');
                $submitButton.prop('disabled', true).addClass('disabled').prop('title', validationMessage);
                $form.data('credit-valid', false);
                return false;
            }

            // TONU: Only uses invoice credit limit (no base rate needed)
            if (isTONU()) {
                var invoiceCharges = getInvoiceChargesTotal();
                if (invoiceLimit <= 0) {
                    $message.removeClass('alert-warning alert-success').addClass('alert-danger')
                        .text('You do not have invoicing limit. Cannot create TONU load.').removeClass('d-none');
                    $submitButton.prop('disabled', true).addClass('disabled');
                    zeroRateFields();
                    $form.data('credit-valid', false);
                    return false;
                }
                if (invoiceCharges > invoiceLimit) {
                    $message.removeClass('alert-warning alert-success').addClass('alert-danger')
                        .text('Insufficient invoicing limit. Your invoicing limit is ' + formatCreditAmount(invoiceLimit) + '. You entered ' + formatCreditAmount(invoiceCharges) + '.')
                        .removeClass('d-none');
                    $submitButton.prop('disabled', true).addClass('disabled');
                    $('#shipper_load_final_rate').val(0);
                    $form.data('credit-valid', false);
                    return false;
                }
                $message.removeClass('alert-danger').addClass('alert-warning')
                    .text('TONU - Invoice limit: ' + formatCreditAmount(invoiceLimit) + '. Used: ' + formatCreditAmount(invoiceCharges) + '.')
                    .removeClass('d-none');
                $submitButton.prop('disabled', false).removeClass('disabled').prop('title', 'Save');
                $form.data('credit-valid', true);
                return true;
            }

            // Non-TONU: Base rate + non-invoice charges use remaining limit
            //           Invoice-checked charges use invoice limit
            var baseRate = parseFloat($('#load_shipper_rate').val()) || 0;
            var fscRate = parseFloat($('#load_fsc_rate').val()) || 0;
            var fscAmount = (fscRate / 100) * baseRate;
            var nonInvoiceCharges = getNonInvoiceChargesTotal();
            var invoiceCharges = getInvoiceChargesTotal();

            // Only For Invoice=checked charges are deducted from the invoicing limit.
            // Base rate, F.S.C and non-invoice charges must fit inside the remaining limit -
            // the invoicing limit is never used to cover a shortfall there.
            var remainingUsed = baseRate + fscAmount + nonInvoiceCharges;
            var totalInvoiceUsed = invoiceCharges;

            // Check remaining credit limit (base rate + non-invoice charges)
            if (remainingLimit <= 0 && invoiceLimit <= 0) {
                $message.removeClass('alert-warning alert-success').addClass('alert-danger')
                    .text('No remaining credit limit available.').removeClass('d-none');
                $submitButton.prop('disabled', true).addClass('disabled');
                zeroRateFields();
                $form.data('credit-valid', false);
                return false;
            }

            // The remaining limit has to cover base rate + F.S.C + non-invoice charges on its own
            if (remainingUsed > remainingLimit) {
                var shortageAmount = remainingUsed - remainingLimit;
                $message.removeClass('alert-warning alert-success').addClass('alert-danger')
                    .text('Amount (' + formatCreditAmount(remainingUsed) + ') exceeds remaining credit limit (' + formatCreditAmount(remainingLimit) + '). You need ' + formatCreditAmount(shortageAmount) + ' more credits.')
                    .removeClass('d-none');
                $submitButton.prop('disabled', true).addClass('disabled');
                $('#shipper_load_final_rate').val(0);
                $form.data('credit-valid', false);
                return false;
            }

            // Check invoice credit limit (For Invoice=checked charges only)
            if (totalInvoiceUsed > invoiceLimit) {
                var shortageAmount = totalInvoiceUsed - invoiceLimit;
                $message.removeClass('alert-warning alert-success').addClass('alert-danger')
                    .text('Insufficient invoicing limit. Your invoicing limit is ' + formatCreditAmount(invoiceLimit) + '. You need ' + formatCreditAmount(shortageAmount) + ' more credits.')
                    .removeClass('d-none');
                $submitButton.prop('disabled', true).addClass('disabled');
                $('#shipper_load_final_rate').val(0);
                $form.data('credit-valid', false);
                return false;
            }

            // All good - show only what is left on each limit after this load
            var deductionSummary = 'Available: ' + formatCreditAmount(remainingLimit - remainingUsed) + ' | Invoicing Limit: ' + formatCreditAmount(invoiceLimit - totalInvoiceUsed);

            $message.removeClass('alert-danger').addClass('alert-warning')
                .text(deductionSummary)
                .removeClass('d-none');

            // Log detailed calculations to console for background tracking
            console.log('Credit Limit Check:', {
                'Base Rate': formatCreditAmount(baseRate),
                'FSC Rate %': parseFloat(fscRate || 0).toFixed(1) + '%',
                'FSC Amount': formatCreditAmount(fscAmount),
                'Invoice Charges (For Invoice=checked)': formatCreditAmount(invoiceCharges),
                '  → Deducted from Invoice Limit': formatCreditAmount(invoiceCharges),
                'Non-Invoice Charges (For Invoice=unchecked)': formatCreditAmount(nonInvoiceCharges),
                '  → Deducted from Remaining Limit': formatCreditAmount(nonInvoiceCharges),
                'Total Used from Remaining': formatCreditAmount(remainingUsed),
                'Total Used from Invoice Limit': formatCreditAmount(totalInvoiceUsed),
                'Remaining Available': formatCreditAmount(remainingLimit - remainingUsed),
                'Invoice Limit Available': formatCreditAmount(invoiceLimit - totalInvoiceUsed),
                'Remaining Limit': formatCreditAmount(remainingLimit),
                'Invoice Limit': formatCreditAmount(invoiceLimit)
            });
            $submitButton.prop('disabled', false).removeClass('disabled').prop('title', 'Save');
            $form.data('credit-valid', true);
            return true;
        }

        function toggleLoadFormByCredit() {
            var $form = $('#myFormLoad');
            var $select = $('#load_bill_to');
            var $message = $('#credit-limit-message');

            if (!$form.length || !$select.length) {
                return;
            }

            var creditInfo = getSelectedCustomerCreditLimit();
            var availableLimit = Number(creditInfo.remaining) || 0;
            var invoiceLimit = Number(creditInfo.invoice) || 0;
            var shouldLock = !!$select.val() && availableLimit <= 0;

            $form.data('credit-locked', shouldLock);

            $form.find(':input')
                .not($select)
                .not('[type="hidden"]')
                .each(function () {
                    $(this).prop('disabled', shouldLock);
                });

            $form.find('input[type="submit"], input[type="reset"], input[type="button"], button').prop('disabled', shouldLock);

            if (shouldLock) {
                zeroRateFields();
                $message
                    .removeClass('alert-warning alert-success')
                    .addClass('alert-danger')
                    .text('You do not have sufficient limit to create this load.')
                    .removeClass('d-none');
            } else if ($select.val()) {
                $message
                    .removeClass('alert-danger')
                    .addClass('alert-warning')
                    .text('Available limit: ' + formatCreditAmount(availableLimit) + ' | Invoicing limit: ' + formatCreditAmount(invoiceLimit) + '.')
                    .removeClass('d-none');
            } else {
                $message.text('').addClass('d-none');
            }

            $select.prop('disabled', false);
        }

        $(document).ready(function () {
			// Bind the change event
			$('#load_bill_to').on('change', function () {
                var selectedOption = $(this).find('option:selected').data('customer-id');
                $('#customer_id').val(selectedOption || '');
				$('#load_shipper_rate').prop('readonly', false).val(0);
                $('#shipper_load_final_rate').val('');
                toggleLoadFormByCredit();
                validateCreditForLoad();
			});

            $('#shipper_load_final_rate').on('input change', function () {
                validateCreditForLoad();
            });

            // Re-validate when shipment type changes (TONU logic)
            $('[name="load_type"]').on('change', function () {
                validateCreditForLoad();
            });

            // Re-validate when "For Invoice" checkbox is toggled, and keep the submitted
            // hidden flag in sync so the server receives one on/off value per charge row,
            // in the same order as the amount inputs.
            $(document).on('change', '.for_invoice', function () {
                $(this).closest('.row').find('.for_invoice_flag').val(this.checked ? 'on' : 'off');
                validateCreditForLoad();
            });

            $('#myFormLoad').on('submit', function (e) {
                if ($(this).data('credit-locked') || !validateCreditForLoad()) {
                    e.preventDefault();
                }
            });

            toggleLoadFormByCredit();
            validateCreditForLoad();
		});
        $(document).ready(function () {

            function toggleForInvoiceFields() {
                const shipmentType = $('[name="load_type"] option:selected').text().trim().toUpperCase();
                const isTonu = shipmentType === 'TONU';

                $('.for-invoice-field').toggle(isTonu);
                if (!isTonu) {
                    $('.for_invoice').prop('checked', false);
                    $('.for_invoice_flag').val('off');
                }
            }

            $('[name="load_type"]').on('change', toggleForInvoiceFields);
            toggleForInvoiceFields();

            $('#load_shipper_other_charges').on('click', function () {
                $('#myModal').show();
            });

            $('#myModal .close-modal-btn').on('click', function () {
                $('#myModal').hide();
            });


        });  


        $(document).ready(function () {

            $('#load_other_charges').on('click', function () {
                $('#otherChargesModal').show();
            });

            $('#otherChargesModal .close-modal-btn').on('click', function () {
                $('#otherChargesModal').hide();
            });


        });



        /*****************shipper************************/
        $(document).ready(function () {
            $('#addBtn').on('click', function (e) {
                e.preventDefault();

                const liCount = $('#navTabs li').length;
                const nextcount = liCount + 1;

                // 1. Add new tab
                const newTab = `
            <li class="nav-item" role="presentation">
                <a class="nav-link" style="padding: 1px 11px; border-radius: 10px;" 
                   id="formTab${nextcount}" data-bs-toggle="tab" href="#shipperForm${nextcount}" 
                   role="tab" aria-controls="shipperForm${nextcount}" aria-selected="false">
                   Shipper ${nextcount}
				   <i class="fa fa-trash remove" data-id="shipperForm${nextcount}" style="margin-top: 1px;margin-left: 4px;"></i>
                </a>
            </li>
        `;
                $('#navTabs').append(newTab);

                // 2. Clone the first form and update IDs, names
                const newContent = $('#shipperForm1').clone();
                newContent.attr('id', `shipperForm${nextcount}`);
                newContent.attr('aria-labelledby', `formTab${nextcount}`);
                newContent.removeClass('show active'); // Ensure new tab pane is hidden by default

                // Update input field names and ids to prevent duplication
                newContent.find('input, select, textarea, div[id]').each(function () {
                    let name = $(this).attr('name');
                    let id = $(this).attr('id');
                    if (name) {
                        $(this).attr('name', name + '' + nextcount).val('');
                    }
                    if (id) {
                        $(this).attr('id', id + '' + nextcount).val('');
                    }
                });

                $('#tabContent').append(newContent);
				
				/****shipper code*****/
				
				function fetchShipperNames(query, parentId) {
					if (query.trim() !== '') {
						$.ajax({
							url: "{{ route('fetch.shipper.details') }}",
							method: "GET",
							data: { query: query },
							dataType: "json",
							success: function(response) {
								var html = '';
								if (response.error) {
									html = '<div class="item dropdown-item" style="border: none;padding: 4px 0;">' + response.error + '</div>';
								} else {
									response.forEach(function(shipper) {
										html += '<div class="item dropdown-item" style="border: none;padding: 4px 0;" ' +
											'data-name="' + shipper.shipper_name + '" ' +
											'data-address="' + shipper.shipper_address + '" ' +
											'data-city="' + shipper.shipper_city + '" ' +
											'data-state="' + shipper.shipper_state + '" ' +
											'data-country="' + shipper.shipper_country + '" ' +
											'data-zip="' + shipper.shipper_zip + '">' +
											shipper.shipper_name + '</div>';
									});
								}
								$('#'+parentId+' .shipperList').html(html).show();
							},
							error: function(xhr, status, error) {
								console.error(error);
							}
						});
					} else {
						$('#'+parentId+' .shipperList').html('').hide();
					}
				}

				$('.load_shipper').on('keyup', function() {
					var query = $(this).val();
					let parentId = $(this).closest('.tab-pane').attr('id');
					
					fetchShipperNames(query, parentId);

					// Clear the location field if shipper name is empty
					if (query.trim() === '') {
						$('#'+parentId+' .load_shipper_location').val('');
					}
				});

				// Listen for click event on shipper list items
				$(document).on('click', '.shipperList .item', function() {
					let parentId = $(this).closest('.tab-pane').attr('id');
					var selectedShipperName = $(this).data('name');
					var selectedShipperAddress = $(this).data('address');
					var selectedShipperCity = $(this).data('city');
					var selectedShipperState = $(this).data('state');
					var selectedShipperCountry = $(this).data('country');
					var selectedShipperZip = $(this).data('zip');

					var countryName = '';
					if (selectedShipperCountry) {
						var countryParts = selectedShipperCountry.split(' ');
						countryName = countryParts.slice(1).join(' ');
					}

					// Format the full address as "address, city, state, zip, country"
					var fullAddress = selectedShipperAddress + ', ' + selectedShipperCity + ', ' +
						selectedShipperState + ', ' + selectedShipperZip + (countryName ? ', ' + countryName : '');

					$('#'+parentId+' .load_shipper').val(selectedShipperName);
					$('#'+parentId+' .load_shipper_location').val(fullAddress);
					$('#'+parentId+' .shipperList').html('').hide(); // Clear the list
				});

				// Hide the dropdown when clicking outside
				$(document).on('click', function(event) {
					if (!$(event.target).closest('.shipperList, .load_shipper').length) {
						$('.shipperList').html('').hide();
					}
				});
				
				$(document).ready(function () {
					const ids = ['.load_consignee', '.load_shipper'];

					ids.forEach(function (id) {
						// Prevent copy, paste, and cut
						$(id).on('copy paste cut', function (e) {
							e.preventDefault();
							$('#mc-error-message').text('Cut is not allowed').fadeIn();

							  // Hide after 10 seconds
							  setTimeout(function() {
								  $('#mc-error-message').text('').fadeOut();
							  }, 1000);
						});
					});
				});
            });
        });



        /*****************consignee************************/
        $(document).ready(function () {
            $('#addBtnconsignee').on('click', function (e) {
                e.preventDefault();

                const liCount = $('#navTabs1 li').length;
                const nextcounts = liCount + 1;

                // 1. Add new tab
                const newTab = `
                <li class="nav-item" role="presentation">
                    <a class="nav-link" style="padding: 1px 11px; border-radius: 10px;" 
                    id="formTab${nextcounts}" data-bs-toggle="tab" href="#consigneeSections${nextcounts}" 
                    role="tab" aria-controls="consigneeSections${nextcounts}" aria-selected="false">
                    Consignee ${nextcounts}
                    <i class="fa fa-trash remove" data-id="consigneeSections${nextcounts}" style="margin-top: 1px;margin-left: 4px;"></i>
                    </a>
                </li>`;
                $('#navTabs1').append(newTab);

                // 2. Clone the first form and update IDs, names
                const newContent = $('#consigneeSections1').clone();
                newContent.attr('id', `consigneeSections${nextcounts}`);
                newContent.attr('aria-labelledby', `formTab${nextcounts}`);
                newContent.removeClass('show active'); // Ensure new tab pane is hidden by default

                // Update input field names and ids to prevent duplication
                newContent.find('input, select, textarea, div[id]').each(function () {
                    let name = $(this).attr('name');
                    let id = $(this).attr('id');
                    if (name) {
                        $(this).attr('name', name + '' + nextcounts).val('');
                    }
                    if (id) {
                        $(this).attr('id', id + '' + nextcounts).val('');
                    }
                });

                $('#tabContent1').append(newContent);
				
				/**********consignee add code*************/
				function fetchConsigneeNames(query, parentId) {
					if (query.trim() !== '') {
						$.ajax({
							url: "{{ route('fetch.consignee.details') }}",
							method: "GET",
							data: {
								query: query
							},
							dataType: "json",
							success: function (response) {
								var html = '';
								response.forEach(function (consignee) {
									html +=
										'<div class="item dropdown-item" style="border: none;padding: 4px 0;" data-name="' +
										consignee.consignee_name + '" data-address="' + consignee
										.consignee_address + '" data-city="' + consignee
										.consignee_city + '" data-state="' + consignee
										.consignee_state + '" data-country="' + consignee
										.consignee_country + '" data-zip="' + consignee
										.consignee_zip + '">' + consignee.consignee_name + '</div>';
								});
								$('#'+parentId+' .consigneeList').html(html).show();
							},
							error: function (xhr, status, error) {
								console.error(error);
							}
						});
					} else {
						$('.consigneeList').html('').hide();
					}
				}

				$('.load_consignee').on('keyup', function () {
					var query = $(this).val();
					let parentId = $(this).closest('.tab-pane').attr('id');
					fetchConsigneeNames(query, parentId);

					// Clear the location field if consignee name is empty
					if (query.trim() === '') {
						$('#'+parentId+' .load_consignee_location').val('');
					}
				});

				// Listen for click event on consignee list items
				$(document).on('click', '.consigneeList .item', function () {
					let parentId = $(this).closest('.tab-pane').attr('id');
					
					var selectedConsigneeName = $(this).data('name');
					var selectedConsigneeAddress = $(this).data('address');
					var selectedConsigneeCity = $(this).data('city');
					var selectedConsigneeState = $(this).data('state');
					var selectedConsigneeCountry = $(this).data('country');
					var selectedConsigneeZip = $(this).data('zip');

					var countryName = '';
					if (selectedConsigneeCountry) {
						var countryParts = selectedConsigneeCountry.split(' ');
						countryName = countryParts.slice(1).join(' ');
					}

					var fullAddress = selectedConsigneeAddress + ', ' + selectedConsigneeCity + ', ' +
						selectedConsigneeState + ', ' + selectedConsigneeZip + 
						(countryName ? ', ' + countryName : '');

					$('#'+parentId+' .load_consignee').val(selectedConsigneeName);
					$('#'+parentId+' .load_consignee_location').val(fullAddress);
					$('#'+parentId+' .consigneeList').html('').hide(); // Clear the list
				});



				// Hide the dropdown when clicking outside
				$(document).on('click', function (event) {
					if (!$(event.target).closest('.consigneeList, .load_consignee').length) {
						$('.consigneeList').html('').hide();
					}
				});
				
				$(document).ready(function () {
					const ids = ['.load_consignee', '.load_shipper'];

					ids.forEach(function (id) {
						// Prevent copy, paste, and cut
						$(id).on('copy paste cut', function (e) {
							e.preventDefault();
							$('#mc-error-message').text('Cut is not allowed').fadeIn();

							  // Hide after 10 seconds
							  setTimeout(function() {
								  $('#mc-error-message').text('').fadeOut();
							  }, 1000);
						});
					});
				});
            });
        });
   
$(document).ready(function () {
    setInterval(() => {
        $('.nav-item .remove').on('click', function() {
            var $parentLi = $(this).closest('li');
            var removeId = $(this).data('id');
            
            $parentLi.remove(); // Remove the parent <li>
            $('#' + removeId).remove(); // Remove the element with ID from data-id
        });
    }, 500);
    
});
</script>
<script>

        $(document).ready(function () {
            function updateTotalshipper() {
                var total = 0;

                $('[name="shipperchargeAmount[]"]').each(function (index, inputBox) {
                    var amount = parseFloat($(inputBox).val()) || 0;
                    total += amount;
                });

                $('#totalChargeAmount').val(total.toFixed(2));

                var loadShipperRate = parseFloat($('#load_shipper_rate').val()) || 0;
                total += loadShipperRate;

                var loadFscRate = parseFloat($('#load_fsc_rate').val()) || 0;
                total += (loadFscRate / 100) * loadShipperRate;

                // Always show the calculated total in final rate field
                $('#shipper_load_final_rate').val(total.toFixed(2));
                // Re-validate UI based on new totals (keeps calculated final rate visible)
                validateCreditForLoad();

                // Also send non-invoice portion to the server-side remaining-credit check
                var customer_id = $('#customer_id').val();

                // Calculate total of invoice-selected charges only
                var invoiceTotal = 0;
                $('.for_invoice').each(function(index, checkbox) {
                    var checked = $(checkbox).is(':checked');
                    if (checked) {
                        // Find corresponding amount input by index
                        var amountInput = $('[name="shipperchargeAmount[]"]').eq(index);
                        var val = parseFloat(amountInput.val()) || 0;
                        invoiceTotal += val;
                    }
                });

                // Non-invoice portion is validated against remaining credit, the For Invoice
                // portion against the invoicing limit.
                var nonInvoiceFinalRate = Math.max(0, total - invoiceTotal);

                $.ajax({
                    url: '{{ route('check.remaing.limit') }}',
                    method: 'GET',
                    data: {
                        customer_id: customer_id,
                        finalrate: total,
                        remaining_amount: nonInvoiceFinalRate,
                        invoice_amount: invoiceTotal,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#mc-error-message').text(response.message).fadeIn();

                            // Hide after 2 seconds
                            setTimeout(function() {
                                $('#mc-error-message').text('').fadeOut();
                            }, 2000);

                            // Block saving but keep what was typed. This check only covers the
                            // remaining limit; a For Invoice charge is paid out of the invoicing
                            // limit, so the customer other charges must not be cleared here.
                            var $loadForm = $('#myFormLoad');
                            $loadForm.data('credit-valid', false);
                            $loadForm.find('input[type="submit"]')
                                .prop('disabled', true)
                                .addClass('disabled')
                                .prop('title', response.message);
                        }
                    }
                });
            }

            $(document).on('input', '[name="shipperchargeAmount[]"], #load_shipper_rate, #load_fsc_rate',
                function () {
                    updateTotalshipper();
                });

        });

        function calculateTotalcustomer() {
            let total = 0;
            $('[name="shipperchargeAmount[]"]').each(function () {
                let value = parseFloat($(this).val());
                if (!isNaN(value)) {
                    total += value;
                }
            });
            $('#totalChargeAmount').val(total.toFixed(2));

            var loadShipperRate = parseFloat($('#load_shipper_rate').val()) || 0;
            total += loadShipperRate;

            var loadFscRate = parseFloat($('#load_fsc_rate').val()) || 0;
            total += (loadFscRate / 100) * loadShipperRate;

            // Always show the calculated total in final rate field
            $('#shipper_load_final_rate').val(total.toFixed(2));
            
            // Then validate credit (will disable submit if exceeded, but rate stays visible)
            validateCreditForLoad();
        }

        // Bind input event to existing .shipperchargeAmount inputs
        $(document).on('input', '[name="shipperchargeAmount[]"]', function () {
            calculateTotalcustomer();
        });

        // Handle Add Charge button click
        $('#addChargeBtn').on('click', function () {
            const $template = $('#chargeRowTemplate');
            const $clone = $template.clone();

            $clone.removeAttr('id'); // remove id to avoid duplicates
            $clone.css('display', 'flex');

            // Append clone to modal body container
            $('.modal-body .container').append($clone);

            // Attach remove event to the new remove button
            $clone.find('.remove-charge').on('click', function () {
                $clone.remove();
                calculateTotalcustomer();
            });
        });

        // Handle removal of pre-existing charges
        $(document).on('click', '.remove-charge', function () {
            $(this).closest('.row').remove();
            calculateTotalcustomer();
        });

    </script>
    <script>
        $(document).ready(function () {


            // Function to calculate and update the total amount
            function updateTotalcarrier() {

                var total = 0;

                // Iterate through each charge input box
                $('[name="inputBox2[]"], [name="shipper_other_charge[]"]').each(function (index, inputBox) {
                    var amount = parseFloat($(inputBox).val()) || 0;
                    total += amount;
                });

                $('#totalShipperOtherChgarges').val(total.toFixed(2));
                

                // Add load_carrier_fee to the total
                var loadCarrierFee = parseFloat($('#load_carrier_fee').val()) || 0;
                total += loadCarrierFee;

                // Get the billing FSC rate
                var billingFSCRate = parseFloat($('#load_billing_fsc_rate').val()) || 0;

                // Calculate the percentage of load_carrier_fee based on billing FSC rate
                var fscAmount = (loadCarrierFee * billingFSCRate) / 100;

                // Add the calculated FSC amount to the total
                total += fscAmount;

                // Set the sum in load_final_carrier_fee
                $('#load_final_carrier_fee').val(total.toFixed(2));

                var customer_rate = $('#shipper_load_final_rate').val();
            
                if(total > customer_rate){
                      $('#mc-error-message').text("Final carrier fee should not be more than final customer rate").fadeIn();
                      // Clear the carrier charges by name: the .shipper_other_charge class is also
                      // on the customer charge rows, and those must not be wiped here.
                      $('[name="inputBox2[]"], [name="shipper_other_charge[]"]').val(0);
                      $('#totalShipperOtherChgarges').val(0);
                        $('#load_carrier_fee').val(0);
                        $('#load_final_carrier_fee').val(0);
                        // Hide after 10 seconds
                        setTimeout(function() {
                            $('#mc-error-message').text('').fadeOut();
                        }, 2000); 
                }
                
            }

            // Handle input changes to update the total
            $(document).on('input',
                '[name="inputBox2[]"], [name="shipper_other_charge[]"], #load_carrier_fee, #load_billing_fsc_rate',
                function () {
                    updateTotalcarrier();

                });
        });
</script>
<script>
	
	
	$(document).ready(function () {
        const ids = ['.load_consignee', '.load_shipper'];

        ids.forEach(function (id) {
            // Prevent copy, paste, and cut
            $(id).on('copy paste cut', function (e) {
                e.preventDefault();
                $('#mc-error-message').text('Cut is not allowed').fadeIn();

				  // Hide after 10 seconds
				  setTimeout(function() {
					  $('#mc-error-message').text('').fadeOut();
				  }, 1000);
            });
        });
    });
	
	
    $(document).ready(function () {

        function calculateTotalcarrier() {
            let total = 0;
            $('[name="shipper_other_charge[]"]').each(function () {
                let value = parseFloat($(this).val());
                if (!isNaN(value)) {
                    total += value;
                }
            });
            $('#totalShipperOtherChgarges').val(total.toFixed(2));

            //var load_carrier_fee = $('#load_carrier_fee').val();

            var loadCarrierFee = parseFloat($('#load_carrier_fee').val()) || 0;
            total += loadCarrierFee;

            var billingFSCRate = parseFloat($('#load_billing_fsc_rate').val()) || 0;
            var fscAmount = (loadCarrierFee * billingFSCRate) / 100;
            total += fscAmount;
            $('#load_final_carrier_fee').val(total.toFixed(2));

            //var final_rate_carrier = parseFloat(load_carrier_fee) + parseFloat(total);

            //$('#load_final_carrier_fee').val(final_rate_carrier);

        }

        // Function to add input row
        $('.create-input').click(function (e) {
            e.preventDefault(); // Prevent the default form submission

            var inputRow = $('<div class="row">' +
                '<div class="col-md-6"><div class="form-group"><input class="form-control" style="width:100%;margin-top: 29px;" type="text" name="shipper_type_charge[]" placeholder="Enter Charge Type"></div></div>' +
                '<div class="col-md-5"><div class="form-group"><input class="form-control" style="width:100%;margin-top: 29px;" type="text" name="shipper_other_charge[]" placeholder="Enter Amount" /></div></div>' +
                '<div class="col-md-1"><div class="form-group"><button class="closebtn" style="margin-top: 17px;color:red;border: none;background: unset;"><i class="fa fa-trash"></i></button></div></div>' +
                '</div>');
            $('#inputs').append(inputRow);

            $(document).on('click', '.closebtn', function () {
                $(this).closest('.row').remove();
                calculateTotalcarrier();
            });
        });

        // Function to remove input row
        $(document).on('click', '.closebtn', function () {
            $(this).closest('.row').remove();
            calculateTotalcarrier();
        });
    });
</script>

<script>
    $(document).ready(function () {
    // Function to fetch carrier suggestions based on any input (name, MC number, DOT number)
    function fetchCarrierSuggestions(field, inputValue) {
        $.ajax({
            url: '{{ route('fetch.carrier.suggestions') }}',
            method: 'POST',
            data: {
                field: field,          // Specify the field (name, MC, DOT)
                inputValue: inputValue, // User input
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                let carrierList = $('#carrier-list');
                carrierList.empty(); 

                if (response.length > 0) {
                    response.forEach(function (carrier) {
                        carrierList.append('<li class="list-group-item carrier-item" data-id="' + carrier.id + '">' + carrier.carrier_name + ' - MC: ' + carrier.mcNumber + ', DOT: ' + carrier.dotNumber + '</li>');
                    });
                    carrierList.show();
                } else {
                    carrierList.hide();
                }
            },
            error: function (xhr, status, error) {
                console.error("Error fetching carrier suggestions: ", error);
            }
        });
    }


    // Function to fetch full carrier details once a carrier is selected
    function fetchCarrierDetails(carrierId) {
        $.ajax({
            url: '{{ route('fetch.carrier.details') }}',
            method: 'POST',
            data: {
                carrierId: carrierId, 
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                if (response) {
                    $('#carrier_id').val(response.id);
                    $('#load_carrier').val(response.carrier_name);
                    $('#carrier_mc_ff_input').val(response.mcNumber);
                    $('#carrier_dot').val(response.dotNumber);
                    $('#load_carrier_phone').val(response.phone);
                }
                $('#carrier-list').hide(); 
            },
            error: function (xhr, status, error) {
                console.error("Error fetching carrier details: ", error);
            }
        });
    }


    // Event handler for input fields to trigger carrier suggestions
    function handleInputChange() {
        let inputValue = $(this).val();
        let field = $(this).attr('id');

        if (inputValue.length >= 3) {
            fetchCarrierSuggestions(field, inputValue);
        } else {
            $('#carrier-list').hide();
        }
    }

    // Attach the event to all relevant fields (carrier name, MC number, DOT number)
    $('#load_carrier, #carrier_mc_ff_input, #carrier_dot').on('input', handleInputChange);

    // Handle selection of a carrier from the suggestion list
    $(document).on('click', '.carrier-item', function () {
        let carrierId = $(this).data('id');
        fetchCarrierDetails(carrierId); // Fetch the full carrier details
    });

    // Hide the suggestion list when clicking outside the input fields
    $(document).click(function (e) {
        if (!$(e.target).closest('#load_carrier, #carrier_mc_ff_input, #carrier_dot, #carrier-list').length) {
            $('#carrier-list').hide();
        }
    });
});
</script>

<script>
    $(document).ready(function() {
        function fetchShipperNames(query, parentId) {
            if (query.trim() !== '') {
                $.ajax({
                    url: "{{ route('fetch.shipper.details') }}",
                    method: "GET",
                    data: { query: query },
                    dataType: "json",
                    success: function(response) {
                        var html = '';
                        if (response.error) {
                            html = '<div class="item dropdown-item" style="border: none;padding: 4px 0;">' + response.error + '</div>';
                        } else {
                            response.forEach(function(shipper) {
                                html += '<div class="item dropdown-item" style="border: none;padding: 4px 0;" ' +
                                    'data-name="' + shipper.shipper_name + '" ' +
                                    'data-address="' + shipper.shipper_address + '" ' +
                                    'data-city="' + shipper.shipper_city + '" ' +
                                    'data-state="' + shipper.shipper_state + '" ' +
                                    'data-country="' + shipper.shipper_country + '" ' +
                                    'data-zip="' + shipper.shipper_zip + '">' +
                                    shipper.shipper_name + '</div>';
                            });
                        }
                        $('#'+parentId+' .shipperList').html(html).show();
                    },
                    error: function(xhr, status, error) {
                        console.error(error);
                    }
                });
            } else {
                $('#'+parentId+' .shipperList').html('').hide();
            }
        }

        $('.load_shipper').on('keyup', function() {
            var query = $(this).val();
			let parentId = $(this).closest('.tab-pane').attr('id');
			
            fetchShipperNames(query, parentId);

            // Clear the location field if shipper name is empty
            if (query.trim() === '') {
                $('#'+parentId+' .load_shipper_location').val('');
            }
        });

        // Listen for click event on shipper list items
        $(document).on('click', '.shipperList .item', function() {
			let parentId = $(this).closest('.tab-pane').attr('id');
            var selectedShipperName = $(this).data('name');
            var selectedShipperAddress = $(this).data('address');
            var selectedShipperCity = $(this).data('city');
            var selectedShipperState = $(this).data('state');
            var selectedShipperCountry = $(this).data('country');
            var selectedShipperZip = $(this).data('zip');

            var countryName = '';
            if (selectedShipperCountry) {
                var countryParts = selectedShipperCountry.split(' ');
                countryName = countryParts.slice(1).join(' ');
            }

            // Format the full address as "address, city, state, zip, country"
            var fullAddress = selectedShipperAddress + ', ' + selectedShipperCity + ', ' +
                selectedShipperState + ', ' + selectedShipperZip + (countryName ? ', ' + countryName : '');

            $('#'+parentId+' .load_shipper').val(selectedShipperName);
            $('#'+parentId+' .load_shipper_location').val(fullAddress);
            $('#'+parentId+' .shipperList').html('').hide(); // Clear the list
        });

        // Hide the dropdown when clicking outside
        $(document).on('click', function(event) {
            if (!$(event.target).closest('.shipperList, .load_shipper').length) {
                $('.shipperList').html('').hide();
            }
        });
    });
</script>

<script>
$(document).ready(function () {
        function fetchConsigneeNames(query, parentId) {
            if (query.trim() !== '') {
                $.ajax({
                    url: "{{ route('fetch.consignee.details') }}",
                    method: "GET",
                    data: {
                        query: query
                    },
                    dataType: "json",
                    success: function (response) {
                        var html = '';
                        response.forEach(function (consignee) {
                            html +=
                                '<div class="item dropdown-item" style="border: none;padding: 4px 0;" data-name="' +
                                consignee.consignee_name + '" data-address="' + consignee
                                .consignee_address + '" data-city="' + consignee
                                .consignee_city + '" data-state="' + consignee
                                .consignee_state + '" data-country="' + consignee
                                .consignee_country + '" data-zip="' + consignee
                                .consignee_zip + '">' + consignee.consignee_name + '</div>';
                        });
                        $('#'+parentId+' .consigneeList').html(html).show();
                    },
                    error: function (xhr, status, error) {
                        console.error(error);
                    }
                });
            } else {
                $('.consigneeList').html('').hide();
            }
        }

        $('input[name="load_consignee"]').on('keyup', function () {
            var query = $(this).val();
			let parentId = $(this).closest('.tab-pane').attr('id');
            fetchConsigneeNames(query, parentId);

            // Clear the location field if consignee name is empty
            if (query.trim() === '') {
                $('#'+parentId+' .load_consignee_location').val('');
            }
        });

        // Listen for click event on consignee list items
        $(document).on('click', '.consigneeList .item', function () {
			let parentId = $(this).closest('.tab-pane').attr('id');
            var selectedConsigneeName = $(this).data('name');
            var selectedConsigneeAddress = $(this).data('address');
            var selectedConsigneeCity = $(this).data('city');
            var selectedConsigneeState = $(this).data('state');
            var selectedConsigneeCountry = $(this).data('country');
            var selectedConsigneeZip = $(this).data('zip');

            var countryName = '';
            if (selectedConsigneeCountry) {
                var countryParts = selectedConsigneeCountry.split(' ');
                countryName = countryParts.slice(1).join(' ');
            }

            var fullAddress = selectedConsigneeAddress + ', ' + selectedConsigneeCity + ', ' +
                selectedConsigneeState + ', ' + selectedConsigneeZip + 
                (countryName ? ', ' + countryName : '');

            $('#'+parentId+' .load_consignee').val(selectedConsigneeName);
            $('#'+parentId+' .load_consignee_location').val(fullAddress);
            $('#'+parentId+' .consigneeList').html('').hide(); // Clear the list
        });



        // Hide the dropdown when clicking outside
        $(document).on('click', function (event) {
            if (!$(event.target).closest('.consigneeList, .load_consignee').length) {
                $('.consigneeList').html('').hide();
            }
        });
    });
</script>
<script>
$(document).ready(function() {
  $('#load_shipper_commodity').on('input', function() {
    $('#load_consignee_commodity').val($(this).val());
  });
  $('#load_shipper_qty').on('input', function() {
    $('#load_consignee_qty').val($(this).val());
  });
  $('#load_shipper_weight').on('input', function() {
    $('#load_consignee_weight').val($(this).val());
  });
  $('#load_shipper_value').on('input', function() {
    $('#load_consignee_value').val($(this).val());
  });
});
</script>
<script>
$(document).ready(function () {
    // Set initial ID for the search form (fallback)
    $('form.app-search .position-relative').attr('id', 'alls');

    let initializedTabs = {};

    function initializeTab(target) {
        let inputSelector = '';
        let ajaxUrl = '';
        let resultContainer = '';
        let tableSelector = '';

        if (target === '#all') {
            $('form.app-search .position-relative').attr('id', 'alls');
            inputSelector = '#alls input[name="query"]';
            ajaxUrl = '/broker/broker_all_load';
            resultContainer = '#all_search';
            tableSelector = '#datatable-buttons-all';
        } else if (target === '#open') {
            $('form.app-search .position-relative').attr('id', 'opens');
            inputSelector = '#opens input[name="query"]';
            ajaxUrl = '/broker/broker_open_load';
            resultContainer = '#open_search';
            tableSelector = '#datatable-buttons-open';
        } else if (target === '#delivered') {
            $('form.app-search .position-relative').attr('id', 'delivereds');
            inputSelector = '#delivereds input[name="query"]'; 
            ajaxUrl = '/broker/broker_delivered_load';
            resultContainer = '#delivered_search';
            tableSelector = '#datatable-buttons-delivered';
        } else if (target === '#complete') {
            $('form.app-search .position-relative').attr('id', 'completes');
            inputSelector = '#completes input[name="query"]';
            ajaxUrl = '/broker/broker_complete_load';
            resultContainer = '#complete_search';
            tableSelector = '#datatable-buttons-complete';
        } else if (target === '#invoice') {
            $('form.app-search .position-relative').attr('id', 'invoices');
            inputSelector = '#invoices input[name="query"]';
            ajaxUrl = '/broker/broker_invoice_load';
            resultContainer = '#invoice_search';
            tableSelector = '#datatable-buttons-invoice';
        } else if (target === '#paid') {
            $('form.app-search .position-relative').attr('id', 'paids');
            inputSelector = '#paids input[name="query"]';
            ajaxUrl = '/broker/broker_paid_load';
            resultContainer = '#paid_search';
            tableSelector = '#datatable-buttons-paid';
        } else {
            return; // Exit if it's not one of the expected tabs
        }

        $(inputSelector).on('keyup', function () {
            let query = $(this).val().trim();
			let user_id = $('select[name="filteruser"]').val();

            clearTimeout($.data(this, 'timer'));
            let wait = setTimeout(() => {
                if (query.length > 0) {
                    $('.loader-container').removeClass('hide');

                    $.ajax({
                        url: ajaxUrl,
                        type: 'GET',
                        data: { query: query, user_id:user_id },
                        success: function (response) {
                            if ($.fn.DataTable.isDataTable(tableSelector)) {
                                $(tableSelector).DataTable().destroy();
                            }

                            $(resultContainer).html(response);
$('.custom-pagination').hide();
                            $(tableSelector).DataTable({
                                responsive: true,
                                dom: 'Bfrtip',
                                fixedHeader: true,
                                search: false,
								order: [[0, 'desc']],
                                pageLength: 50,
                                stateSave: true,   // ✅ remembers column visibility, page, search, etc.
        buttons: [
            {
                extend: 'colvis',
                text: 'Select Columns'
            }
        ]
                            });

                            $('.loader-container').addClass('hide');
                        },
                        error: function (xhr) {
                            console.error("AJAX error:", xhr.responseText);
                            $('.loader-container').addClass('hide');
                        }
                    });
                } else {
                    // $(resultContainer).html('');
                     $(resultContainer).html('');
    $('.custom-pagination').show();
                }
            }, 300);

            $(this).data('timer', wait);
        });

        initializedTabs[target] = true;
    }

    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).attr("href");
        //if (!initializedTabs[target]) {
            initializeTab(target);
       // }
    });

    // --- Trigger initialization for default active tab on page load ---
    const activeTabLink = $('a[data-bs-toggle="tab"].active');
    if (activeTabLink.length > 0) {
        const activeTabTarget = activeTabLink.attr("href");
        initializeTab(activeTabTarget);
    }
});



$(document).ready(function() {
    const tables = {
        '#all': { selector: '#datatable-buttons-all', initialized: false },
        '#open': { selector: '#datatable-buttons-open', initialized: false },
        '#delivered': { selector: '#datatable-buttons-delivered', initialized: false },
        '#complete': { selector: '#datatable-buttons-complete', initialized: false },
        '#invoice': { selector: '#datatable-buttons-invoice', initialized: false },
        '#paid': { selector: '#datatable-buttons-paid', initialized: false }
    };

    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        const target = $(e.target).attr('href');
        if (tables[target] && !tables[target].initialized) {
            $(tables[target].selector).DataTable({
                responsive: true,
                dom: 'Bfrtip',
                search: false,
				order: [[0, 'desc']],
                info: false,
                paging: false,
                pageLength: 50,
                fixedHeader: true,
                        stateSave: true,   // ✅ remembers column visibility, page, search, etc.
        buttons: [
            {
                extend: 'colvis',
                text: 'Select Columns'
            }
        ]  
            });
            tables[target].initialized = true;
        }
    });

    const activeTab = $('a[data-bs-toggle="tab"].active').attr('href');
    if (tables[activeTab] && !tables[activeTab].initialized) {
        $(tables[activeTab].selector).DataTable({
            responsive: true,
            dom: 'Bfrtip',
			search: false,
			order: [[0, 'desc']],
            info: false,
            paging: false,
            pageLength: 50,
            fixedHeader: true,
                    stateSave: true,   // ✅ remembers column visibility, page, search, etc.
        buttons: [
            {
                extend: 'colvis',
                text: 'Select Columns'
            }
        ]
        });
        tables[activeTab].initialized = true;
    }
});
$(document).ready(function() {
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).attr("href");
        let inputSelector = '';
        let ajaxUrl = '';
        let resultContainer = '';
        let tableSelector = '';

        if (target === '#all') {
            $('form.app-search .position-relative').attr('id', 'alls');
            inputSelector = '#alls input[name="query"]';
            ajaxUrl = '/broker/broker_all_load';
            resultContainer = '#all_search';
            tableSelector = '#datatable-buttons-all';
        } else if (target === '#open') {
            $('form.app-search .position-relative').attr('id', 'opens');
            inputSelector = '#opens input[name="query"]';
            ajaxUrl = '/broker/broker_open_load';
            resultContainer = '#open_search';
            tableSelector = '#datatable-buttons-open';
        } else if (target === '#delivered') {
            $('form.app-search .position-relative').attr('id', 'delivereds');
            inputSelector = '#delivereds input[name="query"]';
            ajaxUrl = '/broker/broker_delivered_load';
            resultContainer = '#delivered_search';
            tableSelector = '#datatable-buttons-delivered';
        } else if (target === '#complete') {
            $('form.app-search .position-relative').attr('id', 'completes');
            inputSelector = '#completes input[name="query"]';
            ajaxUrl = '/broker/broker_complete_load';
            resultContainer = '#complete_search';
            tableSelector = '#datatable-buttons-complete';
        } else if (target === '#invoice') {
            $('form.app-search .position-relative').attr('id', 'invoices');
            inputSelector = '#invoices input[name="query"]';
            ajaxUrl = '/broker/broker_invoice_load';
            resultContainer = '#invoice_search';
            tableSelector = '#datatable-buttons-invoice';
        } else if (target === '#paid') {
            $('form.app-search .position-relative').attr('id', 'paids');
            inputSelector = '#paids input[name="query"]';
            ajaxUrl = '/broker/broker_paid_load';
            resultContainer = '#paid_search';
            tableSelector = '#datatable-buttons-paid';
        } else {
            return; // Exit if it's not one of the expected tabs
        }

         $.ajax({
            url: ajaxUrl,
            type: 'GET',
            data: {
				query: '' 
			}, 
            success: function (response) {
                if ($.fn.DataTable.isDataTable(tableSelector)) {
                    $(tableSelector).DataTable().destroy();
                }

                $(resultContainer).html(response);

                $(tableSelector).DataTable({
                    responsive: true,
                    dom: 'Bfrtip',
                    search: false,
                    order: [[0, 'desc']],
                    info: false,
                    paging: false,
                    pageLength: 50,
                    fixedHeader: true,
                           stateSave: true,   // ✅ remembers column visibility, page, search, etc.
        buttons: [
            {
                extend: 'colvis',
                text: 'Select Columns'
            }
        ]
                });

                $('.loader-container').addClass('hide');
            },
            error: function (xhr) {
                console.error("AJAX error:", xhr.responseText);
                $('.loader-container').addClass('hide');
            }
        })
    });
});



$(document).ready(function() {
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).attr("href");
        let inputSelector = '';
        let ajaxUrl = '';
        let resultContainer = '';
        let tableSelector = '';

        if (target === '#all') {
            $('form.app-search .position-relative').attr('id', 'alls');
            inputSelector = '#alls input[name="query"]';
            ajaxUrl = '/broker/broker_all_load';
            resultContainer = '#all_search';
            tableSelector = '#datatable-buttons-all';
        } else if (target === '#open') {
            $('form.app-search .position-relative').attr('id', 'opens');
            inputSelector = '#opens input[name="query"]';
            ajaxUrl = '/broker/broker_open_load';
            resultContainer = '#open_search';
            tableSelector = '#datatable-buttons-open';
        } else if (target === '#delivered') {
            $('form.app-search .position-relative').attr('id', 'delivereds');
            inputSelector = '#delivereds input[name="query"]';
            ajaxUrl = '/broker/broker_delivered_load';
            resultContainer = '#delivered_search';
            tableSelector = '#datatable-buttons-delivered';
        } else if (target === '#complete') {
            $('form.app-search .position-relative').attr('id', 'completes');
            inputSelector = '#completes input[name="query"]';
            ajaxUrl = '/broker/broker_complete_load';
            resultContainer = '#complete_search';
            tableSelector = '#datatable-buttons-complete';
        } else if (target === '#invoice') {
            $('form.app-search .position-relative').attr('id', 'invoices');
            inputSelector = '#invoices input[name="query"]';
            ajaxUrl = '/broker/broker_invoice_load';
            resultContainer = '#invoice_search';
            tableSelector = '#datatable-buttons-invoice';
        } else if (target === '#paid') {
            $('form.app-search .position-relative').attr('id', 'paids');
            inputSelector = '#paids input[name="query"]';
            ajaxUrl = '/broker/broker_paid_load';
            resultContainer = '#paid_search';
            tableSelector = '#datatable-buttons-paid';
        } else {
            return; // Exit if it's not one of the expected tabs
        }

         $.ajax({
            url: ajaxUrl,
            type: 'GET',
            data: {
				query: '' 
			}, 
            success: function (response) {
                if ($.fn.DataTable.isDataTable(tableSelector)) {
                    $(tableSelector).DataTable().destroy();
                }

                $(resultContainer).html(response);

                $(tableSelector).DataTable({
                    responsive: true,
                    dom: 'Bfrtip',
                    search: false,
                    order: [[0, 'desc']],
                    fixedHeader: true,
                    pageLength: 50,
                           stateSave: true,   // ✅ remembers column visibility, page, search, etc.
        buttons: [
            {
                extend: 'colvis',
                text: 'Select Columns'
            }
        ]
                });

                $('.loader-container').addClass('hide');
            },
            error: function (xhr) {
                console.error("AJAX error:", xhr.responseText);
                $('.loader-container').addClass('hide');
            }
        })
    });
});


$('#filteruser').on('change', function () {
    const selectedUser = $(this).val();
    const activeTab = $('.nav-link.active').attr('href'); // e.g., "#open"
    let ajaxUrl = '';
    let resultContainer = '';
    let tableSelector = '';

    if (activeTab === '#all') {
        ajaxUrl = '/broker/load_search_by_user';
        resultContainer = '#all_search';
        tableSelector = '#datatable-buttons-all';
    } else if (activeTab === '#open') {
        ajaxUrl = '/broker/load_search_by_user';
        resultContainer = '#open_search';
        tableSelector = '#datatable-buttons-open';
    } else if (activeTab === '#delivered') {
        ajaxUrl = '/broker/load_search_by_user';
        resultContainer = '#delivered_search';
        tableSelector = '#datatable-buttons-delivered';
    } else if (activeTab === '#complete') {
        ajaxUrl = '/broker/load_search_by_user';
        resultContainer = '#complete_search';
        tableSelector = '#datatable-buttons-complete';
    } else if (activeTab === '#invoice') {
        ajaxUrl = '/broker/load_search_by_user';
        resultContainer = '#invoice_search';
        tableSelector = '#datatable-buttons-invoice';
    } else if (activeTab === '#paid') {
        ajaxUrl = '/broker/load_search_by_user';
        resultContainer = '#paid_search';
        tableSelector = '#datatable-buttons-paid';
    } else {
        return;
    }

    $('.loader-container').removeClass('hide');

    $.ajax({
        url: ajaxUrl,
        type: 'GET',
        data: {
			activeTab: activeTab,
            user_id: selectedUser  // assuming your backend expects user_id
        },
        success: function (response) {
            if ($.fn.DataTable.isDataTable(tableSelector)) {
                $(tableSelector).DataTable().destroy();
            }

            $(resultContainer).html(response);

            $(tableSelector).DataTable({
                responsive: true,
                fixedHeader: true,
                dom: 'Bfrtip',
                search: false,
                order: [[0, 'desc']],
                pageLength: 50,
                        stateSave: true,   // ✅ remembers column visibility, page, search, etc.
        buttons: [
            {
                extend: 'colvis',
                text: 'Select Columns'
            }
        ]
            });

            $('.loader-container').addClass('hide');
        },
        error: function (xhr) {
            console.error("AJAX error:", xhr.responseText);
            $('.loader-container').addClass('hide');
        }
    });
});

</script>
<script>
document.addEventListener("DOMContentLoaded", function () {

    const pickupInput = document.querySelector('.load_shipper_appointment');
    const deliveryInput = document.querySelector('.load_consignee_appointment');

    function validateDeliveryDate() {

        let pickupValue = pickupInput.value;
        let deliveryValue = deliveryInput.value;

        if (pickupValue && deliveryValue) {

            let pickupDate = new Date(pickupValue);
            let deliveryDate = new Date(deliveryValue);

            if (deliveryDate <= pickupDate) {
                alert("Delivery date & time must be greater than Pickup date & time.");
                deliveryInput.value = "";
            }
        }
    }

    deliveryInput.addEventListener("change", validateDeliveryDate);

});
</script>
<script>
document.querySelector('.load_shipper_appointment').addEventListener('change', function () {
    document.querySelector('.load_consignee_appointment').min = this.value;
});
</script>
    @endsection
