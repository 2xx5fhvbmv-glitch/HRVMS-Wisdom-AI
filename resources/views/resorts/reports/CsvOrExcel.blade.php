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

@if(!empty($insights))
    <table>
        <thead>
            <tr><th>WAI Insights</th></tr>
        </thead>
        <tbody>
            @foreach($insights as $line)
                <tr><td>{{ $line }}</td></tr>
            @endforeach
        </tbody>
    </table>
@endif

