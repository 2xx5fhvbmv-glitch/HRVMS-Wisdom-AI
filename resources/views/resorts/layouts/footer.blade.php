<!-- footer -->
<footer class="wai-footer">
    <span class="cp">©{{ date('Y') }} Wisdom AI Pvt Ltd</span>
    <div class="taglines">
        <span class="tp"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>Every Data Shielded</span>
        <span class="tp"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 4.9L19 9.8l-4.1 2.9L16 18l-4-2.7L8 18l1.1-5.3L5 9.8l5.1-1.9z"/></svg>Creativity Secured</span>
        <span class="tp"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>All Innovations Protected</span>
    </div>
</footer>
<div class="navigation-wrapper " >
    <div class="navigation-box bg-gradient">
        <div class="navbar-content" id="navbar-mobile-view">
              
        </div>
        {{-- <div class="navbar-content">
            <ul class="nav flex-column">
                @php 
                    $resort_id = Auth::guard('resort-admin')->user()->resort_id ;
                    $auth_id = isset(Auth::guard('resort-admin')->user()->GetEmployee) ? Auth::guard('resort-admin')->user()->GetEmployee->id : 26;
                   
                    $menu = Common::GetResortMenu($resort_id , Route::currentRouteName());
                @endphp
                @foreach ($menu['menu'] as $s=>$ak)
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" aria-current="page" href="javascript:void(0)" id="dropdownMenuButton{{$s}}"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <span>   {{ $ak['ModuleName'] }}</span>
                    </a>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton{{$s}}">
                        <ul class="nav flex-column">
                            @php 
                                $Submenu = Common::GetResortMenuPage($ak['ModuleId']);
                            @endphp
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
                </li>
                @endforeach
             
            </ul>
        </div> --}}
    </div>
</div>

<div class="notification-wrapper ">
    <div class="notification-title">
        <div class="ntf-kicker">
            <span class="ntf-kicker-l"><span class="ntf-dot"></span>Notifications</span>
            <a href="{{ route('resort.Mark.NotificationList')}}" class="ntf-viewall">View all &rarr;</a>
        </div>
        <div class="ntf-heading">Recent activity</div>
    </div>
    <div class="notification-body">
        @php
            $resort_id = Auth::guard('resort-admin')->user()->resort_id;
            $user_id =  isset(Auth::guard('resort-admin')->user()->GetEmployee) ? Auth::guard('resort-admin')->user()->GetEmployee->id : '' ;
            print_r(Common::ResortNotification($user_id,$resort_id));
        @endphp

    </div>
</div>

{{-- Wisdom AI — floating HR assistant (self-gates by access tier) --}}
@include('resorts.partials.wisdom-chat')
