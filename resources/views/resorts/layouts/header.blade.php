<style>
    /* Menu button height matched to the search bar it sits beside (40px) —
       was ~52px, taller than the search bar next to it. .menu-box only
       exists in this file (the vertical-menu header), not in
       header1.blade.php, so this is safe to apply unscoped. */
    .menu-box .btn {
        padding: 4px 30px;
    }

    /* Search placeholder toned down — it was full-opacity white, the same
       visual weight as real typed text, reading as loud/unpolished next to
       the rest of the bar. .search-bar-nav / .search-input are likewise
       unique to this file. */
    .search-bar-nav .search-input::placeholder {
        color: rgba(255, 255, 255, 0.55);
        font-weight: 400;
    }

    /* Same placeholder treatment for the expandable search box that opens
       under the horizontal menu bar (also present, as its own separate
       copy, in header1.blade.php) — it was still showing the old
       full-opacity white placeholder since it doesn't share the
       .search-bar-nav wrapper the fix above targets. */
    .serch-box input::placeholder {
        color: rgba(255, 255, 255, 0.55);
        font-weight: 400;
    }

    /* Notification count badge polish: a fixed 10x10px circle with
       asymmetric padding (padding-right only) and an 11.2px font that
       barely fit — enlarged slightly, evenly centered, with a subtle ring
       so it reads clearly instead of looking like a stray clipped dot.
       .notification-nav is also used by header1.blade.php's bell icon;
       unlike .top-navbar's background (which meant something different in
       each file), a cleaner badge is equally correct in both, so this one
       is left unscoped on purpose. */
    .notification-nav span {
        top: -4px;
        right: 2px;
        width: 17px;
        height: 17px;
        padding-right: 0;
        font-size: 10px;
        font-weight: 600;
        border: 2px solid #01414d;
        box-sizing: content-box;
    }

    /* ================= Dynamic Island =================
       Replaces the old plain bell <li>. Idle = charcoal pill (bell + the
       same unread count this file always rendered). Hover morphs (spring)
       into a 3-item menu: Notifications reuses the .notification-btn
       click handler in js.blade.php unchanged; Messages / Ask WAI dispatch
       CustomEvents into wisdom-chat.blade.php's existing panel JS — same
       hand-off pattern the old edge-notch launcher used. Unscoped like the
       badge rule above, for the same reason (shared bell concept). */
    /* The <li> itself still carries the generic .nav-icon class (a 40x40
       circular icon tile with its own hover-to-lime background) — that
       treatment was sized for the old plain bell <a>, not this pill, and
       was rendering as a second circle behind/around the Island plus a
       lime hover flash. The Island draws 100% of its own look, so strip
       .nav-icon's box model and hover state for this one <li> only. */
    .notification-nav.nav-icon {
        width: auto;
        height: auto;
        background-color: transparent;
        border-radius: 0;
        display: block;
    }
    .notification-nav.nav-icon:hover {
        background-color: transparent;
    }
    /* Idle = a plain round icon, same 40x40 circle as the search icon next
       to it and the avatar after it — matches the rest of the row instead
       of standing out as a pill. The unread count stays the original
       overlapping corner badge (.notification-nav span, styled above);
       .wai-island being position:relative + the same 40x40 box the old
       .nav-icon <li> used to be means that badge anchors identically. */
    .wai-island {
        position: relative;
        width: 40px;
        height: 40px;
        background: hsla(0, 0%, 0%, 0.2); /* matches .nav-icon's own idle background (search icon) */
        border-radius: 50%;
        cursor: pointer;
        flex: none;
    }
    .wai-island-idle {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: opacity .16s;
    }
    .wai-island[data-state="open"] .wai-island-idle {
        opacity: 0;
    }
    .notification-nav .wai-island-bell,
    .notification-nav.nav-icon:hover .wai-island-bell {
        width: 18px;
        height: 18px;
        filter: brightness(0) invert(1);
    }
    .wai-island-morph {
        position: absolute;
        top: 0; right: 0;
        background: #06181c;
        border-radius: inherit;
        overflow: hidden;
        opacity: 0;
        pointer-events: none;
        width: 100%;
        height: 100%;
        z-index: 9;
        box-shadow: 0 18px 44px rgba(0,0,0,.36);
        transition: width .5s cubic-bezier(.34,1.56,.64,1), height .5s cubic-bezier(.34,1.56,.64,1),
                    border-radius .45s cubic-bezier(.34,1.56,.64,1), opacity .18s;
    }
    .wai-island[data-state="open"] .wai-island-morph {
        opacity: 1;
        pointer-events: auto;
        width: 300px;
        height: 152px;
        border-radius: 22px;
    }
    .wai-island-menu {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        padding: 10px;
        gap: 2px;
        justify-content: center;
        opacity: 0;
        transition: opacity .16s;
    }
    .wai-island[data-state="open"] .wai-island-menu {
        opacity: 1;
        transition-delay: .12s;
    }
    .wai-island-opt {
        display: flex;
        align-items: center;
        gap: 12px;
        width: 100%;
        background: transparent;
        border: none;
        color: #fff;
        font: inherit;
        font-size: 13px;
        font-weight: 500;
        text-align: left;
        padding: 10px 11px;
        border-radius: 12px;
        cursor: pointer;
        white-space: nowrap;
    }
    .wai-island-opt:hover {
        background: rgba(255,255,255,.09);
    }
    .wai-island-opt svg {
        opacity: .85;
        flex: none;
    }
    .wai-island-opt .wai-island-ai {
        color: var(--lime, #E0FF02);
        opacity: 1;
    }
    /* .notification-nav span (developer.min.css) is the small round unread
       dot's own display:flex/justify-content:center/align-items:center —
       correct for that badge, but .wai-island-ol/.wai-island-badge are
       also plain <span>s under the same .notification-nav ancestor, so it
       was silently centering their text too. Reset explicitly rather than
       just position/size. */
    .notification-nav .wai-island-ol {
        display: block;
        position: static; top: auto; right: auto; background: none; border: none;
        width: auto; height: auto; padding: 0; border-radius: 0; box-sizing: content-box;
        flex: 1;
        text-align: left;
        font-size: inherit;
        color: inherit;
    }
    .notification-nav .wai-island-badge {
        display: block;
        position: static; top: auto; right: auto; border: none;
        width: auto; height: auto; padding: 0; border-radius: 0; box-sizing: content-box;
        background: none;
        text-align: left;
        color: var(--lime, #E0FF02);
        font-size: 12px;
        font-weight: 600;
        flex: none;
    }
    .notification-nav .wai-island-badge:empty {
        display: none;
    }
    @media (prefers-reduced-motion: reduce) {
        .wai-island-idle, .wai-island-morph, .wai-island-menu {
            transition: none;
        }
    }

    /* Menu-bar avatar — photo-first with an initials fallback. The old
       markup stacked a photo <img> over a static persone.svg with no
       onerror wiring, so a broken/missing photo URL just rendered as a
       broken-image icon (the svg never actually showed — .img-box has no
       position:relative for it to layer under, and overflow:hidden clips
       it). */
    .wai-avatar-box {
        position: relative;
        display: block;
    }
    .wai-avatar-fallback {
        display: none;
        position: absolute;
        inset: 0;
        align-items: center;
        justify-content: center;
        background: var(--teal-soft, #F5F8F8);
        color: var(--teal, #014653);
        font-size: 14px;
        font-weight: 600;
    }

    /* developer.min.css's base rule renders this span as a red circle
       unconditionally — it never hid itself when there was nothing unread,
       so the dot stayed visible at 0 just as much as at 5. */
    .notification-nav span:empty {
        display: none;
    }

    /* Global search results dropdown (resources/views/resorts/search/index.blade.php)
       — redesigned into a grouped, internally-scrollable results list with
       a fixed header/footer. Some rules need !important to beat
       "#suggesstion-box .serchresult li a span" and other pre-existing
       rules in developer.min.css, which are ID-scoped / apply broadly and
       would otherwise win over these plain classes. */
    #suggesstion-box .serchresult {
        border-radius: 25px !important;
        padding: 0 !important;
        display: flex;
        flex-direction: column;
        max-height: 420px !important;
        overflow: hidden !important;
    }
    .sr-heading-row {
        padding: 14px 18px 10px;
        flex-shrink: 0;
    }
    .sr-heading {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: var(--faint);
    }
    .sr-scroll {
        flex: 1;
        overflow-y: auto;
        max-height: 320px;
        padding: 0 10px;
    }
    .sr-group + .sr-group { margin-top: 6px; }
    .sr-group-heading {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: var(--faint);
        padding: 6px 8px;
    }
    .serchresult ul.sr-group-list { margin: 0; padding: 0; list-style: none; }
    .serchresult ul.sr-group-list li { margin-bottom: 2px !important; }
    .serchresult ul li a.sr-item {
        display: flex !important;
        align-items: flex-start;
        gap: 10px;
        padding: 8px;
        border-radius: 10px;
        transition: background .15s ease;
    }
    .serchresult ul li:hover a.sr-item,
    .search-result ul li.active-li a.sr-item {
        background: rgba(1, 70, 83, 0.08) !important;
        text-decoration: none !important;
    }
    .sr-avatar {
        width: 32px !important;
        height: 32px !important;
        border-radius: 50% !important;
        flex-shrink: 0;
        object-fit: cover;
    }
    .sr-avatar-initials {
        display: flex !important;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 12px !important;
        font-weight: 600;
        padding: 0 !important;
    }
    .sr-icon-tile {
        width: 32px !important;
        height: 32px !important;
        border-radius: 9px !important;
        display: flex !important;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 13px !important;
        padding: 0 !important;
    }
    .sr-icon-tile-announcement { background: #FFF6E5 !important; color: var(--warning); }
    .sr-icon-tile-document { background: #EEF2FF !important; color: #4A5F8A; }
    .sr-icon-tile-muted { background: #F1F3F4 !important; color: var(--muted); }
    .sr-body { min-width: 0; flex: 1; background: none !important; padding: 0 !important; }
    .sr-row-top { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; background: none !important; padding: 0 !important; }
    .sr-row-top strong { font-weight: 600 !important; font-size: 14px !important; color: var(--ink); }
    .sr-highlight { background: rgba(224, 255, 2, 0.55); color: inherit; border-radius: 2px; padding: 0 1px; }
    .sr-badge {
        font-size: 10px !important;
        font-weight: 600;
        padding: 2px 8px !important;
        border-radius: 9px !important;
        white-space: nowrap;
    }
    .sr-badge-employee { background: var(--teal-3) !important; color: var(--teal); }
    .sr-badge-announcement { background: #FFF6E5 !important; color: var(--warning); }
    .sr-badge-document { background: #EEF2FF !important; color: #4A5F8A; }
    .sr-badge-muted { background: #F1F3F4 !important; color: var(--muted); }
    .sr-meta {
        display: block;
        font-size: 12px !important;
        color: var(--muted);
        margin-top: 2px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        background: none !important;
        padding: 0 !important;
        font-weight: 400 !important;
    }
    .sr-empty { padding: 20px 18px; text-align: center; color: var(--faint); font-size: 13px; }
    .sr-footer {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 10px 18px;
        border-top: 1px solid var(--line);
        font-size: 11px;
        color: var(--faint);
    }
    .sr-footer-keys { white-space: nowrap; }
    .sr-footer kbd {
        display: inline-block;
        padding: 1px 5px;
        border-radius: 4px;
        background: #F1F3F4;
        color: var(--muted);
        font-size: 10px;
        font-family: inherit;
    }
    @media (max-width: 576px) {
        #suggesstion-box .serchresult { max-width: 100%; }
        .sr-meta { white-space: normal; }
    }
</style>
<header>
    <!-- As a link -->
    <nav class="bg-body-tertiary">
        <div class="container-fluid">
            <div class="row g-sm-3 g-1 justify-content-between align-items-center">
                <div class="col-xl-auto col-auto ">
                    @php
                        $resort_admin = Auth::guard('resort-admin')->user();
                        $resort_id = $resort_admin->resort_id;
                        $auth_id = isset($resort_admin->GetEmployee) ? $resort_admin->GetEmployee->id : 26;
                        $current_route = Route::currentRouteName();
                        $_empRank = $resort_admin->GetEmployee->rank ?? null;
                        $_hrDeptId = \App\Models\ResortDepartment::where('resort_id', $resort_id)->where('name', 'Human Resources')->value('id');
                        $_empDeptId = $resort_admin->GetEmployee->Dept_id ?? null;
                        if ($resort_admin->is_master_admin == 1) {
                            $_logoRoute = route('resort.Page.Permission');
                        } elseif ($_empRank == 8) {
                            $_logoRoute = route('resort.master.gm_dashboard');
                        } elseif (in_array($_empRank, [3, 7]) || ($_empRank == 1 && $_empDeptId == $_hrDeptId)) {
                            $_logoRoute = route('resort.master.hr_dashboard');
                        } elseif ($_empRank == 1) {
                            $_logoRoute = route('resort.master.excom_dashboard');
                        } else {
                            $_logoRoute = route('resort.master.hod_dashboard');
                        }
                    @endphp
                    <a href="{{ $_logoRoute }}" class="brand-logo"><img src="{{ URL::asset('resorts_assets/images/wisdom-ai.png')}}" /></a>
                </div>

                
                    <div class="col-xl-auto col-auto ">
                        <div class="d-flex">
                            <div class="menu-box dropdown hrvmsshowMenu " id="navbar-vertical-view-menu" style="display: none;">
                                <a href="javascript:void(0);" class="btn ">Menu</a>
                                <div class="dropdown-menu" id="navbar-vertical-view">
                                    
                                </div>
                            </div>

                            <div class="col-xl-auto col-auto hrvmsshowMenu"  style="display: none;">
                                <div class="top-navbar d-flex align-items-center justify-content-between " >

                                    @if($resort_admin->menu_type == 'horizontal')
                                        <div class="navcarosel-box" id="navbar-desktop-view" >
                                            {{-- <div class="carosel-menu">
                                                @foreach ($menu['menu'] as $ak)
                                                <div class="text-center" id="caroselMenuActive">
                                                    <div class="btn-group">
                                                        <a type="button" class="dropdown-toggle" data-bs-toggle="dropdown"
                                                            aria-expanded="false">
                                                            {{ $ak['ModuleName'] }}
                                                        </a>
                                                        <div class="dropdown-menu carosel-nav-menu">

                                                            @php 
                                                                $Submenu = Common::GetResortMenuPage($ak['ModuleId'] )
                                                            @endphp
                                                            <ul class="nav flex-column">
                                                            
                                                                    @foreach ($Submenu as $sm)
                                                                    
                                                                        @if($sm['TypeOfPage'] ==  "InsideOfMenu" && $sm['Type'] =='normal')
                                                                            @if(Common::resortHasPermission( $ak['ModuleId'],$sm['Page_id'],config('settings.resort_permissions.view')))
                                                                                <li>
                                                                                    <a class="dropdown-item" href="{{ route($sm['route'])}}">
                                                                                    {{$sm['PageName']}}
                                                                                    </a>
                                                                                </li>
                                                                            @else
                                                                            
                                                                                <li>
                                                                                    <a class="dropdown-item" href="javascript:void(0)">
                                                                                    No Permission Found
                                                                                    </a>
                                                                                </li>
                                                                            @endif
                                                                        @endif
                                                                    @endforeach
                                                                    
                                                            
                                                        
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            
                                                @endforeach
                                            </div>
                                            --}}
                                        </div>
                                    @endif
                                    
                                    <ul class="navbar-nav  flex-row align-items-center col-auto">
                                        <li class="nav-item search-bar-nav " >
                                            <input type="text" class="form-control search-input" placeholder="Search for Employee, Holidays, Categories, etc." id="search-Input" autocomplete="off" >
                                            <a class="serch-icon search-btn-icon">
                                                <img src="{{ URL::asset('resorts_assets/images/search.svg')}}" alt="" class="img-fluid">
                                            </a>
                                            {{-- <a class="serch-icon close-x text-white" >
                                                <i class="fa-solid fa-xmark close-x"></i>
                                            </a> --}}
                                            <div id="suggesstion-box" class="search-result"></div>
                                        </li>
                                        <li class="nav-item nav-icon search-icon-nav">
                                            <a href="javascript:void(0);" class="btn-serchbox">
                                                <img src="{{ URL::asset('resorts_assets/images/search.svg')}}" alt="" class="img-fluid" />
                                            </a>
                                        </li>
                                        
                                        <li class="nav-item nav-icon notification-nav">
                                            <div class="wai-island" id="waiIsland" data-state="idle">
                                                <div class="wai-island-idle">
                                                    <img src="{{ URL::asset('resorts_assets/images/bell.svg')}}" alt="" class="wai-island-bell" />
                                                    <span class="wai-cnt">@if(Auth::guard('resort-admin')->user()->type != "super" && Auth::guard('resort-admin')->check()){{ App\Helpers\Common::getNotificationCount(Auth::guard('resort-admin')->user()->resort_id,Auth::guard('resort-admin')->user()->GetEmployee->id) }}@endif</span>
                                                </div>
                                                <div class="wai-island-morph">
                                                    <div class="wai-island-menu">
                                                        <button type="button" class="wai-island-opt notification-btn" data-wai-open="notif">
                                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
                                                            <span class="wai-island-ol">Notifications</span>
                                                            <span class="wai-island-badge" data-wai-mirror></span>
                                                        </button>
                                                        <button type="button" class="wai-island-opt" data-wai-open="messages">
                                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-8.5 8.5 8.5 8.5 0 0 1-3.8-.9L3 21l1.9-5.7A8.5 8.5 0 1 1 21 11.5z"/></svg>
                                                            <span class="wai-island-ol">Messages</span>
                                                            <span class="wai-island-badge" id="msgCount"></span>
                                                        </button>
                                                        <button type="button" class="wai-island-opt" data-wai-open="waibot">
                                                            <svg class="wai-island-ai" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l1.8 5.3L19 9l-5.2 1.7L12 16l-1.8-5.3L5 9l5.2-1.7z"/></svg>
                                                            <span class="wai-island-ol">Ask WAI</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                        <li class="nav-item nav-icon d-lg-none">
                                            <a href="javascript:void(0);" id="toggle-icon2" class="toggle-icon">
                                                <label for="toggle-check">
                                                    <input type="checkbox" id="toggle-check" />
                                                    <span></span>
                                                    <span></span>
                                                    <span></span>
                                                </label>
                                            </a>
                                        </li>
                                        <li class="nav-item dropdown profile-dropdown">
                                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                @php
                                                    $waiHeaderUser = Auth::guard('resort-admin')->user();
                                                    $waiHeaderInitials = strtoupper(substr($waiHeaderUser->first_name ?? '', 0, 1) . substr($waiHeaderUser->last_name ?? '', 0, 1)) ?: 'U';
                                                @endphp
                                                <span class="img-box wai-avatar-box">
                                                    <img src=" {{ Common::getResortUserPicture($waiHeaderUser->id) }}" alt="" class="img-fluid" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" />
                                                    <span class="wai-avatar-fallback">{{ $waiHeaderInitials }}</span>
                                                </span>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-end bg-gradient py-0">
                                                <ul>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('resort.user.profile') }}"><span class="img-box"><img
                                                                    src="{{ URL::asset('resorts_assets/images/user.svg')}}" alt=""
                                                                    class="img-fluid"></span>Profile</a>
                                                    </li>
                                                
                                                        <li>
                                                            <a class="dropdown-item" href="{{route('resort.sitesettings')}}"><span class="img-box"><img
                                                                        src="{{ URL::asset('resorts_assets/images/settings.svg')}}" alt=""
                                                                        class="img-fluid"></span>Settings</a>
                                                        </li>
                                                
                                                    <li class="d-flex justify-content-between align-items-center">

                                                        <a class="dropdown-item" href="#"><span class="img-box"><img
                                                                    src="{{URL::asset('resorts_assets/images/currency.svg')}}" alt=""
                                                                    class="img-fluid"></span>Currency</a>

                                                        <div class="switch-toggle @if (Common::GetResortCurrentCurrency()  == 'MVR' )   select-left @else   select-right @endif  " id="container_currency">
                                                            {{-- <div id="item"></div> --}}
                                                            <div class="left">
                                                                <img src="{{URL::asset('resorts_assets/images/currency-sign.svg')}}" alt="icon">
                                                            </div>
                                                            <div class="right">
                                                                <img src="{{ URL::asset('resorts_assets/images/dollar-sign.svg')}}" alt="icon">
                                                            </div>
                                                        </div>

                                                    </li>

                                                    <li class="d-flex justify-content-between align-items-center hor-ver-box">
                                                        <a class="dropdown-item" href="#"><span class="img-box"><img
                                                                    src="{{URL::asset('resorts_assets/images/currency.svg')}}" alt=""
                                                                    class="img-fluid"></span>Menu Type</a>

                                                        <div class="switch-toggle @if (Common::getMenuTypeByUser()  == 'horizontal' ) select-horizontal @else select-vertical @endif  " id="container_menuType">
                                                            {{-- <div id="item"></div> --}}
                                                            <div class="horizontal">
                                                                <img src="{{URL::asset('resorts_assets/images/arrows-alt-h.svg')}}" alt="icon"> {{-- horizontal --}}
                                                            </div>
                                                            <div class="vertical" >
                                                                <img src="{{ URL::asset('resorts_assets/images/arrows-alt-h.svg')}}" alt="icon"> {{-- vertical --}}
                                                            </div>
                                                        </div>
                                                    </li>
                                                    
                                                    <li>
                                                        <a class="dropdown-item" href="javascript::void(0)" id="logout"><span class="img-box"><img
                                                                    src="{{ URL::asset('resorts_assets/images/log-out.svg')}}" alt=""
                                                                    class="img-fluid"></span>Logout
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                    </div>

                <div class="col-xl-auto col-auto">
                    <a href="{{ $_logoRoute }}" class="tooltip-left brand-logo resort-logo">
                        <img src="{{ Common::GetResortLogo(Auth::guard('resort-admin')->user()->resort_id) }}" class="resort-image-header" />
                        <span class="tooltip-text-left">{{ Auth::guard('resort-admin')->user()->resort->resort_name }}</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <div class="serch-box">
            <div>
                <div class="position-relative">
                    <input type="text" class="form-control search-input"  placeholder="Search for Employee, Holidays, Categories, etc."/>
                    <a class="serch-icon">
                        <img src="{{ URL::asset('resorts_assets/images/search.svg')}}" alt="" class="img-fluid">
                    </a>
                    <div id="suggesstion-box" class="search-result"></div>
                </div>
                <a class="close-icon">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            </div>
    </div>
</header>
