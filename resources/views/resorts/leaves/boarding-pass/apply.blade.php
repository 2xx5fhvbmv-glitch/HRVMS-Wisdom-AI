@extends('resorts.layouts.app')
@section('page_tab_title' ,"Leave Details")

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
                        <span>Leave</span>
                        <h1>Boarding Pass</h1>
                    </div>
                </div>
            </div>
        </div>

        <form id="boarding-pass" action="{{ route('resort.boardingpass.store') }}" method="POST">
            @csrf
            <div class="card card-boardingPass">
                <div class="row gx-xl-5 g-md-4 g-3 mb-4">
                    <div class="col-lg-6">
                        <div class="mb-md-4 mb-3">
                            <label for="depDate" class="form-label">DEPARTURE DATE</label>
                            <input type="text" class="form-control datepicker" id="depDate" placeholder="DEPARTURE DATE" name="dept_date">
                        </div>
                        <div class="mb-md-4 mb-3">
                            <label for="depTime" class="form-label">DEPARTURE TIME</label>
                            <input type="time" class="form-control" id="depTime" placeholder="DEPARTURE TIME" name="dept_time">
                        </div>
                        <div>
                            <label for="" class="form-label">TRANSPORTATION *</label>
                            <div>
                                @if($transportations)
                                    @foreach($transportations as $key => $value)
                                        <div class="form-check form-check-inline">
                                            <input 
                                                class="form-check-input transportation-checkbox" 
                                                type="radio" 
                                                name="dept_transportation" 
                                                value="{{ $key }}" 
                                                id="dept-transportation-{{ $key }}">
                                            <label class="form-check-label" for="dept-transportation-{{ $key }}">{{ $value }}</label>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="mb-md-4 mb-3">
                            <label for="arrDate" class="form-label">ARRIVAL DATE</label>
                            <input type="text" class="form-control datepicker" id="arrDate" placeholder="ARRIVAL DATE" name="arrival_date">
                        </div>
                        <div class="mb-md-4 mb-3">
                            <label for="arrTime" class="form-label">ARRIVAL TIME</label>
                            <input type="time" class="form-control" id="arrTime" placeholder="ARRIVAL TIME" name="arrival_time">
                        </div>
                        <div>
                            <label for="" class="form-label">TRANSPORTATION *</label>
                            <div>
                            <div>
                                @if($transportations)
                                    @foreach($transportations as $key => $value)
                                        <div class="form-check form-check-inline">
                                            <input 
                                                class="form-check-input transportation-checkbox" 
                                                type="radio" 
                                                name="arrival_transportation" 
                                                value="{{ $key }}" 
                                                id="arrival-transportation-{{ $key }}">
                                            <label class="form-check-label" for="arrival-transportation-{{ $key }}">{{ $value }}</label>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div>
                            <label for="reason" class="form-label">REASON</label>
                            <textarea class="form-control" id="reason" name="reason" placeholder="Reason" rows="4" required data-parsley-departure-or-arrival
                            data-parsley-errors-container="#error"
                            ></textarea>
                        </div>
                        <div id="error"></div>
                    </div>
                </div>
                <div class="d-none d-xl-block" style="height: 126px;"></div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
    $(document).ready(function () {
        $('.datepicker').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            startDate: new Date(), // Disable past dates
        }).on('changeDate', function () {
            // Trigger Parsley validation when the date changes
            $(this).parsley().validate();
        });
        // Define custom validator to check if either departure or arrival details are filled
        $.validator.addMethod("departureOrArrival", function(value, element) {
            const depDate = $('#depDate').val();
            const depTime = $('#depTime').val();
            const depTransport = $('input[name="dept_transportation"]:checked').val();
            const arrDate = $('#arrDate').val();
            const arrTime = $('#arrTime').val();
            const arrTransport = $('input[name="arrival_transportation"]:checked').val();

            // Ensure that at least one set of departure or arrival details is filled
            return (depDate && depTime && depTransport) || (arrDate && arrTime && arrTransport);
        }, "Please provide either departure or arrival details with transportation.");

        // Apply validation to the form
        $('#boarding-pass').validate({
            rules: {
                dept_date: {
                    departureOrArrival: true,  // Custom validation rule for departure date
                    date: true  // Validate the date format
                },
                arr_date: {
                    departureOrArrival: true,  // Custom validation rule for arrival date
                    date: true  // Validate the date format
                },
                reason: {
                    maxlength: 255  // Reason field is optional but has a max length
                },
            },
            messages: {
                dept_date: {
                    departureOrArrival: "Please provide either departure or arrival details with transportation.",
                    date: "Please enter a valid departure date."
                },
                arr_date: {
                    departureOrArrival: "Please provide either departure or arrival details with transportation.",
                    date: "Please enter a valid arrival date."
                },
                reason: {
                    maxlength: "Reason cannot exceed 255 characters."
                },
            },
            submitHandler: function(form) {
                // Handle form submission via AJAX
                var formData = new FormData(form); // Serialize form data for submission

                $.ajax({
                    url: "{{ route('resort.boardingpass.store') }}", // Update with actual route
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.msg, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            form.reset(); // Reset form if needed
                        } else {
                            toastr.error(response.msg, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function(response) {
                        var errors = response.responseJSON;
                        var errs = '';
                        $.each(errors.errors, function(key, error) {
                            errs += error + '<br>';
                        });
                        toastr.error(errs, { positionClass: 'toast-bottom-right' });
                    }
                });
            }
        });
    });
</script>
@endsection