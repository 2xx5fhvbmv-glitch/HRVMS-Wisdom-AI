@if($employees->count() <= 0)
    <div class="col-12">
        <div class=" text-center">
            No employees found.
        </div>
    </div>
@else

   @foreach($employees as $employee)
        <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
            <div class="empListPeopleEmp-block">
                <div class="dropdown table-dropdown ">
                    <button class="btn btn-secondary dropdown-toggle dotsV-link" type="button"
                        id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-ellipsis-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton1">
                        <li><a class="dropdown-item" href="{{route('people.employees.details', base64_encode($employee->id))}}">View Profile</a></li>
                        <li><a class="dropdown-item delete-employee" href="#" data-emp-id="{{$employee->id}}">Delete Employee</a></li>
                        <li>
                            <a class="dropdown-item add-to-team-btn" href="#" data-emp-id="{{ $employee->id }}">
                                Add to Team / Assign Role
                            </a>
                        </li>
                        <li><a class="dropdown-item change-status" href="#" 
                            data-id="{{ $employee->id }}" 
                            data-status="{{ $employee->status }}">
                            Change Status
                        </a></li>
                    </ul>
                </div>
                <div class="img-circle">
                    <img src="{{Common::getResortUserPicture($employee->Admin_Parent_id ?? null)}}" alt="image">
                </div>
                <h6>{{ $employee->resortAdmin->full_name }}</h6>
                <span class="badge badge-themeNew">#{{ $employee->Emp_id }}</span>
                <p>{{ $employee->position->position_title }}</p>
                <div class="block">
                    <table>
                        <tr><td>Department:</td><td>{{ $employee->department->name }}</td></tr>
                        <tr><td>Status</td><td>
                            @if($employee->status == 'Active')
                                <span class="badge badge-themeSuccess">{{ $employee->status }}</span>
                            @else
                                <span class="badge badge-themeDanger">{{ $employee->status }}</span>
                            @endif
                        </td></tr>
                        <tr><td>Employment Type</td><td>{{$employee->employment_type}}</td></tr>
                    </table>
                </div>
                <div class="form-check">
                    <input class="form-check-input employee-checkbox" type="checkbox" id="employeeCheckbox_{{ $employee->id }}" value="{{ $employee->id }}">
                    <label class="form-check-label" for="employeeCheckbox_{{ $employee->id }}">Select Employee</label>
                </div>
            </div>
        </div>
    @endforeach
@endif