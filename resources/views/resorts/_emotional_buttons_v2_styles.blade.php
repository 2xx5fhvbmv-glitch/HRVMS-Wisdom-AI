{{--
    Presentation-only CSS for the emotional-button design system, shared
    across every remaining module (Accommodation, Incident, Survey, Reports,
    Support, Visa, Grievance & Disciplinary, File Management, SOS,
    Compliance). Same system already shipped per-module-prefixed on
    Performance/Leave/Payroll/Talent Acquisition/Time & Attendance/Workforce
    Planning/Learning — this one is intentionally SHARED (one file, one
    `eb-` prefix) instead of ten near-identical per-module copies, since
    none of these ten modules has enough distinct button volume to justify
    its own partial. Scoped to `eb-` prefixed classes so nothing here leaks
    onto other modules still using btn-theme/btn-themeBlue/btn-danger/etc.

    :active rules are declared LAST, after every :hover rule — both are
    simultaneously true on a real click and the later-declared rule needs
    to win at equal specificity for the press/"zoom" feedback to show.
--}}
<style>
    .eb-btn-primary, .eb-btn-secondary, .eb-btn-positive,
    .eb-btn-accent, .eb-btn-hero, .eb-btn-neutral, .eb-btn-critical, .eb-btn-decline, .eb-btn-ghost {
        transition: transform .16s cubic-bezier(.2,.8,.2,1), box-shadow .16s ease, background .16s ease, border-color .16s ease;
    }
    .eb-btn-primary:focus-visible, .eb-btn-secondary:focus-visible, .eb-btn-positive:focus-visible,
    .eb-btn-accent:focus-visible, .eb-btn-hero:focus-visible, .eb-btn-neutral:focus-visible, .eb-btn-critical:focus-visible,
    .eb-btn-decline:focus-visible, .eb-btn-ghost:focus-visible {
        outline: 2px solid var(--teal);
        outline-offset: 2px;
    }

    /* Main forward action — Submit/Save/Update/Send.
       Button text stays literal #fff — it's contrast-on-a-solid-teal-
       button text, not a card/surface color, so it must NOT follow --card
       (which goes dark in Dark/Teal and would make the text disappear).
       Same reasoning applies to every other literal #fff/rgba(20,35,42,…)
       box-shadow below in this file — shadows stay dark-toned in every
       theme (same call Phase 1 made for --shadow's own dark override). */
    .eb-btn-primary { background: var(--teal); color: #fff; }
    .eb-btn-primary:hover { background: var(--teal); color: #fff; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.35); }

    /* Low-stakes, recedes on purpose — View/Back/Download/Export.
       #C9D6D7 border has no token match (not a near-duplicate of --line)
       — left literal. */
    .eb-btn-secondary { background: transparent; color: var(--teal); border: 1.5px solid #C9D6D7; }
    .eb-btn-secondary:hover { background: var(--paper); border-color: var(--teal); color: var(--teal); transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.18); }

    /* Affirming — Approve, Resolve, Restore, Mark Safe/Complete. */
    .eb-btn-positive { background: var(--positive-bg); color: var(--positive); }
    .eb-btn-positive:hover { background: var(--positive); color: #fff; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.25); }

    /* Additive/construction, general — Create/Add New/Upload/Raise Request. */
    .eb-btn-accent { background: var(--teal-3); color: var(--teal-2); }
    .eb-btn-accent:hover { background: var(--teal-2); color: #fff; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.25); }

    /* The brand lime — reserved for a single standout hero CTA per page,
       not blanketed across every additive button. --lime is unchanged
       across all 3 themes (Phase 1), so this is safe to tokenize; the
       #17260a text has no token match (a deliberately near-black-green,
       not neutral) — left literal. */
    .eb-btn-hero { background: var(--lime); color: #17260a; }
    .eb-btn-hero:hover { background: var(--lime); color: #17260a; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.35); }

    /* Backing out / low-stakes state change, not a decision — Cancel,
       Close, Clear, Dismiss. */
    .eb-btn-neutral { background: var(--neutral-bg); color: var(--darkblack); }
    .eb-btn-neutral:hover { background: var(--teal-soft); color: var(--darkblack); transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.18); }

    /* The hard-stop decision — Reject, Delete, Escalate. */
    .eb-btn-critical { background: var(--critical-bg); color: var(--critical); }
    .eb-btn-critical:hover { background: var(--critical); color: #fff; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.25); }

    /* A considered negative response inside a formal flow (Decline an
       offer/contract/interview invite) — distinct from Critical's "delete
       a record" urgency: outlined, not filled, so it doesn't read as an
       alarm the way a solid-red destructive action does. #FFC2B3 border
       has no token match — left literal. */
    .eb-btn-decline { background: transparent; color: var(--critical); border: 1.5px solid #FFC2B3; }
    .eb-btn-decline:hover { background: var(--critical); border-color: var(--critical); color: #fff; transform: translateY(-2px); box-shadow: 0 8px 18px -8px rgba(20,35,42,.25); }

    /* Quiet icon-only trigger — kebab/"..." menus and other low-emphasis
       toggles that shouldn't compete visually with real actions on the
       page. No idle color at all; only a faint hover/press affordance.
       The rgba(93,111,117,…) hover/active tints are --muted's exact RGB,
       but no --muted-rgb primitive exists (only --teal-rgb was added in
       Phase 0/1, and this phase adds no new tokens) — left literal. */
    .eb-btn-ghost { background: transparent; border: none; color: var(--muted); }
    .eb-btn-ghost:hover { background: rgba(93,111,117,.1); color: var(--ink); transform: none; box-shadow: none; }
    .eb-btn-ghost:active { background: rgba(93,111,117,.16); transform: none; }

    .eb-btn-primary:active, .eb-btn-secondary:active, .eb-btn-positive:active,
    .eb-btn-accent:active, .eb-btn-hero:active, .eb-btn-neutral:active, .eb-btn-critical:active, .eb-btn-decline:active {
        transition-duration: .07s;
        transform: translateY(0) scale(.94);
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }

    /* Disabled state — without this, the sitewide `.btn[disabled]` rule
       (background: #6fa329, an olive green unrelated to any brand color)
       wins on specificity and silently repaints any disabled button in
       this system that color. */
    .eb-btn-primary[disabled], .eb-btn-secondary[disabled], .eb-btn-positive[disabled],
    .eb-btn-accent[disabled], .eb-btn-hero[disabled], .eb-btn-neutral[disabled], .eb-btn-critical[disabled],
    .eb-btn-decline[disabled], .eb-btn-ghost[disabled] {
        opacity: .55;
        cursor: not-allowed;
        transform: none;
    }
    .eb-btn-primary[disabled] { background: var(--teal); color: #fff; }
    .eb-btn-secondary[disabled] { background: transparent; color: var(--teal); }
    .eb-btn-positive[disabled] { background: var(--positive-bg); color: var(--positive); }
    .eb-btn-accent[disabled] { background: var(--teal-3); color: var(--teal-2); }
    .eb-btn-hero[disabled] { background: var(--lime); color: #17260a; }
    .eb-btn-neutral[disabled] { background: var(--neutral-bg); color: var(--darkblack); }
    .eb-btn-critical[disabled] { background: var(--critical-bg); color: var(--critical); }
    .eb-btn-decline[disabled] { background: transparent; color: var(--critical); }

    /* .page-hedding sits directly on the dark teal body::before band
       (var(--teal), 315px tall behind every page header — itself already
       tokenized, so it lightens correctly in Dark/Teal). Secondary's
       transparent-bg + dark-teal-text combo disappears into that band —
       only the faint light border shows. Scoped override: Secondary keeps
       its normal look everywhere else (inside a .card, which has its own
       opaque white background), just goes light-on-teal here instead.
       Left literal (not --card): this is contrast-on-the-teal-band white,
       not a surface color — --card goes dark in Dark/Teal, which would
       break this exact override. White reads fine against the band's
       Dark/Teal teal values too (both still dark/saturated enough). */
    .page-hedding .eb-btn-secondary { color: #fff; border-color: rgba(255,255,255,.55); }
    .page-hedding .eb-btn-secondary:hover { background: rgba(255,255,255,.14); border-color: #fff; color: #fff; }

    /* Row-action icon for the hard-stop decision (Delete/Reject/Escalate) —
       the shared global .btnIcon-danger is an old dark red (#A90000), not
       the agreed brand Critical scarlet; this scoped class replaces it
       wherever these modules render a delete/reject icon, without touching
       the global class other modules still rely on. rgba(255,36,0,.09) is
       --critical's exact RGB as a tint — no --critical-rgb primitive
       exists (this phase adds no new tokens), left literal. */
    .eb-icon-critical { color: var(--critical); background: rgba(255,36,0,.09); }
    .eb-icon-critical:hover { color: #fff; background: var(--critical); }

    /* Row-action icon for a low-stakes dismissal (cancel an inline edit,
       not a delete) — distinct from Critical so "cancel" doesn't read as
       alarming as "delete". #6b6b6b/rgba(107,107,107,…) are a distinct
       neutral gray with no close token match (not near --muted's
       #5D6F75) — left literal. */
    .eb-icon-neutral { color: #6b6b6b; background: rgba(107,107,107,.09); }
    .eb-icon-neutral:hover { color: #fff; background: #6b6b6b; }
</style>
