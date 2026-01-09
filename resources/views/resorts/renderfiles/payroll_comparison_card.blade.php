@if(!empty($payrollData))
    @php
        $months = array_keys($payrollData);
        $currentMonth = $months[0] ?? null;
        $previousMonth = $months[1] ?? null;

    @endphp

    
        <div class="card card-salaryCalc h-auto" id="card-salaryCalc">
            <div class="card-title">
                <div class="row justify-content-between align-items-center g-md-3 g-1">
                    <div class="col">
                        <h3 class="text-nowrap">Payroll Comparison</h3>
                    </div>
                    <div class="col-auto">
                        <div class="form-group">
                            <select class="form-select" id="monthSelector">
                                @for ($i = 1; $i <= 12; $i++)
                                    @php
                                        $monthNum = str_pad($i, 2, '0', STR_PAD_LEFT);
                                        $monthName = date("F", mktime(0, 0, 0, $i, 1));
                                    @endphp
                                    <option value="{{ $monthNum }}" @if($monthNum == now()->format('m')) selected @endif>
                                        {{ $monthName }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                {{-- Previous Year - Same Month --}}
                @if($previousMonth)
                    <div class="col-sm-6 border-right">
                        <div class="leaveUser-bgBlock">
                            <h6>{{ $previousMonth }}</h6>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col">
                                <div class="four-progressbar">
                                    @foreach(['basicSalary' => 'blue', 'serviceCharge' => 'skyblue', 'normalOT' => 'blue', 'holidayOT' => 'skyblue'] as $key => $color)
                                        

                                        <div class="progress-container {{ $color }}" data-progress="{{ $payrollData[$previousMonth][$key]['percentage'] }}">
                                            <svg class="progress-circle" viewBox="0 0 120 120">
                                                <circle class="progress-background" cx="60" cy="60" r="54"></circle>
                                                <circle class="progress" cx="60" cy="60" r="54"></circle>
                                            </svg>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-auto">
                                <ul class="list-unstyled d-flex flex-wrap gap-3 mb-0">
                                    <li class="d-flex align-items-center">
                                        <div class="doughnut-label">
                                            <span style="background-color: #014653"></span>Basic salary
                                        </div>
                                    </li>
                                    <li class="d-flex align-items-center">
                                        <div class="doughnut-label">
                                            <span style="background-color: #53CAFF"></span>Service charge
                                        </div>
                                    </li>
                                    <li class="d-flex align-items-center">
                                        <div class="doughnut-label">
                                            <span style="background-color: #014653"></span>Normal OT
                                        </div>
                                    </li>
                                    <li class="d-flex align-items-center">
                                        <div class="doughnut-label">
                                            <span style="background-color: #53CAFF"></span>Holiday OT
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        @php
                            $previousTotal = $payrollData[$previousMonth]['total'] ?? 0;
                            $currentTotal = $payrollData[$currentMonth]['total'] ?? 0;
                            $reverseChange = 0;

                            if ($previousTotal > 0 && $currentTotal > 0) {
                                $reverseChange = round((($previousTotal - $currentTotal) / $currentTotal) * 100);
                            }
                        @endphp

                        <p class="text-center">
                            <span class="chartImg-block">
                                @if($previousTotal === 0)
                                    <img src="{{ asset('resorts_assets/images/chartLine-danger.svg') }}" alt="icon" class="me-2">
                                @elseif($previousTotal > $currentTotal)
                                    <img src="{{ asset('resorts_assets/images/chartLine-danger.svg') }}" alt="icon" class="me-2">
                                @else
                                    <img src="{{ asset('resorts_assets/images/chartLine-success.svg') }}" alt="icon" class="me-2">
                                @endif
                            </span>
                            @if($previousTotal === 0)
                                <span class="text-danger">N/A</span> Compared to {{ $currentMonth }}
                            @else
                                <span class="{{ $reverseChange >= 0 ? 'text-danger' : 'text-successTheme' }}">
                                    {{ $reverseChange >= 0 ? '-' : '+' }}{{ abs($reverseChange) }}%
                                </span> Compared to {{ $currentMonth }}
                            @endif
                        </p>
                    </div>
                @endif

                {{-- Current Year - Selected Month --}}
                @if($currentMonth)
                    <div class="col-sm-6">
                        <div class="leaveUser-bgBlock">
                            <h6>{{ $currentMonth }}</h6>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col">
                                <div class="four-progressbar">
                                    @foreach(['basicSalary' => 'blue', 'serviceCharge' => 'skyblue', 'normalOT' => 'blue', 'holidayOT' => 'skyblue'] as $key => $color)
                                        <div class="progress-container {{ $color }}" data-progress="{{ $payrollData[$currentMonth][$key]['percentage'] }}">
                                            <svg class="progress-circle" viewBox="0 0 120 120">
                                                <circle class="progress-background" cx="60" cy="60" r="54"></circle>
                                                <circle class="progress" cx="60" cy="60" r="54"></circle>
                                            </svg>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-auto">
                                <ul class="list-unstyled d-flex flex-wrap gap-3 mb-0">
                                    <li class="d-flex align-items-center">
                                        <div class="doughnut-label">
                                            <span style="background-color: #014653"></span>Basic salary
                                        </div>
                                    </li>
                                    <li class="d-flex align-items-center">
                                        <div class="doughnut-label">
                                            <span style="background-color: #53CAFF"></span>Service charge
                                        </div>
                                    </li>
                                    <li class="d-flex align-items-center">
                                        <div class="doughnut-label">
                                            <span style="background-color: #014653"></span>Normal OT
                                        </div>
                                    </li>
                                    <li class="d-flex align-items-center">
                                        <div class="doughnut-label">
                                            <span style="background-color: #53CAFF"></span>Holiday OT
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        @php
                            $percentageChange = 0;
                            if ($previousTotal == 0 && $currentTotal > 0) {
                                $percentageChange = 100;
                            } elseif ($previousTotal > 0 && $currentTotal == 0) {
                                $percentageChange = -100;
                            } elseif ($previousTotal > 0) {
                                $percentageChange = round((($currentTotal - $previousTotal) / $previousTotal) * 100);
                            }
                        @endphp

                        <p class="text-center">
                            <span class="chartImg-block">
                                <img src="{{ asset($percentageChange >= 0 ? 'resorts_assets/images/chartLine-success.svg' : 'resorts_assets/images/chartLine-danger.svg') }}" alt="icon" class="me-2">
                            </span>
                            <span class="{{ $percentageChange >= 0 ? 'text-successTheme' : 'text-danger' }}">
                                {{ $percentageChange >= 0 ? '+' : '-' }}{{ abs($percentageChange) }}%
                            </span> Compared to {{ $previousMonth }}
                        </p>
                    </div>
                @endif
            </div>
        </div>
    
@else
    
        <div class="card">
            <div class="card-body">
                <p>No payroll data available for comparison.</p>
            </div>
        </div>
   
@endif
