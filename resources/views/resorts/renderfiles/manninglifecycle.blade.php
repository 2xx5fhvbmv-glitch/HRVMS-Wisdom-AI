
<div class="card-title d-flex justify-content-between">


    <h3>Manning {{ (isset($Year)) ? $Year : $getNotifications['year']  }}</h3>
</div>
    @php
        // Defined all the possible steps for budget approval in sitesetting Array
         $allSteps = config('settings.manningRequestLifeCycle');
    @endphp
    <ul class="manning-timeline">
    @if (!empty($allSteps))
        @foreach ($allSteps as $stepKey => $stepName)

            <li class="@if(array_key_exists($stepKey, $getNotifications['BudgetStatus']) && $getNotifications['BudgetStatus'][$stepKey]['comments'] == $stepName)
                    active
                @else
                    complete
                @endif">
                <span>{{ $stepName }}  @if(array_key_exists($stepKey, $getNotifications['BudgetStatus']) && $getNotifications['BudgetStatus'][$stepKey]['comments'] == $stepName)

                  </span>
                    @endif
                </li>
        @endforeach
    @endif
    </ul>
</div>
