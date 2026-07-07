{{--
    Vacancy approval modals + JS — shared between the HR / HOD / Admin TA
    dashboards so every rank in the approval chain (Finance, HOD, EXCOM,
    GM, HR) has the same Respond → Hold/Reject/Approve flow.

    Originally lived inline in hrdashboard.blade.php only, which is why
    HOD / EXCOM / Finance users on hoddashboard.blade.php saw only the
    status timeline and no action button.

    The Vacancies card itself (the trigger row) is rendered separately by
    each dashboard so it can sit in the right grid slot; this partial
    contains only the modals + the JS that wires their buttons.
--}}

<div class="modal fade" id="FreshRespond-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Respond data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="respond-main"></div>
            </div>
            <div class="modal-footer justify-content-center">
                <a href="#respond-HoldModel" id="holdResponseModel" data-bs-toggle="modal"  data-bs-dismiss="modal" class="btn btn-themeSkyblue">On Hold</a>
                <a href="#respond-rejectModal" id="RejectResponseModel" data-bs-toggle="modal" data-bs-dismiss="modal" class="btn btn-danger">Reject</a>
                <a href="javascript:void(0)" id="ApprovedResponseModel" data-bs-toggle="modal" data-bs-dismiss="modal" class="btn btn-themeBlue">Approved</a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="respond-HoldModel" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Respond</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="HoldNewVacanciyForm">
                @csrf
                <div class="modal-body">
                    <label class="form-label mb-8">Select date</label>
                    <div class="modalCalendar-block">
                        <div id="calendarModal"></div>
                        <input type="date" style="display:none" id="HoldDate" name="HoldDate">
                        <input type="hidden" id="Calender_ta_id" name="ta_id">
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    <button type="submit" class="btn btn-themeBlue">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="respond-rejectModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Respond</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="rejectionNewVacanciyForm">
                @csrf
                <div class="modal-body">
                    <textarea class="form-control" rows="7" name="New_Vacancy_Rejected" placeholder="Reason for Rejection"></textarea>
                </div>
                <input type="hidden" id="Rejectio_ta_id" name="Rejectio_ta_id">
                <div class="modal-footer justify-content-center">
                    <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    <button type="submit"  class="btn btn-themeBlue">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="respond-approvalModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered  modal-small modal-respondApp">
        <div class="modal-content">
            <div class="modal-header border-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img src="{{ URL::asset('resorts_assets/images/check-circle.svg')}}" alt="icon">
                <h4>submission confirmation</h4>
                <p id="rejaction_msg"></p>
                <a href="#" data-bs-dismiss="modal" class="btn btn-themeBlue">Close</a>
            </div>
        </div>
    </div>
</div>

<script>
// This partial is only included inline when there's at least one pending
// approval (see hoddashboard.blade.php), landing this <script> tag in the
// page body BEFORE the jQuery <script src> tag, which loads near the
// footer (resorts.layouts.js, included after the main content section in
// resorts.layouts.app). `$(document).ready(...)` itself needs `$` to
// exist to be called at all, so referencing it here threw "$ is not
// defined" immediately and the whole click handler below never got
// registered — the Respond button did nothing. Poll with plain JS
// (no jQuery needed) until jQuery has actually loaded, then run the
// exact same code unchanged.
(function waitForJQuery(cb) {
    if (window.jQuery) return cb();
    setTimeout(function () { waitForJQuery(cb); }, 30);
})(function () {
$(document).ready(function() {

    // Open the Respond modal and copy the row's data attributes onto
    // the three action buttons so the subsequent Hold/Reject/Approve
    // handlers know which vacancy + child notification they're acting on.
    $(document).on("click", ".respondOfFreshmodal", function () {
        $('#FreshRespond-modal').modal('show');
        var image       = $(this).attr("data-images");
        var position    = $(this).attr("data-position");
        var department  = $(this).attr("data-departmentname");
        var NoOfVacnacy = $(this).attr("data-NoOfVacnacy");
        var rank        = $(this).attr('data-rank');
        var ta_id       = $(this).attr('data-ta_id');
        var Child_ta_id = $(this).attr('data-Child_ta_id');
        var createdBy   = $(this).attr('data-createdby');
        var creatorRank = $(this).attr('data-creatorrank');

        $("#holdResponseModel").attr("data-ta_id", ta_id);
        $("#RejectResponseModel").attr("data-ta_id", ta_id);
        $("#ApprovedResponseModel").attr("data-ta_id", ta_id);
        $("#ApprovedResponseModel").attr("data-Child_ta_id", Child_ta_id);
        $("#holdResponseModel").attr("data-Child_ta_id", Child_ta_id);
        $("#RejectResponseModel").attr("data-Child_ta_id", Child_ta_id);

        var hm = '<div class="respond-block">' +
                    '<div class="img-circle">' +
                        '<img src="' + image + '" alt="image">' +
                    '</div>' +
                    '<div>' +
                        '<h6>' + department + ' (' + rank + ')</h6>' +
                        '<p><strong>' + createdBy + ' (' + creatorRank + ')</strong> Requested for Hire ' + NoOfVacnacy + ' ' + position + '</p>' +
                    '</div>' +
                 '</div>';
        $(".respond-main").html(hm);
    });

    // Hold flow — store the child id on the hidden field so the form
    // POST carries the right reference.
    $(document).on("click", "#holdResponseModel", function () {
        var Child_ta_id = $(this).attr('data-Child_ta_id');
        $("#Calender_ta_id").val(Child_ta_id);
    });

    $('#HoldNewVacanciyForm').validate({
        rules:    { HoldDate: { required: true } },
        messages: { HoldDate: { required: "Please select Hold Date." } },
        submitHandler: function (form) {
            var formData = new FormData(form);
            if (typeof isDateSelected !== 'undefined' && !isDateSelected) {
                toastr.error("Please select a date from the calendar.", "Error", { positionClass: 'toast-bottom-right' });
                return false;
            }
            $.ajax({
                url: "{{ route('resort.ta.HiringNotification') }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    $('#respond-HoldModel').modal('hide');
                    if (response.success) {
                        $("#FreshHiringRequest").html(response.view);
                        toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                    } else {
                        toastr.error(response.message, "Error", { positionClass: 'toast-bottom-right' });
                    }
                }
            });
        }
    });

    // Reject flow
    $(document).on("click", "#RejectResponseModel", function () {
        var Child_ta_id = $(this).attr('data-Child_ta_id');
        $("#Rejectio_ta_id").val(Child_ta_id);
    });

    $('#rejectionNewVacanciyForm').validate({
        rules:    { New_Vacancy_Rejected: { required: true } },
        messages: { New_Vacancy_Rejected: { required: "Please Enter Reason." } },
        submitHandler: function (form) {
            var formData = new FormData(form);
            $.ajax({
                url: "{{ route('resort.ta.RejectionVcancies') }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    $('#respond-rejectModal').modal('hide');
                    if (response.success) {
                        $("#FreshHiringRequest").html(response.view);
                        toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                    } else {
                        toastr.error(response.message, "Error", { positionClass: 'toast-bottom-right' });
                    }
                }
            });
        }
    });

    // Approve flow — fire-and-forget POST, then show the confirmation
    // modal and refresh the Fresh Hiring Request list with the server's
    // updated HTML.
    $(document).on("click", "#ApprovedResponseModel", function () {
        var ta_id       = $(this).attr('data-ta_id');
        var Child_ta_id = $(this).attr('data-Child_ta_id');
        $.ajax({
            url: "{{ route('resort.ta.ApprovedVcancies') }}",
            type: "POST",
            data: { ta_id: ta_id, Child_ta_id: Child_ta_id, "_token": "{{ csrf_token() }}" },
            success: function (response) {
                $('#respond-rejectModal').modal('hide');
                if (response.success) {
                    $('#respond-approvalModal').modal('show');
                    $("#FreshHiringRequest").html(response.view);
                    if (typeof response.Todolistview !== 'undefined') {
                        $(".todoList-main").html(response.Todolistview);
                    }
                    toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                } else {
                    toastr.error(response.message, "Error", { positionClass: 'toast-bottom-right' });
                }
            },
            error: function (response) {
                var errors = response.responseJSON;
                var errs = '';
                if (errors && errors.errors) {
                    $.each(errors.errors, function (key, error) { errs += error + '<br>'; });
                }
                toastr.error(errs, { positionClass: 'toast-bottom-right' });
            }
        });
    });

});
}); // end waitForJQuery
</script>
