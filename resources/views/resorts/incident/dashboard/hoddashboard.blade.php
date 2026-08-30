@extends('resorts.layouts.app')
@section('page_tab_title' ,"Incident Dashboard")

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
    @php
        // hod_dashboard()'s "second KPI" is $pending_incidents (status =
        // Reported only) — a narrower metric than HR/Admin's $open_incidents
        // (anything not yet Resolved), so it keeps its own true label
        // rather than being forced into "Open Incidents" wording that
        // wouldn't match what the number actually counts.
        $kpi2Value = $pending_incidents ?? 0;
        $kpi2Label = 'Pending Incidents';
        $preventiveViewAllRoute = route('incident.hod.preventive');
    @endphp
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
@endsection

@section('import-css')
@include('resorts.incident.dashboard._dashboard_styles')
@endsection

@section('import-scripts')
<script>
    window.incidentDetailBaseUrl = "{{ route('incident.view', ['id' => 'INCIDENT_ID']) }}";
    window.meetingDetailBaseUrl = "{{ route('incident.meeting.detail', ['id' => 'MEETING_ID']) }}";
    window.trendsRoute = "{{ route('incident.hod-chart.getTrends') }}";
    window.resolutionStatsRoute = "{{ route('incident.hod.getResolutionTimelineStats') }}";
    window.preventiveListRoute = "{{ route('incident.preventive.hodlist') }}";
    window.pendingResolutionsRoute = "{{ route('incident.hod-pending-approvals') }}";
</script>
@include('resorts.incident.dashboard._dashboard_scripts')
@endsection
