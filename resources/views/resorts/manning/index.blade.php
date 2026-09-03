@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<style>
    /* Same requested push as the other module dashboards/pages — extra
       breathing room between the hero and the content below it, scoped to
       this page (.page-hedding's own margin-bottom is shared by every
       page's hero). padding-bottom, not margin: adjacent sibling margins
       collapse to the larger of the two rather than summing. Below
       Bootstrap's sm breakpoint the extra padding pushes content into the
       teal hero curve's rounded bottom-left corner (body::before,
       border-radius 0 0 50px 50px) — same collision found on Payroll —
       neutralized below 576px. */
    #manning-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #manning-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="manning-hero">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>WORKFORCE PLANNING</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <a href="{{ route('resort.budget.config') }}" class="btn btn-sm wfp-btn-neutral">
                        <i class="fa-solid fa-arrow-left"></i> Back to Configuration
                    </a>
                </div>
            </div>
        </div>

        <div class="card manning-card">
            <ul class="nav nav-tabs manning-tabs" id="manningTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-btn-div" data-bs-toggle="tab" data-bs-target="#tab-div" type="button" role="tab" aria-controls="tab-div" aria-selected="true">
                        Divisions <span class="manning-count">{{ $resort_divisions->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-btn-dep" data-bs-toggle="tab" data-bs-target="#tab-dep" type="button" role="tab" aria-controls="tab-dep" aria-selected="false">
                        Departments <span class="manning-count">{{ $resort_departments->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-btn-sec" data-bs-toggle="tab" data-bs-target="#tab-sec" type="button" role="tab" aria-controls="tab-sec" aria-selected="false">
                        Sections <span class="manning-count">{{ $resort_sections->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-btn-pos" data-bs-toggle="tab" data-bs-target="#tab-pos" type="button" role="tab" aria-controls="tab-pos" aria-selected="false">
                        Positions <span class="manning-count">{{ $resort_positions->count() }}</span>
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="manningTabContent">

                <div class="tab-pane fade show active" id="tab-div" role="tabpanel" aria-labelledby="tab-btn-div">
                    <div class="manning-toolbar">
                        <div class="input-group manning-search">
                            <input type="search" class="form-control" id="divisions-search" placeholder="Search divisions…" />
                            <i class="fa-solid fa-search"></i>
                        </div>
                        <a href="#" class="btn btn-sm wfp-btn-primary @if(App\Helpers\Common::checkRouteWisePermission('resort.budget.manning',config('settings.resort_permissions.create')) == false) d-none @endif " data-bs-toggle="modal"
                            data-bs-target="#add-divisionmodal">
                            <i class="fa-solid fa-plus me-2"></i>New Division
                        </a>
                    </div>
                    <div class="manning-thwrap">
                    <table id="divisions-table" class="table  w-100">
                        <thead>
                            <tr>
                                <th class="text-nowrap">Name</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-nowrap">Action</th>
                            </tr>
                        </thead>

                    </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-dep" role="tabpanel" aria-labelledby="tab-btn-dep">
                    <div class="manning-toolbar">
                        <div class="input-group manning-search">
                            <input type="search" class="form-control" id="departments-search" placeholder="Search departments…" />
                            <i class="fa-solid fa-search"></i>
                        </div>
                        <a href="#departments-modal" class="btn btn-sm wfp-btn-primary @if(App\Helpers\Common::checkRouteWisePermission('resort.budget.manning',config('settings.resort_permissions.create')) == false) d-none @endif"
                            data-bs-toggle="modal" data-bs-target="#departments-modal">
                            <i class="fa-solid fa-plus me-2"></i>New Department
                        </a>
                    </div>
                    <div class="manning-thwrap">
                    <table id="departments-table" class="table  w-100">
                        <thead>
                            <tr>
                                <th class="text-nowrap">Name</th>
                                <th class="text-nowrap">Division</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-nowrap">Action</th>
                            </tr>
                        </thead>
                    </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-sec" role="tabpanel" aria-labelledby="tab-btn-sec">
                    <div class="manning-toolbar">
                        <div class="input-group manning-search">
                            <input type="search" class="form-control" id="sections-search" placeholder="Search sections…" />
                            <i class="fa-solid fa-search"></i>
                        </div>
                        <a href="#" class="btn btn-sm wfp-btn-primary @if(App\Helpers\Common::checkRouteWisePermission('resort.budget.manning',config('settings.resort_permissions.create')) == false) d-none @endif" data-bs-toggle="modal"
                            data-bs-target="#sections-modal">
                            <i class="fa-solid fa-plus me-2"></i>New Section
                        </a>
                    </div>
                    <div class="manning-thwrap">
                    <table id="sections-table" class="table  w-100">
                        <thead>
                            <tr>
                                <th class="text-nowrap">Name</th>
                                <th class="text-nowrap">Division</th>
                                <th class="text-nowrap">Department</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-nowrap">Action</th>
                            </tr>
                        </thead>

                    </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-pos" role="tabpanel" aria-labelledby="tab-btn-pos">
                    <div class="manning-toolbar">
                        <div class="input-group manning-search">
                            <input type="search" class="form-control" id="positions-search" placeholder="Search positions…" />
                            <i class="fa-solid fa-search"></i>
                        </div>
                        <a href="#" class="btn btn-sm wfp-btn-primary @if(App\Helpers\Common::checkRouteWisePermission('resort.budget.manning',config('settings.resort_permissions.create')) == false) d-none @endif" data-bs-toggle="modal"
                            data-bs-target="#positions-modal">
                            <i class="fa-solid fa-plus me-2"></i>New Position
                        </a>
                    </div>
                    <div class="manning-thwrap">
                    <table id="positions-table" class="table  w-100">
                        <thead>
                            <tr>
                                <th class="text-nowrap">Name</th>
                                <th class="text-nowrap">No. of Positions</th>
                                <th class="text-nowrap">Department</th>
                                <th class="text-nowrap">Section</th>
                                <th class="text-nowrap">Division</th>
                                <th class="text-nowrap">Rank</th>
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

<!-- Add Division Modal -->
<div class="modal fade" id="add-divisionmodal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Add Division</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addDivisionForm"  data-parsley-validate>
                    @csrf
                    <div class="form-group mb-2">
                        <label class="division-select1" for="division-select1">Select Existing Division <span class="red-mark">*</span></label>
                        <select id="division-select1" name="division_id" class="form-select select2-modal"
                            data-parsley-errors-container="#div-division_id1"
                            required data-parsley-required-message="Please select a division.">
                            <option value="">Select Division</option>
                        </select>
                        <div id="div-division_id1"></div>

                    </div>


      
                    <label class="mx-auto d-block text-center mb-2">OR</label>

                    <div class="form-group mb-20">
                        <label class="ExistingDivision" for="new-division-name">Add New Division <span class="red-mark">*</span></label>
             
                        <input type="text" id="new-division-name" name="name" class="form-control" placeholder="Add Name of Division" required data-parsley-required-message="Add Name of Division">
                    </div>

					<div class="form-group mb-20">
                        <input type="text" id="new-division-code" name="code" class="form-control"
                               placeholder="Add Code of Division">
                    </div>

                    <div class="form-group mb-3">
                        <select id="status-select" name="status" class="form-select select2-modal" data-parsley-errors-container="#div-division_status" required data-parsley-required-message="Please select a status.">
                            <option value="">Select Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <div id="div-division_status"></div>

                    </div>

                    <div class="modal-footer justify-content-end">
                        <button type="button" class="btn btn-sm wfp-btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm wfp-btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Department Modal -->
<div class="modal fade" id="departments-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-small">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="staticBackdropLabel">Add Department</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
            <form id="addDepartmentForm" data-parsley-validate>
                @csrf

                <!-- Division Selection -->
                <div class="form-group mb-20">
                    <label class="division-select1" for="division-select2">Select Existing Division <span class="red-mark">*</span></label>
                    <select id="division-select2" name="division_id" class="form-select select2-modal"
                        data-parsley-errors-container="#div-division_id"
                        required data-parsley-required-message="Please select a division.">
                        <option value="">Select Division</option>
                    </select>
                    <div id="div-division_id"></div>
                </div>

                <!-- Department Select -->
                <div class="form-group mb-2">
                    <label class="department-select1" for="department-select1">Select Department <span class="red-mark">*</span></label>
                    <select id="department-select1" name="dept_id" class="form-select select2-modal"
                        data-parsley-errors-container="#div-dept_id"
                        required data-parsley-required-message="Please select a department.">
                        <option value="">Select Department</option>
                    </select>
                    <div id="div-dept_id"></div>
                </div>

                <label class="mx-auto d-block text-center mb-2">OR</label>

                <!-- Department Name Input -->
                <div class="form-group mb-20">
                    <label class="department-name-label" for="new-dept-name">Add Name of Department <span class="red-mark">*</span></label>
                    <input type="text" name="name" id="new-dept-name" class="form-control"
                        placeholder="Add Name of Department"
                        required data-parsley-required-message="Department name is required.">
                </div>

                <!-- Department Code -->
                <div class="form-group mb-20">
                    <label for="new-dept-code">Add Code of Department</label>
                    <input type="text" name="code" id="new-dept-code" class="form-control"
                        placeholder="Add Code of Department">
                </div>

                <!-- Status -->
                <div class="form-group mb-3">
                    <select class="form-select select2-modal" name="status"
                        data-parsley-errors-container="#div-status"
                        required data-parsley-required-message="Please select a status.">
                        <option value="">Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <div id="div-status"></div>
                </div>

                <!-- Buttons -->
                <div class="modal-footer justify-content-end">
                    <button type="button" class="btn btn-sm wfp-btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm wfp-btn-primary">Submit</button>
                </div>
            </form>

			</div>
		</div>
	</div>
</div>

<!-- Add Section Modal -->
<div class="modal fade" id="sections-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-small">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="staticBackdropLabel">Add Sections</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
                <form id="addSectionForm" data-parsley-validate>
                    @csrf
                    <div class="form-group mb-20">
                        <label class="department-name-label" for="new-dept-name">Select Department <span class="red-mark">*</span></label>
                        <select id="section-dept" name="dept_id" class="form-select select2-modal"
                         data-parsley-errors-container="#div-section_dept_id"
                         required data-parsley-required-message="Please select a department.">
                            <option value="">Select Department</option>
                        </select>
                        <div id="div-section_dept_id"></div>   

                    </div>

                    <div class="form-group mb-20">
                        <label class="SelectionModel-name-label" for="new-dept-name">Select Section</label>
                        <select id="section-select" name="section_id" class="form-select select2-modal SelectionModel-name-class"
                          data-parsley-errors-container="#div-section_select">
                            <option value="">Select Section (or add new below)</option>
                        </select>
                        <div id="div-section_select"></div>   

                    </div>

                    <label class="mx-auto d-block text-center mb-2">OR</label>

                    <div class="form-group mb-20">
                        <label class="NameofSection-label" for="new-dept-name">Add Name Of Section <span class="red-mark">*</span></label>
                        <input type="text" id="new-section-name" name="name" class="form-control NameofSection-class" required data-parsley-required-message="Please Add Name of Section" placeholder="Add Name of Section">
                    </div>

                    <div class="form-group mb-20">
                        <label class="department-name-label" for="new-dept-name">Add Section Code</label>
                        <input type="text" name="code" id="new-section-code" class="form-control"
                        placeholder="Add Code of Section">
                    </div>

                    <div class="form-group mb-3">
                        <label class="department-name-label" for="new-dept-name">Select Status <span class="red-mark">*</span></label>
                        <select class="form-select select2-modal" name="status"
                          data-parsley-errors-container="#div-section_status" 
                          required data-parsley-required-message="Please select a status.">
                            <option value="">Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <div id="div-section_status"></div>   

                    </div>

                    <div class="modal-footer justify-content-end">
                        <button type="button" class="btn btn-sm wfp-btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm wfp-btn-primary">Submit</button>
                    </div>
                </form>

            </div>
		</div>
	</div>
</div>

<!-- Add Position Modal -->
<div class="modal fade" id="positions-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-small">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="staticBackdropLabel">Add Positions</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
                <form id="addPositionForm" data-parsley-validate>
                    @csrf
                    <div class="form-group mb-20">
                        <label class="position-dept-label">Select Department <span class="red-mark">*</span></label>
                        <select id="position-dept" name="dept_id" class="form-select select2-modal PositionDept-class"
                            data-parsley-errors-container="#div-position_dept_id"
                            required data-parsley-required-message="Please select a department.">
                            <option value="">Select Department</option>
                        </select>
                        <div id="div-position_dept_id"></div>
                    </div>
                    
                    <div class="form-group mb-20">
                        <label class="position-section-label">Select Section</label>
                        <select id="position-section" name="section_id" class="form-select select2-modal PositionSec-class">
                            <option value="">Select Section</option>
                        </select>
                    </div>

                    <div class="form-group mb-20">
                        <label class="position-select-label">Select Position</label>
                        <select id="position-select" name="position_id" class="form-select select2-modal PositionSelect-class"
                            data-parsley-errors-container="#div-position_select">
                            <option value="">Select Position (or add new below)</option>
                        </select>
                        <div id="div-position_select"></div>
                    </div>
                    <label class="mx-auto d-block text-center mb-2">OR</label>
                    <div class="form-group mb-20">
                        <label class="position-title-label">Add Title Of Position <span class="red-mark">*</span></label>
                        <input type="text" id="new-position-title" name="position_title" class="form-control PositionTitle-class"
                            placeholder="Add Title Of Position"
                            required data-parsley-required-message="Please add the title of the position.">
                    </div>
                    <div class="form-group mb-20">
                        <label class="position-code-label">Add Code Of Position</label>
                        <input type="text" id="new-position-code" name="code" class="form-control"
                            placeholder="Add Code Of Position">
                    </div>
                    <div class="form-group mb-20">
                        <label class="position-rank-label">Select Rank <span class="red-mark">*</span></label>
                        <select class="form-select select2-modal" name="Rank" required data-parsley-required-message="Please select a rank.">
                            <option value="">Rank</option>
                            @foreach (config('settings.eligibilty') as  $k => $item)
                                <option value="{{ $k }}">{{ $item }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="position-status-label">Select Status <span class="red-mark">*</span></label>
                        <select class="form-select select2-modal" name="status" required data-parsley-required-message="Please select a status.">
                            <option value="">Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="modal-footer justify-content-end">
                        <button type="button" class="btn btn-sm wfp-btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm wfp-btn-primary">Submit</button>
                    </div>
                </form>

            </div>
		</div>
	</div>
</div>

@endsection

@section('import-css')
@include('resorts.workforce_planning._wfp_buttons_v2_styles')
@include('resorts.manning._manning_styles')
<style>
    /* The People > Employees search box gets its pill shape + teal icon from
       default.css's `.card-header .form-control` / `.card-header .input-group>i`
       rules — this page's search boxes sit in `.card-title`, not `.card-header`,
       so they fell back to the plain generic .form-control look. Same values,
       scoped to this page's search boxes specifically rather than editing the
       shared stylesheet or restructuring these cards' markup. */
    .manning-search .form-control { padding: 10px 18px; border-radius: 13px !important; }
    .manning-search>i { top: 20px; color: var(--teal); }
</style>
@endsection

@section('import-scripts')

<script>
$(document).ready( function() {
        $('#addDepartmentForm').parsley();
        $('#addSectionForm').parsley();
        $('#addDivisionForm').parsley();




    // Function to update validation rules based on user input
    function updateValidation() {
        var selectedDivision = $('#division-select1').val().trim();
        var newDivisionName = $('#new-division-name').val().trim();

        var $divisionSelect = $('#division-select1');
        var $newDivisionInput = $('#new-division-name');

             
        if (selectedDivision && !newDivisionName) 
        {
                    $(".division-select1").show();
                    $(".ExistingDivision").hide();
            $newDivisionInput.removeAttr('required')
                             .removeAttr('data-parsley-required-message')
                             .parsley().reset();
            $divisionSelect.attr('required', 'required')
                           .attr('data-parsley-required-message', 'Please select a division.')
                           .parsley().reset();
        }
        else if (!selectedDivision && newDivisionName)
        {
            $(".division-select1").hide();
            $(".ExistingDivision").show();
            // "Add Name of Division" is filled; make "Select Existing Division" optional
            $divisionSelect.removeAttr('required')
                           .removeAttr('data-parsley-required-message')
                           .parsley().reset();
            $newDivisionInput.attr('required', 'required')
                             .attr('data-parsley-required-message', 'Please enter a division name.')
                             .parsley().reset();
        } else {
            // Neither or both fields are filled; make both required
            $divisionSelect.attr('required', 'required')
                           .attr('data-parsley-required-message', 'Please select a division.')
                           .parsley().reset();
            $newDivisionInput.attr('required', 'required')
                             .attr('data-parsley-required-message', 'Please enter a division name.')
                             .parsley().reset();
        }
    }

    function DepartmentupdateValidation() {
        var selectedDept = $('#department-select1').val().trim();
        var newDeptName = $('#new-dept-name').val().trim();

        var $deptSelect = $('#department-select1');
        var $deptInput = $('#new-dept-name');

        if (selectedDept && !newDeptName) {

            $(".department-name-label").hide();
            
            $(".department-select1").show();
            $deptInput.removeAttr('required')
                    .removeAttr('data-parsley-required-message')
                    .parsley().reset();

            $deptSelect.attr('required', 'required')
                    .attr('data-parsley-required-message', 'Please select a department.')
                    .parsley().reset();
        } else if (!selectedDept && newDeptName) {
            // Only text input is filled
                $(".department-name-label").show();
            
            $(".department-select1").hide();
            $deptSelect.removeAttr('required')
                    .removeAttr('data-parsley-required-message')
                    .parsley().reset();

            $deptInput.attr('required', 'required')
                    .attr('data-parsley-required-message', 'Department name is required.')
                    .parsley().reset();
        } else {
            // Neither or both filled - both required
            $deptSelect.attr('required', 'required')
                    .attr('data-parsley-required-message', 'Please select a department.')
                    .parsley().reset();

            $deptInput.attr('required', 'required')
                    .attr('data-parsley-required-message', 'Department name is required.')
                    .parsley().reset();
        }
    }

    function PositionUpdateValidation()
    {
        var selectedPosition = $('.PositionSelect-class').val().trim();
        var newPositionTitle = $('.PositionTitle-class').val().trim();

        var $positionSelect = $('.PositionSelect-class');
        var $positionInput = $('.PositionTitle-class');

        if (selectedPosition && !newPositionTitle) {
            // If position selected, hide input field requirement
            $(".position-title-label").hide();
            $(".position-select-label").show();

            $positionInput.removeAttr('required')
                .removeAttr('data-parsley-required-message')
                .parsley().reset();

            $positionSelect.attr('required', 'required')
                .attr('data-parsley-required-message', 'Please select a position.')
                .parsley().reset();

        } else if (!selectedPosition && newPositionTitle) {
            // If new position title filled, hide dropdown requirement
            $(".position-title-label").show();
            $(".position-select-label").hide();

            $positionSelect.removeAttr('required')
                .removeAttr('data-parsley-required-message')
                .parsley().reset();

            $positionInput.attr('required', 'required')
                .attr('data-parsley-required-message', 'Please add title of position.')
                .parsley().reset();

        } else {
            // If neither or both filled, make both required
            $(".position-title-label").show();
            $(".position-select-label").show();

            $positionSelect.attr('required', 'required')
                .attr('data-parsley-required-message', 'Please select a position.')
                .parsley().reset();

            $positionInput.attr('required', 'required')
                .attr('data-parsley-required-message', 'Please add title of position.')
                .parsley().reset();
        }
    }

    $('.PositionSelect-class, .PositionTitle-class').on('change keyup', PositionUpdateValidation);

// Trigger the validation on input/change
    $('#department-select1').on('change', function() {
        DepartmentupdateValidation();
        if(!isNaN($(this).val()))
        {
          $(".department-name-label").show();

        }
        else
        {
          $(".department-name-label").hide();
        }
    });
 
    $('#new-dept-name').on('input',function() {
      DepartmentupdateValidation();
        if(!isNaN($(this).val()))
        {
         $(".department-select1").show();

        }
        else
        {
         $(".department-select1").hide();
        }
    });
  
    $('#division-select1').on('change', function() {
        updateValidation();
    });
    $('#new-division-name').on('input', function() {
        updateValidation();
    });


  function SectionupdateValidation() {
    var selectedSection = $('.SelectionModel-name-class').val()?.trim();
    var newSectionName = $('.NameofSection-class').val()?.trim();

    var $sectionSelect = $('.SelectionModel-name-class');
    var $sectionInput = $('.NameofSection-class');

    if (selectedSection && !newSectionName) {
        // Section selected from dropdown
        $(".NameofSection-label").hide();
        $(".SelectionModel-name-label").show();

        $sectionInput.removeAttr('required')
            .removeAttr('data-parsley-required-message')
            .parsley().reset();

        $sectionSelect.attr('required', 'required')
            .attr('data-parsley-required-message', 'Please select a section.')
            .parsley().reset();

    } else if (!selectedSection && newSectionName) {
        // New section name typed
        $(".NameofSection-label").show();
        $(".SelectionModel-name-label").hide();

        $sectionSelect.removeAttr('required')
            .removeAttr('data-parsley-required-message')
            .parsley().reset();

        $sectionInput.attr('required', 'required')
            .attr('data-parsley-required-message', 'Please add name of section.')
            .parsley().reset();

    } else {
        // Both empty or both filled – show both
        $(".NameofSection-label").show();
        $(".SelectionModel-name-label").show();

        $sectionSelect.attr('required', 'required')
            .attr('data-parsley-required-message', 'Please select a section.')
            .parsley().reset();

        $sectionInput.attr('required', 'required')
            .attr('data-parsley-required-message', 'Please add name of section.')
            .parsley().reset();
    }
}

$('.SelectionModel-name-class, .NameofSection-class').on('change keyup', SectionupdateValidation);

   

    SectionupdateValidation();
    $('#section-select').on('change', function() {
        SectionupdateValidation();
        if(!isNaN($(this).val()))
        {
         $(".department-select1").show();

        }
        else
        {
         $(".department-select1").hide();
        }
    });
    $('#new-section-name').on('input', function() {
        SectionupdateValidation();
        if(!isNaN($(this).val()))
        {
         $(".department-select1").show();

        }
        else
        {
         $(".department-select1").hide();
        }
    });
        SectionupdateValidation();
    // Initial validation setup
    updateValidation();
    $('.select2-modal').select2();
    window.resortDivisions = @json($resort_divisions);
    window.resortDepartments = @json($resort_departments);
    window.resortSections = @json($resort_sections);
    window.Position_Rank = @json(config('settings.eligibilty'));

    // Function to reload all 4 DataTables
    function reloadAllTables() {
        $('#divisions-table').DataTable().ajax.reload(null, false);
        $('#departments-table').DataTable().ajax.reload(null, false);
        $('#sections-table').DataTable().ajax.reload(null, false);
        $('#positions-table').DataTable().ajax.reload(null, false);
    }

    // Function to fetch fresh dropdown data from backend and populate modals
    function loadDropdownData(callback) {
        $.ajax({
            url: "{{ route('manning.dropdown.data') }}",
            type: "GET",
            success: function(data) {
                // Update global variables for inline editing
                window.resortDivisions = data.resort_divisions;
                window.resortDepartments = data.resort_departments;
                window.resortSections = data.resort_sections;
                window.Position_Rank = data.eligibility;

                // Division modal - division select (uses global divisions by name)
                var $divSelect1 = $('#division-select1');
                $divSelect1.find('option:not(:first)').remove();
                $.each(data.divisions, function(i, div) {
                    $divSelect1.append('<option value="' + div.name + '">' + div.name + '</option>');
                });

                // Department modal - division select (uses resort_divisions by id)
                var $divSelect2 = $('#division-select2');
                $divSelect2.find('option:not(:first)').remove();
                $.each(data.resort_divisions, function(i, div) {
                    $divSelect2.append('<option value="' + div.id + '">' + div.name + '</option>');
                });

                // Department modal - department select (uses global departments by name)
                var $deptSelect1 = $('#department-select1');
                $deptSelect1.find('option:not(:first)').remove();
                $.each(data.departments, function(i, dept) {
                    $deptSelect1.append('<option value="' + dept.name + '">' + dept.name + '</option>');
                });

                // Section modal - department select (uses resort_departments by id)
                var $sectionDept = $('#section-dept');
                $sectionDept.find('option:not(:first)').remove();
                $.each(data.resort_departments, function(i, dept) {
                    $sectionDept.append('<option value="' + dept.id + '">' + dept.name + '</option>');
                });

                // Section modal - section select (uses global sections by name)
                var $sectionSelect = $('#section-select');
                $sectionSelect.find('option:not(:first)').remove();
                $.each(data.sections, function(i, sec) {
                    $sectionSelect.append('<option value="' + sec.name + '">' + sec.name + '</option>');
                });

                // Position modal - department select (uses resort_departments by id)
                var $posDept = $('#position-dept');
                $posDept.find('option:not(:first)').remove();
                $.each(data.resort_departments, function(i, dept) {
                    $posDept.append('<option value="' + dept.id + '">' + dept.name + '</option>');
                });

                // Position modal - section select (uses resort_sections by id)
                var $posSec = $('#position-section');
                $posSec.find('option:not(:first)').remove();
                $.each(data.resort_sections, function(i, sec) {
                    $posSec.append('<option value="' + sec.id + '">' + sec.name + '</option>');
                });

                // Position modal - position select (uses global positions by title)
                var $posSelect = $('#position-select');
                $posSelect.find('option:not(:first)').remove();
                $.each(data.positions, function(i, pos) {
                    $posSelect.append('<option value="' + pos.position_title + '">' + pos.position_title + '</option>');
                });

                // Reinitialize select2 on modal selects
                $('.select2-modal').select2();

                if (typeof callback === 'function') callback();
            },
            error: function() {
                toastr.error("Failed to load dropdown data.", "Error", {
                    positionClass: 'toast-bottom-right'
                });
            }
        });
    }

    // Load dropdown data when any "Add New" modal is opened
    $('#add-divisionmodal, #departments-modal, #sections-modal, #positions-modal').on('show.bs.modal', function() {
        var $modal = $(this);
        loadDropdownData(function() {
            // Reset form and toggles after data is loaded
            $modal.find('form')[0].reset();
            $modal.find('select').val(null).trigger('change');
            toggleDivisionInput();
            toggleDepartmentInput();
            toggleSectionInput();
            togglePositionInput();
        });
    });
    
    
    // Disable the textbox initially if the select box has a value
    toggleDivisionInput();
    toggleDepartmentInput();
    toggleSectionInput();
    togglePositionInput();

    // On change of the select box
    $('#division-select1').on('change', function() {
        toggleDivisionInput();
      
    });

    $('#department-select1').on('change', function() {
        toggleDepartmentInput();
    });

    $('#section-select').on('change', function() {
        toggleSectionInput();
    });

    $('#position-select').on('change', function() {
        togglePositionInput();
    });

    // On input in the textbox
    $('#new-division-name').on('input', function() {
        toggleDivisionInput();
    });

    $('#new-dept-name').on('input', function() {
        toggleDepartmentInput();
    });

    $('#new-section-name').on('input', function() {
        toggleSectionInput();
    });

    $('#new-position-title').on('input', function() {
        togglePositionInput();
    });

    // Function to toggle between select box and textbox
    function toggleDivisionInput() {
        let selectValue = $('#division-select1').val();
        let textboxValue = $('#new-division-name').val().trim();

        if (selectValue) {
            // If a division is selected, disable the textbox
            $('#new-division-name').prop('disabled', true);
        } else {
            // If no division is selected, enable the textbox
            $('#new-division-name').prop('disabled', false);
        }

        if (textboxValue) {
            // If the textbox has input, disable the select box
            $('#division-select1').prop('disabled', true);
        } else {
            // If the textbox is empty, enable the select box
            $('#division-select1').prop('disabled', false);
        }
    }

    // Function to toggle between select box and textbox
    function toggleDepartmentInput() {
        let selectValue = $('#department-select1').val();
        let textboxValue = $('#new-dept-name').val().trim();

        if (selectValue) {
            // If a division is selected, disable the textbox
            $('#new-dept-name').prop('disabled', true);
        } else {
            // If no division is selected, enable the textbox
            $('#new-dept-name').prop('disabled', false);
        }

        if (textboxValue) {
            // If the textbox has input, disable the select box
            $('#department-select1').prop('disabled', true);
        } else {
            // If the textbox is empty, enable the select box
            $('#department-select1').prop('disabled', false);
        }
    }


    function toggleSectionInput() {
        let selectValue = $('#section-select').val();
        let textboxValue = $('#new-section-name').val().trim();

        if (selectValue) {
            // If a division is selected, disable the textbox
            $('#new-section-name').prop('disabled', true);
        } else {
            // If no division is selected, enable the textbox
            $('#new-section-name').prop('disabled', false);
        }

        if (textboxValue) {
            // If the textbox has input, disable the select box
            $('#section-select').prop('disabled', true);
        } else {
            // If the textbox is empty, enable the select box
            $('#section-select').prop('disabled', false);
        }
    }

    function togglePositionInput() {
        let selectValue = $('#position-select').val();
        let textboxValue = $('#new-position-title').val().trim();

        if (selectValue) {
            // If a division is selected, disable the textbox
            $('#new-position-title').prop('disabled', true);
        } else {
            // If no division is selected, enable the textbox
            $('#new-position-title').prop('disabled', false);
        }

        if (textboxValue) {
            // If the textbox has input, disable the select box
            $('#position-select').prop('disabled', true);
        } else {
            // If the textbox is empty, enable the select box
            $('#position-select').prop('disabled', false);
        }
    }

    var divisionTable = $('#divisions-table').DataTable({
        "searching": true,
        "bLengthChange": false,
        "bFilter": true,
        "bInfo": true,
        "bAutoWidth": false,
        "scrollX": true,
        "iDisplayLength": 6,
        "dom": "rtip", // no default search box — the custom #divisions-search input drives .search() instead
        processing: true,
        serverSide: true,
        order: [[3, 'desc']], // Sort by created_at DESC
        ajax: '{{ route("manning.divisions.data") }}',
        columns: [
            { data: 'name', name: 'name', className: 'text-nowrap' },
            { data: 'status', name: 'status', className: 'text-nowrap' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
            { data: 'created_at', name: 'created_at', visible: false, searchable: false },
        ]
    });

    $('#divisions-search').on('keyup', function () {
        divisionTable.search(this.value).draw();
    });


    $('#addDivisionForm').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: "{{ route('manning.divisions.store') }}",
            type: "POST",
            data: $('#addDivisionForm').serialize(),
            success: function(response) {
                console.log(response);
                if (response.success == true) {
                    $('#add-divisionmodal').modal('hide');
                    reloadAllTables();

                    // Reset the form fields
                    $('#addDivisionForm')[0].reset();
                    $('#addDivisionForm').find('select').val(null).trigger('change');

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
                console.log("Full error response:", xhr);
                // Clear previous errors
                $('#addDivisionForm').find('.error-message').remove();

                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function(key, error) {
                        let field = $('[name="' + key + '"]');
                        field.after('<span class="error-message text-danger">' + error[0] + '</span>');
                    });
                } else {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : "An error occurred while creating or updating.";
                    toastr.error(msg, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            }
        });
    });

    $(document).on("click", "#divisions-table .edit-row-btn", function (event) {
        event.preventDefault(); // Prevent default action
        // Find the parent row
        var $row = $(this).closest("tr");
        // Extract division ID
        var divisionId = $(this).data('division-id');
        // Extract current values from the row (Name, Status)
        var currentName = $row.find("td:nth-child(1)").text().trim();
        var currentStatus = $row.find("td:nth-child(2)").text().trim().toLowerCase();
        var editRowHtml = `
            <td class="py-1">
                <div class="form-group">
                    <input type="text" class="form-control" value="${currentName}" />
                </div>
            </td>
            <td class="py-1">
                <div class="form-group">
                    <select class="form-select select2-modal">
                        <option ${currentStatus === "active" ? "selected" : ""} value="active">Active</option>
                        <option ${currentStatus === "inactive" ? "selected" : ""} value="inactive">Inactive</option>
                    </select>
                </div>
            </td>
            <td class="py-1">
                <a href="#" class="btn wfp-btn-primary update-row-btn" data-division-id="${divisionId}">Submit</a>
            </td>
        `;
        // Replace row content with editable form
        $row.html(editRowHtml);
    });

    // Handle click on update button
    $(document).on("click", "#divisions-table .update-row-btn", function (event) {
        event.preventDefault(); // Prevent default action

        var $row = $(this).closest("tr");

        // Get updated values (Name, Status)
        var divisionId = $(this).data('division-id');
        var updatedName = $row.find("input").eq(0).val();
        var updatedStatus = $row.find("select").val();

            const hasScriptTag = str => /<\s*script\b[^>]*>(.*?)<\s*\/\s*script\s*>/gi.test(str);

                // Allow letters, numbers, spaces, dashes (-), underscores (_), dots (.), forward slashes (/), apostrophes ('), and ampersands (&)
                const hasInvalidChar = str => /[^a-zA-Z0-9\s\-_.\/'&]/g.test(str);

                function fieldsAreClean(...fields) {
                    return fields.every(v => {
                        // First check for script tags
                        if (hasScriptTag(v)) {
                            return false;
                        }
                        
                        // Then check for invalid characters
                        if (hasInvalidChar(v)) {
                            return false;
                        }
                        
                        return true;
                    });
                }

                // Validate inputs
                if (!fieldsAreClean(updatedName)) {
                    toastr.error(
                        "Invalid characters detected. Only letters, numbers, spaces, dashes (-), underscores (_), dots (.), forward slashes (/), apostrophes ('), and ampersands (&) are allowed. Script tags are strictly prohibited.",
                        "Validation Error",
                        { positionClass: "toast-bottom-right" }
                    );
                    return;
                }
        $.ajax({
            url: "{{ route('manning.divisions.inlineUpdate', '') }}/" + divisionId,
            type: "PUT",
            data: {
                name: updatedName,
                status: updatedStatus
            },
            success: function(response) {
                if(response.success == true) {
                    var statusClass = updatedStatus === 'active' ? 'text-success' : 'text-danger';
                    var statusLabel = updatedStatus.charAt(0).toUpperCase() + updatedStatus.slice(1);

                    var updatedRowHtml = `
                        <td class="text-nowrap">${updatedName}</td>
                        <td class="text-nowrap ${statusClass}">${statusLabel}</td>
                        <td class="text-nowrap">
                            <div class="d-flex align-items-center">
                                <a href="#" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-division-id="${divisionId}">
                                    <img src="{{ asset('resorts_assets/images/edit.svg') }}" alt="" class="img-fluid" />
                                </a>
                                <a href="#" class="btn-lg-icon icon-bg-red delete-row-btn" data-division-id="${divisionId}">
                                    <img src="{{ asset('resorts_assets/images/trash-red.svg') }}" alt="" class="img-fluid" />
                                </a>
                            </div>
                        </td>
                    `;

                    $row.html(updatedRowHtml);
                    reloadAllTables();

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
                toastr.error("An error occurred while updating the division.", "Error", {
                    positionClass: 'toast-bottom-right'
                });
            }
        });

    });

    // Confirmation dialog before delete
    $(document).on('click', '#divisions-table .delete-row-btn', function (e) {
        e.preventDefault();
        // Get the division ID from the data attribute
        var divisionId = $(this).data('division-id');
        wisdomConfirm({
            role: 'destructive',
            title: 'Sure want to delete?',
            text: 'This cannot be undone',
            confirmText: 'Yes',
            cancelText: 'No'
        }).then((result) => {
        if (result.isConfirmed) {
                $.ajax({
                    type: "DELETE",
                    url: "{{ route('manning.divisons.destroy', '') }}/" + divisionId,
                    dataType: "json",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                }).done(function(result) {
                    if (result.success == true) {
                        toastr.success(result.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });

                        reloadAllTables();
                    } else {
                        toastr.error(result.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                }).fail(function(error) {
                    toastr.error("Something went wrong", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                });
            }
        });
    });

    var deptTable = $('#departments-table').dataTable({
        "searching": true,
        "bLengthChange": false,
        "bFilter": true,
        "bInfo": true,
        "bAutoWidth": false,
        "scrollX": true,
        "iDisplayLength": 6,
        "dom": "rtip", // no default search box — the custom #departments-search input drives .search() instead
        processing: true,
        serverSide: true,
        order:[[4,'desc']],
        ajax: '{{ route("manning.departments.data") }}',
        columns: [
            { data: 'name', name: 'name' },
            { data: 'division', name: 'division' },
            { data: 'status', name: 'status' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
            {data:'created_at',visible:false,searchable:false},
        ]
    });

    $('#departments-search').on('keyup', function () {
        $('#departments-table').DataTable().search(this.value).draw();
    });

    $('#addDepartmentForm').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: "{{ route('manning.departments.store') }}",
            type: "POST",
            data: $('#addDepartmentForm').serialize(),
            success: function(response) {
                if(response.success == true) {
                    $('#departments-modal').modal('hide');
                    reloadAllTables();

                     // Reset the form fields
                     $('#addDepartmentForm')[0].reset();

                    // Optionally reset select2 dropdowns or other dynamic elements if needed
                    $('#addDepartmentForm').find('select').val(null).trigger('change');

                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                } else {
                    toastr.error(response.message, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            }
            ,
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

    $(document).on("click", "#departments-table .edit-row-btn", function (event) {
        event.preventDefault();

        var $row = $(this).closest("tr");
        var deptId = $(this).data('dept-id');

        var currentName = $row.find("td:nth-child(1)").text().trim();
        var currentDivision = $row.find("td:nth-child(2)").text().trim();
        var currentStatus = $row.find("td:nth-child(3)").text().trim().toLowerCase();

        var divisionOptions = '<option value="">Select Division</option>';
        window.resortDivisions.forEach(function(division) {
            var selected = (currentDivision === division.name) ? 'selected' : '';
            divisionOptions += `<option value="${division.id}" ${selected}>${division.name}</option>`;
        });

        var editRowHtml = `
            <td class="py-1">
                <div class="form-group">
                    <input type="text" class="form-control" value="${currentName}" />
                </div>
            </td>
            <td class="py-1">
                <select id="division-select" name="division_id" class="form-select select2-modal">
                    ${divisionOptions}
                </select>
            </td>
            <td class="py-1">
                <div class="form-group">
                    <select class="form-select select2-modal">
                        <option ${currentStatus === "active" ? "selected" : ""} value="active">Active</option>
                        <option ${currentStatus === "inactive" ? "selected" : ""} value="inactive">Inactive</option>
                    </select>
                </div>
            </td>
            <td class="py-1">
                <a href="#" class="btn wfp-btn-primary update-row-btn" data-dept-id="${deptId}">Submit</a>
            </td>
        `;

        $row.html(editRowHtml);

        // Re-initialize Select2 if you're using it
    });

    // Handle click on update button
    $(document).on("click", "#departments-table .update-row-btn", function (event) {
        event.preventDefault(); // Prevent default action

        var $row = $(this).closest("tr");
        // Get updated values (Name, Division, Status)
        var dept_id = $(this).data('dept-id');
        var updatedName = $row.find("input").eq(0).val();
        var updatedDivision = $row.find("select").eq(0).val();
        var updatedStatus = $row.find("select").eq(1).val();
       
                const hasScriptTag = str => /<\s*script\b[^>]*>(.*?)<\s*\/\s*script\s*>/gi.test(str);

                // Allow letters, numbers, spaces, dashes (-), underscores (_), dots (.), forward slashes (/), apostrophes ('), and ampersands (&)
                const hasInvalidChar = str => /[^a-zA-Z0-9\s\-_.\/'&]/g.test(str);

                function fieldsAreClean(...fields) {
                    return fields.every(v => {
                        // First check for script tags
                        if (hasScriptTag(v)) {
                            return false;
                        }
                        
                        // Then check for invalid characters
                        if (hasInvalidChar(v)) {
                            return false;
                        }
                        
                        return true;
                    });
                }

                // Validate inputs
                if (!fieldsAreClean(updatedName)) {
                    toastr.error(
                        "Invalid characters detected. Only letters, numbers, spaces, dashes (-), underscores (_), dots (.), forward slashes (/), apostrophes ('), and ampersands (&) are allowed. Script tags are strictly prohibited.",
                        "Validation Error",
                        { positionClass: "toast-bottom-right" }
                    );
                    return;
                }
        $.ajax({
            url: "{{ route('manning.departments.inlineUpdate', '') }}/" + dept_id,
            type: "PUT",
            data: {
                name: updatedName,
                division:updatedDivision,
                status: updatedStatus
            },
            success: function(response) {
                if(response.success == true) {
                    var statusClass = updatedStatus === 'active' ? 'text-success' : 'text-danger';
                    var statusLabel = updatedStatus.charAt(0).toUpperCase() + updatedStatus.slice(1);
                    var updatedDivisionName = response.divisionName;

                    var updatedRowHtml = `
                        <td class="text-nowrap">${updatedName}</td>
                        <td class="text-nowrap">${updatedDivisionName}</td>
                        <td class="text-nowrap ${statusClass}">${statusLabel}</td>
                        <td class="text-nowrap">
                            <div class="d-flex align-items-center">
                                <a href="#" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-dept-id="${dept_id}">
                                    <img src="{{ asset('resorts_assets/images/edit.svg') }}" alt="" class="img-fluid" />
                                </a>
                                <a href="#" class="btn-lg-icon icon-bg-red delete-row-btn" data-dept-id="${dept_id}">
                                    <img src="{{ asset('resorts_assets/images/trash-red.svg') }}" alt="" class="img-fluid" />
                                </a>
                            </div>
                        </td>
                    `;

                    $row.html(updatedRowHtml);
                    reloadAllTables();
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

    // Confirmation dialog before delete
    $(document).on('click', '#departments-table .delete-row-btn', function (e) {
        e.preventDefault();
        // Get the division ID from the data attribute
        var dept_id = $(this).data('dept-id');
        wisdomConfirm({
            role: 'destructive',
            title: 'Sure want to delete?',
            text: 'This cannot be undone',
            confirmText: 'Yes',
            cancelText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "DELETE",
                    url: "{{ route('manning.departments.destroy', '') }}/" + dept_id,
                    dataType: "json",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                }).done(function(result) {
                    if (result.success == true) {
                        toastr.success(result.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });

                        reloadAllTables();
                    } else {
                        toastr.error(result.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                }).fail(function(error) {
                    toastr.error("Something went wrong", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                });
            }
        });
    });

    var sectionTable = $('#sections-table').dataTable({
        "searching": true,
        "bLengthChange": false,
        "bFilter": true,
        "bInfo": true,
        "bAutoWidth": false,
        "scrollX": true,
        "iDisplayLength": 6,
        "dom": "rtip", // no default search box — the custom #sections-search input drives .search() instead
        processing: true,
        serverSide: true,
        order:[[5,'desc']],
        ajax: '{{ route("manning.sections.data") }}',
        columns: [
            { data: 'name', name: 'name' },
            { data: 'division', name: 'division' },
            { data: 'department', name: 'department' },
            { data: 'status', name: 'status' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
            {data:'created_at',visible:false,searchable:false},
        ]
    });

    $('#sections-search').on('keyup', function () {
        $('#sections-table').DataTable().search(this.value).draw();
    });

    $('#addSectionForm').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: "{{ route('manning.sections.store') }}",
            type: "POST",
            data: $('#addSectionForm').serialize(),
            success: function(response) {
                if(response.success == true) {
                    $('#sections-modal').modal('hide');
                    reloadAllTables();

                    // Reset the form fields
                    $('#addSectionForm')[0].reset();

                    // Optionally reset select2 dropdowns or other dynamic elements if needed
                    $('#addSectionForm').find('select').val(null).trigger('change');

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

    $(document).on("click", "#sections-table .edit-row-btn", function (event) {
        event.preventDefault();

        var $row = $(this).closest("tr");
        var sectionId = $(this).data('section-id');

        var currentName = $row.find("td:nth-child(1)").text().trim();
        var currentDivision = $row.find("td:nth-child(2)").text().trim();
        var currentDepartment = $row.find("td:nth-child(3)").text().trim();
        var currentStatus = $row.find("td:nth-child(4)").text().trim().toLowerCase();

        // Use the `resortDivisions` array from the global scope
        var divisionOptions = '<option value="">Select Division</option>';
        window.resortDivisions.forEach(function(division) {
            var selected = (currentDivision === division.name) ? 'selected' : '';
            divisionOptions += `<option value="${division.id}" ${selected}>${division.name}</option>`;
        });

        var deptOptions = '<option value="">Select Departments</option>';
        window.resortDepartments.forEach(function(dept) {
            var selected = (currentDepartment === dept.name) ? 'selected' : '';
            deptOptions += `<option value="${dept.id}" ${selected}>${dept.name}</option>`;
        });

        var editRowHtml = `
            <td class="py-1">
                <div class="form-group">
                    <input type="text" class="form-control" value="${currentName}" />
                </div>
            </td>
            <td class="py-1">
                <select id="division-select3" name="division_id" class="form-select select2-modal">
                    ${divisionOptions}
                </select>
            </td>
            <td class="py-1">
                <select id="department-select2" name="dept_id" class="form-select select2-modal">
                    ${deptOptions}
                </select>
            </td>
            <td class="py-1">
                <div class="form-group">
                    <select class="form-select select2-modal">
                        <option ${currentStatus === "active" ? "selected" : ""} value="active">Active</option>
                        <option ${currentStatus === "inactive" ? "selected" : ""} value="inactive">Inactive</option>
                    </select>
                </div>
            </td>
            <td class="py-1">
                <a href="#" class="btn wfp-btn-primary update-row-btn" data-section-id="${sectionId}">Submit</a>
            </td>
        `;

        $row.html(editRowHtml);

        // Re-initialize Select2 if you're using it
        $('#section-select').select2();
    });

    // Handle click on update button
    $(document).on("click", "#sections-table .update-row-btn", function (event) {
        event.preventDefault(); // Prevent default action

        var $row = $(this).closest("tr");

        // Get updated values (Name, Division, Department, Status)
        var section_id = $(this).data('section-id');
        var updatedName = $row.find("input").eq(0).val();
        var updatedDivision = $row.find("select").eq(0).val();
        var updatedDepartment = $row.find("select").eq(1).val();
        var updatedStatus = $row.find("select").eq(2).val();

            const hasScriptTag = str => /<\s*script\b[^>]*>(.*?)<\s*\/\s*script\s*>/gi.test(str);

                // Allow letters, numbers, spaces, dashes (-), underscores (_), dots (.), forward slashes (/), apostrophes ('), and ampersands (&)
                const hasInvalidChar = str => /[^a-zA-Z0-9\s\-_.\/'&]/g.test(str);

                function fieldsAreClean(...fields) {
                    return fields.every(v => {
                        // First check for script tags
                        if (hasScriptTag(v)) {
                            return false;
                        }
                        
                        // Then check for invalid characters
                        if (hasInvalidChar(v)) {
                            return false;
                        }
                        
                        return true;
                    });
                }

                // Validate inputs
                if (!fieldsAreClean(updatedName)) {
                    toastr.error(
                        "Invalid characters detected. Only letters, numbers, spaces, dashes (-), underscores (_), dots (.), forward slashes (/), apostrophes ('), and ampersands (&) are allowed. Script tags are strictly prohibited.",
                        "Validation Error",
                        { positionClass: "toast-bottom-right" }
                    );
                    return;
                }
        $.ajax({
            url: "{{ route('manning.sections.inlineUpdate') }}",
            type: "POST",
            data: {
                "id":section_id,
                name: updatedName,
                division:updatedDivision,
                department:updatedDepartment,
                status: updatedStatus
            },
            success: function(response) {
                if(response.success == true) {
                    var statusClass = updatedStatus === 'active' ? 'text-success' : 'text-danger';
                    var statusLabel = updatedStatus.charAt(0).toUpperCase() + updatedStatus.slice(1);
                    var updatedDivisionName = response.divisionName;
                    var updatedDepartmentName = response.deptName;

                    var updatedRowHtml = `
                        <td class="text-nowrap">${updatedName}</td>
                        <td class="text-nowrap">${updatedDivisionName}</td>
                         <td class="text-nowrap">${updatedDepartmentName}</td>
                        <td class="text-nowrap ${statusClass}">${statusLabel}</td>
                        <td class="text-nowrap">
                            <div class="d-flex align-items-center">
                                <a href="#" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-section-id="${section_id}">
                                    <img src="{{ asset('resorts_assets/images/edit.svg') }}" alt="" class="img-fluid" />
                                </a>
                                <a href="#" class="btn-lg-icon icon-bg-red delete-row-btn" data-section-id="${section_id}">
                                    <img src="{{ asset('resorts_assets/images/trash-red.svg') }}" alt="" class="img-fluid" />
                                </a>
                            </div>
                        </td>
                    `;

                    $row.html(updatedRowHtml);
                    reloadAllTables();

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
                    console.log(xhr.responseJSON);

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

    // Confirmation dialog before delete
    $(document).on('click', '#sections-table .delete-row-btn', function (e) {
        e.preventDefault();
        // Get the division ID from the data attribute
        var section_id = $(this).data('section-id');
        wisdomConfirm({
            role: 'destructive',
            title: 'Sure want to delete?',
            text: 'This cannot be undone',
            confirmText: 'Yes',
            cancelText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "DELETE",
                    url: "{{ route('manning.sections.destroy', '') }}/" + section_id,
                    dataType: "json",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                }).done(function(result) {
                    if (result.success == true) {
                        toastr.success(result.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });

                        reloadAllTables();
                    } else {
                        toastr.error(result.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                }).fail(function(error) {
                    toastr.error("Something went wrong", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                });
            }
        });
    });

    var positionTable = $('#positions-table').dataTable({
        "searching": true,
        "bLengthChange": false,
        "bFilter": true,
        "bInfo": true,
        "bAutoWidth": false,
        "scrollX": true,
        "iDisplayLength": 6,
        "dom": "rtip", // no default search box — the custom #positions-search input drives .search() instead
        processing: true,
        serverSide: true,
        order:[[8,'desc']],
        ajax: '{{ route("manning.positions.data") }}',
        columns: [
            { data: 'position_title', name: 'position_title' },
            { data: 'no_of_positions', name: 'no_of_positions'},
            { data: 'department', name: 'department' },
            { data: 'section', name: 'section' },
            { data: 'division', name: 'division' },
            { data: 'Rank', name: 'Rank' },
            { data: 'status', name: 'status' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
            {data:'created_at',visible:false,searchable:false},
        ]
    });

    $('#positions-search').on('keyup', function () {
        $('#positions-table').DataTable().search(this.value).draw();
    });

    $('#addPositionForm').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: "{{ route('manning.positions.store') }}",
            type: "POST",
            data: $('#addPositionForm').serialize(),
            success: function(response) {
                if(response.success == true) {
                    $('#positions-modal').modal('hide');
                    reloadAllTables();

                    // Reset the form fields
                    $('#addPositionForm')[0].reset();

                    // Optionally reset select2 dropdowns or other dynamic elements if needed
                    $('#addPositionForm').find('select').val(null).trigger('change');

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

    $(document).on("click", "#positions-table .edit-row-btn", function (event) {
        event.preventDefault();

        var $row = $(this).closest("tr");
        var positionId = $(this).data('position-id');

        var currentName = $row.find("td:nth-child(1)").text().trim();
        var currentNoOfPositions = $row.find("td:nth-child(2)").text().trim();
        var currentDepartment = $row.find("td:nth-child(3)").text().trim();
        var currentSection = $row.find("td:nth-child(4)").text().trim();
        var currentDivision = $row.find("td:nth-child(5)").text().trim();
        var rankId = $row.find("td:nth-child(6)").text().trim();
        var currentStatus = $row.find("td:nth-child(7)").text().trim().toLowerCase();
        var divisionOptions = '<option value="">Select Division</option>';

        // var rankId = $row.find('input[name="rankId"]').val(); // Capture the rankId from the input field

        window.resortDivisions.forEach(function(division) {
            var selected = (currentDivision === division.name) ? 'selected' : '';
            divisionOptions += `<option value="${division.id}" ${selected}>${division.name}</option>`;
        });

        var deptOptions = '<option value="">Select Departments</option>';
        window.resortDepartments.forEach(function(dept) {
            var selected = (currentDepartment === dept.name) ? 'selected' : '';
            deptOptions += `<option value="${dept.id}" ${selected}>${dept.name}</option>`;
        });

        var sectionOptions = '<option value="">Select Section</option>';
        window.resortSections.forEach(function(section) {
            var selected = (currentSection === section.name) ? 'selected' : '';
            sectionOptions += `<option value="${section.id}" ${selected}>${section.name}</option>`;
        });

        var RankOptions = '<option value="">Select Rank</option>';

        $.each(window.Position_Rank, function(index, value) {
             var selected = (rankId === value) ? 'selected' : '';
            RankOptions += `<option value="${index}"  ${selected}>${value}</option>`;
        });
        // console.log(RankOptions);
        var editRowHtml = `
            <td class="py-1">
                <div class="form-group">
                    <input type="text" name="position_title" class="form-control" value="${currentName}" />
                </div>  
            </td>
            <td class="py-1">
                <div class="form-group">
                    <span>${currentNoOfPositions}</span> <!-- Display non-editable number of positions -->
                </div>
            </td>

            <td class="py-1">
                <select id="department-select3" name="dept_id" class="form-select select2-modal">
                    ${deptOptions}
                </select>
            </td>
            <td class="py-1">
                <select id="section-select3" name="section_id" class="form-select select2-modal">
                    ${sectionOptions}
                </select>
            </td>
            <td class="py-1">
                <select id="division-select4" name="division_id" class="form-select select2-modal">
                    ${divisionOptions}
                </select>
            </td>
            <td class="py-1">
                <div class="form-group">
                    <select class="form-select select2-modal">
                        ${RankOptions}
                    </select>
                </div>
            </td>
            <td class="py-1">
                <div class="form-group">
                    <select class="form-select select2-modal">
                        <option ${currentStatus === "active" ? "selected" : ""} value="active">Active</option>
                        <option ${currentStatus === "inactive" ? "selected" : ""} value="inactive">Inactive</option>
                    </select>
                </div>
            </td>
            <td class="py-1">
                <a href="#" class="btn wfp-btn-primary update-row-btn" data-position-id="${positionId}">Submit</a>
            </td>
        `;

        $row.html(editRowHtml);

        // Re-initialize Select2 if you're using it
        $('#position-select').select2();
        positionTable.draw();
    });

    // Handle click on update button
    $(document).on("click", "#positions-table .update-row-btn", function (event) {
        event.preventDefault(); // Prevent default action

        var $row = $(this).closest("tr");

        // Get updated values
        var position_id = $(this).data('position-id');
        var updatedName = $row.find("input").eq(0).val();
        var updatednoofPositions = $row.find("td:nth-child(2)").text().trim();
        var updatedDepartment = $row.find("select").eq(0).val();
        var updatedSection = $row.find("select").eq(1).val();
        var updatedDivision = $row.find("select").eq(2).val();
        var Rank = $row.find("select").eq(3).val();
        var updatedStatus = $row.find("select").eq(4).val();

        $.ajax({
            url: "{{ route('manning.positions.inlineUpdate', '') }}/" + position_id,
            type: "PUT",
            data: {
                name: updatedName,
                no_of_positions:updatednoofPositions,
                division:updatedDivision,
                department:updatedDepartment,
                section:updatedSection,
                status: updatedStatus,
                Rank:Rank
            },
            success: function(response) {
                if(response.success == true) {
                    var statusClass = updatedStatus === 'active' ? 'text-success' : 'text-danger';
                    var statusLabel = updatedStatus.charAt(0).toUpperCase() + updatedStatus.slice(1);
                    var updatedDivisionName = response.divisionName;
                    var updatedDepartmentName = response.deptName;
                    var updatedSectionName = response.sectionName;
                    var RankName = response.Rank;

                    var updatedRowHtml = `
                        <td class="text-nowrap">${updatedName}</td>
                        <td class="text-nowrap">${updatednoofPositions}</td>
                        <td class="text-nowrap">${updatedDepartmentName}</td>
                        <td class="text-nowrap">${updatedSectionName}</td>
                        <td class="text-nowrap">${updatedDivisionName}</td>
                        <td class="text-nowrap">${RankName}</td>
                        <td class="text-nowrap ${statusClass}">${statusLabel}</td>
                        <td class="text-nowrap">
                            <div class="d-flex align-items-center">
                                <a href="#" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-position-id="${position_id}">
                                    <img src="{{ asset('resorts_assets/images/edit.svg') }}" alt="" class="img-fluid" />
                                </a>
                                <a href="#" class="btn-lg-icon icon-bg-red delete-row-btn" data-position-id="${position_id}">
                                    <img src="{{ asset('resorts_assets/images/trash-red.svg') }}" alt="" class="img-fluid" />
                                </a>
                            </div>
                        </td>
                    `;

                    $row.html(updatedRowHtml);
                    reloadAllTables();

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

    // Confirmation dialog before delete
    $(document).on('click', '#positions-table .delete-row-btn', function (e) {
        e.preventDefault();
        // Get the division ID from the data attribute
        var position_id = $(this).data('position-id');
        wisdomConfirm({
            role: 'destructive',
            title: 'Sure want to delete?',
            text: 'This cannot be undone',
            confirmText: 'Yes',
            cancelText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "DELETE",
                    url: "{{ route('manning.positions.destroy', '') }}/" + position_id,
                    dataType: "json",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                }).done(function(result) {
                    if (result.success == true) {
                        toastr.success(result.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });

                        reloadAllTables();
                    } else {
                        toastr.error(result.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                }).fail(function(error) {
                    toastr.error("Something went wrong", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                });
            }
        });
    });

        handleHashNavigation();

    // Also handle if the hash changes during the session
    $(window).on('hashchange', function() {
        handleHashNavigation();
    });

    // DataTables sizes columns against the container's width at init time.
    // Departments/Sections/Positions sit inside Bootstrap tab-panes that
    // start hidden (display:none), so their headers get calculated against
    // a zero-width container while the body rows (rendered later once the
    // tab is actually shown) size themselves normally — header and body
    // cells end up on different grids. Re-adjust the just-shown table's
    // column widths against its now-visible container each time a tab
    // becomes active (covers both a manual tab click and a hash-triggered
    // switch from handleHashNavigation(), since both fire shown.bs.tab).
    var manningTabTableMap = {
        '#tab-div': '#divisions-table',
        '#tab-dep': '#departments-table',
        '#tab-sec': '#sections-table',
        '#tab-pos': '#positions-table',
    };
    $('#manningTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        var tableId = manningTabTableMap[$(e.target).attr('data-bs-target')];
        if (tableId && $.fn.DataTable.isDataTable(tableId)) {
            $(tableId).DataTable().columns.adjust().draw(false);
        }
    });

});

function getHashFragment() 
{
    const hashFragment = window.location.hash.substring(1);
    return hashFragment;
}

function handleHashNavigation() {
    const fragment = getHashFragment();

    if (fragment) {
        // Divisions/Departments/Sections/Positions now live behind tabs
        // instead of stacked cards — map the historical hash fragment to
        // its tab first, then scroll/highlight the now-visible pane.
        const tabMap = {
            division: 'tab-btn-div', divisions: 'tab-btn-div',
            department: 'tab-btn-dep', departments: 'tab-btn-dep',
            section: 'tab-btn-sec', sections: 'tab-btn-sec',
            position: 'tab-btn-pos', positions: 'tab-btn-pos',
        };
        const tabBtnId = tabMap[fragment.toLowerCase()];
        const tabBtnEl = tabBtnId && document.getElementById(tabBtnId);

        if (tabBtnEl) {
            bootstrap.Tab.getOrCreateInstance(tabBtnEl).show();
        }

        const sectionElement = tabBtnEl ? document.getElementById('manningTabs').closest('.manning-card') : document.getElementById(fragment);

        if (sectionElement) {
            // Scroll to the section
            setTimeout(() => {
                sectionElement.scrollIntoView({ behavior: 'smooth' });

                // Highlight the section
                sectionElement.classList.add('highlight-section');
                setTimeout(() => {
                    sectionElement.classList.remove('highlight-section');
                }, 3000);
            }, 300); // Small delay to ensure the page is ready

            // If it has a table, you can handle it here
            const tableId = `${fragment.toLowerCase()}-table`;
            const tableElement = document.getElementById(tableId);

            if (tableElement && $.fn.DataTable.isDataTable(`#${tableId}`)) {
                const table = $(`#${tableId}`).DataTable();
                // Handle the table as needed
            }
        }
    }
}

</script>
@endsection
