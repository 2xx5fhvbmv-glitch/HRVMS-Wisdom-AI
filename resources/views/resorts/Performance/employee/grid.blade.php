@if($employees->count() <= 0)
    <div class="col-12">
        <div class="text-center">No employees found.</div>
    </div>
@else
    @foreach($employees as $employee)
        @php
            $st = $employeeStatus[$employee->id] ?? ['label' => 'Not Started', 'rating' => 0, 'has_rating' => false];
            $rating = (int) round($st['rating'] ?? 0);
            $hasRating = $st['has_rating'] ?? ($rating > 0);
            $badgeClass = match($st['label']) {
                'Done'        => 'badge-themeSuccess',
                'In Progress' => 'badge-themeWarning',
                default       => 'badge-themeDanger',
            };
        @endphp
        <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
            <div class="empListPeopleEmp-block text-center">
                <div class="img-circle">
                    <img src="{{ Common::getResortUserPicture($employee->Admin_Parent_id ?? null) }}" alt="image"
                         onerror="this.onerror=null;this.src='{{ url(config('settings.default_picture')) }}';">
                </div>
                <h6>{{ $employee->resortAdmin->full_name ?? '' }}</h6>
                <span class="badge badge-themeNew">#{{ $employee->Emp_id }}</span>
                <p>{{ $employee->position->position_title ?? '' }}</p>

                <div class="rating-stars mb-2">
                    @if($hasRating)
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fa-{{ $i <= $rating ? 'solid' : 'regular' }} fa-star" style="color:#f5a623;"></i>
                        @endfor
                        <span class="small text-muted ms-1">{{ number_format($st['rating'] ?? 0, 1) }}/5</span>
                    @else
                        <span class="small text-muted">No rating yet</span>
                    @endif
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
