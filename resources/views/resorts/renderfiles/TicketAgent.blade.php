<div class="col-12">
    <ol>

        @if( $TicketAgent->isNotEmpty())
            @foreach ($TicketAgent as  $v)


                <li><a href="mailto:{{ $v }}" class="a-link">{{ $v }}</a></li>

            @endforeach
       @endif

    </ol>
</div>
