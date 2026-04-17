@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row g-3 justify-content-between align-items-center">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Performance</span>
                        <h1>Cycles</h1>
                        <p class="text-muted mb-0 small">{{ $cycle->Cycle_Name }} · {{ $total }} {{ $total == 1 ? 'Employee' : 'Employees' }}</p>
                    </div>
                </div>
                <div class="col-auto">
                    <a href="{{ route('Performance.cycle.view', base64_encode($cycle->id)) }}" class="btn btn-themeGray">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <div class="card">
            {{-- Filters --}}
            <form method="GET" action="{{ route('Performance.cycle.analytics', base64_encode($cycle->id)) }}" class="row g-3 mb-4">
                <div class="col-md-3">
                    <select name="department_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Department</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" {{ request('department_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="position_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Position</option>
                        @foreach($positions as $p)
                            <option value="{{ $p->id }}" {{ request('position_id') == $p->id ? 'selected' : '' }}>{{ $p->position_title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="employment_type" class="form-select" onchange="this.form.submit()">
                        <option value="">Employment Type</option>
                        @foreach($employmentTypes as $t)
                            <option value="{{ $t }}" {{ request('employment_type') == $t ? 'selected' : '' }}>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('Performance.cycle.analytics', base64_encode($cycle->id)) }}" class="btn btn-themeBlue btn-sm">Clear Filters</a>
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
                Thresholds — Does not Meet: &lt; 2.5 · Meets: 2.5–4.0 · Exceeds: &gt; 4.0.
            </div>
        </div>
    </div>
</div>
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
