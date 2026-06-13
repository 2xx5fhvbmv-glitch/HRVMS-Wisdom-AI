@php
    $waiInsights = $waiInsights ?? [];
    $waiRows = [
        ['key' => 'weekly_hours', 'label' => "Employees who's number of Weekly Working Hours Exceeded"],
        ['key' => 'overtime',     'label' => 'Excessive Overtime Hours'],
        ['key' => 'no_break',     'label' => 'Mandatory Break Not Taken'],
        ['key' => 'day_off',      'label' => 'Accumulated Day-Off Balances Exceeding Limits'],
    ];
@endphp

<style>
    /* Match the leave module's WAI Insight's card: fixed shell, scrolling list. */
    .card-wiINsight { display: flex; flex-direction: column; }
    .card-wiINsight .leaveUser-main { flex: 1 1 auto; min-height: 0; overflow-y: auto; }
    .card-wiINsight .wai-count { font-size: 20px; font-weight: 600; line-height: 1; }
</style>

<div class="card card-wiINsight">
    <div class="card-title d-flex justify-content-between align-items-start">
        <h3>WAI Insight's</h3>
    </div>
    <div class="leaveUser-main">
        @foreach ($waiRows as $row)
            @php $insight = $waiInsights[$row['key']] ?? ['count' => 0, 'employees' => []]; @endphp
            <div class="leaveUser-block">
                <div class="img">
                    <img src="{{ URL::asset('resorts_assets/images/wisdom-ai-small.svg') }}" alt="image">
                </div>
                <div class="flex-grow-1">
                    <h6>{{ $row['label'] }}</h6>
                    <p class="mb-0">{{ $insight['count'] }} {{ $insight['count'] == 1 ? 'employee' : 'employees' }} flagged</p>
                    @if ($insight['count'] > 0)
                        <a href="javascript:void(0)" class="a-linkTheme wai-view-all"
                           data-wai-key="{{ $row['key'] }}" data-wai-title="{{ $row['label'] }}"
                           data-bs-toggle="modal" data-bs-target="#waiInsightModal">View Details</a>
                    @endif
                </div>
                <div class="text-end">
                    <span class="wai-count {{ $insight['count'] > 0 ? 'text-danger' : 'text-muted' }}">{{ sprintf('%02d', $insight['count']) }}</span>
                </div>
            </div>
        @endforeach
    </div>
</div>

{{-- WAI Insight's details modal --}}
<div class="modal fade" id="waiInsightModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="waiInsightModalTitle">WAI Insight's</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="list-unstyled mb-0" id="waiInsightModalBody"></ul>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var waiData = @json($waiInsights);

        function escapeHtml(value) {
            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        document.querySelectorAll('.wai-view-all').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var key = this.getAttribute('data-wai-key');
                var title = this.getAttribute('data-wai-title');
                var list = (waiData[key] && waiData[key].employees) ? waiData[key].employees : [];

                var titleEl = document.getElementById('waiInsightModalTitle');
                var bodyEl = document.getElementById('waiInsightModalBody');
                if (titleEl) titleEl.textContent = title;
                if (!bodyEl) return;

                if (!list.length) {
                    bodyEl.innerHTML = '<li class="text-muted">No employees to show.</li>';
                    return;
                }

                bodyEl.innerHTML = list.map(function (emp) {
                    return '<li class="d-flex align-items-center justify-content-between mb-3">'
                        + '<div class="d-flex align-items-center gap-2">'
                        + '<div class="img-circle"><img src="' + escapeHtml(emp.photo) + '" alt="" width="36" height="36" style="border-radius:50%;object-fit:cover;"></div>'
                        + '<span>' + escapeHtml(emp.name) + '</span>'
                        + '</div>'
                        + '<small class="text-muted">' + escapeHtml(emp.detail) + '</small>'
                        + '</li>';
                }).join('');
            });
        });
    })();
</script>
