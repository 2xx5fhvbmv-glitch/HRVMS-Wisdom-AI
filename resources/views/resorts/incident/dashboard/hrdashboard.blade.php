@extends('resorts.layouts.app')
@section('page_tab_title' ,"Incident Dashboard")

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding">
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Incident</span>
                            <h1>Dashboard</h1>
                        </div>
                    </div>
                </div>
            </div>

            @include('resorts.incident.dashboard._dashboard_body')
        </div>
    </div>
@includeWhen(isset($incidentInsights), 'resorts.incident.dashboard._insight_modals')
@includeWhen(isset($incidentInsights), 'partials._wai_insight_modals')
@endsection

@section('import-css')
@include('resorts.incident.dashboard._dashboard_styles')
@endsection

@section('import-scripts')
<script>
    window.incidentDetailBaseUrl = "{{ route('incident.view', ['id' => 'INCIDENT_ID']) }}";
    window.meetingDetailBaseUrl = "{{ route('incident.meeting.detail', ['id' => 'MEETING_ID']) }}";
    window.trendsRoute = "{{ route('incident.chart.getTrends') }}";
    window.resolutionStatsRoute = "{{ route('incident.getResolutionTimelineStats') }}";
    window.preventiveListRoute = "{{ route('incident.preventive.list') }}";
    window.pendingResolutionsRoute = "{{ route('incident.pendingResolutions') }}";
</script>
@include('resorts.incident.dashboard._dashboard_scripts')
@endsection
