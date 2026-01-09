<header>
    <!-- As a link -->
    <nav class="bg-body-tertiary nav-staffShop" id="nav-staffShop" style="display: none;">
        <div class="container-fluid">
            <div class="row g-sm-3 g-1 justify-content-between align-items-center">
                <div class="col-xl-auto col-auto ">
                    <a href="{{ route('shopkeeper.dashboard') }}" class="brand-logo">
                        <img src="{{ URL::asset('resorts_assets/images/wisdom-ai.png')}}" />
                    </a>
                </div>
                <div class="col-xl-auto col-auto ">
                    <div class="top-navbar d-flex align-items-center justify-content-between">
                        <div class="navcarosel-box">
                            <div class="carosel-menu" >
                                <div class="text-center">
                                    <div class="btn-group">
                                        <a type="button" href="{{route('shopkeeper.dashboard')}}" class="dropdown-toggle {{ (Route::currentRouteName()=='shopkeeper.dashboard') ? 'active': '' }}" 
                                            aria-expanded="false">
                                            Dashboard
                                        </a>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <div class="btn-group">
                                        <a type="button" href="{{route('shopkeeper.products')}}" class="dropdown-toggle {{ (Route::currentRouteName()=='shopkeeper.products') ? 'active': '' }}" 
                                            aria-expanded="false">
                                            Products
                                        </a>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <div class="btn-group">
                                        <a type="button" href="{{route('shopkeeper.payment.history')}}" class="dropdown-toggle {{ (Route::currentRouteName()=='shopkeeper.payment.history') ? 'active': '' }}" 
                                            aria-expanded="false">
                                            Payment History
                                        </a>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <div class="btn-group">
                                        <a type="button" href="{{route('shopkeeper.configuration')}}" class="dropdown-toggle {{ (Route::currentRouteName()=='shopkeeper.configuration') ? 'active': '' }}" 
                                            aria-expanded="false">
                                            Configuration
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <ul class="navbar-nav  flex-row align-items-center col-auto">
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
                                        <img src=" {{  Auth::guard('shopkeeper')->user()->profile_photo ? asset(config('settings.ShopkeeperProfile_folder') . '/' . Auth::guard('shopkeeper')->user()->profile_photo) : asset('resorts_assets/images/persone.svg') }}" alt="" class="img-fluid" />
                                        <img src=" {{ URL::asset('resorts_assets/images/persone.svg')}}" alt="" class="img-fluid" />
                                    </span>
                                </a>
                                
                                <div class="dropdown-menu dropdown-menu-end bg-gradient py-0">
                                    <ul>
                                        <li>
                                            <a class="dropdown-item" href="{{route('shopkeeper.profile') }}"><span class="img-box">
                                                <img src="{{ URL::asset('resorts_assets/images/user.svg')}}" alt="" class="img-fluid"></span>Profile
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{route('shopkeeper.logout')}}"><span class="img-box">
                                                <img src="{{ URL::asset('resorts_assets/images/log-out.svg')}}" alt="" class="img-fluid"></span>Logout
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-xl-auto col-auto">
                    <a href="{{ route('shopkeeper.dashboard') }}" class="tooltip-left brand-logo resort-logo">
                        <img src="{{ Common::GetResortLogo(Auth::guard('shopkeeper')->user()->resort_id)}}"  class="shopkeeper-image-header"/>
                        <span class="tooltip-text-left">{{ Auth::guard('shopkeeper')->user()->resort->resort_name }}</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="serch-box">
        <div>
            <div class="position-relative">
                <input type="text" class="form-control" />
                <a class="serch-icon">
                    <img src="{{ URL::asset('resorts_assets/images/search.svg')}}" alt="" class="img-fluid">
                </a>
            </div>
            <a class="close-icon">
                <i class="fa-solid fa-xmark"></i>
            </a>
        </div>
    </div>
</header>