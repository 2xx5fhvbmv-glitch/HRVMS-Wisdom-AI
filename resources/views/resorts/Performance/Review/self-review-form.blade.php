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
                    <a href="{{ route('Performance.Review.mySelf') }}" class="btn btn-themeGray">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <div class="card p-4">
            <div class="mb-3">
                <h4>{{ $childCycle->Cycle_Name }}</h4>
                <p class="text-muted mb-2">
                    Cycle Period: {{ \Carbon\Carbon::parse($childCycle->CycleStart)->format('d M Y') }} - {{ \Carbon\Carbon::parse($childCycle->CycleEnd)->format('d M Y') }}
                </p>

                @if(isset($windowStatus) && $windowStatus['status'] !== 'no_window')
                    @if($windowStatus['status'] === 'open')
                        <div class="alert alert-success">
                            <i class="fa-solid fa-circle-check me-2"></i>
                            <strong>Self Review window is open</strong><br>
                            {{ \Carbon\Carbon::parse($windowStatus['start'])->format('d M Y') }} - {{ \Carbon\Carbon::parse($windowStatus['end'])->format('d M Y') }}
                        </div>
                    @elseif($windowStatus['status'] === 'upcoming')
                        <div class="alert alert-warning">
                            <i class="fa-solid fa-clock me-2"></i>
                            <strong>Self Review not yet open</strong><br>
                            Window opens: {{ \Carbon\Carbon::parse($windowStatus['start'])->format('d M Y') }}
                        </div>
                    @elseif($windowStatus['status'] === 'closed')
                        <div class="alert alert-danger">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i>
                            <strong>Self Review window has closed</strong><br>
                            Closed on: {{ \Carbon\Carbon::parse($windowStatus['end'])->format('d M Y') }}
                        </div>
                    @endif
                @endif

                @if($childCycle->is_gm_review)
                    <div class="alert alert-info">
                        <i class="fa-solid fa-info-circle me-2"></i>
                        This is a GM review. After completing your self review, you can export the form for Corporate Office to complete offline.
                    </div>
                @endif
            </div>

            @if(!$template || !isset($template['structure']))
                <div class="alert alert-warning">No template found for this review.</div>
            @else
                <form id="selfReviewForm">
                    @csrf
                    <div id="form-render"></div>

                    @if($childCycle->self_review_status === 'pending')
                        @if(isset($windowStatus) && !$windowStatus['open'] && $windowStatus['status'] !== 'no_window')
                            <div class="alert alert-secondary mt-3">
                                <i class="fa-solid fa-lock me-2"></i>
                                Submission is disabled — review window is not currently open.
                            </div>
                        @else
                            <div class="mt-4 text-end">
                                <button type="submit" class="btn btn-themeBlue">Submit Self Review</button>
                            </div>
                        @endif
                    @else
                        <div class="alert alert-success mt-3">
                            <i class="fa-solid fa-check-circle me-2"></i>
                            You completed this review on {{ \Carbon\Carbon::parse($childCycle->Self_review_date)->format('d M Y') }}
                        </div>
                    @endif
                </form>
            @endif
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
</style>
@endsection

@section('import-scripts')
<script>
    var formStructure = @json($template['structure'] ?? []);
    var existingData = @json($existingData ?? []);
    var isReadOnly = {{ $childCycle->self_review_status === 'completed' ? 'true' : 'false' }};

    $(document).ready(function() {
        renderForm();

        $('#selfReviewForm').on('submit', function(e) {
            e.preventDefault();
            var formData = collectFormData();

            $.ajax({
                url: "{{ route('Performance.Review.submitSelf', ['id' => base64_encode($childCycle->id)]) }}",
                type: 'POST',
                data: $.extend({ _token: '{{ csrf_token() }}' }, formData),
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message, 'Success', { positionClass: 'toast-bottom-right' });
                        setTimeout(function() {
                            window.location.href = "{{ route('Performance.Review.mySelf') }}";
                        }, 1500);
                    }
                },
                error: function() {
                    toastr.error('Failed to submit review', 'Error', { positionClass: 'toast-bottom-right' });
                }
            });
        });
    });

    function renderForm() {
        var html = '';
        if (!Array.isArray(formStructure)) return;

        formStructure.forEach(function(field, idx) {
            var fieldName = field.name || ('field_' + idx);
            var label = field.label || '';
            var value = existingData[fieldName] || '';
            var disabled = isReadOnly ? 'disabled' : '';

            html += '<div class="form-render-field">';
            html += '<label>' + label + (field.required ? ' <span class="text-danger">*</span>' : '') + '</label>';

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
                    html += '<select name="' + fieldName + '" ' + disabled + '>';
                    html += '<option value="">Select</option>';
                    if (field.values) {
                        field.values.forEach(function(opt) {
                            var sel = (value == opt.value) ? 'selected' : '';
                            html += '<option value="' + opt.value + '" ' + sel + '>' + opt.label + '</option>';
                        });
                    }
                    html += '</select>';
                    break;
                case 'checkbox-group':
                    if (field.values) {
                        field.values.forEach(function(opt) {
                            var checked = (Array.isArray(value) && value.includes(opt.value)) ? 'checked' : '';
                            html += '<div><input type="checkbox" name="' + fieldName + '[]" value="' + opt.value + '" ' + checked + ' ' + disabled + '> ' + opt.label + '</div>';
                        });
                    }
                    break;
                case 'starRating':
                    var max = field.maxRating || 5;
                    html += '<div class="rating-stars" data-name="' + fieldName + '" data-value="' + value + '">';
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
            if ($(this).attr('type') === 'checkbox') {
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
</script>
@endsection
