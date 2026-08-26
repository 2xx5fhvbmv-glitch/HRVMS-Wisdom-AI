@extends('admin.layouts.app')
@section('page_tab_title', 'Support Detail')

@php
    $admin            = $support->assignedAdmin;
    $adminFirst       = $admin->first_name ?? '';
    $adminLast        = $admin->last_name ?? '';
    $adminFullName    = trim($adminFirst.' '.$adminLast) ?: 'Unassigned';
    $adminInitials    = ($adminFirst !== '' || $adminLast !== '')
        ? strtoupper(substr($adminFirst, 0, 1).substr($adminLast, 0, 1))
        : 'AD';

    $customer         = $support->createdBy;
    $customerFirst    = optional($customer)->first_name ?? '';
    $customerLast     = optional($customer)->last_name ?? '';
    $customerFullName = trim($customerFirst.' '.$customerLast) ?: 'Customer';
    $customerInitials = ($customerFirst !== '' || $customerLast !== '')
        ? strtoupper(substr($customerFirst, 0, 1).substr($customerLast, 0, 1))
        : 'CU';
    $customerImage    = $customer ? \App\Helpers\Common::getUserPictureForAdmin($customer->id) : asset('admin_assets/files/user-image.png');
    $customerEmail    = optional($customer)->email ?? '';
@endphp

@section('content')
<div class="content-wrapper">
  <section class="content">
    <div class="container-fluid py-3">

      {{-- Ticket header --}}
      <div class="row g-3 mb-3">
        <div class="col-12">
          <div class="card shadow-sm">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start flex-wrap">
                <div>
                  <h4 class="mb-1">{{ $support->subject }}</h4>
                  <div class="text-muted small">
                    Ticket <strong>{{ $support->ticketID }}</strong> · {{ $support->support_category->name ?? 'Uncategorized' }}
                  </div>
                </div>
                <div class="text-end">
                  <span class="badge badge-primary" style="padding:6px 12px;font-size:13px;">{{ $support->status }}</span>
                  <div class="text-muted small mt-1">Created: {{ \Carbon\Carbon::flexible($support->created_at)->format('d M Y, h:i A') }}</div>
                </div>
              </div>

              <hr class="my-3">

              <div class="row g-3">
                <div class="col-md-6">
                  <div class="d-flex align-items-center">
                    <img src="{{ $customerImage }}" alt="customer" class="rounded-circle me-2" style="width:42px;height:42px;object-fit:cover;">
                    <div>
                      <div class="fw-600">{{ $customerFullName }}</div>
                      <div class="text-muted small">{{ $customerEmail ?: 'Customer' }}</div>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="text-md-end">
                    <div class="text-muted small">Assigned to</div>
                    <div class="fw-600">{{ $adminFullName }}</div>
                  </div>
                </div>
              </div>

              @if(empty($supportEmails) || $supportEmails->isEmpty())
                <hr class="my-3">
                <div>
                  <div class="text-muted small mb-1">Original description</div>
                  <div>{{ $support->description }}</div>
                </div>
              @endif

              {{-- Attachments uploaded with the original ticket --}}
              @php
                $ticketAttachments = $support->attachments ? json_decode($support->attachments, true) : [];
              @endphp
              @if(!empty($ticketAttachments))
                <hr class="my-3">
                <div class="text-muted small mb-2">Attachments</div>
                <div>
                  @foreach($ticketAttachments as $attachment)
                    @if(isset($attachment['Filename']) && isset($attachment['Child_id']))
                      <a href="javascript:void(0)" class="download-link d-inline-block me-3 mb-1" data-id="{{ base64_encode($attachment['Child_id']) }}">
                        <i class="fas fa-paperclip"></i> {{ $attachment['Filename'] }}
                      </a>
                    @endif
                  @endforeach
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>

      {{-- Conversation --}}
      <div class="row g-3">
        <div class="col-lg-9">
          <div class="card shadow-sm support-thread-card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Email Conversation</h5>
              <span class="text-muted small" id="lastSyncedLabel"></span>
            </div>
            <div class="card-body">
              <div id="chat-messages" class="chat-messages">
                @forelse($supportEmails as $msg)
                  @php
                    $isAdmin   = $msg->sender === 'admin';
                    $msgDateRaw = method_exists($msg, 'getRawOriginal') ? $msg->getRawOriginal('created_at') : $msg->getOriginal('created_at');
                    $msgDate   = $msgDateRaw ? \Carbon\Carbon::parse($msgDateRaw) : null;
                    $timeLabel = $msgDate
                        ? ($msgDate->isToday() ? $msgDate->format('h:i A') : $msgDate->format('d M Y, h:i A'))
                        : '';
                    $atts = $msg->attachments ? json_decode($msg->attachments, true) : [];
                  @endphp
                  <div class="chat-msg {{ $isAdmin ? 'right' : '' }}" data-msg-id="{{ $msg->id }}">
                    <div class="img-circle">
                      @if($isAdmin)
                        <div class="profile-initials">{{ $adminInitials }}</div>
                      @else
                        <img src="{{ $customerImage }}" alt="customer"/>
                      @endif
                    </div>
                    <div class="msg">
                      <div class="meta">
                        <span class="sender">{{ $isAdmin ? $adminFullName : $customerFullName }}</span>
                        <span class="time">{{ $timeLabel }}</span>
                      </div>
                      <div class="body">{!! html_entity_decode($msg->message) !!}</div>
                      @if(!empty($atts))
                        <div class="attachments">
                          @foreach($atts as $a)
                            @if(is_array($a) && isset($a['Filename'], $a['Child_id']))
                              {{-- Wasabi-uploaded file (admin-side replyStore) --}}
                              <a href="javascript:void(0)" class="download-link" data-id="{{ base64_encode($a['Child_id']) }}">
                                <i class="fas fa-paperclip"></i> {{ $a['Filename'] }}
                              </a>
                            @elseif(is_string($a) && $a !== '')
                              {{-- Resort-side sendReply stores plain
                                   filepaths via Storage::disk('public').
                                   Render them as a direct download link. --}}
                              <a href="{{ asset('storage/' . ltrim($a, '/')) }}" target="_blank" class="d-inline-block me-2">
                                <i class="fas fa-paperclip"></i> {{ basename($a) }}
                              </a>
                            @endif
                          @endforeach
                        </div>
                      @endif
                    </div>
                  </div>
                @empty
                  <div class="text-center text-muted py-5">
                    <i class="fas fa-comments fa-2x mb-2 d-block"></i>
                    No email replies yet.
                  </div>
                @endforelse
              </div>
            </div>

            {{-- Inline reply --}}
            @if(!empty($customerEmail))
              <div class="card-footer">
                <form id="adminReplyForm" enctype="multipart/form-data">
                  @csrf
                  <input type="hidden" name="ticket_id" value="{{ $support->id }}">
                  <input type="hidden" name="subject"   value="{{ 'Re: '.$support->subject }}">
                  <div class="mb-2">
                    <textarea name="body" class="form-control" rows="3" placeholder="Type your reply..." required></textarea>
                  </div>
                  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                      <label class="btn btn-light btn-sm mb-0">
                        <i class="fas fa-paperclip me-1"></i> Attach
                        <input type="file" name="attachment[]" multiple style="display:none;" id="adminReplyAttach">
                      </label>
                      <span id="attachNames" class="text-muted small ms-2"></span>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                      <i class="fas fa-paper-plane me-1"></i> Send Reply
                    </button>
                  </div>
                </form>
              </div>
            @endif
          </div>
        </div>

        <div class="col-lg-3">
          <div class="card shadow-sm">
            <div class="card-body">
              <h6 class="mb-3">Quick Actions</h6>
              <a href="{{ route('admin.support.chat', base64_encode($support->id)) }}" class="btn btn-outline-info btn-sm w-100 mb-2">
                <i class="fas fa-comments me-1"></i> Open Chat
              </a>
              <a href="{{ route('admin.supports.index') }}" class="btn btn-outline-secondary btn-sm w-100">
                <i class="fas fa-arrow-left me-1"></i> Back to List
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

{{-- File preview modal (existing behaviour) --}}
@include('partials._file_view_modal')
@endsection

@section('import-css')
<link rel="stylesheet" href="{{ URL::asset('admin_assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
<style>
.support-thread-card { border-radius: 12px; }
.chat-messages {
    max-height: 60vh;
    overflow-y: auto;
    padding: 4px;
}
.chat-msg {
    display: flex;
    gap: 10px;
    margin-bottom: 14px;
    align-items: flex-start;
}
.chat-msg.right { flex-direction: row-reverse; }
.chat-msg .img-circle {
    width: 38px; height: 38px;
    border-radius: 50%;
    overflow: hidden;
    background: #2EACB3;
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-weight: 600;
    flex-shrink: 0;
}
.chat-msg .img-circle img {
    width: 100%; height: 100%; object-fit: cover;
}
.chat-msg .profile-initials {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    background: #014653;
    color: #fff;
}
.chat-msg .msg {
    background: #f1f3f5;
    padding: 10px 14px;
    border-radius: 14px;
    max-width: 70%;
    word-wrap: break-word;
}
.chat-msg.right .msg {
    background: #014653;
    color: #fff;
    border-radius: 14px;
}
.chat-msg.right .msg .meta .sender,
.chat-msg.right .msg .meta .time { color: rgba(255,255,255,0.85); }
.chat-msg .meta {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    font-size: 11px;
    margin-bottom: 4px;
    color: #6c757d;
}
.chat-msg .meta .sender { font-weight: 600; }
.chat-msg .body { font-size: 14px; line-height: 1.45; }
.chat-msg .attachments { margin-top: 6px; font-size: 12px; }
.chat-msg.right .attachments a { color: #ffd97a; }
.chat-msg .attachments a {
    display: inline-block;
    margin-right: 10px;
    text-decoration: none;
}
</style>
@endsection

@section('import-scripts')
<script src="{{ URL::asset('admin_assets/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script>
(function () {
    var ticketId      = '{{ $support->id }}';
    var ticketIdB64   = '{{ base64_encode($support->id) }}';
    var customerName  = @json($customerFullName);
    var customerImage = @json($customerImage);
    var adminName     = @json($adminFullName);
    var adminInitials = @json($adminInitials);
    var fetchUrl      = '{{ route("admin.supports.fetchMessagesJson", base64_encode($support->id)) }}';
    var replyUrl      = '{{ route("support.email.reply") }}';

    var seenIds = new Set();
    $('#chat-messages .chat-msg').each(function () {
        var id = $(this).data('msg-id');
        if (id) seenIds.add(String(id));
    });

    function escapeHtml(s) {
        return $('<div>').text(s == null ? '' : s).html();
    }

    function renderMessage(msg) {
        var isAdmin = msg.sender === 'admin';
        var avatar = isAdmin
            ? '<div class="profile-initials">' + escapeHtml(adminInitials) + '</div>'
            : '<img src="' + customerImage + '" alt="customer"/>';

        var atts = '';
        if (msg.attachments && msg.attachments.length) {
            atts += '<div class="attachments">';
            msg.attachments.forEach(function (a) {
                if (!a) return;
                // Two persisted shapes:
                //   admin-side replyStore  →  { Filename, Child_id }    (Wasabi)
                //   resort-side sendReply  →  "support_attachments/X"    (string filepath)
                if (typeof a === 'object' && a.Filename && a.Child_id) {
                    atts += '<a href="javascript:void(0)" class="download-link" data-id="' + btoa(String(a.Child_id)) + '"><i class="fas fa-paperclip"></i> ' + escapeHtml(a.Filename) + '</a>';
                } else if (typeof a === 'string' && a !== '') {
                    var fileUrl = "{{ asset('storage') }}/" + a.replace(/^\//, '');
                    var name = a.split('/').pop();
                    atts += '<a href="' + fileUrl + '" target="_blank" class="d-inline-block me-2"><i class="fas fa-paperclip"></i> ' + escapeHtml(name) + '</a>';
                }
            });
            atts += '</div>';
        }

        return ''
            + '<div class="chat-msg ' + (isAdmin ? 'right' : '') + '" data-msg-id="' + msg.id + '">'
            +   '<div class="img-circle">' + avatar + '</div>'
            +   '<div class="msg">'
            +     '<div class="meta">'
            +       '<span class="sender">' + escapeHtml(isAdmin ? adminName : customerName) + '</span>'
            +       '<span class="time">' + escapeHtml(msg.time_label || '') + '</span>'
            +     '</div>'
            +     '<div class="body">' + (msg.message || '') + '</div>'
            +     atts
            +   '</div>'
            + '</div>';
    }

    function appendNewMessages(list) {
        if (!Array.isArray(list)) return;
        var $box = $('#chat-messages');
        var anyAppended = false;
        list.forEach(function (m) {
            if (!m || seenIds.has(String(m.id))) return;
            // If the page started in the empty-state, clear it first.
            if ($box.find('.chat-msg').length === 0) $box.empty();
            seenIds.add(String(m.id));
            $box.append(renderMessage(m));
            anyAppended = true;
        });
        if (anyAppended) $box.scrollTop($box[0].scrollHeight);
    }

    function poll() {
        $.getJSON(fetchUrl).done(function (resp) {
            if (resp && resp.success) {
                appendNewMessages(resp.messages || []);
                var stamp = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                $('#lastSyncedLabel').text('Last synced ' + stamp);
            }
        });
    }
    // Initial scroll-to-bottom on load.
    $(function () {
        var $box = $('#chat-messages');
        $box.scrollTop($box[0] ? $box[0].scrollHeight : 0);
        // Poll every 8 seconds. Lightweight enough for a single conversation,
        // and far simpler than wiring a Pusher event for the email thread.
        setInterval(poll, 8000);
    });

    // Inline reply handler — uses the existing replyStore endpoint.
    $('#adminReplyForm').on('submit', function (e) {
        e.preventDefault();
        var fd = new FormData(this);
        // Append files manually (FormData with input[type=file] does this
        // automatically — left explicit for clarity).
        $('#adminReplyAttach').prop('disabled', false);
        var $btn = $(this).find('button[type=submit]');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Sending...');
        $.ajax({
            url: replyUrl,
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        })
        .done(function (resp) {
            if (resp && resp.success) {
                toastr.success(resp.message || 'Reply sent', 'Success', { positionClass: 'toast-bottom-right' });
                $('#adminReplyForm')[0].reset();
                $('#attachNames').text('');
                poll();
            } else {
                toastr.error(resp && resp.message ? resp.message : 'Failed to send reply', 'Error', { positionClass: 'toast-bottom-right' });
            }
        })
        .fail(function (xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to send reply';
            toastr.error(msg, 'Error', { positionClass: 'toast-bottom-right' });
        })
        .always(function () {
            $btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> Send Reply');
        });
    });

    $('#adminReplyAttach').on('change', function () {
        var names = Array.from(this.files || []).map(f => f.name).join(', ');
        $('#attachNames').text(names);
    });

    // Existing file-preview download link (kept verbatim).
    $(document).on('click', '.download-link', function (e) {
        e.preventDefault();
        var childId = $(this).data('id');
        $('#ViewModeOfFiles').html('<div class="text-center"><p>A file link is being generated. Please wait...</p><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        $('#bdVisa-iframeModel-modal-lg').modal('show');
        $.ajax({
            url: '{{ route("resort.visa.XpactEmpFileDownload", "") }}/' + childId,
            type: 'GET',
            data: { child_id: childId, _token: '{{ csrf_token() }}' },
            success: function (response) {
                var fileUrl = response.NewURLshow;
                $('.downloadLink').attr('href', fileUrl);
                var mimeType = (response.mimeType || '').toLowerCase();
                var iframeTypes = ['video/mp4','video/quicktime','video/x-msvideo','application/pdf','text/plain','application/msword','application/vnd.ms-excel'];
                var imageTypes  = ['image/jpeg','image/png','image/gif'];
                if (imageTypes.includes(mimeType)) {
                    $('#ViewModeOfFiles').html('<img src="' + fileUrl + '" class="popupimgFileModule" alt="Image Preview" style="max-width:100%;max-height:70vh;">');
                } else if (iframeTypes.includes(mimeType)) {
                    $('#ViewModeOfFiles').html('<iframe style="width:100%;height:100%;" src="' + fileUrl + '" allowfullscreen></iframe>');
                } else {
                    $('#bdVisa-iframeModel-modal-lg').modal('hide');
                }
            },
            error: function () {
                $('#bdVisa-iframeModel-modal-lg').modal('hide');
                toastr.error('An error occurred while downloading the file.', 'Error', { positionClass: 'toast-bottom-right' });
            }
        });
    });
})();
</script>
@endsection
