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
                                <h3 class="text-nowrap mb-1">{{$incident->incident_name}} <span
                                        class="badge badge-white">#{{$incident->incident_id}}</span></h3>
                                <p>{{$incident->categoryName->category_name}} | {{$incident->subcategoryName->subcategory_name}}</p>
                            </div>
                            <div class="col-auto">
                                <ul class="userDetailList-wrapper">
                                    <li><span>DATE:</span>{{date('d M Y', strtotime($incident->incident_date))}}</li>
                                    <li><span>TIME:</span>{{date('h:i A', strtotime($incident->incident_time))}}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="row g-lg-4 g-3 mb-3">
                        <div class="col-xl-4 col-md-6">
                            <div class="bg-white">
                                <h6>REPORTED BY:</h6>
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
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="bg-white">
                                <h6>VICTIM:</h6>
                                <div class="d-flex align-items-center">
                                    <div class="img-circle userImg-block me-2">
                                        <img src="{{Common::getResortUserPicture($incident->victim->Admin_Parent_id)}}" alt="user">
                                    </div>
                                    <div>
                                        <h5 class="fw-600">{{$incident->victim->resortAdmin->full_name}}<span class="badge badge-themeNew">#{{$incident->victim->Emp_id}}</span>
                                        </h5>
                                        <p>{{$incident->victim->position->position_title}}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-md-4 pb-md-1 mb-3">
                        <h6 class="mb-2">DESCRIPTION:</h6>
                        <p>{{$incident->description}}</p>
                    </div>

                    <div class="table-responsive">
                        <table>
                            <tr>
                                <th>LOCATION:</th>
                                <td>{{$incident->location}}</td>
                            </tr>
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
                            <tr>
                                <th>WITNESS:</th>
                                <td>
                                    <div class="user-ovImg">
                                        @if($incident->witness)
                                            @foreach($incident->witness as $witness)
                                                <div class="img-circle">
                                                    <img src="{{Common::getResortUserPicture($witness->employee->Admin_Parent_id)}}" alt="image">
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>

                </div>
                <form id="incidentForm">
                    @csrf
                    <div class="row g-md-4 g-3 mb-md-4 mb-3">
                        <input type="hidden" name="incident_id" value="{{$incident->id}}"/>
                        <div class="col-sm-6">
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
                            <label for="assign_to" class="form-label">ASSIGN TO</label>
                            <select class="form-select select2t-none" name="assigned_commiteee[]" id="assign_to" aria-label="Default select example" multiple>
                                <option value="">Select Commiittee </option>
                                @if($incident_committee)
                                    @foreach($incident_committee as $committee)
                                        <option {{ in_array($committee->id, json_decode($incident->assigned_to, true) ?? []) ? 'selected' : '' }} value="{{$committee->id}}">{{$committee->commitee_name}}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label for="add_comments" class="form-label">ADD COMMENTS</label>
                            <textarea class="form-control" name="comments" id="add_comments" placeholder="Type Here..." rows="3">{{ $incident->comments }}</textarea>
                        </div>
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

        $("#incidentForm").submit(function (e) {
            e.preventDefault(); // Prevent default form submission

            let formData = new FormData($("#incidentForm")[0]);

            // Manually append assigned committee values as an array
            let assignedCommittees = $("#assign_to").val(); // This returns an array
            if (assignedCommittees) {
                assignedCommittees.forEach(id => formData.append("assigned_commiteee[]", id)); // Ensure array format
            }

            $.ajax({
                url: "{{ route('incident.assign') }}", // Adjust the route accordingly
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                    setTimeout(function() {
                        window.location.href = response.redirect_url;
                    }, 1000); 
                    
                },
                error: function (xhr) {
                    let errors = xhr.responseJSON.errors;
                    let errorMessage = "An error occurred!";
                    if (errors) {
                        errorMessage = Object.values(errors).join("\n");
                    }

                    toastr.error(errorMessage, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
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
    }); 
</script>
@endsection