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
                            <span>Learning</span>
                            <h1>{{ $page_title }}</h1>
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
                                            <h2>Edit Feedback Form</h2>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body"> <!-- Add body to contain form -->
                                <div class="row g-4">
                                    <div class="col-6">
                                        <label for="form_name" class="form-label">Form Name :</labe>
                                        <input type="text" name="form_name" id="form_name" placeholder="Enter Form Name" class="form-control" value="{{ $form->form_name }}" required/>
                                    </div>

                                    <div class="col-12"> <!-- Full width for the form builder -->
                                        <label for="form_structure" class="form-label">Form Structure</label>
                                        <div id="form-builder"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-end"> <!-- Footer for Save Form button -->
                                <button id="update-form" class="btn btn-themeBlue btn-sm">Update Form</button>

                                <a href="{{ route('feedback-form.index') }}" class="btn btn-themeSkyblue btn-sm">Cancel</a>
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
            let existingFormStructure = @json($form->form_structure);

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
            $('#update-form').on('click', function () {
                const formName = $('#form_name').val();
                const updatedFormStructure = formBuilder.actions.getData('json'); // Get updated structure

                if (!formName) {
                    toastr.success('Form name is required!', "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                    return;
                }

                $.ajax({
                    url: "{{ route('feedback-form.update', $form->id) }}",
                    method: 'POST',
                    data: {
                        form_name: formName,
                        form_structure: updatedFormStructure,
                        _token: $('meta[name="csrf-token"]').attr('content'),
                    },
                    success: function (response) {
                        toastr.success('Form saved successfully!', "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        setTimeout(() => {
                            window.location.href = "{{ route('feedback-form.index') }}";
                        }, 2000);
                    },
                    error: function (error) {
                        toastr.success('An error occurred while updating the form.', "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                        setTimeout(() => {
                            window.location.href = "{{ route('feedback-form.index') }}";
                        }, 2000);
                    },
                });
            });
        });
    </script>
@endsection
