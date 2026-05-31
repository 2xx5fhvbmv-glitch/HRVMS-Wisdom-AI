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
                    <!-- <div class="col-auto  ms-auto"><a class="btn btn-theme" href="#">Add New Employee</a></div> -->
                </div>
            </div>
            <div class="card">
                <div class="row g-xxl-4 g-3">
                    <div class="col-md-auto">
                        <div class="empDetailPeopleEmp-sidebar">
                            <div class="dropdown table-dropdown ">
                                <button class="btn btn-secondary dropdown-toggle dotsV-link" type="button"
                                    id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton1">
                                    {{-- "View Profile" hidden here — this IS the
                                         profile page, so the link only round-tripped
                                         to itself. --}}
                                    <!-- <li><a class="dropdown-item" href="#">Edit Employee</a></li> -->
                                    <li><a class="dropdown-item add-to-team-btn" href="#" data-emp-id="{{$employee->id}}">
                                    Add to Team / Assign Role</a></li>
                                    <li><a class="dropdown-item change-status" href="#"
                                        data-id="{{ $employee->id }}"
                                        data-status="{{ $employee->status }}">
                                        Change Status
                                    </a></li>
                                </ul>
                            </div>
                            <div class="img-circle">
                                <img src="{{Common::getResortUserPicture($employee->Admin_Parent_id ?? null)}}" alt="image">
                            </div>
                            <h6>{{$employee->resortAdmin->full_name}}</h6>
                            <span class="badge badge-white">#{{$employee->Emp_id}}</span>
                            <p>{{$employee->position->position_title}}</p>
                            <div class="position">{{$employee->department->name}}</div>
                            {{-- Status badge.
                                 Previously a disabled <select>, which the
                                 browser rendered with a dropdown chevron even
                                 though it wasn't interactive — the real status
                                 change goes through the "Change Status" item
                                 in the kebab menu above. Swapped for a plain
                                 themed badge so the chevron is gone. Colour
                                 is mapped to status via the existing
                                 badge-theme* classes used elsewhere on this
                                 page (kept consistent with the rest of the
                                 app rather than introducing new CSS). --}}
                            @php
                                $_statusBadge = match (trim((string) $employee->status)) {
                                    'Active'      => 'badge-themeSuccess',
                                    'Onboarding'  => 'badge-themeWarning',
                                    'On Leave'    => 'badge-themeSkyblue',
                                    'Suspended'   => 'badge-themeSkyblue',
                                    'Inactive'    => 'badge-themeDanger',
                                    'Terminated'  => 'badge-themeDanger',
                                    'Resigned'    => 'badge-themeDanger',
                                    default       => 'badge-themeWarning',
                                };
                            @endphp
                            <div class="mb-2">
                                <span id="statusFilter"
                                      class="badge {{ $_statusBadge }} statusBadge"
                                      data-status="{{ $employee->status }}"
                                      style="font-size:13px; font-weight:500; padding:6px 14px; border-radius:999px;">
                                    {{ $employee->status ?: 'Status' }}
                                </span>
                            </div>

                            {{-- Activate Employee — shown only while the employee is
                                 still in the pre-joining 'Onboarding' state. HR uses
                                 this once onboarding is complete to set the joining
                                 date and flip the employee to Active (which then
                                 surfaces them in payroll / attendance). --}}
                            @if($employee->status == 'Onboarding')
                                <div class="alert alert-warning py-2 px-3 mb-2" style="font-size:13px;">
                                    This employee is still <strong>onboarding</strong> and has not joined yet.
                                </div>
                                <button type="button" class="btn btn-theme btn-sm w-100 mb-2" id="activateEmployeeBtn"
                                    data-id="{{ $employee->id }}">
                                    <i class="fa-solid fa-user-check me-1"></i> Activate Employee
                                </button>
                            @endif
                            <table class="table table-lable">
                                <tbody>
                                    <tr id="summary-location-row">
                                        <td>Location:</td>
                                        <td>
                                            @php
                                                $loc = $employee->location
                                                    ?: ($employee->nationality === 'Maldivian' ? 'Malé' : ($employee->nationality ? 'Resorts' : ''));
                                            @endphp
                                            <span class="location-view">
                                                <span class="location-text">{{ $loc ?: '-' }}</span>
                                                <a href="javascript:void(0);" class="location-edit-btn ms-1" title="Edit Location" style="cursor:pointer;">
                                                    <i class="fa-solid fa-pen text-primary" style="font-size:12px;"></i>
                                                </a>
                                            </span>
                                            <span class="location-edit d-none">
                                                <select class="form-select form-select-sm location-select" style="display:inline-block;width:auto;">
                                                    <option value="">-</option>
                                                    <option value="Malé" {{ $loc === 'Malé' ? 'selected' : '' }}>Malé</option>
                                                    <option value="Resorts" {{ $loc === 'Resorts' ? 'selected' : '' }}>Resorts</option>
                                                </select>
                                                <a href="javascript:void(0);" class="location-save-btn ms-1" title="Save"><i class="fa-solid fa-check text-success"></i></a>
                                                <a href="javascript:void(0);" class="location-cancel-btn ms-1" title="Cancel"><i class="fa-solid fa-xmark text-danger"></i></a>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Leaves Remaining:</td>
                                        <td>{{$remianing_leaves ?? 0}}</td>
                                    </tr>
                                    <tr>
                                        <td>Manager:</td>
                                        <td>
                                            <div class="tableUser-block">
                                                <div class="img-circle">
                                                    <img src="{{Common::getResortUserPicture($employee->reportingTo->Admin_Parent_id ?? null)}}" alt="uset">
                                                </div>
                                                <span class="userApplicants-btn">
                                                    {{@$employee->reportingTo->resortAdmin->first_name}} {{@$employee->reportingTo->resortAdmin->last_name}}
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Teams</td>
                                        <td>{{ $employee->sosTeams->pluck('name')->implode(', ') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            {{-- Per-employee sidebar tabs. Every link must carry the
                                 employee identifier so the destination page can scope
                                 to this employee. Existing modules (Probation,
                                 Promotion, Xpat, T&A, Accommodation) already accept a
                                 base64-encoded employee id. Leave / File Management /
                                 Announcement / Clearance / Requests were previously
                                 dead (`javascript:void(0)`) and are now wired to the
                                 closest matching list page, with the employee id in
                                 the query string. Destinations that don't yet honor
                                 the filter will surface the global list — wire the
                                 server-side filter on those pages in follow-up
                                 work. --}}
                            <ul>
                                <!-- <li><a href="#">Workforce Planning</a></li> -->
                                <li><a href="{{route('people.probation.details',[base64_encode($employee->id)])}}">Probation</a></li>
                                {{-- Promotion route has no path placeholder; positional args become a
     keyless query string (?MjAy), so getHistory couldn't read it and
     the page fell back to showing every employee's promotions. Using
     ?employee_id=<id> (raw int, not base64) — that's the param name
     getHistory reads first (see PromotionController@getHistory). --}}
<li><a href="{{ route('people.promotion.history', ['employee_id' => $employee->id]) }}">Promotion</a></li>
                                <li><a href="{{route('resort.visa.XpactEmpDetails',[base64_encode($employee->id)])}}">Xpat</a></li>
                                <li><a href="{{ route('resort.timeandattendance.employee.details', [ base64_encode($employee->id)]); }}">Time & Attendance</a></li>
                                {{-- Leave: opens the per-employee leave history PAGE
                                     (not the PDF download). LeaveController::employeeLevePage
                                     resolves the employee's latest leave and 302-redirects to
                                     leave.details, which already renders the full
                                     leave-history table for that employee. --}}
                                <li><a href="{{ route('leave.employee.page', ['empID' => base64_encode($employee->id)]) }}">Leave</a></li>
                                <li><a href="{{ route('resort.accommodation.AccommodationEmployeeDetails', [base64_encode($employee->id)]) }}">Accommodation</a></li>
                                <!-- <li><a href="#">Payroll</a></li> -->
                                <!-- <li><a href="#">People Relation</a></li> -->
                                <!-- <li><a href="#">L&D</a></li> -->
                                <!-- <li><a href="#">Survey</a></li> -->
                                <!-- <li><a href="#">Incident</a></li> -->
                                {{-- File Management: lands on the employees-folder
                                     page. Each active employee already has a
                                     categorized folder named after their Emp_id
                                     (see Employee::created hook). Passing
                                     emp_code lets that page focus the matching
                                     folder once it consumes the filter. --}}
                                <li><a href="{{ route('Employees.Documents', ['emp_code' => $employee->Emp_id]) }}">File Management</a></li>
                                <li><a href="{{ route('people.announcements', ['empId' => base64_encode($employee->id)]) }}">Announcement</a></li>
                                <li><a href="{{ route('people.exit-clearance', ['empId' => base64_encode($employee->id)]) }}">Clearance</a></li>
                                <li><a href="{{ route('people.approvel.index', ['empId' => base64_encode($employee->id)]) }}">Requests</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col">
                        <div class="empDetailPeopleEmp-tab">
                            <ul class="nav nav-tabs" id="myTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="tab1" data-bs-toggle="tab"
                                        data-bs-target="#tabPane1" type="button" role="tab" aria-controls="tabPane1"
                                        aria-selected="true">Personal Details</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="#tab2" data-bs-toggle="tab" data-bs-target="#tabPane2"
                                        type="button" role="tab" aria-controls="tabPane2"
                                        aria-selected="false">Employment</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="tab3" data-bs-toggle="tab" data-bs-target="#tabPane3"
                                        type="button" role="tab" aria-controls="tabPane3"
                                        aria-selected="false">Education/Qualification</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="tab4" data-bs-toggle="tab" data-bs-target="#tabPane4"
                                        type="button" role="tab" aria-controls="tabPane4"
                                        aria-selected="true">Experience</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="tab5" data-bs-toggle="tab" data-bs-target="#tabPane5"
                                        type="button" role="tab" aria-controls="tabPane5"
                                        aria-selected="true">Expiry</button>
                                </li>
                                @if($employee->resortAdmin && !$employee->resortAdmin->password)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="tab6" data-bs-toggle="tab" data-bs-target="#tabPane6"
                                            type="button" role="tab" aria-controls="tabPane6"
                                            aria-selected="true">Send Credentials</button>
                                    </li>
                                @endif
                                <!-- <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="tab6" data-bs-toggle="tab" data-bs-target="#tabPane6"
                                        type="button" role="tab" aria-controls="tabPane6" aria-selected="true">Roles &
                                        Permissions</button>
                                </li> -->
                            </ul>
                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade show active" id="tabPane1" role="tabpanel"
                                    aria-labelledby="tab1" tabindex="0">
                                    <div class="bg-themeGrayLight mb-md-4 mb-3">
                                        <div class="card-title mb-0">
                                            <div class="row g-md-2 g-1 align-items-center">
                                                <div class="col">
                                                    <h3 class="text-nowrap">Personal Information</h3>
                                                </div>
                                                <div class="col-auto">
                                                    <a href="javascript:void(0);" class="btn-lg-icon icon-bg-blue toggle-edit-save">
                                                        <img src="{{ URL::asset('resorts_assets/images/edit.svg') }}" alt="Edit Icon" class="edit-icon">
                                                    </a>
                                                    <button type="submit" class="btn btn-themeBlue btn-sm d-none" id="btn-save">Submit</button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row g-xxl-4 g-md-3 g-2">
                                            <div class="col-lg-6">
                                                <div class="table-responsive">
                                                    <table class="table table-lable mb-1">
                                                        <tbody>
                                                            <tr>
                                                                <th>Title:</th>
                                                                <td>
                                                                    <span class="view-mode">{{$employee->title}}</span>
                                                                    <select name="title" class="form-select edit-mode d-none" required>
                                                                        <option value="">Select title</option>
                                                                        <option {{$employee->title == "Mr" ? "Selected" : ""}} value="Mr">Mr</option>
                                                                        <option {{$employee->title == "Miss" ? "Selected" : ""}} value="Miss">Miss</option>
                                                                        <option {{$employee->title == "Mrs" ? "Selected" : ""}} value="Mrs">Mrs</option>
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Name:</th>
                                                                <td>
                                                                    <span class="view-mode">{{$employee->resortAdmin->full_name}}</span>
                                                                    <input type="text" name="first_name" class="form-control edit-mode d-none" value="{{$employee->resortAdmin->first_name}}" required minlength="2" maxlength="50" pattern="^[A-Za-z\s\-']+$" placeholder="First Name">
                                                                    <input type="text" name="last_name" class="form-control edit-mode d-none" value="{{$employee->resortAdmin->last_name}}" required minlength="2" maxlength="50" pattern="^[A-Za-z\s\-']+$" placeholder="Last Name">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Gender:</th>
                                                                <td>
                                                                    <span class="view-mode">{{$employee->resortAdmin->gender}}</span>
                                                                    <select name="gender" class="form-select edit-mode d-none" required>
                                                                        <option value="">Select Gender</option>
                                                                        <option {{$employee->resortAdmin->gender == "male" ? "Selected" : ""}} value="male">Male</option>
                                                                        <option {{$employee->resortAdmin->gender == "female" ? "Selected" : ""}} value="female">Female</option>
                                                                        {{-- "Other" intentionally removed per HR policy:
                                                                             gender is collected only for legal/visa records
                                                                             where Male / Female are the only accepted values. --}}
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Date of Birth:</th>
                                                                <td>
                                                                   <span class="view-mode">
                                                                        @php
                                                                            try {
                                                                                $dobDate = \Carbon\Carbon::createFromFormat('Y-m-d', $employee->dob);
                                                                                echo $dobDate->format('d M Y') . ' (' . $dobDate->age . ' years old)';
                                                                            } catch (\Exception $e) {
                                                                                echo $employee->dob ?? 'Not available';
                                                                            }
                                                                        @endphp
                                                                    </span>
                                                                    @php
                                                                        try {
                                                                            $formattedDob = \Carbon\Carbon::createFromFormat('Y-m-d', $employee->dob)->format('d/m/Y');
                                                                        } catch (\Exception $e) {
                                                                            $formattedDob = $employee->dob ?? '';
                                                                        }
                                                                    @endphp
                                                                    <input type="text" name="dob" class="form-control edit-mode d-none datepicker"
                                                                    value="{{ $formattedDob }}"
                                                                    required
                                                                    pattern="^(0[1-9]|[12][0-9]|3[01])/(0[1-9]|1[012])/[0-9]{4}$"
                                                                    placeholder="DD/MM/YYYY">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Marital Status:</th>
                                                                <td>
                                                                    <span class="view-mode">{{$employee->marital_status}}</span>
                                                                    <select name="marital_status" class="form-select edit-mode d-none" required>
                                                                        <option value="">Select Marital Status</option>
                                                                        <option {{$employee->marital_status == "Single" ? "Selected" : ""}} value="Single">Single</option>
                                                                        <option {{$employee->marital_status == "Married" ? "Selected" : ""}} value="Married">Married</option>
                                                                        <option {{$employee->marital_status == "Divorced" ? "Selected" : ""}} value="Divorced">Divorced</option>
                                                                        <option {{$employee->marital_status == "Widowed" ? "Selected" : ""}} value="Widowed">Widowed</option>
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="table-responsive">
                                                    <table class="table table-lable mb-1">
                                                        <tbody>
                                                            <tr>
                                                                <th>Nationality:</th>
                                                                <td>
                                                                    <span class="view-mode">{{$employee->nationality}}</span>
                                                                    <select name="nationality" class="form-select edit-mode d-none" required>
                                                                        <option value="">Select Nationality</option>
                                                                        @if($nationality)
                                                                            @foreach($nationality as $val)
                                                                                <option {{$val == $employee->nationality ? "Selected" : ""}} value="{{$val}}">{{$val}}</option>
                                                                            @endforeach
                                                                        @endif
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Religion:</th>
                                                                <td>
                                                                    <span class="view-mode">{{ ($employee->religion === null || $employee->religion === '') ? "N/A" : ($employee->religion === '0' ? "Non-Muslim" : "Muslim") }}</span>
                                                                    <select name="religion" class="form-select edit-mode d-none" required>
                                                                        <option value="">Select Religion</option>
                                                                        <option {{$employee->religion == 1 ? "Selected" : ""}} value="1">Muslim</option>
                                                                        <option {{$employee->religion == 0 ? "Selected" : ""}} value="0">Non-Muslim</option>
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Blood Group:</th>
                                                                <td>
                                                                    <span class="view-mode">{{$employee->blood_group}}</span>
                                                                    <select name="blood_group" class="form-select edit-mode d-none" required>
                                                                        <option value="">Select Blood Group</option>
                                                                        <option {{$employee->blood_group == "A+" ? "Selected" : ""}} value="A+">A+</option>
                                                                        <option {{$employee->blood_group == "A-" ? "Selected" : ""}} value="A-">A-</option>
                                                                        <option {{$employee->blood_group == "B+" ? "Selected" : ""}} value="B+">B+</option>
                                                                        <option {{$employee->blood_group == "B-" ? "Selected" : ""}} value="B-">B-</option>
                                                                        <option {{$employee->blood_group == "AB+" ? "Selected" : ""}} value="AB+">AB+</option>
                                                                        <option {{$employee->blood_group == "AB-" ? "Selected" : ""}} value="AB-">AB-</option>
                                                                        <option {{$employee->blood_group == "O+" ? "Selected" : ""}} value="O+">O+</option>
                                                                        <option {{$employee->blood_group == "O-" ? "Selected" : ""}} value="O-">O-</option>
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Passport Number:</th>
                                                                <td>
                                                                    <span class="view-mode">{{$employee->passport_number ?? "N/A"}}</span>
                                                                    <input type="text" name="passport_number" class="form-control edit-mode d-none" value="{{$employee->passport_number}}" pattern="^[A-Za-z0-9]{5,20}$" maxlength="20" placeholder="Passport Number" required>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>NID:</th>
                                                                <td>
                                                                    <span class="view-mode">{{$employee->nid ?? "N/A"}}</span>
                                                                    <input type="text" name="nid" class="form-control edit-mode d-none" value="{{$employee->nid}}" pattern="^[A-Za-z0-9]{5,20}$" maxlength="20" placeholder="NID" >
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row g-xxl-4 g-3 mb-md-4 mb-3">
                                        <div class="col-lg-6">
                                            <div class="bg-themeGrayLight h-100">
                                                <div class="card-title mb-0">
                                                    <div class="row g-md-2 g-1 align-items-center">
                                                        <div class="col">
                                                            <h3 class="text-nowrap">Contact Information</h3>
                                                        </div>
                                                        <div class="col-auto">
                                                            <a href="javascript:void(0);" class="btn-lg-icon icon-bg-blue edit-contact-btn">
                                                                <img src="{{ URL::asset('resorts_assets/images/edit.svg')}}" alt="icon">
                                                            </a>
                                                            <button type="submit" class="btn btn-themeBlue btn-sm d-none" id="btn-contact-save">Submit</button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="table-responsive">
                                                    <table class="table table-lable mb-1">
                                                        <tbody>
                                                        <tr>
                                                            <th>Mobile Number:</th>
                                                            <td>
                                                                <span class="view-mode">{{ $employee->resortAdmin->personal_phone }}</span>
                                                                {{-- type="tel" surfaces the phone keyboard on mobile.
                                                                     Pattern allows + as first char (country code),
                                                                     digits, spaces, dashes, brackets. minlength/maxlength
                                                                     reflect real-world phone-number lengths including
                                                                     the +CC prefix. --}}
                                                                {{-- id="personal_phone_contact" disambiguates this input
                                                                     from the SECOND name="personal_phone" rendered
                                                                     under Employment Information below. Without a
                                                                     unique id, the contact save handler's
                                                                     $('[name="personal_phone"]').val() picked the
                                                                     first matched element (which is THIS one only if
                                                                     the user happened to be on the Personal tab) and
                                                                     silently dropped edits made in the other tab. --}}
                                                                <input type="tel" name="personal_phone" id="personal_phone_contact" class="form-control edit-mode d-none"
                                                                       value="{{ $employee->resortAdmin->personal_phone }}"
                                                                       required pattern="^\+?[0-9\-\s()]{7,25}$"
                                                                       minlength="7" maxlength="25"
                                                                       inputmode="tel" autocomplete="tel"
                                                                       placeholder="e.g. +960 9123456">
                                                                <small class="form-text text-muted edit-mode d-none">Include country code, e.g. +91 9098765432</small>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>Email Address:</th>
                                                            <td>
                                                                <span class="view-mode">{{ $employee->resortAdmin->email }}</span>
                                                                <input type="email" name="email" class="form-control edit-mode d-none"
                                                                       value="{{ $employee->resortAdmin->email }}"
                                                                       required maxlength="100"
                                                                       placeholder="Enter email address">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>Permanent Address:</th>
                                                            <td>
                                                                <span class="view-mode">
                                                                    {{$employee->resortAdmin->address_line_1}} {{$employee->resortAdmin->address_line_2}} {{$employee->resortAdmin->city}} {{$employee->resortAdmin->state}} {{$employee->resortAdmin->country}} - {{$employee->resortAdmin->zip}}
                                                                </span>
                                                                <input type="text" name="address_line_1" value="{{ $employee->resortAdmin->address_line_1 }}"
                                                                       class="form-control edit-mode d-none"
                                                                       required maxlength="100"
                                                                       placeholder="Address Line 1">
                                                                <input type="text" name="address_line_2" value="{{ $employee->resortAdmin->address_line_2 }}"
                                                                       class="form-control edit-mode d-none"
                                                                       maxlength="100"
                                                                       placeholder="Address Line 2">
                                                                <input type="text" name="city" value="{{ $employee->resortAdmin->city }}"
                                                                       class="form-control edit-mode d-none"
                                                                       required maxlength="50"
                                                                       placeholder="City">
                                                                <input type="text" name="state" value="{{ $employee->resortAdmin->state }}"
                                                                       class="form-control edit-mode d-none"
                                                                       maxlength="50"
                                                                       placeholder="State">
                                                                <input type="text" name="country" value="{{ $employee->resortAdmin->country }}"
                                                                       class="form-control edit-mode d-none"
                                                                       required maxlength="50"
                                                                       placeholder="Country">
                                                                <input type="text" name="zip" value="{{ $employee->resortAdmin->zip }}"
                                                                       class="form-control edit-mode d-none"
                                                                       required maxlength="20"
                                                                       pattern="^[A-Za-z0-9\- ]{3,20}$"
                                                                       placeholder="ZIP/Postal Code">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>Present Address:</th>
                                                            <td>
                                                                <span class="view-mode">{{$employee->present_address ?? "Not Available"}}</span>
                                                                <textarea class="form-control edit-mode d-none" name="present_address"
                                                                          maxlength="255"
                                                                          placeholder="Present Address"
                                                                          required>{{ $employee->present_address ?? "" }}</textarea>
                                                            </td>
                                                        </tr>
                                                        </tbody>
                                                    </table>

                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="bg-themeGrayLight h-100">
                                                <div class="card-title mb-0">
                                                    <div class="row g-md-2 g-1 align-items-center">
                                                        <div class="col">
                                                            <h3 class="text-nowrap">Emergency Contact Details</h3>
                                                        </div>
                                                        <div class="col-auto">
                                                            <a href="javascript:void(0);" class="btn-lg-icon icon-bg-blue edit-emergency-contact-btn">
                                                                <img src="{{ URL::asset('resorts_assets/images/edit.svg')}}" alt="icon">
                                                            </a>
                                                            <button type="submit" class="btn btn-themeBlue btn-sm d-none" id="btn-emergnecy-contact-save">Submit</button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="table-responsive">
                                                    <table class="table table-lable mb-1">
                                                        <tbody>
                                                            <tr>
                                                                <th>Name:</th>
                                                                <td>
                                                                    <span class="view-mode">{{$employee->emg_cont_first_name}} {{$employee->emg_cont_last_name}}</span>
                                                                    <input type="text" name="emg_cont_first_name" value="{{ $employee->emg_cont_first_name }}" class="form-control edit-mode d-none">
                                                                    <input type="text" name="emg_cont_last_name" value="{{ $employee->emg_cont_last_name }}" class="form-control edit-mode d-none">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Number:</th>
                                                                <td>
                                                                    <span class="view-mode">{{$employee->emg_cont_no}}</span>
                                                                    <input type="text" name="emg_cont_no" value="{{ $employee->emg_cont_no }}" class="form-control edit-mode d-none">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Relation:</th>
                                                                <td>
                                                                    <span class="view-mode">{{$employee->emg_cont_relationship}}</span>
                                                                    <input type="text" name="emg_cont_relationship" value="{{ $employee->emg_cont_relationship }}" class="form-control edit-mode d-none">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Email Address:</th>
                                                                <td>
                                                                    <span class="view-mode">{{$employee->emg_cont_email}}</span>
                                                                    <input type="text" name="emg_cont_email" value="{{ $employee->emg_cont_email }}" class="form-control edit-mode d-none">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Address:</th>
                                                                <td>
                                                                    <span class="view-mode">{{$employee->emg_cont_current_address}}</span>
                                                                    <textarea class="form-control edit-mode d-none" name="emg_cont_current_address">{{$employee->emg_cont_current_address}}</textarea>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bg-themeGrayLight mb-md-4 mb-3" id="additionInfo">
                                        <div class="card-title mb-0">
                                            <div class="row g-md-2 g-1 align-items-center">
                                                <div class="col">
                                                    <h3 class="text-nowrap">Addtional Information</h3>
                                                </div>
                                                <div class="col-auto">
                                                    <a href="javascript:void(0);" class="btn-lg-icon icon-bg-blue edit-addition-info">
                                                        <img src="{{ URL::asset('resorts_assets/images/edit.svg')}}" alt="icon"></a>
                                                    <button type="submit" class="btn btn-themeBlue btn-sm d-none" id="btn-additionl-info-save">Submit</button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row g-xxl-4 g-md-3 g-2">
                                            <div class="col-lg-6">
                                                <div class="table-responsive">
                                                    <table class="table table-lable mb-1">
                                                        <tbody>
                                                            <tr>
                                                                <th>Leave Destination:</th>
                                                                <td>
                                                                    <span class="view-mode">{{$employee->leave_destination}}</span>
                                                                    {{-- Select2 AJAX dropdown — full IATA list lives in the
                                                                         airports table, queried via /resort/airports/search.
                                                                         The single <option> below pre-loads the existing
                                                                         saved value so the field renders correctly without
                                                                         needing to ship every airport in the markup. --}}
                                                                    <select name="leave_destination" class="form-select select2-airport-search edit-mode d-none" data-current-value="{{ $employee->leave_destination }}">
                                                                        <option value="">Select Destination Airport</option>
                                                                        @if(!empty($employee->leave_destination))
                                                                            <option value="{{ $employee->leave_destination }}" selected>{{ $employee->leave_destination }}</option>
                                                                        @endif
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Biometrics:</th>
                                                                <td>
                                                                    {{-- Biometrics file is stored on Wasabi/S3 via
                                                                         Common::AWSEmployeeFileUpload, so `biometric_file`
                                                                         holds an S3 key like
                                                                         `12/public/categorized/abc.../xyz.pdf` — NOT a
                                                                         public-folder path. The previous markup used
                                                                         URL::asset($employee->biometric_file) which
                                                                         resolved to a 404 (the file isn't in /public).
                                                                         Resolve a temporary signed URL through the
                                                                         canonical helper instead so the link actually
                                                                         opens the document. --}}
                                                                    @php
                                                                        $_bio = null;
                                                                        if (!empty($employee->biometric_file)) {
                                                                            $_bioResult = Common::GetApplicantAWSFile($employee->biometric_file);
                                                                            $_bio = ($_bioResult['success'] ?? false) ? ($_bioResult['NewURLshow'] ?? null) : null;
                                                                        }
                                                                    @endphp
                                                                    <span class="view-mode">
                                                                        @if($_bio)
                                                                            <a href="{{ $_bio }}" target="_blank" rel="noopener">
                                                                                <i class="fa fa-file"></i> View
                                                                            </a>
                                                                        @else
                                                                            <span class="text-muted">—</span>
                                                                        @endif
                                                                    </span>
                                                                    {{-- File input always rendered (was only rendered when
                                                                         no file existed, so users couldn't replace an
                                                                         existing biometric record). Hidden until Edit
                                                                         like every other field. --}}
                                                                    <input type="file" name="biometric_file" class="form-control edit-mode d-none"
                                                                           accept=".pdf,.jpg,.jpeg,.png,.gif,.svg,.webp,.heic,.heif">
                                                                </td>
                                                            </tr>

                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="table-responsive">
                                                    <table class="table table-lable mb-1">
                                                        <thead>
                                                            <tr>
                                                                <th>Language Skills:</th>
                                                                <th>Proficiency Levels:</th>
                                                                <th class="edit-mode d-none">Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="language-rows">
                                                            @if($employee->language)
                                                                @foreach($employee->language as $index => $lang)
                                                                    <tr>
                                                                        <td>
                                                                            <span class="view-mode">{{ $lang->language }}</span>
                                                                            <input type="text" name="languages[{{ $index }}][language]" class="form-control edit-mode d-none" value="{{ $lang->language }}">
                                                                        </td>
                                                                        <td>
                                                                            <span class="view-mode">{{ $lang->proficiency_level }}</span>
                                                                            <select class="form-control edit-mode d-none" name="languages[{{ $index }}][proficiency_level]">
                                                                                <option value="" selected disabled readonly>Select Level</option>
                                                                                <option value="Beginner" @if($lang->proficiency_level == "Beginner") selected @endif >Beginner</option>
                                                                                <option value="Intermediate" @if($lang->proficiency_level == "Intermediate") selected @endif >Intermediate</option>
                                                                                <option value="Advanced" @if($lang->proficiency_level == "Advanced") selected @endif >Advanced</option>
                                                                                <option value="Fluent" @if($lang->proficiency_level == "Fluent") selected @endif >Fluent</option>
                                                                                <option value="Native" @if($lang->proficiency_level == "Native") selected @endif >Native</option>
                                                                            </select>
                                                                            {{-- <input type="text" name="languages[{{ $index }}][proficiency_level]" class="" value="{{ $lang->proficiency_level }}"> --}}
                                                                        </td>
                                                                        <td class="edit-mode d-none">
                                                                            <button type="button" class="btn btn-danger btn-sm remove-language">Remove</button>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            @endif
                                                        </tbody>
                                                        <tfoot class="edit-mode d-none">
                                                        <tr>
                                                            <td colspan="3">
                                                                <button type="button" id="add-language" class="btn btn-themeBlue btn-sm">Add More</button>
                                                            </td>
                                                        </tr>
                                                        </tfoot>

                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-title mb-lg-3">
                                        <h3>Recent Activities</h3>
                                    </div>
                                    @include('resorts.people.employee._partials.recent-activities')
                                </div>

                                <div class="tab-pane fade" id="tabPane2" role="tabpanel" aria-labelledby="#tab2"
                                    tabindex="0">
                                    <div class="bg-themeGrayLight mb-md-4 mb-3">
                                        <div class="card-title mb-0">
                                            <div class="row g-md-2 g-1 align-items-center">
                                                <div class="col">
                                                    <h3 class="text-nowrap">Employment Information</h3>
                                                </div>
                                                <div class="col-auto">
                                                    <a href="javascript:void(0);" class="btn-lg-icon icon-bg-blue edit-emp-info">
                                                        <img src="{{ URL::asset('resorts_assets/images/edit.svg')}}" alt="icon">
                                                    </a>
                                                    <button type="submit" class="btn btn-themeBlue btn-sm d-none" id="btn-emp-info-save">Submit</button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row g-xxl-4 g-md-3 g-2">
                                            <div class="col-lg-6">
                                                <div class="table-responsive">
                                                    <table class="table table-lable mb-1">
                                                        <tbody>
                                                            <tr>
                                                                <th>Employee ID:</th>
                                                                <td>
                                                                    <span>{{$employee->Emp_id}}</span>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Email Address:</th>
                                                                <td>
                                                                    <span class="view-mode">{{$employee->resortAdmin->email}}</span>
                                                                    <input type="email" name="email" class="form-control edit-mode d-none" value="{{$employee->resortAdmin->email}}">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Mobile Number:</th>
                                                                <td>
                                                                    <span class="view-mode">{{$employee->resortAdmin->personal_phone}}</span>
                                                                    {{-- type=tel + permissive pattern so HR can paste
                                                                         in international numbers like "+91 9098765432".
                                                                         Matches the Personal-Information Mobile field. --}}
                                                                    {{-- id="personal_phone_employment" — see comment on the
                                                                         personal_phone_contact input above. --}}
                                                                    <input type="tel" name="personal_phone" id="personal_phone_employment" class="form-control edit-mode d-none"
                                                                           value="{{$employee->resortAdmin->personal_phone}}"
                                                                           pattern="^\+?[0-9\-\s()]{7,25}$"
                                                                           minlength="7" maxlength="25"
                                                                           inputmode="tel" autocomplete="tel"
                                                                           placeholder="e.g. +960 9123456">
                                                                    <small class="form-text text-muted edit-mode d-none">Include country code, e.g. +91 9098765432</small>
                                                                </td>
                                                            </tr>

                                                            <tr>
                                                                <th>Division:</th>
                                                                <td>
                                                                    <span class="view-mode"> {{$employee->division->name ?? "N/A"}}</span>
                                                                    <select name="division_id" class="form-select devision-select edit-mode d-none">
                                                                        <option value="" selected>Select Division</option>
                                                                        @if($resort_divisions)
                                                                            @foreach($resort_divisions as $div)
                                                                                <option value="{{$div->id}}" {{(  isset($employee->division->id)  &&   $div->id ==$employee->division->id) ? "Selected" : ""}}>{{$div->name}}</option>
                                                                            @endforeach
                                                                        @endif
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Department:</th>
                                                                <td>
                                                                    <span class="view-mode"> {{$employee->department->name ?? 'N/A'}}</span>
                                                                    <select name="Dept_id" id="department-select" class="form-select edit-mode d-none"
                                                                            data-current-division="{{ $employee->division->id ?? '' }}"
                                                                            data-current-department="{{ $employee->department->id ?? '' }}">
                                                                        <option value="">Select Department</option>
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Section:</th>
                                                                <td>
                                                                    <span class="view-mode"> {{$employee->section->name ?? "N/A"}}</span>
                                                                    <select name="Section_id" id="section-select" class="form-select edit-mode d-none" data-current-department="{{ $employee->department->id ?? ''}}" data-current-section="{{ $employee->section->id ?? '' }}">
                                                                        <option value="">Select Section</option>
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Position:</th>
                                                                <td>
                                                                    <span class="view-mode">{{$employee->position->position_title}}</span>
                                                                    <select name="Position_id" id="position-select" class="form-select edit-mode d-none" data-current-section="{{ $employee->section->id ?? '' }}" data-current-position="{{ $employee->position->id ?? ''}}">
                                                                        <option value="">Select Position</option>
                                                                    </select>
                                                                </td>
                                                            </tr>

                                                            {{-- <tr>
                                                                <th>Department:</th>
                                                                <td>
                                                                    <span class="view-mode"> {{$employee->department->name}}</span>
                                                                    <select name="Dept_id" class="form-select edit-mode d-none">
                                                                        <option value="">Select Department</option>
                                                                        @if($departments)
                                                                            @foreach($departments as $dept)
                                                                                <option {{$dept->id == $employee->Dept_id ? "Selected" : ""}} value="{{$dept->id}}">{{$dept->name}}</option>
                                                                            @endforeach
                                                                        @endif
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Section:</th>
                                                                <td>                                                  <span class="view-mode">
                                                                        {{$employee->section->name ?? "N/A"}}
                                                                    </span>
                                                                    <select name="Section_id" class="form-select edit-mode d-none">
                                                                        <option value="">Select Section</option>
                                                                        @if($sections)
                                                                            @foreach($sections as $section)
                                                                                <option {{$section->id == $employee->Section_id ? "Selected" : ""}} value="{{$section->id}}">{{$section->name}}</option>
                                                                            @endforeach
                                                                        @endif
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Position:</th>
                                                                <td>
                                                                    <span class="view-mode">{{$employee->position->position_title}}</span>
                                                                    <select name="Position_id" class="form-select edit-mode d-none">
                                                                        <option value="">Select Position</option>
                                                                        @if($positions)
                                                                            @foreach($positions as $pos)
                                                                                <option {{$pos->id == $employee->Position_id ? "Selected" : ""}} value="{{$pos->id}}">{{$pos->position_title}}</option>
                                                                            @endforeach
                                                                        @endif
                                                                    </select>
                                                                </td>
                                                            </tr> --}}

                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="table-responsive">
                                                    <table class="table table-lable mb-1">
                                                        <tbody>
                                                            <tr>
                                                                <th>Joining date:</th>
                                                                 <td>
                                                                    @if(!empty($employee->joining_date) && $employee->joining_date !== '0000-00-00' && strtotime($employee->joining_date) && strtotime($employee->joining_date) > 0)
                                                                        @php
                                                                            $jd = \Carbon\Carbon::parse($employee->joining_date);
                                                                            $now = \Carbon\Carbon::now();
                                                                            $years = $jd->diffInYears($now);
                                                                            $tenureLabel = $years >= 1
                                                                                ? ($years . ' year' . ($years != 1 ? 's' : ''))
                                                                                : ($jd->diffInMonths($now) . ' months');
                                                                        @endphp
                                                                        <span class="view-mode">
                                                                            {{ $jd->format('d M Y') }}
                                                                            ({{ $tenureLabel }})
                                                                        </span>
                                                                        <input type="text" name="joining_date" class="form-control edit-mode d-none datepicker" value="{{ \Carbon\Carbon::parse($employee->joining_date)->format('d/m/Y') }}">
                                                                    @else
                                                                        <span class="view-mode text-muted">Not Available</span>
                                                                        <input type="text" name="joining_date" class="form-control edit-mode d-none datepicker" value="">
                                                                    @endif
                                                                </td>

                                                            </tr>
                                                            <tr>
                                                                <th>Employment Status:</th>
                                                                <td>
                                                                    <span class="view-mode">{{$employee->status}}</span>
                                                                    <select name="status" id="employment-status" class="form-select edit-mode d-none">
                                                                        <option value="">Select Status</option>
                                                                        <option {{$employee->status == "Active" ? "Selected" : ""}} value="Active">Active</option>
                                                                        <option {{$employee->status == "Onboarding" ? "Selected" : ""}} value="Onboarding">Onboarding</option>
                                                                        <option {{$employee->status == "Inactive" ? "Selected" : ""}} value="Inactive">Inactive</option>
                                                                        <option {{$employee->status == "Terminated" ? "Selected" : ""}} value="Terminated">Terminated</option>
                                                                        <option {{$employee->status == "Resigned" ? "Selected" : ""}} value="Resigned">Resigned</option>
                                                                        <option {{$employee->status == "On Leave" ? "Selected" : ""}} value="On Leave">On Leave</option>
                                                                        <option {{$employee->status == "Suspended" ? "Selected" : ""}} value="Suspended">Suspended</option>
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Employment Type:</th>
                                                                <td>
                                                                    <span class="view-mode">{{$employee->employment_type}}</span>
                                                                    <select name="employment_type" id="employment-type" class="form-select edit-mode d-none">
                                                                        <option value="">Select Employment Type</option>
                                                                        <option {{$employee->employment_type == "Full-Time" ? "Selected" : ""}} value="Full-Time">Full-Time</option>
                                                                        <option {{$employee->employment_type == "Part-Time" ? "Selected" : ""}} value="Part-Time">Part-Time</option>
                                                                        <option {{$employee->employment_type == "Contract" ? "Selected" : ""}} value="Contract">Contract</option>
                                                                        <option {{$employee->employment_type == "Casual" ? "Selected" : ""}} value="Casual">Casual</option>
                                                                        <option {{$employee->employment_type == "Probationary" ? "Selected" : ""}} value="Probationary">Probationary</option>
                                                                        <option {{$employee->employment_type == "Internship" ? "Selected" : ""}} value="Internship">Internship</option>
                                                                        <option {{$employee->employment_type == "Temporary" ? "Selected" : ""}} value="Temporary">Temporary</option>
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                            @php
                                                                // Fall back to employee's rank when benefit_grid_level is not set.
                                                                // This aligns with how the benefit_grid table keys off emp_grade = rank.
                                                                $eligibility = config('settings.eligibilty') ?? [];
                                                                $effectiveBgl = $employee->benefit_grid_level;
                                                                if (empty($effectiveBgl) && !empty($employee->rank) && isset($eligibility[$employee->rank])) {
                                                                    $effectiveBgl = $employee->rank;
                                                                }
                                                            @endphp
                                                            <tr>
                                                                <th>Benefit Grid Level:</th>
                                                                <td>
                                                                    {{-- View Mode --}}
                                                                    <span class="view-mode">
                                                                        {{ $effectiveBgl && isset($eligibility[$effectiveBgl]) ? $eligibility[$effectiveBgl] : 'N/A' }}
                                                                    </span>

                                                                    {{-- Edit Mode --}}
                                                                    <select name="benefit_grid_level"
                                                                            id="benefit_grid_level"
                                                                            class="form-select edit-mode d-none"
                                                                            data-placeholder="Benefit Grid Level">
                                                                        @if($effectiveBgl && isset($eligibility[$effectiveBgl]))
                                                                            <option value="{{ $effectiveBgl }}" selected>
                                                                                {{ $eligibility[$effectiveBgl] }}
                                                                            </option>
                                                                        @endif
                                                                    </select>
                                                                </td>


                                                                    {{--
                                                                        <option value="">Select Benefit Grid</option>
                                                                        @if($benefitGrids)
                                                                            @foreach($benefitGrids as $grid)
                                                                            <option {{$employee->benefit_grid_level == $grid->emp_grade ? "Selected" : ""}} value="{{ $grid->emp_grade }}">{{ config('settings.eligibilty')[$grid->emp_grade] ?? 'N/A' }}</option>
                                                                            @endforeach
                                                                        @endif
                                                                    </select> --}}

                                                            </tr>
                                                            <tr id="probation-end-date-row" class="{{ $employee->employment_type == 'Probationary' ? '' : 'd-none' }}">
                                                                <th>Probation exp date:</th>
                                                                <td>
                                                                    <span class="view-mode">{{ $employee->probation_end_date ? \Carbon\Carbon::parse($employee->probation_end_date)->format('d M Y') : "-" }}</span>
                                                                    <input type="text" name="probation_end_date" class="form-control edit-mode d-none datepicker" value="{{ $employee->probation_end_date ? \Carbon\Carbon::parse($employee->probation_end_date)->format('d/m/Y') : '' }}">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Contract type:</th>
                                                                <td>
                                                                    <span class="view-mode">{{$employee->contract_type ?? 'N/A'}}</span>
                                                                    <input type="text" name="contract_type" class="form-control edit-mode d-none" value="{{$employee->contract_type}}">
                                                                </td>
                                                            </tr>
                                                            <tr id="termination-date-row" class="{{ $employee->status != 'Terminated' ? 'd-none' : '' }}">
                                                                <th>Termination date:</th>
                                                                <td>
                                                                    <span class="view-mode">{{ $employee->termination_date ? \Carbon\Carbon::parse($employee->termination_date)->format('d M Y') : "-" }}</span>
                                                                    <input type="text" name="termination_date" class="form-control edit-mode d-none datepicker" value="{{ $employee->termination_date ? \Carbon\Carbon::parse($employee->termination_date)->format('d/m/Y') : '' }}">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>TIN:</th>
                                                                <td>
                                                                    <span class="view-mode">{{$employee->tin ?? "N/A"}}</span>
                                                                    {{-- Maldives MIRA TIN: 10 digits, optionally prefixed
                                                                         by a single letter. Server-side regex in
                                                                         EmployeeController@updateEmploymentData enforces
                                                                         the same rule so an API submit can't bypass. --}}
                                                                    <input type="text" class="form-control edit-mode d-none" name="tin" id="tin-input"
                                                                           pattern="^[A-Za-z]?\d{10}$" maxlength="11"
                                                                           value="{{$employee->tin}}"
                                                                           title="10 digits, optionally prefixed by a single letter (e.g. A1234567890)."
                                                                           placeholder="10-digit Maldives TIN">
                                                                    {{-- Required-TIN notice — appears when EWT Status is
                                                                         toggled on in Salary Details below. JS in
                                                                         `submitEmploymentUpdate` also blocks save if
                                                                         this state is true and the input is empty. --}}
                                                                    <small id="tin-required-notice" class="form-text text-danger d-none">
                                                                        <i class="fa-solid fa-circle-exclamation me-1"></i>
                                                                        TIN is required when EWT Status is Enrolled.
                                                                    </small>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Reporting To:</th>
                                                                <td>
                                                                    <span class="view-mode">{{ $employee->reportingTo ? $employee->reportingTo->resortAdmin->full_name . ' (' . $employee->reportingTo->Emp_id . ')' : 'N/A' }}</span>
                                                                    <select name="reporting_to" id="reporting-to-select" class="form-select edit-mode d-none">
                                                                        <option value="">Select Reporting Person</option>
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bg-themeGrayLight mb-md-4 mb-3">
                                        <div class="card-title mb-0">
                                            <div class="row g-md-2 g-1 align-items-center">
                                                <div class="col">
                                                    <h3 class="text-nowrap">Salary Details</h3>
                                                </div>
                                                <div class="col-auto">
                                                    <a href="javascript:void(0);" class="btn-lg-icon icon-bg-blue edit-salary-details">
                                                        <img src="{{ URL::asset('resorts_assets/images/edit.svg')}}" alt="icon">
                                                    </a>
                                                    <button type="submit" class="btn btn-themeBlue btn-sm d-none" id="btn-salary-save">Submit</button>
                                                </div>
                                            </div>
                                        </div>

                                        @php
                                            $conversionRate;
                                            $basicMvr = $employee->basic_salary_currency === 'USD' ? $employee->basic_salary * $conversionRate : $employee->basic_salary;
                                            $totalAllowanceMvr = 0;
                                            foreach ($employee->allowance as $allowance) {
                                                $amt = $allowance->amount ?? 0;
                                                $unit = $allowance->amount_unit ?? 'USD';
                                                $totalAllowanceMvr += $unit === 'USD' ? ($amt * $conversionRate) : $amt;
                                            }
                                            $totalMonthlyEarningMvr = $basicMvr + $totalAllowanceMvr;
                                            $tin = $employee->tin ?? null;
                                        @endphp

                                        <div class="row g-xxl-4 g-md-3 g-2" id="salary-details-container">
                                            <div class="col-lg-6">
                                                <div class="table-responsive">
                                                    <table class="table table-lable mb-1">
                                                        <tbody>
                                                            <tr>
                                                                <th>Basic Salary:</th>
                                                                <td>
                                                                    <span class="view-mode">{{ number_format($employee->basic_salary, 2) ?? 0 }} {{ $employee->basic_salary_currency }}</span>
                                                                    <input type="text" name="basic_salary" class="form-control edit-mode d-none" value="{{ $employee->basic_salary }}">
                                                                    <select class="form-select edit-mode d-none" name="basic_salary_currency">
                                                                        <option value="USD" {{ $employee->basic_salary_currency == "USD" ? "selected" : "" }}>USD</option>
                                                                        <option value="MVR" {{ $employee->basic_salary_currency == "MVR" ? "selected" : "" }}>MVR</option>
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Payment Mode:</th>
                                                                <td>
                                                                    <span class="view-mode">{{ $employee->payment_mode ?? 'Bank' }}</span>
                                                                    <select name="payment_mode" class="form-select edit-mode d-none">
                                                                        <option value="Bank" {{ ($employee->payment_mode ?? '') == 'Bank' ? 'selected' : '' }}>Bank</option>
                                                                        <option value="Cash" {{ ($employee->payment_mode ?? '') == 'Cash' ? 'selected' : '' }}>Cash</option>
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                            @if($employee->nationality == 'Maldivian')
                                                                <tr>
                                                                    <th>Pension:</th>
                                                                    <td>
                                                                        <span class="view-mode">{{ number_format($employee->pension ?? 0, 2) }}</span>
                                                                        <input type="text" name="pension" class="form-control edit-mode d-none" value="{{ $employee->pension ?? 0 }}">
                                                                    </td>
                                                                </tr>
                                                            @else
                                                                <tr><th>Pension:</th><td>N/A</td></tr>
                                                            @endif
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>

                                            <div class="col-lg-6">
                                                <div class="table-responsive">
                                                    <table class="table table-lable mb-1">
                                                        <tbody>
                                                            <tr>
                                                                <th>Entitle for Service Charge:</th>
                                                                <td>
                                                                    <span class="view-mode">{{ $employee->entitled_service_charge == 'yes' ? 'Yes' : 'No' }}</span>
                                                                    <div class="form-check form-switch form-switchTheme switch-blue edit-mode d-none">
                                                                        <input class="form-check-input" type="checkbox" name="entitle_service_charge" id="entitle_service_charge" {{ $employee->entitled_service_charge == 'yes' ? 'checked' : '' }}>
                                                                        <label class="form-check-label" for="entitle_service_charge">Yes</label>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Entitle for Overtime:</th>
                                                                <td>
                                                                    <span class="view-mode">{{ $employee->entitled_overtime == 'yes' ? 'Yes' : 'No' }}</span>
                                                                    <div class="form-check form-switch form-switchTheme switch-blue edit-mode d-none">
                                                                        <input class="form-check-input" type="checkbox" name="entitle_overtime" id="entitle_overtime" {{ $employee->entitled_overtime == 'yes' ? 'checked' : '' }}>
                                                                        <label class="form-check-label" for="entitle_overtime">Yes</label>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Entitle for Public Holiday:</th>
                                                                <td>
                                                                    <span class="view-mode">{{ $employee->entitled_public_holiday == 'yes' ? 'Yes' : 'No' }}</span>
                                                                    <div class="form-check form-switch form-switchTheme switch-blue edit-mode d-none">
                                                                        <input class="form-check-input" type="checkbox" name="entitle_public_holiday" id="entitle_public_holiday" {{ $employee->entitled_public_holiday == 'yes' ? 'checked' : '' }}>
                                                                        <label class="form-check-label" for="entitle_public_holiday">Yes</label>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>EWT Status:</th>
                                                                <td>
                                                                    <span class="view-mode">{{ $tin ? '✅ Enrolled' : ($totalMonthlyEarningMvr >= 30000 ? '❌ Not Enrolled' : 'Not Required') }}</span>
                                                                    <div class="form-check form-switch form-switchTheme switch-blue edit-mode d-none">
                                                                        <input class="form-check-input" type="checkbox" name="ewt_status" id="ewt_status" {{ $employee->ewt_status == 'yes' ? 'checked' : '' }}>
                                                                        <label class="form-check-label" for="ewt_status">Enrolled</label>
                                                                    </div>
                                                                    @if($totalMonthlyEarningMvr >= 30000 && !$tin)
                                                                    <div id="ewt-eligibility-section" class="mt-2">
                                                                        <span class="badge bg-warning text-dark">Earning MVR 30,000 or more — employee may be eligible for EWT registration</span>
                                                                        <div class="mt-2">
                                                                            <span class="badge bg-secondary">TIN Status: <span id="tin-status-label">Not Available</span></span>

                                                                        </div>
                                                                    </div>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>

                                            <div class="col-md-12 mt-3 card-title mb-0">
                                                <div class="col">
                                                    <h3 class="text-nowrap">Allowances</h3>
                                                </div>
                                                <hr/>
                                                <div class="view-mode">
                                                    @if(isset($employee->allowance) && count($employee->allowance) > 0)
                                                    <div class="table-responsive">
                                                        <table class="table">
                                                            <thead>
                                                                <tr>
                                                                    <th>Particulars</th>
                                                                    <th>Amount</th>
                                                                    <th>Amount Unit</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($employee->allowance as $allowance)
                                                                <tr>
                                                                    <td>{{ $allowance->allowanceName->particulars }}</td>
                                                                    <td>{{ number_format($allowance->amount, 2) }}</td>
                                                                    <td>{{ $allowance->amount_unit ?? 'USD' }}</td>
                                                                </tr>
                                                                @endforeach
                                                            </tbody>
                                                            <tfoot>
                                                                <tr>
                                                                    <th>Total:</th>
                                                                    <th colspan="2">{{ number_format($totalAllowanceMvr, 2) }} MVR</th>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                    </div>
                                                    @else
                                                        <p class="text-muted">No allowances found</p>
                                                    @endif
                                                </div>

                                                <!-- Edit mode for allowances -->
                                                <div class="edit-mode d-none">
                                                    <div id="allowances-container">
                                                        @if(isset($employee->allowance) && count($employee->allowance) > 0)
                                                            @foreach($employee->allowance as $index => $allowance)
                                                            <div class="row mb-2 allowance-row">
                                                                <div class="col-4">
                                                                    <select name="allowances[{{ $index }}][type]" class="form-select">
                                                                        <option value="">Select Type</option>
                                                                        @if($resort_allowances)
                                                                            @foreach($resort_allowances as $resort_allowance)
                                                                                <option value="{{ $resort_allowance->id }}" {{ $allowance->allowance_id == $resort_allowance->id ? 'selected' : '' }}>{{ $resort_allowance->particulars }}</option>
                                                                            @endforeach
                                                                        @endif
                                                                    </select>
                                                                </div>
                                                                <div class="col-3">
                                                                    <input type="text" name="allowances[{{ $index }}][amount]" class="form-control" value="{{ $allowance->amount }}" placeholder="Amount">
                                                                </div>
                                                                <div class="col-3">
                                                                    <select name="allowances[{{ $index }}][amount_unit]" class="form-select">
                                                                        <option value="USD" {{ $allowance->amount_unit == 'USD' ? 'selected' : '' }}>USD</option>
                                                                        <option value="MVR" {{ $allowance->amount_unit == 'MVR' ? 'selected' : '' }}>MVR</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-2">
                                                                    <button type="button" class="btn btn-danger btn-sm remove-allowance">
                                                                        <i class="fa fa-times"></i>
                                                                    </button>
                                                                </div>
                                                                <input type="hidden" name="allowances[{{ $index }}][id]" value="{{ $allowance->id }}">
                                                            </div>
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                     <div class="text-end mb-3">
                                                        <button type="button" id="add-allowance" class="btn btn-themeBlue btn-sm">
                                                            + Add Allowance
                                                        </button>
                                                    </div>
                                                    <!-- <div class="row mt-3">
                                                        <div class="col-12">
                                                            <button type="button" id="add-allowance" class="btn btn-sm btn-outline-primary">
                                                                <i class="fa fa-plus"></i> Add Allowance
                                                            </button>
                                                        </div>
                                                    </div> -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>


                                    <div class="bg-themeGrayLight mb-md-4 mb-3">
                                        <div class="card-title mb-0">
                                            <div class="row g-md-2 g-1 align-items-center">
                                                <div class="col">
                                                    <h3 class="text-nowrap">Bank Details</h3>
                                                </div>
                                            </div>
                                        </div>

                                        @if($employee->bankDetails && count($employee->bankDetails))
                                            @foreach($employee->bankDetails->chunk(2) as $bankChunk)
                                                <div class="row g-xxl-4 g-md-3 g-2 mb-2">
                                                    @foreach($bankChunk as $bankDetail)
                                                        <div class="col-lg-6">
                                                            <div class="table-responsive position-relative border rounded p-3 bg-white">
                                                                <!-- Edit Button for each education entry -->
                                                                <a href="javascript:void(0);" data-id="{{ $bankDetail->id }}" class="btn-lg-icon icon-bg-blue position-absolute top-0 end-0 m-2 edit-bank-info" title="Edit">
                                                                    <img src="{{ URL::asset('resorts_assets/images/edit.svg')}}" alt="Edit Icon">
                                                                </a>
                                                                <button type="submit" class="btn btn-themeBlue btn-sm d-none" id="btn-bank-info-save-{{$bankDetail->id}}">Submit</button>

                                                                <table class="table table-lable mb-1">
                                                                    <tbody>
                                                                        <tr>
                                                                            <th>Bank Name:</th>
                                                                            <td>
                                                                                <span class="view-mode">
                                                                                    {{$bankDetail->bank_name ?? "Not Available"}}
                                                                                </span>
                                                                                <input type="text" name="bank_name" class="form-control edit-mode d-none" value="{{$bankDetail->bank_name}}">
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Bank Location/Branch:</th>
                                                                            <td>
                                                                                <span class="view-mode">{{$bankDetail->bank_branch ?? "Not Available"}}</span>
                                                                                <input type="text" name="bank_branch" class="form-control edit-mode d-none" value="{{$bankDetail->bank_branch}}">
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Account Type:</th>
                                                                            <td>
                                                                                <span class="view-mode">{{$bankDetail->account_type ?? "Not Available"}}</span>
                                                                                <input type="text" name="account_type" class="form-control edit-mode d-none" value="{{$bankDetail->account_type}}">
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>IFSC/SWIFT/BIC Code:</th>
                                                                            <td>
                                                                                <span class="view-mode">{{$bankDetail->IFSC_BIC ?? "Not Available"}}</span>
                                                                                <input type="text" name="IFSC_BIC" class="form-control edit-mode d-none" value="{{$bankDetail->IFSC_BIC}}"
                                                                                    data-parsley-required="true"
                                                                                    data-parsley-required-message="IFSC/SWIFT/BIC code is required."
                                                                                    data-parsley-pattern="^[A-Za-z0-9]{6,15}$"
                                                                                    data-parsley-pattern-message="Please enter a valid IFSC/SWIFT/BIC code (6-15 alphanumeric characters)." required>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Account Holder's Name:</th>
                                                                            <td>
                                                                                <span class="view-mode">{{$bankDetail->account_holder_name ?? "Not Available"}}</span>
                                                                                <input type="text" name="account_holder_name" class="form-control edit-mode d-none" value="{{$bankDetail->account_holder_name}}">
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Account Number:</th>
                                                                            <td>
                                                                                <span class="view-mode">{{$bankDetail->account_no ?? "Not Available"}}</span>
                                                                                <input type="number" name="account_no" class="form-control edit-mode d-none" value="{{$bankDetail->account_no}}">
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Currency:</th>
                                                                            <td>
                                                                                <span class="view-mode">{{$bankDetail->currency ?? "Not Available"}}</span>
                                                                                <select name="currency" class="form-control edit-mode d-none" >
                                                                                    <option {{$bankDetail->currency == "USD" ? "Selected" : "" }} value="USD">USD</option>
                                                                                    <option {{$bankDetail->currency == "MVR" ? "Selected" : "" }} value="MVR">MVR</option>
                                                                                </select>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>IBAN:</th>
                                                                            <td>
                                                                                <span class="view-mode">{{$bankDetail->IBAN ?? "Not Available"}}</span>
                                                                                <input type="text" name="IBAN" class="form-control edit-mode d-none" value="{{$bankDetail->IBAN}}">
                                                                            </td>
                                                                        </tr>

                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="row g-xxl-4 g-md-3 g-2 mb-2">
                                                <p class="text-muted px-3 py-2">No bank records found.</p>
                                            </div>
                                        @endif
                                        <div class="text-end mb-3">
                                            <button type="button" id="add-new-bankDetails" class="btn btn-themeBlue btn-sm">
                                                + Add Bank Details
                                            </button>
                                        </div>
                                    </div>

                                    <div class="card-title mb-lg-3">
                                        <h3>Recent Activities</h3>
                                    </div>
                                    @include('resorts.people.employee._partials.recent-activities')

                                    {{-- Employment change-log feed. Populated via AJAX
                                         (people.employees.employment-logs) so the
                                         table-of-edits doesn't bloat first render.
                                         The wrapper carries the employee id; the
                                         loader script lives at the bottom of the
                                         page (look for loadEmploymentLogs). --}}
                                    <div class="card-title mb-lg-3 mt-4">
                                        <h3>Employment Change Log</h3>
                                    </div>
                                    <div id="employment-logs-wrap" data-employee-id="{{ $employee->id }}">
                                        <div class="text-center text-muted py-3">
                                            <i class="fa-solid fa-spinner fa-spin"></i> Loading change log…
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="tabPane3" role="tabpanel" aria-labelledby="tab3" tabindex="0">
                                    <div class="bg-themeGrayLight mb-md-4 mb-3">
                                        <div class="card-title mb-0">
                                            <div class="row g-md-2 g-1 align-items-center">
                                                <div class="col">
                                                    <h3 class="text-nowrap">Education</h3>
                                                </div>
                                            </div>
                                        </div>

                                        @if($employee->education && count($employee->education))
                                            @foreach($employee->education->chunk(2) as $eduChunk)
                                                <div class="row g-xxl-4 g-md-3 g-2 mb-2">
                                                    @foreach($eduChunk as $education)
                                                        <div class="col-lg-6">
                                                            <div class="table-responsive position-relative border rounded p-3 bg-white">
                                                                <!-- Edit Button for each education entry -->
                                                                <a href="javascript:void(0);" data-id="{{ $education->id }}" class="btn-lg-icon icon-bg-blue position-absolute top-0 end-0 m-2 edit-edu-info" title="Edit">
                                                                    <img src="{{ URL::asset('resorts_assets/images/edit.svg')}}" alt="Edit Icon">
                                                                </a>
                                                                <button type="submit" class="btn btn-themeBlue btn-sm d-none" id="btn-edu-info-save-{{$education->id}}">Submit</button>

                                                                <table class="table table-lable mb-1">
                                                                    <tbody>
                                                                        <tr>
                                                                            <th>Education Level/Type:</th>
                                                                            <td>
                                                                                <span class="view-mode">
                                                                                    {{$education->education_level}}
                                                                                </span>
                                                                                <input type="text" name="education_level" class="form-control edit-mode d-none" value="{{$education->education_level}}">
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Institution Name:</th>
                                                                            <td>
                                                                                <span class="view-mode">{{ $education->institution_name}}</span>
                                                                                <input type="text" name="institution_name" class="form-control edit-mode d-none" value="{{$education->institution_name}}">
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Field of Study / Major:</th>
                                                                            <td>
                                                                                <span class="view-mode">{{ $education->field_of_study }}</span>
                                                                                <input type="text" name="field_of_study" class="form-control edit-mode d-none" value="{{$education->field_of_study}}">
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Degree/Certificate Earned:</th>
                                                                            <td>
                                                                                <span class="view-mode">{{ $education->degree }}</span>
                                                                                <input type="text" name="degree" class="form-control edit-mode d-none" value="{{$education->degree}}">
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Attendance Period:</th>
                                                                            <td>
                                                                                <span class="view-mode">{{ $education->attendance_period ?? 'N/A' }}</span>
                                                                                <input type="text" name="attendance_period" class="form-control edit-mode d-none" value="{{$education->attendance_period}}" placeholder="e.g., 2015 - 2019">
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Location:</th>
                                                                            <td>
                                                                                <span class="view-mode">{{ $education->location ?? 'N/A' }}</span>
                                                                                <input type="text" name="location" class="form-control edit-mode d-none" value="{{$education->location}}">
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Document Upload / Certificate Attachment</th>
                                                                            <td>
                                                                                @if($education->certification)
                                                                                    <a href="{{ URL::asset($education->certification) }}" target="_blank"><i class="fa fa-file"></i></a>
                                                                                @else
                                                                                    <input type="file" name="certification" class="form-control edit-mode d-none">
                                                                                @endif
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="row g-xxl-4 g-md-3 g-2 mb-2">
                                                <p class="text-muted px-3 py-2">No education records found.</p>
                                            </div>
                                        @endif
                                        <div class="text-end mb-3">
                                            <button type="button" id="add-new-education" class="btn btn-themeBlue btn-sm">
                                                + Add New Education
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Recent Activities Section -->
                                    <div class="card-title mb-lg-3">
                                        <h3>Recent Activities</h3>
                                    </div>
                                    @include('resorts.people.employee._partials.recent-activities')
                                </div>

                                <div class="tab-pane fade" id="tabPane4" role="tabpanel" aria-labelledby="tab4" tabindex="0">
                                    <div class="bg-themeGrayLight mb-md-4 mb-3">
                                        <div class="card-title mb-0">
                                            <div class="row g-md-2 g-1 align-items-center">
                                                <div class="col">
                                                    <h3 class="text-nowrap">Experience</h3>
                                                </div>
                                            </div>
                                        </div>

                                        @if($employee->experiance && count($employee->experiance))
                                            @foreach($employee->experiance->chunk(2) as $expChunk)
                                                <div class="row g-xxl-4 g-md-3 g-2 mb-2">
                                                    @foreach($expChunk as $exp)
                                                        <div class="col-lg-6">
                                                            <div class="table-responsive position-relative border rounded p-3 bg-white">
                                                                <!-- Edit Button for each experience entry -->
                                                                <a href="javascript:void(0);" data-id="{{ $exp->id }}" class="btn-lg-icon icon-bg-blue position-absolute top-0 end-0 m-2 edit-exp-info" title="Edit">
                                                                    <img src="{{ URL::asset('resorts_assets/images/edit.svg')}}" alt="Edit Icon">
                                                                </a>
                                                                <button type="submit" class="btn btn-themeBlue btn-sm d-none" id="btn-exp-info-save-{{$exp->id}}">Submit</button>
                                                                <table class="table table-lable mb-1">
                                                                    <tbody>
                                                                        <tr>
                                                                            <th>Company Name:</th>
                                                                            <td>
                                                                                <span class="view-mode">{{ $exp->company_name }}</span>
                                                                                <input type="text" name="company_name" class="form-control edit-mode d-none" value="{{$exp->company_name}}">
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Job Title / Position:</th>
                                                                            <td>
                                                                                <span class="view-mode">{{ $exp->job_title }}</span>
                                                                                <input type="text" name="job_title" class="form-control edit-mode d-none" value="{{$exp->job_title}}">
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Employment Type:</th>
                                                                            <td>
                                                                                <span class="view-mode">{{ $exp->employment_type }}</span>

                                                                                <select name="employment_type" class="form-select edit-mode d-none">
                                                                                    <option value="">Select employment_type</option>
                                                                                    <option {{$exp->employment_type == "Full-Time" ? "Selected" : ""}} value="Full-Time">Full-Time</option>
                                                                                    <option {{$exp->employment_type == "Part-Time" ? "Selected" : ""}} value="Part-Time">Part-Time</option>
                                                                                    <option {{$exp->employment_type == "Contract" ? "Selected" : ""}} value="Contract">Contract</option>
                                                                                    <option {{$exp->employment_type == "Casual" ? "Selected" : ""}} value="Casual">Casual</option>
                                                                                    <option {{$exp->employment_type == "Probationary" ? "Selected" : ""}} value="Probationary">Probationary</option>
                                                                                    <option {{$exp->employment_type == "Internship" ? "Selected" : ""}} value="Internship">Internship</option>
                                                                                    <option {{$exp->employment_type == "Temporary" ? "Selected" : ""}} value="Temporary">Temporary</option>
                                                                                </select>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Duration of Employment:</th>
                                                                            <td>
                                                                                <span class="view-mode">{{ $exp->duration }}</span>
                                                                                <input type="text" name="duration" class="form-control edit-mode d-none" value="{{$exp->duration}}" placeholder="e.g. 01/2015-01/2019">
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Location:</th>
                                                                            <td>
                                                                                <span class="view-mode">{{ $exp->location }}</span>
                                                                                <input type="text" name="location" class="form-control edit-mode d-none" value="{{$exp->location}}">
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Reason for Leaving:</th>
                                                                            <td>
                                                                                <span class="view-mode">{{ $exp->reason_for_leaving }}</span>
                                                                                <input type="text" name="reason_for_leaving" class="form-control edit-mode d-none" value="{{$exp->reason_for_leaving}}">
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Reference Name:</th>
                                                                            <td>
                                                                                <span class="view-mode">{{ $exp->reference_name }}</span>
                                                                                <input type="text" name="reference_name" class="form-control edit-mode d-none" value="{{$exp->reference_name}}">
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Reference Contact Information:</th>
                                                                            <td>
                                                                                <span class="view-mode">{{ $exp->reference_contact }}</span>
                                                                                <input type="number" name="reference_contact" class="form-control edit-mode d-none" value="{{$exp->reference_contact}}">
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="row g-xxl-4 g-md-3 g-2 mb-2">
                                                <p class="text-muted px-3 py-2">No experience records found.</p>
                                            </div>
                                        @endif

                                        <div class="text-end mb-3">
                                            <button type="button" id="add-new-experience" class="btn btn-themeBlue btn-sm">
                                                + Add New Experience
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Recent Activities Section -->
                                    <div class="card-title mb-lg-3">
                                        <h3>Recent Activities</h3>
                                    </div>
                                    @include('resorts.people.employee._partials.recent-activities')
                                </div>

                                <div class="tab-pane fade" id="tabPane5" role="tabpanel" aria-labelledby="tab5" tabindex="0">
                                    <div class="bg-themeGrayLight mb-md-4 mb-3">
                                        <div class="card-title mb-0">
                                            <div class="row g-md-2 g-1 align-items-center">
                                                <div class="col">
                                                    <h3 class="text-nowrap">Expiry</h3>
                                                </div>
                                                <div class="col-auto">
                                                    <a href="javascript:void(0);" class="btn-lg-icon icon-bg-blue edit-expiry-btn">
                                                        <img src="{{ URL::asset('resorts_assets/images/edit.svg')}}" alt="icon">
                                                    </a>
                                                    <button type="button" class="btn btn-themeBlue btn-sm d-none" id="btn-expiry-save">Submit</button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row g-xxl-4 g-md-3 g-2">
                                            @if($employee->document && $employee->document->count())
                                                @php
                                                    $chunks = $employee->document->chunk(ceil($employee->document->count() / 2));
                                                @endphp

                                                @foreach($chunks as $chunk)
                                                <div class="col-lg-6">
                                                    <div class="table-responsive">
                                                        <table class="table table-lable mb-1">
                                                            <tbody>
                                                                @foreach($chunk as $doc)
                                                                <tr>
                                                                    <th>
                                                                        <span class="view-mode">{{ $doc->document_title ?? '-' }}</span>
                                                                        <input type="hidden" name="document_ids[]" value="{{ $doc->id }}">
                                                                        <input type="text" name="document_titles[]" class="form-control edit-mode d-none" value="{{ $doc->document_title }}">
                                                                    </th>
                                                                    <td>
                                                                        <span class="view-mode">{{ $doc->expiry_date ? \Carbon\Carbon::parse($doc->expiry_date)->format('d M Y') : '-' }}</span>
                                                                        <input type="text" name="expiry_dates[]" class="form-control edit-mode d-none datepicker" value="{{ $doc->expiry_date ? \Carbon\Carbon::parse($doc->expiry_date)->format('d/m/Y') : '' }}">
                                                                        {{-- Document is stored on Wasabi/S3 under
                                                                             `document_path` (NOT `document_file` —
                                                                             that column doesn't exist; the link
                                                                             always failed silently). Use
                                                                             Common::GetApplicantAWSFile to mint a
                                                                             temporary signed URL — same pattern as
                                                                             the Biometrics row. --}}
                                                                        @if(!empty($doc->document_path))
                                                                            @php
                                                                                $_docResult = Common::GetApplicantAWSFile($doc->document_path);
                                                                                $_docUrl = ($_docResult['success'] ?? false) ? ($_docResult['NewURLshow'] ?? null) : null;
                                                                            @endphp
                                                                            @if($_docUrl)
                                                                                <a href="{{ $_docUrl }}" target="_blank" rel="noopener" class="ms-2" title="View document">
                                                                                    <i class="fa fa-file"></i>
                                                                                </a>
                                                                            @endif
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                                @endforeach
                                            @else
                                                <div class="col-12">
                                                    <p>No documents found.</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="card-title mb-lg-3">
                                        <h3>Recent Activities</h3>
                                    </div>

                                    @include('resorts.people.employee._partials.recent-activities')
                                </div>

                                <div class="tab-pane fade" id="tabPane6" role="tabpanel" aria-labelledby="tab6" tabindex="0">
                                    <div class="bg-themeGrayLight mb-md-4 mb-3">
                                        <div class="card-title mb-0">
                                            <div class="row g-md-2 g-1 align-items-center">
                                                <div class="col">
                                                    <h3 class="text-nowrap">Send Credentials</h3>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row g-xxl-4 g-md-3 g-2">
                                            <button class="btn btn-themeBlue btn-sm send-credentials-btn" data-id="{{ $employee->id }}">
                                                Send Credentials
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="assignTeamModal" tabindex="-1" aria-labelledby="assignTeamLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="assignTeamForm">
                @csrf
                <input type="hidden" name="emp_id" id="empIdInput">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Assign to Team</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="team_id">Select Team</label>
                            <select name="team_id" class="form-select select2t-none" required>
                                <option value="">Select Team</option>
                                @foreach($teams as $team)
                                    <option value="{{ $team->id }}">{{ $team->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="role_id">Select Role</label>
                            <select name="role_id" class="form-select select2t-none" required>
                                <option value="">Select Role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Assign</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="modal fade" id="statusChangeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="statusChangeForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                <h5 class="modal-title">Change Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                <input type="hidden" name="emp_id" id="modal-emp-id">

                <select name="status" id="modal-status" class="form-select select2-modal">
                    <option value="">Status</option>
                    <option {{$employee->status == "Active" ? "Selected" : ""}} value="Active">Active</option>
                    <option {{$employee->status == "Onboarding" ? "Selected" : ""}} value="Onboarding">Onboarding</option>
                    <option {{$employee->status == "Probationary" ? "Selected" : ""}} value="Probationary">Probationary</option>
                    <option {{$employee->status == "Inactive" ? "Selected" : ""}} value="Inactive">Inactive</option>
                    <option {{$employee->status == "Terminated" ? "Selected" : ""}} value="Terminated">Terminated</option>
                    <option {{$employee->status == "Resigned" ? "Selected" : ""}} value="Resigned">Resigned</option>
                    <option {{$employee->status == "On Leave" ? "Selected" : ""}} value="On Leave">On Leave</option>
                    <option {{$employee->status == "Suspended" ? "Selected" : ""}} value="Suspended">Suspended</option>
                </select>
                </div>
                <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </div>
            </form>
        </div>
    </div>

    {{-- Activate Employee modal — captures the joining date and flips the
         employee from 'Onboarding' to 'Active'. --}}
    <div class="modal fade" id="activateEmployeeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="activateEmployeeForm">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Activate Employee</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="emp_id" id="activate-emp-id">
                        <p class="mb-3">Confirm the employee has completed onboarding. This sets their
                            joining date and marks them <strong>Active</strong> — they will then appear
                            in Payroll, Attendance and headcount.</p>
                        <div class="mb-2">
                            <label for="activate-joining-date" class="form-label">Joining Date <span class="red-mark">*</span></label>
                            <input type="text" class="form-control datepicker" name="joining_date"
                                id="activate-joining-date" placeholder="dd/mm/yyyy" autocomplete="off" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-theme">Activate</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Employment-section sensitive-edit confirmation modal. Employment
         data drives payroll, benefit-grid eligibility, reporting lines
         and tax records — accidental edits cascade widely, so a second
         confirmation is warranted before the AJAX PUT fires. --}}
    <div class="modal fade" id="employmentEditConfirmModal" tabindex="-1" aria-labelledby="employmentEditConfirmLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="employmentEditConfirmLabel">
                        <i class="fa-solid fa-triangle-exclamation text-warning me-1"></i>
                        Confirm Employment Update
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2"><strong>This is sensitive data.</strong></p>
                    <p class="mb-0 text-muted" style="font-size: 13px;">
                        Employment information drives payroll, benefit-grid
                        entitlements, reporting structure and statutory
                        records. Changes apply immediately. Are you sure
                        you want to save these edits?
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-themeGray btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-themeBlue btn-sm" id="confirm-emp-info-save">
                        Yes, Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
<style>
    /* Pin the 3-dot kebab to the top-right corner of the profile sidebar.
       default.css already absolute-positions
       `.empDetailPeopleEmp-sidebar .dropdown` at top:22px / right:22px,
       but the rendered button was visually drifting toward the centre.
       Force the corner with !important so cached/legacy theme rules can't
       override. Same approach as the grid card fix on /people/employees. */
    .empDetailPeopleEmp-sidebar { position: relative !important; }
    .empDetailPeopleEmp-sidebar .dropdown,
    .empDetailPeopleEmp-sidebar .table-dropdown {
        position: absolute !important;
        top: 10px !important;
        right: 12px !important;
        left: auto !important;
        z-index: 5;
    }
    .empDetailPeopleEmp-sidebar .dotsV-link {
        padding: 4px 8px !important;
        line-height: 1 !important;
    }

    /* Leave Destination bug — the field uses select2-airport-search, and
       select2 inserts a separate .select2-container right after the
       original <select>. Bootstrap's `.d-none` hides the original select,
       but select2's rendered widget kept showing → users could open and
       select an airport before ever clicking Edit. Hide the widget too
       while the underlying select is in view mode; `removeClass('d-none')`
       in the edit toggle automatically restores it. */
    .select2-airport-search.d-none + .select2-container {
        display: none !important;
    }
</style>
@endsection

@section('import-scripts')

<script>
    // ------------------------------------------------------------------
    // Tab persistence across reloads. Every section submit (Personal
    // Info, Employment, Salary, Allowances, Bank, Status, Activate,
    // etc.) calls location.reload(), which kicked the user back to the
    // default Personal Details tab. Persist the active tab id in the
    // URL hash so the browser's automatic hash preservation on reload
    // drops them back where they were.
    //
    // - Bootstrap fires `shown.bs.tab` whenever a tab activates; we
    //   record its target into location.hash via history.replaceState
    //   (avoids polluting browser history with every tab click).
    // - On DOM ready, look at location.hash. If it matches one of the
    //   tab panes, programmatically activate the matching nav-link.
    // ------------------------------------------------------------------
    (function () {
        function activateTabFromHash() {
            var hash = (window.location.hash || '').replace(/^#/, '');
            if (!hash) return;
            // Only handle our employee-detail tabPaneN ids — don't
            // hijack other potential anchor links on the page.
            if (!/^tabPane\d+$/.test(hash)) return;
            var trigger = document.querySelector('[data-bs-target="#' + hash + '"]');
            if (!trigger) return;
            try {
                // Bootstrap 5 Tab API.
                var tab = bootstrap.Tab.getOrCreateInstance(trigger);
                tab.show();
            } catch (e) {
                // Fallback for environments where the bootstrap global
                // hasn't loaded yet — defer until DOM ready.
                $(trigger).tab && $(trigger).tab('show');
            }
        }

        $(document).on('shown.bs.tab', '[data-bs-toggle="tab"]', function (e) {
            var target = (e.target.getAttribute('data-bs-target') || '').replace(/^#/, '');
            if (!target || !/^tabPane\d+$/.test(target)) return;
            // replaceState so we don't add a history entry per tab click.
            if (history.replaceState) {
                history.replaceState(null, '', '#' + target);
            } else {
                window.location.hash = '#' + target;
            }
        });

        $(document).ready(activateTabFromHash);
    })();

    // Function to load HOD list (defined globally to be accessible from all script blocks)
    function loadHODList() {
        const currentDeptId = $('[name="Dept_id"]').val() || $('#department-select').data('current-department') || '';
        const currentReportingTo = {{ $employee->reporting_to ? $employee->reporting_to : 'null' }};

        $.ajax({
            url: '{{ route("people.getReportingPerson") }}',
            method: 'GET',
            data: {
                department_id: currentDeptId || ''
            },
            success: function(response) {
                if (response.success && response.data && response.data.length > 0) {
                    const $select = $('#reporting-to-select');
                    const currentValue = $select.val(); // Preserve current selection if dropdown already has a value
                    $select.empty();
                    $select.append('<option value="">Select Reporting Person</option>');

                    response.data.forEach(function(emp) {
                        const fullName = (emp.first_name || '') + ' ' + (emp.last_name || '');
                        const empId = emp.Emp_id || '';
                        let selected = '';
                        // Use currentValue if available, otherwise use currentReportingTo
                        if (currentValue && emp.id == currentValue) {
                            selected = 'selected';
                        } else if (!currentValue && currentReportingTo && emp.id == currentReportingTo) {
                            selected = 'selected';
                        }
                        $select.append(`<option value="${emp.id}" ${selected}>${fullName.trim()} (${empId})</option>`);
                    });
                } else {
                    console.warn('No reporting persons found or empty response');
                    const $select = $('#reporting-to-select');
                    $select.empty();
                    $select.append('<option value="">No Reporting Persons Available</option>');
                }
            },
            error: function(xhr) {
                console.error('Failed to load HOD list:', xhr);
                const $select = $('#reporting-to-select');
                $select.empty();
                $select.append('<option value="">Error loading reporting persons</option>');
            }
        });
    }

    $(document).ready(function(){
        $('.datepicker').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true
        });
        $(document).on('click', '.send-credentials-btn', function (e) {
            e.preventDefault();

            let $btn = $(this); // reference to the clicked button
            let employeeId = $btn.data('id');

            if (!employeeId) {
                toastr.error("Invalid employee ID.");
                return;
            }

            $.ajax({
                url: "{{ route('people.employee.send-credentials') }}",
                type: 'POST',
                data: {
                    employee_id: employeeId,
                    _token: "{{ csrf_token() }}"
                },
                beforeSend: function () {
                    $btn.prop('disabled', true).text('Sending...');
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                    } else {
                        toastr.error(response.message || 'Something went wrong.', "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function (xhr) {
                    let error = xhr.responseJSON?.message || 'Request failed.';
                    toastr.error(error, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                },
                complete: function () {
                    $btn.prop('disabled', false).text('Send Credentials');
                }
            });
        });

        $(document).on('click','.add-to-team-btn',function(){
            let empId = $(this).data('emp-id');
            $('#empIdInput').val(empId);
            $('#assignTeamModal').modal('show');
        });

         // Show/hide termination date when status changes
        $('#employment-status').on('change', function() {
            if($(this).val() === 'Terminated') {
            $('#termination-date-row').removeClass('d-none');
            } else {
            $('#termination-date-row').addClass('d-none');
            }
        });

         $('#employment-type').on('change', function() {
            if($(this).val() === 'Probationary') {
            $('#probation-end-date-row').removeClass('d-none');
            } else {
            $('#probation-end-date-row').addClass('d-none');
            }
        });


        $('#assignTeamForm').on('submit', function (e) {
            e.preventDefault();

            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]').prop('disabled', true);

            $.ajax({
                url: "{{ route('people.employees.assign-team') }}",
                type: "POST",
                data: $form.serialize(),
                success: function (response) {
                    if (response.status === 'success') {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        // Modal id is #assignTeamModal (not #assignModal —
                        // that was a typo that left the dialog open after a
                        // successful assignment).
                        $('#assignTeamModal').modal('hide');
                        // Reset form + select2 so a second assignment starts
                        // clean.
                        $form[0].reset();
                        $form.find('select.select2t-none').trigger('change');
                        // Reload to refresh the Teams row in the sidebar
                        // with the new assignment.
                        setTimeout(function () { window.location.reload(); }, 800);
                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function (xhr) {
                    console.error(xhr.responseText);
                    toastr.error("Something went wrong. Please try again.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                },
                complete: function () {
                    $submitBtn.prop('disabled', false);
                }
            });
        });

        $(document).on('click', '.change-status', function (e) {
            e.preventDefault();

            const empId = $(this).data('id');
            const currentStatus = $(this).data('status');

            $('#modal-emp-id').val(empId);

            $('#statusChangeModal').modal('show');
        });

        $('#statusChangeForm').on('submit', function(e) {
            e.preventDefault();

            $.ajax({
                url: '{{route("people.employee.change-status")}}',
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        toastr.success("Status Updated!", "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        const empId = $('#modal-emp-id').val();
                        const newStatus = $('#modal-status').val();

                        // Update the status badge. #statusFilter is now a
                        // <span>, not a <select>, so .val() would silently
                        // no-op — set the text instead, and swap the colour
                        // class to match the new status (mirrors the
                        // server-side $_statusBadge map above).
                        const $badge = $('#statusFilter');
                        const newClass = ({
                            'Active':      'badge-themeSuccess',
                            'Onboarding':  'badge-themeWarning',
                            'On Leave':    'badge-themeSkyblue',
                            'Suspended':   'badge-themeSkyblue',
                            'Inactive':    'badge-themeDanger',
                            'Terminated':  'badge-themeDanger',
                            'Resigned':    'badge-themeDanger',
                        })[newStatus] || 'badge-themeWarning';
                        $badge
                            .text(newStatus)
                            .attr('data-status', newStatus)
                            .removeClass(function (_, cls) {
                                return (cls.match(/(^|\s)badge-theme\S+/g) || []).join(' ');
                            })
                            .addClass(newClass);
                        $('#modal-status').val(newStatus);

                        // ✅ Close modal if using Bootstrap modal
                        $('#statusChangeModal').modal('hide');
                    }
                },
                error: function(xhr) {
                    // alert('Failed to update status');
                    toastr.error("Failed to update status!", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });

        // --- Activate Employee (Onboarding → Active) ----------------------
        $(document).on('click', '#activateEmployeeBtn', function () {
            $('#activate-emp-id').val($(this).data('id'));
            $('#activate-joining-date').val('');
            $('#activateEmployeeModal').modal('show');
        });

        $('#activateEmployeeForm').on('submit', function (e) {
            e.preventDefault();
            var joiningDate = $('#activate-joining-date').val();
            if (!joiningDate) {
                toastr.error('Please select the joining date.', 'Error', { positionClass: 'toast-bottom-right' });
                return;
            }
            var $btn = $(this).find('button[type="submit"]').prop('disabled', true);
            $.ajax({
                url: '{{ route("people.employees.activate") }}',
                method: 'POST',
                data: $(this).serialize(),
                success: function (response) {
                    $btn.prop('disabled', false);
                    if (response && response.success) {
                        toastr.success(response.message || 'Employee activated.', 'Success', { positionClass: 'toast-bottom-right' });
                        $('#activateEmployeeModal').modal('hide');
                        // Reload so payroll/attendance-dependent sections + the
                        // status badge reflect the now-Active employee.
                        setTimeout(function () { location.reload(); }, 1200);
                    } else {
                        toastr.error((response && response.message) || 'Could not activate employee.', 'Error', { positionClass: 'toast-bottom-right' });
                    }
                },
                error: function (xhr) {
                    $btn.prop('disabled', false);
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Could not activate employee.';
                    toastr.error(msg, 'Error', { positionClass: 'toast-bottom-right' });
                }
            });
        });

        $('.toggle-edit-save').on('click', function (e) {
            e.preventDefault();

            const card = $(this).closest('.bg-themeGrayLight');

            card.find('.view-mode').addClass('d-none');
            card.find('.edit-mode').removeClass('d-none');

            $(this).addClass('d-none');
            $(this).siblings('#btn-save').removeClass('d-none');


        });

        $('.edit-contact-btn').on('click', function (e) {
            e.preventDefault();
            $(this).closest('.card-title').siblings('.table-responsive').find('.view-mode').addClass('d-none');
            $(this).closest('.card-title').siblings('.table-responsive').find('.edit-mode').removeClass('d-none');
            $(this).addClass('d-none');
            $(this).siblings('#btn-contact-save').removeClass('d-none');
        });

        $('.edit-emergency-contact-btn').on('click', function (e) {
            e.preventDefault();
            $(this).closest('.card-title').siblings('.table-responsive').find('.view-mode').addClass('d-none');
            $(this).closest('.card-title').siblings('.table-responsive').find('.edit-mode').removeClass('d-none');
            $(this).addClass('d-none');
            $(this).siblings('#btn-emergnecy-contact-save').removeClass('d-none');
        });

        $('.edit-emp-info').on('click', function (e) {
            e.preventDefault();

            setTimeout(function() {
                if(!$('#termination-date-row').hasClass('d-none')) {
                    $('#termination-date-row .datepicker').datepicker({
                        format: 'dd/mm/yyyy',
                        autoclose: true
                    });
                }

                if(!$('#probation-end-date-row').hasClass('d-none')) {
                    $('#probation-end-date-row .datepicker').datepicker({
                        format: 'dd/mm/yyyy',
                        autoclose: true
                    });
                }
            }, 100);

            const card = $(this).closest('.bg-themeGrayLight');

            // Show editable inputs and hide view mode across all table sections
            card.find('.view-mode').addClass('d-none');
            card.find('.edit-mode').removeClass('d-none');

            // Hide edit icon and show submit button
            $(this).addClass('d-none');
            $(this).siblings('#btn-emp-info-save').removeClass('d-none');

            // Load HOD list for Reporting To dropdown
            loadHODList();
        });

        $('.edit-bank-details').on('click', function (e) {
            e.preventDefault();

            const card = $(this).closest('.bg-themeGrayLight');

            // Show editable inputs and hide view mode across all table sections
            card.find('.view-mode').addClass('d-none');
            card.find('.edit-mode').removeClass('d-none');

            // Hide edit icon and show submit button
            $(this).addClass('d-none');
            $(this).siblings('#btn-bank-details-save').removeClass('d-none');
        });

        $('.edit-edu-info').on('click', function (e) {
            e.preventDefault();

            const card = $(this).closest('.table-responsive');

            // Show editable inputs and hide view mode inside this education card
            card.find('.view-mode').addClass('d-none');
            card.find('.edit-mode').removeClass('d-none');

            // Hide edit icon and show submit button
            $(this).addClass('d-none');
            card.find('#btn-edu-info-save-' + $(this).data('id')).removeClass('d-none');
        });

        $('.edit-exp-info').on('click', function (e) {
            e.preventDefault();

            const card = $(this).closest('.table-responsive');

            // Show editable inputs and hide view mode inside this education card
            card.find('.view-mode').addClass('d-none');
            card.find('.edit-mode').removeClass('d-none');

            // Hide edit icon and show submit button
            $(this).addClass('d-none');
            card.find('#btn-exp-info-save-' + $(this).data('id')).removeClass('d-none');
        });

         $('.edit-bank-info').on('click', function (e) {
            e.preventDefault();

            const card = $(this).closest('.table-responsive');

            // Show editable inputs and hide view mode inside this education card
            card.find('.view-mode').addClass('d-none');
            card.find('.edit-mode').removeClass('d-none');

            // Hide edit icon and show submit button
            $(this).addClass('d-none');
            card.find('#btn-bank-info-save-' + $(this).data('id')).removeClass('d-none');

        });

        $('.edit-expiry-btn').on('click', function (e) {
            e.preventDefault();

            // Show all editable inputs
            $('.view-mode').addClass('d-none');
            $('.edit-mode').removeClass('d-none');

            // Hide Edit button and show Save button
            $(this).addClass('d-none');
            $('#btn-expiry-save').removeClass('d-none');

            // Initialize Datepickers
            $('.datepicker').datepicker({
                format: 'dd/mm/yyyy',
                autoclose: true,
                todayHighlight: true
            });
        });

        $('.edit-salary-details').on('click', function (e) {
            e.preventDefault();

            const card = $(this).closest('.bg-themeGrayLight');

            // Show editable inputs and hide view mode
            card.find('.view-mode').addClass('d-none');
            card.find('.edit-mode').removeClass('d-none');

            // Hide edit icon and show submit button
            $(this).addClass('d-none');
            $(this).siblings('#btn-salary-save').removeClass('d-none');
        });

        // Save salary details
        $('#btn-salary-save').on('click', function (e) {
            e.preventDefault();

            let formData = new FormData();
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
            formData.append('employee_id', '{{ $employee->id }}');
            formData.append('basic_salary', $('input[name="basic_salary"]').val());
            formData.append('basic_salary_currency', $('select[name="basic_salary_currency"]').val());
            formData.append('payment_mode', $('select[name="payment_mode"]').val());
            formData.append('pension', $('input[name="pension"]').val());
            formData.append('ewt', $('input[name="ewt"]').val());
            formData.append('entitle_service_charge', $('#entitle_service_charge').is(':checked') ? 1 : 0);
            formData.append('entitle_overtime', $('#entitle_overtime').is(':checked') ? 1 : 0);
            formData.append('entitle_public_holiday', $('#entitle_public_holiday').is(':checked') ? 1 : 0);
            formData.append('ewt_status', $('#ewt_status').is(':checked') ? 1 : 0);

            $('.allowance-row').each(function(index) {
                let id = $(this).find('input[type="hidden"]').val() || '';
                let type = $(this).find('select[name^="allowances"][name$="[type]"]').val();
                let amount = $(this).find('input[name^="allowances"][name$="[amount]"]').val();
                let amount_unit = $(this).find('select[name^="allowances"][name$="[amount_unit]"]').val();

                formData.append(`allowances[${index}][id]`, id);
                formData.append(`allowances[${index}][type]`, type);
                formData.append(`allowances[${index}][amount]`, amount);
                formData.append(`allowances[${index}][amount_unit]`, amount_unit);
            });

            $.ajax({
                url: '{{route("employee.update.salary")}}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        toastr.success('Salary details updated successfully', 'Success',{
                            positionClass: 'toast-bottom-right'
                        });

                        const earning = response.total_earning_mvr;
                        const isTINAvailable = response.tin_no !== null && response.tin_no !== '';

                        if (earning >= 30000) {
                            $('#ewt-eligibility-section').removeClass('d-none');
                            $('#tin-status-label').text(isTINAvailable ? '✅ TIN Available (Enrolled)' : '❌ No TIN (Not Enrolled)');
                        } else {
                            $('#ewt-eligibility-section').addClass('d-none');
                        }

                        setTimeout(function() {
                            location.reload();
                        }, 1200);
                    } else {
                        toastr.error('Failed to update salary details', 'Error', {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(xhr) {
                    toastr.error('Error while updating salary', 'Error', {
                        positionClass: 'toast-bottom-right'
                    });
                    console.error(xhr.responseText);
                }
            });
        });


        // Add new allowance row
        let allowanceIndex = {{ isset($employee->allowances) ? count($employee->allowances) : 0 }};

        $('#add-allowance').on('click', function() {
            const newRow = `
                 <div class="row mb-2 allowance-row">
                    <div class="col-4">
                        <select name="allowances[${allowanceIndex}][type]" class="form-select">
                            <option value="">Select Type</option>
                            @if($resort_allowances)
                                @foreach($resort_allowances as $resort_allowance)
                                    <option value="{{ $resort_allowance->id }}" {{ $resort_allowance->allowance_id == $resort_allowance->id ? 'selected' : '' }}>{{ $resort_allowance->particulars }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-3">
                        <input type="text" name="allowances[${allowanceIndex}][amount]" class="form-control" placeholder="Amount">
                    </div>
                    <div class="col-3">
                        <select name="allowances[${allowanceIndex}][amount_unit]" class="form-select">
                            <option value="USD">USD</option>
                            <option value="MVR">MVR</option>

                        </select>
                    </div>
                    <div class="col-2">
                        <button type="button" class="btn btn-danger btn-sm remove-allowance">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                    <input type="hidden" name="allowances[${allowanceIndex}][id]" >
                </div>
            `;

            $('#allowances-container').append(newRow);
            allowanceIndex++;
        });

        // Remove allowance row
        $(document).on('click', '.remove-allowance', function() {
            $(this).closest('.allowance-row').remove();
        });

        $('#btn-save').on('click', function () {
            let formData = {
                _token: $('meta[name="csrf-token"]').attr('content'), // CSRF token
                employee_id: '{{ $employee->id }}',
                title: $('[name="title"]').val(),
                first_name: $('[name="first_name"]').val(),
                last_name: $('[name="last_name"]').val(),
                gender: $('[name="gender"]').val(),
                dob: $('[name="dob"]').val(),
                marital_status: $('[name="marital_status"]').val(),
                nationality: $('[name="nationality"]').val(),
                religion: $('[name="religion"]').val(),
                blood_group: $('[name="blood_group"]').val(),
                passport_number: $('[name="passport_number"]').val(),
                nid: $('[name="nid"]').val(),
            };

            $.ajax({
                url: '{{ route("employee.update.personal") }}',
                method: 'POST',
                data: formData,
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        location.reload(); // Reload to show updated data
                        $('.datepicker').datepicker({
                            format: 'dd/mm/yyyy',
                            autoclose: true
                        });
                    } else {
                        toastr.error('Failed to update information.', "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function (xhr) {
                    toastr.error('Something went wrong.', "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                    console.log(xhr.responseText);
                }
            });
        });

        $('#btn-contact-save').on('click', function () {
            let formData = {
                _token: $('meta[name="csrf-token"]').attr('content'), // CSRF token
                employee_id: '{{ $employee->id }}',
                // Scoped to the Personal Details tab's input. Without the
                // id-suffixed selector, $('[name="personal_phone"]') also
                // matches the Employment Information tab's input and .val()
                // returned that one's stale value — so any dial-code prefix
                // typed in the Personal tab was silently dropped on save.
                personal_phone: $('#personal_phone_contact').val(),
                email: $('[name="email"]').val(),
                address_line_1: $('[name="address_line_1"]').val(),
                address_line_2: $('[name="address_line_2"]').val(),
                city: $('[name="city"]').val(),
                state: $('[name="state"]').val(),
                country: $('[name="country"]').val(),
                zip: $('[name="zip"]').val(),
                present_address: $('[name="present_address"]').val(),
            };

            $.ajax({
                url: '{{ route("employee.update.contacts") }}',
                method: 'POST',
                data: formData,
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        location.reload(); // Reload to show updated data
                    } else {
                        toastr.error('Failed to update information.', "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function (xhr) {
                    toastr.error('Something went wrong.', "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                    console.log(xhr.responseText);
                }
            });
        });

        $('#btn-emergnecy-contact-save').on('click', function () {
            let formData = {
                _token: $('meta[name="csrf-token"]').attr('content'), // CSRF token
                employee_id: '{{ $employee->id }}',
                emg_cont_first_name: $('[name="emg_cont_first_name"]').val(),
                emg_cont_last_name: $('[name="emg_cont_last_name"]').val(),
                emg_cont_no: $('[name="emg_cont_no"]').val(),
                emg_cont_relationship: $('[name="emg_cont_relationship"]').val(),
                emg_cont_email: $('[name="emg_cont_email"]').val(),
                emg_cont_current_address: $('[name="emg_cont_current_address"]').val(),
            };

            $.ajax({
                url: '{{ route("employee.update.emergency-contacts") }}',
                method: 'POST',
                data: formData,
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        location.reload(); // Reload to show updated data
                    } else {
                        toastr.error('Failed to update information.', "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function (xhr) {
                    toastr.error('Something went wrong.', "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                    console.log(xhr.responseText);
                }
            });
        });

        $('#btn-additionl-info-save').on('click', function () {
            let formData = new FormData();

            formData.append('_token', $('meta[name="csrf-token"]').attr('content')); // CSRF token
            formData.append('employee_id', '{{ $employee->id }}');
            formData.append('leave_destination', $('[name="leave_destination"]').val());

            // Handle biometric file
            let biometricFile = $('[name="biometric_file"]')[0]?.files[0];
            if (biometricFile) {
                formData.append('biometric_file', biometricFile);
            }

            // Handle dynamic languages
            $('#language-rows tr').each(function (index) {
                let lang = $(this).find('input[name^="languages"]').val();
                let level = $(this).find('select[name^="languages"]').val();
                if(lang && level) {
                    formData.append(`languages[${index}][language]`, lang);
                    formData.append(`languages[${index}][proficiency_level]`, level);
                }
            });

            $.ajax({
                url: '{{ route("employee.update.additional-info") }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        location.reload(); // Reload to show updated data
                    } else {
                        toastr.error('Failed to update information.', "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function (xhr) {
                    toastr.error('Something went wrong.', "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                    console.log(xhr.responseText);
                }
            });
        });

        // ----------------------------------------------------------------
        // EWT Status → TIN required flow. When the user toggles EWT to
        // Enrolled in Salary Details, the TIN field (in Employment
        // Information above) becomes mandatory. We:
        //   1. show an inline notice next to the TIN input,
        //   2. focus + highlight the TIN field so the user can type
        //      without scrolling around,
        //   3. block the Employment save AJAX if EWT is on and TIN is
        //      still empty (the server-side save accepts whatever, so
        //      gating is purely client-side).
        // ----------------------------------------------------------------
        function syncEwtTinRequirement() {
            var ewtEnabled = $('#ewt_status').is(':checked');
            var $tin = $('#tin-input');
            var $notice = $('#tin-required-notice');
            if (ewtEnabled) {
                $notice.removeClass('d-none');
                $tin.attr('required', 'required').addClass('border-danger');
                // Open the Employment edit row if it's not already open
                // and pull focus to TIN so the user knows where to type.
                if ($tin.hasClass('d-none')) {
                    $('.edit-emp-info').trigger('click');
                }
                setTimeout(function () { $tin.focus(); }, 50);
            } else {
                $notice.addClass('d-none');
                $tin.removeAttr('required').removeClass('border-danger');
            }
            updateEwtTinSaveGate();
        }

        // Hard gate on the Employment save flow:
        // - Disables BOTH the row-level Save button (#btn-emp-info-save)
        //   AND the modal's confirmation button (#confirm-emp-info-save)
        //   whenever EWT=on AND TIN is blank.
        // - Adds a `title` tooltip so the disabled state isn't silent
        //   ("Enter the TIN — required when EWT Enrolled").
        // - Re-runs on EWT toggle and TIN input so the button comes back
        //   alive the instant the user fills the field, without needing
        //   a focus/blur or click.
        function updateEwtTinSaveGate() {
            var ewtEnabled = $('#ewt_status').is(':checked');
            var tinVal     = ($('#tin-input').val() || '').trim();
            var block      = ewtEnabled && !tinVal;
            var $saveBtns  = $('#btn-emp-info-save, #confirm-emp-info-save');
            if (block) {
                $saveBtns.prop('disabled', true)
                    .addClass('disabled')
                    .attr('title', 'Enter the TIN — required when EWT Status is Enrolled.');
            } else {
                $saveBtns.prop('disabled', false)
                    .removeClass('disabled')
                    .removeAttr('title');
            }
        }

        $(document).on('change', '#ewt_status', syncEwtTinRequirement);
        // Live update as the user types into TIN — disable flips on/off
        // without needing the user to click anywhere.
        $(document).on('input change', '#tin-input', updateEwtTinSaveGate);
        // Run once on DOM-ready so the initial render reflects the
        // current state (handles the "EWT already on, TIN still blank
        // from the DB" load case).
        $(function () { updateEwtTinSaveGate(); });

        // Employment data drives payroll, benefit-grid, reporting + tax
        // — surface a sensitive-edit confirmation modal before firing
        // the AJAX. The actual PUT lives in `submitEmploymentUpdate`
        // and only runs once the user clicks "Yes, Save Changes" in
        // #employmentEditConfirmModal.
        function submitEmploymentUpdate() {
            let formData = {
                _token: $('meta[name="csrf-token"]').attr('content'), // CSRF token
                employee_id: '{{ $employee->id }}',
                Emp_id: $('[name="Emp_id"]').val(),
                email: $('[name="email"]').val(),
                // Scoped to the Employment Information input — see comment
                // on #btn-contact-save's personal_phone selector above.
                personal_phone: $('#personal_phone_employment').val(),
                status: $('[name="status"]').val(),
                joining_date: $('[name="joining_date"]').val(),
                benefit_grid_level: $('[name="benefit_grid_level"]').val(),
                tin: $('[name="tin"]').val(),
                probation_end_date: $('[name="probation_end_date"]').val(),
                contract_type: $('[name="contract_type"]').val(),
                termination_date: $('[name="termination_date"]').val(),
                Position_id: $('[name="Position_id"]').val(),
                Section_id: $('[name="Section_id"]').val(),
                Dept_id: $('[name="Dept_id"]').val(),
                division_id: $('[name="division_id"]').val(),
                reporting_to: $('[name="reporting_to"]').val(),
            };

            var $confirmBtn = $('#confirm-emp-info-save').prop('disabled', true)
                .html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving…');

            $.ajax({
                url: '{{ route("employee.update.employment-data") }}',
                method: 'POST',
                data: formData,
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        $('#employmentEditConfirmModal').modal('hide');
                        // Refresh the change-log so the diff that just
                        // landed appears at the top before the reload
                        // race (the full-page reload below still fires).
                        loadEmploymentLogs(1);
                        location.reload(); // Reload to show updated data
                        $('.datepicker').datepicker({
                            format: 'dd/mm/yyyy',
                            autoclose: true
                        });
                    } else {
                        toastr.error('Failed to update information.', "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                        $confirmBtn.prop('disabled', false).html('Yes, Save Changes');
                    }
                },
                error: function (xhr) {
                    // Surface server-side validation errors (422) verbatim
                    // so HR sees the actual rule that bit them (e.g.
                    // "TIN must be 10 digits …", "TIN is required when
                    // EWT Status is Enrolled."). Falls back to a generic
                    // message for non-validation errors.
                    var msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Something went wrong.';
                    toastr.error(msg, "Error", { positionClass: 'toast-bottom-right' });
                    console.log(xhr.responseText);
                    $confirmBtn.prop('disabled', false).html('Yes, Save Changes');
                }
            });
        }

        $('#btn-emp-info-save').on('click', function () {
            // Client-side gate: EWT Enrolled requires a TIN. Block the
            // confirmation modal from opening if EWT is on but TIN is
            // empty so the user is forced to fill it before the
            // sensitive-data confirmation flow.
            var ewtEnabled = $('#ewt_status').is(':checked');
            var tinVal = ($('#tin-input').val() || '').trim();
            if (ewtEnabled && !tinVal) {
                toastr.error('Enter the TIN before saving — required when EWT Status is Enrolled.',
                    'TIN required', { positionClass: 'toast-bottom-right' });
                $('#tin-input').focus();
                $('#tin-required-notice').removeClass('d-none');
                return;
            }
            // Open the sensitive-edit confirmation modal instead of
            // posting straight to the server. #confirm-emp-info-save
            // below performs the actual save once confirmed.
            $('#employmentEditConfirmModal').modal('show');
        });

        $(document).on('click', '#confirm-emp-info-save', function () {
            submitEmploymentUpdate();
        });

        // ----------------------------------------------------------------
        // Employment Change Log — AJAX-loaded into #employment-logs-wrap.
        // Loaded on DOM ready (so the first render of the Employment tab
        // has data) and re-loaded after a successful Employment save so
        // the change just made appears at the top without a hard reload
        // race.
        // ----------------------------------------------------------------
        function loadEmploymentLogs(page) {
            var $wrap = $('#employment-logs-wrap');
            if (!$wrap.length) return;
            var empId = $wrap.data('employee-id');
            if (!empId) return;
            $.ajax({
                url: '{{ route("people.employees.employment-logs") }}',
                method: 'GET',
                data: { employee_id: empId, page: page || 1 },
                success: function (response) {
                    if (response && response.success) {
                        $wrap.html(response.html);
                    } else {
                        $wrap.html('<div class="text-muted text-center py-3">No change-log data.</div>');
                    }
                },
                error: function () {
                    $wrap.html('<div class="text-danger text-center py-3">Could not load change log.</div>');
                }
            });
        }

        // Initial load.
        loadEmploymentLogs(1);

        // Intercept pagination links inside the change-log wrapper so we
        // stay on the page (instead of navigating to ?page=N at the top).
        $(document).on('click', '#employment-logs-wrap .pagination a', function (e) {
            e.preventDefault();
            var href = $(this).attr('href') || '';
            var match = href.match(/[?&]page=(\d+)/);
            var page = match ? parseInt(match[1], 10) : 1;
            loadEmploymentLogs(page);
        });


        $('#btn-expiry-save').on('click', function () {
            let documentIds = $("input[name='document_ids[]']").map(function(){ return $(this).val(); }).get();
            let documentTitles = $("input[name='document_titles[]']").map(function(){ return $(this).val(); }).get();
            let expiryDates = $("input[name='expiry_dates[]']").map(function(){ return $(this).val(); }).get();

            let formData = {
                _token: $('meta[name="csrf-token"]').attr('content'),
                employee_id: '{{ $employee->id }}',
                document_ids: documentIds,
                document_titles: documentTitles,
                expiry_dates: expiryDates
            };

            $.ajax({
                url: '{{ route("employee.update.expiryDocuments") }}',
                method: 'POST',
                data: formData,
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                        setTimeout(function () { location.reload(); }, 1000);
                    } else {
                        toastr.error(response.message, "Error", { positionClass: 'toast-bottom-right' });
                    }
                },
                error: function (xhr) {
                    let errorMsg = 'Something went wrong!';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    toastr.error(errorMsg, "Error", { positionClass: 'toast-bottom-right' });
                    console.log(xhr.responseText);
                }
            });
        });

        $(document).on('click', '[id^=btn-edu-info-save-]', function (e) {
            e.preventDefault();

            const button = $(this);
            const card = button.closest('.table-responsive');
            const educationId = button.attr('id').replace('btn-edu-info-save-', '');

            const educationLevel = card.find('input[name="education_level"]').val();
            const institutionName = card.find('input[name="institution_name"]').val();
            const degree = card.find('input[name="degree"]').val();
            const attendancePeriod = card.find('input[name="attendance_period"]').val();
            const location = card.find('input[name="location"]').val();
            const fieldOfStudy = card.find('input[name="field_of_study"]').val();

            let certification = card.find('input[name="certification"]')[0]?.files[0]; // corrected here
            let formData = new FormData(); // must use FormData if file uploading!

            formData.append('education_level', educationLevel);
            formData.append('institution_name', institutionName);
            formData.append('field_of_study', fieldOfStudy);
            formData.append('degree', degree);
            formData.append('attendance_period', attendancePeriod);
            formData.append('location', location);
            formData.append('employee_id', '{{ $employee->id }}');
            formData.append('_token', '{{ csrf_token() }}');

            if (certification) {
                formData.append('certification', certification);
            }

            let url = "";

            if (educationId.startsWith('new-')) {
                url = "{{ route('employee.store.eduDetails') }}";
            } else {
                url = "{{ route('employee.update.eduDetails', ['id' => '__id__']) }}".replace('__id__', educationId);
            }

            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                processData: false,  // important for FormData
                contentType: false,  // important for FormData
                success: function(response) {
                    // ✅ Update view-mode spans
                    card.find('span.view-mode').each(function() {
                        const th = $(this).closest('tr').find('th').text().trim();

                        if (th.includes('Education Level') || th.includes('Education Level/Type')) {
                            $(this).text(educationLevel);
                        }
                        if (th.includes('Institution Name')) {
                            $(this).text(institutionName);
                        }
                        if (th.includes('Field of Study') || th.includes('Field of Study / Major')) {
                            $(this).text(fieldOfStudy);
                        }
                        if (th.includes('Degree') || th.includes('Degree/Certificate Earned')) {
                            $(this).text(degree);
                        }
                        if (th.includes('Attendance Period')) {
                            $(this).text(attendancePeriod || 'N/A');
                        }
                        if (th.includes('Location')) {
                            $(this).text(location || 'N/A');
                        }
                    });

                    // Switch back to view mode
                    card.find('.view-mode').removeClass('d-none');
                    card.find('.edit-mode').addClass('d-none');

                    button.addClass('d-none');
                    card.find('.edit-edu-info').removeClass('d-none');

                    toastr.success(response.message || 'Saved successfully.', "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                },
                error: function(xhr) {
                    toastr.error('Something went wrong!', "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });

        $(document).on('click', '#add-new-bankDetails', function() {
            const newId = 'new-' + Date.now(); // temporary ID based on timestamp

            const newBankHtml = `
            <div class="col-lg-6 bank-details-card">
                <div class="table-responsive position-relative border rounded p-3 bg-white">
                    <button type="submit" class="btn btn-themeBlue btn-sm" id="btn-bank-info-save-${newId}">Save</button>
                    <button type="button" id="btn-bank-info-remove-${newId}" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2 remove-bank-details"> Remove
                    </button>

                    <table class="table table-lable mb-1">
                        <tbody>
                            <tr>
                                <th>Bank Name:</th>
                                <td>
                                    <span class="view-mode d-none"></span>
                                    <input type="text" name="bank_name" class="form-control edit-mode" value="">
                                </td>
                            </tr>
                            <tr>
                                <th>Bank Location/Branch:</th>
                                <td>
                                    <span class="view-mode d-none"></span>
                                    <input type="text" name="bank_branch" class="form-control edit-mode">
                                </td>
                            </tr>
                            <tr>
                                <th>Account Type:</th>
                                <td>
                                    <span class="view-mode d-none"></span>
                                    <input type="text" name="account_type" class="form-control edit-mode">
                                </td>
                            </tr>
                            <tr>
                                <th>IFSC/SWIFT/BIC Code:</th>
                                <td>
                                    <span class="view-mode d-none"></span>
                                    <input type="text" name="IFSC_BIC" class="form-control edit-mode"
                                     pattern="^[A-Za-z0-9]{6,11}$" maxlength="11" placeholder="Enter IFSC/SWIFT/BIC Code"
                                     title="6-11 alphanumeric characters"  required>
                                </td>
                            </tr>
                            <tr>
                                <th>Account Holder's Name:</th>
                                <td>
                                    <span class="view-mode d-none"></span>
                                    <input type="text" name="account_holder_name" class="form-control edit-mode">
                                </td>
                            </tr>
                            <tr>
                                <th>Account Number:</th>
                                <td>
                                    <span class="view-mode d-none"></span>
                                    <input type="text" name="account_no" class="form-control edit-mode"
                                        pattern="^[0-9]{6,20}$"
                                        maxlength="20"
                                        minlength="6"
                                        placeholder="Enter Account Number"
                                        title="Account number must be 6-20 digits"
                                        required>
                                </td>
                            </tr>
                            <tr>
                                <th>Currency:</th>
                                <td>
                                    <span class="view-mode d-none"></span>
                                    <select name="currency" class="form-control edit-mode">
                                        <option value="USD">USD</option>
                                        <option value="MVR">MVR</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th>IBAN:</th>
                                <td>
                                    <span class="view-mode d-none"></span>
                                    <input type="text" name="IBAN" class="form-control edit-mode">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            `;

            // Append the new block inside the last row (you can target more specifically if needed)
            $(this).closest('.bg-themeGrayLight').find('p').hide();
            $(this).closest('.bg-themeGrayLight').find('.row.g-xxl-4:last').append(newBankHtml);
        });

        // Add event handler for removing bank details
        $(document).on('click', '.remove-bank-details', function() {
            $(this).closest('.bank-details-card').remove();
        });

        $(document).on('click', '#add-new-education', function() {
            const newId = 'new-' + Date.now(); // temporary ID based on timestamp

            const newEducationHtml = `
            <div class="col-lg-6">
                <div class="table-responsive position-relative border rounded p-3 bg-white">
                    <button type="submit" class="btn btn-themeBlue btn-sm" id="btn-edu-info-save-${newId}">Save</button>

                    <table class="table table-lable mb-1">
                        <tbody>
                            <tr>
                                <th>Education Level/Type:</th>
                                <td>
                                    <span class="view-mode d-none"></span>
                                    <input type="text" name="education_level" class="form-control edit-mode" value="" required>
                                </td>
                            </tr>
                            <tr>
                                <th>Institution Name:</th>
                                <td>
                                    <span class="view-mode d-none"></span>
                                    <input type="text" name="institution_name" class="form-control edit-mode" value="">
                                </td>
                            </tr>
                            <tr>
                                <th>Field of Study / Major:</th>
                                <td>
                                    <span class="view-mode d-none"></span>
                                    <input type="text" name="field_of_study" class="form-control edit-mode" value="">
                                </td>
                            </tr>
                            <tr>
                                <th>Degree/Certificate Earned:</th>
                                <td>
                                    <span class="view-mode d-none"></span>
                                    <input type="text" name="degree" class="form-control edit-mode" value="">
                                </td>
                            </tr>
                            <tr>
                                <th>Attendance Period:</th>
                                <td>
                                    <span class="view-mode d-none"></span>
                                    <input type="text" name="attendance_period" class="form-control edit-mode" value="" placeholder="e.g. 2015-2019">
                                </td>
                            </tr>
                            <tr>
                                <th>Location:</th>
                                <td>
                                    <span class="view-mode d-none"></span>
                                    <input type="text" name="location" class="form-control edit-mode" value="">
                                </td>
                            </tr>
                            <tr>
                                <th>Document Upload / Certificate Attachment</th>
                               <td>
                                    <input type="file" name="certification" class="form-control edit-mode">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            `;

            // Append the new block inside the last row (you can target more specifically if needed)
            $(this).closest('.bg-themeGrayLight').find('p').hide();
            $(this).closest('.bg-themeGrayLight').find('.row.g-xxl-4:last').append(newEducationHtml);
        });

         $(document).on('click', '[id^=btn-bank-info-save-]', function (e) {
            e.preventDefault();
            const button = $(this);
            const card = button.closest('.table-responsive');
            const bankId = button.attr('id').replace('btn-bank-info-save-', '');

            const bank_name = card.find('input[name="bank_name"]').val();
            const bank_branch = card.find('input[name="bank_branch"]').val();
            const account_type = card.find('[name="account_type"]').val();
            const IFSC_BIC = card.find('input[name="IFSC_BIC"]').val();
            const account_holder_name = card.find('input[name="account_holder_name"]').val();
            const account_no = card.find('input[name="account_no"]').val();
            const currency = card.find('select[name="currency"]').val();
            const IBAN = card.find('input[name="IBAN"]').val();


            let formData = new FormData(); // must use FormData if file uploading!

            formData.append('bank_name', bank_name);
            formData.append('bank_branch', bank_branch);
            formData.append('account_type', account_type);
            formData.append('IFSC_BIC', IFSC_BIC);
            formData.append('account_holder_name', account_holder_name);
            formData.append('account_no', account_no);
            formData.append('currency', currency);
            formData.append('IBAN', IBAN);

            formData.append('employee_id', '{{ $employee->id }}');
            formData.append('_token', '{{ csrf_token() }}');

            let url = "";

            if (bankId.startsWith('new-')) {
                url = "{{ route('employee.store.bankDetails') }}";
            } else {
                url = "{{ route('employee.update.bankDetails', ['id' => '__id__']) }}".replace('__id__', bankId);
            }

            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                processData: false,  // important for FormData
                contentType: false,  // important for FormData
                success: function(response) {
                    // ✅ Update view-mode spans
                    card.find('span.view-mode').each(function() {
                        const th = $(this).closest('tr').find('th').text().trim();

                        if (th.includes('Bank Name')) {
                            $(this).text(bank_name);
                        }
                        if (th.includes('Bank Location/Branch')) {
                            $(this).text(bank_branch);
                        }
                        if (th.includes('Account Type')) {
                            $(this).text(account_type);
                        }
                        if (th.includes('IFSC/SWIFT/BIC Code')) {
                            $(this).text(IFSC_BIC);
                        }
                        if (th.includes("Account Holder's Name")) {
                            $(this).text(account_holder_name || 'N/A');
                        }
                        if (th.includes('Account Number')) {
                            $(this).text(account_no || 'N/A');
                        }
                        if (th.includes('Currency')) {
                            $(this).text(currency || 'N/A');
                        }
                        if (th.includes('IBAN')) {
                            $(this).text(IBAN || 'N/A');
                        }
                    });

                    // Switch back to view mode
                    card.find('.view-mode').removeClass('d-none');
                    card.find('.edit-mode').addClass('d-none');

                    button.addClass('d-none');
                    $('#btn-bank-info-remove-' + bankId).addClass('d-none');
                    card.find('#btn-bank-info-remove-' + bankId).removeClass('d-none');
                    card.find('.edit-bank-info').removeClass('d-none');

                    toastr.success(response.message || 'Saved successfully.', "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                },
                error: function(xhr) {
                    toastr.error('Something went wrong!', "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });


        $(document).on('click', '[id^=btn-exp-info-save-]', function (e) {
            e.preventDefault();

            const button = $(this);
            const card = button.closest('.table-responsive');
            const expId = button.attr('id').replace('btn-exp-info-save-', '');

            const company_name = card.find('input[name="company_name"]').val();
            const job_title = card.find('input[name="job_title"]').val();
            const employment_type = card.find('[name="employment_type"]').val();
            const duration = card.find('input[name="duration"]').val();
            const location = card.find('input[name="location"]').val();
            const reason_for_leaving = card.find('input[name="reason_for_leaving"]').val();
            const reference_name = card.find('input[name="reference_name"]').val();
            const reference_contact = card.find('input[name="reference_contact"]').val();

            let formData = new FormData(); // must use FormData if file uploading!

            formData.append('company_name', company_name);
            formData.append('job_title', job_title);
            formData.append('employment_type', employment_type);
            formData.append('duration', duration);
            formData.append('location', location);
            formData.append('reason_for_leaving', reason_for_leaving);
            formData.append('reference_name', reference_name);
            formData.append('reference_contact', reference_contact);

            formData.append('employee_id', '{{ $employee->id }}');
            formData.append('_token', '{{ csrf_token() }}');

            let url = "";

            if (expId.startsWith('new-')) {
                url = "{{ route('employee.store.expDetails') }}";
            } else {
                url = "{{ route('employee.update.expDetails', ['id' => '__id__']) }}".replace('__id__', expId);
            }

            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                processData: false,  // important for FormData
                contentType: false,  // important for FormData
                success: function(response) {
                    // ✅ Update view-mode spans
                    card.find('span.view-mode').each(function() {
                        const th = $(this).closest('tr').find('th').text().trim();

                        if (th.includes('Company Name')) {
                            $(this).text(company_name);
                        }
                        if (th.includes('Job Title / Position') || th.includes('Job Title')) {
                            $(this).text(job_title);
                        }
                        if (th.includes('Employment Type')) {
                            $(this).text(employment_type);
                        }
                        if (th.includes('Duration of Employment')) {
                            $(this).text(duration);
                        }
                        if (th.includes('Location')) {
                            $(this).text(location || 'N/A');
                        }
                        if (th.includes('Reason for Leaving')) {
                            $(this).text(reason_for_leaving || 'N/A');
                        }
                        if (th.includes('Reference Name')) {
                            $(this).text(reference_name || 'N/A');
                        }
                        if (th.includes('Reference Contact Information')) {
                            $(this).text(reference_contact || 'N/A');
                        }
                    });

                    // Switch back to view mode
                    card.find('.view-mode').removeClass('d-none');
                    card.find('.edit-mode').addClass('d-none');

                    button.addClass('d-none');
                    card.find('.edit-exp-info').removeClass('d-none');

                    toastr.success(response.message || 'Saved successfully.', "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                },
                error: function(xhr) {
                    toastr.error('Something went wrong!', "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });

        $(document).on('click', '#add-new-experience', function() {
            const newId = 'new-' + Date.now(); // temporary ID based on timestamp

            const newExperianceHtml = `
            <div class="col-lg-6">
                <div class="table-responsive position-relative border rounded p-3 bg-white">
                    <button type="submit" class="btn btn-themeBlue btn-sm" id="btn-exp-info-save-${newId}">Save</button>

                    <table class="table table-lable mb-1">
                        <tbody>
                            <tr>
                                <th>Company Name:</th>
                                <td>
                                    <span class="view-mode d-none"></span>
                                    <input type="text" name="company_name" class="form-control edit-mode" value="">
                                </td>
                            </tr>
                            <tr>
                                <th>Job Title / Position:</th>
                                <td>
                                    <span class="view-mode d-none"></span>
                                    <input type="text" name="job_title" class="form-control edit-mode" value="">
                                </td>
                            </tr>
                            <tr>
                                <th>Employment Type:</th>
                                <td>
                                    <span class="view-mode d-none"></span>

                                    <select name="employment_type" class="form-select edit-mode d-none">
                                        <option value="">Select employment_type</option>
                                        <option value="Full-Time">Full-Time</option>
                                        <option value="Part-Time">Part-Time</option>
                                        <option value="Contract">Contract</option>
                                        <option value="Casual">Casual</option>
                                        <option value="Probationary">Probationary</option>
                                        <option value="Internship">Internship</option>
                                        <option value="Temporary">Temporary</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th>Duration of Employment:</th>
                                <td>
                                    <span class="view-mode d-none"></span>
                                    <input type="text" name="duration" class="form-control edit-mode" value="" placeholder="e.g. 01/2015-01/2019">
                                </td>
                            </tr>
                            <tr>
                                <th>Location:</th>
                                <td>
                                    <span class="view-mode d-none"></span>
                                    <input type="text" name="location" class="form-control edit-mode" value="">
                                </td>
                            </tr>
                            <tr>
                                <th>Reason for Leaving:</th>
                                <td>
                                    <span class="view-mode d-none"></span>
                                    <input type="text" name="reason_for_leaving" class="form-control edit-mode" value="">
                                </td>
                            </tr>
                            <tr>
                                <th>Reference Name</th>
                               <td>
                                    <input type="text" name="reference_name" class="form-control edit-mode">
                                </td>
                            </tr>
                            <tr>
                                <th>Reference Contact Information:</th>
                                <td>
                                    <span class="view-mode d-none"></span>
                                    <input type="number" name="reference_contact" class="form-control edit-mode" value=""
                                      data-parsley-pattern="^\d{10,15}$"
                                    data-parsley-required-message="Reference contact is required."
                                    data-parsley-pattern-message="Please enter a valid contact number (10-15 digits).">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            `;

            // Append the new block inside the last row (you can target more specifically if needed)
            $(this).closest('.bg-themeGrayLight').find('p').hide();

            $(this).closest('.bg-themeGrayLight').find('.row.g-xxl-4:last').append(newExperianceHtml);
        });
    });
    document.addEventListener("DOMContentLoaded", function () {
        const card = document.querySelector("#additionInfo");
        const editBtn = card.querySelector(".edit-addition-info");
        const saveBtn = card.querySelector("#btn-additionl-info-save");

        editBtn.addEventListener("click", function (e) {
            e.preventDefault();

            // Show edit-mode elements within this card only
            card.querySelectorAll(".edit-mode").forEach(el => {
                el.classList.remove("d-none");
            });

            // Hide view-mode elements within this card only
            card.querySelectorAll(".view-mode").forEach(el => {
                el.classList.add("d-none");
            });

            // Show Submit button
            saveBtn.classList.remove("d-none");
            editBtn.classList.add("d-none");
        });
    });
    document.addEventListener("DOMContentLoaded", function () {
        let languageIndex = {{ $employee->language ? $employee->language->count() : 0 }};

        document.getElementById('add-language').addEventListener('click', function () {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><input type="text" name="languages[${languageIndex}][language]" class="form-control" /></td>
                <td>

                    <select class="form-select select2t-none proficiency-level-select" name="languages[${languageIndex }][proficiency_level]">
                        <option value="" selected disabled readonly>Select Level</option>
                        <option value="Beginner"  >Beginner</option>
                        <option value="Intermediate" >Intermediate</option>
                        <option value="Advanced"  >Advanced</option>
                        <option value="Fluent"  >Fluent</option>
                        <option value="Native"  >Native</option>
                    </select>
                </td>
                <td><button type="button" class="btn btn-danger btn-sm remove-language">Remove</button></td>
            `;
            document.getElementById('language-rows').appendChild(row);
            languageIndex++;
        });

        document.addEventListener('click', function (e) {
            if (e.target && e.target.classList.contains('remove-language')) {
                e.target.closest('tr').remove();
            }
        });
    });
</script>
<script>
    $(document).ready(function () {
  // 1. PRELOAD on page load based on current division/department/section
    const currentDivision = $('#department-select').data('current-division');
    const currentDepartment = $('#department-select').data('current-department');

    if (currentDivision) {
        $.ajax({
            url: "{{ route('people.getDepartmentsByDivision') }}",
            type: "GET",
            data: { division_id: currentDivision },
            success: function (res) {
                if (res.success) {
                    $('#department-select').empty().append('<option value="">Select Department</option>');
                    res.departments.forEach(dep => {
                        $('#department-select').append(`<option value="${dep.id}" ${dep.id == currentDepartment ? 'selected' : ''}>${dep.name}</option>`);
                    });

                    preloadSectionsAndPositions(currentDepartment);
                }
            }
        });
    }

    function preloadSectionsAndPositions(departmentId) {
        const currentSection = $('#section-select').data('current-section');
        const currentPosition = $('#position-select').data('current-position');

        if (departmentId) {
            $.ajax({
                url: "{{ route('people.getSectionByDepartment') }}",
                type: "GET",
                data: { department_id: departmentId },
                success: function (res) {
                    if (res.success) {
                        $('#section-select').empty().append('<option value="">Select Section</option>');
                        if (res.sections.length > 0) {
                            res.sections.forEach(sec => {
                                $('#section-select').append(`<option value="${sec.id}" ${sec.id == currentSection ? 'selected' : ''}>${sec.name}</option>`);
                            });

                            if (currentSection) {
                                // Load positions under section
                                loadPositions({ section_id: currentSection }, currentPosition);
                            } else {
                                // No section selected, load positions directly from department
                                loadPositions({ department_id: departmentId }, currentPosition);
                            }
                        } else {
                            // No sections at all, load positions from department
                            loadPositions({ department_id: departmentId }, currentPosition);
                        }
                    }
                }
            });
        }
    }

    function loadPositions(params, selectedId = null) {
        $.ajax({
            url: '{{ route("people.getPositionBySection") }}',
            type: 'GET',
            data: params,
            success: function (res) {
                $('#position-select').empty().append('<option value="">Select Position</option>');
                res.positions.forEach(pos => {
                    $('#position-select').append(`<option value="${pos.id}" ${pos.id == selectedId ? 'selected' : ''}>${pos.position_title}</option>`);
                });
            }
        });
    }

    // ===== ON CHANGE EVENTS =====

    $('.devision-select').on('change', function () {
        const divisionId = $(this).val();
        $('#department-select').empty().append('<option value="">Select Department</option>');
        $('#section-select').empty().append('<option value="">Select Section</option>');
        $('#position-select').empty().append('<option value="">Select Position</option>');

        if (divisionId) {
            $.ajax({
                url: "{{ route('people.getDepartmentsByDivision') }}",
                type: "GET",
                data: { division_id: divisionId },
                success: function (response) {
                    if (response.success) {
                        response.departments.forEach(dept => {
                            $('#department-select').append(`<option value="${dept.id}">${dept.name}</option>`);
                        });
                    }
                }
            });
        }
    });

    $('#department-select').on('change', function () {
        const departmentId = $(this).val();
        $('#section-select').empty().append('<option value="">Select Section</option>');
        $('#position-select').empty().append('<option value="">Select Position</option>');

        // Reload HOD list when department changes
        loadHODList();

        if (departmentId) {
            $.ajax({
                url: "{{ route('people.getSectionByDepartment') }}",
                type: "GET",
                data: { department_id: departmentId },
                success: function (response) {
                    if (response.success) {
                        if (response.sections.length > 0) {
                            let html = '<option value="">Select Section</option>';
                            response.sections.forEach(section => {
                                html += `<option value="${section.id}">${section.name}</option>`;
                            });
                            $('#section-select').html(html).trigger('change');

                            // Wait for section to be selected before loading positions
                        } else {
                            // No sections, load positions directly from department
                            loadPositions({ department_id: departmentId });
                        }
                    }
                }
            });
        }
    });

    $('#section-select').on('change', function() {
        let sectionId = $(this).val();
        $('#position-select').html('<option></option>').trigger('change');
        if (!sectionId) return;
        loadPositions({ section_id: sectionId });
    });


    $('#position-select').on('change', function () {
        let positionId = $(this).val();
        $('#benefit_grid_level').empty().append('<option></option>');

        if (!positionId) return;

        $.ajax({
            url: '{{ route("people.getBenefitGridByPosition") }}',
            type: 'GET',
            data: { position_id: positionId },
            success: function (res) {
                if (res && res.benfitGrid_emp_id) {
                    let html = '<option>Select Employee Grid</option>';
                    html += `<option value="${res.benfitGrid_emp_id}" selected>${res.emp_grade_name}</option>`;

                    $('#benefit_grid_level').html(html).trigger('change');
                }
            }
        });
    });
});

</script>
<script>
    $(document).ready(function() {
        // Initialize Select2 and Parsley validation
            initSelect2AndValidation();
            initParsleyValidation();
        });

        function initSelect2AndValidation() {
            if ($.fn.select2 && $.fn.parsley) {
                // Initialize Select2
                $(".select2t-none").select2();

                // Add Parsley validation specifically for Select2
                $(".select2t-none").on('change', function() {
                    $(this).parsley().validate();
                });

                // Ensure Select2 trigger changes in Parsley
                $(".select2t-none").on('select2:select', function() {
                    $(this).trigger('change');
                });
            }
        }

        function initParsleyValidation() {
            if ($.fn.parsley) {
                // Initialize Parsley on the form
                $('#msform').parsley({
                    errorClass: 'is-invalid',
                    successClass: 'is-valid',
                    errorsWrapper: '<div class="invalid-feedback"></div>',
                    errorTemplate: '<div></div>',
                    trigger: 'change'
                });

                // Custom Parsley validators
                window.Parsley.addValidator('mobile_number', {
                    validateString: function(value) {
                        // Strip optional + and any whitespace / dashes /
                        // brackets so HR can paste numbers in any common
                        // format (e.g. "+91 90987-65432" or "(960) 9123456").
                        // ITU-T E.164 allows up to 15 digits; minimum 7
                        // covers short country numbers like Maldives 7-digit
                        // formats.
                        var stripped = String(value || '').replace(/[\s()\-]/g, '').replace(/^\+/, '');
                        return /^[0-9]{7,15}$/.test(stripped);
                    },
                    messages: {
                        en: 'Please enter a valid mobile number (7–15 digits, optionally prefixed with +country code).'
                    }
                });

                window.Parsley.addValidator('passport_no', {
                    validateString: function(value) {
                        return /^[A-Z]{1,2}[0-9]{6,9}$/.test(value);
                    },
                    messages: {
                        en: 'Please enter a valid passport number. It should start with 1-2 uppercase letters followed by 6-9 digits.'
                    }
                });
                window.Parsley.addValidator('pin_code', {
                    validateString: function(value) {
                        // Assumes 6-digit PIN code (modify for specific country requirements)
                        return /^\d{6}$/.test(value);
                    },
                    messages: {
                        en: 'Please enter a valid 6-digit PIN code.'
                    }
                });

                window.Parsley.addValidator('validateScript', {
                    validateString: function(value) {
                        // Pattern to match any <script> tags, even with attributes or content
                        const scriptTagPattern = /<\s*script\b[^>]*>(.*?)<\s*\/\s*script\s*>/gi;
                        return !scriptTagPattern.test(value);  // Return true if no script tags are found, false otherwise
                    },
                    messages: {
                        en: 'Script tags are not allowed.'
                    }
                });

                // Add a custom validator for email validation in Parsley
                window.Parsley.addValidator('customEmail', {
                    validateString: function(value) {
                        var emailRegex = /^[a-zA-Z0-9._%-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                        var disallowedEmailRegex = /(.)\1{2,}|(\+.*?\+)|(\.{2,})|(-{2,})|(@-|-\@)|(@\.)|(\.@)/;

                        if (!emailRegex.test(value) || disallowedEmailRegex.test(value)) {
                        return false;
                        }

                        var domain = value.split('@')[1];

                        if (domain.includes('..') || domain.match(/\.\w+\.\w+$/)) {
                        return false;
                        }

                        var validTLDs = ['com', 'org', 'net', 'co', 'in', 'uk', 'info'];
                        var domainParts = domain.split('.').reverse();

                        if (!validTLDs.includes(domainParts[0]) || (domainParts[0] === 'co' && !validTLDs.includes(domainParts[1]))) {
                        return false;
                        }

                        return true;
                    },
                    messages: {
                        en: 'Invalid email address'
                    }
                });
            }
        }

        // --- Inline Location edit in summary panel ---
        $(document).on('click', '.location-edit-btn', function () {
            var $row = $(this).closest('#summary-location-row');
            $row.find('.location-view').addClass('d-none');
            $row.find('.location-edit').removeClass('d-none');
        });

        $(document).on('click', '.location-cancel-btn', function () {
            var $row = $(this).closest('#summary-location-row');
            $row.find('.location-edit').addClass('d-none');
            $row.find('.location-view').removeClass('d-none');
        });

        $(document).on('click', '.location-save-btn', function () {
            var $row = $(this).closest('#summary-location-row');
            var val = $row.find('.location-select').val();
            $.ajax({
                url: "{{ route('people.employees.updateLocation') }}",
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    employee_id: '{{ $employee->id }}',
                    location: val
                },
                success: function (res) {
                    if (res.success) {
                        $row.find('.location-text').text(res.location || '-');
                        $row.find('.location-edit').addClass('d-none');
                        $row.find('.location-view').removeClass('d-none');
                        toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                    }
                },
                error: function (xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Failed to update location', 'Error', { positionClass: 'toast-bottom-right' });
                }
            });
        });

        // Searchable airport dropdown for the Leave Destination field. The
        // global layout init disables search for .select2t-none — this select
        // uses its own class so search stays visible.
        $(document).ready(function () {
            $('.select2-airport-search').each(function () {
                var $sel = $(this);
                if ($sel.hasClass('select2-hidden-accessible')) {
                    try { $sel.select2('destroy'); } catch (e) {}
                }
                $sel.select2({
                    width: '100%',
                    allowClear: true,
                    placeholder: 'Search airport (city, IATA code, or country)',
                    minimumInputLength: 1,
                    ajax: {
                        url: '{{ route("resort.airports.search") }}',
                        dataType: 'json',
                        delay: 200,
                        data: function (params) { return { q: params.term }; },
                        processResults: function (data) { return data; },
                        cache: true
                    }
                });
            });
        });

        // URL-param → toastr bridge.
        // LeaveController::employeeLeavePage redirects here with
        //   ?leave_empty=1
        // when the employee has zero leave records. Surface that as a
        // toast on page load. Switched from session flash to URL param
        // because flash works on local but is fragile on live —
        // depending on session driver, middleware order, and view
        // cache, the value can be consumed or dropped before Blade
        // renders, so the toast silently never fires. URL params
        // round-trip through every layer untouched.
        //
        // Toastr itself is loaded in resorts/layouts/js.blade.php BEFORE
        // @yield('import-scripts') runs, so by the time this $(function)
        // fires it's already defined. The guard is just defensive.
        $(function () {
            try {
                var params = new URLSearchParams(window.location.search);
                if (params.get('leave_empty') === '1' && typeof toastr !== 'undefined') {
                    toastr.info('This employee has no leave history yet.', 'Info', {
                        positionClass: 'toast-bottom-right',
                        timeOut: 4500
                    });
                    // Strip the query param so a page refresh doesn't re-fire the toast.
                    if (window.history && window.history.replaceState) {
                        params.delete('leave_empty');
                        var cleaned = window.location.pathname
                            + (params.toString() ? '?' + params.toString() : '')
                            + window.location.hash;
                        window.history.replaceState({}, '', cleaned);
                    }
                }
            } catch (e) { /* URLSearchParams unsupported — silently skip */ }
        });
</script>
@endsection
