@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

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
                        <span>Incident</span>
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
                                        <h3>{{ $page_title }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <table id="IncidentCommittee" class="table w-100">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">Committee Name</th>
                                    <th class="text-nowrap">Member</th>
                                    <th class="text-nowrap">Action</th>
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

<script>
    $(document).ready(function(){
        fecthIncidentCommittee();
        window.members = @json($CommitteeMembers);
    });

    function fecthIncidentCommittee(){
        var IncidentCommittee = $('#IncidentCommittee').DataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": true,
            "bAutoWidth": false,
            "scrollX": true,
            "iDisplayLength": 10,
            processing: true,
            serverSide: true,
            order:[[3, 'desc']],
            ajax: '{{ route("incident.committees.list") }}',
            columns: [
                { data: 'commitee_name', name: 'commitee_name', className: 'text-nowrap' },
                { data: 'member', name: 'member', className: 'text-nowrap' }, // ✅ Show members
                { data: 'action', name: 'action', orderable: false, searchable: false }, // ✅ Actions
                {data:'created_at',visible:false,searchable:false},
            ],
            error: function(xhr, error, code) {
                console.log(xhr.responseText);
            }
        });
    }
        
    $(document).on('click', '.delete-row-btn', function (e) {
        e.preventDefault();
        var $button = $(this);
        var $row = $button.closest("tr");
        var main_id = $(this).data('cat-id');

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
                    url: "{{ route('incident.committees.destory', ':id') }}".replace(':id', main_id),
                    dataType: "json",
                }).done(function(result) {
                    if (result.success) {
                        toastr.success(result.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        $('#IncidentCommittee').DataTable().ajax.reload();

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

    $(document).on("click", "#IncidentCommittee .edit-row-btn", function (event) {
        event.preventDefault(); // Prevent default action

        var $row = $(this).closest("tr");
        var Main_id = $(this).attr('data-cat-id');
        var committeeName = $row.find("td:nth-child(1)").text().trim(); 
        var members = $row.find("td:nth-child(2)").text().trim().split(", "); // Convert members text into an array

        var memberOptions = '';
        window.members.forEach(function(member) {
            var memberName = member.first_name + " " + member.last_name;
            var selected = members.includes(memberName) ? 'selected' : ''; // Check if member is already assigned
            memberOptions += `<option value="${member.id}" ${selected}>${memberName}</option>`;
        });

        var editRowHtml = `
            <td class="py-1">
                <div class="form-group">
                    <input type="text" class="form-control categoryName" value="${committeeName}"/>
                </div>
            </td>
            <td class="py-1">
                <div class="form-group">
                    <select class="form-select select2t-none committee-members-select" name="committee_members[]" multiple>
                        ${memberOptions}
                    </select>
                </div>
            </td>
            <td class="py-1">
                <a href="javascript:void(0)" class="btn btn-theme update-row-btn_cat" data-cat-id="${Main_id}">Submit</a>
            </td>
        `;

        // Replace row content with editable form
        $row.html(editRowHtml);
        $row.find(".select2t-none").select2();
    });

    $(document).on("click", "#IncidentCommittee .update-row-btn_cat", function (event) {
        event.preventDefault();
        var $row = $(this).closest("tr");
        var Main_id = $(this).attr('data-cat-id');
        var committeeName = $row.find("input.categoryName").val();
        var committeeMembers = $row.find(".committee-members-select").val(); // Get selected members as an array

        $.ajax({
            url: "{{ route('incident.committees.inlineUpdate', '') }}/" + Main_id,
            type: "PUT",
            data: {
                committee_name: committeeName,
                committee_members: committeeMembers
            },
            success: function(response) {
                if (response.success) {
                    $('#IncidentCommittee').DataTable().ajax.reload();
                    toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                } else {
                    toastr.error(response.message, "Error", { positionClass: 'toast-bottom-right' });
                }
            },
            error: function(xhr) {
                let errorMessage = '';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function(key, error) {
                        errorMessage += error + "<br>";
                    });
                } else {
                    errorMessage = "An error occurred while updating the committee.";
                }
                toastr.error(errorMessage, "Error", { positionClass: 'toast-bottom-right' });
            }
        });
    });

</script>
@endsection
