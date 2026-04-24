@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row g-3 justify-content-between">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Performance</span>
                        <h1>Cycles</h1>
                    </div>
                </div>
                <div class="col-auto d-flex gap-2">
                    <a href="{{ route('Performance.cycle.analytics', base64_encode($cycle->id)) }}" class="btn btn-theme">
                        View Analytics
                    </a>
                    <a href="{{ route('Performance.cycle') }}" class="btn btn-themeGray">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="cycle-header mb-4">
                <h3 class="mb-2">
                    {{ $cycle->Cycle_Name }}
                    @if($cycle->status == 'Pending')
                        <span class="badge badge-themeWarning">Pending</span>
                    @elseif($cycle->status == 'OnGoing')
                        <span class="badge badge-success">Ongoing</span>
                    @else
                        <span class="badge badge-themeLight">{{ $cycle->status }}</span>
                    @endif
                </h3>
                <p class="text-muted mb-1">
                    <i class="fa-regular fa-user me-1"></i> {{ $totalEmployees }} {{ $totalEmployees == 1 ? 'Employee' : 'Employees' }}
                </p>
                <p class="text-muted mb-0" style="font-size:13px;">
                    <i class="fa-regular fa-calendar me-1"></i>
                    {{ \Carbon\Carbon::parse($cycle->Start_Date)->format('d M Y') }} - {{ \Carbon\Carbon::parse($cycle->End_Date)->format('d M Y') }}
                </p>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="progress-box">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="progress-label">Self Reviews</span>
                            <span class="progress-value">{{ $selfPct }}%</span>
                        </div>
                        <div class="progress" style="height:8px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $selfPct }}%"></div>
                        </div>
                        <small class="text-muted">{{ $selfCompleted }} of {{ $totalEmployees }} completed</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="progress-box">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="progress-label">Manager Reviews</span>
                            <span class="progress-value">{{ $managerPct }}%</span>
                        </div>
                        <div class="progress" style="height:8px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $managerPct }}%"></div>
                        </div>
                        <small class="text-muted">{{ $managerCompleted }} of {{ $totalEmployees }} completed</small>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-cycleDetail">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Employee Name</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Self Review</th>
                            <th>Manager Review</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($participants as $p)
                            <tr>
                                <td><strong>#{{ $p->emp_id }}</strong></td>
                                <td>
                                    <div class="tableUser-block">
                                        <div class="img-circle"><img src="{{ $p->profileImg }}" alt="user"></div>
                                        <span class="userApplicants-btn">{{ $p->name }}</span>
                                        @if($p->is_gm)
                                            <span class="badge badge-themePrimary ms-1">GM</span>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $p->department }}</td>
                                <td>{{ $p->position }}</td>
                                <td>
                                    @if($p->self_status === 'completed')
                                        <div class="text-success">
                                            <i class="fa-solid fa-circle-check me-1"></i> Completed
                                            <div class="small text-muted">{{ \Carbon\Carbon::parse($p->self_date)->format('d M Y') }}</div>
                                        </div>
                                    @else
                                        <span class="badge badge-themeWarning">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    @if($p->manager_status === 'not_applicable')
                                        <span class="badge badge-themeLight">N/A (External)</span>
                                    @elseif($p->manager_status === 'completed')
                                        <div class="text-success">
                                            <i class="fa-solid fa-circle-check me-1"></i> Completed
                                            <div class="small text-muted">{{ \Carbon\Carbon::parse($p->manager_date)->format('d M Y') }}</div>
                                        </div>
                                    @else
                                        <span class="badge badge-themeWarning">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    @if($p->self_status === 'completed')
                                        <a href="{{ route('Performance.Review.showSelf', ['id' => base64_encode($p->child_id)]) }}"
                                           target="_blank"
                                           class="btn btn-themeBlue btn-sm mb-1"
                                           title="View self review">
                                            <i class="fa-solid fa-eye"></i> Self
                                        </a>
                                    @endif
                                    @if($p->manager_status === 'completed')
                                        <a href="{{ route('Performance.Review.showManager', ['id' => base64_encode($p->child_id)]) }}"
                                           target="_blank"
                                           class="btn btn-themeSkyblue btn-sm"
                                           title="View manager review">
                                            <i class="fa-solid fa-eye"></i> Manager
                                        </a>
                                    @endif
                                    @if($p->self_status !== 'completed' && $p->manager_status !== 'completed')
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No participants in this cycle</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
<style>
    .cycle-header h3 { color: #014653; }
    .progress-box { background: #f8f9fa; padding: 16px 20px; border-radius: 10px; border: 1px solid #e9ecef; }
    .progress-label { font-weight: 600; color: #555; font-size: 14px; }
    .progress-value { font-weight: 700; color: #014653; font-size: 20px; }
    .progress-box .progress { background: #e9ecef; border-radius: 10px; }
    .progress-box .progress-bar { border-radius: 10px; }
    .table-cycleDetail { width: 100%; }
    .table-cycleDetail th { background: #f8f9fa; color: #555; font-weight: 600; padding: 12px 16px; border-bottom: 2px solid #e0e0e0; }
    .table-cycleDetail td { padding: 14px 16px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
    .table-cycleDetail tr:hover { background: #f8f9fa; }
    .tableUser-block { display: flex; align-items: center; gap: 10px; }
    .tableUser-block .img-circle img { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }
</style>
@endsection
