@extends('resorts.layouts.app')
@section('page_tab_title' , $page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Payroll</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="card mb-30">
                        <div class="card-title">
                            <h3>Add Shopkeeper</h3>
                        </div>

                        <form id="shopkeeperForm">
    @csrf
                            <div class="earnings-main">
                                <div class="earnings-block mb-md-4 mb-3">
                                    <div class="row align-items-end g-md-4 g-2 earnings-row">
                                        <div class="col-sm-6">
                                            <label for="name" class="form-label">FULL NAME <span class="red-mark"> * </span></label>
                                            <input type="text" class="form-control name" id="name"
                                                placeholder="Full Name" name="name">
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="email" class="form-label">EMAIL <span class="red-mark"> * </span></label>
                                            <input type="email" class="form-control email" id="email"
                                                placeholder="Email" name="email">
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="contact_no" class="form-label">Contact No <span class="red-mark"> * </span></label>
                                            <input type="tel" class="form-control" id="contact_no" name="contact_no" pattern="[0-9]{10}" maxlength="10" placeholder="Contact No" oninput="this.value = this.value.replace(/[^0-9]/g, '');" >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" class="checkprogress btn payroll-btn-primary btn-sm">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card mb-30">
                        <div class="card-title">
                            <div class="row g-1">
                                <div class="col">
                                    <h3>Recent Shopkeepers</h3>
                                </div>
                                <div class="col-auto">
                                    <a href="{{route('shopkeepers.index')}}" class="a-link">View All</a>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Contact</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentShopkeepers as $index => $shopkeeper)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $shopkeeper->name }}</td>
                                            <td>{{ $shopkeeper->email }}</td>
                                            <td>{{ $shopkeeper->contact_no }}</td>
                                            <td>
                                                <a href="{{ route('resort.shopkeeper.payments', $shopkeeper->id) }}" class="btn btn-sm payroll-btn-secondary">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3">No shopkeepers added yet</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@include('resorts.payroll._payroll_buttons_v2_styles')
@endsection

@section('import-scripts')
<script type="text/javascript">
    // new DataTable('#example');
    $(document).ready(function () {
        $.validator.addMethod("customEmailValidation", function(value, element) {
			// Regular expression for valid email format
			var emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
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
        $.validator.addMethod("noSpecialChars", function(value, element) {
            return this.optional(element) || /^[a-zA-Z\s]*$/.test(value);
        }, "Name should only contain letters and spaces.");
        $('#shopkeeperForm').validate({
            rules: {
                name:{
                    required: true,
                    maxlength: 50,
                    minlength: 1,
                    noSpecialChars: true
                },
                email: {
                    required: true,
                    email: true,
                    customEmailValidation: true
                },
                contact_no: {
                    required: true,
                    maxlength: 10,
                    minlength: 10,
                }
            },
            messages: {
                name :{
                    required: "Please enter full name.",
                    maxlength: "Name cannot be longer than 50 characters",
                    minlength: "Name must be at least 1 character long"
                },
                email: {
                    required: "Please enter email address.",
                    email: "Please enter a valid email address.",
                },
                contact_no: {
                    required: "Please enter contact no.",
                    email: "Please enter a valid contact no.",
                    maxlength: "Contact no must be 10 digits",
                    maxlength: "Contact no must be 10 digits",
                }
            },
            submitHandler: function(form) {
                var emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
                var email = $(form).find('[name="email"]').val();

                if (!emailRegex.test(email)) {
                    toastr.error("Invalid email format", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                    return false;
                }
                var formData = new FormData(form); // Use FormData to include file
                $(".checkprogress").prop('disabled', true);
                $.ajax({
                    url: "{{ route('shopkeepers.save') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.msg, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            window.location.href = response.redirect_url; 
                        } else {
                            toastr.error(response.msg, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                        $(".checkprogress").prop('disabled', false);
                    },
                    error: function(response) 
                    {
                        var errors = response.responseJSON;
                        console.log(errors);
                        var errs = '';
                        if (errors && errors.errors) {
                            $.each(errors.errors, function(key, error) {
                                errs += error + '<br>';
                            });
                            toastr.error(errs, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        } else {
                            toastr.error("An unexpected error occurred", "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                                                $(".checkprogress").prop('disabled', false);

                    }
                });
            }
        });
    });
</script>
@endsection