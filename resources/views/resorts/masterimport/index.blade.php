@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Master Import</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            @foreach ($tiles as $tile)
                <div class="col-xxl-3 col-xl-4 col-md-6">
                    <a href="{{ route($tile['route']) }}" class="card h-100 text-decoration-none">
                        <div class="card-body">
                            <h5 class="card-title mb-2">{{ $tile['label'] }}</h5>
                            <p class="card-text text-muted mb-0">{{ $tile['description'] }}</p>
                            <p class="card-text small mt-2 mb-0">
                                @if($tile['last_updated'])
                                    <span class="text-muted">Last updated:</span> {{ $tile['last_updated'] }}
                                    @if($tile['last_status'] === 'failed')
                                        <span class="badge bg-danger ms-1">Failed</span>
                                    @elseif($tile['last_status'] !== 'completed')
                                        <span class="badge bg-secondary ms-1">{{ ucfirst($tile['last_status']) }}</span>
                                    @endif
                                @else
                                    <span class="text-muted">No import history yet</span>
                                @endif
                            </p>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
