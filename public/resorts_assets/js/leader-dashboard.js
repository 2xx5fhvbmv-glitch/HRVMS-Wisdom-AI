/* Leader master dashboard (non-HR HOD / ExCom / GM) — behaviour for resorts/master-dashboard/leaderdashboard.blade.php.
 * Data comes from window.LD (built server-side from the controller's own variables). All text is inserted with
 * textContent, never as HTML. Duty roster + Action inbox live in roster-inbox.js (shared with the HR dashboard).
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
      askUse.hidden = false; askUse.textContent = txt; askUse.className = 'wai-use' + (usageOut ? ' is-out' : low ? ' is-low' : '');
      // follow-up field: "N left" inside the field, full sentence as the tooltip
      fpUse.hidden = false; fpUse.className = 'wai-quota' + (usageOut ? ' is-out' : rem <= 10 ? ' is-low' : '');
      fpUse.setAttribute('data-tip', usageOut ? txt : rem + ' of ' + u.limit + ' messages left today'); window.WaiMd.roll($('fpUseN'), rem);
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
      if (window.WaiMd) window.WaiMd.render(md, text, { thread: body, reveal: true }); else md.textContent = text;
      a.appendChild(md);
      if (j.success && j.source) { var s = el('div', 'a-src', 'Source · '); s.appendChild(el('b', '', j.source)); a.appendChild(s); }
    }
    function exchange(q) {
      busy = true;
      var qb = el('div', 'q', q); body.appendChild(qb); window.WaiMd.tideIn(qb);
      var a = el('div', 'a'); a.appendChild(thinking()); body.appendChild(a); window.WaiMd.tideIn(a, 120);
      body.scrollTop = body.scrollHeight;
      postJson(U.wai, { message: q }).then(function (j) { if (j && j.usage) applyUsage(j.usage); answer(a, j); })
        .catch(function () { answer(a, { success: false, message: 'Something went wrong. Please try again.' }); })
        .then(function () { busy = false; body.scrollTop = body.scrollHeight; });
    }
    function open(q) { body.textContent = ''; body._waiSubj = null; focus.classList.add('open'); document.body.style.overflow = 'hidden'; exchange(q); setTimeout(function () { fpInput.focus(); }, 60); }
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

  /* ---------- Duty roster timeline + Action inbox (shared with the HR dashboard: roster-inbox.js) ---------- */
  window.mountRosterInbox({ root: root, rosterUrl: U.roster, today: D.today, nowHm: D.nowHm, inbox: D.inbox, fillAvatar: fillAvatar });

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
