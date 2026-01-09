@extends('resorts.layouts.app')
@section('page_tab_title' , $page_title)

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
                            <span>Time And Attendance</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>

                </div>
            </div>
            <div class="card">
                <div class="card-title">
                    <h3>{{ $page_title }}</h3>
                </div>
                <table class="table"  id="HolidayList" >
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Holiday Name</th>
                            <th>Action </th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
    <div class="modal fade" id="PublicHoliday-modal" tabindex="-1" aria-labelledby="PublicHoliday-modalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small modal-PublicHoliday">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Add Public Holiday</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="PublicHolidayForm">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3 align-items-center">
                            <!-- Latitude Input -->
                            <input type="hidden" name="resort_id" value="{{ $resort_id }}">
                            <div class="col-md-12">
                                <select name="PublicHoliday" id="PublicHoliday" class="form-select">
                                    <option value=""  data-id="0">Select Holiday</option>
                                    @foreach ($PublicHoliday as $p )
                                        <option value="{{ $p->id }}"  data-id="{{ $p->id }}" data-date='{{$p->holiday_date}}'>{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12">
                                <input type="text" name="ResortPublicHolidayDate"
                                       id="ResortPublicHolidayDate"
                                       class="form-control" style="display:none" readonly></input>
                            </div>
                            <label class="mx-auto d-block text-center mb-2">OR</label>
                            <div class="col-md-12">
                                <input type="date" name="PublicHolidaydate"   placeholder="Holiday Date" id="PublicHolidaydate" class="form-control" ></input>
                            </div>
                            <div class="col-md-12">
                                <input type="text" name="PublicHolidayName" placeholder="Holiday Name" id="PublicHolidayName" class="form-control" ></input>
                            </div>
                            <div class="col-md-12">
                                <textarea  name="description" placeholder="description" id="description" class="form-control" ></textarea>
                            </div>
                            <input type="hidden" name="id" class="AddPublicHolidays_id" >
                        </div>
                    </div>

                    <div class="modal-footer justify-content-center">
                        <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                        <button type="submit" class="btn btn-theme">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>

$(document).ready(function() {

    $('#HolidayList tbody').empty();
        var HolidayList = $('#HolidayList').DataTable({
        searching: false,
        bLengthChange: false,
        bFilter: true,
        bInfo: true,
        bAutoWidth: false,
        scrollX: true,
        iDisplayLength: 6,
        processing: true,
        serverSide: true,
        order:[[4, 'desc']],
        ajax: {
            url: '{{ route("resort.timeandattendance.publicholidaylist") }}',
            type: 'GET',
        },
        columns: [
            { data: 'PublicHolidaydate', name: 'PublicHolidaydate', className: 'text-nowrap' },
            { data: 'day', name: 'day', className: 'text-nowrap' },
            { data: 'PublicHolidayName', name: 'rank_name', className: 'text-nowrap'},
            { data: 'action', name: 'Action', className: 'text-nowrap'},
            {data:'created_at', visible:false,searchable:false},

        ]
    });

    $.validator.addMethod(
        "requirePublicHolidayNameOrDate",
        function (value, element) {
            // Check if the current field is valid based on the other field's value
            if ($('#PublicHolidayName').val() !== '' && $('#PublicHolidaydate').val() !== '') {
                return false; // Both fields cannot be filled
            }
            return true; // Valid if only one is filled
        },
        "You cannot fill both Holiday Name and Holiday Date. Please choose one."
    );

    $('#PublicHolidayForm').validate({
        rules: {
            PublicHolidaydate: {
                required: function () {
                    // Validate PublicHolidaydate if both other fields are empty
                    return $('#PublicHolidayName').val() === '' && $('#PublicHolidaydate').val() === '';
                }
            },
            PublicHolidayName: {
                required: function () {
                    // Validate PublicHolidayName if PublicHolidaydate is disabled
                    return $('#PublicHolidaydate').is(':disabled');
                }
            },
            PublicHolidaydate: {
                required: function () {
                    // Validate PublicHoliday if PublicHolidaydate is disabled
                    return $('#PublicHolidaydate').is(':disabled');
                }
            }
        },
        messages: {
            PublicHolidaydate: {
                required: "Please select a Holiday Date if Name and Public Holiday are empty."
            },
            PublicHolidayName: {
                required: "Please enter a Holiday Name if Holiday Date is disabled."
            },
            PublicHolidaydate: {
                required: "Please select a Public Holiday if Holiday Date is disabled."
            }
        },
        submitHandler: function (form) {
            var formData = new FormData(form);

            $.ajax({
                url: "{{ route('resort.timeandattendance.ResortHolidayUpdate') }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    $('#respond-HoldModel').modal('hide');
                    if (response.success) {

                        $("#PublicHoliday-modal").modal('hide');

                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(response) {
                    var errors = response.responseJSON;
                    console.log(errors);
                    var errs = '';
                    $.each(errors.errors, function(key, error) { // Adjust according to your response format
                        errs += error + '<br>';
                    });
                    toastr.error(errs, { positionClass: 'toast-bottom-right' });
                }
            });
        }
    });


});



$(document).on("click", ".AddPublicHolidays", function () {
    $("#PublicHoliday-modal").modal('show');
    $("#PublicHoliday").select2();
    let id = $(this).data('id');
    $("#PublicHolidaydate").val($(this).data('publicholidaydate'));
    $("#PublicHolidayName").val($(this).data('publicholidayname'));
    $("#description").val($(this).data('publicholidescription'));

    $(".AddPublicHolidays_id").val(id);


        $("#PublicHoliday").attr("disabled",true);


});
$(document).on("change", "#PublicHoliday", function () {
    let selectedOption = $(this).find(':selected');
    let dataId = selectedOption.data('id');

    if (dataId == 0) {
        $("#PublicHolidaydate").attr('disabled', false);
        $("#PublicHolidayName").attr('disabled', false);
        $("#ResortPublicHolidayDate").val(''); // Clear the date
    } else {
        $("#PublicHolidaydate").attr('disabled', true);
        $("#PublicHolidayName").attr('disabled', true);

        let dates = selectedOption.data('date');
        if (dates) {
            // Ensure the date is in YYYY-MM-DD format
            let formattedDate = '';

            // Handle different date format possibilities
            if (dates.includes('-')) {
                // If already in YYYY-MM-DD format
                formattedDate = dates;
            } else if (dates.includes('/')) {
                // Convert from DD/MM/YYYY to YYYY-MM-DD
                let parts = dates.split('/');
                formattedDate = `${parts[2]}/${parts[1].padStart(2, '0')}/${parts[0].padStart(2, '0')}`;
            }



            $("#ResortPublicHolidayDate").val(formattedDate);
            $("#ResortPublicHolidayDate").show();
        } else {
            $("#ResortPublicHolidayDate").val('');
        }
    }
});

$(document).on("keyup", "#PublicHolidaydate , #PublicHolidayName", function () {

    if($(this).val()!="")
    {
        $("#PublicHoliday").attr('disabled', true);
    }
    else
    {
        $("#PublicHoliday").attr('disabled', false);
    }

});
$(document).on("click", ".delete-row-btn", function () {
    let id = $(this).data('id'); // Get the id of the row to be deleted

    // Confirm with SweetAlert
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, remove it!',
        cancelButtonText: 'No, cancel!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Make the AJAX request to delete the holiday
            $.ajax({
                url: "{{ route('resort.timeandattendance.ResortHolidaydelete') }}", // Ensure the route is correctly set
                type: "POST",
                data: {
                    id: id, // Send the ID to delete
                    _token: "{{ csrf_token() }}" // Send CSRF token for security
                },
                success: function(response) {
                    $('#respond-HoldModel').modal('hide');
                    // Remove the corresponding row from the table
                    $(this).closest(".shift-row").remove();

                    if (response.success) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                    $('#HolidayList').DataTable().ajax.reload();


                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(response) {
                    // Handle errors in case of failure
                    var errors = response.responseJSON;
                    var errs = '';
                    $.each(errors.errors, function(key, error) {
                        errs += error + '<br>';
                    });
                    toastr.error(errs, { positionClass: 'toast-bottom-right' });
                }
            });
        }
    });
});


</script>
@endsection

