@extends('emails.layouts.content')

@section('emailContent')
    <p>Hi {{ $applicantName }},</p>

    <p>Your leave application has been submitted successfully and is pending approval.</p>

    <table cellpadding="6" cellspacing="0" border="0" style="border-collapse:collapse;">
        <tr><td><strong>Leave Type:</strong></td><td>{{ $leaveCategory }}</td></tr>
        <tr><td><strong>From:</strong></td><td>{{ $fromDate }}</td></tr>
        <tr><td><strong>To:</strong></td><td>{{ $toDate }}</td></tr>
        <tr><td><strong>Total Days:</strong></td><td>{{ $totalDays }}</td></tr>
        @if(!empty($reason))
        <tr><td valign="top"><strong>Reason:</strong></td><td>{{ $reason }}</td></tr>
        @endif
    </table>

    <p>You will receive another notification once your manager has reviewed it.</p>
@endsection
