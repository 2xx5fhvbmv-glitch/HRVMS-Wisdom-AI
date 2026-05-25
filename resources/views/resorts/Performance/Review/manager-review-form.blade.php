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
                    <a href="{{ route('Performance.Review.myTeam') }}" class="btn btn-themeGray">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <div class="card p-4">
            <div class="mb-3">
                <h4>{{ $childCycle->Cycle_Name }}</h4>
                <div class="d-flex align-items-center gap-3 mt-2 mb-3">
                    <img src="{{ \App\Helpers\Common::getResortUserPicture(optional($employee->resortAdmin)->id) }}" style="width:50px;height:50px;border-radius:50%;">
                    <div>
                        <div><strong>{{ optional($employee->resortAdmin)->first_name }} {{ optional($employee->resortAdmin)->last_name }}</strong></div>
                        <small class="text-muted">{{ $employee->position->position_title ?? '' }}</small>
                    </div>
                </div>

                @if(isset($windowStatus) && $windowStatus['status'] !== 'no_window')
                    @if($windowStatus['status'] === 'open')
                        <div class="alert alert-success">
                            <i class="fa-solid fa-circle-check me-2"></i>
                            <strong>Manager Review window is open</strong><br>
                            {{ \Carbon\Carbon::parse($windowStatus['start'])->format('d M Y') }} - {{ \Carbon\Carbon::parse($windowStatus['end'])->format('d M Y') }}
                        </div>
                    @elseif($windowStatus['status'] === 'upcoming')
                        <div class="alert alert-warning">
                            <i class="fa-solid fa-clock me-2"></i>
                            <strong>Manager Review not yet open</strong><br>
                            Window opens: {{ \Carbon\Carbon::parse($windowStatus['start'])->format('d M Y') }}
                        </div>
                    @elseif($windowStatus['status'] === 'closed')
                        <div class="alert alert-danger">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i>
                            <strong>Manager Review window has closed</strong><br>
                            Closed on: {{ \Carbon\Carbon::parse($windowStatus['end'])->format('d M Y') }}
                        </div>
                    @endif
                @endif
            </div>

            <ul class="nav nav-tabs mb-3" id="reviewTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#self-tab" type="button">Self Review (Read Only)</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#manager-tab" type="button">My Review</button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="self-tab">
                    <div id="self-form-render"></div>
                </div>
                <div class="tab-pane fade" id="manager-tab">
                    <form id="managerReviewForm">
                        @csrf
                        <div id="manager-form-render"></div>
                        @if($childCycle->manager_review_status === 'pending')
                            @if(isset($windowStatus) && !$windowStatus['open'] && $windowStatus['status'] !== 'no_window')
                                <div class="alert alert-secondary mt-3">
                                    <i class="fa-solid fa-lock me-2"></i>
                                    Submission is disabled — review window is not currently open.
                                </div>
                            @else
                                <div class="mt-4 text-end">
                                    <button type="submit" class="btn btn-themeBlue">Submit Manager Review</button>
                                </div>
                            @endif
                        @else
                            <div class="alert alert-success mt-3">
                                <i class="fa-solid fa-check-circle me-2"></i>
                                You completed this review on {{ \Carbon\Carbon::parse($childCycle->Manager_review_date)->format('d M Y') }}
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
<style>
    .form-render-field { margin-bottom: 16px; }
    .form-render-field label { font-weight: 600; display: block; margin-bottom: 6px; }
    .form-render-field input, .form-render-field textarea, .form-render-field select { width: 100%; padding: 8px 12px; border: 1px solid #dee2e6; border-radius: 6px; }
    .form-render-field .rating-stars span { font-size: 28px; cursor: pointer; color: #ccc; }
    .form-render-field .rating-stars span.active { color: #EFB408; }
    .form-render-field .table-field table { width: 100%; }
    .form-render-field .table-field th { background: #014653; color: #fff; padding: 8px 12px; }
    .form-render-field .table-field td { padding: 8px 12px; border: 1px solid #dee2e6; }
    .form-render-section { margin: 20px 0 12px; padding: 8px 0; border-bottom: 1px solid #e9ecef; }
    .form-render-section h2, .form-render-section h3, .form-render-section h4 { margin: 0; }
    .form-render-field.is-invalid input,
    .form-render-field.is-invalid textarea,
    .form-render-field.is-invalid select { border-color: #dc3545; }
    .form-render-field .field-error { color: #dc3545; font-size: 12px; margin-top: 4px; display: none; }
    .form-render-field.is-invalid .field-error { display: block; }
</style>
@endsection

@section('import-scripts')
<script>
    var formStructure = @json($template['structure'] ?? []);
    var selfData = @json($selfData ?? []);
    var managerData = @json($existingData ?? []);
    var managerReadOnly = {{ $childCycle->manager_review_status === 'completed' ? 'true' : 'false' }};

    $(document).ready(function() {
        renderForm('self-form-render', selfData, true);
        renderForm('manager-form-render', managerData, managerReadOnly);

        $('#managerReviewForm').on('submit', function(e) {
            e.preventDefault();
            var formData = collectFormData('manager-form-render');

            if (!validateRequired('manager-form-render', formData)) {
                toastr.error('Please fill all required fields', 'Validation error', { positionClass: 'toast-bottom-right' });
                return;
            }

            $.ajax({
                url: "{{ route('Performance.Review.submitManager', ['id' => base64_encode($childCycle->id)]) }}",
                type: 'POST',
                data: $.extend({ _token: '{{ csrf_token() }}' }, formData),
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message, 'Success', { positionClass: 'toast-bottom-right' });
                        setTimeout(function() {
                            window.location.href = "{{ route('Performance.Review.myTeam') }}";
                        }, 1500);
                    }
                },
                error: function(xhr) {
                    var payload = xhr.responseJSON || {};
                    if (payload.errors) {
                        var msg = '';
                        $.each(payload.errors, function(field, err) { msg += err + '<br>'; });
                        toastr.error(msg, 'Validation error', { positionClass: 'toast-bottom-right' });
                    } else {
                        toastr.error(payload.message || 'Failed to submit review', 'Error', { positionClass: 'toast-bottom-right' });
                    }
                }
            });
        });
    });

    function renderForm(containerId, existingData, isReadOnly) {
        var html = '';
        if (!Array.isArray(formStructure)) return;

        // The Self Review tab (containerId === 'self-form-render') is a
        // pure read-only view of what the employee submitted — we hide the
        // role-lock notes there, otherwise a Self-only field shows "To be
        // filled by: Self" instead of the employee's actual value.
        var isSelfTab = (containerId === 'self-form-render');

        formStructure.forEach(function(field, idx) {
            var fieldName = field.name || ('field_' + idx);
            var label = field.label || '';
            var value = existingData[fieldName] || '';
            // Lock the field when: the whole review is read-only, OR — for
            // the manager tab only — the controller marked it _readonly
            // because the viewer's role isn't in its responder_roles list.
            var roleLocked = !isSelfTab && !!field._readonly;
            var disabled = (isReadOnly || roleLocked) ? 'disabled' : '';

            // Section headers / paragraph blocks have no input — render label only.
            if (field.type === 'header' || field.type === 'paragraph') {
                html += '<div class="form-render-section">' + label + '</div>';
                return;
            }

            html += '<div class="form-render-field' + (roleLocked ? ' is-role-locked' : '') + '" data-field-name="' + fieldName + '" data-field-required="' + (field.required ? '1' : '0') + '" data-field-type="' + (field.type || '') + '">';
            html += '<label>' + label + (field.required ? ' <span class="text-danger">*</span>' : '') + '</label>';
            if (roleLocked && field._assigned_to) {
                html += '<div class="role-lock-note small text-warning mb-1"><i class="fa-regular fa-lock me-1"></i> To be filled by: ' + field._assigned_to + '</div>';
            }

            switch(field.type) {
                case 'text':
                    html += '<input type="text" name="' + fieldName + '" value="' + value + '" ' + disabled + '>';
                    break;
                case 'textarea':
                    html += '<textarea name="' + fieldName + '" rows="4" ' + disabled + '>' + value + '</textarea>';
                    break;
                case 'number':
                    html += '<input type="number" name="' + fieldName + '" value="' + value + '" ' + disabled + '>';
                    break;
                case 'date':
                    html += '<input type="date" name="' + fieldName + '" value="' + value + '" ' + disabled + '>';
                    break;
                case 'select':
                case 'radio-group':
                    html += '<select name="' + fieldName + '" ' + disabled + '><option value="">Select</option>';
                    if (field.values) {
                        field.values.forEach(function(opt) {
                            var sel = (value == opt.value) ? 'selected' : '';
                            html += '<option value="' + opt.value + '" ' + sel + '>' + opt.label + '</option>';
                        });
                    }
                    html += '</select>';
                    break;
                case 'starRating':
                    var max = field.maxRating || 5;
                    html += '<div class="rating-stars" data-name="' + fieldName + '">';
                    for (var i = 1; i <= max; i++) {
                        var active = (i <= parseInt(value || 0)) ? 'active' : '';
                        html += '<span class="' + active + '" data-val="' + i + '">&#9733;</span>';
                    }
                    html += '<input type="hidden" name="' + fieldName + '" value="' + value + '"></div>';
                    break;
                case 'ratingTable':
                    var cols = (field.columnHeadings || '').split(',').map(function(s){return s.trim();}).filter(Boolean);
                    var rows = (field.rowLabels || '').split(',').map(function(s){return s.trim();}).filter(Boolean);
                    html += '<div class="table-field"><table><thead><tr>';
                    if (rows.length) html += '<th></th>';
                    cols.forEach(function(c){ html += '<th>' + c + '</th>'; });
                    html += '</tr></thead><tbody>';
                    rows.forEach(function(r, ri) {
                        html += '<tr><td style="font-weight:600;background:#f0f0f0;">' + r + '</td>';
                        cols.forEach(function(c, ci) {
                            var cellName = fieldName + '_' + ri + '_' + ci;
                            var cellVal = existingData[cellName] || '';
                            html += '<td><input type="text" name="' + cellName + '" value="' + cellVal + '" ' + disabled + '></td>';
                        });
                        html += '</tr>';
                    });
                    html += '</tbody></table></div>';
                    break;
                default:
                    html += '<input type="text" name="' + fieldName + '" value="' + value + '" ' + disabled + '>';
            }
            html += '<div class="field-error">This field is required.</div>';
            html += '</div>';
        });
        $('#' + containerId).html(html);

        if (!isReadOnly) {
            $('#' + containerId + ' .rating-stars span').on('click', function() {
                var val = $(this).data('val');
                var $parent = $(this).parent();
                $parent.find('span').removeClass('active');
                $parent.find('span').each(function() {
                    if ($(this).data('val') <= val) $(this).addClass('active');
                });
                $parent.find('input[type="hidden"]').val(val);
            });
        }
    }

    function collectFormData(containerId) {
        var data = {};
        $('#' + containerId).find('input, textarea, select').each(function() {
            var name = $(this).attr('name');
            if (!name) return;
            data[name] = $(this).val();
        });
        return data;
    }

    function validateRequired(containerId, formData) {
        var $container = $('#' + containerId);
        $container.find('.form-render-field').removeClass('is-invalid');
        var firstInvalid = null;

        $container.find('.form-render-field').each(function() {
            var $f = $(this);
            if ($f.data('field-required') != 1) return;
            var name = $f.data('field-name');
            var type = $f.data('field-type');
            var val = formData[name];

            var isEmpty = (val === undefined || val === null || val === '' || (Array.isArray(val) && val.length === 0));

            if (type === 'ratingTable') {
                var hasAny = false;
                $.each(formData, function(k, v) {
                    if (k.indexOf(name + '_') === 0 && v !== '' && v !== null && v !== undefined) hasAny = true;
                });
                isEmpty = !hasAny;
            }

            if (isEmpty) {
                $f.addClass('is-invalid');
                if (!firstInvalid) firstInvalid = $f;
            }
        });

        if (firstInvalid) {
            $('html, body').animate({ scrollTop: firstInvalid.offset().top - 100 }, 300);
            return false;
        }
        return true;
    }
</script>
@endsection
