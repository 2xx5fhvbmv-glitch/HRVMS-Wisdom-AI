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
                            <a href="#" class="btn btn-themeBlue btn-sm">Attachment</a>
                            <input type="file" id="uploadFile" name="attachments[]" multiple>
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
                ['Bold', 'Italic', 'Underline'], // Only include Bold, Italic, and Underline
                ['Font', 'FontSize'] // Font family and size
            ],
            removePlugins: 'elementspath', // Remove the bottom status bar
            resize_enabled: false // Disable resizing
        });
    });
</script>
@endsection