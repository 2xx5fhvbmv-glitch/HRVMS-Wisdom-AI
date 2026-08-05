@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Performance</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <table class="table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Cycle Name</th>
                        <th>Cycle Period</th>
                        <th>Self Review</th>
                        <th>Manager Review</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reviews as $r)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ $r->employee_profile }}" style="width:36px;height:36px;border-radius:50%;" alt="">
                                    <div>
                                        <div><strong>{{ $r->employee_name }}</strong></div>
                                        <small class="text-muted">{{ $r->employee_position }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $r->Cycle_Name }}</td>
                            <td>{{ \Carbon\Carbon::parse($r->CycleStart)->format('d M Y') }} - {{ \Carbon\Carbon::parse($r->CycleEnd)->format('d M Y') }}</td>
                            <td>
                                @if($r->self_review_status === 'completed')
                                    <span class="badge badge-success">Completed</span>
                                @else
                                    <span class="badge badge-themeWarning">Pending</span>
                                @endif
                            </td>
                            <td>
                                @if($r->manager_review_status === 'completed')
                                    <span class="badge badge-success">Completed</span>
                                @else
                                    <span class="badge badge-themeWarning">Pending</span>
                                @endif
                            </td>
                            <td>
                                @if($r->self_review_status === 'completed')
                                    @if($r->manager_review_status === 'pending')
                                        <a href="{{ route('Performance.Review.showManager', base64_encode($r->id)) }}" class="btn btn-sm perf-btn-primary">Fill Manager Review</a>
                                    @else
                                        <a href="{{ route('Performance.Review.showManager', base64_encode($r->id)) }}" class="btn btn-sm perf-btn-secondary">View</a>
                                    @endif
                                @else
                                    <span class="text-muted small">Waiting for self review</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No team reviews assigned</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@include('resorts.Performance._performance_buttons_v2_styles')
@endsection
