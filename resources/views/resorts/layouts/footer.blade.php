<!-- footer -->
<footer>
    <div class="footer_wrap">
        <p class="m-0 text-light copy">©{{date('Y')}}  Wisdom AI Pvt Ltd | Every Data Shielded | Creativity Secured | All
            innovations Protected. </p>
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
    <div class="notification-title d-flex justify-content-between">
        <h5>Notifications</h5>
        <a href="{{ route('resort.Mark.NotificationList')}}" class="text-underline btn-link-yellow">View All</a>
    </div>
    <div class="notification-body">
        @php
            $resort_id = Auth::guard('resort-admin')->user()->resort_id;
            $user_id =  isset(Auth::guard('resort-admin')->user()->GetEmployee) ? Auth::guard('resort-admin')->user()->GetEmployee->id : '' ;
            print_r(Common::ResortNotification($user_id,$resort_id));
        @endphp
        
    </div>
</div>
