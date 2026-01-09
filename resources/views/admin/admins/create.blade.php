@extends('admin.layouts.app')

@if($isNew == 1)
	@section('page_tab_title' ,"Create Admin")
@else
	@section('page_tab_title' ,"Edit Admin")
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
						<h1 class="m-0">Create Admin</h1>
					@else
						<h1 class="m-0">Edit Admin</h1>
					@endif
				</div>
				<div class="col-sm-6">
					<ol class="breadcrumb float-sm-right">
						<li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Home</a></li>
						<li class="breadcrumb-item"><a href="{{route('admin.index')}}">Admins</a></li>
						@if( $isNew == 1 )
							<li class="breadcrumb-item active">Create Admin</li>
						@else
							<li class="breadcrumb-item active">Edit Admin</li>
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
						<form method="post" name="formStore" id="formStore" enctype="multipart/form-data" @if(empty($admin->id)) action="{{ route('admin.store') }}" @else action="{{ route('admin.update', $admin->id) }}" @endif >
							@csrf
							@if($admin->id != '')
								<input type="hidden" name="id" id="id" value="{{$admin->id}}">
							@endif
							<div class="card-body">
								<div class="row">
									<div class="col-sm-6">
										<div class="form-group">
											<label>First Name <span class="red-mark">*</span></label>
											<input type="text" name="first_name" id="first_name" class="form-control" placeholder="First name" value="{{$admin->first_name ?? old('first_name') }}">
											<div id="div-first_name"></div>
										</div>
									</div>
									<div class="col-sm-6">
										<div class="form-group">
											<label>Last Name <span class="red-mark">*</span></label>
											<input type="text" name="last_name" id="last_name" class="form-control" placeholder="Last name" value="{{$admin->last_name ?? old('last_name') }}">
											<div id="div-last_name"></div>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-sm-6">
										<div class="form-group">
											<label>Email Address <span class="red-mark">*</span></label>
											<input name="email" id="email" type="email" required class="form-control" value="{{$admin->email ?? old('email') }}" >
											<div id="div-email"></div>
										</div>
									</div>
									<div class="col-sm-6">
										<label>Password @if($admin->id == '') <span class="red-mark">*</span> @endif</label>
										<div class="mb-3">
											<div class="d-flex">
												<input type="password" name="password" id="password" class="form-control"  placeholder="Password">
												<div class="input-group-append">
													<button class="btn btn-outline-secondary" type="button" id="togglePassword">
														<i class="fa fa-eye-slash" aria-hidden="true"></i>
													</button>
												</div>
											</div>
											<div id="div-password"></div>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-sm-6">
										<label>Confirm Password @if($admin->id == '') <span class="red-mark">*</span> @endif</label>
										<div class="mb-3">
											<div class="d-flex">
												<input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Confirm Password">
												<div class="input-group-append">
													<button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
														<i class="fa fa-eye-slash" aria-hidden="true"></i>
													</button>
												</div>
											</div>
											<div id="div-password_confirmation"></div>
										</div>
									</div>

									<div class="col-sm-6">
										<div class="form-group">
											<label>Type <span class="red-mark">*</span></label>
											<div class="form-group select_custom">
												<select  required id="type" name="type" class="form-control selectbox-modal clientiddata" >
													<option selected disabled>Select a type</option>
													<option value="super" @if($admin->type == 'super') Selected @endif>Super Admin</option>
													<option value="sub" @if($admin->type == 'sub') Selected @endif>Staff</option>
												</select>
											</div>
											<div id="div-type"></div>
										</div>
									</div>
								</div>
								<div class="roleSection @if($admin->role_id == 0) d-none @endif">
									<div class="row">
										<div class="col-sm-6">
											<label>Role<span class="req_span">*</span></label>
											<div class="form-group select_custom">
												<select data-parsley-errors-container="#div-role_id" id="role_id" name="role_id" class="form-control selectbox-modal clientiddata" @if($admin->role_id == 0) @else required @endif >
													<option selected disabled>Select a role</option>
													@foreach($roles as $key => $value)
													<option value="{{$value->id}}" @if($value->id == $admin->role_id) Selected @endif>{{$value->name}}</option>
													@endforeach
												</select>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="card-footer">
								<button type="submit" class="btn btn-info">Submit</button>
								<button type="button" onclick="document.getElementById('formStore').reset();" class="btn btn-default resetForm">Reset</a>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</section>
</div>
@endsection
<style>
    .input-group-append > button {
        cursor: pointer;
    }

    .input-group-append > button:focus {
        outline: none;
    }
</style>
@section('import-css')

@endsection

@section('import-scripts')

<script>
	$(document).ready(function() {
		$.validator.addMethod("alphabeticSpace", function(value, element) {
			return this.optional(element) || /^[a-zA-Z ]+$/.test(value);
		}, "Should contain only alphabetic characters and spaces");

		$.validator.addMethod("customEmailValidation", function(value, element) {
			// Regular expression for valid email format
            var emailRegex = /^[a-zA-Z0-9._%-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
			// Custom regex to disallow certain email formats
            var disallowedEmailRegex = /(.)\1{2,}|(\+.*?\+)|(\.{2,})|(-{2,})|(@-|-\@)|(@\.)|(\.@)/;

			// Check if the email format is valid and not matching disallowed patterns
			if (!emailRegex.test(value) || disallowedEmailRegex.test(value)) {
              return false;
            }

			// Extract the domain part of the email address
			var domain = value.split('@')[1];
			// Check if the domain has consecutive periods or repeating TLDs
			if (domain.includes('..') || domain.match(/\.\w+\.\w+$/)) {
				return false; // Invalid domain
			}

			// Check if the domain is valid based on specific TLDs
			var validTLDs = ['com', 'org', 'net', 'co', 'in', 'uk', 'info','ai']; // Valid TLDs including specific ones
			var domainParts = domain.split('.').reverse(); // Split domain into parts and reverse to check TLD first
			if (!validTLDs.includes(domainParts[0]) || (domainParts[0] == 'co' && !validTLDs.includes(domainParts[1]))) {
				return false; // Invalid TLD
			}

			return true; // Valid email format and domain
		}, "Enter a valid email address");

		var url = "{{route('admin.list')}}";

		$('#formStore').validate({
			errorClass: 'text-danger-custom',
			ignore: [],
			rules: {
				'first_name': {
					required: true,
					alphabeticSpace: true,
					maxlength: 30
				},
				'last_name': {
					required: true,
					alphabeticSpace: true,
					maxlength: 30

				},
				'email': {
					required: true,
					customEmailValidation: true,
					email: true,
					remote: {
						url: '{{ route("admin.checkEmailExists") }}',
						type: "post",
						data: {
							email: function() {
								return $('#formStore :input[name="email"]').val();
							},
							id: function() {
								return $('#formStore :input[name="id"]').val();
							}
						}
					}
				},
				'password': {
						required: true,
						minlength: 8,
						maxlength: 16

				},
				password_confirmation: {
						required: true,
						minlength: 8,
						maxlength: 16,
						equalTo: '#password',
				},
			},
			messages: {
				'first_name': {
					required: "The first name is required",
					pattern: "Should contain only alphabetic characters and spaces",
					maxlength: "Max length should be 30 characters"
				},
				'last_name': {
					required: "The last name is required",
					pattern: "Should contain only alphabetic characters and spaces",
					maxlength: "Max length should be 30 characters"

				},
				'email': {
					required: "The email is required",
					email: "Enter valid email",
					remote: "Account with same email already exists"
				},
				'password': {
					@if($admin->id == '')
						required: "The password is required",
						minlength: "Please Enter Password atleast 8 characters long",
					@endif
				},
				password_confirmation:{
					@if($admin->id == '')
						required: "The confirm password is required",
						minlength: "Please Enter confirm Password atleast 8 characters long",
						equalTo: 'The password must match',
					@endif
				}
			},
			errorPlacement: function(error, element) {
				if( element.attr("name") == "password" ) {
					error.insertAfter( "#div-password" );
				} else if( element.attr("name") == "password_confirmation" ) {
					error.insertAfter( "#div-password_confirmation" );
				} else{
					error.insertAfter(element);
				}

			},
			errorElement: 'span'
		});

		var formSubmitted = false; // Flag to track form submission

		$("#formStore").on("submit", function(e) {
			e.preventDefault();

			if (formSubmitted) return; // Prevent multiple form submissions

			var form = $(this);
			var dataString = form.serialize();
			var url = form.attr('action');

			if (form.valid()) {
				formSubmitted = true; // Set flag to true to prevent multiple submissions

				$.ajax({
				type: "POST",
				url: url,
				data: new FormData(form[0]),
				dataType: "JSON",
				contentType: false,
				processData: false,
				beforeSend: function() {
					HoldOn.open({ theme: "sk-cube-grid" });
				},
				success: function(result) {
					if (result.success) {
					toastr.success(result.msg, "Success", { positionClass: 'toast-bottom-right' });

					setTimeout(function() {
						window.location.href = result.redirect_url;
					}, 2000);
					} else {
					toastr.error(result.msg, "Error", { positionClass: 'toast-bottom-right' });
					}
				},
				error: function() {
					toastr.error("Something went wrong", "Error", { positionClass: 'toast-bottom-right' });
				},
				complete: function() {
					formSubmitted = false; // Reset flag after AJAX request completes
					HoldOn.close();
				}
				});
			}
		});

		var resetButtons = document.getElementsByClassName('resetForm');

		// Loop through each reset buttons to bind the click event
		for(var i=0; i<resetButtons.length; i++){
			resetButtons[i].addEventListener('click', resetForm);
		}

		$(document).on('change', '#type', function(e){
			e.preventDefault();

			var value = $(this).val();
			var role_field = $('#role_id');
			role_field.val('');

			if(value == 'super') {
				$(".roleSection").addClass('d-none');
				role_field.prop('required', false);
			} else {
				$(".roleSection").removeClass('d-none');
				role_field.prop('required', true);
			}
		});

		document.getElementById('togglePassword').addEventListener('click', function () {
			var passwordInput = document.getElementById('password');
			var icon = this.querySelector('i');

			if (passwordInput.type === 'password') {
				passwordInput.type = 'text';
				icon.classList.remove('fa-eye-slash');
				icon.classList.add('fa-eye');
			} else {
				passwordInput.type = 'password';
				icon.classList.remove('fa-eye');
				icon.classList.add('fa-eye-slash');
			}
		});

		document.getElementById('toggleConfirmPassword').addEventListener('click', function () {
			var passwordInput = document.getElementById('password_confirmation');
			var icon = this.querySelector('i');

			if (passwordInput.type === 'password') {
				passwordInput.type = 'text';
				icon.classList.remove('fa-eye-slash');
				icon.classList.add('fa-eye');
			} else {
				passwordInput.type = 'password';
				icon.classList.remove('fa-eye');
				icon.classList.add('fa-eye-slash');
			}
		});
	});
</script>
@endsection
