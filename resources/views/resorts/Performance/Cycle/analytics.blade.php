@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<style>
    #performance-cycle-analytics-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #performance-cycle-analytics-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="performance-cycle-analytics-hero">
            <div class="row g-3 justify-content-between align-items-center">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Performance</span>
                        <h1>Cycles</h1>
                        <p class="text-muted mb-0 small">{{ $cycle->Cycle_Name }} · {{ $total }} {{ $total == 1 ? 'Employee' : 'Employees' }}</p>
                    </div>
                </div>
                <div class="col-auto">
                    <a href="{{ route('Performance.cycle.view', base64_encode($cycle->id)) }}" class="btn perf-btn-secondary">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <div class="card">
            {{-- Filters --}}
            <form method="GET" action="{{ route('Performance.cycle.analytics', base64_encode($cycle->id)) }}" class="row g-3 mb-4">
                <div class="col-md-3">
                    <select name="department_id" id="department_id" class="form-select dd-native-select" onchange="this.form.submit()">
                        <option value="">Department</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" {{ request('department_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                    <div class="dd" data-target="#department_id">
                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                            <span class="dd-lbl">{{ optional($departments->firstWhere('id', request('department_id')))->name ?? 'Department' }}</span>
                            <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                        </button>
                        <div class="dd-panel" role="listbox" aria-label="Department">
                            <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a department…"></div>
                            <div class="dd-scroll">
                                <div class="dd-item{{ !request('department_id') ? ' active' : '' }}" role="option" data-value=""><span class="dd-nm">Department</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                @foreach($departments as $d)
                                    <div class="dd-item{{ request('department_id') == $d->id ? ' active' : '' }}" role="option" data-value="{{ $d->id }}"><span class="dd-nm">{{ $d->name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="position_id" id="position_id" class="form-select dd-native-select" onchange="this.form.submit()">
                        <option value="">Position</option>
                        @foreach($positions as $p)
                            <option value="{{ $p->id }}" {{ request('position_id') == $p->id ? 'selected' : '' }}>{{ $p->position_title }}</option>
                        @endforeach
                    </select>
                    <div class="dd" data-target="#position_id">
                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                            <span class="dd-lbl">{{ optional($positions->firstWhere('id', request('position_id')))->position_title ?? 'Position' }}</span>
                            <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                        </button>
                        <div class="dd-panel" role="listbox" aria-label="Position">
                            <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a position…"></div>
                            <div class="dd-scroll">
                                <div class="dd-item{{ !request('position_id') ? ' active' : '' }}" role="option" data-value=""><span class="dd-nm">Position</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                @foreach($positions as $p)
                                    <div class="dd-item{{ request('position_id') == $p->id ? ' active' : '' }}" role="option" data-value="{{ $p->id }}"><span class="dd-nm">{{ $p->position_title }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="employment_type" id="employment_type" class="form-select dd-native-select" onchange="this.form.submit()">
                        <option value="">Employment Type</option>
                        @foreach($employmentTypes as $t)
                            <option value="{{ $t }}" {{ request('employment_type') == $t ? 'selected' : '' }}>{{ $t }}</option>
                        @endforeach
                    </select>
                    <div class="dd" data-target="#employment_type">
                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                            <span class="dd-lbl">{{ request('employment_type') ?: 'Employment Type' }}</span>
                            <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                        </button>
                        <div class="dd-panel" role="listbox" aria-label="Employment Type">
                            <div class="dd-scroll">
                                <div class="dd-item{{ !request('employment_type') ? ' active' : '' }}" role="option" data-value=""><span class="dd-nm">Employment Type</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                @foreach($employmentTypes as $t)
                                    <div class="dd-item{{ request('employment_type') == $t ? ' active' : '' }}" role="option" data-value="{{ $t }}"><span class="dd-nm">{{ $t }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('Performance.cycle.analytics', base64_encode($cycle->id)) }}" class="btn perf-btn-neutral btn-sm">Clear Filters</a>
                </div>
            </form>

            {{-- Buckets --}}
            <div class="row g-3">
                <div class="col-lg-6">
                    @include('resorts.Performance.Cycle.partials.analytics_bucket', [
                        'bucket'  => $buckets['does_not_meet'],
                        'bg'      => '#6FB1A8',
                        'textCls' => 'text-white',
                    ])
                </div>
                <div class="col-lg-6">
                    @include('resorts.Performance.Cycle.partials.analytics_bucket', [
                        'bucket'  => $buckets['meets'],
                        'bg'      => '#1F5461',
                        'textCls' => 'text-white',
                    ])
                </div>
                <div class="col-lg-6">
                    @include('resorts.Performance.Cycle.partials.analytics_bucket', [
                        'bucket'  => $buckets['exceeds'],
                        'bg'      => '#C8E0D8',
                        'textCls' => '',
                    ])
                </div>
                @if($buckets['uncategorized']['count'] > 0)
                    <div class="col-lg-6">
                        @include('resorts.Performance.Cycle.partials.analytics_bucket', [
                            'bucket'  => $buckets['uncategorized'],
                            'bg'      => '#f4f1e9',
                            'textCls' => '',
                        ])
                    </div>
                @endif
            </div>

            <div class="mt-4 small text-muted">
                Rating source: numeric values (1–5 scale) extracted from manager review data.
                Thresholds — Does Not Meet: &lt; 2.5 · Meets: 2.5–4.0 · Exceeds: &gt; 4.0.
            </div>
        </div>
    </div>
</div>
@include('resorts.Performance._performance_buttons_v2_styles')
@include('resorts._dropdown_styles')
@include('resorts._dropdown_script')
@endsection

@section('import-css')
<style>
    .analytics-bucket {
        border-radius: 12px;
        padding: 22px 24px;
        min-height: 170px;
    }
    .analytics-bucket h4 { margin:0 0 4px; font-weight:600; }
    .analytics-bucket .meta { font-size: 14px; opacity: 0.9; }
    .analytics-avatars { display:flex; align-items:center; margin-top:14px; }
    .analytics-avatars img {
        width:40px; height:40px; border-radius:50%;
        object-fit:cover; border:2px solid #fff;
        margin-right:-10px;
    }
    .analytics-avatars .more {
        display:inline-flex; align-items:center; justify-content:center;
        width:40px; height:40px; border-radius:50%;
        background:#333; color:#fff; font-size:12px; font-weight:600;
        border:2px solid #fff; margin-left:-10px;
    }
</style>
@endsection
