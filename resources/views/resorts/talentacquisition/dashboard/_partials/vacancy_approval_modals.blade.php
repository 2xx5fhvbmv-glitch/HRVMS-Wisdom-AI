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

<div class="modal fade" id="FreshRespond-modal" tabindex="-1" aria-labelledby="respondModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered rsp-dialog">
        <div class="modal-content rsp-glass">
            <button type="button" class="rsp-x" data-bs-dismiss="modal" aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
            <h5 class="rsp-title" id="respondModalLabel">Respond to request</h5>
            <div class="rsp-sub">Approve, hold, or reject this hire request.</div>
            <div class="respond-main"></div>
            <div class="rsp-actions">
                <a href="#respond-HoldModel" id="holdResponseModel" data-bs-toggle="modal"  data-bs-dismiss="modal" class="rsp-btn rsp-hold">On hold</a>
                <a href="#respond-rejectModal" id="RejectResponseModel" data-bs-toggle="modal" data-bs-dismiss="modal" class="rsp-btn rsp-reject">Reject</a>
                <a href="javascript:void(0)" id="ApprovedResponseModel" data-bs-toggle="modal" data-bs-dismiss="modal" class="rsp-btn rsp-approve">Approve</a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="respond-HoldModel" tabindex="-1" aria-labelledby="holdModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered rsp-dialog">
        <div class="modal-content rsp-glass">
            <button type="button" class="rsp-x" data-bs-dismiss="modal" aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
            <h5 class="rsp-title" id="holdModalLabel">Put request on hold</h5>
            <div class="rsp-sub">Pick a date to revisit this hire request.</div>
            <form id="HoldNewVacanciyForm">
                @csrf
                <div class="hd-lbl">Hold until</div>
                <div class="wcal-card">
                    <div class="wcal-head">
                        <span class="wcal-m" id="holdCalMonth"></span>
                        <div class="wcal-nav">
                            <button type="button" id="holdCalPrev" aria-label="Previous month"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg></button>
                            <button type="button" id="holdCalNext" aria-label="Next month"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg></button>
                        </div>
                    </div>
                    <div class="wcal-grid" id="holdCalGrid"></div>
                </div>
                <div class="hd-selnote" id="holdSelNote">Select a date to hold this request.</div>
                <input type="date" style="display:none" id="HoldDate" name="HoldDate">
                <input type="hidden" id="Calender_ta_id" name="ta_id">
                <div class="rsp-actions">
                    <a href="#" data-bs-dismiss="modal" class="hd-btn hd-cancel">Cancel</a>
                    <button type="submit" class="hd-btn hd-submit">Submit</button>
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
                    <a href="#" data-bs-dismiss="modal" class="btn ta-btn-secondary ms-auto">Cancel</a>
                    <button type="submit"  class="btn ta-btn-primary">Submit</button>
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
                <a href="#" data-bs-dismiss="modal" class="btn ta-btn-secondary">Close</a>
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
    var rspDefaultPhoto = "{{ url(config('settings.default_picture')) }}";
    function rspEsc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function rspInitials(name) {
        var parts = String(name || '').trim().split(/\s+/).slice(0, 2);
        var out = parts.map(function (p) { return p.charAt(0).toUpperCase(); }).join('');
        return out || '?';
    }
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

        // Photo-first avatar, initials fallback: skip the <img> entirely
        // when it's just the app's default silhouette (not a real photo),
        // and still guard with onerror in case a real photo URL is broken.
        var hasPhoto = image && image !== rspDefaultPhoto;
        var initials = rspInitials(createdBy);
        var avatarInner = hasPhoto
            ? '<img src="' + rspEsc(image) + '" alt="' + rspEsc(createdBy) + '" onerror="this.parentNode.textContent=\'' + rspEsc(initials) + '\'">'
            : rspEsc(initials);

        var hm = '<div class="rsp-req">' +
                    '<span class="rsp-av">' + avatarInner + '</span>' +
                    '<div class="rsp-rbody">' +
                        '<div class="rsp-rtop"><span class="rsp-rname">' + rspEsc(createdBy) + '</span><span class="rsp-tag">' + rspEsc(department) + ' &middot; ' + rspEsc(creatorRank) + '</span></div>' +
                        '<div class="rsp-rsub"><span class="rsp-lbl">Requested to hire</span><span class="rsp-rolepill"><span class="rsp-qty">' + rspEsc(NoOfVacnacy) + '</span>' + rspEsc(position) + '</span></div>' +
                    '</div>' +
                 '</div>';
        $(".respond-main").html(hm);
    });

    // Liquid Glass shell: dark blurred scrim (scoped to <body> only while
    // this modal is open — see _respond_modal_styles.blade.php) + the
    // pointer-tracking specular highlight, skipped under
    // prefers-reduced-motion same as the reference.
    $('#FreshRespond-modal').on('show.bs.modal', function () {
        $('body').addClass('rsp-modal-open');
    }).on('hidden.bs.modal', function () {
        $('body').removeClass('rsp-modal-open');
    });
    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.getElementById('FreshRespond-modal').querySelector('.rsp-glass').addEventListener('pointermove', function (e) {
            var r = this.getBoundingClientRect();
            this.style.setProperty('--mx', ((e.clientX - r.left) / r.width * 100) + '%');
            this.style.setProperty('--my', ((e.clientY - r.top) / r.height * 100) + '%');
        });
    }

    // Hold flow — store the child id on the hidden field so the form
    // POST carries the right reference.
    $(document).on("click", "#holdResponseModel", function () {
        var Child_ta_id = $(this).attr('data-Child_ta_id');
        $("#Calender_ta_id").val(Child_ta_id);
    });

    // "Put request on hold" — canonical calendar (resorts._datepicker_calendar_script)
    // + the same Liquid Glass scrim/specular as the Respond modal.
    var isDateSelected = false;
    var HOLD_MONTHS_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    function fmtHoldDate(dateObj) {
        return dateObj.getDate() + ' ' + HOLD_MONTHS_SHORT[dateObj.getMonth()] + ' ' + dateObj.getFullYear();
    }
    var holdCalendar = null;
    $('#respond-HoldModel').on('show.bs.modal', function () {
        $('body').addClass('rsp-modal-open');
        if (!holdCalendar) {
            holdCalendar = window.wisdomDatepicker.create({
                monthEl: document.getElementById('holdCalMonth'),
                gridEl: document.getElementById('holdCalGrid'),
                prevEl: document.getElementById('holdCalPrev'),
                nextEl: document.getElementById('holdCalNext'),
                onSelect: function (isoDate, dateObj) {
                    $('#HoldDate').val(isoDate);
                    isDateSelected = true;
                    $('#holdSelNote').html('Holding until <b>' + fmtHoldDate(dateObj) + '</b>');
                }
            });
        } else {
            holdCalendar.reset();
        }
        isDateSelected = false;
        $('#HoldDate').val('');
        $('#holdSelNote').text('Select a date to hold this request.');
    }).on('hidden.bs.modal', function () {
        $('body').removeClass('rsp-modal-open');
    });
    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.getElementById('respond-HoldModel').querySelector('.rsp-glass').addEventListener('pointermove', function (e) {
            var r = this.getBoundingClientRect();
            this.style.setProperty('--mx', ((e.clientX - r.left) / r.width * 100) + '%');
            this.style.setProperty('--my', ((e.clientY - r.top) / r.height * 100) + '%');
        });
    }

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
