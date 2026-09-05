{{--
    Shared CSS for the Incident Dashboard — one include for all 3 role
    views. Reuses the app's real global tokens (--teal/--ink/--muted/
    --faint/--line/--card/--positive/--warning/--error/--neutral-bg/--paper,
    from resorts/layouts/_design_tokens.blade.php) directly rather than
    inventing a parallel set — see the Consolidated Budget page regression
    earlier this session for exactly why that's worth avoiding.

    Two colors from the finalized categorical ramp (Aqua var(--aqua), Brass
    #B08D57) aren't in the shared token file yet, so they're declared here,
    scoped to .dbi-wrap only — not promoted to :root, so nothing here can
    leak onto other pages or drift out of sync with a future global change.
--}}
<style>
.dbi-wrap{width:100%;--dbi-c1:var(--teal);--dbi-c2:var(--aqua);--dbi-c3:var(--positive);--dbi-c4:#B08D57}

/* KPI row — minimal: number leads, quiet label, tiny status dot; whole card is the link */
.dbi-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:16px}
.dbi-kpi{position:relative;display:block;text-decoration:none;background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:0 1px 2px rgba(1,70,83,.04),0 10px 26px rgba(1,70,83,.05);padding:20px 20px 18px;transition:border-color .15s,box-shadow .15s,transform .15s}
.dbi-kpi:hover{border-color:var(--teal-3);box-shadow:0 2px 4px rgba(1,70,83,.05),0 14px 32px rgba(1,70,83,.07);transform:translateY(-1px)}
.dbi-kpi .num{font-size:32px;font-weight:600;letter-spacing:-1px;color:var(--ink);line-height:1}
.dbi-kpi .num small{font-size:14px;font-weight:500;color:var(--faint);letter-spacing:0}
.dbi-kpi .lbl{font-size:14px;font-weight:500;color:var(--muted);margin-top:9px}
.dbi-kpi .hint{display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:600;margin-top:11px}
.dbi-kpi .hint .d{width:6px;height:6px;border-radius:50%;flex:none}
.dbi-kpi .hint.warn{color:var(--warning)} .dbi-kpi .hint.warn .d{background:var(--warning)}
.dbi-kpi .hint.ok{color:var(--positive)} .dbi-kpi .hint.ok .d{background:var(--positive)}
.dbi-kpi .hint.neu{color:var(--faint)} .dbi-kpi .hint.neu .d{background:var(--faint)}
.dbi-kpi .go{position:absolute;top:18px;right:18px;color:var(--faint);transition:color .15s,transform .15s}
.dbi-kpi:hover .go{color:var(--teal);transform:translateX(2px)}

/* main grid */
.dbi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;align-items:stretch}
.dbi-sp2{grid-column:span 2}
.dbi-tier{grid-column:1/-1;display:flex;align-items:center;gap:12px;margin:16px 2px 0}
.dbi-tier:first-child{margin-top:0}
.dbi-tier .tl{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.9px;color:var(--muted)}
.dbi-tier .ts{font-size:11px;color:var(--faint)}
.dbi-tier::after{content:"";flex:1;height:1px;background:var(--line)}
@media(max-width:1100px){.dbi-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:680px){.dbi-grid{grid-template-columns:1fr}.dbi-grid .dbi-sp2{grid-column:span 1}}

.dbi-card{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:0 1px 2px rgba(1,70,83,.04),0 10px 26px rgba(1,70,83,.05);padding:18px 20px}
.dbi-card-flush{padding:0}
.dbi-card-h{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;gap:10px}
.dbi-card-h .ttl{font-size:18px;font-weight:600;color:var(--ink)}
.dbi-card-h .ttl em{display:block;font-style:normal;font-size:10.5px;font-weight:500;color:var(--faint);margin-top:1px}
.dbi-viewall{font-size:14px;font-weight:600;color:var(--teal);text-decoration:none;white-space:nowrap}
.dbi-viewall:hover{text-decoration:underline}
.dbi-viewall-block{display:block;margin-top:4px}
.dbi-sel{font-family:inherit;font-size:14px;font-weight:500;color:var(--ink);border:1px solid var(--line);background:var(--neutral-bg);border-radius:8px;padding:5px 9px;cursor:pointer}

/* incident list — minimal, no avatar / no status pill (matches _incident_list_widget.blade.php's look) */
.dbi-card-flush .dbi-card-h{padding:18px 20px 0;margin-bottom:0}
#dbiIncidentList{padding:0 20px 4px}
.dbi-inc{padding:14px 0;border-bottom:1px solid var(--line-2)}
.dbi-inc:first-child{padding-top:10px}
.dbi-inc:last-child{border-bottom:none;padding-bottom:0}
.dbi-inc .top{display:flex;align-items:baseline;justify-content:space-between;gap:12px}
.dbi-inc .top .t{font-size:14px;font-weight:500;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.dbi-inc .top .tm{font-size:11px;color:var(--faint);white-space:nowrap;flex:none}
.dbi-inc .bot{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:4px}
.dbi-inc .bot .cat{font-size:11px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.dbi-inc .vd{font-size:11px;font-weight:600;color:var(--teal);text-decoration:none;white-space:nowrap;flex:none}
.dbi-inc .vd:hover{text-decoration:underline}

/* donut */
.dbi-donut-row{display:flex;align-items:center;gap:18px}
.dbi-donut{width:132px;height:132px;border-radius:50%;position:relative;flex:none}
.dbi-donut .hole{position:absolute;inset:19px;background:var(--card);border-radius:50%;display:grid;place-content:center;text-align:center}
.dbi-donut .hole b{font-size:22px;font-weight:600;color:var(--ink);letter-spacing:-.5px;line-height:1}
.dbi-donut .hole span{font-size:10.5px;font-weight:500;color:var(--faint);margin-top:3px}
.dbi-legend{flex:1;display:flex;flex-direction:column;gap:11px}
.dbi-legend .lg{display:flex;align-items:center;gap:9px;font-size:14px;color:var(--ink)}
.dbi-legend .lg .sq{width:10px;height:10px;border-radius:3px;flex:none}
.dbi-legend .lg .lv{margin-left:auto;font-weight:600;color:var(--ink);font-variant-numeric:tabular-nums}
.dbi-legend .lg .pc{font-size:11px;color:var(--faint);width:38px;text-align:right;font-variant-numeric:tabular-nums}

/* stat list (severity) / resolution timelines */
.dbi-stat{display:flex;align-items:center;gap:12px;padding:11px 0;border-bottom:1px solid var(--line-2)}
.dbi-stat:last-child{border-bottom:none}
.dbi-stat .dot{width:8px;height:8px;border-radius:50%;flex:none}
.dbi-stat .nm{font-size:14px;color:var(--ink)}
.dbi-stat .track{flex:1;height:5px;background:var(--neutral-bg);border-radius:4px;overflow:hidden}
.dbi-stat .track>i{display:block;height:100%;border-radius:4px}
.dbi-stat .v{font-size:14px;font-weight:600;color:var(--ink);font-variant-numeric:tabular-nums;min-width:26px;text-align:right}
.dbi-res{display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--line-2)}
.dbi-res:last-child{border-bottom:none}
.dbi-res .l{display:flex;align-items:center;gap:10px;font-size:14px;color:var(--ink)}
.dbi-res .l .ic{width:28px;height:28px;border-radius:8px;background:var(--teal-soft);color:var(--teal);display:grid;place-items:center;flex:none}
.dbi-res .l .ic.warn{background:var(--warning-bg);color:var(--warning)}
.dbi-res .l .ic.err{background:var(--error-bg);color:var(--error)}
.dbi-res .v{font-size:14px;font-weight:600;color:var(--ink);font-variant-numeric:tabular-nums}
.dbi-res .v.err{color:var(--error)}

/* stacked bar chart (department participation) */
.dbi-bars{display:flex;align-items:flex-end;gap:16px;height:180px;padding-left:26px;position:relative;margin-top:6px}
.dbi-bars .yax{position:absolute;left:0;top:0;bottom:22px;width:22px;display:flex;flex-direction:column;justify-content:space-between;font-size:10.5px;font-weight:500;color:var(--faint);text-align:right}
.dbi-bars .gl{position:absolute;left:26px;right:0;height:1px;background:var(--line-2)}
.dbi-bars .col{flex:1;display:flex;flex-direction:column;align-items:center;gap:6px;height:100%;justify-content:flex-end;z-index:1}
.dbi-bars .stack{width:60%;max-width:44px;display:flex;flex-direction:column-reverse;border-radius:5px 5px 0 0;overflow:hidden}
.dbi-bars .stack i{display:block;width:100%}
.dbi-bars .col .mn{font-size:10.5px;font-weight:500;color:var(--faint)}

/* line chart (trends) */
.dbi-line svg{width:100%;height:auto;display:block}
.dbi-line .gl{stroke:var(--line-2);stroke-width:1}
.dbi-line .axis{fill:var(--faint);font-size:10px}
.dbi-line .area{fill:url(#dbiTealFade)}
.dbi-line .ln{fill:none;stroke:var(--teal);stroke-width:2.5;stroke-linejoin:round;stroke-linecap:round}
.dbi-line .pt{fill:var(--card);stroke:var(--teal);stroke-width:2.5}

/* preventive list */
.dbi-prev{display:flex;align-items:flex-start;gap:11px;padding:11px 0;border-bottom:1px solid var(--line-2)}
.dbi-prev:last-child{border-bottom:none}
.dbi-prev .ic{width:28px;height:28px;border-radius:8px;background:var(--warning-bg);color:var(--warning);display:grid;place-items:center;flex:none}
.dbi-prev .b .t{font-size:14px;font-weight:500;color:var(--ink)}
.dbi-prev .b .s{font-size:11px;color:var(--faint);margin-top:1px}

/* pending-resolution list */
.dbi-pend{padding:11px 0;border-bottom:1px solid var(--line-2)}
.dbi-pend:last-child{border-bottom:none}
.dbi-pend .t{font-size:14px;font-weight:600;color:var(--ink)}
.dbi-pend .s{font-size:14px;color:var(--muted);margin-top:3px;line-height:1.5}

/* upcoming meetings */
.dbi-mtg{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:11px 0;border-bottom:1px solid var(--line-2)}
.dbi-mtg:last-child{border-bottom:none}
.dbi-mtg .t{font-size:14px;font-weight:600;color:var(--ink)}
.dbi-mtg .s{font-size:14px;color:var(--muted);margin-top:3px;line-height:1.5}
.dbi-mtg .when{font-size:10.5px;font-weight:600;color:var(--teal);background:var(--teal-3);padding:2px 9px;border-radius:20px;white-space:nowrap;flex:none}
.dbi-mtg .vd{font-size:11px;font-weight:600;color:var(--teal);text-decoration:none;margin-top:6px;display:inline-block}
.dbi-mtg .vd:hover{text-decoration:underline}

/* delegated cases table */
.dbi-table{width:100%;border-collapse:collapse;font-size:14px}
.dbi-table th{text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--faint);padding:0 0 11px;border-bottom:1px solid var(--line)}
.dbi-table th.c{text-align:center}.dbi-table th.r{text-align:right}
.dbi-table td{padding:13px 0;border-bottom:1px solid var(--line-2);color:var(--muted);vertical-align:middle}
.dbi-table tr:last-child td{border-bottom:none}
.dbi-table td.name{color:var(--ink);font-weight:500}
.dbi-table td.cases{text-align:center;font-variant-numeric:tabular-nums;font-weight:600;color:var(--ink)}
.dbi-table td.cases.zero{color:var(--faint)}
.dbi-table td.status{text-align:right}
.dbi-tagpill{display:inline-flex;align-items:center;gap:5px;font-size:10.5px;font-weight:600;padding:3px 10px;border-radius:20px;background:var(--warning-bg);color:var(--warning)}
.dbi-tagpill .d{width:6px;height:6px;border-radius:50%;background:currentColor}
.dbi-table .status .none{color:var(--faint);font-size:11px}

/* WAI Insights card — teal→lime gradient header, the one reserved "hero" moment */
.dbi-wai{padding:0;overflow:hidden}
.dbi-wai-head{background:var(--grad-celebrate);padding:15px 18px;color:#fff}
.dbi-wai-head .wt{font-size:18px;font-weight:600;letter-spacing:-.2px}
.dbi-wai-head .ws{font-size:10.5px;font-weight:500;color:rgba(255,255,255,.9);margin-top:2px}
.dbi-wai-head .ws a{color:#fff;text-decoration:underline;cursor:pointer}
.dbi-wai-body{padding:2px 18px}
.dbi-wai-item{display:flex;gap:12px;padding:14px 0;border-bottom:1px solid var(--line-2)}
.dbi-wai-item:last-child{border-bottom:none}
.dbi-wai-ic{width:22px;height:22px;border-radius:6px;background:var(--positive-bg);color:var(--positive);display:grid;place-items:center;flex:none;margin-top:1px}
.dbi-wai-item .wtitle{font-size:14px;font-weight:600;color:var(--ink)}
.dbi-wai-item .wdesc{font-size:14px;color:var(--muted);line-height:1.55;margin-top:3px}
.dbi-lnkrow{margin-top:8px}
.dbi-wlink{font-size:14px;font-weight:600;color:var(--teal);text-decoration:none;background:none;border:none;padding:0;cursor:pointer;font-family:inherit}
.dbi-wlink:hover{text-decoration:underline}

/* Incidents by Category — ranked bars (scales to many; top 6 + Other) */
.dbi-ttl-meta{font-size:11px;font-weight:600;color:var(--muted);background:var(--neutral-bg);padding:2px 9px;border-radius:20px;font-variant-numeric:tabular-nums}
.dbi-catbars{display:flex;flex-direction:column;gap:15px;margin-top:6px}
.dbi-catrow .crh{display:flex;align-items:baseline;justify-content:space-between;gap:12px;margin-bottom:6px}
.dbi-catrow .nm{font-size:14px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.dbi-catrow .val{font-size:14px;font-weight:600;color:var(--ink);font-variant-numeric:tabular-nums;white-space:nowrap;flex:none}
.dbi-catrow .val em{font-style:normal;font-weight:500;color:var(--faint);font-size:11px;margin-left:5px}
.dbi-catrow .track{height:8px;background:var(--neutral-bg);border-radius:5px;overflow:hidden}
.dbi-catrow .track>i{display:block;height:100%;background:var(--teal);border-radius:5px}
.dbi-catrow.other .nm{color:var(--muted)}
.dbi-catrow.other .track>i{background:var(--faint)}

/* mini empty state */
.dbi-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;padding:34px 16px;text-align:center;min-height:120px}
.dbi-empty .g{width:42px;height:42px;border-radius:50%;background:var(--teal-soft);display:grid;place-items:center;color:var(--teal)}
.dbi-empty .t{font-size:14px;font-weight:500;color:var(--ink)}
.dbi-empty .s{font-size:11px;color:var(--faint)}
</style>
