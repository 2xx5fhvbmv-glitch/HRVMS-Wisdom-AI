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
                                        <select name="form_type" id="form_type" class="dd-native-select" style="width: 200px">
                                            <option disabled selected>Select Form Type</option>
                                            @foreach($form_types as $key => $form_type)
                                                <option value="{{$key}}">{{$form_type}}</option>
                                            @endforeach
                                        </select>
                                        <div class="dd" data-target="#form_type">
                                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                                <span class="dd-lbl">Select Form Type</span>
                                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                            </button>
                                            <div class="dd-panel" role="listbox" aria-label="Form Type">
                                                <div class="dd-scroll">
                                                    <div class="dd-item active" role="option" data-value="Select Form Type" aria-disabled="true"><span class="dd-nm">Select Form Type</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                    @foreach($form_types as $key => $form_type)
                                                        <div class="dd-item" role="option" data-value="{{ $key }}"><span class="dd-nm">{{ $form_type }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    

                                    <div class="col-4 d-none">
                                        <label for="employee_type" class="form-label">Select Type :<span class="red-mark">*</span></label>
                                        <select name="employee_type" id="employee_type" class="dd-native-select" style="width: 220px">
                                            <option selected disabled>Select Type</option>
                                                <option value="handover">Hand Over </option>
                                                <option value="exit_interview">Exit Interview</option>
                                                <option value="exit_clearance">Exit Clearance</option>
                                        </select>
                                        <div class="dd" data-target="#employee_type">
                                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                                <span class="dd-lbl">Select Type</span>
                                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                            </button>
                                            <div class="dd-panel" role="listbox" aria-label="Type">
                                                <div class="dd-scroll">
                                                    <div class="dd-item active" role="option" data-value="Select Type" aria-disabled="true"><span class="dd-nm">Select Type</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                    <div class="dd-item" role="option" data-value="handover"><span class="dd-nm">Hand Over</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                    <div class="dd-item" role="option" data-value="exit_interview"><span class="dd-nm">Exit Interview</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                    <div class="dd-item" role="option" data-value="exit_clearance"><span class="dd-nm">Exit Clearance</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-4 d-none">
                                        <label for="department" class="form-label">Select Department :<span class="red-mark">*</span></label>
                                        <select name="department" id="department" class="dd-native-select">
                                            <option selected disabled>Select Department</option>
                                            @if($departments)
                                                @foreach($departments as $department)
                                                    <option value="{{$department->id}}">{{$department->name}}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <div class="dd" data-target="#department">
                                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                                <span class="dd-lbl">Select Department</span>
                                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                            </button>
                                            <div class="dd-panel" role="listbox" aria-label="Department">
                                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a department…"></div>
                                                <div class="dd-scroll">
                                                    <div class="dd-item active" role="option" data-value="Select Department" aria-disabled="true"><span class="dd-nm">Select Department</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                    @if($departments)
                                                        @foreach($departments as $department)
                                                            <div class="dd-item" role="option" data-value="{{$department->id}}"><span class="dd-nm">{{$department->name}}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <label for="form_name" class="form-label">Form Name :<span class="red-mark">*</span></label>
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
@include('resorts._dropdown_styles')
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
@include('resorts._dropdown_script')
@endsection
