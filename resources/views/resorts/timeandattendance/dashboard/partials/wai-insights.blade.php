@php
    $waiInsights = $waiInsights ?? [];

    // Check definitions — the only place that names a check. Add/remove/
    // rename a check here and the card, hero, and modal all follow with
    // zero other changes. `label` is a lowercase sentence fragment so it
    // can be reused as-is inside the hero sentence ("N employees over
    // their {label}") and title-cased for the row name — one string
    // driving both, per the "no hard-coded issue names" requirement.
    // ponytail: critical_threshold is a placeholder (no real severe-breach
    // policy exists yet in getWaiInsights()) — swap in real business
    // thresholds per check once they're defined.
    $waiCheckDefs = [
        ['key' => 'weekly_hours', 'label' => 'weekly hours limit',           'threshold' => 0, 'critical_threshold' => 20],
        ['key' => 'overtime',     'label' => 'overtime hours limit',        'threshold' => 0, 'critical_threshold' => 20],
        ['key' => 'no_break',     'label' => 'mandatory break requirement', 'threshold' => 0, 'critical_threshold' => 20],
        ['key' => 'day_off',      'label' => 'day-off balance limit',       'threshold' => 0, 'critical_threshold' => 20],
    ];

    // Reshape into the render-ready checks array. Purely a view-layer
    // derivation of status from count vs. threshold — getWaiInsights()'s
    // own counting/query logic is untouched.
    $waiChecks = array_map(function ($def) use ($waiInsights) {
        $count     = $waiInsights[$def['key']]['count'] ?? 0;
        $employees = $waiInsights[$def['key']]['employees'] ?? [];

        if ($count > $def['critical_threshold']) {
            $status = 'critical';
        } elseif ($count > $def['threshold']) {
            $status = 'flagged';
        } else {
            $status = 'clear';
        }

        return array_merge($def, [
            'count'     => $count,
            'employees' => $employees,
            'status'    => $status,
        ]);
    }, $waiCheckDefs);

    $waiFlagged = array_values(array_filter($waiChecks, fn ($c) => $c['status'] !== 'clear'));
    $waiClear   = array_values(array_filter($waiChecks, fn ($c) => $c['status'] === 'clear'));

    // Hero = highest severity first, then highest count.
    $waiHeroList = $waiFlagged;
    usort($waiHeroList, function ($a, $b) {
        $rank = ['critical' => 1, 'flagged' => 0];
        $bySeverity = $rank[$b['status']] <=> $rank[$a['status']];
        return $bySeverity !== 0 ? $bySeverity : ($b['count'] <=> $a['count']);
    });
    $waiHero        = $waiHeroList[0] ?? null;
    $waiFlaggedCount = count($waiFlagged);

    // Rows render flagged-first, clear checks below.
    $waiSortedChecks = array_merge($waiFlagged, $waiClear);
@endphp

<style>
    /* Neutral/geometry tokens (--teal/--teal-2/--lime/--ink/--muted/
       --faint/--line/--line-2) now come from the shared :root palette
       (resorts/layouts/_design_tokens.blade.php). --teal-mid stays local
       (used as a gradient stop, not a flat teal — see mapping guide).
       --ok/--ok-bg folded into --positive/--positive-bg (same #1f9d6b/
       #e9f7f0 value, now canonical). --warn/--err/--err-bg are exact-hex
       matches for the shared --warning/--error/--error-bg, now pointing
       there too. --warn-bg has no shared equivalent (#fff6e5 doesn't
       match --warning-bg's #FBF0DC) and stays local. */
    .card-wiINsight {
        --teal-mid: #0e8a9e;
        --warn: var(--warning); --warn-bg: #fff6e5; --err: var(--error); --err-bg: var(--error-bg);
    }
    .card-wiINsight { display: flex; flex-direction: column; padding: 0; overflow: hidden; border-radius: 16px; }

    .wai-head { position: relative; overflow: hidden; padding: 17px 18px; }
    .wai-head::before {
        content: ""; position: absolute; inset: 0; pointer-events: none;
        background: linear-gradient(110deg, var(--teal) 0%, #0e8a9e 40%, #7fa61e 70%, var(--lime) 100%);
    }
    .wai-head::after {
        content: ""; position: absolute; inset: 0; pointer-events: none;
        background: linear-gradient(110deg, rgba(1,40,48,.35), transparent 55%);
    }
    .wai-head h2 { position: relative; color: #fff; font-size: 18px; font-weight: 600; margin: 0; }

    .wai-body { padding: 16px; flex: 1 1 auto; min-height: 0; overflow-y: auto; }

    .wai-hero {
        border-radius: 14px;
        padding: 14px 16px;
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 14px;
    }
    .wai-hero.is-alert {
        background: linear-gradient(150deg, #fbeeeb, #fdf4f2);
        border: 1px solid #f4d9d2;
    }
    .wai-hero.is-clear {
        background: linear-gradient(150deg, #eef8f2, #f3faf6);
        border: 1px solid #d7ecdf;
    }
    .wai-hero-count { font-size: 32px; font-weight: 600; color: var(--err); line-height: 1; flex-shrink: 0; }
    .wai-hero-icon { font-size: 22px; color: var(--positive); flex-shrink: 0; }
    .wai-hero-text { flex: 1 1 auto; min-width: 0; }
    .wai-hero-text p { margin: 0; font-size: 14px; color: var(--ink); line-height: 1.4; }
    .wai-hero-text small { color: var(--muted); font-size: 10.5px; font-weight: 500; }
    .wai-hero-link { display: inline-block; margin-top: 6px; font-size: 14px; font-weight: 600; color: var(--err); }
    .wai-hero-link:hover { color: var(--err); }

    .wai-row { display: flex; align-items: flex-start; gap: 12px; padding: 10px 2px; border-bottom: 1px solid var(--line-2); }
    .wai-row:last-child { border-bottom: none; }
    .wai-row-icon {
        width: 32px; height: 32px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        font-size: 14px; flex-shrink: 0;
        align-self: flex-start;
    }
    .wai-row-icon.is-ok { background: var(--positive-bg); color: var(--positive); }
    .wai-row-icon.is-flagged { background: var(--err-bg); color: var(--err); }
    .wai-row-body { flex: 1 1 auto; min-width: 0; }
    .wai-row-body h6 { margin: 0; font-size: 14px; font-weight: 600; color: var(--ink); }
    .wai-row-status { font-size: 11px; font-weight: 500; margin-top: 2px; }
    .wai-row-status.is-clear { color: var(--faint); }
    .wai-row-status.is-flagged { color: var(--err); }
    .wai-row-status a { display: block; margin-top: 2px; font-size: 11px; font-weight: 600; color: var(--teal); }
    .wai-row-count { font-size: 14px; font-weight: 600; flex-shrink: 0; }
    .wai-row-count.is-clear { color: var(--faint); font-weight: 400; }
    .wai-row-count.is-flagged { color: var(--err); }
</style>

<div class="card card-wiINsight" @if(!empty($cardId)) id="{{ $cardId }}" @endif>
    <div class="wai-head">
        <h2>WAI Insights</h2>
    </div>

    <div class="wai-body">
        @if ($waiHero)
            <div class="wai-hero is-alert">
                <div class="wai-hero-count">{{ $waiHero['count'] }}</div>
                <div class="wai-hero-text">
                    @if ($waiFlaggedCount > 1)
                        <p><strong>{{ $waiFlaggedCount }} checks need attention.</strong></p>
                        <small>Most urgent: {{ $waiHero['count'] }} employees over their {{ $waiHero['label'] }}.</small>
                    @else
                        <p>{{ $waiHero['count'] }} employees over their {{ $waiHero['label'] }}.</p>
                    @endif
                    <a href="#" class="lnk wai-hero-link wai-view-all"
                       data-wai-key="{{ $waiHero['key'] }}" data-wai-title="{{ ucfirst($waiHero['label']) }}"
                       data-details="waiInsightModal">Review &rarr;</a>
                </div>
            </div>
        @else
            <div class="wai-hero is-clear">
                <i class="fa-solid fa-circle-check wai-hero-icon"></i>
                <div class="wai-hero-text">
                    <p>All checks passing this week.</p>
                </div>
            </div>
        @endif

        @foreach ($waiSortedChecks as $check)
            <div class="wai-row">
                @if ($check['status'] === 'critical')
                    <div class="wai-row-icon is-flagged"><i class="fa-solid fa-xmark"></i></div>
                @elseif ($check['status'] === 'flagged')
                    <div class="wai-row-icon is-flagged"><i class="fa-solid fa-triangle-exclamation"></i></div>
                @else
                    <div class="wai-row-icon is-ok"><i class="fa-solid fa-check"></i></div>
                @endif
                <div class="wai-row-body">
                    <h6>{{ ucfirst($check['label']) }}</h6>
                    @if ($check['status'] === 'clear')
                        <div class="wai-row-status is-clear">All clear</div>
                    @else
                        <div class="wai-row-status is-flagged">
                            {{ $check['count'] }} {{ $check['count'] == 1 ? 'employee' : 'employees' }} flagged
                            <a href="#" class="lnk wai-view-all"
                               data-wai-key="{{ $check['key'] }}" data-wai-title="{{ ucfirst($check['label']) }}"
                               data-details="waiInsightModal">View details &rarr;</a>
                        </div>
                    @endif
                </div>
                <div class="wai-row-count {{ $check['status'] === 'clear' ? 'is-clear' : 'is-flagged' }}">{{ $check['count'] }}</div>
            </div>
        @endforeach
    </div>
</div>

{{-- WAI Insights details modal — shared frosted-modal chrome
     (partials/_wai_insight_modals.blade.php), employee list stays JS-built
     from the in-memory waiData below since it's a photo/name/detail list,
     not a program-breakdown table. --}}
<div class="wai-backdrop" id="waiInsightModal">
    <div class="wai-modal" role="dialog" aria-modal="true">
        <button class="m-x" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt" id="waiInsightModalTitle">WAI Insights</div>
        <div class="m-tablewrap">
            <div class="m-tscroll">
                <ul class="list-unstyled mb-0 p-3" id="waiInsightModalBody"></ul>
            </div>
        </div>
    </div>
</div>

{{-- Shared frosted-modal chrome/JS (.wai-backdrop/.wai-modal open-close,
     Escape/outside-click) — this module has no recommendation modal
     trigger, but reuses the same open-close mechanism for #waiInsightModal
     above rather than a third bespoke JS implementation. --}}
@include('partials._wai_insight_modals')

<script>
    (function () {
        var waiData = @json($waiInsights);

        function escapeHtml(value) {
            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        document.querySelectorAll('.wai-view-all').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var key = this.getAttribute('data-wai-key');
                var title = this.getAttribute('data-wai-title');
                var list = (waiData[key] && waiData[key].employees) ? waiData[key].employees : [];

                var titleEl = document.getElementById('waiInsightModalTitle');
                var bodyEl = document.getElementById('waiInsightModalBody');
                if (titleEl) titleEl.textContent = title;
                if (!bodyEl) return;

                if (!list.length) {
                    bodyEl.innerHTML = '<li class="text-muted">No employees to show.</li>';
                    return;
                }

                bodyEl.innerHTML = list.map(function (emp) {
                    return '<li class="d-flex align-items-center justify-content-between mb-3">'
                        + '<div class="d-flex align-items-center gap-2">'
                        + '<div class="img-circle"><img src="' + escapeHtml(emp.photo) + '" alt="" width="36" height="36" style="border-radius:50%;object-fit:cover;"></div>'
                        + '<span>' + escapeHtml(emp.name) + '</span>'
                        + '</div>'
                        + '<small class="text-muted">' + escapeHtml(emp.detail) + '</small>'
                        + '</li>';
                }).join('');
            });
        });
    })();
</script>
