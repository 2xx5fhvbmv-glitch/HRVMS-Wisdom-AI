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
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>SOS</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card ">
                <div class="row g-lg-4 g-3 sosteamActivity-header mb-md-4 mb-3">
                    <div class="col-lg-3 col-sm-6">
                        <div class="d-flex bg-themeGrayLight">
                            <h6>Total Members</h6><strong>{{ $totalEmployeesCount }}</strong>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <div class="d-flex bg-themeGrayLight">
                            <h6>Marked Safe</h6><strong>{{ $onlySafeEmpCount }}</strong>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <div class="d-flex bg-themeGrayLight">
                            <h6>Unknown Status</h6><strong>{{ $onlyUnsafeEmpCount }}</strong>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6 text-end">
                        <span class="badge badge-dangerNew mb-2">SOS Active : {{$sosDetails->getSos->name}}</span>
                        <p><i class="fa-regular fa-location-dot"></i> {{$sosDetails->location}}</p>
                    </div>
                </div>
                <input type="hidden" id="sos_history_id" value="{{ $sosDetails->id }}">
                <div class="card-title  mb-md-4 mb-3">
                    <div class="row align-items-center g-md-3 g-2">
                        <div class="col-xl-2 col-md-4 col-sm-5 col-auto">
                            <div class="form-group">
                            <select class="form-select" aria-label="Default select example" name="department" id="departmentFilter">
                                <option value="">All Departments</option>
                                @foreach($getAllDepartments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                                
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="form-check form-switch form-switchTheme switch-blue">
                                <input class="form-check-input" type="checkbox" role="switch"
                                    id="unknownFilter">
                                <label class="form-check-label" for="unknownFilter">Unknown Status
                                    Only</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <form id="massInstructionForm">
                @csrf
                    <input type="hidden" name="sos_history_id" value="{{ $sosDetails->id }}">
                    <div class="sosEmpSafetyStatus-form bg-themeGrayLight  mb-md-4 mb-3">
                        <div class="card-title">
                            <h3>Send Mass Instructions</h3>
                        </div>
                        <div class="row g-md-4 g-2">
                            <div class="col-xl-7 col-md-7 col">
                                <input type="text" name="mass_instruction" class="form-control" placeholder="Mass Instructions" data-parsley-required="true" data-parsley-error-message="Please Enter Mass Instruction" />
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-themeBlue">Submit</button>
                            </div>
                        </div>
                    </div>
                </form>

                <div id="employeStatusSection">
                    @include('resorts.renderfiles.SosEmployeesStatusList', ['employeesStatusList' => $employeesStatusList])
                </div>
            </div>

        </div>
    </div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
    $(document).ready(function () {
        $('#massInstructionForm').parsley();
        
        $('#departmentFilter, #unknownFilter').change(function () {
            let sosHistoryId = $('#sos_history_id').val();
            let departmentId = $("#departmentFilter").val();
            let show_unknown = $('#unknownFilter').is(':checked');

            $.ajax({
                url: "{{ route('sos.filterEmployeeSafetyDetails', '') }}/" + sosHistoryId,
                type: "POST",
                data: {
                    "_token": "{{ csrf_token() }}",
                    departmentId: departmentId,
                    show_status: show_unknown
                },
                success: function(data) {
                    if (data.success) {
                        $("#employeStatusSection").html(data.html);
                    } else {
                        toastr.error("Something went wrong.", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(response) {
                    var errors = response.responseJSON;
                    var errs = '';

                    if(errors && errors.errors) {
                        $.each(errors.errors, function(key, error) {
                            errs += error + '<br>';
                        });
                    } else {
                        errs = "An unexpected error occurred.";
                    }

                    toastr.error(errs, "Error", { positionClass: 'toast-bottom-right' });
                }
            });
        });

        $('#massInstructionForm').submit(function(e) {
            e.preventDefault();

            $.ajax({
                url: "{{ route('sos.updateMassInstruction') }}",
                method: "POST",
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right' 
                        });
                        $('#massInstructionForm')[0].reset();
                        $('#massInstructionForm').parsley().reset();
                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(xhr) {
                    let errorText = xhr.responseJSON?.message || "Something went wrong.";
                    toastr.error(errorText, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });


    });
</script>
@endsection