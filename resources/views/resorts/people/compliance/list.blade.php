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
                    <div class="row  g-3">
                         <div class="col-auto">
                         <div class="page-title">
                              <span>People</span>
                              <h1>{{ $page_title }}</h1>
                         </div>
                         </div>
                         {{-- Action bar: Run rules engine + AI anomaly scan + bulk re-enrich.
                              The two AI buttons are gated behind the same permission as
                              the rules-engine run since they all create/modify rows. --}}
                         <div class="col-auto ms-auto d-flex gap-2 flex-wrap @if(App\Helpers\Common::checkRouteWisePermission('people.compliance.index',config('settings.resort_permissions.create')) == false) d-none @endif">
                              <a class="btn btn-theme" href="{{route('people.compliance.run')}}">Run Compliance Check Now</a>
                              <button type="button" class="btn btn-themeBlue" id="ai-anomaly-scan-btn" title="Run an AI-only anomaly scan that catches issues the rule-based engine can't (salary outliers, ratio drifts, etc.)">
                                  <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Run AI Anomaly Scan
                              </button>
                              <button type="button" class="btn btn-themeGray" id="ai-regenerate-btn" title="Re-run the AI explanation + remediation on the next batch of 15 breached compliance rows. Picks never-enriched rows first, then the oldest enrichments. Click again for the next batch.">
                                  <i class="fa-solid fa-rotate me-1"></i>Regenerate AI
                              </button>
                         </div>
                    </div>
               </div>

            <div>
                <div class="card">
                    <div class="card-header">
                        <div class="row g-md-3 g-2 align-items-center">
                              <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                                   <div class="input-group">
                                        <input type="search" class="form-control search" placeholder="Search" />
                                        <i class="fa-solid fa-search"></i>
                                   </div>
                              </div>

                              <div class="col-auto ms-auto">
                                   <a id="compliance-download-btn" href="{{route('people.compliance.download')}}" class="btn btn-theme me-2">Download</a>
                              </div>
                        </div>
                    </div>
                    <table id="table-exitclearance-form" class="table table-exitclearance-form w-100">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Module Name</th>
                                <th>Compliance Breached</th>
                                <th>Employee Id</th>
                                <th>Employee Name</th>
                                <th>Description</th>
                                <th>Reported On</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    {{-- Shared confirm modal for the two AI action buttons. The JS fills
         in the title + body + confirm-button label/colour and stamps a
         data-action onto #ai-confirm-modal-confirm so a single handler
         can fan out to either runAiRegenerate() or runAiAnomalyScan().
         Sizing matches the existing Dismiss/Revise modals on this page. --}}
    <div class="modal fade" id="ai-confirm-modal" tabindex="-1" aria-labelledby="ai-confirm-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ai-confirm-modal-title">Confirm</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="ai-confirm-modal-body">
                    Are you sure?
                </div>
                <div class="modal-footer justify-content-end">
                    <button type="button" class="btn btn-sm btn-themeGray me-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-themeBlue" id="ai-confirm-modal-confirm">Confirm</button>
                </div>
            </div>
        </div>
    </div>
    @endsection

    @section('import-css')
    <style>
        /* Single visual marker for freshly AI-regenerated rows: a small
           "NEW AI" pill in the first cell. Class `ai-fresh-row` is set
           by the backend on any row whose ai_generated_at is < 30 min
           old (see ComplianceController::list setRowClass) so the pill
           persists across pagination, refresh, and search-induced
           redraws — not just the first render. */
        #table-exitclearance-form tbody tr.ai-fresh-row > td:first-child::before {
            content: "NEW AI";
            display: inline-block;
            background-color: #16a34a;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.4px;
            padding: 2px 7px;
            border-radius: 10px;
            margin-right: 8px;
            vertical-align: middle;
            box-shadow: 0 1px 3px rgba(22, 163, 74, 0.35);
        }
    </style>
    @endsection

    @section('import-scripts')

    <script type="text/javascript">
     $(document).ready(function() {
          updateDownloadUrl();
          $(".select2t-none").select2();
        

           ComplianceIndex();
          $('.search').on('keyup', function() {
               ComplianceIndex();
               updateDownloadUrl();
          });

          // Function to update the download URL with current filters
          function updateDownloadUrl() {
               var searchTerm = $('.search').val();
               var downloadUrl = "{{route('people.compliance.download')}}?searchTerm=" + searchTerm;
               $('.card-header a[href*="people.compliance.download"]').attr('href', downloadUrl);
          }

          $(document).on('click', '.dismmisal', function(e) {
               e.preventDefault();
               var complianceId = $(this).data('id');
               Swal.fire({
                    title: 'Are you sure?',
                    text: "You want to dismiss this compliance?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, dismiss it!'
               }).then((result) => {
                    if (result.isConfirmed) {
                         $.ajax({
                              url: "{{ route('people.compliances.dismiss', '') }}/" + complianceId,
                              type: 'GET',
                              success: function(response) {
                                   Swal.fire(
                                        'Dismissed!',
                                        'Compliance has been dismissed.',
                                        'success'
                                   );
                                  ComplianceIndex();
                              },
                              error: function(xhr, status, error) {
                                   Swal.fire(
                                        'Error!',
                                        'Something went wrong. Please try again.',
                                        'error'
                                   );
                              }
                         });
                    }
               });
          });
     });

     var viewForm;
     function ComplianceIndex()
     {
          if ($.fn.DataTable.isDataTable('#table-exitclearance-form')) {
               viewForm.destroy();
          }
          $('#table-exitclearance-form tbody').empty();
          viewForm = $('#table-exitclearance-form').DataTable({
               searching: false,
               bLengthChange: false,
               bFilter: true,
               bInfo: true,
               bAutoWidth: false,
               scrollX: true,
               iDisplayLength: 10,
               processing: true,
               serverSide: true,
               // NO client-side initial order. The backend's `list()`
               // already orders by `ai_generated_at DESC NULLS LAST,
               // id DESC` so freshly AI-regenerated rows surface at the
               // top after each Regenerate click.
               //
               // The previous `order:[[9, 'desc']]` was sorting by
               // `created_at` desc — that's the row's creation date,
               // which never changes when AI regeneration runs, so the
               // newly-enriched rows stayed wherever they were in the
               // list and HR couldn't see they'd been processed.
               //
               // Columns are still individually sortable when the user
               // clicks a header — this only disables the DEFAULT sort.
               order: [],
               ajax: {
                    url: '{{ route("people.compliance.list") }}',
                    type: 'GET',
                    data: function(d) {
                         var searchTerm = $('.search').val();
                         d.searchTerm = searchTerm;
                    }
               },
          columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'module_name', name: 'module_name', className: 'text-nowrap' },
                    { data: 'compliance_breached_name', name: 'compliance_breached_name' },
                    { data: 'employee_id', name: 'employee_id' },
                    { data: 'employee_name', name: 'employee_name' },
                    { data: 'description', name: 'description' },
                    { data: 'reported_on', name: 'reported_on' },
                    { data: 'status', name: 'status' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                    {data:'created_at',visible:false,searchable:false},
               ],
               // Flash newly AI-regenerated rows green after each batch.
               // The ids come from the Regenerate API response and get
               // stashed in window._aiFreshlyProcessedIds; cleared after
               // 12 s so non-regenerate redraws don't re-trigger the flash.
               drawCallback: function () { applyFreshRowHighlight(); }
          });
     }

    // IDs of compliance rows freshly AI-regenerated in the most recent
    // batch. Set in the AJAX success handler, consumed by the DataTable
    // drawCallback below to flash the rows green for ~10 s after the
    // table reload. Cleared after the flash so a refresh later doesn't
    // re-highlight stale rows.
    window._aiFreshlyProcessedIds = window._aiFreshlyProcessedIds || [];

    // Apply / clear the "just-regenerated" CSS class on rows by id.
    // Called from the DataTable drawCallback so it fires every time the
    // table re-renders, including the post-regenerate reload.
    function applyFreshRowHighlight() {
        if (!window._aiFreshlyProcessedIds.length) return;
        var ids = window._aiFreshlyProcessedIds;
        var hit = 0;
        ids.forEach(function (id) {
            var $row = $('#table-exitclearance-form tbody tr[data-compliance-id="' + id + '"]');
            if ($row.length) {
                $row.addClass('ai-fresh-row');
                hit++;
            }
        });
        // If the freshly-processed rows aren't visible on the current
        // DataTables page, the highlight won't appear — but the toast
        // already told the user "X rows regenerated", so it's not lost.
        // The next reload will still find them if they paginate back.
    }

    // ─── AI action buttons ──────────────────────────────────────────────
    // ONE batch of 15 rows per click. Picks never-enriched rows first,
    // then the oldest enrichments (rotation), so the same row never
    // gets re-processed until everything else has had its turn. Each
    // click is a single round-trip (~15-25s) — no recursive auto-paging.
    function runAiRegenerate() {
        var $b   = $('#ai-regenerate-btn');
        var $tbl = $('#table-exitclearance-form').DataTable
            ? $('#table-exitclearance-form').DataTable()
            : null;
        var orig = $b.html();
        $b.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Regenerating 15…');

        $.ajax({
            url: '{{ route("people.compliance.regenerateAi") }}',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}', limit: 15 },
            success: function (resp) {
                if (!resp || !resp.success) {
                    toastr.error((resp && resp.message) || 'AI regeneration failed.', 'Error', { positionClass: 'toast-bottom-right' });
                    return;
                }
                // Store the freshly-processed ids BEFORE reloading. The
                // drawCallback runs synchronously after ajax.reload's
                // success, so the ids will be available when it fires.
                window._aiFreshlyProcessedIds = Array.isArray(resp.processed_ids) ? resp.processed_ids.slice() : [];

                if ($tbl) {
                    // Reset to the first page so the freshly regenerated
                    // rows (now ordered ai_generated_at DESC server-side)
                    // are actually visible — otherwise HR who was on
                    // page 3 wouldn't see them.
                    $tbl.page(0);
                    $tbl.ajax.reload(null, false);
                }

                var enriched = resp.enriched      || 0;
                var failed   = resp.failed        || 0;
                var remain   = resp.remaining_eligible;

                var summary = 'Regenerated AI for ' + enriched + ' row' + (enriched === 1 ? '' : 's');
                if (failed)            summary += ' (' + failed + ' failed — see logs)';
                if (typeof remain === 'number' && remain > 0) {
                    summary += '. ' + remain + ' more row' + (remain === 1 ? '' : 's') + ' eligible — click Regenerate again for the next batch.';
                } else if (typeof remain === 'number' && remain === 0) {
                    summary += '. All breached rows have fresh AI insights.';
                }
                toastr.success(summary, 'AI regeneration complete', { positionClass: 'toast-bottom-right', timeOut: 8000 });

                // Clear the highlight list after 12 s so the green flash
                // doesn't get re-applied on the NEXT table reload (which
                // can be triggered by anything — search, filter change,
                // pagination). The CSS animation itself fades after ~10 s.
                setTimeout(function () { window._aiFreshlyProcessedIds = []; }, 12000);
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'AI regeneration failed.';
                toastr.error(msg, 'Error', { positionClass: 'toast-bottom-right' });
            },
            complete: function () { $b.prop('disabled', false).html(orig); }
        });
    }

    // Run the AI-only anomaly scan (called after modal confirmation).
    // Mirrors the Regenerate flow: the API returns processed_ids of the
    // newly-filed anomaly rows; we reset to page 1, reload the table,
    // and the rows show up at the top with the NEW AI pill (server-side
    // ai_generated_at = NOW() puts them inside the 30-min freshness
    // window automatically).
    function runAiAnomalyScan() {
        var $b = $('#ai-anomaly-scan-btn');
        var orig = $b.html();
        $b.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Scanning… (~60s)');
        $.ajax({
            url: '{{ route("people.compliance.anomalyScan") }}',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function (resp) {
                if (resp && resp.success) {
                    toastr.success(resp.message, 'AI scan complete', { positionClass: 'toast-bottom-right', timeOut: 8000 });
                    // Stash IDs for the optional JS-side immediate flash
                    // (server-side setRowClass keeps the NEW AI pill on
                    // for 30 min regardless, but this gives instant
                    // feedback before the AJAX reload completes).
                    window._aiFreshlyProcessedIds = Array.isArray(resp.processed_ids) ? resp.processed_ids.slice() : [];
                    var $tbl = $('#table-exitclearance-form').DataTable();
                    if ($tbl) {
                        // Jump to page 1 so the new rows (ordered
                        // ai_generated_at DESC server-side) are visible.
                        $tbl.page(0);
                        $tbl.ajax.reload(null, false);
                    }
                    // Clear the JS-side ID list after 12 s — the server-side
                    // setRowClass takes over with the 30-min window.
                    setTimeout(function () { window._aiFreshlyProcessedIds = []; }, 12000);
                } else {
                    toastr.error((resp && resp.message) || 'AI scan failed.', 'Error', { positionClass: 'toast-bottom-right' });
                }
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'AI scan failed.';
                toastr.error(msg, 'Error', { positionClass: 'toast-bottom-right' });
            },
            complete: function () { $b.prop('disabled', false).html(orig); }
        });
    }

    // Open the shared confirm modal with the right title/body and stamp
    // the action onto the Confirm button. The modal stays inside the
    // page (markup below the table) so styling matches the existing
    // Revise-Budget / Dismiss modals.
    $(document).on('click', '#ai-regenerate-btn', function () {
        $('#ai-confirm-modal-title').text('Regenerate AI insights?');
        $('#ai-confirm-modal-body').html(
            'This will re-run the AI explanation and remediation on the <strong>next batch of 15 compliance rows</strong>.<br><br>' +
            'Rows that have never been AI-enriched are picked first, then the oldest AI-enriched rows. ' +
            'Click again to process the next batch — there is no auto-paging.<br><br>' +
            'Existing AI text on the processed rows is overwritten. The original deterministic description (and severity) is unaffected. ' +
            'Typical run time: 15–25 seconds.'
        );
        $('#ai-confirm-modal-confirm').text('Yes, regenerate next 15')
            .removeClass('btn-themeBlue').addClass('btn-themeGray')
            .data('action', 'regenerate');
        var modal = new bootstrap.Modal(document.getElementById('ai-confirm-modal'));
        modal.show();
    });

    $(document).on('click', '#ai-anomaly-scan-btn', function () {
        $('#ai-confirm-modal-title').text('Run AI anomaly scan?');
        $('#ai-confirm-modal-body').html(
            'This sends a resort-level snapshot (per-position salary distribution, per-department headcount + expat ratio, probation cohort) to the AI service ' +
            'and files each employee-specific anomaly as a new compliance row under <strong>"AI Anomaly Detection"</strong>.<br><br>' +
            'New rows appear at the <strong>top of the list with the green NEW AI pill</strong> (same as Regenerate). ' +
            'Resort-level findings (department-wide patterns) are logged separately but not filed as rows — they have no specific employee to display.<br><br>' +
            'Typical run time: <strong>up to 60 seconds</strong>. Up to 20 anomalies per scan. Existing rows are NOT modified.'
        );
        $('#ai-confirm-modal-confirm').text('Yes, run scan')
            .removeClass('btn-themeGray').addClass('btn-themeBlue')
            .data('action', 'scan');
        var modal = new bootstrap.Modal(document.getElementById('ai-confirm-modal'));
        modal.show();
    });

    // Fan out the modal's Confirm button to the right function based on
    // the data-action stamped during open. Modal closes first so the
    // page returns focus to the triggering button before the AJAX fires.
    $(document).on('click', '#ai-confirm-modal-confirm', function () {
        var action = $(this).data('action');
        var modalEl = document.getElementById('ai-confirm-modal');
        var modal   = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
        if (action === 'regenerate')   runAiRegenerate();
        else if (action === 'scan')    runAiAnomalyScan();
    });
    </script>
    @endsection
