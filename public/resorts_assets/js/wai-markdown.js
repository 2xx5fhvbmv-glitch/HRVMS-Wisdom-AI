/*
 * WAI answer formatter — shared by the HR dashboard ask box (master-dashboard/hrdashboard) and the floating
 * chat (partials/wisdom-chat). Loaded once from layouts/footer. Styles: resorts_assets/css/wai-markdown.css.
 *
 * Minimal Markdown for WAI answers (bold, italic, code, links, bullet / numbered lists, headings, tables),
 * plus record-shaped bullet lists shown as tables. Built with DOM nodes + textContent only, so the model's
 * text can never inject HTML. Usage: WaiMd.render(containerEl, text, {thread: messagesEl, reveal: true}) — give the
 * container class "a-md".
 *
 * Structured answers (see docs/wai-answer-format): [[subject: Name | Position · Dept | id=N]] shows a person strip once
 * per thread, [[figure: Label | Value | secondary]] a single-figure card, [[open: key | employee=N]] a deep link built
 * ONLY from the whitelist the page renders into #wai-root[data-wai-routes] (never from model text). 2-column tables
 * become a key/value table, wider ones a list table. Unknown/malformed markers never show raw [[ ]].
 */
window.WaiMd = (function () {
  'use strict';
  function el(tag, cls, text) { var e = document.createElement(tag); if (cls) e.className = cls; if (text != null) e.textContent = text; return e; }
  // Display-only: 2026-10-07 -> 07 Oct 2026 (day, short month, year)
  var MD_MON = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  function mdDates(s) {
    return s.replace(/\b(\d{4})[-‐-―−](0[1-9]|1[0-2])[-‐-―−](0[1-9]|[12]\d|3[01])\b/g, function (m, y, mo, d) { return d + ' ' + MD_MON[+mo - 1] + ' ' + y; });
  }
  // Dates are reformatted in visible text only — never inside a link's URL or `code`.
  function mdInline(parent, s) {
    var re = /\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)|\*\*([^*]+)\*\*|`([^`]+)`|\*([^*\s][^*]*)\*/g, last = 0, m, a;
    while ((m = re.exec(s))) {
      if (m.index > last) parent.appendChild(document.createTextNode(mdDates(s.slice(last, m.index))));
      if (m[1] != null) { a = el('a', '', mdDates(m[1])); a.href = m[2]; a.target = '_blank'; a.rel = 'noopener'; parent.appendChild(a); }
      else parent.appendChild(m[3] != null ? el('strong', '', mdDates(m[3])) : m[4] != null ? el('code', '', m[4]) : el('em', '', mdDates(m[5])));
      last = re.lastIndex;
    }
    if (last < s.length) parent.appendChild(document.createTextNode(mdDates(s.slice(last))));
  }
  function mdCells(ln) { return ln.trim().replace(/^\|/, '').replace(/\|$/, '').split('|').map(function (c) { return c.trim(); }); }
  var CFG = null;
  function cfg() {
    if (CFG) return CFG;
    CFG = { routes: {}, leave: {}, leaveLc: {} };
    var r = document.getElementById('wai-root');
    if (r) {
      try { CFG.routes = JSON.parse(r.getAttribute('data-wai-routes') || '{}'); } catch (e) {}
      try { CFG.leave = JSON.parse(r.getAttribute('data-wai-leave') || '{}'); } catch (e) {}
    }
    Object.keys(CFG.leave).forEach(function (k) { CFG.leaveLc[k.toLowerCase()] = CFG.leave[k]; });
    return CFG;
  }
  function initials(n) { return (String(n || '').trim().split(/\s+/).filter(Boolean).slice(0, 2).map(function (p) { return p[0].toUpperCase(); }).join('')) || '?'; }
  function plain(c) { return c && c.pill ? c.text : mdPlain(String(c == null ? '' : c)); }
  var GENERIC_K = /^(field|item|detail|details|property|attribute|label|key|parameter|info|name)?$/i, GENERIC_V = /^(value|values|details?|info|description|data)?$/i;
  // Any 2-column table is a key/value table (.acc). Its header row is dropped when it only says "Field / Value" (or is
  // blank); a header with real content ("Building | Building B") is treated as the first fact. Wider tables -> .tbl.
  function mdTable(box, head, rows, cls) {
    var ncol = head ? head.length : (rows[0] ? rows[0].length : 0);
    if (ncol !== 2) return listTable(box, head, rows);
    if (head && !(GENERIC_K.test(plain(head[0])) && GENERIC_V.test(plain(head[1])))) rows = [head].concat(rows);
    return accTable(box, rows);
  }
  function accTable(box, rows) {
    var acc = el('div', 'acc');
    rows.forEach(function (r) {
      var row = el('div', 'r'), k = el('div', 'k'), v = el('div', 'v'), val = r[1] == null ? '—' : r[1];
      mdInline(k, plain(r[0]));
      if (val && val.pill) v.appendChild(el('span', 'a-pill ' + val.pill, val.text));
      else {
        var m = /^(.*?)\s+·\s+(.+)$/.exec(mdPlain(String(val))); // "201 · Bed 2" -> value + quiet secondary
        if (m && !/\*\*|\[/.test(String(val))) { v.appendChild(document.createTextNode(mdDates(m[1]) + ' ')); v.appendChild(el('span', 'sub', '· ' + mdDates(m[2]))); }
        else mdInline(v, String(val));
      }
      row.appendChild(k); row.appendChild(v); acc.appendChild(row);
    });
    box.appendChild(acc);
  }
  function listTable(box, head, rows) {
    var wrap = el('div', 'tbl'), t = el('table'), tb = el('tbody'), ncol = head ? head.length : rows[0].length, tr;
    var whoCol = head && /^(name|employee|staff|person|full name|employee name)\b/i.test(plain(head[0]));
    if (head) { var th = el('thead'); tr = el('tr'); head.forEach(function (h) { var c = el('th'); mdInline(c, h); tr.appendChild(c); }); th.appendChild(tr); t.appendChild(th); }
    rows.forEach(function (r, ri) {
      tr = el('tr'); if (ri >= 8) tr.className = 'more-h';
      r.forEach(function (c, i) {
        var td = el('td'), txt = plain(c), col = i > 0 && cfg().leaveLc[txt.toLowerCase()];
        if (c && c.pill) td.appendChild(el('span', 'a-pill ' + c.pill, c.text));
        else if (col && /^#[0-9a-f]{3,8}$/i.test(col)) { var lt = el('span', 'lt', txt); lt.style.setProperty('--c', col); td.appendChild(lt); }
        else if (i === 0 && whoCol) { var who = el('div', 'who'); who.appendChild(el('span', 'av sm', initials(txt))); var nm = el('span'); mdInline(nm, c == null ? '—' : c); who.appendChild(nm); td.appendChild(who); }
        else mdInline(td, c == null ? '—' : c);
        tr.appendChild(td);
      });
      tb.appendChild(tr);
    });
    if (rows.length > 8) { // quiet "Show all N", expands in place — no pagination
      var more = el('tr', 'show-all'), mtd = el('td'), btn = el('button', '', 'Show all ' + rows.length);
      mtd.colSpan = ncol; btn.type = 'button';
      btn.addEventListener('click', function () { Array.prototype.forEach.call(tb.querySelectorAll('.more-h'), function (x) { x.classList.remove('more-h'); }); more.remove(); });
      mtd.appendChild(btn); more.appendChild(mtd); tb.appendChild(more);
    }
    t.appendChild(tb); wrap.appendChild(t); box.appendChild(wrap);
  }
  // "Name – Dept – Reason: x – Resignation date 2026-08-01 – Pending" bullets -> one table row each.
  // Labelled parts ("Label: value", "Label <date>", "No <label> recorded") become columns; the first plain part is the
  // name and a trailing status word (Pending, Approved, …) becomes a coloured label. Returns null when the list isn't
  // consistently record-shaped, so ordinary bullets are left alone.
  var MD_STATUS = { pending: 'warn', 'in progress': 'warn', open: 'warn', approved: 'ok', active: 'ok', completed: 'ok', resolved: 'ok', done: 'ok', accepted: 'ok', withdrawn: 'muted', cancelled: 'muted', closed: 'muted', inactive: 'muted', rejected: 'err', overdue: 'err', expired: 'err', terminated: 'err', declined: 'err' };
  function mdPlain(t) { return t.replace(/\*\*([^*]+)\*\*/g, '$1').replace(/(^|[^*])\*([^*]+)\*/g, '$1$2').replace(/`([^`]+)`/g, '$1').trim(); }
  // Text after "Label:". A bold label ("**Reason:** x", "**Reason: x**") leaves one ** unpaired here; drop it.
  function mdVal(v) { v = v.trim(); return (v.match(/\*\*/g) || []).length % 2 ? v.replace(/^\*\*\s*|\s*\*\*$/, '') : v; }
  function mdRecords(items) {
    var cols = [], idx = {}, rows = [];
    // the model sometimes writes date dashes as special Unicode hyphens; normalise so dates parse as dates
    items = items.map(function (t) { return t.replace(/(\d{4})[\u2010-\u2015\u2212](\d{2})[\u2010-\u2015\u2212](\d{2})/g, '$1-$2-$3'); });
    function col(k, head) { if (!(k in idx)) { idx[k] = cols.length; cols.push({ k: k, head: head }); } return idx[k]; }
    var ok = items.every(function (t) {
      var segs = t.split(/\s+[—–-]\s+/), row = {}, u = 0;
      if (segs.length < 4) return false;
      segs.forEach(function (sg) {
        var p = mdPlain(sg), m, key;
        if ((m = /^([A-Za-z][A-Za-z &\/]{1,28}):\s*(.+)$/.exec(p))) { key = 'l:' + m[1].toLowerCase(); row[col(key, m[1])] = mdVal(sg.slice(sg.indexOf(':') + 1)); }
        else if ((m = /^([A-Za-z][A-Za-z &\/]{1,28}?)\s+(\d{4}-\d{2}-\d{2})$/.exec(p))) { key = 'l:' + m[1].toLowerCase(); row[col(key, m[1])] = m[2]; }
        else if ((m = /^No (.{3,40}?) (?:recorded|provided|available|set|found)$/i.exec(p))) { key = 'l:' + m[1].toLowerCase(); row[col(key, m[1].charAt(0).toUpperCase() + m[1].slice(1))] = '—'; }
        else { row[col('u:' + u, '')] = sg; u++; }
      });
      rows.push(row); return true;
    });
    if (!ok || rows.length < 2 || cols.filter(function (c) { return c.k.charAt(0) === 'l'; }).length < 2) return null;
    var unl = cols.filter(function (c) { return c.k.charAt(0) === 'u'; }), last = unl.length > 2 ? unl[unl.length - 1] : null, statusCol = -1;
    if (last) {
      var hits = rows.filter(function (r) { var v = r[idx[last.k]]; return v && MD_STATUS[mdPlain(v).toLowerCase()]; }).length;
      if (hits >= rows.length * 0.6) { statusCol = idx[last.k]; last.head = 'Status'; }
    }
    unl.forEach(function (c, i) { if (!c.head) c.head = i === 0 ? 'Name' : 'Details'; });
    return {
      head: cols.map(function (c) { return c.head; }),
      rows: rows.map(function (r) {
        return cols.map(function (c, ci) {
          var v = r[ci];
          if (v == null) return '—';
          if (ci === statusCol) { var st = mdPlain(v).toLowerCase(); return MD_STATUS[st] ? { pill: MD_STATUS[st], text: mdPlain(v) } : v; }
          return v;
        });
      })
    };
  }
  // "- **Building:** Building B", "- **Floor:** 2" ... : facts about ONE thing, shown as a label / value table (no row count).
  function mdKv(items) {
    var rows = [];
    var ok = items.every(function (t) { var m = /^\s*\**\s*([^:*]{1,32}?)\s*\**\s*:(.+)$/.exec(t), v = m && mdVal(m[2]); if (v) rows.push([m[1], v]); return !!v; });
    return ok && rows.length >= 2 ? rows : null;
  }
  // A long plain list (e.g. 20 names) is shown as a compact multi-column table instead of one tall column.
  function mdGrid(box, items) {
    var wrap = el('div', 'a-tbl'), head = el('div', 'a-th', items.length + ' results'), g = el('div', 'a-grid');
    items.forEach(function (t) { var c = el('div', 'a-td'); mdInline(c, t); g.appendChild(c); });
    wrap.appendChild(head); wrap.appendChild(g); box.appendChild(wrap);
  }
  // Markdown tables render as tables. A bullet list whose every line splits the same way ("Name — detail — date")
  // is a list of records, so it is shown as an aligned table too; anything else stays a normal list.
  function renderMd(box, text) {
    var lines = String(text).split('\n'), i = 0, ul = [], ol = [];
    function flush() {
      var l;
      if (ul.length) {
        var parts = ul.map(function (t) { return t.split(/\s+[—–|]\s+|\s+-\s+/); }), n = parts[0].length;
        var rec = ul.length >= 2 && mdRecords(ul);
        var kv = !rec && ul.length >= 2 && mdKv(ul);
        if (rec) mdTable(box, rec.head, rec.rows);
        else if (kv) mdTable(box, null, kv, 'a-kv');
        else if (ul.length >= 2 && n >= 2 && n <= 4 && parts.every(function (p) { return p.length === n; })) mdTable(box, null, parts);
        else if (ul.length >= 4 && parts.every(function (p) { return p.length === 1; })) mdGrid(box, ul);
        else { l = el('ul', ul.every(function (t) { return /^\*\*/.test(t) && t.length > 60; }) ? 'a-notelist' : ''); ul.forEach(function (t) { var li = el('li'); mdInline(li, t); l.appendChild(li); }); box.appendChild(l); }
        ul = [];
      }
      if (ol.length) {
        // numbered list: starts at the number the model wrote, and keeps the bullets that follow an item nested under it
        l = el('ol'); l.style.setProperty('--s', ol[0].n);
        ol.forEach(function (it) {
          var li = el('li'); mdInline(li, it.t);
          if (it.kids.length) { var k = el('ul'); it.kids.forEach(function (t) { var ki = el('li'); mdInline(ki, t); k.appendChild(ki); }); li.appendChild(k); }
          l.appendChild(li);
        });
        box.appendChild(l); ol = [];
      }
    }
    while (i < lines.length) {
      var ln = lines[i];
      if (/^\s*```/.test(ln)) { // fenced code block, shown verbatim
        flush(); var code = []; i++;
        while (i < lines.length && !/^\s*```/.test(lines[i])) code.push(lines[i++]);
        var pre = el('pre'); pre.appendChild(el('code', '', code.join('\n'))); box.appendChild(pre); i++; continue;
      }
      if (/^\s*\|/.test(ln) && i + 1 < lines.length && /^\s*\|?\s*:?-{2,}/.test(lines[i + 1])) {
        flush(); var head = mdCells(ln), rows = []; i += 2;
        while (i < lines.length && /^\s*\|/.test(lines[i])) { rows.push(mdCells(lines[i])); i++; }
        mdTable(box, head, rows); continue;
      }
      var u = /^\s*[-*•]\s+(.*)/.exec(ln), o = /^\s*(\d+)[.)]\s+(.*)/.exec(ln), h = /^\s{0,3}#{1,6}\s+(.*)/.exec(ln);
      if (u) {
        if (ol.length && !ul.length) ol[ol.length - 1].kids.push(u[1]); // bullets directly under a numbered item belong to it
        else ul.push(u[1]);
      }
      else if (o) { if (ul.length) flush(); ol.push({ n: o[1], t: o[2], kids: [] }); }
      else if (!ln.trim()) flush();
      else {
        flush();
        // A line that is entirely italic (*...* or _..._) is the model's footnote: show it as a quiet note box, not slanted text.
        var nt = !h && /^\s*[*_](?![*_\s])(.+?)[*_]\s*$/.exec(ln), p = el('p', nt ? 'a-note' : '');
        if (h) { var hd = el('h4'); mdInline(hd, h[1].replace(/\*\*/g, '')); box.appendChild(hd); }
        else { mdInline(p, nt ? nt[1] : ln); box.appendChild(p); }
      }
      i++;
    }
    flush();
  }
  /* ---------- structured answers ---------- */
  var SVGNS = 'http://www.w3.org/2000/svg';
  function arrow() {
    var sv = document.createElementNS(SVGNS, 'svg'), pa = document.createElementNS(SVGNS, 'path');
    sv.setAttribute('viewBox', '0 0 24 24'); sv.setAttribute('fill', 'none'); sv.setAttribute('stroke', 'currentColor'); sv.setAttribute('stroke-width', '2.2');
    sv.setAttribute('stroke-linecap', 'round'); sv.setAttribute('stroke-linejoin', 'round'); sv.setAttribute('aria-hidden', 'true');
    pa.setAttribute('d', 'M5 12h14M13 6l6 6-6 6'); sv.appendChild(pa); return sv;
  }
  // Pulls [[subject|figure|open: …]] out of the text before markdown runs. Never leaves raw [[ ]] behind.
  function parseMarkers(text) {
    var out = { subject: null, figures: [], open: null, text: '' };
    var t = String(text == null ? '' : text).replace(/\[\[\s*(subject|figure|open)\s*:\s*([^\]]*?)\s*\]\]/gi, function (m, kind, body) {
      var p = body.split('|').map(function (x) { return x.trim(); }), id;
      kind = kind.toLowerCase();
      if (kind === 'subject') {
        if (!p[0]) return '';
        id = /^id\s*=\s*(\d+)$/i.exec(p[p.length - 1] || '');
        out.subject = { name: p[0], sub: p.length > 1 && !/^id\s*=/i.test(p[1]) ? p[1] : '', id: id ? id[1] : '' };
        return '';
      }
      if (kind === 'figure') {
        if (p.length < 2 || !p[0] || !p[1]) return body; // malformed: show its text plainly
        out.figures.push({ k: p[0], v: p[1], sub: p[2] || '' }); return '';
      }
      var key = (p[0] || '').toLowerCase(), R = cfg().routes[key]; id = /^employee\s*=\s*(\d+)$/i.exec(p[1] || '');
      if (R && R.url && (R.url.indexOf('__id__') < 0 || id)) out.open = { R: R, id: id ? id[1] : '' };
      return ''; // unknown key / missing id: no button, no dead link
    });
    out.text = t.replace(/\[\[([^\]]*)\]\]/g, '$1').replace(/\[\[[^\]]*$/, '');
    return out;
  }
  // "You can view it in the X module" sentences are replaced by the button, so drop them when a button is shown.
  function stripWhere(t) {
    return t.split('\n').filter(function (l) { return !(l.length < 160 && /\b(you can|you may|available|found)\b.*\bmodule\b|^\W*employee module\b/i.test(l)); }).join('\n');
  }
  function figEl(f) {
    var d = el('div', 'fig'); d.appendChild(el('div', 'k', f.k)); d.appendChild(el('div', 'v', f.v)); if (f.sub) d.appendChild(el('div', 'sub', f.sub)); return d;
  }
  function subjEl(s) {
    var w = el('div', 'subj'), d = el('div'); w.appendChild(el('span', 'av', initials(s.name)));
    d.appendChild(el('div', 'nm', s.name)); if (s.sub) d.appendChild(el('div', 'ps', s.sub)); w.appendChild(d); return w;
  }
  function goEl(o, who) {
    var R = o.R, a = el('a', 'go', R.btn || ('Open ' + (R.label || ''))), tip = (o.id ? 'Employee › ' + (who ? who + ' › ' : '') : '') + (R.label || '');
    a.href = o.id ? R.url.replace('__id__', encodeURIComponent(btoa(o.id))) : R.url;
    a.setAttribute('data-tip', tip); a.appendChild(arrow()); return a;
  }
  // Today's answers have no markers yet: a 2-column facts table that starts with "Employee" / "Position" rows is turned into
  // the same structure the markers would give — person strip (once per thread), a heading taken from the "view it in the X
  // module" sentence, and a single figure when only one fact is left. Anything else is left exactly as written.
  var K_NAME = /^(employee|employee name|full name|name)$/i, K_POS = /^(position|designation|job title|role)$/i, K_DEPT = /^(department|dept)$/i;
  function legacy(P) {
    if (P.subject || P.figures.length || P.open) return;
    var lines = P.text.split('\n'), st = -1, en, i, rows, sub = { name: '', pos: '', dept: '' }, keep = [];
    for (i = 0; i < lines.length; i++) if (/^\s*\|/.test(lines[i])) { st = i; break; }
    if (st < 0) return;
    for (en = st; en < lines.length && /^\s*\|/.test(lines[en]); en++);
    var cells = lines.slice(st, en).map(mdCells);
    if (cells.some(function (c) { return c.length !== 2; })) return;
    rows = cells.filter(function (c) { return !(/^:?-{2,}:?$/.test(c[0].replace(/\s/g, '')) && /^:?-{2,}:?$/.test(c[1].replace(/\s/g, ''))); });
    if (rows.length && GENERIC_K.test(plain(rows[0][0])) && GENERIC_V.test(plain(rows[0][1]))) rows.shift();
    rows.forEach(function (r) {
      var k = mdPlain(r[0]), v = mdPlain(r[1]);
      if (K_NAME.test(k) && !sub.name) sub.name = v; else if (K_POS.test(k) && !sub.pos) sub.pos = v; else if (K_DEPT.test(k) && !sub.dept) sub.dept = v; else keep.push(r);
    });
    if (!sub.name) return;
    var ps = sub.pos.replace(/\s*\(([^)]*)\)\s*$/, ' · $1'); // "Waitress (F&B Service)" -> "Waitress · F&B Service"
    if (sub.dept && ps.indexOf('·') < 0) ps += (ps ? ' · ' : '') + sub.dept;
    P.subject = { name: sub.name, sub: ps, id: '' };
    var repl = [];
    if (keep.length === 1) { // one fact left = a single figure
      var k1 = mdPlain(keep[0][0]).replace(/\|/g, '/'), v1 = mdPlain(keep[0][1]).replace(/\|/g, '/'), m = /^(.*?)\s*\(\s*([^)]+?)\s*\)\s*$/.exec(v1), extra = '';
      if (m) { v1 = m[1]; extra = /^[A-Z]{3}\b|^[\d$]/.test(m[2]) ? '≈ ' + m[2] : m[2]; }
      P.figures.push({ k: k1, v: v1, sub: extra });
    } else if (keep.length > 1) {
      repl = ['| | |', '|---|---|'].concat(keep.map(function (r) { return '| ' + r[0].replace(/\|/g, '/') + ' | ' + r[1].replace(/\|/g, '/') + ' |'; }));
    }
    // heading from "…in the Accommodation module → Employee Accommodation page" or "… → Rani's profile → Salary / Compensation"
    if (!lines.some(function (l) { return /^\s{0,3}#{1,6}\s/.test(l); })) {
      for (i = 0; i < lines.length; i++) {
        if (!/→|->/.test(lines[i]) || !/module/i.test(lines[i])) continue;
        var segs = mdPlain(lines[i]).split(/\s*(?:→|->)\s*/), head = '';
        if (segs.length >= 3) head = segs[segs.length - 1].replace(/[.\s]+$/, '').replace(/\s+page$/i, '').split(/\s+\/\s+/)[0]; // "Salary / Compensation" -> "Salary"
        else { var mm = /((?:\b[A-Z][\w&\/]*\s+)*[A-Z][\w&\/]*)\s+module/.exec(segs[0]); head = mm ? mm[1] : ''; }
        if (head) repl.unshift('### ' + head);
        break;
      }
    }
    lines.splice.apply(lines, [st, en - st].concat(repl));
    P.text = lines.join('\n');
  }
  function render(box, text, opts) {
    opts = opts || {};
    var P = parseMarkers(text), th = opts.thread, body;
    legacy(P);
    body = P.open ? stripWhere(P.text) : P.text;
    box.textContent = '';
    renderMd(box, body);
    if (P.figures.length) {
      var h = box.querySelector('h4'), at = h ? h.nextSibling : box.firstChild;
      P.figures.forEach(function (f) { box.insertBefore(figEl(f), at); });
    }
    if (P.subject) {
      var sid = (P.subject.id || P.subject.name).toLowerCase();
      if (!th || th._waiSubj !== sid) { box.insertBefore(subjEl(P.subject), box.firstChild); if (th) th._waiSubj = sid; }
    }
    if (P.open) box.appendChild(goEl(P.open, P.subject && P.subject.name));
    if (opts.reveal) reveal(box);
  }
  /* ---------- Tide motion (transform + opacity only; instant under reduced motion / saveData / low-power) ---------- */
  var lite = (window.matchMedia && matchMedia('(prefers-reduced-motion:reduce)').matches) || (navigator.connection && navigator.connection.saveData) || (navigator.hardwareConcurrency || 4) <= 2;
  var TIDE = 'cubic-bezier(.16,1,.3,1)';
  function tideIn(node, delay) {
    if (lite || !node.animate) return;
    node.animate([{ opacity: 0, transform: 'translateY(14px) scale(.985)' }, { opacity: 1, transform: 'none' }], { duration: 520, easing: TIDE, delay: delay || 0, fill: 'backwards' });
  }
  function reveal(box) {
    if (lite) return;
    Array.prototype.forEach.call(box.querySelectorAll('.subj,h4,.acc .r,.fig,.tbl tr,.go,p'), function (p, i) {
      if (p.animate) p.animate([{ opacity: 0, transform: 'translateY(6px)' }, { opacity: 1, transform: 'none' }], { duration: 420, easing: TIDE, delay: Math.min(i, 10) * 45, fill: 'backwards' });
    });
  }
  // Quota digit slides out, the new one slides in.
  function roll(node, value) {
    if (lite || !node.animate || !node.textContent || node.textContent === String(value)) { node.textContent = value; return; } // first value shows at once
    node.animate([{ transform: 'none', opacity: 1 }, { transform: 'translateY(-70%)', opacity: 0 }], { duration: 160, easing: 'cubic-bezier(.4,0,1,1)' }).onfinish = function () {
      node.textContent = value; node.animate([{ transform: 'translateY(70%)', opacity: 0 }, { transform: 'none', opacity: 1 }], { duration: 340, easing: TIDE });
    };
  }
  return { render: render, tideIn: tideIn, roll: roll };
})();
