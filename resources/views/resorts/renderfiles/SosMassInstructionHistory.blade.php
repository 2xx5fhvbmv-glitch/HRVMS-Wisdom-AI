<div class="sosMassInstruction-history">
    @if($history->isNotEmpty())
        @foreach($history as $item)
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <p class="mb-0">{{ $item->message }}</p>
                    <span class="text-muted small">{{ optional($item->sender)->full_name ?? 'N/A' }}</span>
                </div>
                <span class="text-muted small">{{ \Carbon\Carbon::parse($item->created_at)->format('d M Y, h:i A') }}</span>
            </div>
        @endforeach
    @else
        <p class="text-center text-muted">No instructions sent yet.</p>
    @endif
</div>
