@extends('resorts.layouts.app')
@section('page_tab_title' , $page_title)

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>WORKFORCE PLANNING</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs mb-3" id="pcCategoryTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pc-tab-casual" data-pc-category="Casual" type="button">Casual</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="pc-tab-intern" data-pc-category="Intern" type="button">Intern</button>
                    </li>
                </ul>

                <form id="positionConfigForm" class="row g-3 align-items-end mb-4">
                    @csrf
                    <input type="hidden" name="employee_category" id="pc_employee_category" value="Casual">
                    <div class="col-md-3">
                        <label class="form-label">Division</label>
                        <select class="form-select" id="pc_division" required>
                            <option value="">Select Division</option>
                            @foreach ($resort_divisions as $division)
                                <option value="{{ $division->id }}">{{ $division->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Department</label>
                        <select class="form-select" id="pc_department" name="dept_id" required disabled>
                            <option value="">Select Department</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Section</label>
                        <select class="form-select" id="pc_section" name="section_id" disabled>
                            <option value="">Select Section (optional)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Position Title</label>
                        <input type="text" class="form-control" id="pc_position_title" name="position_title" placeholder="e.g. Gardener" required>
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn wfp-btn-primary" id="pcSaveBtn">Add Position</button>
                    </div>
                </form>

                <h5 id="pc-list-title">Casual positions</h5>
                <div class="table-responsive">
                    <table class="table table-collapse">
                        <thead>
                            <tr>
                                <th>Position</th>
                                <th>Department</th>
                                <th>Section</th>
                            </tr>
                        </thead>
                        <tbody id="pc-position-list">
                            @foreach ($positions->where('employee_category', 'Casual') as $pos)
                                <tr data-pc-category="Casual">
                                    <td>{{ $pos->position_title }}</td>
                                    <td>{{ $pos->department->name ?? '' }}</td>
                                    <td>{{ $pos->section->name ?? '' }}</td>
                                </tr>
                            @endforeach
                            @foreach ($positions->where('employee_category', 'Intern') as $pos)
                                <tr data-pc-category="Intern" style="display:none;">
                                    <td>{{ $pos->position_title }}</td>
                                    <td>{{ $pos->department->name ?? '' }}</td>
                                    <td>{{ $pos->section->name ?? '' }}</td>
                                </tr>
                            @endforeach
                            @if($positions->isEmpty())
                                <tr id="pc-empty-row"><td colspan="3">No positions configured yet.</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@include('resorts.workforce_planning._wfp_buttons_v2_styles')
@endsection

@section('import-scripts')
<script>
    $(function () {
        $('#pcCategoryTabs button').on('click', function () {
            $('#pcCategoryTabs button').removeClass('active');
            $(this).addClass('active');
            var category = $(this).data('pc-category');
            $('#pc_employee_category').val(category);
            $('#pc-list-title').text(category + ' positions');
            $('#pc-position-list tr[data-pc-category]').hide();
            $('#pc-position-list tr[data-pc-category="' + category + '"]').show();
        });

        $('#pc_division').on('change', function () {
            var divisionId = $(this).val();
            $('#pc_department').html('<option value="">Select Department</option>').prop('disabled', true);
            $('#pc_section').html('<option value="">Select Section (optional)</option>').prop('disabled', true);
            if (!divisionId) return;

            $.ajax({
                url: '{{ route('people.getDepartmentsByDivision') }}',
                type: 'GET',
                data: { division_id: divisionId },
                success: function (res) {
                    var html = '<option value="">Select Department</option>';
                    (res.departments || []).forEach(function (dept) {
                        html += '<option value="' + dept.id + '">' + dept.name + '</option>';
                    });
                    $('#pc_department').html(html).prop('disabled', false);
                }
            });
        });

        $('#pc_department').on('change', function () {
            var departmentId = $(this).val();
            $('#pc_section').html('<option value="">Select Section (optional)</option>').prop('disabled', true);
            if (!departmentId) return;

            $.ajax({
                url: '{{ route('people.getSectionByDepartment') }}',
                type: 'GET',
                data: { department_id: departmentId },
                success: function (res) {
                    var sections = res.sections || [];
                    if (!sections.length) return;
                    var html = '<option value="">Select Section (optional)</option>';
                    sections.forEach(function (section) {
                        html += '<option value="' + section.id + '">' + section.name + '</option>';
                    });
                    $('#pc_section').html(html).prop('disabled', false);
                }
            });
        });

        $('#positionConfigForm').on('submit', function (e) {
            e.preventDefault();
            var category = $('#pc_employee_category').val();

            $.ajax({
                url: '{{ route('resort.positionconfig.store') }}',
                type: 'POST',
                data: $(this).serialize(),
                success: function (res) {
                    if (!res.success) {
                        toastr.error(res.message || 'Could not save position.', 'Error');
                        return;
                    }
                    toastr.success(res.message, 'Success');
                    $('#pc-empty-row').remove();
                    var pos = res.position;
                    var row = '<tr data-pc-category="' + category + '">' +
                        '<td>' + $('<div>').text(pos.position_title).html() + '</td>' +
                        '<td>' + $('<div>').text((pos.department && pos.department.name) || '').html() + '</td>' +
                        '<td>' + $('<div>').text((pos.section && pos.section.name) || '').html() + '</td>' +
                        '</tr>';
                    $('#pc-position-list').append(row);
                    $('#pc_position_title').val('');
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && (xhr.responseJSON.message || 'Validation error')) || 'Server error';
                    toastr.error(msg, 'Error');
                }
            });
        });
    });
</script>
@endsection
