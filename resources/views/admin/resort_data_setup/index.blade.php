@extends('admin.layouts.app')
@section('page_tab_title', 'Resort Data Setup')

@section('content')
<div class="content-wrapper">
  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-header">
          <h1>Resort Data Setup</h1>
          <p class="text-muted mb-0">Load a new resort's divisions, departments, sections, positions, staff and attendance from the exports of their previous HR system.</p>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="datatable" class="table table-bordered table-hover">
              <thead>
                <tr>
                  <th>Resort ID</th>
                  <th>Resort Name</th>
                  <th>Status</th>
                  <th>Employees</th>
                  <th>Data Setup</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                @foreach($resorts as $resort)
                  @php($last = $lastImport[$resort->id] ?? null)
                  <tr>
                    <td>{{ $resort->resort_id }}</td>
                    <td>{{ $resort->resort_name }}</td>
                    <td>{{ $resort->status }}</td>
                    <td>{{ $employees[$resort->id] ?? 0 }}</td>
                    <td>
                      @if(!$last)
                        <span class="badge badge-secondary">Not started</span>
                      @elseif($last->status === 'imported')
                        <span class="badge badge-success">Imported {{ optional($last->imported_at)->format('d M Y H:i') }}</span>
                      @elseif($last->status === 'undone')
                        <span class="badge badge-secondary">Last import undone</span>
                      @else
                        <span class="badge badge-warning">In progress</span>
                      @endif
                    </td>
                    <td>
                      <a class="btn btn-primary btn-sm" href="{{ route('admin.resort_data_setup.show', $resort->id) }}" title="View">
                        <i class="fas fa-eye"></i> View
                      </a>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
@endsection

@section('import-css')
<link rel="stylesheet" href="{{ URL::asset('admin_assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
@endsection

@section('import-scripts')
<script src="{{ URL::asset('admin_assets/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script>
  $(function () { $('#datatable').DataTable({ order: [[1, 'asc']], columnDefs: [{ orderable: false, targets: 5 }] }); });
</script>
@endsection
