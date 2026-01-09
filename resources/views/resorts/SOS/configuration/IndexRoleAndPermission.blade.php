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
                                        <h3>{{ $page_title }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <table id="IndexSOSRolesAndPermission" class="table  w-100">
                            <thead>
                                <tr>
                                    <th class="text-nowrap"> Role Name  </th>
                                    <th class="text-nowrap"> Assigned Permission </th>
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
@endsection

@section('import-css')
@endsection

@section('import-scripts')

<script>
    window.sosAssignPermissions = @json(config('settings.sosAssignPermissions'));
</script>

<script>
    var DisciplineryCategory = $('#IndexSOSRolesAndPermission').DataTable({
        "searching": false,
        "bLengthChange": false,
        "bFilter": true,
        "bInfo": true,
        "bAutoWidth": false,
        "scrollX": true,
        "iDisplayLength": 6,
        processing: true,
        serverSide: true,
        order:[[3, 'desc']],
        ajax: '{{ route("sos.config.IndexSOSRolesAndPermission") }}',
        columns: [
            { data: 'name', name: 'name', className: 'text-nowrap' },
            { data: 'assigned_permission', name: 'assigned_permission', className: 'text-nowrap' },
            { data: 'Action', name: 'Action', orderable: false, searchable: false },
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
                    url: "{{ route('sos.config.SOSRolesAndPermissionDestory', ':id') }}".replace(':id', main_id),
                    dataType: "json",
                }).done(function(result) {
                    if (result.success) {
                        
                        toastr.success(result.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        $('#IndexSOSRolesAndPermission').DataTable().ajax.reload();

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

    $(document).on("click", "#IndexSOSRolesAndPermission .edit-row-btn", function (event) {
        event.preventDefault();

        var $row = $(this).closest("tr");
        var Main_id = $(this).attr('data-id');
        var RoleName = $row.find("td:nth-child(1)").text().trim();
        var Permission = $row.find("td:nth-child(2)").text().trim();

        // Get the permission IDs from the data attribute if available, else from text
        var selectedPermissions = $(this).data('assign_permission');
        if (!selectedPermissions) {
            selectedPermissions = Permission.split(',').map(s => s.trim());
        }
        if (typeof selectedPermissions === "string") {
            try {
                selectedPermissions = JSON.parse(selectedPermissions);
            } catch (e) {
                selectedPermissions = selectedPermissions.split(',');
            }
        }

        // Use the config from PHP
        let permissionsOptions = window.sosAssignPermissions || {};

        var editRowHtml = `
            <td class="py-1">
                <div class="form-group">
                    <input type="text" class="form-control" id="role_name" name="role_name" value="${RoleName}" placeholder="Role Name">
                </div>
            </td>
            <td class="py-1">
                <div class="form-group">
                    <select class="form-select select2t-none" id="assign_permission" name="assign_permission[]" multiple="multiple" data-parsley-required="true" data-parsley-error-message="Please select a permission">
                        ${Object.entries(permissionsOptions).map(([k, v]) => `<option value="${k}" ${selectedPermissions.includes(k) || selectedPermissions.includes(parseInt(k)) ? 'selected' : ''}>${v}</option>`).join('')}
                    </select>
                </div>
            </td>
            <td class="py-1">
                <a href="javascript:void(0)" class="btn btn-theme update-row-btn_cat" data-id="${Main_id}">Submit</a>
            </td>`;
        $row.html(editRowHtml);

        $("#assign_permission").select2({
            placeholder: "Select Permission",
            allowClear: true,
            width: '100%'        
        });
    });

    $(document).on("click", "#IndexSOSRolesAndPermission .update-row-btn_cat", function (event) {
        event.preventDefault(); // Prevent default action
        var $row = $(this).closest("tr");
        var Main_id = $(this).attr('data-id');
        var role_name = $row.find("input").eq(0).val();
        var permission = $row.find("select").eq(0).val();
        $.ajax({
            url: "{{ route('sos.config.SOSRoleAndPerminlineUpdate', '') }}/" + Main_id,
            type: "PUT",
            data: {
                Main_id:Main_id,
                role_name:role_name,
                assign_permission:permission,
            },
            success: function(response) {
                if(response.success == true) { // Ensure response contains a success key

                    $('#IndexSOSRolesAndPermission').DataTable().ajax.reload();
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
