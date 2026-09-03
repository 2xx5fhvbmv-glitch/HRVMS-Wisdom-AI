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
                            <span>Announcement</span>
                            <h1>{{$page_title}}</h1>
                        </div>
                    </div>
                </div>
            </div>
            <form id="editAnnouncement">
                <div class="card">
                    <div>
                        <div class="row g-md-4 g-3 mb-md-4 mb-3">
                            <div class="col-lg-4 col-sm-6">
                                <label for="announcement_title" class="form-label">ANNOUNCEMENT TITLE</label>
                                <select class="form-select dd-native-select" id="announcement_title" name="announcement_title"
                                    required data-parsley-required-message="Please select a title" data-parsley-errors-container="#category-error">
                                    <option value="">Select Title</option>
                                    @if($categories)
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ $category->id == $announcement->title ? 'selected' : '' }}>{{ $category->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                @php $selectedCategory = $categories ? collect($categories)->firstWhere('id', $announcement->title) : null; @endphp
                                <div class="dd" data-target="#announcement_title">
                                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                        <span class="dd-lbl">{{ $selectedCategory ? $selectedCategory->name : 'Select Title' }}</span>
                                        <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                    </button>
                                    <div class="dd-panel" role="listbox" aria-label="Title">
                                        <div class="dd-scroll">
                                            <div class="dd-item{{ $selectedCategory ? '' : ' active' }}" role="option" data-value=""><span class="dd-nm">Select Title</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @if($categories)
                                                @foreach($categories as $category)
                                                    <div class="dd-item{{ $category->id == $announcement->title ? ' active' : '' }}" role="option" data-value="{{ $category->id }}"><span class="dd-nm">{{ $category->name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div id="category-error"></div>
                            </div>

                            <div class="col-lg-4 col-sm-6">
                                <label for="employee_name" class="form-label">EMPLOYEE NAME</label>
                                <select class="form-select dd-native-select" id="employee_name" name="employee_name"
                                    onchange="getEmpDetails(this.value)" required
                                    data-parsley-required-message="Please select an employee" data-parsley-errors-container="#emp-error">
                                    <option value="">Select Employee</option>
                                    @if($employees)
                                        @foreach($employees as $employee)
                                            <option value="{{ $employee->id }}" {{ $employee->id == $announcement->employee_id ? 'selected' : '' }}>
                                                {{ $employee->Emp_id }} - {{ $employee->resortAdmin->full_name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                @php $selectedEmployee = $employees ? collect($employees)->firstWhere('id', $announcement->employee_id) : null; @endphp
                                <div class="dd" data-target="#employee_name">
                                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                        <span class="dd-lbl">{{ $selectedEmployee ? $selectedEmployee->Emp_id.' - '.$selectedEmployee->resortAdmin->full_name : 'Select Employee' }}</span>
                                        <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                    </button>
                                    <div class="dd-panel" role="listbox" aria-label="Employee">
                                        <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find an employee…"></div>
                                        <div class="dd-scroll">
                                            <div class="dd-item{{ $selectedEmployee ? '' : ' active' }}" role="option" data-value=""><span class="dd-nm">Select Employee</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @if($employees)
                                                @foreach($employees as $employee)
                                                    <div class="dd-item{{ $employee->id == $announcement->employee_id ? ' active' : '' }}" role="option" data-value="{{ $employee->id }}"><span class="dd-nm">{{ $employee->Emp_id }} - {{ $employee->resortAdmin->full_name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div id="emp-error"></div>
                            </div>

                            <div class="col">
                                <div class="d-flex align-items-center" id="empDetails"></div>
                            </div>

                            <div class="col-12">
                                <label for="congratulatory_message" class="form-label">CONGRATULATORY MESSAGE</label>
                                <textarea class="form-control" id="congratulatory_message" name="congratulatory_message"
                                    placeholder="Congratulatory Message" rows="4" required
                                    data-parsley-required-message="Please enter congratulatory message">{{$announcement->message}}</textarea>
                            </div>
                        </div>

                        <div class="card-footer">
                            <div class="row align-items-center g-2">
                                <div class="col-auto">
                                    <button type="button" class="a-link" data-action-type="Draft" onclick="submitAnnouncement(this)">Save As Draft</button>
                                </div>
                                <div class="col-auto ms-auto">
                                    <button type="button" class="btn btn-themeSkyblue btn-sm" data-action-type="Scheduled" onclick="submitAnnouncement(this)">Schedule</button>
                                </div>
                                <div class="col-auto">
                                    <button type="button" class="btn btn-themeBlue btn-sm" data-action-type="Published" onclick="submitAnnouncement(this)">Publish Now</button>
                                </div>
                            </div>

                            <input type="hidden" id="action_type" name="action_type">
                            <input type="hidden" id="published_date" name="published_date">
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Schedule Modal -->
    <div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scheduleModalLabel">Schedule Announcement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label for="scheduled_date" class="form-label">Select Date</label>
                <input type="text" id="scheduled_date" class="form-control" min="{{ date('Y-m-d') }}">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn eb-btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn eb-btn-primary btn-sm" onclick="confirmScheduled()">Confirm</button>
            </div>
            </div>
        </div>
    </div>

@include('resorts._emotional_buttons_v2_styles')
@endsection

@section('import-css')
@include('resorts._dropdown_styles')
@endsection

@section('import-scripts')
<script>
    let form;

    $(document).ready(function () {
        flatpickr('#scheduled_date', {
            dateFormat: 'm/d/Y',
            allowInput: true,
            appendTo: document.body
        });
        form = $("#editAnnouncement").parsley();
        const selectedEmployeeId = $('#employee_name').val();
        if (selectedEmployeeId) {
            getEmpDetails(selectedEmployeeId);
        }

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
                } else {
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

    function submitAnnouncement(button) {
        const actionType = button.getAttribute('data-action-type');
        document.getElementById('action_type').value = actionType;

        if (!form.isValid()) {
            form.validate();
            return false;
        }

        if (actionType === 'Scheduled') {
            pendingActionType = actionType;
            const scheduleModal = new bootstrap.Modal(document.getElementById('scheduleModal'));
            scheduleModal.show();
            return;
        }

        document.getElementById('published_date').value = '';
        submitForm(); // call common AJAX
    }

    function confirmScheduled() {
        const date = document.getElementById('scheduled_date').value;
        if (!date) {
            toastr.error("Please select a date.", "Error", {
                positionClass: 'toast-bottom-right'
            });
            return;
        }

        document.getElementById('published_date').value = date;
        bootstrap.Modal.getInstance(document.getElementById('scheduleModal')).hide();
        submitForm(); // call common AJAX
    }

    function submitForm() {
        const formData = $("#editAnnouncement").serialize();

        $.ajax({
            url: "{{ route('people.announcements.update', base64_encode($announcement->id)) }}", // <-- update route
            type: "POST",
            data: formData,
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                    setTimeout(function() {
                        window.location.href = response.redirect_url;
                    }, 2000);
                } else {
                    toastr.error("Something went wrong.", "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            },
            error: function () {
                toastr.error("Error occurred. Please try again.", "Error", {
                    positionClass: 'toast-bottom-right'
                });
            }
        });
    }

</script>
@include('resorts._dropdown_script')
@endsection