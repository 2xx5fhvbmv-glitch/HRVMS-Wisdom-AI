{{-- Saves an .annual-ticket Yes/No answer (leave.annualTickets.store); included once per page. --}}
@once
<script>
document.addEventListener('click', function (e) {
    var btn = e.target.closest('.annual-ticket-answer');
    if (!btn) return;
    var box = btn.closest('.annual-ticket');
    var fd = new FormData();
    fd.append('employee_id', box.dataset.employeeId);
    fd.append('company_provided', btn.dataset.value);
    fetch(@json(route('leave.annualTickets.store')), {method: 'POST', body: fd, headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json'}})
        .then(function (r) { return r.json().then(function (j) { return {ok: r.ok, j: j}; }); })
        .then(function (res) {
            if (!res.ok || !res.j.success) { (window.toastr ? toastr.error : alert)(res.j.message || 'Could not save the answer.'); return; }
            box.querySelectorAll('.annual-ticket-answer').forEach(function (b) { var on = b === btn; b.classList.toggle('active', on); b.setAttribute('aria-pressed', on); });
            box.querySelector('.annual-ticket-meta').textContent = 'Answered just now. You can change it until the cycle ends.';
            box.dispatchEvent(new CustomEvent('annual-ticket-saved', {bubbles: true, detail: res.j.ticket}));
            if (window.toastr) toastr.success(res.j.message);
        })
        .catch(function () { (window.toastr ? toastr.error : alert)('Could not save the answer.'); });
});
</script>
@endonce
