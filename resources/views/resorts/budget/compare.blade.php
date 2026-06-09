@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
    <p>{{ $message }}</p>
</div>
@endif

@section('content')

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
                            <a href="#" class="btn btn-theme">Sent To Finance</a>
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
                                     of hardcoded "$12,241" / "$11,985" placeholders. --}}
                                <span class="badge badge-dark">
                                    HOD Budget: {{ $currencySymbol }} {{ number_format($totalHodBudget, 0) }}
                                </span>
                                <span class="badge badge-themeWarning">
                                    Wisdom Suggested: {{ $currencySymbol }} {{ number_format($totalAiBudget, 0) }}
                                </span>
                                {{-- Status chip + Regenerate button. The chip
                                     reflects the cached AI state on manning_responses;
                                     the button re-calls the FastAPI endpoint. --}}
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
                                <a href="?regenerate=1"
                                   id="ai-regenerate-link"
                                   class="btn btn-sm btn-themeBlue"
                                   title="Re-run the AI workforce-planning analysis for this department/budget"
                                   onclick="this.innerHTML='<i class=&quot;fa-solid fa-spinner fa-spin me-1&quot;></i>Regenerating…'; this.classList.add('disabled');">
                                    <i class="fa-solid fa-rotate me-1"></i>Regenerate AI
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="compare-budgettable" class="table table-compareBudget w-100">
                        <thead>
                            <tr>
                                <th></th>
                                <th colspan="2" class="text-nowrap text-center bg-theme text-white">HOD Budget</th>
                                <th colspan="2" class="text-nowrap text-center bg-yellow">Wisdom Suggested Budget</th>
                                <th></th>
                            </tr>
                            <tr>
                                <th class="text-nowrap">Position</th>
                                <th class="text-nowrap text-center">Headcount</th>
                                <th class="text-nowrap text-center">Total Budget</th>
                                <th class="text-nowrap text-center">Headcount</th>
                                <th class="text-nowrap text-center">Total Budget</th>
                                <th class="text-nowrap">Justified Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Each row is one position in this department. Numbers come
                                 from the SQL queries in the controller; the "Wisdom Suggested"
                                 columns come from the cached AI recommendation (or empty when
                                 the AI hasn't been called yet). --}}
                            @forelse($positions as $p)
                                @php
                                    $aiSet         = $p->ai_headcount !== null;
                                    $diffHeadcount = $aiSet ? ($p->ai_headcount - $p->headcount) : 0;
                                    $diffBudget    = $aiSet ? ($p->ai_budget    - $p->current_budget) : 0;
                                    $aiHeadClass   = $diffHeadcount > 0 ? 'text-success' : ($diffHeadcount < 0 ? 'text-danger' : '');
                                    $aiBudgetClass = $diffBudget    > 0 ? 'text-success' : ($diffBudget    < 0 ? 'text-danger' : '');
                                @endphp
                                <tr>
                                    <td>{{ $p->position_title }}</td>
                                    <td class="text-nowrap text-center">{{ str_pad($p->headcount, 2, '0', STR_PAD_LEFT) }}</td>
                                    <td class="text-nowrap text-center">{{ $currencySymbol }} {{ number_format($p->current_budget, 0) }}</td>
                                    @if($aiSet)
                                        <td class="text-nowrap text-center {{ $aiHeadClass }}">{{ str_pad($p->ai_headcount, 2, '0', STR_PAD_LEFT) }}</td>
                                        <td class="text-nowrap text-center {{ $aiBudgetClass }}">{{ $currencySymbol }} {{ number_format($p->ai_budget, 0) }}</td>
                                        <td>
                                            @if(!empty($p->ai_justification))
                                                <div class="d-flex align-items-end">
                                                    <p class="m-0">{{ $p->ai_justification }}</p>
                                                </div>
                                            @endif
                                        </td>
                                    @else
                                        <td class="text-nowrap text-center text-muted">—</td>
                                        <td class="text-nowrap text-center text-muted">—</td>
                                        <td class="text-muted">
                                            {{ $aiStatus === 'ready' ? '' : 'Click Regenerate AI to populate.' }}
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No positions configured for this department yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($positions->isNotEmpty())
                            <tfoot>
                                <tr>
                                    <th>Total:</th>
                                    <th class="text-center">{{ $totalHodHeadcount }}</th>
                                    <th class="text-center">{{ $currencySymbol }} {{ number_format($totalHodBudget, 0) }}</th>
                                    <th class="text-lightblue text-center">{{ $totalAiHeadcount }}</th>
                                    <th class="text-lightblue text-center">{{ $currencySymbol }} {{ number_format($totalAiBudget, 0) }}</th>
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
@endsection

@section('import-scripts')
{{-- Flash toast for the Regenerate-AI action. The controller redirects
     back here after a Regenerate click so the user sees one of three
     toasts: AI ready (green), AI timeout (yellow), AI failed (red).
     Without this, a failed AI call left the page visually unchanged
     and HR thought the button was broken. --}}
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
@endsection
