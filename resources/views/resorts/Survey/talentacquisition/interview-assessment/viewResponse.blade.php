@extends('resorts.layouts.app')

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Talent Acquisition</span>
                        <h1>View Interview Assessment Response</h1>
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
                                   
                                    <div class="col-md-12">
                                        <label class="form-label"> Interviewer : {{ $response->interviewer->first_name }} {{ $response->interviewer->last_name }}</label><br/>
                                        <label class="form-label"> Interviewee :   {{ $response->interviewee->first_name }} {{ $response->interviewee->last_name }}</label><br/>
                                        <label class="form-label"> Date Submitted :     {{ $response->created_at->format('d-m-Y') }} </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body"> <!-- Add body to contain form -->
                            <div class="row g-4">
                                <!-- Render Form with Prefilled Data -->
                                <form id="view-interview-form">
                                    <div id="form-render"></div>
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
        @if (session('success'))
            toastr.success("{{ session('success') }}");
        @endif

        @if (session('error'))
            toastr.error("{{ session('error') }}");
        @endif
        // Form structure from the backend
        const formStructure = @json($formStructure);
        const formResponses = @json($responses);

        // Render the form
        $('#form-render').formRender({
            formData: JSON.stringify(formStructure),
        });

        // Prefill responses into the rendered form
        $.each(formResponses, function (key, value) {
            let inputElement = $(`[name="${key}"]`);

            // Check if input element exists and prefill value
            if (inputElement.length > 0) {
                if (inputElement.is(':radio') || inputElement.is(':checkbox')) {
                    inputElement.filter(`[value="${value}"]`).prop('checked', true);
                } else {
                    inputElement.val(value);
                }
            }
        });

        // Disable form fields to make it view-only
        $('#form-render :input').prop('disabled', true);
    });
</script>
@endsection
