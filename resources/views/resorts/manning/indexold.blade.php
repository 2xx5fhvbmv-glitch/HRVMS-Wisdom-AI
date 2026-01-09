@extends('resorts.layouts.app')
@section('page_tab_title' ,"Manning")

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
                        <span>WORKFORCE PLANNING</span>
                        <h1>Manning</h1>
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
                                        <h3>Divisions</h3>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="d-flex justify-content-sm-end align-items-center">
                                        <a href="#" class="btn btn-sm btn-theme " data-bs-toggle="modal"
                                            data-bs-target="#add-divisionmodal">
                                            <i class="fa-solid fa-plus me-2"></i>Add New
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <table id="divisions-table" class="table  w-100">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">Name</th>
                                    <th class="text-nowrap">Code</th>
                                    <th class="text-nowrap">Short Name</th>
                                    <th class="text-nowrap">Status</th>
                                    <th class="text-nowrap">Action</th>
                                </tr>
                            </thead>

                        </table>
                    </div>
                </div>

                <div class="col-xxl-12 col-xl-12 col-lg-12">
                <!-- <div class="col-xxl-6 col-xl-6 col-lg-6"> -->
                    <div class="card">
                        <div class="card-title">
                            <div class="row g-3 align-items-center justify-content-between">
                                <div class="col-auto">
                                    <div class="d-flex justify-content-start align-items-center">
                                        <h3>Departments</h3>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="d-flex justify-content-sm-end align-items-center">
                                        <a href="#departments-modal" class="btn btn-sm btn-theme "
                                            data-bs-toggle="modal" data-bs-target="#">
                                            <i class="fa-solid fa-plus me-2"></i>Add New
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <table id="departments-table" class="table  w-100">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">Name</th>
                                    <th class="text-nowrap">Division</th>
                                    <th class="text-nowrap">Code</th>
                                    <th class="text-nowrap">Short Name</th>
                                    <th class="text-nowrap">Status</th>
                                    <th class="text-nowrap">Action</th>
                                </tr>
                            </thead>

                        </table>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card">
                        <div class="card-title">
                            <div class="row g-3 align-items-center justify-content-between">
                                <div class="col-auto">
                                    <div class="d-flex justify-content-start align-items-center">
                                        <h3>Sections</h3>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="d-flex justify-content-sm-end align-items-center">
                                        <a href="#" class="btn btn-sm btn-theme " data-bs-toggle="modal"
                                            data-bs-target="#sections-modal">
                                            <i class="fa-solid fa-plus me-2"></i>Add New
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <table id="sections-table" class="table  w-100">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">Name</th>
                                    <th class="text-nowrap">Division</th>
                                    <th class="text-nowrap">Departments</th>
                                    <th class="text-nowrap">Code</th>
                                    <th class="text-nowrap">Short Name</th>
                                    <th class="text-nowrap">Status</th>
                                    <th class="text-nowrap">Action</th>
                                </tr>
                            </thead>

                        </table>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card">
                        <div class="card-title">
                            <div class="row g-3 align-items-center justify-content-between">
                                <div class="col-auto">
                                    <div class="d-flex justify-content-start align-items-center">
                                        <h3>Positions</h3>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="d-flex justify-content-sm-end align-items-center">
                                        <!-- <div class="form-group table-search">
                                            <input type="text" class="img-fluid" placeholder="Search" />
                                            <a href="#">
                                                <img src="assets/images/search-green.svg" alt=""
                                                    class="img-fluid" />
                                            </a>
                                        </div> -->
                                        <a href="#" class="btn btn-sm btn-theme " data-bs-toggle="modal"
                                            data-bs-target="#positions-modal">
                                            <i class="fa-solid fa-plus me-2"></i>Add New
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <table id="positions-table" class="table  w-100">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">Name</th>
                                    <th class="text-nowrap">No of positions</th>
                                    <th class="text-nowrap">Departments</th>
                                    <th class="text-nowrap">Division</th>
                                    <th class="text-nowrap">Code</th>
                                    <th class="text-nowrap">Short Title</th>
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
                <form id="addDivisionForm">
                    @csrf
                    <div class="form-group mb-2">
                        <label for="division-select">Select Division</label>
                        <select id="division-select" name="division_id" class="form-select select2t-none">
                            <option value="">Select Division</option>
                            @foreach($divisions as $division)
                                <option value="{{ $division->name }}">{{ $division->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <label class="mx-auto d-block text-center mb-2">OR</label>

                    <div class="form-group mb-20">
                        <input type="text" id="new-division-name" name="division_name" class="form-control"
                               placeholder="Add Name of Division">
                    </div>

					<div class="form-group mb-20">
                        <input type="text" id="new-division-code" name="division_code" class="form-control"
                               placeholder="Add Code of Division">
                    </div>

					<div class="form-group mb-20">
                        <input type="text" id="new-division-short-name" name="division_short_name" class="form-control" placeholder="Add Short Name of Division">
                    </div>

                    <div class="form-group mb-2">
                        <label for="status-select">Status</label>
                        <select id="status-select" name="status" class="form-select select2t-none">
                            <option value="">Select Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="modal-footer justify-content-end">
                        <button type="button" class="btn btn-sm btn-themeGray me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-theme">Submit</button>
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
                <form id="addDepartmentForm">
                    @csrf
                    <div class="form-group mb-20">
                        <label for="division-select">Select Existing Division</label>
                        <select id="division-select" name="division_id" class="form-select select2t-none">
                            <option value="">Select Division</option>
                            @foreach($resort_divisions as $division)
                                <option value="{{ $division->id }}">{{ $division->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-2">
                        <label for="department-select">Select Departments</label>
                        <select id="department-select" name="dept_id" class="form-select select2t-none" aria-label="Default select example">
                            <option value="">Select Department</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->name }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <label class="mx-auto d-block text-center mb-2">OR</label>
                    <div class="form-group mb-20">
                        <input type="text" name="dept_name" id="new-dept-name" class="form-control" placeholder="Add Name of Department ">
                    </div>

                    <div class="form-group mb-20">
                        <input type="text" name="dept_code" id="new-dept-code" class="form-control" placeholder="Add Code of Department ">
                    </div>

                    <div class="form-group mb-20">
                        <input type="text" name="dept_short_name" id="new-dept-short-name" class="form-control" placeholder="Add Short Name of Department ">
                    </div>

                    <div class="form-group ">
                        <select class="form-select select2t-none" name="status" aria-label="Default select example">
                            <option selected>Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="modal-footer justify-content-end">
                        <button type="button" class="btn btn-sm btn-themeGray me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-theme">Submit</button>
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
                <form id="addSectionForm">
                    @csrf
                    <div class="form-group mb-20">
                        <label for="section-dept">Select Existing Department</label>
                        <select id="section-dept" name="dept_id" class="form-select select2t-none">
                            <option value="">Select Department</option>
                            @foreach($resort_departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-20">
                        <label for="section-select">Select Existing Section</label>
                        <select id="section-select" name="section_id" class="form-select select2t-none">
                            <option value="">Select Section</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->name }}">{{ $section->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <label class="mx-auto d-block text-center mb-2">OR</label>
                    <div class="form-group mb-20">
                        <input type="text" id="new-section-name" name="section_name" class="form-control" placeholder="Add Name of Sections ">
                    </div>

                    <div class="form-group mb-20">
                        <input type="text" name="section_code" id="new-section-code" class="form-control" placeholder="Add Code of Sections ">
                    </div>

                    <div class="form-group mb-20">
                        <input type="text" name="section_short_name" id="new-section-short-name" class="form-control" placeholder="Add Short Name of Sections ">
                    </div>

                    <div class="form-group ">
                        <select class="form-select select2t-none" name="status" aria-label="Default select example">
                            <option selected>Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="modal-footer justify-content-end">
                        <button type="button" class="btn btn-sm btn-themeGray me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-theme">Submit</button>
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
                <form id="addPositionForm">
                    @csrf
                    <div class="form-group mb-20">
                        <label for="position-dept">Select Existing Department</label>
                        <select id="position-dept" name="dept_id" class="form-select select2t-none">
                            <option value="">Select Department</option>
                            @foreach($resort_departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-20">
                        <label for="position-select">Select Existing Position</label>
                        <select id="position-select" name="position_id" class="form-select select2t-none">
                            <option value="">Select Position</option>
                            @foreach($positions as $position)
                                <option value="{{ $position->position_title }}">{{ $position->position_title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <label class="mx-auto d-block text-center mb-2">OR</label>
                    <div class="form-group mb-20">
                        <input type="text" id="new-position-title" name="position_title" class="form-control" placeholder="Add Title Of Position ">
                    </div>

                    <div class="form-group mb-20">
                        <input type="number" id="new-no-of-positions" name="no_of_positions" class="form-control" placeholder="Add No of positions ">
                    </div>

                    <div class="form-group mb-20">
                        <input type="text" id="new-position-code" name="position_code" class="form-control" placeholder="Add Code Of Position ">
                    </div>

                    <div class="form-group mb-20">
                        <input type="text" id="new-position-short-title" name="position_short_title" class="form-control" placeholder="Add Short Title Of Position ">
                    </div>

                    <div class="form-group">
                        <select class="form-select select2t-none" name="status" aria-label="Default select example">
                            <option selected>Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>


                    <div class="modal-footer justify-content-end">
                        <button type="button" class="btn btn-sm btn-themeGray me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-theme">Submit</button>
                    </div>
                </form>
            </div>
		</div>
	</div>
</div>

@endsection

@section('import-css')
@endsection

@section('import-scripts')

<script>
    window.resortDivisions = @json($resort_divisions);
    window.resortDepartments = @json($resort_departments);
    // Disable the textbox initially if the select box has a value
    toggleDivisionInput();
    toggleDepartmentInput();
    toggleSectionInput();
    togglePositionInput();

    // On change of the select box
    $('#division-select').on('change', function() {
        toggleDivisionInput();
    });

    $('#department-select').on('change', function() {
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
        let selectValue = $('#division-select').val();
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
            $('#division-select').prop('disabled', true);
        } else {
            // If the textbox is empty, enable the select box
            $('#division-select').prop('disabled', false);
        }
    }

    // Function to toggle between select box and textbox
    function toggleDepartmentInput() {
        let selectValue = $('#department-select').val();
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
            $('#department-select').prop('disabled', true);
        } else {
            // If the textbox is empty, enable the select box
            $('#department-select').prop('disabled', false);
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
        "searching": false,
        "bLengthChange": false,
        "bFilter": true,
        "bInfo": true,
        "bAutoWidth": false,
        "scrollX": true,
        "iDisplayLength": 6,
        processing: true,
        serverSide: true,
        ajax: '{{ route("manning.divisions.data") }}',
        columns: [
            { data: 'name', name: 'name', className: 'text-nowrap' },
            { data: 'code', name: 'code', className: 'text-nowrap' },
            { data: 'short_name', name: 'short_name', className: 'text-nowrap' },
            { data: 'status', name: 'status', className: 'text-nowrap' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ]
    });

    $('#addDivisionForm').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: "{{ route('manning.divisions.store') }}",
            type: "POST",
            data: $('#addDivisionForm').serialize(),
            success: function(response) {


                if(response.success == true) {
                    $('#add-divisionmodal').modal('hide');
                    divisionTable.ajax.reload();

                     // Reset the form fields
                     $('#addDivisionForm')[0].reset();

                    // Optionally reset select2 dropdowns or other dynamic elements if needed
                    $('#addDivisionForm').find('select').val(null).trigger('change');

                    toastr.success(response.msg, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                } else {
                    toastr.error(response.msg, "Error", {
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

        // Extract current values from the row
        var currentName = $row.find("td:nth-child(1)").text().trim();
        var currentCode = $row.find("td:nth-child(2)").text().trim();
        var currentShortName = $row.find("td:nth-child(3)").text().trim();
        var currentStatus = $row.find("td:nth-child(4)").text().trim().toLowerCase(); // Convert to lowercase to ensure match

        console.log("Current Status:", currentStatus); // Debug to check the value

        // Create editable row HTML
        var editRowHtml = `
            <td class="py-1">
                <div class="form-group">
                    <input type="text" class="form-control" value="${currentName}" />
                </div>
            </td>
            <td class="py-1">
                <div class="form-group">
                    <input type="text" class="form-control" value="${currentCode}" />
                </div>
            </td>
            <td class="py-1">
                <div class="form-group">
                    <input type="text" class="form-control" value="${currentShortName}" />
                </div>
            </td>
            <td class="py-1">
                <div class="form-group">
                    <select class="form-select select2t-none">
                        <option ${currentStatus === "active" ? "selected" : ""} value="active">Active</option>
                        <option ${currentStatus === "inactive" ? "selected" : ""} value="inactive">Inactive</option>
                    </select>
                </div>
            </td>
            <td class="py-1">
                <a href="#" class="btn btn-theme update-row-btn" data-division-id="${divisionId}">Submit</a>
            </td>
        `;

        // Replace row content with editable form
        $row.html(editRowHtml);
    });

    // Handle click on update button
    $(document).on("click", "#divisions-table .update-row-btn", function (event) {
        event.preventDefault(); // Prevent default action

        var $row = $(this).closest("tr");

        // Get updated values
        var divisionId = $(this).data('division-id');
        var updatedName = $row.find("input").eq(0).val();
        var updatedCode = $row.find("input").eq(1).val();
        var updatedShortName = $row.find("input").eq(2).val();
        var updatedStatus = $row.find("select").val();

        // Perform the AJAX update request here
        $.ajax({
            url: "{{ route('manning.divisions.inlineUpdate', '') }}/" + divisionId,
            type: "PUT",
            data: {
                name: updatedName,
                code: updatedCode,
                short_name: updatedShortName,
                status: updatedStatus
            },
            success: function(response) {
                if(response.success == true) { // Ensure response contains a success key
                    // Determine the status class and label
                    var statusClass = updatedStatus === 'active' ? 'text-success' : 'text-danger';
                    var statusLabel = updatedStatus.charAt(0).toUpperCase() + updatedStatus.slice(1);

                    // Update the row with new values
                    var updatedRowHtml = `
                        <td class="text-nowrap">${updatedName}</td>
                        <td class="text-nowrap">${updatedCode}</td>
                        <td class="text-nowrap">${updatedShortName}</td>
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

                    toastr.success(response.msg, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                } else {
                    toastr.error(response.msg, "Error", {
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
        swal({
            title: 'Sure want to delete?',
            text: 'This cannot be undone',
            type: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes',
            cancelButtonText: 'No',
            confirmButtonColor: "#DD6B55"
        }).then(function(success) {
            if (success) {
                $.ajax({
                    type: "DELETE",
                    url: "{{ route('manning.divisons.destroy', '') }}/" + divisionId,
                    dataType: "json",
                }).done(function(result) {
                    if (result.success == true) {
                        toastr.success(result.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });

                        $('#divisions-table').DataTable().ajax.reload();
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
        "searching": false,
        "bLengthChange": false,
        "bFilter": true,
        "bInfo": true,
        "bAutoWidth": false,
        "scrollX": true,
        "iDisplayLength": 6,
        processing: true,
        serverSide: true,
        ajax: '{{ route("manning.departments.data") }}',
        columns: [
            { data: 'name', name: 'name' },
            { data: 'division', name: 'division' },
            { data: 'code', name: 'code' },
            { data: 'short_name', name: 'short_name' },
            { data: 'status', name: 'status' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ]
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
                    $('#departments-table').DataTable().ajax.reload();

                     // Reset the form fields
                     $('#addDepartmentForm')[0].reset();

                    // Optionally reset select2 dropdowns or other dynamic elements if needed
                    $('#addDepartmentForm').find('select').val(null).trigger('change');

                    toastr.success(response.msg, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                } else {
                    toastr.error(response.msg, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            }
        });
    });

    $(document).on("click", "#departments-table .edit-row-btn", function (event) {
        event.preventDefault();

        var $row = $(this).closest("tr");
        var deptId = $(this).data('dept-id');

        var currentName = $row.find("td:nth-child(1)").text().trim();
        var currentDivision = $row.find("td:nth-child(2)").text().trim();
        var currentCode = $row.find("td:nth-child(3)").text().trim();
        var currentShortName = $row.find("td:nth-child(4)").text().trim();
        var currentStatus = $row.find("td:nth-child(5)").text().trim().toLowerCase();

        // Use the `resortDivisions` array from the global scope
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
                <select id="division-select" name="division_id" class="form-select select2t-none">
                    ${divisionOptions}
                </select>
            </td>
            <td class="py-1">
                <div class="form-group">
                    <input type="text" class="form-control" value="${currentCode}" />
                </div>
            </td>
            <td class="py-1">
                <div class="form-group">
                    <input type="text" class="form-control" value="${currentShortName}" />
                </div>
            </td>
            <td class="py-1">
                <div class="form-group">
                    <select class="form-select select2t-none">
                        <option ${currentStatus === "active" ? "selected" : ""} value="active">Active</option>
                        <option ${currentStatus === "inactive" ? "selected" : ""} value="inactive">Inactive</option>
                    </select>
                </div>
            </td>
            <td class="py-1">
                <a href="#" class="btn btn-theme update-row-btn" data-dept-id="${deptId}">Submit</a>
            </td>
        `;

        $row.html(editRowHtml);

        // Re-initialize Select2 if you're using it
        $('#division-select').select2();
    });

    // Handle click on update button
    $(document).on("click", "#departments-table .update-row-btn", function (event) {
        event.preventDefault(); // Prevent default action

        var $row = $(this).closest("tr");

        // Get updated values
        var dept_id = $(this).data('dept-id');
        var updatedName = $row.find("input").eq(0).val();
        var updatedDivision = $row.find("select").eq(0).val();
        var updatedCode = $row.find("input").eq(1).val();
        var updatedShortName = $row.find("input").eq(2).val();
        var updatedStatus = $row.find("select").eq(1).val();

        // Perform the AJAX update request here
        $.ajax({
            url: "{{ route('manning.departments.inlineUpdate', '') }}/" + dept_id,
            type: "PUT",
            data: {
                name: updatedName,
                division:updatedDivision,
                code: updatedCode,
                short_name: updatedShortName,
                status: updatedStatus
            },
            success: function(response) {
                if(response.success == true) { // Ensure response contains a success key
                    // Determine the status class and label
                    var statusClass = updatedStatus === 'active' ? 'text-success' : 'text-danger';
                    var statusLabel = updatedStatus.charAt(0).toUpperCase() + updatedStatus.slice(1);
                    var updatedDivisionName = response.divisionName;

                    // Update the row with new values
                    var updatedRowHtml = `
                        <td class="text-nowrap">${updatedName}</td>
                        <td class="text-nowrap">${updatedDivisionName}</td>
                        <td class="text-nowrap">${updatedCode}</td>
                        <td class="text-nowrap">${updatedShortName}</td>
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

                    toastr.success(response.msg, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                } else {
                    toastr.error(response.msg, "Error", {
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
    $(document).on('click', '#departments-table .delete-row-btn', function (e) {
        e.preventDefault();
        // Get the division ID from the data attribute
        var dept_id = $(this).data('dept-id');
        swal({
            title: 'Sure want to delete?',
            text: 'This cannot be undone',
            type: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes',
            cancelButtonText: 'No',
            confirmButtonColor: "#DD6B55"
        }).then(function(success) {
            if (success) {
                $.ajax({
                    type: "DELETE",
                    url: "{{ route('manning.departments.destroy', '') }}/" + dept_id,
                    dataType: "json",
                }).done(function(result) {
                    if (result.success == true) {
                        toastr.success(result.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });

                        $('#departments-table').DataTable().ajax.reload();
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
        "searching": false,
        "bLengthChange": false,
        "bFilter": true,
        "bInfo": true,
        "bAutoWidth": false,
        "scrollX": true,
        "iDisplayLength": 6,
        processing: true,
        serverSide: true,
        ajax: '{{ route("manning.sections.data") }}',
        columns: [
            { data: 'name', name: 'name' },
            { data: 'division', name: 'division' },
            { data: 'department', name: 'department' },
            { data: 'code', name: 'code' },
            { data: 'short_name', name: 'short_name' },
            { data: 'status', name: 'status' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ]
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
                    $('#sections-table').DataTable().ajax.reload();

                    // Reset the form fields
                    $('#addSectionForm')[0].reset();

                    // Optionally reset select2 dropdowns or other dynamic elements if needed
                    $('#addSectionForm').find('select').val(null).trigger('change');

                    toastr.success(response.msg, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                } else {
                    toastr.error(response.msg, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
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
        var currentCode = $row.find("td:nth-child(4)").text().trim();
        var currentShortName = $row.find("td:nth-child(5)").text().trim();
        var currentStatus = $row.find("td:nth-child(6)").text().trim().toLowerCase();

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
                <select id="division-select" name="division_id" class="form-select select2t-none">
                    ${divisionOptions}
                </select>
            </td>
            <td class="py-1">
                <select id="department-select" name="dept_id" class="form-select select2t-none">
                    ${deptOptions}
                </select>
            </td>
            <td class="py-1">
                <div class="form-group">
                    <input type="text" class="form-control" value="${currentCode}" />
                </div>
            </td>
            <td class="py-1">
                <div class="form-group">
                    <input type="text" class="form-control" value="${currentShortName}" />
                </div>
            </td>
            <td class="py-1">
                <div class="form-group">
                    <select class="form-select select2t-none">
                        <option ${currentStatus === "active" ? "selected" : ""} value="active">Active</option>
                        <option ${currentStatus === "inactive" ? "selected" : ""} value="inactive">Inactive</option>
                    </select>
                </div>
            </td>
            <td class="py-1">
                <a href="#" class="btn btn-theme update-row-btn" data-section-id="${sectionId}">Submit</a>
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

        // Get updated values
        var section_id = $(this).data('section-id');
        var updatedName = $row.find("input").eq(0).val();
        var updatedDivision = $row.find("select").eq(0).val();
        var updatedDepartment = $row.find("select").eq(1).val();
        var updatedCode = $row.find("input").eq(1).val();
        var updatedShortName = $row.find("input").eq(2).val();
        var updatedStatus = $row.find("select").eq(2).val();

        // Perform the AJAX update request here
        $.ajax({
            url: "{{ route('manning.sections.inlineUpdate', '') }}/" + section_id,
            type: "PUT",
            data: {
                name: updatedName,
                division:updatedDivision,
                department:updatedDepartment,
                code: updatedCode,
                short_name: updatedShortName,
                status: updatedStatus
            },
            success: function(response) {
                if(response.success == true) { // Ensure response contains a success key
                    // Determine the status class and label
                    var statusClass = updatedStatus === 'active' ? 'text-success' : 'text-danger';
                    var statusLabel = updatedStatus.charAt(0).toUpperCase() + updatedStatus.slice(1);
                    var updatedDivisionName = response.divisionName;
                    var updatedDepartmentName = response.deptName;

                    // Update the row with new values
                    var updatedRowHtml = `
                        <td class="text-nowrap">${updatedName}</td>
                        <td class="text-nowrap">${updatedDivisionName}</td>
                        <td class="text-nowrap">${updatedDepartmentName}</td>
                        <td class="text-nowrap">${updatedCode}</td>
                        <td class="text-nowrap">${updatedShortName}</td>
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

                    toastr.success(response.msg, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                } else {
                    toastr.error(response.msg, "Error", {
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
    $(document).on('click', '#sections-table .delete-row-btn', function (e) {
        e.preventDefault();
        // Get the division ID from the data attribute
        var section_id = $(this).data('section-id');
        swal({
            title: 'Sure want to delete?',
            text: 'This cannot be undone',
            type: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes',
            cancelButtonText: 'No',
            confirmButtonColor: "#DD6B55"
        }).then(function(success) {
            if (success) {
                $.ajax({
                    type: "DELETE",
                    url: "{{ route('manning.sections.destroy', '') }}/" + section_id,
                    dataType: "json",
                }).done(function(result) {
                    if (result.success == true) {
                        toastr.success(result.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });

                        $('#sections-table').DataTable().ajax.reload();
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
        processing: true,
        serverSide: true,
        ajax: '{{ route("manning.positions.data") }}',
        columns: [
            { data: 'position_title', name: 'position_title' },
            { data: 'no_of_positions', name: 'no_of_positions'},
            { data: 'division', name: 'division' },
            { data: 'department', name: 'department' },
            { data: 'code', name: 'code' },
            { data: 'short_title', name: 'short_title' },
            { data: 'status', name: 'status' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ]
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
                    $('#positions-table').DataTable().ajax.reload();

                    // Reset the form fields
                    $('#addPositionForm')[0].reset();

                    // Optionally reset select2 dropdowns or other dynamic elements if needed
                    $('#addPositionForm').find('select').val(null).trigger('change');

                    toastr.success(response.msg, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                } else {
                    toastr.error(response.msg, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            }
        });
    });

    $(document).on("click", "#positions-table .edit-row-btn", function (event) {
        event.preventDefault();

        var $row = $(this).closest("tr");
        var positionId = $(this).data('position-id');

        var currentName = $row.find("td:nth-child(1)").text().trim();
        var currentNoOfPositions = $row.find("td:nth-child(2)").text().trim();
        var currentDivision = $row.find("td:nth-child(3)").text().trim();
        var currentDepartment = $row.find("td:nth-child(4)").text().trim();
        var currentCode = $row.find("td:nth-child(5)").text().trim();
        var currentShortName = $row.find("td:nth-child(6)").text().trim();
        var currentStatus = $row.find("td:nth-child(7)").text().trim().toLowerCase();

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
                    <input type="text" name="position_title" class="form-control" value="${currentName}" />
                </div>
            </td>
            <td class="py-1">
                <div class="form-group">
                    <input type="number" name="no_of_positions" class="form-control" value="${currentNoOfPositions}" />
                </div>
            </td>
            <td class="py-1">
                <select id="division-select" name="division_id" class="form-select select2t-none">
                    ${divisionOptions}
                </select>
            </td>
            <td class="py-1">
                <select id="department-select" name="dept_id" class="form-select select2t-none">
                    ${deptOptions}
                </select>
            </td>
            <td class="py-1">
                <div class="form-group">
                    <input type="text" class="form-control" value="${currentCode}" />
                </div>
            </td>
            <td class="py-1">
                <div class="form-group">
                    <input type="text" class="form-control" value="${currentShortName}" />
                </div>
            </td>
            <td class="py-1">
                <div class="form-group">
                    <select class="form-select select2t-none">
                        <option ${currentStatus === "active" ? "selected" : ""} value="active">Active</option>
                        <option ${currentStatus === "inactive" ? "selected" : ""} value="inactive">Inactive</option>
                    </select>
                </div>
            </td>
            <td class="py-1">
                <a href="#" class="btn btn-theme update-row-btn" data-position-id="${positionId}">Submit</a>
            </td>
        `;

        $row.html(editRowHtml);

        // Re-initialize Select2 if you're using it
        $('#position-select').select2();
    });

    // Handle click on update button
    $(document).on("click", "#positions-table .update-row-btn", function (event) {
        event.preventDefault(); // Prevent default action

        var $row = $(this).closest("tr");

        // Get updated values
        var position_id = $(this).data('position-id');
        var updatedName = $row.find("input").eq(0).val();
        var updatednoofPositions = $row.find("input").eq(1).val();
        var updatedDivision = $row.find("select").eq(0).val();
        var updatedDepartment = $row.find("select").eq(1).val();
        var updatedCode = $row.find("input").eq(2).val();
        var updatedShortName = $row.find("input").eq(3).val();
        var updatedStatus = $row.find("select").eq(2).val();

        // Perform the AJAX update request here
        $.ajax({
            url: "{{ route('manning.positions.inlineUpdate', '') }}/" + position_id,
            type: "PUT",
            data: {
                name: updatedName,
                no_of_positions:updatednoofPositions,
                division:updatedDivision,
                department:updatedDepartment,
                code: updatedCode,
                short_name: updatedShortName,
                status: updatedStatus
            },
            success: function(response) {
                if(response.success == true) { // Ensure response contains a success key
                    // Determine the status class and label
                    var statusClass = updatedStatus === 'active' ? 'text-success' : 'text-danger';
                    var statusLabel = updatedStatus.charAt(0).toUpperCase() + updatedStatus.slice(1);
                    var updatedDivisionName = response.divisionName;
                    var updatedDepartmentName = response.deptName;

                    // Update the row with new values
                    var updatedRowHtml = `
                        <td class="text-nowrap">${updatedName}</td>
                        <td class="text-nowrap">${updatednoofPositions}</td>
                        <td class="text-nowrap">${updatedDivisionName}</td>
                        <td class="text-nowrap">${updatedDepartmentName}</td>
                        <td class="text-nowrap">${updatedCode}</td>
                        <td class="text-nowrap">${updatedShortName}</td>
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
    $(document).on('click', '#positions-table .delete-row-btn', function (e) {
        e.preventDefault();
        // Get the division ID from the data attribute
        var position_id = $(this).data('position-id');
        swal({
            title: 'Sure want to delete?',
            text: 'This cannot be undone',
            type: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes',
            cancelButtonText: 'No',
            confirmButtonColor: "#DD6B55"
        }).then(function(success) {
            if (success) {
                $.ajax({
                    type: "DELETE",
                    url: "{{ route('manning.positions.destroy', '') }}/" + position_id,
                    dataType: "json",
                }).done(function(result) {
                    if (result.success == true) {
                        toastr.success(result.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });

                        $('#positions-table').DataTable().ajax.reload();
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

    // $(document).on('change', '#division-select', function(e){
    //     var divisionId = $(this).val();
       
    //     // Clear the department dropdown
    //     $('#department-select').html('<option value="">Select Department</option>');

    //     if (divisionId) {
    //         $.ajax({
    //             url: "{{ route('manning.departments.getDepartmentsByDivision', '') }}/" + divisionId,
    //             type: 'GET',
    //             dataType: 'json',
    //             success: function(data) {
    //                 if (data.success) {
    //                     $.each(data.departments, function(key, department) {
    //                         $('#department-select').append('<option value="' + department.name + '">' + department.name + '</option>');
    //                     });
    //                 } else {
    //                     alert('Failed to fetch departments');
    //                 }
    //             },
    //             error: function() {
    //                 alert('Error fetching departments');
    //             }
    //         });
    //     }
    // });

    // $(document).on('change', '#position-dept', function(e){
    //     var deptId = $(this).val();
       
    //     // Clear the department dropdown
    //     $('#position-select').html('<option value="">Select Position</option>');

    //     if (deptId) {
    //         $.ajax({
    //             url: "{{ route('manning.positions.getPositionByDept', '') }}/" + deptId,
    //             type: 'GET',
    //             dataType: 'json',
    //             success: function(data) {
    //                 if (data.success) {
    //                     $.each(data.positions, function(key, position) {
    //                         $('#position-select').append('<option value="' + position.position_title + '">' + position.position_title + '</option>');
    //                     });
    //                 } else {
    //                     alert('Failed to fetch positions');
    //                 }
    //             },
    //             error: function() {
    //                 alert('Error fetching positions');
    //             }
    //         });
    //     }
    // });

    // $(document).on('change', '#section-dept', function(e){
    //     var deptId = $(this).val();
       
    //     // Clear the department dropdown
    //     $('#section-select').html('<option value="">Select Section</option>');

    //     if (deptId) {
    //         $.ajax({
    //             url: "{{ route('manning.sections.getSectionsByDept', '') }}/" + deptId,
    //             type: 'GET',
    //             dataType: 'json',
    //             success: function(data) {
    //                 if (data.success) {
    //                     $.each(data.sections, function(key, section) {
    //                         $('#section-select').append('<option value="' + section.name + '">' + section.name + '</option>');
    //                     });
    //                 } else {
    //                     alert('Failed to fetch sections');
    //                 }
    //             },
    //             error: function() {
    //                 alert('Error fetching sections');
    //             }
    //         });
    //     }
    // });

    
</script>
@endsection
