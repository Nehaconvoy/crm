@foreach($carriers as $carrier)

<tr>
    <td>{{$carrier->carrier_mc_ff_input}}</td>
    <td>{{$carrier->carrier_dot}}</td>
    <td>{{$carrier->carrier_name}}</td>
    <td>{{$carrier->user?->name}}</td>
    <td>{{$carrier->created_at}}</td>
    <td>
        <select
            name="mc_check"
            onchange="mc_check(this)"
            id="mc_check-{{ $carrier->id }}"
            class="form-control compliance-select"
            style="width:100%;"
        >
            <option value="">Please Select MC</option>

            <option value="Approved"
                {{ $carrier->mc_check == 'Approved' ? 'selected' : '' }}>
                Approved
            </option>

            <option value="Approved as Exception"
                {{ $carrier->mc_check == 'Approved as Exception' ? 'selected' : '' }}>
                Approved as Exception
            </option>

            <option value="Not Approved"
                {{ $carrier->mc_check == 'Not Approved' ? 'selected' : '' }}>
                Not Approved
            </option>
        </select>

        <textarea
            id="compliance-exception-note-{{ $carrier->id }}"
            class="form-control mt-2 compliance-exception-note"
            placeholder="Enter exception note"
            {{ !empty($carrier->compliance_exception_note) ? 'disabled' : '' }}
            style="
                display: {{ $carrier->mc_check == 'Approved as Exception' ? 'block' : 'none' }};
            "
        >{{ $carrier->compliance_exception_note ?? '' }}</textarea>
    </td>
	<td>
        <select id="mc_setup-{{ $carrier->id }}" onchange="mc_setup(this)" name="setup" class="form-control compliance-select" >
			<option value="" data-setup-id="{{ $carrier->id }}">Please Select Setup</option>
			<option value="Online Setup" data-setup-id="{{ $carrier->id }}" {{ $carrier->setup == 'Online Setup' ? 'selected' : '' }}>Online Setup</option>
			<option value="Manual Setup" data-setup-id="{{ $carrier->id }}" {{ $carrier->setup == 'Manual Setup' ? 'selected' : '' }}>Manual Setup</option>
			<option value="Pre Approved" data-setup-id="{{ $carrier->id }}" {{ $carrier->setup == 'Pre Approved' ? 'selected' : '' }}>Pre Approved</option>
		</select>
	</td>
    <td class="status-{{ $carrier->id }}">{{$carrier->mc_check}}</td>
	<td class="setup-{{ $carrier->id }}">{{$carrier->setup}}</td>
    <td>
        <input type="file"
               id="doc_upload-{{ $carrier->id }}"
               name="doc_upload[]"
               class="form-control"
               multiple
               onchange="uploadCarrierDocuments({{ $carrier->id }})">
    </td>
    <td>
        <div class="carrier-documents-{{ $carrier->id }}">
            @php
                $documents = json_decode($carrier->doc_upload, true) ?? [];
            @endphp

            @if(count($documents))
                @foreach($documents as $index => $doc)
                    <div class="mb-1" id="doc-row-{{ $carrier->id }}-{{ $index }}">
                        <span class="trim-file-name" data-title="{{ $doc['original_name'] }}">
                            {{ $doc['original_name'] }}
                        </span>

                        <a href="{{ asset($doc['file_path']) }}"
                           target="_blank"
                           class="btn btn-sm btn-primary btn-xs">
                            View
                        </a>

                        <button type="button"
                                class="btn btn-sm btn-danger btn-xs"
                                onclick="deleteCarrierDocument({{ $carrier->id }}, {{ $index }})">
                            Delete
                        </button>
                    </div>
                @endforeach
            @else
                <span class="text-muted">No documents uploaded.</span>
            @endif
        </div>
    </td>
    <td>
        <span data-bs-toggle="modal" style="color: #0c7ce6; cursor:pointer" data-bs-target="#view-documents-{{ $carrier->id }}"> View Documents</span>
    </td>
    @if($carrier->carrier_block == 'Blocked')
        <td style="background-color: red; color: white; font-weight: bold;"><input type="checkbox" name="carrier_block" id="carrier_block-{{ $carrier->id }}" onchange="carrier_block(this)" data-carrier-id="{{ $carrier->id }}" {{ $carrier->carrier_block == 'Blocked' ? 'checked' : '' }}></td>
    @else
    <td>
        <input type="checkbox" name="carrier_block" id="carrier_block-{{ $carrier->id }}" onchange="carrier_block(this)" data-carrier-id="{{ $carrier->id }}" {{ $carrier->carrier_block == 'Blocked' ? 'checked' : '' }}>
    </td>
    @endif


    <div class="modal fade" id="view-documents-{{ $carrier->id }}" tabindex="-1" aria-labelledby="view-documents" aria-hidden="true">
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
                        $alladoc = $carrier->carrier_file_upload;
                        $docs = json_decode($alladoc, true);
                        
                    @endphp
                     @if(empty($docs))
                        <p>No documents found.</p>
                    @else

                        <div class="accordion" id="accordionExample">
                            @foreach($docs as $key => $all)
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingOne">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{$key}}" aria-expanded="false" aria-controls="collapse{{$key}}">
                                    view document #{{$key + 1}}
                                    </button>
                                </h2>
                                <div id="collapse{{$key}}" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
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
function mc_check(e) {

    var carrier_id = $(e).attr('id').replace('mc_check-', '');
    var mc_check = $(e).val();

    var $exceptionNote = $('#compliance-exception-note-' + carrier_id);

    if (mc_check === 'Approved as Exception') {

        $exceptionNote.show();

        // If note already exists, keep it disabled
        if ($.trim($exceptionNote.val())) {
            $exceptionNote.prop('disabled', true);
        } else {
            // New exception - allow user to enter note
            $exceptionNote.prop('disabled', false);
            $exceptionNote.focus();
        }

    } else {

        // Any other MC status
        $exceptionNote
            .hide()
            .prop('disabled', true);
    }

    // Don't save exception status until note is entered
    if (mc_check === 'Approved as Exception') {

        if (!$.trim($exceptionNote.val())) {
            return;
        }
    }

    saveMcCheck(
        carrier_id,
        mc_check,
        $exceptionNote.val()
    );
}


function saveMcCheck(carrier_id, mc_check, exception_note) {

    $.ajax({
        url: '/account/mc-chcek',
        method: 'POST',

        data: {
            carrier_id: carrier_id,
            mc_check: mc_check,
            compliance_exception_note: exception_note
        },

        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },

        success: function(response) {

            console.log('Success:', response);

            var $exceptionNote =
                $('#compliance-exception-note-' + carrier_id);

            /*
             * Update textarea with saved value
             */
            if (response.exception_note !== undefined) {

                $exceptionNote.val(response.exception_note);
            }

            /*
             * Once note is successfully saved,
             * disable the textarea.
             */
            if (
                mc_check === 'Approved as Exception' &&
                $.trim($exceptionNote.val())
            ) {

                $exceptionNote
                    .prop('disabled', true)
                    .show();
            }

            /*
             * If MC is changed from Exception
             */
            if (mc_check !== 'Approved as Exception') {

                $exceptionNote
                    .hide()
                    .prop('disabled', true);
            }

            $('#mc-success-message')
                .text(response.message)
                .fadeIn();

            setTimeout(function() {

                $('#mc-success-message')
                    .text('')
                    .fadeOut();

            }, 5000);
        },

        error: function(xhr) {

            console.error('Error:', xhr);

            var message = 'Something went wrong.';

            if (xhr.responseJSON &&
                xhr.responseJSON.message) {

                message = xhr.responseJSON.message;
            }

            $('#mc-error-message')
                .text(message)
                .fadeIn();

            setTimeout(function() {

                $('#mc-error-message')
                    .text('')
                    .fadeOut();

            }, 5000);
        }
    });
}


/*
|--------------------------------------------------------------------------
| Save note when user leaves textarea
|--------------------------------------------------------------------------
*/

$(document).on(
    'blur',
    '.compliance-exception-note',
    function() {

        var $this = $(this);

        /*
         * Don't do anything if textarea is disabled
         */
        if ($this.prop('disabled')) {
            return;
        }

        var carrier_id = this.id.replace(
            'compliance-exception-note-',
            ''
        );

        var mc_check =
            $('#mc_check-' + carrier_id).val();

        if (mc_check !== 'Approved as Exception') {
            return;
        }

        var note = $.trim($this.val());

        if (!note) {

            $this.focus();

            return;
        }

        saveMcCheck(
            carrier_id,
            mc_check,
            note
        );
    }
);

function mc_setup(e) {
    var $selectedOption = $(e).find('option:selected');
    var setup_id = $selectedOption.data('setup-id');
    var mc_setup = $(e).val();

    $.ajax({
        url: '/account/mc-setup', // Ensure this is the correct endpoint
        method: 'POST',
        data: {
            setup_id: setup_id,
            mc_setup: mc_setup
        },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            console.log('Success:', response);
            $('.setup-' + setup_id).text(mc_setup);
            $('#mc-success-message').text(response.message).fadeIn();

            // Hide after 10 seconds
            setTimeout(function () {
                $('#mc-success-message').text('').fadeOut();
            }, 10000); // 10 seconds
        },
        error: function (xhr, status, error) {
            console.error('Error:', error);
            $('#mc-error-message').text(error).fadeIn();

            // Hide after 10 seconds
            setTimeout(function () {
                $('#mc-error-message').text('').fadeOut();
            }, 10000); // 10 seconds
        }
    }); 
}

</script>

<script>
function carrier_block(el) {

    var carrierId = el.getAttribute("data-carrier-id");
    var isChecked = el.checked ? 1 : 0;

    $.ajax({
        url: "{{ route('carrier_block') }}",
        type: "GET",
        data: {
            carrier_id: carrierId,
            is_blocked: isChecked
        },
        success: function(response) {
            console.log(response);
            location.reload();

            // Optional success alert
            Swal.fire({
                icon: 'success',
                title: 'Updated',
                text: 'Carrier status updated to ' + response.value,
                timer: 1500
            });
        },
        error: function(xhr) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to update status'
            });
        }
    });
}
</script>

<script>
function uploadCarrierDocuments(carrierId) {
    let input = document.getElementById('doc_upload-' + carrierId);
    let files = input.files;

    if (!files.length) {
        return;
    }

    let formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('carrier_id', carrierId);

    for (let i = 0; i < files.length; i++) {
        formData.append('doc_upload[]', files[i]);
    }

    $.ajax({
        url: '{{ route("carrier.documents.upload") }}',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function () {
            $('.setup-' + carrierId).html('<span style="color:blue;">Uploading...</span>');
        },
        success: function (response) {
            if (response.success) {
                $('.carrier-documents-' + carrierId).html(response.html);
                alert('Documents uploaded successfully.');
                location.reload(); // Reload the page to reflect changes
            }
        },
        error: function (xhr) {
            console.log(xhr.responseText);
            alert('Something went wrong while uploading documents.');
        }
    });
}

function deleteCarrierDocument(carrierId, docIndex) {
    if (!confirm('Are you sure you want to delete this document?')) {
        return;
    }

    $.ajax({
        url: '{{ route("carrier.documents.delete") }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            carrier_id: carrierId,
            doc_index: docIndex
        },
        success: function (response) {
            if (response.success) {
                $('.carrier-documents-' + carrierId).html(response.html);
                $('#doc_upload-' + carrierId).val('');
                alert('Document deleted successfully.');
                location.reload(); 
            }
        },
        error: function (xhr) {
            console.log(xhr.responseText);
            alert('Something went wrong while deleting the document.');
        }
    });
}
</script>





