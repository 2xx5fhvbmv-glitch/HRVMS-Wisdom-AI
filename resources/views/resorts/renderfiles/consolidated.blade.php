<div class="viewBudget-accordion" id="accordionViewBudget">
    @if(!empty($consolidatedBudget))
        @php $divisionIteration = 1; @endphp
        @foreach ($consolidatedBudget as $divisionName => $divisionData)
            {{-- Level 1: Division --}}
            <div class="accordion-item mb-2 division-accordion">
                <h2 class="accordion-header" id="headingDiv{{ $divisionIteration }}">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseDiv{{ $divisionIteration }}" aria-expanded="false"
                            aria-controls="collapseDiv{{ $divisionIteration }}">
                        <i class="fas fa-building me-2"></i>
                        <h3>{{ $divisionName }}</h3>
                        <span class="badge badge-dark ms-2 small divisionGrandTotal">Budget: ${{ number_format($divisionData['calculated_total'] ?? 0, 2) }}</span>
                    </button>
                </h2>
                <div id="collapseDiv{{ $divisionIteration }}" class="collapse"
                     aria-labelledby="headingDiv{{ $divisionIteration }}" data-bs-parent="#accordionViewBudget">
                    <div class="accordion-body p-2">
                        @php $deptIteration = 1; @endphp
                        @foreach ($divisionData['departments'] as $departmentName => $departmentData)
                            {{-- Level 2: Department --}}
                            <div class="accordion mb-2 ms-3 department-accordion" id="accordionDept{{ $divisionIteration }}_{{ $deptIteration }}">
                                <div class="accordion-item">
                                    <div class="row g-0 align-items-center">
                                        <div class="col-md-9">
                                            <h2 class="accordion-header" id="headingDept{{ $divisionIteration }}_{{ $deptIteration }}">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                        data-bs-target="#collapseDept{{ $divisionIteration }}_{{ $deptIteration }}"
                                                        aria-expanded="false" aria-controls="collapseDept{{ $divisionIteration }}_{{ $deptIteration }}">
                                                    <i class="fas fa-sitemap me-2"></i>
                                                    <span>{{ $departmentName }}</span>
                                                    <span class="badge badge-dark ms-2 small departmentGrandTotal">Budget: ${{ number_format($departmentData['calculated_total'] ?? 0, 2) }}</span>
                                                </button>
                                            </h2>
                                        </div>
                                        <div class="col-md-3 text-end pe-2">
                                            @if($employeeRankPosition['position'] == 'HR' || $employeeRankPosition['position'] == 'Finance')
                                            <a href="#revise-budgetmodal"
                                               data-budget_id="{{ $departmentData['manning_response_id'] }}"
                                               data-dept_id="{{ $departmentData['department_id'] }}"
                                               data-bs-toggle="modal"
                                               class="btn btn-sm btn-white open-revise-modal">
                                                <span class="badge badge-danger">
                                                    <i class="fa-solid fa-clock-rotate-left"></i> Revise Budget
                                                </span>
                                            </a>
                                            @endif
                                        </div>
                                    </div>

                                    <div id="collapseDept{{ $divisionIteration }}_{{ $deptIteration }}"
                                         class="collapse"
                                         aria-labelledby="headingDept{{ $divisionIteration }}_{{ $deptIteration }}"
                                         data-bs-parent="#accordionDept{{ $divisionIteration }}_{{ $deptIteration }}">
                                        <div class="accordion-body p-2">

                                            {{-- Sections under Department --}}
                                            @if(!empty($departmentData['sections']))
                                                @php $sectionIteration = 1; @endphp
                                                @foreach($departmentData['sections'] as $sectionName => $sectionData)
                                                    <div class="accordion mb-2 ms-3 section-accordion" id="accordionSec{{ $divisionIteration }}_{{ $deptIteration }}_{{ $sectionIteration }}">
                                                        <div class="accordion-item">
                                                            <h2 class="accordion-header" id="headingSec{{ $divisionIteration }}_{{ $deptIteration }}_{{ $sectionIteration }}">
                                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                                        data-bs-target="#collapseSec{{ $divisionIteration }}_{{ $deptIteration }}_{{ $sectionIteration }}"
                                                                        aria-expanded="false" aria-controls="collapseSec{{ $divisionIteration }}_{{ $deptIteration }}_{{ $sectionIteration }}">
                                                                    <i class="fas fa-layer-group me-2"></i>
                                                                    <span>{{ $sectionName }}</span>
                                                                    <span class="badge badge-dark ms-2 small sectionGrandTotal">Budget: ${{ number_format($sectionData['calculated_total'] ?? 0, 2) }}</span>
                                                                </button>
                                                            </h2>

                                                            <div id="collapseSec{{ $divisionIteration }}_{{ $deptIteration }}_{{ $sectionIteration }}"
                                                                 class="collapse"
                                                                 aria-labelledby="headingSec{{ $divisionIteration }}_{{ $deptIteration }}_{{ $sectionIteration }}"
                                                                 data-bs-parent="#accordionSec{{ $divisionIteration }}_{{ $deptIteration }}_{{ $sectionIteration }}">
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
