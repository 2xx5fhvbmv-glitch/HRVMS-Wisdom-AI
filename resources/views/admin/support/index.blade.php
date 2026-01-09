@extends('admin.layouts.app')
@section('page_tab_title' ,"Support")

@section('content')
<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid">
        <div class="row">
            <div class="col-12">
            <div class="card">
                <div class="card-header">
                <h1>Support List</h1>
                <div>

                    <!-- <a href="javascript:void(0)" id="bulk_delete" class="btn btn-danger float-right mr-2"><i class="fas fa-minus"></i> Delete</a> -->
                </div>
                </div>
                <div class="card-body">
                <div class="table-responsive">
                    <table id="datatable" class="table table-bordered table-hover dt-responsive">
                    <thead>
                        <tr>
                            <th><input type="checkbox" name="all_chk" id="all_chk"></th>
                            <th>Ticket ID</th>
                            <th>Employee Name</th>
                            <th>Category</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    </table>
                </div>
                </div>
            </div>
            </div>
        </div>
        </div>
    </section>
    <!-- Assign Ticket Modal -->
    <div class="modal fade" id="assignTicketModal" tabindex="-1" aria-labelledby="assignTicketLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignTicketLabel">Assign Ticket</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="ticket_id">
                    <label>Select Admin:</label>
                    <select id="admin_select" class="form-control">
                        <option value="">-- Select Admin --</option>
                        @foreach($support_admins as $admin)
                            <option value="{{ $admin->id }}">{{ $admin->first_name }} {{ $admin->last_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveAssignment">Assign</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Change Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusModalLabel">Change Ticket Status</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="statusForm">
                        <input type="hidden" id="ticket_id">
                        <div class="form-group">
                            <label for="ticket_status">Select Status</label>
                            <select class="form-control" id="ticket_status">
                                <option value="New">New</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Close">Close</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="replyModal" tabindex="-1" role="dialog" aria-labelledby="replyModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form id="replyForm" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="replyModalLabel">Reply to Support Ticket</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="to">To:</label>
                            <input type="hidden" class="form-control" id="ticket_id" name="ticket_id">
                            <input type="email" class="form-control" id="to" name="to" readonly>
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject:</label>
                            <input type="text" class="form-control" id="subject" name="subject">
                        </div>
                        <div class="form-group">
                            <label for="body">Message:</label>
                            <textarea class="form-control" id="body" name="body" rows="10" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="attachment">Attachment:</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="attachment" name="attachment[]" multiple>
                                <label class="custom-file-label" for="attachment">Choose file</label>
                            </div>
                            <small class="form-text text-muted">Max. 32MB</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Send Reply</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('import-css')
<link rel="stylesheet" href="{{ URL::asset('admin_assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ URL::asset('admin_assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ URL::asset('admin_assets/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ URL::asset('admin_assets/plugins/summernote/summernote-bs4.min.css') }}">
<style>
  .tableUser-block {
      display: flex;
      align-items: center;
      white-space: nowrap;
      line-height: 1.3;
  }

  .tableUser-block .img-circle {
        width: 30px; /* Increased size */
        height: 30px;
        min-width: 30px;
        margin-right: 7px;
        border-radius: 50%;
        overflow: hidden;
        display: flex;
        justify-content: center;
    }
</style>
@endsection

@section('import-scripts')
<script src="{{ URL::asset('admin_assets/plugins/summernote/summernote-bs4.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
<script>
  $(document).ready(function() {
    // Initialize Summernote when modal is opened
    $('#replyModal').on('shown.bs.modal', function () {
        $('#body').summernote({
            height: 300,
            focus: true
        });
    });

    // Reset form and destroy Summernote on modal close
    $('#replyModal').on('hidden.bs.modal', function () {
        $('#body').summernote('destroy');
        $('#replyForm')[0].reset();
        $('.custom-file-label').text('Choose file');
    });

    var url = "{{ route('admin.supports.list') }}";

    var table = $('#datatable').DataTable({
        processing: true, 
        serverSide: true,
        ajax: url,
        order: [[2, "asc"]], // Sort by category
        columns: [
            { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false },
            { data: 'ticketID', name: 'ticketID' },
            { data: 'employee_name', name: 'employee_name', orderable: false, searchable: false },
            { data: 'category', name: 'category' },
            { data: 'subject', name: 'subject' },
            { data: 'created_at', name: 'created_at' },
            { data: 'status', name: 'status', orderable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        columnDefs: [
            { responsivePriority: 1, targets: 0 },
            { responsivePriority: 1, targets: -1 }
        ]
    });

    // Tooltip re-initialization on table draw
    table.on('draw.dt', function() {
        $('[data-bs-toggle="tooltip"]').tooltip(); // Use 'data-bs-toggle' for Bootstrap 5+
    });

    $("#all_chk").click(function() {
      if ($(this).is(':checked')) {
        var $view = $('.support_checkbox').prop("checked", true);
      } else {
        var $view = $('.support_checkbox').prop("checked", false);
      }
    });

    $(document).on('click', '.support_checkbox', function() {
      var numberOfChecked = $('.support_checkbox').filter(':checked').length;
      var totalCheckboxes = $('.support_checkbox').length;

      if (numberOfChecked == totalCheckboxes) {
        var $view = $('#all_chk').prop("checked", true);
      }

      if (!$(this).is(':checked')) {
        var $view = $('#all_chk').prop("checked", false);
      }
    });

    $(document).on("click", ".change-status", function () {
        let ticketId = $(this).data("id");
        let currentStatus = $(this).data("status");

        $("#ticket_id").val(ticketId);
        $("#ticket_status").val(currentStatus);

        $("#statusModal").modal("show");
    });

    // Handle form submission
    $("#statusForm").on("submit", function (e) {
        e.preventDefault();

        let ticketId = $("#ticket_id").val();
        let newStatus = $("#ticket_status").val();

        $.ajax({
            url: "{{route('admin.supports.updateStatus')}}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                ticket_id: ticketId,
                status: newStatus
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message, "Success", { positionClass: "toast-bottom-right" });
                    $("#statusModal").modal("hide");
                    $("#datatable").DataTable().ajax.reload();
                } else {
                    toastr.error("Failed to update status.", "Error", { positionClass: "toast-bottom-right" });
                }
            },
            error: function () {
                toastr.error("Error updating status.", "Error", { positionClass: "toast-bottom-right" });
            }
        });
    });

    $(document).on('click', '.assign-ticket', function() {
        let ticketId = $(this).data('id');
        $('#ticket_id').val(ticketId);
        $('#assignTicketModal').modal('show');
    });

    // Show modal and populate fields when clicking "Reply"
    $(document).on('click', '.reply-email', function() {
        let ticketId = $(this).data('id');
        let replyTo = $(this).data('reply-to');
        let subject = $(this).data('subject');
        $('#ticket_id').val(ticketId);
        $('#to').val(replyTo);
        $('#subject').val('Re: ' + subject);
        $('#replyModal').modal('show');
    });

    // Display selected file names for multiple uploads
    $('.custom-file-input').on('change', function() {
        let fileNames = Array.from(this.files).map(file => file.name).join(', ');
        $(this).next('.custom-file-label').text(fileNames || 'Choose file');
    });

    $('#saveAssignment').on('click', function() {
        let ticketId = $('#ticket_id').val();
        let adminId = $('#admin_select').val();
        let assignUrl = "{{ route('admin.supports.assign', ':id') }}".replace(':id', ticketId);

        $.ajax({
            url: assignUrl,
            method: 'POST',
            data: {
                assigned_to: adminId,
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
        
                toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right'} );

                $('#assignTicketModal').modal('hide');
                $('#datatable').DataTable().ajax.reload();

            }
        });
    });

    // Handle form submission
    $('#replyForm').submit(function(e) {
        e.preventDefault();

        console.log("Ticket ID just before submission:", $('#ticket_id').val()); // Debugging Output

        let formData = new FormData(this);

        // Manually append ticket_id if missing
        if (!formData.has('ticket_id') || !formData.get('ticket_id')) {
            let ticketId = $('#ticket_id').val();
            formData.append('ticket_id', ticketId);
            console.log("Manually appended ticket_id:", ticketId);
        }

        console.log("Final FormData Entries:");
        for (let pair of formData.entries()) {
            console.log(pair[0], pair[1]);
        }
        $.ajax({
            url: "{{ route('support.email.reply') }}",
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                $('#replyModal').modal('hide');
                toastr.success(response.message, "Success", {
                    positionClass: 'toast-bottom-right'
                });
                location.reload(); // Refresh page to show the new reply
            },
            error: function(xhr) {
                toastr.success('An error occurred while sending the reply.', "Success", {
                    positionClass: 'toast-bottom-right'
                });
            }
        });
    });

  });
</script>
@endsection
