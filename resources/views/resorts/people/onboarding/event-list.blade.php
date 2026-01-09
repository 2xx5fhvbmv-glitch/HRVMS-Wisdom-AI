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
                            <span>Peple</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="card">
                    <div class="card-header">
                        <div class="row g-md-3 g-2 align-items-center">
                            <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                                <div class="input-group">
                                    <input type="search" class="form-control search" placeholder="Search" />
                                    <i class="fa-solid fa-search"></i>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                    <table id="eventsTable" class="table table-exitclearance-form w-100">
                        <thead>
                            <tr>
                                <th>Event Name</th>
                                <th>Notification Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
    <script>
        window.notificationTimings = @json($notificationTimings ?? []);
        console.log(window.notificationTimings);
    </script>
    <script type="text/javascript">
        $(document).ready(function() {
            $(".select2t-none").select2();
            $('#eventsTable tbody').empty();
            var viewForm = $('#eventsTable').DataTable({
                searching: false,
                bLengthChange: false,
                bFilter: true,
                bInfo: true,
                bAutoWidth: false,
                scrollX: true,
                iDisplayLength: 6,
                processing: true,
                serverSide: true,
                order:[[3, 'desc']],
                ajax: {
                    url: '{{ route("onboarding.events") }}',
                    type: 'GET',
                    data: function(d) {
                        var searchTerm = $('.search').val();
                        d.searchTerm = searchTerm;
                    }
                },
                columns: [
                    { data: 'event_name', name: 'event_name', className: 'text-nowrap'},
                    { data: 'notification_time', name: 'notification_time', className: 'text-nowrap' },
                    { data: 'action', name: 'Action', orderable: false, searchable: false },
                    {data:'created_at',visible:false,searchable:false},
                ]
            });

            $(document).on('click', '.delete-row-btn', function() {
                let id = $(this).data('id');
                let url = "{{ route('onboarding.events.destroy', ':id') }}";
                url = url.replace(':id', id);

                Swal.fire({
                    title: 'Are you sure you want to delete?',
                    text: 'This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No',
                    confirmButtonColor: '#DD6B55',
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: url,
                            method: "DELETE",
                            success: function(response) {
                                if (response.success) {
                                    toastr.success(response.message, "Success", {
                                        positionClass: 'toast-bottom-right',
                                    });
                                    $('#eventsTable').DataTable().ajax.reload();
                                } 
                            },
                            error: function(xhr, status, error) {
                                toastr.error('An error occurred while processing your request.', "Error", {
                                    positionClass: 'toast-bottom-right',
                                });
                                console.error('Error:', error);
                            },
                        });
                    }
                });
            });

            $(document).on("click", ".edit-row-btn", function (event) {
                event.preventDefault();

                let $row = $(this).closest("tr");
                let Main_id = $(this).data('id'); // Get encoded ID
                let eventName = $row.find("td").eq(0).text().trim();
                let currentNotificationTime = $row.find("td").eq(1).text().trim();

                let options = '<option value="">Notification Time</option>';
                console.log(window.notificationTimings);
                for (const [key, value] of Object.entries(window.notificationTimings)) {
                    let selected = (value === currentNotificationTime) ? 'selected' : '';
                    options += `<option value="${key}" ${selected}>${value}</option>`;
                }
                console.log(options);
                let editRowHtml = `
                    <td class="py-1">
                        <input type="text" class="form-control eventName" value="${eventName}" />
                    </td>
                    <td class="py-1">
                        <select class="form-select notification_timing select2t-none">${options}</select>
                    </td>
                    <td class="py-1">
                        <a href="javascript:void(0)" class="btn btn-theme update-row-btn" data-id="${Main_id}">Submit</a>
                    </td>
                `;

                $row.html(editRowHtml);
                $row.find('.select2t-none').select2(); // reinitialize select2 if needed
            });

            $(document).on("click", ".update-row-btn", function (event) {
                event.preventDefault();

                let $row = $(this).closest("tr");
                let Main_id = $(this).data('id');
                let event_name = $row.find(".eventName").val();
                let notification_timing = $row.find(".notification_timing").val();

                $.ajax({
                    url: "{{ route('onboarding.events.inlineUpdate', '') }}/" + Main_id,
                    type: "POST",
                    data: {
                        Main_id: Main_id,
                        event_name: event_name,
                        notification_timing: notification_timing,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#eventsTable').DataTable().ajax.reload();
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                        } else {
                            toastr.error(response.message || "Failed to update.", "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = "An error occurred while updating.";
                        if (xhr.responseJSON?.errors) {
                            errorMessage = Object.values(xhr.responseJSON.errors).join("<br>");
                        }
                        toastr.error(errorMessage, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                });
            });
  
            $('.search').on('keyup', function() {
                viewForm.ajax.reload();
            });
        });
    </script>
@endsection
