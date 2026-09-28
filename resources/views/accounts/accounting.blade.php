@extends('layout.compact.app')
<!-- This links to the app.blade.php layout -->

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
#search-active .pagination-container{
    display:none !important;
}
#mc-error-message{
    padding: 10px;
    background-color: rgb(239 212 214);
    color: rgb(87, 21, 26);
    margin-bottom: 10px;
    border: 1px solid rgb(239 212 214);
    border-radius: 4px;
    position: fixed;
    width: 20%;
    right: 10px;
    z-index: 9999;
    top: 10px;
}
td.dynamic-data a {
    /* color: #a6ce3a !important; */
    text-decoration: none; /* optional: remove underline */
}

td.dynamic-data a:hover {
    color: #7aa428 !important; /* slightly darker green on hover */
}
table.dataTable tbody > tr.selected td,
table.dataTable tbody > tr.selected td span,
table.dataTable tbody > tr.selected td a,
table.dataTable tbody > tr.selected td p {
    border-color: rgba(15, 156, 243, .2);
    color: #000 !important;
}
.mail-document-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 12px; }
.mail-document-card { position: relative; display: block; min-height: 150px; padding: 10px; border: 1px solid #d9e0e7; border-radius: 8px; background: #fff; cursor: pointer; }
.mail-document-card:has(input:not(:checked)) { opacity: .55; }
.mail-document-card input[type="checkbox"] { margin-right: 6px; }
.mail-document-preview { display: flex; min-height: 86px; align-items: center; justify-content: center; flex-direction: column; color: #dc3545; text-decoration: none; overflow: hidden; }
.mail-document-preview img { max-width: 100%; max-height: 86px; object-fit: contain; }
.mail-document-preview .fa-file-pdf { font-size: 40px; }
.mail-document-name { display: block; max-width: 100%; padding-right: 26px; font-size: 12px; overflow-wrap: anywhere; word-break: break-word; white-space: normal; }
.remove-mail-document { position: absolute; right: 8px; bottom: 7px; }

#invoiced_paid .accounting-paid-select {
    height: 32px;
    min-height: 32px;
    padding-top: 2px;
    padding-bottom: 2px;
    line-height: normal;
}

#invoiced_paid .accounting-paid-select + .select2-container,
#invoiced_paid .accounting-paid-select + .select2-container > .selection,
#invoiced_paid .accounting-paid-select + .select2-container > .dropdown-wrapper,
#invoiced_paid .accounting-paid-select + .select2-container .select2-selection--single {
    height: 32px;
    line-height: normal;
    padding: 0;
}

#invoiced_paid .accounting-paid-select + .select2-container .select2-selection--single {
    padding: 6px 32px 6px 12px;
    display: flex;
    align-items: center;
}

#invoiced_paid .accounting-paid-select + .select2-container .select2-selection__rendered {
    line-height: normal;
    padding: 0;
}

#invoiced_paid .accounting-paid-select + .select2-container .select2-selection__arrow {
    height: 30px;
    width: 30px;
}
</style>

<div id="mc-success-message" style="display: none;"></div>
<div id="mc-error-message" style="display: none;"></div>

        @if(session('error'))
        <div class="alert alert-danger" id="error-alert">
            {{ session('error') }}
        </div>
        @endif

        @if(session('success'))
        <div class="alert alert-success" id="success-alert">
            {{ session('success') }}
        </div>
        @endif

        
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Accounting</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">CCI</a></li>
                            <li class="breadcrumb-item active">Accounting</li>
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

                        <h4 class="card-title">Accounting</h4>
						<!-- App Search-->
                        <form class="app-search-load d-none d-lg-block">
                            <div class="position-relative-load" id="opens" style="width: 300px;">
                                <input type="text" class="form-control" name="query" placeholder="Search...">
                                <span class="ri-search-line"></span>
                            </div>
                        </form>

                        <ul class="nav nav-tabs nav-tabs-custom nav-justified" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link {{ $activeTab === 'open' ? 'active' : '' }}" data-bs-toggle="tab" href="#open" role="tab"
                                    aria-selected="true">
                                    <span class="d-block d-sm-none"><i class="fas fa-home"></i></span>
                                    <span class="d-none d-sm-block">Open</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link {{ $activeTab === 'delivered' ? 'active' : '' }}" data-bs-toggle="tab" href="#delivered" role="tab"
                                    aria-selected="false" tabindex="-1">
                                    <span class="d-block d-sm-none"><i class="fas fa-truck"></i></span>
                                    <span class="d-none d-sm-block">Delivered</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link {{ $activeTab === 'completed' ? 'active' : '' }}" data-bs-toggle="tab" href="#completed" role="tab"
                                    aria-selected="false" tabindex="-1">
                                    <span class="d-block d-sm-none"><i class="far fa-user"></i></span>
                                    <span class="d-none d-sm-block">Completed</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link {{ $activeTab === 'invoiced' ? 'active' : '' }}" data-bs-toggle="tab" href="#invoiced" role="tab"
                                    aria-selected="false" tabindex="-1">
                                    <span class="d-block d-sm-none"><i class="far fa-envelope"></i></span>
                                    <span class="d-none d-sm-block">Invoiced</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link {{ $activeTab === 'invoiced_paid' ? 'active' : '' }}" data-bs-toggle="tab" href="#invoiced_paid" role="tab"
                                    aria-selected="false" tabindex="-1">
                                    <span class="d-block d-sm-none"><i class="fas fa-cog"></i></span>
                                    <span class="d-none d-sm-block">Invoiced / Paid</span>
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content p-3 text-muted">
                            <div class="tab-pane {{ $activeTab === 'open' ? 'active show' : '' }}" id="open" role="tabpanel">

                            <button type="button" class="btn btn-primary waves-effect waves-light mb-3" data-bs-toggle="modal" data-bs-target="#exampleModal">Multiple Load PI</button>

                            <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="exampleModalLabel">Enter PI load number</h5>
                                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <form action="{{route('loads-pi')}}" method="post">
                                                @csrf
                                                <input typ="text" name="load_numbers" class="form-control  w-100 mb-3" placeholder="Enter load numbers">
                                                <input type="submit" class="btn btn-primary w-100" name="submit" value="submit">
                                            </form>
                                        </div>
                                        
                                        </div>
                                    </div>
                                </div>

                               <table id="datatable-buttons-open" class="table table-striped table-bordered dt-responsive nowrap accounts-table" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Sr No.</th>
                                            <th>Load #</th>
											<th>Action</th>
                                            <th>W/O #</th>
                                            <th>Customer Name</th>
                                            <th>Customer Final Rate</th>
                                            <th>Agent</th>
                                            <th>Load Creation Date</th>
                                            <th>Shipper Date</th>
                                            <th>Delivered Date</th>
                                            <th>Carrier</th>
                                            <th>Carrier Final Rate</th>
                                            <th>Load Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="open-search">
                                    @include('accounts.partials.accounting_open')
                                    </tbody>
                                </table>
                                <div class="custom-pagination pagination-container">
                                    {!! render_pagination_links($open) !!}
                                </div>
                            </div>
                            <div class="tab-pane {{ $activeTab === 'delivered' ? 'active show' : '' }}" id="delivered" role="tabpanel">
                                <table id="datatable-buttons-delivered" class="table table-striped table-bordered dt-responsive nowrap accounts-table"
                                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Sr No.</th>
                                            <th>Actions</th>
                                            <th>Load #</th>
                                            <th>Customer Name</th>
                                            <th>Customer Final Rate</th>
                                            <th>Internal Team Notes</th>
                                            <th>Paper Work Date</th>
                                            <th>Agent</th>
                                            <th>W/O #</th>
                                            <th>Load Creation Date</th>
                                            <th>Shipper Date</th>
                                            <th>Delivered Date</th>
                                            <th>Actual Delivered Date</th>
                                            <th>Carrier</th>
                                            <th>Carrier MC no</th>
                                            <th>Carrier Base Rate</th>
                                            <th>Carrier FSC Rate</th>
                                            <th>Carrier Other Rate</th>
                                            <th>Carrier Final Charges</th>
                                            <th>Pickup Location</th>
                                            <th>unloading Location</th>
                                            <th>Load Status</th>
                                            <th>Aging</th>
                                        </tr>
                                    </thead>
                                    <tbody id="delivered-search">
                                        @include('accounts.partials.accounting_complete', ['complete' => $delivered, 'showInvoiceAction' => false])
                                    </tbody>
                                </table>
                                <div class="custom-pagination pagination-container">
                                    {!! render_pagination_links($delivered) !!}
                                </div>
                            </div>
                            <div class="tab-pane {{ $activeTab === 'completed' ? 'active show' : '' }}" id="completed" role="tabpanel">
                                <table id="datatable-buttons-completed" class="table table-striped table-bordered dt-responsive nowrap accounts-table"
                                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Sr No.</th>
                                             <th>Actions</th>
                                            <th>Load #</th>
                                            <th>Customer Name</th>
                                            <th>Customer Final Rate</th>
                                            <th>Internal Team Notes</th>
                                            <th>Paper Work Date</th>
                                            <th>Agent</th>
                                             <th>W/O #</th>
                                            <th>Load Creation Date</th>
                                            <th>Shipper Date</th>
                                            <th>Delivered Date</th>
                                            <th>Actual Delivered Date</th>
                                            <th>Carrier</th>
											<th>Carrier MC no</th>
                                            <th>Carrier Base Rate</th>
                                            <th>Carrier FSC Rate</th>
                                            <th>Carrier Other Rate</th>
                                            <th>Carrier Final Charges</th>
                                            <th>Pickup Location</th>
                                            <th>unloading Location</th>
                                            <th>Load Status</th>
                                            <th>Aging</th>
                                        </tr>
                                    </thead>

                                    <tbody id="completed-search">
                                        @include('accounts.partials.accounting_complete')
                                    </tbody>
                                </table>
                                <div class="custom-pagination" id="completed-search">
                                    {!! render_pagination_links($complete) !!}
                                </div>
                            </div>
                            <div class="tab-pane {{ $activeTab === 'invoiced' ? 'active show' : '' }}" id="invoiced" role="tabpanel">
                               
                               <!-- <button type="button" class="btn btn-primary waves-effect waves-light mb-3 custome-search" data-bs-toggle="modal" data-bs-target="#exampleModalinvoice">Multiple Invoice</button> -->
                                <!-- <form id="loadSearchForm">
                                    <label for="load_numbers">Enter Load Numbers (comma separated):</label>
                                    <input type="text" name="load_numbers" id="load_numbers" placeholder="21021,22022" required>
                                    <button type="submit" class="btn btn-primary">Search</button>
                                </form> -->

                             




                            <div class="modal fade" id="exampleModalinvoice" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="exampleModalLabel">Enter Invoice load number</h5>
                                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                     <form id="loadSearchForm">
                                        <input type="text" name="load_numbers" id="load_numbers" placeholder="21021,22022" required>
                                        <button type="submit" class="btn btn-primary">Search</button>
                                    </form>
                                        </div>
                                        
                                        </div>
                                    </div>
                                </div>
                                <table id="datatable-buttons-invoiced" class="table table-striped table-bordered dt-responsive nowrap accounts-table"
       style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <thead>
                                       <tr>
                                            <th>Sr No.</th>
                                            <th>Load #</th>
                                            <th>Invoice #</th>
                                            <th>Actions</th>
                                            <th>W/O #</th>
                                            <th>Customer Name</th>
                                            <th>Paper Work Date</th>
                                            <th>Customer Payment Receiving Date</th>
                                            <th>Shipper Final Rate</th>
                                            <!---<th>Shipper Receiving Amount</th>---->
											<th>Amount Received</th>
                                            <th>Shipper invoice internal note</th>
                                            <th>Invoice Date</th>
                                            <th>Agent Name</th>
                                            <th>Office</th>
                                            <th>Team Leader</th>
                                            <th>Manager</th>
                                            <th>Load Creation Date </th>
                                            <th>Shipper Date</th>
                                            <th>Delivered Date</th>
                                            <th>Actual Del Date</th>
                                            <th>Carrier</th>
											<th>Carrier MC no</th>
                                            <th>Carrier Final Rate</th>
                                            <th>Pickup Location</th>
                                            <th>Unloading Location</th>
                                            <th>Load Status</th>
                                            <th>Invoice Status</th>
                                            <th>Aging</th>
                                            
                                        </tr>
                                    </thead>

                                    <tbody id="invoiced-search">
                                       @include('accounts.partials.accounting_invoiced')
                                    </tbody>
                                    
                                </table>
                                <div class="custom-pagination pagination-container">
                                    {!! render_pagination_links($invoiced) !!}
                                </div>
                            </div>
                            <div class="tab-pane {{ $activeTab === 'invoiced_paid' ? 'active show' : '' }}" id="invoiced_paid" role="tabpanel">
                                <button type="button" class="btn btn-primary waves-effect waves-light mb-3">Multiple Invoice</button>
                                <table id="datatable-buttons-invoiced_paid" class="table table-striped table-bordered dt-responsive nowrap accounts-table"
                                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Load #</th>
                                            <th>Action</th>
                                            <th>Payment Status</th>
                                            <th>Add. Remarks</th>
                                            <th>W/O #</th>
                                            <th>Invoice #</th>
                                            <th>Delivered Date</th>
                                            <th>Customer Name</th>
                                            <th>Invoice Date</th>
                                            <th>Shipper Final Amount</th>
                                            <th>Received Amount</th>
											<th>Remaining/Excess Amount</th>
                                            <th>Short Payment Mark as Full Payment Date</th>
                                            <th>Customer Payment Mark Date</th>
                                            <th>Paper Work Date</th>
                                            <th>Agent</th>
                                            <th>Load Creation Date</th>
                                            <th>Pickup Date</th>
                                            <th>Pickup Location</th>
                                            <th>Unloading Location</th>
											<th>Invoice internal note</th>
                                            <!-- <th>Advance Amount</th> -->
                                            <!-- <th>Invoice Status</th> -->
                                        </tr>
                                    </thead>

                                    <tbody id="invoiced_paid-search">
                                       @include('accounts.partials.accounting_paid')
                                    </tbody>
                                </table>
                                <div class="custom-pagination pagination-container">
                                    {!! render_pagination_links($paid) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- end col -->
        </div> <!-- end row -->



    </div> <!-- container-fluid -->
</div>

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

        if (activeTab === '#open') {
            resultContainer = '#open-search'; pageName = 'open';
            tableSelector = '#datatable-buttons-open';

        } else if (activeTab === '#delivered') {
            resultContainer = '#delivered-search'; pageName = 'delivered';
            tableSelector = '#datatable-buttons-delivered';

        } else if (activeTab === '#completed') {
            resultContainer = '#completed-search'; pageName = 'complete';
            tableSelector = '#datatable-buttons-completed';

        } else if (activeTab === '#invoiced') {
            resultContainer = '#invoiced-search'; pageName = 'invoiced';
            tableSelector = '#datatable-buttons-invoiced';

        } else if (activeTab === '#invoiced_paid') {
            resultContainer = '#invoiced_paid-search'; pageName = 'paid';
            tableSelector = '#datatable-buttons-invoiced_paid';

        } else {
            return; // Exit if it's not one of the expected tabs
        }
    let pageUrl = new URL(href, window.location.origin);
    let pageNumber = pageUrl.searchParams.get(pageName) || '1';

    $.ajax({
        url: '/account/accounting',
        type: 'GET',
        dataType: 'json',
        data: {
            tab: activeTab,
            page: pageNumber,
            [pageName]: pageNumber
        },
        success: function(data) {
            if ($.fn.DataTable.isDataTable(tableSelector)) {
                $(tableSelector).DataTable().destroy();
            }

            $(resultContainer).html(data.html);
            $(resultContainer).closest('.tab-pane').find('.custom-pagination')
                .html(data.pagination || '').show();

            $(tableSelector).DataTable({
                responsive: true,
                dom: 'rtip',
				pageLength: 100, 
                paging: false,
                buttons: [ 'colvis' ],
            });

            const urlParams = new URLSearchParams();
            urlParams.set(pageName, pageNumber);
            window.history.pushState({}, '', '/account/accounting?' + urlParams.toString());
        },
        error: function(xhr) {
            console.error("Pagination AJAX error:", xhr.status, xhr.responseText);
        }
    });
});
</script>
<script>

$(document).ready(function () {
    // Set initial ID for the search form (fallback)
    $('form.app-search-load .position-relative-load, form.app-search .position-relative').attr('id', 'opens');

    let initializedTabs = {};

    function initializeTab(target) {
        let inputSelector = '';
        let ajaxUrl = '';
        let resultContainer = '';
        let tableSelector = '';

        if (target === '#open') {
            $('form.app-search-load .position-relative-load, form.app-search .position-relative').attr('id', 'opens');
            inputSelector = '#opens input[name="query"]';
            ajaxUrl = '/account/accounting_open_search';
            resultContainer = '#open-search';
            tableSelector = '#datatable-buttons-open';

        } else if (target === '#completed') {
            $('form.app-search-load .position-relative-load, form.app-search .position-relative').attr('id', 'completeds');
            inputSelector = '#completeds input[name="query"]';
            ajaxUrl = '/account/accounting_completed_search';
            resultContainer = '#completed-search';
            tableSelector = '#datatable-buttons-completed';

        } else if (target === '#delivered') {
            $('form.app-search-load .position-relative-load, form.app-search .position-relative').attr('id', 'delivereds');
            inputSelector = '#delivereds input[name="query"]';
            ajaxUrl = '/account/accounting_delivered_search';
            resultContainer = '#delivered-search';
            tableSelector = '#datatable-buttons-delivered';

        } else if (target === '#invoiced') {
            $('form.app-search-load .position-relative-load, form.app-search .position-relative').attr('id', 'invoiceds');
            inputSelector = '#invoiceds input[name="query"]';
            ajaxUrl = '/account/accounting_invoiced_search';
            resultContainer = '#invoiced-search';
            tableSelector = '#datatable-buttons-invoiced';

        } else if (target === '#invoiced_paid') {
            $('form.app-search-load .position-relative-load, form.app-search .position-relative').attr('id', 'invoiced_paids');
            inputSelector = '#invoiced_paids input[name="query"]';
            ajaxUrl = '/account/accounting_invoiced_paid_search';
            resultContainer = '#invoiced_paid-search';
            tableSelector = '#datatable-buttons-invoiced_paid';

        } else {
            return; // Exit if it's not one of the expected tabs
        }

        $(inputSelector).on('keyup', function () {
            let query = $(this).val().trim();
            let paginationContainer = $(resultContainer).closest('.tab-pane').find('.custom-pagination');

            clearTimeout($.data(this, 'timer'));
            let wait = setTimeout(() => {
                $('.loader-container').removeClass('hide');

                if (query.length > 0) {
                    paginationContainer.hide();
                } else {
                    paginationContainer.show();
                }

                $.ajax({
                    url: ajaxUrl,
                    type: 'GET',
                    data: { query: query },
                    success: function (response) {
                        if ($.fn.DataTable.isDataTable(tableSelector)) {
                            $(tableSelector).DataTable().destroy();
                        }

                        $(resultContainer).html(response);

                        $(tableSelector).DataTable({
                            responsive: true,
                            dom: 'Bfrtip',
                            pageLength: 100,
                            paging: false,
                            buttons: ['copy', 'excel', 'pdf', 'colvis'],
                        });

                        $('.loader-container').addClass('hide');
                    },
                    error: function () {
                        $('.loader-container').addClass('hide');
                    }
                });
            }, 300);

            $(this).data('timer', wait);
        });

        initializedTabs[target] = true;
    }
	

    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).attr("href");
        //alert(target);
       // if (!initializedTabs[target]) {
            initializeTab(target);
        //}
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
        '#open': { selector: '#datatable-buttons-open', initialized: false },
        '#delivered': { selector: '#datatable-buttons-delivered', initialized: false },
        '#completed': { selector: '#datatable-buttons-completed', initialized: false },
        '#invoiced': { selector: '#datatable-buttons-invoiced', initialized: false },
        '#invoiced_paid': { selector: '#datatable-buttons-invoiced_paid', initialized: false },
    };

    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        const target = $(e.target).attr('href');
        if (tables[target] && !tables[target].initialized) {
            $(tables[target].selector).DataTable({
                responsive: true,
                dom: 'Bfrtip',
                buttons: ['copy', 'excel', 'pdf', 'colvis'],
                pageLength: 100,
            });
            tables[target].initialized = true;
        }
    });

    const activeTab = $('a[data-bs-toggle="tab"].active').attr('href');
    if (tables[activeTab] && !tables[activeTab].initialized) {
        $(tables[activeTab].selector).DataTable({
            responsive: true,
            dom: 'Bfrtip',
            buttons: ['copy', 'excel', 'pdf', 'colvis'],
            searching: false,
            paging: false,
            pageLength: 100,
            
        });
        tables[activeTab].initialized = true;
    }
});

$('input[name="loadquery"]').on('keyup', function () {
    const load_id = $(this).val().trim();
    const target = $('.nav-link.active').attr('href'); // e.g., "#open"
   
        let ajaxUrl = '';
        let resultContainer = '';
        let tableSelector = '';

        if (target === '#open') {
            ajaxUrl = '/account/load_search_by_load';
            resultContainer = '#open-search';
            tableSelector = '#datatable-buttons-open'; 

        } else if (target === '#completed') {
            ajaxUrl = '/account/load_search_by_load';
            resultContainer = '#completed-search';
            tableSelector = '#datatable-buttons-completed';

        } else if (target === '#delivered') {
            ajaxUrl = '/account/load_search_by_load';
            resultContainer = '#delivered-search';
            tableSelector = '#datatable-buttons-delivered';

        } else if (target === '#invoiced') {
            ajaxUrl = '/account/load_search_by_load';
            resultContainer = '#invoiced-search';
            tableSelector = '#datatable-buttons-invoiced';

        } else if (target === '#invoiced_paid') {
            ajaxUrl = '/account/load_search_by_load';
            resultContainer = '#invoiced_paid-search';
            tableSelector = '#datatable-buttons-invoiced_paid';

        } else {
            return;
        }

    $('.loader-container').removeClass('hide');

    $.ajax({
        url: ajaxUrl,
        type: 'GET',
        data: {
            target: target,
            load_id: load_id
        },
        success: function (response) {
            if ($.fn.DataTable.isDataTable(tableSelector)) {
                $(tableSelector).DataTable().destroy();
            }

            $(resultContainer).html(response);

            $(tableSelector).DataTable({
                responsive: true,
                dom: 'Bfrtip',
                order: [[0, 'desc']],
                pageLength: 100,
				searching: false, 
                buttons: [ 'colvis' ],
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
<!-- <script>
document.addEventListener("DOMContentLoaded", function () {
    let invoicedTable = $('#datatable-buttons-invoiced').DataTable({
        responsive: true,
        dom: 'Bfrtip',
        buttons: ['copy', 'excel', 'pdf', 'colvis'],
        pageLength: 100,
    });

    const form = document.getElementById("loadSearchForm");
    form.addEventListener("submit", function (e) {
        e.preventDefault();

        let formData = new FormData(form);

        fetch("{{ route('loads.search.invoice') }}", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "X-Requested-With": "XMLHttpRequest"
            },
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            // Clear DataTable and replace rows
            invoicedTable.clear();

            // Convert response to rows
            let tempDom = $('<tbody>').html(html);
            invoicedTable.rows.add(tempDom.find('tr')).draw();
        })
        .catch(err => console.error(err));
    });
});

</script> -->
@endsection
