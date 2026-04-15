@if($employees->count() <= 0)
    <div class="col-12">
        <div class="text-center">No employees found.</div>
    </div>
@else
    @foreach($employees as $employee)
        @php
            $st = $employeeStatus[$employee->id] ?? ['label' => 'Not Started', 'rating' => 0];
            $rating = (int) round($st['rating']);
            $badgeClass = match($st['label']) {
                'Done'        => 'badge-themeSuccess',
                'In Progress' => 'badge-themeWarning',
                default       => 'badge-themeDanger',
            };
        @endphp
        <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
            <div class="empListPeopleEmp-block text-center">
                <div class="img-circle">
                    <img src="{{ Common::getResortUserPicture($employee->Admin_Parent_id ?? null) }}" alt="image">
                </div>
                <h6>{{ $employee->resortAdmin->full_name ?? '' }}</h6>
                <span class="badge badge-themeNew">#{{ $employee->Emp_id }}</span>
                <p>{{ $employee->position->position_title ?? '' }}</p>

                <div class="rating-stars mb-2">
                    @for($i = 1; $i <= 5; $i++)
                        <i class="fa-{{ $i <= $rating ? 'solid' : 'regular' }} fa-star" style="color:#f5a623;"></i>
                    @endfor
                </div>

                <div class="block">
                    <table>
                        <tr>
                            <td>Appraisal Status</td>
                            <td><span class="badge {{ $badgeClass }}">{{ $st['label'] }}</span></td>
                        </tr>
                    </table>
                </div>

                <a href="{{ route('Performance.employees.details', base64_encode($employee->id)) }}"
                   class="btn btn-theme btn-sm w-100 mt-2">View Details</a>
            </div>
        </div>
    @endforeach
@endif
