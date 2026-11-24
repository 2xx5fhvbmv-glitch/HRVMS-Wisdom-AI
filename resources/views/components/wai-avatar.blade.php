@props(['photo' => null, 'name' => '', 'gender' => null, 'id' => null, 'size' => null])
@php
    // Same rules as wavKind() / wavTint() in resorts/partials/wai-avatar.blade.php.
    $g = strtolower(trim((string) $gender));
    $kind = in_array($g, ['m', 'male']) ? 'm' : (in_array($g, ['f', 'female']) ? 'f' : 'n');
    // Hash UTF-16 code units exactly like wavTint() in JS, so an accented name gets the same tint on both sides.
    $h = 0;
    foreach (unpack('v*', mb_convert_encoding((string) $id, 'UTF-16LE', 'UTF-8')) ?: [] as $c) {
        $h = ($h * 31 + $c) & 0xFFFFFFFF;
        if ($h >= 0x80000000) { $h -= 0x100000000; }
    }
    $tint = $kind === 'n' ? '#E6F0F1' : ['#E3EEF7', '#EEF4E2', '#E6F0F1', '#F3EBDA', '#F6E7EE'][abs($h) % 5];
    // The app's generic default picture counts as "no photo" so the drawn portrait shows instead.
    if ($photo && preg_match('#admin_assets/files/(user-image\.png|default-pic\.jpg)#', (string) $photo)) { $photo = null; }
@endphp
<span class="wav" style="--wb:{{ $tint }}@if($size);width:{{ (int) $size }}px;height:{{ (int) $size }}px @endif"><svg aria-hidden="true"><use href="#wav-{{ $kind }}"/></svg>@if($photo)<img src="{{ $photo }}" alt="{{ $name }}" loading="lazy" decoding="async" onerror="this.remove()">@endif</span>
