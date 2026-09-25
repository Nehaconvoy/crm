@extends('layout.compact.app')
<!-- This links to the app.blade.php layout -->

@section('content')
<style>
    /* General */
    .load-row {
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    /* Open – Red */
    .table-bordered>tbody>tr.row-open td {
        background-color: #e6ffed !important;
        color: #000 !important;
    }

    /* Delivered – Yellow */
    .table-bordered>tbody>tr.row-delivered td {
        background-color: #fff8dc !important;
        color: #000 !important;
    }

    /* Covered – Light Purple */
    .table-bordered>tbody>tr.row-covered td {
        background-color: #f3e8ff !important;
        color: #000 !important;
    }

    /* On Route – Light Blue */
    .table-bordered>tbody>tr.row-onroute td {
        background-color: #e0f2fe !important;
        color: #000 !important;
    }

    /* Unloading – Light Orange */
    .table-bordered>tbody>tr.row-unloading td {
        background-color: #ffedd5 !important;
        color: #000 !important;
    }

    /* Completed – Gray */
    .table-bordered>tbody>tr.row-completed td {
        background-color: #f3f4f6 !important;
        color: #000 !important;
    }

    /* Completed & Paid – Green */
    .table-bordered>tbody>tr.row-completed-paid td {
        background-color: #a6ce3a !important;
        color: #000 !important;
    }

    /* Completed & Paid Record – Teal */
    .table-bordered>tbody>tr.row-completed-paidrecord td {
        background-color: #e0fdfa !important;
        color: #000 !important;
    }

    .table-bordered>tbody>tr.row-cancelled td {
        background-color: #ffe5e5 !important;
        color: #000 !important;
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

    div#datatable-buttons-all_load_filter {
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
                    <h4 class="mb-sm-0">Status Data</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">CCI</a></li>
                            <li class="breadcrumb-item active">Status Data</li>
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

                        <h4 class="card-title">Status Data</h4>
                        <div class="my-4 d-flex justify-content-between align-items-center">
                            <!-- Add Load Button -->
                            <a href="{{ route('create_load') }}">
                                <button type="button" class="btn btn-primary waves-effect waves-light">
                                    + Add Load
                                </button>
                            </a>

                            <!-- Status Legend -->

                            <ul class="list-inline m-0" style="display: flex;">
                                <li class="list-inline-item d-flex align-items-center me-3">
                                    <span class="status-box" style="background:#e6ffed;"></span> Open
                                </li>
                                <li class="list-inline-item d-flex align-items-center me-3">
                                    <span class="status-box" style="background:#fff8dc;"></span> Delivered
                                </li>
                                <li class="list-inline-item d-flex align-items-center me-3">
                                    <span class="status-box" style="background:#e0fdfa;"></span> Mark Paid
                                </li>
                                <li class="list-inline-item d-flex align-items-center me-3">
                                    <span class="status-box" style="background:#a6ce3a;"></span> Invoiced
                                </li>
                                <!-- <li class="list-inline-item d-flex align-items-center me-3">
                                        <span class="status-box" style="background:#ffe5e5;"></span> Invoice
                                    </li> -->
                                <li class="list-inline-item d-flex align-items-center me-3">
                                    <span class="status-box" style="background:#f3f4f6 !important;"></span> Completed
                                </li>
                                <li class="list-inline-item d-flex align-items-center me-3">
                                    <span class="status-box" style="background:#ffe5e5 !important;"></span> Cancelled
                                </li>
                                <li class="list-inline-item d-flex align-items-center me-3">
                                    <span class="status-box" style="background:#f3e8ff !important"></span> Covered
                                </li>
                                <li class="list-inline-item d-flex align-items-center me-3">
                                    <a href="javascript:void(0)" class="privacy_toggle">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                </li>

                            </ul>
                        </div>
                        <div class="row filterdata">
                            <h4>Filter By Agents</h4>

                            <div class="col-2 filter-group" style="min-width:0;overflow:hidden;">
                                <label for="officeSelect">Office:</label>
                                <select id="officeSelect" class="form-control no-select2" style="width:100%;">
                                    <option value="">-- Select Office --</option>
                                    @foreach($office as $data)
                                    <option value="{{$data->id}}">{{$data->office_name}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-2 filter-group" style="min-width:0;overflow:hidden;">
                                <label for="managerSelect">Manager:</label>
                                <select id="managerSelect" class="form-control no-select2" style="width:100%;">
                                    <option value="">-- Select Manager --</option>
                                    @foreach($manager as $data)
                                    <option value="{{$data->id}}">{{$data->manager}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-2 filter-group" style="min-width:0;overflow:hidden;">
                                <label for="teamLeaderSelect">Team Leader:</label>
                                <select id="teamLeaderSelect" class="form-control no-select2" style="width:100%;">
                                    <option value="">-- Select Team Leader --</option>
                                    @foreach($teamlead as $data)
                                    <option value="{{$data->id}}">{{$data->tl}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-2 filter-group" style="min-width:0;overflow:hidden;">
                                <label for="agentSelect">Broker/Customer:</label>
                                <select id="agentSelect" class="form-control no-select2" style="width:100%;">
                                    <option value="">-- Select Broker/Customer --</option>
                                    @foreach($agent as $data)
                                    <option value="{{$data->id}}">{{$data->name}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <button class="col-2 btn btn-primary waves-effect waves-light" onclick="filterResults()"
                                style="
    padding: 9px; height: fit-content; margin-top: 26px;width: 100px !important;
    margin-right: 10px;">Filter</button>
                            <button class="col-2 btn btn-secondary waves-effect waves-light" onclick="location.reload()"
                                style="
    padding: 9px; height: fit-content; margin-top: 26px;width: 100px !important;
    margin-right: 10px;">Reset</button>
                        </div>



                        <ul class="nav nav-tabs nav-tabs-custom nav-justified" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link active" data-bs-toggle="tab" href="#all_load" role="tab"
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
                                    <span class="d-block d-sm-none"><i class="far fa-user"></i></span>
                                    <span class="d-none d-sm-block">Delivered</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" data-bs-toggle="tab" href="#completed" role="tab"
                                    aria-selected="false" tabindex="-1">
                                    <span class="d-block d-sm-none"><i class="far fa-envelope"></i></span>
                                    <span class="d-none d-sm-block">Completed</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" data-bs-toggle="tab" href="#invoiced" role="tab"
                                    aria-selected="false" tabindex="-1">
                                    <span class="d-block d-sm-none"><i class="far fa-envelope"></i></span>
                                    <span class="d-none d-sm-block">Invoiced</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" data-bs-toggle="tab" href="#invoiced_paid" role="tab"
                                    aria-selected="false" tabindex="-1">
                                    <span class="d-block d-sm-none"><i class="fas fa-cog"></i></span>
                                    <span class="d-none d-sm-block">Invoiced / Paid</span>
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

                            <div class="tab-pane active" id="all_load" role="tabpanel">
                                <table id="datatable-buttons-all_load" class="table table-bordered dt-responsive nowrap"
                                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Sr No</th>
                                            <th>Log Check</th>
                                            <th>Load #</th>
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
                                            <th>Carrier Name</th>
                                            <th>Pickup Location</th>
                                            <th>Unloading Location</th>
                                            <th>Load Status</th>
                                            <th>Invoice Status</th>
                                            <th>Cust Final Rate</th>
                                            <th>Carrier Final Rate</th>
                                            <th>Margin</th>
                                            <th>Margin %</th>
                                            <th>Aging</th>
                                            <th>CPR Status</th>
                                        </tr>
                                    </thead>


                                    <tbody id="all_load-search">
                                        @include('admin.home.all_load')
                                    </tbody>
                                </table>
                                <div class="custom-pagination">
                                    {!! render_pagination_links($broker_status->setPageName('all_load')) !!}
                                </div>
                            </div>
                            <div class="tab-pane" id="open" role="tabpanel">
                                <table id="datatable-buttons-open"
                                    class="table table-striped table-bordered dt-responsive nowrap"
                                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <span><a href="{{ route('loadsExcel', 'Open') }}"><button
                                                class="btn btn-primary waves-effect waves-light mb-3 exlbtn">All Open
                                                Excel</button></a></span>
                                    <thead>
                                        <tr>
                                            <th>Sr No</th>
                                            <th>Load #</th>
                                            <th>Agent</th>
                                            <th>W/O #</th>
                                            <th>Customer Name</th>
                                            <th>Office</th>
                                            <th>Team Leader</th>
                                            <th>Manager</th>
                                            <th>Load Creation Date</th>
                                            <th>Shipper Date</th>
                                            <th>Delivered Date</th>
                                            <th>Carrier Name</th>
                                            <th>Pickup Location</th>
                                            <th>Unloading Location</th>
                                            <th>Load Status</th>
                                        </tr>
                                    </thead>


                                    <tbody id="open-search">
                                        @include('admin.home.open_load')
                                    </tbody>
                                </table>
                                <div class="custom-pagination">
                                    {!! render_pagination_links($open->setPageName('open')) !!}
                                </div>
                            </div>
                            <div class="tab-pane" id="delivered" role="tabpanel">
                                <table id="datatable-buttons-delivered"
                                    class="table table-striped table-bordered dt-responsive nowrap"
                                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <span><a href="{{ route('loadsExcel', 'Delivered') }}"><button
                                                class="btn btn-primary waves-effect waves-light mb-3 exlbtn">All
                                                Delivered Excel</button></a></span>
                                    <thead>
                                        <tr>
                                            <th>Sr No</th>
                                            <th>Load #</th>
                                            <th>Agent </th>
                                            <th>W/O #</th>
                                            <th>Customer Name</th>
                                            <th>Office</th>
                                            <th>Team Leader</th>
                                            <th>Manager</th>
                                            <th>Load Creation Date</th>
                                            <th>Shipper Date</th>
                                            <th>Delivered Date</th>
                                            <th>Actual Del Date</th>
                                            <th>Carrier Name</th>
                                            <th>Pickup Location</th>
                                            <th>Unloading Location</th>
                                            <th>Load Status</th>
                                        </tr>
                                    </thead>


                                    <tbody id="delivered-search">
                                        @include('admin.home.delivered')
                                    </tbody>
                                </table>
                                <div class="custom-pagination">
                                    {!! render_pagination_links($deliverd->setPageName('delivered')) !!}
                                </div>
                            </div>
                            <div class="tab-pane" id="completed" role="tabpanel">
                                <table id="datatable-buttons-completed"
                                    class="table table-striped table-bordered dt-responsive nowrap"
                                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <span><a href="{{ route('loadsExcel', 'Completed') }}"><button
                                                class="btn btn-primary waves-effect waves-light mb-3 exlbtn">All
                                                Completed Excel</button></a></span>
                                    <thead>
                                        <tr>
                                            <th>Sr No</th>
                                            <th>Load #</th>
                                            <th>Agent</th>
                                            <th>W/O #</th>
                                            <th>Customer Name</th>
                                            <th>Office</th>
                                            <th>Team Leader</th>
                                            <th>Manager</th>
                                            <th>Load Creation Date</th>
                                            <th>Shipper Date</th>
                                            <th>Delivered Date</th>
                                            <th>Actual Del Date</th>
                                            <th>Carrier Name</th>
                                            <th>Pickup Location</th>
                                            <th>Unloading Location</th>
                                            <th>Load Status</th>
                                        </tr>
                                    </thead>


                                    <tbody id="completed-search">
                                        @include('admin.home.completed')
                                    </tbody>
                                </table>
                                <div class="custom-pagination">
                                    {!! render_pagination_links($complete->setPageName('completed')) !!}
                                </div>
                            </div>
                            <div class="tab-pane" id="invoiced" role="tabpanel">
                                <table id="datatable-buttons-invoiced"
                                    class="table table-striped table-bordered dt-responsive nowrap"
                                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <span><a href="{{ route('loadsExcel', 'Paid') }}"><button
                                                class="btn btn-primary waves-effect waves-light mb-3 exlbtn">All Paid
                                                Excel</button></a></span>
                                    <thead>
                                        <tr>
                                            <th>Sr No</th>
                                            <th>Load #</th>
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
                                            <th>Carrier Name</th>
                                            <th>Pickup Location</th>
                                            <th>Unloading Location</th>
                                            <th>Load Status</th>
                                        </tr>
                                    </thead>


                                    <tbody id="invoiced-search">
                                        @include('admin.home.invoiced')
                                    </tbody>
                                </table>
                                <div class="custom-pagination">
                                    {!! render_pagination_links($invoice_paid->setPageName('invoiced')) !!}
                                </div>
                            </div>
                            <div class="tab-pane" id="invoiced_paid" role="tabpanel">
                                <table id="datatable-buttons-invoiced_paid"
                                    class="table table-striped table-bordered dt-responsive nowrap"
                                    style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <span><a href="{{ route('loadsExcel', 'Paid Record') }}"><button
                                                class="btn btn-primary waves-effect waves-light mb-3 exlbtn">All Paid
                                                Record Excel</button></a></span>
                                    <thead>
                                        <tr>
                                            <th>Sr No</th>
                                            <th>Load #</th>
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
                                            <th>Carrier</th>
                                            <th>Pickup Location</th>
                                            <th>Unloading Location</th>
                                            <th>Load Status</th>
                                        </tr>
                                    </thead>


                                    <tbody id="invoiced_paid-search">
                                        @include('admin.home.invoiced_paid')
                                    </tbody>
                                </table>
                                <div class="custom-pagination">
                                    {!! render_pagination_links($paid_record->setPageName('invoiced_paid')) !!}
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
    $(document).ready(function () {

        // When Office is selected
        $('#officeSelect').on('change', function () {
            var officeId = $(this).val();
            if (officeId) {
                $.ajax({
                    url: '/admin/get-related-users/' + officeId,
                    type: "GET",
                    dataType: "json",
                    success: function (data) {
                        // Populate Managers
                        $('#managerSelect').empty().append(
                            '<option value="">-- Select Manager --</option>');
                        $.each(data.managers, function (key, value) {
                            $('#managerSelect').append('<option value="' + value
                                .id + '">' + value.manager + '</option>');
                        });

                        // Populate Team Leaders
                        $('#teamLeaderSelect').empty().append(
                            '<option value="">-- Select Team Leader --</option>');
                        $.each(data.team_leaders, function (key, value) {
                            $('#teamLeaderSelect').append('<option value="' + value
                                .id + '">' + value.tl + '</option>');
                        });

                        // Populate Agents
                        $('#agentSelect').empty().append(
                            '<option value="">-- Select Agent --</option>');
                        $.each(data.agents, function (key, value) {
                            $('#agentSelect').append('<option value="' + value.id +
                                '">' + value.name + '</option>');
                        });
                    }
                });
            } else {
                // Reset all if no office
                $('#managerSelect, #teamLeaderSelect, #agentSelect').empty().append(
                    '<option value="">-- Select --</option>');
            }
        });

        // When Manager is selected
        $('#managerSelect').on('change', function () {
            var managerId = $(this).val();
            if (managerId) {
                $.ajax({
                    url: '/admin/get-related-by-manager/' + managerId,
                    type: "GET",
                    dataType: "json",
                    success: function (data) {
                        // Populate Team Leaders
                        $('#teamLeaderSelect').empty().append(
                            '<option value="">-- Select Team Leader --</option>');
                        $.each(data.team_leaders, function (key, value) {
                            $('#teamLeaderSelect').append('<option value="' + value
                                .id + '">' + value.tl + '</option>');
                        });

                        // Populate Agents
                        $('#agentSelect').empty().append(
                            '<option value="">-- Select Agent --</option>');
                        $.each(data.agents, function (key, value) {
                            $('#agentSelect').append('<option value="' + value.id +
                                '">' + value.name + '</option>');
                        });
                    }
                });
            }
        });

        // When Team Leader is selected
        $('#teamLeaderSelect').on('change', function () {
            var tlId = $(this).val();
            if (tlId) {
                $.ajax({
                    url: '/admin/get-related-by-tl/' + tlId,
                    type: "GET",
                    dataType: "json",
                    success: function (data) {
                        // Populate Agents
                        $('#agentSelect').empty().append(
                            '<option value="">-- Select Agent --</option>');
                        $.each(data.agents, function (key, value) {
                            $('#agentSelect').append('<option value="' + value.id +
                                '">' + value.name + '</option>');
                        });
                    }
                });
            }
        });
    });
</script>
<script>
    function filterResults() {
        const office = $('#officeSelect').val();
        const manager = $('#managerSelect').val();
        const teamLeader = $('#teamLeaderSelect').val();
        const agent = $('#agentSelect').val();

        let activeTab = $('.nav-link.active').attr('href');
        let resultContainer = '';
        let tableSelector = '';

        if (activeTab === '#all_load') {
            resultContainer = '#all_load-search';
            tableSelector = '#datatable-buttons-all_load';
        } else if (activeTab === '#open') {
            resultContainer = '#open-search';
            tableSelector = '#datatable-buttons-open';
        } else if (activeTab === '#delivered') {
            resultContainer = '#delivered-search';
            tableSelector = '#datatable-buttons-delivered';
        } else if (activeTab === '#completed') {
            resultContainer = '#completed-search';
            tableSelector = '#datatable-buttons-completed';
        } else if (activeTab === '#invoiced') {
            resultContainer = '#invoiced-search';
            tableSelector = '#datatable-buttons-invoiced';
        } else if (activeTab === '#invoiced_paid') {
            resultContainer = '#invoiced_paid-search';
            tableSelector = '#datatable-buttons-invoiced_paid';
        } else {
            return; // Exit if it's not one of the expected tabs
        }

        $.ajax({
          url: '/admin/search_by_filter',
          type: 'GET',
          dataType: 'json',   
        data: {
          tab: activeTab,
          office: office,
          manager: manager,
          teamLeader: teamLeader,
          agent: agent,
          
    },
    success: function (response) {
        if ($.fn.DataTable.isDataTable(tableSelector)) {
            $(tableSelector).DataTable().destroy();
        }

        $(resultContainer).html(response.html);

        // Pagination update
        let paginationContainer = $(resultContainer).closest('.tab-pane').find('.custom-pagination');
        paginationContainer.css('display', 'block');
        paginationContainer.html(response.pagination || '');
        paginationContainer.show();

        $(tableSelector).DataTable({
            responsive: true,
            dom: 'rtip',
            buttons: ['copy', 'excel', 'pdf', 'colvis'],
            paging: false,
        });
    },
    error: function (xhr) {
        console.error("Filter AJAX error:", xhr.responseText);
    }
});
    }
</script>
<script>
    // $(document).on('click', '.custom-pagination a', function (e) {
    //     e.preventDefault();

    //     let url = $(this).attr('href');

    //     // Get active tab (without the #)
    //     let activeTab = $('.nav-link.active').attr('href');
    //     let resultContainer = '';
    //     let tableSelector = '';

    //     if (activeTab === '#all_load') {
    //         resultContainer = '#all_load-search';
    //         tableSelector = '#datatable-buttons-all_load';
    //     } else if (activeTab === '#open') {
    //         resultContainer = '#open-search';
    //         tableSelector = '#datatable-buttons-open';
    //     } else if (activeTab === '#delivered') {
    //         resultContainer = '#delivered-search';
    //         tableSelector = '#datatable-buttons-delivered';
    //     } else if (activeTab === '#completed') {
    //         resultContainer = '#shipper-search';
    //         tableSelector = '#datatable-buttons-completed';
    //     } else if (activeTab === '#invoiced') {
    //         resultContainer = '#invoiced-search';
    //         tableSelector = '#datatable-buttons-invoiced';
    //     } else if (activeTab === '#invoiced_paid') {
    //         resultContainer = '#invoiced_paid-search';
    //         tableSelector = '#datatable-buttons-invoiced_paid';
    //     } else {
    //         return; // Exit if it's not one of the expected tabs
    //     }
    //     $.ajax({
    //         url: url,
    //         type: 'GET',
    //         data: {
    //             tab: activeTab
    //         },
    //         success: function (data) {
    //             if ($.fn.DataTable.isDataTable(tableSelector)) {
    //                 $(tableSelector).DataTable().destroy();
    //             }

    //             $(resultContainer).html(data);


    //             $(tableSelector).DataTable({
    //                 responsive: true,
    //                 dom: 'rtip',
    //                 buttons: ['copy', 'excel', 'pdf', 'colvis'],
    //                 paging: false,
    //                 pageLength: 50,
    //             });

    //             // Optional: update the browser URL
    //             window.history.pushState("", "", url);
    //         }
    //     });
    // });
$(document).on('click', '.custom-pagination a', function (e) {
    e.preventDefault();

    let href = $(this).attr('href');
    if (!href) return;

    let activeTab = $('.nav-link.active').attr('href');
    let resultContainer = '', tableSelector = '', pageName = '';

    if (activeTab === '#all_load') {
        resultContainer = '#all_load-search'; tableSelector = '#datatable-buttons-all_load'; pageName = 'all_load';
    } else if (activeTab === '#open') {
        resultContainer = '#open-search'; tableSelector = '#datatable-buttons-open'; pageName = 'open';
    } else if (activeTab === '#delivered') {
        resultContainer = '#delivered-search'; tableSelector = '#datatable-buttons-delivered'; pageName = 'delivered';
    } else if (activeTab === '#completed') {
        resultContainer = '#completed-search'; tableSelector = '#datatable-buttons-completed'; pageName = 'completed';
    } else if (activeTab === '#invoiced') {
        resultContainer = '#invoiced-search'; tableSelector = '#datatable-buttons-invoiced'; pageName = 'invoiced';
    } else if (activeTab === '#invoiced_paid') {
        resultContainer = '#invoiced_paid-search'; tableSelector = '#datatable-buttons-invoiced_paid'; pageName = 'invoiced_paid';
    } else {
        return;
    }

    // Extract the correct page parameter from the clicked link.
    let urlObj = new URL(href, window.location.origin);
    let pageNum = urlObj.searchParams.get(pageName) || urlObj.searchParams.get('page') || '1';

    const requestData = {
        tab: activeTab,
        page: pageNum,
        [pageName]: pageNum,
        office: $('#officeSelect').val(),
        manager: $('#managerSelect').val(),
        teamLeader: $('#teamLeaderSelect').val(),
        agent: $('#agentSelect').val(),
    };

    $('.loader-container').removeClass('hide');

    $.ajax({
        url: '/admin/search_by_filter',
        type: 'GET',
        dataType: 'json',
        data: requestData,
        success: function (response) {
            if ($.fn.DataTable.isDataTable(tableSelector)) {
                $(tableSelector).DataTable().destroy();
            }
            $(resultContainer).html(response.html);
            const paginationContainer = $(resultContainer).closest('.tab-pane').find('.custom-pagination');
            paginationContainer.css('display', 'block');
            paginationContainer.html(response.pagination || '');
            paginationContainer.show();
            $(tableSelector).DataTable({
                responsive: true,
                dom: 'rtip',
                buttons: ['copy', 'excel', 'pdf', 'colvis'],
                paging: false,
                pageLength: 50,
            });
            $('.loader-container').addClass('hide');
        },
        error: function (xhr) {
            console.error("Pagination AJAX error:", xhr.responseText);
            $('.loader-container').addClass('hide');
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

            if (target === '#all_load') {
                $('form.app-search .position-relative').attr('id', 'all_loads');
                inputSelector = '#all_loads input[name="query"]';
                ajaxUrl = '/admin/all_search';
                resultContainer = '#all_load-search';
                tableSelector = '#datatable-buttons-all_load';
            } else if (target === '#open') {
                $('form.app-search .position-relative').attr('id', 'opens');
                inputSelector = '#opens input[name="query"]';
                ajaxUrl = '/admin/open_search';
                resultContainer = '#open-search';
                tableSelector = '#datatable-buttons-open';
            } else if (target === '#delivered') {
                $('form.app-search .position-relative').attr('id', 'delivereds');
                inputSelector = '#delivereds input[name="query"]';
                ajaxUrl = '/admin/delivered_search';
                resultContainer = '#delivered-search';
                tableSelector = '#datatable-buttons-delivered';
            } else if (target === '#completed') {
                $('form.app-search .position-relative').attr('id', 'completeds');
                inputSelector = '#completeds input[name="query"]';
                ajaxUrl = '/admin/complete_search';
                resultContainer = '#completed-search';
                tableSelector = '#datatable-buttons-completed';
            } else if (target === '#invoiced') {
                $('form.app-search .position-relative').attr('id', 'invoiceds');
                inputSelector = '#invoiceds input[name="query"]';
                ajaxUrl = '/admin/invoice_search';
                resultContainer = '#invoiced-search';
                tableSelector = '#datatable-buttons-invoiced';
            } else if (target === '#invoiced_paid') {
                $('form.app-search .position-relative').attr('id', 'invoiced_paids');
                inputSelector = '#invoiced_paids input[name="query"]';
                ajaxUrl = '/admin/invoice_paid_search';
                resultContainer = '#invoiced_paid-search';
                tableSelector = '#datatable-buttons-invoiced_paid';
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

                    $(resultContainer).html(response.html);

                    $(tableSelector).DataTable({
                        responsive: true,
                        dom: 'Bfrtip',
                        buttons: ['copy', 'excel', 'pdf', 'colvis'],
                        pageLength: 50,
                        searching: false,
                        paging: false
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
        $('form.app-search .position-relative').attr('id', 'all_loads');

        let initializedTabs = {};

        function initializeTab(target) {
            let inputSelector = '';
            let ajaxUrl = '';
            let resultContainer = '';
            let tableSelector = '';

            if (target === '#all_load') {
                $('form.app-search .position-relative').attr('id', 'all_loads');
                inputSelector = '#all_loads input[name="query"]';
                ajaxUrl = '/admin/all_search';
                resultContainer = '#all_load-search';
                tableSelector = '#datatable-buttons-all_load';
            } else if (target === '#open') {
                $('form.app-search .position-relative').attr('id', 'opens');
                inputSelector = '#opens input[name="query"]';
                ajaxUrl = '/admin/open_search';
                resultContainer = '#open-search';
                tableSelector = '#datatable-buttons-open';
            } else if (target === '#delivered') {
                $('form.app-search .position-relative').attr('id', 'delivereds');
                inputSelector = '#delivereds input[name="query"]';
                ajaxUrl = '/admin/delivered_search';
                resultContainer = '#delivered-search';
                tableSelector = '#datatable-buttons-delivered';
            } else if (target === '#completed') {
                $('form.app-search .position-relative').attr('id', 'completeds');
                inputSelector = '#completeds input[name="query"]';
                ajaxUrl = '/admin/complete_search';
                resultContainer = '#completed-search';
                tableSelector = '#datatable-buttons-completed';
            } else if (target === '#invoiced') {
                $('form.app-search .position-relative').attr('id', 'invoiceds');
                inputSelector = '#invoiceds input[name="query"]';
                ajaxUrl = '/admin/invoice_search';
                resultContainer = '#invoiced-search';
                tableSelector = '#datatable-buttons-invoiced';
            } else if (target === '#invoiced_paid') {
                $('form.app-search .position-relative').attr('id', 'invoiced_paids');
                inputSelector = '#invoiced_paids input[name="query"]';
                ajaxUrl = '/admin/invoice_paid_search';
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
                        dataType: 'json',
                        data: {
                            query: query
                        },
                        success: function (response) {
                            if ($.fn.DataTable.isDataTable(tableSelector)) {
                                $(tableSelector).DataTable().destroy();
                            }

                            $(resultContainer).html(response.html);
                            if (query.length > 0) {
                    paginationContainer.hide(); 
                } else {
                    paginationContainer.html(response.pagination).show();
                }

                            $(tableSelector).DataTable({
                                responsive: true,
                                dom: 'Bfrtip',
                                buttons: ['copy', 'excel', 'pdf',
                                    'colvis'
                                ],
                                pageLength: 50,
                            });

                            $('.loader-container').addClass('hide');
                        },
                        error: function (xhr) {
                            console.error("AJAX error:", xhr.responseText);
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
            '#all_load': {
                selector: '#datatable-buttons-all_load',
                initialized: false
            },
            '#open': {
                selector: '#datatable-buttons-open',
                initialized: false
            },
            '#delivered': {
                selector: '#datatable-buttons-delivered',
                initialized: false
            },
            '#completed': {
                selector: '#datatable-buttons-completed',
                initialized: false
            },
            '#invoiced': {
                selector: '#datatable-buttons-invoiced',
                initialized: false
            },
            '#invoiced_paid': {
                selector: '#datatable-buttons-invoiced_paid',
                initialized: false
            }
        };

        $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            const target = $(e.target).attr('href');
            if (tables[target] && !tables[target].initialized) {
                $(tables[target].selector).DataTable({
                    responsive: true,
                    dom: 'frtip',
                    buttons: ['copy', 'excel', 'pdf', 'colvis'],
                    pageLength: 50,
                    paging: false,
                });
                tables[target].initialized = true;
            }
        });

        const activeTab = $('a[data-bs-toggle="tab"].active').attr('href');
        if (tables[activeTab] && !tables[activeTab].initialized) {
            $(tables[activeTab].selector).DataTable({
                responsive: true,
                dom: 'frtip',
                buttons: ['copy', 'excel', 'pdf', 'colvis'],
                pageLength: 50, // ✅ default show 50
                lengthMenu: [10, 25, 50, 100], // ✅ dropdown options
                paging: false,
            });
            tables[activeTab].initialized = true;
        }
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