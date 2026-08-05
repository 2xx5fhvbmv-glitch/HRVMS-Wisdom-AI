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
                        <h1>{{ strtoupper($kind) }} Form</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <a href="{{ route('Performance.' . $kind . '.index') }}" class="btn perf-btn-secondary btn-sm">
                        <i class="fa-solid fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><strong>Employee:</strong> {{ optional(optional($plan->employee)->resortAdmin)->full_name ?? '-' }}</div>
                    <div class="col-md-4"><strong>Position:</strong> {{ optional($plan->position)->position_title ?? optional(optional($plan->employee)->position)->position_title ?? '-' }}</div>
                    <div class="col-md-4"><strong>Duration:</strong> {{ $plan->duration }}</div>
                    <div class="col-md-4"><strong>Template:</strong> {{ optional($plan->template)->FormName ?? '—' }}</div>
                    <div class="col-md-4"><strong>Assigned On:</strong> {{ \Carbon\Carbon::flexible($plan->created_at)->format('d M Y') }}</div>
                    <div class="col-md-4">
                        <strong>Status:</strong>
                        @if($plan->submitted_at)
                            <span class="badge badge-themeSuccess">Submitted</span>
                            <small class="text-muted d-block mt-1">on {{ \Carbon\Carbon::parse($plan->submitted_at)->format('d M Y, h:i A') }}</small>
                        @else
                            <span class="badge badge-themeWarning">Pending</span>
                        @endif
                    </div>
                    @if($plan->factors)
                        <div class="col-12"><strong>Factors / Focus Areas:</strong> <div>{{ $plan->factors }}</div></div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                @if(empty($structure))
                    <div class="alert alert-warning">No template attached to this plan — nothing to fill in.</div>
                @else
                    @if($plan->submitted_at)
                        <div class="alert alert-success">
                            <i class="fa-solid fa-circle-check me-1"></i>
                            Submitted on {{ \Carbon\Carbon::parse($plan->submitted_at)->format('d M Y, h:i A') }}. The form is read-only.
                        </div>
                    @elseif(!$canEdit)
                        <div class="alert alert-secondary">
                            <i class="fa-solid fa-lock me-1"></i>
                            You can view this plan but only the assigned employee or an authorized manager may submit it.
                        </div>
                    @endif

                    <form id="planForm" autocomplete="off">
                        @csrf
                        <div id="form-render"></div>

                        @if($canEdit)
                            <div class="text-end mt-3">
                                <button type="submit" class="btn perf-btn-primary btn-sm">Submit Form</button>
                            </div>
                        @endif
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@include('resorts.Performance._performance_buttons_v2_styles')
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
    var formStructure = @json($structure ?? []);
    var existingData  = @json($existingData ?? []);
    var canEdit       = {{ $canEdit ? 'true' : 'false' }};
    var submitUrl     = "{{ route('Performance.' . $kind . '.submit', $plan->id) }}";

    $(document).ready(function() {
        renderForm();

        $('#planForm').on('submit', function(e) {
            e.preventDefault();
            if (!canEdit) return;

            var data = collectFormData();
            if (!validateRequired(data)) {
                toastr.error('Please fill all required fields', 'Validation error', { positionClass: 'toast-bottom-right' });
                return;
            }

            // Build FormData so file inputs get uploaded as multipart/form-data,
            // not stringified into the JSON body.
            var fd = new FormData();
            fd.append('_token', '{{ csrf_token() }}');
            $.each(data, function(k, v) {
                if (Array.isArray(v)) {
                    v.forEach(function(item) { fd.append(k + '[]', item); });
                } else {
                    fd.append(k, v == null ? '' : v);
                }
            });
            $('#form-render input[type="file"]').each(function() {
                var name = $(this).attr('name');
                if (!name) return;
                if (this.files && this.files.length) fd.append(name, this.files[0]);
            });

            $.ajax({
                url: submitUrl,
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res.success) {
                        toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                        setTimeout(function() { location.reload(); }, 800);
                    }
                },
                error: function(xhr) {
                    var payload = xhr.responseJSON || {};
                    if (payload.errors) {
                        var msg = '';
                        $.each(payload.errors, function(k, v) { msg += v + '<br>'; });
                        toastr.error(msg, 'Validation error', { positionClass: 'toast-bottom-right' });
                    } else {
                        toastr.error(payload.message || 'Failed to submit', 'Error', { positionClass: 'toast-bottom-right' });
                    }
                }
            });
        });
    });

    function renderForm() {
        var html = '';
        if (!Array.isArray(formStructure)) return;
        var isReadOnly = !canEdit;

        formStructure.forEach(function(field, idx) {
            var fieldName = field.name || ('field_' + idx);
            var label = field.label || '';
            var value = existingData[fieldName] || '';
            var disabled = isReadOnly ? 'disabled' : '';

            if (field.type === 'header' || field.type === 'paragraph') {
                html += '<div class="form-render-section">' + label + '</div>';
                return;
            }

            html += '<div class="form-render-field" data-field-name="' + fieldName + '" data-field-required="' + (field.required ? '1' : '0') + '" data-field-type="' + (field.type || '') + '">';
            html += '<label>' + label + (field.required ? ' <span class="text-danger">*</span>' : '') + '</label>';

            switch(field.type) {
                case 'text':
                    html += '<input type="text" name="' + fieldName + '" value="' + value + '" ' + disabled + '>'; break;
                case 'textarea':
                    html += '<textarea name="' + fieldName + '" rows="4" ' + disabled + '>' + value + '</textarea>'; break;
                case 'number':
                    html += '<input type="number" name="' + fieldName + '" value="' + value + '" ' + disabled + '>'; break;
                case 'date':
                    html += '<input type="date" name="' + fieldName + '" value="' + value + '" ' + disabled + '>'; break;
                case 'select':
                case 'radio-group':
                    html += '<select name="' + fieldName + '" ' + disabled + '><option value="">Select</option>';
                    if (field.values) field.values.forEach(function(opt) {
                        var sel = (value == opt.value) ? 'selected' : '';
                        html += '<option value="' + opt.value + '" ' + sel + '>' + opt.label + '</option>';
                    });
                    html += '</select>'; break;
                case 'checkbox-group':
                    if (field.values) field.values.forEach(function(opt) {
                        var checked = (Array.isArray(value) && value.includes(opt.value)) ? 'checked' : '';
                        html += '<div><input type="checkbox" name="' + fieldName + '[]" value="' + opt.value + '" ' + checked + ' ' + disabled + '> ' + opt.label + '</div>';
                    });
                    break;
                case 'starRating':
                    var max = field.maxRating || 5;
                    html += '<div class="rating-stars" data-name="' + fieldName + '">';
                    for (var i = 1; i <= max; i++) {
                        var active = (i <= parseInt(value || 0)) ? 'active' : '';
                        html += '<span class="' + active + '" data-val="' + i + '">&#9733;</span>';
                    }
                    html += '<input type="hidden" name="' + fieldName + '" value="' + value + '"></div>'; break;
                case 'file':
                    if (isReadOnly) {
                        // Saved files appear under 'value' as a relative path on the local disk.
                        if (value) {
                            var fileUrl = "{{ url('resort/performance/' . $kind . '/' . $plan->id . '/file') }}/" + encodeURIComponent(fieldName);
                            var name = String(value).split('/').pop().replace(/^\d+_/, '');
                            html += '<a href="' + fileUrl + '" class="btn perf-btn-secondary btn-sm"><i class="fa-solid fa-file-arrow-down me-1"></i>' + name + '</a>';
                        } else {
                            html += '<span class="text-muted">No file uploaded</span>';
                        }
                    } else {
                        html += '<input type="file" name="' + fieldName + '" accept="' + (field.accept || '*/*') + '">';
                        if (value) {
                            html += '<div class="small text-muted mt-1">Current: ' + String(value).split('/').pop().replace(/^\d+_/, '') + ' (upload a new file to replace)</div>';
                        }
                    }
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
                    html += '</tbody></table></div>'; break;
                default:
                    html += '<input type="text" name="' + fieldName + '" value="' + value + '" ' + disabled + '>';
            }
            html += '<div class="field-error">This field is required.</div>';
            html += '</div>';
        });
        $('#form-render').html(html);

        if (!isReadOnly) {
            $('.rating-stars span').on('click', function() {
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

    function collectFormData() {
        var data = {};
        $('#form-render').find('input, textarea, select').each(function() {
            var name = $(this).attr('name');
            if (!name) return;
            var type = $(this).attr('type');
            // Skip file inputs here — they're appended directly to FormData below.
            if (type === 'file') return;
            if (type === 'checkbox') {
                if ($(this).is(':checked')) {
                    if (name.endsWith('[]')) {
                        var key = name.slice(0, -2);
                        if (!data[key]) data[key] = [];
                        data[key].push($(this).val());
                    } else {
                        data[name] = $(this).val();
                    }
                }
            } else {
                data[name] = $(this).val();
            }
        });
        return data;
    }

    function validateRequired(data) {
        $('.form-render-field').removeClass('is-invalid');
        var firstInvalid = null;
        $('.form-render-field').each(function() {
            var $f = $(this);
            if ($f.data('field-required') != 1) return;
            var name = $f.data('field-name');
            var type = $f.data('field-type');
            var val = data[name];
            var empty = (val === undefined || val === null || val === '' || (Array.isArray(val) && val.length === 0));
            if (type === 'ratingTable') {
                var hasAny = false;
                $.each(data, function(k, v) {
                    if (k.indexOf(name + '_') === 0 && v !== '' && v !== null && v !== undefined) hasAny = true;
                });
                empty = !hasAny;
            } else if (type === 'file') {
                // Required files: pass if a new file is picked OR a previous upload exists.
                var $input = $f.find('input[type="file"]');
                var hasNew = $input.length && $input[0].files && $input[0].files.length > 0;
                var hasExisting = !!(existingData[name]);
                empty = !(hasNew || hasExisting);
            }
            if (empty) {
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
