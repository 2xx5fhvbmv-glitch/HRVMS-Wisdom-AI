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
                    <div class="col-12">
                        <form id="templateForm" data-parsley-validate> <!-- Ensures the card spans the full width -->
                            <div class="card mb-30">
                                <div class="card-body"> <!-- Add body to contain form -->
                                    <div class="row g-4">
                                        <div class="col-6">
                                            <label for="template_type" class="form-label">TEMPLATE TYPE <span class="red-mark">*</span></label>
                                            <select class="form-select select2t-none" id="template_type"
                                                aria-label="Default select example" required data-parsley-required-message="Please select template type" data-parsley-errors-container="#type-error">
                                                <option value="">Select Template Type</option>
                                                <option value="supervisor_line">Template For Supervisor and Below</option>
                                                <option value="manager_above">Template For Manager and Above</option>
                                            </select>
                                            <div id="type-error"></div>
                                        </div>
                                        <div class="col-6">
                                            <label for="template_name" class="form-label">TEMPLATE NAME <span class="red-mark">*</span></label>
                                            <input type="text" class="form-control" id="template_name" placeholder="Template Name" required data-parsley-required-message="Please enter template name">
                                        </div>
                                        <div class="col-12">
                                            <label for="description" class="form-label">TEMPLATE DESCRIPTION <span class="red-mark">*</span></label>
                                            <textarea class="form-control" id="description" placeholder="Template Description" required data-parsley-required-message="Please enter template description"></textarea>
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
                        </form>
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
            const options = {
                disableFields: ['autocomplete', 'button'],
                i18n: {
                    locale: 'en-US',
                    override: {}
                }
            };

            const formBuilder = $('#form-builder').formBuilder(options);
            $('#save-form').click(function (e) {
                e.preventDefault();
                if (!$('#templateForm').parsley().validate()) {
                    return;
                }
                const formStructure = formBuilder.actions.getData('json');
                const template_type = $('#template_type').val();
                const template_name = $('#template_name').val();
                const description = $('#description').val();

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

                if (!formStructure || formStructure.length === 0) {
                    toastr.error('Please build the form structure.', "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                    return;
                }

                $.ajax({
                    url: "{{ route('onboarding.itinerary-template.store') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        template_type: template_type,
                        template_name: template_name,
                        description: description,
                        form_structure: formStructure
                    },
                    success: function (response) {
                        if (response.success == true) {
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
