@php
    $maxShow = 5;
    $employees = $bucket['employees'];
    $visible = $employees->take($maxShow);
    $extra = max(0, $employees->count() - $maxShow);
@endphp
<div class="analytics-bucket {{ $textCls }}" style="background: {{ $bg }};">
    <h4>{{ $bucket['label'] }}</h4>
    <div class="meta">{{ $bucket['percent'] }}% – {{ $bucket['count'] }} {{ $bucket['count'] == 1 ? 'Employee' : 'Employees' }}</div>
    <div class="analytics-avatars">
        @foreach($visible as $e)
            <img src="{{ $e->profileImg }}" alt="{{ $e->name }}" title="{{ $e->name }}{{ $e->rating !== null ? ' — '.$e->rating.' / 5' : '' }}">
        @endforeach
        @if($extra > 0)
            <span class="more">+{{ $extra }}</span>
        @endif
    </div>
</div>
