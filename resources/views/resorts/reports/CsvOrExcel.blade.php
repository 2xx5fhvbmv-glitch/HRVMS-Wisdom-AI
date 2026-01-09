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