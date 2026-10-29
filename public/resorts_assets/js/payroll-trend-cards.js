/* Payroll dashboard — OT Trend, Pension, Tax and Budget Comparison cards (HTML/SVG, no Chart.js).
 * Data: the page's existing GET endpoints only (window.PTC.urls). Anything an endpoint cannot give us is hidden, never invented.
 * "Processed" = on or before the month of the last committed payroll (the same payroll the Last Payroll KPI shows), and,
 * for OT / Budget, with a value above zero. Months after that are hatched, never plotted as 0.
 * All colours live in payroll-trend-cards.css (tokens); text is inserted with textContent / numbers only.
 */
(function () {
  'use strict';
  var C = window.PTC || {}, U = C.urls || {};
  var lite = (window.matchMedia && matchMedia('(prefers-reduced-motion:reduce)').matches) || (navigator.connection && navigator.connection.saveData) || (navigator.hardwareConcurrency || 4) <= 2;
  var TIDE = 'cubic-bezier(.16,1,.3,1)', SWELL = 'cubic-bezier(.34,1.56,.64,1)', NS = 'http://www.w3.org/2000/svg';
  var M = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  var ML = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  var CUR = String(window.currencySymbol || '$').trim();
  function $(id) { return document.getElementById(id); }
  function f(n, d) { return Number(n).toLocaleString('en-US', { minimumFractionDigits: d || 0, maximumFractionDigits: d || 0 }); }
  function cv(n) { return typeof window.convertAmount === 'function' ? window.convertAmount(n, 'USD') : +n; } // stored USD -> display currency
  function money(n, d) { return CUR + f(cv(n), d); }
  function el(tag, cls, text) { var e = document.createElement(tag); if (cls) e.className = cls; if (text != null) e.textContent = text; return e; }
  function svgEl(tag, attrs) { var e = document.createElementNS(NS, tag); Object.keys(attrs || {}).forEach(function (k) { e.setAttribute(k, attrs[k]); }); return e; }
  function anim(e, k, o) { return lite || !e || !e.animate ? null : e.animate(k, o); }
  function proc(y, i) { return C.lastY != null && (y < C.lastY || (y === C.lastY && i < C.lastM)); }
  function getJson(url, params) {
    var q = Object.keys(params).map(function (k) { return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]); }).join('&');
    return fetch(url + '?' + q, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
      .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); });
  }
  function nice(v) { if (!(v > 0)) return 100; var p = Math.pow(10, Math.floor(Math.log10(v))), m = v / p; var st = [1, 1.5, 2, 2.5, 3, 4, 5, 6, 8, 10].filter(function (x) { return m <= x; })[0]; return st * p; }

  /* ---------- the living hero ---------- */
  function countUp(node, to, dec) {
    var fin = f(to, dec);
    if (lite) { node.textContent = fin; return; }
    var t0 = performance.now();
    (function s(t) { var p = Math.min(1, (t - t0) / 900), e = 1 - Math.pow(1 - p, 3); node.textContent = f(to * e, dec); if (p < 1) requestAnimationFrame(s); else node.textContent = fin; })(t0);
  }
  function tell(card, s, mode) {
    var q = function (k) { return card.querySelector('[data-' + k + ']'); }, hm = card.querySelector('.hm'), side = q('side'), em = q('empty');
    if (s.empty) { hm.hidden = true; side.hidden = true; em.hidden = false; em.textContent = s.empty; return; }
    em.hidden = true; hm.hidden = false;
    q('cap').textContent = s.cap; q('cur').textContent = s.cur || ''; q('u').textContent = s.u || '';
    var ch = q('chip');
    if (s.chip) { ch.hidden = false; ch.className = 'chip ' + s.chip[1]; ch.textContent = s.chip[0]; } else ch.hidden = true;
    if (s.sk) { side.hidden = false; q('sk').textContent = s.sk; q('sv').textContent = s.sv; } else side.hidden = true;
    var n = q('n');
    if (mode === 'count' && s.v != null) countUp(n, s.v, s.dec || 0); else n.textContent = s.n;
    if (mode === 'swap') [n.parentNode, q('cap'), q('sv')].forEach(function (e) { e.getAnimations().forEach(function (a) { a.cancel(); }); anim(e, [{ opacity: .2, transform: 'translateY(5px)' }, { opacity: 1, transform: 'none' }], { duration: 260, easing: TIDE }); });
  }
  // hover / focus on any item re-tells the hero; the others dim; leaving restores the summary
  function hoverable(card, viz, items, stateFor, summary, xl) {
    var cur = -1;
    function lab(i) { if (xl) Array.prototype.forEach.call(xl.children, function (e, k) { e.classList.toggle('on', k === i); }); }
    function on(i) { if (i === cur) return; cur = i; viz.classList.add('hot'); items.forEach(function (e, k) { e.classList.toggle('on', k === i); }); lab(i); tell(card, stateFor(i), 'swap'); }
    function off() { if (cur < 0) return; cur = -1; viz.classList.remove('hot'); items.forEach(function (e) { e.classList.remove('on'); }); lab(-1); tell(card, summary(), 'swap'); }
    items.forEach(function (e, i) { e.addEventListener('pointerenter', function () { on(i); }); e.addEventListener('focus', function () { on(i); }); e.addEventListener('blur', off); });
    viz.onpointerleave = off;
  }
  function months(box) { box.textContent = ''; M.forEach(function (m) { box.appendChild(el('span', '', m)); }); }
  function none(y) { return 'No payroll processed for ' + y; }

  /* =================== OT Trend =================== */
  function smooth(p) {
    var d = 'M' + p[0][0] + ',' + p[0][1];
    for (var i = 0; i < p.length - 1; i++) {
      var p0 = p[i - 1] || p[i], p1 = p[i], p2 = p[i + 1], p3 = p[i + 2] || p2, t = .18;
      d += ' C' + (p1[0] + (p2[0] - p0[0]) * t) + ',' + (p1[1] + (p2[1] - p0[1]) * t) + ' ' + (p2[0] - (p3[0] - p1[0]) * t) + ',' + (p2[1] - (p3[1] - p1[1]) * t) + ' ' + p2[0] + ',' + p2[1];
    }
    return d;
  }
  var otLast = null;
  function ot(first) {
    var card = $('ptcOt'), y = +card.querySelector('select').value;
    Promise.all([getJson(U.ot, { year: y }), getJson(U.ot, { year: y - 1 }).catch(function () { return null; })]).then(function (r) {
      var d = (r[0].data || []).map(Number), p = r[1] && (r[1].data || []).map(Number);
      if (!p || !p.some(function (v) { return v > 0; })) p = null;
      var n = 0; d.forEach(function (v, i) { if (proc(y, i) && v > 0) n = i + 1; });
      otLast = { card: card, y: y, d: d, p: p, n: n };
      drawOt(first);
    }).catch(function () { tell(card, { empty: 'Could not load OT data.' }); });
  }
  function drawOt(first) {
    var o = otLast; if (!o) return;
    var card = o.card, box = $('ptcOtViz'), y = o.y, d = o.d, p = o.p, n = o.n;
    $('ptcOtL1').textContent = y; $('ptcOtL2').textContent = y - 1; $('ptcOtL2w').style.display = p ? '' : 'none';
    var W = Math.max(240, box.clientWidth || 300), H = 190, pl = 30, pr = 8, pt = 30, pb = 24;
    var top = nice(Math.max.apply(null, d.concat(p || [0], [1])) * 1.05), step = (W - pl - pr) / 11;
    function X(i) { return pl + i * step; } function Y(v) { return pt + (H - pt - pb) * (1 - v / top); }
    var base = Y(0), svg = svgEl('svg', { viewBox: '0 0 ' + W + ' ' + H, role: 'img', 'aria-label': 'Overtime hours by month, ' + y });
    var defs = svgEl('defs'), g = svgEl('linearGradient', { id: 'ptcOtG', x1: 0, y1: 0, x2: 0, y2: 1 }), pat = svgEl('pattern', { id: 'ptcOtH', width: 6, height: 6, patternUnits: 'userSpaceOnUse', patternTransform: 'rotate(45)' });
    [['0', '.75'], ['.55', '.28'], ['1', '0']].forEach(function (s) { var st = svgEl('stop', { offset: s[0], 'stop-opacity': s[1] }); st.style.stopColor = 'var(--lime-1)'; g.appendChild(st); });
    pat.appendChild(svgEl('rect', { width: 6, height: 6, 'class': 'hb' })); pat.appendChild(svgEl('rect', { width: 2, height: 6, 'class': 'hs' })); defs.appendChild(g); defs.appendChild(pat); svg.appendChild(defs);
    [.4, .8].map(function (k) { var v = top * k; return top >= 100 ? Math.round(v / 10) * 10 : Math.round(v); }).forEach(function (v) {
      svg.appendChild(svgEl('line', { 'class': 'gl', x1: pl, x2: W - pr, y1: Y(v), y2: Y(v) }));
      var t = svgEl('text', { 'class': 'gt', x: 0, y: Y(v) + 3.5 }); t.textContent = v + 'h'; svg.appendChild(t);
    });
    var npX = n === 0 ? pl - step / 2 : n < 12 ? X(n - 1) + step / 2 : null;
    if (npX != null) {
      svg.appendChild(svgEl('rect', { x: npX + 3, y: pt - 6, width: W - pr - npX - 3, height: base - pt + 6, rx: 8, fill: 'url(#ptcOtH)' }));
      if (W - pr - npX > 70) { var nt = svgEl('text', { 'class': 'np-t', x: (npX + W - pr) / 2, y: pt + 8, 'text-anchor': 'middle' }); nt.textContent = 'Not processed'; svg.appendChild(nt); }
    }
    var ln, ar, cg, ly, pkW = 0, pkX = 0, pkY = 0;
    if (p) { ly = svgEl('path', { 'class': 'ly', d: smooth(p.map(function (v, i) { return [X(i), Y(v)]; })) }); svg.appendChild(ly); }
    if (n > 0) {
      var pts = d.slice(0, n).map(function (v, i) { return [X(i), Y(v)]; }), line = n > 1 ? smooth(pts) : 'M' + pts[0][0] + ',' + pts[0][1] + ' L' + (pts[0][0] + .01) + ',' + pts[0][1];
      ar = svgEl('path', { 'class': 'ar', d: line + ' L' + X(n - 1) + ',' + base + ' L' + X(0) + ',' + base + ' Z', fill: 'url(#ptcOtG)' });
      ln = svgEl('path', { 'class': 'ln', d: line, pathLength: 1 });
      svg.appendChild(ar); svg.appendChild(ln);
      var pk = d.slice(0, n).indexOf(Math.max.apply(null, d.slice(0, n))), pkT = 'Peak · ' + M[pk];
      pkW = pkT.length * 6.3 + 18; pkX = Math.max(pl, Math.min(W - pr - pkW, X(pk) - pkW / 2)); pkY = Math.max(0, Y(d[pk]) - 30);
      cg = svgEl('g', { 'class': 'cap-g' }); cg.appendChild(svgEl('rect', { x: pkX, y: pkY, width: pkW, height: 18, rx: 9 }));
      var ct = svgEl('text', { x: pkX + pkW / 2, y: pkY + 12.5, 'text-anchor': 'middle' }); ct.textContent = pkT; cg.appendChild(ct); svg.appendChild(cg);
    }
    var guide = svgEl('line', { 'class': 'guide', x1: 0, x2: 0, y1: pt - 6, y2: base }), dot = svgEl('circle', { 'class': 'dot', r: 4.5, cx: 0, cy: 0 });
    svg.appendChild(guide); svg.appendChild(dot);
    var mts = M.map(function (m, i) { var t = svgEl('text', { 'class': 'mt', x: X(i), y: H - 4, 'text-anchor': 'middle' }); t.textContent = m; svg.appendChild(t); return t; });
    var cur = -1;
    M.forEach(function (m, i) {
      var h = svgEl('rect', { 'class': 'hit', x: X(i) - step / 2, y: 0, width: step, height: H });
      h.addEventListener('pointerenter', function () {
        if (i === cur) return; cur = i; box.classList.add('hot');
        guide.setAttribute('x1', X(i)); guide.setAttribute('x2', X(i)); dot.style.display = i < n ? '' : 'none';
        if (i < n) { dot.setAttribute('cx', X(i)); dot.setAttribute('cy', Y(d[i])); }
        mts.forEach(function (t, k) { t.classList.toggle('on', k === i); }); tell(card, month(i), 'swap');
      });
      svg.appendChild(h);
    });
    svg.addEventListener('pointerleave', function () { cur = -1; box.classList.remove('hot'); mts.forEach(function (t) { t.classList.remove('on'); }); tell(card, summary(), 'swap'); });
    box.textContent = ''; box.appendChild(svg);
    function hrs(v) { return f(v, v % 1 ? 1 : 0); }
    function summary() {
      if (n === 0) return { empty: none(y) };
      var sum = d.slice(0, n).reduce(function (a, b) { return a + b; }, 0), chip = null;
      if (p) { var ps = p.slice(0, n).reduce(function (a, b) { return a + b; }, 0); if (ps > 0) { var dl = (sum - ps) / ps * 100; chip = [(dl > 0 ? '↑ ' : '↓ ') + Math.abs(dl).toFixed(1) + '% vs ' + (y - 1), dl > 0 ? 'warn' : 'ok']; } }
      return { cap: 'Hours · ' + (n === 12 ? 'Full year' : 'Jan–' + M[n - 1]), n: hrs(sum), v: sum, dec: sum % 1 ? 1 : 0, u: 'hrs', chip: chip };
    }
    function month(i) {
      if (i >= n) return { cap: ML[i] + ' ' + y, n: '—', chip: ['Not processed', 'mute'], sk: p ? (y - 1) + ' same month' : null, sv: p ? hrs(p[i]) + ' hrs' : '' };
      var chip = null; if (p && p[i] > 0) { var dl = d[i] - p[i]; chip = [(dl > 0 ? '+' : '−') + hrs(Math.abs(dl)) + ' hrs vs ' + (y - 1), dl > 0 ? 'warn' : 'ok']; }
      return { cap: ML[i] + ' ' + y, n: hrs(d[i]), u: 'hrs', chip: chip };
    }
    tell(card, summary(), first ? 'count' : first === null ? 'none' : 'swap');
    if (first !== null && n > 0) {
      anim(ln, [{ strokeDasharray: '1 1', strokeDashoffset: 1 }, { strokeDasharray: '1 1', strokeDashoffset: 0 }], { duration: 1100, easing: TIDE });
      anim(ar, [{ opacity: 0 }, { opacity: 1 }], { duration: 700, delay: 420, easing: TIDE, fill: 'backwards' });
      if (ly) anim(ly, [{ opacity: 0 }, { opacity: 1 }], { duration: 600, delay: 200, fill: 'backwards' });
      cg.style.transformOrigin = (pkX + pkW / 2) + 'px ' + (pkY + 18) + 'px';
      anim(cg, [{ opacity: 0, transform: 'scale(.6)' }, { opacity: 1, transform: 'none' }], { duration: 420, delay: 950, easing: SWELL, fill: 'backwards' });
    }
  }

  /* =================== Pension =================== */
  function pn(first) {
    var card = $('ptcPn'), y = +card.querySelector('select').value;
    getJson(U.pension, { YearWisePensionData: y }).then(function (rows) {
      var t = new Array(12).fill(null), last = -1;
      (rows || []).forEach(function (r) { var i = M.indexOf(String(r.month).slice(0, 3)); if (i > -1 && proc(y, i)) { t[i] = { e: cv(r.employee), r: cv(r.employer) }; if (i > last) last = i; } });
      var box = $('ptcPnPills'); box.textContent = '';
      var pills = M.map(function (m, i) {
        var b = el('button', 'pill it ' + (t[i] ? (i === last ? 'f-lime' : 'f-done') : 'f-np'));
        b.type = 'button'; b.setAttribute('aria-label', ML[i] + ': ' + (t[i] ? CUR + f(t[i].e + t[i].r, 2) + ' remitted' : 'not processed')); if (!t[i]) b.tabIndex = -1;
        box.appendChild(b); return b;
      });
      months($('ptcPnX'));
      function summary() {
        if (last < 0) return { empty: none(y) };
        var x = t[last]; return { cap: ML[last] + ' remittance', cur: CUR, n: f(x.e + x.r, 2), v: x.e + x.r, dec: 2, sk: 'Employee + employer', sv: CUR + f(x.e, 2) + ' each' };
      }
      function month(i) {
        if (!t[i]) return { cap: ML[i] + ' ' + y, n: '—', chip: ['Not processed', 'mute'] };
        var x = t[i]; return { cap: ML[i] + ' remittance', cur: CUR, n: f(x.e + x.r, 2), sk: 'Employee + employer', sv: CUR + f(x.e, 2) + ' each' };
      }
      hoverable(card, $('ptcPnViz'), pills, month, summary, $('ptcPnX'));
      tell(card, summary(), first ? 'count' : 'swap');
      pills.forEach(function (b, i) {
        if (!t[i]) anim(b, [{ opacity: 0 }, { opacity: 1 }], { duration: 400, delay: (last + 1) * 55, fill: 'backwards' });
        else anim(b, [{ clipPath: 'inset(100% 0 0 0 round 999px)' }, { clipPath: 'inset(0 0 0 0 round 999px)' }], { duration: 620, delay: i * 55, easing: i === last ? SWELL : TIDE, fill: 'backwards' });
      });
    }).catch(function () { tell(card, { empty: 'Could not load pension data.' }); });
  }

  /* =================== Tax =================== */
  // The tax endpoint is year-only and returns only the bands that actually withheld tax (no rates, no staff counts,
  // no tax-free band), so steps are sized by the tax withheld in each band and the rate / staff lines are not shown.
  function tx(first) {
    var card = $('ptcTx'), y = +card.querySelector('select').value;
    getJson(U.tax, { year: y }).then(function (r) {
      var labels = r.labels || [], vals = (r.data || []).map(function (v) { return cv(v); }), tot = vals.reduce(function (a, b) { return a + b; }, 0), mx = Math.max.apply(null, vals.concat([0]));
      var st = $('ptcTxStairs'), xl = $('ptcTxX'); st.textContent = ''; xl.textContent = '';
      if (!labels.length || tot <= 0) { st.style.gridTemplateColumns = ''; tell(card, { empty: none(y) }); return; }
      st.style.gridTemplateColumns = xl.style.gridTemplateColumns = 'repeat(' + labels.length + ',minmax(0,1fr))';
      var top = vals.indexOf(mx), steps = labels.map(function (l, i) {
        var s = el('div', 'st it'); s.tabIndex = 0; s.setAttribute('aria-label', l + ': ' + CUR + f(vals[i], 2) + ' withheld');
        s.appendChild(el('div', 'amt', CUR + f(vals[i]))); var b = el('div', 'blk ' + (i === top ? 'f-lime' : 'f-done')); b.style.setProperty('--h', Math.round(26 + vals[i] / mx * 104) + 'px'); s.appendChild(b);
        st.appendChild(s); var x = el('div'), bl = el('div', 'b', l); bl.title = l; x.appendChild(bl); xl.appendChild(x); return s;
      });
      function summary() { return { cap: 'Withheld · ' + (y === C.lastY && C.lastM < 12 ? 'Jan–' + M[C.lastM - 1] + ' ' + y : y), cur: CUR, n: f(tot, 2), v: tot, dec: 2 }; }
      function band(i) { return { cap: labels[i], cur: CUR, n: f(vals[i], 2), chip: [Math.round(vals[i] / tot * 100) + '% of tax', 'mute'] }; }
      hoverable(card, $('ptcTxViz'), steps, band, summary, xl);
      tell(card, summary(), first ? 'count' : 'swap');
      steps.forEach(function (s, i) {
        anim(s.querySelector('.blk'), [{ transform: 'scaleY(0)' }, { transform: 'none' }], { duration: 620, delay: i * 90, easing: i === top ? SWELL : TIDE, fill: 'backwards' });
        anim(s.querySelector('.amt'), [{ opacity: 0, transform: 'translateY(6px)' }, { opacity: 1, transform: 'none' }], { duration: 360, delay: i * 90 + 380, easing: TIDE, fill: 'backwards' });
      });
    }).catch(function () { $('ptcTxStairs').textContent = ''; $('ptcTxX').textContent = ''; tell(card, { empty: 'Could not load tax data.' }); });
  }

  /* =================== Budget Comparison =================== */
  function bg(first) {
    var card = $('ptcBg'), y = +card.querySelector('select').value;
    getJson(U.budget, { year: y }).then(function (r) {
      var b = (r.budgeted || []).map(Number), a = (r.actual || []).map(Number), n = 0;
      a.forEach(function (v, i) { if (proc(y, i) && v > 0) n = i + 1; });
      var ann = b.reduce(function (s, v) { return s + v; }, 0), btd = b.slice(0, n).reduce(function (s, v) { return s + v; }, 0), spent = a.slice(0, n).reduce(function (s, v) { return s + v; }, 0);
      var span = n === 12 ? 'Full year' : 'Jan–' + M[Math.max(n, 1) - 1], pace = $('ptcBgPace'), vr = $('ptcBgVar'), hasBudget = ann > 0;
      pace.textContent = ''; vr.textContent = ''; vr.appendChild(el('span', 'zl', 'On budget')); months($('ptcBgX'));
      if (n === 0) { tell(card, { empty: none(y) }); return; }
      var w = hasBudget ? spent / ann * 100 : 0, m = hasBudget ? btd / ann * 100 : 0;
      if (hasBudget) {
        var fill = el('div', 'fill f-done'); fill.style.setProperty('--w', Math.min(100, w) + '%'); pace.appendChild(fill);
        if (m > w) { var gap = el('div', 'gap f-np'); gap.style.setProperty('--a', w + '%'); gap.style.setProperty('--g', (m - w) + '%'); pace.appendChild(gap); }
        var mk = el('div', 'mk'); mk.style.setProperty('--m', Math.min(100, m) + '%'); mk.appendChild(el('b', '', 'Budget to date')); pace.appendChild(mk);
      }
      var vars = b.map(function (bv, i) { return i < n && a[i] > 0 ? a[i] - bv : null; }), vmax = Math.max.apply(null, vars.filter(function (v) { return v != null; }).map(Math.abs).concat([1]));
      var cols = vars.map(function (v, i) {
        var c = el('div', 'vc it' + (v == null ? ' np' : ''));
        if (v == null) { c.appendChild(el('div', 'vb f-np')); }
        else { c.tabIndex = 0; c.setAttribute('aria-label', ML[i] + ': ' + (v > 0 ? 'over' : 'under') + ' by ' + money(Math.abs(v))); var bar = el('div', 'vb ' + (v > 0 ? 'up f-err' : 'dn f-done')); bar.style.height = Math.max(4, Math.abs(v) / vmax * 54) + 'px'; c.appendChild(bar); }
        vr.appendChild(c); return c;
      });
      function summary() {
        if (!hasBudget) return { cap: 'Spent · ' + span, cur: CUR, n: f(cv(spent)), v: cv(spent), sk: 'Budget', sv: 'Not set' };
        var v = btd - spent, under = v >= 0;
        return { cap: (under ? 'Under budget · ' : 'Over budget · ') + span, cur: CUR, n: f(cv(Math.abs(v))), v: cv(Math.abs(v)), chip: [(Math.abs(v) / btd * 100).toFixed(1) + '%', under ? 'ok' : 'err'], sk: 'Annual budget used', sv: Math.round(spent / ann * 100) + '%' };
      }
      function month(i) {
        if (vars[i] == null) return { cap: ML[i] + ' ' + y, n: '—', chip: ['Not processed', 'mute'], sk: 'Budget', sv: b[i] > 0 ? money(b[i]) : '—' };
        var df = a[i] - b[i];
        return { cap: ML[i] + ' · actual', cur: CUR, n: f(cv(a[i])), chip: b[i] > 0 ? [money(Math.abs(df)) + (df > 0 ? ' over' : ' under'), df > 0 ? 'err' : 'ok'] : null, sk: 'Budget', sv: b[i] > 0 ? money(b[i]) : 'Not set' };
      }
      hoverable(card, $('ptcBgViz'), cols, month, summary, $('ptcBgX'));
      if (hasBudget) {
        pace.onpointerenter = function () { $('ptcBgViz').classList.add('hot'); tell(card, { cap: 'Spent · ' + span, cur: CUR, n: f(cv(spent)), chip: [Math.round(w) + '% of year', 'mute'], sk: 'Budget to date', sv: money(btd) }, 'swap'); };
        pace.onpointerleave = function () { $('ptcBgViz').classList.remove('hot'); tell(card, summary(), 'swap'); };
      }
      tell(card, summary(), first ? 'count' : 'swap');
      anim(pace.querySelector('.fill'), [{ transform: 'scaleX(0)' }, { transform: 'none' }], { duration: 900, easing: TIDE });
      anim(pace.querySelector('.mk'), [{ opacity: 0, transform: 'translateY(-8px)' }, { opacity: 1, transform: 'none' }], { duration: 420, delay: 700, easing: SWELL, fill: 'backwards' });
      cols.forEach(function (c, i) { var bar = c.querySelector('.vb'); anim(bar, [{ transform: c.classList.contains('np') ? 'none' : 'scaleY(0)', opacity: c.classList.contains('np') ? 0 : 1 }, { transform: 'none', opacity: 1 }], { duration: 520, delay: 300 + i * 45, easing: TIDE, fill: 'backwards' }); });
    }).catch(function () { tell(card, { empty: 'Could not load budget data.' }); });
  }

  /* ---------- run each card once it is on screen; re-draw on filter change ---------- */
  var run = { ptcOt: ot, ptcPn: pn, ptcTx: tx, ptcBg: bg };
  function start(id) { var c = $(id); if (!c || c.closest('.d-none')) return; c.querySelector('select').addEventListener('change', function () { run[id](false); }); run[id](true); }
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (es) { es.forEach(function (e) { if (e.isIntersecting) { io.unobserve(e.target); start(e.target.id); } }); }, { threshold: .25 });
    Object.keys(run).forEach(function (id) { if ($(id)) io.observe($(id)); });
  } else Object.keys(run).forEach(start);
  // OT is drawn to its width: redraw (no motion) when the card resizes
  if ('ResizeObserver' in window && $('ptcOtViz')) { var rw = 0; new ResizeObserver(function (es) { var w = Math.round(es[0].contentRect.width); if (otLast && rw && w !== rw) drawOt(null); rw = w; }).observe($('ptcOtViz')); }
  if (!lite) Array.prototype.forEach.call(document.querySelectorAll('.card.ptc'), function (c, i) { anim(c, [{ opacity: 0, transform: 'translateY(14px) scale(.985)' }, { opacity: 1, transform: 'none' }], { duration: 520, easing: TIDE, delay: i * 45, fill: 'backwards' }); });
})();
