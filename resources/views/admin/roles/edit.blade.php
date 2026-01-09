@extends('admin.layouts.app')

@if($isNew == 1)
	@section('page_tab_title' ,"Create Role")
@else
	@section('page_tab_title' ,"Edit Role")
@endif

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
					@if( $isNew == 1 )
						<h1 class="m-0">Create Role</h1>
					@else
						<h1 class="m-0">Edit Role</h1>
					@endif
				</div>
				<div class="col-sm-6">
					<ol class="breadcrumb float-sm-right">
						<li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Home</a></li>
						<li class="breadcrumb-item"><a href="{{route('admin.role.index')}}">Roles</a></li>

						@if( $isNew == 1 )
							<li class="breadcrumb-item active">Create Role</li>
						@else
							<li class="breadcrumb-item active">Edit Role</li>
						@endif
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

						<form method="post" name="formStore" id="formStore" enctype="multipart/form-data" @if( empty($data->id) ) action="{{ route('admin.role.store') }}" @else action="{{ route('admin.role.update', $data->id) }}" @endif >
							@csrf
							<div class="card-body">
								<div class="row">
									<div class="col-sm-6">
										<div class="form-group">
											<label>Name <span class="red-mark">*</span></label>
											<input        
										
											data-parsley-pattern="^[a-zA-Z0-9]+( [a-zA-Z0-9]+)*$" data-parsley-pattern-message="Only alphanumeric characters are allowed." required type="text" name="name" id="name" class="form-control" placeholder="Name" value="{{$data->name}}">
											<div id="div-name"></div>
										</div>
									</div>
									<div class="col-sm-6">
										<div class="form-group">
											<label>Status <span class="red-mark">*</span></label>
											<div class="form-group brdrfull">
												<div class="row">
													<div class="col-auto pr-0">
														<div class="age-title">
															<input type="radio" @if($data->id != '' && $data->status == "active") checked="true" @else checked="true" @endif id="status-yes" name="status" value="active">
															<label for="status-yes">Active</label>
														</div>
													</div>
													<div class="col-auto">
														<div class="age-title">
															<input type="radio" id="status-no" value="inactive" name="status" @if($data->id != '' && $data->status == "inactive") checked="true" @endif>
															<label for="status-no">Inactive</label>
														</div>
													</div>
												</div>
											</div>
											<div id="div-status"></div>
										</div>
									</div>
								</div>
							</div>
							<div class="card-footer">
								<button type="submit" class="btn btn-info">Submit</button>
								<button type="button" id="reset" class="btn btn-default resetForm">Reset</button>

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
@endsection

@section('import-scripts')
<script>
	$(document).ready(function() {
		$.validator.addMethod("alphabeticSpace", function(value, element) {
			return this.optional(element) || /^[a-zA-Z ]+$/.test(value);
		}, "Should contain only alphabetic characters and spaces");

		var url = "{{route('admin.list')}}";

		$('#formStore').validate({
			errorClass: 'text-danger-custom',
			ignore: [],
			rules: {
				'name': {
					required: true,
					alphabeticSpace: true,
					maxlength: 30 ,
				},
				'status': {
					required: true
				}
			},
			messages: {
				'name': {
					required: "The name field is required",
					maxlength: "Max length should be 30 characters" ,
				},
				'status': {
					required: "The status field is required"
				}
			},
			errorPlacement: function(error, element) {
				if( element.attr("name") == "name" ) {
					error.insertAfter( "#div-name" );
				} else if( element.attr("name") == "status" ) {
					error.insertAfter( "#div-status" );
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

		var resetButtons = document.getElementsByClassName('resetForm');

		// Loop through each reset buttons to bind the click event
		for(var i=0; i<resetButtons.length; i++){
			resetButtons[i].addEventListener('click', resetForm);
		}

	});

</script>
@endsection
