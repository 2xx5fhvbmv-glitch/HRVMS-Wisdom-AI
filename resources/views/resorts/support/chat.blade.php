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
                <div class="row g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Support</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-3 g-xxl-4 card-heigth">
                <div class="col-lg-7">
                    <div class="card card-billingInvoiceSupport">
                        <div class="card-title">
                            <div class="row g-3 g-2 align-items-center justify-content-between">
                                <div class="col-auto">
                                    <div class="d-flex align-items-center">
                                        <div class="me-md-3 me-2">
                                            <div class="profile-initials">
                                                {{ strtoupper(substr($support->assignedAdmin->first_name, 0, 1)) }}{{ strtoupper(substr($support->assignedAdmin->last_name, 0, 1)) }}
                                            </div>                                         
                                        </div>
                                        <div>
                                            <h6>
                                                {{$support->assignedAdmin->first_name." ".$support->assignedAdmin->last_name}}
                                            </h6>
                                            <p>Admin Support</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <span id="chatStatus" class="chatStatus-text online">Online</span>
                                </div>
                            </div>
                        </div>
                        <div class="billingInvoiceChart-block">
                            <div>
                                <div id="chat-messages" class="chat-messages">
                                    @foreach($messages as $msg)    
                                        <div class="chat-msg {{ $msg->sender_type === 'employee' ? 'right' : '' }}">
                                            <div class="img-circle">
                                                @if($msg->sender_type === 'employee')
                                                    <img src="{{Common::getResortUserPicture('$support->createdBy')}}" alt="user"/>
                                                @else
                                                    <div class="profile-initials">
                                                        {{ strtoupper(substr($support->assignedAdmin->first_name, 0, 1)) }}{{ strtoupper(substr($support->assignedAdmin->last_name, 0, 1)) }}
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="msg">
                                                <div class="time"> {{ $msg->created_at->format('h:i A') }}</div>
                                                <p>{{ $msg->message }}</p>
                                                @if($msg->attachment)
                                                    <div class="attachments">
                                                        @foreach(json_decode($msg->attachment, true) as $attachment)

                                                            @if(isset($attachment['Filename']) && isset($attachment['Child_id']))
                                                                <a href="javascript:void(0)" class="download-link" data-id="{{base64_encode($attachment['Child_id'])}}">
                                                                       {{$attachment['Filename']}}
                                                                </a>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="chatSend-input">
                                <input type="hidden" id="support_id" value="{{ $support->id }}">
                                <input type="hidden" id="receiver_id" value="{{ $support->assigned_to }}">
                                <input type="hidden" id="receiver_name" value="{{ $support->assignedAdmin->first_name.' '.$support->assignedAdmin->last_name }}">
                                <input type="hidden" id="receiver_image" value="{{ $support->assignedAdmin->profile_pic }}">
                                <input type="text" id="message" class="form-control" placeholder="Type a message...">
                                 <!-- Attachment Input (Hidden) -->
                                <input type="file" id="attachment" name="attachments[]" class="d-none" accept="image/*, .pdf, .docx, .xlsx" multiple>
                                <label for="attachment" class="attachment-icon">
                                    <i class="fa-solid fa-paperclip"></i>
                                </label>

                                <a href="#" id="sendMessage" class="btn btn-themeBlue">
                                    <i class="fa-solid fa-paper-plane"></i>
                                </a>
                            </div>
                        </div>

                        <!-- File Preview Before Sending -->
                        <div id="file-preview-container" style="display: none; margin-top: 10px;">
                            <div class="file-preview">
                                <div class="file-icon">
                                    <i id="file-icon" class="fa-solid fa-file"></i>
                                </div>
                                <div class="file-info">
                                    <p id="file-name"></p>
                                    <span id="file-size"></span>
                                </div>
                                <div class="remove-file" onclick="removeAttachment()">
                                    <i class="fa-solid fa-xmark"></i>
                                </div>
                            </div>
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
<style>
    .profile-initials {
        width: 40px;
        height: 40px;
        background-color: #2eacb3; /* Change color as needed */
        color: white;
        font-weight: bold;
        font-size: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%; /* Makes it circular */
        text-transform: uppercase;
    }
    .attachment-icon {
        cursor: pointer;
        margin-right: 10px;
        font-size: 18px;
        color: #007bff;
    }

    .file-preview {
        display: flex;
        align-items: center;
        background: #f9f9f9;
        padding: 8px 12px;
        border-radius: 8px;
        max-width: 250px;
        margin-top: 5px;
        box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
    }

    .file-icon {
        font-size: 20px;
        color: #007bff;
        margin-right: 10px;
    }

    .file-info p {
        margin: 0;
        font-size: 14px;
        font-weight: bold;
    }

    .file-info span {
        font-size: 12px;
        color: #666;
    }

    .remove-file {
        margin-left: auto;
        cursor: pointer;
        color: red;
        font-size: 16px;
    }
</style>
@endsection

@section('import-scripts')
<script>
    $(document).ready(function () {
        const supportId = $("#support_id").val(); 
        const receiverId = $("#receiver_id").val(); 
        const receiverName = $("#receiver_name").val(); 
        const receiverImage = $("#receiver_image").val(); 
        const senderName = "{{Auth::guard('resort-admin')->user()->first_name }} {{ Auth::guard('resort-admin')->user()->last_name }}";
        const senderImage = "{{ Common::getResortUserPicture(Auth::guard('resort-admin')->user()->id) }}";
   
        // **Send Message & Emit WebSocket Event**
        $("#sendMessage").click(function (e) {
            e.preventDefault();

            const message = $("#message").val().trim();
            const files = $("#attachment")[0].files;

            if (!message.trim()) {
                toastr.error("Please enter a message.", "Error");
                return false;
            }

            let formData = new FormData();
            formData.append("support_id", $("#support_id").val());
            formData.append("senderId", userId);
            formData.append("senderType", userType);
            formData.append("receiverId", $("#receiver_id").val());
            formData.append("receiverType", userType === "admin" ? "employee" : "admin");
            formData.append("receiver_name", receiverName);
            formData.append("receiver_image", receiverImage);
            formData.append("senderName", senderName);
            formData.append("senderImage", senderImage);
            formData.append("message", message);

            for (let i = 0; i < files.length; i++) {
                formData.append("attachments[]", files[i]);
            }

            $.ajax({
                url: "{{ route('support.chat.sendMessage') }}",
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.success) {

                        let attachments = response.message.attachments;
                        if (typeof attachments === "string") {
                            attachments = JSON.parse(attachments); // Convert to array if it's a string
                        }
                        if (!Array.isArray(attachments)) {
                            attachments = []; // Fallback to empty array if null or invalid
                        }
                        // Emit WebSocket event with all message details
                        socket.emit("send-message", {
                            message: response.message.message,
                            senderId: response.message.sender_id,
                            receiverId: response.message.receiver_id,
                            senderName: senderName,
                            senderImage: senderImage,
                            receiverName: receiverName,
                            receiverImage: receiverImage,
                            attachments: attachments
                        });

                        appendMessage({
                            message: response.message.message,
                            senderId: response.message.sender_id,
                            receiverId: response.message.receiver_id,
                            senderName: senderName,
                            senderImage: senderImage,
                            receiverName: receiverName,
                            receiverImage: receiverImage,
                            attachments: attachments

                        }, true , 'employee');
                        $("#message").val("");
                        $("#attachment").val("");
                        $("#file-preview-container").hide();
                    }
                }
            });
        });

        $("#attachment").change(function () {
            let file = this.files[0];
            if (file) {
                let fileName = file.name;
                let fileSize = (file.size / 1024).toFixed(2) + " KB";
                let fileExt = fileName.split('.').pop().toLowerCase();

                let iconClass = "fa-file";
                if (["jpg", "jpeg", "png", "gif"].includes(fileExt)) {
                    iconClass = "fa-file-image";
                } else if (["pdf"].includes(fileExt)) {
                    iconClass = "fa-file-pdf";
                } else if (["doc", "docx"].includes(fileExt)) {
                    iconClass = "fa-file-word";
                } else if (["xls", "xlsx"].includes(fileExt)) {
                    iconClass = "fa-file-excel";
                }

                $("#file-name").text(fileName);
                $("#file-size").text(fileSize);
                $("#file-icon").attr("class", "fa-solid " + iconClass);
                $("#file-preview-container").show();
            }
        });
    });
    function removeAttachment() {
        $("#attachment").val(""); // Clear file input
        $("#file-preview-container").hide();
    }
</script>
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