
@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
@endif

@section('content')
<style>
    /* Same requested push as the other module dashboards (Payroll / Talent
       Acquisition / People / Time and Attendance / Leave / Performance /
       Learning / Accommodation / Incident / Survey) — extra breathing room
       between the hero and the content card below it, scoped to this page
       (.page-hedding's own margin-bottom is shared by every page's hero).
       padding-bottom, not margin: adjacent sibling margins collapse to the
       larger of the two rather than summing. Below Bootstrap's sm
       breakpoint the extra padding pushes the card into the teal hero
       curve's rounded bottom-left corner (body::before, border-radius 0 0
       50px 50px) — same collision found on Payroll — neutralized below
       576px. */
    #reports-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #reports-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="reports-hero">
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
                        <div class="dd d-inline-block me-1 align-middle" id="predefinedReportsDd" style="width:230px">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">Predefined Reports</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Predefined Reports">
                                <div class="dd-search">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg>
                                    <input type="text" placeholder="Find a report…">
                                </div>
                                <div class="dd-scroll">
                                    <a class="dd-item" role="option" href="{{ route('resort.report.wfp.index') }}"><span class="dd-nm">Workforce Planning Reports</span></a>
                                    <a class="dd-item" role="option" href="{{ route('resort.report.payroll.index') }}"><span class="dd-nm">Payroll Reports</span></a>
                                    <a class="dd-item" role="option" href="{{ route('resort.report.visa.index') }}"><span class="dd-nm">Visa / Immigration Reports</span></a>
                                    <a class="dd-item" role="option" href="{{ route('resort.report.filemgmt.index') }}"><span class="dd-nm">File Management Reports</span></a>
                                    <a class="dd-item" role="option" href="{{ route('resort.report.survey.index') }}"><span class="dd-nm">Survey Reports</span></a>
                                    <a class="dd-item" role="option" href="{{ route('resort.report.learning.index') }}"><span class="dd-nm">Learning & Development Reports</span></a>
                                    <a class="dd-item" role="option" href="{{ route('resort.report.grievance.index') }}"><span class="dd-nm">Grievance & Disciplinary Reports</span></a>
                                    <a class="dd-item" role="option" href="{{ route('resort.report.incident.index') }}"><span class="dd-nm">Incident Management Reports</span></a>
                                    <a class="dd-item" role="option" href="{{ route('resort.report.accommodation.index') }}"><span class="dd-nm">Accommodation Reports</span></a>
                                    <a class="dd-item" role="option" href="{{ route('resort.report.sos.index') }}"><span class="dd-nm">SOS / Emergency Reports</span></a>
                                    <a class="dd-item" role="option" href="{{ route('resort.report.compliance.index') }}"><span class="dd-nm">Compliance Reports</span></a>
                                    <a class="dd-item" role="option" href="{{ route('resort.report.employee.index') }}"><span class="dd-nm">Employee Master Reports</span></a>
                                    <a class="dd-item" role="option" href="{{ route('resort.report.onboarding.index') }}"><span class="dd-nm">Onboarding Reports</span></a>
                                    <a class="dd-item" role="option" href="{{ route('resort.report.announcement.index') }}"><span class="dd-nm">Announcement Reports</span></a>
                                    <a class="dd-item" role="option" href="{{ route('resort.report.probation.index') }}"><span class="dd-nm">Probation Reports</span></a>
                                    <a class="dd-item" role="option" href="{{ route('resort.report.promotion.index') }}"><span class="dd-nm">Promotion Reports</span></a>
                                    <a class="dd-item" role="option" href="{{ route('resort.report.transfer.index') }}"><span class="dd-nm">Transfer Reports</span></a>
                                    <a class="dd-item" role="option" href="{{ route('resort.report.salary_increment.index') }}"><span class="dd-nm">Salary Increment Reports</span></a>
                                    <a class="dd-item" role="option" href="{{ route('resort.report.salary_advance_loan.index') }}"><span class="dd-nm">Salary Advance & Loan Reports</span></a>
                                    <a class="dd-item" role="option" href="{{ route('resort.report.resignation_exit.index') }}"><span class="dd-nm">Resignation & Exit Clearance Reports</span></a>
                                </div>
                            </div>
                        </div>
                        <a href="{{ route('reports.create') }}" class="btn eb-btn-accent @if(Common::checkRouteWisePermission('resort.report.index',config('settings.resort_permissions.create')) == false) d-none @endif">Custom Report</a>
                    </div>
                </div>  
            </div>  
            <div class="card-body">
                <div class="table">
                        @if(session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif
                        
                      
                                <table class="table" id="ReportList">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Created At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                      
                                    </tbody>
                                </table>
                </div>
            </div>
        </div>
    </div>
</div>
@include('resorts._emotional_buttons_v2_styles')
@include('resorts._dropdown_styles')
@include('resorts._dropdown_script')
@endsection

@section('import-css')
<style>
    /* The action column's View/Delete buttons come back from the
       controller as full Bootstrap .btn.btn-sm buttons (.eb-btn-secondary /
       .eb-btn-critical — solid --critical-bg fill, lift+shadow on hover),
       a different component from the row-action icon button every other
       table in the app uses: .btn-tableIcon (25x25, 7px radius) + a
       .btnIcon-teal / .eb-icon-critical color modifier — light tint at
       rest, solid fill only on hover, no lift. Same pattern already on the
       Action config page and SOS Team Management (both render literal
       .btn-tableIcon.btnIcon-teal / .eb-icon-critical from their own
       controllers). Can't change the backend-returned classes here, so
       reproduce that exact box + color pair via override instead of
       leaving Reports as a one-off third button style. */
    #ReportList td .btn.eb-btn-secondary,
    #ReportList td .btn.eb-btn-critical {
        width: 25px;
        height: 25px;
        padding: 0 !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 7px !important;
        border: none !important;
        transform: none !important;
        box-shadow: none !important;
    }
    #ReportList td .btn.eb-btn-secondary {
        color: var(--teal) !important;
        background: rgb(0 69 82 / 9%) !important;
    }
    #ReportList td .btn.eb-btn-secondary:hover {
        color: #fff !important;
        background: var(--teal) !important;
    }
    #ReportList td .btn.eb-btn-critical {
        color: var(--critical) !important;
        background: rgba(255, 36, 0, .09) !important;
    }
    #ReportList td .btn.eb-btn-critical:hover {
        color: #fff !important;
        background: var(--critical) !important;
    }
</style>
@endsection

@section('import-scripts')
<script>
    $(document).ready(function(){
        loadSupportlist();
    });

    function loadSupportlist() {
        if ($.fn.DataTable.isDataTable('#ReportList')) {
            $('#ReportList').DataTable().destroy();
        }

        var table = $('#ReportList').DataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": true,
            "bAutoWidth": false,
            "scrollX": true,
            "iDisplayLength": 10,
            processing: true,
            serverSide: true,
            ordering: true,
            order: [[2, 'desc']], // Order by Created At column (index 2) in descending order
            ajax: {
                url: "{{ route('resort.report.index')}}",
                type: 'GET',
            },
            columns: [
                { data: 'name', name: 'name', orderable: true },
                { data: 'description', name: 'description', orderable: false, searchable: false },
                { data: 'CareatedAt', name: 'CareatedAt', orderable: true, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false }

            ],
        });
    }

    $(document).on('click', '.report-delete-btn', function() {
        const id = $(this).data('id');
        wisdomConfirm({
            role: 'destructive',
            title: 'Delete this report?',
            text: 'This cannot be undone.',
            confirmText: 'Yes, delete it'
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: "{{ url('resort/report') }}/" + id,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(res) {
                    if (res.success) {
                        toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                        if ($.fn.DataTable.isDataTable('#ReportList')) {
                            $('#ReportList').DataTable().ajax.reload();
                        } else {
                            location.reload();
                        }
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Failed to delete report', 'Error', { positionClass: 'toast-bottom-right' });
                }
            });
        });
    });
</script>
@endsection