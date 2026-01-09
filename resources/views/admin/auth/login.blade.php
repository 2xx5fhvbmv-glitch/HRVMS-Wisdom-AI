<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>HRVMS- wisdomAI | Log in</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="{{ URL::asset('admin_assets/plugins/fontawesome-free/css/all.min.css') }}">
  <link rel="stylesheet" href="{{ URL::asset('admin_assets/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ URL::asset('admin_assets/dist/css/adminlte.min.css') }}">
  <link rel="stylesheet" href="{{ URL::asset('assets/css/custom.css') }}">
  <link rel="stylesheet" href="{{ URL::asset('admin_assets/plugins/toastr/toastr.min.css') }}">
  <link rel="stylesheet" href="{{ URL::asset('admin_assets/plugins/holdon/holdon.min.css') }}">
</head>
<body class="hold-transition login-page">
  <div class="login-box">
    <div class="card card-outline card-primary">
      <div class="card-header text-center">
        <a href="" class="h1"><b>HRVMS-</b>wisdomAI</a>
      </div>
      <div class="card-body">
        <p class="login-box-msg">Sign in to start your session</p>
        <form name="formLogin" id="formLogin" method="post">
          @csrf
          <div class="form-group">
            <div class="input-group">
              <input type="email" name="email" id="email" class="form-control" placeholder="Email" >
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-envelope"></span>
                </div>
              </div>
            </div>
            <div id="div-email"></div>
          </div>
          <div class="form-group">
            <div class="input-group">
              <input type="password" name="password" id="password" class="form-control" placeholder="Password">
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-lock"></span>
                </div>
              </div>
            </div>
            <div id="div-password"></div>
          </div>

          <div class="row">
            <div class="col-md-8 col-sm-12">
              <div class="icheck-primary">
                <input type="checkbox" id="remember" name="remember_token">
                <label for="remember">
                  Remember Me
                </label>
              </div>
            </div>
            <div class="col-md-4 col-sm-12">
              <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            </div>
          </div>
        </form>
        <p class="mb-1">
          <a href="{{route('admin.password.request')}}">I forgot my password</a>
        </p>
      </div>
    </div>
  </div>

  <!-- jQuery -->
  <script src="{{ URL::asset('admin_assets/plugins/jquery/jquery.min.js') }}"></script>
  <script src="{{ URL::asset('admin_assets/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ URL::asset('admin_assets/dist/js/adminlte.min.js') }}"></script>
  <script src="{{ URL::asset('assets/js/jquery.validate.min.js') }}"></script>
  <script src="{{ URL::asset('admin_assets/plugins/toastr/toastr.min.js') }}"></script>
  <script src="{{ URL::asset('admin_assets/plugins/holdon/holdon.min.js') }}"></script>
  <script type="text/javascript">
    $(document).ready( function() {

      // Add form validation
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
            error.insertAfter( "#div-email" );
          } else if( element.attr("name") == "password" ) {
            error.insertAfter( "#div-password" );
          } else {
            error.insertAfter(element);
          }
        },
        errorElement: 'span'
      });

      $( "#formLogin" ).on( "submit", function(e) {
        e.preventDefault();

        var dataString = $(this).serialize();
        var url = "{{route('admin.login')}}";

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
  </script>
</body>
</html>
