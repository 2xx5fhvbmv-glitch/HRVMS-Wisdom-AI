<header>
    <!-- As a link -->
    <nav class="bg-body-tertiary">
        <div class="container-fluid">
            <div class="row g-sm-3 g-1 justify-content-between align-items-center">
                <div class="col-xl-auto col-auto ">
                    <a href="{{ route('resort.workforceplan.dashboard') }}" class="brand-logo"><img src="{{ URL::asset('resorts_assets/images/wisdom-ai.png')}}" /></a>
                </div>
                @php 
                        $resort_id = Auth::guard('resort-admin')->user()->resort_id ;
                    $auth_id = isset(Auth::guard('resort-admin')->user()->GetEmployee) ? Auth::guard('resort-admin')->user()->GetEmployee->id : 3;
                
                    $menu = Common::GetResortMenu($resort_id , $auth_id)
                @endphp

                <div class="col-xl-auto col-auto ">
                    <div class="top-navbar d-flex align-items-center justify-content-between">
                        <div class="navcarosel-box">
                            <div class="carosel-menu">
                                @foreach ($menu as $ak)
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
                  
                        </div>
                        
                        <ul class="navbar-nav  flex-row align-items-center col-auto">
                            <li class="nav-item nav-icon">
                                <a href="#" class="btn-serchbox">
                                    <img src="{{ URL::asset('resorts_assets/images/search.svg')}}" alt="" class="img-fluid" />
                                </a>
                            </li>
                            <li class="nav-item nav-icon notification-nav">
                                <a href="#" class="notification-btn">
                                    <img src="{{ URL::asset('resorts_assets/images/bell.svg')}}" alt="" class="img-fluid" />
                                </a>
                            </li>
                            <li class="nav-item nav-icon d-lg-none">
                                <a href="#" id="toggle-icon2" class="toggle-icon">
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
                                        <li>
                                            <a class="dropdown-item" href="{{route('resort.logout')}}"><span class="img-box"><img
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
                <div class="col-xl-auto col-auto">
                    <a href="{{ route('resort.workforceplan.dashboard') }}" class="brand-logo resort-logo">
                        <img  src="{{ Common::GetResortLogo(Auth::guard('resort-admin')->user()->resort_id)}}" />
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <div class="serch-box">
        <div>
            <div class="position-relative">
                <input type="text" class="form-control" />
                <a class="serch-icon    ">
                    <img src="{{ URL::asset('resorts_assets/images/search.svg')}}" alt="" class="img-fluid">
                </a>
            </div>
            <a class="close-icon">
                <i class="fa-solid fa-xmark"></i>
            </a>
        </div>
    </div>
</header>
