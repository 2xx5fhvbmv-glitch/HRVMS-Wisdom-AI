@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Payroll</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <a href="{{ route('payroll.dashboard') }}" class="btn payroll-btn-secondary btn-sm">Back to Dashboard</a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Period</th>
                            <th>Employees</th>
                            <th>Total Amount</th>
                            <th>Created</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($drafts as $index => $draft)
                            <tr>
                                <td>{{ $drafts->firstItem() + $index }}</td>
                                <td>{{ \Carbon\Carbon::parse($draft->start_date)->format('d M Y') }} - {{ \Carbon\Carbon::parse($draft->end_date)->format('d M Y') }}</td>
                                <td>{{ $draft->employee_count }}</td>
                                <td>{!! Common::formatCurrency($draft->total_payroll ?? 0, 'USD') !!}</td>
                                <td>{{ \Carbon\Carbon::flexible($draft->created_at)->format('d M Y, h:i A') }}</td>
                                <td><span class="badge badge-themeWarning">{{ ucfirst($draft->status) }}</span></td>
                                <td>
                                    <a href="{{ route('payroll.run') }}?resume={{ $draft->id }}" class="btn btn-sm payroll-btn-secondary"
                                       onclick="localStorage.setItem('payroll_id','{{ $draft->id }}');localStorage.setItem('currentStep','7');">
                                        <i class="fa-solid fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No draft payrolls found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($drafts->hasPages())
                <div class="d-flex justify-content-center py-3">
                    {{ $drafts->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('import-css')
@include('resorts.payroll._payroll_buttons_v2_styles')
@endsection
