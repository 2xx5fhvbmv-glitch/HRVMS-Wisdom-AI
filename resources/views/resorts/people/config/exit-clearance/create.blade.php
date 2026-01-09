@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title);

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
                                            <h2>Create {{$page_title}}</h2>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body"> <!-- Add body to contain form -->
                                <div class="row g-4">
                                    <div class="col-4" id="form-type-container">
                                        <label for="form_type" class="form-label">Select Form Type :<span class="red-mark">*</span></label>
                                        <select name="form_type" id="form_type" class="select2t-none" style="width: 200px">
                                            <option disabled selected>Select Form Type</option>
                                            @foreach($form_types as $key => $form_type)
                                                <option value="{{$key}}">{{$form_type}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    

                                    <div class="col-4 d-none">
                                        <label for="employee_type" class="form-label">Select Type :<span class="red-mark">*</span></labe>
                                        <select name="employee_type" id="employee_type" class="select2t-none" style="width: 220px">
                                            <option selected disabled>Select Type</option>
                                                <option value="handover">Hand Over </option>
                                                <option value="exit_interview">Exit Interview</option>
                                                <option value="exit_clearance">Exit Clearance</option>
                                        </select>
                                    </div>

                                    <div class="col-4 d-none">
                                        <label for="department" class="form-label">Select Depatment :<span class="red-mark">*</span></labe>
                                        <select name="department" id="department" class="select2t-none">
                                            <option selected disabled>Select Department</option>
                                            @if($departments)
                                                @foreach($departments as $department)
                                                    <option value="{{$department->id}}">{{$department->name}}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <label for="form_name" class="form-label">Form Name :<span class="red-mark">*</span></labe>
                                        <input type="text" name="form_name" id="form_name" placeholder="Enter Form Name" class="form-control"/>
                                    </div>
                                    <div class="col-12"> <!-- Full width for the form builder -->
                                        <div id="form-builder"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-end"> <!-- Footer for Save Form button -->
                                <button id="save-form" class="btn btn-themeBlue btn-sm">Save Form</button>
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
        });
    </script>

    <script>
        $(document).ready(function () {
            $(".select2t-none").select2();
            const options = {
                disableFields: ['autocomplete', 'button'],
                i18n: {
                    locale: 'en-US',
                    override: {}
                }
            };

            const formBuilder = $('#form-builder').formBuilder(options);
            $('#save-form').click(function () {
                const formStructure = formBuilder.actions.getData('json');
                const department = $('#department').val();
                const form_name = $('#form_name').val();
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

                if (!form_name) {
                    toastr.error('Please enter a form name.', "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                    return; 
                }

                if (!formStructure || formStructure.length === 0) {
                    toastr.error('Please build the form structure.', "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                    return;
                }

                $.ajax({
                    url: "{{ route('people.exit-clearance.store') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        department: department,
                        form_type: form_type,
                        form_name: form_name,
                        employee_type: employee_type,
                        form_structure: formStructure
                    },
                    success: function (response) {
                        if (response.success == true) {
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            formBuilder.actions.clearFields();
                            $('#department').val(null).trigger('change');
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
