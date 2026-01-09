@extends('emails.layouts.content')

@section('emailContent')
    <p>Hello {{$name}},</p>
    {!! $aboveBody !!}
    <p><a style="padding:5px 10px;background-color:#DA2128;color:#ffffff" href="{{ $resetUrl }}" target="_blank">Reset Password</a></p>
    {!! $belowBody !!}
@endsection
