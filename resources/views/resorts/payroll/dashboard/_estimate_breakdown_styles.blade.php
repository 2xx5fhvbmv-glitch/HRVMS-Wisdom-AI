{{--
    Styles for the "how is this calculated" Payroll Breakdown modal.
    Round 3: white header with a cycle-progress bar (no large gross
    number in the header), teal-soft comparison cards, tinted
    Earnings/Deductions panels with a subtotal row at the bottom of each
    instead of a total in the header. Tokens and exact colors below are
    the owner's own spec, taken as given. Scoped to peb- (Payroll
    Estimate Breakdown) prefixed classes so this can't leak elsewhere.
--}}
<style>
    /* Neutral/geometry tokens (--teal/--teal-2/--teal-3/--teal-soft/--ink/
       --muted/--faint/--line/--line-2) now come from the shared :root
       palette (resorts/layouts/_design_tokens.blade.php). --peb-earn
       folded into --positive/--positive-bg (shared --positive was
       updated to this file's own #1f9d6b so the merge is exact); --peb-ded
       folded into --error (exact hex match). --peb-earn-border/
       --peb-ded-bg/--peb-ded-border stay local — no shared-token
       equivalent exists for them yet (--peb-ded-bg's #fdf4f2 is close to
       but not exactly --error-bg's #FDEEEB, so left rather than
       silently nudged). */
    :root {
        --peb-earn-border: #dcefe5;
        --peb-ded-bg: #fdf4f2;
        --peb-ded-border: #f6ddd6;
    }

    .peb-info-btn {
        width: 15px; height: 15px; border-radius: 50%; border: none;
        background: var(--teal-3); color: var(--teal); font-size: 9px; font-weight: 700;
        display: inline-flex; align-items: center; justify-content: center; cursor: pointer;
        padding: 0; line-height: 1; vertical-align: middle; margin-left: 4px;
    }
    .peb-info-btn i { font-style: italic; font-family: Georgia, serif; }
    .peb-info-btn:hover { background: var(--teal); color: #fff; }

    .peb-dialog { max-width: 600px; }
    .peb-content { border-radius: 22px; border: none; overflow: hidden; }

    /* ===== header: white, hairline border, no gross figure ===== */
    .peb-header {
        background: #fff; border-bottom: 1px solid var(--line);
        padding: 18px 22px 16px; align-items: flex-start;
    }
    .peb-header-main { flex: 1; min-width: 0; }
    .peb-close { width: 16px; height: 16px; background-color: transparent; opacity: .55; }
    .peb-close:hover { opacity: .9; }
    .peb-period { font-size: 11px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: var(--faint); margin: 0 0 8px; }
    .peb-cycle { display: flex; align-items: center; gap: 10px; }
    .peb-cycle-lbl { font-size: 14px; font-weight: 700; color: var(--teal); white-space: nowrap; }
    .peb-cycle-track { flex: 1; max-width: 200px; height: 6px; border-radius: 100px; background: var(--line-2); overflow: hidden; }
    .peb-cycle-fill { display: block; height: 100%; background: var(--teal); border-radius: 100px; width: 0%; transition: width .3s ease; }

    /* ===== comparison cards: teal-soft, not dark teal ===== */
    .peb-compare {
        display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
        background: #fff; padding: 16px 22px 4px;
    }
    .peb-compare-card { background: var(--teal-soft); border: 1px solid var(--line); border-radius: 12px; padding: 12px 14px; }
    .peb-compare-lbl { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: var(--faint); margin-bottom: 5px; }
    .peb-compare-amt { font-size: 19px; font-weight: 700; color: var(--ink); font-variant-numeric: tabular-nums; }
    .peb-compare-delta { font-size: 12px; color: var(--positive); font-weight: 600; margin-top: 4px; }
    .peb-compare-delta.down { color: var(--error); }
    .peb-compare-sub { font-size: 11px; color: var(--faint); margin-top: 4px; }

    .peb-tabs { border-bottom: 1px solid var(--line); padding: 0 22px; margin: 12px 0 0; }
    .peb-tabs .nav-link {
        border: none; background: none; font-size: 12.5px; font-weight: 600; color: var(--faint);
        padding: 10px 4px; margin-right: 20px; border-radius: 0; border-bottom: 2px solid transparent;
    }
    .peb-tabs .nav-link.active { color: var(--teal); border-bottom-color: var(--teal); background: none; }
    .peb-tab-count { color: var(--faint); font-weight: 600; }

    .peb-body { padding: 14px 22px 6px; max-height: 52vh; overflow-y: auto; }
    .peb-loading, .peb-empty { font-size: 13px; color: var(--faint); padding: 24px 0; text-align: center; }
    .peb-error { font-size: 13px; color: var(--error); padding: 16px 0; text-align: center; }

    /* ===== earnings / deductions panels ===== */
    .peb-panel { border-radius: 14px; padding: 4px 16px 14px; margin: 12px 0; border: 1px solid; }
    .peb-panel.earn-panel { background: var(--positive-bg); border-color: var(--peb-earn-border); }
    .peb-panel.ded-panel { background: var(--peb-ded-bg); border-color: var(--peb-ded-border); }
    .peb-panel:first-child { margin-top: 2px; }

    .peb-panel-head { display: flex; align-items: center; gap: 8px; padding: 12px 0 6px; }
    .peb-dot { width: 7px; height: 7px; border-radius: 50%; flex: none; }
    .peb-dot-earn { background: var(--positive); }
    .peb-dot-ded { background: var(--error); }
    .peb-panel-name { font-size: 11px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: var(--muted); }

    .peb-earn { color: var(--positive); }
    .peb-ded { color: var(--error); }

    .peb-line { border-bottom: 1px solid rgba(20,35,42,.07); }
    .peb-line:last-child { border-bottom: none; }
    .peb-line-head { display: flex; align-items: center; gap: 10px; padding: 10px 0; cursor: pointer; }
    .peb-line-main { flex: 1; min-width: 0; }
    .peb-line-name { font-size: 13px; font-weight: 500; color: var(--ink); }
    .peb-line-count { font-size: 11px; color: var(--faint); margin-top: 1px; }
    .peb-line-right { text-align: right; }
    .peb-line-amt { font-size: 13px; font-weight: 500; font-variant-numeric: tabular-nums; }
    .peb-chevron { color: var(--faint); font-size: 14px; transition: transform .15s ease; flex: none; }
    .peb-line.open .peb-chevron { transform: rotate(90deg); }
    .peb-line-detail { display: none; padding: 0 0 12px 0; }
    .peb-line.open .peb-line-detail { display: block; }

    /* ===== subtotal row at the bottom of each panel ===== */
    .peb-subtotal-row { display: flex; justify-content: space-between; align-items: center; border-top: 2px solid rgba(20,35,42,.14); margin-top: 4px; padding: 10px 0 4px; font-size: 13px; font-weight: 600; color: var(--ink); }
    .peb-subtotal-row span:last-child { font-size: 14px; font-weight: 700; font-variant-numeric: tabular-nums; }

    .peb-emp-row { display: flex; align-items: center; gap: 9px; padding: 5px 0; }
    .peb-emp-avatar { width: 22px; height: 22px; border-radius: 50%; background: var(--teal); color: #fff; font-size: 9px; font-weight: 600; display: flex; align-items: center; justify-content: center; flex: none; }
    .peb-emp-name { font-size: 12px; font-weight: 500; color: var(--ink); flex: 1; }
    .peb-emp-ctx { font-size: 11px; font-weight: 400; color: var(--faint); }
    .peb-emp-amt { font-size: 12px; font-weight: 600; font-variant-numeric: tabular-nums; }
    .peb-view-all { font-size: 11.5px; font-weight: 500; color: var(--teal-2); padding: 4px 0 0; cursor: default; }
    .peb-view-all.peb-empty-line { color: var(--faint); font-weight: 400; }

    .peb-net-band { display: flex; justify-content: space-between; align-items: center; padding: 10px 0 4px; }
    .peb-net-lbl { font-size: 13px; font-weight: 600; color: var(--ink); }
    .peb-net-sub { font-size: 10.5px; color: var(--muted); margin-top: 1px; }
    .peb-net-amt { font-size: 18px; font-weight: 700; color: var(--teal); font-variant-numeric: tabular-nums; }

    .peb-activity-list { display: flex; flex-direction: column; }
    .peb-activity-row { display: flex; align-items: center; gap: 10px; padding: 9px 0; border-bottom: 1px solid var(--line-2); }
    .peb-activity-row:last-child { border-bottom: none; }
    .peb-a-avatar { width: 26px; height: 26px; border-radius: 50%; background: var(--teal); color: #fff; font-size: 9.5px; font-weight: 600; display: flex; align-items: center; justify-content: center; flex: none; }
    .peb-a-main { flex: 1; min-width: 0; }
    .peb-a-name { font-size: 12.5px; font-weight: 500; color: var(--ink); }
    .peb-a-dept { font-size: 11px; font-weight: 400; color: var(--faint); }
    .peb-a-meta { font-size: 10.5px; color: var(--faint); margin-top: 1px; }
    .peb-a-amt { font-size: 12.5px; font-weight: 600; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .peb-status-pill { font-size: 9px; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; padding: 1px 6px; border-radius: 100px; margin-left: 5px; }
    .peb-status-pill.present { background: var(--positive-bg); color: var(--positive); }
    .peb-status-pill.absent { background: var(--peb-ded-bg); color: var(--error); }
    .peb-status-pill.ot { background: #FFF6CC; color: #8a6d00; }
    .peb-status-pill.dayoff { background: var(--line-2); color: var(--faint); }

    .peb-load-more { text-align: center; padding: 12px 0 6px; }
    .peb-load-more-btn { border: 1px solid var(--line); background: #fff; border-radius: 100px; font-size: 11.5px; font-weight: 600; color: var(--teal-2); padding: 6px 16px; cursor: pointer; }
    .peb-load-more-btn:hover { background: var(--teal-soft); }
    .peb-load-more-btn:disabled { opacity: .5; cursor: default; }
    .peb-load-more-count { font-size: 10.5px; color: var(--faint); margin-top: 6px; }

    .peb-foot { padding: 10px 22px 20px; border-top: 1px solid var(--line); margin-top: 4px; }
    .peb-foot p { font-size: 11px; color: var(--faint); line-height: 1.6; margin: 0; }
</style>
