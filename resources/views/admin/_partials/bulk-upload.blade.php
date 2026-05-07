{{--
    Bulk-upload UI partial used by Divisions / Departments / Sections /
    Positions index pages. Renders the two action buttons and a modal that
    handles the AJAX upload + server response (created / skipped / errors).

    Required vars:
        $module       — 'divisions' | 'department' | 'sections' | 'positions'
        $sampleRoute  — route name for the sample-sheet GET
        $importRoute  — route name for the import POST
        $entityLabel  — display label, e.g. 'Division', 'Department'
--}}
<a href="{{ route($sampleRoute) }}" class="btn btn-success float-right mr-2" data-no-loader>
    <i class="fas fa-download"></i> Download Sample
</a>
<a href="javascript:void(0)" class="btn btn-info float-right mr-2" data-toggle="modal" data-target="#bulkUploadModal-{{ $module }}">
    <i class="fas fa-upload"></i> Upload Sheet
</a>

<div class="modal fade" id="bulkUploadModal-{{ $module }}" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Upload {{ $entityLabel }}s</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="bulkUploadForm-{{ $module }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <p class="text-muted">
                        Use the <strong>Download Sample</strong> button to grab the template.
                        Fill in the rows, then upload the .xlsx file here.
                        Existing rows with the same name are skipped.
                    </p>
                    <div class="form-group">
                        <label>Excel file (.xlsx, .xls, .csv)</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                    </div>
                    <div id="bulkUploadResult-{{ $module }}" class="d-none">
                        <div class="alert" role="alert"></div>
                        <ul class="text-danger small mb-0"></ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var formId   = '#bulkUploadForm-{{ $module }}';
    var resultId = '#bulkUploadResult-{{ $module }}';
    var importUrl = "{{ route($importRoute) }}";
    $(document).on('submit', formId, function (e) {
        e.preventDefault();
        var $form = $(this);
        var $result = $(resultId);
        $result.addClass('d-none').find('.alert').removeClass('alert-success alert-danger').empty();
        $result.find('ul').empty();
        $.ajax({
            url: importUrl,
            method: 'POST',
            data: new FormData($form[0]),
            processData: false,
            contentType: false,
            success: function (resp) {
                $result.removeClass('d-none')
                    .find('.alert').addClass('alert-success').text(resp.msg || 'Upload complete');
                if (resp.errors && resp.errors.length) {
                    var $ul = $result.find('ul');
                    resp.errors.forEach(function (err) { $ul.append($('<li>').text(err)); });
                }
                if (typeof toastr !== 'undefined') toastr.success(resp.msg || 'Upload complete');
                if (typeof datatable !== 'undefined') {
                    try { datatable.ajax.reload(null, false); } catch (_) {}
                }
                $form[0].reset();
                setTimeout(function () { window.location.reload(); }, 1200);
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.msg)) || 'Upload failed';
                $result.removeClass('d-none')
                    .find('.alert').addClass('alert-danger').text(msg);
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    var $ul = $result.find('ul');
                    Object.values(xhr.responseJSON.errors).flat().forEach(function (err) {
                        $ul.append($('<li>').text(err));
                    });
                }
            }
        });
    });
})();
</script>
