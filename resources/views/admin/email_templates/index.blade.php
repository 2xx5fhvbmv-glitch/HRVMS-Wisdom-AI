@extends('admin.layouts.app')
@section('page_tab_title' ,"Email Templates")

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
					<h1 class="m-0">Email Templates</h1>
				</div>
				<div class="col-sm-6">
					<ol class="breadcrumb float-sm-right">
						<li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Home</a></li>
						<li class="breadcrumb-item active">Email Templates</li>
					</ol>
				</div>
			</div>
		</div>
	</div>

	<!-- Main content -->
	<section class="content">
		<div class="container-fluid">
			<div class="row">
				<div class="col-12">
					<div class="card">
						<div class="card-header">
							<a href="{{route('admin.emailTemplate.create')}}" class="btn btn-primary float-right"><i class="fas fa-plus"></i> Add Email Template</a>
						</div>
						<div class="card-body">
							<div class="table-responsive">
								<table id="datatable" class="table table-bordered table-hover dt-responsive">
									<thead>
										<tr>
											<th>Name</th>
											<th>Created at</th>
											<th>Updated at</th>
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
<!-- DataTables  & Plugins -->
<script src="{{ URL::asset('admin_assets/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/jszip/jszip.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/pdfmake/pdfmake.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/pdfmake/vfs_fonts.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/datatables-buttons/js/buttons.html5.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/datatables-buttons/js/buttons.print.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/datatables-buttons/js/buttons.colVis.min.js') }}"></script>
<script>
	$(document).ready(function() {
		var url = "{{route('admin.emailTemplate.list')}}";

		var table = $('#datatable').DataTable({
			processing: true,
			serverSide: true,
			ajax: url,
			ordering : true,
			columns: [
				{ 
					data: 'name',
					name: 'name',
				},
				{ data: 'created_at', name: 'created_at' },
				{
					data: 'updated_at',
					name: 'updated_at',
					searchable: false
				},
				{
					data: 'action',
					name: 'action',
					orderable: false,
					searchable: false
				}
			],
			order: [[1, 'desc']]
		});

		// $('#datatable tfoot th').each(function() {
    	// var title = $(this).text();

    	// if( title != '' ) {
    	// 	$(this).html('<input class="form-control" type="text" placeholder="Search ' + title + '" />');
    	// }
    });

    table.columns().every( function() {
    	var that = this;
    	
    	$('input', this.footer()).on('keyup change', function() {
    		if (that.search() !== this.value) {
    			that
    			.search(this.value)
    			.draw();
    		}
    	});
    });

</script>
@endsection