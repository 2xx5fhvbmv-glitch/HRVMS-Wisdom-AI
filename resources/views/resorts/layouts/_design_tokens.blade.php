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
}
</style>
