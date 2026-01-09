<header>
    <!-- As a link -->
    <nav class="bg-body-tertiary">
        <div class="container-fluid">
            <div class="row g-sm-3 g-1 justify-content-between align-items-center">
                <div class="col-xl-auto col-auto ">
                    <a href="<?php echo e(route('resort.workforceplan.dashboard')); ?>" class="brand-logo"><img src="<?php echo e(URL::asset('resorts_assets/images/wisdom-ai.png')); ?>" /></a>
                </div>
                <?php 
                    $resort_admin = Auth::guard('resort-admin')->user();
                    $resort_id = Auth::guard('resort-admin')->user()->resort_id;
                    $auth_id = isset(Auth::guard('resort-admin')->user()->GetEmployee) ? Auth::guard('resort-admin')->user()->GetEmployee->id : 26;
                    $current_route = Route::currentRouteName();
                    
                ?>

                
                    <div class="col-xl-auto col-auto ">
                        <div class="d-flex">
                            <div class="menu-box dropdown hrvmsshowMenu " id="navbar-vertical-view-menu" style="display: none;">
                                <a href="javascript:void(0);" class="btn ">Menu</a>
                                <div class="dropdown-menu" id="navbar-vertical-view">
                                    
                                </div>
                            </div>

                            <div class="col-xl-auto col-auto hrvmsshowMenu"  style="display: none;">
                                <div class="top-navbar d-flex align-items-center justify-content-between " >

                                    <?php if($resort_admin->menu_type == 'horizontal'): ?>
                                        <div class="navcarosel-box" id="navbar-desktop-view" >
                                            
                                        </div>
                                    <?php endif; ?>
                                    
                                    <ul class="navbar-nav  flex-row align-items-center col-auto">
                                        <li class="nav-item search-bar-nav " >
                                            <input type="text" class="form-control search-input" placeholder="Search for Employee, Holidays, Categories, etc." id="search-Input" autocomplete="off" >
                                            <a class="serch-icon search-btn-icon">
                                                <img src="<?php echo e(URL::asset('resorts_assets/images/search.svg')); ?>" alt="" class="img-fluid">
                                            </a>
                                            
                                            <div id="suggesstion-box" class="search-result"></div>
                                        </li>
                                        <li class="nav-item nav-icon search-icon-nav">
                                            <a href="javascript:void(0);" class="btn-serchbox">
                                                <img src="<?php echo e(URL::asset('resorts_assets/images/search.svg')); ?>" alt="" class="img-fluid" />
                                            </a>
                                        </li>
                                        
                                        <li class="nav-item nav-icon notification-nav">
                                            <span><?php if(Auth::guard('resort-admin')->user()->type != "super" && Auth::guard('resort-admin')->check()): ?> <?php echo e(App\Helpers\Common::getNotificationCount(Auth::guard('resort-admin')->user()->resort_id,Auth::guard('resort-admin')->user()->GetEmployee->id)); ?> <?php endif; ?></span>
                                            <a href="javascript:void(0);" class="notification-btn">
                                                <img src="<?php echo e(URL::asset('resorts_assets/images/bell.svg')); ?>" alt="" class="img-fluid" />
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
                                                    <img src=" <?php echo e(Common::getResortUserPicture(Auth::guard('resort-admin')->user()->id)); ?>" alt="" class="img-fluid" />
                                                    <img src=" <?php echo e(URL::asset('resorts_assets/images/persone.svg')); ?>" alt="" class="img-fluid" />
                                                </span>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-end bg-gradient py-0">
                                                <ul>
                                                    <li>
                                                        <a class="dropdown-item" href="<?php echo e(route('resort.user.profile')); ?>"><span class="img-box"><img
                                                                    src="<?php echo e(URL::asset('resorts_assets/images/user.svg')); ?>" alt=""
                                                                    class="img-fluid"></span>Profile</a>
                                                    </li>
                                                
                                                        <li>
                                                            <a class="dropdown-item" href="<?php echo e(route('resort.sitesettings')); ?>"><span class="img-box"><img
                                                                        src="<?php echo e(URL::asset('resorts_assets/images/settings.svg')); ?>" alt=""
                                                                        class="img-fluid"></span>Settings</a>
                                                        </li>
                                                
                                                    <li class="d-flex justify-content-between align-items-center">

                                                        <a class="dropdown-item" href="#"><span class="img-box"><img
                                                                    src="<?php echo e(URL::asset('resorts_assets/images/currency.svg')); ?>" alt=""
                                                                    class="img-fluid"></span>Currency</a>

                                                        <div class="switch-toggle <?php if(Common::GetResortCurrentCurrency()  == 'MVR' ): ?>   select-left <?php else: ?>   select-right <?php endif; ?>  " id="container_currency">
                                                            
                                                            <div class="left">
                                                                <img src="<?php echo e(URL::asset('resorts_assets/images/currency-sign.svg')); ?>" alt="icon">
                                                            </div>
                                                            <div class="right">
                                                                <img src="<?php echo e(URL::asset('resorts_assets/images/dollar-sign.svg')); ?>" alt="icon">
                                                            </div>
                                                        </div>

                                                    </li>

                                                    <li class="d-flex justify-content-between align-items-center hor-ver-box">
                                                        <a class="dropdown-item" href="#"><span class="img-box"><img
                                                                    src="<?php echo e(URL::asset('resorts_assets/images/currency.svg')); ?>" alt=""
                                                                    class="img-fluid"></span>Menu Type</a>

                                                        <div class="switch-toggle <?php if(Common::getMenuTypeByUser()  == 'horizontal' ): ?> select-horizontal <?php else: ?> select-vertical <?php endif; ?>  " id="container_menuType">
                                                            
                                                            <div class="horizontal">
                                                                <img src="<?php echo e(URL::asset('resorts_assets/images/arrows-alt-h.svg')); ?>" alt="icon"> 
                                                            </div>
                                                            <div class="vertical" >
                                                                <img src="<?php echo e(URL::asset('resorts_assets/images/arrows-alt-h.svg')); ?>" alt="icon"> 
                                                            </div>
                                                        </div>
                                                    </li>
                                                    
                                                    <li>
                                                        <a class="dropdown-item" href="javascript::void(0)" id="logout"><span class="img-box"><img
                                                                    src="<?php echo e(URL::asset('resorts_assets/images/log-out.svg')); ?>" alt=""
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
                    <a href="<?php echo e(route('resort.workforceplan.dashboard')); ?>" class="tooltip-left brand-logo resort-logo">
                        <img src="<?php echo e(Common::GetResortLogo(Auth::guard('resort-admin')->user()->resort_id)); ?>" class="resort-image-header" />
                        <span class="tooltip-text-left"><?php echo e(Auth::guard('resort-admin')->user()->resort->resort_name); ?></span>
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
                        <img src="<?php echo e(URL::asset('resorts_assets/images/search.svg')); ?>" alt="" class="img-fluid">
                    </a>
                    <div id="suggesstion-box" class="search-result"></div>
                </div>
                <a class="close-icon">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            </div>
    </div>
</header>
<?php /**PATH C:\wamp64\www\Wisdom-Ai\HRVMS-Wisdom-AI\resources\views/resorts/layouts/header.blade.php ENDPATH**/ ?>