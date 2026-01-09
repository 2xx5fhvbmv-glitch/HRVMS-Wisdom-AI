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
            <li class="breadcrumb-item"><a href="{{route('admin.resorts.index')}}">Resorts</a></li>
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
              <form method="POST" id="frmEditPermissions" class="form-horizontal" action="{{ route('admin.resorts.update_permissions', $resort_id) }}">
                  @csrf
                  <div class="container mt-4">
                      <!-- Department Tabs -->
                      <ul class="nav nav-tabs" id="departmentTabs" role="tablist">
                          @foreach($departments as $department)
                              <li class="nav-item" role="presentation">
                                  <button class="department-tab nav-link @if ($loop->first) active @endif" id="tab-{{ $department->id }}" data-bs-toggle="tab" data-bs-target="#department-{{ $department->id }}" type="button" role="tab" aria-controls="department-{{ $department->id }}" aria-selected="@if ($loop->first) true @else false @endif">
                                      {{ $department->name }}
                                  </button>
                              </li>
                          @endforeach
                      </ul>

                      <div class="tab-content mt-3" id="departmentTabsContent">
                          @foreach($departments as $department)
                              <div class="tab-pane fade @if ($loop->first) show active @endif" id="department-{{ $department->id }}" role="tabpanel" aria-labelledby="tab-{{ $department->id }}">
                                  <!-- Position Table for the Department -->
                                  <table class="table table-bordered data table-permissions">
                                      <thead>
                                          <tr>
                                              <th>Position</th>
                                              <th>Module</th>
                                              @foreach($permissions as $permission)
                                                  <th>{{ $permission->name }}</th>
                                              @endforeach
                                              <th>All</th>
                                          </tr>
                                      </thead>
                                      <tbody>
                                          @foreach ($positions[$department->id] as $position)
                                          @foreach ($modules as $module)
                                            @php
                                              $i = 0;
                                            @endphp
                                            <tr>
                                              @if ($loop->first)
                                                  <td rowspan="{{ count($modules) }}">{{ $position->position_title }}</td>
                                              @endif
                                              <td>{{$module->name}}</td>
                                                @foreach($permissions as $permission)
                                                  @php
                                                    $index = in_array($permission->id,$modulePermissions[$module->id]);
                                                  @endphp
                                                  @if($index)
                                                    <td>
                                                      <div class="rdio rdio-primary radio-inline d-flex justify-content-center h-100 align-items-center">
                                                        <input class="form-check-input" id="{{$modulePermissionIds[$module->id][$i]}}" name="module_permissions[]" type="checkbox" value="{{ $position->id }}:{{ $module->id }}:{{ $modulePermissionIds[$module->id][$i] }}" @if(in_array($modulePermissionIds[$module->id][$i],$positionPermissions[$position->id][$module->id])) checked  @endif>
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
                                              <td>
                                                <div class="rdio rdio-primary radio-inline d-flex justify-content-center h-100 align-items-center">
                                                  <input id="{{strtolower($module->name)}}" name="all[]" type="checkbox" class="form-check-input all">
                                                  <label for="{{strtolower($module->name)}}"></label>
                                                </div>
                                              </td>
                                            </tr>
                                          @endforeach
                                          @endforeach
                                      </tbody>
                                  </table>
                              </div>
                          @endforeach
                      </div>
                  </div>
                  <div class="row mt-4">
                      <div class="col-12">
                          <div class="row g-lg-4 g-3">
                              <div class="col-auto">
                                  <button type="submit" class="btn btn-info">Save</button>
                                  <a href="{{ route('admin.positions.index') }}" class="btn btn-outline-info">Cancel</a>
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
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
@endsection

@section('import-scripts')
<script src="{{ URL::asset('admin_assets/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.7/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

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
<script>
    document.querySelectorAll('.select-all').forEach(selectAllCheckbox => {
    selectAllCheckbox.addEventListener('change', function() {
        const positionId = this.dataset.positionId;
        const moduleId = this.dataset.moduleId;
        const isChecked = this.checked;

        document.querySelectorAll(`input[name="permissions[${positionId}][${moduleId}][]"]`).forEach(checkbox => {
            checkbox.checked = isChecked;
        });
    });
});

</script>
@endsection
