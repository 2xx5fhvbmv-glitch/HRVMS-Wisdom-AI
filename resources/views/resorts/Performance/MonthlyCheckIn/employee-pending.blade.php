@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Performance</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>

        @if($pending->isEmpty())
            <div class="card"><div class="card-body text-center text-muted">No pending check-in requests.</div></div>
        @else
            <div class="row g-3">
                @foreach($pending as $p)
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="mb-2">Monthly Check-In <span class="badge badge-themeWarning">Pending</span></h5>
                                <div class="mb-1"><strong>Date:</strong> {{ \Carbon\Carbon::parse($p->date_discussion)->format('d M Y') }}</div>
                                <div class="mb-1"><strong>Time:</strong> {{ $p->start_time }} - {{ $p->end_time }}</div>
                                <div class="mb-2"><strong>Meeting Place:</strong> {{ $p->Meeting_Place }}</div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn perf-btn-positive btn-sm approve-btn" data-id="{{ $p->id }}">Approve</button>
                                    <button type="button" class="btn perf-btn-critical btn-sm reject-btn" data-id="{{ $p->id }}">Reject</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="rejectForm">
            @csrf
            <input type="hidden" name="checkin_id" id="rejectCheckinId">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Check-In</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control" rows="4" required placeholder="Please explain why you are rejecting this check-in"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn perf-btn-neutral btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn perf-btn-critical btn-sm">Submit Rejection</button>
                </div>
            </div>
        </form>
    </div>
</div>
@include('resorts.Performance._performance_buttons_v2_styles')
@endsection

@section('import-scripts')
<script>
$(document).on('click', '.approve-btn', function() {
    const id = $(this).data('id');
    Swal.fire({
        title: 'Approve this check-in?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, approve'
    }).then((result) => {
        if (!result.isConfirmed) return;
        $.ajax({
            url: "{{ url('resort/performance/monthly-check-in/employee-approve') }}/" + id,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                    setTimeout(() => location.reload(), 600);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to approve', 'Error', { positionClass: 'toast-bottom-right' });
            }
        });
    });
});

$(document).on('click', '.reject-btn', function() {
    $('#rejectCheckinId').val($(this).data('id'));
    $('#rejectForm')[0].reset();
    $('#rejectCheckinId').val($(this).data('id'));
    $('#rejectModal').modal('show');
});

$(document).on('submit', '#rejectForm', function(e) {
    e.preventDefault();
    const id = $('#rejectCheckinId').val();
    const reason = $('[name="reason"]', this).val();
    $.ajax({
        url: "{{ url('resort/performance/monthly-check-in/employee-reject') }}/" + id,
        type: 'POST',
        data: { _token: '{{ csrf_token() }}', reason: reason },
        success: function(res) {
            if (res.success) {
                $('#rejectModal').modal('hide');
                toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                setTimeout(() => location.reload(), 600);
            }
        },
        error: function(xhr) {
            const errs = xhr.responseJSON?.errors;
            if (errs) {
                let msg = '';
                $.each(errs, function(k, v) { msg += v + '<br>'; });
                toastr.error(msg, 'Validation Error', { positionClass: 'toast-bottom-right' });
            } else {
                toastr.error(xhr.responseJSON?.message || 'Failed to reject', 'Error', { positionClass: 'toast-bottom-right' });
            }
        }
    });
});
</script>
@endsection
