@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Learning &amp; Development</span>
                        <h1>{{ $form->form_name }}</h1>
                    </div>
                </div>
                <div class="col-auto d-flex gap-2">
                    <a href="{{ route('feedback-form.edit', $form->id) }}" class="btn lnd-btn-secondary btn-sm">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                    </a>
                    <a href="{{ route('feedback-form.index') }}" class="btn lnd-btn-neutral btn-sm">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div id="preview-render"></div>
            </div>
        </div>
    </div>
</div>
@include('resorts.Learning._learning_buttons_v2_styles')
@endsection

@section('import-css')
<style>
    .preview-field { margin-bottom: 18px; }
    .preview-field label { font-weight: 600; display: block; margin-bottom: 6px; }
    .preview-field input, .preview-field textarea, .preview-field select { width: 100%; padding: 8px 12px; border: 1px solid #dee2e6; border-radius: 6px; background:#f8f9fa; }
    .preview-field .rating-stars span { font-size: 28px; color: #ccc; }
    .preview-section { margin: 20px 0 12px; padding: 8px 0; border-bottom: 1px solid #e9ecef; }
    .preview-section h2, .preview-section h3, .preview-section h4 { margin: 0; }
    .preview-table { width:100%; margin-bottom: 10px; }
    .preview-table th { background:#014653; color:#fff; padding: 8px 12px; font-weight: 500; }
    .preview-table td { padding: 8px 12px; border: 1px solid #dee2e6; }
</style>
@endsection

@section('import-scripts')
<script>
    var formStructure = @json($structure ?? []);

    $(document).ready(function() {
        var html = '';
        if (!Array.isArray(formStructure)) formStructure = [];

        formStructure.forEach(function(field, idx) {
            var label = field.label || '';
            if (field.type === 'header' || field.type === 'paragraph') {
                html += '<div class="preview-section">' + label + '</div>';
                return;
            }

            html += '<div class="preview-field">';
            html += '<label>' + label + (field.required ? ' <span class="text-danger">*</span>' : '') + '</label>';

            switch (field.type) {
                case 'textarea':
                    html += '<textarea rows="3" disabled></textarea>';
                    break;
                case 'number':
                    html += '<input type="number" disabled>';
                    break;
                case 'date':
                    html += '<input type="date" disabled>';
                    break;
                case 'select':
                case 'radio-group':
                    html += '<select disabled><option>Select</option>';
                    (field.values || []).forEach(function(o) { html += '<option>' + o.label + '</option>'; });
                    html += '</select>';
                    break;
                case 'checkbox-group':
                    (field.values || []).forEach(function(o) { html += '<div><input type="checkbox" disabled> ' + o.label + '</div>'; });
                    break;
                case 'starRating':
                    var max = field.maxRating || 5;
                    html += '<div class="rating-stars">';
                    for (var i = 0; i < max; i++) html += '<span>&#9733;</span>';
                    html += '</div>';
                    break;
                case 'ratingTable':
                    var cols = (field.columnHeadings || '').split(',').map(function(s){return s.trim();}).filter(Boolean);
                    var rows = (field.rowLabels || '').split(',').map(function(s){return s.trim();}).filter(Boolean);
                    html += '<table class="preview-table"><thead><tr>';
                    if (rows.length) html += '<th></th>';
                    cols.forEach(function(c) { html += '<th>' + c + '</th>'; });
                    html += '</tr></thead><tbody>';
                    rows.forEach(function(r) {
                        html += '<tr><td style="font-weight:600;background:#f0f0f0;">' + r + '</td>';
                        cols.forEach(function() { html += '<td><input type="text" disabled></td>'; });
                        html += '</tr>';
                    });
                    html += '</tbody></table>';
                    break;
                default:
                    html += '<input type="text" disabled>';
            }
            html += '</div>';
        });

        if (!html) html = '<div class="text-muted">This form has no fields yet.</div>';
        $('#preview-render').html(html);
    });
</script>
@endsection
