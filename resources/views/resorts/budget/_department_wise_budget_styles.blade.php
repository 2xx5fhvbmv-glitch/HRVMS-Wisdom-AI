{{--
    Department Wise View Budget — shared chrome for the frozen-column,
    sticky-header/footer budget grid. One include, reused by
    resources/views/resorts/budget/view.blade.php every time this screen
    renders (once per department navigation — there is no per-page
    multi-department list, each department is its own page load).

    Classes are prefixed `dwb-` throughout to avoid collision with the
    generic `.card`/`.badge`/`.money` names already used elsewhere in this
    large app. Colours reuse existing design tokens
    (_design_tokens.blade.php) — no new palette introduced.
--}}
<style>
    .dwb-card{ width:100%; background:#fff; border:1px solid var(--line); border-radius:16px;
        box-shadow:var(--shadow); padding:18px 20px 16px; }
    .dwb-hd{ display:flex; align-items:center; gap:11px; margin-bottom:14px; flex-wrap:wrap; }
    .dwb-hd .dwb-dept{ font-size:18px; font-weight:600; color:var(--ink); }
    .dwb-hd .dwb-badge{ display:inline-flex; align-items:center; gap:7px; font-size:11px;
        color:var(--teal); background:var(--teal-3); border-radius:20px; padding:5px 13px; }
    .dwb-hd .dwb-badge .k{ color:var(--muted); font-weight:600; text-transform:uppercase; letter-spacing:.3px; }
    .dwb-hd .dwb-badge b{ font-size:14px; font-weight:500; }
    .dwb-hd .dwb-sp{ flex:1; }
    .dwb-btn-compare{ background:var(--teal); color:#fff; border:1px solid var(--teal); font-size:14px;
        font-weight:500; border-radius:8px; padding:8px 18px; }
    .dwb-btn-compare:hover{ background:var(--teal-2); color:#fff; }

    .dwb-wrap{ overflow:auto; max-height:66vh; border:1px solid var(--line); border-radius:12px; }
    .dwb-wrap::-webkit-scrollbar{ width:11px; height:11px; }
    .dwb-wrap::-webkit-scrollbar-thumb{ background:var(--neutral-bg); border-radius:6px; border:2px solid #fff; }
    .dwb-table{ border-collapse:separate; border-spacing:0; width:max-content; font-size:14px; }
    .dwb-table th, .dwb-table td{ padding:13px 16px; white-space:nowrap; background:#fff; text-align:left;
        vertical-align:middle; height:50px; }
    .dwb-table tbody td{ border-bottom:1px solid var(--line-2); }

    .dwb-table thead th{ position:sticky; top:0; z-index:3; background:var(--teal-soft); color:var(--muted);
        font-size:11px; font-weight:600; letter-spacing:.4px; text-transform:uppercase; border-bottom:1px solid var(--line); }
    .dwb-table th.num, .dwb-table td.num{ text-align:right; font-variant-numeric:tabular-nums; }

    {{--
        default.css carries an older #filled-positions-table block (nth-child
        sticky offsets, table-layout:fixed, text-align:left, width:180px) built
        for this same table's previous nested-table markup — still relied on by
        sibling views (view1.blade.php, view_budget_all_dp.blade.php) that keep
        that id and that layout. This table uses id="dwb-positions-table"
        instead so none of that legacy CSS matches it, rather than editing a
        shared stylesheet two other pages still depend on.
    --}}
    .dwb-table td.col-act, .dwb-table th.col-act{ position:sticky; left:0;
        width:96px; min-width:96px; max-width:96px; padding-left:12px; padding-right:8px; }
    .dwb-table .col-pos{ position:sticky; left:96px; width:214px; min-width:214px; max-width:214px;
        box-shadow:6px 0 10px -6px rgba(1,70,83,.14); border-right:1px solid var(--line); }
    .dwb-table tbody .col-act, .dwb-table tbody .col-pos{ z-index:2 !important; background:#fff; }
    .dwb-table thead .col-act, .dwb-table thead .col-pos{ z-index:20 !important; }
    {{-- Position is one visual block per group — only the group-boundary
         border-top (below) should divide it, not every row's own border-bottom. --}}
    .dwb-table tbody .col-pos{ border-bottom:none; }

    .dwb-table th.mstart, .dwb-table td.mstart{ width:100px; min-width:100px; max-width:100px;
        padding-left:10px; padding-right:10px; }
    .dwb-table tr.dwb-gf td{ border-top:1px solid var(--line); }
    .dwb-table tr.dwb-gf:first-child td{ border-top:none; }

    .dwb-pos-t{ font-size:14px; font-weight:500; color:var(--ink); white-space:normal; line-height:1.3; }
    .dwb-emp{ font-size:14px; font-weight:500; color:var(--ink); }
    .dwb-rank{ font-size:11px; font-weight:600; letter-spacing:.3px; color:var(--muted); background:var(--line-2);
        border-radius:5px; padding:2px 8px; }
    .dwb-nat{ font-size:14px; color:var(--ink); }
    .dwb-muted{ color:var(--faint); }
    .dwb-no-pos{ display:inline-grid; place-items:center; min-width:24px; height:22px; padding:0 6px;
        border-radius:7px; background:var(--teal-3); color:var(--teal); font-weight:600; font-size:11px; }
    .dwb-money{ font-size:14px; font-weight:600; color:var(--ink); }
    .dwb-money.soft{ font-weight:500; }
    .dwb-money.zero{ color:var(--faint); font-weight:500; }
    .dwb-vac{ display:inline-flex; align-items:center; gap:6px; font-size:11px; font-weight:600;
        color:var(--positive); background:var(--positive-bg); border-radius:20px; padding:3px 11px; }
    .dwb-vac .d{ width:6px; height:6px; border-radius:50%; background:var(--positive); }
    .dwb-est{ font-size:14px; color:var(--positive); font-weight:500; }

    .dwb-iconbtn{ width:28px; height:28px; border-radius:7px; border:1px solid var(--line); display:inline-grid;
        place-items:center; cursor:pointer; background:#fff; transition:background .15s ease; }
    .dwb-iconbtn+.dwb-iconbtn{ margin-left:5px; }
    .dwb-iconbtn.ed{ color:var(--teal); } .dwb-iconbtn.ed:hover{ background:var(--teal-soft); }
    .dwb-iconbtn.ok{ color:#fff; background:var(--positive); border-color:var(--positive); }
    .dwb-iconbtn.x{ color:var(--muted); }
    @media (prefers-reduced-motion: reduce){ .dwb-iconbtn{ transition:none; } }

    .dwb-cin{ width:100%; min-width:64px; font-family:inherit; font-size:14px; color:var(--ink);
        border:1px solid var(--neutral-bg); border-radius:6px; padding:6px 8px; outline:none; background:#fff; }
    .dwb-cin:focus{ border-color:var(--teal); box-shadow:0 0 0 2px rgba(var(--teal-rgb),.12); }
    .dwb-cin.num{ text-align:right; width:92px; }
    .dwb-month-cell .dwb-cin.num{ width:76px; }
    .dwb-table tr.dwb-editing td{ background:var(--teal-soft); }
    .dwb-table tr.dwb-editing .col-act, .dwb-table tr.dwb-editing .col-pos{ background:var(--teal-soft); }

    .dwb-edit{ display:none; }
    .dwb-table tr.dwb-editing .dwb-view{ display:none; }
    .dwb-table tr.dwb-editing .dwb-edit{ display:inline-block; }
    .dwb-act-view, .dwb-act-edit{ display:inline-flex; align-items:center; }
    .dwb-act-edit{ display:none; }
    .dwb-table tr.dwb-editing .dwb-act-view{ display:none; }
    .dwb-table tr.dwb-editing .dwb-act-edit{ display:inline-flex; }

    .dwb-table tfoot th, .dwb-table tfoot td{ position:sticky; bottom:0; z-index:3; background:var(--teal-3); font-weight:700;
        color:var(--ink); border-top:1px solid var(--neutral-bg); text-align:left; }
    .dwb-table tfoot .col-act, .dwb-table tfoot .col-pos{ z-index:20 !important; background:var(--teal-3); }
    .dwb-table tfoot .lbl{ font-size:11px; letter-spacing:.4px; text-transform:uppercase; color:var(--teal); }
</style>
