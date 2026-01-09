@extends('admin.layouts.app')

@section('page_tab_title' ,"Settings")

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
					<h1 class="m-0">Settings</h1>
				</div>
				<div class="col-sm-6">
					<ol class="breadcrumb float-sm-right">
						<li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Home</a></li>
						<li class="breadcrumb-item active">Settings</li>
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
            
            <form method="POST" name="formStore" id="formStore" class="form-horizontal" enctype="multipart/form-data" action="{{ route('settings.save') }}">
            	@csrf
            	<div class="card-body">
					<div class="row">
						<div class="col-sm-6">
							<div class="form-group">
								<label>Site Title</label>
								<input type="text" name="site_title" id="site_title" class="form-control" placeholder="Site Title" value="{{$data->site_title ?? old('site_title') }}">
								<div id="div-site_title"></div>
							</div>
						</div>
						
						<div class="col-sm-6">
							<div class="form-group">
            					<label>Contact Number</label>
								<input type="tel" class="form-control" id="contact_number" name="contact_number" pattern="[0-9]{10}" maxlength="10" placeholder="Contact number" oninput="this.value = this.value.replace(/[^0-9]/g, '');" value="{{$data->contact_number ?? old('contact_number') }}">
            					<div id="div-contact_number"></div>
            				</div>
						</div>
					</div>

            		<div class="row">
            			<div class="col-sm-6">
            				<div class="form-group">
            					<label>Email address</label>
            					<input type="text" name="email_address" id="email_address" class="form-control" placeholder="Email address" value="{{$data->email_address ?? old('email_address') }}">
            					<div id="div-email_address"></div>
            				</div>
            			</div>
            			<div class="col-sm-6">
            				<div class="form-group">
            					<label>Admin email</label>
            					<input type="text" name="admin_email" id="admin_email" class="form-control" placeholder="Admin email" value="{{$data->admin_email ?? old('admin_email') }}">
            					<div id="div-admin_email"></div>
            				</div>
            			</div>
            		</div>

            		<div class="row">
						<div class="col-sm-5">
            				<div class="form-group">
            					<label for="site_logo">Site Admin Logo</label>
            					<div class="input-group">
            						<div class="custom-file">
            							<input onchange="imagePreview(event, 'siteLogo')" type="file" class="custom-file-input" id="site_logo" name="site_logo" accept="image/*">
            							<label class="custom-file-label" for="site_logo">Choose file</label>
            						</div>
            						<div class="input-group-append">
            							<span class="input-group-text">Upload</span>
            						</div>
            					</div>
            				</div>
            			</div>
            			<div class="col-sm-1">
            				@if( $data->site_logo != '' )
            					<img id="siteLogo" class="logo-img" width="100px" src="{{url(config('settings.site_logo_folder')).'/'.$data->site_logo}}">
            				@else
            					<img id="siteLogo" class="logo-img" width="100px" src="{{url(config('settings.default_picture'))}}" />
            				@endif
            			</div>
            			<div class="col-sm-5">
            				<div class="form-group">
            					<label for="site_favicon">Site Favicon</label>
            					<div class="input-group">
            						<div class="custom-file">
            							<input onchange="imagePreview(event, 'siteFavicon')" type="file" class="custom-file-input" id="site_favicon" name="site_favicon" accept="image/*">
            							<label class="custom-file-label" for="site_favicon">Choose file</label>
            						</div>
            						<div class="input-group-append">
            							<span class="input-group-text">Upload</span>
            						</div>
            					</div>
            				</div>
            			</div>
            			<div class="col-sm-1">
            				@if( $data->site_favicon != '' )
            					<img id="siteFavicon" class="logo-img" width="auto" src="{{url(config('settings.site_favicon_folder')).'/'.$data->site_favicon}}">
            				@else
            					<img id="siteFavicon" class="logo-img" width="100px" src="{{url(config('settings.default_picture'))}}" />
            				@endif
            			</div>
            		</div>

            		<div class="row">
            			<div class="col-sm-5">
            				<div class="form-group">
            					<label for="header_logo">Site header logo</label>
            					<div class="input-group">
            						<div class="custom-file">
            							<input onchange="imagePreview(event, 'siteHeader')" type="file" class="custom-file-input" id="header_logo" name="header_logo" accept="image/*">
            							<label class="custom-file-label" for="header_logo">Choose file</label>
            						</div>
            						<div class="input-group-append">
            							<span class="input-group-text">Upload</span>
            						</div>
            					</div>
            				</div>
            			</div>
            			<div class="col-sm-1">
            				@if( $data->header_logo != '' )
            					<img id="siteHeader" class="logo-img" width="100px" src="{{url(config('settings.site_logo_folder')).'/'.$data->header_logo}}">
            				@else
            					<img id="siteHeader" class="logo-img" width="100px" src="{{url(config('settings.default_picture'))}}" />
            				@endif
            			</div>
						<div class="col-sm-5">
            				<div class="form-group">
            					<label for="footer_logo">Site footer logo</label>
            					<div class="input-group">
            						<div class="custom-file">
            							<input onchange="imagePreview(event, 'siteFooter')" type="file" class="custom-file-input" id="footer_logo" name="footer_logo" accept="image/*">
            							<label class="custom-file-label" for="footer_logo">Choose file</label>
            						</div>
            						<div class="input-group-append">
            							<span class="input-group-text">Upload</span>
            						</div>
            					</div>
            				</div>
            			</div>
            			<div class="col-sm-1">
            				@if( $data->footer_logo != '' )
            					<img id="siteFooter" class="logo-img" width="100px" src="{{url(config('settings.site_logo_folder'))."/".$data->footer_logo}}">
            				@else
            					<img id="siteFooter" class="logo-img" width="100px" src="{{url(config('settings.default_picture'))}}" />
            				@endif
            			</div>
            		</div>
      		
            		<div class="row">
            			<div class="col-sm-6">
            				<div class="form-group">
            					<label>Date Format</label>
            					<select class="form-control" id="date_format" name="date_format">
									<option selected disabled>Select date format</option>
									@foreach($dateFormats as $key => $value)
										<option value="{{$value}}" @if($value == $data->date_format) Selected @endif>{{$value}}</option>
									@endforeach
								</select>
							</div>
						</div>
						<div class="col-sm-6">
            				<div class="form-group">
            					<label>Time Format</label>
            					<select class="form-control" id="time_format" name="time_format">
									<option selected disabled>Select date format</option>
									@foreach($timeFormats as $key => $value)
										<option value="{{$value}}" @if($value == $data->time_format) Selected @endif>{{$value}}</option>
									@endforeach
								</select>
							</div>
						</div>
            		</div>

					<div class="row">
            			<div class="col-sm-6">
						<div class="form-group">
								<label>Address</label>
								<textarea name="address_1" id="address_1" class="form-control">{{$data->address_1 ?? old('address_1') }}</textarea>
								<div id="div-address_1"></div>
							</div>
            			</div>
						<div class="col-sm-6">
            				<div class="form-group">
            					<label>Login Page Content</label>
            					<textarea name="contents" id="contents" class="form-control">{{$data->contents ?? old('contents') }}</textarea>
            					<div id="div-contents"></div>
            				</div>
            			</div>
						<div class="col-sm-6">
            				<div class="form-group">
            					<label>Support email</label>
            					<input type="text" name="support_email" id="support_email" class="form-control" placeholder="Support email" value="{{$data->support_email ?? old('support_email') }}">
            					<div id="div-support_email"></div>
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

@section('import-css')
@endsection

@section('import-scripts')
<script src="{{ URL::asset('admin_assets/plugins/select2/js/select2.full.min.js') }}"></script>
<script>
	var imagePreview = function(event, id) {
		var output = document.getElementById(id);
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
		var resetButtons = document.getElementsByClassName('resetForm');

		// Loop through each reset buttons to bind the click event
		for(var i=0; i<resetButtons.length; i++){
			resetButtons[i].addEventListener('click', resetForm);
		}
		$('#formStore').validate({
			errorClass: 'text-danger-custom',
			ignore: [],
			rules: {
				'site_title': {
					required: true,
					maxlength: 50,
				},
				'email_address': {
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
				'admin_email': {
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
			},
			messages: {
				'email_address': {
					email: "The email address is not valid"
				},
				'admin_email': {
					email: "The admin email is not valid"
				},
				'site_title': {
					required: "Please Enter Site title",
					maxlength: "Site title must be less than 50 characters"
				},
				
			},
			errorPlacement: function(error, element) {
				if( element.attr("name") == "max_level" ) {
					error.insertAfter( "#div-max_level" );
				} else if( element.attr("name") == "max_zone" ) {
					error.insertAfter( "#div-max_zone" );
				} else if( element.attr("name") == "email_address" ) {
					error.insertAfter( "#div-email_address" );
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
							window.location.reload();
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