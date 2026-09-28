<!doctype html>
<html lang="en">

    <head>
        
        <meta charset="utf-8" />
        <title>CRM CARGOCONVOY</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta content="Premium Multipurpose Admin & Dashboard Template" name="description" />
        <meta content="Themesdesign" name="author" />
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <!-- App favicon -->
        <link rel="icon" href="https://cargoconvoy.co/wp-content/uploads/2025/08/favicon_16x16.png" type="image/png">

        <!-- jquery.vectormap css -->
        <link href="{{ asset('assets/libs/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.css') }}" rel="stylesheet" type="text/css" />

        <!-- DataTables -->
        <link href="{{ asset('assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ asset('assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ asset('assets/libs/datatables.net-select-bs4/css/select.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />

        <!-- Responsive datatable examples -->
        <link href="{{ asset('assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />  

        <!-- Bootstrap Css -->
        <link href="{{ asset('assets/css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
        <!-- Icons Css -->
        <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
        <!-- App Css-->
        <link href="{{ asset('assets/css/app.min.css') }}" id="app-style" rel="stylesheet" type="text/css" />
       <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
     
		<!-- jQuery first -->
		<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

		<style>
		/* Only the open dropdown needs to clear modals (see rules below). A blanket z-index here
		   made every closed select2 control float above open modals. */
		span.select2.select2-container.select2-container--default {
				width: 100% !important;
			}
		.select2-dropdown { z-index: 999999 !important; }
		.select2-container--open { z-index: 999999 !important; }
		.select2-dropdown--above {
			top: 100% !important;
			bottom: auto !important;
			border-top: 1px solid #aaa !important;
			border-bottom: 1px solid #aaa !important;
			border-radius: 0 0 4px 4px !important;
		}
		.select2-container--default .select2-selection--single .select2-selection__arrow {
			height: 100% !important;
			right: 6px !important;
			width: 32px !important;
			background: #f6f7fb !important;
			border-left: 1px solid #d8dbe2 !important;
		}
		.select2-container--default .select2-selection--single .select2-selection__arrow b {
			border-color: #2f3a4a transparent transparent transparent !important;
			border-width: 6px 5px 0 5px !important;
			margin-top: -3px !important;
		}
		.select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
			border-color: transparent transparent #2f3a4a transparent !important;
			border-width: 0 5px 6px 5px !important;
		}
		.select2-container--default .select2-selection--single {
			height: calc(1.5em + 0.75rem + 2px) !important;
			padding: 0.375rem 2rem 0.375rem 0.75rem !important;
			border: 0.2px solid #00000024 !important;
			border-radius: 4px !important;
			display: flex !important;
			align-items: center !important;
		}
		.select2-container--default .select2-selection--single .select2-selection__rendered {
			padding: 0 !important;
			line-height: normal !important;
			color: #495057 !important;
		}
            body[data-topbar="dark"] .app-search .form-control {
    background-color: rgba(var(--bs-topbar-search-bg), .07);
    color: #fff;
    border: 2px solid #fff;
}

.ri-search-line:before {
    content: "\f06f";
    color: #fff;
}   
/* ul.pagination {
    display: none;
}

div#datatable-buttons_filter {
    display: none;
} */

.custom-pagination {
    margin-top: 1rem;
}

.dataTables_wrapper:has(+ .custom-pagination .pagination-summary) .dataTables_info {
    display: none !important;
}

.custom-pagination .pagination {
    justify-content: center;
    flex-wrap: wrap;
    gap: 0.25rem;
    margin-bottom: 0;
}

.custom-pagination .page-item .page-link {
    border-radius: 0.25rem;
}

.custom-pagination .page-item.active .page-link {
    background-color: #0d6efd !important;
    border-color: #0d6efd !important;
    color: #fff !important;
}

.custom-pagination .page-item .page-link:hover {
    color: #0d6efd;
}
/* div#datatable_length {
    display: none;
} */
input#same_as_consignee {
    border: 1px solid #000;
        margin-top: -7px;
    margin-left: 15px;
}

input#consignee_add_shippper {
    margin-left: 10px !important;
    margin-top: -10px !important;
    border: 1px solid #000 !important;
    margin-top: -7px;
    margin-left: 15px;
}


#datatable-buttons-vendor thead th {
    position: sticky;
    top: 0;
    background: #fff; 
    z-index: 10;       
}
tbody {
    border: 1px solid #00000052;
}
thead{
    border: 1px solid #00000052;
}


 /* Reduce tbody row height (padding) */
  .table tbody td {
    padding: 4px 6px; /* smaller cell spacing (top-bottom, left-right) */
    font-size: 11px;  /* smaller text */
    line-height: 1.8; /* tighter row height */
    font-family: Verdana, Arial, Helvetica, sans-serif;
  }

  /* Reduce column width (optional) */
  .table tbody td {
    white-space: nowrap;   /* prevents wrapping */
    text-overflow: ellipsis; /* add ... for long text */
  }

  /* Optional: keep header readable */
  .table thead th {
    padding: 8px 10px;
    font-size: 14px;
  }


table.dataTable tbody > tr.selected td,
table.dataTable tbody > tr.selected td span,
table.dataTable tbody > tr.selected td a,
table.dataTable tbody > tr.selected td p {
    border-color: rgba(15, 156, 243, .2);
    color: #fff !important;
}
.table tbody td span, .table tbody td a, .table tbody td p
 {
    padding: 4px 6px;
    font-size: 12px !important;
    line-height: 1.8;
    font-family: Verdana, Arial, Helvetica, sans-serif;
}

.form-control:focus{
	border:1px solid red !important;
}
.position-relative {
    position: relative !important;
    width: 100% !important;
}
select.form-control {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: linear-gradient(45deg, transparent 50%, #666 50%), linear-gradient(135deg, #666 50%, transparent 50%);
    background-position: calc(100% - 16px) calc(50% - 3px), calc(100% - 10px) calc(50% - 3px);
    background-size: 6px 6px, 6px 6px;
    background-repeat: no-repeat;
    padding-right: 2rem;
}

.form-control {
    border: 0.2px solid #00000024 !important;
}


#crm-loader {
    position: fixed;
    width: 100%;
    height: 100%;
    background: #0f0f0f;
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.loader-container {
    text-align: center;
    color: #fff;
}

.spinner {
    width: 60px;
    height: 60px;
    border: 4px solid rgba(255,255,255,0.1);
    border-top: 4px solid #a6ce3a;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 15px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loader-container h3 {
    font-size: 16px;
    letter-spacing: 1px;
    color: #aaa;
}
</style>
    </head>

    <body data-topbar="dark">
    <!-- <div id="crm-loader">
    <div class="loader-container">
        <div class="spinner"></div>
        <h3>Loading CCI CRM...</h3>
    </div>
</div> -->
    <!-- <body data-layout="horizontal" data-topbar="dark"> -->

        <!-- Begin page -->
        <div id="layout-wrapper">

            @include('layout.partials.header')


            @yield('content')


            @include('layout.partials.footer')
        
        </div>
        <!-- END layout-wrapper -->

        <!-- Right Sidebar -->
        <div class="right-bar">
            <div data-simplebar class="h-100">
                <div class="rightbar-title d-flex align-items-center px-3 py-4">
            
                    <h5 class="m-0 me-2">Settings</h5>

                    <a href="javascript:void(0);" class="right-bar-toggle ms-auto">
                        <i class="mdi mdi-close noti-icon"></i>
                    </a>
                </div>

                <!-- Settings -->
                <hr class="mt-0" />
                <h6 class="text-center mb-0">Choose Layouts</h6>

                <div class="p-4">
                    <div class="mb-2">
                        <img src="{{ asset('assets/images/layouts/layout-1.jpg') }}" class="img-fluid img-thumbnail" alt="layout-1">
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input theme-choice" type="checkbox" id="light-mode-switch" checked>
                        <label class="form-check-label" for="light-mode-switch">Light Mode</label>
                    </div>
    
                    <div class="mb-2">
                        <img src="{{ asset('assets/images/layouts/layout-2.jpg') }}" class="img-fluid img-thumbnail" alt="layout-2">
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input theme-choice" type="checkbox" id="dark-mode-switch" data-bsStyle="{{ asset('assets/css/bootstrap.min.css') }}" data-appStyle="{{ asset('assets/css/app.min.css') }}">
                        <label class="form-check-label" for="dark-mode-switch">Dark Mode</label>
                    </div>


            
                </div>

            </div> <!-- end slimscroll-menu-->
        </div>
        <!-- /Right-bar -->

        <!-- Right bar overlay-->
        <div class="rightbar-overlay"></div>

        <!-- JAVASCRIPT -->
        <script src="{{ asset('assets/libs/jquery/jquery.min.js') }}"></script>
        <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ asset('assets/libs/metismenu/metisMenu.min.js') }}"></script>
        <script src="{{ asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
        <script src="{{ asset('assets/libs/node-waves/waves.min.js') }}"></script>

        
        <!-- apexcharts -->
        <script src="{{ asset('assets/libs/apexcharts/apexcharts.min.js') }}"></script>

        <!-- jquery.vectormap map -->
        <script src="{{ asset('assets/libs/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.min.js') }}"></script>
        <script src="{{ asset('assets/libs/admin-resources/jquery.vectormap/maps/jquery-jvectormap-us-merc-en.js') }}"></script>

        <!-- Required datatable js -->
        <script src="{{ asset('assets/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
        
        <!-- Responsive examples -->
        <script src="{{ asset('assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
        <script src="{{ asset('assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>

        <script src="{{ asset('assets/js/pages/dashboard.init.js') }}"></script>

        <!-- App js -->
        <script src="{{ asset('assets/js/app.js') }}"></script>


        <!-- Required datatable js -->
        <script src="{{ asset('assets/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
        <!-- Buttons examples -->
        <script src="{{ asset('assets/libs/datatables.net-buttons/js/dataTables.buttons.min.js') }}"></script>
        <script src="{{ asset('assets/libs/datatables.net-buttons-bs4/js/buttons.bootstrap4.min.js') }}"></script>
        <script src="{{ asset('assets/libs/jszip/jszip.min.js') }}"></script>
        <script src="{{ asset('assets/libs/pdfmake/build/pdfmake.min.js') }}"></script>
        <script src="{{ asset('assets/libs/pdfmake/build/vfs_fonts.js') }}"></script>
        <script src="{{ asset('assets/libs/datatables.net-buttons/js/buttons.html5.min.js') }}"></script>
        <script src="{{ asset('assets/libs/datatables.net-buttons/js/buttons.print.min.js') }}"></script>
        <script src="{{ asset('assets/libs/datatables.net-buttons/js/buttons.colVis.min.js') }}"></script>

        <script src="{{ asset('assets/libs/datatables.net-keytable/js/dataTables.keyTable.min.js') }}"></script>
        <script src="{{ asset('assets/libs/datatables.net-select/js/dataTables.select.min.js') }}"></script>
        
        <!-- Responsive examples -->
        <script src="{{ asset('assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
        <script src="{{ asset('assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>

        <!-- Datatable init js -->
        <script src="{{ asset('assets/js/pages/datatables.init.js') }}"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

     
        <script> 
		
			function select2TextMatcher(params, data) {
				if ($.trim(params.term) === '') {
					return data;
				}

				if (data && data.text) {
					var text = data.text.toLowerCase();
					var term = params.term.toLowerCase();
					if (text.indexOf(term) > -1) {
						return data;
					}
				}

				return null;
			}

			// Initialize ALL selects with dropdownParent body so they always open downward.
			// Pass a container (e.g. a freshly cloned form) to initialise only that subtree.
			window.initSelect2 = function (scope) {
				var $selects = scope ? $(scope).find('select') : $('select');

				$selects.not('.no-select2').each(function () {
					var $select = $(this);
					var $modal = $select.closest('.modal');
					// A placeholder overwrites the text of the empty option, so reuse that option's
					// own wording ("Select Status", "Select Customer", ...) instead of a generic label
					var $emptyOption = $select.find('option[value=""]').first();
					var placeholder = $select.data('placeholder')
						|| $emptyOption.text().trim()
						|| 'Select';

					if ($select.data('select2')) {
						$select.select2('destroy');
					} else if ($select.hasClass('select2-hidden-accessible')) {
						// Markup copied by .clone() from an initialised select: drop the orphan container
						$select.siblings('.select2-container').remove();
						$select.removeClass('select2-hidden-accessible')
							.removeAttr('data-select2-id aria-hidden tabindex');
					}

					$select.select2({
						// Inside a modal the dropdown must live in the modal, or Bootstrap's
						// focus trap steals keystrokes from the search box
						dropdownParent: $modal.length ? $modal : $('body'),
						width: '100%',
						// allowClear needs an empty option to clear back to, else select2 warns
						allowClear: $emptyOption.length > 0,
						placeholder: placeholder,
						matcher: select2TextMatcher
					});
				});
			};

			$(document).ready(function () {
				window.initSelect2();
			});

            // Wait for the DOM to be fully loaded
            document.addEventListener("DOMContentLoaded", function() {
                // Set a timeout to hide the alert after 10 seconds
                setTimeout(function() {
                    // Hide the error alert if it exists
                    var errorAlert = document.getElementById("error-alert");
                    if (errorAlert) {
                        errorAlert.style.display = "none";
                    }

                    // Hide the success alert if it exists
                    var successAlert = document.getElementById("success-alert");
                    if (successAlert) {
                        successAlert.style.display = "none";
                    }
                }, 5000); // 10,000 milliseconds = 10 seconds
            });
        </script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const tables = document.querySelectorAll("table");

    tables.forEach(function (table) {
        table.addEventListener("click", function (e) {
            let row = e.target.closest("tr");
            if (row && table.contains(row)) {
                table.querySelectorAll("tr.selected").forEach(tr => tr.classList.remove("selected"));
                row.classList.add("selected"); // green style will apply from CSS
            }
        });
    });
});

</script>

<script>
window.addEventListener("load", function () {
    document.getElementById("crm-loader").style.display = "none";
});
</script>


    </body>

</html>