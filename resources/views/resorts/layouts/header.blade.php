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
                                            <span>@if(Auth::guard('resort-admin')->user()->type != "super" && Auth::guard('resort-admin')->check()){{ App\Helpers\Common::getNotificationCount(Auth::guard('resort-admin')->user()->resort_id,Auth::guard('resort-admin')->user()->GetEmployee->id) }}@endif</span>
                                            <a href="javascript:void(0);" class="notification-btn">
                                                <img src="{{ URL::asset('resorts_assets/images/bell.svg')}}" alt="" class="img-fluid" />
                                            </a>
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
                                                <span class="img-box">
                                                    <img src=" {{ Common::getResortUserPicture(Auth::guard('resort-admin')->user()->id) }}" alt="" class="img-fluid" />
                                                    <img src=" {{ URL::asset('resorts_assets/images/persone.svg')}}" alt="" class="img-fluid" />
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
