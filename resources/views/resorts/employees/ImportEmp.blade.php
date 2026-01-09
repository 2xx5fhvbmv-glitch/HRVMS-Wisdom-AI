@extends('resorts.layouts.app')
@section('page_tab_title' , $page_title)

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
                        <span>Employee</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="row g-4">
                <div class="col-xxl-7 col-xl-8 col-lg-9">
                    <div class="card">
                        {{-- <div class="row">
                            <div class="col-lg-6"> --}}
                                @php
                                    $BudgetConfig= Common::GetBudgetConfigLinks(Auth::guard('resort-admin')->user()->resort_id);
                                @endphp
                                <form id="BudgetConfigFiles">
                                    @csrf
                                    <div class="row g-md-4 g-3 mb-md-5 mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label d-block" for="position">Export Department and Position File</label>
                                            <a href="{{ route('resort.export.Employee')}}"  class="btn btn-theme btn-small Employeefile mt-2">Download Template</a>
                                            <!-- <a href=""  class="btn btn-theme btn-small Employeefile mt-2">Download</a> -->
                                        </div>
                                        <!-- <div class="col-md-6">
                                            <label class="form-label d-block" for="position">How To Import Employees Using Excel File</label>
                                            <a href="{{ URL::asset('resorts_assets/demofiles/Employee.xlsx') }}" target="_blank" class="btn btn-theme btn-small Employeefile mt-2">Download </a>
                                        </div> -->
                                    </div>
                                    <div class="row g-md-4 g-3 mb-md-4 mb-3">
                                        <div class="col-md-12">
                                            
                                            <div class="uploadFile-block">
                                                <div class="uploadFile-btn">
                                                    <a href="javascript:void(0);" target="_blank" class="btn btn-themeBlue btn-sm">Upload File</a>
                                                    <input type="file" name="Employeefile" id="Employeefile" style="display: none;">
                                                </div>
                                                <span id="file-name-display" class="text-muted d-block mt-2"></span>
                                            </div>
                                            <span id="file-extension-error" class="text-danger d-block mt-2" style="display:none;"></span>
                                        </div>
                                    </div>

                                    <div class="card-footer text-end ">
                                        <button type="submit" class="btn btn-theme">Submit</button>
                                    </div>
                                </form>
                            {{-- </div>
                        </div> --}}
                    </div>
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


    $('.uploadFile-btn a').click(function() {
        $('#Employeefile').click();
    });

    // Show file name on file select
    $('#Employeefile').on('change', function() {
        let fileName = this.files[0] ? this.files[0].name : '';
        $('#file-name-display').text(fileName);
    });

    $("#department").select2({
    'placeholder':'Select Department',
    });
    $("#position").select2({
    'placeholder':'Select position',
    });

    // Department wise Position
    $(document).on('change', '#department', function() {
        var deptId = $(this).val();
        $.ajax({
            url: "{{ route('resort.get.position') }}",
            type: "post",
            data: {
                deptId: deptId,

            },
            success: function(data) {
                // Clear the dropdown and add a placeholder option
                $("#position").empty().append('<option value="">Select Position</option>');

                if(data.success == true) {
                    // Append new options
                    $.each(data.data, function(key, value) {
                        $("#position").append('<option value="'+value.id+'">'+value.position_title+'</option>');
                    });

                } else {
                    // If no data, just keep the placeholder
                    $("#position").empty().append('<option value="">Select Position</option>');
                }
            },
            error: function(response) {
                toastr.error("Position Not Found", { positionClass: 'toast-bottom-right' });
            }
        });
    });
    $('#BudgetConfigFiles').on('submit', function(e) {
                const fileInput = $('#Employeefile');
                const filePath = fileInput.val();
                const allowedExtensions = /(\.xls|\.xlsx)$/i;

                // Check if the file extension is valid
                if (!allowedExtensions.exec(filePath)) {
                    e.preventDefault(); // Prevent form submission
                    $('#error-message').show(); // Show error message
                } else {
                    $('#error-message').hide(); // Hide error message if valid
                }
            });
            $('#BudgetConfigFiles').validate({
                rules: {
                    department: {
                        required: true,
                    },
                    position: {
                        required: true,
                    },
                    Employeefile: {
                        required: true,
                    }
                },
                messages: {
                    department: {
                        required: "Please select a Department.",
                    },
                    position: {
                        required: "Please select Position.",
                    },
                    Employeefile: {
                        required: "Please select Employee File.",
                    }
                },
                submitHandler: function (form) {
                    var formData = new FormData(form);

                    $.ajax({
                        url: "{{ route('resort.Depat_Position_Emp_Import') }}",
                        type: "POST",
                        data: formData,
                        contentType: false,
                        processData: false,
                        success: function (response) {
                            if (response.success) {
                                toastr.success(response.msg, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });
                                $('#BudgetConfigFiles')[0].reset();
                            } else {
                                toastr.error(response.msg, "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        },
                        error: function (xhr) {
                            if (xhr.status === 422 || xhr.status === 400) {
                                var response = xhr.responseJSON; // The whole response object
                                var errorMessages = '';
                                if (response.errors && Array.isArray(response.errors)) {
                                    // Loop through each error object
                                    response.errors.forEach(function(errorObj) {
                                        errorMessages += `Row ${errorObj.row}: ${errorObj.error} <br>`;
                                    });
                                } else {
                                    errorMessages = response.msg || "An unexpected error occurred.";
                                }

                                toastr.error(errorMessages, "Import Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            } else {
                                toastr.error("An unexpected error occurred. Please try again.", "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        }
                    });
                }
            });

});


</script>
<script>
    // Show error message for invalid file extension
    $('#BudgetConfigFiles').on('submit', function(e) {
        const fileInput = $('#Employeefile');
        const filePath = fileInput.val();
        const allowedExtensions = /(\.xls|\.xlsx)$/i;

        if (!allowedExtensions.exec(filePath)) {
            e.preventDefault();
            $('#file-extension-error').text('Only .xls or .xlsx files are allowed.').show();
        } else {
            $('#file-extension-error').hide();
        }
    });
</script>
@endsection
