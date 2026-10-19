/* Leader master dashboard (non-HR HOD / ExCom / GM) — behaviour for resorts/master-dashboard/leaderdashboard.blade.php.
 * Data comes from window.LD (built server-side from the controller's own variables). All text is inserted with
 * textContent, never as HTML. Toasts use the app-wide wisdomToast() (resorts_assets/js/wai-toast.js).
 */
(function () {
  'use strict';
  var D = window.LD || {}, U = D.urls || {};
  var root = document.getElementById('ldmd');
  if (!root) return;
  var $ = function (id) { return document.getElementById(id); };
  var CSRF = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  var JSON_HEAD = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };

  function el(tag, cls, text) { var e = document.createElement(tag); if (cls) e.className = cls; if (text != null) e.textContent = text; return e; }
  function initials(n) { return (String(n || '').trim().split(/\s+/).filter(Boolean).slice(0, 2).map(function (p) { return p[0].toUpperCase(); }).join('')) || '?'; }
  var AVC = [['#014653', '#fff'], ['#2E7B86', '#fff'], ['#7FBEC6', '#013a46'], ['#E6F0F1', '#014653'], ['#D99A2B', '#fff']];
  // Photo first; initials chip when there is no photo or the image fails to load.
  function fillAvatar(box, name, photo) {
    var ini = initials(name), h = 0, c;
    for (var i = 0; i < String(name || '').length; i++) h = (h * 31 + String(name).charCodeAt(i)) >>> 0;
    c = AVC[h % AVC.length]; box.style.background = c[0]; box.style.color = c[1];
    if (!photo) { box.textContent = ini; return box; }
    var img = new Image(); img.alt = name || ''; img.src = photo;
    img.onerror = function () { box.textContent = ini; };
    box.textContent = ''; box.appendChild(img);
    return box;
  }
  function getJson(url) { return fetch(url, { headers: JSON_HEAD, credentials: 'same-origin' }).then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); }); }
  function postJson(url, body) {
    return fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', body: JSON.stringify(body || {}) })
      .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { j.__ok = r.ok; return j; }); });
  }
  function toast(type, title, msg, opts) { if (window.wisdomToast) window.wisdomToast(type, title, msg, opts); }

  /* ---------- Leave — on leave today / upcoming (type colours = leave configuration) ---------- */
  (function () {
    var list = $('lvList'), L = D.leave || { today: [], up: [] };
    function tag(x) { var t = el('span', 'lv-type', x.t); t.style.color = x.c; t.style.background = x.c + '1F'; return t; }
    function head(x, meta) {
      var wrap = el('div', 'lv-item'), av = el('span', 'lv-av'), bd = el('div', 'lv-bd'), top = el('div', 'lv-top');
      fillAvatar(av, x.n, x.photo);
      top.appendChild(el('span', 'lv-nm', x.n)); top.appendChild(tag(x));
      bd.appendChild(top); bd.appendChild(el('div', 'lv-meta', meta));
      wrap.appendChild(av); wrap.appendChild(bd);
      return { wrap: wrap, bd: bd };
    }
    function render(v) {
      list.textContent = '';
      var rows = v === 'today' ? L.today : L.up;
      if (!rows.length) { list.appendChild(el('div', 'lv-empty', v === 'today' ? 'No one from your team is on leave today.' : 'No upcoming leave.')); return; }
      rows.forEach(function (x) {
        var h, p, bar, f, st;
        if (v === 'today') {
          h = head(x, (x.r ? x.r + ' · ' : '') + x.from + ' – ' + x.to);
          p = el('div', 'lv-prog'); bar = el('span', 'lv-bar'); bar.appendChild(document.createElement('i')).style.width = Math.min(100, Math.round(x.day / x.of * 100)) + '%';
          p.appendChild(bar); p.appendChild(el('span', 'lv-day', 'Day ' + x.day + ' of ' + x.of));
          h.bd.appendChild(p);
          var back = el('div', 'lv-back', 'Back '); back.appendChild(el('b', '', x.back)); h.bd.appendChild(back);
        } else {
          h = head(x, (x.r ? x.r + ' · ' : '') + x.dates + ' · ' + x.len);
          f = el('div', 'lv-foot'); f.appendChild(el('span', 'lv-when', x.in));
          st = el('span', 'lv-st ' + x.st); st.appendChild(document.createElement('i')); st.appendChild(document.createTextNode(x.st === 'ok' ? 'Approved' : 'Pending'));
          f.appendChild(st); h.bd.appendChild(f);
        }
        list.appendChild(h.wrap);
      });
    }
    Array.prototype.forEach.call(document.querySelectorAll('#ldmd .lv-tab'), function (b) {
      b.addEventListener('click', function () {
        Array.prototype.forEach.call(document.querySelectorAll('#ldmd .lv-tab'), function (x) { var on = x === b; x.classList.toggle('on', on); x.setAttribute('aria-selected', on); });
        render(b.getAttribute('data-v'));
      });
    });
    $('lvCtToday').textContent = L.today.length; $('lvCtUp').textContent = L.up.length; // same source as the lists
    render('today');
  })();

  /* leave card height = bottom of the team-today cards */
  (function () {
    var pay = $('leaveCard'), ref = $('teamToday');
    if (!pay || !ref) return;
    function align() {
      if (window.innerWidth <= 1100) { pay.style.height = ''; return; }
      pay.style.height = '';
      var h = ref.getBoundingClientRect().bottom - pay.getBoundingClientRect().top;
      if (h > 0) pay.style.height = h + 'px';
    }
    window.addEventListener('load', align);
    window.addEventListener('resize', align);
    requestAnimationFrame(align);
  })();

  /* ---------- To do: Performance items (server) + Time & Attendance items (existing to-do endpoint) ---------- */
  (function () {
    var list = $('tdList'), count = $('tdCount');
    function refresh() {
      var n = list.querySelectorAll('.td-item').length;
      count.textContent = n;
      if (!n && !list.querySelector('.empty')) list.appendChild(el('div', 'empty', 'Nothing to do right now.'));
    }
    function add(photo, name, sub, label, href, cls) {
      var item = el('div', 'td-item'), ico = el('span', 'td-ico'), bd = el('span', 'td-bd'), tt = el('span', 'td-tt', name), btn = el('a', 'td-btn' + (cls ? ' ' + cls : ''), label);
      fillAvatar(ico, name, photo); tt.appendChild(el('small', '', sub)); bd.appendChild(tt); btn.href = href;
      item.appendChild(ico); item.appendChild(bd); item.appendChild(btn);
      var e = list.querySelector('.empty'); if (e) e.remove();
      list.appendChild(item);
    }
    (D.todo || []).forEach(function (t) { add(t.photo, t.n, t.sub, t.label, t.url, t.cls); });
    refresh();
    getJson(U.todo + '?length=8').then(function (res) {
      (res.data || []).slice(0, 8).forEach(function (r) {
        var type = r.action_type, label, cls, sub;
        if (type === 'check_in') { label = 'Check-in'; cls = 'green'; sub = 'Pending check-in · ' + (r.StartTime || '') + ' shift'; }
        else if (type === 'check_out') { label = 'Check-out'; cls = 'red'; sub = 'Pending check-out · ' + (r.ExpectedEndTime || r.EndTime || ''); }
        else if (type === 'overtime_pending') { label = 'Approve'; cls = 'amber'; sub = 'Overtime pending'; }
        else return;
        var photo = r.profileImg ? (/^(https?:)?\/\//.test(r.profileImg) || r.profileImg[0] === '/' ? r.profileImg : '/' + r.profileImg) : '';
        add(photo, r.EmployeeName || '', sub, label, U.todoPage, cls);
      });
      refresh();
    }).catch(refresh);
  })();

  /* ---------- WAI ask → existing Wisdom chat endpoint (access tier is enforced server-side) ---------- */
  (function () {
    var focus = $('focus'), body = $('fpBody'), askInput = $('askInput'), askGo = $('askGo');
    var fpInput = $('fpInput'), fpGo = $('fpGo'), fpClose = $('fpClose'), askUse = $('askUse'), fpUse = $('fpUse'), busy = false, usageOut = false;
    // Daily allowance (same as the HR dashboard): the server sends `usage: {limit, used, remaining}` on chat + history; nothing is shown without it.
    function applyUsage(u, fromEvent) {
      if (!u || !(u.limit > 0)) return;
      var rem = Math.max(0, u.remaining != null ? u.remaining : u.limit - (u.used || 0));
      var low = rem > 0 && rem <= Math.max(5, Math.ceil(u.limit * 0.2));
      var txt = rem === 0 ? 'Daily limit reached. Try again later.'
        : low ? 'Only ' + rem + ' message' + (rem === 1 ? '' : 's') + ' left today'
        : rem + ' of ' + u.limit + ' messages left today';
      usageOut = rem === 0;
      [askUse, fpUse].forEach(function (n) { n.hidden = false; n.textContent = txt; n.className = (n.id === 'askUse' ? 'wai-use' : 'fp-use') + (usageOut ? ' is-out' : low ? ' is-low' : ''); });
      [askInput, fpInput, askGo, fpGo].forEach(function (n) { n.disabled = usageOut; });
      if (!fromEvent) document.dispatchEvent(new CustomEvent('wai:usage', { detail: u })); // keeps the floating chat in step
    }
    document.addEventListener('wai:usage', function (e) { applyUsage(e.detail, true); });
    fetch(U.waiHistory, { headers: JSON_HEAD, credentials: 'same-origin' })
      .then(function (r) { return r.json().catch(function () { return {}; }); })
      .then(function (j) { if (j && j.usage) applyUsage(j.usage); })
      .catch(function () {});
    function thinking() {
      var w = el('div', 'loader-wrapper'), l = el('div', 'letter-wrapper');
      w.setAttribute('role', 'status'); w.setAttribute('aria-label', 'Thinking');
      w.appendChild(el('div', 'loader'));
      'Thinking'.split('').forEach(function (c) { l.appendChild(el('span', 'loader-letter', c)); });
      w.appendChild(l);
      return w;
    }
    function answer(a, j) {
      var text = j.success ? j.reply : (j.message || 'Something went wrong. Please try again.');
      a.textContent = '';
      // Optional labels from the endpoint, rendered only when it sends them (see backend notes): label = "Company policy · leave", source = citation.
      if (j.success && j.label) a.appendChild(el('div', 'a-tag', j.label));
      var md = el('div', 'a-md');
      if (window.WaiMd) window.WaiMd.render(md, text); else md.textContent = text;
      a.appendChild(md);
      if (j.success && j.source) { var s = el('div', 'a-src', 'Source · '); s.appendChild(el('b', '', j.source)); a.appendChild(s); }
    }
    function exchange(q) {
      busy = true;
      body.appendChild(el('div', 'q', q));
      var a = el('div', 'a'); a.appendChild(thinking()); body.appendChild(a);
      body.scrollTop = body.scrollHeight;
      postJson(U.wai, { message: q }).then(function (j) { if (j && j.usage) applyUsage(j.usage); answer(a, j); })
        .catch(function () { answer(a, { success: false, message: 'Something went wrong. Please try again.' }); })
        .then(function () { busy = false; body.scrollTop = body.scrollHeight; });
    }
    function open(q) { body.textContent = ''; focus.classList.add('open'); document.body.style.overflow = 'hidden'; exchange(q); setTimeout(function () { fpInput.focus(); }, 60); }
    function close() { focus.classList.remove('open'); document.body.style.overflow = ''; askInput.value = ''; }
    function submitAsk() { var v = (askInput.value || '').trim(); if (v && !busy && !usageOut) open(v); }
    function submitFp() { var v = (fpInput.value || '').trim(); if (v && !busy && !usageOut) { exchange(v); fpInput.value = ''; } }
    askGo.addEventListener('click', submitAsk);
    askInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') submitAsk(); });
    Array.prototype.forEach.call(document.querySelectorAll('#ldmd .wai-glass .hchip'), function (ch) {
      function go() { if (!busy && !usageOut) open(ch.textContent.trim()); }
      ch.addEventListener('click', go);
      ch.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); go(); } });
    });
    fpGo.addEventListener('click', submitFp);
    fpInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') submitFp(); });
    fpClose.addEventListener('click', close);
    focus.addEventListener('click', function (e) { if (e.target === focus) close(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && focus.classList.contains('open')) close(); });
  })();

  /* glass specular follows the cursor */
  (function () {
    var g = $('waiGlass'); if (!g) return;
    g.addEventListener('pointermove', function (e) { var r = g.getBoundingClientRect(); g.style.setProperty('--mx', ((e.clientX - r.left) / r.width * 100) + '%'); g.style.setProperty('--my', ((e.clientY - r.top) / r.height * 100) + '%'); });
    g.addEventListener('pointerleave', function () { g.style.setProperty('--mx', '75%'); g.style.setProperty('--my', '-10%'); });
  })();

  /* ---------- Duty roster timeline (needs the roster endpoint; empty state until it exists) ---------- */
  (function () {
    var body = $('tlBody'), pos = $('tlPos'), date = $('tlDate'), now = $('tlNow'), lbl = $('tlNowLbl'), cnt = $('tlCount'), leg = $('tlLeg');
    var rows = [], serverOffset = 0;
    function hm(s) { var p = String(s || '').split(':'); return (+p[0] || 0) + (+p[1] || 0) / 60; }
    function pad(n) { return String(n).padStart(2, '0'); }
    function nowHours() { var d = new Date(); return ((d.getHours() + d.getMinutes() / 60 + serverOffset) % 24 + 24) % 24; }
    // Server clock (app timezone) minus browser clock, so the Now line follows the property's time, not the viewer's.
    serverOffset = hm(D.nowHm) - (new Date().getHours() + new Date().getMinutes() / 60);
    function tick() {
      var isToday = date.value === D.today, h = nowHours();
      now.style.display = isToday ? '' : 'none';
      now.style.setProperty('--t', h);
      lbl.textContent = 'Now ' + pad(Math.floor(h)) + ':' + pad(Math.floor(h % 1 * 60));
    }
    function legend(list) {
      leg.textContent = '';
      var seen = {};
      function item(sw, text, st) { var s = el('span'), i = el('i'); if (st) i.setAttribute('style', st); else i.style.background = sw; s.appendChild(i); s.appendChild(document.createTextNode(text)); leg.appendChild(s); }
      list.forEach(function (r) {
        if (r.state === 'lv' || r.state === 'miss' || seen[r.shift]) return; seen[r.shift] = 1;
        item(r.tone === 'am' ? 'var(--teal)' : '#CFE0E3', r.shift + ' · ' + pad(Math.floor(r.start)) + ':' + pad(Math.round(r.start % 1 * 60)) + '–' + pad(Math.floor(r.end)) + ':' + pad(Math.round(r.end % 1 * 60)));
      });
      item('', 'No check-in', 'background:#fff;border:1px dashed rgba(229,87,63,.6)');
      item('', 'On leave', 'background:rgba(107,95,199,.16)');
      item('var(--lime)', 'Now');
    }
    function render() {
      var f = pos.value, list = rows.filter(function (r) { return f === 'all' || r.position === f; });
      cnt.textContent = list.length + ' rostered';
      body.textContent = '';
      if (!list.length) { body.appendChild(el('div', 'tl-empty', U.roster ? 'No one rostered for this position on this date.' : 'The duty roster is not connected to this dashboard yet.')); legend([]); return; }
      list.forEach(function (r) {
        var row = el('div', 'tl-row'), who = el('div', 'tl-who'), av = el('span', 'cmp-av'), main = el('div', 'rs-main'), track = el('div', 'tl-track');
        var s = Math.max(0, Math.min(24, +r.start || 0)), e = Math.max(0, Math.min(24, +r.end || 24)); if (e <= s) e = 24;
        var cls = r.state === 'miss' ? 'miss' : r.state === 'lv' ? 'lv' : r.tone, txt = r.state === 'miss' ? 'No check-in' : r.state === 'lv' ? 'On leave' : r.shift;
        fillAvatar(av, r.name, r.photo);
        main.appendChild(el('div', 'rs-nm', r.name)); main.appendChild(el('div', 'rs-ps', r.position));
        who.appendChild(av); who.appendChild(main);
        var bar = el('div', 'tl-bar ' + cls, txt); bar.style.setProperty('--s', s); bar.style.setProperty('--e', e);
        if (r.tip) bar.setAttribute('data-tip', r.tip);
        track.appendChild(bar); row.appendChild(who); row.appendChild(track); body.appendChild(row);
      });
      legend(list);
    }
    function load() {
      if (!U.roster) { rows = []; render(); return; }
      body.textContent = ''; body.appendChild(el('div', 'tl-empty', 'Loading…'));
      getJson(U.roster + '?date=' + encodeURIComponent(date.value)).then(function (res) {
        rows = (res.rows || []).map(function (r) { r.start = +r.start; r.end = +r.end; r.tone = r.tone || (r.start < 12 ? 'am' : 'pm'); return r; });
        var cur = pos.value, seen = {}; pos.textContent = ''; pos.appendChild(new Option('All positions', 'all'));
        rows.forEach(function (r) { if (!seen[r.position]) { seen[r.position] = 1; pos.appendChild(new Option(r.position, r.position)); } });
        pos.value = seen[cur] ? cur : 'all';
        render(); tick();
      }).catch(function () { rows = []; body.textContent = ''; body.appendChild(el('div', 'tl-empty', 'Could not load the roster. Please try again.')); });
    }
    pos.addEventListener('change', render);
    date.addEventListener('change', function () { load(); tick(); });
    load(); tick(); setInterval(tick, 60000);
  })();

  /* ---------- Action inbox ---------- */
  (function () {
    var list = $('aiList'), ITEMS = (D.inbox || []).slice(), filt = 'all';
    var SVG = {
      ok: 'M20 6L9 17l-5-5', no: 'M18 6L6 18M6 6l12 12', chev: 'M9 18l6-6-6-6', pen: 'M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z',
      eye: 'M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z'
    };
    function icon(k, sw) {
      var s = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
      s.setAttribute('viewBox', '0 0 24 24'); s.setAttribute('fill', 'none'); s.setAttribute('stroke', 'currentColor'); s.setAttribute('stroke-width', sw || '2.6'); s.setAttribute('stroke-linecap', 'round'); s.setAttribute('stroke-linejoin', 'round'); s.setAttribute('aria-hidden', 'true');
      var p = document.createElementNS('http://www.w3.org/2000/svg', 'path'); p.setAttribute('d', SVG[k]); s.appendChild(p);
      if (k === 'eye') { var c = document.createElementNS('http://www.w3.org/2000/svg', 'circle'); c.setAttribute('cx', '12'); c.setAttribute('cy', '12'); c.setAttribute('r', '3'); s.appendChild(c); }
      return s;
    }
    var TL = { res: 'Resignation', req: 'Request', exit: 'Exit clearance' };
    function btn(cls, label, k, attrs) {
      var b = el(attrs.href ? 'a' : 'button', cls); if (!attrs.href) b.type = 'button';
      Object.keys(attrs).forEach(function (a) { b.setAttribute(a, attrs[a]); });
      b.appendChild(icon(k, '2.6'));
      return b;
    }
    function row(x) {
      var r = el('div', 'ai-row'), t = el('span'), pp = el('div', 'ai-pp'), av = el('span', 'ai-av'), who = el('div'), md = el('div', 'ai-md'), ac = el('div', 'ai-ac');
      t.appendChild(el('span', 'ai-tag ' + x.t, TL[x.t]));
      fillAvatar(av, x.n, x.photo); who.style.minWidth = '0';
      who.appendChild(el('div', 'ai-nm', x.n)); who.appendChild(el('div', 'ai-rl', x.r));
      pp.appendChild(av); pp.appendChild(who);
      md.appendChild(document.createTextNode(x.d));
      var mt = el('div', 'ai-mt', x.m || '');
      if (x.dl) { if (x.m) mt.appendChild(document.createTextNode(' · ')); mt.appendChild(el('span', x.od ? 'ai-od' : '', x.dl)); }
      md.appendChild(mt);
      if (x.t === 'req') {
        if (x.view) ac.appendChild(btn('ai-icb vw', '', 'eye', { href: x.view, title: 'View', 'aria-label': 'View request' }));
        if (x.ok) ac.appendChild(btn('ai-icb ok', '', 'ok', { title: 'Approve', 'aria-label': 'Approve', 'data-do': 'ok', 'data-i': ITEMS.indexOf(x) }));
        if (x.no) ac.appendChild(btn('ai-icb no', '', 'no', { title: 'Reject', 'aria-label': 'Reject', 'data-do': 'no', 'data-i': ITEMS.indexOf(x) }));
      } else {
        var a = el('a', 'ai-act'); a.href = x.url;
        if (x.t === 'res') { a.appendChild(document.createTextNode('Review & approve')); a.appendChild(icon('chev', '2.4')); }
        else { a.appendChild(icon('pen', '2')); a.appendChild(document.createTextNode('Fill form')); }
        ac.appendChild(a);
      }
      r.appendChild(t); r.appendChild(pp); r.appendChild(md); r.appendChild(ac);
      return r;
    }
    function counts() {
      var c = { all: ITEMS.length, res: 0, req: 0, exit: 0 }; ITEMS.forEach(function (x) { c[x.t]++; });
      Array.prototype.forEach.call(document.querySelectorAll('#ldmd [data-c]'), function (b) { b.textContent = c[b.getAttribute('data-c')]; });
      $('aiCnt').textContent = c.all;
    }
    function render() {
      list.textContent = '';
      var rows = ITEMS.filter(function (x) { return filt === 'all' || x.t === filt; });
      if (!rows.length) {
        var e = el('div', 'ai-empty'), ei = el('span', 'ei'); ei.appendChild(icon('ok')); e.appendChild(ei); e.appendChild(el('b', '', 'All caught up')); e.appendChild(document.createTextNode('Nothing waiting on you here.'));
        list.appendChild(e); return;
      }
      rows.forEach(function (x) { list.appendChild(row(x)); });
    }
    $('aiFilters').addEventListener('click', function (e) {
      var b = e.target.closest('.ai-fc'); if (!b) return; filt = b.getAttribute('data-f');
      Array.prototype.forEach.call(document.querySelectorAll('#ldmd .ai-fc'), function (x) { x.classList.toggle('on', x === b); });
      render();
    });
    list.addEventListener('click', function (e) {
      var b = e.target.closest('[data-do]'); if (!b || b.disabled) return;
      var x = ITEMS[+b.getAttribute('data-i')], d = b.getAttribute('data-do'), ok = d === 'ok', rowEl = b.closest('.ai-row');
      b.disabled = true;
      postJson(ok ? x.ok : x.no, { id: x.id }).then(function (j) {
        if (!j.__ok || j.success === false) { b.disabled = false; toast('err', 'Couldn’t ' + (ok ? 'approve' : 'reject') + ' request', j.message || 'Nothing was changed. Please try again.'); return; }
        rowEl.classList.add('gone');
        setTimeout(function () {
          var k = ITEMS.indexOf(x); if (k > -1) ITEMS.splice(k, 1); counts(); render();
          var opts = x.undo ? { undo: function () {
            postJson(x.undo, { id: x.id }).then(function (u) {
              if (!u.__ok || u.success === false) { toast('err', 'Couldn’t undo', u.message || 'Please try again.'); return; }
              ITEMS.splice(Math.min(k, ITEMS.length), 0, x); counts(); render(); toast('info', 'Action undone', x.n + '’s request is back in your inbox.');
            });
          } } : {};
          toast(ok ? 'ok' : 'info', ok ? 'Request approved' : 'Request rejected', x.n + '’s request ' + (x.r || '').replace('Request ', '') + ' has been ' + (ok ? 'approved.' : 'rejected.'), opts);
        }, 280);
      }).catch(function () { b.disabled = false; toast('err', 'Couldn’t ' + (ok ? 'approve' : 'reject') + ' request', 'Nothing was changed. Please try again.'); });
    });
    render(); counts();
  })();

  /* ---------- Calendar + upcoming events ---------- */
  (function () {
    var mg = $('mg'), mo = $('calMo'), up = $('upList'), EV = D.events || [];
    var MN = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    var MS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    var t = D.today.split('-'), TY = +t[0], TM = +t[1] - 1, TD = +t[2], y = TY, m = TM;
    var byDate = {}; EV.forEach(function (e) { (byDate[e.date] = byDate[e.date] || []).push(e); });
    function key(yy, mm, dd) { return yy + '-' + String(mm + 1).padStart(2, '0') + '-' + String(dd).padStart(2, '0'); }
    function grid() {
      mo.textContent = MN[m] + ' ' + y; mg.textContent = '';
      var first = new Date(y, m, 1).getDay(), dim = new Date(y, m + 1, 0).getDate(), prev = new Date(y, m, 0).getDate(), i, d;
      function cell(txt, cls, dot) { var c = el('div', 'dcell' + (cls ? ' ' + cls : ''), txt); if (dot) c.appendChild(el('span', 'evd')); mg.appendChild(c); }
      for (i = 0; i < first; i++) cell(prev - first + 1 + i, 'mut');
      for (d = 1; d <= dim; d++) {
        var isToday = y === TY && m === TM && d === TD, has = !!byDate[key(y, m, d)];
        cell(d, isToday ? 'today' : has ? 'ev' : '', isToday || has);
      }
      var trail = (7 - (first + dim) % 7) % 7; for (i = 1; i <= trail; i++) cell(i, 'mut');
    }
    function avatars(e) {
      var ppl = e.people || []; if (!ppl.length) return null;
      if (ppl.length === 1) return fillAvatar(el('span', 'av'), ppl[0].n, ppl[0].p);
      var st = el('span', 'stack'); ppl.slice(0, 2).forEach(function (p) { st.appendChild(fillAvatar(el('span', 'av'), p.n, p.p)); });
      return st;
    }
    function upcoming() {
      up.textContent = '';
      var t0 = new Date(TY, TM, TD), list = EV.filter(function (e) { return new Date(e.date + 'T00:00:00') >= t0; }).slice(0, 12);
      if (!list.length) { up.appendChild(el('div', 'empty', 'No upcoming events.')); return; }
      list.forEach(function (e) {
        var d = new Date(e.date + 'T00:00:00'), days = Math.round((d - t0) / 86400000);
        var r = el('div', 'ue'), chip = el('div', 'dchip'), ub = el('div', 'ub'), right = el('div', 'ue-right');
        chip.appendChild(el('div', 'dd tnum', d.getDate())); chip.appendChild(el('div', 'dm', MS[d.getMonth()]));
        var ut = el('div', 'ut', e.title); ut.title = e.title + (e.sub ? ' — ' + e.sub : ''); ub.appendChild(ut); if (e.sub) ub.appendChild(el('div', 'us', e.sub));
        var av = avatars(e); if (av) right.appendChild(av);
        right.appendChild(el('span', 'uw ' + (days === 0 ? 'today' : days <= 3 ? 'soon' : ''), days === 0 ? 'Today' : days <= 3 ? days + (days === 1 ? ' day' : ' days') : d.getDate() + ' ' + MS[d.getMonth()]));
        r.appendChild(chip); r.appendChild(ub); r.appendChild(right); up.appendChild(r);
      });
    }
    $('calPrev').addEventListener('click', function () { m--; if (m < 0) { m = 11; y--; } grid(); });
    $('calNext').addEventListener('click', function () { m++; if (m > 11) { m = 0; y++; } grid(); });
    grid(); upcoming();
  })();
})();
