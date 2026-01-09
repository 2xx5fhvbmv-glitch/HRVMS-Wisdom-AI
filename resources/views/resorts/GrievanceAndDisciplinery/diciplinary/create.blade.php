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
                        <span>Grievance And Disciplinery</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>

            </div>
        </div>

        <div class="card">
            <div class="card-header">
                {{-- <div class="row g-md-3 g-2 align-items-center">
                    <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                        <div class="input-group">
                            <input type="search" class="form-control " placeholder="Search" />
                            <i class="fa-solid fa-search"></i>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-3 col-sm-4 col-7">
                        <select class="form-select">
                            <option selected>Select Department</option>
                            <option value="1">abc</option>
                            <option value="2">abc</option>
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-3 col-sm-4 col-7">
                        <select class="form-select">
                            <option selected>Select Position</option>
                            <option value="1">abc</option>
                            <option value="2">abc</option>
                        </select>
                    </div>

                </div> --}}
            </div>
         <form id="disciplinaryForm" enctype="multipart/form-data" data-parsley-validate>
    @csrf
    <div class="row gx-4 gy-lg-3 gy-5 mb-3">
        <div class="col-lg-8">
            <div class="row gx-4 g-3">
                <!-- Employee -->
                <div class="col-sm-6">
                    <label class="form-label">Employee <span class="text-danger">*</span></label>
                    <select class="form-select" name="Employee_id" id="Employee_id"
                        required data-parsley-required-message="Please select an employee"
                        data-parsley-errors-container="#employee-error-list">
                        <option value=""></option>
                        @foreach($Employee ?? [] as $item)
                            <option value="{{ base64_encode($item->id) }}">
                                {{ $item->resortAdmin->first_name }} {{ $item->resortAdmin->last_name }}
                            </option>
                        @endforeach
                    </select>
                    <div id="employee-error-list" class="text-danger small"></div>
                </div>

                <!-- Category -->
                <div class="col-sm-6">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <select class="form-select" name="Category_id" id="Category_id"
                        required data-parsley-required-message="Please select a category"
                        data-parsley-errors-container="#category-error-list">
                        <option value=""></option>
                        @foreach($DisciplinaryCategories ?? [] as $item)
                            <option value="{{ base64_encode($item->id) }}">{{ $item->DisciplinaryCategoryName }}</option>
                        @endforeach
                    </select>
                    <div id="category-error-list" class="text-danger small"></div>
                </div>

                <!-- Offence -->
                <div class="col-12">
                    <label class="form-label">Offence <span class="text-danger">*</span></label>
                    <select class="form-select select2t-none" name="Offence_id" id="Offence_id"
                        required data-parsley-required-message="Please select an offence"
                        data-parsley-errors-container="#offence-error-list">
                        <option></option>
                    </select>
                    <div id="offence-error-list" class="text-danger small"></div>
                </div>

                <!-- Action -->
                <div class="col-sm-6">
                    <label class="form-label">Action <span class="text-danger">*</span></label>
                    <select class="form-select select2t-none" name="Action_id" id="Action_id"
                        required data-parsley-required-message="Please select an action"
                        data-parsley-errors-container="#action-error-list">
                        <option value=""></option>
                        @foreach($ActionStore ?? [] as $item)
                            <option value="{{ base64_encode($item->id) }}">{{ $item->ActionName }}</option>
                        @endforeach
                    </select>
                    <div id="action-error-list" class="text-danger small"></div>
                </div>

                <!-- Severity -->
                <div class="col-sm-6">
                    <label class="form-label">Severity <span class="text-danger">*</span></label>
                    <select class="form-select select2t-none" name="Severity_id" id="Severity_id"
                        required data-parsley-required-message="Please select severity"
                        data-parsley-errors-container="#severity-error-list">
                        <option value=""></option>
                        @foreach($SeverityStore ?? [] as $item)
                            <option value="{{ base64_encode($item->id) }}">{{ $item->SeverityName }}</option>
                        @endforeach
                    </select>
                    <div id="severity-error-list" class="text-danger small"></div>
                </div>

                <!-- Expiry Date -->
                <div class="col-sm-4">
                    <label class="form-label">Expiry Date <span class="text-danger">*</span></label>
                    <input type="text" name="Expiry_date" class="form-control Expiry_date datepicker"
                        required data-parsley-required-message="Please select an expiry date"
                        placeholder="Select Date">
                </div>

                <!-- Priority -->
                <div class="col-sm-4">
                    <label class="form-label">Priority <span class="text-danger">*</span></label>
                    <select class="form-select select2t-none" name="priority_level" id="priority_level"
                        required data-parsley-required-message="Please select a priority"
                        data-parsley-errors-container="#priority-error-list">
                        <option value="" disabled selected>Select Priority</option>
                        <option value="High">High</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="Low">Low</option>
                    </select>
                    <div id="priority-error-list" class="text-danger small"></div>
                </div>

                <!-- Assign To -->
                <div class="col-sm-4">
                    <label class="form-label">Assign To <span class="text-danger">*</span></label>
                    <select class="form-select" id="assign_to" name="assign_to"
                        required data-parsley-required-message="Please select who to assign"
                        data-parsley-errors-container="#assign-error-list">
                        <option value=""></option>
                        @foreach($committiee ?? [] as $c)
                            <option value="{{ $c->id }}">{{ $c->CommitteeName }}</option>
                        @endforeach
                    </select>
                    <div id="assign-error-list" class="text-danger small"></div>
                </div>

                <!-- Incident Description -->
                <div class="col-sm-12">
                    <label class="form-label">Incident Description</label>
                    <textarea class="form-control" id="incident-description" name="incident_description"
                        rows="4" placeholder="Describe the incident..."></textarea>
                </div>

                <!-- Witness -->
                <div class="col-sm-6">
                    <label class="form-label">Witnesses (Optional)</label>
                    <select class="form-select select2" name="select_witness[]" multiple id="select-witness">
                        <option value="">Select Witness</option>
                        @foreach($Employee ?? [] as $item)
                            <option value="{{ base64_encode($item->id) }}">
                                {{ $item->resortAdmin->first_name }} {{ $item->resortAdmin->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Attachments -->
                <div class="col-sm-6">
                    <label class="form-label">Attachment</label>
                    <div class="uploadFile-block">
                        <div class="uploadFile-btn">
                            <a href="#" class="btn btn-themeBlue btn-sm">Upload File</a>
                            <input type="file" name="attachment[]" id="attachment"
                                accept=".png,.jpg,.jpeg,.pdf" multiple>
                        </div>
                        <div class="uploadFile-text text-muted">Accepted: PNG, JPEG, PDF</div>
                        <div id="attachment-preview" class="mt-1 text-primary small"></div>
                    </div>
                </div>

                <!-- Signed Document -->
                <div class="col-sm-6">
                    <label class="form-label">Upload Signed Document</label>
                    <div class="uploadFile-block">
                        <div class="uploadFile-btn">
                            <a href="#" class="btn btn-themeBlue btn-sm">Upload File</a>
                            <input type="file" name="upload_signed_document" id="upload-signed-document"
                                accept=".png,.jpg,.jpeg,.pdf">
                        </div>
                        <div class="uploadFile-text text-muted">Accepted: PNG, JPEG, PDF</div>
                        <div id="signed-preview" class="mt-1 text-primary small"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Offences -->
        <div class="col-lg-4">
            <div class="fullFinal-main">
                <div class="card-title">
                    <h3>Active Offences</h3>
                </div>
                <div class="table-responsive mt-md-4 mt-2">
                    <table class="table table-Disciplinary">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Offence</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Dynamic content -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit -->
    <div class="card-footer text-end">
        <button type="submit" class="btn btn-themeBlue ms-1 btn-sm Submitme">Submit</button>
    </div>
</form>

        </div>

    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
 $(document).ready(function(){

    $("#disciplinaryForm").parsley();
        $('#Employee_id').select2({
            placeholder: 'Select Employee',
            width: '100%'
        });
        $('#Category_id').select2({
            placeholder: 'Select Category',
            width: '100%'
        });
        $('#Offence_id').select2({
            placeholder: 'Select Offence',
            width: '100%'
        });
        $('#Severity_id').select2({
            placeholder: 'Select Severity',
            width: '100%'
        });
        $('#Action_id').select2({
            placeholder: 'Select Action',
            width: '100%'
        });
        $('#priority_level').select2({
            placeholder: 'Select Priority Level',
            width: '100%'
        });
        $('#assign_to').select2({
            placeholder: 'Select Committee',
            width: '100%'
        });
        
         
        $('.Expiry_date').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,      // Close the picker after selection
            todayHighlight: true  // Highlight today's date
        });
        $(document).on('change', '#Category_id', function() {
            var Category_id = $(this).val();

            $.ajax({
                url: "{{ route('GrievanceAndDisciplinery.Disciplinary.GetCategoryWiseOffence') }}", 
                type: 'POST',
                data: {"_token":"{{ csrf_token() }}","id":Category_id},
                success: function(response) 
                {
                    if (response.success) 
                    {
                        var option = '<option value="">Select Offence</option>';
                        $.each(response.data, function(i, v) {
                            option += `<option value="${v.newid}">${v.OffensesName}</option>`;
                        });
                        $('#Offence_id').html(option);
                    
                    } 
                    else 
                    {
                        toastr.error(response.message, "Error",
                        {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(response) 
                {
                    var errors = response.responseJSON;

                    if (errors.error) { 
                        // If it's a duplicate entry error
                        toastr.error(errors.error, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    } else {
                        // If it's a validation error
                        var errs = '';
                        $.each(errors.errors, function(key, error) {
                            errs += error + '<br>';
                        });

                        toastr.error(errs, "Validation Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                }

            });
                
        
        });


        document.getElementById("attachment").addEventListener("change", function () {
            const files = this.files;
            let fileNames = "";
            for (let i = 0; i < files.length; i++) {
                fileNames += `<div>${files[i].name}</div>`;
            }
            document.getElementById("attachment-preview").innerHTML = fileNames;
        });

        document.getElementById("upload-signed-document").addEventListener("change", function () {
            const file = this.files[0];
            document.getElementById("signed-preview").innerHTML = file ? `<div>${file.name}</div>` : "";
        });


        $("#disciplinaryForm").on("submit", function (event) {
            event.preventDefault();
            var formData = new FormData(this);
            

            $(".Submitme").prop("disabled", true);
            $(".Submitme").text("Submitting...");
            $.ajax({
                url: "{{ route('GrievanceAndDisciplinery.Disciplinary.StoreDisciplinary') }}",
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                         $(".Submitme").prop("disabled", false);
                        $(".Submitme").text("Submit");
                        window.location.href = "{{ route('GrievanceAndDisciplinery.Disciplinary.DisciplinaryIndex') }}";
                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });

                        $(".Submitme").prop("disabled", false);
                        $(".Submitme").text("Submit");
                    }
                },
                error: function (response) 
                {
                    console.log(response);
                    var errors = response.responseJSON;
                    if (errors.error) { 
                        // If it's a duplicate entry error
                        toastr.error(errors.error, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    } else {
                        // If it's a validation error
                        var errs = '';
                        $.each(errors.errors, function(key, error) {
                            errs += error + '<br>';
                        });

                        toastr.error(errs, "Validation Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                }
            });
        });

        $('#Employee_id').on('change', function() {
            GetOpenOffences();
        });


});


function GetOpenOffences()
{
    if ($.fn.dataTable.isDataTable('.table-Disciplinary')) 
    {
        $('.table-Disciplinary').DataTable().destroy();
    }
    var TableAccomMainten = $('.table-Disciplinary').DataTable({
        "searching": false,
        "bLengthChange": false,
        "bFilter": true,
        "bInfo": true,
        "bAutoWidth": false,
        "scrollX": true,
        "iDisplayLength": 6,
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("GrievanceAndDisciplinery.DisciplineryOpenOffence") }}',
            type: 'GET',
            data: function (d) {
                d.Employee_id =$("#Employee_id").val();
            }
        },
        columns: [
            { data: 'Category', name: 'Category', className: 'text-nowrap' },
            { data: 'Offense', name: 'Offense', className: 'text-nowrap' },
            { data: 'Date', name: 'Date', className: 'text-nowrap' },
            { data: 'Action', name: 'Action', className: 'text-nowrap' },
        ]
    });

}

</script>
@endsection