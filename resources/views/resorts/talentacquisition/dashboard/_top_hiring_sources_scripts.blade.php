{{--
    Script half of _top_hiring_sources.blade.php, split out so it loads
    after jQuery instead of inline in the content body. The markup/style
    partial is @include'd early in the page (inside the card grid), but its
    <script> tag used to render at that same spot too — before this app's
    jQuery <script src> (which loads late, in import-scripts), so `$` was
    undefined the instant the browser parsed it. Include this file from
    each dashboard's own @section('import-scripts') instead, after jQuery
    has already loaded. Behaviour/markup/AJAX endpoint are unchanged.
--}}
@once
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
