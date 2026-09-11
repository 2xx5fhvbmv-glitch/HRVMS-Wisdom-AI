{{-- Shared design system for the three Shopkeeper screens (Create, View all,
     Payments). One scoped `sk-` prefixed stylesheet included by all three —
     keeps the restyle consistent and means a future change only happens
     once. Uses the app's real design tokens (var(--teal) etc., defined
     globally in resources/views/resorts/layouts/_design_tokens.blade.php)
     with literal fallbacks so this still renders correctly if a token is
     ever renamed. --}}
<style>
.sk-wrap { display: grid; grid-template-columns: 1fr 1.35fr; gap: 16px; align-items: start; }
@media (max-width: 900px) { .sk-wrap { grid-template-columns: 1fr; } }

.sk-card { background: #fff; border-radius: 18px; box-shadow: 0 1px 2px rgba(1,70,83,.05), 0 16px 40px rgba(1,70,83,.10); padding: 22px 24px; }

.sk-ct { display: flex; align-items: center; gap: 11px; margin-bottom: 20px; }
.sk-ct h2 { font-size: 18px; font-weight: 600; color: var(--ink, #14232A); }
.sk-ct .sk-spacer { flex: 1; }
.sk-viewall { display: inline-flex; align-items: center; gap: 5px; color: var(--teal, #014653); font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; }
.sk-viewall svg { width: 14px; height: 14px; }
/* explicit color (not just an omission) — .sk-viewall is an <a>, and the
   app's global default.css ships a bare `a:hover{color:#005AB8}` whose
   specificity (element+pseudo) beats a plain class rule's color unless
   the hover state re-declares it. See .sk-ico:hover below for the fuller
   note; same mechanism, different selector. */
.sk-viewall:hover { color: var(--teal, #014653); text-decoration: underline; }

/* form (Create screen) */
.sk-grid { display: grid; gap: 18px; }
.sk-f label { display: flex; align-items: center; gap: 7px; font-size: 13px; font-weight: 600; letter-spacing: .2px; text-transform: uppercase; color: #6B7378; margin-bottom: 8px; }
.sk-f label .sk-req { color: #C7CDCF; font-weight: 400; }
.sk-inp { width: 100%; border: 1px solid var(--line, #EEF2F2); border-radius: 12px; padding: 13px 15px; font: inherit; font-size: 15px; color: var(--ink, #14232A); background: #fff; outline: none; transition: border-color .14s, box-shadow .14s; }
.sk-inp::placeholder { color: #99A1A5; }
.sk-inp:focus { border-color: #cfe0e1; box-shadow: 0 0 0 3px rgba(1,70,83,.05); }
.sk-inp.error { border-color: #B4462F; }
.sk-err { color: #B4462F; font-size: 12px; margin-top: 6px; display: none; }
.sk-err.show { display: block; }
/* jQuery Validate's own default errorClass ("error") + generated
   <label class="error"> for #shopkeeperForm — no app-wide .error style
   exists for it today, so this was rendering as unstyled browser-default
   text before the restyle too. Scoped to this form only. */
#shopkeeperForm label.error { color: #B4462F; font-size: 12px; font-weight: 500; text-transform: none; margin-top: 6px; display: block; }

.sk-cfoot { display: flex; justify-content: flex-end; margin-top: 24px; padding-top: 22px; border-top: 1px solid var(--line, #EEF2F2); }
.sk-submit { background: var(--teal, #014653); color: #fff; border: none; border-radius: 12px; padding: 14px 34px; font: inherit; font-size: 15px; font-weight: 600; cursor: pointer; transition: transform .16s cubic-bezier(.34,1.56,.64,1), box-shadow .16s ease, background .16s ease; }
.sk-submit:hover { background: var(--teal-2, #035b6c); transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.35); }
.sk-submit:active { transition-duration: .07s; transform: translateY(0) scale(.97); }
.sk-submit:disabled { opacity: .6; cursor: default; transform: none; box-shadow: none; }
@media (prefers-reduced-motion: reduce) { .sk-submit, .sk-viewall, .sk-ico, .sk-pribtn, .sk-okbtn, .sk-pg { transition: none; } }

/* table (shared shape, doubled class + !important on padding — the app's
   own default.css ships ".table thead th{padding:0 10px 12px !important}"
   / ".table tbody td{padding:16px 10px !important}" plus a first-child
   padding-left:0 reset, all !important, meant for other DataTables-style
   tables app-wide. Matching !important here, scoped to .sk-tbl only, is
   the only way to win without touching that shared file. */
.sk-tbl { width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid var(--line, #EEF2F2); border-radius: 14px; overflow: hidden; }
.sk-tbl.sk-tbl th { background: var(--teal-soft, #F5F8F8); text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; color: #6B7378; padding: 12px 15px !important; border-bottom: 1px solid var(--line, #EEF2F2); white-space: nowrap; }
.sk-tbl.sk-tbl td { padding: 12px 15px !important; border-bottom: 1px solid #F3F6F6; font-size: 14px; color: #3A4145; vertical-align: middle; }
.sk-tbl.sk-tbl th:first-child, .sk-tbl.sk-tbl td:first-child { padding-left: 15px !important; }
.sk-tbl.sk-tbl tr:last-child td { border-bottom: none; }
.sk-tbl .sk-idx { color: #93A4A9; width: 34px; font-variant-numeric: tabular-nums; }
.sk-tbl .sk-num { font-variant-numeric: tabular-nums; }
.sk-tbl .sk-email { color: #6B7378; }
.sk-tbl .sk-act { text-align: right; }
.sk-tbl tbody tr:hover td { background: #fcfdfd; }
.sk-tbl tr.sk-editing td { background: var(--teal-soft, #F5F8F8) !important; }
.sk-empty { padding: 34px 14px; text-align: center; color: #99A1A5; font-size: 13.5px; }

/* sortable header (View all screen) */
.sk-th-sort { display: inline-flex; align-items: center; gap: 5px; cursor: pointer; user-select: none; }
.sk-th-sort .sk-chev { width: 12px; height: 12px; opacity: 0; transition: opacity .12s, transform .12s; }
.sk-tbl th:hover .sk-th-sort .sk-chev { opacity: .5; }
.sk-th-sort.asc .sk-chev, .sk-th-sort.desc .sk-chev { opacity: 1; color: var(--teal, #014653); }
.sk-th-sort.desc .sk-chev { transform: rotate(180deg); }

/* photo-first avatar with initials fallback — fallback painted first,
   photo second, so a loaded photo naturally covers the initials and an
   onerror-removed <img> reveals them underneath (never the reverse, or
   the fallback permanently hides a working photo). */
.sk-nm { display: flex; align-items: center; gap: 11px; min-width: 0; }
.sk-av { position: relative; width: 34px; height: 34px; flex: none; border-radius: 50%; background: var(--teal-soft, #F5F8F8); overflow: hidden; }
.sk-av-fallback { position: absolute; inset: 0; display: grid; place-items: center; color: var(--teal, #014653); font-size: 12px; font-weight: 600; }
.sk-av img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.sk-nm .sk-t { font-weight: 600; color: var(--ink, #14232A); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* inline edit + delete-confirm action icons */
.sk-cellinp { width: 100%; border: 1px solid var(--line, #EEF2F2); border-radius: 8px; padding: 8px 10px; font: inherit; font-size: 14px; color: var(--ink, #14232A); background: #fff; outline: none; transition: border-color .14s, box-shadow .14s; }
.sk-cellinp:focus { border-color: #cfe0e1; box-shadow: 0 0 0 3px rgba(1,70,83,.05); }
.sk-acts { display: inline-flex; gap: 7px; justify-content: flex-end; align-items: center; }
.sk-ico { width: 34px; height: 34px; border-radius: 10px; border: 1px solid var(--line, #EEF2F2); background: #fff; color: #6B7378; display: inline-grid; place-items: center; cursor: pointer; text-decoration: none; transition: border-color .14s, color .14s, background .14s; }
.sk-ico svg { width: 16px; height: 16px; }
/* .sk-ico.view is an <a> — a global a:hover{color:...} rule (element +
   pseudo-class, higher specificity than the plain .sk-ico class alone)
   was winning and showing the browser/app default link-blue on hover.
   This re-asserts the neutral resting color for every icon button
   regardless of tag; the more specific .sk-ico.del:hover / .confirm-del
   rule below still correctly overrides it for the one variant that's
   meant to change (destructive = red, matching the app's existing
   Critical button convention). */
.sk-ico:hover { color: #6B7378; }
.sk-ico.view:hover, .sk-ico.edit:hover { border-color: #cfe0e1; background: var(--teal-soft, #F5F8F8); }
.sk-ico.del:hover, .sk-ico.confirm-del { border-color: #f0d5d1; color: #B4462F; background: #fbeceb; }
.sk-ico.save { border-color: var(--teal, #014653); background: var(--teal, #014653); color: #fff; }
.sk-ico.save:hover { background: var(--teal-2, #035b6c); border-color: var(--teal-2, #035b6c); }
.sk-ico.cancel:hover, .sk-ico.cancel-del:hover { border-color: #C7CDCF; }
.sk-delq { color: #B4462F; font-size: 12.5px; font-weight: 600; margin-right: 2px; }

/* toolbar (View all + Payments) */
.sk-toolbar { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.sk-search { position: relative; flex: 1; min-width: 180px; max-width: 320px; }
.sk-search input { width: 100%; border: 1px solid var(--line, #EEF2F2); border-radius: 12px; padding: 11px 14px 11px 40px; font: inherit; font-size: 14px; color: var(--ink, #14232A); background: #fff; outline: none; transition: border-color .14s, box-shadow .14s; }
.sk-search input::placeholder { color: #99A1A5; }
.sk-search input:focus { border-color: #cfe0e1; box-shadow: 0 0 0 3px rgba(1,70,83,.05); }
.sk-search svg { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #99A1A5; pointer-events: none; }
.sk-count { color: #6B7378; font-size: 13px; font-weight: 500; white-space: nowrap; }
.sk-pribtn { background: var(--teal, #014653); color: #fff; border: none; border-radius: 12px; padding: 12px 20px; font: inherit; font-size: 14px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; transition: transform .16s cubic-bezier(.34,1.56,.64,1), box-shadow .16s ease, background .16s ease; }
/* .sk-pribtn ("+ Add shopkeeper") is an <a> — same global a:hover{color:
   #005AB8} leak as .sk-viewall/.sk-ico, so color must be re-asserted here
   too, not just background/transform. */
.sk-pribtn:hover { background: var(--teal-2, #035b6c); color: #fff; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.35); }
.sk-pribtn:active { transition-duration: .07s; transform: translateY(0) scale(.97); }
.sk-pribtn svg { width: 16px; height: 16px; }
.sk-ghostbtn { background: #fff; color: #3A4145; border: 1px solid var(--line, #EEF2F2); border-radius: 12px; padding: 11px 16px; font: inherit; font-size: 14px; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; transition: border-color .14s, color .14s; }
.sk-ghostbtn:hover { border-color: #C7CDCF; }
.sk-ghostbtn svg { width: 15px; height: 15px; }
.sk-spacer-flex { flex: 1; }
.sk-chkall { display: inline-flex; align-items: center; gap: 9px; font-size: 13.5px; color: #3A4145; font-weight: 500; cursor: pointer; user-select: none; white-space: nowrap; }
.sk-okbtn { background: #1F7A54; color: #fff; border: none; border-radius: 12px; padding: 11px 18px; font: inherit; font-size: 14px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; transition: background .16s, transform .16s cubic-bezier(.34,1.56,.64,1), box-shadow .16s; }
.sk-okbtn svg { width: 16px; height: 16px; }
.sk-okbtn:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(31,122,84,.5); }
.sk-okbtn:disabled { background: #C7CDCF; cursor: default; transform: none; box-shadow: none; }

/* checkbox */
.sk-chk { appearance: none; -webkit-appearance: none; width: 18px; height: 18px; flex: none; border: 1.5px solid #C7CDCF; border-radius: 6px; cursor: pointer; background: #fff; position: relative; transition: background .14s, border-color .14s; }
.sk-chk:checked, .sk-chk:indeterminate { background: var(--teal, #014653); border-color: var(--teal, #014653); }
.sk-chk:checked::after { content: ""; position: absolute; left: 5px; top: 1.5px; width: 5px; height: 9px; border: solid #fff; border-width: 0 2px 2px 0; transform: rotate(45deg); }
.sk-chk:indeterminate::after { content: ""; position: absolute; left: 3.5px; top: 7px; width: 9px; height: 2px; background: #fff; }

/* payments-only: summary bar + legend + status pills + scroll wrapper */
.sk-sumbar { display: flex; justify-content: space-between; align-items: center; gap: 20px; background: #fff; border-radius: 16px; box-shadow: 0 1px 2px rgba(1,70,83,.05), 0 16px 40px rgba(1,70,83,.10); padding: 20px 24px; margin-bottom: 16px; flex-wrap: wrap; }
.sk-sum-amt { display: flex; align-items: center; gap: 16px; }
.sk-sum-amt .sk-ic { width: 46px; height: 46px; flex: none; border-radius: 12px; background: var(--teal-soft, #F5F8F8); color: var(--teal, #014653); display: grid; place-items: center; }
.sk-sum-amt .sk-ic svg { width: 22px; height: 22px; }
.sk-sum-amt .sk-lab { font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: #6B7378; }
.sk-sum-amt .sk-big { font-size: 28px; font-weight: 600; color: var(--teal, #014653); line-height: 1.1; margin-top: 2px; font-variant-numeric: tabular-nums; }
.sk-legend { display: flex; gap: 16px; flex-wrap: wrap; }
.sk-legend .sk-lg { display: inline-flex; align-items: center; gap: 7px; font-size: 12.5px; color: #6B7378; font-weight: 500; }
.sk-legend .sk-lg .sk-dot { width: 8px; height: 8px; border-radius: 50%; }
.sk-idc { font-size: 12px; color: #6B7378; max-width: 150px; overflow: hidden; text-overflow: ellipsis; display: inline-block; vertical-align: middle; }
.sk-empc { font-weight: 600; color: #3A4145; }

.sk-pill { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 20px; }
.sk-pill .sk-dot { width: 6px; height: 6px; border-radius: 50%; }
.sk-pill.paid { background: #E7F4EC; color: #1F7A54; } .sk-pill.paid .sk-dot { background: #1F7A54; }
.sk-pill.consented { background: #E7F1F7; color: #145A7A; } .sk-pill.consented .sk-dot { background: #2E7FB0; }
.sk-pill.partial { background: #FBF1E1; color: #9A6512; } .sk-pill.partial .sk-dot { background: #C8891F; }
.sk-pill.neutral { background: #F7F8F8; color: #6B7378; } .sk-pill.neutral .sk-dot { background: #99A1A5; }

/* pagination (shared shape with the app's DataTables Bootstrap pager —
   this just restyles the existing .paginate_button markup DataTables
   already generates around #shopkeeper-table / #payment-table, no new
   JS or extra wrapper markup needed). Scoped to .sk-card since that's
   the real enclosing element DataTables injects its wrapper into. */
.sk-card .dataTables_wrapper { margin-top: 4px; }
.sk-card .dataTables_info { color: #99A1A5; font-size: 13px; }
.sk-card .dataTables_paginate .paginate_button {
    min-width: 34px; height: 34px; border-radius: 10px !important; border: 1px solid var(--line, #EEF2F2) !important;
    background: #fff !important; color: #3A4145 !important; font-size: 13.5px; font-weight: 500;
    display: inline-grid; place-items: center; padding: 0 10px !important; margin-left: 6px;
    transition: border-color .14s, color .14s, background .14s;
}
.sk-card .dataTables_paginate .paginate_button:hover:not(.disabled):not(.current) { border-color: #cfe0e1 !important; background: #fff !important; }
.sk-card .dataTables_paginate .paginate_button.current { background: var(--teal, #014653) !important; color: #fff !important; border-color: var(--teal, #014653) !important; }
.sk-card .dataTables_paginate .paginate_button.disabled { opacity: .4; }
</style>
