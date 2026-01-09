@extends('admin.layouts.app')

@section('page_tab_title' ,"Edit Email Template")

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
					<h1 class="m-0">Edit Email Template</h1>
				</div>
				<div class="col-sm-6">
					<ol class="breadcrumb float-sm-right">
						<li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Home</a></li>
						<li class="breadcrumb-item active">Edit Email Template</li>
					</ol>
				</div>
			</div>
		</div>
	</div>

	<!-- Main content -->
	<section class="content">
		<div class="container-fluid">
			<div class="row">
				<div class="col-md-12">
					<div class="card card-warning">
						
						<form method="post" name="formStore" id="formStore" enctype="multipart/form-data" @if( empty($data->id) ) action="{{ route('admin.emailTemplate.store') }}" @else action="{{ route('admin.emailTemplate.update', $data->id) }}" @endif>
							@csrf
							<div class="card-body">
								<div class="row">
									<div class="col-sm-6">
										<div class="form-group">
											<label>Name <span class="red-mark">*</span></label>
											<input type="text" name="name" id="name" class="form-control" placeholder="Name" value="{{$data->name ?? old('name') }}">
											<div id="div-name"></div>
										</div>
									</div>
									<div class="col-sm-6">
										<div class="form-group">
											<label>Subject <span class="red-mark">*</span></label>
											<input type="text" name="subject" id="subject" class="form-control" placeholder="Subject" value="{{$data->subject ?? old('subject') }}">
											<div id="div-subject"></div>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-sm-12">
										<div class="form-group">
											<label>Content <span class="red-mark">*</span></label>
											<textarea class="form-control" required="" id="body" name="body" rows="10" cols="80">{{$data->body ?? old('body')}}</textarea>
											<div id="div-body"></div>
										</div>
									</div>
								</div>
							</div>
							<div class="card-footer">
								<button type="submit" class="btn btn-info">Submit</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</section>
</div>
@endsection

@section('import-css')
<link rel="stylesheet" href="{{ URL::asset('admin_assets/plugins/summernote/summernote-bs4.min.css') }}">
@endsection

@section('import-scripts')
<script src="{{ URL::asset('admin_assets/plugins/summernote/summernote-bs4.min.js') }}"></script>
<script>
	$(document).ready(function() {
		$('#body').summernote();

		$('#formStore').validate({
			errorClass: 'text-danger-custom',
			ignore: [],
			rules: {
				'name': {
					required: true
				},
				'subject': {
					required: true
				},
				'body': {
					required: true
				}
			},
			messages: {
				'name': {
					required: "The name  is required"
				},
				'subject': {
					required: "The subject is required"
				},
				'body': {
					required: "The body is required"
				}
			},
			errorPlacement: function(error, element) {
				if( element.attr("name") == "name" ) {
					error.insertAfter( "#div-name" );
				} else if( element.attr("name") == "subject" ) {
					error.insertAfter( "#div-subject" );
				} else if( element.attr("name") == "body" ) {
					error.insertAfter( "#div-body" );
				} else {
					error.insertAfter(element);
				}
			},
			errorElement: 'span'
		});

		$( "#formStore" ).on( "submit", function(e) {
			e.preventDefault();

			var dataString = $(this).serialize();
			var url = $(this).attr('action');

			if( $(this).valid() ) {
				$.ajax({
					type: "POST",
					url: url,
					data: new FormData(this),
					dataType: "JSON",
					contentType: false,
					processData: false,
					beforeSend: function(msg) {
						HoldOn.open({
							theme:"sk-cube-grid"
						});
					},
					success: function (result) {
						HoldOn.close();
						if( result.success == true ) {
							toastr.success( result.msg, "Success", { positionClass: 'toast-bottom-right'} );

							window.setTimeout(function() {
								window.location.href = result.redirect_url;
							}, 2000);
						} else {
							toastr.error( result.msg, "Error", { positionClass: 'toast-bottom-right'} );
						}
					},
					error: function (result) {
						HoldOn.close();
						toastr.error( "Something went wrong", "Error", { positionClass: 'toast-bottom-right'} );
					}
				});
			}
		});
	});
</script>
@endsection