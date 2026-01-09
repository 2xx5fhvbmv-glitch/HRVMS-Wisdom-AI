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
                        <span>People</span>
                        <h1>{{$page_title}}</h1>
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
                                        <h3>{{$page_title}}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <table id="ResignationReasons" class="table w-100">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">Reason</th>
                                    <th class="text-nowrap">Status</th>
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
        fecth();
    });

    function fecth(){
        if ($.fn.dataTable.isDataTable('#ResignationReasons')) {
            $('#ResignationReasons').DataTable().destroy();
        }
        var ResignationReasons = $('#ResignationReasons').DataTable({
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
            ajax: '{{ route("people.resignation-reason.list") }}',
            columns: [
                { data: 'reason', name: 'reason', className: 'text-nowrap' }, // Fixed column name
                { data: 'status', name: 'status'}, 
                { data: 'action', name: 'action', orderable: false, searchable: false }, // Fixed 'Action' to 'action'
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
                    url: "{{ route('people.resignation-reasons.destroy', ':id') }}".replace(':id', main_id),
                    dataType: "json",
                }).done(function(result) {
                    if (result.success) {
                        toastr.success(result.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        $('#ResignationReasons').DataTable().ajax.reload();

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

    $(document).on("click", "#ResignationReasons .edit-row-btn", function (event) {
        event.preventDefault(); // Prevent default action
        // Find the parent row
        var $row = $(this).closest("tr");
        var Main_id = $(this).attr('data-id');
        var reason = $row.find("td:nth-child(1)").text().trim();
        var currentStatus = $row.find("td:nth-child(2)").text().trim();

        var editRowHtml = `
            <td class="py-1">
                <div class="form-group">
                    <input type="text" class="form-control reason" value="${reason}" />
                </div>
            </td>
            <td class="py-1">
                <div class="form-group">
                    <select class="form-control status" id="status">
                        <option value="Active" ${currentStatus === 'Active' ? 'selected' : ''}>Active</option>
                        <option value="Inactive" ${currentStatus === 'Inactive' ? 'selected' : ''}>Inactive</option>
                    </select>
                </div>
            </td>
            <td class="py-1">
                <a href="javascript:void(0)" class="btn btn-theme update-row-btn_cat" data-id="${Main_id}">Submit</a>
            </td>
        `;

        $row.html(editRowHtml);
    });

    $(document).on("click", "#ResignationReasons .update-row-btn_cat", function (event) {
        event.preventDefault(); // Prevent default action
        var $row = $(this).closest("tr");
        var Main_id = $(this).attr('data-id');
        var reason = $row.find("input.reason").val();
        var Status = $row.find("select.status").val();

        $.ajax({
            url: "{{ route('people.resignation-reasons.inlineUpdate', '') }}/" + Main_id,
            type: "POST",
            data: {
                Main_id: Main_id,
                reason: reason,
                status: Status
            },
            success: function(response) {
                if (response.success == true) { // Ensure response contains a success key
                    $('#ResignationReasons').DataTable().ajax.reload();
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
                    });
                } else {
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
