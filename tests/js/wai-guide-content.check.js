/* Run: node tests/js/wai-guide-content.check.js
 * Checks the WAI Guide content is consistent: every guide belongs to a module, every step route has a "where" entry,
 * every screen entry points at a real guide. (tests/Feature/WaiGuideContentTest.php checks the route names exist.) */
global.window = {};
require('../../public/resorts_assets/js/wai-guide-content.js');
const C = window.WAIGuideContent, bad = [], mods = new Set(C.MODS.map(m => m[0]));
const need = r => { if (r && !C.PG[r]) bad.push('route missing from PG: ' + r); };
for (const [id, g] of Object.entries(C.GUIDES)) {
  if (!mods.has(g.m)) bad.push(id + ': unknown module ' + g.m);
  if (!g.t || !g.mins || !g.steps || !g.steps.length) bad.push(id + ': needs title, mins and steps');
  if (g.live) { need(g.live); continue; }
  g.steps.forEach((s, i) => { if (!s[0]) bad.push(id + ' step ' + (i + 1) + ': no title'); need(s[2]); if (s[3] && s[2] === undefined) bad.push(id + ' step ' + (i + 1) + ': selector needs a route (use \'\' to stay on the current screen)'); });
}
for (const [r, s] of Object.entries(C.SCREENS)) { if (!C.PG[r] && !s.t) bad.push(r + ': a screen outside the modules needs a title'); if (s.guide && !C.GUIDES[s.guide]) bad.push(r + ': unknown guide ' + s.guide); (s.pop || []).forEach(p => { if (!C.GUIDES[p]) bad.push(r + ': unknown popular guide ' + p); }); if (s.after) need(s.after.go); }
for (const [r, p] of Object.entries(C.PG)) if (!mods.has(p[0])) bad.push('PG ' + r + ': unknown module ' + p[0]);
const n = Object.keys(C.GUIDES).length;
if (bad.length) { console.error(bad.join('\n')); process.exit(1); }
console.log('ok: ' + n + ' guides, ' + Object.keys(C.PG).length + ' screens');
