@extends('resorts.layouts.app')
@section('page_tab_title' ,"Interview Assessment Form")

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
                            <span>Talent Acquisition</span>
                            <h1>Interview Assessment Form</h1>
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
                                            <h2>Create Interview Assessment Form</h2>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body"> <!-- Add body to contain form -->
                                <div class="row g-4">
                                    <div class="col-6">
                                        <label for="form_name" class="form-label">Form Name :</labe>
                                        <input type="text" name="form_name" id="form_name" placeholder="Enter Form Name" class="form-control"/>
                                    </div>
                                    <div class="col-6">
                                        <label for="position" class="form-label">Select Positions :</labe>
                                        <select name="positions" id="positions" class="select2t-none">
                                            <option value="">Select Positions</option>
                                            @if($positions)
                                                @foreach($positions as $pos)
                                                    <option value="{{$pos->id}}">{{$pos->position_title}}</option>
                                                @endforeach
                                            @endif
                                        </select>
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
            $(".select2t-none").select2();
            const options = {
                disableFields: ['autocomplete', 'button'],
                i18n: {
                    locale: 'en-US',
                    override: {}
                }
            };

            const formBuilder =$('#form-builder').formBuilder(options);
            $('#save-form').click(function () {
                const formStructure = formBuilder.actions.getData('json');
                // console.log(formStructure);
                $.ajax({
                    url: "{{ route('interview-assessment.store') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        position : $('#positions').val(),
                        form_name: $('#form_name').val(), // Example, make dynamic
                        form_structure: formStructure
                    },
                    success: function (response) {
                        toastr.success('Form saved successfully!', "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        setTimeout(() => {
                            window.location.href = "{{ route('interview-assessment.index') }}";
                        }, 2000);
                    },
                    error: function () {
                        toastr.success('Error saving form.', "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                        // setTimeout(() => {
                        //     window.location.href = "{{ route('interview-assessment.index') }}";
                        // }, 2000);
                    }
                });
            });
        });
    </script>
@endsection
