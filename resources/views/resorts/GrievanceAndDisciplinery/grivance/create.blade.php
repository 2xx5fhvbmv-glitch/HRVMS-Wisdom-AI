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
                        <span>People Relation</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <!-- <div class="col-xxl-2 col-auto ms-auto">
                    <select class="form-select select2t-none" id="select-budgeted"
                        aria-label="Default select example">
                        <option selected>All Cases Combined</option>
                        <option value="1">bbb</option>
                    </select>
                </div> -->
            </div>
        </div>


        <div class="card">

<form id="GrivanceSubmitForm" data-parsley-validate>
    @csrf
    <div class="card-title">
        <h3>Grievance Category</h3>
    </div>
    <div class="row g-xl-4 g-3 mb-md-4 mb-3">
        <div class="col-lg-4 col-sm-6">
            <label for="select_type" class="form-label">SELECT TYPE</label>
            <select class="form-select dd-native-select" name="Grivance_Cat_id" id="Grivance_Cat_id"
                required data-parsley-errors-container="#error-Grivance_Cat_id"
                data-parsley-required-message="Please select a category type">
                <option value=""></option>
                @foreach($GrievanceCategory as $item)
                    <option value="{{ $item->id }}">{{ $item->Category_Name }}</option>
                @endforeach
            </select>
            <div class="dd" data-target="#Grivance_Cat_id">
                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                    <span class="dd-lbl">Select Grievance Category</span>
                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <div class="dd-panel" role="listbox" aria-label="Grievance category">
                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a category…"></div>
                    <div class="dd-scroll">
                        @foreach($GrievanceCategory as $item)
                            <div class="dd-item" role="option" data-value="{{ $item->id }}"><span class="dd-nm">{{ $item->Category_Name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div id="error-Grivance_Cat_id"></div>
        </div>

        <div class="col-lg-4 col-sm-6">
            <label for="select_type" class="form-label">SELECT OFFENSE</label>
            <select class="form-select dd-native-select" name="Grivance_Sub_cat" id="Grivance_Sub_cat"
                required data-parsley-errors-container="#error-Grivance_Sub_cat"
                data-parsley-required-message="Please select an offense">
                <option value=""></option>
            </select>
            <div class="dd" data-target="#Grivance_Sub_cat">
                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                    <span class="dd-lbl">Select Grievance Subcategory</span>
                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <div class="dd-panel" role="listbox" aria-label="Grievance subcategory">
                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a subcategory…"></div>
                    <div class="dd-scroll"></div>
                </div>
            </div>
            <div id="error-Grivance_Sub_cat"></div>
        </div>
    </div>

    <div class="card-title">
        <h3>Grievant Information</h3>
    </div>
    <div class="row g-xl-4 g-3 mb-md-4 mb-3">
        <div class="col-md-4 col-sm-6">
            <label for="employee_name" class="form-label">Employee Name</label>
            <select class="form-select dd-native-select" name="Employee_id" id="Employee_id"
                required data-parsley-errors-container="#error-Employee_id"
                data-parsley-required-message="Please select an employee">
                <option value=""></option>
                @foreach($Employee as $item)
                    <option value="{{ base64_encode($item->id) }}">
                        {{ $item->resortAdmin->first_name }} {{ $item->resortAdmin->last_name }}
                    </option>
                @endforeach
            </select>
            <div class="dd" data-target="#Employee_id">
                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                    <span class="dd-lbl">Select Employee</span>
                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <div class="dd-panel" role="listbox" aria-label="Employee">
                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find an employee…"></div>
                    <div class="dd-scroll">
                        @foreach($Employee as $item)
                            <div class="dd-item" role="option" data-value="{{ base64_encode($item->id) }}"><span class="dd-nm">{{ $item->resortAdmin->first_name }} {{ $item->resortAdmin->last_name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div id="error-Employee_id"></div>
        </div>

        <div class="col-md-4 col-sm-6">
            <label class="form-label">Employee ID</label>
            <input type="text" class="form-control" readonly id="employee_main_id" placeholder="Employee ID">
        </div>

        <div class="col-md-4 col-sm-6">
            <label class="form-label">Job Title</label>
            <input type="text" class="form-control" readonly id="job_title" placeholder="Job Title">
        </div>

        <div class="col-md-4 col-sm-6">
            <label class="form-label">Department</label>
            <input type="text" class="form-control" readonly id="Department" placeholder="Department">
        </div>

        <div class="col-md-4 col-sm-6">
            <label class="form-label">Supervisor</label>
            <input type="text" class="form-control" readonly id="supervisor" placeholder="Supervisor">
        </div>

        <div class="col-md-4 col-sm-6">
            <label class="form-label">DATE</label>
            <input type="text" class="form-control datepicker" name="date" id="date"
                value="{{ date('d M Y') }}" placeholder="{{ date('d M Y') }}"
                required data-parsley-required-message="Please select a date">
        </div>

        <div class="col-12">
            <label class="form-label">DESCRIPTION</label>
            <textarea class="form-control" id="description" name="Grivance_description" placeholder="Type Here.." rows="4"
                required
                data-parsley-required-message="Please provide a description"
                data-parsley-minlength="5"
                data-parsley-minlength-message="Description must be at least 5 characters"
                data-parsley-nohtml
                data-parsley-nohtml-message="HTML tags and scripts are not allowed. Only letters, numbers, spaces and basic punctuation are permitted."></textarea>
        </div>

        <div class="col-md-4 col-sm-6">
            <label class="form-label">DATE AND TIME</label>
            <input type="text" class="form-control datepicker" id="date_time" name="Grivance_date_time"
                placeholder="Choose Date & Time" required
                data-parsley-required-message="Please select date and time">
        </div>

        <div class="col-md-4 col-sm-6">
            <label class="form-label">LOCATION (IF APPLICABLE)</label>
            <input type="text" class="form-control" name="location" id="location" placeholder="Location">
        </div>

        <div class="col-md-4 col-sm-6">
            <label class="form-label">WITNESSES (IF APPLICABLE)</label>
            <select class="form-select select2t-none" name="witness_id[]" multiple id="witness_id">
                <option value=""></option>
                @foreach($Employee as $item)
                    <option value="{{ base64_encode($item->id) }}">
                        {{ $item->resortAdmin->first_name }} {{ $item->resortAdmin->last_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-12">
            <label class="form-label">Explain Your Desired Resolution?</label>
            <textarea class="form-control" id="Grivance_Eexplination_description" name="Grivance_Eexplination_description"
                placeholder="Type Here.." rows="4" required
                data-parsley-required-message="Please explain your desired resolution"
                data-parsley-minlength="10"
                data-parsley-minlength-message="Explanation must be at least 10 characters"
                data-parsley-nohtml
                data-parsley-nohtml-message="HTML tags and scripts are not allowed."></textarea>
        </div>

        <div class="col-lg-4 col-sm-6">
            <label class="form-label">ATTACHMENTS (OPTIONAL)</label>
            <div class="uploadFile-block">
                <div class="uploadFile-btn">
                    <a href="#" class="btn eb-btn-accent btn-sm">Upload File</a>
                    <input type="file" id="uploadFile" name="Attachments[]" class="uploadFile-input" multiple
                        data-parsley-max-file-size="2"
                        data-parsley-fileextension="png,jpg,jpeg,gif,svg,webp,heic,heif,pdf">
                </div>
                <div class="uploadFile-text">PNG, JPEG, HEIC, PDF</div>
            </div>
        </div>

        <div class="col-lg-4 col-sm-6">
            <label class="form-label">CONFIDENTIALITY / ANONYMOUS REQUEST</label>
            <div class="mt-sm-2">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="inlineCheckbox1" name="Confidential" value="option1" checked>
                    <label class="form-check-label" for="inlineCheckbox1">Confidential</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="Anonymous" id="inlineCheckbox2" value="option2" checked>
                    <label class="form-check-label" for="inlineCheckbox2">Anonymous</label>
                </div>
            </div>
        </div>
    </div>

    <div class="card-footer">
        <div class="row align-items-center g-3">
            <div class="col-auto ms-auto">
                <button type="submit" class="btn eb-btn-primary btn-sm">Submit</button>
            </div>
        </div>
    </div>
</form>
        </div>
    </div>
</div>
@include('resorts._emotional_buttons_v2_styles')
@include('resorts._dropdown_styles')
@include('resorts._dropdown_script')
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
$(document).ready(function() {

    window.Parsley.addValidator('nohtml', 
    {
        validateString: function(value) {
            // Reject anything that looks like HTML tags
            return !/<[^>]*script|<[^>]*>/.test(value.toLowerCase());
        },
        messages: {
            en: 'HTML tags and scripts are not allowed.'
        }
    });
    flatpickr('.datepicker', {
        dateFormat: 'd/m/Y',
        allowInput: true,
        appendTo: document.body
    });
    $('#witness_id').select2({
        placeholder: 'Select Witness',
        minimumResultsForSearch: -1,
        width: '100%'
    });


            // Re-validate on change
            $('#Employee_id, #Grivance_Cat_id, #Grivance_Sub_cat').on('change', function () {
                $(this).parsley().validate();
            });

            $('#GrivanceSubmitForm').parsley();
        $('#GrivanceSubmitForm').on('submit', function(e)
        {
            e.preventDefault();

            let form = $(this);
            
            if (form.parsley().isValid()) 
            {
                let formData = new FormData(this);
                $.ajax({
                    url: "{{ route('GrievanceAndDisciplinery.grivance.GrievanceSubmiteStore') }}", 
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log(response.success);
                        if (response.success) {
                            // Show success message
                            toastr.success(response.message,"Success",
                            {
                                positionClass: 'toast-bottom-right'
                            });
                        } else {
                                toastr.error(response.message, "Error",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                        }
                    },
                    error: function(response) 
                    {
                        var errors = response.responseJSON;
                        if (errors.error) 
                        { 
                            toastr.error(errors.error, "Error", 
                            {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                        else 
                        {
                            var errs = '';
                            $.each(errors.errors, function(key, error) 
                            {
                                errs += error + '<br>';
                            });
                            toastr.error(errs, "Validation Error",{
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    }

                });
            }
        });

});

    

    $(document).on("change","#Employee_id",function() {
    
        var emp =  $(this).val();

        $.ajax({
            url: "{{ route('GrievanceAndDisciplinery.grivance.GetEmployeeDetails') }}", 
            type: "get",
            data: {"_token":"{{ csrf_token() }}","emp":emp},
            success: function(response) {
                console.log(response);
                if (response.success) {
                
                $("#supervisor").val(response.data.Superviser.Main_Name);  
                $("#employee_main_id").val(response.data.Employee.Emp_id);
                $("#Department").val(response.data.Employee.DepartmentName);
                $("#job_title").val(response.data.Employee.PositionName);
                }
                else
                {
                    toastr.error(response.message, "Error",
                    {
                        positionClass: 'toast-bottom-right'
                    });
                }
            },
            error: function(response) {
                var errors = response.responseJSON;
                var errs = '';
                $.each(errors.errors, function(key, error) {
                    errs += error + '<br>';
                });
                toastr.error(errs, {
                    positionClass: 'toast-bottom-right'
                });
            }
        });
    });

    $(document).on("change","#Grivance_Cat_id ",function() {
        var id   = $(this).val();
        $.ajax({
            url: "{{ route('GrievanceAndDisciplinery.grivance.GetGrivanceSubCat') }}", 
            type: "get",
            data: {"_token":"{{ csrf_token() }}","id":id},
            success: function(response) {
                if (response.success) 
                {

                    var option  ="";

                    $(response.data).each(function(i,a){
                        console.log(a);
                        option  =`<option value="${a.id}" >${a.Sub_Category_Name}</option>`;
                    });
                    $("#Grivance_Sub_cat").html(option);
                    wisdomDD.rebuild('#Grivance_Sub_cat');
                }
                else
                {
                    toastr.error(response.message, "Error",
                    {
                        positionClass: 'toast-bottom-right'
                    });
                }
            },
            error: function(response) {
                var errors = response.responseJSON;
                var errs = '';
                $.each(errors.errors, function(key, error) {
                    errs += error + '<br>';
                });
                toastr.error(errs, {
                    positionClass: 'toast-bottom-right'
                });
            }
        });
    });
</script>
@endsection