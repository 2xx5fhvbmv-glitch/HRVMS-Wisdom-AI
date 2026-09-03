<script src="{{ URL::asset('resorts_assets/js/jquery-3.6.0.min.js')}}"></script>
<script src="{{ URL::asset('resorts_assets/js/jquery.min.js')}}"></script>
<script src="{{ URL::asset('resorts_assets/js/jquery.lazy.min.js')}}"></script>
<script src="{{ URL::asset('resorts_assets/js/dataTables.min.js')}}"></script>
<script src="{{ URL::asset('resorts_assets/js/dataTables.bootstrap5.js')}}"></script>
<script src="{{ URL::asset('resorts_assets/js/chart.js')}}"></script>
<!-- Dark/Teal theme system — disabled 2026-08-30, not production-ready.
     See resources/views/resorts/layouts/app.blade.php for the full
     explanation; one of 6 switches, all must be uncommented together. -->
<!-- <script src="{{ URL::asset('resorts_assets/js/chart-theme.js')}}"></script> -->
<script src="{{ URL::asset('resorts_assets/js/bootstrap-datepicker.min.js')}}"></script>
<script src="{{ URL::asset('resorts_assets/js/select2.min.js')}}"></script>
<script src="{{ URL::asset('resorts_assets/js/moment.min.js')}}"></script>
<script src="{{ URL::asset('resorts_assets/js/parsley.min.js')}}"></script>


<script src="{{ URL::asset('resorts_assets/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{ URL::asset('resorts_assets/js/script.js')}}"></script>

<script src="{{ URL::asset('resorts_assets/js/slick.min.js')}}"></script>

<script src="{{ URL::asset('resorts_assets/js/daterangepicker.min.js')}}"></script>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script src="{{ URL::asset('resorts_assets/additionalJs/swatalart.min.js') }}"></script>
<script src="{{ URL::asset('resorts_assets/additionalJs/sweetalert2.js') }}"></script>
@include('resorts.layouts._confirm')
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
    // Toastr re-theme glue (see toastr-theme.css, shared with the resort-admin
    // and admin portals). One place so every existing toastr call in the
    // shopkeeper portal gets the new frosted look automatically.
    var WT_DURATION = 5000; // visible-toast duration for the .wt-prog bar
    var wtPendingSticky = false;
    if (window.toastr) {
        toastr.options.closeButton = true;
        toastr.options.progressBar = false;
        toastr.options.closeOnHover = false;
        toastr.options.showMethod = 'show';
        toastr.options.hideMethod = 'hide';
        toastr.options.timeOut = 0; // toastr's own internal auto-hide timer would race our .wt-prog bar's animationend (see WT_DURATION above)
        toastr.options.extendedTimeOut = 0;
        toastr.options.onShown = function () {
            var $t = $(this);
            if (wtPendingSticky) { wtPendingSticky = false; return; }
            $t.append(
                $('<span class="wt-prog"></span>')
                    .css('animation-duration', WT_DURATION + 'ms')
                    .on('animationend', function () {
                        if (window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches) {
                            toastr.clear($t);
                            return;
                        }
                        $t.addClass('wt-out').one('animationend', function () { toastr.clear($t); });
                    })
            );
        };
    }
    function wisdomToast(type, title, message, opts) {
        if (!window.toastr) return;
        opts = opts || {};
        var sticky = !!opts.sticky || !!(opts.list && opts.list.length);
        var esc = function (s) {
            return String(s).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        };
        var html = esc(message || '');
        if (opts.list && opts.list.length) {
            html += '<ul class="wt-errlist">' + opts.list.map(function (i) { return '<li>' + esc(i) + '</li>'; }).join('') + '</ul>';
        }
        wtPendingSticky = sticky;
        var $toast = toastr[type](html, title, {
            timeOut: 0,
            extendedTimeOut: 0,
            escapeHtml: false
        });
        return $toast;
    }
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.3/jquery.validate.min.js"></script>


<script src="https://cdn.socket.io/4.0.0/socket.io.min.js"></script>
<script>
        // Global currency helpers for shopkeeper
        var displayCurrency = '{{ Common::getDisplayCurrency() }}';
        var currencySymbol = (displayCurrency === 'MVR') ? 'MVR ' : '$';
        var usdToMvrRate = {{ Common::getUsdToMvrRate() }};

        function convertAmount(amount, sourceCurrency) {
            amount = parseFloat(amount) || 0;
            sourceCurrency = (sourceCurrency || 'USD').toUpperCase();
            if (displayCurrency === 'MVR' && sourceCurrency === 'USD') return amount * usdToMvrRate;
            if (displayCurrency === 'Dollar' && sourceCurrency === 'MVR') return amount / usdToMvrRate;
            return amount;
        }

        function formatAmount(amount, sourceCurrency) {
            var converted = convertAmount(amount, sourceCurrency);
            return currencySymbol + converted.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        $(window).on('load', function () {
            $('#nav-staffShop').show(100);
        });

    const socket = io("{{ env('BASE_URL', 'http://localhost:3000') }}",{
        transports: ["websocket"]
    }); // WebSocket server from env

    socket.on('new-notification', (data) => {
        console.log(data);
        let htmlview = data.html;
        let ReciverId="{{  Auth::guard('shopkeeper')->user()->id }}";
        // Check if GetEmployee exists before trying to access its properties
        let RankOfResort = "{{ isset(Auth::guard('resort-admin')->user()->GetEmployee) ? Auth::guard('resort-admin')->user()->GetEmployee->rank : '' }}";
        let Dept_id = parseInt("{{ isset(Auth::guard('resort-admin')->user()->GetEmployee) ? Auth::guard('resort-admin')->user()->GetEmployee->Dept_id : '' }}");
        let type = data.type;
        let SenderId = data.resortid;
        let PendingDepartment_id = data.PendingDepartment_id;
            
        if(type == 10 && ReciverId == SenderId)
        {
            $(".notification-body").html(htmlview);
        }
    });

       const $notificationBtn = $(".notification-btn");
        const $notificationWrapper = $(".notification-wrapper");
    $(document).ready(function (){
        $notificationBtn.on("click", function (e) {
            e.stopPropagation(); // Prevent event from bubbling up to document click
            if ($notificationWrapper.hasClass("end-0")) {
                $notificationWrapper.removeClass("end-0");
            } else {
                $notificationWrapper.addClass("end-0");
            }
        });

        // Remove 'end-0' class when clicking outside notification-btn and notification-wrapper
        $(document).on("click", function (e) {
            if (
                !$notificationWrapper.is(e.target) &&
                !$notificationBtn.is(e.target) &&
                $notificationWrapper.has(e.target).length === 0 &&
                $notificationBtn.has(e.target).length === 0
            ) {
                $notificationWrapper.removeClass("end-0");
            }
        });
    });

    // Top nav (Dashboard/Products/Payment History/Configuration) uses the
    // same .navcarosel-box/.carosel-menu markup as the resort header, which
    // only gets its Slick prev/next arrows from a .slick() init — the
    // resort side does this in resources/views/resorts/layouts/js.blade.php
    // (emnurend()), but this shopkeeper layout never called it at all, so
    // the arrows never rendered here.
    $(document).ready(function () {
        var activeIndex = -1;
        $('.carosel-menu .dropdown-toggle').each(function (index) {
            if ($(this).hasClass('active')) {
                activeIndex = index;
                return false;
            }
        });

        var slideCount = $('.carosel-menu .text-center').length;
        if (slideCount <= 3) {
            $('.navcarosel-box').css('max-width', 'fit-content');
            $('.carosel-menu').addClass('d-flex').css('gap', '8px');
            $('.carosel-menu .text-center').css('width', 'auto');
        } else {
            $('.carosel-menu').slick({
                variableWidth: true,
                slidesToShow: 1,
                infinite: false,
                slidesToScroll: 3,
                initialSlide: activeIndex >= 0 ? activeIndex : 0,
                dots: false,
                focusOnSelect: false,
                swipe: true
            });
        }

        function updateActiveClasses() {
            $('.carosel-menu .text-center').each(function () {
                if ($(this).find('.dropdown-toggle').hasClass('active')) {
                    $(this).addClass('slick-current slick-active');
                } else {
                    $(this).removeClass('slick-current slick-active');
                }
            });
        }
        updateActiveClasses();
        $('.carosel-menu').on('afterChange', function () {
            updateActiveClasses();
        });
    });

    // Load notifications on page load
    function loadShopkeeperNotifications() {
        $.ajax({
            url: "{{ route('shopkeeper.notifications') }}",
            type: "GET",
            success: function(response) {
                if (response.success) {
                    $(".notification-body").html(response.html);
                }
            }
        });
    }
    loadShopkeeperNotifications();

    // Mark notification as read
    $(document).on("click", ".MarkShopNotification", function() {
        var id = $(this).data('id');
        $.ajax({
            url: "{{ route('shopkeeper.notifications.mark') }}",
            type: "POST",
            data: {"_token": "{{ csrf_token() }}", "id": id},
            success: function(response) {
                if (response.success) {
                    $(".class_remove_me_" + id).remove();
                }
            }
        });
    });
</script>

@yield('import-scripts')