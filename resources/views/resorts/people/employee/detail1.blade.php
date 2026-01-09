@extends('resorts.layouts.app')
@section('page_tab_title' ,"People Dashboard")

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
                            <h1>Employee Details</h1>
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
                                    <li><a class="dropdown-item" href="{{route('people.employees.details', base64_encode($employee->id))}}">View Profile</a></li>
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
                            <select class="form-select  active mb-2" id="statusFilter" disabled>
                                <option value="">Status</option>
                                <option {{$employee->status == "Active" ? "Selected" : ""}} value="Active">Active</option>
                                <option {{$employee->status == "Inactive" ? "Selected" : ""}} value="Inactive">Inactive</option>
                                <option {{$employee->status == "Terminated" ? "Selected" : ""}} value="Terminated">Terminated</option>
                                <option {{$employee->status == "Resigned" ? "Selected" : ""}} value="Resigned">Resigned</option>
                                <option {{$employee->status == "On Leave" ? "Selected" : ""}} value="On Leave">On Leave</option>
                                <option {{$employee->status == "Suspended" ? "Selected" : ""}} value="Suspended">Suspended</option>
                            </select>
                            <table class="table table-lable">
                                <tbody>
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
                                                    {{$employee->reportingTo->resortAdmin->first_name}} {{$employee->reportingTo->resortAdmin->last_name}}
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
                            <ul>
                                <!-- <li><a href="#">Workforce Planning</a></li> -->
                                <li><a href="{{route('people.probation.details',[base64_encode($employee->id)])}}">Probation</a></li>
                                <li><a href="{{ route('people.promotion.history', [base64_encode($employee->id)]) }}">Promotion</a></li>
                                <li><a href="{{route('resort.visa.XpactEmpDetails',[base64_encode($employee->id)])}}">Xpat</a></li>
                                <li><a href="{{ route('resort.timeandattendance.employee.details', [ base64_encode($employee->id)]); }}">Time & Attendance</a></li>
                                <li><a href="#">Leave</a></li>
                                <li><a href="{{ route('resort.accommodation.AccommodationEmployeeDetails', [base64_encode($employee->id)]) }}">Accommodation</a></li>
                                <!-- <li><a href="#">Payroll</a></li> -->
                                <!-- <li><a href="#">People Relation</a></li> -->
                                <!-- <li><a href="#">L&D</a></li> -->
                                <!-- <li><a href="#">Survey</a></li> -->
                                <!-- <li><a href="#">Incident</a></li> -->
                                <li><a href="#">File Management</a></li>
                                <li><a href="#">Announcement</a></li>
                                <li><a href="#">Clearance</a></li>
                                <li><a href="#">Requests</a></li>
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
                                                    <a href="#" class="btn-lg-icon icon-bg-blue toggle-edit-save">
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
                                                                    <select name="title" class="form-select edit-mode d-none">
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
                                                                    <input type="text" name="first_name" class="form-control edit-mode d-none" value="{{$employee->resortAdmin->first_name}}">
                                                                    <input type="text" name="last_name" class="form-control edit-mode d-none" value="{{$employee->resortAdmin->last_name}}">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Gender:</th>
                                                                <td>
                                                                    <span class="view-mode">{{$employee->resortAdmin->gender}}</span>
                                                                    <select name="gender" class="form-select edit-mode d-none">
                                                                        <option value="">Select Gender</option>
                                                                        <option {{$employee->resortAdmin->gender == "male" ? "Selected" : ""}} value="male">Male</option>
                                                                        <option {{$employee->resortAdmin->gender == "female" ? "Selected" : ""}} value="female">Female</option>
                                                                        <option {{$employee->resortAdmin->gender == "other" ? "Selected" : ""}} value="other">Other</option>
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Date of Birth:</th>
                                                                <td>
                                                                    <span class="view-mode">{{ \Carbon\Carbon::parse($employee->dob)->format('d M Y') }} 
                                                                    ({{ \Carbon\Carbon::parse($employee->dob)->age }} years old)</span>
                                                                    <input type="text" name="dob" class="form-control edit-mode d-none datepicker" value="{{ \Carbon\Carbon::parse($employee->dob)->format('d/m/Y') }}">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Marital Status:</th>
                                                                <td>
                                                                    <span class="view-mode">{{$employee->marital_status}}</span>
                                                                    <select name="marital_status" class="form-select edit-mode d-none">
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
                                                                    <select name="nationality" class="form-select edit-mode d-none">
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
                                                                    <span class="view-mode">{{$employee->religion == 0 ? "Non-Muslim" : "Muslim"}}</span>
                                                                    <select name="religion" class="form-select edit-mode d-none">
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
                                                                    <select name="blood_group" class="form-select edit-mode d-none">
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
                                                                    <input type="text" name="passport_number" class="form-control edit-mode d-none" value="{{$employee->passport_number}}">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>NID:</th>
                                                                <td>
                                                                    <span class="view-mode">{{$employee->nid ?? "N/A"}}</span>
                                                                    <input type="text" name="nid" class="form-control edit-mode d-none" value="{{$employee->nid}}">
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
                                                            <a href="#" class="btn-lg-icon icon-bg-blue edit-contact-btn">
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
                                                                <input type="text" name="personal_phone" class="form-control edit-mode d-none" value="{{ $employee->resortAdmin->personal_phone }}">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>Email Address:</th>
                                                            <td>
                                                                <span class="view-mode">{{ $employee->resortAdmin->email }}</span>
                                                                <input type="email" name="email" class="form-control edit-mode d-none" value="{{ $employee->resortAdmin->email }}">
                                                            </td>
                                                        </tr>
                                                            <tr>
                                                                <th>Permanent Address:</th>
                                                                <td>
                                                                <span class="view-mode">{{$employee->resortAdmin->address_line_1}} {{$employee->resortAdmin->address_line_2}} {{$employee->resortAdmin->city}} {{$employee->resortAdmin->state}} {{$employee->resortAdmin->country}} - {{$employee->resortAdmin->zip}}</span>
                                                                    <input type="text" name="address_line_1" value="{{ $employee->resortAdmin->address_line_1 }}" class="form-control edit-mode d-none">
                                                                    <input type="text" name="address_line_2" value="{{ $employee->resortAdmin->address_line_2 }}" class="form-control edit-mode d-none">
                                                                    <input type="text" name="city" value="{{ $employee->resortAdmin->city }}" class="form-control edit-mode d-none">
                                                                    <input type="text" name="state" value="{{ $employee->resortAdmin->state }}" class="form-control edit-mode d-none">
                                                                    <input type="text" name="country" value="{{ $employee->resortAdmin->country }}" class="form-control edit-mode d-none">
                                                                    <input type="text" name="zip" value="{{ $employee->resortAdmin->zip }}" class="form-control edit-mode d-none">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Present Address:</th>
                                                                <td>
                                                                    <span class="view-mode">{{$employee->present_address ?? "Not Available"}}</span>
                                                                    <textarea class="form-control edit-mode d-none" name="present_address">{{$employee->present_address ?? "Not Available"}}</textarea>
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
                                                            <a href="#" class="btn-lg-icon icon-bg-blue edit-emergency-contact-btn">
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
                                                    <a href="#" class="btn-lg-icon icon-bg-blue edit-addition-info">
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
                                                                    <input type="text" name="leave_destination" value="{{ $employee->leave_destination }}" class="form-control edit-mode d-none">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Biometrics:</th>
                                                                <td>
                                                                    @if($employee->biometric_file)
                                                                        <a href="{{ URL::asset($employee->biometric_file) }}" target="_blank"><i class="fa fa-file"></i></a>
                                                                    @else
                                                                        <input type="file" name="biometric_file" class="form-control edit-mode d-none">
                                                                    @endif
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
                                                                            <input type="text" name="languages[{{ $index }}][proficiency_level]" class="form-control edit-mode d-none" value="{{ $lang->proficiency_level }}">
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
                                    <div class="row g-xxl-4 g-lg-3 g-2">
                                        <div class="col-xl-4 col-lg-6">
                                            <div class="recentActPeopleEmp-block">
                                                <div>
                                                    <h6>Annual Leave</h6>
                                                    <p>From 01 Nov 2024 to 5 Jan 2025</p>
                                                </div>
                                                <span class="badge badge-themeSuccess">Approved</span>
                                            </div>
                                        </div>
                                        <div class="col-xl-4 col-lg-6">
                                            <div class="recentActPeopleEmp-block">
                                                <div>
                                                    <h6>Time-off requests</h6>
                                                    <p>Lorem ipsum is dummy text of industry</p>
                                                </div>
                                                <span class="badge badge-themeSuccess">Approved</span>
                                            </div>
                                        </div>
                                        <div class="col-xl-4 col-lg-6">
                                            <div class="recentActPeopleEmp-block">
                                                <div>
                                                    <h6>Permissions</h6>
                                                    <p>Lorem ipsum is dummy text of industry</p>
                                                </div>
                                                <span class="badge badge-themeYellow">Pending</span>
                                            </div>
                                        </div>
                                    </div>
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
                                                    <a href="#" class="btn-lg-icon icon-bg-blue edit-emp-info">
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
                                                                    <input type="text" name="personal_phone" class="form-control edit-mode d-none" value="{{$employee->resortAdmin->personal_phone}}">
                                                                </td>
                                                            </tr>
                                                    
                                                            <tr>
                                                                <th>Division:</th>
                                                                <td>
                                                                    <span class="view-mode"> {{$employee->division->name ?? "N/A"}}</span>
                                                                    <select name="division_id" class="form-select edit-mode d-none">
                                                                        <option value="">Select Division</option>
                                                                        @if($resort_divisions)
                                                                            @foreach($resort_divisions as $div)
                                                                                <option {{$div->id == $employee->division_id ? "Selected" : ""}} value="{{$div->id}}">{{$div->name}}</option>
                                                                            @endforeach
                                                                        @endif
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                            <tr>
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
                                                                <th>Joining date:</th>
                                                                <td>
                                                                    <span class="view-mode">{{ \Carbon\Carbon::parse($employee->joining_date)->format('d M Y') }} 
                                                                    ({{ \Carbon\Carbon::parse($employee->joining_date)->age }} years)</span>
                                                                    <input type="text" name="joining_date" class="form-control edit-mode d-none datepicker" value="{{ \Carbon\Carbon::parse($employee->joining_date)->format('d/m/Y') }}">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Employment Status:</th>
                                                                <td> 
                                                                    <span class="view-mode">{{$employee->status}}</span>
                                                                    <select name="status" id="employment-status" class="form-select edit-mode d-none">
                                                                        <option value="">Select Status</option>
                                                                        <option {{$employee->status == "Active" ? "Selected" : ""}} value="Active">Active</option>
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
                                                            <tr>
                                                                <th>Benefit Grid Level:</th>
                                                                <td>
                                                                    <span class="view-mode">
                                                                        {{ config('settings.eligibilty')[$employee->benefit_grid_level] ?? 'N/A' }}
                                                                    </span>
                                                                    <select name="benefit_grid_level" class="form-select edit-mode d-none">
                                                                        <option value="">Select Benefit Grid</option>
                                                                        @if($benefitGrids)
                                                                            @foreach($benefitGrids as $grid)
                                                                            <option {{$employee->benefit_grid_level == $grid->emp_grade ? "Selected" : ""}} value="{{ $grid->emp_grade }}">{{ config('settings.eligibilty')[$grid->emp_grade] ?? 'N/A' }}</option>
                                                                            @endforeach
                                                                        @endif
                                                                    </select>
                                                                </td>
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
                                                                   <input type="text" class="form-control edit-mode d-none" name="tin" pattern="\d{6,10}" maxlength="10" class="form-control" value="{{$employee->tin}}">

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
                                                    <a href="#" class="btn-lg-icon icon-bg-blue edit-salary-details">
                                                        <img src="{{ URL::asset('resorts_assets/images/edit.svg')}}" alt="icon">
                                                    </a>
                                                    <button type="submit" class="btn btn-themeBlue btn-sm d-none" id="btn-salary-save">Submit</button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        @php
                                            $conversionRate = 15.42;
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
                                                    <div class="row mt-3">
                                                        <div class="col-12">
                                                            <button type="button" id="add-allowance" class="btn btn-sm btn-outline-primary">
                                                                <i class="fa fa-plus"></i> Add Allowance
                                                            </button>
                                                        </div>
                                                    </div>
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
                                                                <a href="#" data-id="{{ $bankDetail->id }}" class="btn-lg-icon icon-bg-blue position-absolute top-0 end-0 m-2 edit-bank-info" title="Edit">
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
                                                                                <input type="text" name="IFSC_BIC" class="form-control edit-mode d-none" value="{{$bankDetail->IFSC_BIC}}">
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
                                                                                <input type="text" name="account_no" class="form-control edit-mode d-none" value="{{$bankDetail->account_no}}">
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Currency:</th>
                                                                            <td>
                                                                                <span class="view-mode">{{$bankDetail->currency ?? "Not Available"}}</span>
                                                                                <select name="currency" class="form-control edit-mode d-none" >
                                                                                    <option {{$bankDetail->currency == "USD" ? "Selected" : "" }}value="USD">USD</option>
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
                                    <div class="row g-xxl-4 g-lg-3 g-2">
                                        <div class="col-xl-4 col-lg-6">
                                            <div class="recentActPeopleEmp-block">
                                                <div>
                                                    <h6>Annual Leave</h6>
                                                    <p>From 01 Nov 2024 to 5 Jan 2025</p>
                                                </div>
                                                <span class="badge badge-themeSuccess">Approved</span>
                                            </div>
                                        </div>
                                        <div class="col-xl-4 col-lg-6">
                                            <div class="recentActPeopleEmp-block">
                                                <div>
                                                    <h6>Time-off requests</h6>
                                                    <p>Lorem ipsum is dummy text of industry</p>
                                                </div>
                                                <span class="badge badge-themeSuccess">Approved</span>
                                            </div>
                                        </div>
                                        <div class="col-xl-4 col-lg-6">
                                            <div class="recentActPeopleEmp-block">
                                                <div>
                                                    <h6>Permissions</h6>
                                                    <p>Lorem ipsum is dummy text of industry</p>
                                                </div>
                                                <span class="badge badge-themeYellow">Pending</span>
                                            </div>
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
                                                                <a href="#" data-id="{{ $education->id }}" class="btn-lg-icon icon-bg-blue position-absolute top-0 end-0 m-2 edit-edu-info" title="Edit">
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
                                                                                <input type="text" name="attendance_period" class="form-control edit-mode d-none" value="{{$education->attendance_period}}">
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
                                    <div class="row g-xxl-4 g-lg-3 g-2">
                                        <div class="col-xl-4 col-lg-6">
                                            <div class="recentActPeopleEmp-block">
                                                <div>
                                                    <h6>Annual Leave</h6>
                                                    <p>From 01 Nov 2024 to 5 Jan 2025</p>
                                                </div>
                                                <span class="badge badge-themeSuccess">Approved</span>
                                            </div>
                                        </div>
                                        <div class="col-xl-4 col-lg-6">
                                            <div class="recentActPeopleEmp-block">
                                                <div>
                                                    <h6>Time-off requests</h6>
                                                    <p>Lorem ipsum is dummy text of industry</p>
                                                </div>
                                                <span class="badge badge-themeSuccess">Approved</span>
                                            </div>
                                        </div>
                                        <div class="col-xl-4 col-lg-6">
                                            <div class="recentActPeopleEmp-block">
                                                <div>
                                                    <h6>Permissions</h6>
                                                    <p>Lorem ipsum is dummy text of industry</p>
                                                </div>
                                                <span class="badge badge-themeYellow">Pending</span>
                                            </div>
                                        </div>
                                    </div>
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
                                                                <a href="#" data-id="{{ $exp->id }}" class="btn-lg-icon icon-bg-blue position-absolute top-0 end-0 m-2 edit-exp-info" title="Edit">
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
                                                                                <input type="text" name="duration" class="form-control edit-mode d-none" value="{{$exp->duration}}">
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
                                                                                <input type="text" name="reference_contact" class="form-control edit-mode d-none" value="{{$exp->reference_contact}}">
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
                                    <div class="row g-xxl-4 g-lg-3 g-2">
                                        <div class="col-xl-4 col-lg-6">
                                            <div class="recentActPeopleEmp-block">
                                                <div>
                                                    <h6>Annual Leave</h6>
                                                    <p>From 01 Nov 2024 to 5 Jan 2025</p>
                                                </div>
                                                <span class="badge badge-themeSuccess">Approved</span>
                                            </div>
                                        </div>
                                        <div class="col-xl-4 col-lg-6">
                                            <div class="recentActPeopleEmp-block">
                                                <div>
                                                    <h6>Time-off requests</h6>
                                                    <p>Lorem ipsum is dummy text of industry</p>
                                                </div>
                                                <span class="badge badge-themeSuccess">Approved</span>
                                            </div>
                                        </div>
                                        <div class="col-xl-4 col-lg-6">
                                            <div class="recentActPeopleEmp-block">
                                                <div>
                                                    <h6>Permissions</h6>
                                                    <p>Lorem ipsum is dummy text of industry</p>
                                                </div>
                                                <span class="badge badge-themeYellow">Pending</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="tabPane5" role="tabpanel" aria-labelledby="tab5" tabindex="0">
                                    <div class="bg-themeGrayLight mb-md-4 mb-3">
                                        <div class="card-title mb-0">
                                            <div class="row g-md-2 g-1 align-items-center">
                                                <div class="col">
                                                    <h3 class="text-nowrap">Expiry</h3>
                                                </div>
                                                <div class="col-auto">
                                                    <a href="#" class="btn-lg-icon icon-bg-blue edit-expiry-btn">
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
                                                                        @if($doc->document_file)
                                                                            <a href="{{ URL::asset($doc->document_file) }}" target="_blank" class="ms-2"><i class="fa fa-file"></i></a>
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

                                    <div class="row g-xxl-4 g-lg-3 g-2">
                                        <div class="col-xl-4 col-lg-6">
                                        <div class="recentActPeopleEmp-block">
                                            <div>
                                            <h6>Annual Leave</h6>
                                            <p>From 01 Nov 2024 to 5 Jan 2025</p>
                                            </div>
                                            <span class="badge badge-themeSuccess">Approved</span>
                                        </div>
                                        </div>
                                        <div class="col-xl-4 col-lg-6">
                                        <div class="recentActPeopleEmp-block">
                                            <div>
                                            <h6>Time-off requests</h6>
                                            <p>Lorem ipsum is dummy text of industry</p>
                                            </div>
                                            <span class="badge badge-themeSuccess">Approved</span>
                                        </div>
                                        </div>
                                        <div class="col-xl-4 col-lg-6">
                                        <div class="recentActPeopleEmp-block">
                                            <div>
                                            <h6>Permissions</h6>
                                            <p>Lorem ipsum is dummy text of industry</p>
                                            </div>
                                            <span class="badge badge-themeYellow">Pending</span>
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
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
    $(document).ready(function(){
        $('.datepicker').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true
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

            $.ajax({
                url: "{{ route('people.employees.assign-team') }}",
                type: "POST",
                data: $(this).serialize(),
                success: function (response) {
                    $('#assignModal').modal('hide');
                    if (response.status === 'success') {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        // Optionally refresh or update the team list
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

                        // ✅ Update status select field value
                        $('#statusFilter').val(newStatus);
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
                        toastr.success('Salary details updated successfully', 'Success');

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
                        toastr.error('Failed to update salary details', 'Error');
                    }
                },
                error: function(xhr) {
                    toastr.error('Error while updating salary', 'Error');
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
                            <option value="USD" {{ ($resort_allowance->amount_unit ?? 'USD') == 'USD' ? 'selected' : '' }}>USD</option>
                            <option value="MVR" {{ ($resort_allowance->amount_unit ?? '') == 'MVR' ? 'selected' : '' }}>MVR</option>
                            
                        </select>
                    </div>
                    <div class="col-2">
                        <button type="button" class="btn btn-danger btn-sm remove-allowance">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                    <input type="hidden" name="allowances[${allowanceIndex}][id]" value="{{ $resort_allowance->id }}">
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
                personal_phone: $('[name="personal_phone"]').val(),
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
                let lang = $(this).find('input[name^="languages"]').eq(0).val();
                let level = $(this).find('input[name^="languages"]').eq(1).val();

                if (lang && level) {
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

        $('#btn-emp-info-save').on('click', function () {
            let formData = {
                _token: $('meta[name="csrf-token"]').attr('content'), // CSRF token
                employee_id: '{{ $employee->id }}',
                Emp_id: $('[name="Emp_id"]').val(),
                email: $('[name="email"]').val(),
                personal_phone: $('[name="personal_phone"]').val(),
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
            };

            $.ajax({
                url: '{{ route("employee.update.employment-data") }}',
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
                        toastr.error('Failed to update documents.', "Error", { positionClass: 'toast-bottom-right' });
                    }
                },
                error: function (xhr) {
                    toastr.error('Something went wrong.', "Error", { positionClass: 'toast-bottom-right' });
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
            <div class="col-lg-6">
                <div class="table-responsive position-relative border rounded p-3 bg-white">
                    <button type="submit" class="btn btn-themeBlue btn-sm" id="btn-bank-info-save-${newId}">Save</button>

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
                                    <input type="text" name="IFSC_BIC" class="form-control edit-mode">
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
                                    <input type="text" name="account_no" class="form-control edit-mode">
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
                                    <input type="text" name="education_level" class="form-control edit-mode" value="">
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
                                    <input type="text" name="attendance_period" class="form-control edit-mode" value="">
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
            const currency = card.find('input[name="currency"]').val();
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
                                    <input type="text" name="duration" class="form-control edit-mode" value="">
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
                                    <input type="text" name="reference_contact" class="form-control edit-mode" value="">
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
                <td><input type="text" name="languages[${languageIndex}][proficiency_level]" class="form-control" /></td>
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
@endsection