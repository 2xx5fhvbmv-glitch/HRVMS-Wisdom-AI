{{--
    Shared look for the Talent Acquisition list screens (Upcoming Interviews, Rejected Applications,
    Review Reminders) — the same card / table / pill / avatar language as the redesigned Applicants and
    Shortlisted screens. Scoped to #tal so nothing leaks; wrap the page card in <div id="tal">.
--}}
<style>
#tal{
  --teal:#014653; --teal-2:#035b6c; --teal-3:#E6F0F1; --teal-soft:#f1f7f7;
  --ink:#14232A; --g1:#3A4145; --g2:#6B7378; --muted:#5D6F75; --faint:#93A4A9;
  --line:#E2EBEC; --line-2:#EEF4F4;
  --ok:#1F9D6B; --ok-bg:#E7F4EE; --warn:#B7791F; --warn-bg:#FBF0DC; --err:#E5573F; --err-bg:#FDEEEB;
  --violet:#6B5FC7; --violet-bg:#EEE9FB; --info:#1E7A85; --info-bg:#E2F0F2;
  font-family:'Poppins',-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
}
#tal .tal-note{font-size:12.5px;color:var(--muted)}
#tal .list-main{padding:0 18px 18px}
#tal .tal-pill{display:inline-flex;align-items:center;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;white-space:nowrap}
#tal .tal-pill.ok{background:var(--ok-bg);color:var(--ok)}
#tal .tal-pill.violet{background:var(--violet-bg);color:var(--violet)}
#tal .tal-pill.info{background:var(--info-bg);color:var(--info)}
#tal .tal-pill.teal{background:var(--teal-soft);color:var(--teal)}
#tal .tal-pill.warn{background:var(--warn-bg);color:var(--warn)}
#tal .tal-pill.err{background:var(--err-bg);color:var(--err)}
#tal .tal-pill.muted{background:var(--line-2);color:var(--g2)}
#tal .tal-tbl{border-collapse:separate;border-spacing:0;font-size:12.5px;min-width:900px;margin:0;width:100%}
#tal .tal-tbl th,#tal .tal-tbl td{padding:12px 14px;text-align:left;white-space:nowrap;border-bottom:1px solid var(--line-2);vertical-align:middle;font-weight:400;color:var(--g1)}
#tal .tal-tbl thead th{background:var(--teal-soft);font-size:10px;font-weight:600;letter-spacing:.4px;text-transform:uppercase;color:var(--muted)}
#tal .tal-tbl th.r,#tal .tal-tbl td.r{text-align:right}
#tal .tal-tbl tbody tr:hover td{background:#fafcfc}
#tal .tal-tbl tbody tr.main{cursor:pointer}
#tal .tal-appcell{display:flex;align-items:center;gap:11px;min-width:0}
#tal .tal-av{flex:none;width:36px;height:36px;border-radius:50%;background:#E1EBEC;color:var(--teal);font-size:11px;font-weight:600;display:grid;place-items:center;overflow:hidden;border:2px solid #fff;box-shadow:0 0 0 1.5px var(--teal-3);position:relative}
#tal .tal-av img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
#tal .tal-appcell .nm{font-size:13.5px;font-weight:500;color:var(--ink)}
#tal .tal-appcell .sub{font-size:11px;color:var(--muted);margin-top:1px}
#tal .tal-stack .a{font-size:12.5px;color:var(--g1)}
#tal .tal-stack .b{font-size:11px;color:var(--muted);margin-top:1px}
#tal .tal-iv .ivd{font-size:13px;font-weight:500;color:var(--g1)}
#tal .tal-iv .ivt{font-size:11px;color:var(--muted);margin-top:2px}
#tal .tal-iv .ivt b{font-weight:600;color:var(--teal);font-variant-numeric:tabular-nums}
#tal .tal-iv .ivt .sep{color:#C7CDCF;margin:0 5px}
#tal .tal-iv .none{font-size:12.5px;color:var(--faint)}
#tal .tal-reason{display:inline-block;max-width:260px;overflow:hidden;text-overflow:ellipsis;vertical-align:middle;color:var(--g1)}
#tal .tal-actcell{display:flex;align-items:center;gap:6px;justify-content:flex-end}
#tal .tal-iact{width:30px;height:30px;border-radius:8px;border:1px solid var(--line);background:#fff;color:var(--teal);display:grid;place-items:center;padding:0;cursor:pointer;text-decoration:none}
#tal .tal-iact:hover{border-color:var(--teal);background:var(--teal-soft);color:var(--teal)}
#tal .tal-iact svg{width:14px;height:14px}
#tal .tal-join{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;border-radius:9px;padding:7px 12px;background:var(--teal);color:#fff;text-decoration:none;white-space:nowrap;transition:background .15s,transform .15s}
#tal .tal-join:hover{background:var(--teal-2);color:#fff}
#tal .tal-join:active{transform:scale(.96)}
#tal .tal-join svg{width:13px;height:13px}
#tal .flagimg{width:20px;height:14px;object-fit:cover;border-radius:3px;box-shadow:0 0 0 1px rgba(1,70,83,.12);margin-right:6px;vertical-align:-2px}
#tal .tal-iv .ivt .flagimg{margin-right:4px}
#tal .ccode{display:inline-block;font-size:9.5px;font-weight:600;letter-spacing:.3px;color:var(--teal);background:var(--teal-soft);border-radius:5px;padding:1px 5px;margin-right:6px;vertical-align:1px}
#tal .tal-tbl thead th .dt-column-title{white-space:nowrap}
#tal .tal-tbl thead th.dt-orderable-asc span.dt-column-order:before,
#tal .tal-tbl thead th.dt-orderable-asc span.dt-column-order:after,
#tal .tal-tbl thead th.dt-orderable-desc span.dt-column-order:before,
#tal .tal-tbl thead th.dt-orderable-desc span.dt-column-order:after{opacity:0}
#tal .tal-tbl thead th.dt-ordering-asc span.dt-column-order:before,
#tal .tal-tbl thead th.dt-ordering-desc span.dt-column-order:after{opacity:1;color:var(--teal);font-size:.7em}
#tal .tal-tbl thead th.dt-ordering-asc span.dt-column-order:after,
#tal .tal-tbl thead th.dt-ordering-desc span.dt-column-order:before{display:none}
@media (max-width:720px){#tal .list-main{padding-left:12px;padding-right:12px}}
@media (prefers-reduced-motion:reduce){#tal *{transition:none!important;animation:none!important}}
</style>
