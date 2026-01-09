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

                        <table id="IndexSOSTeamManagement" class="table  w-100">
                            <thead>
                                <tr>
                                    <th class="text-nowrap"> Team Name  </th>
                                    <th class="text-nowrap"> Description  </th>
                                    <th class="text-nowrap"> Assigned Members </th>
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
<div class="modal fade" id="editTeam-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form id="edit-team-details" name="edit-team-details">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Edit Team</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-xxl-4 g-3 align-items-end">
                        <input type="hidden" id="AppendTeamMemberCount" value="0">
                        <input type="hidden" name="team_id" id="team_id" />
                        <div class="col-12">
                            <input type="text" class="form-control mb-3" id="team_name" name="team_name" placeholder="Team Name" data-parsley-required="true" data-parsley-error-message="Please Enter Team Name">
                        </div>
                        <div class="row g-md-4 g-3 AppendTeamMember">

                        </div>
                        
                        <div class="col-12">
                            <a href="javascript:void(0);" class="btn btn-themeSkyblue btn-sm AddAppendTeamMember blockAdd-btn">Add More</a>
                        </div>
                        <div class="col-12">
                            <textarea class="form-control description" id="description" name="description" placeholder="Description" rows="3" 
                            data-parsley-required="true" 
                            data-parsley-required-message="Please Enter Description"
                            data-parsley-validate-script 
                            data-parsley-validate-script-message="Script tags are not allowed." 
                            data-parsley-errors-container="#description-error-1" 
                            data-parsley-pattern="^(?!.*<[^>]*>).*$"></textarea>
                            <div id="description-error-1"></div>
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

        $('#edit-team-details').parsley();

        getSOSTeams();
        // Trigger search on input change
        $('#searchInput').on('keyup change', function () {
            getSOSTeams(); // Reload emergency types
        });
        function getSOSTeams() {
            if ($.fn.DataTable.isDataTable("#IndexSOSTeamManagement")) {
                $("#IndexSOSTeamManagement").DataTable().destroy();
            }
            var TeamManagement = $('#IndexSOSTeamManagement').DataTable({
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
                    url: '{{ route("sos.config.IndexSOSTeamManagement") }}',
                    type: "GET",
                    data: function (d) {
                        d.searchTerm = $('#searchInput').val();
                    }
                },
                columns: [
                    { data: 'name', name: 'name', className: 'text-nowrap' },
                    { data: 'description', name: 'description', className: 'text-nowrap' },
                    { data: 'assigned_employees', name: 'assigned_employees', className: 'text-nowrap' },
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

        $(document).on('click', '.edit-team-btn', function () {
            const teamId = $(this).data('id');

            $.ajax({
                url: "{{ route('sos.team.edit', ':id') }}".replace(':id', teamId),
                method: 'GET',
                success: function (response) {
                    $('#team_id').val(response.id);
                    $('#team_name').val(response.name);
                    $('#description').val(response.description);

                    $('.AppendTeamMember').html('');
                    $("#AppendTeamMemberCount").val(0);

                    window.allEmployees = response.all_employees.map(e => ({ id: e.id, name: e.name }));
                    window.allRoles = response.all_roles.map(r => ({ id: r.id, name: r.name }));

                    // Group members by role for multi-select
                    let grouped = {};
                    response.members.forEach(member => {
                        if (!grouped[member.member_role]) grouped[member.member_role] = [];
                        grouped[member.member_role].push(member.team_member_id);
                    });

                    let index = 0;
                    Object.keys(grouped).forEach(roleId => {
                        let selectedMembers = grouped[roleId];

                        // Build employee options with selected
                        let employeeOptions = window.allEmployees.map(emp => {
                            let selected = selectedMembers.includes(emp.id) ? 'selected' : '';
                            return `<option value="${emp.id}" ${selected}>${emp.name}</option>`;
                        }).join('');

                        // Build role options with selected
                        let roleOptions = window.allRoles.map(role => {
                            let selected = role.id == roleId ? 'selected' : '';
                            return `<option value="${role.id}" ${selected}>${role.name}</option>`;
                        }).join('');

                        const html = `
                        <div class="row g-3 TeamMember_${index}">
                            <div class="col-md-5 team-member-group">
                                <label for="team_member" class="form-label fw-bold">Team Member</label>
                                <select class="form-select select2t-none" name="employee[${index}][team_member][]" multiple="multiple">
                                    ${employeeOptions}
                                </select>
                            </div>
                            <div class="col-md-5 team-member-group">
                                <label for="member_role" class="form-label fw-bold">Role</label>
                                <select class="form-select select2t-none" name="employee[${index}][member_role]">
                                    ${roleOptions}
                                </select>
                            </div>
                            <div class="col-md-2 team-member-group">
                                <label for="member_role" class="form-label fw-bold d-md-block d-none">&nbsp;</label>
                                <button type="button" class="btn btn-danger btn-sm TeamMemberRemove mt-md-1" data-id="${index}">
                                    <i class="fa fa-trash"></i> 
                                </button>
                            </div>
                        </div>
                        `;
                        $('.AppendTeamMember').append(html);
                        // Initialize select2 for both selects
                        $('.AppendTeamMember .TeamMember_' + index + ' .select2t-none').select2({
                            width: '100%'
                        });
                        index++;
                    });

                    $("#AppendTeamMemberCount").val(index);

                    $('#editTeam-modal').modal('show');
                }
            });
        });

        //Team management form start
        $(document).on('click','.AddAppendTeamMember',function() {
            let TeamMemberCount = parseInt($("#AppendTeamMemberCount").val()) || 0;
            TeamMemberCount++;

            var row = `
                <div class="row g-md-4 g-3 mb-md-4 mb-3 TeamMember_${TeamMemberCount}">
                    <!-- Team member -->
                    <div class="col-md-6 team-member-group">
                        <label for="team_member" class="form-label fw-bold">Team Member</label>
                        <select class="form-select select2t-none"
                            name="employee[${TeamMemberCount}][team_member][]"
                            id="TeamMemberMain_${TeamMemberCount}"
                            data-id="${TeamMemberCount}"
                            multiple="multiple"
                            required
                            data-parsley-required="true"
                            data-parsley-error-message="Please select a team member">
                            ${window.allEmployees.map(emp => `<option value="${emp.id}">${emp.name}</option>`).join('')}
                        </select>
                    </div>
                    <!-- Role -->
                    <div class="col-md-6 team-member-group">
                        <label for="member_role" class="form-label fw-bold">Role</label>
                        <select class="form-select select2t-none"
                            name="employee[${TeamMemberCount}][member_role]"
                            id="RoleMain_${TeamMemberCount}"
                            data-id="${TeamMemberCount}"
                            required
                            data-parsley-required="true"
                            data-parsley-error-message="Please select a role">
                            <option value=""></option>
                            ${window.allRoles.map(role => `<option value="${role.id}">${role.name}</option>`).join('')}
                        </select>
                    </div>

                    <!-- Remove Button -->
                    <div class="col-md-6 d-flex align-items-end team-member-group">
                        <button type="button" class="btn btn-danger btn-sm TeamMemberRemove" 
                                data-id="${TeamMemberCount}">
                            <i class="fa fa-trash"></i> 
                        </button>
                    </div>
                </div>
                `;
            $(".AppendTeamMember").append(row);
            $("#AppendTeamMemberCount").val(TeamMemberCount);
            $("#TeamMemberMain_"+TeamMemberCount).select2({
                placeholder: "Select Employee",
                allowClear: true,
                width: '100%'        
            });
            $("#RoleMain_"+TeamMemberCount).select2({
                placeholder: "Select Role",
                allowClear: true,
                width: '100%'        
            });
        }); 
        $(document).on('click','.TeamMemberRemove',function()
        {
            var loction =$(this).data('id');
            $(".TeamMember_"+loction).remove();
            var count = parseInt($("#AppendTeamMemberCount").val())-1;
            $("#AppendTeamMemberCount").val(count);
        });
        //Team management form end

        
        $(document).on('submit', '#edit-team-details', function (e) {
            e.preventDefault();

            var formData = new FormData(this);

            for (var pair of formData.entries()) {
                console.log(pair[0], pair[1]); // Log each key-value pair
            }
            var teamId = $('#team_id').val();
            var updateUrl = "{{ route('sos.team.update', ':id') }}".replace(':id', teamId);

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
                confirmButtonColor: "#DD6B55"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "DELETE",
                        url: "{{ route('sos.config.SOSTeamManagementDestory', ':id') }}".replace(':id', main_id),
                        dataType: "json",
                    }).done(function(result) {
                        if (result.success) {
                            
                            toastr.success(result.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $('#IndexSOSTeamManagement').DataTable().ajax.reload();

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

        //This is for inline update 

        /* $(document).on("click", "#IndexSOSTeamManagement .edit-row-btn", function (event) {
            event.preventDefault(); // Prevent default action
            
            // Find the parent row
            var $row = $(this).closest("tr");

            var Main_id = $(this).attr('data-id');
            var escapedId = $.escapeSelector(Main_id); 
            var name = $(this).attr('data-name');

            var $TeamNameCell = $row.find("td:nth-child(1)");
            var $DescriptionCell = $row.find("td:nth-child(2)");

            var TeamName = $TeamNameCell.text().trim();
            var Description = $DescriptionCell.text().trim();

            $TeamNameCell.html(`
                                <input type="text" class="form-control" id="name" name="name" value="${TeamName}" placeholder="Team Name">
                            `);
            $DescriptionCell.html(`
                            <textarea type="text" class="form-control description" >${Description}</textarea>
                            `);

            $row.find("td:last-child").html(`
                <button class="btn btn-sm btn-success update-row-btn me-1" data-id="${Main_id}">Update</button>
                <button class="btn btn-sm btn-secondary cancel-row-btn" data-id="${Main_id}">Cancel</button>
            `);

            $row.data('name', TeamName);
            $row.data('description', Description);
        
        });

        // Cancel edit
        $(document).on("click", "#IndexSOSTeamManagement .cancel-row-btn", function () {
            var $row = $(this).closest("tr");
            var Main_Id = $(this).data('id');

            var TeamName = $row.data('name');
            var Description = $row.data('description');

            $row.find("td:nth-child(1)").text(TeamName);
            $row.find("td:nth-child(2)").text(Description);

            var viewUrl = `{!! str_replace('__MEETING_ID__', '${Main_Id}', $viewRoute) !!}`;

            $row.find("td:last-child").html(`
                <a href="${viewUrl}" title="View Team Detail" class="btn-tableIcon btnIcon-yellow me-1">
                    {!! $viewIcon !!}
                </a>
                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-id="${Main_Id}">
                    <img src="{{ $editIcon }}" alt="Edit" class="img-fluid">
                </a>
                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-id="${Main_Id}">
                    <img src="{{ $trashIcon }}" alt="Delete" class="img-fluid">
                </a>
            `);
        });

        $(document).on("click", "#IndexSOSTeamManagement .update-row-btn", function (event) {
            event.preventDefault(); // Prevent default action
            var $row = $(this).closest("tr");
            var Main_id = $(this).attr('data-id');
            var team_name = $row.find("input").eq(0).val();
            var description = $row.find("textarea").val();
            $.ajax({
                url: "{{ route('sos.config.SOSTeamManagementinlineUpdate', '') }}/" + Main_id,
                type: "PUT",
                data: {
                    Main_id:Main_id,
                    team_name:team_name,
                    description:description,
                },
                success: function(response) {
                    if(response.success == true) { // Ensure response contains a success key

                        $('#IndexSOSTeamManagement').DataTable().ajax.reload();
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(xhr) {

                        let errorMessage = '';

                        if (xhr.responseJSON && xhr.responseJSON.errors) {

                            $.each(xhr.responseJSON.errors, function(key, error) {

                            errorMessage += error + "<br>";
                            })
                        }
                        else
                        {
                            errorMessage = "An error occurred while Create or Update."; // Default error message
                        }

                        toastr.error(errorMessage, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
            });
        }); */
    });
</script>
@endsection
