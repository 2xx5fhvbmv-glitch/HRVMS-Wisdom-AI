@if($upcoming_employees->count())
    @foreach($upcoming_employees as $employee)
        <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
            <div class="upArrivalGrid-block">
                <div class="text-center">
                    <div class="img-circle">
                        <img src="{{Common::getResortUserPicture($employee->Admin_Parent_id ?? null)}}" alt="image">
                    </div>
                    <h6>{{ $employee->resortAdmin->full_name }}</h6>
                    <p>{{ $employee->position->position_title }}</p>
                </div>
                <div class="table-block">
                    <table>
                        <tbody>
                            <tr>
                                <th>Department:</th>
                                <td>{{ $employee->department->name }}</td>
                            </tr>
                            <tr>
                                <th>Joining Date:</th>
                                <td>{{ \Carbon\Carbon::parse($employee->joining_date)->format('d M Y') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="form-check form-check-inline">
                    <input type="hidden" name="Admin_Parent_id" id="Admin_Parent_id{{ $employee->id }}" value="{{ $employee->Admin_Parent_id }}">
                    <input class="form-check-input employee-radio" required type="radio" name="selected_employee" value="{{ $employee->id }}">
                    <label class="form-check-label" for="inlineCheckbox{{ $employee->id }}">Select Employee</label>
                </div>
            </div>
        </div>
    @endforeach
@else
    <div class="col-12 text-center text-muted">
        No upcoming employees found.
    </div>
@endif
