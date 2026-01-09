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
                            <span>Incident</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card incident-card">
                <div class="bg-themeGrayLight mb-md-4 mb-3">
                    <div class="card-title pb-md-3 mb-md-4">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3 class="text-nowrap mb-1">{{$incident->incident_name}} 
                                    <span class="badge badge-white">#{{$incident->incident_id}}</span>
                                </h3>
                                <p>{{$incident->categoryName->category_name}} | {{$incident->subcategoryName->subcategory_name}}</p>
                            </div>
                            <div class="col-auto">
                                <ul class="userDetailList-wrapper">
                                    <li><span>REPORTED BY:</span>
                                        <div class="d-flex align-items-center">
                                            <div class="img-circle userImg-block me-2">
                                                <img src="{{Common::getResortUserPicture($incident->reporter->Admin_Parent_id)}}" alt="user">
                                            </div>
                                            <div>
                                                <h5 class="fw-600">{{$incident->reporter->resortAdmin->full_name}}<span class="badge badge-themeNew">#{{$incident->reporter->Emp_id}}</span>
                                                </h5>
                                                <p>{{$incident->reporter->position->position_title}}</p>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class=" mb-3">
                        <h6 class="mb-2">DESCRIPTION:</h6>
                        <p>{{$incident->description}}</p>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <tr>
                                <th>ATTACHMENTS:</th>
                                 <td>  
                                     @if($incident->attachments && count($incident->attachements) > 0)
                                        <div class="attachments">
                                            @foreach(json_decode($incident->attachements, true) as $attachment)

                                                @if(isset($attachment['Filename']) && isset($attachment['Child_id']))
                                                    <a href="javascript:void(0)" class="download-link" data-id="{{base64_encode($attachment['Child_id'])}}">
                                                            {{$attachment['Filename']}}
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    @else
                                        No attachment available
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="bg-themeGrayLight mb-md-4 mb-3">
                    <table id="investigationTable" class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Start Date</th>
                                <th>Expected Resolution Date</th>
                                <th>Investigation Findings</th>
                                <th>Follwup Actions</th>
                                <th>Resolution Notes</th>
                                <th>Commitee Member</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($investigations)
                                @foreach($investigations as $investigation)
                                    <tr>
                                        <td>{{@$investigation->start_date}}</td>
                                        <td>{{@$investigation->expected_resolution_date}}</td>
                                        <td>{{@$investigation->investigation_findings}}</td>
                                        <td>{{@$investigation->followupAction->followup_action }}</td>
                                        <td>{{@$investigation->resolution_notes}}</td>
                                        <td>{{@$investigation->addedBy->employee->resortAdmin->full_name ?? "-"}}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="5">No Investigation found</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                <form id="incidentInvestigation">
                    <div class="row g-md-4 g-3 mb-3">
                        <div class="col-sm-6">
                            <input type="hidden" name="incident_id" id="incident_id" value="{{$incident->id}}"/>
                            <input type="hidden" id="original_priority" value="{{ $incident->priority }}"/>

                            <label for="priority_level" class="form-label">PRIORITY LEVEL</label>
                            <select class="form-select select2t-none" id="priority_level" name="priority"
                                aria-label="Default select example">
                                <option value="">Select Priority </option>
                                <option value="Low" {{ $incident->priority == "Low" ? 'selected' : '' }}>Low</option>
                                <option value="Medium" {{ $incident->priority == "Medium" ? 'selected' : '' }}>Medium</option>
                                <option value="High" {{ $incident->priority == "High" ? 'selected' : '' }}>High</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label for="incident_severity" class="form-label">INCIDENT SEVERITY</label>
                            <select class="form-select select2t-none" name="severity" id="severity" aria-label="Default select example">
                                <option value="">Select Severity </option>
                                @if($severities)
                                    @foreach($severities as $severity)   
                                        <option value="{{$severity}}" {{ $incident->severity == $severity ? 'selected' : '' }}>{{$severity}}</option>
                                    @endforeach                                
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="card-title mb-md-4">
                        <h3>Authority Notified</h3>
                    </div>
                    <div class="row align-items-center g-md-4 g-3 mb-md-4 pb-md-1 mb-3">
                        <!-- Police -->
                        <div class="col-12">
                            <div class="d-flex mb-3">
                                <label class="form-label me-3">Police:</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input police-option" type="radio" name="police" id="yesPolice" value="yes" {{ isset($investigations[0]) && $investigations[0]->police_notified === 'yes' ? 'checked' : '' }} {{ isset($investigations[0]) && $investigations[0]->police_notified ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="yesPolice">Yes</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input police-option" type="radio" name="police" id="noPolice" value="no" {{ isset($investigations[0]) && $investigations[0]->police_notified === 'no' ? 'checked' : '' }} {{isset($investigations[0]) && $investigations[0]->police_notified  ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="noPolice">No</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input police-option" type="radio" name="police" id="notReqPolice" value="not_required"  {{ isset($investigations[0]) && $investigations[0]->police_notified === 'not_required' ? 'checked' : '' }} {{ isset($investigations[0]) && $investigations[0]->police_notified ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="notReqPolice">Not Required</label>
                                </div>
                            </div>
                           
                            <div class="row mt-2 police-date-time d-none">
                                <div class="col-md-3">
                                    <input type="text" 
                                        class="form-control datepicker" 
                                        placeholder="Police Date" 
                                        name="police_date" 
                                        id="police_date" 
                                        value="{{isset($investigations[0]) &&  $investigations[0]->police_date ?? '' }}" 
                                        @if(isset($investigations[0]) && $investigations[0]->police_date) readonly @endif>
                                </div>
                                <div class="col-md-3">
                                    <input type="time" 
                                        class="form-control" 
                                        placeholder="Police Time" 
                                        name="police_time" 
                                        id="police_time"  
                                        value="{{ $investigations[0]->police_time ?? '' }}" 
                                        @if(isset($investigations[0]) && $investigations[0]->police_time) readonly @endif>
                                </div>
                            </div>

                        </div>

                        <!-- MNDF -->
                        <div class="col-12">
                            <div class="d-flex mb-3">
                                <label class="form-label me-3">MNDF:</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input mdf-option" type="radio" name="mdf" id="yesMdf" value="yes" {{ isset($investigations[0]) && $investigations[0]->mdf_notified === 'yes' ? 'checked' : '' }} {{isset($investigations[0]) && $investigations[0]->mdf_notified ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="yesMdf">Yes</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input mdf-option" type="radio" name="mdf" id="noMdf" value="no" {{isset($investigations[0]) && $investigations[0]->mdf_notified === 'no' ? 'checked' : '' }} {{isset($investigations[0]) && $investigations[0]->mdf_notified ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="noMdf">No</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input mdf-option" type="radio" name="mdf" id="notReqMdf" value="not_required" {{isset($investigations[0]) && $investigations[0]->mdf_notified === 'not_required' ? 'checked' : '' }} {{isset($investigations[0]) && $investigations[0]->mdf_notified ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="notReqMdf">Not Required</label>
                                </div>
                            </div>
                            <div class="row mt-2 mdf-date-time d-none">
                                <div class="col-md-3">
                                    <input type="text" class="form-control datepicker" placeholder="MNDF Date" name="mndf_date" id="mndf_date" value="{{ $investigations[0]->mndf_date ?? '' }}" {{isset($investigations[0]) && $investigations[0]->mndf_date ? 'readonly' : '' }}>
                                </div>
                                <div class="col-md-3">
                                    <input type="time" class="form-control" placeholder="MNDF Time" name="mndf_time" id="mndf_time" value="{{ $investigations[0]->mndf_time ?? '' }}" {{isset($investigations[0]) && $investigations[0]->mndf_time ? 'readonly' : '' }}>
                                </div>
                            </div>
                        </div>

                        <!-- Fire and Rescue Service -->
                        <div class="col-12">
                            <div class="d-flex mb-3">
                                <label class="form-label me-3">Fire and Rescue Service:</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input fire-option" type="radio" name="firerescue" id="yesFireRescue" value="yes" {{isset($investigations[0]) && $investigations[0]->fire_rescue_notified === 'yes' ? 'checked' : '' }} {{isset($investigations[0]) && $investigations[0]->fire_rescue_notified ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="yesFireRescue">Yes</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input fire-option" type="radio" name="firerescue" id="noFireRescue" value="no"  {{isset($investigations[0]) && $investigations[0]->fire_rescue_notified === 'no' ? 'checked' : '' }} {{isset($investigations[0]) && $investigations[0]->fire_rescue_notified ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="noFireRescue">No</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input fire-option" type="radio" name="firerescue" id="notReqFireRescue" value="not_required"  {{ isset($investigations[0]) && $investigations[0]->fire_rescue_notified === 'not_required' ? 'checked' : '' }} {{isset($investigations[0]) && $investigations[0]->fire_rescue_notified ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="notReqFireRescue">Not Required</label>
                                </div>
                            </div>
                            <div class="row mt-2 fire-date-time d-none">
                                <div class="col-md-3">
                                    <input type="text" class="form-control datepicker" placeholder="Fire & Rescue Date" name="fire_date" id="fire_date" value="{{ $investigations[0]->fire_rescue_date ?? '' }}" {{isset($investigations[0]) &&  $investigations[0]->fire_rescue_date ? 'readonly' : '' }}>
                                </div>
                                <div class="col-md-3">
                                    <input type="time" class="form-control" placeholder="Fire & Rescue Time" name="fire_time" id="fire_time" value="{{ $investigations[0]->fire_rescue_time ??'' }}" {{isset($investigations[0]) &&  $investigations[0]->fire_rescue_time ? 'readonly' : '' }}>
                                </div>
                            </div>
                        </div>
                        <!-- Ministry -->
                         <div class="col-12">
                            <div class="d-flex mb-3">
                                <label class="form-label me-3">Ministry:</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input Ministry-option" type="radio" name="Ministry_notified" id="yesMinistry" value="yes" {{isset($investigations[0]) && $investigations[0]->Ministry_notified === 'yes' ? 'checked' : '' }} {{isset($investigations[0]) && $investigations[0]->Ministry_notified ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="yesMinistry">Yes</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input Ministry-option" type="radio" name="Ministry_notified" id="noMinistry" value="no"  {{isset($investigations[0]) && $investigations[0]->Ministry_notified === 'no' ? 'checked' : '' }} {{isset($investigations[0]) && $investigations[0]->Ministry_notified ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="noMinistry">No</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input Ministry-option" type="radio" name="Ministry_notified" id="notReqMinistry" value="not_required"  {{ isset($investigations[0]) && $investigations[0]->Ministry_notified === 'not_required' ? 'checked' : '' }} {{isset($investigations[0]) && $investigations[0]->Ministry_notified ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="notReqMinistry">Not Required</label>
                                </div>
                            </div>
                            <div class="row mt-2 Ministry-date-time d-none">
                                <div class="col-md-3">
                                    <input type="text" class="form-control datepicker" placeholder="Ministry Date" name="Ministry_notified_date" id="Ministry_notified_date" value="{{ $investigations[0]->Ministry_notified_date ?? '' }}" {{isset($investigations[0]) &&  $investigations[0]->Ministry_notified_date ? 'readonly' : '' }}>
                                </div>
                                <div class="col-md-3">
                                    <input type="time" class="form-control" placeholder="Ministry  Time" name="Ministry_time" id="Ministry_time" value="{{ $investigations[0]->Ministry_time ??'' }}" {{isset($investigations[0]) &&  $investigations[0]->Ministry_time ? 'readonly' : '' }}>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-title mb-md-4">
                        <h3>Investigation Details</h3>
                    </div>
                    <div class="row align-items-end g-3 mb-md-4 mb-3">
                        <div class="col-sm-6">
                            <label for="start_date" class="form-label">START DATE</label>
                            <input type="text" class="form-control datepicker" id="start_date" placeholder="Select Date">
                        </div>
                        <div class="col-sm-6">
                            <label for="expResoDate" class="form-label">EXPECTED RESOLUTION DATE</label>
                            <input type="text" class="form-control datepicker" id="expResoDate" placeholder="Select Date">
                        </div>
                        <div class="col-12">
                            <label for="investFind" class="form-label">INVESTIGATION FINDINGS AND RECOMMENDATIONS</label>
                            <textarea class="form-control" id="investFind" placeholder="Add detailed notes, observations, or findings" rows="3"></textarea>
                        </div>
                        <div class="col-sm-6">
                            <label for="followUpActions" class="form-label">FOLLOW-UP ACTIONS</label>
                            <select class="form-select select2t-none" id="followUpActions"
                                aria-label="Default select example">
                                <option value="">Followup Actions</option>
                                @if($followup_actions)
                                    @foreach($followup_actions as $action)
                                        <option 
                                            value="{{ $action->id }}" 
                                            data-requires-statement="{{ $action->requires_employee_statement ? '1' : '0' }}"
                                        >
                                            {{ $action->followup_action }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-auto d-none" id="request-witness-statement">
                            <a href="#" class="btn btn-themeSkyblue" id="btn-request-statement" data-incident-id="{{ $incident->id }}">
                                Request For Employee Statement
                            </a>                        
                        </div>
                        @if($incident_witness_statements || $incident_employee_statements)
                            <div class="col-12 mt-4">
                                {{-- Employee Statements --}}
                                @if($incident_employee_statements && count($incident_employee_statements))
                                    <h5 class="mb-3">Employee Statements</h5>
                                    <div class="row">
                                        @foreach($incident_employee_statements as $statement)
                                            <div class="col-md-4 mb-3">
                                                <div class="card shadow-sm">
                                                    <div class="card-body">
                                                        <h6 class="card-title">{{ $statement->employee->resortAdmin->full_name ?? 'Unknown Employee' }}</h6>
                                                        <p class="card-text text-muted mb-1">
                                                            Date : {{ \Carbon\Carbon::parse($statement->created_at)->format('d M Y, h:i A') }}
                                                        </p>
                                                        <p class="card-text">{{ $statement->statement ?? 'No statement provided.' }}</p>

                                                        @php
                                                            $attachments = json_decode($statement->document_path, true);
                                                        @endphp
                                                        @if (!empty($attachments) && is_array($attachments))
                                                            @foreach ($attachments as $file)
                                                                @php
                                                                    $file = trim(str_replace(['\\', '"'], '', $file));
                                                                @endphp

                                                                @if ($file)
                                                                    @php
                                                                        $fileUrl = asset($file);
                                                                        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

                                                                        switch ($extension) {
                                                                            case 'pdf':
                                                                                $icon = 'fa-file-pdf text-danger';
                                                                                break;
                                                                            case 'doc':
                                                                            case 'docx':
                                                                                $icon = 'fa-file-word text-primary';
                                                                                break;
                                                                            case 'xls':
                                                                            case 'xlsx':
                                                                                $icon = 'fa-file-excel text-success';
                                                                                break;
                                                                            case 'jpg':
                                                                            case 'jpeg':
                                                                            case 'png':
                                                                            case 'gif':
                                                                                $icon = 'fa-file-image text-warning';
                                                                                break;
                                                                            default:
                                                                                $icon = 'fa-file text-secondary';
                                                                                break;
                                                                        }
                                                                    @endphp

                                                                    <a href="{{ $fileUrl }}" target="_blank" class="me-3" title="{{ basename($file) }}">
                                                                        <i class="fa-solid {{ $icon }} fa-lg"></i> View
                                                                    </a>
                                                                @endif
                                                            @endforeach
                                                        @else
                                                            <span class="text-muted">No attachments</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Witness Statements --}}
                                @if($incident_witness_statements && count($incident_witness_statements))
                                    <h5 class="mt-4 mb-3">Witness Statements</h5>
                                    <div class="row">
                                        @foreach($incident_witness_statements as $witness)
                                        
                                            <div class="col-md-4 mb-3">
                                                <div class="card shadow-sm">
                                                    <div class="card-body">
                                                        <h6 class="card-title">{{ $witness->employee->resortAdmin->full_name ?? 'Unknown Witness' }}</h6>
                                                        <p class="card-text text-muted mb-1">
                                                            Date : {{ \Carbon\Carbon::parse($witness->created_at)->format('d M Y, h:i A') }}
                                                        </p>
                                                        <p class="card-text">{{ $witness->witness_statements ?? 'No statement provided.' }}</p>
                                                        @php
                                                            $attachments = json_decode($witness->witness_statement_file, true);
                                                        @endphp
                                                        @if (!empty($attachments) && is_array($attachments))
                                                            @foreach ($attachments as $file)
                                                                @php
                                                                    $file = trim(str_replace(['\\', '"'], '', $file));
                                                                @endphp

                                                                @if ($file)
                                                                    @php
                                                                        $fileUrl = asset($file);
                                                                        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

                                                                        switch ($extension) {
                                                                            case 'pdf':
                                                                                $icon = 'fa-file-pdf text-danger';
                                                                                break;
                                                                            case 'doc':
                                                                            case 'docx':
                                                                                $icon = 'fa-file-word text-primary';
                                                                                break;
                                                                            case 'xls':
                                                                            case 'xlsx':
                                                                                $icon = 'fa-file-excel text-success';
                                                                                break;
                                                                            case 'jpg':
                                                                            case 'jpeg':
                                                                            case 'png':
                                                                            case 'gif':
                                                                                $icon = 'fa-file-image text-warning';
                                                                                break;
                                                                            default:
                                                                                $icon = 'fa-file text-secondary';
                                                                                break;
                                                                        }
                                                                    @endphp

                                                                    <a href="{{ $fileUrl }}" target="_blank" class="me-3" title="{{ basename($file) }}">
                                                                        <i class="fa-solid {{ $icon }} fa-lg"></i> View
                                                                    </a>
                                                                @endif
                                                            @endforeach
                                                        @else
                                                            <span class="text-muted">No attachments</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif

                        <div class="col-12">
                            <label for="resolutionNotes" class="form-label">RESOLUTION NOTES</label>
                            <textarea class="form-control" id="resolutionNotes" placeholder="Type Here..."
                                rows="3"></textarea>
                        </div>
                                
                        @if($isHR)
                            <div class="col-md-4 col-sm-6">
                                <label for="outcomeType" class="form-label">OUTCOME TYPE</label>
                                <select class="form-select select2t-none" id="outcomeType" aria-label="Default select example">
                                    <option value="">OUTCOME TYPE </option>
                                    @if($outcome_types)
                                        @foreach($outcome_types as $type)
                                            <option value="{{$type->id}}">{{$type->outcome_type}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <div class="col-md-4 col-sm-6">
                                <label for="pre_mea" class="form-label">PREVENTIVE MEASURES</label>
                                <textarea class="form-control" id="pre_mea" name="pre_mea"></textarea>
                            </div>

                            <div class="col-md-4 col-sm-6">
                                <label for="action_taken" class="form-label">ACTION TAKEN</label>
                                <select class="form-select select2t-none" id="action_taken" aria-label="Default select example">
                                    <option value="">ACTION TAKEN </option>
                                    @if($action_takens)
                                        @foreach($action_takens as $action)
                                            <option value="{{$action->id}}">{{$action->action_taken}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <div class="col-sm-6">
                                <label class="form-label">APPROVAL</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="inlineCheckbox1" name="approval" value="1" {{ $incident->approval == 1 ? 'checked' : '' }} {{ $incident->approval == 1 ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="inlineCheckbox1">
                                        Forward the investigation report to relevant approvers
                                    </label>
                                </div>
                            </div>
                        @endif

                        <div class="col-sm-6">
                            <label for="assign_to" class="form-label">Status</label>
                            <select class="form-select select2t-none" name="status" id="status" aria-label="Default select example">
                                <option value="">Select Status </option>
                                @if($statuses)
                                   @foreach($statuses as $st)   
                                        <option value="{{$st}}" {{ $incident->status == $st ? 'selected' : '' }}>{{$st}}</option>
                                   @endforeach                                
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-themeBlue">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
     <div class="modal fade" id="bdVisa-iframeModel-modal-lg" tabindex="-1" aria-labelledby="myLargeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
            <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Download File</h5>
                
                    <a href="" class="btn btn-smbtn-primary downloadLink" target="_blank"> Download</a>
                
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                    <div class="modal-body">
                    
                            <div class=" ratio ratio-21x9" id="ViewModeOfFiles">

                            </div>
                    
                    </div>
                    <div class="modal-footer">
                        <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    </div>
    
            </div>
        </div>
    </div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
    $(document).ready(function(){
        $('.select2t-none').select2();
        $('.datepicker').each(function () {
            if (!$(this).prop('readonly')) {
                $(this).datepicker({
                    format: 'dd/mm/yyyy',
                    autoclose: true
                });
            }
        });

        const originalPriority = $('#original_priority').val();

        $('#priority_level').on('change', function () {
            let newValue = $(this).val();

            if (originalPriority && newValue !== originalPriority) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'Priority level was already set by HR. Do you want to change it?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, change it',
                    cancelButtonText: 'No, keep original',
                    confirmButtonColor: "#DD6B55"
                }).then((result) => {
                    if (!result.isConfirmed) {
                        $('#priority_level').val(originalPriority); // Revert if not confirmed
                    }
                });
            }
        });


        function toggleDateTimeSection(group, value) {
            if (value === 'yes') {
                $('.' + group + '-date-time').removeClass('d-none');
            } else {
                $('.' + group + '-date-time').addClass('d-none');
            }
        }

        const policeValue = $('input[name="police"]:checked').val();
        toggleDateTimeSection('police', policeValue);

        const mdfValue = $('input[name="mdf"]:checked').val();
        toggleDateTimeSection('mdf', mdfValue);

        const fireValue = $('input[name="firerescue"]:checked').val();
        toggleDateTimeSection('fire', fireValue);

        $('.police-option').change(function () {
            toggleDateTimeSection('police', $(this).val());
        });

        $('.mdf-option').change(function () {
            toggleDateTimeSection('mdf', $(this).val());
        });

        $('.fire-option').change(function () {
            toggleDateTimeSection('fire', $(this).val());
        });
        $('.Ministry-option').change(function () {
            toggleDateTimeSection('Ministry', $(this).val());
        });
        $('#followUpActions').on('change', function () {
            let selectedOption = $(this).find('option:selected');
            let requiresStatement = selectedOption.data('requires-statement');

            if (requiresStatement == 1) {
                $('#request-witness-statement').removeClass('d-none');
            } else {
                $('#request-witness-statement').addClass('d-none');
            }
        });

        $('#incidentInvestigation').on('submit', function(e) {
            e.preventDefault();

            let formData = {
                incident_id:$('#incident_id').val(),
                priority: $('#priority_level').val(),
                severity: $('#severity').val(),
                police: $("input[name='police']:checked").val(),
                police_date: $('#police_date').val(),
                police_time: $('#police_time').val(),
                mdf: $("input[name='mdf']:checked").val(),
                mndf_date: $('#mndf_date').val(),
                mndf_time: $('#mndf_time').val(),
                firerescue: $("input[name='firerescue']:checked").val(),
                fire_date: $('#fire_date').val(),
                fire_time: $('#fire_time').val(),
                start_date: $('#start_date').val(),
                expResoDate: $('#expResoDate').val(),
                investFind: $('#investFind').val(),
                followUpActions: $('#followUpActions').val(),
                resolutionNotes: $('#resolutionNotes').val(),
                outcomeType: $('#outcomeType').val(),
                pre_mea: $('#pre_mea').val(),
                action_taken: $('#action_taken').val(),
                approval: $('#inlineCheckbox1').is(':checked') ? 1 : 0,
                status: $('#status').val(),
                _token: '{{ csrf_token() }}',
                 Ministry_notified_date: $('#Ministry_notified_date').val(),
                Ministry_time: $('#Ministry_time').val(),
                Ministry_notified:$("input[name='Ministry_notified']:checked").val(), 
            };

            $.ajax({
                url: "{{ route('incident.investigation.store') }}",
                type: "POST",
                data: formData,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        $('#incidentInvestigation')[0].reset(); 
                    }
                },
                error: function(response) {
                    toastr.error('Error saving incident investigation. Please check inputs.', "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });

        $(document).on('click', '#btn-request-statement', function (e) {
            e.preventDefault();

            const incidentId = $(this).data('incident-id');

            $.ajax({
                url: '{{ route("incident.request-statement") }}',
                method: 'POST',
                data: {
                    incident_id: incidentId
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                },
                error: function (xhr) {
                    toastr.error("Failed to send request. Please try again.", "Error", { positionClass: 'toast-bottom-right' });
                }
            });
        });

        $('#inlineCheckbox1').on('change', function () {
            if ($(this).is(':checked')) {
                let incidentId = $('#incident_id').val();

                $.ajax({
                    url: '{{ route("incident.investigation.approve") }}',
                    type: 'POST',
                    data: {
                        incident_id: incidentId,
                        approval: $('#inlineCheckbox1').is(':checked') ? 1 : 0,
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        toastr.success(response.message || 'Approval forwarded to GM.', 'Success', {
                            positionClass: 'toast-bottom-right'
                        });
                    },
                    error: function () {
                        toastr.error('Failed to update approval status.', 'Error', {
                            positionClass: 'toast-bottom-right'
                        });
                        $('#inlineCheckbox1').prop('checked', false); // revert if failed
                    }
                });
            }
        });

         $(document).on("click", ".download-link", function(e) {
            e.preventDefault();
            var childId = $(this).data('id');
            var $downloadLink = $(this);

            // First, set a loading message
            $("#ViewModeOfFiles").html('<div class="text-center"><p>A file link is being generated. Please wait...</p><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
            
            // Show the modal with the loading message
            $("#bdVisa-iframeModel-modal-lg").modal('show');
            
            $.ajax({
                url: "{{ route('resort.visa.XpactEmpFileDownload', '') }}/" + childId,
                type: 'GET',
                data: { child_id: childId, "_token":"{{csrf_token()}}"},
                success: function(response) 
                {
                    let fileUrl = response.NewURLshow;
                    $(".downloadLink").attr("href", fileUrl);
                    
                    let mimeType = response.mimeType.toLowerCase();
                    let iframeTypes = [
                                        'video/mp4', 'video/quicktime', 'video/x-msvideo', // Videos
                                        'application/pdf', 'text/plain',                   // PDF & Text
                                        'application/msword', 'application/vnd.ms-excel'   // Word & Excel
                                    ];
                    let imageTypes = ['image/jpeg', 'image/png', 'image/gif'];
            
                    // Clear the loading message and show the actual content
                    if (imageTypes.includes(mimeType)) 
                    {
                        $("#ViewModeOfFiles").html(`
                            <img src="${fileUrl}" class="popupimgFileModule" onclick="showImage('${fileUrl}')" alt="Image Preview">`);
                    } 
                    // If file type is supported for iframe display
                    else if (iframeTypes.includes(mimeType)) {
                        $("#ViewModeOfFiles").html(`
                            <iframe style="width: 100%; height: 100%;" src="${fileUrl}" allowfullscreen></iframe>
                        `);
                    } 
                    else {
                        $("#bdVisa-iframeModel-modal-lg").modal('hide');
                        // window.location.href = fileUrl; // Triggers download automatically
                    }
                },
                error: function(xhr, status, error) 
                {
                    $("#bdVisa-iframeModel-modal-lg").modal('hide');
                    toastr.error("An error occurred while downloading the file.", "Error", { positionClass: 'toast-bottom-right' });
                }
            });
        });

    })   
    document.addEventListener("DOMContentLoaded", function () {
        function toggleDateTime(radioClass, containerClass) {
            document.querySelectorAll("." + radioClass).forEach(function (radio) {
                radio.addEventListener("change", function () {
                    if (this.value === "yes") {
                        document.querySelector("." + containerClass).classList.remove("d-none");
                    } else {
                        document.querySelector("." + containerClass).classList.add("d-none");
                    }
                });
            });
        }
        toggleDateTime("police-option", "police-date-time");
        toggleDateTime("mdf-option", "mdf-date-time");
        toggleDateTime("fire-option", "fire-date-time");
        toggleDateTime("Ministry-option", "Ministry-date-time");
        Ministry
    }); 
   
</script>
@endsection