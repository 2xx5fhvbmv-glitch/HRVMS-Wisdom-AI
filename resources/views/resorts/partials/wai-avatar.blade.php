{{-- WAI avatar, shared by every screen: sprite (drawn once), CSS and the JS helper wavHTML().
     Real photo first; no photo (or a photo that fails to load) shows the drawn portrait underneath.
     Portrait comes from the recorded gender only (male / female); anything else is the neutral figure. --}}
<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <symbol id="wav-m" viewBox="0 0 64 64"><path d="M10 64c2-11 11-17 22-17s20 6 22 17z" fill="#2E7B86" opacity=".85"/><rect x="28" y="38" width="8" height="10" rx="3" fill="#C8936E"/><ellipse cx="32" cy="29" rx="11" ry="13" fill="#C8936E"/><path d="M32 13c-10 0-15 7-14 15 1-5 5-8 14-8s13 3 14 8c1-8-4-15-14-15z" fill="#231C18"/></symbol>
  <symbol id="wav-f" viewBox="0 0 64 64"><path d="M10 64c2-11 11-17 22-17s20 6 22 17z" fill="#2E7B86" opacity=".85"/><rect x="28" y="38" width="8" height="10" rx="3" fill="#C8936E"/><ellipse cx="32" cy="29" rx="11" ry="13" fill="#C8936E"/><path d="M32 12c-11 0-16 8-15 18 0 9 2 16 3 20h6c-3-7-4-14-3-21 2-4 5-6 9-6s7 2 9 6c1 7 0 14-3 21h6c1-4 3-11 3-20 1-10-4-18-15-18z" fill="#231C18"/></symbol>
  <symbol id="wav-n" viewBox="0 0 64 64"><path d="M12 64c2-11 10-17 20-17s18 6 20 17z" fill="#014653" opacity=".32"/><circle cx="32" cy="28" r="11" fill="#014653" opacity=".32"/></symbol>
</svg>
<style>
.wav{position:relative;display:inline-block;vertical-align:middle;width:36px;height:36px;border-radius:50%;overflow:hidden;flex:none;background:var(--wb,#E6F0F1)}
.wav svg,.wav img{position:absolute;inset:0;width:100%;height:100%;display:block}
.wav img{object-fit:cover}
</style>
<script>
var WAV_BG=['#E3EEF7','#EEF4E2','#E6F0F1','#F3EBDA','#F6E7EE'];
function wavKind(g){g=String(g||'').trim().toLowerCase();return g==='m'||g==='male'?'m':g==='f'||g==='female'?'f':'n'}
function wavTint(id){var h=0,s=String(id||'');for(var i=0;i<s.length;i++)h=(h*31+s.charCodeAt(i))|0;return WAV_BG[Math.abs(h)%WAV_BG.length]}
function wavEsc(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]})}
// o: {photo, name, gender, id}  (id = employee code, picks the background tint)
// The app's generic default picture (Common::getResortUserPicture fallback) counts as "no photo".
function wavHTML(o){var k=wavKind(o.gender);if(o.photo&&/admin_assets\/files\/(user-image\.png|default-pic\.jpg)/.test(o.photo))o=Object.assign({},o,{photo:''});
  return '<span class="wav" style="--wb:'+(k==='n'?'#E6F0F1':wavTint(o.id))+'"><svg aria-hidden="true"><use href="#wav-'+k+'"/></svg>'+
    (o.photo?'<img src="'+wavEsc(o.photo)+'" alt="'+wavEsc(o.name)+'" loading="lazy" decoding="async" onerror="this.remove()">':'')+'</span>'}
</script>
