@extends('admin.layouts.app')
@section('page_tab_title' ,"Chat")

@section('content')
<div class="content-wrapper">
  <section class="content">
    <div class="container-fluid">
      @php
          $admin            = $support->assignedAdmin;
          $adminFirst       = $admin->first_name ?? '';
          $adminLast        = $admin->last_name ?? '';
          $adminFullName    = trim($adminFirst.' '.$adminLast) ?: 'Unassigned';
          $adminInitials    = ($adminFirst !== '' || $adminLast !== '')
              ? strtoupper(substr($adminFirst, 0, 1).substr($adminLast, 0, 1))
              : '—';

          $customer         = $support->createdBy;
          $customerFirst    = optional($customer)->first_name ?? '';
          $customerLast     = optional($customer)->last_name ?? '';
          $customerFullName = trim($customerFirst.' '.$customerLast) ?: 'Customer';
          $customerInitials = ($customerFirst !== '' || $customerLast !== '')
              ? strtoupper(substr($customerFirst, 0, 1).substr($customerLast, 0, 1))
              : '—';
          $customerImage    = $customer ? \App\Helpers\Common::getUserPictureForAdmin($customer->id) : asset('admin_assets/files/user-image.png');
          $customerEmpId    = optional(optional($customer)->GetEmployee)->id;
      @endphp
      <div class="row g-3 g-xxl-4">
        <div class="col-lg-8 mx-auto">
          <div class="card card-billingInvoiceSupport">
            <div class="card-title">
              <div class="row g-3 g-2 align-items-center justify-content-between">
                <div class="col-auto">
                  <div class="d-flex align-items-center">
                    <div class="me-md-3 me-2">
                      <img src="{{ $customerImage }}" alt="customer" class="img-circle"/>
                    </div>
                    <div>
                      <h6>{{ $customerFullName }}</h6>
                      <p class="mb-0 text-muted">Customer</p>
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
                    @php
                        // Show full date for older messages, time-only for
                        // today. Previously only the time was rendered, so
                        // every chat row looked like it happened "today".
                        $msgDate = $msg->created_at;
                        $timeLabel = $msgDate && $msgDate->isToday()
                            ? $msgDate->format('h:i A')
                            : ($msgDate ? $msgDate->format('d M Y, h:i A') : '');
                    @endphp
                    <div class="chat-msg {{ $msg->sender_type === 'admin' ? 'right' : '' }}">
                      <div class="img-circle">
                        @if($msg->sender_type === 'admin')
                          <div class="profile-initials">{{ $adminInitials }}</div>
                        @else
                          <img src="{{ $customerImage }}" alt="customer"/>
                        @endif
                      </div>
                      <div class="msg">
                        <div class="time">{{ $timeLabel }}</div>
                        <p>{{ $msg->message }}</p>
                        @if($msg->attachment)
                          <div class="attachments">
                            @foreach(json_decode($msg->attachment, true) as $attachment)
                              @if(isset($attachment['Filename']) && isset($attachment['Child_id']))
                                <a href="javascript:void(0)" class="download-link" data-id="{{ base64_encode($attachment['Child_id']) }}">
                                  {{ $attachment['Filename'] }}
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
              <form id="chat-form" enctype="multipart/form-data" class="chatSend-input">
                <input type="hidden" id="support_id" value="{{ $support->id }}">
                <input type="hidden" id="receiver_id" value="{{ $customerEmpId }}">
                <input type="hidden" id="receiver_name" value="{{ $customerFullName }}">
                <input type="hidden" id="receiver_image" value="{{ $customerImage }}">

                <input type="text" id="message" name="message" class="form-control" placeholder="Type a message...">
                <input type="file" id="attachment" name="attachments[]" class="d-none" accept="image/*, .pdf, .docx, .xlsx" multiple>
                <label for="attachment" class="attachment-icon">
                  <i class="fa-solid fa-paperclip"></i>
                </label>
                <button type="submit" class="btn btn-primary chat-send-btn">
                  <i class="fa-solid fa-paper-plane"></i>
                </button>
              </form>
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
    /* Chat container — ported from resort_assets/css/default.css so the
       admin support chat looks identical to the resort one. */
    .card.card-billingInvoiceSupport {
        padding: 20px 30px;
        border-radius: 12px;
    }
    .card-billingInvoiceSupport .card-title {
        margin-bottom: 16px;
        padding-bottom: 16px;
        border-bottom: 1px solid #E7E7E7;
    }
    .card-billingInvoiceSupport .img-circle {
        width: 50px;
        height: 50px;
        min-width: 50px;
        border-radius: 50%;
        overflow: hidden;
        display: inline-block;
    }
    .card-billingInvoiceSupport .img-circle img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
    }
    .card-billingInvoiceSupport h6 {
        font-size: 18px;
        font-weight: 600;
        margin: 0;
    }
    .chatStatus-text.online  { --chatStatus: #7AD45A; }
    .chatStatus-text.offline { --chatStatus: #A90000; }
    .chatStatus-text {
        display: flex;
        align-items: center;
        color: var(--chatStatus);
    }
    .chatStatus-text:before {
        content: '';
        width: 14px;
        height: 14px;
        background: var(--chatStatus);
        border-radius: 50%;
        margin-right: 4px;
    }
    .billingInvoiceChart-block {
        display: flex;
        flex-direction: column;
    }
    .billingInvoiceChart-block > div:first-child {
        height: 595px;
        overflow: auto;
        padding-right: 4px;
    }
    .billingInvoiceChart-block > div:first-child > div {
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        min-height: 100%;
    }
    .chat-msg {
        display: flex;
        align-items: end;
        gap: 12px;
        margin-bottom: 30px;
    }
    .chat-msg.right {
        flex-direction: row-reverse;
        justify-content: flex-start;
        text-align: right;
    }
    .chat-msg .img-circle {
        width: 40px;
        height: 40px;
        min-width: 40px;
        border-radius: 50%;
        overflow: hidden;
    }
    .chat-msg .img-circle img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .chat-msg .msg {
        padding: 18px 24px;
        background: #FFFFFF;
        border: 1px solid #E7E7E7;
        border-radius: 8px;
        max-width: 80%;
    }
    .chat-msg.right .msg {
        background: #F0F8FF;
    }
    .chat-msg .time {
        font-size: 12px;
        opacity: .6;
        margin-bottom: 4px;
    }
    .chat-msg p {
        font-size: 16px;
        margin: 0;
        white-space: pre-wrap;
        word-break: break-word;
    }

    /* Composer */
    .chatSend-input {
        position: relative;
        margin-top: 8px;
    }
    .chatSend-input .form-control {
        padding-right: 90px;
        border-radius: 8px;
        height: 50px;
    }
    .chatSend-input .attachment-icon {
        position: absolute;
        top: 50%;
        right: 60px;
        transform: translateY(-50%);
        font-size: 16px;
        color: #888;
        cursor: pointer;
        margin: 0;
    }
    .chatSend-input .chat-send-btn {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        font-size: 18px;
        padding: 10px 18px;
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }

    /* Avatar fallback (initials) */
    .profile-initials {
        width: 40px;
        height: 40px;
        background-color: #2eacb3;
        color: white;
        font-weight: bold;
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        text-transform: uppercase;
    }
    .card-billingInvoiceSupport > .card-title .img-circle .profile-initials {
        width: 50px;
        height: 50px;
        font-size: 18px;
    }

    /* Attachment list */
    .attachments {
        margin-top: 8px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .attachments a.download-link,
    .attachments a.attachment-link {
        color: #007bff;
        text-decoration: underline;
        word-break: break-all;
    }

    /* File preview card before send */
    .file-preview {
        display: flex;
        align-items: center;
        background: #f9f9f9;
        padding: 8px 12px;
        border-radius: 8px;
        max-width: 280px;
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
@include('partials.pusher-init')
<script src="https://cdn.socket.io/4.0.1/socket.io.min.js"></script>
<script>
    $(document).ready(function () {
        // Legacy Node socket.io server (BASE_URL) — wrapped in try/catch so
        // a stopped/missing server doesn't spam the console or break the
        // page. Real-time delivery is handled by Pusher/Echo above.
        var socket;
        try {
            socket = io("{{ env('BASE_URL', 'http://localhost:3000') }}", { reconnection: false, timeout: 2000 });
        } catch (e) { socket = { emit: function () {}, on: function () {} }; }
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

        // Scroll to the latest message on initial load.
        (function () {
            const c = $('#chat-messages');
            if (c.length) c.scrollTop(c[0].scrollHeight);
        })();

        // === Real-time incoming messages via Laravel Echo / Pusher ===
        // Server fires NewChatMessage on the public 'chat.{receiver_id}'
        // channel. The admin subscribes on its own admin id; messages from the
        // resort employee will surface here without page reload. Guarded so it
        // silently no-ops when Echo isn't bundled (BROADCAST_DRIVER=log).
        if (typeof window.Echo !== 'undefined' && userId) {
            window.Echo.channel('chat.' + userId)
                .listen('NewChatMessage', function (e) {
                    console.log('[chat] incoming', { senderId: e.senderId, receiverId: e.receiverId, message: e.message });
                    // Channel `chat.{userId}` is scoped to this admin, so
                    // any event here is meant for us. Just skip our own
                    // echoes — don't filter by senderId/receiverId match.
                    if (String(e.senderId) === String(userId)) return;
                    appendMessage({
                        senderName:  e.senderName,
                        senderImage: e.senderImage,
                        message:     e.message,
                        attachments: e.attachments || [],
                    }, false);
                    if (typeof window.playChatPing === 'function') window.playChatPing();
                    const c = $('#chat-messages');
                    if (c.length) c.scrollTop(c[0].scrollHeight);
                });
        }

        // Render a chat bubble in the new resort-style markup. Both incoming
        // and outgoing messages use this — `isSender=true` flips the bubble
        // to the right-hand side via the `.right` class.
        function appendMessage(data, isSender) {
            const position = isSender ? "right" : "";
            const senderName = data.senderName || "Unknown";
            // Live messages just landed → today by definition, so show
            // time-only. The blade-rendered history below uses the message's
            // own created_at and prepends a date when it's older than today.
            const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
            const senderImage = data.senderImage || null;
            const senderInitials = senderName.split(" ").map(n => n.charAt(0)).join("").toUpperCase();
            const safeMessage = $('<div>').text(data.message || '').html();

            const imageHtml = senderImage
                ? `<img src="${senderImage}" alt="user"/>`
                : `<div class="profile-initials">${senderInitials}</div>`;

            // Attachments — server returns { Filename, Child_id }; render as
            // `.download-link` so the existing click handler picks them up.
            let attachmentsHtml = "";
            if (data.attachments && data.attachments.length > 0) {
                attachmentsHtml = `<div class="attachments">`;
                data.attachments.forEach(file => {
                    if (!file) return;
                    if (typeof file === "string") {
                        attachmentsHtml += `
                            <a href="${file}" target="_blank" class="attachment-link">
                                <i class="fa fa-file"></i> ${file.split('/').pop()}
                            </a>`;
                        return;
                    }
                    const filename = file.Filename || file.filename || 'file';
                    const childId  = file.Child_id || file.child_id;
                    if (!childId) return;
                    const encodedId = btoa(String(childId));
                    attachmentsHtml += `
                        <a href="javascript:void(0)" class="download-link" data-id="${encodedId}">
                            <i class="fa fa-file"></i> ${filename}
                        </a>`;
                });
                attachmentsHtml += `</div>`;
            }

            const chatHtml = `
                <div class="chat-msg ${position}">
                    <div class="img-circle">
                        ${imageHtml}
                    </div>
                    <div class="msg">
                        <div class="time">${time}</div>
                        <p>${safeMessage}</p>
                        ${attachmentsHtml}
                    </div>
                </div>
            `;

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
