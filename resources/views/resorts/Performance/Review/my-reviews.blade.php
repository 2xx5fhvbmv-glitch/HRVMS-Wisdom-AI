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
                        <th>Cycle Name</th>
                        <th>Cycle Period</th>
                        <th>Self Review Window</th>
                        <th>Self Review Status</th>
                        <th>Manager Review Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reviews as $r)
                        <tr>
                            <td><strong>{{ $r->Cycle_Name }}</strong>
                                @if($r->is_gm_review)
                                    <span class="badge badge-themePrimary">GM Review</span>
                                @endif
                            </td>
                            <td>{{ \Carbon\Carbon::parse($r->CycleStart)->format('d M Y') }} - {{ \Carbon\Carbon::parse($r->CycleEnd)->format('d M Y') }}</td>
                            <td>
                                @if(isset($r->window) && $r->window['status'] === 'open')
                                    <span class="badge badge-success">Open until {{ \Carbon\Carbon::parse($r->window['end'])->format('d M Y') }}</span>
                                @elseif(isset($r->window) && $r->window['status'] === 'upcoming')
                                    <span class="badge badge-themeWarning">Opens {{ \Carbon\Carbon::parse($r->window['start'])->format('d M Y') }}</span>
                                @elseif(isset($r->window) && $r->window['status'] === 'closed')
                                    <span class="badge badge-danger">Closed</span>
                                @else
                                    <span class="badge badge-themeLight">No schedule</span>
                                @endif
                            </td>
                            <td>
                                @if($r->self_review_status === 'completed')
                                    <span class="badge badge-success">Completed on {{ \Carbon\Carbon::parse($r->Self_review_date)->format('d M Y') }}</span>
                                @else
                                    <span class="badge badge-themeWarning">Pending</span>
                                @endif
                            </td>
                            <td>
                                @if($r->manager_review_status === 'not_applicable')
                                    <span class="badge badge-themeLight">N/A (External)</span>
                                @elseif($r->manager_review_status === 'completed')
                                    <span class="badge badge-success">Completed on {{ \Carbon\Carbon::parse($r->Manager_review_date)->format('d M Y') }}</span>
                                @else
                                    <span class="badge badge-themeWarning">Pending</span>
                                @endif
                            </td>
                            <td>
                                @if($r->self_review_status === 'pending')
                                    <a href="{{ route('Performance.Review.showSelf', base64_encode($r->id)) }}" class="btn btn-sm perf-btn-primary">Fill Self Review</a>
                                @else
                                    <a href="{{ route('Performance.Review.showSelf', base64_encode($r->id)) }}" class="btn btn-sm perf-btn-secondary">View</a>
                                @endif
                                @if($r->is_gm_review && $r->self_review_status === 'completed')
                                    <a href="{{ route('Performance.Review.gmExport', base64_encode($r->id)) }}" target="_blank" class="btn btn-sm perf-btn-secondary">
                                        <i class="fa-solid fa-download me-1"></i> Export for Corporate
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No reviews assigned to you</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@include('resorts.Performance._performance_buttons_v2_styles')
@endsection
