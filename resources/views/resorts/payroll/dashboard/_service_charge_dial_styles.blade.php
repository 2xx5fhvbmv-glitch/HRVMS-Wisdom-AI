{{--
    Service Charges — radial year-dial widget (Payroll HR Dashboard).
    `scd-` prefixed classes so nothing here collides with this app's large
    global CSS (in particular `.card`/`.doughnut-label`, both reused by
    100+ other dashboard cards). Tokens + the card-chrome refinement are
    scoped to `.card-serviceCharges` — a class already unique to this one
    widget — rather than touching the shared `.card` rule everything else
    on the dashboard still uses.
--}}
<style>
    .card-serviceCharges{
        --teal:#014653; --teal-2:#035b6c; --teal-3:#e6f0f1; --teal-soft:#f1f7f7;
        --ink:#14232A; --g1:#3A4145; --g2:#6B7378; --g3:#99A1A5; --g4:#C7CDCF; --g6:#F4F5F5;
        --line:#E2EBEC; --line-2:#EEF4F4;
        border-radius:16px !important; border:1px solid var(--line); box-shadow:0 1px 2px rgba(1,70,83,.04),0 10px 26px rgba(1,70,83,.05) !important;
    }
    .card-serviceCharges .card-title{ margin-bottom:0; }
    .card-serviceCharges .card-title h3{ font-size:15px; font-weight:600; color:var(--ink); }
    .scd-rule{ height:1px; background:var(--line-2); margin:14px -20px 0; }

    .scd-dialwrap{ position:relative; width:100%; height:clamp(220px,26vw,260px); overflow:hidden; margin-top:6px; }
    .scd-dialwrap svg{ display:block; width:100%; height:100%; }
    .scd-bar{ cursor:pointer; transition:opacity .12s; }
    .scd-dialwrap:hover .scd-bar{ opacity:.4; }
    .scd-dialwrap .scd-bar:hover{ opacity:1; }
    .scd-mlabel{ font-size:10px; fill:var(--g2); font-family:'Poppins',sans-serif; font-weight:600; }

    .scd-foot{ display:flex; align-items:center; justify-content:center; gap:0; border-top:1px solid var(--line-2); margin:10px -20px -20px; padding:14px 20px; }
    .scd-stat{ flex:1; text-align:center; }
    .scd-stat .scd-v{ font-size:16px; font-weight:600; color:var(--ink); letter-spacing:-.01em; }
    .scd-stat .scd-l{ font-size:10.5px; font-weight:500; color:var(--g2); margin-top:1px; }
    .scd-stat + .scd-stat{ border-left:1px solid var(--line-2); }

    .scd-tip{ position:fixed; z-index:50; pointer-events:none; background:var(--ink); color:#fff; font-size:11.5px; font-weight:500;
        padding:6px 9px; border-radius:8px; opacity:0; transform:translate(-50%,-118%); transition:opacity .1s; white-space:nowrap; box-shadow:0 6px 18px rgba(0,0,0,.22); }
    .scd-tip b{ font-weight:600; }
    .scd-tip .scd-tv{ color:#c9f24a; }

    @media (prefers-reduced-motion: reduce){
        .scd-bar, .scd-tip{ transition:none; }
    }
</style>
