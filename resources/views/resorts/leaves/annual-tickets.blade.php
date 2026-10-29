@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding page-appHedding">
                <div class="row justify-content-between align-items-center g-md-2 g-1">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Leave</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="btn-group btn-group-sm" role="group" aria-label="Show">
                            <a href="{{ route('leave.annualTickets') }}" class="btn {{ $show === 'unanswered' ? 'btn-primary' : 'btn-outline-primary' }}">Not answered yet</a>
                            <a href="{{ route('leave.annualTickets', ['show' => 'all']) }}" class="btn {{ $show === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">All employees</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <p class="mb-0">Whether the company has provided each employee's round-trip ticket for their current Annual Leave cycle (from their joining anniversary). Answers can be changed until the cycle ends; a new cycle starts unanswered.</p>
                </div>
                <div class="card-body">
                    @if($rows->isEmpty())
                        <p class="mb-0 text-muted">{{ $show === 'unanswered' ? 'Every employee has an answer for their current cycle.' : 'No active employees with a joining date.' }}</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr><th>Employee</th><th>Department</th><th>Current cycle</th><th>Ticket provided by the company?</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($rows as $row)
                                        <tr>
                                            <td>{{ $row->name }} <small class="text-muted">{{ $row->code }}</small></td>
                                            <td>{{ $row->department }}</td>
                                            <td>{{ $row->cycle_start->format('d M Y') }} – {{ $row->cycle_end->format('d M Y') }}</td>
                                            <td>
                                                <span class="annual-ticket d-inline-flex align-items-center gap-2" data-employee-id="{{ $row->id }}">
                                                    <span class="btn-group btn-group-sm" role="group" aria-label="Ticket provided for {{ $row->name }}">
                                                        <button type="button" class="btn btn-outline-success annual-ticket-answer {{ $row->company_provided === true ? 'active' : '' }}" data-value="1" aria-pressed="{{ $row->company_provided === true ? 'true' : 'false' }}">Yes</button>
                                                        <button type="button" class="btn btn-outline-danger annual-ticket-answer {{ $row->company_provided === false ? 'active' : '' }}" data-value="0" aria-pressed="{{ $row->company_provided === false ? 'true' : 'false' }}">No</button>
                                                    </span>
                                                    <small class="text-muted annual-ticket-meta">{{ $row->company_provided === null ? 'Not answered yet.' : '' }}</small>
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-scripts')
    @include('resorts.leaves.partials.annual-ticket-script')
@endsection
