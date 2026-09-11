{{--
    WISDOM AI — canonical pop-up calendar / date picker (shared, app-wide).
    Pairs with resorts._datepicker_calendar_styles. Include both once per
    page (regardless of how many calendar instances it has), then wire
    each instance explicitly — a calendar carries its own per-instance
    state (which month is showing, what's selected), so unlike the
    dropdown component this isn't a class-based auto-scan; each caller
    creates its own instance against its own elements.

    Usage:
        var picker = window.wisdomDatepicker.create({
            monthEl: document.getElementById('holdCalMonth'),
            gridEl:  document.getElementById('holdCalGrid'),
            prevEl:  document.getElementById('holdCalPrev'),   // optional
            nextEl:  document.getElementById('holdCalNext'),   // optional
            onSelect: function (isoDate, dateObj) { ... }       // fires on day click
        });
        picker.reset();          // clears selection, jumps back to today's month
        picker.getSelected();    // 'YYYY-MM-DD' or null
--}}
<script>
window.wisdomDatepicker = (function () {
    var DOWS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    var MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    function pad(n) { return n < 10 ? '0' + n : '' + n; }
    // y = full year, m = 0-based month (matches Date#getMonth), d = day of month
    function toIso(y, m, d) { return y + '-' + pad(m + 1) + '-' + pad(d); }

    function create(opts) {
        var view = new Date();
        view.setDate(1);
        var selectedIso = null;

        function render() {
            var year = view.getFullYear();
            var month = view.getMonth();
            if (opts.monthEl) opts.monthEl.textContent = MONTHS[month] + ' ' + year;

            var firstDow = new Date(year, month, 1).getDay();
            var daysInMonth = new Date(year, month + 1, 0).getDate();
            var prevDaysInMonth = new Date(year, month, 0).getDate();
            var today = new Date();
            var todayIso = toIso(today.getFullYear(), today.getMonth(), today.getDate());

            var cells = [];
            for (var lead = firstDow - 1; lead >= 0; lead--) {
                cells.push({ day: prevDaysInMonth - lead, mut: true });
            }
            for (var d = 1; d <= daysInMonth; d++) {
                cells.push({ day: d, mut: false, iso: toIso(year, month, d) });
            }
            var trailDay = 1;
            while (cells.length < 42) {
                cells.push({ day: trailDay++, mut: true });
            }

            var html = DOWS.map(function (dow) { return '<div class="wcal-dow">' + dow + '</div>'; }).join('');
            html += cells.map(function (c) {
                if (c.mut) return '<div class="wcal-d wcal-mut">' + c.day + '</div>';
                var cls = 'wcal-d';
                if (c.iso === todayIso) cls += ' wcal-today';
                if (c.iso === selectedIso) cls += ' wcal-sel';
                return '<div class="' + cls + '" data-iso="' + c.iso + '">' + c.day + '</div>';
            }).join('');

            opts.gridEl.innerHTML = html;
        }

        opts.gridEl.addEventListener('click', function (e) {
            var cell = e.target.closest('.wcal-d');
            if (!cell || cell.classList.contains('wcal-mut')) return;
            selectedIso = cell.getAttribute('data-iso');
            render();
            if (typeof opts.onSelect === 'function') {
                var parts = selectedIso.split('-');
                opts.onSelect(selectedIso, new Date(+parts[0], +parts[1] - 1, +parts[2]));
            }
        });

        if (opts.prevEl) {
            opts.prevEl.addEventListener('click', function () {
                view.setMonth(view.getMonth() - 1);
                render();
            });
        }
        if (opts.nextEl) {
            opts.nextEl.addEventListener('click', function () {
                view.setMonth(view.getMonth() + 1);
                render();
            });
        }

        render();

        return {
            reset: function () {
                view = new Date();
                view.setDate(1);
                selectedIso = null;
                render();
            },
            getSelected: function () { return selectedIso; }
        };
    }

    return { create: create };
})();
</script>
