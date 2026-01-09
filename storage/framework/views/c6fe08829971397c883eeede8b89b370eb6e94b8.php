<!-- Core Libraries First -->
<script src="<?php echo e(URL::asset('resorts_assets/js/jquery-3.6.0.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('resorts_assets/ckeditor/ckeditor.js')); ?>"></script>
    <script src="<?php echo e(URL::asset('resorts_assets/ckeditor/samples/js/sample.js')); ?>"></script>
<!-- jQuery Validation -->
<!-- Lazy Loading -->
<script src="<?php echo e(URL::asset('resorts_assets/js/jquery.lazy.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('resorts_assets/js/slick.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('resorts_assets/js/chart.js')); ?>"></script>
<!-- DataTables -->
<script src="<?php echo e(URL::asset('resorts_assets/js/dataTables.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('resorts_assets/js/dataTables.bootstrap5.js')); ?>"></script>
<!-- Google Charts -->
<script src="<?php echo e(URL::asset('resorts_assets/js/charts-loader.js')); ?>"></script>

<!-- Bootstrap and UI Components -->
<script src="<?php echo e(URL::asset('resorts_assets/js/bootstrap.bundle.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('resorts_assets/js/bootstrap-datepicker.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('resorts_assets/js/select2.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('resorts_assets/js/moment.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('resorts_assets/js/fullcalendar.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('resorts_assets/js/daterangepicker.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('resorts_assets/js/OrgChart.js')); ?>"></script>
<script src="<?php echo e(URL::asset('resorts_assets/js/script.js')); ?>"></script>
<script src="<?php echo e(URL::asset('resorts_assets/js/parsley.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('resorts_assets/js/jquery.validate.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('resorts_assets/js/toastr.min.js')); ?>"></script>


<script src="<?php echo e(URL::asset('applicant_form_assets/js/croppie.js')); ?>"></script>
<script src="<?php echo e(URL::asset('resorts_assets/additionalJs/swatalart.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('resorts_assets/additionalJs/sweetalert2.js')); ?>"></script>
<script src="<?php echo e(URL::asset('resorts_assets/js/flatpickr.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('resorts_assets/js/jQuery.print.js')); ?>"></script>
<script src="<?php echo e(URL::asset('resorts_assets/js/chartjs-chart-treemap.js')); ?>"></script>
<script src="<?php echo e(URL::asset('resorts_assets/js/socket.io.min.js')); ?>"></script>



<script>
    var dt_format = "<?php echo e(Common::getDateAndSetFormateToDatepicker()); ?>";
</script>


<script>
    

    $(document).on("click", "#logout", function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Are you sure?',
            text: "You want to logout!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Logout!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "<?php echo e(route('resort.logout')); ?>";
            }
        });
    });
    
    $(window).on('load', function () {
        requestAnimationFrame(function() {
            loadMenu();
        });
    }); 
     function getDeviceType() {
            const width = window.innerWidth;
            if (width <= 767) {
                return 'mobile';
            } else if (width >= 768 && width <= 1024) {
                return 'tablet';
            } else {
                return 'desktop';
            }
        }

    function loadMenu(){
         var deviceType = getDeviceType();
         $.ajax({
            url: "<?php echo e(route('resort.getMenuData')); ?>", 
            type: "GET",
            data: {
                 deviceType: deviceType,
                 page_route: '<?php echo e(Route::currentRouteName()); ?>'
             },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    if( response.device === 'mobile') {
                        $('#navbar-vertical-view-menu').addClass('d-none');
                        $('.search-bar-nav').addClass('d-none');
                        $('#navbar-mobile-view').html(response.menuHtml);
                    } else {
                        if( response.menu_type === 'horizontal') {
                            $('#navbar-vertical-view-menu').addClass('d-none');
                             $('.search-bar-nav').addClass('d-none');
                            $('#navbar-desktop-view').html(response.menuHtml);
                        } else {
                             $('.search-icon-nav').addClass('d-none');
                            $('#navbar-vertical-view').html(response.menuHtml);
                        }
                    }
                }
                emnurend();
                $('.hrvmsshowMenu').show(10);
            },
        });
    }
    function emnurend(){
        var activeIndex = -1;
  
            // Find the index of the menu item with active class
            $('.carosel-menu .dropdown-toggle').each(function(index) {
                if ($(this).hasClass('active')) {
                activeIndex = index;
                return false; // Exit the loop once found
                }
            });
            
            
            $('.carosel-menu').slick({
                variableWidth: true,
                slidesToShow: 1,
                infinite: true,
                slidesToScroll: 3,
                initialSlide: activeIndex >= 0 ? activeIndex : 0,
                dots: false,
                focusOnSelect: false,  // on click slide false
                swipe: true
            });

            // Handle active class toggle
            function updateActiveClasses() {
                $('.carosel-menu .text-center').each(function () {
                    if ($(this).hasClass('active')) {
                        $(this).addClass('slick-current slick-active');
                    } else {
                        $(this).removeClass('slick-current slick-active');
                    }
                });
            }

            // Initial call to set classes
            updateActiveClasses();

            // Handle click event to toggle active class
            $('.carosel-menu .text-center').on('click', function () {
                $('.carosel-menu .text-center').removeClass('active');
                $(this).addClass('active');
                updateActiveClasses();
            });

            // Sync Slick changes with active class
            $('.carosel-menu').on('afterChange', function () {
                updateActiveClasses();
            });

            $(".btn-serchbox").click(function () {
                $(".serch-box").addClass("show-serch-box");
            });
            $(".close-icon").click(function () {
                $(".serch-box").removeClass("show-serch-box");
            });
            // Click outside to close the search box
            $(document).mouseup(function (e) {
                var searchBox = $(".serch-box");

                // If the target is not the search box or a child of the search box
                if (!searchBox.is(e.target) && searchBox.has(e.target).length === 0) {
                searchBox.removeClass("show-serch-box");
                }
            });

            function closeAllDropdowns() {
                $(".carosel-nav-menu").each(function () {
                if ($(this).hasClass("show")) {
                    $(this).fadeOut(function () {
                    $(this).removeAttr("style").removeClass("show");
                    });
                }
                });
            }
            // Handle dropdown button click
            $(".carosel-menu .btn-group a").on("click", function (e) {
                // e.preventDefault();

                var $dropdownMenu = $(this).next(".carosel-nav-menu");

                // If the dropdown is already open, close it
                if ($dropdownMenu.hasClass("show")) {
                $dropdownMenu.fadeOut(function () {
                    $dropdownMenu.removeClass("show").removeAttr("style");
                });
                } else {
                // Close any other open dropdowns

                if (!$dropdownMenu.hasClass("moved-out")) {
                    $("body").append($dropdownMenu);
                    $dropdownMenu.addClass("moved-out");
                }
                closeAllDropdowns();

                // Position and show the dropdown
                var offset = $(this).offset();
                var buttonWidth = $(this).outerWidth();

                $dropdownMenu
                    .css({
                    top: offset.top + $(this).outerHeight(),
                    left: offset.left,
                    position: "absolute",
                    "min-width": buttonWidth,
                    "z-index": 9999,
                    })
                    .fadeIn()
                    .addClass("show");
                }
            });

            // Close all dropdowns when clicking outside
            $(document).on("click", function (e) {
                if (
                !$(e.target).closest(".carosel-nav-menu, .carosel-menu .btn-group a")
                    .length
                ) {
                closeAllDropdowns();
                }
            });

            // Prevent dropdown from closing when clicking inside it
            $(document).on("click", ".carosel-nav-menu", function (e) {
                e.stopPropagation();
            });


             // Function to handle navigation and icon toggle
                function toggleNavigation() {
                    if ($("#toggle-check").is(":checked")) {
                    $(".navigation-wrapper").addClass("left-0"); // Show the navigation
                    $(".toggle-icon").addClass("cross-icon"); // Change to cross icon
                    } else {
                    $(".navigation-wrapper").removeClass("left-0"); // Hide the navigation
                    $(".toggle-icon").removeClass("cross-icon"); // Change back to hamburger menu
                    }
                }

                // Listen for checkbox change to toggle navigation and icon
                $("#toggle-check").on("change", function () {
                    toggleNavigation();
                });

                // Handle clicks on the body to close the navigation if it's open
                $(document).on("click", function (event) {
                    if (!$(event.target).closest(".navigation-wrapper, #toggle-icon2").length) {
                    if ($(".navigation-wrapper").hasClass("left-0")) {
                        $("#toggle-check").prop("checked", false); // Uncheck the checkbox
                        toggleNavigation(); // Update the navigation and icon
                    }
                    }
                });

                // Ensure the checkbox and navigation work correctly when clicking directly on the checkbox
                $("#toggle-icon2").on("click", function (event) {
                    // Prevent the click from propagating to document click handler
                    event.stopPropagation();
                });

                // navigation and icon toggle***********************************************************end
                // navigation and icon toggle*************************************************************start

                    const $notificationBtn = $(".notification-btn");
                    const $notificationWrapper = $(".notification-wrapper");

                    // Toggle 'end-0' class when notification button is clicked
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

    }

    // const socket = io("<?php echo e(env('NOTIFICATION_URL', 'http://localhost:8080')); ?>",{
    //     transports: ["websocket"]
    // });

    const socket = io("<?php echo e(env('BASE_URL')); ?>", {transports: ["websocket"]});


    // Register user ID
    const userId = "<?php echo e(Auth::guard('resort-admin')->user()->GetEmployee->id ?? 0); ?>";
    const userType = "employee";
    const panelType = "resort"; // Detect panel type



    socket.on("connect", () => {
        console.log("Connected to socket server. Emitting userId...");
        socket.emit("register-user", userId);

    });

    // Listen for new notifications
    // socket.on("new-resort-notification", (data) => {
    //     console.log("Received WebSocket Notification:", data);

    //     console.log(data.htmlbody);
    //     const notificationHTML = data.htmlbody;

    //     document.querySelector(".notification-body").innerHTML += notificationHTML;
    // });

    socket.on('new-notification', (data) => {
        // console.log(data);
        let htmlview = data.html;
        let sendto = parseInt(data.sendto);
        let ReciverResortId="<?php echo e(Auth::guard('resort-admin')->user()->resort_id); ?>";
        // Check if GetEmployee exists before trying to access its properties
        let RankOfResort = "<?php echo e(isset(Auth::guard('resort-admin')->user()->GetEmployee) ? Auth::guard('resort-admin')->user()->GetEmployee->rank : ''); ?>";
        let User_id = "<?php echo e(isset(Auth::guard('resort-admin')->user()->GetEmployee) ? Auth::guard('resort-admin')->user()->GetEmployee->id : ''); ?>";
        let Dept_id = parseInt("<?php echo e(isset(Auth::guard('resort-admin')->user()->GetEmployee) ? Auth::guard('resort-admin')->user()->GetEmployee->Dept_id : ''); ?>");
        let type = data.type;
        let SenderResortId = data.resortid;
        let PendingDepartment_id = data.PendingDepartment_id;
        // alert(type);
        if(type == 1)
        {
            $(".notification-body").html(htmlview);
        }
        if(type == 2)
        {
            if(SenderResortId == ReciverResortId &&  RankOfResort == "2")
            {
                $(".AppendRequestManningRequest").html(htmlview);
            }
        }
        if(type ==3) // Remainder for Department
        {
            let PendingDepartment_id = data.html.PendingDepartment_id;
            if (Array.isArray(PendingDepartment_id) && PendingDepartment_id.includes(Dept_id)) {

                if (SenderResortId == ReciverResortId && RankOfResort == "2") {
                    $(".AppendRequestManningRequest").html(htmlview);
                }
            }
        }
        if(type == 4) // HOD will send Mainning request based on maning HR dasbhoard to get a response to pading response Department list
        {
            if(SenderResortId == ReciverResortId &&  RankOfResort == "3")
            {
                $(".HrRequestViewCard").html(htmlview);
            }
        }
        if(type  == 5  && SenderResortId == ReciverResortId && RankOfResort == "2")
        {
            $(".AppendRequestManningRequest").html(htmlview);
        }
        if(type == 6 && SenderResortId == ReciverResortId && RankOfResort == "2")
        {
            $(".AppendRequestManningRequest").html(htmlview);
        }
        if(type == 7 && SenderResortId == ReciverResortId && RankOfResort == "3" )
        {
            $("#FreshHiringRequest").html(htmlview);
        }
        if(type == 8 && SenderResortId == ReciverResortId &&  RankOfResort == 7 )
        {
            $("#FreshHiringRequest").html(htmlview);
        }
        if(type == 9  && SenderResortId == ReciverResortId &&  RankOfResort == 8 )
        {
            $("#todoList-main").html(htmlview);
            
        }
        if(type == 10 && SenderResortId == ReciverResortId )
        {
            // console.log(parseInt(User_id) == sendto,parseInt(User_id) , sendto);
            if(parseInt(User_id) == sendto)
            {
                $(".notification-body").html(htmlview);
            }
            else
            {
                $(".notification-body").html(htmlview);
            }
      
        }
    });

    socket.on("receive-message", function (data) {
        console.log("📨 New message received:", data);

        // Ensure that 'userId' is defined
        if (typeof userId === "undefined") {
            console.log("⚠️ Warning: userId is not defined.");
            return;
        }

        let attachments = data.attachments;
        if (typeof attachments === "string") {
            attachments = JSON.parse(attachments); // Convert to array if it's a string
        }
        if (!Array.isArray(attachments)) {
            attachments = []; // Fallback to empty array if null or invalid
        }
        console.log("📨 New message userId:", userId);

        if (data.senderId !== userId) {
            appendMessage({
                message: data.message,
                senderId: data.senderId,
                receiverId: data.receiverId,
                senderName: data.senderName,
                senderImage: data.senderImage,
                receiverName: data.receiverName,
                receiverImage: data.receiverImage,
                attachments: attachments
            }, false , 'employee');
        }
    });
    // socket.on("receive-message", (data) => {
    //     console.log("📨 New message received:", data);

    //     // Ensure that 'userId' is defined
    //     if (typeof userId === "undefined") {
    //         console.log("⚠️ Warning: userId is not defined.");
    //         return;
    //     }

    //     console.log("📨 New message userId:", userId);

    //     if (data.senderId !== userId) {
    //         appendMessage(data, false, 'employee');
    //     }
    // });

    function appendMessage(data, isSender) {
        let position = isSender ? "right" : "";
        let senderName = data.senderName || "Unknown";
        let time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        let senderImage = data.senderImage ? data.senderImage : null;
        let senderInitials = senderName.split(" ").map(n => n.charAt(0)).join("").toUpperCase();
        let imageHtml = senderImage
            ? `<img class="direct-chat-img" src="${senderImage}" alt="user"/>`
            : `<div class="profile-initials direct-chat-img">${senderInitials}</div>`;

        // **Attachments HTML**
        let attachmentsHtml = "";
        if (data.attachments && data.attachments.length > 0) {
            attachmentsHtml = `<div class="attachments">`;
            data.attachments.forEach(file => {
                attachmentsHtml += `
                    <a href="${file}" target="_blank" class="attachment-link">
                        <i class="fa fa-file"></i> ${file.split('/').pop()}
                    </a>
                `;
            });
            attachmentsHtml += `</div>`;
        }

        let chatHtml = "";

        // **Admin Panel HTML**
        if (panelType === "admin") {
            chatHtml = `
                <div class="direct-chat-msg ${position}">
                    <div class="direct-chat-infos clearfix">
                        <span class="direct-chat-name float-${position}">${senderName}</span>
                        <span class="direct-chat-timestamp float-${position === "right" ? "left" : "right"}">${time}</span>
                    </div>
                    ${imageHtml}
                    <div class="direct-chat-text">${data.message || ''} ${attachmentsHtml}</div>
                </div>
            `;
        }
        
        // **Resort Panel HTML**
        else if (panelType === "resort") {
            chatHtml = `
                <div class="chat-msg ${position}">
                    <div class="img-circle">
                        ${imageHtml}
                    </div>
                    <div class="msg">
                        <div class="time">${time}</div>
                        <p>${data.message || ''}</p>
                        ${attachmentsHtml}
                    </div>
                </div>
            `;
        }

        // Append message to both Admin and Resort chat boxes
        $("#chat-messages").append(chatHtml);
    }
</script>

<script type="text/javascript">
    $(document).ready( function() {
        $("#loader").css("display", "none");
        $.ajaxSetup({
            headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $(".select2t-none").select2({
            minimumResultsForSearch: -1,
        });
        $(".switch-toggle").click(function() {
            console.log($(this));
        });

        // Initialize Select2 for the dropdown
        $('#division-select').select2({
            dropdownParent: $('#add-divisionmodal')
        });
        $(".occupancydate").datepicker({
            dateFormat: 'dd-mm-yy'
        });
        var currentDate = new Date();
        $(".occupancydate").datepicker("setDate",currentDate);
        // Register custom validation method
        $.validator.addMethod('greaterThanOrEqual', function(value, element, param) {
            var target = $(param);
            if (!target.length) {
                return true;
            }
            return parseFloat(value) >= parseFloat(target.val());
        }, "Total rooms must be greater than or equal to occupied rooms.");
        $.validator.addMethod('maximum', function(value, element, max) {
            return this.optional(element) || value <= max;
        }, "Value must be less than or equal to {0}.");

        // Validate the form
        $('#AddoccupancyForm').validate({
            rules: {
                occupancydate: {
                    required: true,
                    date: true
                },
                occupancyinPer: {
                    required: true,
                    number: true,
                    maximum: 100,
                },
                occupancytotalRooms: {
                    required: true,
                    digits: true,
                    greaterThanOrEqual: '#occupancyOccupiedRooms'
                },
                occupancyOccupiedRooms: {
                    required: true,
                    digits: true
                }
            },
            messages: {
                occupancydate: {
                    required: "Please select a date.",
                    date: "Please enter a valid date."
                },
                occupancyinPer: {
                    required: "Please enter the occupancy percentage.",
                    number: "Please enter a valid number.",
                    maximum: "Occupancy percentage must be less than or equal to 100."
                },
                occupancytotalRooms: {
                    required: "Please enter the total number of rooms.",
                    digits: "Please enter a valid integer number.",
                    greaterThanOrEqual: "Total rooms must be greater than or equal to occupied rooms."
                },
                occupancyOccupiedRooms: {
                    required: "Please enter the number of occupied rooms.",
                    digits: "Please enter a valid integer number."
                }
            },
            /* submitHandler: function(form) {
                // Form is valid, submit it via AJAX
                $.ajax({
                    url: "<?php echo e(route('resort.occupancy.store')); ?>", // Ensure route is correct
                    type: "POST",
                    data: $(form).serialize(),
                    success: function(response) {
                        if (response.success) {
                            $('#add-occupancymodal').modal('hide');
                            let RoomsAvailable = response.data[0];
                            let OccuplanyPercentage = response.data[1];

                            // Update DOM elements
                            $('.OccuplanyPercentage').html(OccuplanyPercentage);
                            $('#rooms-available').html(RoomsAvailable);

                            // Update CSS and tooltip
                            $('.pie').css({
                                '--p': OccuplanyPercentage // Assuming OccuplanyPercentage is a numeric value
                            });
                            $('.pie').attr('title', OccuplanyPercentage + '% Occupancy');
                            toastr.success(response.msg, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $('#AddoccupancyForm').get(0).reset();
                        } else {
                            toastr.error(response.msg, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function(response) {
                        var errors = response.responseJSON;
                        var errs = '';
                        $.each(errors.errors, function(key, error) { // Adjust according to your response format
                            errs += error + '<br>';
                        });
                        toastr.error(errs, { positionClass: 'toast-bottom-right' });
                    }
                });
            } */

            submitHandler: function(form) {
                // Form is valid, submit it via AJAX
                $.ajax({
                    url: "<?php echo e(route('resort.occupancy.store')); ?>", // Ensure route is correct
                    type: "POST",
                    data: $(form).serialize(),
                    success: function (response) {
                        if (response.success) {
                            $('#add-occupancymodal').modal('hide'); // Close the modal

                            // Fetch updated occupancy data
                            $.ajax({
                                url: "<?php echo e(route('resort.occupancy.getUpdatedData')); ?>", // Replace with your route to fetch updated data
                                type: "GET",
                                success: function (data) {
                                    const updatedId = response.updated_id;
                                    // Destroy the slider if it exists and clear the content
                                    if ($('#occupancy-chart-slider').hasClass('slick-initialized')) {
                                        $('#occupancy-chart-slider').slick('unslick');
                                    }

                                    // Clear the existing content
                                    $('#occupancy-chart-slider').empty();
                                    
                                    // Move updated item to front
                                    if (updatedId) {
                                        const index = data.occupancies.findIndex(oc => oc.id === updatedId);
                                        console.log('index', index);
                                        if (index !== -1) {
                                            const updatedItem = data.occupancies.splice(index, 1)[0];
                                            data.occupancies.unshift(updatedItem);
                                        }
                                    }
                                    // Loop through the updated data and append it to the div
                                    data.occupancies.forEach(function (oc) {
                                        console.log('oc', oc);
                                        const occupancyHtml = `
                                            <div>
                                                <div>
                                                    <div class="d-flex justify-content-center date-slider">

                                                    <a href="#" data-bs-toggle="tooltip">${new Date(oc.occupancydate).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })}</a>
                                                     
                                                    </div>
                                                    <div class="pie my-3" style="--p:${oc.occupancyinPer};--green:#014653;--border:10px" data-bs-toggle="tooltip"
                                                        data-bs-placement="right" title="${oc.occupancyinPer}% Occupancy">
                                                        <div>
                                                            <strong class="d-block">${oc.occupancyinPer}%</strong>
                                                            <span>Occupancy</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card-fotter d-flex justify-content-between">
                                                    <h4>Rooms Available:</h4>
                                                    <label>${oc.occupancytotalRooms - oc.occupancyOccupiedRooms > 0 ? oc.occupancytotalRooms - oc.occupancyOccupiedRooms : 0}</label>
                                                </div>
                                            </div>
                                        `;
                                        $('#occupancy-chart-slider').append(occupancyHtml);
                                    });

                                    // $('#occupancy-chart-slider').slick('unslick'); // Destroy if already initialized
                                    $('#occupancy-chart-slider').slick({
                                        slidesToShow: 1,
                                        slidesToScroll: 1,
                                        dots: false,
                                        arrows: true,
                                        initialSlide: 0 // show the updated record first
                                    });
    
                                    // Reinitialize any plugins if necessary (e.g., tooltips or sliders)
                                    // $('[data-bs-toggle="tooltip"]').tooltip();
                                },
                                error: function () {
                                    toastr.error("Failed to fetch updated occupancy data.", "Error", {
                                        positionClass: 'toast-bottom-right'
                                    });
                                }
                            });

                            toastr.success(response.msg, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                        } else {
                            toastr.error(response.msg, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function(response) {
                        var errors = response.responseJSON;
                        var errs = '';
                        $.each(errors.errors, function(key, error) { // Adjust according to your response format
                            errs += error + '<br>';
                        });
                        toastr.error(errs, { positionClass: 'toast-bottom-right' });
                    }
                });
            }
        });

        $(document).on("click", ".Pending-Department", function () {
            $.ajax({
                url: "<?php echo e(route('resort.pendingDepartment')); ?>", // Ensure route is correct
                type: "GET",
                success: function(response) {
                    if (response.success == true)
                    {

                        $(".PendingDepartmentlist").html(response.PendingDepartmentResoponse);
                    }
                },
                error: function(response) {
                    var errors = response.responseJSON;
                    var errs = '';
                    $.each(errors.errors, function(key, error) { // Adjust according to your response format
                        errs += error + '<br>';
                    });
                    toastr.error(errs, { positionClass: 'toast-bottom-right' });
                }
            });
        });

        //Budget page to Details
        $(document).on('click', '.departmentBudget', function(e) {
            e.preventDefault(); // Prevent the default anchor behavior
            $(this).closest('form').submit(); // Submit the closest form
        });

        $('#cancel-button').on('click', function() {
            $('#AddoccupancyForm')[0].reset();
            $('#AddoccupancyForm').validate().resetForm();
        });

        // Alternatively, you can reset the form when the modal is hidden (close button or background click)
        $('#add-occupancymodal').on('hidden.bs.modal', function() {
            // Reset the form fields and validation when modal closes
            $('#AddoccupancyForm')[0].reset();
            $('#AddoccupancyForm').validate().resetForm();
        });

        // Request For minging
        $('#RequestManning').validate({
            rules: {
                manningRequest: {
                    required: true,
                    // maximum: 700,
                },
            },
            messages: {
                manningRequest: {
                    required: "Please enter the Manning Request MSG.",
                    // maximum: "request massage must be less than  to 700."
                }
            },
            submitHandler: function(form) {
                // Form is valid, submit it via AJAX
                $.ajax({
                    url: "<?php echo e(route('resort.manning.notification')); ?>", // Ensure route is correct
                    type: "POST",
                    data: $(form).serialize(),
                    success: function(response) {
                        if (response.success) {
                            $('#sendRequest-modal').modal('hide');

                            $(".HrRequestViewCard").html(response.html);
                            toastr.success(response.msg, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            // $('#sendRequest-modal').get(0).reset();
                        } else {
                            toastr.error(response.msg, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function(response) {
                        var errors = response.responseJSON;
                        var errs = '';
                        $.each(errors.errors, function(key, error) { // Adjust according to your response format
                            errs += error + '<br>';
                        });
                        toastr.error(errs, { positionClass: 'toast-bottom-right' });
                    }
                });
            }
        });
        // end of mining request

        // Start Reminder Request notfications

        $('#ReminderRequestManning').validate({
            rules: {
                manningRequest: {
                    required: true,
                    // maximum: 700,
                },
            },
            messages: {
                manningRequest: {
                    required: "Please enter the occupancy percentage.",
                    // maximum: "request massage must be less than  to 700."
                }
            },
            submitHandler: function(form) {
                // Form is valid, submit it via AJAX
                $.ajax({
                    url: "<?php echo e(route('resort.reminder.manning.notification')); ?>", // Ensure route is correct
                    type: "POST",
                    data: $(form).serialize(),
                    success: function(response) {
                        if (response.success) {
                            $('#sendReminder-modal').modal('hide');

                            // $(".HrRequestViewCard").html(response.html);
                            toastr.success(response.msg, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            // $('#sendRequest-modal').get(0).reset();
                        } else {
                            toastr.error(response.msg, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    // error: function(response) {
                    //     if (errors && errors.errors) { // Make sure errors object exists
                    //         $.each(errors.errors, function(key, error) {
                    //             errs += error + '<br>';
                    //         });
                    //         toastr.error(errs, { positionClass: 'toast-bottom-right' });
                    //     } else {
                    //         // Handle the case where there are no validation errors or a different response format
                    //         toastr.error("An unexpected error occurred.", { positionClass: 'toast-bottom-right' });
                    //     }
                    // }
                });
            }
        });

        // End of Reminder Request notfications

        // Send To Finance

        $('#SendToFinance').validate({
            rules: {
                year: { required: true },
            },
            messages: {
                year: { required: "Please Select Year." },
            },

            submitHandler: function(form) {

                Swal.fire({
                    title: 'Are you sure?',
                    text: "You are about to send the budget to finance!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, send it!'
                }).then((result) => {

                    if (result.isConfirmed) {

                        $.ajax({
                            url: "<?php echo e(route('resort.SendToFinance.manning.notification')); ?>",
                            type: "POST",
                            data: $(form).serialize(),
                            success: function(response) {

                                if (response.success) {
                                    $('#sendReminder-modal').modal('hide');

                                    $(".SendToFinance").attr('disabled','disabled');
                                    $(".revise-budgetmodal").attr('disabled','disabled');

                                    toastr.success(response.msg, "Success", {
                                        positionClass: 'toast-bottom-right'
                                    });

                                } else {
                                    toastr.error(response.msg, "Error", {
                                        positionClass: 'toast-bottom-right'
                                    });
                                }
                            },

                            error: function(response) {
                                var errors = response.responseJSON;
                                var errs = '';

                                if (errors && errors.errors) {
                                    $.each(errors.errors, function(key, error) {
                                        errs += error + '<br>';
                                    });
                                } else {
                                    errs = 'An unexpected error occurred. Please try again.';
                                }

                                toastr.error(errs, { positionClass: 'toast-bottom-right' });
                            }
                        });

                    }
                });
            }
        });
        // End of Finance

        $('textarea[name="ReviseBudgetComment"]').keydown(function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
            }
        });

        $('#ReviseBudget').validate({
            rules: {
                ReviseBudgetComment: {
                    required: true,
                }
            },
            messages: {
                ReviseBudgetComment: {
                    required: "Please Add Revise Budget Comment.",
                }
            },
            submitHandler: function (form, event) {
                event.preventDefault();

                $.ajax({
                    url: "<?php echo e(route('resort.ReviseBudget.manning.notification')); ?>",
                    type: "POST",
                    data: $(form).serialize(),
                    success: function (response) {
                        if (response.success) {
                            $('#revise-budgetmodal').modal('hide');
                            $(".SendToFinance").prop('disabled', true);
                            $(".revise-budgetmodal").prop('disabled', true);
                            toastr.success(response.msg, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function (response) {
                        let errors = response.responseJSON;
                        let errs = '';

                        if (errors && errors.errors) {
                            $.each(errors.errors, function (key, error) {
                                errs += error + '<br>';
                            });
                        } else {
                            errs = 'An unexpected error occurred. Please try again.';
                        }

                        toastr.error(errs, { positionClass: 'toast-bottom-right' });
                    }
                });
            }
        });


        // End of ReviseBudget
        $('.data-Table').dataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": false,
            "bAutoWidth": false,
            scrollX: true,
            "iDisplayLength": 10,
        });

        // Pusher Notification
        //  Resort Notification
        // Pusher.logToConsole = true;
        // var pusher = new Pusher('55d404203d5a8231840a', {
        // cluster: 'ap2'
        // });

        //     var channel = pusher.subscribe('Resortevent-channel');
        //     channel.bind('ResorteNotification-event', function(data) {

        //         let htmlview = data.html.html;
        //         let ReciverResortId="<?php echo e(Auth::guard('resort-admin')->user()->resort_id); ?>";
        //       // Check if GetEmployee exists before trying to access its properties
        //         let RankOfResort = "<?php echo e(isset(Auth::guard('resort-admin')->user()->GetEmployee) ? Auth::guard('resort-admin')->user()->GetEmployee->rank : ''); ?>";
        //         let Dept_id = parseInt("<?php echo e(isset(Auth::guard('resort-admin')->user()->GetEmployee) ? Auth::guard('resort-admin')->user()->GetEmployee->Dept_id : ''); ?>");
        //         let type = data.html.type;
        //         let SenderResortId = data.html.resortid;
        //         let PendingDepartment_id = data.html.PendingDepartment_id;
        //         console.log(type,PendingDepartment_id,Dept_id);
        //             if(type == 1)
        //             {
        //                 $(".notification-body").html(htmlview);
        //             }
        //             else if(type == 2)
        //             {
        //                 if(SenderResortId == ReciverResortId &&  RankOfResort == "2")
        //                 {
        //                     $(".AppendRequestManningRequest").html(htmlview);
        //                 }
        //             }


        //             else if(type ==3) // Remainder for Department
        //             {
        //                 let PendingDepartment_id = data.html.PendingDepartment_id;
        //                 if (Array.isArray(PendingDepartment_id) && PendingDepartment_id.includes(Dept_id)) {

        //                     if (SenderResortId == ReciverResortId && RankOfResort == "2") {
        //                         $(".AppendRequestManningRequest").html(htmlview);
        //                     }
        //                 }
        //             }
        //             else if(type == 4) // HOD will send Mainning request based on maning HR dasbhoard to get a response to pading response Department list
        //             {
        //                 if(SenderResortId == ReciverResortId &&  RankOfResort == "3")
        //                 {
        //                     $(".HrRequestViewCard").html(htmlview);
        //                 }
        //             }
        //             else if(type  == 5  && SenderResortId == ReciverResortId && RankOfResort == "2")
        //             {
        //                 $(".AppendRequestManningRequest").html(htmlview);
        //             }
        //             else if(type == 6 && SenderResortId == ReciverResortId && RankOfResort == "2")
        //             {
        //                 $(".AppendRequestManningRequest").html(htmlview);
        //             }
        //     });

        // End of notfications
        // End of Pusher Notification
        $(document).on("keyup", "#occupancytotalRooms", function () {
            let totalRooms = $(this).val();
            let occupiedRooms = $('#occupancyOccupiedRooms').val();
            let roomsAvailable = parseFloat(( occupiedRooms/totalRooms) * 100).toFixed(2);
            $('.occupancyinPer').val(roomsAvailable);
        });

        $(document).on("keyup", "#occupancyOccupiedRooms", function () {
            let value = $(this).val();
            // Check if the value is negative or not a number
            if (value < 0 || isNaN(value)) {
                $(this).val(0); // Reset the value to 0
                $('.occupancyinPer').val(0); // Optionally reset another field to 0 if needed
            }
        });

        $('#occupancy-chart-slider').slick({
            // centerMode: true,
            slidesToShow: 1,
            slidesToScroll: 1,
            dots: false,
            arrows: true,
        });
        let selectedOption = $(".ManningBudgetMonthWise").val();
        $(document).on("change", ".ManningBudgetMonthWise", function () {
            let ManningBudgetYearWise = $(this).val();
            triggerManningBudgetMonthWise(ManningBudgetYearWise);
        });

        if (selectedOption) {
            triggerManningBudgetMonthWise(selectedOption);
        }

        function triggerManningBudgetMonthWise(ManningBudgetYearWise) {
            $.ajax({
                url: "<?php echo e(route('resort.ManningBudget.MonthWise')); ?>",
                type: "POST",
                data: { "_token": "<?php echo e(csrf_token()); ?>", ManningBudgetmonthYearWise: ManningBudgetYearWise },
                beforeSend: function () {
                    $('#loader').show();
                },
                success: function (response) {
                    if (response.success) {
                        $(".appendManningBudgetMontly").html(response.html);
                        $(".TotalBudget").html(response.TotalBudgetTotalBudget);
                    } else {
                        // Show error notification if response returns an error
                        toastr.error(response.msg, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function (response) {
                    var errors = response.responseJSON;
                    var errs = '';
                    $.each(errors.errors, function (key, error) {
                        errs += error + '<br>';
                    });
                    // Show the error messages using toastr
                    toastr.error(errs, { positionClass: 'toast-bottom-right' });
                },
                complete: function () {
                    $('#loader').hide();
                }
            });
        }
            
        async function getCurrencyRates(resortId) {
            try {
                const response = await $.ajax({
                    url: "<?php echo e(route('getCurrencyRates', ['resortId' => '__ID__'])); ?>".replace('__ID__', resortId),
                    method: 'GET',
                    dataType: 'json'
                });
                return response;
            } catch (error) {
                console.error('Error fetching conversion rates:', error);
                return { usd_to_mvr: 15.42, mvr_to_usd: 0.065 }; // Default values if error occurs
            }
        }

        async function updateCurrency(currency, resortid) {
            try {
                const response = await $.ajax({
                    url: "<?php echo e(route('sitesetting.UpdateCurrency')); ?>", // Ensure this route is correctly set
                    type: 'POST',
                    data: {
                        currency: currency,
                        resortid: resortid,
                        _token: '<?php echo e(csrf_token()); ?>'
                    }
                });

                if (response.success) {
                    toastr.success('Currency changed to ' + currency,'success', {
                        positionClass: 'toast-bottom-right'
                    });
                    return currency;
                } else {
                    toastr.error(response.msg, 'Error', {
                        positionClass: 'toast-bottom-right'
                    });
                    return null;
                }
            } catch (error) {
                toastr.error('Error updating currency');
                return null;
            }
        }

        $(document).on("click", "#container_currency", async function () {
            let resortid = "<?php echo e(Auth::guard('resort-admin')->user()->resort_id); ?>";
            let currency, conversionRate;
            let currentStep = $("fieldset[style*='visibility: visible']").attr("data-step");
                      
            if ($(this).hasClass('select-left')) {
                currency = 'Dollar';
                $(this).removeClass('select-left').addClass('select-right');
            } else {
                currency = 'MVR';
                $(this).removeClass('select-right').addClass('select-left');
            }

            const updatedCurrency = await updateCurrency(currency, resortid);
           
            if (updatedCurrency) {
                const rates = await getCurrencyRates(resortid);

                var conversionRate1 = updatedCurrency === 'Dollar' ? rates.usd_to_mvr : rates.mvr_to_usd;

                if(currentStep == 4)
                {
                    getstep4data(searchTerm="",updatedCurrency, conversionRate1);
                }
                // console.log(conversionRate);
                else if(currentStep == 5)
                {
                    getstep5data(updatedCurrency, conversionRate1);
                }
                else if(currentStep == 6)
                {
                    getstep6data(updatedCurrency, conversionRate1);
                }
                else if(currentStep == 7)
                {
                    calculatePayrollSummary(updatedCurrency, conversionRate);
                }
                
            } else {
                console.log('Currency update failed.');
            }
        });

        // Add custom validation method for file type
        $.validator.addMethod("fileType", function(value, element, param) {
            const fileExtension = value.split('.').pop().toLowerCase();
            const allowedExtensions = param.split(',');
            return allowedExtensions.includes(fileExtension);
        }, "Please upload a file of type: .xls, .xlsx, or .csv.");

        // Usage
        $('#ImportoccupancyForm').validate({
            rules: {
                importFile: {
                    required: true,
                    fileType: "xls,xlsx,csv" // Specify allowed file types
                }
            },
            messages: {
                importFile: {
                    required: "Please select File.",
                    fileType: "Please upload a file of type: .xls, .xlsx, or .csv."
    
                }
            },
           

            submitHandler: function(form) {
                var formData = new FormData(form); // Use FormData to include file

                $.ajax({
                    url: "<?php echo e(route('resort.occupancy.ImportDatas')); ?>",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        const affectedIds = response.affected_ids || [];
                        if (response.success) {
                            $('#Import-occupancymodal').modal('hide');

                            toastr.success(response.msg, "Success", {
                                positionClass: 'toast-bottom-right'
                            });

                            $('#ImportoccupancyForm')[0].reset();
                             // Fetch all occupancy data again after import
                            $.ajax({
                                url: "<?php echo e(route('resort.occupancy.getUpdatedData')); ?>",
                                type: "GET",
                                success: function(data) {
                                    let updated = [];
                                    let others = [];

                                    data.occupancies.forEach(function(oc) {
                                        if (affectedIds.includes(oc.id)) {
                                            updated.push(oc);
                                        } else {
                                            others.push(oc);
                                        }
                                    });

                                    const finalList = [...updated, ...others];

                                    // Re-render the slider
                                    if ($('#occupancy-chart-slider').hasClass('slick-initialized')) {
                                        $('#occupancy-chart-slider').slick('unslick');
                                    }

                                    $('#occupancy-chart-slider').empty();

                                    finalList.forEach(function(oc) {
                                        const html = `
                                            <div>
                                                <div class="d-flex justify-content-center date-slider">
                                                    <a href="#" data-bs-toggle="tooltip" data-bs-placement="right" >${new Date(oc.occupancydate).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })}</a>
                                                </div>
                                                <div class="pie my-3" style="--p:${oc.occupancyinPer};--green:#014653;--border:10px"
                                                    title="${oc.occupancyinPer}% Occupancy">
                                                    <div>
                                                        <strong class="d-block">${oc.occupancyinPer}%</strong>
                                                        <span>Occupancy</span>
                                                    </div>
                                                </div>
                                                <div class="card-fotter d-flex justify-content-between">
                                                    <h4>Rooms Available:</h4>
                                                    <label>${oc.occupancytotalRooms - oc.occupancyOccupiedRooms > 0 ? oc.occupancytotalRooms - oc.occupancyOccupiedRooms : 0}</label>
                                                </div>
                                                </div>
                                            </div>
                                        `;
                                        $('#occupancy-chart-slider').append(html);
                                    });

                                    $('#occupancy-chart-slider').slick({
                                        slidesToShow: 1,
                                        slidesToScroll: 1,
                                        dots: false,
                                        arrows: true,
                                    });
                                }
                            });
                        } else {
                            toastr.error(response.msg, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function(response) {
                        var errors = response.responseJSON;
                        var errs = '';
                        $.each(errors.errors || {}, function(key, error) {
                            errs += error + '<br>';
                        });
                        toastr.error(errs || 'Something went wrong', {
                            positionClass: 'toast-bottom-right'
                        });
                    }

                });
            }
        });

        $("#importFile").on("change", function(event) {
            var file = event.target.files[0];
            if (file) {
                $("#ImportOccupancy").html(file.name);
                
            }
        });
        $(document).on("click",".MarkNotification",function(){
            id = $(this).data('id');
            $.ajax({
                    url: "<?php echo e(route('resort.Mark.Notification')); ?>",
                    type: "POST",
                    data: {"_token":"<?php echo e(csrf_token()); ?>","id":id},
                  
                    success: function(response) {
                        if (response.success) {
                            
                            $(".class_remove_me_"+id).remove();

                        } else {
                            toastr.error(response.msg, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    }
                });
        });

         $(document).on("click", "#container_menuType", function () {
            let resortid = "<?php echo e(Auth::guard('resort-admin')->user()->resort_id); ?>";
            let menuType;

            if ($(this).hasClass('select-horizontal')) {
                menuType = 'vertical'; 
                $(this).removeClass('select-horizontal').addClass('select-vertical');
            } else {
                menuType = 'horizontal';
                $(this).removeClass('select-vertical').addClass('select-horizontal');
            }
           
            $.ajax({
                url: "<?php echo e(route('resort.update.menu-type')); ?>",
                type: 'POST',
                data: {
                    menuType: menuType,
                    resortid: resortid,
                    _token: '<?php echo e(csrf_token()); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message,'Success', {
                            positionClass: 'toast-bottom-right'
                        });
                        setTimeout(() => {
                            window.location.reload();
                        }, timeout = 1000);
                    } else {
                        toastr.error(response.message, 'Error', {
                            positionClass: 'toast-bottom-right'
                        });
                        
                        $("#container_menuType").toggleClass('select-horizontal');
                        setTimeout(() => {
                            window.location.reload();
                        }, timeout = 1000);
                    }
                },
                error: function(xhr, status, error) {
                    toastr.error('Error updating menu type');
                    $("#container_menuType").toggleClass('select-horizontal');
                }
            });
        });

        $(document).on("keyup", ".search-input", function(e) {
            if (["ArrowUp", "ArrowDown", "ArrowLeft", "ArrowRight", "Enter"].includes(e.key)) {
            return; // Exclude  keys
            }
            var searchTerm = $(this).val();

            if (searchTerm.trim() !== "" && searchTerm.length >= 3) { // Check if search term is not empty
                $.ajax({
                    url: "<?php echo e(route('resort.search.index')); ?>",
                    type: "GET",
                    data: {
                        search_term: searchTerm,
                    },
                    success: function(response) {
                        if (response.success) {
                            $(".search-result").removeClass('d-none')
                            $(".search-result").html(response.html);
                             currentIndex = -1;
                        } else {
                            toastr.error(response.msg, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function(error) {
                        console.error("Error fetching search results:", error);
                    }
                });
            } else {
                $(".search-result").addClass('d-none'); // Clear search results if input is empty
            }
        });


        let currentIndex = -1;

        $(document).on("keydown", function (e) {
            const $results = $(".search-result ul li:visible");

            if (!$results.length || $(".search-result").hasClass("d-none")) return;

            // Only allow ArrowUp, ArrowDown, Enter
            if (!["ArrowUp", "ArrowDown", "Enter"].includes(e.key)) return;

            e.preventDefault();

            if (e.key === "ArrowDown") {
                currentIndex++;
                if (currentIndex >= $results.length) currentIndex = 0;
                highlightResult($results);
            } else if (e.key === "ArrowUp") {
                currentIndex--;
                if (currentIndex < 0) currentIndex = $results.length - 1;
                highlightResult($results);
            } else if (e.key === "Enter") {
                if (currentIndex >= 0) {
                    const $link = $results.eq(currentIndex).find("a");
                    if ($link.length) {
                        window.open($link.attr("href"), "_blank");
                    }
                }
            }
        });

        function highlightResult($results) {
            $results.removeClass("active-li");
            $results.eq(currentIndex).addClass("active-li");
            scrollIntoViewIfNeeded($results.eq(currentIndex)[0]);
        }

        function scrollIntoViewIfNeeded(element) {
            if (element.scrollIntoViewIfNeeded) {
                element.scrollIntoViewIfNeeded();
            } else if (element.scrollIntoView) {
                element.scrollIntoView({ behavior: "smooth", block: "nearest" });
            }
        }
    });
  
</script>
<?php echo $__env->yieldContent('import-scripts'); ?><?php /**PATH C:\wamp64\www\Wisdom-Ai\HRVMS-Wisdom-AI\resources\views/resorts/layouts/js.blade.php ENDPATH**/ ?>