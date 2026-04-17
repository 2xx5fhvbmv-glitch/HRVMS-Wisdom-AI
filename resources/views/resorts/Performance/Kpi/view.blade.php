@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Performance</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <a href="{{ route('Performance.kpi.KpiList') }}" class="btn btn-themeBlue btn-sm">
                        <i class="fa-solid fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>

        {{-- Creator info --}}
        @php
            $creatorAdmin = $kpi->creator ?? null;
            $creatorName = $creatorAdmin ? ($creatorAdmin->first_name.' '.$creatorAdmin->last_name) : 'GM';
            $creatorPosition = optional(optional($creatorAdmin)->getEmployee)->position->position_title ?? null;
            $statusMap = [
                'pending'   => 'badge-themeWarning',
                'responded' => 'badge-themeSkyblue',
                'approved'  => 'badge-themeSuccess',
                'rejected'  => 'badge-themeDanger',
            ];
            $badgeClass = $statusMap[$kpi->status] ?? 'badge-themeWarning';
        @endphp

        <div class="card mb-3">
            <div class="d-flex align-items-center p-3">
                <div class="img-circle userImg-block me-3">
                    <img src="{{ Common::getResortUserPicture($creatorAdmin->id ?? null) }}" alt="user" style="width:56px;height:56px;border-radius:50%;">
                </div>
                <div class="flex-grow-1">
                    <h5 class="mb-1">{{ $creatorName }}</h5>
                    <small class="text-muted">
                        Created By{{ $creatorPosition ? ' · '.$creatorPosition : '' }}
                    </small>
                </div>
                <span class="badge {{ $badgeClass }}">{{ ucfirst($kpi->status) }}</span>
            </div>
        </div>

        {{-- KPI Summary --}}
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">KPI Details</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <strong>Property Goal:</strong> {{ $kpi->property_goal }}
                    </div>
                    <div class="col-md-3">
                        <strong>Value (Weightage):</strong> {{ $kpi->PropertyGoalweightage }}%
                    </div>
                    <div class="col-md-3">
                        <strong>Budget:</strong>
                        {{ $kpi->PropertyGoalbudget !== null && $kpi->PropertyGoalbudget !== '' ? number_format((float)$kpi->PropertyGoalbudget) : '-' }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Response entries --}}
        @if($kpi->responded_by)
            @php
                $responder = $kpi->responder ?? null;
                $responderName = $responder ? ($responder->resortAdmin->first_name ?? '').' '.($responder->resortAdmin->last_name ?? '') : '-';
                $responderPos = optional(optional($responder)->position)->position_title ?? null;
                $entries = is_array($kpi->response_entries) ? $kpi->response_entries : [];
            @endphp
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Response</h5>
                    <span class="text-muted small">
                        Responded by: <strong>{{ trim($responderName) ?: '-' }}</strong>
                        {{ $responderPos ? ' · '.$responderPos : '' }}
                        @if($kpi->responded_at)
                            <span class="ms-2">on {{ \Carbon\Carbon::parse($kpi->responded_at)->format('d M Y, h:i A') }}</span>
                        @endif
                    </span>
                </div>
                <div class="card-body">
                    @if(count($entries) > 0)
                        <div class="table-responsive">
                            <table class="table table-collapseNew">
                                <thead>
                                    <tr>
                                        <th style="width:50px;">#</th>
                                        <th>Individual Goal</th>
                                        <th>Budget</th>
                                        <th>Weightage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($entries as $i => $entry)
                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td>{{ $entry['individual_goal'] ?? '-' }}</td>
                                            <td>{{ isset($entry['budget']) && $entry['budget'] !== '' && $entry['budget'] !== null ? number_format((float)$entry['budget']) : '-' }}</td>
                                            <td>{{ isset($entry['weightage']) ? $entry['weightage'].'%' : '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold">
                                        <td colspan="2" class="text-end">Total</td>
                                        <td>{{ number_format((float)$kpi->response_budget) }}</td>
                                        <td>{{ $kpi->response_weightage }}%</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        {{-- Fallback for older records without response_entries — split joined string --}}
                        @php
                            $goalParts = $kpi->individual_goal ? array_values(array_filter(array_map('trim', explode('|', $kpi->individual_goal)))) : [];
                        @endphp
                        @if(count($goalParts) > 0)
                            <div class="table-responsive">
                                <table class="table table-collapseNew">
                                    <thead>
                                        <tr>
                                            <th style="width:50px;">#</th>
                                            <th>Individual Goal</th>
                                            <th>Budget</th>
                                            <th>Weightage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($goalParts as $i => $goal)
                                            <tr>
                                                <td>{{ $i + 1 }}</td>
                                                <td>{{ $goal }}</td>
                                                <td class="text-muted">-</td>
                                                <td class="text-muted">-</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="fw-bold">
                                            <td colspan="2" class="text-end">Total</td>
                                            <td>{{ $kpi->response_budget !== null ? number_format((float)$kpi->response_budget) : '-' }}</td>
                                            <td>{{ $kpi->response_weightage !== null ? $kpi->response_weightage.'%' : '-' }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                                <small class="text-muted">Per-entry budget/weightage is not available for this record (submitted before per-entry tracking was enabled).</small>
                            </div>
                        @else
                            <div class="text-muted">No response entries recorded.</div>
                        @endif
                    @endif
                </div>
            </div>
        @endif

        {{-- Actual entries --}}
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Actual</h5>
            </div>
            <div class="card-body">
                @php
                    $allocatedBudget    = (float) ($kpi->response_budget ?? 0);
                    $allocatedWeightage = (float) ($kpi->response_weightage ?? 0);
                    $usedBudget         = (float) $kpi->childrenKpi->sum('budget');
                    $usedWeightage      = (float) $kpi->childrenKpi->sum('weightage');
                    $balanceBudget      = $allocatedBudget - $usedBudget;
                    $balanceWeightage   = $allocatedWeightage - $usedWeightage;
                    $colspan            = 3; // # + Individual Goal + Month
                    $trailingColspan    = ($canAddActual ?? false) ? 3 : 2; // Created By + Added On + (Action?)
                @endphp

                @if($kpi->childrenKpi->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-collapseNew">
                            <thead>
                                <tr>
                                    <th style="width:50px;">#</th>
                                    <th>Individual Goal</th>
                                    <th>Month</th>
                                    <th>Budget</th>
                                    <th>Weightage</th>
                                    <th>Created By</th>
                                    <th>Added On</th>
                                    @if($canAddActual ?? false)
                                        <th style="width:100px;">Action</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($kpi->childrenKpi as $i => $child)
                                    @php
                                        $cAdmin = $child->creator ?? null;
                                        $cName = $cAdmin ? trim(($cAdmin->first_name ?? '').' '.($cAdmin->last_name ?? '')) : null;
                                        $cPos = optional(optional($cAdmin)->getEmployee)->position->position_title ?? null;
                                    @endphp
                                    <tr id="actual-row-{{ $child->id }}">
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $child->individual_goal ?: '-' }}</td>
                                        <td>{{ $child->month ?: '-' }}</td>
                                        <td>{{ $child->budget !== null && $child->budget !== '' ? number_format((float)$child->budget) : '-' }}</td>
                                        <td>{{ $child->weightage !== null ? $child->weightage.'%' : '-' }}</td>
                                        <td>
                                            @if($cName)
                                                <div>{{ $cName }}</div>
                                                @if($cPos)<small class="text-muted">{{ $cPos }}</small>@endif
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $child->created_at ? $child->created_at->format('d M Y, h:i A') : '-' }}</td>
                                        @if($canAddActual ?? false)
                                            <td>
                                                <button class="btn btn-danger btn-sm delete-actual-btn" data-id="{{ $child->id }}">Remove</button>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="{{ $colspan }}" class="text-end">Total Achieved</td>
                                    <td>{{ number_format($usedBudget) }}</td>
                                    <td>{{ $usedWeightage }}%</td>
                                    <td colspan="{{ $trailingColspan }}"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-muted mb-3">No actual entries yet.</div>
                @endif

                {{-- Balance summary --}}
                <div class="row g-3 mt-2">
                    <div class="col-md-4">
                        <div class="p-3 rounded" style="background:#f1f5f9;">
                            <small class="text-muted d-block">TARGET</small>
                            <div><strong>Budget:</strong> {{ number_format($allocatedBudget) }}</div>
                            <div><strong>Weightage:</strong> {{ $allocatedWeightage }}%</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded" style="background:#fff4e5;">
                            <small class="text-muted d-block">ACHIEVED</small>
                            <div><strong>Budget:</strong> {{ number_format($usedBudget) }}</div>
                            <div><strong>Weightage:</strong> {{ $usedWeightage }}%</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded" style="background:{{ ($balanceBudget < 0 || $balanceWeightage < 0) ? '#fde7e7' : '#e6f4ea' }};">
                            <small class="text-muted d-block">REMAINING</small>
                            <div><strong>Budget:</strong>
                                <span style="color:{{ $balanceBudget < 0 ? '#d9534f' : '#28a745' }}">{{ number_format($balanceBudget) }}</span>
                            </div>
                            <div><strong>Weightage:</strong>
                                <span style="color:{{ $balanceWeightage < 0 ? '#d9534f' : '#28a745' }}">{{ $balanceWeightage }}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                @if($canAddActual ?? false)
                    <hr>
                    <h6 class="mb-3">Add Actual Entry</h6>
                    <form id="AddActualForm" data-parsley-validate>
                        @csrf
                        <div class="actualKpi-main">
                            <div class="actualKpi-block">
                                <div class="row g-md-4 g-3 mb-md-4 mb-3">
                                    <div class="col-sm-6">
                                        <label class="form-label">INDIVIDUAL GOAL</label>
                                        <select class="form-select" name="entries[0][individual_goal]" required>
                                            <option value="">Select Individual Goal</option>
                                            @foreach(($goalOptions ?? []) as $goal)
                                                <option value="{{ $goal }}">{{ $goal }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label">MONTH</label>
                                        <select class="form-select" name="entries[0][month]" required>
                                            <option value="">Select Month</option>
                                            @foreach(($months ?? []) as $m)
                                                <option value="{{ $m }}">{{ $m }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label">BUDGET</label>
                                        <input type="number" class="form-control" name="entries[0][budget]"
                                               placeholder="Budget (optional)" min="1">
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label">WEIGHTAGE</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="entries[0][weightage]"
                                                   placeholder="Weightage" required min="1">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 d-flex align-items-end d-none remove-col">
                                        <a href="#" class="btn btn-danger btn-sm actualKpi-remove">Remove</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="md-mb-4 mb-3">
                            <a href="#" class="btn btn-themeSkyblue btn-sm actualKpi-add">Add More</a>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-themeBlue btn-sm">Save Actual Entries</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        {{-- GM Decision --}}
        @if($kpi->gm_action)
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">GM Decision</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <strong>Action:</strong>
                            <span class="badge {{ $kpi->gm_action === 'approved' ? 'badge-themeSuccess' : 'badge-themeDanger' }}">
                                {{ ucfirst($kpi->gm_action) }}
                            </span>
                        </div>
                        <div class="col-md-6">
                            <strong>When:</strong>
                            {{ $kpi->gm_action_at ? \Carbon\Carbon::parse($kpi->gm_action_at)->format('d M Y, h:i A') : '-' }}
                        </div>
                        <div class="col-12">
                            <strong>Remarks:</strong>
                            <div>{{ $kpi->gm_remarks ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@section('import-scripts')
<script>
$(document).ready(function () {
    @if($canAddActual ?? false)
    $('#AddActualForm').parsley();
    var entryIndex = 1;

    $(document).on('click', '.actualKpi-add', function (e) {
        e.preventDefault();
        var newBlock = $('.actualKpi-block').first().clone();
        newBlock.find('input').val('');
        newBlock.find('select').prop('selectedIndex', 0);
        newBlock.find('input, select').each(function() {
            var name = $(this).attr('name');
            if (name) {
                $(this).attr('name', name.replace(/entries\[\d+\]/, 'entries[' + entryIndex + ']'));
            }
        });
        newBlock.find('.remove-col').removeClass('d-none');
        $('.actualKpi-main').append(newBlock);
        entryIndex++;
    });

    $(document).on('click', '.actualKpi-remove', function (e) {
        e.preventDefault();
        $(this).closest('.actualKpi-block').remove();
    });

    $(document).on('submit', '#AddActualForm', function(e) {
        e.preventDefault();
        var form = $(this);
        if (!form.parsley().isValid()) return;

        $.ajax({
            url: "{{ route('Performance.kpi.storeActual', $kpi->id) }}",
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message, "Success", { positionClass: 'toast-bottom-right' });
                    setTimeout(function() { window.location.reload(); }, 600);
                }
            },
            error: function(xhr) {
                var errors = xhr.responseJSON;
                if (errors && errors.errors) {
                    var errs = '';
                    $.each(errors.errors, function(k, v) { errs += v + '<br>'; });
                    toastr.error(errs, "Validation Error", { positionClass: 'toast-bottom-right' });
                } else {
                    toastr.error(errors?.message || 'Something went wrong', "Error", { positionClass: 'toast-bottom-right' });
                }
            }
        });
    });

    $(document).on('click', '.delete-actual-btn', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Remove this actual entry?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, remove'
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: "{{ url('resort/performance/kpi/actual') }}/" + id,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(res) {
                    if (res.success) {
                        toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                        $('#actual-row-' + id).remove();
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Failed', 'Error', { positionClass: 'toast-bottom-right' });
                }
            });
        });
    });
    @endif
});
</script>
@endsection
