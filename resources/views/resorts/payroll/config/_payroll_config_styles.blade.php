{{-- Shared design system for the Payroll Configuration screen. One
     `pc-` prefixed stylesheet, included once by index.blade.php. Uses the
     app's real design tokens (var(--teal) etc., defined globally in
     resources/views/resorts/layouts/_design_tokens.blade.php) with literal
     fallbacks so this still renders correctly if a token is ever renamed.
     The Cutoff day / Limit type selects reuse the app's own shared .dd
     dropdown component (resorts._dropdown_styles / _dropdown_script) —
     no select styling duplicated here. --}}
<style>
.pc-wrap { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; align-items: start; }
.pc-col { display: flex; flex-direction: column; gap: 16px; min-width: 0; }
@media (max-width: 900px) { .pc-wrap { grid-template-columns: 1fr; } }

.pc-card { background: #fff; border-radius: 18px; box-shadow: 0 1px 2px rgba(1,70,83,.05), 0 16px 40px rgba(1,70,83,.10); padding: 22px 24px; display: flex; flex-direction: column; }

.pc-ct { display: flex; align-items: center; gap: 11px; margin-bottom: 18px; flex-wrap: wrap; }
.pc-ct h2 { font-size: 18px; font-weight: 600; color: var(--ink, #14232A); }
.pc-ct .pc-spacer { flex: 1; }

/* form */
.pc-grid { display: grid; gap: 18px; }
.pc-g2 { grid-template-columns: 1fr 1fr; }
.pc-fspan { grid-column: 1 / -1; }
@media (max-width: 480px) { .pc-g2 { grid-template-columns: 1fr; } }
.pc-f label { display: flex; align-items: center; gap: 7px; font-size: 13px; font-weight: 600; letter-spacing: .2px; text-transform: uppercase; color: #6B7378; margin-bottom: 8px; }
.pc-f label .pc-req { color: #C7CDCF; font-weight: 400; }
.pc-inp { width: 100%; border: 1px solid var(--line, #EEF2F2); border-radius: 12px; padding: 13px 15px; font: inherit; font-size: 15px; color: var(--ink, #14232A); background: #fff; outline: none; transition: border-color .14s, box-shadow .14s; }
.pc-inp::placeholder { color: #99A1A5; }
.pc-inp:focus { border-color: #cfe0e1; box-shadow: 0 0 0 3px rgba(1,70,83,.05); }

/* segmented toggle (Currency) */
.pc-seg { display: inline-flex; background: #F7F8F8; border: 1px solid var(--line, #EEF2F2); border-radius: 12px; padding: 3px; gap: 2px; }
.pc-seg button { border: none; background: none; font: inherit; font-size: 14px; font-weight: 600; color: #6B7378; padding: 9px 20px; border-radius: 9px; cursor: pointer; transition: background .14s, color .14s; }
.pc-seg button.on { background: var(--teal, #014653); color: #fff; }

/* buttons */
.pc-cfoot { display: flex; justify-content: flex-end; margin-top: 22px; padding-top: 20px; border-top: 1px solid var(--line, #EEF2F2); }
.pc-submit { background: var(--teal, #014653); color: #fff; border: none; border-radius: 12px; padding: 13px 30px; font: inherit; font-size: 15px; font-weight: 600; cursor: pointer; transition: transform .16s cubic-bezier(.34,1.56,.64,1), box-shadow .16s ease, background .16s ease; }
.pc-submit:hover { background: var(--teal-2, #035b6c); transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.35); }
.pc-submit:active { transition-duration: .07s; transform: translateY(0) scale(.97); }
.pc-submit:disabled { opacity: .6; cursor: default; transform: none; box-shadow: none; }
.pc-ghostbtn { background: #fff; color: #3A4145; border: 1px solid var(--line, #EEF2F2); border-radius: 11px; padding: 9px 15px; font: inherit; font-size: 13.5px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; text-decoration: none; transition: border-color .14s, color .14s; }
.pc-ghostbtn:hover { border-color: #C7CDCF; color: var(--teal, #014653); }
.pc-ghostbtn svg { width: 15px; height: 15px; }
.pc-tealbtn { background: var(--teal, #014653); color: #fff; border: none; border-radius: 11px; padding: 10px 16px; font: inherit; font-size: 13.5px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; transition: background .14s; }
.pc-tealbtn:hover { background: var(--teal-2, #035b6c); }
.pc-tealbtn svg { width: 15px; height: 15px; }
@media (prefers-reduced-motion: reduce) { .pc-submit, .pc-ghostbtn, .pc-tealbtn, .pc-drop, .pc-seg button { transition: none; } }

/* dropzone (click-to-upload only — no drag/drop) */
.pc-drop { border: 1.5px dashed #C7CDCF; border-radius: 14px; padding: 26px 20px; text-align: center; cursor: pointer; transition: border-color .14s, background .14s; }
.pc-drop:hover { border-color: var(--teal, #014653); background: var(--teal-soft, #F5F8F8); }
.pc-drop .pc-dropic { width: 40px; height: 40px; border-radius: 11px; background: var(--teal-soft, #F5F8F8); color: var(--teal, #014653); display: grid; place-items: center; margin: 0 auto 12px; }
.pc-drop .pc-dropic svg { width: 20px; height: 20px; }
.pc-drop .pc-t { font-size: 14px; font-weight: 400; color: var(--ink, #14232A); }
.pc-drop .pc-s { font-size: 12.5px; color: #99A1A5; margin-top: 4px; }

/* table (Recent deductions) */
.pc-tbl { width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid var(--line, #EEF2F2); border-radius: 14px; overflow: hidden; }
.pc-tbl th { background: var(--teal-soft, #F5F8F8); text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; color: #6B7378; padding: 12px 15px; border-bottom: 1px solid var(--line, #EEF2F2); }
.pc-tbl td { padding: 12px 15px; border-bottom: 1px solid var(--line-2, #F3F6F6); font-size: 14px; color: #3A4145; vertical-align: middle; }
.pc-tbl tr:last-child td { border-bottom: none; }
.pc-tbl .pc-num { text-align: right; font-variant-numeric: tabular-nums; }
.pc-tbl tbody tr:hover td { background: #fcfdfd; }
.pc-tbl .pc-name { font-weight: 500; color: var(--ink, #14232A); }
.pc-chip { display: inline-block; background: #F7F8F8; color: #3A4145; font-size: 12px; font-weight: 500; padding: 3px 10px; border-radius: 20px; }
.pc-cur { color: #6B7378; font-weight: 400; }
.pc-empty { padding: 24px 14px; text-align: center; color: #99A1A5; font-size: 13.5px; }
</style>
