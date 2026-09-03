@if(!empty($payrollData))
    @php
        $months = array_keys($payrollData);
        $currentMonth = $months[0] ?? null;
        $previousMonth = $months[1] ?? null;
        $twoYearsAgoMonth = $months[2] ?? null;

        // Presentation-only: approved teal ramp (darkest -> softest), all
        // already used elsewhere on this dashboard except the softest tint,
        // which is a lighter extension of the same family rather than a new hue.
        $pcCategories = [
            'basicSalary'   => ['label' => 'Basic Salary', 'color' => 'var(--teal)'],
            'serviceCharge' => ['label' => 'Service Charge', 'color' => '#0E8A9E'],
            'normalOT'      => ['label' => 'Normal OT', 'color' => '#6FB7C2'],
            'holidayOT'     => ['label' => 'Holiday OT', 'color' => '#B7DEE2'],
        ];
    @endphp

        <div class="card card-salaryCalc payroll-comparison-card h-auto" id="card-salaryCalc">
            <div class="pc-header">
                <h3 class="pc-header-title">Payroll Comparison</h3>
                <div class="form-group">
                    <select class="form-select" id="monthSelector">
                        @for ($i = 1; $i <= 12; $i++)
                            @php
                                $monthNum = str_pad($i, 2, '0', STR_PAD_LEFT);
                                $monthName = date("F", mktime(0, 0, 0, $i, 1));
                                $sel = isset($selectedMonth) ? $selectedMonth : now()->format('m');
                            @endphp
                            <option value="{{ $monthNum }}" @if($monthNum == str_pad($sel, 2, '0', STR_PAD_LEFT)) selected @endif>
                                {{ $monthName }}
                            </option>
                        @endfor
                    </select>
                </div>
            </div>

            @php
                $currentTotal = $payrollData[$currentMonth]['total'] ?? 0;
                $previousTotal = $payrollData[$previousMonth]['total'] ?? 0;
                $twoYearsAgoTotal = $twoYearsAgoMonth ? ($payrollData[$twoYearsAgoMonth]['total'] ?? 0) : 0;

                // Current year vs previous year
                $currentVsPrev = 0;
                if ($previousTotal == 0 && $currentTotal > 0) {
                    $currentVsPrev = 100;
                } elseif ($previousTotal > 0 && $currentTotal == 0) {
                    $currentVsPrev = -100;
                } elseif ($previousTotal > 0) {
                    $currentVsPrev = round((($currentTotal - $previousTotal) / $previousTotal) * 100);
                }

                // Previous year vs two years ago — still computed exactly as
                // before (untouched calculation); the previous column no
                // longer displays it though, per the new design (it shows a
                // static "Baseline period" pill instead).
                $prevVsTwoYears = 0;
                if ($twoYearsAgoTotal == 0 && $previousTotal > 0) {
                    $prevVsTwoYears = 100;
                } elseif ($twoYearsAgoTotal > 0 && $previousTotal == 0) {
                    $prevVsTwoYears = -100;
                } elseif ($twoYearsAgoTotal > 0) {
                    $prevVsTwoYears = round((($previousTotal - $twoYearsAgoTotal) / $twoYearsAgoTotal) * 100);
                }

                $pcItems = function ($month) use ($payrollData, $pcCategories) {
                    $items = [];
                    foreach ($pcCategories as $key => $meta) {
                        $items[] = [
                            'key' => $key,
                            'label' => $meta['label'],
                            'color' => $meta['color'],
                            'amount' => $month ? ($payrollData[$month][$key]['amount'] ?? 0) : 0,
                        ];
                    }
                    return $items;
                };
            @endphp

            <div class="row g-3 pc-columns">
                {{-- Current Period (Left) --}}
                @if($currentMonth)
                    <div class="col-sm-6 pc-col border-right">
                        <span class="badge badge-themeBlue pc-period-pill">{{ $currentMonth }}</span>

                        @if($currentTotal > 0)
                            <div class="pc-donut-wrap">
                                <canvas id="pcDonutCurrent"></canvas>
                            </div>
                            <div class="pc-legend">
                                @foreach($pcItems($currentMonth) as $item)
                                    <div class="pc-legend-row">
                                        <span class="pc-legend-dot" style="background:{{ $item['color'] }};"></span>
                                        <span class="pc-legend-label">{{ $item['label'] }}</span>
                                        <span class="pc-legend-amount">{{ Common::GetResortCurrencySymbol() }}{{ number_format($item['amount'], 0) }}</span>
                                    </div>
                                @endforeach
                            </div>

                            <p class="pc-compare-line">
                                @if($previousTotal == 0)
                                    <span class="badge badge-themeGray pc-compare-pill">No prior data</span>
                                @else
                                    @php
                                        $pcTrendClass = $currentVsPrev > 0 ? 'badge-themeSuccess' : ($currentVsPrev < 0 ? 'badge-themeDanger' : 'badge-themeGray');
                                        $pcTrendIcon = $currentVsPrev > 0 ? 'fa-arrow-up' : ($currentVsPrev < 0 ? 'fa-arrow-down' : 'fa-minus');
                                    @endphp
                                    <span class="badge {{ $pcTrendClass }} pc-compare-pill">
                                        <i class="fa-solid {{ $pcTrendIcon }}"></i>
                                        {{ $currentVsPrev > 0 ? '+' : '' }}{{ $currentVsPrev }}%
                                    </span>
                                    <span class="pc-compare-text">vs {{ $previousMonth }}</span>
                                @endif
                            </p>
                        @else
                            <div class="pc-empty">
                                <p>No data for this period</p>
                                <span class="badge badge-themeGray">No data</span>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Previous Period (Right) --}}
                @if($previousMonth)
                    <div class="col-sm-6 pc-col">
                        <span class="badge badge-themeGray pc-period-pill">{{ $previousMonth }}</span>

                        @if($previousTotal > 0)
                            <div class="pc-donut-wrap">
                                <canvas id="pcDonutPrevious"></canvas>
                            </div>
                            <div class="pc-legend">
                                @foreach($pcItems($previousMonth) as $item)
                                    <div class="pc-legend-row">
                                        <span class="pc-legend-dot" style="background:{{ $item['color'] }};"></span>
                                        <span class="pc-legend-label">{{ $item['label'] }}</span>
                                        <span class="pc-legend-amount">{{ Common::GetResortCurrencySymbol() }}{{ number_format($item['amount'], 0) }}</span>
                                    </div>
                                @endforeach
                            </div>

                            <p class="pc-compare-line">
                                <span class="badge badge-themeGray pc-compare-pill">Baseline period</span>
                            </p>
                        @else
                            <div class="pc-empty">
                                <p>No data for this period</p>
                                <span class="badge badge-themeGray">No data</span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <script>
            // Re-declared on every render (initial load and AJAX month-swap
            // both inject this partial's markup fresh) so the chart-init
            // function below always reads the data for whatever is currently
            // in the DOM.
            window.pcComparisonData = {
                current: {!! $currentTotal > 0 ? json_encode(['label' => $currentMonth, 'total' => $currentTotal, 'items' => $pcItems($currentMonth)]) : 'null' !!},
                previous: {!! $previousTotal > 0 ? json_encode(['label' => $previousMonth, 'total' => $previousTotal, 'items' => $pcItems($previousMonth)]) : 'null' !!}
            };
        </script>

@else

        <div class="card">
            <div class="card-body">
                <p>No payroll data available for comparison.</p>
            </div>
        </div>

@endif
