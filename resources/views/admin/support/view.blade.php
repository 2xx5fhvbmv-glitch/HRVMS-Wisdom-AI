@extends('admin.layouts.app')
@section('page_tab_title' ,"Support Detail")

@section('content')
<div class="content-wrapper">
  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h1>Support Detail</h1>
            </div>
            <div class="card-body">
                <p><strong>Ticket Id: </strong> {{$support->ticketID}}</p>
                <p><strong>Employee Name:</strong> {{ $support->createdBy->first_name }} {{ $support->createdBy->last_name }}</p>
                <p><strong>Category:</strong> {{ $support->support_category->name ?? 'N/A' }}</p>
                <p><strong>Subject:</strong> {{ $support->subject }}</p>
                @if($supportEmails->isEmpty())
                  <p><strong>Description:</strong> {{ $support->description }}</p>
                @endif
                <p><strong>Status:</strong> <span class="badge badge-primary">{{ $support->status }}</span></p>
                <p><strong>Created At:</strong> {{ \Carbon\Carbon::parse($support->created_at)->format('d M Y') }}</p>

            </div>

            @if($supportEmails->isNotEmpty())
              <div class="card-body">
                    <!-- Chat Messages -->
                  <div id="chat-messages" class="direct-chat-messages">
                      @foreach($supportEmails as $msg)
                          <!-- Message to the right -->
                          <div class="direct-chat-msg {{ $msg->sender === 'admin' ? 'right' : '' }}">
                                                    
                              <!-- /.direct-chat-img -->
                              <div class="direct-chat-text">
                              {!! html_entity_decode($msg->message) !!}

                              @if($msg->attachments)
                                  <div class="attachments">
                                      @foreach(json_decode($msg->attachments, true) as $attachment)
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
            @endif

            <div class="card-footer float-end">
              <a href="{{ route('admin.supports.index') }}" class="btn btn-secondary float-end">Back</a>
            </div>
          </div>
        </div>
      </div>
    </div>
    
  </section>
</div>

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
@endsection

@section('import-css')
<link rel="stylesheet" href="{{ URL::asset('admin_assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ URL::asset('admin_assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ URL::asset('admin_assets/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
<style>
 
</style>
@endsection

@section('import-scripts')
<script src="{{ URL::asset('admin_assets/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
<script>
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
</script>
@endsection
