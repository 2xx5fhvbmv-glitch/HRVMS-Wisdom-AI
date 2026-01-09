<div class="viewBudget-accordion" id="accordionViewBudget">
    @if(!empty($MainArray))
        @php $iteation=1; @endphp
            @foreach ($MainArray as $key => $value)
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading{{ $iteation }}">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapse{{ $iteation }}" aria-expanded="true" aria-controls="collapse{{ $iteation }}">
                            <h3>{{ $key }}</h3>
                            <span class="badge badge-dark">Budget: $ @if(array_key_exists($key,$DepartmentTotal))  {{ $DepartmentTotal[$key]}} @endif</span>
                            <a href="#" class="text-lightblue fw-500 fs-13">WSB: $11,985</a>
                            <a href="#" class="btn btn-xs btn-coolblue">compare</a>
                        </button>
                    </h2>
                    <div id="collapse{{ $iteation }}" class="accordion-collapse collapse {{ ($iteation == 1)  ? 'show':''}} " aria-labelledby="heading{{ $iteation }}"
                        data-bs-parent="#accordionViewBudget">
                        <div class="table-responsive">
                            <table id="department-budget-table" class="table table-striped w-100">
                                <thead>
                                    <tr>
                                        <th class="text-nowrap">Positions</th>
                                        <th class="text-nowrap">No. of position</th>
                                        <th class="text-nowrap w-120">Rank</th>
                                        <th class="text-nowrap">Nation</th>
                                        <th>Current salary</th>
                                        @foreach ($header as $h)
                                            <th>{{ $h }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($value as $item)
                                        <tr>
                                            @foreach ($item as $key=>$i)
                                                <td class="text-nowrap">
                                                    {{ $i }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @php $iteation++; @endphp
        @endforeach
    @endif
</div>