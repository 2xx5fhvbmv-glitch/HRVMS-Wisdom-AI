{{-- Flight-ticket tracking: HR's Yes/No for the employee's current Annual Leave cycle, editable within the cycle.
     Needs $ticket (Common::annualTicketState) and $ticketName (employee's name). Saves via leave.annualTickets.store. --}}
<div class="annual-ticket d-flex flex-wrap align-items-center gap-2" data-employee-id="{{ $ticket['employee_id'] }}">
    <span class="annual-ticket-q">
        Has the company provided {{ $ticketName }}’s round-trip ticket for the current cycle
        ({{ \Carbon\Carbon::parse($ticket['cycle_start'])->format('d M Y') }} – {{ \Carbon\Carbon::parse($ticket['cycle_end'])->format('d M Y') }})?
    </span>
    <span class="btn-group btn-group-sm" role="group" aria-label="Ticket provided">
        <button type="button" class="btn btn-outline-success annual-ticket-answer {{ $ticket['company_provided'] === true ? 'active' : '' }}" data-value="1" aria-pressed="{{ $ticket['company_provided'] === true ? 'true' : 'false' }}">Yes</button>
        <button type="button" class="btn btn-outline-danger annual-ticket-answer {{ $ticket['company_provided'] === false ? 'active' : '' }}" data-value="0" aria-pressed="{{ $ticket['company_provided'] === false ? 'true' : 'false' }}">No</button>
    </span>
    <small class="text-muted annual-ticket-meta">
        @if($ticket['company_provided'] !== null)
            Answered{{ $ticket['decided_by'] ? ' by ' . $ticket['decided_by'] : '' }}{{ $ticket['decided_at'] ? ' on ' . \Carbon\Carbon::parse($ticket['decided_at'])->format('d M Y') : '' }}. You can change it until the cycle ends.
        @else
            Not answered yet.
        @endif
    </small>
</div>
@include('resorts.leaves.partials.annual-ticket-script')
