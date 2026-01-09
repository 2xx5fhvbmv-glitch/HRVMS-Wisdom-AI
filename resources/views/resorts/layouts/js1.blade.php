<!-- Core Libraries First -->
<script src="{{ URL::asset('resorts_assets/js/jquery-3.6.0.min.js')}}"></script>
<script src="{{ URL::asset('resorts_assets/ckeditor/ckeditor.js')}}"></script>
    <script src="{{ URL::asset('resorts_assets/ckeditor/samples/js/sample.js')}}"></script>
<!-- jQuery Validation -->
<!-- Lazy Loading -->
<script src="{{ URL::asset('resorts_assets/js/jquery.lazy.min.js')}}"></script>
<script src="{{ URL::asset('resorts_assets/js/slick.min.js')}}"></script>
<script src="{{ URL::asset('resorts_assets/js/chart.js')}}"></script>
<!-- DataTables -->
<script src="{{ URL::asset('resorts_assets/js/dataTables.min.js')}}"></script>
<script src="{{ URL::asset('resorts_assets/js/dataTables.bootstrap5.js')}}"></script>
<!-- Google Charts -->
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<!-- Bootstrap and UI Components -->
<script src="{{ URL::asset('resorts_assets/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{ URL::asset('resorts_assets/js/bootstrap-datepicker.min.js')}}"></script>
<script src="{{ URL::asset('resorts_assets/js/select2.min.js')}}"></script>
<script src="{{ URL::asset('resorts_assets/js/moment.min.js')}}"></script>
<script src="{{ URL::asset('resorts_assets/js/fullcalendar.min.js')}}"></script>
<script src="{{ URL::asset('resorts_assets/js/daterangepicker.min.js')}}"></script>
<script src="{{ URL::asset('resorts_assets/js/script.js')}}"></script>
<script src="{{ URL::asset('resorts_assets/js/parsley.min.js')}}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.3/jquery.validate.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="{{ URL::asset('applicant_form_assets/js/croppie.js')}}"></script>
<script src="{{ URL::asset('resorts_assets/additionalJs/swatalart.min.js') }}"></script>
<script src="{{ URL::asset('resorts_assets/additionalJs/sweetalert2.js') }}"></script>
<script src="{{ URL::asset('resorts_assets/js/flatpickr.min.js')}}"></script>
<script type="text/JavaScript" src="https://cdnjs.cloudflare.com/ajax/libs/jQuery.print/1.6.0/jQuery.print.js"></script>
<script>
    var dt_format = "{{Common::getDateAndSetFormateToDatepicker()}}";
</script>
<script src="https://cdn.socket.io/4.0.0/socket.io.min.js"></script>
<script>
    $(window).on('load', function () {
        setTimeout(function () { // allowing 3 secs to fade out loader
            $('.skeleton-wrapper').fadeOut('slow');
        }, 1000);
    });
    const socket = io("http://localhost:3000", {
        transports: ["websocket"]
    });

    // Register user ID
    const userId = "{{ Auth::guard('resort-admin')->user()->GetEmployee->id }}";
    // console.log(userId);
    socket.emit("register-user", userId);

    // Listen for new notifications
    // socket.on("new-resort-notification", (data) => {
    //     console.log("Received WebSocket Notification:", data);

    //     console.log(data.htmlbody);
    //     const notificationHTML = data.htmlbody;

    //     document.querySelector(".notification-body").innerHTML += notificationHTML;
    // });

    socket.on('new-notification', (data) => {
        console.log(data);
        let htmlview = data.html;
        let sendto = parseInt(data.sendto);
        let ReciverResortId="{{  Auth::guard('resort-admin')->user()->resort_id }}";
        // Check if GetEmployee exists before trying to access its properties
        let RankOfResort = "{{ isset(Auth::guard('resort-admin')->user()->GetEmployee) ? Auth::guard('resort-admin')->user()->GetEmployee->rank : '' }}";
        let User_id = "{{ isset(Auth::guard('resort-admin')->user()->GetEmployee) ? Auth::guard('resort-admin')->user()->GetEmployee->id : '' }}";
        let Dept_id = parseInt("{{ isset(Auth::guard('resort-admin')->user()->GetEmployee) ? Auth::guard('resort-admin')->user()->GetEmployee->Dept_id : '' }}");
        let type = data.type;
        let SenderResortId = data.resortid;
        let PendingDepartment_id = data.PendingDepartment_id;
        // alert(type);
        console.log(type,SenderResortId,ReciverResortId);
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
       
        if(type == 10)
        {
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
            submitHandler: function(form) {
                // Form is valid, submit it via AJAX
                $.ajax({
                    url: "{{ route('resort.occupancy.store') }}", // Ensure route is correct
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
            }
        });

        $(document).on("click", ".Pending-Department", function () {
            $.ajax({
                url: "{{ route('resort.pendingDepartment') }}", // Ensure route is correct
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
                    required: "Please enter the occupancy percentage.",
                    // maximum: "request massage must be less than  to 700."
                }
            },
            submitHandler: function(form) {
                // Form is valid, submit it via AJAX
                $.ajax({
                    url: "{{ route('resort.manning.notification') }}", // Ensure route is correct
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
                    url: "{{ route('resort.reminder.manning.notification') }}", // Ensure route is correct
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
                resort_id: {
                    required: true,

                },
                Department_id: {
                    required: true,

                },
                BudgetId: {
                    required: true,

                },
            },
            messages: {
                resort_id: {
                    required: "Please Resort Id not Defined.",

                },
                Department_id: {
                    required: "Please Resort Department Id Missing .",
                },
                BudgetId: {
                    required: "Please Resort Budget Id Missing.",

                },
            },
            submitHandler: function(form) {
                // Form is valid, submit it via AJAX
                $.ajax({
                    url: "{{ route('resort.SendToFinance.manning.notification') }}", // Ensure route is correct
                    type: "POST",
                    data: $(form).serialize(),
                    success: function(response) {
                        if (response.success) {
                            $('#sendReminder-modal').modal('hide');


                            $(".SendToFinance").attr('disabled','disabled');
                            $(".revise-budgetmodal").attr('disabled','disabled');
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
                    error: function(response) {
                        var errors = response.responseJSON;
                        var errs = '';

                        if (errors && errors.errors) {
                            $.each(errors.errors, function(key, error) {
                                errs += error + '<br>';
                            });
                        } else {
                            errs = 'An unexpected error occurred. Please try again.'; // Fallback message if errors object isn't available
                        }

                        toastr.error(errs, { positionClass: 'toast-bottom-right' });
                    }
                });
            }
        });
        // End of Finance

        // ReviseBudget
        $('#ReviseBudget').validate({
            rules: {
                ReviseBudgetComment:{
                    required: true,
                }
            },
            messages: {
                ReviseBudgetComment:{
                    required: "Please Add Revise Budget Comment.",
                }
            },
            submitHandler: function(form) {
                // Form is valid, submit it via AJAX
                $.ajax({
                    url: "{{ route('resort.ReviseBudget.manning.notification') }}", // Ensure route is correct
                    type: "POST",
                    data: $(form).serialize(),
                    success: function(response) {
                        console.log(response)
                        if (response.success == true) {
                            $('#sendReminder-modal').modal('hide');
                            $(".SendToFinance").attr('disabled','disabled');
                            $(".revise-budgetmodal").attr('disabled','disabled');
                            toastr.success(response.msg, "Success", {
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
                            errs = 'An unexpected error occurred. Please try again.'; // Fallback message if errors object isn't available
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
        //         let ReciverResortId="{{  Auth::guard('resort-admin')->user()->resort_id }}";
        //       // Check if GetEmployee exists before trying to access its properties
        //         let RankOfResort = "{{ isset(Auth::guard('resort-admin')->user()->GetEmployee) ? Auth::guard('resort-admin')->user()->GetEmployee->rank : '' }}";
        //         let Dept_id = parseInt("{{ isset(Auth::guard('resort-admin')->user()->GetEmployee) ? Auth::guard('resort-admin')->user()->GetEmployee->Dept_id : '' }}");
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
                url: "{{ route('resort.ManningBudget.MonthWise') }}",
                type: "POST",
                data: { "_token": "{{ csrf_token() }}", ManningBudgetmonthYearWise: ManningBudgetYearWise },
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
                    url: "{{ route('getCurrencyRates', ['resortId' => '__ID__']) }}".replace('__ID__', resortId),
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
                    url: "{{ route('sitesetting.UpdateCurrency') }}", // Ensure this route is correctly set
                    type: 'POST',
                    data: {
                        currency: currency,
                        resortid: resortid,
                        _token: '{{ csrf_token() }}'
                    }
                });

                if (response.success) {
                    toastr.success('Currency changed to ' + currency);
                    return currency;
                } else {
                    toastr.error(response.msg);
                    return null;
                }
            } catch (error) {
                toastr.error('Error updating currency');
                return null;
            }
        }

        $(document).on("click", "#container_currency", async function () {
            let resortid = "{{ Auth::guard('resort-admin')->user()->resort_id }}";
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

        // Usage
        $('#ImportoccupancyForm').validate({
            rules: {
                importFile: {
                    required: true,
                }
            },
            messages: {
                importFile: {
                    required: "Please select File.",
                }
            },
            submitHandler: function(form) {
                var formData = new FormData(form); // Use FormData to include file


                $.ajax({
                    url: "{{ route('resort.occupancy.ImportDatas') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#Import-occupancymodal').modal('hide');

                            toastr.success(response.msg, "Success", {
                                positionClass: 'toast-bottom-right'
                            });

                            $('#ImportoccupancyForm')[0].reset();
                        } else {
                            toastr.error(response.msg, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    }

                    // error: function(response) {
                    //     var errors = response.responseJSON;
                    //     var errs = '';
                    //     $.each(errors.errors, function(key, error) { // Adjust according to your response format
                    //         errs += error + '<br>';
                    //     });
                    //     toastr.error(errs, { positionClass: 'toast-bottom-right' });
                    // }
                });
            }
        });

        $("#importFile").on("change", function(event) {
            var file = event.target.files[0];
            if (file) {
                $("#ImportOccupancy").html(file.name);
                //     var output = document.getElementById("signature_show_img");
                //     output.src = URL.createObjectURL(file);
                //     output.onload = function() {
                //         URL.revokeObjectURL(output.src);
                //     };
            }
        });
    });
</script>
@yield('import-scripts')