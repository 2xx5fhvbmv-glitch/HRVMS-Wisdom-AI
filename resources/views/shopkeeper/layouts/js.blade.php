<script src="{{ URL::asset('resorts_assets/js/jquery-3.6.0.min.js')}}"></script>
<script src="{{ URL::asset('resorts_assets/js/jquery.min.js')}}"></script>
<script src="{{ URL::asset('resorts_assets/js/jquery.lazy.min.js')}}"></script>
<script src="{{ URL::asset('resorts_assets/js/dataTables.min.js')}}"></script>
<script src="{{ URL::asset('resorts_assets/js/dataTables.bootstrap5.js')}}"></script>
<script src="{{ URL::asset('resorts_assets/js/chart.js')}}"></script>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.3/jquery.validate.min.js"></script>


<script src="https://cdn.socket.io/4.0.0/socket.io.min.js"></script>
<script>

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
</script>

@yield('import-scripts')