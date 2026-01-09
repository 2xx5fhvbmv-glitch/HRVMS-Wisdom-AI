<!doctype html>
<html lang=en data-bs-theme=auto>

<head>
    <meta charset=utf-8>
    <meta name=viewport content="width=device-width,initial-scale=1">
    <meta name="description" content="">
    <meta name=keywords content="">
    <link href="<?php echo e(URL::asset('resorts_assets/css/bootstrap.min.css')); ?>" rel=stylesheet>
    <link href="<?php echo e(URL::asset('resorts_assets/css/select2.min.css')); ?>" rel=stylesheet>
    <link href="<?php echo e(URL::asset('resorts_assets/css/slick-theme.css')); ?>" rel=stylesheet>
    <link href="<?php echo e(URL::asset('resorts_assets/css/slick.css')); ?>" rel=stylesheet>
    <link href="<?php echo e(URL::asset('resorts_assets/css/all.min.css')); ?>" rel=stylesheet>
    <link href="<?php echo e(URL::asset('resorts_assets/css/dataTables.min.css')); ?>" rel=stylesheet>

    <link href="<?php echo e(URL::asset('resorts_assets/css/default.css')); ?>" rel=stylesheet>
    <link href="<?php echo e(URL::asset('resorts_assets/css/media.css')); ?>" rel=stylesheet>
    <link rel="stylesheet" href="<?php echo e(URL::asset('admin_assets/plugins/toastr/toastr.min.css')); ?>">



    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo e(URL::asset('resorts_assets/images//apple-touch-icon.png')); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo e(URL::asset('resorts_assets/images//favicon-32x32.png')); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo e(URL::asset('resorts_assets/images//favicon-16x16.png')); ?>">

    <title>HRVMS | LOGIN</title>
</head>

<body class="login-body">
    <div class="row g-0 h-100">
        <div class=" col-md-6">
            <div class="login-left">
                <div class="img-obj cover"><img src="<?php echo e(URL::asset('resorts_assets/images/login.jpg')); ?>" alt="image"></div>
                <div class="row g-sm-3 g-1 justify-content-between align-items-center ">
                    <div class="col-auto">
                        <a href="https://www.thewisdom.ai/" class="brand-logo">
                          <img style="height: 80px" src="<?php echo e(URL::asset('resorts_assets/images/Brand color 1@3x.png')); ?>"></a>
                    </div>

                </div>
                <div class="head">
                    <h1>Start Your Journey with us</h1>
                </div>
                <div>
                    <div class="login-slider slider">
                        <div>
                            <p>
                              <?php echo e($settings->contents ?? ""); ?>

                            </p>
                            
                        </div>
                    </div>
                </div>
                <div class="row g-sm-3 g-1 justify-content-between align-items-center">
                    <div class="col-auto">
                        <a href="https://www.thewisdom.ai/privacy-policy">Privacy Policy</a>
                    </div>
                    <div class="col-auto">
                        <a href="https://www.thewisdom.ai/terms-and-conditions">Terms of services</a>
                    </div>
                </div>
            </div>
        </div>
        <div class=" col-md-6">
            <div class="login-right">
                <div class="login-form">
                    <h3>Welcome to Wisdom AI®</h3>
                    <form name="formLogin" id="formLogin" method="post">
                      <?php echo csrf_field(); ?>
                      <div class="row g-md-4 g-3">
                          <div class="col-12">
                              <div class="form-floating">
                                  <input type="email" name="email" id="email" class="form-control"
                                      placeholder="name@example.com">
                                  <label for="email">Email address</label>
                              </div>
                              <div id="div-email"></div>
                          </div>
                          <div class="col-12">
                              <div class="form-floating">
                                  <input type="password" name="password" id="password" class="form-control"
                                      placeholder="Password">
                                  <label for="password">Password</label>

                                  <i class="fa-regular fa-eye-slash" id="togglePassword"></i>
                              </div>
                              <div id="div-password"></div>
                          </div>
                          <div class="col-auto">
                              <div class="form-check">
                                  <input class="form-check-input" type="checkbox" name="remember_token" value="" id="remember" checked>
                                  <label class="form-check-label" for="flexCheckDefault">
                                      Remind me
                                  </label>
                              </div>
                          </div>
                          <div class="col-auto ms-auto"><a href="<?php echo e(route('resort.password.request')); ?>" class="a-link">Forgot Password?</a></div>
                          <div class="col-12">
                            <button type="submit" class="btn btn-themeNeon w-100 mt-2">Log In</button>
                          </div>
                      </div>
                    </form>
                </div>
                <div class="footer">© <?php echo e(date('Y')); ?> Wisdom AI Pvt Ltd | Every Data Shielded | Creativity Secured | All
                  innovations Protected.</div>
            </div>
        </div>
    </div>

    <script src="<?php echo e(URL::asset('resorts_assets/js/jquery.min.js')); ?>"></script>
    <script src="<?php echo e(URL::asset('resorts_assets/js/jquery.lazy.min.js')); ?>"></script>

    <script src="<?php echo e(URL::asset('resorts_assets/js/select2.min.js')); ?>"></script>
    <script src="<?php echo e(URL::asset('resorts_assets/js/bootstrap.bundle.min.js')); ?>"></script>
    <script src="<?php echo e(URL::asset('resorts_assets/js/slick.min.js')); ?>"></script>
    <script src="<?php echo e(URL::asset('admin_assets/plugins/toastr/toastr.min.js')); ?>"></script>
    <script src="<?php echo e(URL::asset('admin_assets/plugins/holdon/holdon.min.js')); ?>"></script>
    <script src="<?php echo e(URL::asset('assets/js/jquery.validate.min.js')); ?>"></script>
    <script type="text/javascript">
        $(document).ready( function() {

            $(".login-slider").slick({
            dots: true, arrows: false,
            infinite: true,
            slidesToShow: 1,
            slidesToScroll: 1
        });
          $('#formLogin').validate({
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
              }
            },
            messages: {
              'email': {
                required: "The email is required",
                email: "Enter valid email",
              },
              'password': {
                required: "The password is required",
                minlength: "Your password must be at least 8 characters long"
              }
            },
            errorPlacement: function(error, element) {
              if( element.attr("name") == "email" ) {
                error.insertAfter( "#div-email" ).css('color', 'red');;
              } else if( element.attr("name") == "password" ) {
                error.insertAfter( "#div-password" ).css('color', 'red');;
              } else {
                error.insertAfter(element);
              }
            },
            errorElement: 'span'
          });

          $( "#formLogin" ).on( "submit", function(e) {
            e.preventDefault();

            var dataString = $(this).serialize();
            var url = "<?php echo e(route('resort.login')); ?>";

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
                    window.location.href = result.redirect_url;
                  } else {
                    toastr.error( result.msg, "Error", { positionClass: 'toast-bottom-right'} );
                  }
                },
                error: function (result) {
                  HoldOn.close();
                  $( "#er-message" ).fadeIn();
                  var data = result.responseJSON;
                  if( data.status == false ) {
                    if( data.account_not_verified == 1 ) {
                      $("#formLogin").addClass('d-none');
                      $("#formResendOtp").removeClass('d-none');
                      $("#formTitle").text("Verify account");
                    }
                    $( "#er-message" ).html('<span> '+data.message+'</span>');
                  } else {
                    $( "#er-message" ).html('<span><i class="fa fa-exclamation-triangle"></i> something went wrong please try again</span>');
                  }
                }
              });
            }
          });

        });
      </script>
      <script>
        // Check if remember me is checked and autofill email and password
        window.onload = function () {
          if (localStorage.getItem('rememberMe') && localStorage.getItem('rememberMe') === 'true') {
            var storedEmail = localStorage.getItem('email');
            var storedPassword = localStorage.getItem('password');
            document.getElementById('email').value = storedEmail;
            document.getElementById('password').value = storedPassword;
            document.getElementById('remember').checked = true;
          }
        };

        // Save email and password to local storage when remember me is checked
        document.getElementById('formLogin').addEventListener('submit', function (event) {
          var rememberMe = document.getElementById('remember').checked;
          if (rememberMe) {
            localStorage.setItem('email', document.getElementById('email').value);
            localStorage.setItem('password', document.getElementById('password').value);
            localStorage.setItem('rememberMe', 'true');
          } else {
            localStorage.removeItem('email');
            localStorage.removeItem('password');
            localStorage.setItem('rememberMe', 'false');
          }
        });

        document.getElementById('togglePassword').addEventListener('click', function () {
        var passwordInput = document.getElementById('password');
        var icon = this;
        // console.log(icon);

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

    </script>

</body>

</html>
<?php /**PATH C:\wamp64\www\Wisdom-Ai\HRVMS-Wisdom-AI\resources\views/resorts/auth/login.blade.php ENDPATH**/ ?>