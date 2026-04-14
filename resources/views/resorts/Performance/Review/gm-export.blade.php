<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $page_title }}</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 30px; max-width: 900px; margin: 0 auto; color: #333; }
        .header { text-align: center; border-bottom: 3px solid #014653; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { color: #014653; margin: 0; }
        .info-table { width: 100%; margin-bottom: 30px; border-collapse: collapse; }
        .info-table td { padding: 10px 12px; border: 1px solid #ddd; }
        .info-table td:first-child { background: #f8f9fa; font-weight: 600; width: 200px; }
        .section { margin-bottom: 30px; }
        .section h3 { color: #014653; border-bottom: 2px solid #014653; padding-bottom: 8px; }
        .field { margin-bottom: 15px; padding: 10px; background: #f8f9fa; border-radius: 6px; }
        .field-label { font-weight: 600; color: #555; display: block; margin-bottom: 6px; }
        .field-value { color: #333; min-height: 24px; }
        .corporate-section { background: #fffae6; border: 2px dashed #EFB408; padding: 20px; border-radius: 8px; margin-top: 30px; }
        .corporate-section h3 { color: #014653; margin-top: 0; }
        .signature-line { display: inline-block; border-bottom: 1px solid #333; min-width: 200px; min-height: 24px; }
        .stars { color: #EFB408; font-size: 20px; }
        .star-empty { color: #ccc; font-size: 20px; }
        .table-field table { width: 100%; border-collapse: collapse; }
        .table-field th { background: #014653; color: #fff; padding: 8px 12px; }
        .table-field td { padding: 8px 12px; border: 1px solid #ddd; }
        @media print { .no-print { display: none; } body { padding: 0; } }
        .actions { margin-bottom: 20px; text-align: right; }
        .btn { background: #014653; color: #fff; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; }
    </style>
</head>
<body>
    <div class="actions no-print">
        <button class="btn" onclick="window.print()">Print / Save as PDF</button>
    </div>

    <div class="header">
        <h1>Performance Review Form</h1>
        <p style="margin: 8px 0 0; color: #666;">For Corporate Office Review</p>
    </div>

    <div class="section">
        <h3>Cycle Information</h3>
        <table class="info-table">
            <tr><td>Cycle Name</td><td>{{ $childCycle->Cycle_Name }}</td></tr>
            <tr><td>Cycle Period</td><td>{{ \Carbon\Carbon::parse($childCycle->CycleStart)->format('d M Y') }} to {{ \Carbon\Carbon::parse($childCycle->CycleEnd)->format('d M Y') }}</td></tr>
            <tr><td>Employee Name</td><td>{{ optional($employee->resortAdmin)->first_name }} {{ optional($employee->resortAdmin)->last_name }}</td></tr>
            <tr><td>Position</td><td>{{ optional($employee->position)->position_title ?? 'N/A' }}</td></tr>
            <tr><td>Self Review Date</td><td>{{ $childCycle->Self_review_date ? \Carbon\Carbon::parse($childCycle->Self_review_date)->format('d M Y') : 'N/A' }}</td></tr>
        </table>
    </div>

    <div class="section">
        <h3>Self Review (Completed by GM)</h3>
        @if($template && isset($template['structure']) && is_array($template['structure']))
            @foreach($template['structure'] as $idx => $field)
                @php
                    $fieldName = $field['name'] ?? 'field_' . $idx;
                    $value = $selfData[$fieldName] ?? '';
                    $type = $field['type'] ?? 'text';
                @endphp
                <div class="field">
                    <span class="field-label">{{ $field['label'] ?? '' }}</span>
                    <div class="field-value">
                        @if($type === 'starRating')
                            @php $max = $field['maxRating'] ?? 5; $val = (int) $value; @endphp
                            @for($i = 1; $i <= $max; $i++)
                                @if($i <= $val)
                                    <span class="stars">&#9733;</span>
                                @else
                                    <span class="star-empty">&#9733;</span>
                                @endif
                            @endfor
                            ({{ $val }}/{{ $max }})
                        @elseif($type === 'ratingTable')
                            @php
                                $cols = array_filter(array_map('trim', explode(',', $field['columnHeadings'] ?? '')));
                                $rows = array_filter(array_map('trim', explode(',', $field['rowLabels'] ?? '')));
                            @endphp
                            <div class="table-field">
                                <table>
                                    <thead>
                                        <tr>
                                            @if(count($rows)) <th></th> @endif
                                            @foreach($cols as $c) <th>{{ $c }}</th> @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($rows as $ri => $r)
                                            <tr>
                                                <td style="font-weight:600;background:#f0f0f0;">{{ $r }}</td>
                                                @foreach($cols as $ci => $c)
                                                    @php $cellName = $fieldName . '_' . $ri . '_' . $ci; @endphp
                                                    <td>{{ $selfData[$cellName] ?? '' }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            {{ is_array($value) ? implode(', ', $value) : ($value ?: '—') }}
                        @endif
                    </div>
                </div>
            @endforeach
        @else
            <p>No template content</p>
        @endif
    </div>

    <div class="corporate-section">
        <h3>Corporate Office Review</h3>
        <p style="color: #666; margin-bottom: 20px;">This section to be completed by Corporate Office and returned to HR.</p>

        @if($template && isset($template['structure']) && is_array($template['structure']))
            @foreach($template['structure'] as $idx => $field)
                <div class="field" style="background: #fff;">
                    <span class="field-label">{{ $field['label'] ?? '' }}</span>
                    <div class="field-value" style="border-bottom: 1px solid #999; min-height: 40px;">&nbsp;</div>
                </div>
            @endforeach
        @endif

        <div style="margin-top: 30px;">
            <p><strong>Reviewer Name:</strong> <span class="signature-line">&nbsp;</span></p>
            <p><strong>Designation:</strong> <span class="signature-line">&nbsp;</span></p>
            <p><strong>Date:</strong> <span class="signature-line">&nbsp;</span></p>
            <p><strong>Signature:</strong> <span class="signature-line">&nbsp;</span></p>
        </div>
    </div>
</body>
</html>
