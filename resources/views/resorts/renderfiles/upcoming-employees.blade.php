@if($upcoming_candidates->count())
    @foreach($upcoming_candidates as $candidate)
        @php
            $employee = $candidate->employee;
            $hasEmployee = !is_null($employee);
            $position = $hasEmployee
                ? ($employee->position->position_title ?? $candidate->position_title)
                : $candidate->position_title;
            $department = $hasEmployee
                ? ($employee->department->name ?? $candidate->department_name)
                : $candidate->department_name;
        @endphp
        <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
            <div class="upArrivalGrid-block">
                <div class="text-center">
                    <div class="img-circle">
                        <img src="{{ Common::getResortUserPicture($hasEmployee ? ($employee->Admin_Parent_id ?? null) : null) }}" alt="image">
                    </div>
                    <h6>{{ $candidate->full_name }}</h6>
                    <p>{{ $position ?? 'N/A' }}</p>
                </div>
                <div class="table-block">
                    <table>
                        <tbody>
                            <tr>
                                <th>Department:</th>
                                <td>{{ $department ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Joining Date:</th>
                                <td>
                                    @if($hasEmployee && $employee->joining_date)
                                        {{ \Carbon\Carbon::parse($employee->joining_date)->format('d M Y') }}
                                    @else
                                        {{ $candidate->Joining_availability ?? '-' }}
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                @if($hasEmployee)
                    <div class="form-check form-check-inline">
                        <input type="hidden" name="Admin_Parent_id" id="Admin_Parent_id{{ $employee->id }}" value="{{ $employee->Admin_Parent_id }}">
                        <input class="form-check-input employee-radio" required type="radio" name="selected_employee" value="{{ $employee->id }}">
                        <label class="form-check-label">Select Employee</label>
                    </div>
                @else
                    {{-- Talent Acquisition applicant who accepted their contract but has
                         no Employee record yet. Selectable: the Employee record is
                         auto-created from the applicant data when the user proceeds.
                         The radio value is prefixed `applicant:` so the Step 1 handler
                         can tell it apart from a real employees.id. --}}
                    <div class="form-check form-check-inline">
                        <input class="form-check-input employee-radio" required type="radio" name="selected_employee" value="applicant:{{ $candidate->applicant_id }}">
                        <label class="form-check-label">Select Candidate</label>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
@else
    <div class="col-12 text-center text-muted">
        No accepted candidates found.
    </div>
@endif
