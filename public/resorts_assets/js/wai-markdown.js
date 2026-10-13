/*
 * WAI answer formatter — shared by the HR dashboard ask box (master-dashboard/hrdashboard) and the floating
 * chat (partials/wisdom-chat). Loaded once from layouts/footer. Styles: resorts_assets/css/wai-markdown.css.
 *
 * Minimal Markdown for WAI answers (bold, italic, code, links, bullet / numbered lists, headings, tables),
 * plus record-shaped bullet lists shown as tables. Built with DOM nodes + textContent only, so the model's
 * text can never inject HTML. Usage: WaiMd.render(containerEl, text) — give the container class "a-md".
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
  function mdTable(box, head, rows, cls) {
    var wrap = el('div', 'a-tbl' + (cls ? ' ' + cls : '')), t = el('table'), tr, tb = el('tbody');
    if (head) { var th = el('thead'); tr = el('tr'); head.forEach(function (h) { var c = el('th'); mdInline(c, h); tr.appendChild(c); }); th.appendChild(tr); t.appendChild(th); }
    rows.forEach(function (r) { tr = el('tr'); r.forEach(function (c, i) { var td = el('td', i === 0 ? 'k' : ''); if (c && c.pill) td.appendChild(el('span', 'a-pill ' + c.pill, c.text)); else mdInline(td, c == null ? '\u2014' : c); tr.appendChild(td); }); tb.appendChild(tr); });
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
        mdInline(p, h ? '**' + h[1].replace(/\*\*/g, '') + '**' : (nt ? nt[1] : ln)); box.appendChild(p);
      }
      i++;
    }
    flush();
  }
  return { render: renderMd };
})();
