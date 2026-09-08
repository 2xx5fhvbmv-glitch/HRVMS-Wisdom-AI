{{--
    Styles for the "how is this calculated" Payroll Breakdown popover — v2
    revision (hierarchy fix + Liquid Glass shell), ported from
    payroll_popover_v2_reference.html verbatim. Reference tokens mapped onto
    the app's real shared :root palette (resorts/layouts/_design_tokens.blade.php)
    wherever an equivalent exists: --teal/--teal-2/--teal-3/--teal-soft/--ink/
    --line/--line-2, reference --g2→app --muted, reference --g3→app --faint.

    What changed vs v1 (see the task's own "what changed" list):
    - Section headers (.fcap) are dot + label only — no amount, no more
      green/red color; every dot is plain teal now.
    - Totals live only in their own .tot row, on the SAME 4-column grid as
      the .cat rows above them (1fr 44px 88px 16px) so the amount lines up
      in the same column — no amount duplicated in the header AND the total.
    - All financial figures (.pos/.neg, both .tot rows, the "vs. yesterday"
      delta) are plain ink — the only color left in the popover is teal, on
      the two headline balances and the net payout. No green/red/orange.
    - Zero-value categories use --faint (was --line, functionally
      invisible) — muted but legible — and hide their chevron.
    - Net payout is 20px/700 (was 26px/600 — overflowed at that size; the
      brief for this revision explicitly says don't re-enlarge it).
    - Real Liquid Glass shell: border + layered inset speculars + drop
      shadow, plus the pointer-tracking ::before highlight and ::after top
      sheen the v1 file never had. The dashboard's own .modal-backdrop is a
      flat rgba(0,0,0) opacity fade (default Bootstrap, no blur) — not the
      dark+blurred scrim the "high-opacity" recipe assumes — so this uses
      the documented frosted fallback (rgba(255,255,255,.9) +
      blur(28px) saturate(160%)) for the fill, same values v1 already had,
      while still adding the shell chrome (border/shadow/specular) the
      frosted fallback still calls for.
    - Inner cards (comparison cards, earnings/deductions frames, empty
      state) are solid #fff now, not translucent — glass is the shell only.
    - The orange "largest deduction" accent bar (.attn) is removed —
      nothing marks a row by color/stripe now, only its amount.

    Every selector stays scoped under .pay-pop (the class added to
    Bootstrap's own .modal-content — see _estimate_breakdown_modal.blade.php)
    so nothing here leaks onto the rest of the page, same reasoning v1 used
    for its generic .money/.pos/.neg names.
--}}
<style>
    :root {
        --peb-ok: #4A7C64;
        --peb-ok-bg: #E4F3E9;
    }

    /* Trigger icon on the dashboard card — not part of the popover itself,
       kept exactly as it was before this restyle. */
    .peb-info-btn {
        width: 15px; height: 15px; border-radius: 50%; border: none;
        background: var(--teal-3); color: var(--teal); font-size: 9px; font-weight: 700;
        display: inline-flex; align-items: center; justify-content: center; cursor: pointer;
        padding: 0; line-height: 1; vertical-align: middle; margin-left: 4px;
    }
    .peb-info-btn i { font-style: italic; font-family: Georgia, serif; }
    .peb-info-btn:hover { background: var(--teal); color: #fff; }

    .peb-dialog { max-width: 492px; }

    /* ---------- Liquid Glass shell ---------- */
    .pay-pop {
        position: relative; padding: 24px 24px 20px; color: var(--ink); font-size: 14px; line-height: 1.5;
        overflow: hidden; font-family: 'Poppins', sans-serif;
        background: rgba(255,255,255,.9); backdrop-filter: blur(28px) saturate(160%); -webkit-backdrop-filter: blur(28px) saturate(160%);
        border: 1px solid rgba(255,255,255,.5); border-radius: 22px;
        box-shadow: inset 0 1px 1px rgba(255,255,255,.75), inset 0 -1px 2px rgba(255,255,255,.3), 0 26px 74px rgba(0,0,0,.34);
    }
    .pay-pop::before {
        content: ""; position: absolute; inset: 0; border-radius: inherit; pointer-events: none; z-index: 0;
        background: radial-gradient(220px 220px at var(--mx, 50%) var(--my, 0%), rgba(255,255,255,.4), transparent 60%);
        opacity: .7; transition: opacity .2s;
    }
    .pay-pop::after {
        content: ""; position: absolute; left: 18px; right: 18px; top: 0; height: 1px; pointer-events: none; z-index: 0;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,.95), transparent);
    }
    .pay-pop > * { position: relative; z-index: 1; }

    .pay-pop .money { font-variant-numeric: tabular-nums; }
    /* All-ink amounts — the sign (or literal "−") carries the meaning, not color. */
    .pay-pop .pos, .pay-pop .neg { color: var(--ink); }

    .pay-pop .x { position: absolute; top: 16px; right: 16px; width: 28px; height: 28px; border-radius: 50%; background: transparent; border: none; color: var(--faint); cursor: pointer; font-size: 14px; display: grid; place-items: center; line-height: 1; z-index: 2; }
    .pay-pop .x:hover { background: var(--line-2); color: var(--ink); }
    .pay-pop .kick { display: flex; align-items: center; gap: 7px; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .8px; color: var(--muted); }
    .pay-pop .kick .dot { width: 6px; height: 6px; border-radius: 50%; background: var(--teal); }

    .pay-pop .prog { height: 5px; border-radius: 4px; background: var(--line-2); overflow: hidden; }
    .pay-pop .prog > i { display: block; height: 100%; background: var(--teal); border-radius: 4px; width: 0%; transition: width .3s ease; }
    .pay-pop .top { margin-top: 12px; display: flex; align-items: center; justify-content: space-between; gap: 14px; }
    .pay-pop .top b { font-size: 14px; font-weight: 600; color: var(--teal); white-space: nowrap; }
    .pay-pop .top .prog { flex: 1; }
    .pay-pop .top span { font-size: 11px; color: var(--faint); white-space: nowrap; }

    .pay-pop .cmp { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 18px; }
    .pay-pop .cmp .c { border: 1px solid var(--line); border-radius: 14px; padding: 13px 15px; background: #fff; }
    .pay-pop .cmp .c .k { font-size: 9.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .6px; color: var(--faint); }
    .pay-pop .cmp .c .a { font-size: 22px; font-weight: 600; letter-spacing: -.5px; margin-top: 3px; color: var(--teal); }
    .pay-pop .cmp .c .d { font-size: 10.5px; margin-top: 3px; color: var(--muted); font-weight: 500; }
    .pay-pop .cmp .c .d.mut { color: var(--faint); font-weight: 400; }

    .pay-pop .tabs { display: flex; gap: 20px; margin-top: 20px; border-bottom: 1px solid var(--line); }
    .pay-pop .tabs .t { font-size: 12px; font-weight: 500; color: var(--faint); padding: 0 0 10px; border-bottom: 2px solid transparent; cursor: pointer; background: none; border-top: none; border-left: none; border-right: none; font-family: inherit; }
    .pay-pop .tabs .t.on { color: var(--teal); border-bottom-color: var(--teal); font-weight: 600; }
    .pay-pop .panel { display: none; }
    .pay-pop .panel.on { display: block; }

    .pay-pop .frame { margin-top: 16px; border: 1px solid var(--line); border-radius: 14px; overflow: hidden; background: #fff; }
    .pay-pop .frame + .frame { margin-top: 12px; }
    /* section header — dot + label only, no amount (total lives only in .tot below) */
    .pay-pop .fcap { padding: 12px 15px; border-bottom: 1px solid var(--line); display: flex; align-items: center; gap: 8px; }
    .pay-pop .fcap .gd { width: 7px; height: 7px; border-radius: 50%; background: var(--teal); }
    .pay-pop .fcap .lbl { font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .7px; color: var(--muted); }

    /* expandable category rows — amount column shared with .tot below */
    .pay-pop .cat { display: grid; grid-template-columns: 1fr 44px 88px 16px; align-items: center; gap: 8px; padding: 9px 15px; border-bottom: 1px solid var(--line-2); cursor: pointer; }
    .pay-pop .cat:last-child { border-bottom: none; }
    .pay-pop .cat:hover { background: var(--line-2); }
    .pay-pop .cat .nm { font-size: 12.5px; color: var(--ink); }
    .pay-pop .cat .emp { text-align: right; color: var(--faint); font-size: 11px; font-variant-numeric: tabular-nums; }
    .pay-pop .cat .amt { text-align: right; font-size: 13px; font-weight: 500; font-variant-numeric: tabular-nums; }
    .pay-pop .cat .cv { color: var(--line); font-size: 11px; text-align: center; transition: transform .18s ease; }
    .pay-pop .cat.open { background: var(--line-2); }
    .pay-pop .cat.open .cv { transform: rotate(90deg); color: var(--teal); }
    /* zero rows — muted but legible (was --line, functionally invisible) */
    .pay-pop .cat.zero { cursor: default; }
    .pay-pop .cat.zero .nm, .pay-pop .cat.zero .amt, .pay-pop .cat.zero .emp { color: var(--faint); }
    .pay-pop .cat.zero .cv { visibility: hidden; }

    /* total row — same 4-column grid as .cat, so the amount lines up in the
       same column as the line items above it. Appears only here — never in
       the header too. */
    .pay-pop .tot { display: grid; grid-template-columns: 1fr 44px 88px 16px; align-items: center; gap: 8px; padding: 11px 15px; border-top: 1px solid var(--line); background: var(--line-2); font-size: 13px; font-weight: 600; color: var(--ink); }
    .pay-pop .tot .amt { text-align: right; font-variant-numeric: tabular-nums; }

    /* employee drill-down (hidden until .cat is opened; built lazily on first open) */
    .pay-pop .emp-list { display: none; background: var(--teal-soft); border-bottom: 1px solid var(--line-2); }
    .pay-pop .emp-list.on { display: block; }
    .pay-pop .emp-scroll { max-height: 190px; overflow-y: auto; }
    .pay-pop .er { display: grid; grid-template-columns: 26px 1fr 88px; align-items: center; gap: 10px; padding: 8px 15px 8px 22px; border-bottom: 1px solid rgba(1,70,83,.07); }
    .pay-pop .er:last-of-type { border-bottom: none; }
    .pay-pop .av { width: 26px; height: 26px; border-radius: 50%; background: var(--teal-3); color: var(--teal); font-size: 9.5px; font-weight: 600; display: grid; place-items: center; letter-spacing: .2px; overflow: hidden; flex: none; }
    .pay-pop .av img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .pay-pop .who { min-width: 0; display: flex; flex-direction: column; gap: 1px; line-height: 1.3; }
    .pay-pop .who .n { display: block; font-size: 12px; color: var(--ink); font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .pay-pop .who .dp { display: block; font-size: 10.5px; color: var(--faint); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .pay-pop .ev { text-align: right; font-size: 12px; font-weight: 500; font-variant-numeric: tabular-nums; }
    .pay-pop .viewall { padding: 10px 15px 12px 22px; }
    .pay-pop .viewall a { font-size: 11.5px; font-weight: 600; color: var(--teal); text-decoration: none; }
    .pay-pop .viewall a:hover { text-decoration: underline; }
    .pay-pop .viewall.empty-line { color: var(--faint); font-size: 11.5px; padding: 10px 15px 12px 22px; }
    .pay-pop .emp-scroll::-webkit-scrollbar { width: 6px; }
    .pay-pop .emp-scroll::-webkit-scrollbar-thumb { background: var(--line); border-radius: 3px; }

    /* net payout — the single largest number in the popover (hero), 20px —
       do not enlarge; a previous build ran this at 27px and it overflowed. */
    .pay-pop .net { margin-top: 14px; border: 1px solid var(--teal-3); background: var(--teal-soft); border-radius: 14px; padding: 15px 16px; display: flex; align-items: center; justify-content: space-between; }
    .pay-pop .net .nl { font-size: 12px; font-weight: 600; color: var(--ink); }
    .pay-pop .net .nl em { display: block; font-style: normal; font-size: 10px; font-weight: 400; color: var(--faint); margin-top: 2px; }
    .pay-pop .net .nv { font-size: 20px; font-weight: 700; color: var(--teal); letter-spacing: -.4px; }

    /* activity feed: same .er row shape, enriched with a status pill on
       the name line and time/note on the department line (this feed
       carries more per-row context than the earnings/deductions
       drill-down, so those two lines each pack in one extra detail). Status
       pills are a presence/attendance signal, not a financial amount, so
       they're outside this revision's "ink + teal only" scope for money
       figures and keep their existing colors. */
    .pay-pop .er .pill { font-size: 8.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; padding: 1px 6px; border-radius: 100px; margin-left: 6px; vertical-align: 1px; }
    .pay-pop .er .pill.present { background: var(--peb-ok-bg); color: var(--peb-ok); }
    .pay-pop .er .pill.absent { background: var(--error-bg); color: var(--error); }
    .pay-pop .er .pill.ot { background: #FFF6CC; color: #8a6d00; }
    .pay-pop .er .pill.dayoff { background: var(--line-2); color: var(--faint); }

    /* empty state */
    .pay-pop .empty { margin-top: 16px; border: 1px dashed var(--line); border-radius: 14px; padding: 38px 20px; text-align: center; background: #fff; }
    .pay-pop .empty .glyph { width: 46px; height: 46px; margin: 0 auto 14px; border-radius: 50%; background: var(--teal-soft); display: grid; place-items: center; }
    .pay-pop .empty .glyph i { width: 16px; height: 16px; border: 1.5px solid var(--teal); border-radius: 50%; position: relative; opacity: .85; display: block; }
    .pay-pop .empty .glyph i::after { content: ""; position: absolute; left: 50%; top: 3px; width: 1.5px; height: 5px; background: var(--teal); transform-origin: bottom; border-radius: 2px; }
    .pay-pop .empty .et { font-size: 13px; font-weight: 500; color: var(--ink); }
    .pay-pop .empty .es { font-size: 11.5px; color: var(--faint); margin-top: 4px; line-height: 1.5; }

    .pay-pop .foot { margin-top: 14px; font-size: 10.5px; color: var(--faint); line-height: 1.5; }

    /* loading / error states — not shown in the reference (its demo only
       covers the loaded states) but needed for the real fetch lifecycle */
    .pay-pop .loading, .pay-pop .empty.err-empty { font-size: 12.5px; color: var(--faint); padding: 20px 0; text-align: center; }
    .pay-pop .err { font-size: 12.5px; color: var(--error); padding: 16px 0; text-align: center; }

    /* "Load more" — activity feed pagination, styled as a quiet pill to
       match .viewall's link language rather than a heavy button */
    .pay-pop .more-wrap { text-align: center; padding: 10px 0 4px; }
    .pay-pop .more-btn { border: 1px solid var(--line); background: #fff; border-radius: 100px; font-size: 11.5px; font-weight: 600; color: var(--teal-2); padding: 6px 16px; cursor: pointer; font-family: inherit; }
    .pay-pop .more-btn:hover { background: var(--teal-soft); }
    .pay-pop .more-btn:disabled { opacity: .5; cursor: default; }
    .pay-pop .more-count { font-size: 10.5px; color: var(--faint); margin-top: 6px; }

    @media (prefers-reduced-transparency: reduce) {
        .pay-pop { background: #fff; backdrop-filter: none; -webkit-backdrop-filter: none; }
        .pay-pop::before { display: none; }
    }
    @media (prefers-reduced-motion: reduce) {
        .pay-pop::before { display: none; }
        .pay-pop * { transition: none !important; }
    }
</style>
