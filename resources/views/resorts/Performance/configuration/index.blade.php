@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<style>
    #performance-config-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #performance-config-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="performance-config-hero">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Performance</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <!-- <div class="col-auto">
                    <div class="d-flex justify-content-end">
                        <a href="#" class="btn btn-theme">Request Manning</a>
                    </div>
                </div> -->
            </div>
        </div>

        <div>
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-title">
                            <h3>Form Templates</h3>
                            <div class="text-end">
                                <a href="{{ route('Performance.config.PerformanceTemplateFormList') }}" class="a-link">View All</a>
                            </div>
                        </div>
                        <p class="text-muted mb-3">Build position-specific appraisal templates. Select hierarchy below to create a targeted form template.</p>
                        <form id="FormTemplate">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-500">Division</label>
                                    <select class="form-select ResortDivision dd-native-select" id="ResortDivision" name="ResortDivision">
                                        <option value="">Select Division</option>
                                        @if($resort_divisions->isNotEmpty())
                                            @foreach($resort_divisions as $resort_division)
                                                <option value="{{ $resort_division->id }}">{{ $resort_division->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <div class="dd" data-target="#ResortDivision">
                                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                            <span class="dd-lbl">Select Division</span>
                                            <svg class="dd-chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                        </button>
                                        <div class="dd-panel" role="listbox" aria-label="Division">
                                            <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a division…"></div>
                                            <div class="dd-scroll">
                                                <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select Division</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                @if($resort_divisions->isNotEmpty())
                                                    @foreach($resort_divisions as $resort_division)
                                                    <div class="dd-item" role="option" data-value="{{ $resort_division->id }}"><span class="dd-nm">{{ $resort_division->name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-500">Department</label>
                                    <select class="form-select Department dd-native-select" id="Department">
                                        <option value="">Select Department</option>
                                    </select>
                                    <div class="dd" data-target="#Department">
                                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                            <span class="dd-lbl">Select Department</span>
                                            <svg class="dd-chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                        </button>
                                        <div class="dd-panel" role="listbox" aria-label="Department">
                                            <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a department…"></div>
                                            <div class="dd-scroll">
                                                <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select Department</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-500">Section</label>
                                    <select class="form-select Section dd-native-select" id="Section">
                                        <option value="">Select Section</option>
                                    </select>
                                    <div class="dd" data-target="#Section">
                                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                            <span class="dd-lbl">Select Section</span>
                                            <svg class="dd-chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                        </button>
                                        <div class="dd-panel" role="listbox" aria-label="Section">
                                            <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a section…"></div>
                                            <div class="dd-scroll">
                                                <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select Section</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-500">Position</label>
                                    <select class="form-select Position dd-native-select" id="Position">
                                        <option value="">Select Position</option>
                                    </select>
                                    <div class="dd" data-target="#Position">
                                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                            <span class="dd-lbl">Select Position</span>
                                            <svg class="dd-chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                        </button>
                                        <div class="dd-panel" role="listbox" aria-label="Position">
                                            <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a position…"></div>
                                            <div class="dd-scroll">
                                                <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select Position</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="button" class="btn perf-btn-accent FormTemplate w-100" data-flag="simple" disabled>
                                    <i class="fa-solid fa-plus me-1"></i> Create Template
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-title">
                            <h3>Performance Meeting Email</h3>
                        </div>
                        <p class="text-muted mb-3">Customize the email template sent to participants when scheduling performance meetings.</p>
                        <form id="ScheduleMeetingEmailForm">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-500">Email Template</label>
                                <textarea class="form-control ScheduleMeetingEmail" rows="7" name="ScheduleMeetingEmail" id="ScheduleMeetingEmail">{!! isset($PerformanceMeetingContent->content) ? $PerformanceMeetingContent->content : ''  !!}</textarea>
                            </div>

                            <div class="placeholders-box">
                                <strong class="d-block mb-2"><i class="fa-solid fa-tag me-1"></i> Available Placeholders</strong>
                                <div class="row g-2">
                                    <div class="col-md-6"><code class="insert-placeholder">{Employee_name}</code></div>
                                    <div class="col-md-6"><code class="insert-placeholder">{Title}</code></div>
                                    <div class="col-md-6"><code class="insert-placeholder">{Description}</code></div>
                                    <div class="col-md-6"><code class="insert-placeholder">{Meeting_Link}</code></div>
                                    <div class="col-md-6"><code class="insert-placeholder">{Meeting_Date}</code></div>
                                    <div class="col-md-6"><code class="insert-placeholder">{Meeting_Time}</code></div>
                                    <div class="col-md-6"><code class="insert-placeholder">{Meeting_Location}</code></div>
                                    <div class="col-md-6"><code class="insert-placeholder">{Your_Name}</code></div>
                                    <div class="col-md-6"><code class="insert-placeholder">{Your_Designation}</code></div>
                                    <div class="col-md-6"><code class="insert-placeholder">{Resort_Name}</code></div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-3">
                                <button type="submit" class="btn perf-btn-primary w-100">
                                    <i class="fa-regular fa-save me-1"></i> Save Template
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- <div class="col-lg-6 ">
                    <div class="card h-100">
                        <div class="card-title">
                            <h3>Review Types</h3>
                        </div>
                        <div class="mb-3">
                            <button type="button" class="btn btn-themeSkyblue AddReviewCategories">Add Review Categories</button>
                        </div>
                            <form id="reviewForm" data-parsley-validate>
                                @csrf
                                <div class="AppendReviewCategories">
                                    <div class="row g-md-4 g-3 mb-1 flex-grow-1">
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control" name="category_title[]" placeholder="Category Title" required data-parsley-required-message="Category title is required">
                                        </div>
                                        <div class="col-sm-6">
                                            <input type="number" class="form-control" placeholder="Weightage" name="category_weightage[]" required data-parsley-type="number" data-parsley-required-message="Weightage is required">
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" id="review_categoriesCounts" name="review_categoriesCounts" value="1">

                                <div class="card-footer text-end">
                                        <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                                </div>
                            </form>
                            <div class="row g-md-4 g-3 mb-1 flex-grow-1">
                                <table class="table table-ReviewType" id="ReviewType">
                                    <thead>
                                        <tr>
                                            <th>Category Title</th>
                                            <th>Weightage (%)</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                </table>

                            </div>



                    </div>
                </div> --}}




                <div class="col-xl-4 col-lg-6 col-md-6">
                    <div class="card h-100 d-flex flex-column">
                        <div class="card-title">
                            <h3>Mid-Year / Half-Year Appraisal</h3>
                            <div class="text-end">
                                <a href="{{ route('Performance.config.ProfessionalFormList', ['type' => 'ProfessionalForm']) }}" class="a-link">View All</a>
                            </div>
                        </div>
                        <p class="text-muted">Mid-year performance appraisal form for regular reviews.</p>
                        <div class="mt-auto">
                            <button type="button" class="btn perf-btn-accent w-100" data-flag="ProfessionalForm" id="ProfessionalForm">
                                <i class="fa-solid fa-plus me-1"></i> Create Template
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-6 col-md-6">
                    <div class="card h-100 d-flex flex-column">
                        <div class="card-title">
                            <h3>90 Day Appraisal Form</h3>
                            <div class="text-end">
                                <a href="{{ route('Performance.config.NintyPerformanceFormList') }}" class="a-link">View All</a>
                            </div>
                        </div>
                        <p class="text-muted">Probationary review form for new hires within 90 days.</p>
                        <div class="mt-auto">
                            <button type="button" class="btn perf-btn-accent w-100" data-flag="nintyDayForm" id="nintyDayForm">
                                <i class="fa-solid fa-plus me-1"></i> Create Template
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-lg-6 col-md-6">
                    <div class="card h-100 d-flex flex-column">
                        <div class="card-title">
                            <h3>Annual Appraisal</h3>
                            <div class="text-end">
                                <a href="{{ route('Performance.config.ProfessionalFormList', ['type' => 'annualAppraisal']) }}" class="a-link">View All</a>
                            </div>
                        </div>
                        <p class="text-muted">Yearly performance appraisal form for end-of-year reviews.</p>
                        <div class="mt-auto">
                            <button type="button" class="btn perf-btn-accent w-100" data-flag="annualAppraisal" id="annualAppraisal">
                                <i class="fa-solid fa-plus me-1"></i> Create Template
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-6 col-md-6">
                    <div class="card h-100 d-flex flex-column">
                        <div class="card-title">
                            <h3>Performance Improvement Plan (PIP)</h3>
                            <div class="text-end">
                                <a href="{{ route('Performance.config.ProfessionalFormList', ['type' => 'pipForm']) }}" class="a-link">View All</a>
                            </div>
                        </div>
                        <p class="text-muted">Action plan for employees requiring performance improvement.</p>
                        <div class="mt-auto">
                            <button type="button" class="btn perf-btn-accent w-100" data-flag="pipForm" id="pipForm">
                                <i class="fa-solid fa-plus me-1"></i> Create Template
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-6 col-md-6">
                    <div class="card h-100 d-flex flex-column">
                        <div class="card-title">
                            <h3>Professional Development Plan (PDP)</h3>
                            <div class="text-end">
                                <a href="{{ route('Performance.config.ProfessionalFormList', ['type' => 'pdpForm']) }}" class="a-link">View All</a>
                            </div>
                        </div>
                        <p class="text-muted">Career growth and skill development planning form.</p>
                        <div class="mt-auto">
                            <button type="button" class="btn perf-btn-accent w-100" data-flag="pdpForm" id="pdpForm">
                                <i class="fa-solid fa-plus me-1"></i> Create Template
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="FormTemplete-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <form id="jobDesEditForm" data-parsley-validate>
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel_performance"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="flag" id="flag">
                    <input type="hidden" name="Division_id" id="FormResortDivision">
                    <input type="hidden" name="Department_id" id="FormResortDepartment">
                    <input type="hidden" name="Section_id" id="FormResortSeciton">
                    <input type="hidden" name="Position_id" id="FormResortPosition">
                    <div class="row">
                        <div class="col-md-3">
                            <label>Form Name</label>
                            <input type="text" name="form_name" id="form_name" class="form-control"
                                required data-parsley-required-message="Form Name is required">
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12">
                            <div id="form-builder"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal" class="btn perf-btn-neutral ms-auto">Cancel</a>
                    <button type="button" id="save-form" class="btn perf-btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
@include('resorts.Performance._performance_buttons_v2_styles')
@include('resorts._dropdown_styles')
@include('resorts._dropdown_script')
@endsection

@section('import-css')
<style>
    .form-actions { display: none; }
    .rating-field { padding: 8px 0; }
    .rating-stars { display: flex; gap: 4px; }
    .rating-star { font-size: 28px !important; cursor: pointer; color: #ccc; transition: color 0.2s; }
    .table-field table { width: 100%; margin-top: 8px; }
    .table-field th { background: var(--teal); color: #fff; font-weight: 500; padding: 8px 12px; }
    .table-field td { background: #fff; padding: 8px 12px; }
    .table-field th[contenteditable]:focus, .table-field td[contenteditable]:focus { outline: 2px solid var(--aqua); }

    /* Configuration page polish */
    .placeholders-box { background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 8px; padding: 14px; font-size: 12px; }
    .placeholders-box strong { color: var(--teal); font-size: 13px; }
    .placeholders-box code.insert-placeholder { display: inline-block; background: #fff; border: 1px solid #dee2e6; padding: 4px 8px; border-radius: 4px; cursor: pointer; color: var(--teal); font-size: 11px; transition: all 0.2s; }
    .placeholders-box code.insert-placeholder:hover { background: var(--teal); color: #fff; border-color: var(--teal); }
    .card .card-title { margin-bottom: 12px; }
    .card p.text-muted { font-size: 13px; line-height: 1.5; }
    .form-label.fw-500 { font-weight: 500; font-size: 13px; color: #555; margin-bottom: 4px; }

    /* Prevent Select2 overflow */
    #FormTemplate .select2-container { width: 100% !important; max-width: 100%; }
    #FormTemplate .form-select { width: 100%; max-width: 100%; }
</style>
@endsection

@section('import-scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<script src="https://formbuilder.online/assets/js/form-builder.min.js"></script>
<script src="https://formbuilder.online/assets/js/form-render.min.js"></script>
<script>

       $(document).ready(function ()
       {
                // CKEDITOR.replace('ScheduleMeetingEmail');

                //         CKEDITOR.instances['ScheduleMeetingEmail'].destroy(); // Destroy existing CKEditor instance if it exists

            CKEDITOR.replace('ScheduleMeetingEmail'); // Re-initialize CKEditor

            const BASE_URL = "{{ asset('resorts_assets/lang/en-US.lang') }}";

            var templates = {
                starRating: function(fieldData) {
                    return {
                        field: '<div class="rating-field"><div class="rating-stars"></div></div>',
                        onRender: function(evt) {
                            var max = this.config.maxRating || 5;
                            var $stars = $(evt).find('.rating-stars').empty();
                            for (var i = 1; i <= max; i++) {
                                $stars.append('<span class="rating-star" data-value="'+i+'">&#9733;</span>');
                            }
                            $stars.on('click', '.rating-star', function() {
                                $(this).prevAll().addBack().css('color', '#EFB408');
                                $(this).nextAll().css('color', '#ccc');
                            });
                        }
                    };
                },
                ratingTable: function(fieldData) {
                    return {
                        field: '<div class="table-field-wrapper"></div>',
                        onRender: function(evt) {
                            var config = this.config;
                            var colHeadings = (config.columnHeadings || '').split(',').map(function(s){ return s.trim(); }).filter(Boolean);
                            var rowLabels = (config.rowLabels || '').split(',').map(function(s){ return s.trim(); }).filter(Boolean);
                            var cols = colHeadings.length || 3;
                            var rows = rowLabels.length || 3;
                            var hasRowLabels = rowLabels.length > 0;

                            var html = '<table class="table table-bordered table-field"><thead><tr>';
                            if (hasRowLabels) html += '<th style="background:#014653;color:#fff;font-weight:500;padding:8px 12px;"></th>';
                            for (var c = 0; c < cols; c++) {
                                html += '<th style="background:#014653;color:#fff;font-weight:500;padding:8px 12px;">' + (colHeadings[c] || 'Column '+(c+1)) + '</th>';
                            }
                            html += '</tr></thead><tbody>';
                            for (var r = 0; r < rows; r++) {
                                html += '<tr>';
                                if (hasRowLabels) html += '<td style="font-weight:600;background:#f0f0f0;">' + (rowLabels[r] || '') + '</td>';
                                for (var c = 0; c < cols; c++) {
                                    html += '<td style="padding:8px 12px;"><input type="text" class="form-control form-control-sm" placeholder=""></td>';
                                }
                                html += '</tr>';
                            }
                            html += '</tbody></table>';
                            $(evt).find('.table-field-wrapper').html(html);
                        }
                    };
                }
            };
            // Per-field "Responder Roles" — controls who can fill each
            // field on the responder page. Empty / missing = everyone.
            var RESPONDER_ROLE_OPTIONS = {
                'GM': 'GM', 'EXCOM': 'EXCOM', 'HOD': 'HOD', 'HR': 'HR',
                'MGR': 'MGR', 'SUP': 'SUP', 'LINE WORKERS': 'LINE WORKERS',
                'Finance': 'Finance', 'Self': 'Self (appraisee)'
            };
            var RESPONDER_ROLES_ATTR = {
                responder_roles: {
                    label: 'Responder Roles (who fills)',
                    multiple: true,
                    options: RESPONDER_ROLE_OPTIONS
                }
            };
            var ROLE_GATED_TYPES = [
                'text','textarea','password','email','phone','url','number','date','time','hidden',
                'select','checkbox','radio','checkbox-group','radio-group','file','autocomplete',
                'header','paragraph','button','starRating','ratingTable'
            ];
            var typeUserAttrs = {
                starRating: Object.assign({ maxRating: { label: 'Max Rating', value: 5, type: 'number' } }, RESPONDER_ROLES_ATTR),
                ratingTable: Object.assign({
                    columnHeadings: { label: 'Column Headings (comma separated)', value: 'Criteria, Rating, Comments', type: 'text' },
                    rowLabels: { label: 'Row Labels (comma separated)', value: 'Communication, Teamwork, Leadership', type: 'text' }
                }, RESPONDER_ROLES_ATTR)
            };
            ROLE_GATED_TYPES.forEach(function (t) {
                if (!typeUserAttrs[t]) typeUserAttrs[t] = Object.assign({}, RESPONDER_ROLES_ATTR);
            });

            const options = {
                    disableFields: ['autocomplete', 'button'],
                    templates: templates,
                    typeUserAttrs: typeUserAttrs,
                    fields: [
                        { label: 'Star Rating', type: 'starRating', icon: '\u2B50' },
                        { label: 'Table / Matrix', type: 'ratingTable', icon: '\u25A6' }
                    ],
                    i18n: {
                        locale: 'en-US',
                        override: {},
                        url: BASE_URL
                    }
                };

            const formBuilder =$('#form-builder').formBuilder(options);
            $('#save-form').click(function (e) {
                var formname = $('#form_name').val();
                var flag= $('#flag').val();

                if(!isNaN(formname))
                {
                    toastr.error('Please enter Form Name.', "Error", {
                            positionClass: 'toast-bottom-right'
                        });

                    return false;
                }
                const formStructure = formBuilder.actions.getData('json');
                e.preventDefault();
                if(flag =="nintyDayForm")
                {
                    $.ajax({
                        url: "{{ route('Performance.config.NintyDayPeformanceFormStore') }}",
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            FormName: formname, // Example, make dynamic
                            form_structure: formStructure
                        },
                        success: function (response) {
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $("#jobDesEditForm")[0].reset();
                            $("#FormTemplete-modal").modal('hide');

                            $("#ResortDivision").val(null).trigger('change');
                            $(".Department").val(null).trigger('change');
                            $(".Section").val(null).trigger('change');
                            $(".Position").val(null).trigger('change');
                            formBuilder.actions.setData([]);
                            $(".FormTemplate").attr('disabled', true);
                        },
                        error: function(response)
                        {
                            var errors = response.responseJSON;
                            var errs = '';
                            $.each(errors.errors, function(key, error)
                            {
                                errs += error + '<br>';
                            });
                            toastr.error(errs, {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    });
                }
                else if(flag =="ProfessionalForm" || flag =="annualAppraisal" || flag =="pipForm" || flag =="pdpForm")
                {
                    $.ajax({
                        url: "{{ route('Performance.config.ProfessionalFormStore') }}",
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            FormName: formname, // Example, make dynamic
                            form_structure: formStructure,
                            form_type: flag
                        },
                        success: function (response) {
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $("#jobDesEditForm")[0].reset();
                            $("#FormTemplete-modal").modal('hide');

                            $("#ResortDivision").val(null).trigger('change');
                            $(".Department").val(null).trigger('change');
                            $(".Section").val(null).trigger('change');
                            $(".Position").val(null).trigger('change');
                            formBuilder.actions.setData([]);
                            $(".FormTemplate").attr('disabled', true);
                        },
                        error: function(response)
                        {
                            var errors = response.responseJSON;
                            var errs = '';
                            $.each(errors.errors, function(key, error)
                            {
                                errs += error + '<br>';
                            });
                            toastr.error(errs, {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    });
                }
                else
                {
                    $.ajax({
                        url: "{{ route('Performance.config.PerformanceTemplateFormStore') }}",
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            Division_id : $('#FormResortDivision').val(),
                            Department_id : $('#FormResortDepartment').val(),
                            Section_id : $('#FormResortSeciton').val(),
                            Position_id : $('#FormResortPosition').val(),
                            FormName: formname, // Example, make dynamic
                            form_structure: formStructure
                        },
                        success: function (response)
                        {
                            toastr.success('Form saved successfully!', "Success",
                            {
                                positionClass: 'toast-bottom-right'
                            });
                            $("#jobDesEditForm")[0].reset();
                            $("#FormTemplete-modal").modal('hide');
                            $("#ResortDivision").val(null).trigger('change');
                            $(".Department").val(null).trigger('change');
                            $(".Section").val(null).trigger('change');
                            $(".Position").val(null).trigger('change');
                            formBuilder.actions.setData([]);
                            $(".FormTemplate").attr('disabled', true);
                        },
                        error: function(response)
                        {
                            var errors = response.responseJSON;
                            var errs = '';
                            $.each(errors.errors, function(key, error)
                            {
                                errs += error + '<br>';
                            });
                            toastr.error(errs, {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    });
                }

            });
             ReviewType();
            $('#reviewForm').parsley();
            $('#reviewForm').on('submit', function (e) {
                e.preventDefault(); // Prevent form submission

                if ($(this).parsley().isValid()) {
                    $.ajax({
                        url: "{{ route('Performance.config.ReviewTypes') }}",  // Update with your Laravel route
                        type: "POST",
                        data: $(this).serialize(),
                        dataType: "json",
                        beforeSend: function () {
                        },
                        success: function (response) {
                            if (response.success) {
                                toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });
                                $('#reviewForm')[0].reset(); // Reset form
                                $('#reviewForm').parsley().reset(); // Reset Parsley validation
                                ReviewType();
                            } else {
                                toastr.error("Failed to save deductions.", "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        },
                        error: function (xhr) {
                            if (xhr.responseJSON) {
                                const errors = xhr.responseJSON.errors;
                                let errorMessages = '';
                                Object.keys(errors).forEach(function (key) {
                                    errorMessages += `${errors[key]}<br>`;
                                });
                                toastr.error(errorMessages, "Validation Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        }
                    });
                }
            });
            $(".Position , .Section").on("change",function(){
                $(".FormTemplate").attr('disabled', false);
                $("#FormResortDivision").val($('#ResortDivision').val());
                $("#FormResortDepartment").val($('.Department').val());
                $("#FormResortSeciton").val($('.Section').val());
                $("#FormResortPosition").val($('.Position').val());
            });




        });

            $('#ScheduleMeetingEmailForm').validate({
                rules: {
                    ScheduleMeetingEmail: {
                        required: true,
                    }
                },
                messages: {
                    ScheduleMeetingEmail: {
                        required: "Please Enter Meeting Content",
                    }
                },
                submitHandler: function(form) {
                    const content = CKEDITOR.instances['ScheduleMeetingEmail'].getData(); // Get CKEditor content
                    $.ajax({
                        url: "{{ route('Performance.config.ScheduleMeetingEmail') }}",
                        type: "POST",
                        data: {
                            "ScheduleMeetingEmail": content, // Send CKEditor content
                            "_token": "{{ csrf_token() }}"
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });
                                // Close modal after success
                                $('#jobDesEdit-modal').modal('hide');
                            }
                        },
                        error: function(xhr, status, error) {
                            let errs = '';

                            if (xhr.status === 422) {
                                let response = xhr.responseJSON;
                                if (response.errors) {
                                    $.each(response.errors, function(key, value) {
                                        errs += value[0] + '<br>';
                                    });
                                }
                            } else {
                                errs = 'An unexpected error occurred. Please try again.';
                            }

                            toastr.error(errs, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        },
                        complete: function() {
                            $("#jobDesEdit-modal form")[0].reset();
                            $("#jobDesEdit-modal input[type='hidden']").val('');

                            // Reset CKEditor
                            if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances['ScheduleMeetingEmail']) {
                                CKEDITOR.instances['ScheduleMeetingEmail'].setData('');
                            }
                        }
                    });
                }
            });
        $(document).on('click', '.FormTemplate , #nintyDayForm , #ProfessionalForm , #annualAppraisal , #pipForm , #pdpForm', function()
        {
            var flag= $(this).data('flag');
            $("#flag").val(flag);
            $('#form_name').val(' ');
            $("#FormTemplete-modal").modal('show');
            var header='';
            if(flag =="ProfessionalForm")
            {
                header ="Professional Development Form";
            }
            else if(flag =="nintyDayForm")
            {
                header ="90 Day Form";
            }
            else if(flag =="annualAppraisal")
            {
                header ="Annual Appraisal";
            }
            else if(flag =="pipForm")
            {
                header ="Performance Improvement Plan (PIP)";
            }
            else if(flag =="pdpForm")
            {
                header ="Professional Development Plan (PDP)";
            }
            else
            {
                header ="Form Template";
            }
            $("#staticBackdropLabel_performance").text(header);
        });
        $(document).on('change', '.ResortDivision', function() {
            $.ajax({
                url: "{{ route('resort.get.ResortDivision') }}",
                type: "post",
                data: {
                    division_id: $(this).val(),
                },
                success: function(data) {

                    // Clear the dropdown and add a placeholder option
                    $(".Department").html('<option value="">Select Department</option>');
                    window.wisdomDD.rebuild('#Department');
                    if(data.success == true) {
                        let string='<option value="">Select Department</option>';
                        // Append new options
                        $.each(data.data, function(key, value) {

                            string+='<option value="'+value.id+'">'+value.name+'</option>';
                        });

                        $(".Department").html(string);
                        window.wisdomDD.rebuild('#Department');

                    } else {
                        let string='<option value="">Select Department</option>';
                    }
                },
                error: function(response) {
                    toastr.error("Department Not Found", { positionClass: 'toast-bottom-right' });
                }
            });
        });

        $(document).on('change', '.Department', function() {
            var deptId = $(this).val();
            $.ajax({
                url: "{{ route('resort.ta.PositionSections') }}",
                type: "post",
                data: {
                    deptId: deptId
                },
                success: function(d) {
                    // Clear the dropdown and add a placeholder option


                    if(d.success == true) {

                        let string='<option value="">Select Position</option>';
                        $.each(d.data.ResortPosition, function(key, value) {
                            string+='<option value="'+value.id+'">'+value.position_title+'</option>';
                        });
                        $(".Position").html(string);
                        window.wisdomDD.rebuild('#Position');

                        let string1='<option value="">Select Section</option>';
                        $.each(d.data.ResortSection, function(key, value) {
                            string1+='<option value="'+value.id+'">'+value.name+'</option>';
                        });
                        $(".Section").html(string1);
                        window.wisdomDD.rebuild('#Section');

                    }
                },
                error: function(response) {
                    toastr.error("Position Not Found", { positionClass: 'toast-bottom-right' });
                }
            });
        });
        $(document).on('click', '.AddReviewCategories', function () {

            id = $("#review_categoriesCounts").val();

            id = parseInt(id) + 1;
            $(".AppendReviewCategories").append(`
                            <div class="row g-md-4 g-3 mb-1 remove_id_${id} flex-grow-1">
                                <hr>
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control" name="category_title[]" placeholder="Category Title" required data-parsley-required-message="Category title is required">
                                        </div>
                                        <div class="col-sm-4">
                                            <input type="number" class="form-control" placeholder="Weightage" name="category_weightage[]" required data-parsley-type="number" data-parsley-required-message="Weightage is required">
                                        </div>

                                    <div class="col-sm-2">
                                        <button type="button" data-id="${id}"  class="btn btn-sm eb-btn-critical removeReviewCategories"><i class="fa fa-trash"></i></button>
                                    </div>
                            </div>
                            `);
            $("#review_categoriesCounts").val(id);

        });
        $(document).on('click', '.removeReviewCategories', function () {
            var locaiton = $(this).data('id');
            $(".remove_id_" + locaiton).remove();

            id = parseInt(locaiton) - 1;

          $("#review_categoriesCounts").val(id);

        });


        $(document).on("click", "#ReviewType .edit-row-btn", function (event) {
            event.preventDefault(); // Prevent default action

            // Find the parent row
            var $row = $(this).closest("tr");

            // Extract division ID
            var id = $(this).data('id');

            var category_title = $row.find("td:nth-child(1)").text().trim();
            var category_weightage = $row.find("td:nth-child(2)").text().trim();

            var editRowHtml = `
                    <td class="py-1">
                        <div class="form-group">
                            <input type="text" class="form-control category_title" value="${category_title}" />
                        </div>
                    </td>
                    <td class="py-1">
                        <div class="form-group">
                            <input type="number" class="form-control category_weightage" value="${category_weightage}" />
                        </div>
                    </td>
                    <td class="py-1">
                        <a href="#" class="btn btn-theme update-row-btn_ReviewType" data-id="${id}">Submit</a>
                    </td>
                `;

            // Replace row content with editable form
            $row.html(editRowHtml);
        });
        $(document).on("click", "#ReviewType .update-row-btn_ReviewType", function (event) {
            event.preventDefault(); // Prevent default action
            var $row = $(this).closest("tr");
            var id = $(this).data('id');
            var category_title = $row.find("input").eq(0).val();
            var category_weightage = $row.find("input").eq(1).val();

            $.ajax({
                url: "{{ route('Performance.config.ReviewTypes.inlineUpdate', '') }}/" + id,
                type: "PUT",
                data: {
                    category_title : category_title,
                    category_weightage : category_weightage,
                },
                success: function(response) {
                    if(response.success == true) { // Ensure response contains a success key
                        // Update the row with new values
                        var updatedRowHtml = `
                            <td class="text-nowrap">${category_title}</td>
                            <td class="text-nowrap">${category_weightage}</td>
                            <td class="text-nowrap">
                                <div class="d-flex align-items-center">
                                    <a href="#" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-id="${id}">
                                        <img src="{{ asset('resorts_assets/images/edit.svg') }}" alt="" class="img-fluid" />
                                    </a>
                                    <a href="#" class="btn-lg-icon icon-bg-red delete-row-btn" data-id="${id}">
                                        <img src="{{ asset('resorts_assets/images/trash-red.svg') }}" alt="" class="img-fluid" />
                                    </a>
                                </div>
                            </td>
                        `;

                        $row.html(updatedRowHtml);

                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(xhr) {

                        let errorMessage = '';

                        if (xhr.responseJSON && xhr.responseJSON.errors) {

                            $.each(xhr.responseJSON.errors, function(key, error) {

                            errorMessage += error + "<br>";
                            })
                        }
                        else
                        {
                            errorMessage = "An error occurred while Create or Update."; // Default error message
                        }

                        toastr.error(errorMessage, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
            });


        });


        $(document).on('click', '.delete-row-btn', function (e) {
            e.preventDefault();
            var $button = $(this);
            var $row = $button.closest("tr");

            // Get the division ID from the data attribute
            var main_id = $(this).data('id');

            wisdomConfirm({
                role: 'destructive',
                title: 'Sure want to delete?',
                text: 'This cannot be undone',
                confirmText: 'Yes',
                cancelText: 'No'
            }).then((result) => {
                if (result.isConfirmed)
                {

                    $.ajax({
                        type: "delete",
                        url: "{{ route('Performance.DestroyReviewTypes','') }}/"+main_id,
                        dataType: "json",
                    }).done(function(result) {
                        if (result.success == true) {
                            $row.remove();

                            toastr.success(result.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });

                            ReviewType();

                        }
                            else {
                            toastr.error(result.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    }).fail(function(error) {
                        toastr.error("Something went wrong", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    });
                }
            });
        });

        function ReviewType()
        {
            if ($.fn.dataTable.isDataTable('#ReviewType'))
            {
                $('#ReviewType').DataTable().destroy();
            }

            var TableAccomMainten = $('#ReviewType').DataTable({
                "searching": false,
                "bLengthChange": false,
                "bFilter": true,
                "bInfo": true,
                "bAutoWidth": false,
                "scrollX": true,
                "iDisplayLength": 6,
                processing: true,
                serverSide: true,
                order:[[3,'desc']],
                ajax: {
                    url: '{{ route("Performance.configuration") }}',
                    type: 'GET',
                    data: function (d) {
                        d.ResortDepartment = $(".ResortDepartment").val();
                    }
                },
                columns: [
                    { data: 'category_title', name: 'category_title', className: 'text-nowrap' },
                    { data: 'category_weightage', name: 'category_weightage', className: 'text-nowrap' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                    {data:'created_at', visible:false,searchable:false},
                ]
            });
        }
</script>
@endsection
