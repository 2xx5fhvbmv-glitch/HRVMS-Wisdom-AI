<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $report->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: left;
            padding: 8px;
        }
        td {
            padding: 8px;
            vertical-align: top;
        }
        .report-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .report-meta {
            margin-bottom: 20px;
            font-size: 11px;
            color: #666;
        }
        .json-header {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
        }
        .json-key {
            font-style: italic;
            text-align: right;
            padding-right: 10px;
        }
        .json-value {
            padding-left: 10px;
        }
    </style>
</head>
<body>
    <div class="report-title">{{ $report->name }}</div>
    
    <div class="report-meta">
        Generated on: {{ now()->format('Y-m-d H:i:s') }}
    </div>
        <table class="table table-border table-striped">
    <thead>
        <tr>
            @foreach($columns as $column)
                <th>{{ $column }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse($data as $row)
            <tr>
                @foreach($columns as $column)
                    <td>{{ $row[$column] ?? 'N/A' }}</td>
                @endforeach
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($columns) }}" class="text-center">No data available</td>
            </tr>
        @endforelse
    </tbody>
</table>

@if(!empty($insightsHtml))
    <div class="wai-insights">
        <h2 style="font-size:16px; border-bottom:1px solid #ccc; padding-bottom:4px;">WAI Insights</h2>
        {!! $insightsHtml !!}
    </div>
@endif
</body>
</html>