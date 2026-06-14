{{-- Learning & Development AI-insight detail modals. Included by the Learning
     HR dashboard; reads $learningInsights. Opened by the "View Details" links. --}}
@php
    $compD = $learningInsights['completion']['details']   ?? [];
    $mandD = $learningInsights['mandatory']['details']    ?? [];
    $reqD  = $learningInsights['requests']['details']     ?? [];
    $probD = $learningInsights['probationary']['details'] ?? [];
@endphp

<!-- Training Completion Rate -->
<div class="modal fade" id="learningInsightCompletionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $learningInsights['completion']['title'] ?? 'Training Completion Rate' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $learningInsights['completion']['body'] ?? '' }}</p>
                @if(!empty($learningInsights['completion']['recommendation']))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $learningInsights['completion']['recommendation'] }}</p>@endif
                @if(!empty($compD['rows']))
                    <p class="mb-1">Completion by program (threshold {{ $compD['threshold'] ?? 0 }}%):</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead><tr><th>Program</th><th class="text-end">Participants</th><th class="text-end">Met threshold</th><th class="text-end">Rate</th></tr></thead>
                            <tbody>
                                @foreach($compD['rows'] as $row)
                                    <tr><td>{{ $row['program'] }}</td><td class="text-end">{{ $row['participants'] }}</td><td class="text-end">{{ $row['completed'] }}</td><td class="text-end">{{ $row['rate'] }}%</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="mb-0">No training attendance recorded yet.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Mandatory Training Compliance -->
<div class="modal fade" id="learningInsightMandatoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $learningInsights['mandatory']['title'] ?? 'Mandatory Training Compliance' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $learningInsights['mandatory']['body'] ?? '' }}</p>
                @if(!empty($learningInsights['mandatory']['recommendation']))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $learningInsights['mandatory']['recommendation'] }}</p>@endif
                @if(!empty($mandD['rows']))
                    <p class="mb-1">Overall: {{ $mandD['completed'] ?? 0 }} of {{ $mandD['required'] ?? 0 }} required completions ({{ $mandD['pct'] ?? 0 }}%), {{ $mandD['outstanding'] ?? 0 }} outstanding.</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead><tr><th>Program</th><th class="text-end">Required</th><th class="text-end">Completed</th><th class="text-end">Compliance</th></tr></thead>
                            <tbody>
                                @foreach($mandD['rows'] as $row)
                                    <tr><td>{{ $row['program'] }}</td><td class="text-end">{{ $row['required'] }}</td><td class="text-end">{{ $row['completed'] }}</td><td class="text-end">{{ $row['pct'] }}%</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="mb-0">No mandatory programs configured yet.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Learning Request Pipeline -->
<div class="modal fade" id="learningInsightRequestsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $learningInsights['requests']['title'] ?? 'Learning Request Pipeline' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $learningInsights['requests']['body'] ?? '' }}</p>
                @if(!empty($learningInsights['requests']['recommendation']))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $learningInsights['requests']['recommendation'] }}</p>@endif
                @if(!empty($reqD['counts']))
                    <p class="mb-1 fw-bold">Pipeline @if(isset($reqD['approval_rate']) && $reqD['approval_rate'] !== null)(approval rate {{ $reqD['approval_rate'] }}%)@endif</p>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-striped align-middle">
                            <thead><tr><th>Status</th><th class="text-end">Count</th></tr></thead>
                            <tbody>
                                @foreach($reqD['counts'] as $st => $c)
                                    <tr><td>{{ $st }}</td><td class="text-end">{{ $c }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if(!empty($reqD['categories']))
                        <p class="mb-1 fw-bold">Top requested categories</p>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-striped align-middle">
                                <thead><tr><th>Category</th><th class="text-end">Requests</th></tr></thead>
                                <tbody>
                                    @foreach($reqD['categories'] as $row)
                                        <tr><td>{{ $row['category'] }}</td><td class="text-end">{{ $row['count'] }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                    @if(!empty($reqD['denials']))
                        <p class="mb-1 fw-bold">Top denial reasons</p>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle">
                                <thead><tr><th>Reason</th><th class="text-end">Count</th></tr></thead>
                                <tbody>
                                    @foreach($reqD['denials'] as $row)
                                        <tr><td>{{ $row['reason'] }}</td><td class="text-end">{{ $row['count'] }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @else
                    <p class="mb-0">No learning requests submitted yet.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Probationary Training Progress -->
<div class="modal fade" id="learningInsightProbationaryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $learningInsights['probationary']['title'] ?? 'Probationary Training Progress' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ $learningInsights['probationary']['body'] ?? '' }}</p>
                @if(!empty($learningInsights['probationary']['recommendation']))<p style="color:#2EACB3;"><strong>Recommendation:</strong> {{ $learningInsights['probationary']['recommendation'] }}</p>@endif
                @if(!empty($probD))
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <tbody>
                                <tr><td>Staff on probation</td><td class="text-end">{{ $probD['employees'] ?? 0 }}</td></tr>
                                <tr><td>Required programs</td><td class="text-end">{{ $probD['programs'] ?? 0 }}</td></tr>
                                <tr><td>Completed</td><td class="text-end">{{ $probD['completed'] ?? 0 }} of {{ $probD['total'] ?? 0 }}</td></tr>
                                <tr><td>Outstanding</td><td class="text-end">{{ $probD['outstanding'] ?? 0 }}</td></tr>
                                <tr class="fw-bold"><td>Completion</td><td class="text-end">{{ $probD['pct'] ?? 0 }}%</td></tr>
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="mb-0">No probationary programs or staff on probation yet.</p>
                @endif
            </div>
        </div>
    </div>
</div>
