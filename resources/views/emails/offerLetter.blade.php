@extends('emails.layouts.content')

@section('emailContent')

<div style="text-align: left;">
    <p>Dear <strong>{{ $candidateName }}</strong>,</p>

    <p>We are pleased to inform you that you have been selected for the position of <strong>{{ $positionTitle }}</strong>
    @if($department) in the <strong>{{ $department }}</strong> department @endif
    at <strong>{{ $resortName }}</strong>.</p>

    <p>Please find your offer letter attached to this email. You can also view and download it using the link below.</p>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $offerLetterLink }}" style="background: #004552; color: #ffffff; padding: 14px 40px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 15px; display: inline-block;">
            View Offer Letter
        </a>
    </div>

    <p>To respond to this offer, please click the button above and choose to <strong>Accept</strong> or <strong>Decline</strong> the offer.</p>

    <p>If you have any questions, please don't hesitate to contact our HR department.</p>

    <p style="margin-top: 30px;">Best regards,<br>
    <strong>{{ $resortName }}</strong><br>
    Human Resources Department</p>
</div>

@endsection
