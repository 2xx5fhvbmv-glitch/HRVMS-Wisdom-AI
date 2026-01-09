@extends('admin.layouts.app')
@section('page_tab_title' , "Permissions")

@if ($message = Session::get('success'))
<div class="alert alert-success">
  <p>{{ $message }}</p>
</div>
@endif

@section('content')
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Permissions</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{route('admin.role.index')}}">Roles</a></li>
            <li class="breadcrumb-item active">Permissions</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-12">
          <div class="card card-warning">
            <div class="card-body">
              <form method="POST" id="frmEditPermissions" class="form-horizontal" action="{{ route('admin.role.update_role_permissions',$role->id) }}" >
                @csrf
                <div class="table-responsive">
                  <table id="datatable" class="table table-bordered table-striped data table-permissions">
                    <thead>
                      <tr>
                        <th style="text-align:left">Module</th>
                        <th style="text-align:left">All</th>
                        @foreach($permissions as $permission)
                          <th>{{$permission->name}}</th>
                        @endforeach
                      </tr>
                    </thead>
                    <tbody>
                      @foreach ($modules as $module)
                        @php
                          $i = 0;
                        @endphp
                        <tr>
                          <td>{{$module->name}}</td>
                          <td>
                            <div class="rdio rdio-primary radio-inline d-flex justify-content-center h-100 align-items-center">
                              <input id="{{strtolower($module->name)}}" name="all[]" type="checkbox" class="form-check-input all">
                              <label for="{{strtolower($module->name)}}"></label>
                            </div>
                          </td>
                          @foreach($permissions as $permission)
                            @php
                              $index = in_array($permission->id,$modulePermissions[$module->id]);
                            @endphp

                            @if($index)
                              <td>
                                <div class="rdio rdio-primary radio-inline d-flex justify-content-center h-100 align-items-center">
                                  <input class="form-check-input" id="{{$modulePermissionIds[$module->id][$i]}}" name="module_permissions[]" type="checkbox" value="{{$modulePermissionIds[$module->id][$i]}}" @if(in_array($modulePermissionIds[$module->id][$i],$rolePermissions)) checked  @endif>
                                  <label for="{{$modulePermissionIds[$module->id][$i]}}"></label>
                                </div>
                              </td>
                              @php
                                $i++;
                              @endphp
                            @else
                              <td></td>
                            @endif
                          @endforeach
                        </tr>
                      @endforeach 
                    </tbody>
                  </table>
                </div>
                <div class="row mt-4">
                  <div class="col-12">
                    <div class="row g-lg-4 g-3">
                      <div class="col-auto">
                        <button type="submit" class="btn btn-info">Save</button>
                        <a href="{{route('admin.role.index')}}" class="btn btn-outline-info">Cancel</a>
                      </div>
                    </div>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script src="{{ URL::asset('admin_assets/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>

<script type="text/javascript">
  var url = "{{route('admin.role.list')}}";

  $(document).ready(function() {
    var table = $('#datatable').DataTable({
      paging: false,
      lengthChange: false,
      ordering: false,
      searching: false,
      info: false
    });

    $(".all").click(function () {
      if ($(this).is(':checked')) {
        $(this).parents("tr").find(".form-check-input").prop("checked", true)
      } else {
        $(this).parents("tr").find(".form-check-input").prop("checked", false)
      }
    });

    $(document).on("click", ".form-check-input", function (e) {
      var numberOfChecked = $(this).parents("tr").find(".form-check-input").filter(':checked').length;
      var totalCheckboxes = $(this).parents("tr").find(".form-check-input").length-1;

      if (numberOfChecked == totalCheckboxes) {
        $(this).parents("tr").find(".all").prop("checked", true);
      }

      if (!$(this).is(':checked')) {
        $(this).parents("tr").find(".all").prop("checked", false);
      }
    });

    $(document).on( "submit", "#frmEditPermissions", function(e) {
      e.preventDefault();

      var url = $(this).attr('action');

      var formData = new FormData(this);

      $.ajax({
        type: "POST",
        url: url,
        data: formData,
        dataType: "JSON",
        contentType: false,
        processData: false,
        cache: false,
        beforeSend: function(msg) {
          HoldOn.open({
            theme:"sk-cube-grid"
          });
        },
        success: function (result) {
          HoldOn.close();
          if( result.success == true ) {
            toastr.success( result.msg, "Success", { positionClass: 'toast-bottom-right'} );
          } else {
            toastr.error( result.msg, "Error", { positionClass: 'toast-bottom-right'} );
          }
        },
        error: function (result) {
          HoldOn.close();
          toastr.error( "Something went wrong", "Error", { positionClass: 'toast-bottom-right'} );
        }
      });
    });
  });
</script>
@endsection