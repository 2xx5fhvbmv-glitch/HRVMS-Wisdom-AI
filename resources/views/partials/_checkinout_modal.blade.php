{{--
    Shared Check-In / Check-Out confirmation modal — used by every Time &
    Attendance "To Do List" widget (todolist.blade.php, hoddashboard.blade.php
    [HOD+EXCOM], hrdashboard.blade.php). One partial, one JS entry point
    (openCheckInOutModal), so every role sees the identical redesigned dialog.

    Frontend/presentation only: this REPLACES the wisdomConfirm() SweetAlert
    call that previously wrapped this same confirm+time-input step — the
    submit itself (POST to ManualCheckInOut with roster_id/action/date/time)
    is unchanged, still owned by each page's own click handler.

    Classes are prefixed (.ciom-*) rather than reusing the reference's bare
    names (.modal, .field, .emp) — this app already has global CSS under
    those exact bare names (.modal is Bootstrap's own), so bare names would
    silently collide.
--}}
<div class="ciom-backdrop" id="ciomBackdrop">
    <div class="ciom-modal" role="dialog" aria-modal="true">
        <button type="button" class="ciom-x" id="ciomClose" aria-label="Close">&times;</button>
        <div class="ciom-ic">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
        </div>
        <div class="ciom-title" id="ciomTitle">Confirm Check-In</div>
        <div class="ciom-sub" id="ciomSub">Record the check-in for this employee at the time below.</div>

        <div class="ciom-emp">
            <div class="ciom-av"><img id="ciomEmpImg" src="" alt=""></div>
            <div class="ciom-info">
                <div class="ciom-nm" id="ciomEmpName">&nbsp;</div>
                <div class="ciom-sh" id="ciomEmpShift">&nbsp;</div>
            </div>
        </div>

        <div class="ciom-field-wrap">
            <div class="ciom-field-label" id="ciomFieldLabel">Check-in time</div>
            <input type="text" class="ciom-time-input" id="ciomTimeInput" autocomplete="off">
        </div>

        <div class="ciom-foot">
            <button type="button" class="ciom-btn-secondary" id="ciomCancel">Cancel</button>
            <button type="button" class="ciom-btn-primary" id="ciomConfirm">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6L9 17l-5-5"/></svg>
                <span id="ciomConfirmText">Yes, Check-In</span>
            </button>
        </div>
    </div>
</div>
<style>
    .ciom-backdrop { position: fixed; inset: 0; background: rgba(20,35,42,.42); display: flex; align-items: center; justify-content: center; padding: 24px; z-index: 1070; opacity: 0; pointer-events: none; transition: opacity .18s ease; }
    .ciom-backdrop.open { opacity: 1; pointer-events: auto; }
    .ciom-modal { width: min(400px, 100%); background: #fff; border-radius: 20px; box-shadow: 0 30px 80px rgba(0,0,0,.3); padding: 30px 28px 24px; text-align: center; position: relative; font-family: 'Poppins', sans-serif; transform: translateY(10px) scale(.98); transition: transform .18s ease; }
    .ciom-backdrop.open .ciom-modal { transform: translateY(0) scale(1); }
    .ciom-x { position: absolute; top: 16px; right: 16px; width: 30px; height: 30px; border-radius: 50%; background: transparent; border: none; color: var(--faint, #99A1A5); cursor: pointer; display: grid; place-items: center; font-size: 15px; transition: .15s; }
    .ciom-x:hover { background: var(--line-2, #EEF4F4); color: var(--ink, #14232A); }
    .ciom-ic { width: 56px; height: 56px; border-radius: 50%; background: var(--teal-3, #E6F0F1); color: var(--teal, #014653); display: grid; place-items: center; margin: 0 auto 16px; }
    .ciom-title { font-size: 18px; font-weight: 600; color: var(--ink, #14232A); letter-spacing: -.01em; }
    .ciom-sub { font-size: 13px; color: var(--muted, #6B7378); margin-top: 8px; line-height: 1.5; max-width: 300px; margin-left: auto; margin-right: auto; }

    .ciom-emp { display: flex; align-items: center; gap: 10px; justify-content: center; margin: 18px 0 4px; padding: 10px 14px; background: var(--line-2, #EEF4F4); border-radius: 12px; }
    .ciom-av { width: 32px; height: 32px; border-radius: 50%; background: var(--line, #E2EBEC); flex: none; overflow: hidden; }
    .ciom-av img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .ciom-info { text-align: left; min-width: 0; }
    .ciom-nm { font-size: 13px; font-weight: 600; color: var(--ink, #14232A); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ciom-sh { font-size: 11.5px; color: var(--muted, #6B7378); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    .ciom-field-wrap { margin-top: 18px; text-align: left; }
    .ciom-field-wrap .flatpickr-wrapper { display: block; width: 100%; }
    .ciom-field-label { font-size: 9.5px; font-weight: 600; letter-spacing: .5px; text-transform: uppercase; color: var(--faint, #99A1A5); margin-bottom: 6px; }
    .ciom-time-input { width: 100%; height: 44px; border: 1px solid var(--line-3, #C7CDCF); border-radius: 12px; padding: 0 14px; font-family: inherit; font-size: 14px; color: var(--ink, #14232A); background: #fff; cursor: pointer; transition: .15s; text-align: center; }
    .ciom-time-input:hover, .ciom-time-input:focus { border-color: var(--teal, #014653); outline: none; }

    .ciom-foot { display: flex; gap: 10px; margin-top: 22px; }
    .ciom-btn-secondary { flex: 1; height: 44px; border: 1px solid var(--line, #E2EBEC); border-radius: 12px; background: #fff; color: var(--ink, #14232A); font-family: inherit; font-size: 13.5px; font-weight: 500; cursor: pointer; transition: .15s; }
    .ciom-btn-secondary:hover { border-color: var(--line-3, #C7CDCF); background: var(--line-2, #EEF4F4); }
    .ciom-btn-primary { flex: 1.3; height: 44px; border: none; border-radius: 12px; background: var(--teal, #014653); color: #fff; font-family: inherit; font-size: 13.5px; font-weight: 500; cursor: pointer; transition: .15s; display: flex; align-items: center; justify-content: center; gap: 8px; }
    .ciom-btn-primary:hover { background: var(--teal-2, #035b6c); }
    .ciom-btn-primary:disabled { opacity: .6; cursor: not-allowed; }
    .ciom-btn-primary.is-checkout { background: var(--warning, #D98A00); }
    .ciom-btn-primary.is-checkout:hover { background: #c17c00; }
</style>
<script>
    // openCheckInOutModal(opts) — the one entry point every To Do List widget
    // calls instead of wisdomConfirm(). opts: { action:'check_in'|'check_out',
    // employeeName, shiftLabel, time (H:i or h:i A), onConfirm(selectedTime) }.
    // Uses the app's already-themed flatpickr time UI for the time field
    // (reused, not reimplemented) rather than hand-rolling a second picker.
    (function () {
        var backdrop = document.getElementById('ciomBackdrop');
        var fp = null;
        function ensureFp() {
            if (fp || !window.flatpickr) return;
            fp = flatpickr('#ciomTimeInput', {
                enableTime: true, noCalendar: true, dateFormat: 'H:i', altInput: true, altFormat: 'h:i K',
                time_24hr: false, minuteIncrement: 1, allowInput: true, static: true
            });
        }
        function close() { backdrop.classList.remove('open'); }
        window.openCheckInOutModal = function (opts) {
            opts = opts || {};
            var isCheckIn = opts.action === 'check_in';
            document.getElementById('ciomTitle').textContent = isCheckIn ? 'Confirm Check-In' : 'Confirm Check-Out';
            document.getElementById('ciomSub').textContent = 'Record the ' + (isCheckIn ? 'check-in' : 'check-out') + ' for this employee at the time below.';
            document.getElementById('ciomFieldLabel').textContent = isCheckIn ? 'Check-in time' : 'Check-out time';
            document.getElementById('ciomEmpName').textContent = opts.employeeName || '';
            document.getElementById('ciomEmpShift').textContent = opts.shiftLabel || '';
            document.getElementById('ciomEmpImg').src = opts.employeeImage || '';
            document.getElementById('ciomConfirmText').textContent = 'Yes, ' + (isCheckIn ? 'Check-In' : 'Check-Out');
            var confirmBtn = document.getElementById('ciomConfirm');
            confirmBtn.classList.toggle('is-checkout', !isCheckIn);
            confirmBtn.disabled = false;

            ensureFp();
            if (fp) fp.setDate(opts.time || null, true, 'H:i');

            var onConfirm = function () {
                confirmBtn.disabled = true;
                opts.onConfirm && opts.onConfirm(document.getElementById('ciomTimeInput').value, function reset() {
                    confirmBtn.disabled = false;
                }, close);
            };
            confirmBtn.onclick = onConfirm;
            backdrop.classList.add('open');
        };
        document.getElementById('ciomClose').addEventListener('click', close);
        document.getElementById('ciomCancel').addEventListener('click', close);
        backdrop.addEventListener('click', function (e) { if (e.target === backdrop) close(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
    })();
</script>
