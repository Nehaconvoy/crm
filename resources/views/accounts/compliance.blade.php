@extends('layout.compact.app') <!-- This links to the app.blade.php layout -->

@section('content')
<style>
div#datatable-buttons-cpr_filter, ul.pagination.pagination-rounded, div#datatable-buttons_filter{
    display: none;
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
    background-color: rgb(212, 237, 218);
    color: rgb(87, 21, 26);
    margin-bottom: 10px;
    border: 1px solid rgb(195, 230, 203);
    border-radius: 4px;
    position: fixed;
    width: 20%;
    right: 10px;
    z-index: 9999;
    top: 10px;
}
select#rate_check-21291 {
    border: 1px solid #000;
}

.mc-check-selector,
.compliance-select {
    height: 31px;
    min-height: 31px;
    padding-top: 2px;
    padding-bottom: 2px;
}

.compliance-select + .select2-container,
.compliance-select + .select2-container .select2-selection--single {
    height: 32px;
    line-height: normal;
}

.compliance-select + .select2-container {
    transform: translateY(-2px);
}

.compliance-select + .select2-container .select2-selection__rendered {
    line-height: 30px;
    text-align: center;
    padding-top: 0;
    padding-bottom: 0;
}

.compliance-select + .select2-container .select2-selection__arrow {
    height: 30px;
    width: 30px;
}

.compliance-select + .select2-container .select2-selection__clear {
    line-height: normal;
    padding: 0;
}

.compliance-select + .select2-container > .selection,
.compliance-select + .select2-container > .dropdown-wrapper {
    height: 32px;
    padding: 0;
    line-height: normal;
}

#datatable-buttons-cpr .compliance-select + .select2-container {
    width: 150px !important;
    min-width: 150px;
}

#datatable-buttons-cpr .select2-results__options {
    max-height: 180px;
    overflow-y: auto;
}

#datatable-buttons-cpr .select2-results__option {
    min-height: 30px;
    padding: 6px 8px;
    white-space: nowrap;
}

.select2-container--open {
    z-index: 10000 !important;
}

.select2-container--open .select2-dropdown {
    z-index: 10001 !important;
    min-width: 150px;
}

.select2-container--open .select2-results__options {
    max-height: 180px !important;
    overflow-y: auto !important;
}

.select2-container--open .select2-results__option {
    min-height: 30px;
    padding: 6px 8px;
    white-space: nowrap;
}

.select2-container--open .select2-dropdown--below {
    top: 4px !important;
}

.cpr-documents-modal .modal-dialog {
    width: min(800px, calc(100vw - 2rem));
    max-width: 800px;
}

.cpr-documents-modal .modal-body {
    max-height: 70vh;
    overflow-y: auto;
}

.cpr-documents-modal .accordion-button {
    min-height: 42px;
    white-space: normal;
    text-align: left;
}

.cpr-documents-modal .accordion-body {
    max-height: 55vh;
    overflow: auto;
}

#datatable-buttons-cpr td {
    vertical-align: middle;
}

#datatable-buttons-cpr select.cpr-native-select {
    width: 150px;
    min-width: 150px;
    max-width: 150px;
    height: 32px;
    min-height: 32px;
    padding: 4px 24px 4px 8px;
    font-size: 13px;
    line-height: 1.2;
    appearance: auto;
    -webkit-appearance: menulist;
    background-image: none !important;
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
                                    <h4 class="mb-sm-0">Compliance</h4>

                                    <div class="page-title-right">
                                        <ol class="breadcrumb m-0">
                                            <li class="breadcrumb-item"><a href="javascript: void(0);">CCI</a></li>
                                            <li class="breadcrumb-item active">Compliance</li>
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
        
                                        <h4 class="card-title">Compliance</h4>
                                       
        
                                        <ul class="nav nav-tabs nav-tabs-custom nav-justified" role="tablist">
                                            <li class="nav-item" role="presentation">
                                                <a class="nav-link {{ $activeTab === 'mc' ? 'active' : '' }}" data-bs-toggle="tab" href="#mc" role="tab" aria-selected="true">
                                                    <span class="d-block d-sm-none"><i class="fas fa-home"></i></span>
                                                    <span class="d-none d-sm-block">Mc Check</span> 
                                                </a>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <a class="nav-link {{ $activeTab === 'cpr' ? 'active' : '' }}" data-bs-toggle="tab" href="#cpr" role="tab" aria-selected="false" tabindex="-1">
                                                    <span class="d-block d-sm-none"><i class="far fa-user"></i></span>
                                                    <span class="d-none d-sm-block">CPR Check</span> 
                                                </a>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <a class="nav-link {{ $activeTab === 'block_carrier' ? 'active' : '' }}" data-bs-toggle="tab" href="#block_carrier" role="tab" aria-selected="false" tabindex="-1">
                                                    <span class="d-block d-sm-none"><i class="far fa-user"></i></span>
                                                    <span class="d-none d-sm-block">Blocked Carrier</span> 
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
                                            <div class="tab-pane {{ $activeTab === 'mc' ? 'active show' : '' }}" id="mc" role="tabpanel">
                                               
                                                <table id="datatable-buttons" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
												<span><a href="{{ route('allcomplianceloadsExcel', 'Mc') }}"><button class="btn btn-primary waves-effect waves-light mb-3 exlbtn">All MC Excel</button></a></span>
                                                    <thead>
                                                    <tr>
                                                        <th>MC No</th>
                                                        <th>DOT</th>
                                                        <th>Carrier Name</th>
                                                        <th>Agent</th>
                                                        <th>Added date</th>
                                                        <th>MC Check</th>
														<th>Setup</th>
                                                        <th>MC Status</th>
														<th>Setup Status</th>
                                                                <th>Upload Documents</th>
                                                                <th>Uploaded Documents</th>
                                                        <th>Documents</th>
                                                        <th>Carrier Block</th>
                                                    </tr>
                                                    </thead>


                                                    <tbody id="compliance-mc-search">
                                                        @include('accounts.partials.compliance_mc_table')
                                                    </tbody>
                                                </table>
                                                 <div class="custom-pagination">
                                                    {!! render_pagination_links($carriers->setPageName('mc')) !!}
                                                </div>
                                            </div>
                                            <div class="tab-pane {{ $activeTab === 'cpr' ? 'active show' : '' }}" id="cpr" role="tabpanel">
                                                <table id="datatable-buttons-cpr" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
												<span><a href="{{ route('allcomplianceloadsExcel', 'Cpr') }}"><button class="btn btn-primary waves-effect waves-light mb-3 exlbtn">All CPR Excel</button></a></span>
                                                    <thead>
                                                    <tr>
                                                        <th>Load #</th>
                                                        <th>Logs Check</th>
                                                        <th>W/O #</th>
                                                        <th>Agent</th>
                                                        <th>Customer #</th>
                                                        <th>Customer Final Rate</th>
                                                        <th>Office</th>
                                                        <th>Team Leader</th>
                                                        <th>Manager</th>
                                                        <th>Load Create Date</th>
                                                        <th>Shipper Date</th>
                                                        <th>Delivery Date</th>
                                                        <th>Equipment Type</th>
                                                        <th>Carrier Name</th>
                                                        <th>Carrier Final Rate</th>
                                                        <th>Pickup Location</th>
                                                        <th>Unloading Location</th>
                                                        <th>Rate check</th>
                                                        <th>Load Status</th>
                                                        <th>CPR</th>
                                                        <th>Select CPR</th>
                                                        <th>Macro</th>
                                                        <th>No Of Macro</th>
                                                        <th>CPR Status</th>
                                                        <th>Documents</th>
                                                    </tr>
                                                    </thead>
                                                    

                                                    <tbody id="compliance-cpr-search">
                                                        @include('accounts.partials.compliance_cpr_table')
                                                    </tbody>
                                                </table>
                                                <div class="custom-pagination">
                                                    {!! render_pagination_links($loads->setPageName('cpr')) !!}
                                                </div>
                                            </div>

                                            <div class="tab-pane" id="block_carrier" role="tabpanel">

                                            
                                                <table id="datatable-buttons-block-carrier" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
												<!-- <span><a href="{{ route('allcomplianceloadsExcel', 'Cpr') }}"><button class="btn btn-primary waves-effect waves-light mb-3 exlbtn">All CPR Excel</button></a></span> -->
                                                    <thead>
                                                    <tr>
                                                       <th>MC No</th>
                                                        <th>DOT</th>
                                                        <th>Carrier Name</th>
                                                        <th>Agent</th>
                                                        <th>Added date</th>
                                                        <th>MC Status</th>
														<th>Setup Status</th>
                                                        <th>Carrier Block</th>
                                                    </tr>
                                                    </thead>
                                                    

                                                    <tbody id="compliance-block-carrier-search">
                                                        @include('accounts.partials.compliance_block_carrier_table')
                                                    </tbody>
                                                </table>
                                                <div class="custom-pagination">
                                                    {!! render_pagination_links($carrier_blocked->setPageName('block_carrier')) !!}
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
function initCprSelect2(scope) {
    if (typeof $.fn.select2 !== 'function') {
        return;
    }

    var $scope = scope ? $(scope) : $('#compliance-cpr-search');
    $scope.find('select.compliance-select').each(function () {
        var $select = $(this);
        var $emptyOption = $select.find('option[value=""]').first();
        var placeholder = $emptyOption.text().trim() || 'Select';

        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }

        $select.select2({
            width: '150px',
            dropdownParent: $('body'),
            minimumResultsForSearch: Infinity,
            placeholder: placeholder,
            allowClear: $emptyOption.length > 0
        });
    });
}

$(document).ready(function () {
    initCprSelect2();
});

   $(document).on('click', '.custom-pagination a', function(e) {
    e.preventDefault();

    let href = $(this).attr('href');
    if (!href) return;
	let resultContainer = '';
    let tableSelector = '';
    let pageName = '';
    // Get active tab (without the #)
    let activeTab = $('.nav-link.active').attr('href');

	if (activeTab === '#cpr') {
         resultContainer = '#compliance-cpr-search'; pageName = 'cpr';
         tableSelector = '#datatable-buttons-cpr';
	} else if (activeTab === '#mc') {
        resultContainer = '#compliance-mc-search'; pageName = 'mc';
            tableSelector = '#datatable-buttons';
	} else if (activeTab === '#block_carrier') {
        resultContainer = '#compliance-block-carrier-search'; pageName = 'block_carrier';
            tableSelector = '#datatable-buttons-block-carrier';
	} else {
		return;
	}
    const pageUrl = new URL(href, window.location.origin);
    const pageNumber = pageUrl.searchParams.get(pageName) || '1';
    $.ajax({
        url: '/account/compliance',
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
                            if (activeTab === '#cpr') {
                                initCprSelect2(resultContainer);
                            } else if (activeTab === '#mc' && typeof window.initSelect2 === 'function') {
                window.initSelect2(resultContainer);
            }
            $(resultContainer).closest('.tab-pane').find('.custom-pagination')
                .html(data.pagination || '').show();

            $(tableSelector).DataTable({
                responsive: true,
                dom: 'Bfrtip',
                paging: false,
                stateSave: true,
                                        buttons: [
                                    {
                                        extend: 'colvis',
                                        text: 'Select Columns'
                                    }
                                ],
            });

            const urlParams = new URLSearchParams();
            urlParams.set(pageName, pageNumber);
            window.history.pushState({}, '', '/account/compliance?' + urlParams.toString());
        }
    });
});
</script>
<script>

$(document).ready(function () {
    $('form.app-search .position-relative').attr('id', 'mcsearch');

    let initializedTabs = {};

    function initializeTab(target) {
        let inputSelector = '';
        let ajaxUrl = '';
        let resultContainer = '';
        let tableSelector = '';

        if (target === '#cpr') {
            $('form.app-search .position-relative').attr('id', 'cprsearch');
            inputSelector = '#cprsearch input[name="query"]';
            ajaxUrl = '/account/compliance-search-cpr';
            resultContainer = '#compliance-cpr-search';
            tableSelector = '#datatable-buttons-cpr';
        } else if (target === '#mc') {
            $('form.app-search .position-relative').attr('id', 'mcsearch');
            inputSelector = '#mcsearch input[name="query"]';
            ajaxUrl = '/account/compliance-search-mc';
            resultContainer = '#compliance-mc-search';
            tableSelector = '#datatable-buttons';
        } else {
            return;
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
                        data: { query: query },
                        success: function (response) {
                            if ($.fn.DataTable.isDataTable(tableSelector)) {
                                $(tableSelector).DataTable().destroy();
                            }
                            $(resultContainer).html(response);
                            if (target === '#cpr') {
                                initCprSelect2(resultContainer);
                            } else if (target === '#mc' && typeof window.initSelect2 === 'function') {
                                window.initSelect2(resultContainer);
                            }
                            $(tableSelector).DataTable({
                                responsive: true,
                                dom: 'Bfrtip',
                                 stateSave: true,
                                        buttons: [
                                    {
                                        extend: 'colvis',
                                        text: 'Select Columns'
                                    }
                                ],
                                paging: false,
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
            if (target === '#cpr') {
                initCprSelect2();
            }
        //}
    });

    const activeTabLink = $('a[data-bs-toggle="tab"].active');
    if (activeTabLink.length > 0) {
        const activeTabTarget = activeTabLink.attr("href");
        initializeTab(activeTabTarget);
    }
});


$(document).ready(function() {
    
    // Delay CPR DataTable initialization until tab is shown
    let cprTableInitialized = false;
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr("href");
        if (target === '#cpr' && !cprTableInitialized) {
            $('#datatable-buttons-cpr').DataTable({
                responsive: true,
                dom: 'Bfrtip',
                stateSave: true,
                                        buttons: [
                                    {
                                        extend: 'colvis',
                                        text: 'Select Columns'
                                    }
                                ],
            });
            cprTableInitialized = true;
        }
    });

});

</script>


@endsection