@php
    $waiInsights = $waiInsights ?? [];
    $waiRows = [
        ['key' => 'weekly_hours', 'label' => "Employees who's number of Weekly Working Hours Exceeded"],
        ['key' => 'overtime',     'label' => 'Excessive Overtime Hours'],
        ['key' => 'no_break',     'label' => 'Mandatory Break Not Taken'],
        ['key' => 'day_off',      'label' => 'Accumulated Day-Off Balances Exceeding Limits'],
    ];
@endphp

<div class="card">
    <div class="card-title d-flex justify-content-between">
        <h3>WAI Insight's</h3>
    </div>
    <div>
        @foreach ($waiRows as $row)
            @php $insight = $waiInsights[$row['key']] ?? ['count' => 0, 'employees' => []]; @endphp
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <p class="mb-0">{{ $row['label'] }}</p>
                <span class="d-inline-flex align-items-center gap-2">
                    <span class="d-inline-block text-end {{ $insight['count'] > 0 ? 'text-danger' : '' }}">{{ sprintf('%02d', $insight['count']) }}</span>
                    @if ($insight['count'] > 0)
                        <a href="javascript:void(0)" class="wai-view-all" title="View all details"
                           data-wai-key="{{ $row['key'] }}" data-wai-title="{{ $row['label'] }}"
                           data-bs-toggle="modal" data-bs-target="#waiInsightModal">
                            <i class="fa-regular fa-eye"></i>
                        </a>
                    @endif
                </span>
            </div>
        @endforeach
    </div>
</div>

{{-- WAI Insight's details modal --}}
<div class="modal fade" id="waiInsightModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
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
