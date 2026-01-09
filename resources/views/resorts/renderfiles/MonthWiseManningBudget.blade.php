
@if($resort_divisions)
    @php  $total = 0;
    @endphp

    <div class="total-b-box text-center mb-2">
        <p class="fs-18 fw-500">Total Budget: <span class="text-theme TotalBudget "><img class="currency-budget-icon h-18" src="{{ $currency }}">{{ number_format($total, 2) }}</span></p>
    </div>
    <div class="accordion budget-accordion" id="accordionExample">
        @foreach($resort_divisions as $key => $value)
        <div class="accordion-item">
            <h2 class="accordion-header" id="heading{{$key}}">
                <button class="accordion-button {{ $key == 0 ? '' : 'collapsed' }}"


                type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapse{{$key}}" aria-expanded="{{ $key == 0 ? 'true' : 'false' }}"
                    aria-controls="collapse{{$key}}">
                    <div class="d-flex align-items-center justify-content-between w-100 pe-sm-4 pe-1">
                        <span class="name"> {{$value->name}}</span>

                        @php

                            $departmentTotal =  array_key_exists($value->id, $departmenet_total) ? $departmenet_total[$value->id] : 0.00;
                            $total += $departmentTotal;
                        @endphp
                        <span class="lable-budget">Budget:<?php echo number_format($departmentTotal,2); ?>  </span>

                    </div>
                </button>
            </h2>
            <div id="collapse{{$key}}" class="accordion-collapse collapse {{ $key == 0 ? 'show' : '' }}"
                aria-labelledby="heading{{$key}}" data-bs-parent="#accordionExample">
                <div class="accordion-body">
                    @foreach($resort_departments->where('division_id', $value->id) as $department)
                        @php

                            $array = [];
                            if(!empty($department->monthWiseBudget)) 
                            {
                                
                                foreach ($department->monthWiseBudget as $Key => $item) {
                                    $array['dept_id'] = $item['dept_id'];

                                    $array['position_monthly_data_id'][] = $item['id'];
                                    $array['manning_response_id'] = $item['manning_response_id'];
                                    $array['Message_id'] = $department->Message_id;
                                }
                            }
                        @endphp
                        <div class="accordion-innerbox bg-white d-flex justify-content-between align-items-center">
                            @if($department->BudgetPageLink == 1)
                                <form id="departmentBudget" method="POST" action="{{ route('resort.department.wise.budget.data') }}">
                                    @csrf

                                <a href="javascript::void(0)" target="_blank">
                                        <p class="mb-0 fw-500 departmentBudget">
                                            <input type="hidden" name="data[]" value="{{ json_encode($array)}}">
                                            {{$department->name}}

                                            <span class="badge ms-sm-3 badge-warning">{{ $department->BudgetStatus }}  </span>
                                        </p>
                                    </a>


                                    <button type="submit"   @if($department->BudgetPageLink == 1) class="submitBtn"  @endif style="display: none;">Submit</button>


                                </form>
                            @else
                                <p class="mb-0 fw-500 departmentBudget">
                                    {{$department->name}}
                                    <span class="badge ms-sm-3 badge-warning">{{ $department->BudgetStatus }}</span>
                                </p>
                            @endif
                            <span class="fw-normal">Budget:      <img class="currency-budget-icon" src="{{ $currency }}">{{ isset($department->OldEmployeesBudgetValue )  ? $department->OldEmployeesBudgetValue : 0.00 }}</span>
                        </div>
                    @endforeach

                </div>
            </div>
        </div>
        @endforeach
@endif


    </div>

    <script>
        document.querySelector('.TotalBudget').innerHTML = ' <img class="currency-budget-icon h-18" src="{{ $currency }}">  {{ number_format($total, 2) }}';
    </script>
