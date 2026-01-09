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

                        <table id="IncidentActiontaken" class="table w-100">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">Action Taken Name</th>
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
        fecthIncidentActiontype();
    });

    function fecthIncidentActiontype(){
        var IncidentActiontaken = $('#IncidentActiontaken').DataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": true,
            "bAutoWidth": false,
            "scrollX": true,
            "iDisplayLength": 10,
            processing: true,
            serverSide: true,
            ajax: '{{ route("incident.action-taken.list") }}',
            columns: [
                { data: 'action_taken', name: 'action_taken', className: 'text-nowrap' }, // Fixed column name
                { data: 'action', name: 'action', orderable: false, searchable: false }, // Fixed 'Action' to 'action'
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
                    url: "{{ route('incident.action-taken.destory', ':id') }}".replace(':id', main_id),
                    dataType: "json",
                }).done(function(result) {
                    if (result.success) {
                        toastr.success(result.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        $('#IncidentActiontaken').DataTable().ajax.reload();

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

    $(document).on("click", "#IncidentActiontaken .edit-row-btn", function (event) {
        event.preventDefault(); // Prevent default action
        // Find the parent row
        var $row = $(this).closest("tr");
        var Main_id = $(this).attr('data-cat-id');
        var actionTaken = $row.find("td:nth-child(1)").text().trim();                
        var editRowHtml = `
                <td class="py-1">
                    <div class="form-group">
                        <input type="text" class="form-control actionTaken" value="${actionTaken}" />
                    </div>
                </td>
                <td class="py-1">
                    <a href="javascript:void(0)" class="btn btn-theme update-row-btn_cat" data-cat-id="${Main_id}">Submit</a>
                </td>
            `;

        // // Replace row content with editable form
        $row.html(editRowHtml);
    
    });

    $(document).on("click", "#IncidentActiontaken .update-row-btn_cat", function (event) {
        event.preventDefault(); // Prevent default action
        var $row = $(this).closest("tr");
        var Main_id = $(this).attr('data-cat-id');
        var actionTaken = $row.find("input").eq(0).val();
        $.ajax({
            url: "{{ route('incident.action-taken.inlineUpdate', '') }}/" + Main_id,
            type: "PUT",
            data: {
                Main_id:Main_id,
                action_taken:actionTaken
            },
            success: function(response) {
                if(response.success == true) { // Ensure response contains a success key

                    $('#IncidentActiontaken').DataTable().ajax.reload();
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
