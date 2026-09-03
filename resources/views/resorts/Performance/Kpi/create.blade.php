@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@php $isEdit = !empty($kpi); @endphp

@section('content')
    <style>
        #performance-kpi-create-hero { padding-bottom: 40px; }
        @media (max-width: 575.98px) {
            #performance-kpi-create-hero { padding-bottom: 0; }
        }
    </style>
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding" id="performance-kpi-create-hero">
                <div class="row justify-content-between g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Performance</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('Performance.kpi.KpiList') }}" class="btn perf-btn-secondary btn-sm">
                            <i class="fa-solid fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>

            <div>
                <form id="CreateKPIForm" data-parsley-validate>
                    @csrf
                    <div class="card">

                        <div class="createKpi-main">
                            <div class="createKpi-block">
                                <div class="row g-md-4 g-3 mb-md-4 mb-3">
                                    <div class="col-sm-6">
                                        <label class="form-label">PROPERTY GOAL</label>
                                        <input type="text" class="form-control"
                                               name="{{ $isEdit ? 'property_goal' : 'goals[0][property_goal]' }}"
                                               value="{{ $isEdit ? $kpi->property_goal : '' }}"
                                               placeholder="Property Goal" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label">BUDGET</label>
                                        <input type="number" class="form-control"
                                               name="{{ $isEdit ? 'PropertyGoalbudget' : 'goals[0][budget]' }}"
                                               value="{{ $isEdit ? $kpi->PropertyGoalbudget : '' }}"
                                               placeholder="Budget (optional)" min="1">
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label">WEIGHTAGE (VALUE)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control weightage-input"
                                                   name="{{ $isEdit ? 'PropertyGoalweightage' : 'goals[0][weightage]' }}"
                                                   value="{{ $isEdit ? $kpi->PropertyGoalweightage : '' }}"
                                                   placeholder="Weightage" required min="1" max="100">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 d-flex align-items-end d-none remove-col">
                                        <a href="#" class="btn perf-btn-neutral btn-sm createKpi-remove">Remove</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @unless($isEdit)
                            <div class="text-end mb-2 pe-2">
                                <small class="text-muted">Total Weightage: <strong id="weightageTotal">0</strong>% / 100%</small>
                            </div>

                            <div class="md-mb-4 mb-3">
                                <a href="#" class="btn perf-btn-accent btn-sm createKpi-add">Add More</a>
                            </div>
                        @endunless

                        <div class="card-footer text-end">
                            <button type="submit" class="btn perf-btn-primary btn-sm">{{ $isEdit ? 'Update' : 'Send' }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@include('resorts.Performance._performance_buttons_v2_styles')
@endsection

@section('import-scripts')
<script>
$(document).ready(function () {
    var isEdit  = {{ $isEdit ? 'true' : 'false' }};
    var storeUrl  = "{{ route('Performance.kpi.store') }}";
    var updateUrl = "{{ $isEdit ? route('Performance.kpi.update', $kpi->id) : '' }}";
    var listUrl   = "{{ route('Performance.kpi.KpiList') }}";

    $('#CreateKPIForm').parsley();
    var goalIndex = 1;

    function recalcWeightage() {
        if (isEdit) return;
        var total = 0;
        $('.weightage-input').each(function() {
            var v = parseFloat($(this).val());
            if (!isNaN(v)) total += v;
        });
        $('#weightageTotal').text(total);
        $('#weightageTotal').css('color', total === 100 ? '#28a745' : '#d9534f');
    }

    $(document).on('input', '.weightage-input', recalcWeightage);

    $(document).on('click', '.createKpi-add', function (e) {
        e.preventDefault();
        var newBlock = $('.createKpi-block').first().clone();
        newBlock.find('input').val('');
        newBlock.find('input').each(function() {
            var name = $(this).attr('name');
            if (name) {
                $(this).attr('name', name.replace(/goals\[\d+\]/, 'goals[' + goalIndex + ']'));
            }
        });
        newBlock.find('.remove-col').removeClass('d-none');
        $('.createKpi-main').append(newBlock);
        goalIndex++;
        recalcWeightage();
    });

    $(document).on('click', '.createKpi-remove', function (e) {
        e.preventDefault();
        $(this).closest('.createKpi-block').remove();
        recalcWeightage();
    });

    $(document).on('submit', '#CreateKPIForm', function(e) {
        e.preventDefault();
        var form = $(this);

        // In create mode only: validate total weightage = 100
        if (!isEdit) {
            var total = 0;
            $('.weightage-input').each(function() {
                var v = parseFloat($(this).val());
                if (!isNaN(v)) total += v;
            });
            if (total !== 100) {
                toastr.error('Total weightage must be exactly 100%. Current total: ' + total + '%', 'Validation Error', { positionClass: 'toast-bottom-right' });
                return;
            }
        }

        if (form.parsley().isValid()) {
            $.ajax({
                url: isEdit ? updateUrl : storeUrl,
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                        setTimeout(function() {
                            window.location.href = response.route || listUrl;
                        }, 600);
                    }
                },
                error: function(xhr) {
                    var errors = xhr.responseJSON;
                    if (errors && errors.errors) {
                        var errs = '';
                        $.each(errors.errors, function(key, error) { errs += error + '<br>'; });
                        toastr.error(errs, "Validation Error", { positionClass: 'toast-bottom-right' });
                    } else {
                        toastr.error(errors?.message || 'Something went wrong!', "Error", { positionClass: 'toast-bottom-right' });
                    }
                }
            });
        }
    });
});
</script>
@endsection
