
@extends('resorts.layouts.app')
@section('page_tab_title',$page_title)

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
    <form id="report-create-form" data-parsley-validate>
        @csrf
        <div class="row">
            <!-- Report Name -->
            <div class="col-md-6 form-group mb-3">
                <label for="name">Report Name <span class="req_span">*</span></label>
                <input type="text"
                    class="form-control"
                    id="name"
                    name="name"
                    required
                    data-parsley-required
                    data-parsley-pattern="^[a-zA-Z0-9\s.,'\$/:]+$"
                    data-parsley-pattern-message="Only alphanumeric characters and , . ' $ / : are allowed"
                    data-parsley-required-message="Report name is required">
            </div>

            <!-- Description -->
            <div class="col-md-6 form-group mb-3">
                <label for="description">Description</label>
                <textarea class="form-control"
                    id="description"
                    name="description"
                    rows="3"
                    data-parsley-maxlength="250"
                    data-parsley-maxlength-message="Description cannot exceed 250 characters"></textarea>
            </div>

            <!-- Select Database Table -->
            <div class="col-md-6 form-group mb-3">
                <label for="table_name">Select Database Table <span class="req_span">*</span></label>
                <select class="form-control"
                    id="table_name"
                    name="table_name"
                    required
                    data-parsley-required
                    data-parsley-required-message="Please select a table"
                    data-parsley-errors-container="#error-msg">
                    <option value="">Select a table</option>
                    @foreach($tables as $table)
                        @foreach($table as $key => $value)
                            <option value="{{ $value }}">{{ ucfirst(str_replace('_', ' ', $value)) }}</option>
                        @endforeach
                    @endforeach
                </select>
                <div id="error-msg" class="text-danger mt-1"></div>
            </div>

            <!-- Date Range -->
            <div class="col-md-3 form-group mb-3">
                <label for="from_date">From Date <span class="req_span">*</span></label>
                <input type="date"
                    class="form-control"
                    id="from_date"
                    name="from_date"
                    required
                    data-parsley-required-message="Please select a from date">
            </div>
            <div class="col-md-3 form-group mb-3">
                <label for="to_date">To Date <span class="req_span">*</span></label>
                <input type="date"
                    class="form-control"
                    id="to_date"
                    name="to_date"
                    required
                    data-parsley-required-message="Please select a to date"
                    data-parsley-afterdate="#from_date"
                    data-parsley-afterdate-message="To date must be after from date">
            </div>

            <!-- Columns Container -->
            <div class="col-md-6 form-group mb-3">
                <label>Select Columns</label>
                <div id="columns-container">
                    <p>Please select a table first</p>
                </div>
            </div>

            <!-- Foreign Key Columns -->
            <div class="col-md-6 form-group mb-3">
                <label>Select Foreign Keys Columns</label>
                <div id="columns-ForeignKeyscontainer">
                    <p>Please select a table first</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="col-md-12 form-group mb-3">
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

            <!-- Submit -->
            <div class="col-md-12 form-group">
                <button type="submit" class="btn btn-sm btn-theme">Save Report</button>
                <a href="{{ route('resort.report.index') }}" class="btn btn-sm btn-danger">Cancel</a>
            </div>
        </div>
    </form>
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

          $("#todate").datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,      // Close the picker after selection
            todayHighlight: true  // Highlight today's date
        });
         $("#formdate").datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,      // Close the picker after selection
            todayHighlight: true  // Highlight today's date
        });
     
        $('#report-create-form').parsley();
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
    
        $('#report-create-form').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            if (!$form.parsley().isValid()) 
            {
                $form.parsley().validate();
                return false;
            }
            else
            {
                var formData = new FormData(this);

                $(this).find('.alert').remove();

                $.ajax({
                    url: "{{ route('reports.store') }}",
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val()
                    },
                    success: function(response) 
                    {
                        if(response.success) {
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                        }else{
                            toastr.error(response.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    
                        setTimeout(function() {
                            window.location.href = "{{ route('resort.report.index') }}";
                        }, 1500);
                    },
                    error: function(xhr) {
                        // Show validation errors
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            let errorHtml = '<div class="alert alert-danger"><ul>';
                            $.each(errors, function(key, messages) {
                                $.each(messages, function(i, msg) {
                                    errorHtml += '<li>' + msg + '</li>';
                                });
                            });
                            errorHtml += '</ul></div>';
                            $form.prepend(errorHtml);
                        } else {
                            $form.prepend('<div class="alert alert-danger">An error occurred. Please try again.</div>');
                        }
                    }
                });
            }
        });
     


});



</script>
@endsection