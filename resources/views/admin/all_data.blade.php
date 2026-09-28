@extends('layout.compact.app')
<!-- This links to the app.blade.php layout -->

@section('content')
<style>
    .dt-buttons.btn-group.flex-wrap {
        display: none;
    }

    div#datatable-buttons-customer_filter {
        display: none;
    }

    .hide_blur_privacy {
        transition: 0.2s ease;
    }

    .hide_blur_privacy.blurred {
        filter: blur(6px);
    }
</style>
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">All Data</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">CCI</a></li>
                            <li class="breadcrumb-item active">All Data</li>
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

                        <h4 class="card-title">All Data</h4>
<div class="my-4 d-flex justify-content-end">
    <ul class="list-inline m-0">
        <li class="list-inline-item">
            <a href="javascript:void(0)" class="privacy_toggle">
                <i class="fa fa-eye"></i>
            </a>
        </li>
    </ul>
</div>

                        <ul class="nav nav-tabs nav-tabs-custom nav-justified" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link {{ $activeTab === 'customer' ? 'active' : '' }}" data-bs-toggle="tab" href="#customer" role="tab"
                                    aria-selected="true">
                                    <span class="d-block d-sm-none"><i class="fas fa-home"></i></span>
                                    <span class="d-none d-sm-block">Customer</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link {{ $activeTab === 'carrier' ? 'active' : '' }}" data-bs-toggle="tab" href="#carrier" role="tab"
                                    aria-selected="false" tabindex="-1">
                                    <span class="d-block d-sm-none"><i class="far fa-user"></i></span>
                                    <span class="d-none d-sm-block">Carrier</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link {{ $activeTab === 'consignee' ? 'active' : '' }}" data-bs-toggle="tab" href="#consignee" role="tab"
                                    aria-selected="false" tabindex="-1">
                                    <span class="d-block d-sm-none"><i class="far fa-user"></i></span>
                                    <span class="d-none d-sm-block">Consignee</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link {{ $activeTab === 'shipper' ? 'active' : '' }}" data-bs-toggle="tab" href="#shipper" role="tab"
                                    aria-selected="false" tabindex="-1">
                                    <span class="d-block d-sm-none"><i class="far fa-envelope"></i></span>
                                    <span class="d-none d-sm-block">Shipper</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link {{ $activeTab === 'load' ? 'active' : '' }}" data-bs-toggle="tab" href="#load" role="tab" aria-selected="false"
                                    tabindex="-1">
                                    <span class="d-block d-sm-none"><i class="far fa-envelope"></i></span>
                                    <span class="d-none d-sm-block">Loads</span>
                                </a>
                            </li>

                        </ul>

                        <div class="loader-container hide">
                            <div class="bouncing-dots">
                                <div class="dot"></div>
                                <div class="dot"></div>
                                <div class="dot"></div>
                            </div>
                        </div>

                        <div class="tab-content p-3 text-muted">
                            <div class="tab-pane {{ $activeTab === 'customer' ? 'active show' : '' }}" id="customer" role="tabpanel">

                                <table id="datatable-buttons-customer"
                                    class="table table-striped table-bordered dt-responsive nowrap"
                                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <span><a href="{{ route('allloadsExcel', 'Customer') }}"><button
                                                class="btn btn-primary waves-effect waves-light mb-3 exlbtn">All
                                                Customer Excel</button></a></span>
                                    <thead>
                                        <tr>
                                            <th>Sr No.</th>
                                            <th>Agent</th>
                                            <th>Office</th>
                                            <th>Company</th>
                                            <th>Address</th>
                                            <th>Contact Number</th>
                                            <th>Dispatcher Email</th>
                                            <th>Billing Email</th>
                                            <th>Date Added</th>
                                            <th>Team Leader</th>
                                            <th>Manager</th>
                                            <th>Requested Credit</th>
                                            <th>Credit Used</th>
                                            <th>Remaining Limit</th>
                                            <th>Approved Status</th>
                                            <th>Last Load</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="customer-search">
                                        @include('admin.all_data.customer')
                                    </tbody>
                                </table>
                                <div class="custom-pagination">
                                    {!! render_pagination_links($sortedCustomers->setPageName('customer')) !!}
                                </div>
                            </div>
                            <div class="tab-pane {{ $activeTab === 'carrier' ? 'active show' : '' }}" id="carrier" role="tabpanel">
                                <table id="datatable-buttons-carrier"
                                    class="table table-striped table-bordered dt-responsive nowrap"
                                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <span><a href="{{ route('allloadsExcel', 'Carriers') }}"><button
                                                class="btn btn-primary waves-effect waves-light mb-3 exlbtn">All Carrier
                                                Excel</button></a></span>
                                    <thead>
                                        <tr>
                                            <th>Sr No.</th>
                                            <th>Carrier Name</th>
                                            <th>MC#</th>
                                            <th>DOT#</th>
                                            <th>Address</th>
                                            <th>Phone No</th>
                                            <th>Added Date</th>
                                            <th>Agent</th>
                                            <th>Team Leader</th>
                                            <th>Manager</th>
                                            <th>Status</th>
                                            <th> Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="carrier-search">
                                        @include('admin.all_data.carrier')
                                    </tbody>
                                </table>
                                <div class="custom-pagination">
                                    {!! render_pagination_links($external->setPageName('carrier')) !!}
                                </div>
                            </div>
                            <div class="tab-pane {{ $activeTab === 'consignee' ? 'active show' : '' }}" id="consignee" role="tabpanel">
                                <table id="datatable-buttons-consignee"
                                    class="table table-striped table-bordered dt-responsive nowrap"
                                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <span><a href="{{ route('allloadsExcel', 'Consignee') }}"><button
                                                class="btn btn-primary waves-effect waves-light mb-3 exlbtn">All
                                                Consignee Excel</button></a></span>
                                    <thead>
                                        <tr>
                                            <th>Sr No</th>
                                            <th>Consignee Name</th>
                                            <th>Address</th>
                                            <th>Phone No</th>
                                            <th>Added Date</th>
                                            <th>Agent</th>
                                            <th>Team Leader</th>
                                            <th>Manager</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="consignee-search">
                                        @include('admin.all_data.consignee')
                                    </tbody>
                                </table>
                                <div class="custom-pagination">
                                    {!! render_pagination_links($consignee->setPageName('consignee')) !!}
                                </div>
                            </div>
                            <div class="tab-pane {{ $activeTab === 'shipper' ? 'active show' : '' }}" id="shipper" role="tabpanel">
                                <table id="datatable-buttons-shipper"
                                    class="table table-striped table-bordered dt-responsive nowrap"
                                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <span><a href="{{ route('allloadsExcel', 'Shipper') }}"><button
                                                class="btn btn-primary waves-effect waves-light mb-3 exlbtn">All Shipper
                                                Excel</button></a></span>
                                    <thead>
                                        <tr>
                                            <th>Sr No.</th>
                                            <th>Shipper Name</th>
                                            <th>Address</th>
                                            <th>Phone No</th>
                                            <th>Added Date</th>
                                            <th>Agent</th>
                                            <th>Team Leader</th>
                                            <th>Manager</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="shipper-search">
                                        @include('admin.all_data.shipper')
                                    </tbody>
                                </table>
                                <div class="custom-pagination">
                                    {!! render_pagination_links($shipper->setPageName('shipper')) !!}
                                </div>
                            </div>
                            <div class="tab-pane {{ $activeTab === 'load' ? 'active show' : '' }}" id="load" role="tabpanel">
                                <table id="datatable-buttons-load"
                                    class="table table-striped table-bordered dt-responsive nowrap"
                                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <span><a href="{{ route('allloadsExcel', 'Loads') }}"><button
                                                class="btn btn-primary waves-effect waves-light mb-3 exlbtn">All Load
                                                Excel</button></a></span>
                                    <thead>
                                        <tr>
                                            <th>Sr No</th>
                                            <th>Load #</th>
                                            <th>Action</th>
                                            <th>Agent</th>
                                            <th>Invoice #</th>
                                            <th>Invoice Date</th>
                                            <th>W/O #</th>
                                            <th>Customer Name</th>
                                            <th>Office</th>
                                            <th>Team Leader</th>
                                            <th>Manager</th>
                                            <th>Load Creation Date</th>
                                            <th>Shipper Date</th>
                                            <th>Delivered Date</th>
                                            <th>Actual Del Date</th>
                                            <th>Carrier MC</th>
                                            <th>Carrier</th>
                                            <th>Pickup Location</th>
                                            <th>Unloading Location</th>
                                            <th>Load Status</th>
                                            <th>Cust Final Rate</th>
                                            <th>Carrier Final Rate</th>
                                            <th>Margin</th>
                                            <th>Margin %</th>
                                            <th>Aging</th>
                                            <th>Delivered Aging</th>
                                            <th>Carrier PDF</th>
                                            <th>Shipper PDF</th>
                                        </tr>
                                    </thead>
                                    <tbody id="load-search">
                                        @include('admin.all_data.loads')
                                    </tbody>
                                </table>
                                <div class="custom-pagination">
                                    {!! render_pagination_links($loads->setPageName('loads')) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- end col -->
        </div> <!-- end row -->



    </div> <!-- container-fluid -->
</div>
<!-- End Page-content -->
<script>
    $(document).on('click', '.custom-pagination a', function (e) {
        e.preventDefault();

        let href = $(this).attr('href');
        if (!href) return;

        // Get active tab (without the #)
        let activeTab = $('.nav-link.active').attr('href');
        let resultContainer = '';
        let tableSelector = '';
        let pageName = '';

        if (activeTab === '#customer') {
            resultContainer = '#customer-search'; pageName = 'customer';
            tableSelector = '#datatable-buttons-customer';
        } else if (activeTab === '#carrier') {
            resultContainer = '#carrier-search'; pageName = 'carrier';
            tableSelector = '#datatable-buttons-carrier';
        } else if (activeTab === '#consignee') {
            resultContainer = '#consignee-search'; pageName = 'consignee';
            tableSelector = '#datatable-buttons-consignee';
        } else if (activeTab === '#shipper') {
            resultContainer = '#shipper-search'; pageName = 'shipper';
            tableSelector = '#datatable-buttons-shipper';
        } else if (activeTab === '#load') {
            resultContainer = '#load-search'; pageName = 'loads';
            tableSelector = '#datatable-buttons-load';
        } else {
            return; // Exit if it's not one of the expected tabs
        }
        const pageUrl = new URL(href, window.location.origin);
        const pageNumber = pageUrl.searchParams.get(pageName) || '1';
        $.ajax({
            url: '/admin/all_data',
            type: 'GET',
            data: {
                tab: activeTab,
                page: pageNumber,
                [pageName]: pageNumber
            },
            success: function (data) {
                if ($.fn.DataTable.isDataTable(tableSelector)) {
                    $(tableSelector).DataTable().destroy();
                }

                $(resultContainer).html(data.html);
                $(resultContainer).closest('.tab-pane').find('.custom-pagination')
                    .html(data.pagination || '').show();

                $(tableSelector).DataTable({
                    responsive: true,
                    dom: 'rtip',
                    buttons: ['copy', 'excel', 'pdf', 'colvis'],
                    info: false,
                    paging: false,
                    pageLength: 100,

                });

                const urlParams = new URLSearchParams();
                urlParams.set(pageName, pageNumber);
                window.history.pushState({}, '', '/admin/all_data?' + urlParams.toString());
            }
        });
    });


    $(document).ready(function () {
        $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            const target = $(e.target).attr("href");
            let inputSelector = '';
            let ajaxUrl = '';
            let resultContainer = '';
            let tableSelector = '';

            if (target === '#customer') {
                $('form.app-search .position-relative').attr('id', 'customers');
                inputSelector = '#customers input[name="query"]';
                ajaxUrl = '/admin/customer_search';
                resultContainer = '#customer-search';
                tableSelector = '#datatable-buttons-customer';
            } else if (target === '#carrier') {
                $('form.app-search .position-relative').attr('id', 'carriers');
                inputSelector = '#carriers input[name="query"]';
                ajaxUrl = '/admin/carrier_search';
                resultContainer = '#carrier-search';
                tableSelector = '#datatable-buttons-carrier';
            } else if (target === '#consignee') {
                $('form.app-search .position-relative').attr('id', 'consignees');
                inputSelector = '#consignees input[name="query"]';
                ajaxUrl = '/admin/consignee_search';
                resultContainer = '#consignee-search';
                tableSelector = '#datatable-buttons-consignee';
            } else if (target === '#shipper') {
                $('form.app-search .position-relative').attr('id', 'shippers');
                inputSelector = '#shippers input[name="query"]';
                ajaxUrl = '/admin/shipper_search';
                resultContainer = '#shipper-search';
                tableSelector = '#datatable-buttons-shipper';
            } else if (target === '#load') {
                $('form.app-search .position-relative').attr('id', 'loads');
                inputSelector = '#loads input[name="query"]';
                ajaxUrl = '/admin/load_search';
                resultContainer = '#load-search';
                tableSelector = '#datatable-buttons-load';
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
                        buttons: ['copy', 'excel', 'pdf', 'colvis'],
                        searching: false,
                        info: false,
                        paging: false,
                        pageLength: 100,
                    });

                    $('.loader-container').addClass('hide');
                },
                // error: function (xhr) {
                // console.error("AJAX error:", xhr.responseText);
                // $('.loader-container').addClass('hide');
                // }
            });

        });
    });
</script>
<script>
    $(document).ready(function () {
        // Set initial ID for the search form (fallback)
        $('form.app-search .position-relative').attr('id', 'customers');

        let initializedTabs = {};

        function initializeTab(target) {
            let inputSelector = '';
            let ajaxUrl = '';
            let resultContainer = '';
            let tableSelector = '';

            if (target === '#customer') {
                $('form.app-search .position-relative').attr('id', 'customers');
                inputSelector = '#customers input[name="query"]';
                ajaxUrl = '/admin/customer_search';
                resultContainer = '#customer-search';
                tableSelector = '#datatable-buttons-customer';
            } else if (target === '#carrier') {
                $('form.app-search .position-relative').attr('id', 'carriers');
                inputSelector = '#carriers input[name="query"]';
                ajaxUrl = '/admin/carrier_search';
                resultContainer = '#carrier-search';
                tableSelector = '#datatable-buttons-carrier';
            } else if (target === '#consignee') {
                $('form.app-search .position-relative').attr('id', 'consignees');
                inputSelector = '#consignees input[name="query"]';
                ajaxUrl = '/admin/consignee_search';
                resultContainer = '#consignee-search';
                tableSelector = '#datatable-buttons-consignee';
            } else if (target === '#shipper') {
                $('form.app-search .position-relative').attr('id', 'shippers');
                inputSelector = '#shippers input[name="query"]';
                ajaxUrl = '/admin/shipper_search';
                resultContainer = '#shipper-search';
                tableSelector = '#datatable-buttons-shipper';
            } else if (target === '#load') {
                $('form.app-search .position-relative').attr('id', 'loads');
                inputSelector = '#loads input[name="query"]';
                ajaxUrl = '/admin/load_search';
                resultContainer = '#load-search';
                tableSelector = '#datatable-buttons-load';
            } else {
                return; // Exit if it's not one of the expected tabs
            }

            $(inputSelector).on('keyup', function () {
                let query = $(this).val().trim();

                clearTimeout($.data(this, 'timer'));
                let wait = setTimeout(() => {
                    if (query.length > 0) {
                        $('.loader-container').removeClass('hide');

                        $.ajax({
                            url: ajaxUrl,
                            type: 'GET',
                            data: {
                                query: query
                            },
                            success: function (response) {
                                if ($.fn.DataTable.isDataTable(tableSelector)) {
                                    $(tableSelector).DataTable().destroy();
                                }

                                $(resultContainer).html(response);

                                $(tableSelector).DataTable({
                                    responsive: true,
                                    dom: 'Bfrtip',
                                    buttons: ['copy', 'excel', 'pdf',
                                        'colvis'
                                    ],
                                    pageLength: 100,
                                });

                                $('.loader-container').addClass('hide');
                            },
                            error: function (xhr) {
                                console.error("AJAX error:", xhr.responseText);
                                $('.loader-container').addClass('hide');
                            }
                        });
                    } else {
                        $(resultContainer).html('');
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
            //}
        });

        // --- Trigger initialization for default active tab on page load ---
        const activeTabLink = $('a[data-bs-toggle="tab"].active');
        if (activeTabLink.length > 0) {
            const activeTabTarget = activeTabLink.attr("href");
            initializeTab(activeTabTarget);
        }
    });



    $(document).ready(function () {
        const tables = {
            '#customer': {
                selector: '#datatable-buttons-customer',
                initialized: false
            },
            '#carrier': {
                selector: '#datatable-buttons-carrier',
                initialized: false
            },
            '#consignee': {
                selector: '#datatable-buttons-consignee',
                initialized: false
            },
            '#shipper': {
                selector: '#datatable-buttons-shipper',
                initialized: false
            },
            '#load': {
                selector: '#datatable-buttons-load',
                initialized: false
            }
        };

        $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            const target = $(e.target).attr('href');
            if (tables[target] && !tables[target].initialized) {
                $(tables[target].selector).DataTable({
                    responsive: true,
                    dom: 'Bfrtip',
                    buttons: ['copy', 'excel', 'pdf', 'colvis'],
                    pageLength: 100,
                    info: false,
                    paging: false,
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
                pageLength: 100,
                info: false,
                paging: false,
            });
            tables[activeTab].initialized = true;
        }


        $('#agentFilter').on('change', function () {
            var selectedAgent = $(this).val();

            if (selectedAgent) {
                // Search only in Agent column (column index 2, zero-based)
                table.column(2).search('^' + selectedAgent + '$', true, false).draw();
            } else {
                // Clear search filter on Agent column
                table.column(2).search('').draw();
            }
        })
    });
</script>


<script>
    document.addEventListener('click', function (e) {

        const btn = e.target.closest('.privacy_toggle');
        if (!btn) return;

        const allData = document.querySelectorAll('.hide_blur_privacy');
        const icon = btn.querySelector('i');

        // Check current state (are we blurred?)
        const isBlurred = allData.length && allData[0].classList.contains('blurred');

        // Toggle blur on ALL privacy fields
        allData.forEach(el => {
            el.classList.toggle('blurred', !isBlurred);
        });

        // Toggle all eye icons
        document.querySelectorAll('.privacy_toggle i').forEach(i => {
            i.classList.toggle('fa-eye', isBlurred); // show eye when unblur
            i.classList.toggle('fa-eye-slash', !isBlurred); // show slash when blurred
        });

    });
</script>
@endsection