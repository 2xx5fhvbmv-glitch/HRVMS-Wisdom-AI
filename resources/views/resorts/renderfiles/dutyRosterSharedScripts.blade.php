{{-- Shared between Create/View Duty Roster pages — identical on both, kept in
     one place instead of two copies that would drift out of sync. --}}
<script type="text/javascript">
    function initializeDayOffPickerModel() {
        // Clear the input value
        $("#DayOffDatesModel").val('');

        // Destroy existing picker if any
        if(dayOffPickerModel) {
            dayOffPickerModel.destroy();
        }

        // Initialize flatpickr with multiple date selection
        dayOffPickerModel = flatpickr("#DayOffDatesModel", {
            mode: "multiple",
            dateFormat: "Y-m-d",
            inline: false,
            clickOpens: true,
            allowInput: false,
            conjunction: ", ",
            onOpen: function(selectedDates, dateStr, instance) {
                $('#DayOffDatesModel').addClass('active');
            },
            onClose: function(selectedDates, dateStr, instance) {
                $('#DayOffDatesModel').removeClass('active');
            },
            onReady: function(selectedDates, dateStr, instance) {
                instance.calendarContainer.style.zIndex = 10000;
                instance.redraw();
            },
            locale: {
                firstDayOfWeek: 1
            }
        });
    }

    function calculateTotalTime(overtime,DayWiseTotalHours,flag="")
    {
        if(overtime == "" || overtime==0)
        {
            overtime = "00:00";
        }
        if (!/^([0-9]{1,2}):([0-9]{2})$/.test(overtime)) {
            toastr.error("Please enter a valid overtime value in HH:MM format.", "Error", {
                positionClass: 'toast-bottom-right'
            });
            return;
        }

        // Split the overtime input into hours and minutes
        let [hours, minutes] = overtime.split(':');
        hours = parseInt(hours);
        minutes = parseInt(minutes);

        let totalHrs = "";

        if (DayWiseTotalHours !== "")
        {
            // Use provided DayWiseTotalHours (from DB or selected shift)
            totalHrs = DayWiseTotalHours;
        }
        else
        {
            // Fallback: read TotalHours from the correct shift dropdown
            if (flag === "Modal") {
                totalHrs = $("#Shiftpopup").find(":selected").data('totalhrs') || "00:00";
            } else {
                totalHrs = $("#Shift").find(":selected").data('totalhrs') || "00:00";
            }
        }

        // Ensure totalHrs is in HH:MM format (normalize cases like "8" to "08:00")
        if (!/^([0-9]{1,2}):([0-9]{2})$/.test(totalHrs)) {
            let numericHours = parseInt(totalHrs) || 0;
            totalHrs = numericHours.toString().padStart(2, '0') + ':00';
        }

        let [shiftHours, shiftMinutes] = totalHrs.split(':');
        shiftHours = parseInt(shiftHours);
        shiftMinutes = parseInt(shiftMinutes);
        let shiftTotalHrs = shiftHours + (shiftMinutes / 60);
        if ($.isNumeric(hours) && $.isNumeric(minutes) && $.isNumeric(shiftTotalHrs))
        {
            let totalHours = Math.floor(shiftTotalHrs); // Get the hour part
            let totalMinutes = (shiftTotalHrs - totalHours) * 60; // Convert decimal minutes back to actual minutes


            totalHours += hours;
            totalMinutes += minutes;

            // Adjust for overflow of minutes (60 minutes = 1 hour)
            // Round totalMinutes to avoid floating-point precision issues
            totalMinutes = Math.round(totalMinutes);

            if (totalMinutes >= 60) {
                totalHours += Math.floor(totalMinutes / 60);
                totalMinutes = totalMinutes % 60; // Remaining minutes after converting to hours
            }
            // Format the result as "HH:MM"
            let updatedTotalHrs = `${totalHours.toString().padStart(2, '0')}:${totalMinutes.toString().padStart(2, '0')}`;
            // Display the updated total hours
            if(flag == "Modal")
            {
                $("#TotalHoursModelInput").val(updatedTotalHrs);
                $("#TotalHoursModel").html(updatedTotalHrs);
            }
            else
            {
                $("#TotalHoursInput").val(updatedTotalHrs);
                $("#TotalHours").html(updatedTotalHrs);
            }
        }
        else
        {
            toastr.error("Please enter a valid overtime value.", "Error", {
                positionClass: 'toast-bottom-right'
            });
        }
    }
</script>
