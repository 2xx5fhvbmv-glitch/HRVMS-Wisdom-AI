@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row g-3 justify-content-between">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Performance</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto d-flex gap-2">
                    @if($archivedView ?? false)
                        <a href="{{ route('Performance.pip.index') }}" class="btn btn-themeGray">
                            <i class="fa-solid fa-arrow-left me-1"></i> Active Plans
                        </a>
                    @else
                        <a href="{{ route('Performance.pip.index', ['archived' => 1]) }}" class="btn btn-themeBlue">
                            <i class="fa-solid fa-box-archive me-1"></i> View Archived
                        </a>
                        <button type="button" class="btn btn-theme" data-bs-toggle="modal" data-bs-target="#addPipModal">
                            <i class="fa-solid fa-plus me-1"></i> Add to PIP
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="row g-md-3 g-2 align-items-center">
                    <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8">
                        <div class="input-group">
                            <input type="search" class="form-control pipSearch" placeholder="Search">
                            <i class="fa-solid fa-search"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-pip" id="pipTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Template</th>
                            <th>Duration</th>
                            <th>Factors</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plans as $plan)
                            @php
                                $emp = $plan->employee;
                                $ra = $emp ? $emp->resortAdmin : null;
                            @endphp
                            <tr>
                                <td><strong>#{{ optional($emp)->Emp_id }}</strong></td>
                                <td>
                                    <div class="tableUser-block">
                                        <div class="img-circle"><img src="{{ \App\Helpers\Common::getResortUserPicture(optional($ra)->id) }}" alt="user"></div>
                                        <span class="userApplicants-btn">{{ optional($ra)->first_name }} {{ optional($ra)->last_name }}</span>
                                    </div>
                                </td>
                                <td>{{ optional($plan->position)->position_title ?? optional($emp->position)->position_title ?? 'N/A' }}</td>
                                <td>{{ optional($plan->template)->FormName ?? '-' }}</td>
                                <td>{{ $plan->duration }}</td>
                                <td><small>{{ \Illuminate\Support\Str::limit($plan->factors, 60) }}</small></td>
                                @php
                                    $startDate = \Carbon\Carbon::flexible($plan->created_at);
                                    $endDate   = null;
                                    if ($plan->duration && preg_match('/(\d+)\s*(day|week|month|year)s?/i', $plan->duration, $m)) {
                                        $qty  = (int) $m[1];
                                        $unit = strtolower($m[2]);
                                        $endDate = match ($unit) {
                                            'day'   => (clone $startDate)->addDays($qty),
                                            'week'  => (clone $startDate)->addWeeks($qty),
                                            'month' => (clone $startDate)->addMonths($qty),
                                            'year'  => (clone $startDate)->addYears($qty),
                                            default => null,
                                        };
                                    }
                                @endphp
                                <td>{{ $startDate->format('d M Y') }}</td>
                                <td>{{ $endDate ? $endDate->format('d M Y') : '-' }}</td>
                                <td>
                                    @if($plan->submitted_at)
                                        <span class="badge badge-themeSuccess">Submitted</span>
                                    @elseif(!$plan->template_id)
                                        <span class="badge badge-themeLight">No Template</span>
                                    @else
                                        <span class="badge badge-themeWarning">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('Performance.pip.view', $plan->id) }}" class="btn-tableIcon btnIcon-blue" title="View / Fill Form"><i class="fa-solid fa-eye"></i></a>
                                    @if($archivedView ?? false)
                                        <a href="javascript:void(0)" class="btn-tableIcon btnIcon-green pip-restore" data-id="{{ $plan->id }}" title="Restore"><i class="fa-solid fa-rotate-left"></i></a>
                                    @else
                                        <a href="javascript:void(0)" class="btn-tableIcon btnIcon-yellow pip-archive" data-id="{{ $plan->id }}" title="Archive"><i class="fa-solid fa-box-archive"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-muted py-4">No employees in PIP</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add to PIP Modal -->
<div class="modal fade" id="addPipModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add to PIP</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addPipForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">SELECT EMPLOYEE <span class="text-danger">*</span></label>
                        <select class="form-select select2pip" name="employee_id" id="pipEmployee" required>
                            <option value="">Select</option>
                            @foreach($employees as $e)
                                <option value="{{ $e->id }}" data-position="{{ $e->Position_id }}">
                                    {{ optional($e->resortAdmin)->first_name }} {{ optional($e->resortAdmin)->last_name }} ({{ $e->Emp_id }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">POSITION</label>
                        <input type="text" class="form-control" id="pipPositionDisplay" placeholder="Select an employee first" readonly>
                        <input type="hidden" name="position_id" id="pipPosition">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">TEMPLATE</label>
                        <select class="form-select select2pip" name="template_id" id="pipTemplate">
                            <option value="">Select Template</option>
                            @foreach($templates as $t)
                                <option value="{{ $t->id }}">{{ $t->FormName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">DURATION <span class="text-danger">*</span></label>
                        <select class="form-select select2pip" name="duration" id="pipDuration" required>
                            <option value="">Select</option>
                            <option value="30 Days">30 Days</option>
                            <option value="60 Days">60 Days</option>
                            <option value="90 Days">90 Days</option>
                            <option value="6 Months">6 Months</option>
                            <option value="1 Year">1 Year</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">FACTORS</label>
                        <textarea class="form-control" name="factors" id="pipFactors" rows="4" placeholder="Type here..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-themeGray" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-themeBlue">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('import-css')
<style>
    .table-pip th { background: #f8f9fa; padding: 12px 16px; font-weight: 600; color: #555; }
    .table-pip td { padding: 14px 16px; vertical-align: middle; }
    .tableUser-block { display: flex; align-items: center; gap: 10px; }
    .tableUser-block .img-circle img { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }
    .form-label { font-weight: 600; font-size: 12px; color: #555; letter-spacing: 0.3px; }
</style>
@endsection

@section('import-scripts')
<script>
    $(document).ready(function() {
        $('.select2pip').select2({
            dropdownParent: $('#addPipModal'),
            width: '100%'
        });

        // Auto-fill position when employee selected
        var pipPositions = @json($positions->pluck('position_title', 'id'));
        $('#pipEmployee').on('change', function() {
            var posId = $(this).find(':selected').data('position');
            if (posId) {
                $('#pipPosition').val(posId);
                $('#pipPositionDisplay').val(pipPositions[posId] || '');
            } else {
                $('#pipPosition').val('');
                $('#pipPositionDisplay').val('');
            }
        });

        // Search filter
        $('.pipSearch').on('keyup', function() {
            var term = $(this).val().toLowerCase();
            $('#pipTable tbody tr').each(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(term) !== -1);
            });
        });

        // Submit form
        $('#addPipForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: '{{ route("Performance.pip.store") }}',
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message, 'Success', { positionClass: 'toast-bottom-right' });
                        $('#addPipModal').modal('hide');
                        setTimeout(function() { location.reload(); }, 800);
                    }
                },
                error: function(xhr) {
                    var errs = 'Failed to submit';
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        errs = '';
                        $.each(xhr.responseJSON.errors, function(k, v) { errs += v[0] + '<br>'; });
                    }
                    toastr.error(errs, 'Error', { positionClass: 'toast-bottom-right' });
                }
            });
        });

        // Delete
        $(document).on('click', '.pip-archive', function() {
            if (!confirm('Archive this PIP plan? It can be restored later.')) return;
            var id = $(this).data('id');
            var $row = $(this).closest('tr');
            $.ajax({
                url: '{{ url("resort/performance/pip") }}/' + id + '/archive',
                type: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(300, function() { $(this).remove(); });
                        toastr.success(response.message, 'Success', { positionClass: 'toast-bottom-right' });
                    }
                }
            });
        });

        $(document).on('click', '.pip-restore', function() {
            var id = $(this).data('id');
            var $row = $(this).closest('tr');
            $.ajax({
                url: '{{ url("resort/performance/pip") }}/' + id + '/restore',
                type: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(300, function() { $(this).remove(); });
                        toastr.success(response.message, 'Success', { positionClass: 'toast-bottom-right' });
                    }
                }
            });
        });
    });
</script>
@endsection
