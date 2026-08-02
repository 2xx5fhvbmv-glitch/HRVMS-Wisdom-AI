{{--
    "How is this calculated" modal for the Estimated Payroll card. Pure
    read-only display — fetches from payroll.dashboard.estimate-breakdown
    and payroll.dashboard.estimate-activity (both GET, no writes) via
    jQuery in dashboard.blade.php's import-scripts section. Row content
    is rendered client-side from JSON, not server-rendered here.
--}}
<div class="modal fade" id="payrollBreakdownModal" tabindex="-1" aria-labelledby="payrollBreakdownModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered peb-dialog">
        <div class="modal-content peb-content">
            <div class="modal-header peb-header">
                <div class="peb-header-main">
                    <p class="peb-period" id="pebPeriodLabel">Estimated Payroll</p>
                    <div class="peb-cycle">
                        <span class="peb-cycle-lbl" id="pebCycleLabel">Day &mdash; of &mdash;</span>
                        <span class="peb-cycle-track"><span class="peb-cycle-fill" id="pebCycleFill"></span></span>
                    </div>
                </div>
                <button type="button" class="btn-close peb-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="peb-compare">
                <div class="peb-compare-card">
                    <div class="peb-compare-lbl">As of today</div>
                    <div class="peb-compare-amt" id="pebToday">&mdash;</div>
                    <div class="peb-compare-delta" id="pebDelta"></div>
                </div>
                <div class="peb-compare-card">
                    <div class="peb-compare-lbl">As of yesterday</div>
                    <div class="peb-compare-amt" id="pebYesterday">&mdash;</div>
                    <div class="peb-compare-sub" id="pebYesterdayDate"></div>
                </div>
            </div>

            <ul class="nav nav-tabs peb-tabs" id="pebTabBar" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="peb-tab-breakdown" data-bs-toggle="tab" data-bs-target="#pebBreakdownPane" type="button" role="tab">Breakdown</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="peb-tab-activity" data-bs-toggle="tab" data-bs-target="#pebActivityPane" type="button" role="tab">Today's Activity <span class="peb-tab-count" id="pebActivityCount"></span></button>
                </li>
            </ul>

            <div class="modal-body peb-body">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="pebBreakdownPane" role="tabpanel">
                        <div id="pebBreakdownLoading" class="peb-loading">Loading&hellip;</div>
                        <div id="pebBreakdownError" class="peb-error d-none"></div>
                        <div id="pebBreakdownContent" class="d-none">
                            <div class="peb-panel earn-panel">
                                <div class="peb-panel-head"><span class="peb-dot peb-dot-earn"></span><span class="peb-panel-name">Earnings</span></div>
                                <div id="pebEarningsList"></div>
                                <div class="peb-subtotal-row">
                                    <span>Total earnings</span>
                                    <span class="peb-earn" id="pebEarnTotal"></span>
                                </div>
                            </div>
                            <div class="peb-panel ded-panel">
                                <div class="peb-panel-head"><span class="peb-dot peb-dot-ded"></span><span class="peb-panel-name">Deductions</span></div>
                                <div id="pebDeductionsList"></div>
                                <div class="peb-subtotal-row">
                                    <span>Total deductions</span>
                                    <span class="peb-ded" id="pebDedTotal"></span>
                                </div>
                            </div>
                            <div class="peb-net-band">
                                <div>
                                    <div class="peb-net-lbl">Net estimated payout</div>
                                    <div class="peb-net-sub">Earnings &minus; Deductions</div>
                                </div>
                                <div class="peb-net-amt" id="pebNet"></div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="pebActivityPane" role="tabpanel">
                        <div id="pebActivityLoading" class="peb-loading d-none">Loading&hellip;</div>
                        <div id="pebActivityError" class="peb-error d-none"></div>
                        <div id="pebActivityEmpty" class="peb-empty d-none">No activity recorded yet today.</div>
                        <div id="pebActivityList" class="peb-activity-list"></div>
                        <div id="pebActivityLoadMoreWrap" class="peb-load-more d-none">
                            <button type="button" id="pebActivityLoadMore" class="peb-load-more-btn">Load more</button>
                            <div class="peb-load-more-count" id="pebActivityCountText"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="peb-foot">
                <p id="pebFootCaption">Computed from live payroll data. Estimate until the run is finalized.</p>
            </div>
        </div>
    </div>
</div>
