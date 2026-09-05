@php
    // Shared across the on-screen view and the print/download output — one
    // source of truth for the benefits statement. Presentation only: reads
    // the same $benefit_grid / $benefitGridChildren / $selected_* the
    // controller already computes, nothing added or recomputed.
    $gradeName = optional(\App\Models\ResortBenefitGradeLevel::find($benefit_grid->emp_grade))->name ?? $benefit_grid->emp_grade;

    $effectiveDate = 'Not set';
    if (!empty($benefit_grid->effective_date)) {
        try {
            $effectiveDate = \Carbon\Carbon::parse($benefit_grid->effective_date)->format('d-M-Y');
        } catch (\Exception $e) {
            $effectiveDate = $benefit_grid->effective_date;
        }
    }

    $isActive = strtolower(trim($benefit_grid->status ?? '')) === 'active';

    $linenChips = array_filter(array_map('trim', $selected_linen_array ?? []));
    $sportsChips = array_filter(array_map('trim', $selected_sports ?? []));

    // Inline icon set (no icon font/lib — same approach as the reference).
    $svg = fn($path) => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' . $path . '</svg>';
    $icons = [
        'leave'    => $svg('<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>'),
        'discount' => $svg('<path d="M9 9h.01M15 15h.01M16 8l-8 8"/><path d="M20.9 12.6a2 2 0 000-1.2l-.9-2.4.1-2.6a2 2 0 00-1.5-1.5L16.6 4l-2.4-.9a2 2 0 00-1.2 0L10.6 4 8 3.9a2 2 0 00-1.5 1.5L6.4 8 3.9 10.6a2 2 0 000 1.2l.9 2.4-.1 2.6A2 2 0 006.1 18.4L8.4 20l2.4.9a2 2 0 001.2 0l2.4-.9 2.6.1a2 2 0 001.5-1.5z"/>'),
        'work'     => $svg('<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'),
        'home'     => $svg('<path d="M3 21h18M5 21V9l7-5 7 5v12M9 21v-6h6v6"/>'),
        'travel'   => $svg('<path d="M17.8 19.2L16 11l3.5-3.5a2.1 2.1 0 00-3-3L13 8 4.8 6.2a1 1 0 00-.9 1.7L9 11l-2 3-3-.5-1 1 4 2 2 4 1-1L9.5 17l3-2 3.6 5.1a1 1 0 001.7-.9z"/>'),
        'health'   => $svg('<path d="M20.8 4.6a5.5 5.5 0 00-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 00-7.8 7.8l1.1 1L12 21l7.7-7.6 1.1-1a5.5 5.5 0 000-7.8z"/>'),
        'stay'     => $svg('<path d="M2 8h20M2 8v10a1 1 0 001 1h18a1 1 0 001-1V8M6 8V6a2 2 0 012-2h8a2 2 0 012 2v2"/>'),
        'fun'      => $svg('<path d="M6 3v18M6 8h9a3 3 0 010 6H6"/>'),
        'people'   => $svg('<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>'),
        'check'    => '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg>',
    ];
@endphp

<div class="dochead">
    <div class="gradebadge"><span class="g">{{ $gradeName }}</span><span class="gl">Grade</span></div>
    <div class="dh-main">
        <div class="dh-cap">Benefits package</div>
        <div class="dh-facts">
            <span class="f">Effective <b class="tnum">{{ $effectiveDate }}</b></span>
            <span class="f">Salary <b>{{ $benefit_grid->salary_period ?: 'Not set' }}</b></span>
            <span class="f">Paid in <b>{{ $benefit_grid->salary_paid_in ?: 'Not set' }}</b></span>
            <span class="f">Contract <b>{{ $benefit_grid->contract_status ?: 'Not set' }}</b></span>
        </div>
    </div>
    <span class="stpill @if(!$isActive) stpill-muted @endif"><span class="dot"></span>{{ $benefit_grid->status ?: 'Not set' }}</span>
</div>

{{-- Time off & leave — dynamic per-resort leave categories --}}
<div class="sec">
    <div class="sec-h"><span class="ic">{!! $icons['leave'] !!}</span><span class="t">Time off &amp; leave</span><span class="rule"></span></div>
    <div class="dl">
        @forelse($benefitGridChildren as $child)
            <div class="di">
                <span class="k">{{ $child->leave_category_name ?? 'N/A' }}</span>
                @if(is_numeric($child->allocated_days))
                    <span class="v tnum">{{ $child->allocated_days }}<span class="u">{{ (int) $child->allocated_days === 1 ? 'day' : 'days' }}</span></span>
                @else
                    <span class="v na">{{ $child->allocated_days ?: 'Not set' }}</span>
                @endif
            </div>
        @empty
            <div class="di"><span class="k">Leave allocation</span><span class="v na">Not set</span></div>
        @endforelse
    </div>
</div>

{{-- Discounts --}}
<div class="sec">
    <div class="sec-h"><span class="ic">{!! $icons['discount'] !!}</span><span class="t">Discounts</span><span class="rule"></span></div>
    <div class="dl">
        @foreach([
            'Food & beverage' => $benefit_grid->food_and_beverages_discount,
            'Alcoholic beverages' => $benefit_grid->alchoholic_beverages_discount,
            'Spa' => $benefit_grid->spa_discount,
            'Dive center' => $benefit_grid->dive_center_discount,
            'Water sports' => $benefit_grid->water_sports_discount,
            'Friends with benefit' => $benefit_grid->friends_with_benefit_discount,
        ] as $label => $val)
            <div class="di">
                <span class="k">{{ $label }}</span>
                @if(is_numeric($val))
                    <span class="v pct tnum">{{ rtrim(rtrim(number_format($val, 2), '0'), '.') }}<span class="u">%</span></span>
                @else
                    <span class="v na">Not set</span>
                @endif
            </div>
        @endforeach
    </div>
</div>

{{-- Work & schedule --}}
<div class="sec">
    <div class="sec-h"><span class="ic">{!! $icons['work'] !!}</span><span class="t">Work &amp; schedule</span><span class="rule"></span></div>
    <div class="dl">
        <div class="di"><span class="k">Working hours per week</span>
            @if(is_numeric($benefit_grid->working_hrs_per_week))
                <span class="v tnum">{{ $benefit_grid->working_hrs_per_week }}<span class="u">hrs</span></span>
            @else
                <span class="v na">Not set</span>
            @endif
        </div>
        <div class="di"><span class="k">Day off per week</span>
            @if(is_numeric($benefit_grid->day_off_per_week))
                <span class="v tnum">{{ $benefit_grid->day_off_per_week }}<span class="u">{{ (int) $benefit_grid->day_off_per_week === 1 ? 'day' : 'days' }}</span></span>
            @else
                <span class="v na">Not set</span>
            @endif
        </div>
        <div class="di"><span class="k">Public holidays per year</span>
            @if(is_numeric($benefit_grid->public_holiday_per_year))
                <span class="v tnum">{{ $benefit_grid->public_holiday_per_year }}<span class="u">days</span></span>
            @else
                <span class="v na">Not set</span>
            @endif
        </div>
        <div class="di"><span class="k">Meals per day</span>
            @if(is_numeric($benefit_grid->meals_per_day))
                <span class="v tnum">{{ $benefit_grid->meals_per_day }}</span>
            @else
                <span class="v na">Not set</span>
            @endif
        </div>
        <div class="di"><span class="k">Incentives service charge</span>
            @if(is_numeric($benefit_grid->service_charge))
                <span class="v tnum">{{ number_format($benefit_grid->service_charge, 2) }}</span>
            @else
                <span class="v na">Not set</span>
            @endif
        </div>
        <div class="di"><span class="k">Overtime</span>
            <span class="v {{ strtolower(trim($benefit_grid->overtime ?? '')) === 'yes' ? 'inc' : 'na' }}">
                @if(strtolower(trim($benefit_grid->overtime ?? '')) === 'yes')
                    {!! $icons['check'] !!}Included
                @else
                    {{ $benefit_grid->overtime ?: 'Not set' }}
                @endif
            </span>
        </div>
        <div class="di"><span class="k">Paid public-holiday &amp; Friday rate</span>
            @if(is_numeric($benefit_grid->paid_worked_public_holiday_and_friday))
                <span class="v tnum">{{ number_format($benefit_grid->paid_worked_public_holiday_and_friday, 2) }}</span>
            @else
                <span class="v na">Not set</span>
            @endif
        </div>
    </div>
</div>

{{-- Accommodation & living --}}
<div class="sec">
    <div class="sec-h"><span class="ic">{!! $icons['home'] !!}</span><span class="t">Accommodation &amp; living</span><span class="rule"></span></div>
    <div class="dl">
        <div class="di"><span class="k">Accommodation</span><span class="v">{{ $benefit_grid->accommodation_status ?: 'Not set' }}</span></div>
        <div class="di"><span class="k">Housekeeping</span><span class="v">{{ $benefit_grid->housekeeping ?: 'Not set' }}</span></div>
        <div class="di"><span class="k">Laundry</span><span class="v">{{ $benefit_grid->laundry ?: 'Not set' }}</span></div>
        <div class="di">
            <span class="k">Uniform</span>
            @php($uniformNorm = strtolower(trim($benefit_grid->uniform ?? '')))
            @if(in_array($uniformNorm, ['yes', 'no']))
                <span class="v {{ $uniformNorm === 'yes' ? 'inc' : 'na' }}">
                    @if($uniformNorm === 'yes')
                        {!! $icons['check'] !!}Included
                    @else
                        Not included
                    @endif
                </span>
            @else
                <span class="v">{{ $benefit_grid->uniform ?: 'Not set' }}</span>
            @endif
        </div>
        @foreach([
            'Furniture & fixtures' => $benefit_grid->furniture_and_fixtures,
            'Internet access' => $benefit_grid->internet_access,
            'Telephone' => $benefit_grid->telephone,
        ] as $label => $val)
            <div class="di">
                <span class="k">{{ $label }}</span>
                <span class="v {{ strtolower(trim($val ?? '')) === 'yes' ? 'inc' : 'na' }}">
                    @if(strtolower(trim($val ?? '')) === 'yes')
                        {!! $icons['check'] !!}Included
                    @else
                        Not included
                    @endif
                </span>
            </div>
        @endforeach
        <div class="di full">
            <span class="k">Linen provided</span>
            @if(count($linenChips))
                <div class="chips">
                    @foreach($linenChips as $chip)<span class="chip">{{ $chip }}</span>@endforeach
                </div>
            @else
                <span class="v na">Not set</span>
            @endif
        </div>
    </div>
</div>

{{-- Travel & relocation --}}
<div class="sec">
    <div class="sec-h"><span class="ic">{!! $icons['travel'] !!}</span><span class="t">Travel &amp; relocation</span><span class="rule"></span></div>
    <div class="dl">
        <div class="di"><span class="k">Free return flights to Mal&eacute; / year</span>
            @if(is_numeric($benefit_grid->free_return_flight_to_male_per_year))
                <span class="v tnum">{{ $benefit_grid->free_return_flight_to_male_per_year }}</span>
            @else
                <span class="v na">Not set</span>
            @endif
        </div>
        <div class="di"><span class="k">Seaplane staff rate to/from Mal&eacute;</span>
            @if(is_numeric($benefit_grid->staff_rate_for_seaplane_male))
                <span class="v tnum">${{ number_format($benefit_grid->staff_rate_for_seaplane_male, 2) }}</span>
            @else
                <span class="v na">Not set</span>
            @endif
        </div>
        <div class="di"><span class="k">Max excess-luggage relocation</span>
            @if(is_numeric($benefit_grid->max_excess_luggage_relocation_expense))
                <span class="v tnum">${{ number_format($benefit_grid->max_excess_luggage_relocation_expense, 2) }}</span>
            @else
                <span class="v na">Not set</span>
            @endif
        </div>
        <div class="di"><span class="k">Mal&eacute; subsistence allowance</span>
            @if(is_numeric($benefit_grid->male_subsistence_allowance))
                <span class="v tnum">${{ number_format($benefit_grid->male_subsistence_allowance, 2) }}</span>
            @else
                <span class="v na">Not set</span>
            @endif
        </div>
        {{-- "Relocation Tickets to Maldives" is duplicated in the source data — rendered once here only. --}}
        @foreach([
            'Relocation tickets to Maldives' => $benefit_grid->relocation_ticket,
            'Annual-leave ticket to/from POH' => $benefit_grid->annual_leave_ticket,
            'Ticket upon termination' => $benefit_grid->ticket_upon_termination,
        ] as $label => $val)
            <div class="di">
                <span class="k">{{ $label }}</span>
                <span class="v {{ strtolower(trim($val ?? '')) === 'yes' ? 'inc' : 'na' }}">
                    @if(strtolower(trim($val ?? '')) === 'yes')
                        {!! $icons['check'] !!}Included
                    @else
                        Not included
                    @endif
                </span>
            </div>
        @endforeach
    </div>
</div>

{{-- Health & bonuses --}}
<div class="sec">
    <div class="sec-h"><span class="ic">{!! $icons['health'] !!}</span><span class="t">Health &amp; bonuses</span><span class="rule"></span></div>
    <div class="dl">
        <div class="di"><span class="k">Ramadan bonus</span>
            @if(is_numeric($benefit_grid->ramadan_bonus))
                <span class="v tnum">MVR {{ number_format($benefit_grid->ramadan_bonus, 2) }}</span>
            @else
                <span class="v na">Not set</span>
            @endif
        </div>
        <div class="di"><span class="k">Ramadan eligibility</span><span class="v">{{ $benefit_grid->ramadan_bonus_eligibility ?: 'Not set' }}</span></div>
        <div class="di">
            <span class="k">Health care &amp; insurance</span>
            <span class="v {{ strtolower(trim($benefit_grid->health_care_insurance ?? '')) === 'yes' ? 'inc' : 'na' }}">
                @if(strtolower(trim($benefit_grid->health_care_insurance ?? '')) === 'yes')
                    {!! $icons['check'] !!}Included
                @else
                    Not included
                @endif
            </span>
        </div>
        <div class="di">
            <span class="k">Staff loan &amp; salary advance</span>
            <span class="v {{ strtolower(trim($benefit_grid->loan_and_salary_advanced ?? '')) === 'yes' ? 'inc' : 'na' }}">
                @if(strtolower(trim($benefit_grid->loan_and_salary_advanced ?? '')) === 'yes')
                    {!! $icons['check'] !!}Included
                @else
                    {{ $benefit_grid->loan_and_salary_advanced ?: 'Not set' }}
                @endif
            </span>
        </div>
    </div>
</div>

{{-- Staff stay rates --}}
<div class="sec">
    <div class="sec-h"><span class="ic">{!! $icons['stay'] !!}</span><span class="t">Staff stay rates</span><span class="rule"></span></div>
    <div class="dl">
        <div class="di"><span class="k">Single room</span>
            @if(is_numeric($benefit_grid->standard_staff_rate_for_single))
                <span class="v tnum">${{ number_format($benefit_grid->standard_staff_rate_for_single, 2) }} <span class="u">/ night</span></span>
            @else
                <span class="v na">Not set</span>
            @endif
        </div>
        <div class="di"><span class="k">Double room</span>
            @if(is_numeric($benefit_grid->standard_staff_rate_for_double))
                <span class="v tnum">${{ number_format($benefit_grid->standard_staff_rate_for_double, 2) }} <span class="u">/ night</span></span>
            @else
                <span class="v na">Not set</span>
            @endif
        </div>
    </div>
</div>

{{-- Recreation & entertainment --}}
<div class="sec">
    <div class="sec-h"><span class="ic">{!! $icons['fun'] !!}</span><span class="t">Recreation &amp; entertainment</span><span class="rule"></span></div>
    @if(count($sportsChips))
        <div class="chips">
            @foreach($sportsChips as $chip)<span class="chip">{{ $chip }}</span>@endforeach
        </div>
    @else
        <div class="dl"><div class="di"><span class="k">Recreation &amp; entertainment</span><span class="v na">Not set</span></div></div>
    @endif
</div>

{{-- Positions covered by this grade --}}
@if(!empty($positionsByRank) && $positionsByRank->isNotEmpty())
<div class="sec">
    <div class="sec-h"><span class="ic">{!! $icons['people'] !!}</span><span class="t">Positions covered</span><span class="rule"></span></div>
    <div class="dl">
        @foreach($positionsByRank as $rankName => $positions)
            <div class="di full">
                <span class="k">{{ $rankName }}</span>
                <div class="chips">
                    @foreach($positions as $position)
                        <span class="chip">{{ $position->position_title }} ({{ $position->employees_count }} employee{{ $position->employees_count == 1 ? '' : 's' }})</span>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif
