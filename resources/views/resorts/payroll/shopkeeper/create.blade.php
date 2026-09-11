@extends('resorts.layouts.app')
@section('page_tab_title' , $page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<style>
    #shopkeeper-create-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #shopkeeper-create-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="shopkeeper-create-hero">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Payroll</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>

        <div class="sk-wrap">

            <!-- Add shopkeeper -->
            <div class="sk-card">
                <div class="sk-ct"><h2>Add shopkeeper</h2></div>

                <form id="shopkeeperForm">
    @csrf
                    <div class="sk-grid">
                        <div class="sk-f">
                            <label for="name">Full name <span class="sk-req">*</span></label>
                            <input type="text" class="sk-inp name" id="name" placeholder="e.g. Tuck Shop" name="name">
                        </div>
                        <div class="sk-f">
                            <label for="email">Email <span class="sk-req">*</span></label>
                            <input type="email" class="sk-inp email" id="email" placeholder="name@example.com" name="email">
                        </div>
                        <div class="sk-f">
                            <label for="contact_no">Contact no <span class="sk-req">*</span></label>
                            <input type="tel" class="sk-inp" id="contact_no" name="contact_no" pattern="[0-9]{10}" maxlength="10" placeholder="Contact number" oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                        </div>
                    </div>
                    <div class="sk-cfoot">
                        <button type="submit" class="checkprogress sk-submit">Submit</button>
                    </div>
                </form>
            </div>

            <!-- Recent shopkeepers -->
            <div class="sk-card">
                <div class="sk-ct"><h2>Recent shopkeepers</h2><span class="sk-spacer"></span>
                    <a href="{{ route('shopkeepers.index') }}" class="sk-viewall">View all <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </div>
                <table class="sk-tbl">
                    <thead><tr><th class="sk-idx">#</th><th>Name</th><th>Email</th><th>Contact</th><th class="sk-act">Action</th></tr></thead>
                    <tbody>
                        @forelse($recentShopkeepers as $index => $shopkeeper)
                            @php
                                $skParts = preg_split('/\s+/', trim($shopkeeper->name));
                                $skInitials = strtoupper(($skParts[0][0] ?? '') . (isset($skParts[1]) ? $skParts[1][0] : '')) ?: '?';
                                $skPhoto = !empty($shopkeeper->profile_photo) ? asset(config('settings.ShopkeeperProfile_folder') . '/' . $shopkeeper->profile_photo) : null;
                            @endphp
                            <tr>
                                <td class="sk-idx">{{ $index + 1 }}</td>
                                <td>
                                    <div class="sk-nm">
                                        <span class="sk-av">
                                            <span class="sk-av-fallback">{{ $skInitials }}</span>
                                            @if($skPhoto)<img src="{{ $skPhoto }}" alt="{{ $shopkeeper->name }}" onerror="this.remove()">@endif
                                        </span>
                                        <span class="sk-t">{{ $shopkeeper->name }}</span>
                                    </div>
                                </td>
                                <td class="sk-email">{{ $shopkeeper->email }}</td>
                                <td class="sk-num">{{ $shopkeeper->contact_no }}</td>
                                <td class="sk-act">
                                    <a href="{{ route('resort.shopkeeper.payments', $shopkeeper->id) }}" class="sk-ico view" title="View"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="sk-empty">No shopkeepers added yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>
@endsection

@section('import-css')
@include('resorts.payroll._payroll_buttons_v2_styles')
@include('resorts.payroll.shopkeeper._shopkeeper_styles')
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