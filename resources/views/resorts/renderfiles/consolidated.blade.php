<div class="viewBudget-accordion" id="accordionViewBudget">
    @if(!empty($consolidatedBudget))
        @php $divisionIteration = 1; @endphp
        @foreach ($consolidatedBudget as $divisionName => $divisionData)
            {{-- Level 1: Division --}}
            <div class="accordion-item mb-2 division-accordion">
                <h2 class="accordion-header" id="headingDiv{{ $divisionIteration }}">
                    <button class="accordion-button collapsed cb-accordion-btn" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseDiv{{ $divisionIteration }}" aria-expanded="false"
                            aria-controls="collapseDiv{{ $divisionIteration }}">
                        <div class="cb-row-main">
                            <span class="wb-level-tag">Division</span>
                            <span class="wb-group-row-name">{{ $divisionName }}</span>
                        </div>
                        <div class="wb-group-row-budget divisionGrandTotal">{!! Common::formatCurrency($divisionData['calculated_total'] ?? 0, 'USD') !!}</div>
                        <i class="fas fa-chevron-right cb-chevron"></i>
                    </button>
                </h2>
                <div id="collapseDiv{{ $divisionIteration }}" class="collapse"
                     aria-labelledby="headingDiv{{ $divisionIteration }}" data-bs-parent="#accordionViewBudget">
                    <div class="accordion-body p-2">
                        @php $deptIteration = 1; @endphp
                        @foreach ($divisionData['departments'] as $departmentName => $departmentData)
                            {{-- Level 2: Department --}}
                            @php
                                $deptPositionCount = count($departmentData['positions'] ?? []);
                                foreach (($departmentData['sections'] ?? []) as $sectionData) {
                                    $deptPositionCount += count($sectionData['positions'] ?? []);
                                }
                            @endphp
                            <div class="accordion mb-2 ms-3 department-accordion" id="accordionDept{{ $divisionIteration }}_{{ $deptIteration }}">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingDept{{ $divisionIteration }}_{{ $deptIteration }}">
                                        <button class="accordion-button collapsed cb-accordion-btn" type="button" data-bs-toggle="collapse"
                                                data-bs-target="#collapseDept{{ $divisionIteration }}_{{ $deptIteration }}"
                                                aria-expanded="false" aria-controls="collapseDept{{ $divisionIteration }}_{{ $deptIteration }}">
                                            <div class="cb-row-main">
                                                <span class="wb-level-tag">Department</span>
                                                <span class="wb-group-row-name">{{ $departmentName }}</span>
                                                <div class="wb-group-row-meta">{{ $deptPositionCount }} {{ Str::plural('position', $deptPositionCount) }}</div>
                                            </div>
                                            <div class="wb-group-row-budget departmentGrandTotal">{!! Common::formatCurrency($departmentData['calculated_total'] ?? 0, 'USD') !!}</div>
                                            <i class="fas fa-chevron-right cb-chevron"></i>
                                        </button>
                                    </h2>

                                    <div id="collapseDept{{ $divisionIteration }}_{{ $deptIteration }}"
                                         class="collapse"
                                         aria-labelledby="headingDept{{ $divisionIteration }}_{{ $deptIteration }}">
                                        <div class="accordion-body p-2">

                                            {{-- Sections under Department --}}
                                            @if(!empty($departmentData['sections']))
                                                @php $sectionIteration = 1; @endphp
                                                @foreach($departmentData['sections'] as $sectionName => $sectionData)
                                                    <div class="accordion mb-2 ms-3 section-accordion" id="accordionSec{{ $divisionIteration }}_{{ $deptIteration }}_{{ $sectionIteration }}">
                                                        <div class="accordion-item">
                                                            <h2 class="accordion-header" id="headingSec{{ $divisionIteration }}_{{ $deptIteration }}_{{ $sectionIteration }}">
                                                                <button class="accordion-button collapsed cb-accordion-btn" type="button" data-bs-toggle="collapse"
                                                                        data-bs-target="#collapseSec{{ $divisionIteration }}_{{ $deptIteration }}_{{ $sectionIteration }}"
                                                                        aria-expanded="false" aria-controls="collapseSec{{ $divisionIteration }}_{{ $deptIteration }}_{{ $sectionIteration }}">
                                                                    <div class="cb-row-main">
                                                                        <span class="wb-level-tag">Section</span>
                                                                        <span class="wb-group-row-name">{{ $sectionName }}</span>
                                                                    </div>
                                                                    <div class="wb-group-row-budget sectionGrandTotal">{!! Common::formatCurrency($sectionData['calculated_total'] ?? 0, 'USD') !!}</div>
                                                                    <i class="fas fa-chevron-right cb-chevron"></i>
                                                                </button>
                                                            </h2>

                                                            <div id="collapseSec{{ $divisionIteration }}_{{ $deptIteration }}_{{ $sectionIteration }}"
                                                                 class="collapse"
                                                                 aria-labelledby="headingSec{{ $divisionIteration }}_{{ $deptIteration }}_{{ $sectionIteration }}">
                                                                <div class="accordion-body p-2">
                                                                    @php $posSecIteration = 1; @endphp
                                                                    @foreach($sectionData['positions'] as $positionName => $positionData)
                                                                        <div class="ms-3 mb-2">
                                                                            @include('resorts.renderfiles.position_tree_item', [
                                                                                'positionName' => $positionName,
                                                                                'positionData' => $positionData,
                                                                                'departmentData' => $departmentData,
                                                                                'departmentName' => $departmentName,
                                                                                'resortCosts' => $resortCosts,
                                                                                'header' => $header,
                                                                                'mvrToDollarRate' => $mvrToDollarRate,
                                                                'accordionId' => "pos{$divisionIteration}_{$deptIteration}_{$sectionIteration}_{$posSecIteration}"
                                                                            ])
                                                                        </div>
                                                                        @php $posSecIteration++; @endphp
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @php $sectionIteration++; @endphp
                                                @endforeach
                                            @endif

                                            {{-- Direct Positions under Department (no section) --}}
                                            @if(!empty($departmentData['positions']))
                                                @php $positionIteration = 1; @endphp
                                                @foreach($departmentData['positions'] as $positionName => $positionData)
                                                    <div class="ms-3 mb-2">
                                                        @include('resorts.renderfiles.position_tree_item', [
                                                            'positionName' => $positionName,
                                                            'positionData' => $positionData,
                                                            'departmentData' => $departmentData,
                                                            'departmentName' => $departmentName,
                                                            'resortCosts' => $resortCosts,
                                                            'header' => $header,
                                                            'mvrToDollarRate' => $mvrToDollarRate,
                                                            'accordionId' => "pos{$divisionIteration}_{$deptIteration}_0_{$positionIteration}"
                                                        ])
                                                    </div>
                                                    @php $positionIteration++; @endphp
                                                @endforeach
                                            @endif

                                        </div>
                                    </div>
                                </div>
                            </div>
                            @php $deptIteration++; @endphp
                        @endforeach
                    </div>
                </div>
            </div>
            @php $divisionIteration++; @endphp
        @endforeach
    @endif
</div>

<style>
    /* ---- Accordion shells (Division / Department / Section) ----
       Same visual language as View Budget's nav-card rows (.wb-group-row /
       .wb-level-tag / .wb-group-row-name / .wb-group-row-budget), applied
       directly to the Bootstrap accordion trigger button instead of a
       plain div — the two pages navigate differently (real expand/collapse
       here vs. View Budget's replace-in-place drill-down) but should look
       identical at rest. One shared rule for every tier — View Budget
       itself uses a single row style for division/department/section/
       position alike, no per-level size variation. */
    .cb-accordion-btn {
        display: flex;
        align-items: center;
        gap: 12px;
        width: 100%;
        border: 1px solid var(--wb-line);
        border-radius: 10px;
        padding: 10px 14px;
        background: var(--wb-card) !important;
        color: var(--wb-ink);
        font-weight: 600;
        transition: background .12s, border-color .12s;
    }
    .cb-accordion-btn:hover { background: var(--wb-teal-tint-2) !important; border-color: var(--wb-teal-tint-1); }
    /* Suppress Bootstrap's default right-side caret — using our own
       chevron instead, so it stays brand-colored, not the theme's default. */
    .cb-accordion-btn::after { display: none !important; }

    .cb-row-main { flex: 1; min-width: 0; }
    .cb-chevron {
        color: var(--wb-faint);
        font-size: 12px;
        flex-shrink: 0;
        transition: transform 0.2s ease;
    }
    .cb-accordion-btn[aria-expanded="true"] .cb-chevron { transform: rotate(90deg); }

    .division-accordion { background: transparent; border: none; }
</style>
