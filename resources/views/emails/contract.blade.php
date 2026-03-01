@extends('emails.layouts.content')

@section('emailContent')

<div style="text-align: left;">
    <p>Dear <strong>{{ $candidateName }}</strong>,</p>

    <p>Following your acceptance of our offer for the position of <strong>{{ $positionTitle }}</strong>
    @if($department) in the <strong>{{ $department }}</strong> department @endif
    at <strong>{{ $resortName }}</strong>, we are pleased to share your employment contract.</p>

    <p>Please find your contract attached to this email. You can also view and download it using the link below.</p>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $contractLink }}" style="background: #004552; color: #ffffff; padding: 14px 40px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 15px; display: inline-block;">
            View Contract
        </a>
    </div>

    <p>To respond, please click the button above and choose to <strong>Accept</strong> or <strong>Decline</strong> the contract.</p>

    <p>Once you accept the contract, your employee account will be created automatically and you will receive your login credentials via email.</p>

    <p>If you have any questions, please don't hesitate to contact our HR department.</p>

    <p style="margin-top: 30px;">Best regards,<br>
    <strong>{{ $resortName }}</strong><br>
    Human Resources Department</p>
</div>

@endsection
