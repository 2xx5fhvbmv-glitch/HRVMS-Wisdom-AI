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
</style>
