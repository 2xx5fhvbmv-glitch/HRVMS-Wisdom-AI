{{--
    Shared WAI Insight modals — ONE recommendation modal + shared frosted
    styling for every module's own details modal(s). Used across every
    dashboard that has a WAI Insights card (Learning, Leave, Grievance &
    Disciplinary, Visa, Survey, Accommodation, Incident, Payroll,
    Performance, Talent Acquisition, Time & Attendance).

    Trigger contract (put these on each insight row):
      - <button class="lnk-rec" data-title="..." data-rec="..."
                data-details="theDetailsModalDomId">View recommendation &rarr;</button>
        (only render when a recommendation string actually exists)
      - <a class="lnk" data-details="theDetailsModalDomId">View details &rarr;</a>
    Each module keeps its OWN details modal markup/table (column shapes
    differ per module — Learning's Program/Participants/Rate table doesn't
    fit Grievance's status breakdown or Payroll's trend figures) — only the
    outer chrome (.wai-backdrop/.wai-modal/.m-kicker/.mt/.m-tablewrap/
    .m-table) is shared, so every module's table gets the same frosted look
    without forcing an incompatible column set onto it.

    Both .wai-backdrop and .wai-modal are namespaced, not reused from
    Bootstrap's own bare `.modal`/`.modal-backdrop`. Originally this backdrop
    WAS bare `.modal-backdrop` — that seemed safe because Bootstrap's own
    `.modal-backdrop` rule doesn't touch `display`, so there was no direct
    property collision. What broke: Bootstrap's own `.modal{z-index:1060}`
    and this bare override's `z-index:1060` were an exact tie, so on any
    page that also has a genuine Bootstrap `.modal.fade` (most dashboards
    do, via some other, unrelated feature), Bootstrap's freshly-appended
    `.modal-backdrop` — later in the DOM at open time — painted on top of
    that OTHER modal's own content, since equal z-index resolves by DOM
    order. Namespacing both classes here removes any shared selector with
    Bootstrap's own modal system, so this can't happen again.
--}}
<style>
    .wai-backdrop { position: fixed; inset: 0; background: rgba(20,35,42,.28);
        backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
        display: flex; align-items: center; justify-content: center; padding: 24px; z-index: 1060;
        opacity: 0; pointer-events: none; transition: opacity .28s ease; }
    .wai-backdrop.open { opacity: 1; pointer-events: auto; }
    .wai-modal { position: relative; width: min(420px,100%); border-radius: 22px; padding: 32px 32px 28px;
        background: rgba(255,255,255,.82);
        backdrop-filter: blur(28px) saturate(160%); -webkit-backdrop-filter: blur(28px) saturate(160%);
        border: 1px solid rgba(255,255,255,.7);
        box-shadow: 0 24px 70px rgba(1,70,83,.20), 0 2px 8px rgba(1,70,83,.06);
        transform: translateY(14px) scale(.985); opacity: .6;
        transition: transform .3s cubic-bezier(.16,1,.3,1), opacity .3s ease;
        font-family: 'Poppins', sans-serif; }
    .wai-backdrop.open .wai-modal { transform: none; opacity: 1; }
    .wai-modal.wide { width: min(620px,100%); }
    .wai-modal .m-x { position: absolute; top: 20px; right: 20px; width: 30px; height: 30px; border-radius: 50%;
        background: transparent; border: none; color: var(--faint, #99A1A5); cursor: pointer; font-size: 15px;
        display: grid; place-items: center; transition: color .15s, background .15s; }
    .wai-modal .m-x:hover { background: var(--line-2, #EEF4F4); color: var(--ink, #14232A); }
    .wai-modal .m-kicker { display: flex; align-items: center; gap: 7px; font-size: 10.5px; font-weight: 600;
        text-transform: uppercase; letter-spacing: .9px; color: var(--muted, #6B7378); }
    .wai-modal .m-kicker .dot { width: 6px; height: 6px; border-radius: 50%; background: var(--teal, #014653); }
    .wai-modal .mt { font-size: 19px; font-weight: 600; letter-spacing: -.3px; color: var(--ink, #14232A); margin-top: 12px; line-height: 1.25; }
    .wai-modal .m-label { font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .9px; color: var(--teal, #014653); margin: 20px 0 8px; }
    .wai-modal .m-rec { font-size: 15px; color: var(--ink, #14232A); line-height: 1.6; }
    .wai-modal .m-foot { display: flex; justify-content: flex-end; margin-top: 28px; }
    .wai-modal .m-btn { border-radius: 12px; padding: 11px 20px; font-weight: 500; font-size: 13px;
        border: none; background: var(--teal, #014653); color: #fff; cursor: pointer; font-family: inherit; }
    .wai-modal .m-btn:hover { background: var(--teal-2, #035b6c); }
    .wai-modal .m-tablewrap { margin-top: 22px; border: 1px solid var(--line, #E2EBEC); border-radius: 14px; overflow: hidden; background: rgba(255,255,255,.55); }
    .wai-modal .m-tcap { font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .7px; color: var(--muted, #6B7378); padding: 12px 16px; border-bottom: 1px solid var(--line, #E2EBEC); }
    .wai-modal .m-tscroll { max-height: 280px; overflow-y: auto; }
    .wai-modal .m-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
    .wai-modal .m-table th { position: sticky; top: 0; background: rgba(248,250,250,.92); backdrop-filter: blur(6px);
        text-align: right; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px;
        color: var(--faint, #99A1A5); padding: 9px 16px; border-bottom: 1px solid var(--line, #E2EBEC); }
    .wai-modal .m-table th:first-child { text-align: left; }
    .wai-modal .m-table td { padding: 11px 16px; text-align: right; color: var(--ink, #14232A);
        border-bottom: 1px solid var(--line-2, #EEF4F4); font-variant-numeric: tabular-nums; }
    .wai-modal .m-table td:first-child { text-align: left; color: var(--ink, #14232A); font-weight: 500; }
    .wai-modal .m-table tr:last-child td { border-bottom: none; }
    .wai-modal .m-table .rate { font-weight: 700; }
    .wai-modal .m-table .rate.zero { color: var(--error, #E5573F); }
    .wai-modal .m-table .rate.full { color: var(--positive, #1F9D6B); }
    .wai-modal .m-table tr.attn td:first-child { box-shadow: inset 2px 0 0 var(--error, #E5573F); }
    .wai-modal .m-empty { font-size: 13px; color: var(--muted, #6B7378); margin: 20px 0 0; }

    /* People-row variant: swaps the first .m-table column for a photo-first
       avatar + name/ID identity cell, for any module's "view details" list
       whose rows are employees rather than metrics. Nest inside a normal
       .m-table — <td class="emp"><div class="emp">...</div></td> — first
       column stays left-aligned via the existing .m-table td:first-child
       rule above. */
    .wai-modal .m-table .emp { display: flex; align-items: center; gap: 11px; }
    .wai-modal .m-table .av { width: 34px; height: 34px; border-radius: 50%; flex: none; position: relative;
        display: grid; place-items: center; font-size: 11.5px; font-weight: 600;
        color: var(--teal, #014653); background: var(--teal-3, #E6F0F1); overflow: hidden; }
    .wai-modal .m-table .av img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
    .wai-modal .m-table .who { min-width: 0; }
    .wai-modal .m-table .who .nm { font-size: 13px; font-weight: 500; color: var(--ink, #14232A);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .wai-modal .m-table .who .id { font-size: 11px; color: var(--faint, #99A1A5); margin-top: 1px; }
    .wai-modal .m-table .wage .amt { font-weight: 600; color: var(--ink, #14232A); }
    .wai-modal .m-table .wage .cur { color: var(--faint, #99A1A5); margin-left: 3px; font-size: 11px; font-weight: 600; }
    .wai-modal .m-table .wage-missing { color: var(--error, #E5573F); font-weight: 600; }

    /* ---- Liquid Glass modal variant (Apple material) — data-list mode:
       no kicker, count folded into the caption bar, pointer-tracking
       specular highlight. An alternative to .wai-modal's plain frosted
       look — opt in per-modal by using .lg-modal instead of .wai-modal
       on the same .wai-backdrop scrim. That scrim's translucent-dark +
       blur is the reason this reads with real colour instead of
       collapsing to flat grey — Liquid Glass needs a dark+blurred scrim
       behind it, not an opaque one, to have anything to reflect. */
    .lg-modal { position: relative; width: min(452px,100%); border-radius: 22px; padding: 32px 32px 28px; overflow: hidden;
        background: rgba(255,255,255,.55);
        backdrop-filter: blur(22px) saturate(220%) brightness(1.06); -webkit-backdrop-filter: blur(22px) saturate(220%) brightness(1.06);
        border: 1px solid rgba(255,255,255,.45);
        box-shadow:
            inset 0 1px 1px rgba(255,255,255,.75),
            inset 0 -1px 2px rgba(255,255,255,.3),
            inset 0 0 44px rgba(255,255,255,.07),
            0 26px 74px rgba(0,0,0,.34);
        transform: translateY(14px) scale(.985); opacity: .6;
        transition: transform .3s cubic-bezier(.16,1,.3,1), opacity .3s ease, box-shadow .35s, backdrop-filter .35s;
        font-family: 'Poppins', sans-serif; }
    .wai-backdrop.open .lg-modal { transform: none; opacity: 1; }
    /* pointer-tracking specular highlight, driven by --mx/--my (set in
       the script block below) */
    .lg-modal::before { content: ""; position: absolute; inset: 0; border-radius: inherit; pointer-events: none; z-index: 0;
        background: radial-gradient(280px 220px at var(--mx,80%) var(--my,-5%), rgba(255,255,255,.4), transparent 60%); transition: opacity .3s; }
    /* thin top sheen line */
    .lg-modal::after { content: ""; position: absolute; left: 14%; right: 14%; top: 0; height: 1px; pointer-events: none; z-index: 0;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,.85), transparent); }
    .lg-modal:hover { backdrop-filter: blur(24px) saturate(240%) brightness(1.11); -webkit-backdrop-filter: blur(24px) saturate(240%) brightness(1.11);
        box-shadow: inset 0 1px 1px rgba(255,255,255,.85), inset 0 -1px 2px rgba(255,255,255,.35), inset 0 0 52px rgba(255,255,255,.1), 0 30px 82px rgba(0,0,0,.38); }
    .lg-modal .m-x, .lg-modal .mt, .lg-modal .m-sub, .lg-modal .m-tablewrap { position: relative; z-index: 1; }
    .lg-modal .m-x { position: absolute; top: 20px; right: 20px; width: 30px; height: 30px; border-radius: 50%;
        background: transparent; border: none; color: var(--faint, #99A1A5); cursor: pointer; font-size: 15px;
        display: grid; place-items: center; transition: background .15s, color .15s; }
    .lg-modal .m-x:hover { background: rgba(255,255,255,.5); color: var(--ink, #14232A); }
    .lg-modal .mt { font-size: 19px; font-weight: 600; letter-spacing: -.3px; color: var(--ink, #14232A); line-height: 1.25; padding-right: 28px; }
    .lg-modal .m-sub { font-size: 12.5px; color: var(--muted, #6B7378); margin-top: 5px; line-height: 1.45; }
    .lg-modal .m-tablewrap { margin-top: 22px; border: 1px solid rgba(255,255,255,.5); border-radius: 14px; overflow: hidden; background: rgba(255,255,255,.4); }
    .lg-modal .m-tcap { font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .7px; color: var(--muted, #6B7378); padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,.5); }
    .lg-modal .m-tscroll { max-height: 360px; overflow-y: auto; }
    .lg-modal .m-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .lg-modal .m-table th { position: sticky; top: 0; background: rgba(255,255,255,.5); text-align: left; font-size: 10px; font-weight: 600;
        text-transform: uppercase; letter-spacing: .5px; color: var(--faint, #99A1A5); padding: 9px 16px; border-bottom: 1px solid rgba(255,255,255,.5); }
    .lg-modal .m-table th.sr, .lg-modal .m-table th:first-child { width: 74px; }
    .lg-modal .m-table td { padding: 11px 16px; color: var(--ink, #14232A); border-bottom: 1px solid rgba(255,255,255,.45); }
    .lg-modal .m-table tr:last-child td { border-bottom: none; }
    .lg-modal .m-table tr:hover td { background: rgba(255,255,255,.35); }
    /* positional fallback (td:first-child/nth-child(2)) alongside the
       .sr/.nm classes — some callers' row HTML is built server-side
       without column classes, so this works either way. */
    .lg-modal .m-table td.sr, .lg-modal .m-table td:first-child { color: var(--faint, #99A1A5); font-variant-numeric: tabular-nums; }
    .lg-modal .m-table td.nm, .lg-modal .m-table td:nth-child(2) { font-weight: 500; color: var(--ink, #14232A); }
    @media (prefers-reduced-motion: reduce) {
        .lg-modal, .lg-modal::before, .lg-modal:hover { transition: none; }
    }

    .lnkrow { display: flex; align-items: center; gap: 14px; margin-top: 12px; flex-wrap: wrap; }
    .lnkrow .lnk { font-size: 12px; font-weight: 600; color: var(--teal, #014653); text-decoration: none; }
    .lnkrow .lnk:hover { text-decoration: underline; }
    .lnkrow .lnk-rec { background: none; border: none; padding: 0; cursor: pointer; font-family: inherit;
        font-size: 12px; font-weight: 600; color: var(--teal, #014653); }
    .lnkrow .lnk-rec:hover { text-decoration: underline; }
    .lnkrow .sep { width: 1px; height: 12px; background: var(--line-3, #C7CDCF); }
</style>

<div class="wai-backdrop" id="recModal">
    <div class="wai-modal" role="dialog" aria-modal="true">
        <button class="m-x" id="recClose" aria-label="Close">&times;</button>
        <div class="m-kicker"><span class="dot"></span>WAI Insight</div>
        <div class="mt" id="recTitle"></div>
        <div class="m-label" style="margin-top:20px">Recommendation</div>
        <div class="m-rec" id="recRec"></div>
        <div class="m-foot"><button class="m-btn" id="recViewDetails">View details &rarr;</button></div>
    </div>
</div>

<script>
(function () {
    function waiOpen(el) { if (el) el.classList.add('open'); }
    function waiClose(el) { if (el) el.classList.remove('open'); }

    var recModal = document.getElementById('recModal');
    var recTitle = document.getElementById('recTitle');
    var recRec = document.getElementById('recRec');
    var recViewDetailsBtn = document.getElementById('recViewDetails');
    var currentDetailsId = null;

    document.addEventListener('click', function (e) {
        var recBtn = e.target.closest('.lnk-rec');
        if (recBtn) {
            currentDetailsId = recBtn.getAttribute('data-details') || null;
            recTitle.textContent = recBtn.getAttribute('data-title') || '';
            recRec.textContent = recBtn.getAttribute('data-rec') || '';
            if (recViewDetailsBtn) recViewDetailsBtn.style.display = currentDetailsId ? '' : 'none';
            waiOpen(recModal);
            return;
        }

        var detailsLink = e.target.closest('.lnk[data-details]');
        if (detailsLink) {
            e.preventDefault();
            waiOpen(document.getElementById(detailsLink.getAttribute('data-details')));
            return;
        }

        if (e.target === recViewDetailsBtn) {
            waiClose(recModal);
            if (currentDetailsId) waiOpen(document.getElementById(currentDetailsId));
            return;
        }

        if (e.target.classList.contains('m-x')) {
            waiClose(e.target.closest('.wai-backdrop'));
            return;
        }

        if (e.target.classList.contains('wai-backdrop')) {
            waiClose(e.target);
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('.wai-backdrop.open').forEach(waiClose);
    });

    // Liquid Glass — pointer-tracking specular highlight. Listeners live
    // directly on each .lg-modal element (not a document-wide delegated
    // listener) so pointermove only ever fires while the cursor is over
    // an actual glass modal; .wai-backdrop's pointer-events:none while
    // closed means these are inert until the modal is open anyway.
    // Deferred to DOMContentLoaded (not run inline here): this partial can
    // be included anywhere on a page, including above a module's own
    // .lg-modal markup further down the same template — querying for
    // .lg-modal before the parser has reached it would silently find
    // nothing and attach no listeners at all.
    function attachLiquidGlassTracking() {
        document.querySelectorAll('.lg-modal').forEach(function (lg) {
            if (lg.dataset.lgTrackingBound) return;
            lg.dataset.lgTrackingBound = '1';
            lg.addEventListener('pointermove', function (e) {
                var r = lg.getBoundingClientRect();
                lg.style.setProperty('--mx', ((e.clientX - r.left) / r.width * 100) + '%');
                lg.style.setProperty('--my', ((e.clientY - r.top) / r.height * 100) + '%');
            });
            lg.addEventListener('pointerleave', function () {
                lg.style.setProperty('--mx', '80%');
                lg.style.setProperty('--my', '-5%');
            });
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', attachLiquidGlassTracking);
    } else {
        attachLiquidGlassTracking();
    }
})();
</script>
