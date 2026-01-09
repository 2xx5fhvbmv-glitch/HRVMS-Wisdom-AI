@if($LeaveCategories)
    @foreach($LeaveCategories as $category)
        <div class="col-xxl-4 col-lg-6 col-md-4 col-sm-6">
            <div class="leaveCate-block themeDiffColor-block" style="background-color: {{ $category->color.'14' }};border-color:{{ $category->color}}">
                <div class="d-flex">
                    <h6 class="themeDiffColor" style="color: {{ $category->color}}">{{$category->leave_type}}</h6>
                    <div class="d-flex align-items-center">
                        <a href="#editLeave-modal" 
                        data-bs-toggle="modal"
                        data-leave-id="{{ $category->id }}"
                        data-leave-type="{{ $category->leave_type }}"
                        data-number-of-days="{{ $category->number_of_days }}"
                        data-carry-forward="{{ $category->carry_forward }}"
                        data-carry-max="{{ $category->carry_max ?? '' }}"
                        data-earned-leave="{{ $category->earned_leave }}"
                        data-earned-max="{{ $category->earned_max ?? '' }}"
                        data-eligibility="{{ $category->eligibility }}"
                        data-frequency="{{ $category->frequency }}"
                        data-number-of-times="{{ $category->number_of_times }}"
                        data-color="{{ $category->color }}"
                        data-combine-with-other="{{ $category->combine_with_other }}"
                        data-leave-category="{{ $category->leave_category }}">
                            <img src="{{ URL::asset('resorts_assets/images/edit.svg')}}" alt="icon">
                        </a>
                        <a href="#" data-leave-id="{{ $category->id }}" class="btn-lg-icon icon-bg-red delete-leave-btn"><img src="{{ URL::asset('resorts_assets/images/trash-red.svg')}}" alt="icon"></a>
                    </div>
                </div>
                <p>{{$category->number_of_days}} Days</p>
                <p>Forwarded Next Year - {{$category->carry_max ?? 0}} Days</p>
            </div>
        </div>
    @endforeach
@endif

<div class="col">
    <div class="addDash-block">
        <i class="fa-regular fa-plus"></i>
        <h6>Add Leave Category</h6>
    </div>
</div>
