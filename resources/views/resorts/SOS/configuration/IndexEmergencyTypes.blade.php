@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')

<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Resort Config Pages</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                </div>
            </div>
        </div>
        <div>
            <div class="row g-30">
                <div class="col-xxl-12 col-xl-12 col-lg-12">
                <!-- <div class="col-xxl-6 col-xl-6 col-lg-6"> -->
                    <div class="card">
                    <div class="card-header">
                        <div class="row g-md-3 g-2 align-items-center">
                            <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                                <div class="input-group">
                                    <input type="search" class="form-control " id="searchInput" placeholder="Search" />
                                    <i class="fa-solid fa-search"></i>
                                </div>
                            </div>

                            <div class="col-auto ms-auto"><a href="{{route('sos.config.index')}}" class="btn  btn-themeSkyblue btn-sm">Add New</a>
                            </div>
                        </div>
                    </div>

                        <table id="IndexSOSEmergencyTypes" class="table  w-100">
                            <thead>
                                <tr>
                                    <th class="text-nowrap"> Emergency Type Name  </th>
                                    <th class="text-nowrap"> Description  </th>
                                    <th class="text-nowrap"> Assigned Team Name </th>
                                    <th class="text-nowrap"> Action </th>
                                </tr>
                            </thead>

                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- modal -->
<div class="modal fade" id="editEmergencyType-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form id="edit-emergency-type" name="edit-emergency-type">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Edit Emergency Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-xxl-4 g-3 align-items-end">
                        <input type="hidden" id="AppendTeamMemberCount" value="0">
                        <input type="hidden" name="emergency_id" id="emergency_id" />
                        <div class="col-12">
                            <input type="text" class="form-control mb-3" id="emergency_name" name="emergency_name" placeholder="Name Of Emergency" data-parsley-required="true" data-parsley-error-message="Please Enter Emergency Type">
                        </div>
                        <div class="col-12">
                            <textarea class="form-control description" id="description" name="description" placeholder="Description" rows="3" data-parsley-required="true" 
                            data-parsley-required-message="Please Enter Description"
                            data-parsley-validate-script 
                            data-parsley-validate-script-message="Script tags are not allowed." 
                            data-parsley-errors-container="#description-error" 
                            data-parsley-pattern="^(?!.*<[^>]*>).*$"></textarea>
                            <div id="description-error"></div>
                        </div>
                        <div class="col-12">
                                <label for="assign_default_team" class="form-label">ASSIGN DEFAULT TEAMS</label>
                                <select class="form-select select2t-none" aria-label="Default select example"
                                    id="assign_default_team" name="assign_default_team[]" multiple="multiple" data-parsley-required="true"
                                        data-parsley-error-message="Please Select Default Team">
                                    <option value="" ></option>
                                        @if($allTeams->isNotEmpty())
                                            @foreach($allTeams as $team)
                                                <option value="{{$team->id}}">{{$team->name}}</option>
                                            @endforeach
                                        @endif
                                </select>
                            </div>
                        <div class="row g-md-4 g-3 AppendCustomField">

                        </div>
                        <div id="custom-fields-container"></div>
                        <div class="col-12">
                            <button id="add-custom-field" type="button" class="btn btn-sm btn-theme">Add custom Field </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    <button type="submit" class="btn btn-themeBlue btn-sm" id="submit">Submit</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')

@php
    $viewIcon = '<i class="fa-regular fa-eye"></i>';
    $editIcon = asset("resorts_assets/images/edit.svg");
    $trashIcon = asset("resorts_assets/images/trash-red.svg");
    $viewRoute = route('sos.team.detail', '__MEETING_ID__'); // placeholder
@endphp

<script>
    $(document).ready(function (){ 

        window.Parsley.addValidator('validateScript', {
            validateString: function(value) {
                // Pattern to match any <script> tags, even with attributes or content
                const scriptTagPattern = /<\s*script\b[^>]*>(.*?)<\s*\/\s*script\s*>/gi;
                return !scriptTagPattern.test(value);  // Return true if no script tags are found, false otherwise
            },
            messages: {
                en: 'Script tags are not allowed.'
            }
        });
        
        $('#edit-emergency-type').parsley();

        getEmergencyTypes();
        // Trigger search on input change
        $('#searchInput').on('keyup change', function () {
            getEmergencyTypes(); // Reload emergency types
        });

        function getEmergencyTypes() {
            if ($.fn.DataTable.isDataTable("#IndexSOSEmergencyTypes")) {
                $("#IndexSOSEmergencyTypes").DataTable().destroy();
            }

            var EmergencyTypes = $('#IndexSOSEmergencyTypes').DataTable({
                "searching": false,
                "bLengthChange": false,
                "bFilter": true,
                "bInfo": true,
                "bAutoWidth": false,
                "scrollX": true,
                "iDisplayLength": 6,
                processing: true,
                serverSide: true,
                order:[[4, 'desc']],
                ajax: {
                    url: '{{ route("sos.config.IndexSOSEmergencyTypes") }}',
                    type: "GET",
                    data: function (d) {
                        d.searchTerm = $('#searchInput').val();
                    }
                },
                columns: [
                    { data: 'name', name: 'name', className: 'text-nowrap' },
                    { data: 'description', name: 'description', className: 'text-nowrap' },
                    { data: 'assigned_default_team', name: 'assigned_default_team', className: 'text-nowrap' },
                    { data: 'Action', name: 'Action', orderable: false, searchable: false },
                    {data:'created_at', visible:false,searchable:false},
                ],
                rowCallback: function (row, data) {
                    if (data.danger_flag) {
                        $(row).addClass('danger-tr');
                    }
                },
                error: function (xhr) {
                    console.log(xhr.responseText);
                }
            });
        }

        $(document).on('click', '.edit-emergency-btn', function () {
            // Get data attributes
            let id = $(this).data('id');
            let name = $(this).data('name');
            let description = $(this).data('description');
            let assignedTeamId = $(this).data('assigned_default_team');
            let assignedTeamIds = $(this).data('assigned_default_team');
            // Parse if it's a stringified array
            if (typeof assignedTeamIds === 'string') {
                try {
                    assignedTeamIds = JSON.parse(assignedTeamIds);
                } catch (e) {
                    assignedTeamIds = assignedTeamIds.split(',').map(Number);
                }
            }
            // Clear previous custom fields first
            $('.AppendCustomField').empty();

            // Set input values
            $('#emergency_id').val(id);
            $('#emergency_name').val(name);
            $('#description').val(description);
            
            // Set Select2 dropdown value and trigger change
            $('#assign_default_team').val(assignedTeamIds).trigger('change');
            
                let customFields = $(this).data('custom_fields');
                let fields = typeof customFields === 'string' ? JSON.parse(customFields) : customFields;
                
                customFields.forEach(field => {
                let fieldName = field.name || '';
                let fieldValue = field.value || '';

                let fieldHtml = `
                    <div class="row mb-2 g-md-4 g-3">
                        <div class="col-4">
                            <input type="text" name="custom_field_names[]" class="form-control" placeholder="Field Name" value="${fieldName}">
                        </div>
                        <div class="col-4">
                            <input type="text" name="custom_field_values[]" class="form-control" placeholder="Field Value" value="${fieldValue}">
                        </div>
                        <div class="col-auto">
                        <button type="button" class="btn btn-danger remove-custom-field">Remove</button>
                    </div></div>`;

                $('.AppendCustomField').append(fieldHtml);
            });
            // Show the modal (if not auto-opened by data-bs-toggle)
            $('#editEmergencyType-modal').modal('show');
        });

        document.getElementById('add-custom-field').addEventListener('click', function() {
            const container = document.createElement('div');
            container.classList.add('row', 'mb-2','g-md-4','g-3');
            container.innerHTML = `
                <div class="col-6">
                    <input type="text" name="custom_field_names[]" class="form-control" placeholder="Field Name">
                </div>
                <div class="col-6">
                    <input type="text" name="custom_field_values[]" class="form-control" placeholder="Field Value">
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-danger remove-custom-field">Remove</button>
                </div>
            `;
            document.getElementById('custom-fields-container').appendChild(container);
        });

        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('remove-custom-field')) {
                event.target.closest('.row').remove();
            }
        });

        $('#assign_default_team').select2({
            dropdownParent: $('#editEmergencyType-modal'),
            placeholder: "Select Default Team",
            allowClear: true
        });
        

        $(document).on('click', '.delete-row-btn', function (e) {
            e.preventDefault();
            var $button = $(this);
            var $row = $button.closest("tr");
            var main_id = $(this).data('id');

            Swal.fire({
                title: 'Sure want to delete?',
                text: 'This cannot be undone',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'No',
                confirmButtonColor: "#DD6B55",
                customClass: {
                    icon: 'swal-icon-size'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "DELETE",
                        url: "{{ route('sos.config.SOSEmergencyTypesDestory', ':id') }}".replace(':id', main_id),
                        dataType: "json",
                    }).done(function(result) {
                        if (result.success) {
                            
                            toastr.success(result.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $('#IndexSOSEmergencyTypes').DataTable().ajax.reload();

                        } else {
                            toastr.error(result.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    }).fail(function(jqXHR) {
                        toastr.error(jqXHR.responseJSON?.message || "An unexpected error occurred.", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    });
                }
            });
        });

        $(document).on('submit', '#edit-emergency-type', function (e) {
            e.preventDefault();

            var formData = new FormData(this);

            // for (var pair of formData.entries()) {
            //     console.log(pair[0], pair[1]); // Log each key-value pair
            // }
            var emergencyId = $('#emergency_id').val();
            var updateUrl = "{{ route('sos.emergencyType.update', ':id') }}".replace(':id', emergencyId);

            // Make the AJAX request
            $.ajax({
                url: updateUrl,
                type: 'POST', // Laravel accepts PUT via POST with _method override
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                    location.reload();
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.errors;
                        // Loop through all validation errors
                        $.each(errors, function (key, messages) {
                            $.each(messages, function (i, message) {
                                toastr.error(message, "Validation Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            });
                        });
                    } else {
                        toastr.error('An unexpected error occurred.', "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                }
            });
        });
    });
        
</script>
@endsection
