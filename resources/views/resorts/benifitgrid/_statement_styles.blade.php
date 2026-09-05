<style>
/* Benefit Grid statement — shared by the on-screen view and the print/
   download output (resorts.benifitgrid.pdf). Reads tokens from the app's
   live shared palette (resorts/layouts/_design_tokens.blade.php) — no
   local :root, no new colors. Dense/statement scale (FONT_SIZE_REFERENCE
   micro range), not the full-width dashboard scale — this is a compact
   printed-style document, not a dashboard.

   On-screen the sheet is fluid full-width (APP_LAYOUT_CONTAINER.md — no
   fixed max-width column); the centered/page-boxed look is print-only,
   handled entirely by the @media print block below. */

/* Download action button uses the shared eb-btn-secondary system
   (resorts._emotional_buttons_v2_styles) — same class every other
   module's Download/Back/Export header button uses, including its
   existing .page-hedding light-on-teal override. No custom button CSS
   needed here. */

/* the document sheet — fluid, spans the available width */
.bgst-sheet{background:var(--card);border:1px solid var(--line);border-radius:18px;box-shadow:var(--shadow);padding:40px 48px;font-family:'Poppins',sans-serif;color:var(--ink);font-size:14px;line-height:1.5}
.bgst-sheet .tnum{font-variant-numeric:tabular-nums}
@media(max-width:767px){.bgst-sheet{padding:28px 22px}}

/* header block */
.bgst-sheet .dochead{display:flex;align-items:center;gap:22px;padding-bottom:24px;border-bottom:2px solid var(--teal-3);flex-wrap:wrap}
.bgst-sheet .gradebadge{display:flex;flex-direction:column;align-items:center;justify-content:center;min-width:78px;min-height:78px;padding:8px 10px;border-radius:18px;background:linear-gradient(150deg,var(--teal),var(--teal-2));color:#fff;flex:none}
.bgst-sheet .gradebadge .g{font-size:20px;font-weight:600;line-height:1.15;letter-spacing:-.3px;text-align:center;word-break:break-word}
.bgst-sheet .gradebadge .gl{font-size:9px;font-weight:600;letter-spacing:.6px;text-transform:uppercase;opacity:.8;margin-top:5px}
.bgst-sheet .dh-main{flex:1;min-width:0}
.bgst-sheet .dh-cap{font-size:10.5px;font-weight:600;letter-spacing:.7px;text-transform:uppercase;color:var(--faint)}
.bgst-sheet .dh-facts{display:flex;flex-wrap:wrap;gap:8px 26px;margin-top:11px}
.bgst-sheet .dh-facts .f{font-size:13px;color:var(--muted)}
.bgst-sheet .dh-facts .f b{color:var(--ink);font-weight:600}
.bgst-sheet .stpill{display:inline-flex;align-items:center;gap:7px;font-size:12.5px;font-weight:600;padding:6px 13px;border-radius:20px;background:var(--positive-bg);color:var(--positive);flex:none;text-transform:capitalize}
.bgst-sheet .stpill .dot{width:7px;height:7px;border-radius:50%;background:var(--positive)}
.bgst-sheet .stpill-muted{background:var(--line-2);color:var(--muted)}
.bgst-sheet .stpill-muted .dot{background:var(--faint)}
@media(max-width:640px){
  /* Stack the header instead of letting the pill center against a
     multi-line wrapped facts list (it visually overlapped mid-list). */
  .bgst-sheet .dochead{flex-direction:column;align-items:flex-start;gap:14px}
  .bgst-sheet .stpill{align-self:flex-start}
}

/* section — soft tinted panel so sections read as separated blocks on
   the wide fluid sheet without heavy borders/shadows. #F6FAFA/#EAF1F1 in
   the reference map to the app's own --teal-soft/--teal-3 (same hue,
   already-live tokens) rather than adding two near-duplicate colors. */
.bgst-sheet .sec{margin-top:14px;background:var(--teal-soft);border:1px solid var(--teal-3);border-radius:14px;padding:18px 22px}
.bgst-sheet .sec:first-of-type{margin-top:22px}
.bgst-sheet .sec-h{display:flex;align-items:center;gap:11px;margin-bottom:14px}
.bgst-sheet .sec-h .ic{width:28px;height:28px;border-radius:8px;background:var(--card);color:var(--teal);display:grid;place-items:center;flex:none}
.bgst-sheet .sec-h .t{font-size:14px;font-weight:600;color:var(--ink);white-space:nowrap}
.bgst-sheet .sec-h .rule{flex:1;height:1px;background:var(--line)}

/* definition rows — fan out across the wide canvas: 3 → 2 → 1 */
.bgst-sheet .dl{display:grid;grid-template-columns:repeat(3,1fr);gap:0 48px}
@media(max-width:1100px){.bgst-sheet .dl{grid-template-columns:1fr 1fr}}
@media(max-width:640px){.bgst-sheet .dl{grid-template-columns:1fr}}
.bgst-sheet .di{display:flex;align-items:baseline;justify-content:space-between;gap:16px;padding:10px 0;border-bottom:1px dashed var(--line-2)}
.bgst-sheet .di .k{font-size:13px;color:var(--muted);line-height:1.4}
.bgst-sheet .di .v{font-size:14px;font-weight:600;color:var(--ink);text-align:right;white-space:nowrap}
.bgst-sheet .di .v .u{font-size:12px;font-weight:500;color:var(--muted);margin-left:2px}
.bgst-sheet .di .v.na{color:var(--faint);font-weight:500;font-style:italic;white-space:normal}
.bgst-sheet .di .v.inc{color:var(--positive);display:inline-flex;align-items:center;gap:6px}
.bgst-sheet .di .v.pct{color:var(--teal)}
.bgst-sheet .di.full{grid-column:1 / -1;flex-direction:column;align-items:stretch;border-bottom:none;padding-bottom:2px}
.bgst-sheet .di.full .k{margin-bottom:10px}
.bgst-sheet .chips{display:flex;flex-wrap:wrap;gap:8px}
.bgst-sheet .chip{font-size:12.5px;font-weight:500;color:var(--ink);background:var(--teal-soft);border:1px solid var(--teal-3);border-radius:20px;padding:6px 13px}

/* download-only: resort letterhead + footer (same branding data the old
   PDF showed — carried forward, just restyled to match the statement).
   Fluid, same as the sheet — print re-boxes everything uniformly below. */
.bgst-letterhead{display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap;margin-bottom:20px}
.bgst-letterhead img{max-height:44px;max-width:160px;object-fit:contain}
.bgst-letterhead .addr{text-align:right;font-size:11.5px;line-height:1.5;color:var(--muted)}
.bgst-letterhead .addr b{display:block;font-size:14px;font-weight:600;color:var(--ink);margin-bottom:2px}
.bgst-footer{text-align:center;font-size:11px;color:var(--muted);margin-top:24px}

/* ===== download / print: same statement, re-boxed to a portrait page ===== */
@media print{
  @page{margin:16mm}
  html,body{background:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact}
  .bgst-page{padding:0}
  .bgst-sheet{border:none;border-radius:0;box-shadow:none;padding:0}
  .bgst-sheet .dl{grid-template-columns:1fr 1fr;gap:0 32px}
  .bgst-sheet .dochead,.bgst-sheet .sec-h{break-inside:avoid}
  .bgst-sheet .sec{break-inside:avoid;margin-top:22px;background:#fff;border:none;border-radius:0;padding:0}
  .bgst-sheet .di{break-inside:avoid}
}
</style>
