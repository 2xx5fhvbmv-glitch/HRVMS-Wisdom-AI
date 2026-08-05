@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding">
                <div class="row g-3 align-items-center">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Performance</span>
                            <h1>Employees</h1>
                        </div>
                    </div>
                    <div class="col-auto ms-auto">
                        <a href="{{ route('Performance.employees') }}" class="btn perf-btn-secondary btn-sm">
                            <i class="fa-solid fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <div class="row align-items-center g-3">
                        <div class="col-auto">
                            <div class="img-circle">
                                <img src="{{ Common::getResortUserPicture($employee->Admin_Parent_id ?? null) }}" alt="image" style="width:72px;height:72px;border-radius:50%;">
                            </div>
                        </div>
                        <div class="col">
                            <h4 class="mb-1 d-flex align-items-center flex-wrap gap-2">
                                {{ $employee->resortAdmin->full_name ?? '' }}
                                <span class="badge badge-themeNew">#{{ $employee->Emp_id }}</span>
                                @php
                                    $badgeClass = match($status['label']) {
                                        'Done'        => 'badge-themeSuccess',
                                        'In Progress' => 'badge-themeWarning',
                                        default       => 'badge-themeDanger',
                                    };
                                    $headerLabel = $status['label'] === 'Done' ? 'Appraisal Done'
                                        : ($status['label'] === 'In Progress' ? 'Appraisal In Progress' : 'Appraisal Pending');
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $headerLabel }}</span>
                            </h4>
                            <div class="text-muted">{{ $employee->position->position_title ?? '' }}{{ !empty($employee->department->name) ? ' · '.$employee->department->name : '' }}</div>
                            <div class="rating-stars mt-1">
                                @php
                                    $rating = (int) round($status['rating'] ?? 0);
                                    $hasRating = $status['has_rating'] ?? ($rating > 0);
                                @endphp
                                @if($hasRating)
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="fa-{{ $i <= $rating ? 'solid' : 'regular' }} fa-star" style="color:#f5a623;"></i>
                                    @endfor
                                    <span class="small text-muted ms-1">{{ number_format($status['rating'] ?? 0, 1) }}/5</span>
                                @else
                                    <span class="small text-muted">No rating yet</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-auto text-end">
                            @if($latestChildCycleId && ($latestChildReviewRoute ?? null))
                                <a href="{{ route($latestChildReviewRoute, base64_encode($latestChildCycleId)) }}"
                                   class="btn perf-btn-secondary btn-sm mb-1">View Appraisal Form</a>
                            @elseif($latestChildCycleId)
                                <button type="button" class="btn perf-btn-secondary btn-sm mb-1" disabled title="Appraisal form available once the self review is submitted">View Appraisal Form</button>
                            @else
                                <button type="button" class="btn perf-btn-secondary btn-sm mb-1" disabled title="No appraisal cycle assigned">View Appraisal Form</button>
                            @endif
                            @if($activePip)
                                <button type="button" class="btn perf-btn-critical btn-sm mb-1 remove-plan-btn" data-plan-id="{{ $activePip->id }}" data-plan-type="pip">Remove from PIP</button>
                            @endif
                            @if($activePdp)
                                <button type="button" class="btn perf-btn-critical btn-sm mb-1 remove-plan-btn" data-plan-id="{{ $activePdp->id }}" data-plan-type="pdp">Remove from PDP</button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-collapseNew">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Meeting Feedback</th>
                                    <th>Training</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($history as $row)
                                    @php
                                        $mgr = $row->manager_review_data ? json_decode($row->manager_review_data, true) : [];
                                        $feedback = is_array($mgr) ? ($mgr['feedback'] ?? $mgr['summary'] ?? '') : '';
                                        $training = is_array($mgr) ? ($mgr['training'] ?? 'No') : 'No';
                                        $duration = $row->Start_Date && $row->End_Date
                                            ? \Carbon\Carbon::parse($row->Start_Date)->diffInMonths(\Carbon\Carbon::parse($row->End_Date)).' month'
                                            : '-';
                                        if ($row->manager_review_status === 'completed'
                                            || ($row->manager_review_status === 'not_applicable' && $row->self_review_status === 'completed')) {
                                            $statusLabel = 'Done';
                                        } elseif ($row->self_review_status === 'completed') {
                                            $statusLabel = 'In Progress';
                                        } else {
                                            $statusLabel = 'Not Started';
                                        }
                                        $rowBadge = $statusLabel === 'Done' ? 'badge-themeSuccess'
                                            : ($statusLabel === 'In Progress' ? 'badge-themeWarning' : 'badge-themeDanger');
                                    @endphp
                                    <tr>
                                        <td>{{ $row->Manager_review_date ? \Carbon\Carbon::parse($row->Manager_review_date)->format('d M Y') : ($row->created_at ? \Carbon\Carbon::flexible($row->created_at)->format('d M Y') : '-') }}</td>
                                        <td>
                                            @if($feedback)
                                                {{ \Illuminate\Support\Str::limit($feedback, 180) }}
                                            @else
                                                <span class="text-muted">{{ $row->Cycle_Name ?? '—' }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $training }}</td>
                                        <td>{{ $duration }}</td>
                                        <td><span class="badge {{ $rowBadge }}">{{ $statusLabel }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center">No appraisal history found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@include('resorts.Performance._performance_buttons_v2_styles')
@endsection

@section('import-scripts')
<script>
    $(document).on('click', '.remove-plan-btn', function () {
        const planId = $(this).data('plan-id');
        const planType = $(this).data('plan-type'); // 'pip' or 'pdp'
        const label = planType.toUpperCase();
        const baseUrl = planType === 'pip'
            ? "{{ url('resort/Performance/pip') }}"
            : "{{ url('resort/Performance/pdp') }}";

        Swal.fire({
            title: 'Remove from ' + label + '?',
            text: 'This will delete the active ' + label + ' plan for this employee.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, remove'
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: baseUrl + '/' + planId,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function (res) {
                    if (res.success) {
                        toastr.success(res.message || ('Removed from ' + label), 'Success', { positionClass: 'toast-bottom-right' });
                        setTimeout(() => location.reload(), 600);
                    } else {
                        toastr.error(res.message || 'Failed to remove', 'Error', { positionClass: 'toast-bottom-right' });
                    }
                },
                error: function (xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Something went wrong', 'Error', { positionClass: 'toast-bottom-right' });
                }
            });
        });
    });
</script>
@endsection
