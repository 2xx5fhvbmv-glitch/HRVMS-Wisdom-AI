@extends('resorts.layouts.app')
@section('page_tab_title' ,"Incident Dashboard")

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<style>
    /* Same requested push as the other module dashboards (Payroll / Talent
       Acquisition / People / Time and Attendance / Leave / Performance /
       Learning / Accommodation) — extra breathing room between the hero
       and the KPI row below it, scoped to this page (.page-hedding's own
       margin-bottom is shared by every page's hero). padding-bottom, not
       margin: adjacent sibling margins collapse to the larger of the two
       rather than summing. Below Bootstrap's sm breakpoint the extra
       padding pushes the KPI row's first card into the teal hero curve's
       rounded bottom-left corner (body::before, border-radius 0 0 50px
       50px) — same collision found on Payroll — neutralized below 576px.
       hrdashboard.blade.php only — the KPI row lives in the shared
       _dashboard_body partial (also used by hoddashboard/admindashboard),
       left untouched so this stays scoped to the HR variant. */
    #incident-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #incident-hero { padding-bottom: 0; }
    }
</style>
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding" id="incident-hero">
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
