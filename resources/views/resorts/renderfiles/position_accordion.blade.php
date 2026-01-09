{{-- Level 4: Position with Employee and Vacant Tables --}}
<div class="accordion mb-2" id="{{ $accordionId }}">
    <div class="accordion-item border">
        <h2 class="accordion-header" id="heading{{ $accordionId }}">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapse{{ $accordionId }}" aria-expanded="false" 
                    aria-controls="collapse{{ $accordionId }}">
                <i class="fas fa-user-tie me-2"></i>
                <span class="fw-normal">{{ $positionName }}</span>
                <span class="badge badge-dark ms-2 small">Budget: $ 0.00</span>
                <span class="badge badge-info ms-2 small">Filled: {{ $positionData['max_counts']['max_filledcount'] }}</span>
                <span class="badge badge-warning ms-1 small">Vacant: {{ $positionData['max_counts']['max_vacantcount'] }}</span>
            </button>
        </h2>
        <div id="collapse{{ $accordionId }}" class="accordion-collapse collapse" 
             aria-labelledby="heading{{ $accordionId }}">
            <div class="accordion-body p-3">
                
                {{-- Employee Table --}}
                <div class="mb-4">
                    <h6 class="mb-3 fw-semibold border-bottom pb-2">
                        <i class="fas fa-users me-2"></i>Employees
                    </h6>
                        <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-nowrap">Name</th>
                                    <th class="text-nowrap">Rank</th>
                                    <th class="text-nowrap">Nationality</th>
                                    <th class="text-nowrap text-end">Basic Salary</th>
                                    <th class="text-nowrap text-end">Current Salary</th>
                                    @foreach ($header as $h)
                                        <th class="text-nowrap text-end">{{ $h }}</th>
                                    @endforeach
                                    
                                </tr>
                            </thead>
                            <tbody>
                                @if(!empty($positionData['employees']) && count($positionData['employees']) > 0)
                                    @foreach($positionData['employees'] as $employee)
                                        <tr>
                                            <td class="fw-medium">{{ ucwords(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')) }}</td>
                                            <td >
                                                @php 
                                                    $Rank = config('settings.Position_Rank');
                                                    $AvailableRank = !empty($employee->rank) && array_key_exists($employee->rank, $Rank) ? $Rank[$employee->rank] : '';
                                                @endphp
                                                <span class="badge badge-secondary small">{{ $AvailableRank }}</span>
                                            </td>
                                            <td>{{ $employee->nationality ?? '-' }}</td>
                                            <td class="text-center">${{ number_format($employee->basic_salary ?? 0, 2) }}</td>
                                            <td class="text-center">${{ number_format($employee->basic_salary ?? 0, 2) }}</td>
                                            @foreach ($resortCosts as $cost)
                                                <td class="text-center">${{ number_format($cost->amount, 2) }}</td>
                                            @endforeach
                                            
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="{{ count($header) + 6 }}" class="text-center text-muted py-3">
                                            <small><i class="fas fa-info-circle me-1"></i>No employees assigned to this position</small>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                    </table>
                </div>

                {{-- Vacant Table --}}
                <div class="mt-4">
                    <h6 class="mb-3 fw-semibold border-bottom pb-2">
                        <i class="fas fa-user-plus me-2"></i>Vacant Positions
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-nowrap">Position</th>
                                    <th class="text-nowrap text-center">No. of Vacant</th>
                                    <th class="text-nowrap">Rank</th>
                                    <th class="text-nowrap">Nation</th>
                                    @foreach ($header as $h)
                                        <th class="text-nowrap text-end">{{ $h }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @if($positionData['max_counts']['max_vacantcount'] > 0)
                                    @for($i = 0; $i < $positionData['max_counts']['max_vacantcount']; $i++)
                                        <tr>
                                            <td class="fw-medium">{{ $positionName }}</td>
                                            <td class="text-center"><span class="badge badge-warning small">1</span></td>
                                            <td>
                                                @php 
                                                    $Rank = config('settings.Position_Rank');
                                                    $AvailableRank = array_key_exists($positionData['rank'], $Rank) ? $Rank[$positionData['rank']] : '';
                                                @endphp
                                                <span class="badge badge-secondary small">{{ $AvailableRank }}</span>
                                            </td>
                                            <td class="text-muted">-</td>
                                            @foreach ($resortCosts as $cost)
                                                <td class="text-end">${{ number_format($cost->amount, 2) }}</td>
                                            @endforeach
                                        </tr>
                                    @endfor
                                @else
                                    <tr>
                                        <td colspan="{{ count($header) + 5 }}" class="text-center text-muted py-3">
                                            <small><i class="fas fa-check-circle me-1"></i>No vacant positions</small>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

             

            </div>
        </div>
    </div>
</div>

