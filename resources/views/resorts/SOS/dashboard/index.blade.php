
@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

{{-- @if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
@endif --}}

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>SOS</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>
        <div class="card card-sosHistory">
            <div class="card-header">
                <div class="row g-md-3 g-2 align-items-center">
                    <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                        <div class="input-group">
                            <input type="search" class="form-control " id="searchInput" placeholder="Search" />
                            <i class="fa-solid fa-search"></i>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                        <input type="text" class="form-control datepicker" name="dateFilter" id="dateFilter">
                    </div>
                    <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                        <select class="form-select select2t-none" id="statusFilter">
                            <option value="" selected>Select Status</option>
                            <option value="Completed">Completed</option>
                            <option value="Pending">Pending</option>
                            <option value="Drilled">Drilled</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="col-auto ms-auto">
                        <div class="d-flex align-items-center">
                            <label for="flexSwitchCheckDefault" class="form-label mb-0 me-3">Active Data</label>
                            <div class="form-check form-switch form-switchTheme">
                                <input class="form-check-input" type="checkbox" role="switch"
                                    id="flexSwitchCheckDefault">
                                <label class="form-check-label" for="flexSwitchCheckDefault">Archive Data</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- data-Table  -->
            <table id="SOSHistoryTable" class="table data-Table  table-sosHistory w-100">
                <thead>
                    <tr>
                        <th>SOS Type</th>
                        <th>Initiated By</th>
                        <th>Location</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- modal -->
<div class="modal fade" id="drillSOSmodal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small modal-drillSOS ">
        <div class="modal-content">
            <div class="modal-header border-0">
                <!-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> -->
            </div>
            <div class="modal-body">
                <h4>Is This A Drill Or Real?</h4>
                <div>
                    <a href="JavaScript:void(0);" class="btn btn-themeBlue btn-sm me-md-4 me-2 mark-sos-status" data-type="Drilled">Drill SOS</a>
                    <a href="JavaScript:void(0);" class="btn btn-themeGray btn-sm mark-sos-status" data-type="Real">Real SOS</a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('import-css')
@endsection

@section('import-scripts')

@if($hasPendingSOS)
    <script>
        window.onload = function() {
            var myModal = new bootstrap.Modal(document.getElementById('drillSOSmodal'), {
                keyboard: false,  // disables closing with ESC
                backdrop: 'static' // optional: also prevents clicking outside to close
            });
            myModal.show();
        }
    </script>
@endif

<script type="text/javascript">
    $(document).ready(function () {

        $('#statusFilter').select2({
            minimumResultsForSearch: -1,
            width: '100%'
        });

        $('.datepicker').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,      // Close the picker after selection
            todayHighlight: true  // Highlight today's date
        });

        // flatpickr("#dateFilter", {
        //     dateFormat: "d/m/Y", // Match your current format
        //     allowInput: true
        // });

        getSOSHistory();
        // Trigger search on input change
        $('#searchInput,#dateFilter,#statusFilter').on('keyup change', function () {
            getSOSHistory(); // Reload emergency types
        });

        $('#flexSwitchCheckDefault').on('change', function () {
            getSOSHistory(); // reload table on toggle
        });

        function getSOSHistory() {
            if ($.fn.DataTable.isDataTable("#SOSHistoryTable")) {
                $("#SOSHistoryTable").DataTable().destroy();
            }

            var EmergencyTypes = $('#SOSHistoryTable').DataTable({
                "searching": false,
                "bLengthChange": false,
                "bFilter": true,
                "bInfo": true,
                "bAutoWidth": false,
                "scrollX": true,
                "iDisplayLength": 6,
                order: [[6, 'desc']], 
                processing: true,
                serverSide: true,
                
                ajax: {
                    url: '{{ route("sos.dashboard.index") }}',
                    type: "GET",    
                    data: function (d) {
                        d.searchTerm = $('#searchInput').val();
                        d.status = $('#statusFilter').val();
                        let selectedDate = $('.datepicker').val();

                        if (selectedDate) {
                            let parts = selectedDate.split('/');
                            d.date = `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
                        }
                         else {
                            d.date = '';
                        }
                        
                        d.show_archived = $('#flexSwitchCheckDefault').is(':checked');
                    }
                },
                columns: [
                    { data: 'sos_type', name: 'sos_type', className: 'text-nowrap' },
                    { data: 'initiated_by', name: 'initiated_by', className: 'text-nowrap' },
                    { data: 'location', name: 'location', className: 'text-nowrap' },
                    { data: 'date', name: 'date', className: 'text-nowrap' },
                    { data: 'status', name: 'status', className: 'text-nowrap' },
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

        $(document).on('click', '.action-delete', function(e) {

            e.preventDefault();
            var url = $(this).data('url');

            Swal.fire({
                title: 'Sure want to archive?',
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
                        url: url,
                        dataType: "json",
                    }).done(function(result) {
                        if (result.success) {
                            
                            toastr.success(result.msg, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $('#SOSHistoryTable').DataTable().ajax.reload();

                        } else {
                            toastr.error(result.msg, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    }).fail(function(jqXHR) {
                        toastr.error(jqXHR.responseJSON?.msg || "An unexpected error occurred.", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    });
                }
            });

        
        });

        $(document).on('click', '.mark-sos-status', function (e) {
            e.preventDefault();
            const type = $(this).data('type');

            $.ajax({
                url: '{{ route("sos.emergency.update.status") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    type: type
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.msg);
                        $('#drillSOSmodal').modal('hide');
                        $('#SOSHistoryTable').DataTable().ajax.reload();
                    } else {
                        toastr.error(response.msg);
                    }
                },
                error: function (xhr) {
                    toastr.error(xhr.responseJSON?.msg || "Unexpected error occurred");
                }
            });
        });

    });

</script>

@endsection
