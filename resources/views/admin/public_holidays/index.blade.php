@extends('admin.layouts.app')
@section('page_tab_title' ,"Public Holidays")

@section('content')
<div class="content-wrapper">
  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h1>Public Holidays</h1>
              <div>
                @if(Common::hasPermission(config('settings.public_holidays.departments'),config('settings.permissions.create')))
                  <a href="{{route('admin.public_holidays.create')}}" class="btn btn-primary float-right"><i class="fas fa-plus"></i> Add</a>
                @endif
                @if(Common::hasPermission(config('settings.public_holidays.departments'),config('settings.permissions.delete')))
                  <a href="javascript:void(0)" id="bulk_delete" class="btn btn-danger float-right mr-2"><i class="fas fa-minus"></i> Delete</a>
                @endif
              </div>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table id="datatable" class="table table-bordered table-hover dt-responsive">
                  <thead>
                    <tr>
                      <th><input type="checkbox" name="all_chk" id="all_chk"></th>
                      <th>Name</th>
                      <th>Holiday Date</th>
                      <th>Description</th>
                      <th>Status</th>
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
    var url = "{{route('admin.public_holidays.list')}}";

    var table = $('#datatable').DataTable({
      processing: false,
      ordering: true,
      serverSide: true,
      ajax: url,
      order: [],
      columns: [{
        data: 'checkbox',
        name: 'checkbox',
        orderable: false,
        searchable: false
      },
      {
        data: 'name',
        name: 'name',
      },
      {
        data: 'holiday_date',
        name: 'holiday_date',
      },
      {
        data: 'description',
        name: 'description',
      },
      {
        data: 'status',
        name: 'status',
      },
      {
        data: 'created_by',
        name: 'created_by',
      },
      {
        data: 'created_at',
        name: 'created_at',
      },
      {
        data: 'updated_at',
        name: 'updated_at',
      },
      {
        data: 'action',
        name: 'action',
        searchable: false,
        orderable: false,
      }
      ],
      columnDefs: [{
				responsivePriority: 1,
				targets: 0
			}, {
				responsivePriority: 1,
				targets: -1
			}]
    });

    table.on('draw.dt', function() {
      $('[data-toggle="tooltip"]').tooltip();
    });

    $("#all_chk").click(function() {
      if ($(this).is(':checked')) {
        var $view = $('.holiday_checkbox').prop("checked", true);
      } else {
        var $view = $('.holiday_checkbox').prop("checked", false);
      }
    });

    $(document).on('click', '.holiday_checkbox', function() {
      var numberOfChecked = $('.holiday_checkbox').filter(':checked').length;
      var totalCheckboxes = $('.holiday_checkbox').length;

      if (numberOfChecked == totalCheckboxes) {
        var $view = $('#all_chk').prop("checked", true);
      }

      if (!$(this).is(':checked')) {
        var $view = $('#all_chk').prop("checked", false);
      }
    });

    $(document).on('click', '.action-delete', function(e) {
      e.preventDefault();
      var url = $(this).data('url');

      swal({
        title: 'Sure want to delete?',
        text: 'This cannot be undone',
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'No',
        confirmButtonColor: "#DD6B55"
      }).then(function(success) {
        if (success) {
          $.ajax({
            type: "DELETE",
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

              $('#datatable').DataTable().ajax.reload();
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

    $(document).on('click', '#bulk_delete', function() {
      var id = [];

      $('.holiday_checkbox:checked').each(function() {
        id.push($(this).val());
      });

      if (id.length > 0) {
        swal({
          title: 'Sure want to delete selected data?',
          text: 'This cannot be undone',
          type: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Yes',
          cancelButtonText: 'No',
          confirmButtonColor: "#DD6B55"
        }).then(function(success) {
          if (success) {
            $.ajax({
              url: "{{route('admin.public_holidays.massremove')}}",
              data: {
                id: id
              },
              dataType: "json",
              beforeSend: function(msg) {
                HoldOn.open({
                  theme: "sk-cube-grid"
                });
              },
            }).done(function(result) {
              HoldOn.close();
              $('#all_chk').prop("checked", false);
              if (result.success == true) {
                toastr.success(result.msg, "Success", {
                  positionClass: 'toast-bottom-right'
                });

                $('#datatable').DataTable().ajax.reload();
              } else {
                toastr.error(result.msg, "Error", {
                  positionClass: 'toast-bottom-right'
                });
              }
            }).fail(function(error) {
              HoldOn.close();
              $('#all_chk').prop("checked", false);
              toastr.error("Something went wrong", "Error", {
                positionClass: 'toast-bottom-right'
              });
            });
          }
        });
      } else {
        toastr.error("Please select atleast one checkbox", "Error", {
          positionClass: 'toast-bottom-right'
        });
      }
    });

    $(document).on('click', '.changeStatus', function(e) {
      e.preventDefault();
      var url = $(this).data('url');

      $.ajax({
        type: "GET",
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
          $("#datatable").DataTable().ajax.reload();
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
    });
  });
</script>
@endsection