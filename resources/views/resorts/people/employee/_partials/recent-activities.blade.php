{{-- Shared Recent Activities feed — rendered inside every detail-page
     tab pane. Source: $recentActivities computed in
     EmployeeController::details (last 3 across leaves, promotions,
     salary increments, sorted by created_at desc). Each row exposes
     {title, subtitle, status, badge_class}. --}}
<div class="row g-xxl-4 g-lg-3 g-2">
    @forelse($recentActivities ?? [] as $_act)
        <div class="col-xl-4 col-lg-6">
            <div class="recentActPeopleEmp-block">
                <div>
                    <h6>{{ $_act->title }}</h6>
                    <p>{{ $_act->subtitle }}</p>
                </div>
                <span class="badge {{ $_act->badge_class }}">{{ ucfirst((string) $_act->status) }}</span>
            </div>
        </div>
    @empty
        <div class="col-12">
            <p class="text-muted mb-0">No recent activity yet for this employee.</p>
        </div>
    @endforelse
</div>
