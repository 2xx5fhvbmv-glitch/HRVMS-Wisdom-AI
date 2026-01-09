@extends('admin.layouts.app')
@section('page_tab_title' ,"Chat")

@section('content')
<div class="content-wrapper">
  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">            
           <!-- DIRECT CHAT -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Direct Chat</h3>
                </div>

                <div class="card-body">
                    <!-- Chat Messages -->
                    <div id="chat-messages" class="direct-chat-messages">
                        @foreach($messages as $msg)
                            <!-- Message to the right -->
                            <div class="direct-chat-msg {{ $msg->sender_type === 'admin' ? 'right' : '' }}">
                                <div class="direct-chat-infos clearfix">
                                    @if($msg->sender_type === 'admin')
                                        <span class="direct-chat-name float-right">{{$support->assignedAdmin->first_name}} {{$support->assignedAdmin->last_name}}</span>
                                        <span class="direct-chat-timestamp float-left"> {{ $msg->created_at->format('h:i A') }}</span>
                                    @else
                                        <span class="direct-chat-name float-left">{{$support->createdBy->first_name}} {{$support->createdBy->last_name}}</span>
                                        <span class="direct-chat-timestamp float-right"> {{ $msg->created_at->format('h:i A') }}</span>
                                    @endif
                                </div>
                                <!-- /.direct-chat-infos -->
                                @if($msg->sender_type === 'admin')
                                    <div class="profile-initials direct-chat-img">
                                        {{ strtoupper(substr($support->assignedAdmin->first_name, 0, 1)) }}{{ strtoupper(substr($support->assignedAdmin->last_name, 0, 1)) }}
                                    </div>
                                @else
                                    <img class="direct-chat-img" src="{{Common::getResortUserPicture('$support->createdBy->GetEmployee->Admin_Parent_id')}}" alt="message user image">
                                @endif
                                <!-- /.direct-chat-img -->
                                <div class="direct-chat-text">
                                {{ $msg->message }}
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
                                <!-- /.direct-chat-text -->
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="card-footer">
                    <form id="chat-form" enctype="multipart/form-data">
                        <div class="input-group">
                            <input type="hidden" id="support_id" value="{{ $support->id }}">
                            <input type="hidden" id="receiver_id" value="{{ $support->createdBy->GetEmployee->id }}">
                            <input type="hidden" id="receiver_name" value="{{ $support->createdBy->first_name.' '.$support->createdBy->last_name }}">
                            <input type="hidden" id="receiver_image" value="{{ Common::getResortUserPicture($support->createdBy) }}">

                            <input type="text" id="message" name="message" placeholder="Type Message..." class="form-control">

                            <input type="file" id="attachment" name="attachments[]" class="d-none" accept="image/*, .pdf, .docx, .xlsx" multiple>
                            <label for="attachment" class="attachment-icon">
                                <i class="fa fa-paperclip"></i>
                            </label>
                            
                            <span class="input-group-append">
                                <button type="submit" class="btn btn-primary">Send</button>
                            </span>
                        </div>
                    </form>
                    <!-- File Preview Before Sending -->
                    <div id="file-preview-container" style="display: none; margin-top: 10px;">
                        <div class="file-preview">
                            <div class="file-icon">
                                <i id="file-icon" class="fa fa-file"></i>
                            </div>
                            <div class="file-info">
                                <p id="file-name"></p>
                                <span id="file-size"></span>
                            </div>
                            <div class="remove-file" onclick="removeAttachment()">
                                <i class="fa fa-times"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
      </div>
    </div>
  </section>
<div class="modal fade" id="bdVisa-iframeModel-modal-lg" tabindex="-1" aria-labelledby="myLargeModalLabel"
      aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title mb-0" id="staticBackdropLabel">Download File</h5>
        <div class="d-flex align-items-center">
            <a href="#" class="btn btn-sm btn-primary downloadLink" target="_blank" style="margin-right: 35px;">Download</a>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
      </div>
      <div class="modal-body">
        <div class=" ratio ratio-21x9" id="ViewModeOfFiles">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
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
        position: absolute;
        top: 50%;
        right: 72px;
        transform: translateY(-50%);
        font-size: 14px;
        color: #DDDDDD;
        cursor: pointer;
        margin-right: 10px;
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
<script src="https://cdn.socket.io/4.0.1/socket.io.min.js"></script>
<script>
    $(document).ready(function () {
        const socket = io("{{ env('BASE_URL', 'http://localhost:3000') }}"); // WebSocket server from env
        const userId = "{{ Auth::guard('admin')->user()->id }}"; // Admin ID
        const userType = "admin"; // Change dynamically for resort users
        const supportId = $("#support_id").val(); 
        const receiverId = $("#receiver_id").val(); 
        const receiverName = $("#receiver_name").val(); 
        const receiverImage = $("#receiver_image").val(); 
        const panelType = "{{ Auth::guard('admin')->check() ? 'admin' : 'resort' }}"; // Detect panel type
        const senderName = "{{Auth::guard('admin')->user()->first_name }} {{ Auth::guard('admin')->user()->last_name }}";
        const senderImage = "{{ Auth::guard('admin')->user()->profile_pic }}";
        socket.emit("register-user", userId);

        // Function to append message on the correct panel
        function appendMessage(data, isSender) {
            let position = isSender ? "right" : "";
            let senderName = data.senderName || "Unknown";
            let time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            let senderImage = data.senderImage ? data.senderImage : null;
            let senderInitials = senderName.split(" ").map(n => n.charAt(0)).join("").toUpperCase();
            let imageHtml = senderImage
                ? `<img class="direct-chat-img" src="${senderImage}" alt="user"/>`
                : `<div class="profile-initials direct-chat-img">${senderInitials}</div>`;

            // **Attachments HTML**
            let attachmentsHtml = "";
            if (data.attachments && data.attachments.length > 0) {
                attachmentsHtml = `<div class="attachments">`;
                data.attachments.forEach(file => {
                    attachmentsHtml += `
                        <a href="${file}" target="_blank" class="attachment-link">
                            <i class="fa fa-file"></i> ${file.split('/').pop()}
                        </a>
                    `;
                });
                attachmentsHtml += `</div>`;
            }

            let chatHtml = "";

            // **Admin Panel HTML**
            if (panelType === "admin") {
                chatHtml = `
                    <div class="direct-chat-msg ${position}">
                        <div class="direct-chat-infos clearfix">
                            <span class="direct-chat-name float-${position}">${senderName}</span>
                            <span class="direct-chat-timestamp float-${position === "right" ? "left" : "right"}">${time}</span>
                        </div>
                        ${imageHtml}
                        <div class="direct-chat-text">${data.message || ''} ${attachmentsHtml}</div>
                    </div>
                `;
            }
            
            // **Resort Panel HTML**
            else if (panelType === "resort") {
                chatHtml = `
                    <div class="chat-msg ${position}">
                        <div class="img-circle">
                            ${imageHtml}
                        </div>
                        <div class="msg">
                            <div class="time">${time}</div>
                            <p>${data.message || ''}</p>
                            ${attachmentsHtml}
                        </div>
                    </div>
                `;
            }

            // Append message to both Admin and Resort chat boxes
            $("#chat-messages").append(chatHtml);
        }


        // **Send Message & Emit WebSocket Event**
        $("#chat-form").submit(function (e) {
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
            // const messageData = {
            //     support_id: supportId,
            //     senderId: userId,
            //     senderType: userType,
            //     receiverId: receiverId,
            //     receiverType: userType === "admin" ? "employee" : "admin",
            //     receiver_name: receiverName,
            //     receiver_image: receiverImage,
            //     senderName: "{{ Auth::guard('admin')->user()->first_name }} {{ Auth::guard('admin')->user()->last_name }}",
            //     senderImage: "{{ Auth::guard('admin')->user()->profile_pic }}", // Use initials if null
            //     message: message
            // };

            // **Step 1: Save Message in Database**
            $.ajax({
                url: "{{ route('admin.chat.sendMessage') }}",
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
                        // **Step 2: Emit WebSocket event**
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
                        // **Append message on sender's side**
                        appendMessage({
                            message: response.message.message,
                            senderId: response.message.sender_id,
                            receiverId: response.message.receiver_id,
                            senderName: senderName,
                            senderImage: senderImage,
                            receiverName: receiverName,
                            receiverImage: receiverImage,
                            attachments: attachments

                        }, true , 'admin');

                        $("#message").val(""); // Clear input
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
                $("#file-icon").attr("class", "fa " + iconClass);
                $("#file-preview-container").show();
            }
        });

        // **Receiving a message in real time**
        socket.on("receive-message", function (data) {
            console.log("📨 New message received:", data);

            // Ensure that 'userId' is defined
            if (typeof userId === "undefined") {
                console.log("⚠️ Warning: userId is not defined.");
                return;
            }

            let attachments = data.message.attachments;
            if (typeof attachments === "string") {
                attachments = JSON.parse(attachments); // Convert to array if it's a string
            }
            if (!Array.isArray(attachments)) {
                attachments = []; // Fallback to empty array if null or invalid
            }
            console.log("📨 New message userId:", userId);

            if (data.senderId !== userId) {
                appendMessage({
                    message: data.message,
                    senderId: data.senderId,
                    receiverId: data.receiverId,
                    senderName: data.senderName,
                    senderImage: data.senderImage,
                    receiverName: data.receiverName,
                    receiverImage: data.receiverImage,
                    attachments: attachments
                }, false , 'admin');
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
    });
    function removeAttachment() {
        $("#attachment").val(""); // Clear file input
        $("#file-preview-container").hide();
    }
</script>
@endsection
