/* WAI Guide: edge notch, Guide panel, step card, spotlight, welcome tour and Find anything (Cmd/Ctrl+K).
 * Frontend only: static content (wai-guide-content.js), client-side state (localStorage), no requests, no polling.
 * Only the notch exists at load. Everything else is built the first time it is needed, then reused.
 * Public surface: WAIGuide.done(key, label?) · WAIGuide.open(view?, id?) · WAIGuide.start(id, from?) · WAIGuide.palette()
 * Loaded on demand by the small starter in resources/views/resorts/partials/wai-guide.blade.php. */
(function () {
  'use strict';
  var W = window, D = document, C = W.WAIGuideContent, N = D.getElementById('wg-notch');
  if (!N || !C) return;
  var stub = W.WAIGuide;
  var $ = function (s, r) { return (r || D).querySelector(s); };
  var $$ = function (s, r) { return [].slice.call((r || D).querySelectorAll(s)); };
  var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  var LITE = (W.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches) || (navigator.connection && navigator.connection.saveData) || (navigator.hardwareConcurrency || 4) <= 2;
  if (LITE) D.documentElement.classList.add('wg-lite');
  var cfg = { route: N.dataset.route || '', uid: N.dataset.uid || '0', name: N.dataset.name || '', routes: {} };
  try { cfg.routes = JSON.parse(N.dataset.routes || '{}'); } catch (e) { /* no routes: guides still open, "Show me" just can't navigate */ }
  var GU = C.GUIDES, SC = C.SCREENS, PG = C.PG || {}, ALL = Object.keys(GU), ORDER = ALL, ROUTE = (C.AL || {})[cfg.route] || cfg.route, ROLE = ((C.RANK || {})[N.dataset.rank] || '').toLowerCase(), MAC = /Mac|iPhone|iPad/.test(navigator.platform || ''), KEY = MAC ? '⌘K' : 'Ctrl+K';
  /* who the person is: the rank's name, plus HR when the portal says so (an HR department head has rank HOD but is HR, so rank alone is not enough) */
  var ROLES = (ROLE ? [ROLE === 'md' ? 'gm' : ROLE] : []).concat(N.dataset.hr && ROLE !== 'hr' ? ['hr'] : []);

  /* ---------- state (localStorage, one key per user: wai-guide:{userId} → { tourSeen, run }) ---------- */
  var SK = 'wai-guide:' + cfg.uid, ST = {};
  try { ST = JSON.parse(localStorage.getItem(SK)) || {}; } catch (e) { ST = {}; }
  function save() { try { localStorage.setItem(SK, JSON.stringify(ST)); } catch (e) { /* private mode */ } }
  var DONE = {}, RUN = null, G = { on: false, view: 'home', gid: null }, COACH = null, lastFocus = null, smt = 0;

  /* ---------- small helpers ---------- */
  var P = function (d) { return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + d + '</svg>'; };
  var I = {
    cv: P('<path d="M9 18l6-6-6-6"/>'), arr: P('<path d="M5 12h14M13 6l6 6-6 6"/>'), x: P('<path d="M18 6 6 18M6 6l12 12"/>'),
    ck: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>',
    back: P('<path d="M15 18l-6-6 6-6"/>'), search: P('<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>'),
    compass: P('<circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2 5-5 2 2-5z"/>'),
    list: P('<path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4.5" cy="6" r="1"/><circle cx="4.5" cy="12" r="1"/><circle cx="4.5" cy="18" r="1"/>'),
    grid: P('<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>'),
    play: P('<path d="M7 5v14l11-7z"/>')
  };
  function mk(id, cls, tag, attrs) {
    var e = D.createElement(tag || 'div'); e.id = id; e.className = cls;
    if (attrs) Object.keys(attrs).forEach(function (k) { e.setAttribute(k, attrs[k]); });
    D.body.appendChild(e); return e;
  }
  /* the first line is the bold title; the second, if any, is plain text */
  function toast(m, type, more) { if (W.wisdomToast) W.wisdomToast(type || 'info', m, more || ''); }
  function nav(route) { var u = cfg.routes[route]; if (u) { location.href = u; return true; } return false; }
  function idle(f) { if (W.requestIdleCallback) W.requestIdleCallback(f, { timeout: 2000 }); else setTimeout(f, 1); }
  var nSteps = function (n) { return n + (n === 1 ? ' step' : ' steps'); };
  /* a step is [title, help, route name, selector of a control to point at, selector of a menu to open first, how it is done]; where it happens is worked out from the route */
  var where = function (r) { var p = PG[r]; return p ? modBy(p[0])[1] + ' › ' + p[1] : ''; };
  /* the first control matching the selector that is actually showing (the menu, for one, exists twice and only one is visible) */
  var qa = function (sel) { try { return $$(sel); } catch (e) { return []; } };
  var pick = function (sel) { return qa(sel).filter(function (e) { return e.offsetParent; })[0] || null; };
  var steps = function (id) { var g = GU[id]; return g.live ? g.steps : g.steps.map(function (s, i) { return { k: id + i, t: s[0], d: s[1] || '', loc: where(s[2]), go: s[2], sel: s[3], open: s[4], ev: s[5] || 'click' }; }); };
  var modBy = function (key) { return C.MODS.filter(function (m) { return m[0] === key; })[0] || []; };
  var modByName = function (n) { n = (n || '').toLowerCase(); return C.MODS.filter(function (m) { return m[1].toLowerCase() === n; })[0] || null; };
  /* guides written for the user's own role come first (no role or no "who" means it suits everyone) */
  /* what a guide is found by: its title, its module, its keywords and the titles of its steps */
  var gtext = function (id) { var g = GU[id]; return g._x || (g._x = (g.t + ' ' + modBy(g.m)[1] + ' ' + (g.kw || '') + ' ' + g.steps.map(function (x) { return x.t || x[0]; }).join(' ')).toLowerCase()); };
  var mine = function (id) { var w = (GU[id].who || '').toLowerCase(); return !w || !ROLES.length || w.indexOf('everyone') > -1 || ROLES.some(function (r) { return w.indexOf(r) > -1; }); };
  /* guides for other roles stay out of every list unless the person turns on Show all guides (an unknown role sees everything) */
  /* who may open what: HR department heads get everything; everyone else gets the guides whose first screen is in their own menu (the server sends the menu pages they may view),
     or, for a screen that isn't a menu page, whose module they have at least one menu page in. With no menu data the "who is it for" labels decide. */
  var FULL = !!N.dataset.hr, PM = null, REV = {}, MOK = {};
  if (!FULL && N.dataset.allowed != null) {
    var sets = function (j) { var o = {}; try { JSON.parse(j || '[]').forEach(function (r) { o[r] = 1; }); } catch (e) { /* empty */ } return o; };
    PM = { menu: sets(N.dataset.menu), ok: sets(N.dataset.allowed) };
    Object.keys(C.AL || {}).forEach(function (a) { (REV[C.AL[a]] = REV[C.AL[a]] || []).push(a); });
  }
  var may = function (r) { return PM.ok[r] || (REV[r] || []).some(function (a) { return PM.ok[a]; }); };
  var isMenu = function (r) { return PM.menu[r] || (REV[r] || []).some(function (a) { return PM.menu[a]; }); };
  var modOpen = function (m) { return m in MOK ? MOK[m] : (MOK[m] = Object.keys(PG).some(function (r) { return PG[r][0] === m && may(r); })); };
  var canOpen = function (id) {
    var g = GU[id], st = g.steps.filter(function (x) { return x[2]; })[0], r = g.live || (st && st[2]);
    return !r || may(r) || (!isMenu(r) && modOpen(g.m));
  };
  var refilter = function () { ORDER = ALL.filter(function (i) { return ST.all || FULL || (PM ? canOpen(i) : (!ROLES.length || mine(i))); }); }; refilter();
  var byRole = function (l) { return l.filter(mine).concat(l.filter(function (i) { return !mine(i); })); };
  var here_ = function (s) { return !!s.sel && (!s.go || s.go === ROUTE); };
  var isDone = function (g, s, i) { return g.live ? !!DONE[s.k] : i < RUN.i; };

  /* the menu is loaded after the page, so read it from the page the first time it is needed */
  var NAV = [];
  function navIndex() {
    if (NAV.length) return NAV;
    var seen = {};
    $$('#navbar-desktop-view a.dropdown-item[href], #navbar-vertical-view a.dropdown-item[href], #navbar-mobile-view a.dropdown-item[href]').forEach(function (a) {
      if (!/^https?:/.test(a.href) || seen[a.href]) return; seen[a.href] = 1;
      var g = a.closest('.btn-group,.dropdown-submenu,.nav-item.dropdown'), tg = g && g.querySelector('.dropdown-toggle');
      NAV.push({ t: a.textContent.trim(), m: tg ? tg.textContent.trim() : '', u: a.href });
    });
    return NAV;
  }
  /* "You're on": the screen's own entry, else its menu label and module */
  function here() {
    var sc = SC[ROUTE] || {}, pg = PG[ROUTE];
    if (sc.t || pg) return { t: sc.t || (pg && /^(Dashboard|Configuration)$/.test(pg[1]) ? modBy(pg[0])[1] + ' ' + pg[1] : pg[1]), d: sc.d || (pg ? 'Part of ' + modBy(pg[0])[1] + '. ' + modBy(pg[0])[2] : ''), mod: pg && pg[0], pop: sc.pop };
    var n = navIndex().filter(function (x) { try { return new URL(x.u).pathname === location.pathname; } catch (e) { return false; } })[0], m = n && modByName(n.m);
    var h1 = $('.page-title h1');
    return { t: n ? n.t : (h1 ? h1.textContent.trim() : D.title.split('|').pop().trim()), d: m ? 'Part of ' + m[1] + '. ' + m[2] : '', mod: m ? m[0] : null };
  }
  /* the guide this screen is the start of (or, failing that, one that passes through it) */
  var mainOf = function () {
    var s = SC[ROUTE]; if (s && s.guide && GU[s.guide] && ORDER.indexOf(s.guide) > -1) return s;
    var own = byRole(ORDER.filter(function (i) { return !GU[i].live && GU[i].steps[0][2] === ROUTE; }))[0] || byRole(ORDER.filter(function (i) { return !GU[i].live && GU[i].steps.some(function (x) { return x[2] === ROUTE; }); }))[0];
    return own ? { guide: own } : null;
  };
  function prog(id) { var g = GU[id]; if (!g.live) return null; return { n: g.steps.filter(function (s) { return DONE[s.k]; }).length, t: g.steps.length }; }

  /* ---------- notch ---------- */
  function drawNotch() {
    var m = mainOf(), g = m && GU[m.guide], setup = g && g.live === ROUTE, h, fill = 100, label = 'Open the Guide';
    N.classList.toggle('wg-hid', !!RUN); N.tabIndex = RUN ? -1 : 0;
    if (setup) {
      var p = prog(m.guide), nx = g.steps.filter(function (s) { return !DONE[s.k]; })[0], c = 2 * Math.PI * 13;
      fill = Math.max(12, p.n / p.t * 100); if (nx) label += '. Next step: ' + nx.t;
      h = '<span class="wg-ring"><svg viewBox="0 0 32 32" aria-hidden="true"><circle class="bg" cx="16" cy="16" r="13"/><circle class="fg" cx="16" cy="16" r="13" stroke-dasharray="' + c + '" stroke-dashoffset="' + c * (1 - p.n / p.t) + '"/></svg><b>' + p.n + '/' + p.t + '</b></span>' +
        '<span class="wg-nt"><b>Guide</b><span class="wg-ns">' + (nx ? 'Next: <b>' + esc(nx.t) + '</b>' : 'Setup complete') + '</span></span>';
    } else h = '<span class="wg-ni">' + I.compass + '</span><span class="wg-nt"><b>Guide</b><span class="wg-ns">Guides for this screen</span></span>';
    N.setAttribute('aria-label', label);
    $('#wg-nx').innerHTML = h; $('.wg-nm i', N).style.setProperty('--p', fill / 100);
  }

  /* ---------- Guide panel: home, guide detail, all guides, modules ---------- */
  function panel() { return $('#wg-panel') || mk('wg-panel', 'wg-panel', 'aside', { 'role': 'dialog', 'aria-label': 'Guide', 'aria-hidden': 'true' }); }
  function openG(view, gid) {
    if (!G.on) lastFocus = D.activeElement;
    G.on = true; G.view = view || 'home'; G.gid = gid || null;
    var p = panel(); drawG(); void p.offsetWidth; p.classList.add('on'); p.setAttribute('aria-hidden', 'false');
    setTimeout(function () { var f = $('#wg-gq', p) || $('.wg-hd .wg-ic', p); if (f) f.focus(); }, 60);
  }
  function closeG() {
    if (!G.on) return; G.on = false; var p = panel(); p.classList.remove('on'); p.setAttribute('aria-hidden', 'true');
    var f = lastFocus && D.contains(lastFocus) ? lastFocus : N; if (f.focus) f.focus();
  }
  function gRow(id, main) {
    var g = GU[id], st = steps(id), p = prog(id), dn = p && p.n === p.t;
    var meta = nSteps(st.length) + ' · about ' + g.mins + ' min' + (p && p.n && !dn ? ' · ' + p.n + ' of ' + p.t + ' done' : '') + (dn ? ' · done' : '');
    var ic = '<span class="wg-pl">' + (dn ? I.ck : I.play) + '</span>', tx = '<span class="wg-tx"><b>' + esc(g.t) + '</b><span>' + meta + '</span></span>';
    if (main) return '<div class="wg-row wg-main"><div class="wg-rt">' + ic + tx + '</div><button type="button" class="wg-btn wg-p wg-wide" data-wga="detail" data-v="' + id + '">' + I.play + (dn ? 'Walk me through again' : 'Walk me through') + '</button></div>';
    return '<button type="button" class="wg-row" data-wga="detail" data-v="' + id + '">' + ic + tx + '<span class="wg-chev">' + I.cv + '</span></button>';
  }
  /* every module is one folded line; it opens on a click, and while searching every module that matches is open. The current screen's module starts open. */
  function allList(q) {
    q = (q || '').trim().toLowerCase();
    var cur = here().mod, words = q ? q.split(/\s+/) : [];
    var h = C.MODS.map(function (x) {
      var l = ORDER.filter(function (i) { return GU[i].m === x[0] && words.every(function (w) { return gtext(i).indexOf(w) > -1; }); });
      return l.length ? '<details class="wg-fold"' + (q || x[0] === cur ? ' open' : '') + '><summary><span>' + esc(x[1]) + '</span><em>' + l.length + '</em>' + I.cv + '</summary><div class="wg-list">' + l.map(function (i) { return gRow(i); }).join('') + '</div></details>' : '';
    }).join('');
    return h || '<div class="wg-none">No guide matches “' + esc(q) + '”.</div>';
  }
  function roleNote() {
    if (FULL || !(PM || ROLES.length)) return '';
    var who = PM ? 'the screens you can open' : 'your role (' + ROLES.map(function (r) { return r.length < 4 ? r.toUpperCase() : r.charAt(0).toUpperCase() + r.slice(1); }).join(' and ') + ')';
    return '<p class="wg-rn">' + (ST.all ? 'Showing every guide.' : 'Showing the guides for ' + esc(who) + '.') + ' <button type="button" class="wg-lnk" data-wga="roleall">' + (ST.all ? 'Only mine' : 'Show all guides') + '</button></p>';
  }
  function drawG() {
    var sc = here(), m = mainOf(), body = '';
    var hd = '<div class="wg-hd"><div class="wg-tx"><span class="wg-yo">You’re on</span><b>' + esc(sc.t) + '</b>' + (sc.d ? '<p>' + esc(sc.d) + '</p>' : '') + '</div><button type="button" class="wg-ic" data-wga="close" aria-label="Close the Guide">' + I.x + '</button></div>';
    if (G.view === 'detail') {
      var id = G.gid, g = GU[id], st = steps(id), p = prog(id), nx = g.live ? st.findIndex(function (s) { return !DONE[s.k]; }) : -1;
      body = '<button type="button" class="wg-back" data-wga="view" data-v="home">' + I.back + 'Back</button><div class="wg-det"><b>' + esc(g.t) + '</b><span class="wg-mt">' + esc(modBy(g.m)[1]) + (g.who ? ' · ' + esc(g.who) : '') + ' · about ' + g.mins + ' min' + (p && p.n ? ' · ' + p.n + ' of ' + p.t + ' done' : '') + '</span>' +
        '<ol class="wg-ol">' + st.map(function (s, i) { var d = g.live && DONE[s.k]; return '<li class="' + (d ? 'dn' : '') + '"><span class="wg-k">' + (d ? I.ck : i + 1) + '</span><span><b>' + esc(s.t) + '</b>' + (s.d || s.loc ? '<span>' + esc(s.d || s.loc) + '</span>' : '') + '</span></li>'; }).join('') + '</ol>' +
        '<div class="wg-act">' + (p && p.n && p.n < p.t ? '<button type="button" class="wg-btn wg-p" data-wga="start" data-v="' + id + '" data-from="' + nx + '">' + I.play + 'Continue from step ' + (nx + 1) + '</button><button type="button" class="wg-btn wg-s" data-wga="start" data-v="' + id + '" data-from="0">Start over</button>'
          : '<button type="button" class="wg-btn wg-p" data-wga="start" data-v="' + id + '" data-from="0">' + I.play + (p && p.n === p.t ? 'Walk me through again' : 'Start guide') + '</button>') + '</div></div>';
    } else if (G.view === 'all') {
      body = '<button type="button" class="wg-back" data-wga="view" data-v="home">' + I.back + 'Back</button>' +
        '<label class="wg-gs">' + I.search + '<input id="wg-gq" placeholder="Search all ' + ORDER.length + ' guides, for example leave or visa" aria-label="Search guides" autocomplete="off" value="' + esc(G.q || '') + '"></label>' + roleNote() + '<div id="wg-gl">' + allList(G.q) + '</div>';
    } else if (G.view === 'mods') {
      body = '<button type="button" class="wg-back" data-wga="view" data-v="home">' + I.back + 'Back</button><div class="wg-sec">What each module is for</div>' + C.MODS.map(function (x) { return '<div class="wg-res"><span class="wg-ti">' + I.grid + '</span><span><b>' + esc(x[1]) + '</b><span>' + esc(x[2]) + '</span></span></div>'; }).join('');
    } else {
      if (m) {
        body += '<div class="wg-sec">Walk me through</div>' + gRow(m.guide, 1);
        if (m.after) body += '<button type="button" class="wg-aft" data-wga="goto" data-v="' + m.after.go + '"><span>When you’re done here: <b>' + esc(m.after.t) + '</b></span><span class="wg-go">Go ' + I.arr + '</span></button>';
      }
      var pop = (sc.pop || byRole(ORDER.filter(function (i) { return GU[i].steps.some(function (x) { return x[2] === ROUTE; }); })).concat(byRole(ORDER.filter(function (i) { return sc.mod && GU[i].m === sc.mod; })))).filter(function (v, i, a) { return GU[v] && ORDER.indexOf(v) > -1 && a.indexOf(v) === i && !(m && v === m.guide); }).slice(0, 4);
      var startHere = !pop.length; /* a screen no guide passes through: offer a way in, not an empty list */
      if (startHere) { var seen = {}; pop = ['st-top'].concat(byRole(ORDER).filter(function (i) { var k = GU[i].m; return !GU[i].live && i !== 'st-top' && mine(i) && !seen[k] && (seen[k] = 1); })).filter(function (v) { return GU[v]; }).slice(0, 4); }
      body += '<div class="wg-sec">' + (startHere ? 'Start here' : 'Popular guides') + '</div><div class="wg-list">' + pop.map(function (i) { return gRow(i); }).join('') + '</div><div class="wg-foot"><button type="button" class="wg-lnk" data-wga="view" data-v="all">All guides (' + ORDER.length + ') ' + I.arr + '</button></div>';
    }
    panel().innerHTML = hd + '<div class="wg-body">' + body + '</div>';
  }

  /* ---------- step card (the walkthrough) ---------- */
  function persist() { ST.run = RUN && !RUN.fin ? { id: RUN.id, i: RUN.i, list: RUN.list ? 1 : 0, show: RUN.show ? 1 : 0, t: Date.now() } : null; save(); }
  function startGuide(id, from) {
    closeG(); unspot(); RUN = { id: id, i: Math.max(0, from | 0), list: true, fin: false }; persist(); drawRun(); drawNotch();
    var g = GU[id], s = steps(id)[RUN.i];
    if (g.live && !DONE[s.k] && !(s.need && !DONE[s.need])) setTimeout(function () { showMe(0); }, LITE ? 0 : 380);
    else if (!g.live && s.go && s.go !== ROUTE) showMe(0); /* take them to the screen first; the card is waiting for them there */
    else if (!g.live && here_(s)) setTimeout(function () { showMe(5); }, LITE ? 0 : 380);
  }
  function stopGuide() { RUN = null; unspot(); closeMenu(); persist(); var r = $('#wg-run'); if (r) { r.classList.remove('on'); r.setAttribute('aria-hidden', 'true'); } drawNotch(); }
  function drawRun() {
    var r = $('#wg-run') || mk('wg-run', 'wg-run', 'section', { 'aria-label': 'Guide steps' });
    if (!RUN) { r.classList.remove('on'); return; }
    var g = GU[RUN.id], st = steps(RUN.id), n = st.filter(function (s, i) { return isDone(g, s, i); }).length, h;
    r.setAttribute('aria-hidden', 'false');
    if (RUN.fin) {
      var af = (SC[g.live] || {}).after;
      h = '<div class="wg-rh"><span class="wg-pl">' + I.ck + '</span><b>' + esc(g.t) + '</b><button type="button" class="wg-ic" data-wga="run" data-v="close" aria-label="Close the guide">' + I.x + '</button></div><div class="wg-rbar"><i></i></div>' +
        '<div class="wg-rb"><div class="wg-rs">All done</div><div class="wg-rtt">You’ve finished every step.</div><p class="wg-rp">You can walk through it again any time from the Guide.</p>' +
        (af ? '<button type="button" class="wg-aft" data-wga="goto" data-v="' + af.go + '" data-run="close"><span>Next: <b>' + esc(af.t) + '</b></span><span class="wg-go">Go ' + I.arr + '</span></button>' : '') + '</div>' +
        '<div class="wg-rf"><button type="button" class="wg-lnk" data-wga="run" data-v="again">Walk me through again</button><span class="wg-sp"></span><button type="button" class="wg-btn wg-p" data-wga="run" data-v="close">Close</button></div><span class="wg-sr" role="status">All done. You have finished every step.</span>';
    } else {
      var cur = st[RUN.i], cd = isDone(g, cur, RUN.i), lk = g.live && cur.need && !DONE[cur.need], last = RUN.i === st.length - 1, can = g.live || !!cur.sel || !!(cur.go && cur.go !== ROUTE);
      h = '<div class="wg-rh"><span class="wg-pl">' + I.play + '</span><b>' + esc(g.t) + '</b><button type="button" class="wg-ic' + (RUN.list ? ' on' : '') + '" data-wga="run" data-v="list" aria-label="Show all steps" aria-pressed="' + !!RUN.list + '" title="All steps">' + I.list + '</button><button type="button" class="wg-ic" data-wga="run" data-v="close" aria-label="Close the guide">' + I.x + '</button></div>' +
        '<div class="wg-rbar"><i style="--p:' + n / st.length + '"></i></div>' +
        '<div class="wg-rb"><div class="wg-rs">Step ' + (RUN.i + 1) + ' of ' + st.length + (cd && g.live ? ' · already done' : '') + '</div><div class="wg-rtt">' + esc(cur.t) + '</div>' + (cur.d ? '<p class="wg-rp">' + esc(cur.d) + '</p>' : '') + (cur.loc ? '<p class="wg-rl">' + I.compass + esc(cur.loc) + '</p>' : '') +
        (lk ? '<p class="wg-rw">' + esc(cur.why) + '. Do step ' + (st.findIndex(function (s) { return s.k === cur.need; }) + 1) + ' first.</p>' : '') +
        (RUN.list ? '<ol class="wg-rlst">' + st.map(function (s, i) { var d = isDone(g, s, i); return '<li class="' + (d ? 'dn' : '') + (i === RUN.i ? ' cur' : '') + '" data-wga="jump" data-v="' + i + '" tabindex="0" role="button"><span class="wg-k">' + (d ? I.ck : '') + '</span>' + esc(s.t) + '</li>'; }).join('') + '</ol>' : '') + '</div>' +
        '<div class="wg-rf">' + (can ? '<button type="button" class="wg-lnk" data-wga="run" data-v="skip">' + (last ? 'Finish' : cd || !g.live ? 'Next step' : 'Skip step') + '</button>' : '') + '<span class="wg-sp"></span>' + (RUN.i > 0 ? '<button type="button" class="wg-btn wg-s" data-wga="run" data-v="prev">Back</button>' : '') + (can ? '<button type="button" class="wg-btn wg-p" data-wga="run" data-v="show"' + (lk ? ' disabled' : '') + '>' + I.play + (!g.live && cur.go && cur.go !== ROUTE ? 'Take me there' : 'Show me') + '</button>' : '<button type="button" class="wg-btn wg-p" data-wga="run" data-v="skip">' + (last ? 'Finish' : 'Next step') + '</button>') + '</div>' +
        '<span class="wg-sr" role="status">Step ' + (RUN.i + 1) + ' of ' + st.length + ', ' + esc(cur.t) + '</span>';
    }
    r.innerHTML = h; r.classList.add('on');
  }
  function runTo(i, fwd) {
    var st = steps(RUN.id); clearTimeout(smt); unspot();
    if (i >= st.length) { RUN.fin = true; closeMenu(); } else RUN.i = Math.max(0, i);
    RUN.show = 0; persist(); drawRun();
    var g = GU[RUN.id], s = !RUN.fin && steps(RUN.id)[RUN.i];
    if (s && !g.live && fwd && s.go && s.go !== ROUTE) showMe(0);
    else if (s && !g.live && here_(s)) smt = setTimeout(function () { showMe(5); }, LITE ? 0 : 200);
  }
  /* Show me: live steps point at the real control (its tab first if needed); other guides open the step's screen */
  /* a step whose control isn't on this person's screen (it belongs to another role): say so, skip it and carry on; never more than 3 in a row */
  var SKN = 0;
  function skipStep(s) {
    if (!RUN || SKN >= 3 || RUN.i + 1 >= steps(RUN.id).length) return false;
    SKN++; toast('Skipped: ' + s.t, 'info', 'That step is for another role, so it isn’t on your screen.'); runTo(RUN.i + 1, 1); return true;
  }
  function showMe(tries, opened, man) { /* man: the person pressed Show me, so never skip on their behalf */
    if (!RUN) return;
    var g = GU[RUN.id], s = steps(RUN.id)[RUN.i];
    if (!g.live) {
      if (s.go && ROUTE !== s.go) { RUN.show = s.sel ? 1 : 0; persist(); if (!nav(s.go) && !(!man && skipStep(s))) toast('That screen isn’t available to you', 'warn'); return; }
      var pt = s.sel && pick(s.sel), op = !pt && s.open && !opened && pick(s.open);
      if (op) { op.click(); smt = setTimeout(function () { showMe(tries, 1, man); }, 280); return; } /* open the menu it sits in, once, then point at it */
      var hm = !pt && s.sel && (qa(s.sel)[0] || 0).closest && qa(s.sel)[0].closest('.modal'), tr = hm && hm.id && tries > 0 && !hm.classList.contains('show') && pick('[data-bs-target="#' + hm.id + '"],a[href="#' + hm.id + '"]');
      if (tr) { tr.click(); smt = setTimeout(function () { showMe(tries - 1, opened, man); }, 600); return; } /* the control is inside a pop-up nobody has opened yet (the step before was skipped): open it, then point */
      var md = pt && D.querySelector('.modal.show'), x = md && !md.contains(pt) && md.querySelector('[data-bs-dismiss="modal"]');
      if (x) { x.click(); smt = setTimeout(function () { showMe(tries, opened, man); }, 450); return; } /* a pop-up from an earlier step is still open and this control is behind it: close it, then point */
      if (pt) { SKN = 0; spot(pt, '<b>' + esc(s.t) + '</b>' + (s.d ? '<p>' + esc(s.d) + '</p>' : '')); return; }
      if (s.sel && tries > 0) { smt = setTimeout(function () { showMe(tries - 1, opened); }, 400); return; }
      if (s.sel && !(!man && skipStep(s))) toast('I can’t see that control here', 'warn', 'It may be for another role, or an earlier step comes first.');
      return;
    }
    if (ROUTE !== g.live) { RUN.show = 1; persist(); nav(g.live); return; }
    var tb = s.tab && $(g.tabs.sel.replace('{t}', s.tab));
    if (tb && !tb.classList.contains('on')) {
      spot(tb, '<b>Open the ' + esc(g.tabs.names[s.tab]) + ' tab</b><p>This step happens on that tab.</p>', { wait: g.tabs.sel.replace('{t}', s.tab) }); return;
    }
    var el = $('[data-guide="' + s.el + '"]');
    if (!el || !el.offsetParent) {
      if (tries > 0) { smt = setTimeout(function () { showMe(tries - 1); }, 400); return; }
      toast('That control isn’t on screen yet', 'warn', 'Do the step before it first.'); return;
    }
    spot(el, '<b>' + esc(s.t) + '</b><p>' + (DONE[s.k] ? 'You did this already. This is where it lives.' : s.hint || (s.click ? 'Click the highlighted link.' : 'Click the highlighted button.')) + '</p>');
  }
  /* a live step was finished on the page: tick it, bump the card, move on and point at the next one */
  function done(k, label) {
    if (DONE[k]) return; DONE[k] = 1; drawNotch(); if (G.on) drawG();
    var g = RUN && GU[RUN.id];
    if (label) toast(label, 'ok');
    if (!g || !g.live) return;
    var st = g.steps, i = st.findIndex(function (s) { return s.k === k; });
    if (i < 0 || RUN.fin) return;
    if (i !== RUN.i) { drawRun(); return; }
    clearTimeout(smt); unspot();
    var nx = st.findIndex(function (s, j) { return j > i && !DONE[s.k]; });
    if (nx < 0) { RUN.fin = true; persist(); drawRun(); toast('Setup complete', 'ok'); return; }
    RUN.i = nx; persist(); drawRun();
    var r = $('#wg-run'); r.classList.add('bump'); setTimeout(function () { r.classList.remove('bump'); }, 600);
    smt = setTimeout(function () { showMe(0); }, LITE ? 0 : 500);
  }

  /* ---------- following the user: do the step on the page, and the guide moves on to the next control by itself ---------- */
  var DEB = 0;
  var onScreen = function (s) { return !s.go || s.go === ROUTE; };
  var onControl = function (s, t) { return qa(s.sel).some(function (e) { return e === t || e.contains(t); }); };
  /* the nearest upcoming step whose control was just clicked (clicking ahead counts for the steps in between) */
  function clicked(t) {
    var st = steps(RUN.id), best = -1;
    for (var j = RUN.i; j < Math.min(st.length, RUN.i + 4); j++) { var x = st[j]; if (x.sel && x.ev === 'click' && onScreen(x) && onControl(x, t)) { best = j; break; } }
    return best;
  }
  function advanceTo(n) {
    clearTimeout(smt); clearTimeout(DEB); unspot();
    var st = steps(RUN.id), s = st[n];
    if (!s) { RUN.fin = true; RUN.show = 0; persist(); drawRun(); return; }
    RUN.i = n; RUN.show = s.sel ? 1 : 0; persist(); drawRun();
    var r = $('#wg-run'); r.classList.add('bump'); setTimeout(function () { r.classList.remove('bump'); }, 600);
    /* the next control often appears only after the page reacts (a box opens), and a click may be taking the page elsewhere: give it a moment */
    if (s.go && s.go !== ROUTE) smt = setTimeout(function () { showMe(0); }, 700);
    else if (s.sel) smt = setTimeout(function () { showMe(10); }, LITE ? 0 : 450);
  }
  function followClick(t) { if (!RUN || RUN.fin || GU[RUN.id].live) return; var j = clicked(t); if (j > -1) advanceTo(j + 1); }
  function followInput(ev, change) {
    if (!RUN || RUN.fin || GU[RUN.id].live) return;
    var s = steps(RUN.id)[RUN.i], at = RUN.i;
    if (!s || !s.sel || !onScreen(s) || !onControl(s, ev.target)) return;
    if (change && s.ev === 'change') { advanceTo(at + 1); return; }
    if (!change && s.ev === 'input') { clearTimeout(DEB); DEB = setTimeout(function () { if (RUN && RUN.i === at && String(ev.target.value || '').trim()) advanceTo(at + 1); }, 1100); } /* done once they stop typing */
  }

  /* ---------- spotlight and coach card (visual only: the highlighted control stays clickable) ---------- */
  var HOLE, COEL, CC, TGT = null, TGT_D = null, raf = 0, ft = 0;
  function layers() { if (HOLE) return; HOLE = mk('wg-hole', 'wg-hole'); COEL = mk('wg-coach', 'wg-coach', 'div', { 'aria-live': 'polite' }); CC = mk('wg-cc', 'wg-cc'); }
  function place() {
    if (!COACH) return;
    var cw = Math.min(300, innerWidth - 24), ch = COEL.offsetHeight, el = COACH.el;
    if (!el) { HOLE.classList.remove('on'); COEL.style.left = (innerWidth - cw) / 2 + 'px'; COEL.style.top = Math.max(12, (innerHeight - ch) / 2) + 'px'; return; }
    var r = COACH.rect ? COACH.rect() : el.getBoundingClientRect(), p = 6, vis = r.bottom > 0 && r.top < innerHeight && r.right > 0 && r.left < innerWidth, x = r.left, y = r.bottom + 16;
    HOLE.style.left = r.left - p + 'px'; HOLE.style.top = r.top - p + 'px'; HOLE.style.width = r.width + p * 2 + 'px'; HOLE.style.height = r.height + p * 2 + 'px'; HOLE.classList.toggle('off', !vis);
    if (y + ch > innerHeight - 12) y = r.top - ch - 16;
    if (y < 12) { y = Math.max(12, r.top); x = r.right + 16; if (x + cw > innerWidth - 12) x = r.left - cw - 16; }
    COEL.style.left = Math.max(12, Math.min(x, innerWidth - cw - 12)) + 'px'; COEL.style.top = Math.max(12, y) + 'px';
  }
  function tick() {
    if (raf) return; raf = requestAnimationFrame(function () { raf = 0; if (HOLE) HOLE.classList.add('wg-fast'); place(); clearTimeout(ft); ft = setTimeout(function () { if (HOLE) HOLE.classList.remove('wg-fast'); }, 150); });
  }
  function spot(el, html, o) {
    o = o || {}; layers(); clearTgt();
    COACH = { el: el, wait: o.wait, tour: o.tour, rect: o.rect };
    if (el) { TGT = el; TGT_D = el.getAttribute('aria-describedby'); el.setAttribute('aria-describedby', 'wg-coach'); el.scrollIntoView({ block: 'nearest', behavior: LITE ? 'auto' : 'smooth' }); HOLE.classList.add('on'); } else HOLE.classList.remove('on');
    CC.classList.toggle('on', !!o.tour); CC.classList.toggle('dim', !el && !!o.tour);
    COEL.innerHTML = html; COEL.classList.add('on');
    setTimeout(place, LITE ? 0 : 180); if (!LITE) setTimeout(place, 640); /* second pass: the page may still be animating in */
    removeEventListener('resize', tick); removeEventListener('scroll', tick, true); addEventListener('resize', tick); addEventListener('scroll', tick, true);
  }
  function clearTgt() { if (TGT) { if (TGT_D == null) TGT.removeAttribute('aria-describedby'); else TGT.setAttribute('aria-describedby', TGT_D); TGT = null; } }
  function unspot() {
    clearTgt(); COACH = null; removeEventListener('resize', tick); removeEventListener('scroll', tick, true);
    if (HOLE) { HOLE.classList.remove('on'); COEL.classList.remove('on'); CC.classList.remove('on'); CC.classList.remove('dim'); }
  }

  /* ---------- welcome tour (first visit only, never replayed from the panel) ---------- */
  function vis(e) { return e && e.offsetParent ? e : null; }
  var TOUR = [
    { el: function () { return vis($('#navbar-desktop-view')) || vis($('#navbar-vertical-view-menu')) || vis($('#navbar-mobile-view')); }, t: 'Every module lives here', p: 'Payroll, Leave, Accommodation and the rest. Click a module to see its screens.' },
    { el: function () { var l = $$('.carosel-menu .btn-group > a, .dropdown-submenu > a, #navbar-mobile-view .nav-item > a').filter(vis); var ok = l.filter(function (a) { var r = a.getBoundingClientRect(); return r.left >= 0 && r.right <= innerWidth; }); return ok.filter(function (a) { return /workforce/i.test(a.textContent); })[0] || ok[0] || l[0] || null; } /* the first module that is fully on screen (Workforce Planning when you have it), so its menu opens inside the window */, t: 'Each module has a few screens', p: 'Most modules start with a Dashboard and end with Configuration, where you set things up once.' },
    { el: function () { return null; }, t: 'Lost? Search for it', p: 'Type a screen or a task, like “assign a bed” or “run payroll”. Press <kbd>' + KEY + '</kbd> from anywhere.' },
    { el: function () { return N; }, t: 'Every screen has a Guide', p: 'It sits on the right edge, out of the way. Hover to see your next step, click to open it.' }
  ];
  /* stop 2 opens the real module menu (the page's own click handler) so its screens are on show, and closes it again when the tour moves on */
  function closeMenu() { if ($('.carosel-nav-menu.show, .dropdown-menu.show')) D.documentElement.click(); }
  function openMenu(t) {
    var m = t._wgMenu || (t.nextElementSibling && t.nextElementSibling.classList.contains('carosel-nav-menu') ? t.nextElementSibling : null); t._wgMenu = m;
    if (!m) return null; if (!m.classList.contains('show')) t.click();
    return function () { var a = t.getBoundingClientRect(); if (!m.classList.contains('show')) return a; var b = m.getBoundingClientRect(), l = Math.min(a.left, b.left), tp = Math.min(a.top, b.top), r = Math.max(a.right, b.right), bt = Math.max(a.bottom, b.bottom); return { left: l, top: tp, right: r, bottom: bt, width: r - l, height: bt - tp }; };
  }
  function tour(i) {
    if (i >= TOUR.length) {
      closeMenu(); unspot(); N.classList.remove('wg-open'); toast('Tour done', 'ok', 'The Guide sits on the right edge of the screen.');
      if (!LITE) { N.classList.add('wg-pulse'); setTimeout(function () { N.classList.remove('wg-pulse'); }, 5600); } return;
    }
    var s = TOUR[i], last = i === TOUR.length - 1, tg = s.el(), rect = null;
    if (i !== 1) closeMenu(); else if (tg) rect = openMenu(tg);
    N.classList.toggle('wg-open', last);
    if (last) { var f = function (e) { if (e.propertyName === 'width') { N.removeEventListener('transitionend', f); place(); } }; N.addEventListener('transitionend', f); } /* frame the notch once it has finished opening */
    spot(tg, '<div class="wg-cn">Welcome tour · ' + (i + 1) + ' of ' + TOUR.length + '</div><b>' + s.t + '</b><p>' + s.p + '</p><div class="wg-ca"><span class="wg-dots">' + TOUR.map(function (_, j) { return '<i class="' + (j === i ? 'on' : '') + '"></i>'; }).join('') + '</span><button type="button" class="wg-skip" data-wga="tour" data-v="end">Skip</button>' + (i ? '<button type="button" class="wg-btn wg-s" data-wga="tour" data-v="' + (i - 1) + '">Back</button>' : '') + '<button type="button" class="wg-btn wg-p" data-wga="tour" data-v="' + (i + 1) + '">' + (last ? 'Finish' : 'Next') + '</button></div>', { tour: 1, rect: rect });
    setTimeout(function () { var b = $('.wg-btn.wg-p', COEL); if (b) b.focus(); }, 80);
  }
  function welcome() {
    var w = $('#wg-wel');
    if (!w) {
      w = mk('wg-wel', 'wg-wel', 'div', { 'role': 'dialog', 'aria-modal': 'true', 'aria-labelledby': 'wg-wt' });
      w.innerHTML = '<div class="wg-wc"><h2 id="wg-wt">Welcome to Wisdom AI' + (cfg.name ? ', ' + esc(cfg.name) : '') + '</h2><p>A 30-second tour shows you where things are.</p>' +
        '<div class="wg-wl"><div><i>' + I.grid + '</i><span><b>Modules</b>What each part is for</span></div><div><i>' + I.search + '</i><span><b>Find anything</b>Screens and how-tos</span></div><div><i>' + I.list + '</i><span><b>Setup steps</b>What to do next</span></div></div>' +
        '<div class="wg-act"><button type="button" class="wg-btn wg-s" data-wga="wel" data-v="skip">Skip for now</button><span class="wg-sp"></span><button type="button" class="wg-btn wg-p" data-wga="wel" data-v="go">Show me around ' + I.cv + '</button></div></div>';
    }
    lastFocus = D.activeElement; void w.offsetWidth; w.classList.add('on'); ST.tourSeen = 1; save();
    setTimeout(function () { var b = $('.wg-btn.wg-p', w); if (b) b.focus(); }, 80);
  }

  /* ---------- Find anything ---------- */
  var PL = [], PH = 0;
  function pal() { return $('#wg-pal') || (function () { var p = mk('wg-pal', 'wg-pal', 'div', { 'role': 'dialog', 'aria-modal': 'true', 'aria-label': 'Find anything' });
    p.innerHTML = '<div class="wg-pc"><div class="wg-pi">' + I.search + '<input id="wg-pq" placeholder="Try “assign a bed”, “run payroll” or “visa”" autocomplete="off" aria-label="Find anything"><kbd>Esc</kbd></div><div class="wg-pr" id="wg-pr"></div><div class="wg-pf"><span>↑↓ to move</span><span>Enter to open</span><span>Tasks open a step-by-step guide</span></div></div>'; return p; })(); }
  function openP() {
    var p = pal(); if (p.classList.contains('on')) return; lastFocus = D.activeElement; $('#wg-pq').value = ''; PH = 0; drawP(); void p.offsetWidth; p.classList.add('on'); setTimeout(function () { $('#wg-pq').focus(); }, 30);
  }
  function closeP() { var p = $('#wg-pal'); if (!p || !p.classList.contains('on')) return; p.classList.remove('on'); if (lastFocus && D.contains(lastFocus) && lastFocus.focus) lastFocus.focus(); }
  function drawP() {
    var q = $('#wg-pq').value.trim().toLowerCase(), m = function (s) { return !q || s.toLowerCase().indexOf(q) > -1; };
    var tasks = ORDER.filter(function (id) { return m(gtext(id)); }).slice(0, q ? 6 : 4).map(function (id) { return { type: 'task', id: id, h: '<span class="wg-ti">' + I.play + '</span><span><b>How to: ' + esc(GU[id].t) + '</b><span>' + esc(modBy(GU[id].m)[1]) + ' · ' + nSteps(steps(id).length) + ' · about ' + GU[id].mins + ' min</span></span>' }; });
    var scr = navIndex().filter(function (x) { return m(x.m + ' ' + x.t); }).slice(0, q ? 8 : 4).map(function (x) { return { type: 'go', u: x.u, h: '<span class="wg-ti">' + I.grid + '</span><span><b>' + esc(x.t) + '</b><span>' + esc(x.m) + '</span></span>' }; });
    var mods = q ? C.MODS.filter(function (x) { return m(x[1] + ' ' + x[2]); }).map(function (x) { var f = navIndex().filter(function (n) { return n.m.toLowerCase() === x[1].toLowerCase(); })[0]; return f && { type: 'go', u: f.u, h: '<span class="wg-ti">' + I.compass + '</span><span><b>' + esc(x[1]) + '</b><span>' + esc(x[2]) + '</span></span>' }; }).filter(Boolean).slice(0, 3) : [];
    PL = tasks.concat(scr, mods);
    var h = '', idx = 0, grp = function (list, t) { if (list.length) h += '<div class="wg-gl">' + t + '</div>' + list.map(function (x) { return '<button type="button" class="wg-res' + (idx === PH ? ' hi' : '') + '" data-wga="pi" data-v="' + idx++ + '">' + x.h + '<span class="wg-cv">' + I.cv + '</span></button>'; }).join(''); };
    grp(tasks, 'How do I…'); grp(scr.concat(mods), 'Screens and modules');
    $('#wg-pr').innerHTML = h || '<div class="wg-none">Nothing found</div>';
  }
  function pick(i) {
    var x = PL[i]; if (!x) return; closeP();
    if (x.type === 'task') openG('detail', x.id); else location.href = x.u;
  }

  /* ---------- events: one click, one keydown, one input listener ---------- */
  D.addEventListener('click', function (ev) {
    var t = ev.target, a = t.closest('[data-wga]'), v, id;
    if (t.closest('.wg-coach,.wg-cc,.wg-run')) ev.stopPropagation(); /* the page's own "click outside" handlers must not react to the tour (they would close the open menu) */
    if (a) {
      v = a.dataset.v; id = a.dataset.wga;
      if (id === 'notch') { G.on ? closeG() : openG('home'); }
      else if (id === 'close') closeG();
      else if (id === 'view') { G.view = v; if (v !== 'all') G.q = ''; drawG(); if (v === 'all') { var gq = $('#wg-gq'); if (gq) gq.focus(); } }
      else if (id === 'detail') openG('detail', v);
      else if (id === 'start') startGuide(v, +a.dataset.from);
      else if (id === 'goto') { var rc = a.dataset.run === 'close'; if (nav(v) && rc) { RUN = null; persist(); } else if (!cfg.routes[v]) toast('That screen isn’t available to you', 'warn'); }
      else if (id === 'jump' && RUN) runTo(+v);
      else if (id === 'run' && RUN) {
        SKN = 0;
        if (v === 'close') stopGuide(); else if (v === 'list') { RUN.list = !RUN.list; persist(); drawRun(); } else if (v === 'show') showMe(0, 0, 1);
        else if (v === 'skip') runTo(RUN.i + 1, 1); else if (v === 'prev') runTo(RUN.i - 1); else if (v === 'again') { RUN.fin = false; runTo(0); }
      }
      else if (id === 'wel') { $('#wg-wel').classList.remove('on'); if (v === 'go') setTimeout(function () { tour(0); }, 250); else toast('Skipped', 'info', 'The Guide sits on the right edge of the screen if you need it.'); }
      else if (id === 'tour') { tour(v === 'end' ? 99 : +v); }
      else if (id === 'pi') pick(+v);
      else if (id === 'roleall') { ST.all = ST.all ? 0 : 1; save(); refilter(); drawG(); drawNotch(); }
      return;
    }
    if (t.closest('.wg-cc')) return; /* the tour's catch layer: clicks on the dimmed page do nothing */
    if (t.closest('#wg-pal') && !t.closest('.wg-pc')) { closeP(); return; }
    /* clicks on the real page: never blocked, only observed */
    if (COACH && COACH.wait && t.closest(COACH.wait)) { unspot(); clearTimeout(smt); smt = setTimeout(function () { showMe(0); }, 140); }
    followClick(t);
    var gd = t.closest('[data-guide]');
    if (gd) Object.keys(GU).forEach(function (n) { var g = GU[n]; if (g.live) g.steps.forEach(function (s) { if (s.click && s.el === gd.dataset.guide) done(s.k); }); });
    if (G.on && !t.closest('.wg-panel,.wg-run,.wg-coach,.wg-pal,.wg-wel,#wg-notch')) closeG();
  }, true);
  D.addEventListener('change', function (ev) { followInput(ev, 1); });
  D.addEventListener('input', function (ev) { followInput(ev, 0); if (ev.target.id === 'wg-pq') { PH = 0; drawP(); } else if (ev.target.id === 'wg-gq') { G.q = ev.target.value; $('#wg-gl').innerHTML = allList(G.q); } });
  D.addEventListener('keydown', function (ev) {
    var k = ev.key, pEl = $('#wg-pal.on'), wEl = $('#wg-wel.on');
    if ((ev.metaKey || ev.ctrlKey) && k.toLowerCase() === 'k') { ev.preventDefault(); if (pEl) closeP(); else openP(); return; }
    if (pEl) {
      var n = $$('#wg-pr [data-wga="pi"]');
      if (k === 'ArrowDown' || k === 'ArrowUp') { ev.preventDefault(); PH = (PH + (k === 'ArrowDown' ? 1 : -1) + n.length) % Math.max(n.length, 1); n.forEach(function (b, i) { b.classList.toggle('hi', i === PH); }); if (n[PH]) n[PH].scrollIntoView({ block: 'nearest' }); return; }
      if (k === 'Enter') { ev.preventDefault(); pick(PH); return; }
    }
    if (k === 'Escape') {
      if (pEl) closeP(); else if (COACH && COACH.tour) tour(99); else if (COACH) unspot(); else if (wEl) { wEl.classList.remove('on'); } else if (G.on) closeG();
      return;
    }
    if (k === 'Enter' && ev.target.matches && ev.target.matches('.wg-rlst li')) { ev.target.click(); return; }
    if (k === 'Tab') { /* keep focus inside the open dialog */
      var root = pEl || wEl || (G.on && $('#wg-panel')); if (!root) return;
      var f = $$('button:not([disabled]),input,summary,[href],[tabindex="0"]', root).filter(function (e) { return e.offsetParent; });
      if (!f.length) return; var first = f[0], lastEl = f[f.length - 1];
      if (!root.contains(D.activeElement)) { ev.preventDefault(); first.focus(); } else if (ev.shiftKey && D.activeElement === first) { ev.preventDefault(); lastEl.focus(); } else if (!ev.shiftKey && D.activeElement === lastEl) { ev.preventDefault(); first.focus(); }
    }
  });

  /* ---------- public surface, then the idle start ---------- */
  var api = { ready: true, palette: function () { openP(); }, done: done, open: function (v, id) { openG(v || 'home', id); }, start: function (id, from) { if (GU[id]) startGuide(id, from | 0); } };
  W.WAIGuide = api;
  ((stub && stub.q) || []).forEach(function (a) { if (api[a[0]]) api[a[0]].apply(api, [].slice.call(a, 1)); });
  idle(function () {
    drawNotch();
    var r = ST.run;
    if (r && GU[r.id] && Date.now() - r.t < 288e5) { /* a walkthrough that was running before this page loaded */
      RUN = { id: r.id, i: Math.min(r.i, steps(r.id).length - 1), list: !!r.list, fin: false }; drawRun(); drawNotch();
      if (r.show) { RUN.show = 0; persist(); showMe(8); }
    } else if (r) { ST.run = null; save(); }
    if (!ST.tourSeen && !RUN && innerWidth >= 992) setTimeout(welcome, 700);
  });
})();
