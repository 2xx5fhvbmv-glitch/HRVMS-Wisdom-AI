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
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>People</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <div class="col-auto ms-auto">
                        <a href="{{route('people.transfer.history')}}" class="btn btn-theme">View Transfer History</a>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                            <div class="input-group">
                                <input type="search" class="form-control" id="searchInput" placeholder="Search" />
                                <i class="fa-solid fa-search"></i>                           
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select select2t-none" id="deptFilter">
                                <option value="">By Department</option>
                                @if($departments)
                                    @foreach($departments as $dept)
                                        <option value="{{$dept->id}}">{{$dept->name}}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select select2t-none" id="positionFilter">
                                <option value="">By Position</option>
                                @if($positions)
                                    @foreach($positions as $pos)
                                        <option value="{{$pos->id}}">{{$pos->position_title}}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select select2t-none" id="statusFilter">
                                <option value="">By Probation Status</option>
                                <option value="Pending">Pending</option>
                                <option value="Approved">Approved</option>
                                <option value="Rejected">Rejected</option>
                                <option value="On Hold">On Hold</option>
                            </select>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <input type="text" name="dateFilter" id="dateFilter" class="form-control datepicker"/>
                        </div>
                    </div>
                </div>
                <div class="table-responsive mb-3">
                    <table class="table table-lable table-transferHistoryPeopleEmp mb-1" id="transferHistoryTable">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Employee Name</th>
                                <th>Current Position</th>
                                <th>Target Position</th>
                                <th>Current Department</th>
                                <th>Target Department</th>
                                <th>Transfer date</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
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
    const transferApprovalUrl = @json(route('people.transfer.handle-approval', ['id' => '__ID__', 'action' => '__ACTION__']));

    $(document).ready(function () {
        $('.select2t-none').select2();

        $(".datepicker").datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            todayHighlight: true
        });

        getTransferData();

        $('#searchInput, #dateFilter, #statusFilter, #deptFilter, #positionFilter').on('keyup change', function () {
            getTransferData();
        });

        $('#resetFilters').on('click', function () {
            $('#searchInput, #dateFilter, #statusFilter, #deptFilter, #positionFilter').val('').trigger('change');
            getTransferData();
        });

        $(document).on('click', '.transfer-action', function () {
            const transferId = $(this).data('id');
            const action = $(this).data('action');
            const finalUrl = transferApprovalUrl.replace('__ID__', transferId).replace('__ACTION__', action);

            Swal.fire({
                title: `Are you sure?`,
                html: `
                    <p>You want to <b>${action}</b> this transfer?</p>
                    <textarea id="swal-reason" class="swal2-textarea" placeholder="Enter reason or comment"></textarea>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: `Yes, ${action} it!`,
                preConfirm: () => {
                    const reason = document.getElementById('swal-reason').value.trim();

                    if ((action === 'Rejected' || action === 'On Hold') && reason === '') {
                        Swal.showValidationMessage('Comment is required for Rejected or On Hold actions.');
                        return false;
                    }

                    return { reason };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: finalUrl,
                        method: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            reason: result.value.reason
                        },
                        success: function (res) {
                            toastr.success(res.message || 'Action completed successfully.', "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $('#transferHistoryTable').DataTable().ajax.reload(null, false);
                        },
                        error: function (xhr) {
                            let msg = xhr.responseJSON?.message || 'Action failed';
                            toastr.error(msg, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    });
                }
            });

        });
    });

    function getTransferData() {
        if ($.fn.dataTable.isDataTable('#transferHistoryTable')) {
            $('#transferHistoryTable').DataTable().destroy();
        }

        $('#transferHistoryTable').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            lengthChange: false,
            info: true,
            autoWidth: false,
            scrollX: true,
            pageLength: 10,
            order:[[10, 'desc']],
            ajax: {
                url: '{{ route("people.transfer.list") }}',
                data: function (d) {
                    d.department_id = $('#deptFilter').val();
                    d.position_id = $('#positionFilter').val();
                    d.searchTerm = $('#searchInput').val();
                    d.status = $('#statusFilter').val();

                    let selectedDate = $('#dateFilter').val();
                    if (selectedDate) {
                        let parts = selectedDate.split('/');
                        d.date = `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
                    } else {
                        d.date = '';
                    }
                }
            },
            columns: [
                { data: 'employee_id', name: 'employee_id' },
                { data: 'employee_name', name: 'employee_name' },
                { data: 'current_position', name: 'current_position' },
                { data: 'target_position', name: 'target_position' },
                { data: 'current_department', name: 'current_department' },
                { data: 'target_department', name: 'target_department' },
                { data: 'effective_date', name: 'effective_date' },
                { data: 'reason_for_transfer', name: 'reason_for_transfer' },
                { data: 'status', name: 'status' },
                { data: 'actions', orderable: false, searchable: false },
                {data:'created_at',visible:false,searchable:false},
            ]
        });
    }
</script>
@endsection
