{{-- Zero-as-dash money cell. Presentational only — never changes the
     underlying value, only how a literal 0 displays. --}}
@if ((float) $value == 0)
    <span class="cb-zero-dash">&mdash;</span>
@else
    {!! Common::formatCurrency($value, 'USD') !!}
@endif
