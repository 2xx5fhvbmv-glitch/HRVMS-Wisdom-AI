@extends('emails.layouts.content')

@section('emailContent')
    <p>Hi {{ $recipientName ?? 'there' }},</p>

    <p>{!! $body ?? 'You have a new Incident-module notification.' !!}</p>

    @if(!empty($details) && is_array($details))
        <table cellpadding="6" cellspacing="0" border="0" style="border-collapse:collapse;">
            @foreach($details as $label => $value)
                <tr>
                    <td valign="top"><strong>{{ $label }}:</strong></td>
                    <td>{{ $value }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if(!empty($ctaUrl))
        <p style="margin-top:16px;">
            <a href="{{ $ctaUrl }}" style="background:#014653;color:#fff;padding:8px 18px;border-radius:6px;text-decoration:none;display:inline-block;">
                {{ $ctaLabel ?? 'View in HRVMS' }}
            </a>
        </p>
    @endif

    <p style="color:#6c757d;font-size:12px;margin-top:18px;">
        You're receiving this because you're a participant in this incident.
        Open HRVMS to view the full details.
    </p>
@endsection
