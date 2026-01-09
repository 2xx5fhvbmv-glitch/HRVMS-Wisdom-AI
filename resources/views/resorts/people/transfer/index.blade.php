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
                </div>
            </div>
            <div class="card card-transferRequestPeopleEmp">
                <form id="transferInitiate" class="@if(App\Helpers\Common::checkRouteWisePermission('people.transfer.initiate',config('settings.resort_permissions.create')) == false) d-none @endif" data-parsley-validate>
                    @csrf
                    <div class="card-header">
                        <div class="row g-md-4 g-3">
                            <div class="col-md-4 col-sm-6">
                                <label for="select_employee" class="form-label">SELECT EMPLOYEE <span class="red-mark">*</span></label>
                                <select class="form-select select2t-none" id="employee_name" name="employee_name"
                                    onchange="getEmpDetails(this.value)" required 
                                    data-parsley-required-message="Please select an employee" data-parsley-errors-container="#emp-error">
                                    <option value="">Select Employee</option>
                                    @if($employees)
                                        @foreach($employees as $employee)
                                            <option value="{{ $employee->id }}">
                                                {{ $employee->Emp_id }} - {{ $employee->resortAdmin->full_name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                <div id="emp-error"></div>
                            </div>
                            <div class="col">
                                <div class="d-flex align-items-center" id="empDetails"></div>
                            </div>
                            <!-- <div class="col-auto align-self-end">
                                <a href="#" class="btn btn-themeSkyblue btn-sm">
                                    Notify Employee
                                </a>
                            </div> -->
                        </div>
                    </div>
                    <div class="row g-md-4 g-3 mb-md-4 mb-3">
                        <div class="col-md-6 col-sm-6">
                            <label for="current_dep" class="form-label">CURRENT DEPARTMENT/PROPERTY <span class="red-mark">*</span></label>
                            <input type="text" name="current_dep" id="current_dep" class="form-control" readonly/>
                            <input type="hidden" name="current_dep_id" id="current_dep_id" class="form-control" readonly/>
                        </div>
                        <div class="col-md-6 col-sm-6">
                            <label for="target_dep" class="form-label">TARGET DEPARTMENT/PROPERTY <span class="red-mark">*</span></label>
                            <select class="form-select select2t-none" name="target_dep" id="target_dep" aria-label="Default select example" required 
                            data-parsley-required-message="Please select target department" data-parsley-errors-container="#dept-error">
                                <option value="">Select Department</option>
                                @if($departments)
                                    @foreach($departments as $dept)
                                        <option value="{{$dept->id}}">{{$dept->name}}</option>
                                    @endforeach
                                @endif
                            </select>
                            <div id="dept-error"></div>
                        </div>
                        <div class="col-md-6 col-sm-6">
                            <label for="current_pos" class="form-label">CURRENT POSITION <span class="red-mark">*</span></label>
                            <input type="text" name="current_pos" id="current_pos" class="form-control" readonly/>
                            <input type="hidden" name="current_pos_id" id="current_pos_id" class="form-control" readonly/>
                        </div>
                        <div class="col-md-6 col-sm-6">
                            <label for="target_pos" class="form-label">TARGET POSITION <span class="red-mark">*</span></label>
                            <select class="form-select select2t-none" name="target_pos" id="target_pos" aria-label="Default select example" required 
                            data-parsley-required-message="Please select target position" data-parsley-errors-container="#position-error">
                                <option value="">Select Position</option>
                            </select>
                            <div id="position-error"></div>
                        </div>
                        <div class="col-md-6 col-sm-6">
                            <label for="reason_transfer" class="form-label">REASON FOR TRANSFER <span class="red-mark">*</span></label>
                            <input type="text" id="reason_transfer" name="reason_transfer" class="form-control" placeholder="Reason For Transfer" required 
                            data-parsley-required-message="Please enter reason for transfer">
                        </div>
                        <div class="col-md-6 col-sm-6">
                            <label for="effective_date" class="form-label">EFFECTIVE DATE OF TRANSFER <span class="red-mark">*</span></label>
                            <input type="text" id="effective_date" name="effective_date" class="form-control datepicker" placeholder="Select Date" required 
                            data-parsley-required-message="Please select effective date">
                        </div>
                        <div class="col-md-6 col-sm-6">
                            <label for="transfer_status" class="form-label">TRANSFER STATUS <span class="red-mark">*</span></label>
                            <select class="form-select select2t-none" id="transfer_status" name="transfer_status"
                                aria-label="Default select example" required 
                                data-parsley-required-message="Please select transfer status" data-parsley-errors-container="#status-error">
                                <option value="">Transfer Status</option>
                                <option value="Permanent">Permanent</option>
                                <option value="Temporary">Temporary</option>
                            </select>
                            <div id="status-error"></div>
                        </div>
                        <div class="col-md-6 col-sm-6">
                            <label for="additional_notes" class="form-label">ADDITIONAL NOTES</label>
                            <input type="text" id="additional_notes" name="additional_notes" class="form-control " placeholder="Additional Notes">
                        </div>
                        <div class="col-md-6 col-sm-6">
                            <label for="reporting_manager" class="form-label">Reporting Manager <span class="red-mark">*</span></label>
                            <select name="reporting_manager" id="reporting_manager" class="form-select select2t-none" required 
                            data-parsley-required-message="Please select reporting manager" data-parsley-errors-container="#reporting-manager-error">
                                <option value="">Reporting manager</option>
                            </select>
                            <div id="reporting-manager-error"></div>
                        </div>
                    </div>
                    <div class="card-footer mb-md-4 mb-3 text-end">
                        <button type="button" class="btn btn-themeSkyblue btn-sm" id="submitTransfer">Submit Transfer Request</button>
                    </div>
                </form>
                <div class="bg-themeGrayLight">
                    <div class="card-title">
                        <h3>Past Transfer History</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-lable mb-1" id="transfer-history">
                            <thead>
                                <tr>
                                    <th>Effective Date</th>
                                    <th>Current Department</th>
                                    <th>New Department</th>
                                    <th>Transfer Type</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                
                            </tbody>
                        </table>
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
    $(document).ready(function () {
        let form1 = $("#transferInitiate").parsley(); // Initialize Parsley

        $('.datepicker').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            todayHighlight: true,
            startDate: new Date() // Disables all past dates
        });
        $('.select2t-none').select2();

        $('#submitTransfer').on('click', function (e) {
            e.preventDefault();
        
            if (!form1.isValid()) {
                form1.validate();
                return false;
            }
            const targetDep = $('#target_dep').val();
            const targetPos = $('#target_pos').val();

            if (!targetDep || !targetPos) {
                toastr.error("Please select both target department and target position.","Error", {
                    positionClass: 'toast-bottom-right'
                });
                return;
            }

            // First: Check if target department has budget
            $.ajax({
                url: '{{ route("transfer.checkBudget") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    target_dep: targetDep,
                    target_pos: targetPos
                },
                success: function (response) {
                    if (response.success) {
                        // Budget available — proceed with submission
                        let formData = new FormData($('#transferInitiate')[0]);

                        $.ajax({
                            url: '{{ route("people.transfer.store") }}',
                            method: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            success: function (response) {
                                if (response.success) {
                                    toastr.success(response.message, "Success", {
                                        positionClass: 'toast-bottom-right'
                                    });
                                    setTimeout(() => {
                                        window.location.href = response.redirect_url;
                                    }, 2000);
                                    $('#transferInitiate')[0].reset();
                                    $('#employee_name').trigger('change');
                                }
                            },
                            error: function (xhr) {
                                let errors = xhr.responseJSON.errors;
                                let errorMsg = "Error:\n";
                                $.each(errors, function (key, value) {
                                    errorMsg += `${value}\n`;
                                });
                                toastr.error(errorMsg, "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        });
                    } else {
                        // Budget check failed
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function () {
                    toastr.error("Error checking department budget.","Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });

        $('#target_dep').on('change', function () { 
            var departmentId = $(this).val();
            if (departmentId) {
                $.ajax({
                    url: '{{ route("resort.get.position") }}',
                    type: "post",
                    data: {
                        deptId: departmentId,
                        _token: '{{ csrf_token() }}' // Include CSRF token for POST
                    },
                    dataType: 'json',
                    success: function (data) {
                        let string = '<option value="">Select Position</option>'; // ✅ initialize
                        if (data.success === true) {
                            $.each(data.data, function (key, value) {
                                string += '<option value="' + value.id + '">' + value.position_title + '</option>';
                            });
                        }
                        $("#target_pos").html(string); // ✅ update dropdown
                    }
                });
            } else {
                $('#target_pos').html('<option value="">Select Position</option>');
            }
        });

        $('#target_pos').on('change', function () { 
            var positionId = $(this).val();
            var departmentId = $('#target_dep').val();
            if (positionId) {
                $.ajax({
                    url: '{{ route("resort.get.reporting_managers") }}',
                    type: "post",
                    data: {
                        positionId: positionId,
                        departmentId:departmentId,
                        _token: '{{ csrf_token() }}' // Include CSRF token for POST
                    },
                    dataType: 'json',
                    success: function (data) {
                        console.log(data);
                        let string = '<option value="">Select Reporting Manager</option>'; // ✅ initialize
                        if (data.success === true) {
                            $.each(data.data, function (key, value) {
                                string += '<option value="' + value.id + '">' + value.first_name + ' ' + value.last_name +'</option>';
                            });
                        }
                        $("#reporting_manager").html(string); // ✅ update dropdown
                    }
                });
            } else {
                $('#target_pos').html('<option value="">Select Position</option>');
            }
        });
    });

    function getEmpDetails(empId) {
        if (!empId) return;
        $.ajax({
            url: "{{ route('employee.get.details') }}",
            type: "GET",
            data: { employee_id: empId },
            success: function (response) {
                if (response.success) {
                    $('#empDetails').empty();
                    const html = `
                        <div class="position-relative me-lg-4 me-md-3 me-2">
                            <div class="img-circle userImg-block">
                                <img src="${response.data.profile_picture}" alt="user">
                            </div>
                        </div>
                        <div>
                            <h4 class="fw-600">${response.data.full_name} 
                                <span class="badge badge-themeNew">#${response.data.emp_id}</span>
                            </h4>
                            <p>${response.data.department} - ${response.data.position}</p>
                        </div>`;
                    $('#empDetails').append(html);
                    $('#current_dep').val(response.data.department);
                    $('#current_dep_id').val(response.data.dept_id);
                    $('#current_pos').val(response.data.position);
                    $('#current_pos_id').val(response.data.pos_id);

                    $.ajax({
                        url: '{{ route("employee.transfer.history") }}',
                        type: 'POST',
                        data: {
                            employee_id: empId,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            var html = '';
                            if (response.success && response.data.length > 0) {
                                $.each(response.data, function (i, row) {
                                    html += '<tr>' +
                                                '<td>' + row.effective_date + '</td>' +
                                                '<td>' + row.current_department + '</td>' +
                                                '<td>' + row.target_department + '</td>' +
                                                '<td>' + row.transfer_type + '</td>' +
                                                '<td><span class="badge badge-' + (row.status == 'Approved' ? 'themeSuccess' : 'themeWarning') + '">' + row.status + '</span></td>' +
                                            '</tr>';
                                });
                            } else {
                                html = '<tr><td colspan="5">No transfer history found.</td></tr>';
                            }
                            $('#transfer-history tbody').html(html);
                        }
                    });
                } else {
                    // alert("Employee details not found!");
                    toastr.error("Employee details not found!", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            },
            error: function () {
                toastr.error("Error fetching employee details.", "Error", {
                    positionClass: 'toast-bottom-right'
                });
            }
        });
    }
</script>
@endsection