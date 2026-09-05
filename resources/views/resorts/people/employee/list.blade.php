@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
    <style>
        #people-employees-hero { padding-bottom: 40px; }
        @media (max-width: 575.98px) {
            #people-employees-hero { padding-bottom: 0; }
        }
    </style>
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding" id="people-employees-hero">
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>People</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <div class="col-auto  ms-auto">
                        <a class="btn btn-theme" href="{{route('people.employees.create')}}">Add New Employee</a>
                    </div>
                </div>
            </div>
            <div class="card card-empListPeopleEmp">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                            <div class="input-group">
                                <input type="search" class="form-control" id="search-input" placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select dd-native-select" id="deptFilter">
                                <option value="">Departments</option>
                                @if($departments)
                                    @foreach($departments as $dept)
                                        <option value="{{$dept->id}}">{{$dept->name}}</option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="dd" data-target="#deptFilter">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Departments</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Department">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a department…"></div>
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Departments</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @if($departments)
                                            @foreach($departments as $dept)
                                                <div class="dd-item" role="option" data-value="{{$dept->id}}"><span class="dd-nm">{{$dept->name}}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select dd-native-select" id="positionFilter">
                                <option value="">Positions</option>
                                @if($positions)
                                    @foreach($positions as $pos)
                                        <option value="{{$pos->id}}">{{$pos->position_title}}</option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="dd" data-target="#positionFilter">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Positions</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Position">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a position…"></div>
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Positions</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @if($positions)
                                            @foreach($positions as $pos)
                                                <div class="dd-item" role="option" data-value="{{$pos->id}}"><span class="dd-nm">{{$pos->position_title}}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select dd-native-select" id="statusFilter">
                                <option value="">Status</option>
                                <option value="Onboarding">Onboarding</option>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                                <option value="Terminated">Terminated</option>
                                <option value="Resigned">Resigned</option>
                                <option value="On Leave">On Leave</option>
                                <option value="Suspended">Suspended</option>
                            </select>
                            <div class="dd" data-target="#statusFilter">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Status</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Status">
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Status</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Onboarding"><span class="dd-nm">Onboarding</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Active"><span class="dd-nm">Active</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Inactive"><span class="dd-nm">Inactive</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Terminated"><span class="dd-nm">Terminated</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Resigned"><span class="dd-nm">Resigned</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="On Leave"><span class="dd-nm">On Leave</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Suspended"><span class="dd-nm">Suspended</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select dd-native-select" id="locationFilter">
                                <option value="">Location</option>
                                <option value="Malé">Malé</option>
                                <option value="Resorts">Resorts</option>
                            </select>
                            <div class="dd" data-target="#locationFilter">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Location</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Location">
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Location</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Malé"><span class="dd-nm">Malé</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Resorts"><span class="dd-nm">Resorts</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-1 col-md-2 col-sm-3 col-6">
                            <button class="btn btn-themeBlue btn-sm" id="clearFilter">Clear Filter</button>
                        </div>
                        

                        
                        <div class="col-auto ms-auto">
                            {{-- Bulk Delete intentionally hidden — destructive employee
                                 removal must go through Resignation / Exit Clearance.
                                 The selection-driven Export action stays. --}}
                            <button id="exportSelectedEmployees" class="btn btn-themeBlue btn-sm">Export</button>
                        </div>
                       
                        <div class="col-auto">
                            <a href="javascript:void();" class="btn btn-grid active">
                                <img src="{{ URL::asset('resorts_assets/images/grid.svg')}}" alt="icon">
                            </a>
                            <a href="javascript:void();" class="btn btn-list">
                                <img src="{{ URL::asset('resorts_assets/images/list.svg')}}" alt="icon">
                            </a>
                        </div>
                    </div>
                </div>
                <div class="list-main d-none">
                    <div class="table-responsive">
                        <table class="table table-collapseNew table-applicants" id="employeeListTable">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAllDataTable" /></th>
                                    <th>Id</th>
                                    <th>Employee Name</th>
                                    <th>Position</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Employment Type</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                <div class="grid-main">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <label>
                                Show 
                                <select id="gridPageSize" class="form-select form-select-sm dd-native-select" style="width: auto; display: inline-block;">
                                    <option value="10" selected>10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                <div class="dd" data-target="#gridPageSize" style="width:auto; display:inline-block; min-width:70px;">
                                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                        <span class="dd-lbl">10</span>
                                        <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                    </button>
                                    <div class="dd-panel" role="listbox" aria-label="Page Size">
                                        <div class="dd-scroll">
                                            <div class="dd-item active" role="option" data-value="10"><span class="dd-nm">10</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            <div class="dd-item" role="option" data-value="25"><span class="dd-nm">25</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            <div class="dd-item" role="option" data-value="50"><span class="dd-nm">50</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            <div class="dd-item" role="option" data-value="100"><span class="dd-nm">100</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        </div>
                                    </div>
                                </div>
                                entries
                            </label>
                        </div>
                        <div class="form-check mb-0 d-flex align-items-center">
                            <input class="form-check-input" type="checkbox" id="selectAllEmployees" value="option1">
                            <label class="form-check-label mx-2" for="selectAllEmployees"> Select All Employees </label>
                        </div>
                    </div>

                    <div id="grid-employee-container" class="row g-md-4 g-3 mb-4"></div>
                    <div id="grid-pagination"></div>

                </div>
                <div id="selectAllNotice" class="mt-2 d-none">
                    All <span id="totalEmployees">0</span> employees are selected across all pages.
                    <a href="#" id="clearAllSelection">Clear selection</a>
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
                            <label for="team_id">Select Team <span class="req_span">*</span></label>
                            <select name="team_id" id="assign_team_id" class="form-select dd-native-select" required>
                                <option value="">Select Team</option>
                                @foreach($teams as $team)
                                    <option value="{{ $team->id }}">{{ $team->name }}</option>
                                @endforeach
                            </select>
                            <div class="dd" data-target="#assign_team_id">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Select Team</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Team">
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select Team</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @foreach($teams as $team)
                                            <div class="dd-item" role="option" data-value="{{ $team->id }}"><span class="dd-nm">{{ $team->name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="role_id">Select Role <span class="req_span">*</span></label>
                            <select name="role_id" id="assign_role_id" class="form-select dd-native-select" required>
                                <option value="">Select Role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                            <div class="dd" data-target="#assign_role_id">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Select Role</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Role">
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select Role</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @foreach($roles as $role)
                                            <div class="dd-item" role="option" data-value="{{ $role->id }}"><span class="dd-nm">{{ $role->name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn eb-btn-primary">Assign</button>
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
                <h5 class="modal-title">Change Status<span class="req_span">*</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                <input type="hidden" name="emp_id" id="modal-emp-id">

                <select name="status" id="modal-status" class="form-select dd-native-select" required>
                    <option value="">Status</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                    <option value="Terminated">Terminated</option>
                    <option value="Resigned">Resigned</option>
                    <option value="On Leave">On Leave</option>
                    <option value="Suspended">Suspended</option>
                </select>
                <div class="dd" data-target="#modal-status">
                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                        <span class="dd-lbl">Status</span>
                        <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                    </button>
                    <div class="dd-panel" role="listbox" aria-label="Status">
                        <div class="dd-scroll">
                            <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Status</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                            <div class="dd-item" role="option" data-value="Active"><span class="dd-nm">Active</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                            <div class="dd-item" role="option" data-value="Inactive"><span class="dd-nm">Inactive</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                            <div class="dd-item" role="option" data-value="Terminated"><span class="dd-nm">Terminated</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                            <div class="dd-item" role="option" data-value="Resigned"><span class="dd-nm">Resigned</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                            <div class="dd-item" role="option" data-value="On Leave"><span class="dd-nm">On Leave</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                            <div class="dd-item" role="option" data-value="Suspended"><span class="dd-nm">Suspended</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                        </div>
                    </div>
                </div>
                </div>
                <div class="modal-footer">
                <button type="submit" class="btn eb-btn-primary">Update</button>
                </div>
            </div>
            </form>
        </div>
    </div>
@include('resorts._emotional_buttons_v2_styles')
@endsection

@section('import-css')
@include('resorts._dropdown_styles')
<style>
    /* Pin the 3-dot kebab to the top-right corner of every employee card.
       default.css line ~4800 already absolutely-positions
       `.empListPeopleEmp-block .dropdown` at top:22px / right:22px, but
       the rendered card was still showing the kebab visually drifted
       toward the centre. Force the corner with !important so a cached
       theme stylesheet or a later rule can't pull it back. Match the
       same selector to keep the override specific to this card type. */
    .empListPeopleEmp-block { position: relative !important; }
    .empListPeopleEmp-block .dropdown,
    .empListPeopleEmp-block .table-dropdown {
        position: absolute !important;
        top: 10px !important;
        right: 12px !important;
        left: auto !important;
        z-index: 5;
    }
    .empListPeopleEmp-block .dotsV-link {
        padding: 4px 8px !important;
        line-height: 1 !important;
    }
</style>
@endsection


@section('import-scripts')
<script>
    let employeeGridUrl = "{{ route('people.employees.grid') }}";
    let selectedEmployees = new Set();
    let selectAllAcrossPages = false;

    $(document).ready(function () {
        loadGridView();

        // Toggle View
        $(".btn-grid").addClass("active");
        $(".grid-main").removeClass("d-none").addClass("d-block");
        $(".list-main").addClass("d-none");

        $(".btn-list").click(function () {
            $(this).addClass("active");
            $(".list-main").removeClass("d-none").addClass("d-block");
            $(".btn-grid").removeClass("active");
            $(".grid-main").addClass("d-none");
            getEmpTable();
        });

        $(".btn-grid").click(function () {
            $(this).addClass("active");
            $(".grid-main").removeClass("d-none").addClass("d-block");
            $(".btn-list").removeClass("active");
            $(".list-main").addClass("d-none");
            loadGridView();
        });

        // Filters and search
        $(document).on('keyup', '#search-input', function () {
            $('.btn-grid').hasClass('active') ? loadGridView() : getEmpTable();
        });

        $('#deptFilter, #positionFilter, #statusFilter, #locationFilter').on('change', function () {
            $('.btn-grid').hasClass('active') ? loadGridView() : getEmpTable();
        });

        // Pagination
        $(document).on('click', '#grid-pagination .pagination a', function (e) {
            e.preventDefault();
            let page = $(this).attr('href').split('page=')[1];
            loadGridView(page);
        });

        $(document).on('change', '#selectAllDataTable', function () {
            const isChecked = $(this).is(':checked');
            $('.employee-checkbox').each(function () {
                const id = $(this).val();
                $(this).prop('checked', isChecked);
                isChecked ? selectedEmployees.add(id) : selectedEmployees.delete(id);
            });

            if (isChecked) {
                selectAllAcrossPages = false; // for list view, keep it local by default
            }
        });

        // Select all across all pages
        $('#selectAllEmployees').on('change', function () {
            const isChecked = $(this).is(':checked');

            if (isChecked) {
                selectAllAcrossPages = true;
                $('#selectAllNotice').removeClass('d-none');

                $.ajax({
                    url: '{{ route("people.employees.all-ids") }}',
                    method: 'GET',
                    data: {
                        department_id: $('#deptFilter').val(),
                        position_id: $('#positionFilter').val(),
                        status: $('#statusFilter').val(),
                        location: $('#locationFilter').val(),
                        searchTerm: $('#search-input').val()
                    },
                    success: function (response) {
                        selectedEmployees = new Set(response.ids);
                        $('#totalEmployees').text(response.total);
                        $('.employee-checkbox').prop('checked', true);
                    }
                });
            } else {
                selectAllAcrossPages = false;
                selectedEmployees.clear();
                $('#selectAllNotice').addClass('d-none');
                $('.employee-checkbox').prop('checked', false);
            }
        });

        $('#gridPageSize').on('change', function () {
            loadGridView(1); // Reset to page 1 when pageSize changes
        });
        // Individual checkbox change
        $(document).on('change', '.employee-checkbox', function () {
            const id = $(this).val();
            if ($(this).is(':checked')) {
                selectedEmployees.add(id);
            } else {
                selectedEmployees.delete(id);
                selectAllAcrossPages = false;
                $('#selectAllEmployees').prop('checked', false);
                $('#selectAllNotice').addClass('d-none');
            }
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

                        getEmpTable(); 
                        loadGridView(); // Reload Grid View

                    
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

        $(document).on('click','.add-to-team-btn',function(){
            let empId = $(this).data('emp-id');
            $('#empIdInput').val(empId);
            $('#assignTeamModal').modal('show');
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

         $(document).on('click', '.delete-employee', function(e) {
            e.preventDefault();
            const empId = $(this).data('emp-id');

            wisdomConfirm({
                role: 'destructive',
                title: 'Are you sure?',
                text: "Are you sure you want to delete this employee!",
                confirmText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('employee.delete') }}", // Adjust route name
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            id: empId
                        },
                        success: function(response) {
                            wisdomAlert({
                                type: 'success',
                                title: 'Deleted!',
                                text: response.message
                            }).then(() => {
                                if ($('.btn-grid').hasClass('active')) {
                                    loadGridView(); // Reload Grid View
                                } else {
                                    getEmpTable();  // Reload List View
                                }
                            });
                        },
                        error: function(xhr) {
                            wisdomAlert({
                                type: 'error',
                                title: 'Error!',
                                text: xhr.responseJSON?.message || 'Something went wrong.'
                            });
                        }
                    });
                }
            });
        });


        // Clear selection link
        $('#clearAllSelection').on('click', function (e) {
            e.preventDefault();
            selectedEmployees.clear();
            selectAllAcrossPages = false;
            $('#selectAllEmployees').prop('checked', false);
            $('#selectAllNotice').addClass('d-none');
            $('.employee-checkbox').prop('checked', false);
        });

        // Export selected
        $('#exportSelectedEmployees').on('click', function () {
            if (selectedEmployees.size === 0) {
                alert('Please select at least one employee to export.');
                return;
            }

            const ids = Array.from(selectedEmployees);

            $.ajax({
                url: '{{ route("people.employees.export") }}',
                method: 'POST',
                data: {
                    employee_ids: ids,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                xhrFields: { responseType: 'blob' },
                success: function (response, status, xhr) {
                    const filename = xhr.getResponseHeader('Content-Disposition')?.split('filename=')[1]?.replaceAll('"', '') || 'employees-export.xlsx';
                    const url = window.URL.createObjectURL(response);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                }
            });
        });

        // Status change, team assign, delete, etc. remain unchanged...

        $('#clearFilter').on('click', function () {
            selectedEmployees.clear();
            selectAllAcrossPages = false;
            $('#selectAllEmployees').prop('checked', false);
            $('#selectAllNotice').addClass('d-none');
            $('#search-input').val('');
            $('#deptFilter').val('').trigger('change');
            $('#statusFilter').val('').trigger('change');
            $('#positionFilter').val('').trigger('change');
            $('#locationFilter').val('').trigger('change');
            loadGridView();
            getEmpTable();
        });
    });

    function loadGridView(page = 1) {
        let pageSize = $('#gridPageSize').val();
        $.ajax({
            url: `${employeeGridUrl}?page=${page}&pageSize=${pageSize}`,
            data: {
                department_id: $('#deptFilter').val(),
                position_id: $('#positionFilter').val(),
                status: $('#statusFilter').val(),
                location: $('#locationFilter').val(),
                searchTerm: $('#search-input').val(),
            },
            success: function (res) {
                $('#grid-employee-container').html(res.html);
                $('#grid-pagination').html(res.pagination);

                $('.employee-checkbox').each(function () {
                    const id = $(this).val();
                    if (selectAllAcrossPages) {
                        $(this).prop('checked', true);
                        selectedEmployees.add(id); // ensure consistency
                    } else {
                        $(this).prop('checked', selectedEmployees.has(id));
                    }
                });

                if (selectAllAcrossPages) {
                    $('#selectAllEmployees').prop('checked', true);
                    $('#selectAllNotice').removeClass('d-none');
                    $('#totalEmployees').text(selectedEmployees.size);
                } else {
                    const allChecked = $('.employee-checkbox').length &&
                    $('.employee-checkbox:checked').length === $('.employee-checkbox').length;
                    $('#selectAllEmployees').prop('checked', allChecked);
                }
            }
        });
    }

    function getEmpTable() {
        if ($.fn.dataTable.isDataTable('#employeeListTable')) {
            $('#employeeListTable').DataTable().destroy();
        }
        
        $('#employeeListTable').DataTable({
            searching: false,
            bLengthChange: true,
            bInfo: true,
            scrollX: true,
            processing: true,
            serverSide: true,
            order: [[8, 'desc']],
            ajax: {
                url: '{{ route("people.employees.list") }}',
                data: function (d) {
                    d.department_id = $('#deptFilter').val();
                    d.position_id = $('#positionFilter').val();
                    d.searchTerm = $('#search-input').val();
                    d.status = $('#statusFilter').val();
                    d.location = $('#locationFilter').val();
                }
            },
            columns: [
                { data: 'checkbox', orderable: false, searchable: false },
                { data: 'Emp_id' },
                { data: 'applicant',orderable: false },
                { data: 'position',orderable: false },
                { data: 'department',orderable: false },
                { data: 'status' },
                { data: 'employment_type' },
                { data: 'action', orderable: false, searchable: false },
                { data:'created_at',visible:false,searchable:false},
            ],
            drawCallback: function (settings) {
                // Restore checkbox state after pagination/sorting
                $('.employee-checkbox').each(function () {
                    const id = $(this).val();
                    if (selectAllAcrossPages) {
                        $(this).prop('checked', true);
                        selectedEmployees.add(id);
                    } else {
                        $(this).prop('checked', selectedEmployees.has(id));
                    }
                });

                if (selectAllAcrossPages) {
                    $('#selectAllEmployees').prop('checked', true);
                    $('#selectAllNotice').removeClass('d-none');
                    $('#totalEmployees').text(selectedEmployees.size);
                } else {
                    const allChecked = $('.employee-checkbox').length &&
                        $('.employee-checkbox:checked').length === $('.employee-checkbox').length;
                    $('#selectAllEmployees').prop('checked', allChecked);
                }
            }
        });
    }
</script>
@include('resorts._dropdown_script')
@endsection