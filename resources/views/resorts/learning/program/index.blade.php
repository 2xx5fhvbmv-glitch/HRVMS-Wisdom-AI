@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
    <style>
        #learning-program-index-hero { padding-bottom: 40px; }
        @media (max-width: 575.98px) {
            #learning-program-index-hero { padding-bottom: 0; }
        }
    </style>
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding" id="learning-program-index-hero">
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Learning & Development</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card lp-panel">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center justify-content-end">
                        <div class="col-xl-3 col-lg-5 col-sm-6">
                            <div class="input-group">
                                <input type="search" class="form-control" id="searchInput" placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-4 col-md-5 col-6">
                            <select id="categoryFilter" class="form-select dd-native-select">
                                <option value=""> All Category</option>
                                @if($categories)
                                    @foreach($categories as $category)
                                        <option value="{{$category->id}}">{{$category->category}}</option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="dd" data-target="#categoryFilter">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">All Category</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Category">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a category…"></div>
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">All Category</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @if($categories)
                                            @foreach($categories as $category)
                                                <div class="dd-item" role="option" data-value="{{ $category->id }}"><span class="dd-nm">{{ $category->category }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lp-rows-wrap">
                    <table id="table-LearningProgram" class="table lp-table w-100 mb-0">
                        <thead>
                            <tr>
                                <th>Learning Name</th>
                                <th>Category</th>
                                <th>Target Audience</th>
                                <th>Duration</th>
                                <th>Frequency</th>
                                <th>Delivery Mode</th>
                                <th>Action</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- shared frosted popover for audience overflow -->
    <div class="aud-pop" id="audPop">
        <div class="k"><span class="dot"></span>Audience</div>
        <div class="list" id="audList"></div>
    </div>
@include('resorts._dropdown_styles')
@include('resorts._dropdown_script')
@endsection

@section('import-css')
<style>
    :root {
        --lp-g2: var(--muted, #6B7378);
        --lp-g3: var(--faint, #99A1A5);
        --lp-g4: var(--line, #E2EBEC);
    }

    /* Search + category filter: original .card > .card-header > .row grid with
       plain .input-group/.form-control/.form-select — untouched, matches every
       other list page in the app. Everything below is one .card, like the
       original, so the white surface flows continuously from toolbar to the
       row list to the pagination footer, with rounded corners throughout. */

    /* DataTables chrome reskinned into stacked row-cards. */
    .lp-rows-wrap { background: transparent; }
    /* table.dataTable{border-spacing:0} in dataTables.min.css is element+class
       specificity, beating a bare .lp-table class rule regardless of load order
       — match it with the same element+class qualifier to win. */
    table.lp-table { border-collapse: separate; border-spacing: 0 12px; border-bottom: none; }
    .lp-table thead { display: none; }
    /* border-radius on <tr> is unreliable across browsers even in separate-border
       mode — put the card look (bg/border/radius/shadow) on the <td> instead,
       which clips and rounds properly. dataTables.min.css sets
       table.dataTable>tbody>tr{background-color:transparent} (3 element-parts) —
       harmless now since the row itself no longer carries the background. */
    table.lp-table tbody tr { transition: none; }
    /* default.css has ".table td{border-bottom:1px solid rgb(231,231,231) !important}"
       for ordinary table row separators — our table still carries the bare .table
       class, so it wins on the bottom edge only, and never updates on hover since
       it's untouched by our :hover rule. Match it with !important on every side. */
    table.lp-table tbody tr td.lp-cell { background: #fff; border: 1px solid var(--lp-g4) !important; border-radius: 14px; box-shadow: 0 1px 2px rgba(var(--teal-rgb),.05), 0 8px 24px rgba(var(--teal-rgb),.05); transition: border-color .15s; }
    table.lp-table tbody tr:hover td.lp-cell { border-color: var(--faint, #C7CDCF) !important; }
    .lp-table tbody td { border: none; padding: 0; }
    /* default.css has an app-wide table convention: first/last <td> in a row
       get padding-left/padding-right zeroed (first is !important) so tables
       don't double-pad against the container. Our row has exactly one visible
       column, so it's both first and last — needs an explicit, targeted win. */
    #table-LearningProgram tbody tr td.lp-cell { padding: 18px 20px !important; }

    .prow { display: flex; gap: 24px; align-items: center; }
    .prow .anchor { flex: 1.4; min-width: 0; }
    .prow .pn { font-size: 15px; font-weight: 500; color: var(--ink); letter-spacing: -.01em; }
    .prow .pc { font-size: 12px; color: var(--lp-g2); margin-top: 3px; font-weight: 400; }
    .prow .facets { flex: 2; display: grid; grid-template-columns: 84px 96px 130px 1fr; gap: 0 22px; align-items: center; }
    .facet { min-width: 0; }
    .facet .fl { font-size: 9.5px; font-weight: 600; letter-spacing: .5px; text-transform: uppercase; color: var(--lp-g3); margin-bottom: 6px; white-space: nowrap; }
    .facet .fv { font-size: 12.5px; color: var(--ink); font-weight: 400; }
    .facet.aud .fv { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
    .prow .act { flex: none; }

    .tnum { font-variant-numeric: tabular-nums; }
    .chip { display: inline-flex; align-items: center; font-size: 11px; font-weight: 600; color: var(--ink); background: #fff; border: 1px solid var(--lp-g4); border-radius: 8px; padding: 3px 9px; white-space: nowrap; }
    .chip.more { color: var(--teal); background: var(--teal-3); border-color: transparent; cursor: pointer; position: relative; transition: .15s; }
    .chip.more:hover { background: var(--teal); color: #fff; }
    .mode { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 400; color: var(--ink); }
    .mode .d { width: 7px; height: 7px; border-radius: 50%; }
    .mode.face .d { background: var(--teal); }
    .mode.hybrid .d { background: var(--teal-bright, #2EACB3); }
    .mode.online .d { background: var(--positive); }

    .lp-table .act a.btn-tableIcon { width: 36px; height: 36px; border-radius: 10px; border: 1px solid var(--lp-g4); background: #fff; display: grid; place-items: center; color: var(--lp-g2); transition: .15s; }
    .lp-table .act a.btn-tableIcon:hover { border-color: var(--teal); color: var(--teal); background: var(--teal-soft); }

    /* Frosted-glass audience popover — same material as the WAI recommendation modal */
    .aud-pop { position: fixed; z-index: 1060; width: 230px; border-radius: 22px; padding: 22px 22px 20px;
        background: rgba(255,255,255,.82); backdrop-filter: blur(28px) saturate(160%); -webkit-backdrop-filter: blur(28px) saturate(160%);
        border: 1px solid rgba(255,255,255,.7); box-shadow: 0 24px 70px rgba(var(--teal-rgb),.20), 0 2px 8px rgba(var(--teal-rgb),.06);
        opacity: 0; transform: translateY(8px) scale(.985); pointer-events: none; transition: opacity .28s ease, transform .3s cubic-bezier(.16,1,.3,1); }
    .aud-pop.open { opacity: 1; transform: translateY(0) scale(1); pointer-events: auto; }
    .aud-pop .k { display: flex; align-items: center; gap: 7px; font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .9px; color: var(--lp-g2); margin-bottom: 14px; }
    .aud-pop .k .dot { width: 6px; height: 6px; border-radius: 50%; background: var(--teal); }
    .aud-pop .list { display: flex; flex-direction: column; gap: 11px; }
    .aud-pop .a-row { display: flex; align-items: center; gap: 10px; font-size: 13.5px; color: var(--ink); font-weight: 400; line-height: 1.3; }
    .aud-pop .a-row .b { width: 5px; height: 5px; border-radius: 50%; background: var(--teal); flex: none; opacity: .7; }

    .lp-table .dataTables_empty { padding: 32px 20px; text-align: center; color: var(--lp-g3); font-size: 13px; }

    @media(max-width: 900px) {
        .prow { flex-direction: column; gap: 16px; align-items: stretch; }
        .prow .facets { grid-template-columns: 1fr 1fr; gap: 16px; }
        .prow .act { align-self: flex-end; }
    }
</style>
@endsection

@section('import-scripts')
    <script type="text/javascript">
        var MODE_CLASS = {'face-to-face':'face','hybrid':'hybrid','online':'online'};
        var MODE_LABEL = {'face-to-face':'Face-to-face','hybrid':'Hybrid','online':'Online'};
        var LP_MAX_CHIPS = 1;

        function lpModeTag(m) {
            var cls = MODE_CLASS[m] || 'face';
            var label = MODE_LABEL[m] || (m || '-');
            return '<span class="mode ' + cls + '"><span class="d"></span>' + label + '</span>';
        }

        // target_audience arrives pre-joined as "A, B, C" from the server (same
        // column used by the old plain table) — split back into a list for the
        // chip + popover UI. Assumes department/position/grade/employee labels
        // don't themselves contain the literal ", " separator.
        function lpAudienceCell(joined) {
            if (!joined || joined === 'N/A') return '<span class="fv" style="color:var(--faint)">—</span>';
            var arr = joined.split(', ').filter(Boolean);
            var shown = arr.slice(0, LP_MAX_CHIPS).map(function (a) { return '<span class="chip">' + a + '</span>'; }).join('');
            var rest = arr.slice(LP_MAX_CHIPS);
            var more = rest.length
                ? '<span class="chip more" data-rest="' + encodeURIComponent(JSON.stringify(rest)) + '">+' + rest.length + '</span>'
                : '';
            return shown + more;
        }

        function lpRowCard(row) {
            return '' +
                '<div class="prow">' +
                    '<div class="anchor"><div class="pn">' + (row.name || '') + '</div><div class="pc">' + (row.category || '') + '</div></div>' +
                    '<div class="facets">' +
                        '<div class="facet"><div class="fl">Duration</div><div class="fv tnum">' + (row.duration || '-') + '</div></div>' +
                        '<div class="facet"><div class="fl">Frequency</div><div class="fv" style="text-transform:capitalize">' + (row.frequency || '-') + '</div></div>' +
                        '<div class="facet"><div class="fl">Delivery</div><div class="fv">' + lpModeTag(row.delivery_mode) + '</div></div>' +
                        '<div class="facet aud"><div class="fl">Audience</div><div class="fv">' + lpAudienceCell(row.target_audience) + '</div></div>' +
                    '</div>' +
                    '<div class="act">' + (row.action || '') + '</div>' +
                '</div>';
        }

        $(document).ready(function () {
            getLearningPrograms();

            $('#searchInput, #categoryFilter').on('keyup change', function () {
                getLearningPrograms();
            });
        });

        function getLearningPrograms(){
            $('#table-LearningProgram tbody').empty();
            if ($.fn.DataTable.isDataTable('#table-LearningProgram ')) {
                $('#table-LearningProgram ').DataTable().destroy();
            }
            var programTable = $('#table-LearningProgram').DataTable({
                searching: false,
                ordering: true,
                bLengthChange: false,
                bFilter: true,
                bInfo: true,
                bAutoWidth: false,
                scrollX: false,
                iDisplayLength: 6,
                processing: true,
                serverSide: true,
                order: [[8, 'desc']],
                ajax: {
                    url: '{{ route("learning.programs.list") }}',
                    data: function (d) {
                        d.searchTerm = $('#searchInput').val();
                        d.category = $('#categoryFilter').val();
                    },
                    type: 'GET',
                },
                columns: [
                    { data: 'name', name: 'Learning Name', className: 'lp-cell', render: (data, type, row) => type === 'display' ? lpRowCard(row) : data },
                    { data: 'category', name: 'Category', visible: false },
                    { data: 'target_audience', name: 'Target Audience', visible: false },
                    { data: 'duration', name: 'Duration', visible: false },
                    { data: 'frequency', name: 'Frequency', visible: false },
                    { data: 'delivery_mode', name: 'Delivery Mode', visible: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false, visible: false },
                    { data: 'created_at', visible: false, searchable: false },
                ]
            });
        }

        /* Frosted audience popover — hover + click, positioned to the chip.
           Delegated to document since rows are redrawn by DataTables on every page/search. */
        (function(){
            var pop = document.getElementById('audPop');
            var list = document.getElementById('audList');
            var pinned = false;
            function fill(rest) {
                list.innerHTML = rest.map(function (d) { return '<div class="a-row"><span class="b"></span>' + d + '</div>'; }).join('');
            }
            function place(el) {
                var r = el.getBoundingClientRect();
                pop.style.visibility = 'hidden'; pop.classList.add('open');
                var pw = pop.offsetWidth, ph = pop.offsetHeight;
                var left = r.left + r.width / 2 - pw / 2;
                left = Math.max(12, Math.min(left, window.innerWidth - pw - 12));
                var top = r.bottom + 8;
                if (top + ph > window.innerHeight - 12) top = r.top - ph - 8;
                pop.style.left = left + 'px'; pop.style.top = top + 'px';
                pop.style.visibility = '';
            }
            function open(el) { fill(JSON.parse(decodeURIComponent(el.dataset.rest))); place(el); pop.classList.add('open'); }
            function close() { if (!pinned) pop.classList.remove('open'); }

            $(document).on('mouseenter', '.chip.more', function () { if (!pinned) open(this); });
            $(document).on('mouseleave', '.chip.more', function () { setTimeout(close, 80); });
            $(document).on('click', '.chip.more', function (e) { e.stopPropagation(); pinned = true; open(this); });
            pop.addEventListener('mouseenter', function () { if (!pinned) pop.classList.add('open'); });
            pop.addEventListener('mouseleave', function () { if (!pinned) pop.classList.remove('open'); });
            document.addEventListener('click', function (e) {
                if (!pop.contains(e.target) && !e.target.classList.contains('more')) { pinned = false; pop.classList.remove('open'); }
            });
            window.addEventListener('scroll', function () { pinned = false; pop.classList.remove('open'); }, true);
        })();

        $(document).ready(function () {
            const $userReviewTasksBtn = $(".userReviewTasks-btn");
            const $userReviewTasksWrapper = $(".userReviewTasks-wrapper");

            $userReviewTasksBtn.on("click", function (e) {
                e.stopPropagation();
                $userReviewTasksWrapper.toggleClass("end-0");
            });

            $(document).on("click", function (e) {
                if (
                    !$userReviewTasksWrapper.is(e.target) &&
                    !$userReviewTasksBtn.is(e.target) &&
                    $userReviewTasksWrapper.has(e.target).length === 0 &&
                    $userReviewTasksBtn.has(e.target).length === 0
                ) {
                    $userReviewTasksWrapper.removeClass("end-0");
                }
            });
        });
    </script>
@endsection
