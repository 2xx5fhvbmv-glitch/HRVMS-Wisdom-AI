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
                <div class="row justify-content-between g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>People</span>
                            <h1>{{$page_title}}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="row g-4">
                    <div class="col-12"> <!-- Ensures the card spans the full width -->
                        <div class="card mb-30">
                            <div class="card-title">
                                <div class="row g-6 align-items-center justify-content-between">
                                    <div class="col-12"> <!-- Full width for the title section -->
                                        <div class="d-flex justify-content-start align-items-center">
                                            <h2>Edit {{$page_title}}</h2>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body"> <!-- Add body to contain form -->
                                <div class="row g-4">
                                    <div class="col-4" id="form-type-container">
                                        <label for="form_type" class="form-label">Select Form Type :<span class="red-mark">*</span></label>
                                        <select name="form_type" id="form_type" class="select2t-none" style="width: 200px">
                                            @foreach($form_types as $key => $form_type)
                                                <option value="{{$key}}" @if($exit_clearance->form_type == $key) selected @endif>{{$form_type}}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-4">
                                        <label for="employee_type" class="form-label">Select Type :<span class="red-mark">*</span></labe>
                                        <select name="employee_type" id="employee_type" class="select2t-none" style="width: 220px;">
                                            <option selected disabled>Select Type</option>
                                                <option value="handover" @if(@$exit_clearance->type == 'handover') selected @endif>Hand Over </option>
                                                <option value="exit_interview" @if(@$exit_clearance->type == 'exit_interview') selected @endif>Exit Interview</option>
                                                <option value="exit_clearance" @if(@$exit_clearance->type == 'exit_clearance') selected @endif>Exit Clearance</option>
                                        </select>
                                    </div>

                                    <div class="col-4">
                                        <label for="department" class="form-label">Select Depatment :<span class="red-mark">*</span></labe>
                                        <select name="department" id="department" class="select2t-none" style="width: 220px;">
                                            <option selected disabled>Select Department</option>
                                            @if($departments)
                                                @foreach($departments as $department)
                                                    <option value="{{$department->id}}" @if($exit_clearance->department_id == $department->id) selected @endif>{{$department->name}}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>

                                    <div class="col-4">
                                        <label for="form_name" class="form-label">Form Name :<span class="red-mark">*</span></labe>
                                        <input type="text" name="form_name" id="form_name" placeholder="Enter Form Name" class="form-control" value="{{ $exit_clearance->form_name }}" required/>
                                    </div>

                                    <div class="col-12"> <!-- Full width for the form builder -->
                                        <label for="form_structure" class="form-label">Form Structure <span class="red-mark">*</span></label>
                                        <div id="form-builder"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-end"> <!-- Footer for Save Form button -->
                                <button id="update-form" class="btn btn-themeBlue btn-sm">Update Form</button>

                                <a href="{{ route('people.exit-clearance.index') }}" class="btn btn-themeSkyblue btn-sm">Cancel</a>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
<style>
    #ui-datepicker-div{
        display:none!important;
    }
</style>
@endsection

@section('import-scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <script src="https://formbuilder.online/assets/js/form-builder.min.js"></script>
    <script src="https://formbuilder.online/assets/js/form-render.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#form_type').change(function () {
                const selectedKey = $(this).val();
                if (selectedKey === 'department') {
                    $('#department').closest('.col-4').removeClass('d-none');
                    $('#employee_type').closest('.col-4').addClass('d-none');
                } else {
                    $('#department').closest('.col-4').addClass('d-none');
                    $('#employee_type').closest('.col-4').removeClass('d-none');
                }
            });

            const selectedFormType = $('#form_type').val(); 
            if ($('#form_type').val() === 'department') {
                $('#department').closest('.col-4').removeClass('d-none');
                 $('#employee_type').closest('.col-4').addClass('d-none');
            } else {
                $('#department').closest('.col-4').addClass('d-none');
                $('#employee_type').closest('.col-4').removeClass('d-none');
            }
        });

        $(document).ready(function () {
            $(".select2t-none").select2();

            // Load the existing form structure
            let existingFormStructure = @json($exit_clearance->form_structure);

            // Parse if the form structure is still a string
            if (typeof existingFormStructure === 'string') {
                try {
                    existingFormStructure = JSON.parse(existingFormStructure);
                } catch (error) {
                    console.error('Error parsing form structure:', error);
                    alert('Form structure is invalid.');
                    return;
                }
            }

            if (!Array.isArray(existingFormStructure)) {
                console.error('Parsed form structure is not an array:', existingFormStructure);
                alert('Form structure is invalid.');
                return;
            }

            console.log('Existing Form Structure:', existingFormStructure);
            const formBuilder = $('#form-builder').formBuilder();
            formBuilder.promise.then(() => {
                formBuilder.actions.setData(existingFormStructure);
            });

            // Handle the update button click
            $('#update-form').on('click', function () {
                const formName = $('#form_name').val();
                 const department = $('#department').val();
                const updatedFormStructure = formBuilder.actions.getData('json'); 
                const form_type = $('#form_type').val();
                const employee_type = $('#employee_type').val();

                if (!form_type) {
                    toastr.error('Please select a form type.', "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                    return; 
                }

                if (form_type === 'department' && !department) {
                    toastr.error('Please select a department.', "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                    return; 
                }
                if (!formName) {
                    toastr.error('Please enter a form name.', "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                    return; 
                }

                if (!updatedFormStructure || updatedFormStructure.length === 0) {
                    toastr.error('Please build the form structure.', "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                    return;
                }

                $.ajax({
                    url: "{{ route('people.exit-clearance.update', $exit_clearance->id) }}",
                    method: 'POST',
                    data: {
                        department: department,
                        form_name: formName,
                        form_type: form_type,
                        employee_type: employee_type,
                        form_structure: updatedFormStructure,
                        _token: $('meta[name="csrf-token"]').attr('content'),
                    },
                    success: function (response) {
                        if (response.success) {
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            window.location.href = response.redirect_url;
                        } 
                    },
                    error: function () {
                        toastr.error('Error saving form.', "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                      
                    }
                });
            });
        });
    </script>
@endsection
