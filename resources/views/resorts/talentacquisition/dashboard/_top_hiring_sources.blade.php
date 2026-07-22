{{--
    Shared "Top Hiring Sources" card — admin/hr dashboards both @include this.
    Reuses the existing resort.ta.topHiringSources AJAX endpoint and its
    response shape (per-source monthly counts for one year) unchanged; the
    Period filter (Full Year / Last Quarter / Last 6 Months / a single
    month) is applied entirely client-side by summing whichever months of
    the already-fetched year are in scope, so switching Period never needs
    another request. The Vacancy filter does need a new request (there's no
    per-vacancy breakdown in a single year-fetch), so it re-calls the same
    endpoint with an added optional vacancy_id param
    (see TalentAcquisitionDashboardController::topHiringSources()).

    Vacancy options come from $NewVacancies — already fetched by
    admin_dashboard()/hr_dashboard() for the Open Vacancies table above, no
    new query for the dropdown either. Markup + JS are both here (behind
    @once) so including this partial from two dashboards never duplicates
    the script.
--}}
<div class="card card-topHiring topHiring-v2 ta-toprow-card">
    <div class="card-title">
        <div class="row justify-content-between align-items-start g-2">
            <div class="col">
                <h3 class="text-nowrap">Top Hiring Sources</h3>
                <div class="topHiring-summary" id="topHiringSummary">&nbsp;</div>
            </div>
        </div>
        <div class="row g-2 topHiring-filters">
            <div class="col-sm-6">
                <label class="topHiring-filter-label" for="topHiringPeriod">Period</label>
                <select class="form-select topHiring-period" id="topHiringPeriod" aria-label="Period">
                    <option value="full_year" selected>Full Year</option>
                    <option value="last_quarter">Last Quarter</option>
                    <option value="last_6_months">Last 6 Months</option>
                    @php
                        $thMonthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
                    @endphp
                    @foreach($thMonthNames as $thMonthIndex => $thMonthName)
                        <option value="{{ $thMonthIndex + 1 }}">{{ $thMonthName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6">
                <label class="topHiring-filter-label" for="topHiringVacancy">Vacancy</label>
                <select class="form-select topHiring-vacancy" id="topHiringVacancy" aria-label="Vacancy">
                    <option value="" selected>All vacancies</option>
                    @if(isset($NewVacancies))
                        @foreach($NewVacancies as $vac)
                            <option value="{{ $vac->vacancy_id }}">{{ $vac->positionTitle }} &middot; {{ $vac->Department }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
        </div>
    </div>

    <div class="topHiring-list" id="topHiringList"></div>

    <div class="ta-chart-empty d-none" id="topHiringEmptyState">
        <i class="fa-regular fa-chart-line"></i>
        <p>No applicants from tracked sources for this selection.</p>
    </div>
</div>

@once
<style>
    .topHiring-v2 .topHiring-summary {
        font-size: 13px;
        color: #5D6F75;
        margin-top: 2px;
    }
    .topHiring-filters {
        margin-top: 14px;
    }
    .topHiring-filter-label {
        display: block;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: #93A4A9;
        margin-bottom: 4px;
    }
    .topHiring-list {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        padding-right: 4px;
        margin-top: 16px;
    }
    .ta-chart-empty {
        flex: 1 1 auto;
        min-height: 0;
    }
    .ths-row {
        padding: 10px 0;
        border-bottom: 1px solid #E7E7E7;
    }
    .ths-row:first-child { padding-top: 0; }
    .ths-row:last-child { border-bottom: 0; padding-bottom: 0; }
    .ths-row-top {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 8px;
    }
    .ths-rank {
        width: 16px;
        flex-shrink: 0;
        font-size: 13px;
        color: #93A4A9;
        font-weight: 600;
    }
    .ths-icon-tile {
        width: 30px;
        height: 30px;
        min-width: 30px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 14px;
        font-weight: 600;
        flex-shrink: 0;
    }
    .ths-icon-tile-globe { background: #5D6F75; }
    .ths-name {
        flex: 1 1 auto;
        font-weight: 600;
        color: #14232A;
        font-size: 14px;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .ths-count {
        font-weight: 700;
        color: #14232A;
        font-variant-numeric: tabular-nums;
        flex-shrink: 0;
    }
    .ths-pct {
        font-weight: 500;
        color: #5D6F75;
        font-variant-numeric: tabular-nums;
        min-width: 52px;
        text-align: right;
        flex-shrink: 0;
    }
    .ths-bar-track {
        height: 6px;
        border-radius: 6px;
        background: #F1F7F7;
        overflow: hidden;
        margin-left: 40px;
    }
    .ths-bar-fill {
        height: 100%;
        border-radius: 6px;
    }
</style>

<script>
    (function () {
        var THS_KNOWN_PLATFORMS = {
            'linkedin': { icon: 'fa-linkedin', color: '#0A66C2' },
            'facebook': { icon: 'fa-facebook-f', color: '#1877F2' },
            'instagram': { icon: 'fa-instagram', color: '#C13584' },
            'twitter': { icon: 'fa-x-twitter', color: '#000000' },
            'x': { icon: 'fa-x-twitter', color: '#000000' },
            'tiktok': { icon: 'fa-tiktok', color: '#000000' },
            'youtube': { icon: 'fa-youtube', color: '#FF0000' },
            'whatsapp': { icon: 'fa-whatsapp', color: '#25D366' },
            'google': { icon: 'fa-google', color: '#4285F4' }
        };
        var THS_TEAL_PALETTE = ['#014653', '#0E8A9E', '#2EACB3', '#4A5F8A', '#5D6F75'];
        var THS_DOMAIN_RE = /^[a-z0-9-]+(\.[a-z0-9-]+)+$/i;

        function thsEscapeHtml(str) {
            return $('<div>').text(str == null ? '' : String(str)).html();
        }

        function thsHashColor(name) {
            var hash = 0;
            for (var i = 0; i < name.length; i++) {
                hash = name.charCodeAt(i) + ((hash << 5) - hash);
            }
            return THS_TEAL_PALETTE[Math.abs(hash) % THS_TEAL_PALETTE.length];
        }

        function thsSourceIconHtml(sourceName) {
            var name = (sourceName || '').trim();
            var known = THS_KNOWN_PLATFORMS[name.toLowerCase()];
            if (known) {
                return '<span class="ths-icon-tile" style="background:' + known.color + ';"><i class="fa-brands ' + known.icon + '"></i></span>';
            }
            if (THS_DOMAIN_RE.test(name)) {
                return '<span class="ths-icon-tile ths-icon-tile-globe"><i class="fa-solid fa-globe"></i></span>';
            }
            var initial = name.charAt(0).toUpperCase() || '?';
            return '<span class="ths-icon-tile" style="background:' + thsHashColor(name) + ';">' + thsEscapeHtml(initial) + '</span>';
        }

        function thsMonthIndicesForPeriod(period) {
            var now = new Date();
            var currentMonthIndex = now.getMonth(); // 0-based
            if (period === 'last_quarter' || period === 'last_6_months') {
                var span = period === 'last_quarter' ? 3 : 6;
                var start = Math.max(0, currentMonthIndex - span + 1);
                var idx = [];
                for (var i = start; i <= currentMonthIndex; i++) idx.push(i);
                return idx;
            }
            var m = parseInt(period, 10);
            if (!isNaN(m) && m >= 1 && m <= 12) return [m - 1];
            // 'full_year' (or anything unrecognised) = all 12 months
            var all = [];
            for (var j = 0; j < 12; j++) all.push(j);
            return all;
        }

        function thsRenderList($card, response, period) {
            var monthIdx = thsMonthIndicesForPeriod(period);
            var sourceTotals = (response.datasets || []).map(function (ds) {
                var total = 0;
                monthIdx.forEach(function (i) {
                    var v = ds.data && ds.data[i];
                    total += v ? Number(v) : 0;
                });
                return { name: ds.label, color: ds.backgroundColor, total: total };
            }).filter(function (s) { return s.total > 0; });

            sourceTotals.sort(function (a, b) { return b.total - a.total; });

            var grandTotal = sourceTotals.reduce(function (sum, s) { return sum + s.total; }, 0);
            var maxTotal = sourceTotals.length ? sourceTotals[0].total : 0;
            var channelCount = sourceTotals.length;

            var $list = $card.find('.topHiring-list');
            var $empty = $card.find('.ta-chart-empty');

            if (!sourceTotals.length) {
                $card.find('.topHiring-summary').text('No data yet');
                $list.addClass('d-none').empty();
                $empty.removeClass('d-none');
                return;
            }

            $card.find('.topHiring-summary').text(
                grandTotal + ' applicant' + (grandTotal === 1 ? '' : 's') + ' · ' +
                channelCount + ' channel' + (channelCount === 1 ? '' : 's')
            );
            $empty.addClass('d-none');
            $list.removeClass('d-none');

            var html = '';
            sourceTotals.forEach(function (s, i) {
                var pct = grandTotal > 0 ? ((s.total / grandTotal) * 100).toFixed(1) : null;
                var barWidth = maxTotal > 0 ? Math.max(4, (s.total / maxTotal) * 100) : 0;
                html += '<div class="ths-row">'
                    + '<div class="ths-row-top">'
                    + '<span class="ths-rank">' + (i + 1) + '</span>'
                    + thsSourceIconHtml(s.name)
                    + '<span class="ths-name">' + thsEscapeHtml(s.name) + '</span>'
                    + '<span class="ths-count">' + s.total + '</span>'
                    + '<span class="ths-pct">' + (pct !== null ? pct + '%' : '—') + '</span>'
                    + '</div>'
                    + '<div class="ths-bar-track"><div class="ths-bar-fill" style="width:' + barWidth + '%; background:' + (s.color || '#0E8A9E') + ';"></div></div>'
                    + '</div>';
            });
            $list.html(html);
        }

        $(document).ready(function () {
            var $card = $('.topHiring-v2').first();
            if (!$card.length) return;

            var thsLastResponse = null;

            function thsFetch() {
                $.ajax({
                    url: "{{ route('resort.ta.topHiringSources') }}",
                    type: 'post',
                    data: {
                        "_token": "{{ csrf_token() }}",
                        "YearWiseTopSource": new Date().getFullYear(),
                        "vacancy_id": $card.find('.topHiring-vacancy').val() || ''
                    },
                    success: function (response) {
                        thsLastResponse = response;
                        thsRenderList($card, response, $card.find('.topHiring-period').val());
                    },
                    error: function (xhr) {
                        console.error('Failed to fetch Top Hiring Sources data', xhr);
                    }
                });
            }

            $card.on('change', '.topHiring-period', function () {
                if (thsLastResponse) {
                    thsRenderList($card, thsLastResponse, $(this).val());
                }
            });
            $card.on('change', '.topHiring-vacancy', function () {
                thsFetch();
            });

            thsFetch();
        });
    })();
</script>
@endonce
