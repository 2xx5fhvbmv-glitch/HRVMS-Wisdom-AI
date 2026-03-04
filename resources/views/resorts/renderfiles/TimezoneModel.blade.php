<div class="head">
    <div>Your Clock</div>
    <div>Applicant's O'clock</div>
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

            // Check if the current time slot is booked (handles comma-separated times)
            $isSlotDisabled = false;
            foreach($bookedTimes as $bookedSlot) {
                $bookedResortTimes = array_map('trim', explode(',', $bookedSlot['ResortInterviewtime']));
                if(in_array($startTime->format('h:i A'), $bookedResortTimes)) {
                    $isSlotDisabled = true;
                    break;
                }
            }
        @endphp

        <div class="row_time @if($isSlotDisabled) disable @endif" style="position:relative;">
            <div>
                <img src="{{ URL::asset('resorts_assets/images/flag-maldives.webp') }}" alt="flag">
                {{ $startTime->format('h:i A') }}
            </div>

            <div>
                <img style="height:20px" src="{{ $Applicant_form_data->flag_url }}" alt="flag">
                {{ $startTime->copy()->setTimezone($applicantTimeZone)->format('h:i A') }}
            </div>

            @if($isSlotDisabled)
                <span class="badge bg-danger" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);z-index:1;font-size:11px;">Booked</span>
            @endif

            <input type="checkbox" name="SlotBook[]" value="{{ $i }}" data-id="{{ $i }}" data-ResortInterviewtime="{{ $startTime->format('h:i A') }}" data-ApplicantInterviewtime="{{ $startTime->copy()->setTimezone($applicantTimeZone)->format('h:i A') }}" class="Timezone_checkBox" @if($isSlotDisabled) disabled @endif>
        </div>

        <?php $startTime->addMinutes(30); $i++; ?>
    @endwhile
</div>

<input type="hidden" name="ResortInterviewtime" id="ResortInterviewtime_collected">
<input type="hidden" name="ApplicantInterviewtime" id="ApplicantInterviewtime_collected">

<p>or</p>
<div class="row">
    <div class="col-md-6">
        <label class="form-label mb-1">Your Time</label>
        <input type="hidden" class="form-control" name="MalidivanManualTime1" />
        <input type="time" class="form-control" name="MalidivanManualTime" />
    </div>
    <div class="col-md-6">
        <label class="form-label mb-1">Applicant's Time</label>
        <input type="hidden" class="form-control" name="ApplicantManualTime1" />
        <input type="time" class="form-control" name="ApplicantManualTime" readonly style="background-color: #e9ecef;" />
    </div>
</div>
<input type="hidden" id="resortTimezone" value="{{ $ResortTimeZone }}">
<input type="hidden" id="applicantTimezone" value="{{ $applicantTimeZone }}">

<input type="hidden" name="Round" value="{{$Round}}">
<input type="hidden" name="InterviewType" value="{{$InterviewType}}">
