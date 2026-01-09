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
@endsection

@section('import-css')
@endsection

@section('import-scripts')

@php
    $viewIcon = '<i class="fa-regular fa-eye"></i>';
    $editIcon = asset("resorts_assets/images/edit.svg");
    $trashIcon = asset("resorts_assets/images/trash-red.svg");
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

            var roleOptions = `<option value=""></option>`;
            var employeeOptions = `<option value=""></option>`;
            @foreach($getMembers as $emp)
                employeeOptions += `<option value="{{ $emp->id }}" ${member_id == "{{ $emp->id }}" ? 'selected' : ''}>{{ $emp->first_name. ' ' . $emp->last_name}}</option>`;
            @endforeach

            @foreach($Roles as $item)
                roleOptions += `<option value="{{ $item->id }}" ${role_id == "{{ $item->id }}" ? 'selected' : ''}>{{ $item->name }}</option>`;
            @endforeach

            $memberCell.html(`<select class="form-select select2t-none" name="member_id" id="member_id">
                                ${employeeOptions}
                                </select>`);
            $roleCell.html(`<select class="form-select select2t-none" name="role_id" id="role_id">
                                ${roleOptions}
                                </select>`);

            $row.find("td:last-child").html(`
                <button class="btn btn-sm btn-success update-row-btn me-1" data-id="${Main_id}">Update</button>
                <button class="btn btn-sm btn-secondary cancel-row-btn" data-id="${Main_id}">Cancel</button>
            `);

            $row.data('original-member', orgMember);
            $row.data('original-role', orgRole);

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
                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-id="${Main_Id}">
                    <img src="{{ $editIcon }}" alt="Edit" class="img-fluid">
                </a>
                <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-id="${Main_Id}">
                    <img src="{{ $trashIcon }}" alt="Delete" class="img-fluid">
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
                            errorMessage = "An error occurred while Create or Update."; // Default error message
                        }

                        toastr.error(errorMessage, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
            });
        });
        
</script>
@endsection
