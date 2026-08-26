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
                    <button class="btn eb-btn-ghost dropdown-toggle dotsV-link" type="button"
                        id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-ellipsis-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton1">
                        <li><a class="dropdown-item" href="{{route('people.employees.details', base64_encode($employee->id))}}">View Profile</a></li>
                        {{-- Delete intentionally hidden — destructive employee removal
                             must go through Resignation / Exit Clearance, not a card
                             dropdown. Uncomment if a hard-delete entry point is needed. --}}
                        {{-- <li><a class="dropdown-item delete-employee" href="#" data-emp-id="{{$employee->id}}">Delete Employee</a></li> --}}
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
                        <tr><td>Department:</td><td>{{ $employee->department->name ?? '-' }}</td></tr>
                        <tr><td>Status:</td><td>
                            @if($employee->status == 'Active')
                                <span class="badge badge-themeSuccess">{{ $employee->status }}</span>
                            @else
                                <span class="badge badge-themeDanger">{{ $employee->status }}</span>
                            @endif
                        </td></tr>
                        <tr><td>Employment Type:</td><td>{{$employee->employment_type}}</td></tr>
                        {{-- Email + phone surfaced on the card so HR can dial / mail
                             without clicking into the profile. Email truncates when
                             long; full address sits in the title attribute on hover. --}}
                        <tr>
                            <td>Mobile:</td>
                            <td>
                                @php $_mob = $employee->resortAdmin->personal_phone ?? null; @endphp
                                @if($_mob)
                                    <a href="tel:{{ $_mob }}" class="text-decoration-none">{{ $_mob }}</a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Email:</td>
                            <td>
                                @php $_em = $employee->resortAdmin->email ?? null; @endphp
                                @if($_em)
                                    <a href="mailto:{{ $_em }}"
                                       class="text-decoration-none d-inline-block text-truncate"
                                       style="max-width: 160px; vertical-align: bottom;"
                                       title="{{ $_em }}">{{ $_em }}</a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
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
@include('resorts._emotional_buttons_v2_styles')
