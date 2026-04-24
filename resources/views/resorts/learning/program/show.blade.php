@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Learning &amp; Development</span>
                        <h1>{{ $program->name }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <a href="{{ route('learning.programs.index') }}" class="btn btn-themeBlue btn-sm">
                        <i class="fa-solid fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><strong>Category:</strong> {{ optional($program->category)->category ?? '-' }}</div>
                    <div class="col-md-4"><strong>Duration:</strong> {{ $program->days }} Days {{ $program->hours }} hrs</div>
                    <div class="col-md-4"><strong>Frequency:</strong> {{ ucfirst($program->frequency) }}</div>
                    <div class="col-md-4"><strong>Delivery Mode:</strong> {{ ucfirst($program->delivery_mode) }}</div>
                    <div class="col-md-4"><strong>Trainer:</strong> {{ $trainer && $trainer->resortAdmin ? trim($trainer->resortAdmin->first_name.' '.$trainer->resortAdmin->last_name) : '-' }}</div>
                    <div class="col-md-4"><strong>Audience Type:</strong> {{ ucfirst($program->audience_type ?? '-') }}</div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Description</h5></div>
            <div class="card-body">
                <div>{!! nl2br(e($program->description)) !!}</div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Objectives &amp; Goals</h5></div>
            <div class="card-body">
                <div>{!! nl2br(e($program->objectives)) !!}</div>
            </div>
        </div>

        @if($program->prior_qualification)
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">Prior Qualification</h5></div>
                <div class="card-body">
                    <div>{!! nl2br(e($program->prior_qualification)) !!}</div>
                </div>
            </div>
        @endif

        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Target Audience</h5></div>
            <div class="card-body">
                @if(count($audienceLabels) > 0)
                    <ul class="mb-0">
                        @foreach($audienceLabels as $label)
                            <li>{{ $label }}</li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-muted">No audience configured.</div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="mb-0">Learning Materials</h5></div>
            <div class="card-body">
                @if(isset($materials) && $materials->count() > 0)
                    <ul class="mb-0 list-unstyled">
                        @foreach($materials as $m)
                            <li class="mb-2">
                                <a href="{{ route('learning.programs.material', base64_encode($m->id)) }}" class="btn btn-themeLight btn-sm">
                                    <i class="fa-solid fa-file-arrow-down me-1"></i>
                                    {{ preg_replace('/^\d+_/', '', basename($m->file_path)) }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-muted">No materials uploaded.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
