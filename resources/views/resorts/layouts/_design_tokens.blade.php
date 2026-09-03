<style>
:root {
  /* The portal is light-themed only — no dark palette exists yet. Without this,
     browsers/OSes reporting prefers-color-scheme:dark will heuristically
     repaint light backgrounds as dark at the paint layer (invisible to
     getComputedStyle), crushing contrast on every white card/table row. */
  color-scheme: light;
  --teal:#014653; --teal-2:#035b6c; --teal-3:#E6F0F1; --teal-soft:#F5F8F8; --lime:#E0FF02;
  --paper:#F9F8F1; --ink:#14232A; --muted:#5D6F75; --faint:#93A4A9;
  --line:#E2EBEC; --line-2:#EEF4F4; --card:#FFFFFF; --neutral-bg:#DEDEDE;
  --positive:#1F9D6B; --positive-bg:#E9F7F0;
  --warning:#D98A00; --warning-bg:#FBF0DC;
  --critical:#FF2400; --critical-bg:#FFDED9;
  --error:#E5573F; --error-bg:#FDEEEB;
  --grad-celebrate:linear-gradient(135deg,var(--teal),var(--lime));
  --grad-celebrate-hover:linear-gradient(135deg,#013641,#c7e102);

  /* Phase 0 consolidation additions — colours that previously only lived
     in public/resorts_assets/css/default.css's own :root block, hoisted
     here unchanged so that file's block (and everything else) can point
     at one place. Kept as their own tokens, not merged into a
     similar-looking existing one: --success/--danger are visibly
     different shades from --positive/--critical/--error and are used by
     different call sites, so unifying them is a design decision for a
     later phase, not a side effect of this refactor. */
  --aqua:#2EACB3; --teal-deep:#013842;
  --darkblack:#222222; --black:#000000;
  --success:#0CAF00; --danger:#C80000;
  /* Primitive for the many rgba(1,70,83,.0x) teal tints scattered across
     the app (== --teal). Not used yet — Phase 2 rewrites those tints as
     rgba(var(--teal-rgb), .0x). */
  --teal-rgb:1,70,83;

  /* Phase 1 additions — the page background was hardcoded in default.css
     (body{background:hsla(190,98%,16%,.05)}), hoisted here so Dark/Teal
     can override it; light value is that exact same hsla(), unchanged.
     --shadow: the card box-shadow recipe every redesigned page already
     hardcodes verbatim (0 1px 2px rgba(1,70,83,.04),0 10px 26px
     rgba(1,70,83,.05)) — added here with its light value only for now;
     repointing those existing declarations to var(--shadow) is Phase 2
     migration work, same category as the rest of the 85% hardcoded
     colours, not done in this pass. */
  --page:hsla(190,98%,16%,.05);
  --shadow:0 1px 2px rgba(1,70,83,.04),0 10px 26px rgba(1,70,83,.05);

  /* Phase 2b addition — Rejected/Missing status pills (approval-chain UI)
     used a distinct brick-red with no existing token; #C23A3A is visibly
     different from --critical/--error, so it gets its own token rather
     than being folded into either. --rejected-bg's Light value is the
     exact current pill-background literal from
     _ta_widgets_v2_styles.blade.php's .appr-chain-pill-rejected rule
     (rgba(194,58,58,.1)) — the sibling .appr-chain-pill-missing uses two
     other alphas (.08 background, .45 border) for the same base color;
     those are genuinely different values, not migrated to this token,
     left literal. */
  --rejected:#C23A3A; --rejected-bg:rgba(194,58,58,.1);

  /* Phase 2c addition — #A90000 is the app's actual live danger colour
     for buttons/badges/icons/progress bars in default.css (.btn-danger,
     .badge-danger, .btnIcon-danger, .progress-themeDanger — 17 raw
     occurrences), distinct from the sparsely-used --danger (#C80000).
     Given its own token rather than merged into --danger (Δ31/255,
     well past the near-duplicate bar). Dark/Teal follow the same
     lighten-for-legibility treatment already used by --critical/--error. */
  --danger-strong:#A90000;
}

/* ==================================================================
   Phase 1 — Dark & Teal theme overrides. Light above is unchanged and
   stays the default (no [data-theme] attribute). Selecting a theme sets
   data-theme="dark"|"teal" on <html> (see partials._theme_engine) — an
   attribute selector outranks a bare :root in specificity, so these
   reliably win regardless of source order.
   Only the SSOT's own tokens are covered. The ~85% of colours that are
   still hardcoded per-page (see docs/theme-readiness-audit.md) will not
   react to a theme switch until Phase 2 migrates them — expected, not a
   bug in this phase.
   ================================================================== */
html[data-theme="dark"], html[data-theme="teal"] { color-scheme: dark; }

html[data-theme="dark"] {
  --page:#0E1A1E; --card:#16262C;
  --ink:#EAF0F1; --muted:#9BADB2; --faint:#7A8E94;
  --line:#243940; --line-2:#1B2D33;
  --teal:#1C8092; --teal-2:#2A9AAE; --teal-3:#183A42; --teal-soft:#16272D;
  --teal-rgb:28,128,146;
  --positive:#57B98A; --positive-bg:#153026;
  --warning:#D6A44F; --warning-bg:#2E2413;
  --critical:#FF6B54; --critical-bg:#3A1E18;
  --error:#F0705A; --error-bg:#3A1E18;
  --aqua:#3FC0C8; --neutral-bg:#2A3E45;
  --shadow:0 1px 2px rgba(0,0,0,.45), 0 12px 30px rgba(0,0,0,.4);
  --rejected:#E67264; --rejected-bg:#38201E;
  --danger-strong:#FF4242;
  /* Regression-audit fix — these 6 tokens had no Dark override, so
     anything using them kept its Light colour on a dark background.
     --darkblack/--black: pure text/icon roles everywhere except 3
     "always dark fill" spots in default.css (a slick-arrow circle behind
     a white icon, a button hover invert, an upload-progress dot) — those
     3 were repointed to the literal #222222 instead of following this
     token, so they keep looking the same in every theme; see that
     file's inline comments. --success/--danger: single/near-single
     text|badge|button roles, lightened to match the --critical/--error
     dark family. --paper: background-only (secondary-button hover,
     muted badges) — matched to --card so hovering doesn't produce a
     bright light patch on a dark page. --teal-deep: a dropdown-menu
     surface (resorts/layouts/css.blade.php) that measured ~1.2:1 against
     the dark page/card — barely visible as its own surface — given a
     modest lift for real separation, still a dark teal, not lightened
     to text-legible levels (nothing sits directly on it but white text,
     which already reads fine at the Light value too). */
  --darkblack:#EAF0F1; --black:#EAF0F1;
  --success:#3FD98A; --danger:#F0705A;
  --paper:#16262C; --teal-deep:#175F6E;
}

html[data-theme="teal"] {
  --page:#062830; --card:#0B3742;
  --ink:#EAF3F4; --muted:#8DA9AF; --faint:#6C8C93;
  --line:#154853; --line-2:#0D3641;
  --teal:#2FA6B6; --teal-2:#3CB9C8; --teal-3:#123E48; --teal-soft:#0D3742;
  --teal-rgb:47,166,182;
  --positive:#57B98A; --positive-bg:#0E3A30;
  --warning:#D6A44F; --warning-bg:#33291A;
  --critical:#FF6B54; --critical-bg:#3E2620;
  --error:#F0705A; --error-bg:#3E2620;
  --aqua:#4FD0D8; --neutral-bg:#174049;
  --shadow:0 1px 2px rgba(0,0,0,.45), 0 12px 30px rgba(0,0,0,.4);
  --rejected:#E67264; --rejected-bg:#3C2320;
  --danger-strong:#FF4242;
  --darkblack:#EAF3F4; --black:#EAF3F4;
  --success:#3FD98A; --danger:#F0705A;
  --paper:#0B3742; --teal-deep:#175F6E;
}
</style>
