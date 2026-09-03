{{--
    Shared Incident Dashboard JS — one include for all 3 role views.
    Every AJAX call/route/response-field below is exactly what the existing
    hrdashboard.blade.php already called (see DashboardController.php) —
    only the *rendering* changed, from Chart.js instances to pure CSS/SVG,
    per the redesign brief ("no charting library"). Chart.js itself is
    still loaded globally (other pages use it) — this page just stops
    calling `new Chart(...)`.

    Expects these globals to already be set by the including dashboard file:
      window.trendsRoute, window.resolutionStatsRoute,
      window.preventiveListRoute, window.pendingResolutionsRoute,
      window.incidentDetailBaseUrl, window.meetingDetailBaseUrl
--}}
<script>
$(document).ready(function () {
    dbiLoadIncidentList();
    dbiLoadResolutionTimelineStats();
    dbiLoadUpcomingMeetings();
    dbiLoadPreventiveActions();
    dbiLoadPendingResolutions();
    dbiLoadDepartmentParticipation();

    var $yearSelect = $('#dbiTrendYear');
    var currentYear = new Date().getFullYear();
    for (var y = currentYear; y >= currentYear - 5; y--) {
        $yearSelect.append('<option value="' + y + '"' + (y === currentYear ? ' selected' : '') + '>' + y + '</option>');
    }
    wisdomDD.rebuild('#dbiTrendYear');
    dbiLoadTrends(currentYear);
    $yearSelect.on('change', function () { dbiLoadTrends($(this).val()); });
});

function dbiEsc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
}

/* ---- Incident List (same route/fields as _incident_list_widget.blade.php) ---- */
function dbiLoadIncidentList() {
    $.ajax({
        url: '{{ route("incident.todoList") }}',
        method: 'GET',
        success: function (incidents) {
            var $c = $('#dbiIncidentList');
            if (!incidents.length) {
                $c.html('<div class="dbi-empty"><div class="t">No incidents reported yet</div></div>');
                return;
            }
            var html = '';
            incidents.forEach(function (inc) {
                var href = window.incidentDetailBaseUrl.replace('INCIDENT_ID', btoa(inc.id));
                html += '<div class="dbi-inc">'
                    + '<div class="top"><span class="t">' + dbiEsc(inc.title) + '</span><span class="tm">' + dbiEsc(inc.time_ago) + '</span></div>'
                    + '<div class="bot"><span class="cat">' + dbiEsc(inc.category) + '</span><a class="vd" href="' + href + '">View details &rarr;</a></div>'
                    + '</div>';
            });
            $c.html(html);
        }
    });
}

/* ---- Resolution Timelines — plain text fill, same 4 fields as before ---- */
function dbiLoadResolutionTimelineStats() {
    $.ajax({
        url: window.resolutionStatsRoute,
        method: 'GET',
        success: function (res) {
            $('#casesNearingDeadline').text(res.nearingDeadline);
            $('#breachedTimelines').text(res.breachedTimelines);
            $('#resolvedCases').text(res.resolvedPercentage + '%');
            $('#openInvestigations').text(res.openInvestigations);
        }
    });
}

/* ---- Upcoming Meetings ---- */
function dbiLoadUpcomingMeetings() {
    $.ajax({
        url: '{{ route("incident.getUpcomingMeetings") }}',
        method: 'GET',
        success: function (meetings) {
            var $c = $('#dbiUpcomingMeetings');
            if (!meetings.length) {
                $c.html('<div class="dbi-empty">'
                    + '<div class="g"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="3"/><path d="M3 10h18M8 2v4M16 2v4"/></svg></div>'
                    + '<div class="t">No upcoming meetings</div><div class="s">Scheduled reviews will appear here.</div>'
                    + '</div>');
                return;
            }
            var html = '';
            meetings.forEach(function (m) {
                var href = window.meetingDetailBaseUrl.replace('MEETING_ID', btoa(m.id));
                html += '<div class="dbi-mtg">'
                    + '<div><div class="t">' + dbiEsc(m.title) + '</div><div class="s">' + dbiEsc(m.description) + '</div>'
                    + '<a class="vd" href="' + href + '">View details &rarr;</a></div>'
                    + '<span class="when">' + dbiEsc(m.day_label) + ', ' + dbiEsc(m.scheduled_time) + '</span>'
                    + '</div>';
            });
            $c.html(html);
        }
    });
}

/* ---- Preventive Measures ---- */
function dbiLoadPreventiveActions() {
    $.ajax({
        url: window.preventiveListRoute,
        method: 'GET',
        success: function (actions) {
            var $c = $('#dbiPreventiveList');
            if (!actions.length) {
                $c.html('<div class="dbi-empty"><div class="t">No preventive actions found</div></div>');
                return;
            }
            var html = '';
            actions.forEach(function (a) {
                html += '<div class="dbi-prev">'
                    + '<span class="ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l7 4v5c0 4-3 7-7 9-4-2-7-5-7-9V7z"/></svg></span>'
                    + '<div class="b"><div class="t">' + dbiEsc(a.title) + '</div><div class="s">' + dbiEsc(a.description) + '</div></div>'
                    + '</div>';
            });
            $c.html(html);
        }
    });
}

/* ---- Pending Resolution Approval ---- */
function dbiLoadPendingResolutions() {
    $.ajax({
        url: window.pendingResolutionsRoute,
        method: 'GET',
        success: function (data) {
            var $c = $('#dbiPendingResolutions');
            if (!data.length) {
                $c.html('<div class="dbi-empty">'
                    + '<div class="g"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg></div>'
                    + '<div class="t">Nothing pending approval</div><div class="s">Resolutions awaiting sign-off will show up here.</div>'
                    + '</div>');
                return;
            }
            var html = '';
            data.forEach(function (item) {
                html += '<div class="dbi-pend">'
                    + '<div class="t">' + dbiEsc(item.incident_name) + '</div>'
                    + '<div class="s">' + dbiEsc(item.investigation_findings || 'No findings yet.') + '</div>'
                    + '</div>';
            });
            $c.html(html);
        }
    });
}

/* ---- Incident Trends — pure SVG line + area (was Chart.js "line") ---- */
function dbiLoadTrends(year) {
    $.ajax({
        url: window.trendsRoute,
        method: 'GET',
        data: { year: year },
        success: function (res) {
            var $c = $('#dbiTrendChart');
            var labels = res.labels || [];
            var data = (res.data || []).map(Number);
            if (!labels.length) { $c.html('<div class="dbi-empty"><div class="t">No trend data</div></div>'); return; }

            var w = 720, h = 210, padL = 30, padR = 10, padT = 20, padB = 28;
            var plotW = w - padL - padR, plotH = h - padT - padB;
            var max = Math.max(5, Math.max.apply(null, data));
            // round up to a clean step so the axis labels aren't fractional
            var step = Math.ceil(max / 2 / 5) * 5 || 5;
            var top = step * 2;

            var n = labels.length;
            var x = function (i) { return n > 1 ? padL + (i / (n - 1)) * plotW : padL + plotW / 2; };
            var y = function (v) { return padT + plotH - (Math.min(v, top) / top) * plotH; };

            var pts = data.map(function (v, i) { return x(i) + ',' + y(v).toFixed(1); }).join(' ');
            var area = 'M' + x(0) + ',' + (padT + plotH) + ' ' + pts.split(' ').map(function (p) { return p; }).join(' ')
                + ' ' + x(n - 1) + ',' + (padT + plotH) + ' Z';

            var dots = data.map(function (v, i) {
                return '<circle class="pt" cx="' + x(i) + '" cy="' + y(v).toFixed(1) + '" r="3.5"></circle>';
            }).join('');

            var axisLabels = labels.map(function (lbl, i) {
                var shortLbl = String(lbl).split(' ')[0]; // "Jan 2026" -> "Jan"
                return '<text x="' + x(i) + '" y="' + (h - 8) + '" text-anchor="middle">' + dbiEsc(shortLbl) + '</text>';
            }).join('');

            var svg = '<svg viewBox="0 0 ' + w + ' ' + h + '" preserveAspectRatio="none" aria-hidden="true">'
                + '<defs><linearGradient id="dbiTealFade" x1="0" y1="0" x2="0" y2="1">'
                + '<stop offset="0" stop-color="#014653" stop-opacity=".16"/><stop offset="1" stop-color="#014653" stop-opacity="0"/>'
                + '</linearGradient></defs>'
                + '<line class="gl" x1="' + padL + '" y1="' + padT + '" x2="' + (w - padR) + '" y2="' + padT + '"></line>'
                + '<line class="gl" x1="' + padL + '" y1="' + (padT + plotH / 2) + '" x2="' + (w - padR) + '" y2="' + (padT + plotH / 2) + '"></line>'
                + '<line class="gl" x1="' + padL + '" y1="' + (padT + plotH) + '" x2="' + (w - padR) + '" y2="' + (padT + plotH) + '"></line>'
                + '<text class="axis" x="8" y="' + (padT + 4) + '">' + top + '</text>'
                + '<text class="axis" x="8" y="' + (padT + plotH / 2 + 4) + '">' + (top / 2) + '</text>'
                + '<text class="axis" x="12" y="' + (padT + plotH + 4) + '">0</text>'
                + '<path class="area" d="' + area + '"></path>'
                + '<polyline class="ln" points="' + pts + '"></polyline>'
                + dots
                + '<g class="axis">' + axisLabels + '</g>'
                + '</svg>';
            $c.html(svg);
        }
    });
}

/* ---- Department-wise Participation — pure CSS stacked columns, categorical ramp (was Chart.js stacked "bar") ---- */
function dbiLoadDepartmentParticipation() {
    $.ajax({
        url: '{{ route("incident.chart.getDepartmentWiseParticipation") }}',
        method: 'GET',
        dataType: 'json',
        success: function (chartData) {
            var $c = $('#dbiDeptChart');
            var labels = chartData.labels || [];
            var datasets = chartData.datasets || [];
            if (!labels.length || !datasets.length) {
                $c.html('<div class="dbi-empty"><div class="t">No participation recorded yet</div></div>');
                return;
            }
            var ramp = ['var(--dbi-c1)', 'var(--dbi-c2)', 'var(--dbi-c3)', 'var(--dbi-c4)'];
            var max = 0;
            labels.forEach(function (lbl, i) {
                var total = 0;
                datasets.forEach(function (ds) { total += Number(ds.data[i] || 0); });
                if (total > max) max = total;
            });
            max = Math.max(max, 4);
            var yTop = Math.ceil(max / 20) * 20 || 20;

            var cols = labels.map(function (lbl, i) {
                var stack = datasets.map(function (ds, di) {
                    var v = Number(ds.data[i] || 0);
                    var pxHeight = Math.round((v / yTop) * 148); // 148 = bars height (180) minus label row (~32)
                    return '<i style="height:' + pxHeight + 'px;background:' + ramp[di % ramp.length] + '"></i>';
                }).join('');
                return '<div class="col"><div class="stack">' + stack + '</div><span class="mn">' + dbiEsc(lbl.split(' ')[0]) + '</span></div>';
            }).join('');

            var legend = datasets.map(function (ds, di) {
                return '<div class="lg"><span class="sq" style="background:' + ramp[di % ramp.length] + '"></span>' + dbiEsc(ds.label) + '</div>';
            }).join('');

            $c.html(
                '<div class="dbi-bars" style="flex:1">'
                + '<div class="yax"><span>' + yTop + '</span><span>' + (yTop / 2) + '</span><span>0</span></div>'
                + '<div class="gl" style="top:0"></div><div class="gl" style="top:50%"></div><div class="gl" style="bottom:22px"></div>'
                + cols
                + '</div>'
                + '<div class="dbi-legend" style="max-width:150px">' + legend + '</div>'
            );
        },
        error: function () {
            $('#dbiDeptChart').html('<div class="dbi-empty"><div class="t">No participation recorded yet</div></div>');
        }
    });
}
</script>
