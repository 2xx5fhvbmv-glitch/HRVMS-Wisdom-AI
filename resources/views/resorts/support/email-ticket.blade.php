@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@section('content')
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding">
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Support</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>                       
                </div>
            </div>

            <div class="card card-billingInvoiceSupport">
                <div class="row g-3 g-2 align-items-center justify-content-between">
                    <div class="col-auto">
                        <div class="d-flex align-items-center">
                            <div>
                                <h6> #{{$support->ticketID}}</h6>
                                <p>Category: {{ $support->support_category->name ?? 'N/A' }}</p>
                                <p>Subject: {{$support->subject}}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto">
                        <span id="chatStatus" class="chatStatus-text online">{{$support->status}}</span>
                    </div>
                </div>
                
                <div class="billingInvoiceChart-block">
                    <div>
                        <div>
                            @foreach($supportEmails as $message)
                                @php
                                    $isCurrentUser = ($message->sender_id == $loggedInEmployee); // Assuming auth user ID is available
                                    $positionClass = $isCurrentUser ? 'right' : ''; // Add 'right' class for sender messages
                                    $userImage = $isCurrentUser ? asset('assets/images/user-3.svg') : asset('assets/images/user-2.svg');
                                @endphp

                                <div class="chat-msg {{ $positionClass }}">
                                    <!-- <div class="img-circle">
                                        <img src="{{ $userImage }}" alt="user">
                                    </div> -->
                                    <div class="msg">
                                        <div class="time">{{ \Carbon\Carbon::parse($message->created_at)->format('d-M-Y h:i A') }}</div>
                                        <div class="content">
                                            <p>{!! html_entity_decode($message->message) !!}</p>
                                            
                                            @if(!empty($message->attachments))
                                                <ul>
                                                    @foreach(json_decode($message->attachments, true) as $attachment)

                                                        @if(isset($attachment['Filename']) && isset($attachment['Child_id']))
                                                            <li>



                                                                    <a href="javascript:void(0)" class="download-link" data-id="{{base64_encode($attachment['Child_id'])}}">
                                                                       {{$attachment['Filename']}}
                                                                    </a>
                                                                
                                                            
                                                            </li>
                                                        @endif
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
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
$(document).ready(function() {
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