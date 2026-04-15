@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

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
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-title">
                            <h3>Form Templates</h3>
                            <div class="text-end">
                            </div>
                        </div>
                        <div class="row g-md-4 g-3 mb-1 flex-grow-1">
                            <table class="table table-PerformanceTemplateFormList" id="table-PerformanceTemplateFormList">
                                <thead>
                                    <tr>

                                        <th> Form Name</th>
                                        <th> Action</th>
                                    </tr>
                                </thead>
                            </table>

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
                    <h5 class="modal-title" id="staticBackdropLabel">Edit Professional Development Form</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="Form_id" id="Form_id">
                    <div class="row">
                        <div class="col-md-3">
                            <label>Form Name</label>
                            <input type="text" name="form_name" id="form_name" class="form-control"
                                required data-parsley-required-message="Form Name is required" value="">
                        </div>
                        <input type="hidden" name="id" id="form_id">
                    </div>
                    <div class="row mt-2">
                        <div class="col-12">
                            <div id="form-builder"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    <button type="button" id="save-form" class="btn btn-themeBlue">Submit</button>
                </div>
            </form>

        </div>
    </div>
</div>
<div class="modal fade" id="FormView-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">View Professional Development Form</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="form-render-view"></div>
            </div>
            <div class="modal-footer">
                <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Close</a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
<style>
    .form-actions { display: none; }
    .rating-field { padding: 8px 0; }
    .rating-stars { display: flex; gap: 4px; }
    .rating-star { font-size: 28px !important; cursor: pointer; color: #ccc; transition: color 0.2s; }
    .table-field table { width: 100%; margin-top: 8px; }
    .table-field th { background: #014653; color: #fff; font-weight: 500; padding: 8px 12px; }
    .table-field td { background: #fff; padding: 8px 12px; }
    .table-field th[contenteditable]:focus, .table-field td[contenteditable]:focus { outline: 2px solid #2EACB3; }
</style>
@endsection

@section('import-scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<script src="https://formbuilder.online/assets/js/form-builder.min.js"></script>
<script src="https://formbuilder.online/assets/js/form-render.min.js"></script>
<script>
$(document).ready(function ()
{
    ReviewType();
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
                    for (var c = 0; c < cols; c++) html += '<th style="background:#014653;color:#fff;font-weight:500;padding:8px 12px;">' + (colHeadings[c] || 'Column '+(c+1)) + '</th>';
                    html += '</tr></thead><tbody>';
                    for (var r = 0; r < rows; r++) {
                        html += '<tr>';
                        if (hasRowLabels) html += '<td style="font-weight:600;background:#f0f0f0;">' + (rowLabels[r] || '') + '</td>';
                        for (var c = 0; c < cols; c++) html += '<td style="padding:8px 12px;"><input type="text" class="form-control form-control-sm" placeholder=""></td>';
                        html += '</tr>';
                    }
                    html += '</tbody></table>';
                    $(evt).find('.table-field-wrapper').html(html);
                }
            };
        }
    };
    var typeUserAttrs = {
        starRating: { maxRating: { label: 'Max Rating', value: 5, type: 'number' } },
        ratingTable: {
            columnHeadings: { label: 'Column Headings (comma separated)', value: 'Criteria, Rating, Comments', type: 'text' },
            rowLabels: { label: 'Row Labels (comma separated)', value: 'Communication, Teamwork, Leadership', type: 'text' }
        }
    };

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
    var formBuilder = $('#form-builder').formBuilder(options);

    $(document).on("click", ".edit-row-btn", function(){
        var id = $(this).data('id');
        var name = $(this).data('name');
        $("#Form_id").val(id);
        $("#FormTemplete-modal").modal('show');

        // Clear existing fields instead of emptying the div
        formBuilder.actions.clearFields();

        $.ajax({
            url: "{{ route('Performance.config.GetProfessionalForm', ['id' => '__ID__']) }}".replace('__ID__', id),
            type: "get",
            data: {
                id: id,
            },
            success: function (response) {
                var d = response.data;
                $("#form_name").val(d[1]);
                $("#Form_id").val(d[2]);
                let existingFormStructure = d[0];

                console.log("Loaded Form Structure:", existingFormStructure);

                // Set existing form data
                // formBuilder.promise.then(function() {
                    formBuilder.actions.setData(existingFormStructure);
                // });

                toastr.success('Form loaded successfully!', "Success", {
                    positionClass: 'toast-bottom-right'
                });
            },
            error: function(response)
            {
                var errors = response.responseJSON;
                var errs = '';
                $.each(errors.errors, function(key, error) {
                    errs += error + '<br>';
                });
                toastr.error(errs, {
                    positionClass: 'toast-bottom-right'
                });
            }
        });
    });

    // View button handler
    $(document).on("click", ".view-row-btn", function(){
        var id = $(this).data('id');
        $("#FormView-modal").modal('show');
        $('#form-render-view').empty();

        $.ajax({
            url: "{{ route('Performance.config.GetProfessionalForm', ['id' => '__ID__']) }}".replace('__ID__', id),
            type: "get",
            data: { id: id },
            success: function (response) {
                var d = response.data;
                var formData = d[0];

                // Handle double-encoded JSON
                if (typeof formData === 'string') {
                    try { formData = JSON.parse(formData); } catch(e) {}
                }

                var html = '';
                var fields = Array.isArray(formData) ? formData : [];
                fields.forEach(function(field) {
                    html += '<div class="rendered-field mb-3">';
                    html += '<label class="form-label fw-bold">' + (field.label || '') + '</label>';

                    if (field.type === 'starRating') {
                        var max = field.maxRating || 5;
                        html += '<div class="rating-stars">';
                        for (var i = 1; i <= max; i++) {
                            html += '<span class="rating-star" style="font-size:28px;color:#EFB408;cursor:default;">&#9733;</span>';
                        }
                        html += '</div>';
                    } else if (field.type === 'ratingTable') {
                        var colHeadings = (field.columnHeadings || '').split(',').map(function(s){ return s.trim(); }).filter(Boolean);
                        var rowLabels = (field.rowLabels || '').split(',').map(function(s){ return s.trim(); }).filter(Boolean);
                        var cols = colHeadings.length || 3;
                        var rows = rowLabels.length || 3;
                        var hasRowLabels = rowLabels.length > 0;
                        html += '<table class="table table-bordered table-field"><thead><tr>';
                        if (hasRowLabels) html += '<th style="background:#014653;color:#fff;padding:8px 12px;"></th>';
                        for (var c = 0; c < cols; c++) html += '<th style="background:#014653;color:#fff;padding:8px 12px;">' + (colHeadings[c] || 'Column '+(c+1)) + '</th>';
                        html += '</tr></thead><tbody>';
                        for (var r = 0; r < rows; r++) {
                            html += '<tr>';
                            if (hasRowLabels) html += '<td style="font-weight:600;background:#f0f0f0;">' + (rowLabels[r] || '') + '</td>';
                            for (var c = 0; c < cols; c++) html += '<td style="padding:8px 12px;">&nbsp;</td>';
                            html += '</tr>';
                        }
                        html += '</tbody></table>';
                    } else {
                        var singleField = JSON.stringify([field]);
                        var $temp = $('<div>');
                        $temp.formRender({ dataType: 'json', formData: singleField });
                        html += $temp.html();
                    }
                    html += '</div>';
                });
                $('#form-render-view').html(html);
                $('#form-render-view').find('input, select, textarea').attr('disabled', true);
            },
            error: function(response) {
                toastr.error("Failed to load form data.", "Error", {
                    positionClass: 'toast-bottom-right'
                });
            }
        });
    });

    $('#save-form').click(function (e)
    {
        var formname = $('#form_name').val();

        if(!isNaN(formname))
        {
            toastr.error('Please enter  form Name.', "Error", {
                    positionClass: 'toast-bottom-right'
                });

            return false;
        }
        const formStructure = formBuilder.actions.getData('json');
        e.preventDefault();
        $.ajax({
            url: "{{ route('Performance.config.ProfessionalFormUpdate') }}",
            type: "POST",
            data: {
                FormName: formname,
                form_structure: formStructure,
                Form_id:$("#Form_id").val(),
            },
            success: function (response)
            {

                $("#FormTemplete-modal").modal('hide');
                    toastr.success('Form saved successfully!', "Success",
                    {
                        positionClass: 'toast-bottom-right'
                    });
                ReviewType();


            },
            error: function(response)
            {
                var errors = response.responseJSON;
                var errs = '';
                $.each(errors.errors, function(key, error) {
                    errs += error + '<br>';
                });
                toastr.error(errs, {
                    positionClass: 'toast-bottom-right'
                });
            }
        });
    });
});



        function ReviewType()
        {
            if ($.fn.dataTable.isDataTable('#table-PerformanceTemplateFormList'))
            {
                $('#table-PerformanceTemplateFormList').DataTable().destroy();
            }

            var TableAccomMainten = $('#table-PerformanceTemplateFormList').DataTable({
                "searching": false,
                "bLengthChange": false,
                "bFilter": true,
                "bInfo": true,
                "bAutoWidth": false,
                "scrollX": true,
                "iDisplayLength": 6,
                processing: true,
                serverSide: true,
                order:[[2,'desc']],
                ajax: {
                    url: '{{ route("Performance.config.ProfessionalFormList") }}',
                    type: 'GET',
                    data: function (d) {
                        d.ResortDepartment = $(".ResortDepartment").val();
                        d.type = '{{ $form_type ?? "" }}';
                    }
                },
                columns: [

                    { data: 'FormBuilderName', name: 'FormBuilderName', className: 'text-nowrap' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                    {data:'created_at', visible:false,searchable:false},
                ]
            });
        }

        $(document).on('click', '.delete-row-btn', function (e)
        {
            e.preventDefault();
            var main_id = $(this).data('id');
            Swal.fire({
                    title: 'Sure want to delete?',
                    text: 'This cannot be undone',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No',
                    confirmButtonColor: "#DD6B55"
                }).then((result) =>
                {
                    if (result.isConfirmed)
                    {
                        $.ajax({
                            type: "delete",
                            url: "{{ route('Performance.DestroyProfessionalForm','') }}/"+main_id,
                            dataType: "json",
                        }).done(function(result) {
                            if (result.success == true) {

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
</script>
@endsection
