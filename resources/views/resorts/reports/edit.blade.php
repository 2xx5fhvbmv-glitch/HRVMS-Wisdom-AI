
@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
@endif

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Report</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-title">
                <div class="row g-1">
                    <div class="col">
                        <h3>Reports</h3>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('resort.report.index')}}" class="btn btn-sm btn-theme "> Report List</a>
                    </div>
                </div>  
            </div> 
            <div class="card-body">
                <div class="row">
                    <form method="POST" action="{{ route('reports.store') }}">
                        @csrf
                        
                        <div class="form-group mb-3">
                            <label for="name">Report Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{$report->name}}" required>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3">{{isset($report->description) ? $report->description :''}}</textarea>
                        </div>
                        <?php
                                $table = $report->query_params;
                        ?>
                        <div class="form-group mb-3">
                            <label for="table_name">Select Database Table</label>
                            <select class="form-control" id="table_name" name="table_name" required>
                                <option value="">Select a table</option>
                                @foreach($tables as $table)
                                    @foreach($table as $key => $value)
                                        <option value="{{ $value }}" {{ (isset($table['table']) && $value == $table['table']) ? 'selected' : '' }}>
                                    @endforeach
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group mb-3">
                                <label>Select Columns</label>
                                <div id="columns-container">
                                    <p>Please select a table first</p>
                                </div>
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label>Select Foreign Keys Columns</label>
                                <div id="columns-ForeignKeyscontainer">
                                    <p>Please select a table first</p>
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label>Filters</label>
                            <div id="filters-container">
                                <div class="alert alert-info">
                                    You can add filters after selecting columns
                                </div>
                            </div>
                            <button type="button" class="btn btn-info mt-2" id="add-filter" style="display: none;">
                                Add Filter
                            </button>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Create Report</button>
                            <a href="{{ route('resort.report.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            
            </div>

        </div>
    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
     $(document).ready(function() {
        $("#table_name").select2({
            placeholder: "Select a Table"
        })
                 $(document).on('change', '#table_name', function(){
            const tableName = $(this).val();
            if (tableName) {
                $.ajax({
                    url: '{{ route("resort.reports.get-columns") }}',
                    method: 'GET',
                    data: { table: tableName },
                    success: function(response) {
                        // For parent table columns
                        let html = '<div class="form-check">';
                        
                        // Check if parent_columns exists and is an array
                        if (response.data && response.data.parent_columns && Array.isArray(response.data.parent_columns)) {
                            response.data.parent_columns.forEach(function(column) {
                                html += `
                                    <div class="mb-2">
                                        <input class="form-check-input column-checkbox" type="checkbox" name="columns[]" value="${column.original}" id="column-${column.original}">
                                        <label class="form-check-label" for="column-${column.original}">${column.formatted}</label>
                                    </div>
                                `;
                            });
                        } else {
                            html += '<p>No varchar or enum columns found in this table</p>';
                        }
                        
                        html += '</div>';
                        $('#columns-container').html(html);
                        
                        // For foreign keys and related tables
                        let html1 = '';
                        
                        // Check if related_tables exists and is an array
                        if (response.data && response.data.related_tables && Array.isArray(response.data.related_tables)) {
                            response.data.related_tables.forEach(function(relation) {
                                // Add the foreign key as a checkbox
                                html1 += `<div class="mb-3">
                                    <div class="foreign-key-container">
                                        <input class="form-check-input column-checkbox"  data-bs-toggle="collapse" 
                                                data-bs-target="#collapse-${relation.referenced_table}" 
                                                aria-expanded="false" type="checkbox" 
                                            name="columns[]" value="${relation.referenced_table}" 
                                            id="table-${relation.referenced_table}">
                                        <label class="form-check-label fw-bold" for="table-${relation.referenced_table}">
                                            ${relation.formatted_table_name} (Related Table)
                                        </label>
                                    </div>
                                    
                                    <!-- Collapsible section for related table columns -->
                                    <div class="ms-4 mt-2">
                                     
                                        
                                        <div class="collapse" id="collapse-${relation.referenced_table}">
                                            <div class="card card-body">`;
                                
                                // Add checkboxes for each column in the related table
                                if (relation.columns && Array.isArray(relation.columns)) {
                                    relation.columns.forEach(function(column) {
                                        html1 += `
                                            <div class="mb-2">
                                                <input class="form-check-input related-column-checkbox" 
                                                    type="checkbox" 
                                                    name="related_columns[${relation.referenced_table}][]" 
                                                    value="${column.original}" 
                                                    id="column-${relation.referenced_table}-${column.original}">
                                                <label class="form-check-label" 
                                                    for="column-${relation.referenced_table}-${column.original}">
                                                    ${column.formatted}
                                                </label>
                                            </div>
                                        `;
                                    });
                                } else {
                                    html1 += '<p>No varchar or enum columns found in this related table</p>';
                                }
                                
                                html1 += `
                                            </div>
                                        </div>
                                    </div>
                                </div>`;
                            });
                        } else {
                            html1 += '<p>No related tables found</p>';
                        }
                        
                        $('#columns-ForeignKeyscontainer').html(html1);
                        
                        // Enable adding filters after columns are loaded
                        $('.column-checkbox, .related-column-checkbox').change(function() {
                            if ($('.column-checkbox:checked, .related-column-checkbox:checked').length > 0) {
                                $('#add-filter').show();
                            } else {
                                $('#add-filter').hide();
                            }
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error("Error fetching columns:", error);
                        $('#columns-container').html('<p class="text-danger">Error loading columns. Please try again.</p>');
                        $('#columns-ForeignKeyscontainer').html('');
                    }
                });
            } else {
                $('#columns-container').html('<p>Please select a table first</p>');
                $('#columns-ForeignKeyscontainer').html('');
                $('#add-filter').hide();
            }
        });
        
        // Add filter button
        let filterCount = 0;
        $('#add-filter').click(function() {
            const filterHtml = `
                <div class="card mb-3 filter-card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <select name="filters[${filterCount}][field]" class="form-control filter-field" required>
                                    <option value="">Select Field</option>
                                    ${$('.column-checkbox:checked').map(function() {
                                        return `<option value="${$(this).val()}">${$(this).val()}</option>`;
                                    }).get().join('')}
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="filters[${filterCount}][operator]" class="form-control" required>
                                    <option value="equals">Equals</option>
                                    <option value="contains">Contains</option>
                                    <option value="greater_than">Greater Than</option>
                                    <option value="less_than">Less Than</option>
                                    <option value="between">Between</option>
                                </select>
                            </div>
                            <div class="col-md-4 filter-value-container">
                                <input type="text" name="filters[${filterCount}][value]" class="form-control" placeholder="Value" required>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-danger remove-filter">Remove</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('#filters-container').append(filterHtml);
            filterCount++;
            
            // Handle filter removal
            $('.remove-filter').click(function() {
                $(this).closest('.filter-card').remove();
            });
        });
    });
</script>
@endsection