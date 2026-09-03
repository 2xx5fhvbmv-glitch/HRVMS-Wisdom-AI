@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<style>
    #benifitgrid-view-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #benifitgrid-view-hero { padding-bottom: 0; }
    }
</style>
@include('resorts.benifitgrid._statement_styles')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="benifitgrid-view-hero">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Configuration</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <a href="{{ route('resort.benefitgrid.pdf', $benefit_grid->id) }}" target="_blank" class="btn eb-btn-secondary">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                        Download
                    </a>
                </div>
            </div>
        </div>

        <div class="bgst-page">
            <div class="bgst-sheet">
                @include('resorts.benifitgrid._statement', [
                    'benefit_grid' => $benefit_grid,
                    'benefitGridChildren' => $benefitGridChildren,
                    'selected_linen_array' => $selected_linen_array,
                    'selected_laundry' => $selected_laundry,
                    'selected_sports' => $selected_sports,
                    'positionsByRank' => $positionsByRank ?? null,
                ])
            </div>
        </div>
    </div>
</div>
@include('resorts._emotional_buttons_v2_styles')
@endSection

@section('import-css')
@endsection

@section('import-scripts')
@endsection
