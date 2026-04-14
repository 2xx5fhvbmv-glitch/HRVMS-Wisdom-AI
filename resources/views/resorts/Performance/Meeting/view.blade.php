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
                <div class="col-auto">
                    <a href="{{ route('Performance.Meeting.list') }}" class="btn btn-themeGray">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back to List
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-title">
                        <h3>{{ $meeting->title }}</h3>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="info-item">
                                <span class="info-label"><i class="fa-regular fa-calendar me-2"></i> Date</span>
                                <div class="info-value">{{ \Carbon\Carbon::parse($meeting->date)->format('d M Y') }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <span class="info-label"><i class="fa-regular fa-clock me-2"></i> Time</span>
                                <div class="info-value">{{ $meeting->start_time }} - {{ $meeting->end_time }}</div>
                            </div>
                        </div>
                        @if($meeting->location)
                            <div class="col-md-6">
                                <div class="info-item">
                                    <span class="info-label"><i class="fa-solid fa-location-dot me-2"></i> Location</span>
                                    <div class="info-value">{{ $meeting->location }}</div>
                                </div>
                            </div>
                        @endif
                        @if($meeting->conference_links)
                            <div class="col-md-6">
                                <div class="info-item">
                                    <span class="info-label"><i class="fa-solid fa-link me-2"></i> Meeting Link</span>
                                    <div class="info-value"><a href="{{ $meeting->conference_links }}" target="_blank">{{ $meeting->conference_links }}</a></div>
                                </div>
                            </div>
                        @endif
                        @if($meeting->description)
                            <div class="col-12">
                                <div class="info-item">
                                    <span class="info-label"><i class="fa-regular fa-file-lines me-2"></i> Description</span>
                                    <div class="info-value">{{ $meeting->description }}</div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-title">
                        <h3>Summary</h3>
                    </div>
                    <div class="summary-stats">
                        <div class="stat-item accepted">
                            <div class="stat-label">Accepted</div>
                            <div class="stat-count">{{ $accepted->count() }}</div>
                        </div>
                        <div class="stat-item pending">
                            <div class="stat-label">Pending</div>
                            <div class="stat-count">{{ $pending->count() }}</div>
                        </div>
                        <div class="stat-item declined">
                            <div class="stat-label">Declined</div>
                            <div class="stat-count">{{ $declined->count() }}</div>
                        </div>
                        <div class="stat-item total">
                            <div class="stat-label">Total Participants</div>
                            <div class="stat-count">{{ $participants->count() }}</div>
                        </div>
                    </div>
                </div>
            </div>

            @if($accepted->count() > 0)
                <div class="col-12">
                    <div class="card">
                        <div class="card-title">
                            <h3><i class="fa-solid fa-circle-check text-success me-2"></i> Accepted ({{ $accepted->count() }})</h3>
                        </div>
                        <div class="row g-3">
                            @foreach($accepted as $p)
                                <div class="col-md-6 col-lg-4">
                                    <div class="participant-card accepted">
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="{{ $p->profileImg }}" alt="" class="profile-img">
                                            <div class="flex-fill">
                                                <div><strong>{{ $p->name }}</strong></div>
                                                <small class="text-muted d-block">{{ $p->position }}</small>
                                                <small class="text-muted d-block">{{ $p->email }}</small>
                                                @if($p->responded_at)
                                                    <small class="text-muted d-block"><i class="fa-regular fa-clock"></i> {{ $p->responded_at }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            @if($declined->count() > 0)
                <div class="col-12">
                    <div class="card">
                        <div class="card-title">
                            <h3><i class="fa-solid fa-circle-xmark text-danger me-2"></i> Declined ({{ $declined->count() }})</h3>
                        </div>
                        <div class="row g-3">
                            @foreach($declined as $p)
                                <div class="col-md-6">
                                    <div class="participant-card declined">
                                        <div class="d-flex align-items-center gap-3 mb-2">
                                            <img src="{{ $p->profileImg }}" alt="" class="profile-img">
                                            <div class="flex-fill">
                                                <div><strong>{{ $p->name }}</strong></div>
                                                <small class="text-muted d-block">{{ $p->position }}</small>
                                                <small class="text-muted d-block">{{ $p->email }}</small>
                                                @if($p->responded_at)
                                                    <small class="text-muted d-block"><i class="fa-regular fa-clock"></i> {{ $p->responded_at }}</small>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="reason-box">
                                            <strong class="d-block mb-1">Reason for Declining:</strong>
                                            <div>{{ $p->reason ?: 'No reason provided' }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            @if($pending->count() > 0)
                <div class="col-12">
                    <div class="card">
                        <div class="card-title">
                            <h3><i class="fa-solid fa-clock text-warning me-2"></i> Pending ({{ $pending->count() }})</h3>
                        </div>
                        <div class="row g-3">
                            @foreach($pending as $p)
                                <div class="col-md-6 col-lg-4">
                                    <div class="participant-card pending">
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="{{ $p->profileImg }}" alt="" class="profile-img">
                                            <div class="flex-fill">
                                                <div><strong>{{ $p->name }}</strong></div>
                                                <small class="text-muted d-block">{{ $p->position }}</small>
                                                <small class="text-muted d-block">{{ $p->email }}</small>
                                                <small class="text-warning d-block"><i class="fa-regular fa-clock"></i> Awaiting response</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('import-css')
<style>
    .info-item { padding: 12px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #014653; }
    .info-label { color: #666; font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px; }
    .info-value { color: #333; font-size: 15px; font-weight: 500; }
    .summary-stats { display: flex; flex-direction: column; gap: 10px; }
    .stat-item { padding: 12px 16px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; }
    .stat-item.accepted { background: #e8f5e9; border-left: 4px solid #28a745; }
    .stat-item.declined { background: #ffeaea; border-left: 4px solid #dc3545; }
    .stat-item.pending { background: #fff8e1; border-left: 4px solid #EFB408; }
    .stat-item.total { background: #e3f2fd; border-left: 4px solid #014653; }
    .stat-label { font-weight: 600; color: #555; }
    .stat-count { font-size: 22px; font-weight: 700; color: #333; }
    .participant-card { padding: 14px; border-radius: 8px; border: 1px solid #e0e0e0; height: 100%; }
    .participant-card.accepted { background: #f1f8f4; border-color: #28a745; }
    .participant-card.declined { background: #fef5f5; border-color: #dc3545; }
    .participant-card.pending { background: #fffbeb; border-color: #EFB408; }
    .profile-img { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; }
    .reason-box { background: #fff; border: 1px solid #dc3545; border-radius: 6px; padding: 10px 12px; font-size: 13px; color: #333; }
</style>
@endsection
