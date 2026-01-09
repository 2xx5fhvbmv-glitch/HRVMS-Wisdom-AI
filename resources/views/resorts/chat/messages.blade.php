@foreach ($messages as $msg)
    @php
        $isSender = $msg->sender_id == $resort->id;
    @endphp

    <div class="message-wrapper mb-3 {{ $isSender ? 'sent-message' : '' }}">
        <div class="message {{ $isSender ? 'sent' : 'received' }}">
            <div class="message-content">{{ $msg->message }}</div>
            <div class="message-time">{{ $msg->created_at->format('h:i A') }}</div>
        </div>
    </div>
@endforeach
