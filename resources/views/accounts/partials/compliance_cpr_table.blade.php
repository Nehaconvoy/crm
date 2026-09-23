 @foreach($loads as $delivered)
   
    <tr>
        
        <td>
            <a>
                {{ $delivered->load_number }}
            </a>
        </td>
        <td><a href="{{ route('accounts.view_loads_detail', $delivered->id) }}" class="btn btn-primary btn-sm"> <i class="fas fa-eye"></i></a></td>
        <td>{{ $delivered->load_workorder }}</td>
        <td>@if($delivered->user) {{ $delivered->user?->name }} @endif</td>
        <td>{{ $delivered->load_bill_to }}</td>
        <td>{{ $delivered->shipper_load_final_rate }}</td>
        <td>@if($delivered->user) {{ $delivered->user?->officedata?->office_name }}@endif</td>
        <td>@if($delivered->user) {{ $delivered->user?->teamLeaderInfo?->tl }}@endif</td>
        <td>@if($delivered->user) {{ $delivered->user?->managerInfo?->manager }}@endif</td>
        <td>{{ $delivered->created_at->format('m-d-Y') }}</td>
        @php
        $shipper_appointment =
        json_decode($delivered->load_shipper_appointment,true);
        @endphp
        <td>{{ isset($shipper_appointment[0]['appointment']) ? \Carbon\Carbon::parse($shipper_appointment[0]['appointment'])->format('m-d-Y') : '' }}
        </td>
        @php
        $consignee_appointment =
        json_decode($delivered->load_consignee_appointment,true);
        @endphp
        <td>
            {{ 
                isset($consignee_appointment[0]['appointment']) && 
                \Carbon\Carbon::hasFormat($consignee_appointment[0]['appointment'], 'm-d-Y\TH:i:s') 
                    ? \Carbon\Carbon::parse($consignee_appointment[0]['appointment'])->format('m-d-Y') 
                    : '' 
            }}
        </td>

        <td>{{ $delivered->load_equipment_type }}</td>
        <td>{{$delivered->load_carrier}}</td>
        <td>{{$delivered->load_final_carrier_fee}}</td>
        @php
        $shipper_location = json_decode($delivered->load_shipper_location, true);
        @endphp

            <td >
                {{ $shipper_location[0]['location'] ?? '' }}
            </td>
        @php
        $consignee_location = json_decode($delivered->load_consignee_location, true);
        $last_consignee_location = is_array($consignee_location) && !empty($consignee_location) ? end($consignee_location) : null;
        @endphp

        
        @if (!empty($last_consignee_location) && is_array($last_consignee_location))
            <td>
                {{ $last_consignee_location['location'] ?? '' }}
            </td>
        @else
            <td>
                Not Found Any Drop Location
            </td>
        @endif
        
        <td>
            
            <select name="rate_check" id="rate_check-{{ $delivered->id }}" data-load-id="{{ $delivered->id }}" class="form-control rate_check cpr-native-select no-select2">
                <option value="">Please Select Rate Check</option>
                <option value="1" {{ $delivered->rate_check == '1' ? 'selected' : '' }}>Checked</option>
                <option value="0" {{ $delivered->rate_check == '0' ? 'selected' : '' }}>Not Checked</option>
            </select>
    </td>
        <td>
            {{ $delivered->load_status }}
        </td>
@if($delivered->customer?->pre_payment == 0 || $delivered->customer?->pre_payment == null)
    <td>
        <select name="cpr_check" onchange="changecpr(this)" class="form-control cpr-native-select no-select2">
            <option value="">Select CPR</option>
            <option value="Verified" data-load-id="{{ $delivered->id }}" {{ $delivered->cpr_check == 'Verified' ? 'selected' : '' }}>Verified</option>
            <option value="Not Verified" data-load-id="{{ $delivered->id }}" {{ $delivered->cpr_check == 'Not Verified' ? 'selected' : '' }}>Not Verified</option>
            <option value="Not Received" data-load-id="{{ $delivered->id }}" {{ $delivered->cpr_check == 'Not Received' ? 'selected' : '' }}>Not Received</option>
            <option value="Approved as Exception" data-load-id="{{ $delivered->id }}" {{ $delivered->cpr_check == 'Approved as Exception' ? 'selected' : '' }}>Approved as Exception</option>
        </select>
        <textarea name="cpr_exception_note" data-load-id="{{ $delivered->id }}" class="form-control cpr-exception-note mt-2" rows="2" placeholder="Enter exception note" style="{{ $delivered->cpr_check == 'Approved as Exception' ? '' : 'display: none;' }}">{{ $delivered->cpr_exception_note ?? '' }}</textarea>
    </td>
@elseif($delivered->customer?->pre_payment == 1)
    <td>
        <span class="text-muted danger">On Pre-Payment/Remittance Required</span>
    </td>
@endif
        <td class="cpr_status-{{ $delivered->id }}">
            @if($delivered->cpr_check == 'Not Received' || $delivered->cpr_check == '')
                blank data
            @else
                {{ $delivered->cpr_check }}
            @endif
        </td>
        <td >
            <select name="macro" onchange="macro(this)" class="form-control cpr-native-select no-select2" id="macro-{{ $delivered->id }}" >
                <option value="">Select Macro</option>
                <option value="Sent" data-load-id="{{ $delivered->id }}" {{ $delivered->macro == 'Sent' ? 'selected' : '' }}>Sent</option>
                <option value="Not Sent" data-load-id="{{ $delivered->id }}" {{ $delivered->macro == 'Not Sent' ? 'selected' : '' }}>Not Sent</option>
            </select>
        </td>
        <td >
            <select name="no_of_macro" onchange="no_of_macro(this)" class="form-control cpr-native-select no-select2" id="no_of_macro-{{ $delivered->id }}">
                <option value="">Select No Of Macro</option>
                @for ($i = 0; $i <= 10; $i++)
                    <option value="{{ $i }}" data-load-id="{{ $delivered->id }}" {{ $delivered->no_of_macro == $i ? 'selected' : '' }}>{{ $i }}</option>
                @endfor
            </select>
        </td>

        @if($delivered->cpr_check == 'Verified')
        <td style="padding: 7px 10px !important; vertical-align: middle !important;color: green;">
        Verified
        </td>
        @elseif($delivered->cpr_check == 'Not Verified')
        <td >
            Not Verified
        </td>
        @elseif($delivered->cpr_check == 'Not Received')
        <td >
            Not Received
        </td>
        @elseif($delivered->cpr_check != 'Verified')
        <td >
                Check CPR
        </td>
        @endif
        
        <td>
        
        @if (!empty($delivered->load_delivery_do_file))
                
				<span data-bs-toggle="modal" style="color: #0c7ce6; cursor:pointer" data-bs-target="#view-documents-{{ $delivered->id }}"> <i class="fa fa-eye" style="font-size: 15px;color: #000; margin-right: 6px;"></i></span>
            @else
                No File Available
            @endif
        </td>
        
		
		<div class="modal fade cpr-documents-modal" id="view-documents-{{ $delivered->id }}" tabindex="-1" aria-labelledby="view-documents-{{ $delivered->id }}-title" aria-hidden="true">
        <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <!-- Modal Header -->
                    <div class="modal-header" style="padding-left: 14px;">
                    <h4 class="modal-title" id="view-documents-{{ $delivered->id }}-title">View Documents</h4>
                    <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                    </div>


                    <div class="modal-body">
                    <!-- Existing Credit Logs -->

                    @php
                        $alladoc = $delivered->load_delivery_do_file;
                        $docs = json_decode($alladoc, true);
                        
                    @endphp
                     @if(empty($docs))
                        <p>No documents found.</p>
                    @else

                        <div class="accordion" id="cpr-documents-accordion-{{ $delivered->id }}">
                            @foreach($docs as $key => $all)
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="cpr-documents-heading-{{ $delivered->id }}-{{ $key }}">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#cpr-documents-collapse-{{ $delivered->id }}-{{ $key }}" aria-expanded="false" aria-controls="cpr-documents-collapse-{{ $delivered->id }}-{{ $key }}">
                                    view document #{{$key + 1}}
                                    </button>
                                </h2>
                                <div id="cpr-documents-collapse-{{ $delivered->id }}-{{ $key }}" class="accordion-collapse collapse" aria-labelledby="cpr-documents-heading-{{ $delivered->id }}-{{ $key }}" data-bs-parent="#cpr-documents-accordion-{{ $delivered->id }}">
                                    <div class="accordion-body">
                                       @php
                                            $file = $all; // Or $all['file'] depending on your data structure
                                            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                        @endphp

                                        <div style="margin-bottom: 20px;">
                                            @if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']))
                                                <!-- Image Preview -->
                                                <img src="{{ asset('public/'.$file) }}" alt="Image" style="max-width: 500px;">
                                            @elseif($extension === 'pdf')
                                                <!-- PDF Preview -->
                                                <embed src="{{ asset('public/'.$file) }}" type="application/pdf" width="600" height="400">
                                            @elseif(in_array($extension, ['doc', 'docx']))
                                                <!-- Word Preview with Google Docs Viewer -->
                                                <iframe src="https://docs.google.com/gview?url={{ urlencode(asset('public/'.$file)) }}&embedded=true" 
                                                        style="width:600px; height:500px;" frameborder="0"></iframe>
                                                <!-- Optional download link -->
                                                <br><a href="{{ asset('public/'.$file) }}" target="_blank">Download Word Document</a>
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
                    </div>
                </div>
        </div>
    </div>
    </tr>
@endforeach



<script>

function changecpr(selectElement) {

    var $select = $(selectElement);

    var $selectedOption = $select.find('option:selected');

    var load_id = $selectedOption.data('load-id');

    var cpr_check = $select.val();

    var $exceptionNote = $select
        .siblings('.cpr-exception-note');


    /*
     * Approved as Exception
     * --------------------------------
     * Just show textarea.
     * DO NOT AJAX yet because user
     * still needs to enter the note.
     */
    if (cpr_check === 'Approved as Exception') {

        $exceptionNote.show().focus();

        return;
    }


    /*
     * For all other statuses,
     * hide and clear exception note
     */
    $exceptionNote.hide();

    $exceptionNote.val('');


    /*
     * Save CPR status
     */
    $.ajax({

        url: '/account/cpr-check',

        method: 'POST',

        data: {
            load_id: load_id,
            cpr_check: cpr_check,
            cpr_exception_note: ''
        },

        headers: {
            'X-CSRF-TOKEN':
                $('meta[name="csrf-token"]').attr('content')
        },

        success: function (response) {

            console.log('Success:', response);

            $('.cpr_status-' + load_id)
                .text(cpr_check);

            $('#mc-success-message')
                .text(response.message)
                .fadeIn();

            setTimeout(function () {

                $('#mc-success-message')
                    .text('')
                    .fadeOut();

            }, 1000);
        },

        error: function (xhr) {

            console.error('Error:', xhr);

            var message =
                xhr.responseJSON?.message ||
                'Something went wrong.';

            $('#mc-error-message')
                .text(message)
                .fadeIn();

            setTimeout(function () {

                $('#mc-error-message')
                    .text('')
                    .fadeOut();

            }, 2000);
        }
    });
}


/*
 * Save exception note
 */
$(document).on(
    'blur',
    '.cpr-exception-note',
    function () {

        var $note = $(this);

        var load_id = $note.data('load-id');

        var note = $.trim($note.val());


        /*
         * Don't save empty note
         */
        if (note === '') {

            $('#mc-error-message')
                .text('Please enter an exception note.')
                .fadeIn();

            setTimeout(function () {

                $('#mc-error-message')
                    .text('')
                    .fadeOut();

            }, 2000);

            return;
        }


        /*
         * Save status + note together
         */
        $.ajax({

            url: '/account/cpr-check',

            method: 'POST',

            data: {

                load_id: load_id,

                cpr_check: 'Approved as Exception',

                cpr_exception_note: note
            },

            headers: {

                'X-CSRF-TOKEN':
                    $('meta[name="csrf-token"]').attr('content')
            },

            success: function (response) {

                console.log(
                    'CPR Exception saved:',
                    response
                );


                $('.cpr_status-' + load_id)
                    .text('Approved as Exception');


                $('#mc-success-message')
                    .text(response.message)
                    .fadeIn();


                setTimeout(function () {

                    $('#mc-success-message')
                        .text('')
                        .fadeOut();

                }, 1000);
            },

            error: function (xhr) {

                console.error(
                    'Exception note error:',
                    xhr
                );


                var message =
                    xhr.responseJSON?.message ||
                    'Something went wrong.';


                $('#mc-error-message')
                    .text(message)
                    .fadeIn();


                setTimeout(function () {

                    $('#mc-error-message')
                        .text('')
                        .fadeOut();

                }, 2000);
            }
        });
    }
);
 $(document).ready(function() {

    $(document).on('change', 'select[id^="rate_check-"]', function(e){
        var loadId = $(this).data('load-id');
        var ratecheck = $(this).val();
        $.ajax({
            url: '{{ route("saverateChecks") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: loadId,
                rate_check: ratecheck
            },
            success: function (response) {
                
                console.log('Success:', response);
            
                $('#mc-success-message').text(response.message).fadeIn();

                // Hide after 10 seconds
                setTimeout(function() {
                    $('#mc-success-message').text('').fadeOut();
                }, 1000);
                
            },
            error: function (response) {
                console.error('Error:', error);
                $('#mc-error-message').text(error).fadeIn();

                // Hide after 10 seconds
                setTimeout(function() {
                    $('#mc-error-message').text('').fadeOut();
                }, 1000); 
            }
        });
    });


    

});


function macro(e) {
    var $selectedOption = $(e).find('option:selected');
    var load_id = $selectedOption.data('load-id');
    var macro = $(e).val();

    $.ajax({
        url: '/account/macro',
        method: 'POST',
        data: {
            load_id: load_id,
            macro: macro
        },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            console.log('Success:', response);

            $('#mc-success-message').text(response.message).fadeIn();

            setTimeout(function () {
                $('#mc-success-message').text('').fadeOut();
            }, 1000); // 10 seconds
        },
        error: function (xhr, status, error) {
            console.error('Error:', error);
            $('#mc-error-message').text(error).fadeIn();

            setTimeout(function () {
                $('#mc-error-message').text('').fadeOut();
            }, 1000); // 10 seconds
        }
    });
}



function no_of_macro(e) {
    var $selectedOption = $(e).find('option:selected');
    var load_id = $selectedOption.data('load-id');
    var no_of_macro = $(e).val();

    $.ajax({
        url: '/account/no_of_macro',
        method: 'POST',
        data: {
            load_id: load_id,
            no_of_macro: no_of_macro
        },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            console.log('Success:', response);

            $('#mc-success-message').text(response.message).fadeIn();

            setTimeout(function () {
                $('#mc-success-message').text('').fadeOut();
            }, 1000); // 1 second
        },
        error: function (xhr, status, error) {
            console.error('Error:', error);
            $('#mc-error-message').text(error).fadeIn();

            setTimeout(function () {
                $('#mc-error-message').text('').fadeOut();
            }, 1000); // 1 second
        }
    });
}



</script>