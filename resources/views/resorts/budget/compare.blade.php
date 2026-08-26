@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
    <p>{{ $message }}</p>
</div>
@endif

@section('content')

{{-- Page-top banner for the Regenerate-AI action. Renders inline so the
     user can't miss it even if toastr is slow to load. Disappears on
     the next page load (single-use Laravel flash). --}}
@if(session('ai_flash_msg'))
    @php
        $aiAlertCls = ['success' => 'alert-success', 'warning' => 'alert-warning', 'error' => 'alert-danger'][session('ai_flash_kind', 'info')] ?? 'alert-info';
    @endphp
    <div class="container-fluid pt-3">
        <div class="alert {{ $aiAlertCls }} alert-dismissible fade show mb-0" role="alert">
            <strong>
                @switch(session('ai_flash_kind'))
                    @case('success') ✓ AI ready @break
                    @case('warning') ⚠ AI slow @break
                    @default ✗ AI unreachable
                @endswitch
            </strong>
            — {{ session('ai_flash_msg') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
@endif

<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>WORKFORCE PLANNING</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                @if($available_rank == "HR")
                    <div class="col-auto">
                        <div class="d-flex justify-content-end">
                            <a href="#" class="btn btn-theme">Send To Finance</a>
                            <a href="#revise-budgetmodal" data-bs-toggle="modal" class="btn btn-white ms-3">Revise Budget</a>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div>
            <div class="card">
                <div class="card-title">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm-6">
                            <div class="d-flex justify-content-start align-items-center">
                                <a href="{{ url()->previous() }}" class="me-3" title="Back">
                                    <img src="{{ URL::asset('resorts_assets/images/arrow-left.svg') }}" alt="back" class="img-fluid" />
                                </a>
                                {{-- Department name now sourced from the resort_departments
                                     row matching the route's $deptID, not hardcoded. --}}
                                <h3>{{ $department->name ?? 'Department' }}</h3>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex justify-content-sm-end align-items-center flex-wrap gap-2">
                                {{-- Budget totals computed from the live data instead
                                     of hardcoded "$12,241" / "$11,985" placeholders.
                                     Show monthly + annual together since the AI returns
                                     monthly and HR plans yearly. --}}
                                <span class="badge badge-dark" title="Monthly basic-salary spend (filled positions only)">
                                    HOD: {{ $currencySymbol }} {{ number_format($totalHodBudget, 0) }} / mo
                                    <span class="ms-1 opacity-75">({{ $currencySymbol }} {{ number_format($totalHodBudget * 12, 0) }} / yr)</span>
                                </span>
                                {{-- Vacancy highlight: the HOD total above is filled-positions
                                     only, so it reads lower than the manning-plan total. Surface
                                     the vacant count + cost so the gap is clearly "vacant
                                     positions", not a calculation error. --}}
                                @if(!empty($totalVacant) && $totalVacant > 0)
                                    <span class="badge" style="background:#fff3cd;color:#664d03;border:1px solid #ffe69c;"
                                          title="Vacant positions are budgeted in the manning plan but not in the HOD spend above — this is why the HOD total is lower than the manning-plan total.">
                                        + {{ $totalVacant }} vacant{{ (!empty($totalVacantCost) && $totalVacantCost > 0) ? ' (~'.$currencySymbol.' '.number_format($totalVacantCost, 0).' / mo)' : '' }} — not in HOD
                                    </span>
                                @endif
                                <span class="badge badge-themeWarning" title="Wisdom AI's monthly recommendation" id="wisdom-badge">
                                    Wisdom: {{ $currencySymbol }} <span id="wisdom-monthly-val">{{ number_format($totalAiBudget, 0) }}</span> / mo
                                    <span class="ms-1 opacity-75">({{ $currencySymbol }} <span id="wisdom-annual-val">{{ number_format($totalAiBudget * 12, 0) }}</span> / yr)</span>
                                </span>
                                {{-- Status chip + Regenerate button. The chip
                                     reflects the cached AI state on manning_responses;
                                     the button re-calls the FastAPI endpoint. --}}
                                <span id="ai-status-chip-wrapper">
                                @if($aiStatus === 'ready')
                                    <span class="badge badge-themeSuccess" title="AI suggestions generated {{ optional($aiGeneratedAt)->diffForHumans() }}">
                                        AI ready
                                    </span>
                                @elseif($aiStatus === 'pending')
                                    <span class="badge badge-themeGrayLight">AI generating…</span>
                                @elseif(in_array($aiStatus, ['failed', 'timeout'], true))
                                    <span class="badge badge-themeDanger" title="Click Regenerate to retry">
                                        AI {{ $aiStatus }}
                                    </span>
                                @endif
                                </span>
                                {{-- AJAX-only Regenerate AI. Replaces the
                                     previous full-page redirect to ?regenerate=1
                                     so the page no longer flashes / scrolls
                                     when HR clicks the button. JS handler
                                     below POSTs to the new endpoint and
                                     swaps the AI columns in place. --}}
                                <button type="button"
                                        id="ai-regenerate-btn"
                                        class="btn btn-sm btn-themeBlue"
                                        title="Re-run the AI workforce-planning analysis (in-place; no page reload)"
                                        data-route="{{ route('resort.budget.comparebudget.regenerateAi', ['id' => request()->route('id'), 'budgetid' => request()->route('budgetid')]) }}">
                                    <i class="fa-solid fa-rotate me-1"></i>Regenerate AI
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    {{-- BudgetController computes current_budget/ai_budget as ONE
                         month of basic salary × headcount (the FastAPI service
                         is also documented to return monthly budgets). HR asked
                         for the full year view too, so we keep the monthly
                         columns and add an Annual column that's just ×12. --}}
                    <table id="compare-budgettable" class="table table-compareBudget w-100">
                        <thead>
                            <tr>
                                <th></th>
                                <th colspan="3" class="text-nowrap text-center bg-theme text-white">HOD Budget</th>
                                <th colspan="3" class="text-nowrap text-center bg-yellow">Wisdom Suggested Budget</th>
                                <th></th>
                            </tr>
                            <tr>
                                <th class="text-nowrap">Position</th>
                                <th class="text-nowrap text-center">Headcount</th>
                                <th class="text-nowrap text-center">Monthly</th>
                                <th class="text-nowrap text-center">Annual</th>
                                <th class="text-nowrap text-center">Headcount</th>
                                <th class="text-nowrap text-center">Monthly</th>
                                <th class="text-nowrap text-center">Annual</th>
                                <th class="text-nowrap" style="min-width:420px;">Justified Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Each row is one position in this department. Numbers come
                                 from the SQL queries in the controller; the "Wisdom Suggested"
                                 columns come from the cached AI recommendation (or empty when
                                 the AI hasn't been called yet). --}}
                            @forelse($positions as $p)
                                @php
                                    $aiSet            = $p->ai_headcount !== null;
                                    $diffHeadcount    = $aiSet ? ($p->ai_headcount - $p->headcount) : 0;
                                    $diffBudget       = $aiSet ? ($p->ai_budget    - $p->current_budget) : 0;
                                    $aiHeadClass      = $diffHeadcount > 0 ? 'text-success' : ($diffHeadcount < 0 ? 'text-danger' : '');
                                    $aiBudgetClass    = $diffBudget    > 0 ? 'text-success' : ($diffBudget    < 0 ? 'text-danger' : '');
                                    $currentAnnual    = $p->current_budget * 12;
                                    $aiAnnual         = $aiSet ? ($p->ai_budget * 12) : null;
                                @endphp
                                {{-- data-position-id stamps the position
                                     id on the <tr> so the JS Regenerate
                                     handler can find each row and swap
                                     ONLY the AI cells (cols 5-8) without
                                     touching HOD numbers or the title. --}}
                                <tr data-position-id="{{ $p->id }}">
                                    <td>{{ $p->position_title }}</td>
                                    <td class="text-nowrap text-center">{{ str_pad($p->headcount, 2, '0', STR_PAD_LEFT) }}</td>
                                    <td class="text-nowrap text-center">{{ $currencySymbol }} {{ number_format($p->current_budget, 0) }}</td>
                                    <td class="text-nowrap text-center fw-bold">{{ $currencySymbol }} {{ number_format($currentAnnual, 0) }}</td>
                                    @if($aiSet)
                                        <td class="ai-cell-headcount text-nowrap text-center {{ $aiHeadClass }}">{{ str_pad($p->ai_headcount, 2, '0', STR_PAD_LEFT) }}</td>
                                        <td class="ai-cell-monthly text-nowrap text-center {{ $aiBudgetClass }}">{{ $currencySymbol }} {{ number_format($p->ai_budget, 0) }}</td>
                                        <td class="ai-cell-annual text-nowrap text-center fw-bold {{ $aiBudgetClass }}">{{ $currencySymbol }} {{ number_format($aiAnnual, 0) }}</td>
                                        <td class="ai-cell-justification" style="min-width:420px; max-width:520px; white-space:normal; vertical-align:top;">
                                            @if(!empty($p->ai_justification))
                                                {{-- Larger, paragraph-shaped justification so the
                                                     multi-sentence rationale from the AI is legible.
                                                     Word-wraps naturally. --}}
                                                <p class="m-0 ai-justification-text" style="font-size:14.5px; line-height:1.55; color:#1f2937;">
                                                    {{ $p->ai_justification }}
                                                </p>
                                            @endif
                                        </td>
                                    @else
                                        <td class="ai-cell-headcount text-nowrap text-center text-muted">—</td>
                                        <td class="ai-cell-monthly text-nowrap text-center text-muted">—</td>
                                        <td class="ai-cell-annual text-nowrap text-center text-muted">—</td>
                                        <td class="ai-cell-justification text-muted" style="min-width:420px; max-width:520px; white-space:normal; font-size:14px; vertical-align:top;">
                                            {{ $aiStatus === 'ready' ? '' : 'Click Regenerate AI to populate.' }}
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        No positions configured for this department yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($positions->isNotEmpty())
                            @php
                                $totalHodAnnual = $totalHodBudget * 12;
                                $totalAiAnnual  = $totalAiBudget  * 12;
                            @endphp
                            <tfoot>
                                <tr>
                                    <th>Total:</th>
                                    <th class="text-center">{{ $totalHodHeadcount }}</th>
                                    <th class="text-center">{{ $currencySymbol }} {{ number_format($totalHodBudget, 0) }}</th>
                                    <th class="text-center">{{ $currencySymbol }} {{ number_format($totalHodAnnual, 0) }}</th>
                                    <th class="text-lightblue text-center" id="total-ai-headcount">{{ $totalAiHeadcount }}</th>
                                    <th class="text-lightblue text-center" id="total-ai-monthly">{{ $currencySymbol }} {{ number_format($totalAiBudget, 0) }}</th>
                                    <th class="text-lightblue text-center" id="total-ai-annual">{{ $currencySymbol }} {{ number_format($totalAiAnnual, 0) }}</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Revise Budget modal — unchanged from the original mockup; wiring it up
     to actually persist a revision is a separate piece of work. -->
<div class="modal fade" id="revise-budgetmodal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Revise Budget</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-20">
                    <textarea class="form-control" rows="7" placeholder="Add Comment Regarding Revision"></textarea>
                </div>
            </div>
            <div class="modal-footer justify-content-end">
                <button type="button" class="btn btn-sm btn-themeGray me-2" data-bs-dismiss="modal">Cancel</button>
                <a href="#" class="btn btn-sm btn-theme">Submit</a>
            </div>
        </div>
    </div>
</div>

@endsection

@section('import-css')
<style>
    /* Escape hatch for the global `.table-compareBudget p` rule in
       default.css line ~4506 which clamps every <p> in this table to
       2 lines with -webkit-line-clamp. We need the full AI justification
       (180-400 chars, often 3-4 sentences) visible. Selector specificity
       beats `.table-compareBudget p` so this wins regardless of load
       order. */
    .table-compareBudget p.ai-justification-text {
        display: block !important;
        -webkit-line-clamp: unset !important;
        -webkit-box-orient: initial !important;
        overflow: visible !important;
        white-space: normal !important;
    }
</style>
@endsection

@section('import-scripts')
{{-- Flash toast for the legacy ?regenerate=1 path. Kept as a fallback
     in case anything still navigates with the query param — the new
     AJAX flow below doesn't trigger this. --}}
@if(session('ai_flash_msg'))
<script>
    $(function () {
        var kind = @json(session('ai_flash_kind', 'info'));
        var msg  = @json(session('ai_flash_msg'));
        if (typeof toastr !== 'undefined' && toastr[kind]) {
            toastr[kind](msg, kind === 'success' ? 'Done' : (kind === 'warning' ? 'Slow AI service' : 'AI service unreachable'), {
                positionClass: 'toast-bottom-right',
                timeOut: 8000
            });
        } else {
            alert(msg);
        }
    });
</script>
@endif

{{-- ─── AJAX Regenerate AI ───────────────────────────────────────────
     Button click → POST to the new regenerateAi endpoint → server hits
     the FastAPI service, persists new suggestions, returns the per-row
     payload → JS swaps the AI cells in place. No page reload. --}}
<script>
$(function () {
    var $btn = $('#ai-regenerate-btn');
    if (!$btn.length) return;

    var currencySymbol = @json($currencySymbol);

    function fmtMoney(n) {
        return currencySymbol + ' ' + Math.round(Number(n) || 0).toLocaleString();
    }
    function pad2(n) { n = String(n); return n.length < 2 ? '0' + n : n; }

    $btn.on('click', function () {
        if ($btn.hasClass('disabled')) return;
        var url = $btn.data('route');
        var origHtml = $btn.html();

        $btn.addClass('disabled').prop('disabled', true)
            .html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Regenerating…');

        // Set the status chip to "generating…" while the call is in flight.
        $('#ai-status-chip-wrapper').html('<span class="badge badge-themeGrayLight">AI generating…</span>');

        $.ajax({
            url: url,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            // Per-row LLM cost + network: comfortably below the
            // Hostinger 60 s proxy wall but allows a real LLM round-trip.
            timeout: 90000,
        })
        .done(function (resp) {
            if (!resp || !resp.rows) {
                toastr.error((resp && resp.message) || 'AI regeneration failed.',
                    'Error', { positionClass: 'toast-bottom-right' });
                $('#ai-status-chip-wrapper').html('<span class="badge badge-themeDanger">AI failed</span>');
                return;
            }

            // Swap each row's AI cells. Position id ties rows together
            // so the JS doesn't care about row order.
            resp.rows.forEach(function (r) {
                var $row = $('tr[data-position-id="' + r.position_id + '"]');
                if (!$row.length) return;

                var hasAi = r.ai_headcount !== null && r.ai_headcount !== undefined;
                if (hasAi) {
                    $row.find('.ai-cell-headcount')
                        .removeClass('text-muted')
                        .text(pad2(r.ai_headcount));
                    $row.find('.ai-cell-monthly')
                        .removeClass('text-muted')
                        .text(fmtMoney(r.ai_budget));
                    $row.find('.ai-cell-annual')
                        .removeClass('text-muted')
                        .addClass('fw-bold')
                        .text(fmtMoney(r.ai_annual));
                    $row.find('.ai-cell-justification')
                        .removeClass('text-muted')
                        .html(r.ai_justification
                            ? '<p class="m-0 ai-justification-text" style="font-size:14.5px; line-height:1.55; color:#1f2937;">' + $('<div>').text(r.ai_justification).html() + '</p>'
                            : '');
                } else {
                    $row.find('.ai-cell-headcount').addClass('text-muted').text('—');
                    $row.find('.ai-cell-monthly').addClass('text-muted').text('—');
                    $row.find('.ai-cell-annual').addClass('text-muted').text('—');
                    $row.find('.ai-cell-justification').addClass('text-muted')
                        .html('<span style="font-size:14px;">No AI recommendation.</span>');
                }
            });

            // Footer totals.
            $('#total-ai-headcount').text(resp.total_headcount || 0);
            $('#total-ai-monthly').text(fmtMoney(resp.total_budget));
            $('#total-ai-annual').text(fmtMoney(resp.total_annual));

            // Header badges + status chip.
            $('#wisdom-monthly-val').text(Math.round(Number(resp.total_budget) || 0).toLocaleString());
            $('#wisdom-annual-val').text(Math.round(Number(resp.total_annual) || 0).toLocaleString());

            if (resp.status === 'ready') {
                $('#ai-status-chip-wrapper').html('<span class="badge badge-themeSuccess" title="Just regenerated">AI ready</span>');
                toastr.success(resp.message || 'AI workforce-planning analysis regenerated.',
                    'Done', { positionClass: 'toast-bottom-right', timeOut: 6000 });
            } else if (resp.status === 'timeout') {
                $('#ai-status-chip-wrapper').html('<span class="badge badge-themeDanger">AI timeout</span>');
                toastr.warning(resp.message || 'AI did not respond in time.',
                    'Slow AI service', { positionClass: 'toast-bottom-right', timeOut: 8000 });
            } else {
                $('#ai-status-chip-wrapper').html('<span class="badge badge-themeDanger">AI ' + (resp.status || 'failed') + '</span>');
                toastr.error(resp.message || 'AI workforce-planning analysis failed.',
                    'AI service unreachable', { positionClass: 'toast-bottom-right', timeOut: 8000 });
            }
        })
        .fail(function (xhr, textStatus) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message)
                || (textStatus === 'timeout' ? 'AI request timed out after 90 s.' : 'Network error contacting the AI service.');
            toastr.error(msg, 'Error', { positionClass: 'toast-bottom-right', timeOut: 8000 });
            $('#ai-status-chip-wrapper').html('<span class="badge badge-themeDanger">AI failed</span>');
        })
        .always(function () {
            $btn.removeClass('disabled').prop('disabled', false).html(origHtml);
        });
    });
});
</script>
@endsection
