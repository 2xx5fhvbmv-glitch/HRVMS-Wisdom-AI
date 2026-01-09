@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')

    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding">
                <div class="row justify-content-between g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Leave</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <div class="row g-4">
                    <div class="col-xxl-9 col-lg-8 ">
                        <form id="leave-apply" name="leave-apply" method="post" enctype="multipart/form-data">
                            <div class="card">
                                <div class="append-main">
                                    <div class="append-block mb-4">
                                        <div class="row align-items-end g-md-4 g-3 ">
                                            <div class="col-xl-6 col-sm-4">
                                                <label for="leaveCat1" class="form-label">LEAVE CATEGORY<span class="red-mark">*</span></label>
                                                <select class="form-control select2t-none LeaveCate_id" name="leave_category_id[]" id="leaveCat1" aria-label="Default select example" data-parsley-required="true" data-parsley-errors-container="#leave-cat-error">
                                                    <option value="">Select Leave Category</option>
                                                    @if($leave_categories)
                                                        @foreach($leave_categories as $value)
                                                            <option value="{{$value->leave_cat_id}}"
                                                            data-combine-with-other="{{$value->combine_with_other}}" data-leave-category="{{$value->leave_category}}"
                                                            data-used-leaves = "{{$value->total_leave_days}}"
                                                             data-color="{{$value->color}}" data-allowedDays="{{$value->allocated_days}}">{{$value->leave_type}}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                                <div id="leave-cat-error"></div>
                                            </div>
                                            <div class="col-xl-3 col-sm-4 col-6">
                                                <label for="from_date1" class="form-label">FROM DATE<span class="red-mark">*</span></label>
                                                <input type="text" class="form-control datepicker" id="from_date1" placeholder="From Date" name="from_date[]" data-parsley-required="true"  data-parsley-errors-container="#from-date-error1">
                                                <div id="from-date-error1"></div>
                                            </div>
                                            <div class="col-xl-3 col-sm-4 col-6">
                                                {{-- <a href="#" class="close-btn append-close d-none float-end mb-2"><i class="fa-solid fa-xmark"></i></a> --}}
                                                <label for="to_date1" class="form-label">TO DATE<span class="red-mark">*</span></label>
                                                <input type="text" class="form-control datepicker" id="to_date1"
                                                    placeholder="To Date" name="to_date[]" data-parsley-required="true" data-parsley-endgreaterthanstart="#from_date1" data-parsley-errors-container="#to-date-error11">
                                                <div id="to-date-error1"></div>
                                            </div>
                                            <input type="hidden" id="total_days" name="total_days"/>
                                        </div>
                                    </div>
                                    <a href="#" class="btn btn-themeSkyblue btn-sm mb-3 append-add" id="rowAdder">Add Another Leave</a>
                                    <div id="newinput"></div>
                                </div>
                                <div class="row align-items-end g-4 mb-4">
                                    <div class="col-md-12">
                                        <label for="uploadFile" class="form-label">UPLOAD DOCUMENTS</label>
                                        <div class="uploadFile-block">
                                            <div class="uploadFile-btn">
                                                <a href="#" class="btn btn-themeBlue btn-sm">Upload File</a>
                                                <input type="file" id="uploadFile" name="attachments">
                                            </div>
                                            <div class="uploadFile-text">PNG, JPEG, PDF, Word</div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <label for="leaveReason" class="form-label">LEAVE REASON<span class="red-mark">*</span></label>
                                        <textarea class="form-control" rows="3" name="reason" placeholder="Leave Reason" required data-parsley-errors-container="#reason-error"></textarea>
                                        <div id="reason-error"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="taskDel" class="form-label">TASK DELEGATION</label>
                                        <select class="form-select select2t-none" name="task_delegation"  id="taskDel" data-parsley-errors-container="#task_delegation-error">
                                            <option value="">Select Person</option>
                                            @if($delegations)
                                                @foreach($delegations as $emp)
                                                    <option value="{{$emp->id}}">{{$emp->first_name}} {{$emp->last_name}}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <div id="task_delegation-error"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="destination" class="form-label">DESTINATION</label>
                                        <input type="text" name="destination" placeholder="Destination" class="form-control"/>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">TRANSPORTATION  </label>
                                        <div id="transportation-options">
                                            @if($transportations)
                                                @foreach($transportations as $key => $value)
                                                    <div class="form-check form-check-inline">
                                                        <input 
                                                            class="form-check-input transportation-checkbox" 
                                                            type="checkbox" 
                                                            name="transportation[]" 
                                                            value="{{ $key }}" 
                                                            id="transportation-{{ $key }}">
                                                        <label class="form-check-label" for="transportation-{{ $key }}">{{ $value }}</label>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                        <div id="datepickers-container" class="mt-3"></div>
                                    </div>




                                    
                                    <div class="col-12">
                                        <label class="form-label">DEPARTURE PASS </label>
                                        <div class="form-check form-check-inline">
                                            <input 
                                                class="form-check-input departure-checkbox" 
                                                type="radio" 
                                                name="departure" 
                                                value="Yes" 
                                                id="departure">
                                            <label class="form-check-label" for="departure">Yes</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input 
                                                class="form-check-input departure-checkbox" 
                                                type="radio" 
                                                name="departure" 
                                                value="No" 
                                                id="departure" checked>
                                            <label class="form-check-label" for="departure">No</label>
                                        </div>
                                        
                                        <div id="departure-options" class="d-none">
                                            <div class="row gx-xl-5 g-md-4 g-3 mb-4">
                                                <div class="col-lg-6">
                                                    <div class="mb-md-4 mb-3">
                                                        <label for="depDate" class="form-label">DEPARTURE DATE</label>
                                                        <input type="text" class="form-control datepicker" id="depDate" placeholder="DEPARTURE DATE" name="dept_date">
                                                    </div>
                                                    <div class="mb-md-4 mb-3">
                                                        <label for="depTime" class="form-label">DEPARTURE TIME</label>
                                                        <input type="time" class="form-control" id="depTime" placeholder="DEPARTURE TIME" name="dept_time">
                                                    </div>
                                                    <div>
                                                        <label for="" class="form-label">DEPARTURE TRANSPORTATION *</label>
                                                        <div>
                                                            @if($transportations)
                                                                @foreach($transportations as $key => $value)
                                                                    <div class="form-check form-check-inline">
                                                                        <input 
                                                                            class="form-check-input  dept-transportation-checkbox" 
                                                                            type="radio" 
                                                                            name="dept_transportation" 
                                                                            value="{{ $key }}" 
                                                                            id="dept-transportation-{{ $key }}"
                        
                                                                                data-parsley-errors-container="#dept-transportation-error-container">
                                                                        <label class="form-check-label" for="dept-transportation-{{ $key }}">{{ $value }}</label>
                                                                    </div>
                                                                @endforeach
                                                                <div id="dept-transportation-error-container" class="parsley-errors-list"></div>

                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="mb-md-4 mb-3">
                                                        <label for="arrDate" class="form-label">ARRIVAL DATE</label>
                                                        <input type="text" class="form-control datepicker" id="arrDate" placeholder="ARRIVAL DATE" name="arr_date">
                                                    </div>
                                                    <div class="mb-md-4 mb-3">
                                                        <label for="arrTime" class="form-label">ARRIVAL TIME</label>
                                                        <input type="time" class="form-control" id="arrTime" placeholder="ARRIVAL TIME" name="arr_time">
                                                    </div>
                                                    <div>
                                                        <label for="" class="form-label">ARRIVAL TRANSPORTATION *</label>
                                                        <div>
                                                            <div>
                                                                <div class="transportation-options">
                                                                    @foreach($transportations as $key => $value)
                                                                        <div class="form-check form-check-inline">
                                                                            <input 
                                                                                class="form-check-input  arrival-transportation-checkbox"
                                                                                type="radio"
                                                                                name="arrival_transportation"
                                                                                value="{{ $key }}"
                                                                                id="arrival-transportation-{{ $key }}"
                        
                                                                                data-parsley-errors-container="#transportation-error-container">
                                                                            <label class="form-check-label" for="arrival-transportation-{{ $key }}">{{ $value }}</label>
                                                                        </div>
                                                                    @endforeach
                                                                </div>    
                                                                <div id="transportation-error-container" class="parsley-errors-list"></div>

                                                            
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div>
                                                        <label for="reason" class="form-label">REASON</label>
                                                        <textarea class="form-control" id="boarding_pass_reason" name="boarding_pass_reason" placeholder="Reason" rows="4"  data-parsley-departure-or-arrival
                                                        data-parsley-errors-container="#error"
                                                        ></textarea>
                                                    </div>
                                                    <div id="error"></div>
                                                </div>
                                            </div>    
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-themeBlue btn-sm float-end">Submit</button>
                                </div>
                            </div>
                       </form>
                    </div>
                    <div class="col-xxl-3 col-lg-4 ">
                        <div class="card regInclude-card">
                            <div class="card-title">
                                <h3>Your Request Includes</h3>
                            </div>
                            <div class="regInclude-card" id="dynamic-summary">
                                <!-- Dynamic content will be appended here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
<style>
    .is-invalid {
        border-color: #dc3545!important;
    }

    .invalid-feedback {
        color: #dc3545!important;
        display: block!important;
        margin-top: 5px!important;
    }
</style>
@endsection

@section('import-scripts')    
<script> var functioncallyes = 0;  var cat_ids =[];</script>
<script type="text/javascript">
    $(document).ready(function () {
        // Ensure Parsley is loaded
        if (typeof $.fn.parsley !== 'function') {
            console.error('Parsley.js is not loaded correctly');
            return;
        }

        // Initialize the entire form with Parsley
        var $form = $("#leave-apply");
        $form.parsley({
            excluded: 'input[type=button], input[type=submit], input[type=reset]',
            trigger: 'change',
            successClass: 'is-valid',
            errorClass: 'is-invalid'
        });

        $('.dept-transportation-checkbox').parsley({
            errorClass: 'is-invalid',
            successClass: 'is-valid',
            errorsWrapper: '<div class="invalid-feedback"></div>',
            errorTemplate: '<span></span>',
            trigger: 'change'
        });
        $('.arrival-transportation-checkbox').parsley({
            errorClass: 'is-invalid',
            successClass: 'is-valid',
            errorsWrapper: '<div class="invalid-feedback"></div>',
            errorTemplate: '<span></span>',
            trigger: 'change'
        });
    
       
        // Optional: Custom validation message display
        window.Parsley.on('field:error', function() {
            this.$element.closest('.form-group').addClass('has-error');
        });
        
        window.Parsley.on('field:success', function() {
            this.$element.closest('.form-group').removeClass('has-error');
        });
        // Initialize the datepickers
        $('.datepicker').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            startDate: new Date(), // Disable past dates
        }).on('changeDate', function () {
            // Trigger Parsley validation when the date changes
            $(this).parsley().validate();
        });

        $(".select2t-none").select2();

        // Manually trigger Parsley validation when Select2 changes
        $(".select2t-none").on('change', function () {
            var parsleyField = $(this).parsley();
            parsleyField.validate();

            // Add/remove the error class to the Select2 container based on validation
            if (parsleyField.isValid()) {
                $(this).next('.select2-container').find('.select2-selection').removeClass('is-invalid');
            } else {
                $(this).next('.select2-container').find('.select2-selection').addClass('is-invalid');
            }
        });

        // Parsley field validation handler
        window.Parsley.on('field:validated', function (fieldInstance) {
            var $element = fieldInstance.$element;
            if ($element.hasClass('select2t-none')) {
                // Update the Select2 container's appearance
                var $select2Container = $element.next('.select2-container').find('.select2-selection');
                if (fieldInstance.isValid()) {
                    $select2Container.removeClass('is-invalid');
                } else {
                    $select2Container.addClass('is-invalid');
                }
            }
        });

        // Event listener for adding new leave
        $('#leave-apply').on('.datepicker  change', function () {
            updateRequestSummary();
        });

        // When a transportation checkbox is toggled
        $(document).on('change', '.transportation-checkbox', function () {
            const checkbox = $(this);
            const transportId = checkbox.val();
            const transportName = checkbox.next('label').text();
            const datepickerId = `datepicker-${transportId}`;
            const timepickerId = `timepicker-${transportId}`;
            
            if (checkbox.is(':checked')) {
                // Get the leave period (From Date & To Date)
                const fromDateStr = $('[name="from_date[]"]').val();
                const toDateStr = $('[name="to_date[]"]').val();

                if (!fromDateStr || !toDateStr) {
                    toastr.error("Please select leave dates first!", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                    checkbox.prop('checked', false);
                    return;
                }

                // Parse dates (format: dd/mm/yyyy)
                const parseDate = (dateStr) => {
                    const [day, month, year] = dateStr.split('/');
                    return new Date(year, month - 1, day);
                };

                const minDate = parseDate(fromDateStr);
                const maxDate = parseDate(toDateStr);

           
                // Append datepicker inputs dynamically
                $('#datepickers-container').append(`
                    <div id="main-${transportId}">
                        <div class="row mb-3" id="${datepickerId}">
                            <div class="col-md-6">
                                <label class="form-label">Departure Date for ${transportName}</label>
                                <input type="text" class="form-control transport-departure-date" 
                                    name="departure_date[${transportId}]" 
                                    placeholder="Select Departure Date">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Arrival Date for ${transportName}</label>
                                <input type="text" class="form-control transport-arrival-date" 
                                    name="arrival_date[${transportId}]" 
                                    placeholder="Select Arrival Date">
                            </div>
                        </div>
                        <div class="row mb-3" id="timepicker-${transportId}">
                            <div class="col-md-6">
                                <label class="form-label">Departure Time for ${transportName}</label>
                                <input type="time" class="form-control departure-time" name="departure_time[${transportId}]" placeholder="Select Departure Time">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Arrival Time for ${transportName}</label>
                                <input type="time" class="form-control arrival-time" name="arrival_time[${transportId}]" placeholder="Select Arrival Time">
                            </div>
                        </div>
                    </div>
                `);

                    $(`#${datepickerId} .transport-arrival-date, #${datepickerId} .transport-departure-date`).datepicker({
                    format: 'dd/mm/yyyy',
                    autoclose: true,
                    startDate: minDate,  // Cannot select before leave starts
                    endDate: maxDate,    // Cannot select after leave ends
                });
            } else {
                $(`#main-${transportId}`).remove(); // Remove if unchecked
            }
        });


        $(document).on('change', '[name="from_date[]"], [name="to_date[]"]', function() {
            const fromDateStr = $('[name="from_date[]"]').val();
            const toDateStr = $('[name="to_date[]"]').val();

            if (!fromDateStr || !toDateStr) return;

            // Parse dates
            const parseDate = (dateStr) => {
                const [day, month, year] = dateStr.split('/');
                return new Date(year, month - 1, day);
            };

            const minDate = parseDate(fromDateStr);
            const maxDate = parseDate(toDateStr);

            // Update transportation datepickers
            $('.transport-arrival-date, .transport-departure-date').each(function() {
                $(this).datepicker('setStartDate', minDate);
                $(this).datepicker('setEndDate', maxDate);
            });

            // Update departure pass datepickers
            $('#depDate, #arrDate').each(function() {
                $(this).datepicker('setStartDate', minDate);
                $(this).datepicker('setEndDate', maxDate);
            });
        });
            

        $('#datepickers-container').on('change', '.transport-arrival-date, .transport-departure-date', function() {
            const $row = $(this).closest('.row');
            const arrivalDate = $row.find('.transport-arrival-date').val();
            const departureDate = $row.find('.transport-departure-date').val();

            if (arrivalDate && departureDate) {
                const arrival = new Date(arrivalDate.split('/').reverse().join('-'));
                const departure = new Date(departureDate.split('/').reverse().join('-'));

                if (departure > arrival) {
                    toastr.error("Arrival date cannot be before departure date!");
                    $(this).val('').focus();
                }
            }
        });

        // For boarding pass dates
        $('#departure-options').on('change', '#arrDate, #depDate', function() {
            const arrDate = $('#arrDate').val();
            const depDate = $('#depDate').val();

            if (arrDate && depDate) {
                const arrival = new Date(arrDate.split('/').reverse().join('-'));
                const departure = new Date(depDate.split('/').reverse().join('-'));

                if (departure > arrival) {
                    toastr.error("Arrival date cannot be before departure date!");
                    $(this).val('').focus();
                }
            }
        });

        toggleDepartureOptions();

         $('input[name="departure"]').on('change', function () {
            toggleDepartureOptions();
        });

    });

    $(document).on('change', '.LeaveCate_id', function () {
        const selectedCategoryId = $(this).val(); // Get the selected category ID
        let selectedValues = $("select[name='leave_category_id[]']").map(function () {
            return $(this).val();
        }).get();
        
        $.ajax({
            url: "{{ route('leaves.combineInfo.get') }}", // Replace with the correct route URL
            method: 'GET',
            data: { category_id: selectedValues }, // Send selected category IDs
            dataType: 'json',
            success: function (response) {
                console.log(response);
                if (response.status === 'error') {
                    // Process valid response
                    toastr.error(response.message, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                    $(this).val(''); // Re
                }
            },
            error: function (xhr, status, error) {
                if(status == "error" )
                {
                    toastr.error(error.message, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
                
            }
        });
    });

    function toggleDepartureOptions() {
    const selectedVal = $('input[name="departure"]:checked').val();
    const $options = $('#departure-options');

    if (selectedVal === 'Yes') {
        $options.removeClass('d-none');

        // Get leave period
        const fromDateStr = $('[name="from_date[]"]').val();
        const toDateStr = $('[name="to_date[]"]').val();

        if (!fromDateStr || !toDateStr) {
            toastr.error("Please select leave dates first!", "Error", {
                positionClass: 'toast-bottom-right'
            });
            $('input[name="departure"][value="No"]').prop('checked', true);
            $options.addClass('d-none');
            return;
        }

        // Parse dates
        const parseDate = (dateStr) => {
            const [day, month, year] = dateStr.split('/');
            return new Date(year, month - 1, day);
        };

        const minDate = parseDate(fromDateStr);
        const maxDate = parseDate(toDateStr);

        // Initialize datepickers with restricted range
        $('#depDate, #arrDate').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            startDate: minDate,
            endDate: maxDate,
        });
    } else {
        $options.addClass('d-none');
    }
}

    function initSelect2AndValidation() {
        if ($.fn.select2 && $.fn.parsley) {
            // Initialize Select2
            $(".select2t-none").select2();

            // Add Parsley validation specifically for Select2
            $(".select2t-none").on('change', function() {
                $(this).parsley().validate();
            });

            // Ensure Select2 trigger changes in Parsley
            $(".select2t-none").on('select2:select', function() {
                $(this).trigger('change');
            });
        }
    }

    // Function to update the right panel
    function updateRequestSummary() {
        let leaveSummary = '';
        let totalLeaveDays = 0; // Initialize total days
        let isFormValid = true; // Flag to check if form is valid

        $('.append-block').each(function (index) {
            const leaveCategory = $(this).find('[name="leave_category_id[]"] option:selected');
            const leaveCategoryText = leaveCategory.text();
            const leaveCategoryColor = leaveCategory.data('color') || '#cccccc'; // Fallback to default color
            const fromDate = $(this).find('[name="from_date[]"]').val();
            const toDate = $(this).find('[name="to_date[]"]').val();

            // Retrieve allowed and used leaves from data attributes
            const allowedLeaves = parseInt(leaveCategory.data('alloweddays')) || 0; // Default to 0 if not set
            const usedLeaves = parseInt(leaveCategory.data('used-leaves')) || 0; // Default to 0 if not set
            const remainingLeaves = allowedLeaves - usedLeaves;

            if (leaveCategoryText && fromDate && toDate) {
                const { totalDays, formattedRange } = calculateTotalDays(fromDate, toDate);

                totalLeaveDays += totalDays; // Add total days
                const leaveExceedsBalance = totalDays > remainingLeaves; // Check balance

                if (leaveExceedsBalance) {
                    isFormValid = false; // Mark form as invalid
                }

                const warningText = leaveExceedsBalance
                    ? `<span style="color: red;">(Exceeds balance!)</span>`
                    : '';

                leaveSummary += `
                    <div class="regInclude-block" style="border-left: 5px solid ${leaveCategoryColor}; background-color: ${leaveCategoryColor}14;">
                        <div class="d-flex">
                            <h5 style="color: ${leaveCategoryColor};">${leaveCategoryText}</h5>
                            <h5>(${usedLeaves} / ${allowedLeaves})</h5>
                        </div>
                        <div class="d-flex">
                            <h6>${formattedRange}</h6><span>${totalDays} Days</span>
                        </div>
                        <p>Remaining Leave Balance: ${remainingLeaves} ${warningText}</p>
                    </div>
                    <hr class="mt-1 mb-3">
                `;
            }
        });

        leaveSummary += `
            <div class="bg-themeGrayLight">
                <p>Total:</p> <span>${totalLeaveDays}</span>
            </div>
        `;

        $('#dynamic-summary').html(leaveSummary || '<p>No leave requests yet.</p>');

        // Enable or disable the submit button based on form validity
        if (isFormValid) {
            $('button[type="submit"]').prop('disabled', false);
        } else {
            $('button[type="submit"]').prop('disabled', true);
        }
    }

    // Function to adjust opacity of a color
    function adjustOpacity(color, opacity) {
        const hex = color.replace('#', '');
        const bigint = parseInt(hex, 16);
        const r = (bigint >> 16) & 255;
        const g = (bigint >> 8) & 255;
        const b = bigint & 255;
        return `rgba(${r}, ${g}, ${b}, ${opacity})`;
    }

    // Function to calculate total days (basic example)
    function calculateTotalDays(from, to) {
        const fromParts = from.split('/');
        const toParts = to.split('/');
        const fromDate = new Date(fromParts[2], fromParts[1] - 1, fromParts[0]);
        const toDate = new Date(toParts[2], toParts[1] - 1, toParts[0]);

        if (isNaN(fromDate) || isNaN(toDate)) {
            console.error('Invalid date format:', { from, to });
            return {
                totalDays: 0,
                formattedRange: '',
            };
        }

        const totalDays = Math.ceil((toDate - fromDate) / (1000 * 60 * 60 * 24)) + 1;
        const options = { day: '2-digit', month: 'short' };
        const fromFormatted = fromDate.toLocaleDateString('en-GB', options);
        const toFormatted = toDate.toLocaleDateString('en-GB', options);

        return {
            totalDays: totalDays,
            formattedRange: `${fromFormatted} - ${toFormatted}`,
        };
    }

    function initDatePicker() {
        if ($.fn.datepicker) {
            $('.datepicker').datepicker({
                format: 'dd/mm/yyyy',
                autoclose: true
            }).on('changeDate', function () {
                $(this).parsley().validate(); // Trigger validation on date change
            });
        }
    }

    document.getElementById('rowAdder').addEventListener('click', function () {
        // Get the container for adding new inputs
        const container = document.getElementById('newinput');

        // Create a unique identifier for new entries (using Date.now() and a random number to reduce the collision risk)
        const uniqueId = Date.now() + '-' + Math.floor(Math.random() * 1000);

        // HTML template for a new work experience section
        const newRow = `
            <div class="append-block mb-4">
                <div class="row align-items-end g-md-4 g-3" id="leave-row-${uniqueId}">
                    <div class="col-xl-6 col-sm-4">
                        <label for="leaveCat-${uniqueId}" class="form-label">LEAVE CATEGORY*</label>
                        <select class="form-select select2t-none LeaveCate_id" name="leave_category_id[]" id="leaveCat-${uniqueId}" data-parsley-required="true" data-parsley-errors-container="#leave-cat-error-${uniqueId}">
                            <option value="">Select Leave Category</option>
                            @if($leave_categories)
                                @foreach($leave_categories as $value)
                                    <option value="{{$value->leave_cat_id}}" data-allowedDays="{{$value->allocated_days}}" data-used-leaves = "{{$value->total_leave_days}}" data-color="{{$value->color}}">{{$value->leave_type}}</option>
                                @endforeach
                            @endif
                        </select>
                        <div id="leave-cat-error-${uniqueId}"></div>
                    </div>
                    <div class="col-xl-3 col-sm-4 col-6">
                        <label for="from-date-${uniqueId}" class="form-label">FROM DATE*</label>
                        <input type="text" class="form-control datepicker" id="from-date-${uniqueId}" 
                            placeholder="From Date" name="from_date[]" data-parsley-required="true"   data-parsley-errors-container="#from-date-error">
                        <div id="from-date-error"></div>
                    </div>
                    <div class="col-xl-3 col-sm-4 col-6">
                        <a href="#" class="close-btn append-close  float-end mb-2" data-row-id="${uniqueId}"><i class="fa-solid fa-xmark"></i></a>
                        <input type="text" class="form-control datepicker" id="to-date-${uniqueId}" 
                            placeholder="To Date" name="to_date[]" data-parsley-required="true" data-parsley-endgreaterthanstart="#from_date-${uniqueId}"   data-parsley-errors-container="#to-date-error">
                        <div id="to-date-error"></div>
                    </div>
                </div>
            </div>
        `;

        // Append the new row
        container.insertAdjacentHTML('beforeend', newRow);

        initSelect2AndValidation(); 
        initDatePicker();

        $('#leave-apply').parsley().destroy(); 
        $('#leave-apply').parsley({
            errorClass: 'is-invalid',
            successClass: 'is-valid',
            errorsWrapper: '<div class="invalid-feedback"></div>',
            errorTemplate: '<div></div>',
            trigger: 'change'
        });

        $('.alpha-only').on('input', function () {
            this.value = this.value.replace(/[^a-zA-Z ]/g, ''); // Allow only alphabetic characters and spaces
        });

        // Remove functionality
        document.querySelectorAll('.append-close').forEach(button => {
            button.addEventListener('click', function () {
                const rowId = this.getAttribute('data-row-id');
                document.getElementById(`leave-row-${rowId}`).remove();

                // Reinitialize Parsley validation for remaining fields after removal
                setTimeout(() => {
                    $('#leave-apply').parsley(); // Reinitialize Parsley on the entire form
                }, 100);
            });
        });
        
    });
     
    document.addEventListener('DOMContentLoaded', function() {
        function initSelect2AndValidation() {
            if ($.fn.select2 && $.fn.parsley) {
                // Initialize Select2
                $(".select2t-none").select2();

                // Add Parsley validation specifically for Select2
                $(".select2t-none").on('change', function() {
                    $(this).parsley().validate();
                });

                // Ensure Select2 trigger changes in Parsley
                $(".select2t-none").on('select2:select', function() {
                    $(this).trigger('change');
                });
            }
        }

        function initDatePicker() {
            if ($.fn.datepicker) {
                $('.datepicker').datepicker({
                    format: 'dd/mm/yyyy',
                    autoclose: true
                }).on('changeDate', function () {
                    $(this).parsley().validate(); // Trigger validation on date change
                });
            }
        }

        // Initialize Parsley Validation
        function initParsleyValidation() {
            if ($.fn.parsley) {
                // Initialize Parsley on the form
                $('#leave-apply').parsley({
                    errorClass: 'is-invalid',
                    successClass: 'is-valid',
                    errorsWrapper: '<div class="invalid-feedback"></div>',
                    errorTemplate: '<div></div>',
                    trigger: 'change'
                });

                window.Parsley.addValidator('validateScript', {
                    validateString: function(value) {
                        // Pattern to match any <script> tags, even with attributes or content
                        const scriptTagPattern = /<\s*script\b[^>]*>(.*?)<\s*\/\s*script\s*>/gi;
                        return !scriptTagPattern.test(value);  // Return true if no script tags are found, false otherwise
                    },
                    messages: {
                        en: 'Script tags are not allowed.'
                    }
                });

                window.Parsley.addValidator('endgreaterthanstart', {
                    validateString: function (endDateValue, startDateSelector) {
                        const startDateStr = $(startDateSelector).val();
                        const endDate = moment(endDateValue, 'DD/MM/YYYY', true);  // Parse end date
                        const startDate = moment(startDateStr, 'DD/MM/YYYY', true);  // Parse start date

                        // Check if both dates are valid
                        if (!startDate.isValid() || !endDate.isValid()) {
                            return true; // Skip validation if any date is invalid or missing
                        }

                        // Check that the end date is strictly after the start date
                        return endDate.isSame(startDate, 'day') || endDate.isAfter(startDate, 'day');
                    },
                    messages: {
                        en: 'To Date must be greater than From Date.'
                    }
                });

            }
        }

        // Alpha-only Input Handling
        function initAlphaOnlyInputs() {
            $('.alpha-only').on('keyup blur', function() {
                $(this).val($(this).val().replace(/[^a-zA-Z\s]/g, ''));
            });
        }
        
        // Form Submission Handling
        function initFormSubmission() {
            $('#leave-apply').on('submit', function(e) {
                // Prevent default submission
                e.preventDefault();

                // Validate entire form
                const form = $(this);
                if (form.parsley().validate()) {
                    // All validations passed
                    var formData = new FormData(this);

                    // Disable submit button to prevent multiple submissions
                    $('#submit')
                        .prop('disabled', true)
                        .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...');

                    // Ajax submission
                    $.ajax({
                        url: "{{ route('leave-applications.store') }}", // Update with your route
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            console.log(response.status);
                            if(response.status == "success"){
                                // Handle successful submission
                                toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });
                                window.setTimeout(function() {
                                    window.location.href = response.redirect_url;
                                }, 2000);
                            }
                            else{
                                toastr.error(response.message, "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                               
                        },
                        error: function(xhr) {
                            // Handle submission errors
                            var errorMessage = 'An error occurred while submitting your application.';
                            
                            // Check for specific error responses
                            if (xhr.responseJSON && xhr.responseJSON.errors) {
                                // Construct error message from Laravel validation errors
                                errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }

                            // Show error alert
                            toastr.success(errorMessage, "Error", {
                                positionClass: 'toast-bottom-right'
                            });

                            // Re-enable submit button
                            $('#submit')
                                .prop('disabled', false)
                                .html('Submit Application');
                        },
                        complete: function() {
                            // Optional: Any cleanup or final actions
                            // Re-enable submit button if it's still disabled
                            $('#submit')
                                .prop('disabled', false)
                                .html('Submit Application');
                        }
                    });
                }
                else
                    return false; // Stop if validation fails
            });
        }

        // Initialize All Validations and Plugins
        function initializeFormValidation() {
            initSelect2AndValidation();
            initParsleyValidation();
            initDatePicker();
            initAlphaOnlyInputs();
            initFormSubmission();
        }

        // Call initialization when document is ready
        $(document).ready(initializeFormValidation);
        
    });
</script>
@endsection