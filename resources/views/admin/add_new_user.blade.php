@extends('layout.compact.app')
<!-- This links to the app.blade.php layout -->

@section('content')

<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Add User</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">CCI</a></li>
                            <li class="breadcrumb-item active">Add User</li>
                        </ol>
                    </div>

                </div>
            </div>
        </div>
        <!-- end page title -->

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
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">

                        <form id="addOfficeForm" class="row" method="POST" action="{{ route('createuser') }}"
                            novalidate>
                            @csrf

                            <div class="col-3 mb-4">
                                <label class="form-label" for="FullName">Full Name</label>
                                <input type="text" id="FullName" name="FullName" class="form-control"
                                    value="{{ old('FullName') }}">
                                @error('FullName') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-3 mb-4">
                                <label class="form-label" for="EmailAddress">Email Address</label>
                                <input type="text" id="EmailAddress" name="EmailAddress" class="form-control"
                                    value="{{ old('EmailAddress') }}">
                                @error('EmailAddress') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-3 mb-4">
                                <label class="form-label" for="ConfirmEmailAddress">Confirm Email Address</label>
                                <input type="text" id="ConfirmEmailAddress" name="ConfirmEmailAddress"
                                    class="form-control" value="{{ old('ConfirmEmailAddress') }}">
                                @error('ConfirmEmailAddress') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-3 mb-4">
                                <label class="form-label" for="Password">Password</label>
                                <input type="password" id="Password" name="Password" class="form-control">
                                @error('Password') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-3 mb-4">
                                <label class="form-label" for="ConfirmPassword">Confirm Password</label>
                                <input type="password" id="ConfirmPassword" name="ConfirmPassword" class="form-control">
                                @error('ConfirmPassword') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-3 mb-4">
                                <label class="form-label" for="EmployeeCode">Employee Code</label>
                                <input type="text" id="EmployeeCode" name="EmployeeCode" class="form-control"
                                    value="{{ old('EmployeeCode') }}">
                                @error('EmployeeCode') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-3 mb-4">
                                <label class="form-label" for="FullAddress">Full Address</label>
                                <input type="text" id="FullAddress" name="FullAddress" class="form-control"
                                    value="{{ old('FullAddress') }}">
                                @error('FullAddress') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-3 mb-4">
                                <label class="form-label" for="modalOfficeName">Office</label>
                                <select id="modalOfficeName" name="modalOfficeName" class="form-control">
                                    <option value="">Select Office</option>
                                    @foreach ($allOffice as $office)
                                    <option value="{{ $office->id }}"
                                        {{ old('modalOfficeName') == $office->id ? 'selected' : '' }}>
                                        {{ $office->office_name }}</option>
                                    @endforeach
                                </select>
                                @error('modalOfficeName') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-3 mb-4">
                                <label class="form-label" for="modaldepartmentName">Department</label>
                                <select id="modaldepartmentName" name="modaldepartmentName" class="form-control">
                                    <option value="">Select Department</option>
                                    @foreach ($allDepartment as $department)
                                    <option value="{{ $department->id }}"
                                        {{ old('modaldepartmentName') == $department->id ? 'selected' : '' }}>
                                        {{ $department->department_name }}</option>
                                    @endforeach
                                </select>
                                @error('modaldepartmentName') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-3 mb-4">
                                <label class="form-label" for="modalmangerName">Select Manager</label>
                                <select id="modalmangerName" name="modalmangerName" class="form-control">
                                    <option value="">Select Manager</option>
                                    @foreach ($allmanger as $manager)
                                    <option value="{{ $manager->id }}"
                                        {{ old('modalmangerName') == $manager->id ? 'selected' : '' }}>
                                        {{ $manager->manager }}</option>
                                    @endforeach
                                </select>
                                @error('modalmangerName') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-3 mb-4">
                                <label class="form-label" for="modalleaderName">Team Leader</label>
                                <select id="modalleaderName" name="modalleaderName" class="form-control">
                                    <option value="">Select Team Leader</option>
                                    @foreach ($allteamleader as $leader)
                                    <option value="{{ $leader->id }}"
                                        {{ old('modalleaderName') == $leader->id ? 'selected' : '' }}>{{ $leader->tl }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('modalleaderName') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-3 mb-4">
                                <label class="form-label" for="Role">Select Role</label>
                                <select id="Role" name="Role" class="form-control">
                                    <option value="">Select Role</option>
                                    @foreach ($allroles as $role)
                                    <option value="{{ $role->id }}" {{ old('Role') == $role->id ? 'selected' : '' }}>
                                        {{ $role->name }}</option>
                                    @endforeach
                                </select>
                                @error('Role') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-3 mb-4">
                                <label class="form-label" for="EmergencyContact">Emergency Contact</label>
                                <input type="text" id="EmergencyContact" name="EmergencyContact" class="form-control"
                                    value="{{ old('EmergencyContact') }}">
                                @error('EmergencyContact') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-3 mb-4">
                                <label class="form-label" for="ContactNumber">Contact Number</label>
                                <input type="tel" id="ContactNumber" name="ContactNumber" class="form-control"
                                    maxlength="30" value="{{ old('ContactNumber') }}">
                                @error('ContactNumber') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-primary">Create User</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div> <!-- end col -->
        </div> <!-- end row -->
    </div> <!-- container-fluid -->
</div>
<!-- End Page-content -->
<script>

$('#modaldepartmentName').on('change', function() {
    var id = $(this).val();

    $.ajax({
        url: '/admin/get-manager/' + id, // Dynamically append the ID 
        method: 'GET', // or 'POST' depending on your backend route
        data: { id: id },
        success: function(response) {
            //console.log(response); // Handle the successful response
            $('#modalmangerName').html(response);
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
        }
    });
});



$('#modalmangerName').on('change', function() {
    var id = $(this).val();

    $.ajax({
        url: '/admin/get-tl/' + id, // Dynamically append the ID
        method: 'GET', // or 'POST' depending on your backend route
        data: { id: id },
        success: function(response) {
            //console.log(response); // Handle the successful response
           $('#modalleaderName').html(response);
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
        }
    });
});

</script>

@endsection