<script src="{{ URL::asset('admin_assets/plugins/jquery/jquery.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/jquery-ui/jquery-ui.min.js') }}"></script>
<script>
  $.widget.bridge('uibutton', $.ui.button)
</script>
<script src="{{ URL::asset('admin_assets/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/select2/js/select2.full.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/moment/moment.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/daterangepicker/daterangepicker.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/summernote/summernote-bs4.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/dist/js/adminlte.js') }}"></script>
<script src="{{ URL::asset('admin_assets/dist/js/pages/dashboard.js') }}"></script>
<script src="{{ URL::asset('assets/js/jquery.validate.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/toastr/toastr.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/6.6.9/sweetalert2.min.js"></script>
<script src="{{ URL::asset('admin_assets/plugins/bs-custom-file-input/bs-custom-file-input.min.js') }}"></script>
<script src="{{ URL::asset('admin_assets/plugins/holdon/holdon.min.js') }}"></script>
<script src="{{URL::asset('admin_assets/js/parsley.min.js')}}"></script>
<script src="{{ URL::asset('assets/js/bootstrap-datepicker.min.js') }}"></script>
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

  var baseUrl = "{{url('/')}}";
  var dt_format = "{{Common::getDateAndSetFormateToDatepicker()}}";

  @if(Session::has('message'))
  var type = "{{ Session::get('alert-type', 'info') }}";

  switch(type) {
    case 'info':
    toastr.info("{{ Session::get('message') }}","Info",{ positionClass: 'toast-bottom-right'});
    break;

    case 'warning':
    toastr.warning("{{ Session::get('message') }}","Warning",{ positionClass: 'toast-bottom-right'});
    break;

    case 'success':
    toastr.success("{{ Session::get('message') }}","Success",{ positionClass: 'toast-bottom-right'});
    break;

    case 'error':
    toastr.error("{{ Session::get('message') }}","Error",{ positionClass: 'toast-bottom-right'});
    break;
  }
  @endif

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

@if( Auth::guard('admin')->check() )
{{-- Live unread-counts poll: refreshes the Supports sidebar nav badge AND
     the Chat / Email Reply badges per ticket every 10s without a reload.
     Lightweight (just two indexed COUNTs); piggybacks on the already-loaded
     jQuery from the admin layout. --}}
<script>
(function () {
    if (typeof jQuery === 'undefined') return;
    var endpoint = "{{ route('admin.supports.counts') }}";

    function setBadge($el, count) {
        if (!$el || !$el.length) return;
        if (count > 0) {
            $el.removeClass('d-none').text(count > 99 ? '99+' : count);
        } else {
            $el.addClass('d-none').text('0');
        }
    }

    function poll() {
        $.getJSON(endpoint).done(function (data) {
            if (!data) return;
            // Sidebar Supports nav badge
            setBadge($('#js-supports-nav-badge'), parseInt(data.nav_total || 0, 10));

            // Per-ticket badges in the Supports DataTable. The DataTable
            // re-renders rows on each draw, so the elements may not exist
            // on every page; that's fine — selectors return empty jQuery
            // and we no-op.
            var byTicket = data.by_ticket || {};
            $('.js-chat-unread-badge').each(function () {
                var $b = $(this);
                var t = byTicket[$b.data('ticket-id')];
                setBadge($b, t ? parseInt(t.chat || 0, 10) : 0);
            });
            $('.js-email-unread-badge').each(function () {
                var $b = $(this);
                var t = byTicket[$b.data('ticket-id')];
                setBadge($b, t ? parseInt(t.email || 0, 10) : 0);
            });
        });
    }

    // Kick off after page load + every 10s thereafter.
    $(function () {
        poll();
        setInterval(poll, 10000);

        // After a DataTables redraw the per-ticket badges are fresh DOM
        // nodes — make sure they show the latest counts immediately
        // instead of waiting up to 10s for the next poll.
        $(document).on('draw.dt', function () { poll(); });
    });
})();
</script>
@endif

@yield('import-scripts')