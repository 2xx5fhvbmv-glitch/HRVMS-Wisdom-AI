<script src="{{ URL::asset('applicant_form_assets/js/jquery-3.6.0.min.js')}}"></script>
<script src="{{ URL::asset('applicant_form_assets/js/jquery.min.js')}}"></script>
<script src="{{ URL::asset('applicant_form_assets/js/jquery.lazy.min.js')}}"></script>
<script src="{{ URL::asset('applicant_form_assets/js/bootstrap-datepicker.min.js')}}"></script>
<script src="{{ URL::asset('applicant_form_assets/js/select2.min.js')}}"></script>
<script src="{{ URL::asset('applicant_form_assets/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{ URL::asset('applicant_form_assets/js/slick.min.js')}}"></script>
<script src="{{ URL::asset('applicant_form_assets/js/moment.min.js')}}"></script>
<script src="{{ URL::asset('applicant_form_assets/js/script.js')}}"></script>

<script src="{{ URL::asset('applicant_form_assets/js/parsley.min.js')}}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="{{ URL::asset('applicant_form_assets/js/croppie.js')}}"></script>
<script>
    var dt_format = 'd-m-Y';;
   
</script>
<script type="text/javascript">
    $(document).ready( function() {
        $("#loader").css("display", "none");
        $.ajaxSetup({
            headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    });
</script>
@yield('import-scripts')