<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>HRVMS- wisdomAI | Request New Password</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="{{ URL::asset('admin_assets/plugins/fontawesome-free/css/all.min.css') }}">
  <link rel="stylesheet" href="{{ URL::asset('admin_assets/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ URL::asset('admin_assets/dist/css/adminlte.min.css') }}">
  <link rel="stylesheet" href="{{ URL::asset('assets/css/custom.css') }}">
  <link rel="stylesheet" href="{{ URL::asset('admin_assets/plugins/toastr/toastr.min.css') }}">
  <link rel="stylesheet" href="{{ URL::asset('admin_assets/plugins/holdon/holdon.min.css') }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/6.6.9/sweetalert2.min.css">
</head>
<body class="hold-transition login-page">
  <div class="login-box forgotpass-box">
 

    <div class="card card-outline card-primary">
      <div class="card-header text-center">
      <a href="" class="h1"><b>HRVMS-</b>wisdomAI</a>
      </div>
      <div id="div-message">
          @if (session('success'))
              <div class="alert alert-success">
                  {{ session('success') }}
              </div>
              {{ Session::forget('success') }}
          @endif

          @if (session('error'))
              <div class="alert alert-danger">
                  {{ session('error') }}
              </div>
          @endif
      </div>
      <div class="card-body">
        <p class="login-box-msg">You forgot your password? Here you can easily retrieve a new password.</p>
        <form name="formRequestPassword" id="formRequestPassword" method="post">
          @csrf
          <div class="form-group">
            <div class="input-group">
              <input type="email" name="email" id="email" class="form-control" placeholder="Email">
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-envelope"></span>
                </div>
              </div>
            </div>
            <div id="div-email"></div>
          </div>

          <div class="row">
            <div class="col-12">
              <button type="submit" class="btn btn-primary btn-block">Request new password</button>
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
  <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/6.6.9/sweetalert2.min.js"></script>
  <script type="text/javascript">
    $(document).ready( function() {

      $.ajaxSetup({
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
      });

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
            error.insertAfter( "#div-email" );
          } else {
            error.insertAfter(element);
          }
        },
        errorElement: 'span'
      });

      $( "#formRequestPassword" ).on( "submit", function(e) {
        e.preventDefault();

        var dataString = $(this).serialize();
        var url = "{{route('admin.password.request-submit')}}";

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
                swal({
                  title: 'Success!',
                  text:  result.msg,
                  type: 'success',                  
                }).then(function (success) {
	    	          if (success) {
                    window.location.href = result.redirect_url;
                  }
	              });                
              } 
              else 
              {
                swal({
                  title: 'Error!',
                  text:  result.msg,
                  type: 'error',                  
                }).then(function (success) {
                  if (success) {
                    window.location.href = result.redirect_url;
                  }
                });
              }
            },
            error: function (result) {
              HoldOn.close();
              $( "#er-message" ).fadeIn();
              var data = result.responseJSON;
              console.log(result);
              if( data.status == false ) {
                if( data.account_not_verified == 1 ) {
                  $("#formRequestPassword").addClass('d-none');
                  
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
</body>
</html>
