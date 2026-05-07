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

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach
                    </ul>
                </div>
            @endif

            <div class="card card-billingInvoiceSupport">
                <div class="card-title mb-3">
                    <h3>Compose New Message</h3>
                </div>
                <form id="emailReplyForm" action="{{ route('resort.support-email.reply') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="ticket_id" value="{{ $ticketId }}">
                    <input type="text" class="form-control form-control-small mb-md-3 mb-2" value="{{$supportEmail}}" name="to_email" placeholder="To" required readonly>
                    <input type="text" class="form-control form-control-small mb-3" name="subject" placeholder="Subject:" required value="Re : {{$support->subject}}">

                    <div class="mb-3">
                        <textarea id="editor" name="message"></textarea>
                    </div>

                    <div class="uploadFile-block flex-wrap mb-3">
                        <div class="uploadFile-btn">
                            {{-- Anchor triggers the (hidden) file picker so users get the
                                 "Attachment" button labelling without seeing the raw input. --}}
                            <a href="javascript:void(0)" id="attachmentPickerBtn" class="btn btn-themeBlue btn-sm">Attachment</a>
                            <input type="file" id="uploadFile" name="attachments[]" multiple style="display:none;">
                            <span id="uploadFile-list" class="ms-2 small text-muted"></span>
                        </div>
                        <div class="uploadFile-text">PDF or Excel</div>
                    </div>

                    <div class="card-footer">
                        <div class="row g-2">
                            <div class="col-auto">
                                <button type="submit" class="btn btn-themeBlue btn-sm">Send</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script type="text/javascript">
    $(document).ready(function () {
        CKEDITOR.replace('editor', {
            toolbar: [
                ['Bold', 'Italic', 'Underline'],
                ['Font', 'FontSize']
            ],
            removePlugins: 'elementspath',
            resize_enabled: false
        });

        // Make the "Attachment" button actually open the file picker, and
        // surface the picked filenames so users get feedback.
        $('#attachmentPickerBtn').on('click', function () {
            $('#uploadFile').trigger('click');
        });
        $('#uploadFile').on('change', function () {
            var names = Array.from(this.files).map(function (f) { return f.name; }).join(', ');
            $('#uploadFile-list').text(names || '');
        });

        // AJAX submit — keeps the user on the page, surfaces validation
        // errors inline, resets the form on success.
        $('#emailReplyForm').on('submit', function (e) {
            e.preventDefault();

            // Sync CKEditor's iframe content back to the underlying textarea
            // before grabbing the FormData — otherwise message arrives empty.
            if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances && CKEDITOR.instances.editor) {
                CKEDITOR.instances.editor.updateElement();
            }

            var $form    = $(this);
            var $submit  = $form.find('button[type="submit"]');
            var formData = new FormData(this);

            $submit.prop('disabled', true).text('Sending...');

            $.ajax({
                url:        $form.attr('action'),
                type:       'POST',
                data:       formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('input[name="_token"]', $form).val(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                success: function (response) {
                    toastr.success(
                        (response && response.message) || 'Reply sent successfully.',
                        'Success',
                        { positionClass: 'toast-bottom-right' }
                    );
                    if (CKEDITOR.instances.editor) {
                        CKEDITOR.instances.editor.setData('');
                    }
                    $('#uploadFile').val('');
                    $('#uploadFile-list').text('');
                },
                error: function (xhr) {
                    var msg = 'Failed to send reply. Please try again.';
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    toastr.error(msg, 'Error', { positionClass: 'toast-bottom-right' });
                },
                complete: function () {
                    $submit.prop('disabled', false).text('Send');
                }
            });
        });
    });
</script>
@endsection