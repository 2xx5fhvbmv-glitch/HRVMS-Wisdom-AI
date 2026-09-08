@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@section('content')

<div class="body-wrapper pb-5">
    <div class="container-fluid">

        @php
            // Reshape the controller's $positions into the exact object shape
            // the client-side render function expects — the same shape is
            // produced again (from the regenerate-AI response) in JS, so both
            // the first paint and a live regenerate run through one render
            // function with no duplicated logic.
            $P = $positions->map(function ($p) {
                $aiSet = $p->ai_headcount !== null;
                $isNew = ((int) $p->headcount === 0) && ((float) $p->current_budget === 0.0)
                    && $aiSet && ((int) $p->ai_headcount > 0);
                return [
                    'pid'   => $p->id,
                    't'     => $p->position_title,
                    'hod'   => [
                        'hc' => (int) $p->headcount,
                        'mo' => (float) $p->current_budget,
                        'yr' => (float) $p->current_budget * 12,
                    ],
                    'wai'   => [
                        'hc' => $aiSet ? (int) $p->ai_headcount : 0,
                        'mo' => $aiSet ? (float) $p->ai_budget : 0,
                        'yr' => $aiSet ? (float) $p->ai_budget * 12 : 0,
                    ],
                    'aiSet'  => $aiSet,
                    'isNew'  => $isNew,
                    'reason' => $aiSet ? (string) $p->ai_justification : '',
                ];
            })->values();
        @endphp

        <div class="cb-card">
            <div class="cb-hd">
                <a href="{{ url()->previous() }}" class="cb-back" aria-label="Back" title="Back">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                </a>
                <div>
                    <div class="cb-kick">Compare Budget</div>
                    <div class="cb-dept">{{ $department->name ?? 'Department' }}</div>
                </div>
                <div class="cb-sp"></div>
                <button type="button" id="cb-regen-btn" class="cb-regen"
                        title="Re-run the AI workforce-planning analysis"
                        data-route="{{ route('resort.budget.comparebudget.regenerateAi', ['id' => request()->route('id'), 'budgetid' => request()->route('budgetid')]) }}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 4v6h-6M1 20v-6h6M20.5 9A9 9 0 006 5.3L1 10M23 14l-5 4.7A9 9 0 013.5 15"/></svg>
                    <span id="cb-regen-label">Regenerate AI</span>
                </button>
            </div>

            <!-- summary -->
            <div class="cb-cmp">
                <div class="cb-box">
                    <div class="cb-lbl">HOD Budget</div>
                    <div class="cb-big cb-tnum" id="cb-hod-monthly">{{ $currencySymbol }}0<span>/mo</span></div>
                    <div class="cb-kv">
                        <div class="cb-r"><span class="cb-k">Annual</span><span class="cb-v" id="cb-hod-annual">{{ $currencySymbol }}0</span></div>
                        <div class="cb-r"><span class="cb-k">Positions</span><span class="cb-v cb-tnum" id="cb-hod-positions">0</span></div>
                    </div>
                </div>
                <div class="cb-conn">
                    <div class="cb-arw"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></div>
                    <div class="cb-amt cb-tnum" id="cb-conn-amt">{{ $currencySymbol }}0/mo</div>
                    <div class="cb-meta cb-tnum" id="cb-conn-meta"></div>
                </div>
                <div class="cb-box cb-w">
                    <div class="cb-lbl">WAI <span class="cb-ai" id="cb-ai-chip"><span class="cb-d"></span><span id="cb-ai-chip-text">AI not generated</span></span></div>
                    <div class="cb-big cb-tnum" id="cb-wai-monthly">{{ $currencySymbol }}0<span>/mo</span></div>
                    <div class="cb-kv">
                        <div class="cb-r"><span class="cb-k">Annual</span><span class="cb-v" id="cb-wai-annual">{{ $currencySymbol }}0</span></div>
                        <div class="cb-r"><span class="cb-k">Positions</span><span class="cb-v cb-tnum" id="cb-wai-positions">0</span></div>
                    </div>
                </div>
            </div>

            <!-- comparison table -->
            <div class="cb-tbl-wrap">
                <table id="cb-table">
                    <thead>
                        <tr>
                            <th class="cb-c-pos" rowspan="2">Position</th>
                            <th class="cb-grp" colspan="3">HOD Budget</th>
                            <th class="cb-grp cb-wai cb-gstart" colspan="3">WAI Suggested</th>
                            <th class="cb-c-reason" rowspan="2">Justified reason</th>
                        </tr>
                        <tr class="cb-sub">
                            <th class="cb-num cb-c-hc">Headcount</th><th class="cb-num cb-c-mo">Monthly</th><th class="cb-num cb-c-yr">Annual</th>
                            <th class="cb-num cb-c-hc cb-gstart">Headcount</th><th class="cb-num cb-c-mo">Monthly</th><th class="cb-num cb-c-yr">Annual</th>
                        </tr>
                    </thead>
                    <tbody id="cb-body"></tbody>
                    <tfoot><tr id="cb-foot"></tr></tfoot>
                </table>
            </div>
        </div>

    </div>
</div>

@endsection

@section('import-css')
@include('resorts.budget._compare_budget_styles')
@endsection

@section('import-scripts')
<script>
$(function () {
    var currencySymbol = @json($currencySymbol);
    var P = @json($P);
    var aiStatus = @json($aiStatus);

    function m(n) { return currencySymbol + Math.round(Number(n) || 0).toLocaleString('en-US'); }
    function pad2(n) { n = String(n); return n.length < 2 ? '0' + n : n; }
    function esc(s) {
        return $('<div>').text(s == null ? '' : String(s)).html();
    }
    // The AI justification sometimes ends with an inline "Risk: ..." clause
    // (there is no separate risk field in the data) — split it out so it can
    // render as its own line, same as the finalized reference design.
    function splitReason(text) {
        text = text || '';
        var match = text.match(/\bRisk:\s*/i);
        if (!match) return { reason: text, risk: '' };
        return {
            reason: text.slice(0, match.index).trim(),
            risk: text.slice(match.index + match[0].length).trim()
        };
    }

    function rowHtml(p) {
        var hodHc = pad2(p.hod.hc);
        var waiCells;
        if (!p.aiSet) {
            waiCells =
                '<td class="cb-num cb-c-hc cb-gstart"><span class="cb-hc cb-zero">—</span></td>' +
                '<td class="cb-num cb-c-mo"><span class="cb-money cb-zero">—</span></td>' +
                '<td class="cb-num cb-c-yr"><span class="cb-money cb-zero">—</span></td>' +
                '<td class="cb-reason"><span class="cb-hint">Click Regenerate AI to populate.</span></td>';
        } else {
            var changed = (p.hod.mo !== p.wai.mo) || (p.hod.hc !== p.wai.hc);
            var flag = '';
            if (p.isNew) {
                flag = '<span class="cb-pill cb-new">New role</span>';
            } else if (changed && p.hod.mo > 0 && p.wai.mo > p.hod.mo) {
                flag = '<span class="cb-delta cb-up">+' + Math.round((p.wai.mo - p.hod.mo) / p.hod.mo * 100) + '%</span>';
            }
            var rp = splitReason(p.reason);
            waiCells =
                '<td class="cb-num cb-c-hc cb-gstart"><span class="cb-hc">' + pad2(p.wai.hc) + '</span></td>' +
                '<td class="cb-num cb-c-mo"><div class="cb-mstack"><span class="cb-money">' + m(p.wai.mo) + '</span>' + flag + '</div></td>' +
                '<td class="cb-num cb-c-yr"><span class="cb-money">' + m(p.wai.yr) + '</span></td>' +
                '<td class="cb-reason">' + (rp.reason ? esc(rp.reason) : '') +
                    (rp.risk ? '<div class="cb-risk"><b>Risk</b>' + esc(rp.risk) + '</div>' : '') + '</td>';
        }
        return '<tr>' +
            '<td class="cb-pos">' + esc(p.t) + '</td>' +
            '<td class="cb-num cb-c-hc"><span class="cb-hc' + (p.hod.hc === 0 ? ' cb-zero' : '') + '">' + hodHc + '</span></td>' +
            '<td class="cb-num cb-c-mo"><span class="cb-money' + (p.hod.mo === 0 ? ' cb-zero' : '') + '">' + m(p.hod.mo) + '</span></td>' +
            '<td class="cb-num cb-c-yr"><span class="cb-money' + (p.hod.yr === 0 ? ' cb-zero' : '') + '">' + m(p.hod.yr) + '</span></td>' +
            waiCells +
            '</tr>';
    }

    function sum(arr, side, field) {
        return arr.reduce(function (a, p) { return a + p[side][field]; }, 0);
    }

    function updateAiChip(status) {
        var $chip = $('#cb-ai-chip');
        var text = 'AI not generated';
        var ready = false;
        if (status === 'ready') { text = 'AI ready'; ready = true; }
        else if (status === 'pending') { text = 'AI generating…'; }
        else if (status === 'timeout') { text = 'AI timeout'; }
        else if (status === 'failed') { text = 'AI failed'; }
        $chip.toggleClass('ready', ready);
        $('#cb-ai-chip-text').text(text);
    }

    function renderAll() {
        // table
        $('#cb-body').html(P.length ? P.map(rowHtml).join('') : '<tr><td colspan="8" class="cb-zero" style="text-align:center;padding:32px 14px;">No positions configured for this department yet.</td></tr>');

        var totalHodHc = sum(P, 'hod', 'hc'), totalHodMo = sum(P, 'hod', 'mo'), totalHodYr = sum(P, 'hod', 'yr');
        var totalWaiHc = sum(P, 'wai', 'hc'), totalWaiMo = sum(P, 'wai', 'mo'), totalWaiYr = sum(P, 'wai', 'yr');

        if (P.length) {
            $('#cb-foot').html(
                '<td class="cb-pos"><span class="cb-lbl">Total</span></td>' +
                '<td class="cb-num cb-tnum cb-c-hc">' + pad2(totalHodHc) + '</td>' +
                '<td class="cb-num cb-tnum cb-c-mo">' + m(totalHodMo) + '</td>' +
                '<td class="cb-num cb-tnum cb-c-yr">' + m(totalHodYr) + '</td>' +
                '<td class="cb-num cb-tnum cb-c-hc cb-gstart">' + pad2(totalWaiHc) + '</td>' +
                '<td class="cb-num cb-tnum cb-c-mo">' + m(totalWaiMo) + '</td>' +
                '<td class="cb-num cb-tnum cb-c-yr">' + m(totalWaiYr) + '</td>' +
                '<td class="cb-reason"></td>'
            );
        } else {
            $('#cb-foot').empty();
        }

        // summary boxes
        $('#cb-hod-monthly').html(m(totalHodMo) + '<span>/mo</span>');
        $('#cb-hod-annual').text(m(totalHodYr));
        $('#cb-hod-positions').html(pad2(totalHodHc) + (window.cbTotalVacant > 0 ? '<span class="cb-vac">+' + window.cbTotalVacant + ' vacant</span>' : ''));

        $('#cb-wai-monthly').html(m(totalWaiMo) + '<span>/mo</span>');
        $('#cb-wai-annual').text(m(totalWaiYr));
        $('#cb-wai-positions').text(pad2(totalWaiHc));

        // connector
        var deltaMo = totalWaiMo - totalHodMo;
        var deltaHc = totalWaiHc - totalHodHc;
        var sign = deltaMo >= 0 ? '+' : '−';
        $('#cb-conn-amt').text(sign + m(Math.abs(deltaMo)) + '/mo').toggleClass('down', deltaMo < 0);
        var metaParts = [];
        if (totalHodMo > 0) {
            metaParts.push((deltaMo >= 0 ? '+' : '−') + Math.round(Math.abs(deltaMo) / totalHodMo * 100) + '%');
        }
        if (deltaHc !== 0) {
            metaParts.push((deltaHc > 0 ? '+' : '−') + Math.abs(deltaHc) + ' role' + (Math.abs(deltaHc) === 1 ? '' : 's'));
        }
        $('#cb-conn-meta').text(metaParts.join(' · '));
    }

    window.cbTotalVacant = {{ (int) $totalVacant }};
    updateAiChip(aiStatus);
    renderAll();

    // ─── Regenerate AI ───────────────────────────────────────────────
    var $btn = $('#cb-regen-btn');
    if (!$btn.length) return;

    $btn.on('click', function () {
        if ($btn.prop('disabled')) return;
        var url = $btn.data('route');

        $btn.prop('disabled', true).addClass('spinning');
        $('#cb-regen-label').text('Regenerating…');
        updateAiChip('pending');

        $.ajax({
            url: url,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            timeout: 90000,
        })
        .done(function (resp) {
            if (!resp || !resp.rows) {
                toastr.error((resp && resp.message) || 'AI regeneration failed.', 'Error', { positionClass: 'toast-bottom-right' });
                updateAiChip('failed');
                return;
            }

            var byId = {};
            resp.rows.forEach(function (r) { byId[r.position_id] = r; });
            P.forEach(function (p) {
                var r = byId[p.pid];
                if (!r) return;
                p.aiSet = r.ai_headcount !== null && r.ai_headcount !== undefined;
                p.wai = { hc: r.ai_headcount || 0, mo: r.ai_budget || 0, yr: r.ai_annual || 0 };
                p.reason = r.ai_justification || '';
                p.isNew = p.hod.hc === 0 && p.hod.mo === 0 && p.aiSet && p.wai.hc > 0;
            });

            updateAiChip(resp.status);
            renderAll();

            if (resp.status === 'ready') {
                toastr.success(resp.message || 'AI workforce-planning analysis regenerated.', 'Done', { positionClass: 'toast-bottom-right', timeOut: 6000 });
            } else if (resp.status === 'timeout') {
                toastr.warning(resp.message || 'AI did not respond in time.', 'Slow AI service', { positionClass: 'toast-bottom-right', timeOut: 8000 });
            } else {
                toastr.error(resp.message || 'AI workforce-planning analysis failed.', 'AI service unreachable', { positionClass: 'toast-bottom-right', timeOut: 8000 });
            }
        })
        .fail(function (xhr, textStatus) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message)
                || (textStatus === 'timeout' ? 'AI request timed out after 90 seconds.' : 'Network error contacting the AI service.');
            toastr.error(msg, 'Error', { positionClass: 'toast-bottom-right', timeOut: 8000 });
            updateAiChip('failed');
        })
        .always(function () {
            $btn.prop('disabled', false).removeClass('spinning');
            $('#cb-regen-label').text('Regenerate AI');
        });
    });

    @if(session('ai_flash_msg'))
    (function () {
        var kind = @json(session('ai_flash_kind', 'info'));
        var msg  = @json(session('ai_flash_msg'));
        if (typeof toastr !== 'undefined' && toastr[kind]) {
            toastr[kind](msg, kind === 'success' ? 'Done' : (kind === 'warning' ? 'Slow AI service' : 'AI service unreachable'), {
                positionClass: 'toast-bottom-right',
                timeOut: 8000
            });
        }
    })();
    @endif
});
</script>
@endsection
