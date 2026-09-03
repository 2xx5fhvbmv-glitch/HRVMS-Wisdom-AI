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
                        <div class="card-title">
                            <div class="row g-3 align-items-center justify-content-between">
                                <div class="col-auto">
                                    <div class="d-flex justify-content-start align-items-center">
                                        <h3>{{ $team->name }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-3 align-items-center justify-content-between">
                                <div class="col-auto">
                                    <div class="d-flex justify-content-start align-items-center">
                                        <p>{{ $team->description }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" value="{{ $team->id }}" id="main_id">
                        <table id="SOSTeamDetail" class="table  w-100">
                            <thead>
                                <tr>
                                    <th class="text-nowrap"> Member Name  </th>
                                    <th class="text-nowrap"> Member Role  </th>
                                </tr>
                            </thead>

                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@include('resorts._emotional_buttons_v2_styles')
@include('resorts._dropdown_styles')
@include('resorts._dropdown_script')
@endsection

@section('import-css')
@endsection

@section('import-scripts')

@php
    $viewIcon = '<i class="fa-regular fa-eye"></i>';
    $editIcon = '<i class="fa-solid fa-pen-to-square"></i>';
    $trashIcon = '<i class="fa-regular fa-trash-can"></i>';
    $viewRoute = route('incident.meeting.detail', '__MEETING_ID__'); // placeholder
@endphp

<script>
        var Main_id = $('#main_id').val();
        var TeamManagement = $('#SOSTeamDetail').DataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": true,
            "bAutoWidth": false,
            "scrollX": true,
            "iDisplayLength": 6,
            processing: true,
            serverSide: true,
            order:[[2, 'desc']],
            ajax: "{{ route('sos.team.getTeamDetails', ':id') }}".replace(':id', Main_id),
            columns: [
                { data: 'team_member_name', name: 'team_member_name', className: 'text-nowrap' },
                { data: 'member_role', name: 'member_role', className: 'text-nowrap' },
                {data:'created_at', visible:false,searchable:false},
            ]
        });

        $(document).on('click', '.delete-row-btn', function (e) {
            e.preventDefault();
            var $button = $(this);
            var $row = $button.closest("tr");
            var main_id = $(this).data('id');

            wisdomConfirm({
                role: 'destructive',
                title: 'Sure want to delete?',
                text: 'This cannot be undone',
                confirmText: 'Yes',
                cancelText: 'No'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "DELETE",
                        url: "{{ route('sos.config.SOSTeamMemberDestory', ':id') }}".replace(':id', main_id),
                        dataType: "json",
                    }).done(function(result) {
                        if (result.success) {
                            
                            toastr.success(result.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $('#SOSTeamDetail').DataTable().ajax.reload();

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
        
        // Edit meeting row
        $(document).on("click", "#SOSTeamDetail .edit-row-btn", function (event) {
            event.preventDefault();

            var $row = $(this).closest("tr");
            var Main_id = $(this).data('id');
            var member_id = $(this).attr('data-member_id');
            var role_id = $(this).attr('data-role_id');
            var escapedId = $.escapeSelector(Main_id); // Use escaped selector for safety

            var $memberCell = $row.find("td:nth-child(1)");
            var $roleCell = $row.find("td:nth-child(2)");

            var orgMember = $memberCell.text().trim();
            var orgRole = $roleCell.text().trim();

            var tickSvg = '<svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg>';
            var roleOptions = `<option value=""></option>`;
            var employeeOptions = `<option value=""></option>`;
            var roleItems = '';
            var employeeItems = '';
            @foreach($getMembers as $emp)
                employeeOptions += `<option value="{{ $emp->id }}" ${member_id == "{{ $emp->id }}" ? 'selected' : ''}>{{ $emp->first_name. ' ' . $emp->last_name}}</option>`;
                employeeItems += `<div class="dd-item${member_id == "{{ $emp->id }}" ? ' active' : ''}" role="option" data-value="{{ $emp->id }}"><span class="dd-nm">{{ $emp->first_name. ' ' . $emp->last_name}}</span>${tickSvg}</div>`;
            @endforeach

            @foreach($Roles as $item)
                roleOptions += `<option value="{{ $item->id }}" ${role_id == "{{ $item->id }}" ? 'selected' : ''}>{{ $item->name }}</option>`;
                roleItems += `<div class="dd-item${role_id == "{{ $item->id }}" ? ' active' : ''}" role="option" data-value="{{ $item->id }}"><span class="dd-nm">{{ $item->name }}</span>${tickSvg}</div>`;
            @endforeach

            $memberCell.html(`<select class="form-select dd-native-select" name="member_id" id="member_id">
                                ${employeeOptions}
                                </select>
                                <div class="dd" data-target="#member_id">
                                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                        <span class="dd-lbl">Select Member</span>
                                        <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                    </button>
                                    <div class="dd-panel" role="listbox" aria-label="Member">
                                        <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a member…"></div>
                                        <div class="dd-scroll">${employeeItems}</div>
                                    </div>
                                </div>`);
            $roleCell.html(`<select class="form-select dd-native-select" name="role_id" id="role_id">
                                ${roleOptions}
                                </select>
                                <div class="dd" data-target="#role_id">
                                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                        <span class="dd-lbl">Select Role</span>
                                        <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                    </button>
                                    <div class="dd-panel" role="listbox" aria-label="Role">
                                        <div class="dd-scroll">${roleItems}</div>
                                    </div>
                                </div>`);

            $row.find("td:last-child").html(`
                <button class="btn btn-sm eb-btn-positive update-row-btn me-1" data-id="${Main_id}">Update</button>
                <button class="btn btn-sm eb-btn-neutral cancel-row-btn" data-id="${Main_id}">Cancel</button>
            `);

            $row.data('original-member', orgMember);
            $row.data('original-role', orgRole);

            wisdomDD.sync('#member_id');
            wisdomDD.sync('#role_id');

        });
        // Cancel edit
        $(document).on("click", "#SOSTeamDetail .cancel-row-btn", function () {
            var $row = $(this).closest("tr");
            var Main_Id = $(this).data('id');

            console.log("main", Main_Id);

            var orgMember = $row.data('original-member');
            var orgRole = $row.data('original-role');

            $row.find("td:nth-child(1)").text(orgMember);
            $row.find("td:nth-child(2)").text(orgRole);

            $row.find("td:last-child").html(`
                <a href="javascript:void(0)" class="btn-tableIcon btnIcon-yellow me-1 edit-row-btn" data-id="${Main_Id}">
                    {!! $editIcon !!}
                </a>
                <a href="javascript:void(0)" class="btn-tableIcon eb-icon-critical delete-row-btn" data-id="${Main_Id}">
                    {!! $trashIcon !!}
                </a>
            `);
        });

        $(document).on("click", "#SOSTeamDetail .update-row-btn", function (event) {
            event.preventDefault(); // Prevent default action
            var $row = $(this).closest("tr");
            var Main_id = $(this).attr('data-id');
            var team_id =  $('#main_id').val();

            var member_id = $row.find("select").eq(0).val();
            var role_id = $row.find("select").eq(1).val();
            $.ajax({
                url: "{{ route('sos.config.SOSTeamMemberinlineUpdate', '') }}/" + Main_id,
                type: "PUT",
                data: {
                    Main_id:Main_id,
                    team_id:team_id,
                    member_id:member_id,
                    role_id:role_id,
                },
                success: function(response) {
                    if(response.success == true) { // Ensure response contains a success key

                        $('#SOSTeamDetail').DataTable().ajax.reload();
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
                            errorMessage = "An error occurred while creating or updating."; // Default error message
                        }

                        toastr.error(errorMessage, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
            });
        });
        
</script>
@endsection
