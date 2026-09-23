<div class="modal fade" id="jdAdvisory-modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Compliance advisory</h5>
                </div>
                <div class="modal-body">
                    <p class="text-muted">The required sections are present, but the items below are missing. Choose how to proceed.</p>
                    <div id="jdAdvisoryPanels"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn ta-btn-primary" id="jdAdvisoryConfirm">Confirm</button>
                </div>
            </div>
        </div>
    </div>

<script>
        // Advisory pop-up: one panel per missing soft item (working hours / place of employment).
        function showJdAdvisory(advisory) {
            const esc = (t) => $('<div>').text(t).html();
            let html = '';
            advisory.items.forEach(function (it) {
                html += '<div class="border rounded p-3 mb-3 jd-adv-panel" data-key="' + it.key + '">' +
                    '<h6>' + esc(it.label) + ' is missing</h6>' +
                    '<p class="small text-muted">' + esc(it.law) + '</p>' +
                    '<textarea class="form-control mb-2" rows="3" readonly>' + esc(it.suggestion) + '</textarea>' +
                    '<div class="btn-group" role="group">' +
                    '<input type="radio" class="btn-check" name="adv_' + it.key + '" id="adv_' + it.key + '_i" value="insert" checked><label class="btn btn-outline-primary btn-sm" for="adv_' + it.key + '_i">Insert as-is</label>' +
                    '<input type="radio" class="btn-check" name="adv_' + it.key + '" id="adv_' + it.key + '_e" value="edit"><label class="btn btn-outline-primary btn-sm" for="adv_' + it.key + '_e">Edit then insert</label>' +
                    '<input type="radio" class="btn-check" name="adv_' + it.key + '" id="adv_' + it.key + '_s" value="skip"><label class="btn btn-outline-secondary btn-sm" for="adv_' + it.key + '_s">Skip</label>' +
                    '</div></div>';
            });
            $('#jdAdvisoryPanels').html(html);
            $('#jdAdvisoryConfirm').data('jd', advisory.jd_id);
            $('#jdAdvisory-modal').modal('show');
        }
        $(document).on('change', '.jd-adv-panel input[type=radio]', function () {
            $(this).closest('.jd-adv-panel').find('textarea').prop('readonly', $(this).val() !== 'edit');
        });
        $('#jdAdvisoryConfirm').on('click', function () {
            const items = {};
            $('.jd-adv-panel').each(function () {
                const $p = $(this);
                items[$p.data('key')] = {
                    action: $p.find('input[type=radio]:checked').val() === 'skip' ? 'skip' : 'insert',
                    text: $p.find('textarea').val()
                };
            });
            const $btn = $(this).prop('disabled', true);
            let url = "{{ route('resort.ta.jobdescription.resolveAdvisory', ':id') }}".replace(':id', $btn.data('jd'));
            $.ajax({ url: url, type: 'POST', dataType: 'json', data: { items: items, _token: "{{ csrf_token() }}" } })
                .done(function () { window.location.href = "{{ route('resort.ta.jobdescription.index') }}"; })
                .fail(function () {
                    $btn.prop('disabled', false);
                    toastr.error('Could not save your decision. Please try again.', 'Error', { positionClass: 'toast-bottom-right' });
                });
        });

</script>
