@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
@endif

@section('content')
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding">
                <div class="row justify-content-between g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Leave</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-title">
                    <h3>{{ $page_title }}</h3>
                </div>
                <div class="leaveUser-bgBlock">
                @php
                    use Carbon\Carbon;

                    try {
                        
                        $formattedTodayDate = Carbon::createFromFormat('d-m', $today)->format('l M, d');
                        $formattedTomorrow = Carbon::createFromFormat('d-m', $tomorrow)->format('l M, d');
                    } catch (\Carbon\Exceptions\InvalidFormatException $e) {
                        $formattedTodayDate = 'Invalid date format';
                        $formattedTomorrow = 'Invalid date format';
                    }

                @endphp
                    <h6>Today : {{$formattedTodayDate}}</h6>
                </div>
                @if($todayBirthdays->isNotEmpty())
                    @foreach($todayBirthdays as $employee)
                        <div class="leaveUser-block">
                            <div class="img-circle">
                                <img src="{{ $employee->profile_picture }}" alt="image">
                            </div>
                            <div>
                                <h6>{{ $employee->resortAdmin->first_name }} {{ $employee->resortAdmin->last_name }}</h6>
                                <p>{{ $employee->position->position_title }}</p>
                            </div>
                        </div>
                    @endforeach
                @else
                    <p>No birthdays today.</p>
                @endif

                <!-- Section for Tomorrow's Birthdays -->
                <div class="leaveUser-bgBlock">
                    <h6>Tomorrow : {{$formattedTomorrow}}</h6>
                </div>
                @if($tomorrowBirthdays->isNotEmpty())
                    @foreach($tomorrowBirthdays as $employee)
                        <div class="leaveUser-block">
                            <div class="img-circle">
                                <img src="{{ $employee->profile_picture }}" alt="image">
                            </div>
                            <div>
                                <h6>{{ $employee->resortAdmin->first_name }} {{ $employee->resortAdmin->last_name }}</h6>
                                <p>{{ $employee->position->position_title }}</p>
                            </div>
                        </div>
                    @endforeach
                @else
                    <p>No birthdays tomorrow.</p>
                @endif

                <div class="leaveUser-bgBlock">
                    <h6>Upcoming Birthdays</h6>
                </div>
                <!-- Remaining Birthdays -->
                @if($remainingBirthdays->isNotEmpty())
                    @foreach($remainingBirthdays as $employee)
                        <div class="leaveUser-bgBlock">
                            <h6>{{ $employee->formatted_dob }}</h6>
                        </div>
                        <div class="leaveUser-block">
                            <div class="img-circle">
                                <img src="{{ $employee->profile_picture }}" alt="image">
                            </div>
                            <div>
                                <h6>{{ $employee->resortAdmin->first_name }} {{ $employee->resortAdmin->last_name }}</h6>
                                <p>{{ $employee->position->position_title }}</p>
                            </div>
                        </div>
                    @endforeach
                @else
                    <p>No upcoming birthdays in the next two months.</p>
                @endif
                </div>
        </div>
    </div>
 @endsection

@section('import-css')
@endsection

@section('import-scripts')

@endsection