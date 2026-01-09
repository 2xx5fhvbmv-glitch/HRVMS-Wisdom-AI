<!doctype html>
<html lang=en data-bs-theme=auto>

<head>
    <meta charset=utf-8>
    <meta name=viewport content="width=device-width,initial-scale=1">
    <meta name="description" content="">
    <meta name=keywords content="">
    <link href="{{ URL::asset('resorts_assets/css/bootstrap.min.css')}}" rel=stylesheet>
    <link href="{{ URL::asset('resorts_assets/css/select2.min.css')}}" rel=stylesheet>
    <link href="{{ URL::asset('resorts_assets/css/slick-theme.css')}}" rel=stylesheet>
    <link href="{{ URL::asset('resorts_assets/css/slick.css')}}" rel=stylesheet>
    <link href="{{ URL::asset('resorts_assets/css/all.min.css')}}" rel=stylesheet>
    <link href="{{ URL::asset('resorts_assets/css/dataTables.min.css')}}" rel=stylesheet>

    <link href="{{ URL::asset('resorts_assets/css/default.css')}}" rel=stylesheet>
    <link href="{{ URL::asset('resorts_assets/css/media.css')}}" rel=stylesheet>
    <link rel="stylesheet" href="{{ URL::asset('admin_assets/plugins/toastr/toastr.min.css') }}">


    <link rel="apple-touch-icon" sizes="180x180" href="{{ URL::asset('resorts_assets/images//apple-touch-icon.png')}}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ URL::asset('resorts_assets/images//favicon-32x32.png')}}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ URL::asset('resorts_assets/images//favicon-16x16.png')}}">

    <title>HRVMS | Reset Password</title>
</head>

<body class="login-body">
    <div class="row g-0 h-100">
        <div class=" col-md-6">
            <div class="login-left">
                <div class="img-obj cover"><img src="{{ URL::asset('resorts_assets/images/login.jpg')}}" alt="image"></div>
                <div class="row g-sm-3 g-1 justify-content-between align-items-center ">
                    <div class="col-auto">
                        <a href="index.html" class="brand-logo"><img src="{{ URL::asset('resorts_assets/images/wisdom-ai.png')}}"></a>
                    </div>

                </div>
                <div class="head">
                    <h1>Start Your Journey with us</h1>
                    <h2>Lorem ipsum dolor sit amet consect etur adipisicing elit Lorem.</h2>
                </div>
                <div>
                    <div class="login-slider slider">
                        <div>
                            <p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Cupiditate officiis eaque ab
                                mollitia reprehenderit natus repudiandae?</p>
                            <div class="d-flex">
                                <div class="img-circle cover"><img src="{{ URL::asset('resorts_assets/images/user-2.svg')}}" alt="user">
                                </div>
                                <div>
                                    <h6>John Doe</h6>
                                    <span>Freelancer</span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Cupiditate officiis eaque ab
                                mollitia reprehenderit natus repudiandae?</p>
                            <div class="d-flex">
                                <div class="img-circle cover"><img src="{{ URL::asset('resorts_assets/images/user-2.svg')}}" alt="user">
                                </div>
                                <div>
                                    <h6>John Doe</h6>
                                    <span>Freelancer</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row g-sm-3 g-1 justify-content-between align-items-center">
                    <div class="col-auto">
                        <a href="index.html">Privacy Policy</a>
                    </div>
                    <div class="col-auto">
                        <a href="#">Terms of services</a>
                    </div>
                </div>
            </div>
        </div>
        <div class=" col-md-6">
            <div class="login-right">
                <div class="login-form">
                    <h3>Welcome to Wisdom AI®</h3>
                @if ($errors->any())
                @foreach ($errors->all() as $error)
                <!-- @dd($errors->any()); -->
                <div class="alert alert-danger alert-dismissible">
                  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                  <h5><i class="icon fas fa-ban"></i> Alert!</h5>
                  {{ $error }}
                </div>
                {{--<div class="alert alert-danger alert-dismissible">
                  <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                  {{ $error }}
                </div>--}}
                @endforeach
                @elseif(session()->has('success'))
                <div class="alert alert-success alert-dismissible">
                  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                  <h5><i class="icon fas fa-check"></i> Success</h5>
                  {{ session()->get('success') }}
                </div>
                {{--<div class="alert alert-success alert-dismissible">
                  <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                  {{ session()->get('success') }}
                </div>--}}
                @endif

                <div id="div-message">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @elseif (session('fail'))
                        <div class="alert alert-danger">
                            {{ session('fail') }}
                        </div>
                    @endif
                </div>
                <form name="formRequestPassword" id="formRequestPassword" method="post" action="{{route('resort.password.reset-submit')}}">
                    @csrf
                    <div class="mb-4">
                        <input type="hidden" name="token" value="{{ $token }}">

                        <label for="email" class="form-label">Email Id</label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="Email" value="{{$email}}">

                        <div id="div-email" style="color:red;"></div>
                    </div>
                    <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="form-floating position-relative">
                        <input type="password" name="password" id="password" class="form-control" placeholder="Password">
                        <label for="password">Password</label>
                        <i class="fa-regular fa-eye-slash toggle-password" id="togglePassword" style="position:absolute; top:50%; right:10px; transform:translateY(-50%); cursor:pointer;"></i>
                    </div>
                    <div id="div-password" style="color:red;"></div>
                </div>

                <div class="mb-4">
                    <label for="password_confirmations" class="form-label">Confirm Password</label>
                    <div class="form-floating position-relative">
                        <input type="password" name="password_confirmation" id="password_confirmations" class="form-control" placeholder="Confirm Password">
                        <label for="password_confirmations">Confirm Password</label>
                        <i class="fa-regular fa-eye-slash toggle-password" id="togglePassword2" style="position:absolute; top:50%; right:10px; transform:translateY(-50%); cursor:pointer;"></i>
                    </div>
                    <div id="div-password_confirmation" style="color:red;"></div>
                </div>




                    <div class="text-center">
                        <button type="submit" class="btn btn-primary  btn-theme">Request new password</button>

                    </div>


                </div>
                </form>
                </div>
                <div class="footer">© 2024 Wisdom AI Pvt Ltd | Every Data Shielded | Creativity Secured | All
                    innovations Protected.</div>
            </div>
        </div>
    </div>

    <script src="{{ URL::asset('resorts_assets/js/jquery.min.js') }}"></script>
    <script src="{{ URL::asset('resorts_assets/js/jquery.lazy.min.js') }}"></script>

    <script src="{{ URL::asset('resorts_assets/js/select2.min.js') }}"></script>
    <script src="{{ URL::asset('resorts_assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ URL::asset('resorts_assets/assets/js/slick.min.js') }}"></script>
    <script src="{{ URL::asset('admin_assets/plugins/toastr/toastr.min.js') }}"></script>
    <script src="{{ URL::asset('admin_assets/plugins/holdon/holdon.min.js') }}"></script>
    <script src="{{ URL::asset('assets/js/jquery.validate.min.js') }}"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet"/>
<!-- Toastr JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script type="text/javascript">
        $(document).ready( function() {
                    $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                    });
                    
                // Add form validation
                $('#formRequestPassword').validate({
                errorClass: 'text-danger-custom',
                ignore: [],
                rules: {
                    'email': {
                        required: true,
                        email: true
                    },
                    'password': {
                        required: true,
                        minlength: 8
                    },
                    'password_confirmation': {
                        required: true,
                        equalTo: '#password',
                        minlength: 8
                    }
                },
                messages: {
                    'email': {
                        required: "The email is required",
                        email: "Enter valid email"
                    },
                    'password': {
                        required: "The password is required",
                        minlength: "Your password must be at least 8 characters long"
                    },
                    'password_confirmation': {
                        required: "The confirm password is required",
                        equalTo: "The password must match",
                        minlength: "Your confirm password must be at least 8 characters long"
                    }
                },
                errorPlacement: function(error, element) {
                    if (element.attr("name") == "email") {
                        error.insertAfter("#div-email").css('color', 'red');
                    } else if (element.attr("name") == "password") {
                        error.insertAfter("#div-password").css('color', 'red');
                    } else if (element.attr("name") == "password_confirmation") {
                        error.insertAfter("#div-password_confirmation").css('color', 'red');
                    } else {
                        error.insertAfter(element).css('color', 'red');
                    }
                },
                errorElement: 'span',
                submitHandler: function(form) {
                    toastr.success( "Password changed successfully", "Success", { positionClass: 'toast-bottom-right'} );

                    setTimeout(function () 
                    {
                        form.submit();
                    }, 1000); // 1 second delay
                }
            });
                document.getElementById('togglePassword').addEventListener('click', function () {
                    var passwordInput = document.getElementById('password');
                    toggleVisibility(passwordInput, this);
                });

                document.getElementById('togglePassword2').addEventListener('click', function () {
                    var confirmPasswordInput = document.getElementById('password_confirmations');
                    toggleVisibility(confirmPasswordInput, this);
                });

                function toggleVisibility(inputField, toggleIcon) {
                    if (inputField.type === 'password') {
                        inputField.type = 'text';
                        toggleIcon.classList.remove('fa-eye-slash');
                        toggleIcon.classList.add('fa-eye');
                    } else {
                        inputField.type = 'password';
                        toggleIcon.classList.remove('fa-eye');
                        toggleIcon.classList.add('fa-eye-slash');
                    }
                }

        });
    </script>
</body>
</html>
