<div class="head">
    <div>Your Clock</div>
    <div>Applicant’s O’clock</div>
</div>
<div class="block">
    <?php
    use Carbon\Carbon;

    $ResortstringTime = config('settings.ResortstringTime'); // Start time in 'h:i A' format
    $ResortEndingTime = config('settings.ResortEndingTime'); // End time in 'h:i A' format
    $ResortTimeZone = config('settings.ResortTimeZone'); // Maldives Timezone

    $applicantTimeZone = $Applicant_form_data->TimeZone;

    $startTime = Carbon::createFromFormat('h:i A', $ResortstringTime, $ResortTimeZone);
    $endTime = Carbon::createFromFormat('h:i A', $ResortEndingTime, $ResortTimeZone);
    $i=1;
    ?>

    @while($startTime <= $endTime)
        @php 
       
            // Check if the current time slot is booked
            $isSlotDisabled = false;
            foreach($bookedTimes as $bookedSlot) {
                
                if($startTime->format('h:i A') == $bookedSlot['ResortInterviewtime']) {
                    $isSlotDisabled = true;
                    break;
                }
            }
        @endphp
            
        <div class="row_time @if($isSlotDisabled) disable @endif">
            <div>
                <img src="{{ URL::asset('resorts_assets/images/flag-maldives.webp') }}" alt="flag">
                {{ $startTime->format('h:i A') }}
            </div>

            <div>
                <img style="height:20px" src="{{ $Applicant_form_data->flag_url }}" alt="flag">
                {{ $startTime->copy()->setTimezone($applicantTimeZone)->format('h:i A') }}
            </div>
            <input type="hidden" name="ResortInterviewtime" id="ResortInterviewtime_{{ $i }}" >
            <input type="hidden" name="ApplicantInterviewtime" id="ApplicantInterviewtime_{{ $i }}" >

            <input type="checkbox" name="SlotBook" data-id="{{ $i }}" data-ResortInterviewtime="{{ $startTime->format('h:i A') }}" data-ApplicantInterviewtime="{{ $startTime->copy()->setTimezone($applicantTimeZone)->format('h:i A') }}" class="Timezone_checkBox" @if($isSlotDisabled) disabled @endif>
        </div>

        <?php $startTime->addMinutes(30); $i++; ?>
    @endwhile
</div>

<p>or</p>
<div class="row">
    <div class="col-md-6">
        <input type="hidden" class="form-control" name="MalidivanManualTime1" placeholder="Malidivan Add Manual Time" />
        <input type="time" class="form-control" name="MalidivanManualTime" placeholder="Malidivan Add Manual Time" />
    </div>
    <div class="col-md-6">
        <input type="hidden" class="form-control" name="ApplicantManualTime1" placeholder="Malidivan Add Manual Time" />
        <input type="time" class="form-control" name="ApplicantManualTime" placeholder="Applicant Add Manual Time" />
    </div>
</div>

<input type="hidden" name="Round" value="{{$Round}}">
<input type="hidden" name="InterviewType" value="{{$InterviewType}}">
