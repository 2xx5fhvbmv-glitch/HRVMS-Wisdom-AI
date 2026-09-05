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
                                <select class="form-select dd-native-select" id="template_id" name="template_id" aria-label="Default select example" required>
                                    <option selected readonly> Select Form</option>
                                    @foreach($exit_clearance_employee_template as $template)
                                            <option value="{{ $template->id }}">{{ $template->form_name }}</option>
                                    @endforeach

                                </select>
                                <div class="dd" data-target="#template_id">
                                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                        <span class="dd-lbl">Select Form</span>
                                        <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                    </button>
                                    <div class="dd-panel" role="listbox" aria-label="Form">
                                        <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a form…"></div>
                                        <div class="dd-scroll">
                                            <div class="dd-item active" role="option" data-value=" Select Form"><span class="dd-nm">Select Form</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @foreach($exit_clearance_employee_template as $template)
                                                <div class="dd-item" role="option" data-value="{{ $template->id }}"><span class="dd-nm">{{ $template->form_name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-6 col-md-6 col-sm-6 col-12" style="margin-top: 1.5rem !important;">
                                <a href="{{route('people.exit-clearance.create')}}" class="btn btn-themeSkyblue btn-sm ">Create Form</a>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-xl-6 col-md-6 col-sm-6 col-12">
                                <label for="reminder_frequency" class="form-label">REMINDER FREQUENCY <span class="req_span">*</span></label>
                                <select class="form-select dd-native-select" id="reminder_frequency" name="reminder_frequency" required  aria-label="Default select example">
                                    <option selected readonly disabled>Select Reminder Frequency</option>
                                    @for($i = 1; $i <= 7; $i++)
                                        <option value="{{ $i }}">Send Reminder Every {{ $i }} Days</option>
                                    @endfor
                                </select>
                                <div class="dd" data-target="#reminder_frequency">
                                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                        <span class="dd-lbl">Select Reminder Frequency</span>
                                        <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                    </button>
                                    <div class="dd-panel" role="listbox" aria-label="Reminder Frequency">
                                        <div class="dd-scroll">
                                            <div class="dd-item active" role="option" data-value="Select Reminder Frequency" aria-disabled="true"><span class="dd-nm">Select Reminder Frequency</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @for($i = 1; $i <= 7; $i++)
                                                <div class="dd-item" role="option" data-value="{{ $i }}"><span class="dd-nm">Send Reminder Every {{ $i }} Days</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @endfor
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-6 col-md-6 col-sm-6 col-12">
                                <label for="select_dep" class="form-label">DEADLINE FOR RESPONSE <span class="req_span">*</span></label>
                                <input type="text" class="form-control" name="deadline_date" id="datapicker_modal" placeholder="Select Date" required data-placeholder="Select Date"/>
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
@include('resorts._dropdown_styles')
@endsection

@section('import-scripts')
<script>
   $(document).ready(function() {
        flatpickr('#datapicker_modal', {
            dateFormat: 'd/m/Y',
            allowInput: true,
            appendTo: document.body
        });

    });
</script>
@include('resorts._dropdown_script')
@endsection
