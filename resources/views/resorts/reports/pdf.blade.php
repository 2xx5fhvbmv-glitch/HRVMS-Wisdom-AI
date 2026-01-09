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
                <th>{{ ucfirst(str_replace('_', ' ', $column)) }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse($data as $index => $row)
            <tr>
                @foreach($columns as $column)
                    <td>
                        @if(isset($relation_tables[$column]))
                            <!-- This is a relation table column - display its data directly -->
                            @if(isset($row[$column]))
                                @php 
                            
                                    $relatedData = $row[$column];
                                    $relColumns = $relation_tables[$column]['columns'] ?? [];
                                @endphp
                                
                                <div class="relation-info">
                                    @foreach($relColumns as $relColumn)
                                        <div class="relation-item">
                                            <b>{{ ucfirst(str_replace('_', ' ', $relColumn)) }}</b>:   
                                            

                                                @if(in_array($relColumn, ["rank","Rank","benefit_grid_level"]))
                                                @php 
                                                    $benefit_grid_level = config('settings.eligibilty');
                                                    $Rank = config('settings.eligibilty');
                                                    if(isset($benefit_grid_level[$relatedData[$relColumn]])) 
                                                    {
                                                        $rankKey = $Rank[$relatedData[$relColumn]];
                                                    }
                                                    elseif(isset($Rank[$relatedData[$relColumn]])) 
                                                    {
                                                        $rankKey = $Rank[$relatedData[$relColumn]];
                                                    }
                                                    else
                                                    {
                                                        $rankKey = "N/A";
                                                    }
                                                    echo  $rankKey;
                                                @endphp

                                            @else
                                            {{ $relatedData[$relColumn] ?? 'N/A' }}
                                            
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                N/A
                            @endif
                        @else
                            <!-- This is a regular column -->
                            @if(isset($row[$column]))

                                @if(in_array($column , ["rank","Rank","benefit_grid_level"]))
                                    @php 
                                        $benefit_grid_level = config('settings.eligibilty');
                                        $Rank = config('settings.eligibilty');
                                        if(isset($benefit_grid_level[$row[$column]])) 
                                        {
                                            $rankKey = $Rank[$row[$column]];
                                        }
                                        elseif(isset($Rank[$row[$column]])) 
                                        {
                                            $rankKey = $Rank[$row[$column]];
                                        }
                                        else
                                        {
                                            $rankKey = "N/A";
                                        }
                                        echo  $rankKey;
                                    @endphp

                                @else
                                {{ $row[$column] }}
                                    
                                @endif
                            
                            @else
                                N/A
                            @endif
                        @endif
                    </td>
                @endforeach
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($columns) }}" class="text-center">No data available</td>
            </tr>
        @endforelse
    </tbody>
</table>
</body>
</html>