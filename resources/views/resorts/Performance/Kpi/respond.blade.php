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

        <div class="card">
            {{-- Creator info header --}}
            <div class="d-flex align-items-center mb-3 pb-3" style="border-bottom:1px solid #eee;">
                @php
                    $creatorAdmin = $kpi->creator ?? null;
                    $creatorName = $creatorAdmin ? ($creatorAdmin->first_name.' '.$creatorAdmin->last_name) : 'GM';
                    $creatorPosition = optional(optional($creatorAdmin)->getEmployee)->position->position_title ?? null;
                @endphp
                <div class="img-circle userImg-block me-3">
                    <img src="{{ Common::getResortUserPicture($creatorAdmin->id ?? null) }}" alt="user" style="width:56px;height:56px;border-radius:50%;">
                </div>
                <div>
                    <h5 class="mb-1">{{ $creatorName }}</h5>
                    <small class="text-muted">
                        Created By{{ $creatorPosition ? ' · '.$creatorPosition : '' }}
                    </small>
                </div>
            </div>

            {{-- Parent KPI summary --}}
            <div class="d-flex flex-wrap gap-4 mb-4">
                <div><strong>PROPERTY GOAL:</strong> {{ $kpi->property_goal }}</div>
                <div><strong>VALUE:</strong> {{ $kpi->PropertyGoalweightage }}%</div>
                @if($kpi->PropertyGoalbudget)
                    <div><strong>BUDGET:</strong> {{ number_format((float)$kpi->PropertyGoalbudget) }}</div>
                @endif
            </div>

            <form id="KpiResponseForm" data-parsley-validate>
                @csrf

                <div class="responseGoal-main">
                    <div class="responseGoal-block">
                        <div class="row g-md-4 g-3 mb-md-4 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label">CREATE INDIVIDUAL GOAL</label>
                                <input type="text" class="form-control" name="responses[0][individual_goal]"
                                       placeholder="Property Goal" required>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">BUDGET</label>
                                <input type="number" class="form-control" name="responses[0][budget]"
                                       placeholder="Budget (optional)" min="1">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">WEIGHTAGE (VALUE)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="responses[0][weightage]"
                                           placeholder="Weightage" required min="1">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-sm-6 d-flex align-items-end d-none remove-col">
                                <a href="#" class="btn btn-danger btn-sm responseGoal-remove">Remove</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="md-mb-4 mb-3">
                    <a href="#" class="btn btn-themeSkyblue btn-sm responseGoal-add">Add More</a>
                </div>

                {{-- Actual section hidden for now
                <div class="card-title">
                    <h3>Actual</h3>
                </div>
                <div class="responseKpi-main">
                    @forelse($kpi->childrenKpi as $child)
                    <div class="row align-items-end g-md-4 g-3 mb-md-4 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label">BUDGET</label>
                            <input type="number" class="form-control" name="child_budget[]"
                                   value="{{ $child->budget }}" placeholder="Budget" min="1">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">WEIGHTAGE (VALUE)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="child_weightage[]"
                                       value="{{ $child->weightage }}" placeholder="Weightage" min="1" max="100">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="row align-items-end g-md-4 g-3 mb-md-4 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label">BUDGET</label>
                            <input type="number" class="form-control" name="child_budget[]"
                                   placeholder="Budget" min="1">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">WEIGHTAGE (VALUE)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="child_weightage[]"
                                       placeholder="Weightage" min="1" max="100">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                    @endforelse
                </div>
                --}}

                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-themeBlue btn-sm">Send</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('import-scripts')
<script>
$(document).ready(function () {
    $('#KpiResponseForm').parsley();
    var responseIndex = 1;

    $(document).on('click', '.responseGoal-add', function (e) {
        e.preventDefault();
        var newBlock = $('.responseGoal-block').first().clone();
        newBlock.find('input').val('');
        newBlock.find('input').each(function() {
            var name = $(this).attr('name');
            if (name) {
                $(this).attr('name', name.replace(/responses\[\d+\]/, 'responses[' + responseIndex + ']'));
            }
        });
        newBlock.find('.remove-col').removeClass('d-none');
        $('.responseGoal-main').append(newBlock);
        responseIndex++;
    });

    $(document).on('click', '.responseGoal-remove', function (e) {
        e.preventDefault();
        $(this).closest('.responseGoal-block').remove();
    });

    $(document).on('submit', '#KpiResponseForm', function(e) {
        e.preventDefault();
        var form = $(this);

        if (!form.parsley().isValid()) return;

        $.ajax({
            url: "{{ route('Performance.kpi.storeResponse', $kpi->id) }}",
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message, "Success", { positionClass: 'toast-bottom-right' });
                    setTimeout(function() {
                        window.location.href = res.route;
                    }, 600);
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
});
</script>
@endsection
