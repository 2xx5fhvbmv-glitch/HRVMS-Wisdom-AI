<script src="<?php echo e(URL::asset('admin_assets/plugins/jquery/jquery.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('admin_assets/plugins/jquery-ui/jquery-ui.min.js')); ?>"></script>
<script>
  $.widget.bridge('uibutton', $.ui.button)
</script>
<script src="<?php echo e(URL::asset('admin_assets/plugins/bootstrap/js/bootstrap.bundle.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('admin_assets/plugins/select2/js/select2.full.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('admin_assets/plugins/moment/moment.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('admin_assets/plugins/daterangepicker/daterangepicker.js')); ?>"></script>
<script src="<?php echo e(URL::asset('admin_assets/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('admin_assets/plugins/summernote/summernote-bs4.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('admin_assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('admin_assets/dist/js/adminlte.js')); ?>"></script>
<script src="<?php echo e(URL::asset('admin_assets/dist/js/pages/dashboard.js')); ?>"></script>
<script src="<?php echo e(URL::asset('assets/js/jquery.validate.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('admin_assets/plugins/toastr/toastr.min.js')); ?>"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/6.6.9/sweetalert2.min.js"></script>
<script src="<?php echo e(URL::asset('admin_assets/plugins/bs-custom-file-input/bs-custom-file-input.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('admin_assets/plugins/holdon/holdon.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('admin_assets/js/parsley.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('assets/js/bootstrap-datepicker.min.js')); ?>"></script>
<script>
  toastr.options.closeButton = true;
  toastr.options.progressBar = true;
  toastr.options.hideMethod = 'slideUp';
  toastr.options.closeEasing = 'swing';
  toastr.options.showEasing = 'easeOutBounce';
  toastr.options.postion = 'bottom-right';
  toastr.options.showDuration = '300';
  toastr.options.hideDuration = '1000';
  toastr.options.timeOut = '5000';
  toastr.options.extendedTimeOut = '1000';

  var baseUrl = "<?php echo e(url('/')); ?>";
  var dt_format = "<?php echo e(Common::getDateAndSetFormateToDatepicker()); ?>";

  <?php if(Session::has('message')): ?>
  var type = "<?php echo e(Session::get('alert-type', 'info')); ?>";

  switch(type) {
    case 'info':
    toastr.info("<?php echo e(Session::get('message')); ?>","Info",{ positionClass: 'toast-bottom-right'});
    break;

    case 'warning':
    toastr.warning("<?php echo e(Session::get('message')); ?>","Warning",{ positionClass: 'toast-bottom-right'});
    break;

    case 'success':
    toastr.success("<?php echo e(Session::get('message')); ?>","Success",{ positionClass: 'toast-bottom-right'});
    break;

    case 'error':
    toastr.error("<?php echo e(Session::get('message')); ?>","Error",{ positionClass: 'toast-bottom-right'});
    break;
  }
  <?php endif; ?>

  $(document).ready( function() {
    $(".select-2-gen").select2();
    $.ajaxSetup({
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
    });

    bsCustomFileInput.init();
  });

  function resetForm(event){
		event.preventDefault();
		var form = event.currentTarget.form;
		var inputs = form.querySelectorAll('input');
    var textareas = form.querySelectorAll('textarea');
		var selects = form.querySelectorAll('select');
    var checkboxes = form.querySelectorAll('checkbox');
    var radios = form.querySelectorAll('radio');
		inputs.forEach(function(input, index){
			input.value = null;
		});
    textareas.forEach(function(textarea, index){
			textarea.value = null;
		});
		selects.forEach(function(select, index){
			select.value = null;
		});
    checkboxes.forEach(function(checkbox, index){
			checkbox.checked = false;
		});
    radios.forEach(function(radio, index){
			radio.checked = false;
		});
	}
</script>
<?php echo $__env->yieldContent('import-scripts'); ?><?php /**PATH C:\wamp64\www\Wisdom-Ai\HRVMS-Wisdom-AI\resources\views/admin/layouts/js.blade.php ENDPATH**/ ?>