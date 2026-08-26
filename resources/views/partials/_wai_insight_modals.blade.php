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
})();
</script>
