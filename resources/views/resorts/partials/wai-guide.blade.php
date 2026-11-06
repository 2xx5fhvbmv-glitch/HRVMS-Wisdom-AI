{{--
    WAI Guide: the in-app guide (edge notch, Guide panel, step card, welcome tour, Find anything).
    Rendered globally from the resort footer. Presentation only: static content and client-side state, no requests, no backend.
    Only the notch and a tiny starter are rendered here. public/resorts_assets/js/wai-guide.js and its content load the first time the Guide is needed
    (hovering or pressing the notch, Cmd/Ctrl+K, a walkthrough already running, the first-visit tour, or a setup step finishing); a normal page load makes no extra request.
    Guide content: public/resorts_assets/js/wai-guide-content.js · Styles: public/resorts_assets/css/wai-guide.css
--}}
@php
    $wgUser = Auth::guard('resort-admin')->user();
@endphp
@if ($wgUser)
@php
    // Route names the guides can open: the keys of PG in wai-guide-content.js (so the list can never drift from the content).
    // Paths only, built once per version of that file; a name that no longer exists is left out and the guide says the screen isn't available.
    $wgFile = public_path('resorts_assets/js/wai-guide-content.js');
    $wgRoutes = Cache::remember('wai-guide-routes-' . @filemtime($wgFile), 3600, function () use ($wgFile) {
        preg_match_all("/'([\w.\-]+)':\s*\['[a-z]+',\s*'/", (string) @file_get_contents($wgFile), $m);
        $out = [];
        foreach (array_unique($m[1]) as $n) {
            if (Route::has($n)) {
                $out[$n] = route($n, [], false);
            }
        }
        return $out;
    });
    // Which of those screens this person can open. HR-department heads (Common::isHR) own the platform, so they get every guide and nothing is computed.
    // Everyone else gets exactly what their sidebar menu shows (same rule as MasterDashboardController::getMenuData: a "view" tick for their position on the menu page).
    // $wgMenu = guide screens that are menu pages (global, built once per version of the content file); $wgAllowed = the ones this position may view.
    $wgHr = \App\Helpers\Common::isHR($wgUser->GetEmployee);
    $wgMenu = $wgAllowed = null;
    $wgPos = $wgUser->GetEmployee->Position_id ?? null;
    if (!$wgHr && $wgPos) {
        $wgMenu = Cache::remember('wai-guide-menu-' . @filemtime($wgFile), 3600, function () use ($wgFile, $wgRoutes) {
            preg_match_all("/'([\w.\-]+)':\s*'[\w.\-]+'/", (string) @file_get_contents($wgFile), $m); // the role-dashboard aliases
            return \App\Models\ModulePages::whereIn('internal_route', array_unique(array_merge(array_keys($wgRoutes), $m[1])))
                ->where('TypeOfPage', 'InsideOfMenu')->where('type', 'normal')->where('status', 'Active')->whereNull('deleted_at')
                ->get(['id', 'Module_Id', 'internal_route'])->mapWithKeys(fn ($p) => [$p->internal_route => $p->Module_Id . '_' . $p->id])->all();
        });
        // The view ticks come from Common::allowedRouteNames(): the same rule as checkRouteWisePermission(), 2 queries cached
        // per resort + position, and the cache is cleared when HR saves Page Permissions (no 5-minute wait).
        $wgAllowed = array_values(array_intersect(array_keys($wgMenu), \App\Helpers\Common::allowedRouteNames(array_keys($wgMenu))));
    }
@endphp
<link rel="stylesheet" href="{{ URL::asset('resorts_assets/css/wai-guide.css') }}?v={{ @filemtime(public_path('resorts_assets/css/wai-guide.css')) }}">
<button type="button" class="wg-notch" id="wg-notch" data-wga="notch" aria-label="Open the Guide"
        data-route="{{ Route::currentRouteName() }}" data-uid="{{ $wgUser->id }}" data-rank="{{ $wgUser->GetEmployee->rank ?? '' }}" data-hr="{{ $wgHr ? 1 : '' }}" data-name="{{ $wgUser->first_name }}" data-routes='@json($wgRoutes)'@if ($wgMenu !== null) data-menu='@json(array_keys($wgMenu))' data-allowed='@json($wgAllowed)'@endif
        data-js="{{ URL::asset('resorts_assets/js/wai-guide-content.js') }}?v={{ @filemtime(public_path('resorts_assets/js/wai-guide-content.js')) }}|{{ URL::asset('resorts_assets/js/wai-guide.js') }}?v={{ @filemtime(public_path('resorts_assets/js/wai-guide.js')) }}">
    <span class="wg-nc"><span class="wg-nm" aria-hidden="true"><i></i></span><span class="wg-nx" id="wg-nx"><span class="wg-ni"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2 5-5 2 2-5z"/></svg></span><span class="wg-nt"><b>Guide</b><span class="wg-ns">Guides for this screen</span></span></span></span>
</button>
{{-- Starter: fetches the guide the first time it is needed and replays what was asked meanwhile. --}}
<script>
(function(){var W=window,D=document,N=D.getElementById('wg-notch'),L=0,Q=[];if(!N)return;
function load(){if(L)return;L=1;N.dataset.js.split('|').forEach(function(u){var s=D.createElement('script');s.src=u;s.async=false;D.body.appendChild(s)})}
function ask(n){return function(){Q.push([n].concat([].slice.call(arguments)));load()}}
W.WAIGuide={q:Q,done:ask('done'),open:ask('open'),start:ask('start'),palette:ask('palette')};
N.addEventListener('pointerenter',load,{once:true});N.addEventListener('focus',load,{once:true});
N.addEventListener('click',function(){if(!W.WAIGuide.ready)W.WAIGuide.open('home')});
D.addEventListener('keydown',function(e){if(!W.WAIGuide.ready&&(e.metaKey||e.ctrlKey)&&(e.key||'').toLowerCase()==='k'){e.preventDefault();W.WAIGuide.palette()}});
try{var st=JSON.parse(localStorage.getItem('wai-guide:'+N.dataset.uid))||{};if(st.run||(!st.tourSeen&&W.innerWidth>=992))(W.requestIdleCallback||setTimeout)(load,{timeout:2500})}catch(e){}
})();
</script>
@endif
