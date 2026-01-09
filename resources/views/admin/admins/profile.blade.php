@extends('admin.layouts.app')
@section('page_tab_title' ,"Admin Profile")

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
					<h1 class="m-0">Admin Profile</h1>
				</div>
				<div class="col-sm-6">
					<ol class="breadcrumb float-sm-right">
						<li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Home</a></li>
						<li class="breadcrumb-item active">Admin Profile</li>
					</ol>
				</div>
			</div>
		</div>
	</div>

	<!-- Main content -->
	<section class="content">
		<div class="container-fluid">
			<div class="row">
				<!-- <div class="col-md-3">
					<div class="card card-primary card-outline">
						<div class="card-body box-profile">
							<div class="text-center">
								<img class="profile-user-img img-fluid img-circle" id="uploadResultSection" src="{{$data->getAdminProfileAttribute()}}" alt="User profile picture">
							</div>

							<h3 class="profile-username text-center">{{ucwords($data->first_name.' '.$data->last_name)}}</h3>
							<h5 class="profile-rolename text-center">{{ucwords($role_name)}}</h5>

							<a href="{{route('admin.logout')}}" class="btn btn-primary btn-block"><b>Logout</b></a>
						</div>
					</div>
				</div> -->

				<div class="col-md-12">
					<div class="card">
						<div class="card-header p-2">
							<ul class="nav nav-pills">
								<li class="nav-item"><a class="nav-link active" href="#profile" data-toggle="tab">Edit Profile</a></li>
								<li class="nav-item"><a class="nav-link" href="#settings" data-toggle="tab">Change Password</a></li>
							</ul>
						</div>
						<div class="card-body">
							<div class="tab-content">
								<div class="tab-pane active" id="profile">
									<form class="form-horizontal" method="post" name="formEdit" id="formEdit" enctype="multipart/form-data" action="{{route('admin.updateProfile')}}">
										@csrf
										<input type="hidden" name="id" id="id" value="{{$data->id}}">

										<div class="form-group row">
											<label for="first_name" class="col-sm-2 col-form-label">First Name <span class="red-mark">*</span></label>
											<div class="col-sm-10">
												<input type="text" class="form-control" id="first_name" name="first_name" placeholder="First name" value="{{$data->first_name}}">
												<div id="div-first_name"></div>
											</div>
										</div>

										<div class="form-group row">
											<label for="last_name" class="col-sm-2 col-form-label">Last Name <span class="red-mark">*</span></label>
											<div class="col-sm-10">
												<input type="text" class="form-control" id="last_name" name="last_name" placeholder="Last name" value="{{$data->last_name}}">
												<div id="div-last_name"></div>
											</div>
										</div>

										<div class="form-group row">
											<label for="inputEmail" class="col-sm-2 col-form-label">Email <span class="red-mark">*</span></label>
											<div class="col-sm-10">
												<input type="email" class="form-control" id="email" name="email" placeholder="Email" value="{{$data->email}}">
											</div>
										</div>

										<div class="form-group row">
											<label for="inputName2" class="col-sm-2 col-form-label">Phone</label>
											<div class="col-sm-10">
												<input type="tel" class="form-control" id="cell_phone" name="cell_phone" pattern="[0-9]{10}" maxlength="10" placeholder="Phone" oninput="this.value = this.value.replace(/[^0-9]/g, '');" value="{{$data->cell_phone ?? old('cell_phone') }}">
											</div>
										</div>

										<!-- <div class="form-group row">
											<label for="inputName2" class="col-sm-2 col-form-label">Profile</label>
											<div class="col-sm-10">
												<input onchange="previewImage(event)" type="file" class="form-control" name="profile_picture" id="profile_picture">
											</div>
										</div> -->

										<div class="form-group row">
											<div class="offset-sm-2 col-sm-10">
												<button type="submit" class="btn btn-info">Submit</button>
											</div>
										</div>
									</form>
								</div>

								<div class="tab-pane" id="settings">
									<form class="form-horizontal" name="formChangePass" method="post" id="formChangePass" enctype="multipart/form-data" action="{{route('admin.changePassword')}}">
										@csrf
										<input type="hidden" name="admin_id" id="admin_id" value="{{$data->id}}">
										<div class="input-group mb-3">
											<label for="first_name" class="col-xl-2 col-md-3 col-sm-4 col-form-label pr-0">Old Password <span class="red-mark">*</span></label>
											<div class="col-xl-10 col-md-9 col-sm-8">
												<div class="d-flex align-items-center">
													<input type="text" class="form-control" id="old_pass" name="old_pass" placeholder="Old password">
													<div class="input-group-append">
														<button class="btn btn-outline-secondary" type="button" id="toggleOldPassword">
															<i class="fa fa-eye-slash" aria-hidden="true"></i>
														</button>
													</div>
												</div>
												<div id="div-old_pass"></div>
											</div>
										</div>
										<div class="input-group mb-3">
											<label for="last_name" class="col-xl-2 col-md-3 col-sm-4 col-form-label pr-0">New Password <span class="red-mark">*</span></label>
											<div class="col-xl-10 col-md-9 col-sm-8">
												<div class="d-flex align-items-center">
													<input type="password" class="form-control" id="password" name="password" placeholder="New Password">
													<div class="input-group-append">
														<button class="btn btn-outline-secondary" type="button" id="togglePassword">
															<i class="fa fa-eye-slash" aria-hidden="true"></i>
														</button>
													</div>
												</div>
												<div id="div-password"></div>
											</div>
										</div>
										<div class="input-group mb-3">
											<label for="last_name" class="col-xl-2 col-md-3 col-sm-4 col-form-label pr-0">Confirm Password <span class="red-mark">*</span></label>
											<div class="col-xl-10 col-md-9 col-sm-8">
												<div class="d-flex align-items-center">
													<input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Confirm Password">
													<div class="input-group-append">
														<button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
															<i class="fa fa-eye-slash" aria-hidden="true"></i>
														</button>
													</div>
												</div>
												<div id="div-password_confirm"></div>
											</div>
										</div>
										<div class="form-group row">
											<div class="offset-xl-2 offset-md-3 offset-sm-4 col-md-1 ">
												<button type="submit" class="btn btn-info">Submit</button>
											</div>
										</div>
									</form>
								</div>
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
	var previewImage = function(event) {
		var output = document.getElementById('uploadResultSection');
		output.src = URL.createObjectURL(event.target.files[0]);
		output.onload = function() {
			URL.revokeObjectURL(output.src)
		}
	};

	$(document).ready(function() {
		$.validator.addMethod("customEmailValidation", function(value, element) {
			// Regular expression for valid email format
			var emailRegex = /^[a-zA-Z0-9._%-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
			// Custom regex to disallow certain email formats
			var disallowedEmailRegex = /(\.)\1{2,}|(\+).*?(\+)|(\.)[\.-]|[\.-](\.)|@[\.-]|[\.-]@/;

			// Check if the email format is valid and not matching disallowed patterns
			if (!emailRegex.test(value) || disallowedEmailRegex.test(value)) {
				return false; // Invalid email format or contains disallowed patterns
			}

			// Extract the domain part of the email address
			var domain = value.split('@')[1];
			// Check if the domain has consecutive periods or repeating TLDs
			if (domain.includes('..') || domain.match(/\.\w+\.\w+$/)) {
				return false; // Invalid domain
			}

			// Check if the domain is valid based on specific TLDs
			var validTLDs = ['com', 'org', 'net', 'co', 'in', 'uk', 'info']; // Valid TLDs including specific ones
			var domainParts = domain.split('.').reverse(); // Split domain into parts and reverse to check TLD first
			if (!validTLDs.includes(domainParts[0]) || (domainParts[0] == 'co' && !validTLDs.includes(domainParts[1]))) {
				return false; // Invalid TLD
			}

			return true; // Valid email format and domain
		}, "Enter a valid email address");
		$('#formEdit').validate({
			errorClass: 'text-danger-custom',
			ignore: [],
			rules: {
				'first_name': {
					required: true,
					maxlength: 50
				},
				'last_name': {
					required: true,
					maxlength: 50
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
				}
			},
			messages: {
				'first_name': {
					required: "This field is required",
					maxlength: "Max length should be 50 characters"
				},
				'last_name': {
					required: "This field is required",
					maxlength: "Max length should be 50 characters"
				},
				'audio_path': {
					required: "This field is required",
					email: "Valid email is required",
					remote: "The email is already used"
				}
			},
			errorPlacement: function(error, element) {
				if (element.attr("name") == "round_type") {
					error.insertAfter("#div-round_type");
				} else if (element.attr("name") == "status") {
					error.insertAfter("#div-status");
				} else if (element.attr("name") == "audio_path") {
					error.insertAfter("#div-audio_path");
				} else if (element.attr("name") == "map_type") {
					error.insertAfter("#div-map_type");
				} else if (element.attr("name") == "img_path") {
					error.insertAfter("#div-img_path");
				} else if (element.attr("name") == "vid_path") {
					error.insertAfter("#div-vid_path");
				} else {
					error.insertAfter(element);
				}
			},
			errorElement: 'span'
		});

		$(document).on("submit", "#formEdit", function(e) {
			e.preventDefault();

			var dataString = $(this).serialize();
			var url = $(this).attr('action');

			if ($(this).valid()) {
				$.ajax({
					type: "POST",
					url: url,
					data: new FormData(this),
					dataType: "JSON",
					contentType: false,
					processData: false,
					beforeSend: function(msg) {
						HoldOn.open({
							theme: "sk-cube-grid"
						});
					},
					success: function(result) {
						HoldOn.close();
						if (result.success == true) {
							toastr.success(result.msg, "Success", {
								positionClass: 'toast-bottom-right'
							});
						} else {
							toastr.error(result.msg, "Error", {
								positionClass: 'toast-bottom-right'
							});
						}
					},
					error: function(result) {
						HoldOn.close();
						toastr.error("Something went wrong", "Error", {
							positionClass: 'toast-bottom-right'
						});
					}
				});
			}
		});

		$('#formChangePass').validate({
			errorClass: 'text-danger-custom',
			ignore: [],
			rules: {
				'old_pass': {
					required: true,
					minlength: 8,
					remote: {
						url: '{{ route("admin.checkPassword") }}',
						type: "post",
						data: {
							old_pass: function() {
								return $('#old_pass').val();
							},
							id: function() {
								return $('#admin_id').val();
							}
						}
					}
				},
				'password': {
					required: true,
					minlength: 8
				},
				'password_confirm': {
					required: true,
					equalTo: "#password",
					minlength: 8
				}
			},
			messages: {
				'old_pass': {
					required: "This field is required",
					minlength: "Your password must be at least 8 characters long",
					remote: "Incorrect password"
				},
				'password': {
					required: "This field is required",
					minlength: "Your password must be at least 8 characters long"
				},
				'password_confirm': {
					required: "This field is required",
					equalTo: "The password field not matching",
					minlength: "Your password must be at least 8 characters long"
				}
			},
			errorPlacement: function(error, element) {
				if (element.attr("name") == "old_pass") {
					error.insertAfter("#div-old_pass");
				} else if (element.attr("name") == "password") {
					error.insertAfter("#div-password");
				} else if (element.attr("name") == "password_confirm") {
					error.insertAfter("#div-password_confirm");
				} else {
					error.insertAfter(element);
				}
			},
			errorElement: 'span'
		});

		$(document).on("submit", "#formChangePass", function(e) {
			e.preventDefault();

			var dataString = $(this).serialize();
			var url = $(this).attr('action');

			if ($(this).valid()) {
				$.ajax({
					type: "POST",
					url: url,
					data: new FormData(this),
					dataType: "JSON",
					contentType: false,
					processData: false,
					beforeSend: function(msg) {
						HoldOn.open({
							theme: "sk-cube-grid"
						});
					},
					success: function(result) {
						HoldOn.close();
						if (result.success == true) {
							toastr.success(result.msg, "Success", {
								positionClass: 'toast-bottom-right'
							});

							window.setTimeout(function() {
								window.location.href = result.redirect_url;
							}, 2000);
						} else {
							toastr.error(result.msg, "Error", {
								positionClass: 'toast-bottom-right'
							});
						}
					},
					error: function(result) {
						HoldOn.close();
						toastr.error("Something went wrong", "Error", {
							positionClass: 'toast-bottom-right'
						});
					}
				});
			}
		});

		document.getElementById('togglePassword').addEventListener('click', function() {
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

		document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
			var passwordInput = document.getElementById('password_confirm');
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

		document.getElementById('toggleOldPassword').addEventListener('click', function() {
			var passwordInput = document.getElementById('old_pass');
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