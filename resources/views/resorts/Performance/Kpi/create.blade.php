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
                                        <input type="text" class="form-control" name="goals[0][property_goal]"
                                               placeholder="Property Goal" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label">BUDGET</label>
                                        <input type="number" class="form-control" name="goals[0][budget]"
                                               placeholder="Budget (optional)" min="1">
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label">WEIGHTAGE (VALUE)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control weightage-input" name="goals[0][weightage]"
                                                   placeholder="Weightage" required min="1" max="100">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 d-flex align-items-end d-none remove-col">
                                        <a href="#" class="btn btn-danger btn-sm createKpi-remove">Remove</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-end mb-2 pe-2">
                            <small class="text-muted">Total Weightage: <strong id="weightageTotal">0</strong>% / 100%</small>
                        </div>

                        <div class="md-mb-4 mb-3">
                            <a href="#" class="btn btn-themeSkyblue btn-sm createKpi-add">Add More</a>
                        </div>

                        {{-- Actual section hidden for now
                        <div class="card-title">
                            <h3>Actual</h3>
                        </div>
                        <div class="row g-md-4 g-3 mb-md-4 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label">BUDGET</label>
                                <input type="number" class="form-control" name="actual_budget"
                                       placeholder="Budget" min="1">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">WEIGHTAGE (VALUE)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="actual_weightage"
                                           placeholder="Weightage" min="1" max="100">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                        --}}

                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-themeBlue btn-sm">Send</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('import-scripts')
<script>
$(document).ready(function () {
    $('#CreateKPIForm').parsley();
    var goalIndex = 1;

    function recalcWeightage() {
        var total = 0;
        $('.weightage-input').each(function() {
            var v = parseFloat($(this).val());
            if (!isNaN(v)) total += v;
        });
        $('#weightageTotal').text(total);
        var $el = $('#weightageTotal');
        if (total === 100) {
            $el.css('color', '#28a745');
        } else {
            $el.css('color', '#d9534f');
        }
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

        // Validate total weightage = 100
        var total = 0;
        $('.weightage-input').each(function() {
            var v = parseFloat($(this).val());
            if (!isNaN(v)) total += v;
        });
        if (total !== 100) {
            toastr.error('Total weightage must be exactly 100%. Current total: ' + total + '%', 'Validation Error', { positionClass: 'toast-bottom-right' });
            return;
        }

        if (form.parsley().isValid()) {
            $.ajax({
                url: '{{ route("Performance.kpi.store") }}',
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                        setTimeout(function() {
                            window.location.href = response.route;
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
