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
                    
                            <div class="card-body"> <!-- Add body to contain form -->
                                <div class="row g-4">
                                    <div class="col-6">
                                        <label for="template_type" class="form-label">TEMPLATE TYPE <span class="red-mark">*</span></label>
                                        <select class="form-select select2t-none" id="template_type"
                                            aria-label="Default select example" required name="template_type" data-parsley-required-message="Please select template type" data-parsley-errors-container="#type-error">
                                            <option value="">Select Template Type</option>
                                            <option {{$templates->template_type == "supervisor_line" ? "Selected" : ""}} value="supervisor_line">Template For Supervisor and Below</option>
                                            <option {{$templates->template_type == "manager_above" ? "Selected" : ""}} value="manager_above">Template For Manager and Above</option>
                                        </select>
                                        <div id="type-error"></div>
                                    </div>
                                    <div class="col-6">
                                        <label for="template_name" class="form-label">TEMPLATE NAME <span class="red-mark">*</span></label>
                                        <input type="text" name="template_name" id="template_name" value="{{$templates->name}}" placeholder="Enter Form Name" class="form-control" required/>
                                    </div>
                                    <div class="col-12">
                                        <label for="description" class="form-label">TEMPLATE DESCRIPTION <span class="red-mark">*</span></label>
                                        <textarea class="form-control" id="description" placeholder="Template Description" required data-parsley-required-message="Please enter template description">{{$templates->description}}</textarea>
                                    </div>
                                    <div class="col-12"> <!-- Full width for the form builder -->
                                        <label for="form_structure" class="form-label">Form Structure <span class="red-mark">*</span></label>
                                        <div id="form-builder"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-end"> <!-- Footer for Save Form button -->
                                <button id="update-form" class="btn btn-themeBlue btn-sm">Update Form</button>

                                <a href="{{ route('onboarding.itinerary-template.list') }}" class="btn btn-themeSkyblue btn-sm">Cancel</a>
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
            $(".select2t-none").select2();

            // Load the existing form structure
            let existingFormStructure = @json($templates->fields);

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

            // Check if the parsed structure is an array
            if (!Array.isArray(existingFormStructure)) {
                console.error('Parsed form structure is not an array:', existingFormStructure);
                alert('Form structure is invalid.');
                return;
            }

            console.log('Existing Form Structure:', existingFormStructure);

            // Initialize FormBuilder
            const formBuilder = $('#form-builder').formBuilder();

            // Populate FormBuilder with the existing form structure
            formBuilder.promise.then(() => {
                formBuilder.actions.setData(existingFormStructure);
            });

            // Handle the update button click
            $('#update-form').on('click', function (e) {
                e.preventDefault();
                const template_name = $('#template_name').val();
                const template_type = $('#template_type').val();
                const description = $('#description').val();
                const updatedFormStructure = formBuilder.actions.getData('json'); // Get updated structure

                if (!template_type) {
                    toastr.error('Please select a template type.', "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                    return; 
                }

                if (!template_name) {
                    toastr.error('Please enter a template name.', "Error", {
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
                    url: "{{ route('onboarding.itinerary-template.update', $templates->id) }}",
                    method: 'POST',
                    data: {
                        template_type: template_type,
                        template_name: template_name,
                        description: description,
                        form_structure: updatedFormStructure,
                        _token: $('meta[name="csrf-token"]').attr('content'),
                    },
                    success: function (response) {
                        if (response.success) {
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            window.setTimeout(function() {
                                window.location.href = response.redirect_url;
                            }, 2000);
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
