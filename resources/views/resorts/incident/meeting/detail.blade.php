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
                                        @foreach ($attachments as $attachment)
                                            @if (is_array($attachment) && !empty($attachment['Filename']) && !empty($attachment['Child_id']))
                                                @php
                                                    $extension = strtolower(pathinfo($attachment['Filename'], PATHINFO_EXTENSION));
                                                    $icon = match ($extension) {
                                                        'pdf'                                  => 'fa-file-pdf text-danger',
                                                        'doc', 'docx'                          => 'fa-file-word text-primary',
                                                        'xls', 'xlsx'                          => 'fa-file-excel text-success',
                                                        'jpg', 'jpeg', 'png', 'gif'            => 'fa-file-image text-warning',
                                                        default                                => 'fa-file text-secondary',
                                                    };
                                                @endphp
                                                <a href="javascript:void(0)" class="download-link me-3" data-id="{{ base64_encode($attachment['Child_id']) }}" title="{{ $attachment['Filename'] }}">
                                                    <i class="fa-solid {{ $icon }} fa-lg"></i> {{ $attachment['Filename'] }}
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
                                    @if($meeting->participant && count($meeting->participant))
                                        <div class="d-flex flex-wrap gap-3">
                                            @foreach($meeting->participant as $participant)
                                                @php
                                                    $admin = optional(optional($participant->employee)->resortAdmin);
                                                    $first = $admin->first_name ?? '';
                                                    $last  = $admin->last_name ?? '';
                                                    $name  = trim($first . ' ' . $last) ?: 'Unknown';
                                                    $role  = $participant->participant_role ?? null;
                                                    $img   = Common::getResortUserPicture(optional($participant->employee)->Admin_Parent_id);
                                                @endphp
                                                <div class="d-flex align-items-center" style="gap:8px;">
                                                    <div class="img-circle" style="width:36px;height:36px;overflow:hidden;border-radius:50%;flex-shrink:0;">
                                                        <img src="{{ $img }}" alt="{{ $name }}" style="width:100%;height:100%;object-fit:cover;">
                                                    </div>
                                                    <div>
                                                        <div style="font-weight:600;font-size:14px;">{{ $name }}</div>
                                                        @if ($role)
                                                            <div class="text-muted" style="font-size:12px;">{{ $role }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted">No participants</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>External Participants:</th>
                                <td>
                                    @if($meeting->externalParticipant && count($meeting->externalParticipant))
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($meeting->externalParticipant as $ext)
                                                @if (!empty($ext->participant_name))
                                                    <span class="badge bg-light text-dark border" style="padding:6px 10px;font-size:13px;">
                                                        <i class="fa-solid fa-user me-1 text-secondary"></i>{{ $ext->participant_name }}
                                                    </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted">None</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th style="vertical-align: top;">Previous Notes / Findings:</th>
                                <td>
                                    @if(!isset($previousMeetings) || $previousMeetings->isEmpty())
                                        <span class="text-muted">No previous meetings have been recorded for this incident.</span>
                                    @else
                                        <div class="previous-notes-block">
                                            @foreach($previousMeetings as $pm)
                                                <div class="previous-note-item mb-3 pb-3 border-bottom">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <strong>{{ $pm->meeting_subject }}</strong>
                                                        <span class="text-muted small">
                                                            {{ \Carbon\Carbon::parse($pm->meeting_date)->format('d M Y') }}
                                                            @if($pm->meeting_time)
                                                                · {{ \Carbon\Carbon::parse($pm->meeting_time)->format('h:i A') }}
                                                            @endif
                                                        </span>
                                                    </div>
                                                    <p class="mb-0 text-body small">{{ $pm->meeting_agenda }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
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

        // Resolve a Child_id to a presigned Wasabi URL and open in a new tab.
        $(document).on('click', '.download-link', function (e) {
            e.preventDefault();
            var childId = $(this).data('id');
            if (!childId) return;
            $.ajax({
                url: "{{ route('resort.visa.XpactEmpFileDownload', '') }}/" + childId,
                type: 'GET',
                success: function (response) {
                    if (response && response.NewURLshow) {
                        window.open(response.NewURLshow, '_blank');
                    } else {
                        toastr.error('File not found.', 'Error', { positionClass: 'toast-bottom-right' });
                    }
                },
                error: function () {
                    toastr.error('Failed to open file.', 'Error', { positionClass: 'toast-bottom-right' });
                }
            });
        });

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