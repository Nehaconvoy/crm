@extends('layout.compact.app') <!-- This links to the app.blade.php layout -->
@section('content')
   
<div class="page-content">
                    <div class="container-fluid">

                        <!-- start page title -->
                        <div class="row">
                            <div class="col-12">
                                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                    <h4 class="mb-sm-0">Activity logs</h4>

                                    <div class="page-title-right">
                                        <ol class="breadcrumb m-0">
                                            <li class="breadcrumb-item"><a href="javascript: void(0);">CCI</a></li>
                                            <li class="breadcrumb-item active">Activity logs</li>
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
        
                                        <h4 class="card-title">Activity logs</h4>
                                       
        
                                        <table id="datatable-buttons-log" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                            <thead>
                                                                <tr>
                                                                    <th>Activity</th>
                                                                    <th>Performed by</th>
                                                                    <th>Date and time (US Eastern)</th>
                                                                </tr>
                                            </thead>
        
        
                                            <tbody>
												@foreach($alllogs as $log)
												@php 
													$diffrent = getdiffrance($log->old_json, $log->new_json);
												@endphp
												
												<tr>
                                                                        <td class="activity-history">
                                                                            <div class="fw-semibold activity-title">{{ $log->message ?: 'Activity was recorded' }}</div>
                                                                            <div class="activity-label text-muted mt-2"><strong>What changed:</strong></div>
                                                                            {!! $diffrent !!}
                                                                        </td>
                                                                        <td class="activity-history">Performed by: <strong class="activity-actor">{{ $log->user_name ?: 'System' }}</strong></td>
                                                                        <td class="activity-history activity-time" data-order="{{ optional($log->created_at)->timestamp ?? 0 }}">{{ format_activity_timestamp($log->created_at ?: $log->updated_at) }}</td>
                                                                    </tr>
												@endforeach
                                            </tbody>
                                        </table>
                                        <div class="custom-pagination">
                                            {!! render_pagination_links($alllogs) !!}
                                        </div>
                                    </div>
                                </div>
                            </div> <!-- end col -->
                        </div> <!-- end row -->



                    </div> <!-- container-fluid -->
                </div>
                <!-- End Page-content -->
<!-- Required datatable js -->
<script src="https://ccicrm.in/public/assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="https://ccicrm.in/public/assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
<script>
<style>
.activity-history {
    font-size: 16px;
}

.activity-history .activity-title {
    font-size: 17px;
}

.activity-history .activity-label,
.activity-history .small {
    font-size: 15px !important;
}

.activity-actor {
    color: #a6ce3a;
}
</style>
$('#datatable-buttons-log').DataTable({
	responsive: true,
	dom: 'Bfrtip',
	buttons: ['copy', 'excel', 'pdf', 'colvis'],
	order: [
        [0, 'desc']
    ]
});

</script>
@endsection