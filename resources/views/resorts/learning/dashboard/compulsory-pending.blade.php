@extends('resorts.layouts.app')
@section('page_tab_title' , $page_title)

@section('content')
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding">
                <div class="row justify-content-between g-3 align-items-center">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Learning & Development</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('learning.manager.dashboard') }}" class="btn btn-themeBlue btn-sm">Back to Dashboard</a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-title mb-md-3">
                    <p class="mb-0 small text-muted">Probationers with pending or overdue compulsory programs. Click <strong>Schedule</strong> to add a session.</p>
                </div>
                <div class="table-responsive">
                    <table class="table table-LearningProgram w-100 mb-0">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Position</th>
                                <th>Program</th>
                                <th>Due By</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($paginator as $row)
                                <tr>
                                    <td>{{ $row->employee_name ?: '—' }}</td>
                                    <td>{{ $row->department ?: '—' }}</td>
                                    <td>{{ $row->position ?: '—' }}</td>
                                    <td>{{ $row->program_name }}</td>
                                    <td>{{ $row->due_on ? $row->due_on->format('d M Y') : '—' }}</td>
                                    <td>
                                        @if($row->is_overdue)
                                            <span class="badge badge-danger">Overdue</span>
                                        @else
                                            <span class="badge badge-warning">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('learning.schedule') }}?program_id={{ $row->program_id }}&employee_id={{ $row->employee_id }}"
                                           class="btn btn-themeBlue btn-sm">Schedule</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No pending compulsory programs.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($paginator->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-3 px-2">
                        <small class="text-muted">
                            Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }} entries
                        </small>
                        <div>
                            {{ $paginator->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
