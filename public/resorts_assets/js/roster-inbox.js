/* Duty roster timeline + Action inbox — shared by the Leader dashboard (leader-dashboard.js) and the HR dashboard
 * (resorts/master-dashboard/hrdashboard.blade.php). Both pages render the same element ids (tlBody/tlPos/tlDate/…,
 * aiList/aiFilters/aiCnt) and keep their own styles. Roster rows come from LeaderDashboard::roster(), inbox items are
 * built server-side. All text is inserted with textContent, never as HTML.
 *
 *   mountRosterInbox({ root, rosterUrl, today, nowHm, inbox, fillAvatar: function (box, name, photo) {} })
 */
window.mountRosterInbox = function (o) {
  'use strict';
  var root = o.root, fillAvatar = o.fillAvatar;
  var $ = function (id) { return document.getElementById(id); };
  var CSRF = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  function el(tag, cls, text) { var e = document.createElement(tag); if (cls) e.className = cls; if (text != null) e.textContent = text; return e; }
  function getJson(url) { return fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' }).then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); }); }
  function postJson(url, body) {
    return fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', body: JSON.stringify(body || {}) })
      .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { j.__ok = r.ok; return j; }); });
  }
  function toast(type, title, msg, opts) { if (window.wisdomToast) window.wisdomToast(type, title, msg, opts); }

  /* ---------- Duty roster timeline (needs the roster endpoint; empty state until it exists) ---------- */
  (function () {
    var body = $('tlBody'), pos = $('tlPos'), date = $('tlDate'), now = $('tlNow'), lbl = $('tlNowLbl'), cnt = $('tlCount'), leg = $('tlLeg');
    if (!body) return;
    var rows = [], serverOffset = 0;
    function hm(s) { var p = String(s || '').split(':'); return (+p[0] || 0) + (+p[1] || 0) / 60; }
    function pad(n) { return String(n).padStart(2, '0'); }
    function nowHours() { var d = new Date(); return ((d.getHours() + d.getMinutes() / 60 + serverOffset) % 24 + 24) % 24; }
    // Server clock (app timezone) minus browser clock, so the Now line follows the property's time, not the viewer's.
    serverOffset = hm(o.nowHm) - (new Date().getHours() + new Date().getMinutes() / 60);
    function tick() {
      var isToday = date.value === o.today, h = nowHours();
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
      item('var(--lime,#E0FF02)', 'Now');
    }
    function render() {
      var f = pos.value, list = rows.filter(function (r) { return f === 'all' || r.position === f; });
      cnt.textContent = list.length + ' rostered';
      body.textContent = '';
      if (!list.length) { body.appendChild(el('div', 'tl-empty', o.rosterUrl ? 'No one rostered for this position on this date.' : 'The duty roster is not connected to this dashboard yet.')); legend([]); return; }
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
      if (!o.rosterUrl) { rows = []; render(); return; }
      body.textContent = ''; body.appendChild(el('div', 'tl-empty', 'Loading…'));
      getJson(o.rosterUrl + '?date=' + encodeURIComponent(date.value)).then(function (res) {
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

  /* ---------- Action inbox (resignations, leave/overtime requests, exit clearance) ---------- */
  (function () {
    var list = $('aiList'); if (!list) return;
    var ITEMS = (o.inbox || []).slice(), filt = 'all';
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
      Array.prototype.forEach.call(root.querySelectorAll('[data-c]'), function (b) { b.textContent = c[b.getAttribute('data-c')]; });
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
      Array.prototype.forEach.call(root.querySelectorAll('.ai-fc'), function (x) { x.classList.toggle('on', x === b); });
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
};
