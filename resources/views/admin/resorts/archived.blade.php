@extends('admin.layouts.app')
@section('page_tab_title' ,"Archived Resorts")

@section('content')
<div class="content-wrapper">
  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h1>Archived Resorts</h1>
              <div>
                <a href="{{ route('admin.resorts.index') }}" class="btn btn-secondary float-right mr-2"><i class="fas fa-arrow-left"></i> Back to Resorts</a>
              </div>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table id="archivedResortsTable" class="table table-bordered table-hover dt-responsive">
                  <thead>
                    <tr>
                      <th><input type="checkbox" name="all_chk" id="all_chk"></th>
                      <th>Resort ID</th>
                      <th>Resort Name</th>
                      <th>Resort Email</th>
                      <th>Status</th>
                      <th>Payment Status</th>
                      <th>Created By</th>
                      <th>Created At</th>
                      <th>Last Updated</th>
                      <th>Action</th>
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
</div>
@endsection

@section('import-css')
<link rel="stylesheet" href="{{ URL::asset('admin_assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ URL::asset('admin_assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ URL::asset('admin_assets/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
@endsection

@section('import-scripts')
<script src="{{ URL::asset('admin_assets/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
<script>
  $(document).ready(function() {
    var url = "{{ route('admin.resorts.archivedList') }}";

    var table = $('#archivedResortsTable').DataTable({
      processing: true,
      serverSide: true,
      ajax: url,
      order: [],
      columns: [
        { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false },
        { data: 'resort_id', name: 'resort_id' },
        { data: 'resort_name', name: 'resort_name' },
        { data: 'resort_email', name: 'resort_email' },
        { data: 'status', name: 'status' },
        { data: 'payment_status', name: 'payment_status' },
        { data: 'created_by', name: 'created_by' },
        { data: 'created_at', name: 'created_at' },
        { data: 'updated_at', name: 'updated_at' },
        { data: 'action', name: 'action', orderable: false, searchable: false },
      ],
      columnDefs: [
        { responsivePriority: 1, targets: 0 },
        { responsivePriority: 1, targets: -1 }
      ]
    });

    table.on('draw.dt', function() {
      $('[data-toggle="tooltip"]').tooltip();
    });

    $(document).on('click', '.action-restore', function(e) {
      e.preventDefault();
      var url = $(this).data('url');

      swal({
        title: 'Sure want to restore?',
        text: 'This cannot be undone',
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'No',
        confirmButtonColor: "#DD6B55"
      }).then(function(success) {
        if (success) {
          $.ajax({
            type: "POST",
            url: url,
            dataType: "json",
            beforeSend: function(msg) {
              HoldOn.open({
                theme: "sk-cube-grid"
              });
            },
          }).done(function(result) {
            HoldOn.close();
            if (result.success == true) {
              toastr.success(result.msg, "Success", {
                positionClass: 'toast-bottom-right'
              });

              $('#archivedResortsTable').DataTable().ajax.reload();
            } else {
              toastr.error(result.msg, "Error", {
                positionClass: 'toast-bottom-right'
              });
            }
          }).fail(function(error) {
            HoldOn.close();
            toastr.error("Something went wrong", "Error", {
              positionClass: 'toast-bottom-right'
            });
          });
        }
      });
    });
  });
</script>
@endsection
