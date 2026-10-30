/* Accommodation > Configuration — buildings, floors & rooms in one flow (Phase A: existing endpoints only).
 *
 * Data model on the server: building_models (id, BuildingName) · bulidng_and_floor_and_rooms (one row per room: building_id,
 * Floor, Room — both integers) · available_accommodation_models (a room's setup: type, beds, who stays, male/female,
 * cleaning, alert) · assing_accommodations (its beds). The tree and the setups each arrive in ONE existing list response.
 * Text is inserted with textContent / escaped; toasts use the app-wide wisdomToast().
 */
(function () {
  'use strict';
  var CFG = window.ACFG || {}, U = CFG.urls || {}, root = document.getElementById('acfg');
  if (!root) return;
  var $ = function (id) { return document.getElementById(id); };
  var CSRF = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  var lite = (window.matchMedia && matchMedia('(prefers-reduced-motion:reduce)').matches) || (navigator.connection && navigator.connection.saveData) || (navigator.hardwareConcurrency || 4) <= 2;
  var TIDE = 'cubic-bezier(.16,1,.3,1)', SWELL = 'cubic-bezier(.34,1.56,.64,1)';
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function anim(el, k, o) { return lite || !el || !el.animate ? null : el.animate(k, o); }
  function tide(els, max) { if (lite) return; Array.prototype.slice.call(els, 0, max || 60).forEach(function (el, i) { el.animate([{ opacity: 0, transform: 'translateY(10px)' }, { opacity: 1, transform: 'none' }], { duration: 460, delay: i * 14, easing: TIDE, fill: 'backwards' }); }); }
  var ICON = {
    plus: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>',
    bin: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/></svg>',
    pen: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L19 9l-4-4L4 16z"/></svg>',
    tap: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 11V5a2 2 0 0 1 4 0v5"/><path d="M13 10a2 2 0 0 1 4 0v1a2 2 0 0 1 4 0v3a7 7 0 0 1-7 7h-1a7 7 0 0 1-6-3.4L4.5 14a2 2 0 0 1 3.4-2l1.1 1.6"/></svg>',
    bld: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"/><path d="M16 9h2a2 2 0 0 1 2 2v10M3 21h18M8 7h4M8 11h4M8 15h4"/></svg>',
    x: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M7 7l10 10M17 7L7 17"/></svg>'
  };

  /* ---------- network (existing endpoints) ---------- */
  function http(method, url, body) {
    var o = { method: method, credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF } };
    if (body !== undefined) { o.headers['Content-Type'] = 'application/json'; o.body = JSON.stringify(body); }
    return fetch(url, o).then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { return { ok: r.ok && j.success !== false, status: r.status, json: j }; }); })
      .catch(function () { return { ok: false, status: 0, json: { message: 'No connection. Please try again.' } }; });
  }
  function msgOf(r) {
    var j = (r && r.json) || {};
    if (j.errors) { var k = Object.keys(j.errors)[0], v = j.errors[k]; if (v) return Array.isArray(v) ? String(v[0]) : (typeof v === 'object' ? String(v[Object.keys(v)[0]]) : String(v)); }
    return j.message || j.error || 'Something went wrong. Please try again.';
  }
  function dt(url) { return http('GET', url + '?draw=1&start=0&length=-1').then(function (r) { if (!r.ok && !(r.json && r.json.data)) throw new Error(msgOf(r)); return r.json.data || []; }); }
  function tpl(t, id) { return t.replace('__ID__', encodeURIComponent(id)); }
  function b64(id) { return btoa(String(id)); }
  function batches(list, n, fn) { // run n at a time, in order
    var out = [], i = 0;
    return (function next() {
      if (i >= list.length) return Promise.resolve(out);
      var chunk = list.slice(i, i + n); i += n;
      return Promise.all(chunk.map(fn)).then(function (r) { out = out.concat(r); return next(); });
    })();
  }
  function toast(msg, undo, type) { if (window.wisdomToast) window.wisdomToast(type || 'ok', msg, '', undo ? { undo: undo } : {}); }
  function toastErr(msg) { if (window.wisdomToast) window.wisdomToast('err', 'Couldn’t save', msg); }

  /* ---------- reference data ---------- */
  var TYPES = (CFG.types || []).map(function (t) { return { id: String(t.id), n: t.n }; });
  /* [key, label, short label, stored rank id] — the rank ids are the existing "room type" values */
  var FOR = [['GM', 'GM', 'GM', '8'], ['EXCOM', 'ExCom', 'ExCom', '1'], ['HOD', 'HOD', 'HOD', '2'], ['MGR', 'Manager', 'Mgr', '4'], ['SUP', 'Supervisor', 'Sup', '5'], ['LINE', 'Line workers', 'Line', '6']];
  var CLEAN = ['Daily', 'Weekly', 'Bi-weekly', 'Monthly']; // "Bi-weekly" is shown; the stored value stays "By Weekly"
  var GL = { M: 'Male', F: 'Female', X: 'Mixed' };
  function typ(id) { return TYPES.filter(function (t) { return t.id === String(id); })[0]; }
  function forBy(k) { return FOR.filter(function (f) { return f[0] === k; })[0] || []; }
  function forByRank(r) { var f = FOR.filter(function (x) { return x[3] === String(r); })[0]; return f ? f[0] : null; }
  function cleanUi(v) { return v === 'By Weekly' ? 'Bi-weekly' : v; }
  function cleanDb(v) { return v === 'Bi-weekly' ? 'By Weekly' : v; }
  var DEF_ALERT = Math.min(100, Math.max(50, +CFG.defaultAlert || 90));   // the resort's default for new rooms (shared by HR users)
  var CATS = [], INV = (CFG.items || []).map(function (i) { return { id: String(i.id), n: i.n || '', code: i.code || '', cat: String(i.cat), catName: 'Other', per: !!i.per, dq: +i.dq || 0, avail: +i.avail || 0 }; });

  /* ---------- state ---------- */
  /* numbering styles: F = by floor (101, 102…), C = continuous (1, 2, 3…), U = the resort's own pattern; the server stores by_floor | continuous | custom */
  var NSIN = { by_floor: 'F', continuous: 'C', custom: 'U' }, NSOUT = { F: 'by_floor', C: 'continuous', U: 'custom' };
  function bmap(x) { return { id: +x.id, n: x.n != null ? x.n : x.BuildingName, g: x.g || x.gender || 'X', ns: NSIN[x.ns || x.room_numbering] || 'F', pat: x.pat || x.room_number_pattern || '', al: (x.al != null ? x.al : x.occupancy_alert) == null ? null : +(x.al != null ? x.al : x.occupancy_alert) }; }
  var B = [], FR = [], SU = [], BLD = (CFG.buildings || []).map(bmap);
  var cur = null, sel = {}, loaded = false;
  function bldg() { return B.filter(function (b) { return b.id === cur; })[0]; }
  /* floor genders by building id: { floor: 'M' | 'F' }, read once per building; no entry = the building's setting */
  var FG = {};
  function floorG(b) {
    if (!(b.id in FG)) { FG[b.id] = {}; http('GET', tpl(U.floorGenders, b.id)).then(function (r) { if (r.ok) { FG[b.id] = (r.json && r.json.floors) || {}; if (bldg() && bldg().id === b.id) drawMain(); } }); }
    return FG[b.id];
  }
  function fgSelect(b, f) {
    if (b.g !== 'X') return '';
    var v = floorG(b)[f] || '';
    return '<select class="fgs" data-fg="' + f + '" aria-label="Who ' + fname(f) + ' is for">' + [['', 'Male & female'], ['M', 'Male only'], ['F', 'Female only']].map(function (o) { return '<option value="' + o[0] + '"' + (o[0] === v ? ' selected' : '') + '>' + o[1] + '</option>'; }).join('') + '</select>';
  }
  function fname(f) { return f === 0 ? 'Ground floor' : 'Floor ' + f; }
  function key(f, no) { return f + '|' + no; }
  function selKeys() { return Object.keys(sel); }
  function selClear() { sel = {}; }
  function allRooms(b) { var o = []; b.floors.forEach(function (fl) { fl.rooms.forEach(function (r) { o.push({ f: fl.f, no: r.no, rid: r.rid, s: r.s }); }); }); return o; }
  function cnt(b) { var r = allRooms(b), set = r.filter(function (x) { return x.s; }); return { fl: b.floors.length, rooms: r.length, set: set.length, beds: set.reduce(function (a, x) { return a + x.s.cap; }, 0) }; }
  function numOf(no) { var m = String(no).match(/\d+$/); return m ? parseInt(m[0], 10) : 0; }
  function natcmp(a, b) { return String(a).localeCompare(String(b), undefined, { numeric: true }); }
  function maxNo(b) { return allRooms(b).reduce(function (m, r) { return Math.max(m, numOf(r.no)); }, 0); }
  function pad2(n) { return n < 10 ? '0' + n : String(n); }
  /* pattern tokens: {F} floor (G for ground), {N} / {NN} room counter on the floor, {C} counter across the building */
  function fmt(pat, f, n, c) { return pat.replace(/\{(F|NN|N|C)\}/g, function (m, t) { return t === 'F' ? (f === 0 ? 'G' : String(f)) : t === 'N' ? String(n) : t === 'NN' ? pad2(n) : String(c); }); }
  function patCheck(p) {
    if (!p) return 'Type a pattern, like F{F}-{N}';
    if (p.length > 40) return 'Keep the pattern under 40 characters';
    if (!/^([A-Za-z0-9 .\/-]|\{(F|N|NN|C)\})+$/.test(p)) return 'Use letters, digits, space, - / . and {F} {N} {NN} {C}';
    if (!/\{(N|NN|C)\}/.test(p)) return 'Add {N}, {NN} or {C} so every room gets its own number';
    return '';
  }
  /* counters already used by the rooms of a building that follow a pattern: { n: highest {N} by floor, c: highest {C} } */
  function patCounters(pat, b) {
    var toks = [], src = pat.replace(/[.*+?^$()|[\]\\\/]/g, '\\$&').replace(/\{(F|NN|N|C)\}/g, function (m, t) { toks.push(t); return t === 'F' ? '(?:G|\\d+)' : t === 'NN' ? '(\\d{2,})' : '(\\d+)'; }), re = new RegExp('^' + src + '$'), out = { n: {}, c: 0 };
    toks = toks.filter(function (t) { return t !== 'F'; });
    allRooms(b).forEach(function (r) { var m = re.exec(r.no); if (!m) return; toks.forEach(function (t, i) { var v = parseInt(m[i + 1], 10); if (t === 'C') out.c = Math.max(out.c, v); else out.n[r.f] = Math.max(out.n[r.f] || 0, v); }); });
    return out;
  }
  function rno(f, i, ns, c, pat) { return ns === 'U' ? fmt(pat, f, i, c) : ns === 'C' ? String(c) : f === 0 ? 'G' + pad2(i) : String(f * 100 + i); }
  /* starting quantities for new rooms, never more than the stock can cover across all of them */
  function fit(i, q) { var n = picked().filter(function (r) { return !r.s; }).length || 1; return Math.max(0, Math.min(q, Math.floor(i.avail / n))); }
  function invDefault(cap) { var o = {}; INV.forEach(function (i) { var q = fit(i, i.per ? cap * (i.dq || 1) : i.dq); if (q > 0) o[i.id] = q; }); return o; }

  function build() {
    var setupBy = {}, used = {};
    SU.forEach(function (r) { setupBy[r.building_id + '|' + r.Floor + '|' + r.RoomNo] = r; });
    B = BLD.map(function (bd) {
      var floors = {};
      FR.filter(function (r) { return +r.building_id === bd.id; }).forEach(function (r) {
        var f = +r.Floor, fl = floors[f] || (floors[f] = { f: f, g: null, rooms: [] }), k = bd.id + '|' + f + '|' + r.Room;
        used[k] = 1; fl.rooms.push({ no: String(r.Room), rid: +r.id, s: setup(setupBy[k]) });
      });
      Object.keys(setupBy).forEach(function (k) { // set up through Excel without a floor/room row: still show it
        var p = k.split('|'); if (+p[0] !== bd.id || used[k]) return;
        var f = +p[1], fl = floors[f] || (floors[f] = { f: f, g: null, rooms: [] }); fl.rooms.push({ no: String(p[2]), rid: null, s: setup(setupBy[k]) });
      });
      var fls = Object.keys(floors).map(function (x) { return floors[x]; });
      fls.forEach(function (fl) { fl.rooms.sort(function (a, b) { return natcmp(a.no, b.no); }); });
      fls.sort(function (a, b) { return a.f - b.f; });
      return { id: bd.id, name: bd.n, floors: fls, ns: bd.ns, g: bd.g, pat: bd.pat, al: bd.al };
    });
  }
  function setup(r) {
    if (!r) return null;
    var beds = String(r.Beds || '0/0').split('/');
    return { aid: +r.id, t: String(r.Accommodation_type_id), cap: +r.Capacity || 0, f: forByRank(r.RoomType), g: r.blockFor === 'Female' ? 'F' : 'M', c: cleanUi(r.CleaningSchedule), a: Math.min(100, Math.max(50, +r.Occupancytheresold || DEF_ALERT)), status: r.RoomStatus || 'Available', occ: +beds[0] || 0 };
  }
  function loadTree() { return dt(U.floorRooms).then(function (r) { FR = r; }); }
  function loadSetups() { return dt(U.setups).then(function (r) { SU = r; }); }
  function loadBuildings() { return dt(U.buildings).then(function (r) { BLD = r.map(bmap); }); }
  function loadTypes() { return dt(U.types).then(function (r) { TYPES = r.map(function (x) { return { id: String(x.id), n: x.AccommodationName }; }); }); }
  function loadCats() { return dt(U.cats).then(function (r) { CATS = r.map(function (x) { return { id: String(x.id), n: x.CategoryName }; }); catNames(); }); }
  function catNames() { var m = {}; CATS.forEach(function (c) { m[c.id] = c.n; }); INV.forEach(function (i) { i.catName = m[i.cat] || 'Other'; }); INV.sort(function (a, b) { return a.catName.localeCompare(b.catName) || a.n.localeCompare(b.n); }); }
  /* reload what changed, then redraw; keeps the open building and any still-valid picks */
  function reload(w) {
    w = w || {};
    var jobs = []; if (w.b) jobs.push(loadBuildings()); if (w.t !== false) jobs.push(loadTree()); if (w.s) jobs.push(loadSetups());
    return Promise.all(jobs).then(function () {
      build(); if (!bldg()) cur = B[0] ? B[0].id : null;
      var b = bldg(), valid = {}; if (b) allRooms(b).forEach(function (r) { valid[key(r.f, r.no)] = 1; });
      Object.keys(sel).forEach(function (k) { if (!valid[k]) delete sel[k]; });
      drawAll(); drawBar();
    });
  }

  /* ---------- buildings rail ---------- */
  var q = '', bf = 'all';
  function bstat(b) { var c = cnt(b); return c.rooms && c.set === c.rooms ? 'ready' : c.set ? 'part' : 'none'; }
  function drawRail() {
    $('bc').textContent = B.length;
    var many = B.length >= 8; $('bsW').hidden = !many; $('bfl').hidden = !many; if (!many) { q = ''; bf = 'all'; }
    var n = { all: B.length, ready: 0, part: 0, none: 0 }; B.forEach(function (b) { n[bstat(b)]++; });
    $('bfl').innerHTML = [['all', 'All'], ['part', 'In progress'], ['none', 'Not set up'], ['ready', 'Ready']].filter(function (x) { return x[0] === 'all' || n[x[0]]; })
      .map(function (x) { return '<button type="button" class="' + (bf === x[0] ? 'on' : '') + '" data-bf="' + x[0] + '">' + x[1] + ' <b>' + n[x[0]] + '</b></button>'; }).join('');
    var list = B.filter(function (b) { return (bf === 'all' || bstat(b) === bf) && (!q || b.name.toLowerCase().indexOf(q.toLowerCase()) > -1); });
    $('bl').innerHTML = !B.length ? '<div class="side-empty">No buildings yet.</div>' : !list.length ? '<div class="side-empty">No buildings match “' + esc(q) + '”.</div>' :
      list.map(function (b) {
        var c = cnt(b), s = bstat(b), st = s === 'ready' ? '<span class="bst ok">✓ Ready</span>' : s === 'part' ? '<span class="bst part">' + c.set + ' of ' + c.rooms + ' set up</span>' : '<span class="bst none">Not set up</span>';
        return '<button type="button" class="bi' + (b.id === cur ? ' on' : '') + '" data-b="' + b.id + '"><span class="bn">' + esc(b.name) + '</span><span class="bm">' + c.fl + ' ' + (c.fl === 1 ? 'floor' : 'floors') + ' · ' + c.rooms + ' ' + (c.rooms === 1 ? 'room' : 'rooms') + ' · ' + GL[b.g] + '</span>' + st + '</button>';
      }).join('');
  }
  $('bl').addEventListener('click', function (e) { var b = e.target.closest('[data-b]'); if (!b || +b.dataset.b === cur) return; cur = +b.dataset.b; selClear(); drawAll(true); drawBar(); });
  $('bs').addEventListener('input', function (e) { q = e.target.value.trim(); drawRail(); });
  $('bfl').addEventListener('click', function (e) { var b = e.target.closest('[data-bf]'); if (!b || b.dataset.bf === bf) return; bf = b.dataset.bf; drawRail(); tide($('bl').children, 20); });

  /* ---------- building view ---------- */
  function tile(fl, r) {
    var k = key(fl.f, r.no), on = !!sel[k];
    if (!r.s) return '<button type="button" class="rm unset' + (on ? ' sel' : '') + '" data-k="' + k + '" aria-pressed="' + on + '" aria-label="Room ' + esc(r.no) + ', not set up"><span class="no">' + esc(r.no) + '</span><span class="inf">Not set up</span></button>';
    var s = r.s, t = typ(s.t), tip = (t ? t.n : '—') + ' · ' + (forBy(s.f)[1] || '—') + ' · ' + (s.g === 'M' ? 'Male' : 'Female') + ' · ' + s.c + ' cleaning';
    return '<button type="button" class="rm' + (on ? ' sel' : '') + '" data-k="' + k + '" aria-pressed="' + on + '" data-tip="' + esc(tip) + '" aria-label="Room ' + esc(r.no) + ': ' + esc(tip) + '"><span class="no">' + esc(r.no) + '</span><span class="g ' + s.g + '">' + s.g + '</span><span class="inf">' + s.cap + ' ' + (s.cap === 1 ? 'bed' : 'beds') + ' · ' + esc(forBy(s.f)[2] || '') + '</span></button>';
  }
  function nextNo(fl) {
    var b = bldg(); if (!b) return '1';
    if (b.ns === 'C') return String(maxNo(b) + 1);
    if (b.ns === 'U') { var pc = patCounters(b.pat, b); return fmt(b.pat, fl.f, (pc.n[fl.f] || 0) + 1, pc.c + 1); }
    var nums = fl.rooms.map(function (r) { return numOf(r.no); }).filter(Boolean), top = nums.length ? Math.max.apply(null, nums) : 0;
    if (fl.f === 0) return fl.rooms.some(function (r) { return /^G/.test(r.no); }) || !nums.length ? 'G' + pad2(top + 1) : String(top + 1);
    return String(nums.length ? top + 1 : fl.f * 100 + 1);
  }
  function numLabel(b) { return b.ns === 'C' ? 'Numbered 1, 2, 3…' : b.ns === 'U' ? 'Numbered like ' + fmt(b.pat, 1, 1, 1) : 'Numbered by floor'; }
  /* a building set to Male or Female can't hold rooms of the other gender */
  function gBlock(b, g) { if (g === 'X') return ''; var bad = allRooms(b).filter(function (r) { return r.s && r.s.g !== g; }).length; return bad ? bad + (bad === 1 ? ' room is' : ' rooms are') + ' set up as ' + (g === 'M' ? 'Female' : 'Male') : ''; }
  function alertRow(b) { return '<span class="lb2">Alert HR at</span>' + (b.al == null ? '<span class="bgv muted">Not set</span><button class="ib" type="button" id="balE" aria-label="Set building alert" data-tip="Set alert">' + ICON.pen + '</button>' : '<span class="bgv">' + b.al + '% full</span><button class="ib" type="button" id="balE" aria-label="Change building alert" data-tip="Change">' + ICON.pen + '</button>'); }
  function drawMain(enter) {
    var el = $('main'), b = bldg();
    if (!loaded) return;
    if (!B.length) {
      el.innerHTML = '<div class="onb"><div class="ei">' + ICON.bld + '</div><div class="et">Set up your staff accommodation</div>' +
        '<div class="es">Add a building with its floors and rooms in one go, then set up many rooms together.</div>' +
        '<div class="steps3"><div><b>1</b><strong>Add a building</strong><span>Name it and say how many floors and rooms it has.</span></div>' +
        '<div><b>2</b><strong>Set up rooms</strong><span>Pick rooms and choose sharing, who stays, male or female, cleaning and inventory.</span></div>' +
        '<div><b>3</b><strong>Assign staff</strong><span>Rooms are ready to assign in Assign Accommodation.</span></div></div>' +
        '<div class="ac-row"><button class="ac-btn btn-main btn-lg" type="button" data-go="addB">' + ICON.plus + 'Add your first building</button><button class="ac-btn btn-gh btn-lg" type="button" data-go="bulk">Upload from Excel</button></div></div>';
      tide(el.querySelectorAll('.steps3 div,.onb .ac-row')); return;
    }
    var c = cnt(b), pct = c.rooms ? Math.round(c.set / c.rooms * 100) : 0, unset = c.rooms - c.set, floors = b.floors.slice().sort(function (x, y) { return y.f - x.f; });
    var prog = !c.rooms ? '<div class="prg"><div class="pgh"><span class="lb2">Room setup</span></div><div class="pgv"><b>0</b> rooms yet</div><button class="ac-btn btn-main" type="button" id="addF2">Add floors</button></div>' :
      unset ? '<div class="prg"><div class="pgh"><span class="lb2">Room setup</span><span class="pgp">' + pct + '%</span></div><div class="pgv"><b>' + c.set + '</b> of ' + c.rooms + ' rooms set up' + (c.beds ? ' <span>· ' + c.beds + ' beds so far</span>' : '') + '</div><div class="pgb" aria-hidden="true"><i style="width:' + pct + '%"></i></div><button class="ac-btn btn-main" type="button" id="doUnset">Set up the other ' + unset + ' ' + (unset === 1 ? 'room' : 'rooms') + '</button></div>' :
      '<div class="prg ok"><div class="pgh"><span class="lb2">Room setup</span><span class="pgp">✓ Ready</span></div><div class="pgv"><b>All ' + c.rooms + '</b> rooms set up <span>· ' + c.beds + ' beds</span></div><div class="pgb" aria-hidden="true"><i style="width:100%"></i></div><a class="ac-btn btn-ctx" href="' + esc(U.assign) + '">Assign staff →</a></div>';
    el.innerHTML = '<div class="bh"><div class="bid"><div class="bt" id="bt"><h2>' + esc(b.name) + '</h2><button class="ib" type="button" id="ren" aria-label="Rename building" data-tip="Rename">' + ICON.pen + '</button></div>' +
      '<div class="facts"><span>' + c.fl + ' ' + (c.fl === 1 ? 'floor' : 'floors') + '</span><span>' + c.rooms + ' ' + (c.rooms === 1 ? 'room' : 'rooms') + '</span><span>' + numLabel(b) + '</span></div>' +
      '<div class="bgf"><span class="lb2">For</span><div class="seg sm" role="radiogroup" aria-label="Who this building is for">' + ['M', 'F', 'X'].map(function (g) { var why = gBlock(b, g);
        return '<button type="button" role="radio" aria-checked="' + (b.g === g) + '" class="' + (b.g === g ? 'on' : '') + (why ? ' dis' : '') + '" data-bg="' + g + '"' + (why ? ' aria-disabled="true" data-tip="' + esc(why) + '"' : '') + '>' + GL[g] + '</button>'; }).join('') + '</div></div>' +
      '<div class="bgf" id="bal">' + alertRow(b) + '</div></div>' + prog + '</div>' +
      (c.rooms ? '<div class="phint">' + ICON.tap + '<span>' + (unset ? 'Click rooms to pick them, or a <b>floor name</b> for the whole floor. New rooms get set up; rooms already set up get edited.' : 'Click any room to edit it, or a <b>floor name</b> to edit the whole floor.') + '</span></div>' : '') +
      '<div class="stack">' + floors.map(function (fl) {
        var s = fl.rooms.filter(function (r) { return r.s; }).length, below = b.floors.filter(function (x) { return x.f === fl.f - 1; })[0], hasSel = fl.rooms.some(function (r) { return sel[key(fl.f, r.no)]; });
        var canCopy = below && below.rooms.some(function (r) { return r.s; }) && fl.rooms.some(function (r) { return !r.s; });
        return '<div class="flr' + (hasSel ? ' has-sel' : '') + '" data-f="' + fl.f + '"><div class="fcol"><button type="button" class="fn" data-fsel="' + fl.f + '" aria-label="Pick every room on ' + fname(fl.f) + '"><b>' + fname(fl.f) + '</b><span>' + fl.rooms.length + ' ' + (fl.rooms.length === 1 ? 'room' : 'rooms') + ' · ' + (s === fl.rooms.length ? 'all set up' : s + ' set up') + '</span><em>Pick whole floor</em></button>' + fgSelect(b, fl.f) + '</div>' +
          '<div class="rooms">' + fl.rooms.map(function (r) { return tile(fl, r); }).join('') + '<button type="button" class="addr" data-addr="' + fl.f + '" aria-label="Add a room to ' + fname(fl.f) + '" data-tip="Add room ' + nextNo(fl) + '">' + ICON.plus + '</button></div>' +
          '<div class="fa">' + (canCopy ? '<button class="ac-btn btn-ctx cpy" type="button" data-copy="' + fl.f + '" data-tip="Give these rooms the same setup as ' + fname(fl.f - 1) + ', room by room, items included">Same as ' + fname(fl.f - 1) + '</button>' : '') +
          '<button class="ib" type="button" data-delf="' + fl.f + '" aria-label="Remove ' + fname(fl.f) + '" data-tip="Remove floor">' + ICON.bin + '</button></div></div>';
      }).join('') + '</div><button class="ac-btn btn-gh addf" type="button" id="addF">' + ICON.plus + 'Add floors</button>';
    if (enter) { tide(el.querySelectorAll('.flr'), 20); tide(el.querySelectorAll('.rm'), 80); }
  }
  function drawAll(enter) { if (!bldg()) cur = B[0] ? B[0].id : null; drawRail(); drawMain(enter); if (enter) tide($('bl').children); }

  $('main').addEventListener('click', function (e) {
    var t = e.target, go = t.closest('[data-go]');
    if (go) { go.dataset.go === 'addB' ? openModal('b') : switchTab('bulk'); return; }
    var rm = t.closest('.rm');
    if (rm) { var k = rm.dataset.k; if (sel[k]) delete sel[k]; else sel[k] = 1; rm.classList.toggle('sel'); rm.setAttribute('aria-pressed', !!sel[k]);
      var fr = rm.closest('.flr'); fr.classList.toggle('has-sel', Array.prototype.some.call(rm.closest('.rooms').querySelectorAll('.rm'), function (x) { return x.classList.contains('sel'); }));
      anim(rm, [{ transform: 'scale(.94)' }, { transform: 'none' }], { duration: 260, easing: SWELL }); drawBar(); return; }
    var fs = t.closest('[data-fsel]');
    if (fs) { var f = +fs.dataset.fsel, fl = bldg().floors.filter(function (x) { return x.f === f; })[0], ks = fl.rooms.map(function (r) { return key(f, r.no); }), all = ks.every(function (k2) { return sel[k2]; });
      ks.forEach(function (k2) { if (all) delete sel[k2]; else sel[k2] = 1; }); drawMain(); drawBar();
      if (!all) anim($('main').querySelector('.flr[data-f="' + f + '"]'), [{ transform: 'scale(.995)' }, { transform: 'none' }], { duration: 240, easing: SWELL }); return; }
    if (t.closest('#doUnset')) { selClear(); allRooms(bldg()).filter(function (r) { return !r.s; }).forEach(function (r) { sel[key(r.f, r.no)] = 1; }); drawMain(); drawBar(); openDrawer(); return; }
    var ad = t.closest('[data-addr]'); if (ad) { addRoom(+ad.dataset.addr); return; }
    var cp = t.closest('[data-copy]'); if (cp) { copyFloor(+cp.dataset.copy); return; }
    var df = t.closest('[data-delf]'); if (df) { removeFloor(+df.dataset.delf); return; }
    if (t.closest('#addF') || t.closest('#addF2')) { openModal('f'); return; }
    if (t.closest('#ren')) rename();
    var bg = t.closest('[data-bg]'); if (bg) { setGender(bg.dataset.bg); return; }
    if (t.closest('#balE')) editAlert();
  });

  /* floor gender: PUT, with undo */
  $('main').addEventListener('change', function (e) {
    var sg = e.target.closest('select[data-fg]'); if (!sg) return;
    var b = bldg(), f = +sg.dataset.fg, map = floorG(b), old = map[f] || '', v = sg.value;
    var put = function (g) { return http('PUT', tpl(U.floorGender, b.id).replace('__FLOOR__', f), { gender: g || null }).then(function (r) { if (!r.ok) { toastErr(msgOf(r)); return false; } if (g) map[f] = g; else delete map[f]; drawMain(); return true; }); };
    put(v).then(function (ok) { if (ok) toast(fname(f) + ' is ' + (v === 'M' ? 'for male staff only' : v === 'F' ? 'for female staff only' : 'for male and female staff'), function () { put(old).then(function (ok2) { if (ok2) toast('Change undone'); }); }); });
  });

  /* who the building is for (stored on the building) */
  function setGender(g) {
    var b = bldg(); if (g === b.g) return;
    var why = gBlock(b, g); if (why) { toast(why + ', so this building can’t be set to ' + GL[g] + ' only.', null, 'warn'); return; }
    var old = b.g;
    http('PUT', U.buildingUpdate, { CatId: b64(b.id), BuildingName: b.name, gender: g }).then(function (r) {
      if (!r.ok) return toastErr(msgOf(r));
      reload({ b: true, t: false }).then(function () { toast(b.name + ' is now ' + (g === 'X' ? 'for male and female staff' : 'for ' + GL[g].toLowerCase() + ' staff only'), function () { http('PUT', U.buildingUpdate, { CatId: b64(b.id), BuildingName: b.name, gender: old }).then(function (u) { if (u.ok) { reload({ b: true, t: false }); toast('Building is back to ' + GL[old]); } else toastErr(msgOf(u)); }); }); });
    });
  }
  /* building alert: HR is told when the building is at least this % full (50–100), or off */
  function editAlert() {
    var b = bldg(), row = $('bal'), v = b.al == null ? DEF_ALERT : b.al;
    row.innerHTML = '<span class="lb2">Alert HR at</span><div class="rng"><input type="range" min="50" max="100" step="5" value="' + v + '" aria-label="Building occupancy alert"><b>' + v + '%</b></div><button class="ac-btn btn-main sm" type="button" id="balS">Save</button>' + (b.al == null ? '' : '<button class="ac-btn btn-gh sm" type="button" id="balO">Turn off</button>') + '<button class="ac-btn btn-gh sm" type="button" id="balC">Cancel</button>';
    var rg = row.querySelector('input'); rg.focus();
    rg.addEventListener('input', function () { row.querySelector('b').textContent = rg.value + '%'; });
    $('balC').onclick = function () { drawMain(); };
    if ($('balO')) $('balO').onclick = function () {
      var old = b.al;
      http('PUT', U.buildingUpdate, { CatId: b64(b.id), BuildingName: b.name, clear_occupancy_alert: true }).then(function (r) {
        if (!r.ok) return toastErr(msgOf(r));
        reload({ b: true, t: false }).then(function () { toast('Building alert turned off for ' + b.name, function () { http('PUT', U.buildingUpdate, { CatId: b64(b.id), BuildingName: b.name, occupancy_alert: old }).then(function (u) { if (u.ok) { reload({ b: true, t: false }); toast('Alert back to ' + old + '%'); } else toastErr(msgOf(u)); }); }); });
      });
    };
    $('balS').onclick = function () {
      var nv = +rg.value, old = b.al; if (nv === old) return drawMain();
      http('PUT', U.buildingUpdate, { CatId: b64(b.id), BuildingName: b.name, occupancy_alert: nv }).then(function (r) {
        if (!r.ok) return toastErr(msgOf(r));
        reload({ b: true, t: false }).then(function () { toast('HR will be told when ' + b.name + ' is ' + nv + '% full', old == null ? null : function () { http('PUT', U.buildingUpdate, { CatId: b64(b.id), BuildingName: b.name, occupancy_alert: old }).then(function (u) { if (u.ok) { reload({ b: true, t: false }); toast('Alert back to ' + old + '%'); } else toastErr(msgOf(u)); }); }); });
      });
    };
  }

  function rename() {
    var bt = $('bt'), b = bldg(); bt.innerHTML = '<input id="renI" value="' + esc(b.name) + '" maxlength="50" aria-label="Building name">';
    var i = $('renI'), fin = false; i.focus(); i.select();
    function done(ok) {
      if (fin) return; fin = true; var v = i.value.trim();
      if (!ok || !v || v === b.name) { drawAll(); return; }
      if (B.some(function (x) { return x.id !== b.id && x.name.toLowerCase() === v.toLowerCase(); })) { toast('There’s already a building called ' + v, null, 'warn'); drawAll(); return; }
      var old = b.name;
      http('PUT', U.buildingUpdate, { CatId: b64(b.id), BuildingName: v }).then(function (r) {
        if (!r.ok) { toastErr(msgOf(r)); return drawAll(); }
        return reload({ b: true, t: false }).then(function () {
          toast('Renamed to ' + v, function () { http('PUT', U.buildingUpdate, { CatId: b64(b.id), BuildingName: old }).then(function (u) { if (u.ok) { reload({ b: true, t: false }); toast('Renamed back to ' + old); } else toastErr(msgOf(u)); }); });
        });
      });
    }
    i.addEventListener('keydown', function (ev) { if (ev.key === 'Enter') done(true); if (ev.key === 'Escape') done(false); });
    i.addEventListener('blur', function () { done(true); });
  }

  /* add one room to a floor */
  function addRoom(f) {
    var b = bldg(), fl = b.floors.filter(function (x) { return x.f === f; })[0], no = nextNo(fl);
    http('POST', U.roomStore, { building_id: b.id, Floor: f, Room: String(no) }).then(function (r) {
      if (!r.ok) return toastErr(msgOf(r));
      reload().then(function () {
        var nt = $('main').querySelector('.rm[data-k="' + key(f, no) + '"]'); anim(nt, [{ transform: 'scale(.6)', opacity: 0 }, { transform: 'none', opacity: 1 }], { duration: 380, easing: SWELL });
        toast('Room ' + no + ' added to ' + fname(f), function () { delRooms(b.id, [{ f: f, no: no }]).then(function (ok) { if (ok) { reload(); toast('Room ' + no + ' removed'); } }); });
      });
    });
  }
  /* delete floor/room rows by (floor, number) in the building; resolves { ok: all went, trash: ids to restore them } */
  function delRooms(bid, list) {
    var b = B.filter(function (x) { return x.id === bid; })[0], ids = [];
    list.forEach(function (x) { var fl = b.floors.filter(function (y) { return y.f === x.f; })[0], r = fl && fl.rooms.filter(function (z) { return z.no === String(x.no); })[0]; if (r && r.rid) ids.push(r.rid); });
    return batches(ids, 10, function (id) { return http('DELETE', tpl(U.roomDestroy, b64(id))); }).then(function (rs) {
      var bad = rs.filter(function (r) { return !r.ok; }); if (bad.length) toastErr(msgOf(bad[0]));
      return { ok: !bad.length, trash: rs.map(function (r) { return r.json && r.json.trash_id; }).filter(Boolean) };
    });
  }
  /* put deleted rows back on their old ids (rooms keep their place, links to them still work) */
  function restoreTrash(trash) { return batches(trash, 10, function (id) { return http('POST', tpl(U.restore, id)); }).then(function (rs) { var bad = rs.filter(function (r) { return !r.ok; }); if (bad.length) toastErr(msgOf(bad[0])); return !bad.length; }); }
  function removeFloor(f) {
    var b = bldg(), fl = b.floors.filter(function (x) { return x.f === f; })[0];
    if (fl.rooms.some(function (r) { return r.s; })) { toast(fname(f) + ' has rooms that are set up, so it can’t be removed here. Remove their setup from the Room setup list first.', null, 'warn'); return; }
    var list = fl.rooms.map(function (r) { return { f: f, no: r.no }; });
    delRooms(b.id, list).then(function (res) {
      Object.keys(sel).forEach(function (k) { if (k.indexOf(f + '|') === 0) delete sel[k]; });
      reload().then(function () { drawBar(); if (res.ok) toast(fname(f) + ' removed', function () { restoreTrash(res.trash).then(function (ok2) { reload(); toast(ok2 ? fname(f) + ' is back' : 'Some rooms couldn’t be restored', null, ok2 ? 'ok' : 'warn'); }); }); });
    });
  }

  /* ---------- selection bar ---------- */
  function picked() { var b = bldg(), o = []; if (!b) return o; b.floors.forEach(function (fl) { fl.rooms.forEach(function (r) { if (sel[key(fl.f, r.no)]) o.push({ f: fl.f, no: r.no, rid: r.rid, s: r.s }); }); }); return o; }
  function pickMode() { var rs = picked(), s = rs.filter(function (r) { return r.s; }).length; return s === 0 ? 'new' : s === rs.length ? 'edit' : 'mix'; }
  function drawBar() {
    var n = selKeys().length; $('sbar').classList.toggle('on', n > 0); if (!n) return;
    var m = pickMode(), rs = picked(), s = rs.filter(function (r) { return r.s; }).length;
    $('sc').innerHTML = '<b>' + n + '</b>' + (n === 1 ? 'room' : 'rooms') + ' picked' + (m === 'mix' ? ' <small>· ' + s + ' already set up</small>' : '');
    $('sSet').textContent = m === 'edit' ? (n === 1 ? 'Edit room' : 'Edit ' + n + ' rooms') : (n === 1 ? 'Set up room' : 'Set up ' + n + ' rooms');
    var del = $('sDel'), blocked = s > 0 || rs.some(function (r) { return !r.rid; });
    del.classList.toggle('dis', blocked); del.setAttribute('aria-disabled', blocked);
    if (blocked) del.setAttribute('data-tip', 'Rooms that are set up can’t be removed here'); else del.removeAttribute('data-tip');
  }
  $('sClr').onclick = function () { selClear(); drawMain(); drawBar(); };
  $('sDel').onclick = function () {
    var rs = picked(), b = bldg(); if ($('sDel').classList.contains('dis')) { toast('Rooms that are set up can’t be removed here. Remove their setup from the Room setup list first.', null, 'warn'); return; }
    var list = rs.map(function (r) { return { f: r.f, no: r.no }; });
    delRooms(b.id, list).then(function (res) { selClear(); reload().then(function () { drawBar(); if (res.ok) toast(list.length + (list.length === 1 ? ' room' : ' rooms') + ' removed', function () { restoreTrash(res.trash).then(function (ok2) { reload(); toast(ok2 ? 'Rooms are back' : 'Some rooms couldn’t be restored', null, ok2 ? 'ok' : 'warn'); }); }); }); });
  };
  $('sSet').onclick = openDrawer;

  /* ---------- room setup drawer ---------- */
  var D = null, invOpen = false, review = false, invQ = '', invF = 'room', failed = [], busy = false;
  function newD() { var bg = bldg() ? bldg().g : 'X'; return { t: null, cap: null, f: null, g: bg === 'X' ? null : bg, c: 'Weekly', a: DEF_ALERT, inv: {}, touched: false, loading: false, from: null }; }
  /* a set-up room's items: { item id: quantity }; items with no stock left aren't in the page's item list, so they're added to it here */
  function loadItems(aid) {
    return http('GET', tpl(U.items, aid)).then(function (r) {
      var m = {}; ((r.json && r.json.items) || []).forEach(function (x) {
        var id = String(x.id); m[id] = +x.qty || 0;
        if (!INV.some(function (i) { return i.id === id; })) { INV.push({ id: id, n: x.name || '', code: x.code || '', cat: '', catName: x.category || 'Other', per: false, dq: 0, avail: +x.available || 0 }); INV.sort(function (a, b) { return a.catName.localeCompare(b.catName) || a.n.localeCompare(b.n); }); }
      }); return m;
    });
  }
  function openDrawer() {
    var rs = picked(); if (!rs.length) return;
    var from = rs.filter(function (r) { return r.s; })[0];
    D = newD();
    if (from) { var s = from.s; D.t = s.t; D.cap = s.cap; D.f = s.f; D.g = s.g; D.c = s.c; D.a = s.a; D.from = from; D.loading = true; }
    var fls = rs.map(function (r) { return r.f; }).filter(function (v, i, a) { return a.indexOf(v) === i; });
    $('drT').textContent = drTitle();
    var where = fls.length > 1 ? rs.length + ' rooms on ' + fls.length + ' floors' : fname(fls[0]) + (rs.length > 1 ? ' · ' + rs[0].no + '–' + rs[rs.length - 1].no : '');
    $('drS').textContent = bldg().name + ' · ' + where;
    invOpen = false; review = false; failed = []; busy = false; drawDrawer(); $('drb').scrollTop = 0;
    $('scrim').classList.add('on'); $('dr').classList.add('on'); $('dr').setAttribute('aria-hidden', 'false');
    var mine = D;
    if (from) loadItems(from.s.aid).then(function (m) { if (D !== mine) return; if (!D.touched) D.inv = m; D.loading = false; if (!review) drawDrawer(); });
    setTimeout(function () { var f = $('drb').querySelector('button'); if (f) f.focus(); }, 300);
  }
  function closeDrawer() { $('scrim').classList.remove('on'); $('dr').classList.remove('on'); $('dr').setAttribute('aria-hidden', 'true'); }
  function drTitle() { var rs = picked(), v = pickMode() === 'edit' ? 'Edit' : 'Set up'; return rs.length === 1 ? v + ' room ' + rs[0].no : v + ' ' + rs.length + ' rooms'; }
  function stepH(id, v, min, max, lbl) { return '<div class="step" data-st="' + id + '"><button type="button" data-d="-1" aria-label="Less ' + esc(lbl) + '"' + (v <= min ? ' disabled' : '') + '>−</button><input type="number" value="' + v + '" min="' + min + '" max="' + max + '" aria-label="' + esc(lbl) + '"><button type="button" data-d="1" aria-label="More ' + esc(lbl) + '"' + (v >= max ? ' disabled' : '') + '>+</button></div>'; }
  var hasNew = function () { return picked().some(function (r) { return !r.s; }); };
  function drawDrawer() {
    if (review) { drawReview(); return; }
    $('drC').textContent = 'Cancel';
    var rs = picked(), cap = D.cap || 0, t = typ(D.t), bg = bldg().g, nOld = rs.filter(function (r) { return r.s; }).length;
    var okRoom = !!D.t, okWho = !!D.f && !!D.g;
    var items = INV.filter(function (i) { return (D.inv[i.id] || 0) > 0; }), pieces = items.reduce(function (a, i) { return a + D.inv[i.id]; }, 0);
    function grp(n, title, ok, sum, body, id) { return '<section class="grp' + (ok ? ' ok' : '') + '"' + (id ? ' id="' + id + '"' : '') + '><div class="gh"><span class="gn">' + (ok ? '✓' : n) + '</span><span class="gt">' + title + '</span><span class="gs"' + (id ? ' id="' + id + 'S"' : '') + '>' + sum + '</span></div>' + body + '</section>'; }
    var invBody = invOpen ? '<label class="isr"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg><input id="invQ" placeholder="Search ' + INV.length + ' items, e.g. pillow, kettle" aria-label="Search inventory items" autocomplete="off" value="' + esc(invQ) + '"><button type="button" class="isx" data-iclr aria-label="Clear search"' + (invQ ? '' : ' hidden') + '>' + ICON.x + '</button></label><div class="ifl" id="invF"></div><div class="inv" id="invL" aria-live="polite"></div><button type="button" class="glink" data-inv="0">Done</button>'
      : '<div class="invs">' + (D.loading ? '<em>Loading items…</em>' : items.length ? items.map(function (i) { return '<span><b>' + D.inv[i.id] + '</b> ' + esc(i.n) + '</span>'; }).join('') : '<em>Nothing added</em>') + '</div>' +
        (nOld > 1 && !D.touched ? '<div class="fnote">Showing the items of room ' + esc(D.from.no) + '. If you change them, all ' + nOld + ' rooms that are already set up get the same items.</div>' : '') +
        '<button type="button" class="glink" data-inv="1"' + (D.loading ? ' disabled' : '') + '>Change items</button>';
    $('drb').innerHTML =
      grp(1, 'Room', okRoom, okRoom ? esc(t ? t.n : '') + ' · ' + cap + ' ' + (cap === 1 ? 'bed' : 'beds') : 'Not chosen yet',
        '<div class="fs"><span class="lb">Accommodation type</span><div class="chips" role="radiogroup">' + TYPES.map(function (x) { return '<button type="button" role="radio" aria-checked="' + (D.t === x.id) + '" class="' + (D.t === x.id ? 'on' : '') + '" data-type="' + x.id + '">' + esc(x.n) + '</button>'; }).join('') + '<button type="button" class="new" data-go="types">+ New type</button></div></div>' +
        (D.t ? '<div class="fs"><span class="lb">Beds in each room</span><div class="ac-row">' + stepH('cap', cap, 1, 12, 'beds') + '</div></div>' : '')) +
      grp(2, 'Who stays', okWho, okWho ? esc(forBy(D.f)[1]) + ' · ' + GL[D.g] : 'Not chosen yet',
        '<div class="fs"><span class="lb">Who stays here</span><div class="chips" role="radiogroup">' + FOR.map(function (f) { return '<button type="button" role="radio" aria-checked="' + (D.f === f[0]) + '" class="' + (D.f === f[0] ? 'on' : '') + '" data-for="' + f[0] + '">' + f[1] + '</button>'; }).join('') + '</div></div>' +
        '<div class="fs"><span class="lb">For</span><div class="seg" role="radiogroup">' + ['M', 'F'].map(function (g) { var lock = bg !== 'X' && bg !== g; return '<button type="button" role="radio" class="' + (D.g === g ? 'on' : '') + (lock ? ' dis' : '') + '" data-g="' + g + '" aria-checked="' + (D.g === g) + '"' + (lock ? ' aria-disabled="true" data-tip="This building is for ' + GL[bg].toLowerCase() + ' staff only"' : '') + '>' + GL[g] + '</button>'; }).join('') + '</div><span class="hint">' + (bg === 'X' ? 'Each room is either male or female.' : 'This building is for ' + GL[bg].toLowerCase() + ' staff only.') + '</span></div>') +
      grp(3, 'Cleaning & alert', true, esc(D.c) + ' · alert at ' + D.a + '%',
        '<div class="fs"><span class="lb">Cleaning</span><div class="chips" role="radiogroup">' + CLEAN.map(function (c) { return '<button type="button" role="radio" aria-checked="' + (D.c === c) + '" class="' + (D.c === c ? 'on' : '') + '" data-clean="' + c + '">' + c + '</button>'; }).join('') + '</div></div>' +
        '<div class="fs"><span class="lb">Alert HR at this occupancy</span><div class="rng"><input type="range" min="50" max="100" step="5" value="' + D.a + '" data-a aria-label="Occupancy alert"><b>' + D.a + '%</b></div></div>', 'gCare') +
      grp(4, 'What’s in each room', true, D.loading ? 'Loading…' : items.length + ' items · ' + pieces + ' pieces', invBody);
    if (invOpen) drawInv();
    var miss = !D.t ? 'Choose an accommodation type' : !D.f ? 'Choose who stays here' : !D.g ? 'Choose male or female' : D.loading ? 'Loading the room’s items…' : '';
    var n = picked().length, ok = $('drOk'); ok.textContent = pickMode() === 'edit' ? 'Review changes' : (n === 1 ? 'Review room' : 'Review ' + n + ' rooms');
    ok.classList.toggle('dis', !!miss); ok.setAttribute('aria-disabled', !!miss); if (miss) ok.setAttribute('data-tip', miss); else ok.removeAttribute('data-tip'); $('why').textContent = miss;
  }
  $('drb').addEventListener('click', function (e) {
    var t = e.target;
    var ed = t.closest('[data-edit]'); if (ed) { setReview(false, +ed.dataset.edit); return; }
    if (t.closest('[data-go]')) { closeDrawer(); switchTab('types'); setTimeout(function () { $('tN').focus(); }, 350); return; }
    var ty = t.closest('[data-type]'); if (ty) { D.t = ty.dataset.type; if (!D.cap) { D.cap = 1; if (hasNew() && !D.touched && !D.from) D.inv = invDefault(1); } drawDrawer(); return; }
    var fo = t.closest('[data-for]'); if (fo) { D.f = fo.dataset.for; drawDrawer(); return; }
    var g = t.closest('[data-g]'); if (g) { if (!g.classList.contains('dis')) { D.g = g.dataset.g; drawDrawer(); } return; }
    var c = t.closest('[data-clean]'); if (c) { D.c = c.dataset.clean; drawDrawer(); return; }
    var iv = t.closest('[data-inv]');
    if (iv) { if (iv.disabled) return; invOpen = iv.dataset.inv === '1'; if (invOpen) { invQ = ''; invF = Object.keys(D.inv).some(function (k) { return D.inv[k] > 0; }) ? 'room' : 'all'; } drawDrawer();
      var gl = $('drb').lastElementChild; anim(gl.querySelector('.inv,.invs'), [{ opacity: 0, transform: 'translateY(-6px)' }, { opacity: 1, transform: 'none' }], { duration: 300, easing: TIDE });
      if (invOpen) { gl.scrollIntoView({ block: 'nearest', behavior: lite ? 'auto' : 'smooth' }); setTimeout(function () { if ($('invQ')) $('invQ').focus(); }, lite ? 0 : 320); } return; }
    var fb = t.closest('[data-if]'); if (fb) { invF = fb.dataset.if; drawInv(); return; }
    if (t.closest('[data-iclr]')) { invQ = ''; $('invQ').value = ''; drawInv(); $('invQ').focus(); return; }
    var sb = t.closest('.step button'); if (sb && !sb.disabled) { var st = sb.closest('[data-st]'), inp = st.querySelector('input'); setStep(st.dataset.st, +inp.value + +sb.dataset.d, +inp.min, +inp.max); }
  });
  $('drb').addEventListener('change', function (e) { var st = e.target.closest('[data-st]'); if (st) { var i = e.target; setStep(st.dataset.st, +i.value, +i.min, +i.max); } });
  $('drb').addEventListener('input', function (e) {
    if (e.target.matches('[data-a]')) { D.a = +e.target.value; e.target.nextElementSibling.textContent = D.a + '%'; $('gCareS').textContent = D.c + ' · alert at ' + D.a + '%'; }
    if (e.target.id === 'invQ') { invQ = e.target.value; if (invQ.trim() && invF === 'room') invF = 'all'; drawInv(); }
  });
  $('drb').addEventListener('keydown', function (e) { if (e.target.id === 'invQ' && e.key === 'Escape' && invQ) { e.stopPropagation(); invQ = ''; e.target.value = ''; drawInv(); } }, true);
  function rollTo(el, v) { if (el.textContent === String(v)) return; el.textContent = v; anim(el, [{ transform: 'translateY(50%)', opacity: 0 }, { transform: 'none', opacity: 1 }], { duration: 260, easing: TIDE }); }
  /* inventory list: only the list redraws, so the search box keeps focus while you type or press + / − */
  function drawInv() {
    var L = $('invL'); if (!L) return;
    var qq = invQ.trim().toLowerCase(), inRoom = INV.filter(function (i) { return (D.inv[i.id] || 0) > 0; }).length;
    $('invF').innerHTML = [['room', 'In this room', inRoom], ['all', 'All items', INV.length]].map(function (x) { return '<button type="button" class="' + (invF === x[0] && !qq ? 'on' : '') + '" data-if="' + x[0] + '">' + x[1] + ' <b>' + x[2] + '</b></button>'; }).join('') + (qq ? '<span class="ifr">Searching all items</span>' : '');
    var sx = $('drb').querySelector('[data-iclr]'); if (sx) sx.hidden = !qq;
    var list = INV.filter(function (i) { return qq ? (i.n + ' ' + i.code + ' ' + i.catName).toLowerCase().indexOf(qq) > -1 : (invF === 'all' || (D.inv[i.id] || 0) > 0); });
    if (!list.length) { L.innerHTML = qq ? '<div class="iem">No item matches “' + esc(invQ.trim()) + '”.<a href="' + esc(U.inventory) + '">Add it in Inventory</a></div>' : '<div class="iem">Nothing in this room yet.<button type="button" class="glink" data-if="all">Browse all items</button></div>'; return; }
    var cats = list.map(function (i) { return i.catName; }).filter(function (v, i, a) { return a.indexOf(v) === i; });
    function hl(s) { var t = esc(s); if (!qq) return t; var k = t.toLowerCase().indexOf(esc(qq)); return k < 0 ? t : t.slice(0, k) + '<mark>' + t.slice(k, k + esc(qq).length) + '</mark>' + t.slice(k + esc(qq).length); }
    L.innerHTML = cats.map(function (c) { return '<div class="ic">' + esc(c) + '</div>' + list.filter(function (i) { return i.catName === c; }).map(function (i) { var v = D.inv[i.id] || 0; return '<div class="ir' + (v ? '' : ' zero') + '" data-row="' + i.id + '"><span class="in">' + hl(i.n) + '<small>' + hl(i.code) + (i.per ? ' · one per bed' : '') + '</small></span>' + stepH('inv:' + i.id, v, 0, 50, i.n) + '</div>'; }).join(''); }).join('');
  }
  function invSum() { var items = INV.filter(function (i) { return (D.inv[i.id] || 0) > 0; }), pieces = items.reduce(function (a, i) { return a + D.inv[i.id]; }, 0), s = $('drb').lastElementChild.querySelector('.gs'); if (s) s.textContent = items.length + ' items · ' + pieces + ' pieces'; }
  function setStep(id, v, min, max) {
    v = Math.max(min, Math.min(max, isNaN(v) ? min : v));
    if (id === 'cap') { D.cap = v; if (hasNew() && !D.touched && !D.from) INV.forEach(function (i) { if (i.per) D.inv[i.id] = fit(i, v * (i.dq || 1)); }); drawDrawer(); return; }
    D.inv[id.slice(4)] = v; D.touched = true;
    if (invOpen) {
      var row = $('invL').querySelector('[data-row="' + id.slice(4) + '"]');
      if (row) { row.classList.toggle('zero', !v); var st = row.querySelector('.step'), inp = st.querySelector('input'), bs = st.querySelectorAll('button'); inp.value = v; bs[0].disabled = v <= min; bs[1].disabled = v >= max; anim(inp, [{ transform: 'scale(1.18)' }, { transform: 'none' }], { duration: 220, easing: SWELL }); }
      var f = $('invF').querySelector('[data-if="room"] b'); if (f) rollTo(f, INV.filter(function (i) { return (D.inv[i.id] || 0) > 0; }).length); invSum(); return;
    }
    drawDrawer();
  }

  /* ---------- review before saving ---------- */
  function cleanInv(m) { var o = {}; Object.keys(m).forEach(function (k) { if (m[k] > 0) o[k] = m[k]; }); return o; }
  function itemsText() { var it = INV.filter(function (i) { return (D.inv[i.id] || 0) > 0; }); return it.length ? it.map(function (i) { return D.inv[i.id] + ' ' + esc(i.n.toLowerCase()); }).join(', ') : 'None'; }
  function diffs(rs) {
    var set = rs.filter(function (r) { return r.s; }); if (!set.length) return [];
    var F = [['Accommodation type', function (s) { return (typ(s.t) || {}).n || '—'; }], ['Beds', function (s) { return String(s.cap); }], ['Who stays', function (s) { return forBy(s.f)[1] || '—'; }], ['For', function (s) { return GL[s.g] || '—'; }], ['Cleaning', function (s) { return s.c; }], ['Occupancy alert', function (s) { return s.a + '%'; }]];
    function uniq(a) { return a.filter(function (v, i, x) { return x.indexOf(v) === i; }); }
    var out = []; F.forEach(function (x) {
      var olds = uniq(set.map(function (r) { return x[1](r.s); })), news = x[1](D), ch = set.some(function (r) { return x[1](r.s) !== news; });
      if (ch) out.push([x[0], olds.length === 1 ? olds[0] : 'varies', news]);
    });
    if (D.touched) out.push(['Items', 'current items', itemsText()]);
    return out;
  }
  /* what would stop the save: not enough stock for new rooms (rooms already set up are checked by the server), or fewer beds than staff already assigned */
  function problems(rs) {
    var p = [], nNew = rs.filter(function (r) { return !r.s; }).length;
    if (nNew) INV.forEach(function (i) { var q2 = D.inv[i.id] || 0; if (q2 && q2 * nNew > i.avail) p.push('Not enough ' + i.n + ': ' + q2 * nNew + ' needed, ' + i.avail + ' in stock'); });
    rs.forEach(function (r) { if (r.s && r.s.occ > D.cap) p.push('Room ' + r.no + ' has ' + r.s.occ + ' staff assigned, so it needs at least ' + r.s.occ + ' beds'); });
    return p;
  }
  function drawReview() {
    var b = bldg(), rs = picked(), n = rs.length, t = typ(D.t), cap = D.cap, no = rs[0].no, mode = pickMode(), nNew = rs.filter(function (r) { return !r.s; }).length;
    var repl = rs.filter(function (r) { return r.s; }), bedDiff = repl.reduce(function (a, r) { return a + (cap - r.s.cap); }, 0), dl = diffs(rs), chg = {}; dl.forEach(function (d) { chg[d[0]] = d; });
    var items = INV.filter(function (i) { return (D.inv[i.id] || 0) > 0; }), pieces = items.reduce(function (a, i) { return a + D.inv[i.id]; }, 0), itemRooms = nNew + (D.touched ? repl.length : 0);
    var none = mode === 'edit' && !dl.length, probs = problems(rs), byF = {}; rs.forEach(function (r) { (byF[r.f] = byF[r.f] || []).push(r); });
    var flist = Object.keys(byF).map(Number).sort(function (x, y) { return y - x; });
    function row(k, v, sec) { var c = chg[k]; return '<div class="rvr' + (c ? ' chg' : '') + '"><span class="rk">' + k + '</span><span class="rv">' + (c ? '<s>' + esc(c[1]) + '</s> → ' : '') + v + '</span><button type="button" class="glink" data-edit="' + sec + '">Edit</button></div>'; }
    $('drT').textContent = 'Check before saving'; $('drS').textContent = b.name + ' · ' + (n === 1 ? 'room ' + no : n + ' rooms');
    var note = mode === 'edit' ? (none ? '<div class="same">Nothing has changed yet. Go back and change a setting, or close this panel.</div>' : '<div class="chgb"><b>You’re changing ' + dl.length + ' ' + (dl.length === 1 ? 'setting' : 'settings') + '</b> on ' + (n === 1 ? 'room ' + no : n + ' rooms') + (bedDiff ? ' · beds ' + (bedDiff > 0 ? '+' : '') + bedDiff + ' overall' : '') + '.<span class="rl">' + dl.map(function (d) { return d[0]; }).join(' · ') + '</span></div>')
      : (repl.length && dl.length ? '<div class="warn"><b>' + repl.length + ' of these ' + (repl.length === 1 ? 'room is' : 'rooms are') + ' already set up</b> and will get the new settings' + (bedDiff ? ' · beds ' + (bedDiff > 0 ? '+' : '') + bedDiff + ' overall' : '') + '.<span class="rl">' + repl.slice(0, 8).map(function (r) { return esc(r.no); }).join(', ') + (repl.length > 8 ? '…' : '') + '</span></div>' : '');
    var fail = failed.length ? '<div class="warn"><b>' + failed.length + ' of ' + n + ' ' + (n === 1 ? 'room' : 'rooms') + ' couldn’t be saved</b>. Rooms are saved in groups, and a group is saved only when all of its rooms can be.<span class="rl">' + failed.map(function (f) { return esc(f.no) + ' — ' + esc(f.msg); }).slice(0, 6).join('<br>') + '</span></div>' : '';
    var prob = probs.length ? '<div class="warn"><b>Fix this before saving</b><span class="rl">' + probs.slice(0, 5).map(esc).join('<br>') + '</span></div>' : '';
    $('drb').innerHTML = fail + prob + note +
      '<div class="rvs"><div><b>' + n + '</b><span>Rooms</span></div><div><b>' + n * cap + '</b><span>Beds</span></div><div><b>' + itemRooms * pieces + '</b><span>Items</span></div><div><b>' + flist.length + '</b><span>' + (flist.length === 1 ? 'Floor' : 'Floors') + '</span></div></div>' +
      '<section class="grp"><div class="gh"><span class="gt">Applies to</span><span class="gs">' + n + ' ' + (n === 1 ? 'room' : 'rooms') + '</span></div><div class="rvf">' + flist.map(function (f) { return '<div><span class="fl2">' + fname(f) + '</span><div class="nos">' + byF[f].map(function (r) { var mk = mode === 'mix' && r.s && dl.length; return '<span class="' + (mk ? 'rp' : '') + '"' + (mk ? ' data-tip="Already set up · gets the new settings"' : '') + '>' + esc(r.no) + '</span>'; }).join('') + '</div></div>'; }).join('') + '</div></section>' +
      '<section class="grp"><div class="gh"><span class="gt">Setup for each room</span></div><div class="rvl">' +
        row('Accommodation type', esc(t ? t.n : ''), 0) + row('Beds', String(cap), 0) + row('Who stays', esc(forBy(D.f)[1]), 1) + row('For', GL[D.g], 1) + row('Cleaning', esc(D.c), 2) + row('Occupancy alert', D.a + '% full', 2) +
        (nNew || D.touched ? row('Items', itemsText(), 3) : '') + '</div></section>';
    $('drC').textContent = 'Back'; $('why').textContent = probs.length ? probs[0] : '';
    var ok = $('drOk'), block = none || !!probs.length || busy;
    ok.textContent = busy ? 'Saving ' + n + (n === 1 ? ' room…' : ' rooms…') : failed.length ? 'Try again' : mode === 'edit' ? 'Save changes' : (n === 1 ? 'Confirm & save' : 'Confirm & save ' + n + ' rooms');
    ok.classList.toggle('dis', block); ok.setAttribute('aria-disabled', block);
    if (none) ok.setAttribute('data-tip', 'Nothing has changed'); else if (probs.length) ok.setAttribute('data-tip', probs[0]); else ok.removeAttribute('data-tip');
  }
  function setReview(on, sec) {
    if (busy) return; review = on;
    if (!on) $('drT').textContent = drTitle();
    drawDrawer(); $('drb').scrollTop = 0;
    if (!on && sec != null) { var g = $('drb').querySelectorAll('.grp')[sec]; if (g) { g.scrollIntoView({ block: 'start', behavior: lite ? 'auto' : 'smooth' }); anim(g, [{ boxShadow: '0 0 0 3px rgba(224,255,2,.7)' }, { boxShadow: '0 0 0 0 rgba(224,255,2,0)' }], { duration: 900, easing: 'ease-out' }); } }
    else anim($('drb'), [{ opacity: 0, transform: on ? 'translateX(18px)' : 'translateX(-18px)' }, { opacity: 1, transform: 'none' }], { duration: 320, easing: TIDE });
  }
  $('drX').onclick = $('scrim').onclick = function () { if (!busy) closeDrawer(); };
  $('drC').onclick = function () { if (busy) return; if (review) setReview(false); else closeDrawer(); };
  $('drOk').onclick = function () {
    var ok = $('drOk');
    if (ok.classList.contains('dis')) { anim($('why'), [{ transform: 'translateX(-4px)' }, { transform: 'translateX(4px)' }, { transform: 'none' }], { duration: 240 }); return; }
    if (!review) { setReview(true); return; }
    saveSetup();
  };

  /* ---------- saving: one all-or-nothing call per group of up to 200 rooms (SetupRoomsBatch creates or updates, with items) ---------- */
  function setupOf(d, status, items) {
    var s = { Accommodation_type_id: d.t, Capacity: d.cap, RoomType: forBy(d.f)[3], blockFor: d.g === 'F' ? 'Female' : 'Male', CleaningSchedule: cleanDb(d.c), Occupancytheresold: d.a };
    if (status) s.RoomStatus = status; if (items) s.items = items; return s;
  }
  /* run groups of rooms one after another; resolves to { done: groups saved, fail: rooms not saved with the reason } */
  function runBatches(b, groups) {
    var done = [], fail = [];
    return groups.reduce(function (pr, g) {
      return pr.then(function () {
        return http('POST', U.setupBatch, { rooms: g.rooms.map(function (r) { return { building_id: b.id, floor: r.f, room: r.no }; }), setup: g.setup }).then(function (r) {
          if (r.ok) { done.push(g); return; }
          var fm = {}, list = (r.json && r.json.failed) || []; list.forEach(function (x) { fm[x.index] = x.message; });
          var generic = list.length ? 'Not saved, because another room in its group couldn’t be' : msgOf(r), rows = g.rooms.map(function (room, i) { return { no: room.no, msg: fm[i] || generic, room: room, own: !!fm[i] }; });
          rows.sort(function (x, y) { return (y.own ? 1 : 0) - (x.own ? 1 : 0); }); fail = fail.concat(rows);
        });
      });
    }, Promise.resolve()).then(function () { return { done: done, fail: fail }; });
  }
  function chunk(list, n) { var o = []; for (var i = 0; i < list.length; i += n) o.push(list.slice(i, i + n)); return o; }
  /* items of every room in the list, keyed by setup id */
  function snapItems(rooms) { var m = {}; return batches(rooms, 10, function (r) { return loadItems(r.s.aid).then(function (it) { m[r.s.aid] = it; }); }).then(function () { return m; }); }
  /* keep the page's stock numbers in step with what the server just reserved or released */
  function stock(map, n, sign) { Object.keys(map).forEach(function (id) { var i = INV.filter(function (x) { return x.id === id; })[0]; if (i) i.avail = Math.max(0, i.avail - sign * map[id] * n); }); }
  function saveSetup() {
    var b = bldg(), rs = failed.length ? failed.map(function (f) { return f.room; }) : picked(), wasEdit = pickMode() === 'edit';
    var fresh = rs.filter(function (r) { return !r.s; }), old = rs.filter(function (r) { return r.s; }), items = cleanInv(D.inv), itemsT = D.touched, d = D;
    var prior = old.map(function (r) { return { f: r.f, no: r.no, s: JSON.parse(JSON.stringify(r.s)) }; });
    busy = true; failed = []; drawReview();
    var groups = [];
    chunk(fresh, 200).forEach(function (ch) { groups.push({ rooms: ch, setup: setupOf(d, null, items), fresh: true }); });
    var byStatus = {}; old.forEach(function (r) { (byStatus[r.s.status] = byStatus[r.s.status] || []).push(r); });
    Object.keys(byStatus).forEach(function (st) { chunk(byStatus[st], 200).forEach(function (ch) { groups.push({ rooms: ch, setup: setupOf(d, st, itemsT ? items : undefined), fresh: false }); }); });
    return (itemsT && old.length ? snapItems(old) : Promise.resolve({})).then(function (oldItems) {
      return runBatches(b, groups).then(function (res) {
        res.done.forEach(function (g) { if (g.fresh) stock(items, g.rooms.length, 1); else if (itemsT) g.rooms.forEach(function (r) { stock(oldItems[r.s.aid] || {}, 1, -1); stock(items, 1, 1); }); });
        busy = false;
        return reload({ s: true }).then(function () {
          selClear(); res.fail.forEach(function (f) { sel[key(f.room.f, f.room.no)] = 1; }); drawMain();
          var saved = rs.length - res.fail.length;
          if (res.fail.length) {
            failed = res.fail.map(function (f) { var rr = picked().filter(function (x) { return x.f === f.room.f && x.no === f.room.no; })[0] || f.room; return { no: f.no, msg: f.msg, room: rr }; });
            review = true; drawBar(); drawReview();
            if (saved) toast(saved + (saved === 1 ? ' room' : ' rooms') + ' saved, ' + res.fail.length + ' not saved', null, 'warn');
            return;
          }
          closeDrawer(); drawBar();
          rs.forEach(function (r, j) { var el = $('main').querySelector('.rm[data-k="' + key(r.f, r.no) + '"]'); anim(el, [{ transform: 'scale(.88)', background: '#f7ffd1' }, { transform: 'none', background: '#fff' }], { duration: 540, delay: 120 + Math.min(j, 40) * 22, easing: SWELL }); });
          var n = rs.length;
          toast(wasEdit ? (n === 1 ? 'Room updated' : n + ' rooms updated') : (n === 1 ? 'Room set up' : n + ' rooms set up'), function () { undoSetup(b, fresh, items, prior, itemsT ? oldItems : null); });
        });
      });
    });
  }
  /* Undo: rooms that were new lose their setup again (the server gives their items back); edited rooms get their old settings (and items) back */
  function undoSetup(b, fresh, items, prior, oldItems) {
    var nb = B.filter(function (x) { return x.id === b.id; })[0], ids = [];
    fresh.forEach(function (r) { var fl = nb.floors.filter(function (x) { return x.f === r.f; })[0], rr = fl && fl.rooms.filter(function (x) { return x.no === r.no; })[0]; if (rr && rr.s) ids.push(rr.s.aid); });
    var groups = {}; prior.forEach(function (u) {
      var its = oldItems ? oldItems[u.s.aid] || {} : undefined, setup = setupOf({ t: u.s.t, cap: u.s.cap, f: u.s.f, g: u.s.g, c: u.s.c, a: u.s.a }, u.s.status, its), k = JSON.stringify(setup);
      (groups[k] = groups[k] || { rooms: [], setup: setup, its: its }).rooms.push(u);
    });
    batches(ids, 10, function (id) { return http('DELETE', tpl(U.setupDestroy, id)); }).then(function (rs) {
      if (rs.every(function (r) { return r.ok; })) stock(items, ids.length, -1);
      return runBatches(b, Object.keys(groups).map(function (k) { return groups[k]; })).then(function (res) {
        res.done.forEach(function (g) { if (oldItems) g.rooms.forEach(function (u) { stock(items, 1, -1); stock(oldItems[u.s.aid] || {}, 1, 1); }); });
        return rs.concat(res.fail.map(function () { return { ok: false }; }));
      });
    }).then(function (all) { var bad = all.filter(function (r) { return !r.ok; }); return reload({ s: true }).then(function () { toast(bad.length ? 'Some rooms couldn’t be restored' : 'Changes undone', null, bad.length ? 'warn' : 'ok'); }); });
  }
  /* "Same as the floor below": each room gets the setup of the room in the same position below, items included; rooms with the same setup go in one call */
  function copyFloor(f) {
    var b = bldg(), fl = b.floors.filter(function (x) { return x.f === f; })[0], below = b.floors.filter(function (x) { return x.f === f - 1; })[0], todo = [];
    fl.rooms.forEach(function (r, i) { var src = below.rooms[i] && below.rooms[i].s; if (!r.s && src) todo.push({ r: r, src: src }); });
    if (!todo.length) return;
    batches(todo, 10, function (x) { return loadItems(x.src.aid).then(function (m) { x.items = m; }); }).then(function () {
      var groups = {}; todo.forEach(function (x) {
        var setup = setupOf({ t: x.src.t, cap: x.src.cap, f: x.src.f, g: x.src.g, c: x.src.c, a: x.src.a }, null, x.items), k = JSON.stringify(setup);
        (groups[k] = groups[k] || { rooms: [], setup: setup, items: x.items }).rooms.push({ f: f, no: x.r.no });
      });
      return runBatches(b, Object.keys(groups).map(function (k) { return groups[k]; }));
    }).then(function (res) {
      var made = []; res.done.forEach(function (g) { stock(g.items, g.rooms.length, 1); g.rooms.forEach(function (r) { made.push({ f: r.f, no: r.no, items: g.items }); }); });
      reload({ s: true }).then(function () {
        var n = made.length; if (!n) return toastErr(res.fail[0] ? res.fail[0].msg : 'Nothing could be copied');
        if (res.fail.length) toast(res.fail.length + ' rooms couldn’t be copied: ' + res.fail[0].msg, null, 'warn');
        toast(n + ' ' + (n === 1 ? 'room' : 'rooms') + ' on ' + fname(f) + ' set up like ' + fname(f - 1), function () {
          var nb = B.filter(function (x) { return x.id === b.id; })[0], ids = [];
          made.forEach(function (m) { var fl2 = nb.floors.filter(function (x) { return x.f === m.f; })[0], rr = fl2 && fl2.rooms.filter(function (x) { return x.no === m.no; })[0]; if (rr && rr.s) { ids.push(rr.s.aid); stock(m.items, 1, -1); } });
          batches(ids, 10, function (id) { return http('DELETE', tpl(U.setupDestroy, id)); }).then(function () { reload({ s: true }); toast('Changes undone'); });
        });
        Array.prototype.forEach.call($('main').querySelectorAll('.flr[data-f="' + f + '"] .rm:not(.unset)'), function (r, i) { anim(r, [{ transform: 'scale(.9)', background: '#f7ffd1' }, { transform: 'none', background: '#fff' }], { duration: 520, delay: i * 25, easing: SWELL }); });
      });
    });
  }

  /* ---------- add building / floors modal ---------- */
  var M = 'b', NS = 'F', PF = [], mBusy = false;
  function mv(id) { return +$(id).querySelector('input').value; }
  var GD = 'X';
  function pat() { return M === 'b' ? $('mPat').value.trim() : bldg().pat; }
  function openModal(kind) {
    M = kind; var b = bldg(); mBusy = false; $('mR').hidden = true;
    ['mNameF', 'mGF', 'mNS', 'mDiv', 'mGdF', 'mAlF'].forEach(function (id) { $(id).hidden = M !== 'b'; });
    $('mE').textContent = ''; $('mName').classList.remove('bad'); $('mPE').textContent = '';
    if (M === 'b') {
      $('mT').textContent = 'Add a building'; $('mS').textContent = 'Name it, say who it’s for and how rooms are numbered, then how many rooms each floor has.'; $('mName').value = '';
      setNS('F'); setGd('X'); $('mPat').value = 'F{F}-{N}'; $('mAl').checked = false; $('mAlR').value = DEF_ALERT; syncAl();
    } else {
      NS = b.ns || 'F'; $('mPatF').hidden = true; var top = Math.max.apply(null, b.floors.map(function (x) { return x.f; }).concat([-1]));
      $('mT').textContent = 'Add floors to ' + b.name; $('mS').textContent = 'New floors go on top of ' + (top < 0 ? 'the building' : fname(top).toLowerCase()) + '. Rooms continue the building’s numbering (' + (NS === 'F' ? 'by floor' : NS === 'C' ? 'continuous' : 'like ' + fmt(b.pat, 1, 1, 1)) + ').';
    }
    var nF = M === 'b' ? 3 : 1, last = M === 'b' ? 8 : (b.floors.length ? b.floors.slice().sort(function (x, y) { return y.f - x.f; })[0].rooms.length || 8 : 8);
    $('mF').querySelector('input').value = nF; PF = Array.apply(null, { length: nF }).map(function () { return last; });
    drawPrev(); $('md').classList.add('on'); $('md').setAttribute('aria-hidden', 'false'); $('scrim').classList.add('on'); setTimeout(function () { (M === 'b' ? $('mName') : $('mOk')).focus(); }, 200);
  }
  function closeModal() { if (mBusy) return; $('md').classList.remove('on'); $('md').setAttribute('aria-hidden', 'true'); if (!$('dr').classList.contains('on')) $('scrim').classList.remove('on'); }
  function plan() {
    var nF = mv('mF'), b = bldg(); while (PF.length < nF) PF.push(PF.length ? PF[PF.length - 1] : 8); PF.length = nF;
    var start = M === 'b' ? ($('mG').checked ? 0 : 1) : Math.max.apply(null, b.floors.map(function (f) { return f.f; }).concat([-1])) + 1, pt = pat();
    var c = M === 'b' ? 0 : NS === 'C' ? maxNo(b) : NS === 'U' ? patCounters(pt, b).c : 0;
    return PF.map(function (n, i) { var f = start + i; return { f: f, n: n, nos: Array.apply(null, { length: n }).map(function (_, j) { return rno(f, j + 1, NS, ++c, pt); }) }; });
  }
  /* what would make the server refuse the plan: a bad pattern, a number that is too long, or the same number twice */
  function planErr(p) {
    if (NS === 'U') { var pe = patCheck(pat()); if (pe) return pe; }
    var seen = {}, b = M === 'f' ? bldg() : null; if (b) allRooms(b).forEach(function (r) { seen[r.no] = 1; });
    for (var i = 0; i < p.length; i++) for (var j = 0; j < p[i].nos.length; j++) {
      var no = p[i].nos[j];
      if (no.length > 15) return 'Room ' + no + ' is too long. Room numbers can be up to 15 characters.';
      if (seen[no]) return 'Room ' + no + ' would appear twice in this building. Change the pattern so every room has its own number.';
      seen[no] = 1;
    }
    return '';
  }
  function drawPrev() {
    var p = plan(), tot = p.reduce(function (a, x) { return a + x.n; }, 0), SHOW = 10;
    $('mP').innerHTML = '<div class="fth"><span>Floor</span><span>Rooms</span><span>Room numbers</span></div>' + p.map(function (x, i) {
      return '<div class="ftr"><b>' + fname(x.f) + '</b><div class="ftc"><div class="step" data-pf="' + i + '"><button type="button" data-d="-1" aria-label="Fewer rooms on ' + fname(x.f) + '"' + (x.n <= 1 ? ' disabled' : '') + '>−</button><input type="number" value="' + x.n + '" min="1" max="60" aria-label="Rooms on ' + fname(x.f) + '"><button type="button" data-d="1" aria-label="More rooms on ' + fname(x.f) + '"' + (x.n >= 60 ? ' disabled' : '') + '>+</button></div>' +
        (i === 0 && p.length > 1 ? '<button type="button" class="glink" data-cpa' + (p.every(function (y) { return y.n === x.n; }) ? ' disabled' : '') + '>Copy to all</button>' : '') + '</div>' +
        '<div class="nos">' + x.nos.slice(0, SHOW).map(function (n) { return '<span>' + n + '</span>'; }).join('') + (x.n > SHOW ? '<em>… ' + x.nos[x.n - 1] + '</em>' : '') + '</div></div>';
    }).join('');
    var s = $('mF'), i2 = s.querySelector('input'), bs = s.querySelectorAll('button'); bs[0].disabled = +i2.value <= +i2.min; bs[1].disabled = +i2.value >= +i2.max;
    $('mSum').textContent = p.length + ' ' + (p.length === 1 ? 'floor' : 'floors') + ' · ' + tot + ' rooms';
    var err = planErr(p); $('mPE').textContent = err; $('mP').classList.toggle('bad', !!err);
    $('mOk').textContent = M === 'b' ? 'Create building' : 'Add ' + (p.length === 1 ? 'floor' : 'floors'); var ok = $('mOk'); ok.classList.toggle('dis', !!err); ok.setAttribute('aria-disabled', !!err); if (err) ok.setAttribute('data-tip', err); else ok.removeAttribute('data-tip');
  }
  (function () {
    var s = $('mF'), i = s.querySelector('input');
    s.addEventListener('click', function (e) { var b = e.target.closest('button'); if (!b || b.disabled) return; i.value = Math.max(+i.min, Math.min(+i.max, +i.value + +b.dataset.d)); drawPrev();
      if (+b.dataset.d > 0) { var rows = $('mP').querySelectorAll('.ftr'); anim(rows[rows.length - 1], [{ opacity: 0, transform: 'translateY(-6px)' }, { opacity: 1, transform: 'none' }], { duration: 320, easing: TIDE }); } });
    i.addEventListener('input', function () { if (+i.value > +i.max) i.value = i.max; if (+i.value >= 1) drawPrev(); });
    i.addEventListener('blur', function () { if (!(+i.value >= +i.min)) i.value = i.min; drawPrev(); });
  })();
  $('mP').addEventListener('click', function (e) {
    if (e.target.closest('[data-cpa]')) { PF = PF.map(function () { return PF[0]; }); drawPrev(); Array.prototype.forEach.call($('mP').querySelectorAll('.nos'), function (r, i) { if (i) anim(r, [{ opacity: .3 }, { opacity: 1 }], { duration: 360, delay: i * 40, easing: TIDE }); }); return; }
    var b = e.target.closest('[data-pf] button'); if (!b || b.disabled) return; var k = +b.closest('[data-pf]').dataset.pf; PF[k] = Math.max(1, Math.min(60, PF[k] + +b.dataset.d)); drawPrev();
  });
  $('mP').addEventListener('change', function (e) { var s = e.target.closest('[data-pf]'); if (!s) return; PF[+s.dataset.pf] = Math.max(1, Math.min(60, +e.target.value || 1)); drawPrev(); });
  function setNS(v) { NS = v; $('mPatF').hidden = M !== 'b' || v !== 'U'; Array.prototype.forEach.call($('mNSs').querySelectorAll('button'), function (b) { b.classList.toggle('on', b.dataset.ns === v); b.setAttribute('aria-checked', b.dataset.ns === v); }); }
  $('mNSs').addEventListener('click', function (e) { var b = e.target.closest('[data-ns]'); if (!b || b.dataset.ns === NS) return; setNS(b.dataset.ns); drawPrev(); Array.prototype.forEach.call($('mP').querySelectorAll('.nos span'), function (s, i) { anim(s, [{ opacity: 0, transform: 'translateY(4px)' }, { opacity: 1, transform: 'none' }], { duration: 280, delay: Math.min(i, 40) * 8, easing: TIDE }); }); });
  $('mG').addEventListener('change', drawPrev);
  $('mPat').addEventListener('input', drawPrev);
  function setGd(v) { GD = v; Array.prototype.forEach.call($('mGd').querySelectorAll('button'), function (b) { b.classList.toggle('on', b.dataset.gd === v); b.setAttribute('aria-checked', b.dataset.gd === v); }); }
  $('mGd').addEventListener('click', function (e) { var b = e.target.closest('[data-gd]'); if (b) setGd(b.dataset.gd); });
  function syncAl() { var on = $('mAl').checked; $('mAlR').disabled = !on; $('mAlV').textContent = on ? $('mAlR').value + '%' : 'Off'; }
  $('mAl').addEventListener('change', syncAl); $('mAlR').addEventListener('input', syncAl);
  $('mName').addEventListener('input', function () { var v = $('mName').value.trim(), dup = BLD.some(function (b) { return b.n.toLowerCase() === v.toLowerCase(); }); $('mE').textContent = dup ? 'There’s already a building called ' + v : ''; $('mName').classList.toggle('bad', dup); });
  $('mName').addEventListener('keydown', function (e) { if (e.key === 'Enter') $('mOk').click(); });
  $('mC').onclick = closeModal;
  function mBusySet(on, txt) { mBusy = on; var ok = $('mOk'); ok.classList.toggle('dis', on); ok.setAttribute('aria-disabled', on); if (txt) ok.textContent = txt; }
  $('mOk').onclick = function () {
    if (mBusy) return;
    if ($('mOk').classList.contains('dis')) { anim($('mPE'), [{ transform: 'translateX(-4px)' }, { transform: 'translateX(4px)' }, { transform: 'none' }], { duration: 240 }); return; }
    var p = plan(), tot = p.reduce(function (a, x) { return a + x.n; }, 0), all = []; p.forEach(function (x) { x.nos.forEach(function (n) { all.push({ f: x.f, no: n }); }); });
    $('mR').hidden = true;
    if (M === 'b') {
      var v = $('mName').value.trim();
      if (!v) { $('mE').textContent = 'Give the building a name'; $('mName').classList.add('bad'); $('mName').focus(); return; }
      if (BLD.some(function (b) { return b.n.toLowerCase() === v.toLowerCase(); })) return;
      mBusySet(true, 'Creating ' + tot + ' rooms…');
      var body = { BuildingName: v, gender: GD, room_numbering: NSOUT[NS], floors: p.map(function (x) { return { floor: x.f, rooms: x.nos }; }) };
      if (NS === 'U') body.room_number_pattern = pat(); if ($('mAl').checked) body.occupancy_alert = +$('mAlR').value;
      http('POST', U.buildingWithFloors, body).then(function (r) {
        // one call, all or nothing: if it fails, nothing was created
        if (!r.ok) { mBusySet(false, 'Create building'); var el = $('mR'); el.hidden = false; el.innerHTML = '<b>The building wasn’t created</b>, and nothing was saved. ' + esc(msgOf(r)); return; }
        finishModal(r.json.id, { name: v, floors: p.length, rooms: tot });
      });
    } else {
      var b = bldg(); mBusySet(true, 'Creating ' + tot + ' rooms…');
      // one call, all or nothing: if it fails, no floor or room was added
      http('POST', tpl(U.buildingAddFloors, b.id), { floors: p.map(function (x) { return { floor: x.f, rooms: x.nos.map(String) }; }) }).then(function (r) {
        if (!r.ok) { mBusySet(false, 'Add ' + (p.length === 1 ? 'floor' : 'floors')); var el = $('mR'); el.hidden = false; el.innerHTML = '<b>No floors were added</b>, and nothing was saved. ' + esc(msgOf(r)); return; }
        finishModal(b.id, null, all, p.length);
      });
    }
  };
  function finishModal(bid, nb, rooms, nFloors) {
    mBusySet(false); $('md').classList.remove('on'); $('md').setAttribute('aria-hidden', 'true'); $('scrim').classList.remove('on');
    cur = bid; selClear();
    reload({ b: true }).then(function () {
      drawAll(!!nb);
      if (nb) toast(nb.name + ' added · ' + nb.floors + ' floors, ' + nb.rooms + ' rooms', function () { http('DELETE', tpl(U.buildingDestroy, b64(bid))).then(function (r) { if (r.ok) { cur = null; reload({ b: true }); toast(nb.name + ' removed'); } else toastErr(msgOf(r)); }); });
      else toast(nFloors + ' ' + (nFloors === 1 ? 'floor' : 'floors') + ' added to ' + bldg().name, function () { delRooms(bid, rooms).then(function (res) { if (res.ok) { reload(); toast('Floors removed'); } }); });
    });
  }
  $('addB').onclick = function () { openModal('b'); };
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    if ($('md').classList.contains('on')) closeModal(); else if ($('dr').classList.contains('on')) { if (!busy) closeDrawer(); } else if (selKeys().length) { selClear(); drawMain(); drawBar(); }
  });
  $('scrim').addEventListener('click', function () { if ($('md').classList.contains('on')) closeModal(); });
  $('md').addEventListener('click', function (e) { if (e.target === $('md')) closeModal(); });

  /* ---------- other tabs ---------- */
  function errIn(id, msg) { $(id).textContent = msg || ''; }
  function drawTypes() {
    $('tc').textContent = TYPES.length;
    $('tl').innerHTML = TYPES.length ? TYPES.map(function (t) { return '<div class="li"><span class="n">' + esc(t.n) + '</span><button class="ib" type="button" data-dt="' + t.id + '" aria-label="Remove ' + esc(t.n) + '" data-tip="Remove">' + ICON.bin + '</button></div>'; }).join('') : '<div class="side-empty">No types yet. Add your first one below, like “Four share”.</div>';
  }
  $('tl').addEventListener('click', function (e) {
    var b = e.target.closest('[data-dt]'); if (!b) return; var t = typ(b.dataset.dt);
    var used = B.some(function (bb) { return allRooms(bb).some(function (r) { return r.s && r.s.t === t.id; }); });
    if (used) { toast(t.n + ' is used by rooms, so it can’t be removed', null, 'warn'); return; }
    http('DELETE', tpl(U.typeDestroy, b64(t.id))).then(function (r) { if (!r.ok) return toastErr(msgOf(r)); loadTypes().then(function () { drawTypes();
      toast(t.n + ' removed', function () { http('POST', U.typeStore, { AccommodationName: t.n, Color: '#014653' }).then(function (u) { if (u.ok) loadTypes().then(drawTypes); else toastErr(msgOf(u)); }); }); }); });
  });
  $('tN').addEventListener('keydown', function (e) { if (e.key === 'Enter') $('tAdd').click(); });
  $('tN').addEventListener('input', function () { var v = $('tN').value.trim(); errIn('tE', v && TYPES.some(function (t) { return t.n.toLowerCase() === v.toLowerCase(); }) ? v + ' already exists' : ''); });
  $('tAdd').onclick = function () {
    var v = $('tN').value.trim(); if (!v) { errIn('tE', 'Give the type a name'); return; } if (TYPES.some(function (t) { return t.n.toLowerCase() === v.toLowerCase(); })) { errIn('tE', v + ' already exists'); return; }
    http('POST', U.typeStore, { AccommodationName: v, Color: '#014653' }).then(function (r) { if (!r.ok) return errIn('tE', msgOf(r)); errIn('tE', ''); $('tN').value = ''; loadTypes().then(function () { drawTypes(); toast(v + ' added'); }); });
  };
  function drawCats() { $('cc').textContent = CATS.length; $('cl').innerHTML = CATS.length ? CATS.map(function (c) { return '<span>' + esc(c.n) + '<button type="button" data-dc="' + c.id + '" aria-label="Remove ' + esc(c.n) + '">' + ICON.x + '</button></span>'; }).join('') : '<div class="side-empty">No categories yet.</div>'; }
  $('cl').addEventListener('click', function (e) {
    var b = e.target.closest('[data-dc]'); if (!b) return; var c = CATS.filter(function (x) { return x.id === b.dataset.dc; })[0];
    if (INV.some(function (i) { return i.cat === c.id; })) { toast(c.n + ' has items in it, so it can’t be removed', null, 'warn'); return; }
    http('DELETE', tpl(U.catDestroy, b64(c.id))).then(function (r) { if (!r.ok) return toastErr(msgOf(r)); loadCats().then(function () { drawCats();
      toast(c.n + ' removed', function () { http('POST', U.catStore, { CategoryName: c.n }).then(function (u) { if (u.ok) loadCats().then(drawCats); else toastErr(msgOf(u)); }); }); }); });
  });
  $('cN').addEventListener('keydown', function (e) { if (e.key === 'Enter') $('cAdd').click(); });
  $('cN').addEventListener('input', function () { var v = $('cN').value.trim(); errIn('cE', v && CATS.some(function (c) { return c.n.toLowerCase() === v.toLowerCase(); }) ? v + ' already exists' : ''); });
  $('cAdd').onclick = function () {
    var v = $('cN').value.trim(); if (!v) { errIn('cE', 'Give the category a name'); return; } if (CATS.some(function (c) { return c.n.toLowerCase() === v.toLowerCase(); })) { errIn('cE', v + ' already exists'); return; }
    http('POST', U.catStore, { CategoryName: v }).then(function (r) { if (!r.ok) return errIn('cE', msgOf(r)); errIn('cE', ''); $('cN').value = ''; loadCats().then(function () { drawCats(); toast(v + ' added'); }); });
  };
  $('aR').value = DEF_ALERT; $('aV').textContent = DEF_ALERT + '%';
  $('aR').addEventListener('input', function (e) { $('aV').textContent = e.target.value + '%'; });
  $('aSave').onclick = function () {
    var v = +$('aR').value;
    http('POST', U.defaultAlert, { default_occupancy_alert: v }).then(function (r) { if (!r.ok) return toastErr(msgOf(r)); DEF_ALERT = v; toast('Occupancy alert saved · new rooms start at ' + v + '%'); });
  };
  (function () { var i = $('eS').querySelector('input'); if (CFG.escalation != null && +CFG.escalation > 0) i.value = Math.max(1, Math.min(30, +CFG.escalation)); })();
  $('eS').addEventListener('click', function (e) { var b = e.target.closest('button'); if (!b) return; var i = $('eS').querySelector('input'); i.value = Math.max(1, Math.min(30, +i.value + +b.dataset.d)); });
  $('eSave').onclick = function () {
    var d = +$('eS').querySelector('input').value, prev = CFG.escalation;
    http('POST', U.escalation, { EscalationDay: String(d) }).then(function (r) {
      if (!r.ok) return errIn('eE', msgOf(r)); errIn('eE', ''); CFG.escalation = d;
      toast('Escalation day saved · ' + d + (d === 1 ? ' day' : ' days'), prev != null ? function () { http('POST', U.escalation, { EscalationDay: String(prev) }).then(function (u) { if (u.ok) { CFG.escalation = prev; $('eS').querySelector('input').value = prev; toast('Escalation day back to ' + prev); } else toastErr(msgOf(u)); }); } : null);
    });
  };
  /* bulk upload (existing template + upload routes) */
  Array.prototype.forEach.call(root.querySelectorAll('[data-up]'), function (box) {
    var kind = box.dataset.up, file = box.querySelector('input[type=file]'), url = kind === 'rooms' ? U.uploadRooms : U.uploadQuick, field = kind === 'rooms' ? 'AvailableAccommodationFile' : 'QuickAssignmentFile', res = box.parentNode.querySelector('[data-ures]'), ut = box.querySelector('[data-ut]');
    function send(f) {
      if (!f) return; ut.firstChild.textContent = f.name; res.innerHTML = ''; var btn = box.querySelector('[data-pick]'); btn.disabled = true; btn.textContent = 'Uploading…';
      var fd = new FormData(); fd.append(field, f);
      fetch(url, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF } })
        .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { return { ok: r.ok, json: j }; }); })
        .then(function (r) {
          btn.disabled = false; btn.textContent = 'Upload file'; file.value = '';
          var j = r.json || {}, errs = j.errors ? (Array.isArray(j.errors) ? j.errors : Object.keys(j.errors).map(function (k) { return j.errors[k]; })) : [];
          var lines = errs.map(function (x) { return typeof x === 'string' ? x : JSON.stringify(x); });
          res.innerHTML = (r.ok ? '<div class="uok">' + esc(j.message || 'Uploaded') + '</div>' : '<div class="uerr"><b>' + esc(j.message || 'The upload didn’t go through') + '</b></div>') + (lines.length ? '<div class="uerr">' + lines.slice(0, 8).map(esc).join('<br>') + (lines.length > 8 ? '<br>…and ' + (lines.length - 8) + ' more' : '') + '</div>' : '');
          if (r.ok) { toast(j.message || 'Upload finished'); if (kind === 'rooms') reload({ b: true, s: true }); }
        });
    }
    box.querySelector('[data-pick]').addEventListener('click', function () { file.click(); });
    file.addEventListener('change', function () { send(file.files[0]); });
    ['dragenter', 'dragover'].forEach(function (ev) { box.addEventListener(ev, function (e) { e.preventDefault(); box.classList.add('drag'); }); });
    ['dragleave', 'drop'].forEach(function (ev) { box.addEventListener(ev, function (e) { e.preventDefault(); box.classList.remove('drag'); }); });
    box.addEventListener('drop', function (e) { if (e.dataTransfer && e.dataTransfer.files[0]) send(e.dataTransfer.files[0]); });
  });

  /* ---------- tabs (remembered in the URL hash) ---------- */
  var tabs = $('tabs'), ind = $('ind');
  function moveInd(b, inst) { ind.style.transition = inst ? 'none' : ''; ind.style.width = b.offsetWidth + 'px'; ind.style.height = b.offsetHeight + 'px'; ind.style.top = b.offsetTop + 'px'; ind.style.transform = 'translateX(' + b.offsetLeft + 'px)'; }
  function switchTab(t, inst) {
    var b = tabs.querySelector('[data-t="' + t + '"]'); if (!b) return;
    Array.prototype.forEach.call(tabs.querySelectorAll('button'), function (x) { x.classList.toggle('on', x === b); x.setAttribute('aria-selected', x === b); }); moveInd(b, inst);
    Array.prototype.forEach.call(root.querySelectorAll('.pane'), function (p) { p.hidden = p.id !== 'p-' + t; });
    selClear(); drawBar(); try { history.replaceState(null, '', '#' + t); } catch (e) { /* hash stays as is */ }
    if (!inst) tide(root.querySelectorAll('#p-' + t + ' .ac-card'));
  }
  tabs.addEventListener('click', function (e) { var b = e.target.closest('button'); if (b && !b.classList.contains('on')) switchTab(b.dataset.t); });
  addEventListener('resize', function () { moveInd(tabs.querySelector('.on'), true); });

  /* ---------- start ---------- */
  var t0 = (location.hash || '').slice(1); if (t0 && tabs.querySelector('[data-t="' + t0 + '"]')) switchTab(t0, true); else moveInd(tabs.querySelector('.on'), true);
  drawTypes(); drawCats();
  Promise.all([loadTree(), loadSetups(), loadCats().catch(function () { })]).then(function () {
    loaded = true; build(); cur = B[0] ? B[0].id : null; drawAll(true); drawCats(); tide(root.querySelectorAll('#p-rooms .ac-card'));
  }).catch(function (e) {
    loaded = true; $('main').innerHTML = '<div class="onb"><div class="et">Couldn’t load your buildings</div><div class="es">' + esc(e && e.message ? e.message : 'Please check your connection.') + '</div><div class="ac-row"><button class="ac-btn btn-main" type="button" onclick="location.reload()">Try again</button></div></div>';
    $('bl').innerHTML = '';
  });
})();
