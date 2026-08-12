
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
                        <a href="{{ route('resort.report.index')}}" class="btn eb-btn-secondary"> Report List</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
    <form id="report-create-form" data-parsley-validate>
        @csrf
        <div class="row">
            <div class="col-md-6 form-group mb-3">
                <label for="name">Report Name <span class="req_span">*</span></label>
                <input type="text" class="form-control" id="name" name="name"
                    value="{{ $report->name }}" required data-parsley-required
                    data-parsley-required-message="Report name is required">
            </div>

            <div class="col-md-6 form-group mb-3">
                <label for="description">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"
                    data-parsley-maxlength="250">{{ $report->description }}</textarea>
            </div>

            <!-- Select Module -->
            <div class="col-md-3 form-group mb-3">
                <label for="module_name">Select Module <span class="req_span">*</span></label>
                <select class="form-control" id="module_name" required data-parsley-required
                    data-parsley-required-message="Please select a module">
                    <option value="">Select a module</option>
                    @foreach($catalog as $module => $entities)
                        <option value="{{ $module }}" {{ $selected['module'] === $module ? 'selected' : '' }}>{{ $module }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Select Entity -->
            <div class="col-md-3 form-group mb-3">
                <label for="entity_name">Select Data <span class="req_span">*</span></label>
                <select class="form-control" id="entity_name" required data-parsley-required
                    data-parsley-required-message="Please select what to report on"
                    data-parsley-errors-container="#error-msg">
                    <option value="">Select a module first</option>
                </select>
                <input type="hidden" id="module_field" name="module" value="{{ $selected['module'] }}">
                <input type="hidden" id="entity_field" name="entity" value="{{ $selected['entity'] }}">
                <div id="error-msg" class="text-danger mt-1"></div>
            </div>

            <!-- Date Range -->
            <div class="col-md-3 form-group mb-3">
                <label for="from_date">From Date</label>
                <input type="date" class="form-control" id="from_date" name="from_date" value="{{ $report->from_date }}">
            </div>
            <div class="col-md-3 form-group mb-3">
                <label for="to_date">To Date</label>
                <input type="date" class="form-control" id="to_date" name="to_date" value="{{ $report->to_date }}">
            </div>

            <!-- Predefined Fields -->
            <div class="col-md-12 form-group mb-3">
                <label>Select Fields <span class="req_span">*</span></label>
                <div id="fields-container" class="border rounded p-3">
                    <p class="text-muted mb-0">Please select what to report on first</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="col-md-12 form-group mb-3">
                <label>Filters</label>
                <div id="filters-container">
                    <div class="alert alert-info">You can add filters after selecting fields</div>
                </div>
                <button type="button" class="btn eb-btn-secondary mt-2" id="add-filter" style="display: none;">Add Filter</button>
            </div>

            <div class="col-md-12 form-group">
                <button type="submit" class="btn eb-btn-primary">Save Report</button>
                <a href="{{ route('resort.report.index') }}" class="btn eb-btn-neutral">Cancel</a>
            </div>
        </div>
    </form>
</div>
        </div>
    </div>
</div>
@include('resorts._emotional_buttons_v2_styles')
@endsection

@section('import-scripts')
<script>
$(document).ready(function() {

    $('#report-create-form').parsley();

    const reportCatalog = @json($catalog);
    const preselected = @json($selected);

    $("#module_name, #entity_name").select2({ placeholder: "Select..." });

    const fieldsPlaceholder = '<p class="text-muted mb-0">Please select what to report on first</p>';

    function clearFilters() {
        $('#filters-container').html('<div class="alert alert-info">You can add filters after selecting fields</div>');
    }

    function renderFields(fields, checked) {
        checked = checked || [];
        let html = '<div class="row">';
        fields.forEach(function(label, i){
            const id = 'field-' + i;
            const isChecked = checked.indexOf(label) !== -1 ? 'checked' : '';
            html += `
                <div class="col-md-4 col-sm-6 mb-2">
                    <div class="form-check">
                        <input class="form-check-input field-checkbox" type="checkbox"
                               name="fields[]" value="${label}" id="${id}" ${isChecked}>
                        <label class="form-check-label" for="${id}">${label}</label>
                    </div>
                </div>`;
        });
        html += '</div>';
        $('#fields-container').html(html);

        $('.field-checkbox').on('change', function(){
            $('#add-filter').toggle($('.field-checkbox:checked').length > 0);
        });
        $('#add-filter').toggle($('.field-checkbox:checked').length > 0);
    }

    function loadEntities(module, selectedEntity) {
        const entities = reportCatalog[module] || {};
        let opts = '<option value="">Select data</option>';
        $.each(entities, function(entityLabel){
            const sel = entityLabel === selectedEntity ? 'selected' : '';
            opts += `<option value="${entityLabel}" ${sel}>${entityLabel}</option>`;
        });
        $('#entity_name').html(opts).trigger('change.select2');
    }

    $(document).on('change', '#module_name', function(){
        const module = $(this).val();
        $('#module_field').val(module);
        $('#entity_field').val('');
        loadEntities(module, '');
        $('#fields-container').html(fieldsPlaceholder);
        $('#add-filter').hide();
        clearFilters();
    });

    $(document).on('change', '#entity_name', function(){
        const module = $('#module_name').val();
        const entity = $(this).val();
        $('#entity_field').val(entity);
        const def = (reportCatalog[module] || {})[entity];
        if (!def || !Array.isArray(def.fields) || def.fields.length === 0) {
            $('#fields-container').html(fieldsPlaceholder);
            $('#add-filter').hide();
            clearFilters();
            return;
        }
        renderFields(def.fields);
        clearFilters();
    });

    // Prefill on load.
    if (preselected.module) {
        loadEntities(preselected.module, preselected.entity);
        const def = (reportCatalog[preselected.module] || {})[preselected.entity];
        if (def && Array.isArray(def.fields)) {
            renderFields(def.fields, preselected.fields || []);
        }
    }

    let filterCount = 0;
    $('#add-filter').click(function() {
        const options = $('.field-checkbox:checked').map(function() {
            return `<option value="${$(this).val()}">${$(this).val()}</option>`;
        }).get().join('');
        const filterHtml = `
            <div class="card mb-3 filter-card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <select name="filters[${filterCount}][field]" class="form-control" required>
                                <option value="">Select Field</option>${options}
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="filters[${filterCount}][operator]" class="form-control" required>
                                <option value="equals">Equals</option>
                                <option value="contains">Contains</option>
                                <option value="greater_than">Greater Than</option>
                                <option value="less_than">Less Than</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="filters[${filterCount}][value]" class="form-control" placeholder="Value" required>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn eb-btn-critical remove-filter">Remove</button>
                        </div>
                    </div>
                </div>
            </div>`;
        $('#filters-container').append(filterHtml);
        filterCount++;
        $('.remove-filter').click(function() { $(this).closest('.filter-card').remove(); });
    });

    $('#report-create-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        if (!$form.parsley().isValid()) { $form.parsley().validate(); return false; }
        if ($('.field-checkbox:checked').length === 0) {
            toastr.error('Please select at least one field for the report.', 'Error', { positionClass: 'toast-bottom-right' });
            return false;
        }
        var formData = new FormData(this);
        $form.find('.alert-danger').remove();
        $.ajax({
            url: "{{ route('reports.store') }}",
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('input[name="_token"]').val() },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                } else {
                    toastr.error(response.message, "Error", { positionClass: 'toast-bottom-right' });
                }
                setTimeout(function() { window.location.href = "{{ route('resort.report.index') }}"; }, 1500);
            },
            error: function(xhr) {
                let msg = 'An error occurred. Please try again.';
                if (xhr.status === 422 && xhr.responseJSON) {
                    msg = xhr.responseJSON.message || msg;
                }
                $form.prepend('<div class="alert alert-danger">' + msg + '</div>');
            }
        });
    });
});
</script>
@endsection
