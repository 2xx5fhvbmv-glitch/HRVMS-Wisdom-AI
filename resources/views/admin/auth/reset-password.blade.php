<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>HRVMS- wisdomAI | Reset Password</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="{{ URL::asset('admin_assets/plugins/fontawesome-free/css/all.min.css') }}">
  <link rel="stylesheet" href="{{ URL::asset('admin_assets/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ URL::asset('admin_assets/dist/css/adminlte.min.css') }}">
  <link rel="stylesheet" href="{{ URL::asset('assets/css/custom.css') }}">
  <link rel="stylesheet" href="{{ URL::asset('admin_assets/plugins/toastr/toastr.min.css') }}">
  <link rel="stylesheet" href="{{ URL::asset('admin_assets/plugins/holdon/holdon.min.css') }}">
</head>
<body class="hold-transition login-page">
  <div class="login-box forgotpass-box">
    @if ($errors->any())
    @foreach ($errors->all() as $error)
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
    <div class="card card-outline card-primary">
      <div class="card-header text-center">
      <a href="" class="h1"><b>HRVMS-</b>wisdomAI</a>
      </div>
      <div class="card-body">

        <p class="login-box-msg">Set new password</p>

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
        <form name="formRequestPassword" id="formRequestPassword" method="post" action="{{route('admin.password.reset-submit')}}">
          @csrf
          <input type="hidden" name="token" value="{{ $token }}">
          <div class="input-group mb-3">
            <input type="email" name="email" id="email" class="form-control" placeholder="Email" value="{{$email}}">
            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-envelope"></span>
              </div>
            </div>
          </div>
          <div id="div-email"></div>

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

          <div class="form-group">
            <div class="input-group">
              <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Confirm Password">
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-lock"></span>
                </div>
              </div>
            </div>
            <div id="div-password_confirmation"></div>
          </div>

          <div class="row">
            <div class="col-12">
              <button type="submit" class="btn btn-primary btn-block">Submit</button>
            </div>
          </div>
        </form>
        <p class="mb-1">
          <a href="{{route('admin.loginindex')}}">Login</a>
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
            minlength: 8 ,
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
            equalTo: 'The password must match',
            minlength: "Your confirm password must be at least 8 characters long"
          }
        },
        errorPlacement: function(error, element) {
          if( element.attr("name") == "email" ) {
            error.insertAfter( "#div-email" );
          } else if( element.attr("name") == "password" ) {
            error.insertAfter( "#div-password" );
          } else if( element.attr("name") == "password_confirmation" ) {
            error.insertAfter( "#div-password_confirmation" );
          } else {
            error.insertAfter(element);
          }
        },
        errorElement: 'span'
      });

      $("#formRequestPassword").on("submit", function(e) {
            e.preventDefault();

            var dataString = $(this).serialize();
            var url = $(this).attr("action");

            if ($(this).valid()) {
                $.ajax({
                    type: "POST",
                    url: url,
                    data: new FormData(this),
                    dataType: "JSON",
                    contentType: false,
                    processData: false,
                    cache: false,
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
                            window.location.href = result.redirect_url;
                        } 
                        else {
                            toastr.error(result.msg, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function(result) {
                        HoldOn.close();
                        $("#er-message").fadeIn();
                        var data = result.responseJSON;
                        if (data.status == false) {
                            if (data.account_not_verified == 1) {
                                $("#formLogin").addClass('d-none');
                                $("#formResendOtp").removeClass('d-none');
                                $("#formTitle").text("Verify account");
                            }
                            $("#er-message").html('<span> ' + data.message + '</span>');
                        } else {
                            $("#er-message").html(
                                '<span><i class="fa fa-exclamation-triangle"></i> something went wrong please try again</span>'
                            );
                        }
                    }
                });
            }
        });
    });
  </script>
</body>
</html>