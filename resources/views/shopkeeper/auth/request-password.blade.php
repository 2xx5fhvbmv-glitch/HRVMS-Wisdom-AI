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

    <title>HRVMS | SHopkeeper Panel</title>
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
                    <h3>Forgot Password</h3>
                    <form name="formRequestPassword" id="formRequestPassword" method="post">
                        @csrf
                        <div class="mb-4">
                            <label for="email" class="form-label">Email Id</label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="Email">

                            <div id="div-email" style="color:red;"></div>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary  btn-theme">Request new password</button>
                        </div>
                    </form>
                    </div>
                <div class="footer">© 2024 Wisdom AI Pvt Ltd | Every Data Shielded | Creativity Secured | All
                    innovations Protected.</div>
            </div>
        </div>
    </div>

    <script src="{{ URL::asset('resorts_assets/js/jquery.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/6.6.9/sweetalert2.min.js"></script>
    <script
    <script src="{{ URL::asset('resorts_assets/js/jquery.lazy.min.js') }}"></script>

    <script src="{{ URL::asset('resorts_assets/js/select2.min.js') }}"></script>
    <script src="{{ URL::asset('resorts_assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ URL::asset('resorts_assets/js/slick.min.js') }}"></script>
    <script src="{{ URL::asset('admin_assets/plugins/toastr/toastr.min.js') }}"></script>
    <script src="{{ URL::asset('admin_assets/plugins/holdon/holdon.min.js') }}"></script>
    <script src="{{ URL::asset('assets/js/jquery.validate.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ URL::asset('resorts_assets/additionalJs/swatalart.min.js') }}"></script>
    <script src="{{ URL::asset('resorts_assets/additionalJs/sweetalert2.js') }}"></script>
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
                }
                },
                messages: {
                'email': {
                    required: "The email is required",
                    email: "Enter valid email"
                }
                },
                errorPlacement: function(error, element) {
                if( element.attr("name") == "email" ) {
                    error.insertAfter( "#div-email" ).css('color', 'red');
                } else {
                    error.insertAfter(element);
                }
                },
                errorElement: 'span'
            });
            $( "#formRequestPassword" ).on( "submit", function(e) {
                e.preventDefault();

                var dataString = $(this).serialize();
                var url = "{{ route('shopkeeper.password.request-submit') }}";

                if( $(this).valid() ) {
                    $.ajax({
                        type: "POST",
                        url: url,
                        data: new FormData(this),
                        dataType: "JSON",
                        contentType: false,
                        processData: false,

                        beforeSend: function() {
                            HoldOn.open({ theme: "sk-cube-grid" });
                        },

                        success: function(result) {
                            HoldOn.close();
                            if (result.success === true) {
                                // Using SweetAlert2
                                Swal.fire({
                                    title: 'Success!',
                                    text: result.msg,
                                    icon: 'success' // Corrected from `type` to `icon`
                                }).then(function (success) {
                                    if (success) {
                                        window.location.href = result.redirect_url;
                                    }
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: result.msg,
                                    icon: 'error' // Corrected from `type` to `icon`
                                }).then(function (success) {
                                    if (success && result.redirect_url) {
                                        window.location.href = result.redirect_url;
                                    }
                                });
                            }
                        },

                        error: function(result) {
                            HoldOn.close();
                            $("#er-message").fadeIn();
                            var data = result.responseJSON;
                            if (data.status === false) {
                                if (data.account_not_verified == 1) {
                                    $("#formRequestPassword").addClass('d-none');
                                }
                                $("#er-message").html('<span> ' + data.message + '</span>');
                            } else {
                                $("#er-message").html('<span><i class="fa fa-exclamation-triangle"></i> Something went wrong. Please try again.</span>');
                            }
                        }
                    });
                }
            });

        });
      </script>

</body>
</html>
