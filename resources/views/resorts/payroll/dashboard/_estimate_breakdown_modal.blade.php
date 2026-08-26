{{--
    "How is this calculated" popover for the Estimated Payroll card. Pure
    read-only display — fetches from payroll.dashboard.estimate-breakdown
    and payroll.dashboard.estimate-activity (both GET, no writes) via
    jQuery in dashboard.blade.php's import-scripts section. Row content is
    rendered client-side from JSON, not server-rendered here.

    Frosted "details" design, matching payroll_popover_reference.html
    verbatim. Still a genuine Bootstrap modal (.modal.fade, data-bs-toggle,
    show.bs.modal) — only .modal-content is restyled (as .pay-pop), so
    Bootstrap's own backdrop/centering/z-index keeps working unchanged.
    Deliberately NOT using the app's shared .wai-backdrop frosted-modal
    system: this is a single popover, not a recommendation+details pair,
    and reusing Bootstrap's own modal here (rather than hand-rolling
    another custom backdrop) is the smaller, safer diff.
--}}
<div class="modal fade" id="payrollBreakdownModal" tabindex="-1" aria-labelledby="payrollBreakdownModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered peb-dialog">
        <div class="modal-content pay-pop">
            <button type="button" class="x" data-bs-dismiss="modal" aria-label="Close">&times;</button>
            <div class="kick"><span class="dot"></span><span id="pebPeriodLabel">Estimated Payroll</span></div>
            <div class="top">
                <b id="pebCycleLabel">Day &mdash; of &mdash;</b>
                <div class="prog"><i id="pebCycleFill" style="width:0%"></i></div>
                <span id="pebCyclePct"></span>
            </div>

            <div class="cmp">
                <div class="c">
                    <div class="k">As of today</div>
                    <div class="a money" id="pebToday">&mdash;</div>
                    <div class="d" id="pebDelta"></div>
                </div>
                <div class="c">
                    <div class="k">As of yesterday</div>
                    <div class="a money" id="pebYesterday">&mdash;</div>
                    <div class="d mut" id="pebYesterdayDate"></div>
                </div>
            </div>

            <div class="tabs" id="pebTabBar">
                <button type="button" class="t on" data-panel="pnlBreakdown" id="peb-tab-breakdown">Breakdown</button>
                <button type="button" class="t" data-panel="pnlActivity" id="peb-tab-activity">Today&rsquo;s activity <span id="pebActivityCount"></span></button>
            </div>

            <div class="panel on" id="pnlBreakdown">
                <div id="pebBreakdownLoading" class="loading">Loading&hellip;</div>
                <div id="pebBreakdownError" class="err d-none"></div>
                <div id="pebBreakdownContent" class="d-none">
                    <div class="frame">
                        <div class="fcap e"><span class="gd"></span>Earnings<span class="fr pos" id="pebEarnTotalHead"></span></div>
                        <div id="pebEarningsList"></div>
                        <div class="tot"><span>Total earnings</span><span class="amt pos" id="pebEarnTotalFoot"></span></div>
                    </div>
                    <div class="frame">
                        <div class="fcap d"><span class="gd"></span>Deductions<span class="fr neg" id="pebDedTotalHead"></span></div>
                        <div id="pebDeductionsList"></div>
                        <div class="tot"><span>Total deductions</span><span class="amt neg" id="pebDedTotalFoot"></span></div>
                    </div>
                    <div class="net"><div class="nl">Net estimated payout<em>Earnings &minus; Deductions</em></div><div class="nv money" id="pebNet"></div></div>
                </div>
                <div class="foot" id="pebFootCaption">Computed from live payroll data. Estimate until the run is finalized.</div>
            </div>

            <div class="panel" id="pnlActivity">
                <div id="pebActivityLoading" class="loading d-none">Loading&hellip;</div>
                <div id="pebActivityError" class="err d-none"></div>
                <div id="pebActivityEmpty" class="empty d-none">
                    <div class="glyph"><i></i></div>
                    <div class="et">No activity recorded yet today</div>
                    <div class="es">Payroll movements posted today will appear here<br>as the run progresses.</div>
                </div>
                <div id="pebActivityList"></div>
                <div id="pebActivityLoadMoreWrap" class="more-wrap d-none">
                    <button type="button" id="pebActivityLoadMore" class="more-btn">Load more</button>
                    <div class="more-count" id="pebActivityCountText"></div>
                </div>
                <div class="foot" id="pebActivityFootCaption">Computed from live payroll data. Estimate until the run is finalized.</div>
            </div>
        </div>
    </div>
</div>
