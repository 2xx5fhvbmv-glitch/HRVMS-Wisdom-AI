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
                                
                                <h3 class="text-nowrap mb-1">{{$meeting->meeting_subject}} <span
                                        class="badge badge-white">#{{$meeting->incidents->incident_id}}</span></h3>
                            </div>
                            <div class="col-auto">
                                <ul class="userDetailList-wrapper">
                                    <li><span>DATE:</span>{{date('d M Y', strtotime($meeting->meeting_date))}}</li>
                                    <li><span>TIME:</span>{{date('h:i A', strtotime($meeting->meeting_time))}}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="mb-md-4 pb-md-1 mb-3">
                        <h6 class="mb-2">Agenda:</h6>
                        <p>{{$meeting->meeting_agenda}}</p>
                    </div>

                    <div class="table-responsive">
                        <table>
                            <tr>
                                <th>Type:</th>
                                <td>{{$meeting->meeting_type}}</td>
                            </tr>
                            <tr>
                                <th>LOCATION:</th>
                                <td>{{$meeting->location}}</td>
                            </tr>
                            <tr>
                                <th>ATTACHMENTS:</th>
                                <td>
                                    @php
                                        $attachments = json_decode($meeting->attachments, true);
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
                                </td>
                            </tr>
                            <tr>
                                <th>Participants:</th>
                                <td>
                                    <div class="user-ovImg">
                                        @if($meeting->participant)
                                            @foreach($meeting->participant as $participant)
                                                <div class="img-circle">
                                                    <img title="{{$participant->employee->resortAdmin->full_name}}" src="{{Common::getResortUserPicture($participant->employee->Admin_Parent_id)}}" alt="image">
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>

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
                    location.reload(); // Reload page after update
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

    })    
</script>
@endsection