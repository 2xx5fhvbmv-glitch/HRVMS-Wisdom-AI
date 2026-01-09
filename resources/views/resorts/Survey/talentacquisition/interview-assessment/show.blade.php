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
                                            <h2>{{ $form[0]->form_name }}</h2>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-body"> <!-- Add body to contain form -->
                                <div class="row g-4">
                                    <form id="interviewForm" method="POST" action="{{ route('interview-assessment.saveResponse', $form[0]->id) }}">
                                        @csrf
                                        <input type="hidden" name="interviewer_id" id="interviewer_id" value="{{$interviewer_id}}"/>
                                        <input type="hidden" name="interviewee_id" id="interviewee_id" value="{{$interviewee_id}}"/>

                                        <div id="form-render"></div>
                                        <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                                    </form>
                                </div>
                            </div>
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
    <script src="https://formbuilder.online/assets/js/form-render.min.js"></script> 
    <script>
        $(document).ready(function () {
            // Form structure passed from the backend
            const formJson = @json($form[0]->form_structure);

            // Parse the string into a valid JSON object
            const parsedFormJson = JSON.parse(formJson);

            // Log the parsed structure for debugging
            // console.log('Parsed Form JSON:', parsedFormJson);

            // Render the form
            $('#form-render').formRender({
                formData: parsedFormJson
            });
        });
    </script>
@endsection
