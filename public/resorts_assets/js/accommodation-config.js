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
  var DEF_ALERT = (function () { try { return Math.min(100, Math.max(50, +localStorage.getItem('acfg.alert') || 90)); } catch (e) { return 90; } })();
  var CATS = [], INV = (CFG.items || []).map(function (i) { return { id: String(i.id), n: i.n || '', code: i.code || '', cat: String(i.cat), catName: 'Other', per: !!i.per, dq: +i.dq || 0, avail: +i.avail || 0 }; });

  /* ---------- state ---------- */
  var B = [], FR = [], SU = [], BLD = (CFG.buildings || []).map(function (b) { return { id: +b.id, n: b.n }; });
  var cur = null, sel = {}, loaded = false;
  function bldg() { return B.filter(function (b) { return b.id === cur; })[0]; }
  function fname(f) { return f === 0 ? 'Ground floor' : 'Floor ' + f; }
  function key(f, no) { return f + '|' + no; }
  function selKeys() { return Object.keys(sel); }
  function selClear() { sel = {}; }
  function allRooms(b) { var o = []; b.floors.forEach(function (fl) { fl.rooms.forEach(function (r) { o.push({ f: fl.f, no: r.no, rid: r.rid, s: r.s }); }); }); return o; }
  function cnt(b) { var r = allRooms(b), set = r.filter(function (x) { return x.s; }); return { fl: b.floors.length, rooms: r.length, set: set.length, beds: set.reduce(function (a, x) { return a + x.s.cap; }, 0) }; }
  function numOf(no) { var n = parseInt(String(no).replace(/^G/, ''), 10); return isNaN(n) ? 0 : n; }
  function maxNo(b) { return allRooms(b).reduce(function (m, r) { return Math.max(m, numOf(r.no)); }, 0); }
  function rno(f, i, ns, c) { return ns === 'C' ? String(c) : String(f === 0 ? i : f * 100 + i); } // Room is an integer column, so ground floor is 1, 2, 3…
  /* starting quantities for new rooms, never more than the stock can cover across all of them */
  function fit(i, q) { var n = picked().filter(function (r) { return !r.s; }).length || 1; return Math.max(0, Math.min(q, Math.floor(i.avail / n))); }
  function invDefault(cap) { var o = {}; INV.forEach(function (i) { var q = fit(i, i.per ? cap * (i.dq || 1) : i.dq); if (q > 0) o[i.id] = q; }); return o; }

  function build() {
    var names = {}; BLD.forEach(function (b) { names[b.n] = b.id; });
    var setupBy = {}, used = {};
    SU.forEach(function (r) { var bid = names[r.BuildingName]; if (bid != null) setupBy[bid + '|' + r.Floor + '|' + r.RoomNo] = r; });
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
      fls.forEach(function (fl) { fl.rooms.sort(function (a, b) { return numOf(a.no) - numOf(b.no); }); });
      fls.sort(function (a, b) { return a.f - b.f; });
      var b = { id: bd.id, name: bd.n, floors: fls, ns: 'F', g: 'X' };
      b.ns = inferNs(b); b.g = deriveG(b);
      return b;
    });
  }
  function setup(r) {
    if (!r) return null;
    var beds = String(r.Beds || '0/0').split('/');
    return { aid: +r.id, t: String(r.Accommodation_type_id), cap: +r.Capacity || 0, f: forByRank(r.RoomType), g: r.blockFor === 'Female' ? 'F' : 'M', c: cleanUi(r.CleaningSchedule), a: +r.Occupancytheresold || DEF_ALERT, bn: 'L', inv: null, status: r.RoomStatus || 'Available', occ: +beds[0] || 0 };
  }
  /* numbering style isn't stored (Phase B adds it): "By floor" when every floor above ground has numbers in its own hundred */
  function inferNs(b) { var up = b.floors.filter(function (fl) { return fl.f > 0 && fl.rooms.length; }); if (!up.length) return 'F'; return up.every(function (fl) { return fl.rooms.every(function (r) { var n = numOf(r.no); return n > fl.f * 100 && n < fl.f * 100 + 100; }); }) ? 'F' : 'C'; }
  /* a building's male / female setting isn't stored either: shown from its rooms (read-only) */
  function deriveG(b) { var set = allRooms(b).filter(function (r) { return r.s; }); if (!set.length) return 'X'; var m = set.filter(function (r) { return r.s.g === 'M'; }).length; return m === set.length ? 'M' : m === 0 ? 'F' : 'X'; }

  function loadTree() { return dt(U.floorRooms).then(function (r) { FR = r; }); }
  function loadSetups() { return dt(U.setups).then(function (r) { SU = r; }); }
  function loadBuildings() { return dt(U.buildings).then(function (r) { BLD = r.map(function (x) { return { id: +x.id, n: x.BuildingName }; }); }); }
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
        return '<button type="button" class="bi' + (b.id === cur ? ' on' : '') + '" data-b="' + b.id + '"><span class="bn">' + esc(b.name) + '</span><span class="bm">' + c.fl + ' ' + (c.fl === 1 ? 'floor' : 'floors') + ' · ' + c.rooms + ' ' + (c.rooms === 1 ? 'room' : 'rooms') + (c.set ? ' · ' + GL[b.g] : '') + '</span>' + st + '</button>';
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
    var b = bldg(); if (b && b.ns === 'C') return String(maxNo(b) + 1);
    var nums = fl.rooms.map(function (r) { return numOf(r.no); }).filter(Boolean), n = nums.length ? Math.max.apply(null, nums) + 1 : (fl.f === 0 ? 1 : fl.f * 100 + 1);
    return String(n);
  }
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
      '<div class="facts"><span>' + c.fl + ' ' + (c.fl === 1 ? 'floor' : 'floors') + '</span><span>' + c.rooms + ' ' + (c.rooms === 1 ? 'room' : 'rooms') + '</span><span>Numbered ' + (b.ns === 'C' ? '1, 2, 3…' : 'by floor') + '</span></div>' +
      '<div class="bgf"><span class="lb2">For</span><span class="bgv">' + (c.set ? GL[b.g] : 'Not set yet') + '</span><span class="fgt">From its rooms</span></div></div>' + prog + '</div>' +
      (c.rooms ? '<div class="phint">' + ICON.tap + '<span>' + (unset ? 'Click rooms to pick them, or a <b>floor name</b> for the whole floor. New rooms get set up; rooms already set up get edited.' : 'Click any room to edit it, or a <b>floor name</b> to edit the whole floor.') + '</span></div>' : '') +
      '<div class="stack">' + floors.map(function (fl) {
        var s = fl.rooms.filter(function (r) { return r.s; }).length, below = b.floors.filter(function (x) { return x.f === fl.f - 1; })[0], hasSel = fl.rooms.some(function (r) { return sel[key(fl.f, r.no)]; });
        var canCopy = below && below.rooms.some(function (r) { return r.s; }) && fl.rooms.some(function (r) { return !r.s; });
        return '<div class="flr' + (hasSel ? ' has-sel' : '') + '" data-f="' + fl.f + '"><div class="fcol"><button type="button" class="fn" data-fsel="' + fl.f + '" aria-label="Pick every room on ' + fname(fl.f) + '"><b>' + fname(fl.f) + '</b><span>' + fl.rooms.length + ' ' + (fl.rooms.length === 1 ? 'room' : 'rooms') + ' · ' + (s === fl.rooms.length ? 'all set up' : s + ' set up') + '</span><em>Pick whole floor</em></button></div>' +
          '<div class="rooms">' + fl.rooms.map(function (r) { return tile(fl, r); }).join('') + '<button type="button" class="addr" data-addr="' + fl.f + '" aria-label="Add a room to ' + fname(fl.f) + '" data-tip="Add room ' + nextNo(fl) + '">' + ICON.plus + '</button></div>' +
          '<div class="fa">' + (canCopy ? '<button class="ac-btn btn-ctx cpy" type="button" data-copy="' + fl.f + '" data-tip="Give these rooms the same setup as ' + fname(fl.f - 1) + ', room by room (items aren’t copied)">Same as ' + fname(fl.f - 1) + '</button>' : '') +
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
  });

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
    http('POST', U.roomStore, { building_id: b.id, Floor: f, Room: +no }).then(function (r) {
      if (!r.ok) return toastErr(msgOf(r));
      reload().then(function () {
        var nt = $('main').querySelector('.rm[data-k="' + key(f, no) + '"]'); anim(nt, [{ transform: 'scale(.6)', opacity: 0 }, { transform: 'none', opacity: 1 }], { duration: 380, easing: SWELL });
        toast('Room ' + no + ' added to ' + fname(f), function () { delRooms(b.id, [{ f: f, no: no }]).then(function (ok) { if (ok) { reload(); toast('Room ' + no + ' removed'); } }); });
      });
    });
  }
  /* delete floor/room rows by (floor, number) in the building; resolves true when all went */
  function delRooms(bid, list) {
    var b = B.filter(function (x) { return x.id === bid; })[0], ids = [];
    list.forEach(function (x) { var fl = b.floors.filter(function (y) { return y.f === x.f; })[0], r = fl && fl.rooms.filter(function (z) { return z.no === String(x.no); })[0]; if (r && r.rid) ids.push(r.rid); });
    return batches(ids, 10, function (id) { return http('DELETE', tpl(U.roomDestroy, b64(id))); }).then(function (rs) { var bad = rs.filter(function (r) { return !r.ok; }); if (bad.length) toastErr(msgOf(bad[0])); return !bad.length; });
  }
  function restoreRooms(bid, list) { return batches(list, 10, function (x) { return http('POST', U.roomStore, { building_id: bid, Floor: x.f, Room: +x.no }); }).then(function (rs) { return rs.every(function (r) { return r.ok; }); }); }
  function removeFloor(f) {
    var b = bldg(), fl = b.floors.filter(function (x) { return x.f === f; })[0];
    if (fl.rooms.some(function (r) { return r.s; })) { toast(fname(f) + ' has rooms that are set up, so it can’t be removed here. Remove their setup from the Room setup list first.', null, 'warn'); return; }
    var list = fl.rooms.map(function (r) { return { f: f, no: r.no }; });
    delRooms(b.id, list).then(function (ok) {
      Object.keys(sel).forEach(function (k) { if (k.indexOf(f + '|') === 0) delete sel[k]; });
      reload().then(function () { drawBar(); if (ok) toast(fname(f) + ' removed', function () { restoreRooms(b.id, list).then(function (ok2) { reload(); toast(ok2 ? fname(f) + ' is back' : 'Some rooms couldn’t be restored', null, ok2 ? 'ok' : 'warn'); }); }); });
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
    delRooms(b.id, list).then(function (ok) { selClear(); reload().then(function () { drawBar(); if (ok) toast(list.length + (list.length === 1 ? ' room' : ' rooms') + ' removed', function () { restoreRooms(b.id, list).then(function (ok2) { reload(); toast(ok2 ? 'Rooms are back' : 'Some rooms couldn’t be restored', null, ok2 ? 'ok' : 'warn'); }); }); }); });
  };
  $('sSet').onclick = openDrawer;

  /* ---------- room setup drawer ---------- */
  var D = null, invOpen = false, review = false, invQ = '', invF = 'room', failed = [], busy = false;
  function newD() { return { t: null, cap: null, f: null, g: null, c: 'Weekly', a: DEF_ALERT, bn: 'L', inv: {} }; }
  function openDrawer() {
    var rs = picked(); if (!rs.length) return;
    var from = rs.filter(function (r) { return r.s; })[0];
    D = from ? { t: from.s.t, cap: from.s.cap, f: from.s.f, g: from.s.g, c: from.s.c, a: from.s.a, bn: 'L', inv: {} } : newD();
    var fls = rs.map(function (r) { return r.f; }).filter(function (v, i, a) { return a.indexOf(v) === i; });
    $('drT').textContent = drTitle();
    var where = fls.length > 1 ? rs.length + ' rooms on ' + fls.length + ' floors' : fname(fls[0]) + (rs.length > 1 ? ' · ' + rs[0].no + '–' + rs[rs.length - 1].no : '');
    $('drS').textContent = bldg().name + ' · ' + where;
    invOpen = false; review = false; failed = []; busy = false; drawDrawer(); $('drb').scrollTop = 0;
    $('scrim').classList.add('on'); $('dr').classList.add('on'); $('dr').setAttribute('aria-hidden', 'false');
    // bed-name style of the first set-up room (A, B… or 1, 2…) comes from its own beds
    if (from) http('GET', tpl(U.beds, from.s.aid)).then(function (r) { var bd = (r.json && r.json.beds) || []; if (bd[0] && /-\d+$/.test(String(bd[0].BedNo))) { D.bn = 'N'; if (!review) drawDrawer(); } });
    setTimeout(function () { var f = $('drb').querySelector('button'); if (f) f.focus(); }, 300);
  }
  function closeDrawer() { $('scrim').classList.remove('on'); $('dr').classList.remove('on'); $('dr').setAttribute('aria-hidden', 'true'); }
  function drTitle() { var rs = picked(), v = pickMode() === 'edit' ? 'Edit' : 'Set up'; return rs.length === 1 ? v + ' room ' + rs[0].no : v + ' ' + rs.length + ' rooms'; }
  function stepH(id, v, min, max, lbl) { return '<div class="step" data-st="' + id + '"><button type="button" data-d="-1" aria-label="Less ' + esc(lbl) + '"' + (v <= min ? ' disabled' : '') + '>−</button><input type="number" value="' + v + '" min="' + min + '" max="' + max + '" aria-label="' + esc(lbl) + '"><button type="button" data-d="1" aria-label="More ' + esc(lbl) + '"' + (v >= max ? ' disabled' : '') + '>+</button></div>'; }
  function bedName(no, i, bn) { return no + '-' + (bn === 'L' ? String.fromCharCode(65 + (i % 26)) : i + 1); }
  var hasNew = function () { return picked().some(function (r) { return !r.s; }); };
  function drawDrawer() {
    if (review) { drawReview(); return; }
    $('drC').textContent = 'Cancel';
    var rs = picked(), no = rs[0].no, cap = D.cap || 0, t = typ(D.t), isNew = hasNew();
    var beds = cap ? Array.apply(null, { length: Math.min(cap, 12) }).map(function (_, i) { return '<span>' + esc(bedName(no, i, D.bn)) + '</span>'; }).join('') : '<span style="background:none;color:var(--faint);padding:0">Choose a type first</span>';
    var okRoom = !!D.t, okWho = !!D.f && !!D.g;
    var items = INV.filter(function (i) { return (D.inv[i.id] || 0) > 0; }), pieces = items.reduce(function (a, i) { return a + D.inv[i.id]; }, 0);
    function grp(n, title, ok, sum, body, id) { return '<section class="grp' + (ok ? ' ok' : '') + '"' + (id ? ' id="' + id + '"' : '') + '><div class="gh"><span class="gn">' + (ok ? '✓' : n) + '</span><span class="gt">' + title + '</span><span class="gs"' + (id ? ' id="' + id + 'S"' : '') + '>' + sum + '</span></div>' + body + '</section>'; }
    var invBody = !isNew ? '<div class="fnote">Items in rooms that are already set up can’t be changed here yet. Add or move them in Inventory.</div>'
      : invOpen ? '<label class="isr"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg><input id="invQ" placeholder="Search ' + INV.length + ' items, e.g. pillow, kettle" aria-label="Search inventory items" autocomplete="off" value="' + esc(invQ) + '"><button type="button" class="isx" data-iclr aria-label="Clear search"' + (invQ ? '' : ' hidden') + '>' + ICON.x + '</button></label><div class="ifl" id="invF"></div><div class="inv" id="invL" aria-live="polite"></div><button type="button" class="glink" data-inv="0">Done</button>'
      : '<div class="invs">' + (items.length ? items.map(function (i) { return '<span><b>' + D.inv[i.id] + '</b> ' + esc(i.n) + '</span>'; }).join('') : '<em>Nothing added</em>') + '</div><button type="button" class="glink" data-inv="1">Change items</button>';
    $('drb').innerHTML =
      grp(1, 'Room', okRoom, okRoom ? esc(t ? t.n : '') + ' · ' + cap + ' ' + (cap === 1 ? 'bed' : 'beds') : 'Not chosen yet',
        '<div class="fs"><span class="lb">Accommodation type</span><div class="chips" role="radiogroup">' + TYPES.map(function (x) { return '<button type="button" role="radio" aria-checked="' + (D.t === x.id) + '" class="' + (D.t === x.id ? 'on' : '') + '" data-type="' + x.id + '">' + esc(x.n) + '</button>'; }).join('') + '<button type="button" class="new" data-go="types">+ New type</button></div></div>' +
        (D.t ? '<div class="fs"><span class="lb">Beds in each room</span><div class="ac-row">' + stepH('cap', cap, 1, 12, 'beds') + '<div class="beds">' + beds + '</div></div></div><div class="fs"><span class="lb">Bed names</span><div class="seg"><button type="button" class="' + (D.bn === 'L' ? 'on' : '') + '" data-bn="L">' + esc(no) + '-A, B…</button><button type="button" class="' + (D.bn === 'N' ? 'on' : '') + '" data-bn="N">' + esc(no) + '-1, 2…</button></div></div>' : '')) +
      grp(2, 'Who stays', okWho, okWho ? esc(forBy(D.f)[1]) + ' · ' + GL[D.g] : 'Not chosen yet',
        '<div class="fs"><span class="lb">Who stays here</span><div class="chips" role="radiogroup">' + FOR.map(function (f) { return '<button type="button" role="radio" aria-checked="' + (D.f === f[0]) + '" class="' + (D.f === f[0] ? 'on' : '') + '" data-for="' + f[0] + '">' + f[1] + '</button>'; }).join('') + '</div></div>' +
        '<div class="fs"><span class="lb">For</span><div class="seg" role="radiogroup"><button type="button" role="radio" class="' + (D.g === 'M' ? 'on' : '') + '" data-g="M" aria-checked="' + (D.g === 'M') + '">Male</button><button type="button" role="radio" class="' + (D.g === 'F' ? 'on' : '') + '" data-g="F" aria-checked="' + (D.g === 'F') + '">Female</button></div><span class="hint">Each room is either male or female.</span></div>') +
      grp(3, 'Cleaning & alert', true, esc(D.c) + ' · alert at ' + D.a + '%',
        '<div class="fs"><span class="lb">Cleaning</span><div class="chips" role="radiogroup">' + CLEAN.map(function (c) { return '<button type="button" role="radio" aria-checked="' + (D.c === c) + '" class="' + (D.c === c ? 'on' : '') + '" data-clean="' + c + '">' + c + '</button>'; }).join('') + '</div></div>' +
        '<div class="fs"><span class="lb">Occupancy alert level</span><div class="rng"><input type="range" min="50" max="100" step="5" value="' + D.a + '" data-a aria-label="Occupancy alert"><b>' + D.a + '%</b></div></div>', 'gCare') +
      grp(4, 'What’s in each room', true, isNew ? items.length + ' items · ' + pieces + ' pieces' : 'Stays as it is', invBody);
    if (invOpen && isNew) drawInv();
    var miss = !D.t ? 'Choose an accommodation type' : !D.f ? 'Choose who stays here' : !D.g ? 'Choose male or female' : '';
    var n = picked().length, ok = $('drOk'); ok.textContent = pickMode() === 'edit' ? 'Review changes' : (n === 1 ? 'Review room' : 'Review ' + n + ' rooms');
    ok.classList.toggle('dis', !!miss); ok.setAttribute('aria-disabled', !!miss); if (miss) ok.setAttribute('data-tip', miss); else ok.removeAttribute('data-tip'); $('why').textContent = miss;
  }
  $('drb').addEventListener('click', function (e) {
    var t = e.target;
    var ed = t.closest('[data-edit]'); if (ed) { setReview(false, +ed.dataset.edit); return; }
    if (t.closest('[data-go]')) { closeDrawer(); switchTab('types'); setTimeout(function () { $('tN').focus(); }, 350); return; }
    var ty = t.closest('[data-type]'); if (ty) { D.t = ty.dataset.type; if (!D.cap) { D.cap = 1; if (hasNew()) D.inv = invDefault(1); } drawDrawer(); return; }
    var fo = t.closest('[data-for]'); if (fo) { D.f = fo.dataset.for; drawDrawer(); return; }
    var g = t.closest('[data-g]'); if (g) { D.g = g.dataset.g; drawDrawer(); return; }
    var c = t.closest('[data-clean]'); if (c) { D.c = c.dataset.clean; drawDrawer(); return; }
    var bn = t.closest('[data-bn]'); if (bn) { D.bn = bn.dataset.bn; drawDrawer(); return; }
    var iv = t.closest('[data-inv]');
    if (iv) { invOpen = iv.dataset.inv === '1'; if (invOpen) { invQ = ''; invF = Object.keys(D.inv).some(function (k) { return D.inv[k] > 0; }) ? 'room' : 'all'; } drawDrawer();
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
    if (id === 'cap') { D.cap = v; if (hasNew()) INV.forEach(function (i) { if (i.per) D.inv[i.id] = fit(i, v * (i.dq || 1)); }); drawDrawer(); return; }
    D.inv[id.slice(4)] = v;
    if (invOpen) {
      var row = $('invL').querySelector('[data-row="' + id.slice(4) + '"]');
      if (row) { row.classList.toggle('zero', !v); var st = row.querySelector('.step'), inp = st.querySelector('input'), bs = st.querySelectorAll('button'); inp.value = v; bs[0].disabled = v <= min; bs[1].disabled = v >= max; anim(inp, [{ transform: 'scale(1.18)' }, { transform: 'none' }], { duration: 220, easing: SWELL }); }
      var f = $('invF').querySelector('[data-if="room"] b'); if (f) rollTo(f, INV.filter(function (i) { return (D.inv[i.id] || 0) > 0; }).length); invSum(); return;
    }
    drawDrawer();
  }

  /* ---------- review before saving ---------- */
  function diffs(rs) {
    var set = rs.filter(function (r) { return r.s; }); if (!set.length) return [];
    var F = [['Accommodation type', function (s) { return (typ(s.t) || {}).n || '—'; }], ['Beds', function (s) { return String(s.cap); }], ['Who stays', function (s) { return forBy(s.f)[1] || '—'; }], ['For', function (s) { return GL[s.g] || '—'; }], ['Cleaning', function (s) { return s.c; }], ['Occupancy alert', function (s) { return s.a + '%'; }]];
    function uniq(a) { return a.filter(function (v, i, x) { return x.indexOf(v) === i; }); }
    var out = []; F.forEach(function (x) {
      var olds = uniq(set.map(function (r) { return x[1](r.s); })), news = x[1](D), ch = set.some(function (r) { return x[1](r.s) !== news; });
      if (ch) out.push([x[0], olds.length === 1 ? olds[0] : 'varies', news]);
    });
    return out;
  }
  /* what would stop the save: not enough stock, or fewer beds than staff already assigned */
  function problems(rs) {
    var p = [], nNew = rs.filter(function (r) { return !r.s; }).length;
    if (nNew) INV.forEach(function (i) { var q2 = D.inv[i.id] || 0; if (q2 && q2 * nNew > i.avail) p.push('Not enough ' + i.n + ': ' + q2 * nNew + ' needed, ' + i.avail + ' in stock'); });
    rs.forEach(function (r) { if (r.s && r.s.occ > D.cap) p.push('Room ' + r.no + ' has ' + r.s.occ + ' staff assigned, so it needs at least ' + r.s.occ + ' beds'); });
    return p;
  }
  function drawReview() {
    var b = bldg(), rs = picked(), n = rs.length, t = typ(D.t), cap = D.cap, no = rs[0].no, mode = pickMode(), nNew = rs.filter(function (r) { return !r.s; }).length;
    var items = nNew ? INV.filter(function (i) { return (D.inv[i.id] || 0) > 0; }) : [], pieces = items.reduce(function (a, i) { return a + D.inv[i.id]; }, 0);
    var repl = rs.filter(function (r) { return r.s; }), bedDiff = repl.reduce(function (a, r) { return a + (cap - r.s.cap); }, 0), dl = diffs(rs), chg = {}; dl.forEach(function (d) { chg[d[0]] = d; });
    var none = mode === 'edit' && !dl.length, probs = problems(rs), byF = {}; rs.forEach(function (r) { (byF[r.f] = byF[r.f] || []).push(r); });
    var flist = Object.keys(byF).map(Number).sort(function (x, y) { return y - x; });
    var bn = Array.apply(null, { length: Math.min(cap, 4) }).map(function (_, i) { return bedName(no, i, D.bn); }).join(', ') + (cap > 4 ? '…' : '');
    function row(k, v, sec) { var c = chg[k]; return '<div class="rvr' + (c ? ' chg' : '') + '"><span class="rk">' + k + '</span><span class="rv">' + (c ? '<s>' + esc(c[1]) + '</s> → ' : '') + v + '</span><button type="button" class="glink" data-edit="' + sec + '">Edit</button></div>'; }
    $('drT').textContent = 'Check before saving'; $('drS').textContent = b.name + ' · ' + (n === 1 ? 'room ' + no : n + ' rooms');
    var note = mode === 'edit' ? (none ? '<div class="same">Nothing has changed yet. Go back and change a setting, or close this panel.</div>' : '<div class="chgb"><b>You’re changing ' + dl.length + ' ' + (dl.length === 1 ? 'setting' : 'settings') + '</b> on ' + (n === 1 ? 'room ' + no : n + ' rooms') + (bedDiff ? ' · beds ' + (bedDiff > 0 ? '+' : '') + bedDiff + ' overall' : '') + '.<span class="rl">' + dl.map(function (d) { return d[0]; }).join(' · ') + '</span></div>')
      : (repl.length && dl.length ? '<div class="warn"><b>' + repl.length + ' of these ' + (repl.length === 1 ? 'room is' : 'rooms are') + ' already set up</b> and will get the new settings' + (bedDiff ? ' · beds ' + (bedDiff > 0 ? '+' : '') + bedDiff + ' overall' : '') + '.<span class="rl">' + repl.slice(0, 8).map(function (r) { return esc(r.no); }).join(', ') + (repl.length > 8 ? '…' : '') + '</span></div>' : '');
    var fail = failed.length ? '<div class="warn"><b>' + failed.length + ' of ' + n + ' ' + (n === 1 ? 'room' : 'rooms') + ' couldn’t be saved</b>. The others are saved.<span class="rl">' + failed.map(function (f) { return esc(f.no) + ' — ' + esc(f.msg); }).slice(0, 6).join('<br>') + '</span></div>' : '';
    var prob = probs.length ? '<div class="warn"><b>Fix this before saving</b><span class="rl">' + probs.slice(0, 5).map(esc).join('<br>') + '</span></div>' : '';
    $('drb').innerHTML = fail + prob + note +
      '<div class="rvs"><div><b>' + n + '</b><span>Rooms</span></div><div><b>' + n * cap + '</b><span>Beds</span></div><div><b>' + nNew * pieces + '</b><span>Items</span></div><div><b>' + flist.length + '</b><span>' + (flist.length === 1 ? 'Floor' : 'Floors') + '</span></div></div>' +
      '<section class="grp"><div class="gh"><span class="gt">Applies to</span><span class="gs">' + n + ' ' + (n === 1 ? 'room' : 'rooms') + '</span></div><div class="rvf">' + flist.map(function (f) { return '<div><span class="fl2">' + fname(f) + '</span><div class="nos">' + byF[f].map(function (r) { var mk = mode === 'mix' && r.s && dl.length; return '<span class="' + (mk ? 'rp' : '') + '"' + (mk ? ' data-tip="Already set up · gets the new settings"' : '') + '>' + esc(r.no) + '</span>'; }).join('') + '</div></div>'; }).join('') + '</div></section>' +
      '<section class="grp"><div class="gh"><span class="gt">Setup for each room</span></div><div class="rvl">' +
        row('Accommodation type', esc(t ? t.n : ''), 0) + row('Beds', cap + ' · ' + esc(bn), 0) + row('Who stays', esc(forBy(D.f)[1]), 1) + row('For', GL[D.g], 1) + row('Cleaning', esc(D.c), 2) + row('Occupancy alert', D.a + '% full', 2) +
        (nNew ? row('Items', items.length ? items.map(function (i) { return D.inv[i.id] + ' ' + esc(i.n.toLowerCase()); }).join(', ') : 'None', 3) : '') + '</div></section>';
    $('drC').textContent = 'Back'; $('why').textContent = probs.length ? probs[0] : '';
    var ok = $('drOk'), block = none || !!probs.length || busy;
    ok.textContent = busy ? 'Saving ' + n + (n === 1 ? ' room…' : ' rooms…') : failed.length ? 'Retry ' + failed.length + ' failed' : mode === 'edit' ? 'Save changes' : (n === 1 ? 'Confirm & save' : 'Confirm & save ' + n + ' rooms');
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

  /* ---------- saving a setup: existing create (new rooms) and update (rooms already set up) endpoints ---------- */
  function setupPayload(rooms, b) {
    var p = { AvailableCount: rooms.length, BuildingName: {}, Floor: {}, RoomNo: {}, Accommodation_type_id: {}, Capacity: {}, RoomType: {}, BedNo: {}, blockFor: {}, CleaningSchedule: {}, RoomStatus: {}, Occupancytheresold: {}, BedNames: {}, Inv_Cat_id: {}, InvQty: {} };
    var ids = Object.keys(D.inv).filter(function (id) { return D.inv[id] > 0; });
    rooms.forEach(function (r, i) {
      var k = i + 1; p.BuildingName[k] = b.id; p.Floor[k] = r.f; p.RoomNo[k] = r.no; p.Accommodation_type_id[k] = D.t; p.Capacity[k] = D.cap; p.RoomType[k] = forBy(D.f)[3]; p.BedNo[k] = D.cap;
      p.blockFor[k] = D.g === 'F' ? 'Female' : 'Male'; p.CleaningSchedule[k] = cleanDb(D.c); p.RoomStatus[k] = 'Available'; p.Occupancytheresold[k] = D.a;
      p.BedNames[k] = Array.apply(null, { length: D.cap }).map(function (_, j) { return bedName(r.no, j, D.bn); });
      if (ids.length) { p.Inv_Cat_id[k] = ids; p.InvQty[k] = {}; ids.forEach(function (id) { p.InvQty[k][id] = D.inv[id]; }); }
    });
    return p;
  }
  function updatePayload(r, beds) {
    var p = { Accommodation_type_id: D.t, Capacity: D.cap, RoomType: forBy(D.f)[3], blockFor: D.g === 'F' ? 'Female' : 'Male', CleaningSchedule: cleanDb(D.c), RoomStatus: r.s.status, Occupancytheresold: D.a, bedNames: {}, newBeds: [] };
    beds.forEach(function (bd, j) { if (j < D.cap) p.bedNames[bd.id] = bedName(r.no, j, D.bn); });
    for (var j = beds.length; j < D.cap; j++) p.newBeds.push(bedName(r.no, j, D.bn));
    return p;
  }
  function saveSetup() {
    var b = bldg(), rs = failed.length ? failed.map(function (f) { return f.room; }) : picked(), wasEdit = pickMode() === 'edit', fresh = rs.filter(function (r) { return !r.s; }), old = rs.filter(function (r) { return r.s; });
    var undoOld = old.map(function (r) { return { r: r, s: JSON.parse(JSON.stringify(r.s)) }; });
    busy = true; failed = []; drawReview();
    var newFail = [];
    // new rooms: one create call per chunk of 10 (each call is one transaction); a failed chunk is retried room by room to find the culprit
    var chunks = []; for (var i = 0; i < fresh.length; i += 10) chunks.push(fresh.slice(i, i + 10));
    var p = chunks.reduce(function (pr, ch) {
      return pr.then(function () {
        return http('POST', U.setupStore, setupPayload(ch, b)).then(function (r) {
          if (r.ok) return;
          if (ch.length === 1) { newFail.push({ no: ch[0].no, msg: msgOf(r), room: ch[0] }); return; }
          return ch.reduce(function (p2, one) { return p2.then(function () { return http('POST', U.setupStore, setupPayload([one], b)).then(function (r2) { if (!r2.ok) newFail.push({ no: one.no, msg: msgOf(r2), room: one }); }); }); }, Promise.resolve());
        });
      });
    }, Promise.resolve());
    p = p.then(function () { // rooms already set up: read their beds, then update each (existing update endpoint)
      return batches(old, 10, function (r) { return http('GET', tpl(U.beds, r.s.aid)).then(function (br) { return http('PUT', tpl(U.setupUpdate, r.s.aid), updatePayload(r, (br.json && br.json.beds) || [])).then(function (ur) { return { r: r, ok: ur.ok, msg: ur.ok ? '' : msgOf(ur) }; }); }); });
    }).then(function (ures) {
      ures.forEach(function (u) { if (!u.ok) newFail.push({ no: u.r.no, msg: u.msg, room: u.r }); });
      busy = false;
      return reload({ s: true }).then(function () {
        selClear(); newFail.forEach(function (f) { sel[key(f.room.f, f.room.no)] = 1; });
        drawMain();
        var saved = rs.length - newFail.length;
        if (newFail.length) {
          failed = newFail.map(function (f) { var rr = picked().filter(function (x) { return x.f === f.room.f && x.no === f.room.no; })[0] || f.room; return { no: f.no, msg: f.msg, room: rr }; });
          review = true; drawBar(); drawReview();
          if (saved) toast(saved + (saved === 1 ? ' room' : ' rooms') + ' saved, ' + newFail.length + ' failed', null, 'warn');
          return;
        }
        closeDrawer(); drawBar();
        var keys = rs.map(function (r) { return key(r.f, r.no); });
        keys.forEach(function (k, j) { var el = $('main').querySelector('.rm[data-k="' + k + '"]'); anim(el, [{ transform: 'scale(.88)', background: '#f7ffd1' }, { transform: 'none', background: '#fff' }], { duration: 540, delay: 120 + Math.min(j, 40) * 22, easing: SWELL }); });
        var n = rs.length;
        toast(wasEdit ? (n === 1 ? 'Room updated' : n + ' rooms updated') : (n === 1 ? 'Room set up' : n + ' rooms set up'), function () { undoSetup(b, fresh, undoOld); });
      });
    });
    return p;
  }
  /* Undo: new setups are removed again (existing delete), edited rooms go back to their previous values (existing update) */
  function undoSetup(b, fresh, undoOld) {
    var nb = B.filter(function (x) { return x.id === b.id; })[0];
    var ids = []; fresh.forEach(function (r) { var fl = nb.floors.filter(function (x) { return x.f === r.f; })[0], rr = fl && fl.rooms.filter(function (x) { return x.no === r.no; })[0]; if (rr && rr.s) ids.push(rr.s.aid); });
    batches(ids, 10, function (id) { return http('DELETE', tpl(U.setupDestroy, id)); }).then(function (rs) {
      return batches(undoOld, 10, function (u) {
        return http('GET', tpl(U.beds, u.s.aid)).then(function (br) {
          var beds = (br.json && br.json.beds) || [], p = { Accommodation_type_id: u.s.t, Capacity: u.s.cap, RoomType: forBy(u.s.f)[3], blockFor: u.s.g === 'F' ? 'Female' : 'Male', CleaningSchedule: cleanDb(u.s.c), RoomStatus: u.s.status, Occupancytheresold: u.s.a, bedNames: {}, newBeds: [] };
          beds.forEach(function (bd, j) { if (j < u.s.cap) p.bedNames[bd.id] = bedName(u.r.no, j, 'L'); });
          for (var j = beds.length; j < u.s.cap; j++) p.newBeds.push(bedName(u.r.no, j, 'L'));
          return http('PUT', tpl(U.setupUpdate, u.s.aid), p);
        });
      }).then(function (us) { return rs.concat(us); });
    }).then(function (all) { var bad = all.filter(function (r) { return !r.ok; }); return reload({ s: true }).then(function () { toast(bad.length ? 'Some rooms couldn’t be restored' : 'Changes undone', null, bad.length ? 'warn' : 'ok'); }); });
  }
  /* "Same as the floor below": the existing create call, room by room by position (items aren't readable yet, so they aren't copied) */
  function copyFloor(f) {
    var b = bldg(), fl = b.floors.filter(function (x) { return x.f === f; })[0], below = b.floors.filter(function (x) { return x.f === f - 1; })[0], todo = [];
    fl.rooms.forEach(function (r, i) { var src = below.rooms[i] && below.rooms[i].s; if (!r.s && src) todo.push({ r: r, src: src }); });
    if (!todo.length) return;
    var made = [];
    batches(todo, 1, function (x) {
      var save = D; D = { t: x.src.t, cap: x.src.cap, f: x.src.f, g: x.src.g, c: x.src.c, a: x.src.a, bn: 'L', inv: {} };
      var pl = setupPayload([{ f: f, no: x.r.no }], b); D = save;
      return http('POST', U.setupStore, pl).then(function (r) { if (r.ok) made.push(x); return r; });
    }).then(function () {
      reload({ s: true }).then(function () {
        var n = made.length; if (!n) return toastErr('Nothing could be copied');
        var nb = B.filter(function (x) { return x.id === b.id; })[0];
        toast(n + ' ' + (n === 1 ? 'room' : 'rooms') + ' on ' + fname(f) + ' set up like ' + fname(f - 1), function () { undoSetup(b, made.map(function (x) { return { f: f, no: x.r.no }; }), []); });
        Array.prototype.forEach.call($('main').querySelectorAll('.flr[data-f="' + f + '"] .rm:not(.unset)'), function (r, i) { anim(r, [{ transform: 'scale(.9)', background: '#f7ffd1' }, { transform: 'none', background: '#fff' }], { duration: 520, delay: i * 25, easing: SWELL }); });
      });
    });
  }

  /* ---------- add building / floors modal ---------- */
  var M = 'b', NS = 'F', PF = [], mBusy = false, mRetry = null;
  function mv(id) { return +$(id).querySelector('input').value; }
  function openModal(kind) {
    M = kind; var b = bldg(); mRetry = null; mBusy = false; $('mR').hidden = true;
    $('mNameF').hidden = M !== 'b'; $('mGF').hidden = M !== 'b'; $('mNS').hidden = M !== 'b'; $('mDiv').hidden = M !== 'b'; $('mE').textContent = ''; $('mName').classList.remove('bad');
    if (M === 'b') { $('mT').textContent = 'Add a building'; $('mS').textContent = 'Name it, choose how rooms are numbered, then say how many rooms each floor has.'; $('mName').value = ''; setNS('F'); }
    else { NS = b.ns || 'F'; var top = Math.max.apply(null, b.floors.map(function (x) { return x.f; }).concat([-1])); $('mT').textContent = 'Add floors to ' + b.name; $('mS').textContent = 'New floors go on top of ' + (top < 0 ? 'the building' : fname(top).toLowerCase()) + '. Rooms continue the building’s numbering (' + (NS === 'F' ? 'by floor' : 'continuous') + ').'; }
    var nF = M === 'b' ? 3 : 1, last = M === 'b' ? 8 : (b.floors.length ? b.floors.slice().sort(function (x, y) { return y.f - x.f; })[0].rooms.length || 8 : 8);
    $('mF').querySelector('input').value = nF; PF = Array.apply(null, { length: nF }).map(function () { return last; });
    drawPrev(); $('md').classList.add('on'); $('md').setAttribute('aria-hidden', 'false'); $('scrim').classList.add('on'); setTimeout(function () { (M === 'b' ? $('mName') : $('mOk')).focus(); }, 200);
  }
  function closeModal() { if (mBusy) return; $('md').classList.remove('on'); $('md').setAttribute('aria-hidden', 'true'); if (!$('dr').classList.contains('on')) $('scrim').classList.remove('on'); if (mRetry) { mRetry = null; reload({ b: true }); } }
  function plan() {
    var nF = mv('mF'), b = bldg(); while (PF.length < nF) PF.push(PF.length ? PF[PF.length - 1] : 8); PF.length = nF;
    var start = M === 'b' ? ($('mG').checked ? 0 : 1) : Math.max.apply(null, b.floors.map(function (f) { return f.f; }).concat([-1])) + 1, c = M === 'b' ? 0 : maxNo(b);
    return PF.map(function (n, i) { var f = start + i; return { f: f, n: n, nos: Array.apply(null, { length: n }).map(function (_, j) { return rno(f, j + 1, NS, ++c); }) }; });
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
    $('mOk').textContent = M === 'b' ? 'Create building' : 'Add ' + (p.length === 1 ? 'floor' : 'floors');
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
  function setNS(v) { NS = v; Array.prototype.forEach.call($('mNSs').querySelectorAll('button'), function (b) { b.classList.toggle('on', b.dataset.ns === v); b.setAttribute('aria-checked', b.dataset.ns === v); }); }
  $('mNSs').addEventListener('click', function (e) { var b = e.target.closest('[data-ns]'); if (!b || b.dataset.ns === NS) return; setNS(b.dataset.ns); drawPrev(); Array.prototype.forEach.call($('mP').querySelectorAll('.nos span'), function (s, i) { anim(s, [{ opacity: 0, transform: 'translateY(4px)' }, { opacity: 1, transform: 'none' }], { duration: 280, delay: Math.min(i, 40) * 8, easing: TIDE }); }); });
  $('mG').addEventListener('change', drawPrev);
  $('mName').addEventListener('input', function () { var v = $('mName').value.trim(), dup = BLD.some(function (b) { return b.n.toLowerCase() === v.toLowerCase(); }); $('mE').textContent = dup ? 'There’s already a building called ' + v : ''; $('mName').classList.toggle('bad', dup); });
  $('mName').addEventListener('keydown', function (e) { if (e.key === 'Enter') $('mOk').click(); });
  $('mC').onclick = closeModal;
  function mBusySet(on, txt) { mBusy = on; var ok = $('mOk'); ok.classList.toggle('dis', on); ok.setAttribute('aria-disabled', on); if (txt) ok.textContent = txt; }
  /* create the rooms of a plan in the existing floor-and-room create, 10 at a time; resolves to the rooms that failed */
  function createRooms(bid, list) {
    var bad = [];
    return batches(list, 10, function (x) { return http('POST', U.roomStore, { building_id: bid, Floor: x.f, Room: +x.no }).then(function (r) { if (!r.ok) bad.push({ f: x.f, no: x.no, msg: msgOf(r) }); }); }).then(function () { return bad; });
  }
  function showFail(bad, ctx) {
    var el = $('mR'); el.hidden = false;
    el.innerHTML = '<b>' + bad.length + ' ' + (bad.length === 1 ? 'room' : 'rooms') + ' couldn’t be created</b>: ' + bad.slice(0, 8).map(function (x) { return esc(x.no); }).join(', ') + (bad.length > 8 ? '…' : '') + '. ' + esc(bad[0].msg) + '<br>What was created is kept.';
    ctx.bad = bad; mRetry = ctx; mBusySet(false, 'Retry ' + bad.length + ' failed');
  }
  $('mOk').onclick = function () {
    if (mBusy) return;
    if (mRetry) { var ctx = mRetry; mBusySet(true, 'Creating ' + ctx.bad.length + ' rooms…'); createRooms(ctx.bid, ctx.bad).then(function (bad) { if (bad.length) showFail(bad, ctx); else finishModal(ctx.bid, ctx.nb, ctx.rooms, ctx.nFloors); }); return; }
    var p = plan(), tot = p.reduce(function (a, x) { return a + x.n; }, 0), all = []; p.forEach(function (x) { x.nos.forEach(function (n) { all.push({ f: x.f, no: n }); }); });
    $('mR').hidden = true;
    if (M === 'b') {
      var v = $('mName').value.trim();
      if (!v) { $('mE').textContent = 'Give the building a name'; $('mName').classList.add('bad'); $('mName').focus(); return; }
      if (BLD.some(function (b) { return b.n.toLowerCase() === v.toLowerCase(); })) return;
      mBusySet(true, 'Creating ' + tot + ' rooms…');
      http('POST', U.buildingStore, { BuildingName: v }).then(function (r) {
        if (!r.ok) { mBusySet(false, 'Create building'); $('mE').textContent = msgOf(r); $('mName').classList.add('bad'); return; }
        return loadBuildings().then(function () {
          var nb = BLD.filter(function (b) { return b.n === v; })[0]; if (!nb) { mBusySet(false, 'Create building'); $('mE').textContent = 'The building was created but couldn’t be found. Please reload the page.'; return; }
          return createRooms(nb.id, all).then(function (bad) { var made = { name: v, floors: p.length, rooms: tot }; if (bad.length) return showFail(bad, { bid: nb.id, nb: made }); finishModal(nb.id, made); });
        });
      });
    } else {
      var b = bldg(); mBusySet(true, 'Creating ' + tot + ' rooms…');
      createRooms(b.id, all).then(function (bad) { if (bad.length) return showFail(bad, { bid: b.id, rooms: all, nFloors: p.length }); finishModal(b.id, null, all, p.length); });
    }
  };
  function finishModal(bid, nb, rooms, nFloors) {
    mBusySet(false); mRetry = null; $('md').classList.remove('on'); $('md').setAttribute('aria-hidden', 'true'); $('scrim').classList.remove('on');
    cur = bid; selClear();
    reload({ b: true }).then(function () {
      drawAll(!!nb);
      if (nb) toast(nb.name + ' added · ' + nb.floors + ' floors, ' + nb.rooms + ' rooms', function () { http('DELETE', tpl(U.buildingDestroy, b64(bid))).then(function (r) { if (r.ok) { cur = null; reload({ b: true }); toast(nb.name + ' removed'); } else toastErr(msgOf(r)); }); });
      else toast(nFloors + ' ' + (nFloors === 1 ? 'floor' : 'floors') + ' added to ' + bldg().name, function () { delRooms(bid, rooms).then(function (ok) { if (ok) { reload(); toast('Floors removed'); } }); });
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
  $('aSave').onclick = function () { DEF_ALERT = +$('aR').value; try { localStorage.setItem('acfg.alert', String(DEF_ALERT)); } catch (e) { /* kept for this visit only */ } toast('Occupancy alert saved · new rooms start at ' + DEF_ALERT + '%'); };
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
