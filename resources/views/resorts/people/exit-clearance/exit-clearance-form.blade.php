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
                            <span>People</span>
                            <h1>{{$page_title}}</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card card-exitInterviewResPeopleEmp">
                <div class="card-header">
                    <div class="row g-md-4 g-3">
                        <div class="col-sm">
                            <div class="d-flex">
                                <div class="img-circle userImg-block me-lg-4 me-md-3 me-2"><img
                                        src="{{ Common::getResortUserPicture($employee->Admin_Parent_id ?? null) }}" alt="user">
                                </div>
                                <div>
                                    <h4 class="fw-600 mb-1">{{$employee->resortAdmin->full_name}} <span class="badge badge-themeNew">{{$employee->Emp_id}}</span>
                                    </h4>
                                <p>{{$employee->department->name}} - {{$employee->position->position_title}}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

               <form action="{{route('people.exit-clearance.assignmentSubmitEmployee')}}" method="POST" id="exitClearanceForm">
                    @csrf
                    <input type="hidden" name="employee_resignation_id" value="{{$employeeResignation->id}}">
                    <div class="g-md-4 g-3 mb-md-4 mb-3 align-items-end">

                        <div class="row mb-2">
                            <div class="col-xl-6 col-md-6 col-sm-6 col-12">
                                <label for="select_dep" class="form-label">SELECT FORM <span class="req_span">*</span></label>
                                <select class="form-select select2t-none" id="template_id" name="template_id" aria-label="Default select example" required>
                                    <option selected readonly> Select Form</option>
                                    @foreach($exit_clearance_employee_template as $template)
                                            <option value="{{ $template->id }}">{{ $template->form_name }}</option>
                                    @endforeach
                                    
                                </select>
                            </div>
                            <div class="col-xl-6 col-md-6 col-sm-6 col-12" style="margin-top: 1.5rem !important;">
                                <a href="{{route('people.exit-clearance.create')}}" class="btn btn-themeSkyblue btn-sm ">Create Form</a>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-xl-6 col-md-6 col-sm-6 col-12">
                                <label for="reminder_frequency" class="form-label">REMINDER FREQUENCY <span class="req_span">*</span></label>
                                <select class="form-select select2t-none" id="reminder_frequency" name="reminder_frequency" required  aria-label="Default select example">
                                    <option selected readonly disabled>Select Reminder Frequency</option>
                                    @for($i = 1; $i <= 7; $i++)
                                        <option value="{{ $i }}">Send Reminder in Every {{ $i }} Days</option>
                                    @endfor
                                </select>
                            </div>

                            <div class="col-xl-6 col-md-6 col-sm-6 col-12">
                                <label for="select_dep" class="form-label">DEADLINE FOR RESPONSE <span class="req_span">*</span></label>
                                <input type="text" class="form-control datepicker" name="deadline_date" id="datapicker_modal" placeholder="Select Date" required data-placeholder="Select Date"/>
                            </div>
                            
                        </div>
                    </div>

                    <div class="card-footer text-end">
                         <button type="submit" class="btn  btn-themeBlue btn-sm">Submit</button>
                    </div>
               </form>


            </div>

        </div>
    </div>

@endsection

@section('import-css')
    
@endsection

@section('import-scripts')
<script>
   $(document).ready(function() {
        $('.datepicker').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            todayHighlight: true
        });

    });
</script>
@endsection
