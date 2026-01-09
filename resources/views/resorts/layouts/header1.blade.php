<header>
    <!-- As a link -->
    <nav class="bg-body-tertiary">
        <div class="container-fluid">
            <div class="row g-sm-3 g-1 justify-content-between align-items-center">
                <div class="col-xl-auto col-auto ">
                    <a href="{{ route('resort.workforceplan.dashboard') }}" class="brand-logo"><img src="{{ URL::asset('resorts_assets/images/wisdom-ai.png')}}" /></a>
                </div>

                <div class="col-xl-auto col-auto ">
                    <div class="top-navbar d-flex align-items-center justify-content-between">
                        <div class="navcarosel-box">
                            <div class="carosel-menu">
                                <div class="text-center" id="{{ (Route::currentRouteName()=='resort.workforceplan.dashboard' || Route::currentRouteName()=='resort.manning.index' || Route::currentRouteName()=='resort.budget.consolidatebudget' ||
                                            Route::currentRouteName()=='resort.budget.viewbudget' ||
                                            Route::currentRouteName()=='resort.budget.comparebudget' ||
                                            Route::currentRouteName()=='resort.budget.config' ||
                                            Route::currentRouteName()=='resort.budget.index' ||
                                            Route::currentRouteName()=='resort.benifitgrid.index' ||
                                            Route::currentRouteName()=='resort.benifitgrid.create' ||
                                            Route::currentRouteName()=='resort.benifitgrid.edit' ||
                                            Route::currentRouteName()=='resort.budget.index' ||
                                            Route::currentRouteName()=='resort.Add.Employee' ||
                                            Route::currentRouteName()=='resort.workforceplan.filledpositions' ||
                                            Route::currentRouteName()=='resort.workforceplan.hoddashboard' ||
                                            Route::currentRouteName()=='resort.department.wise.budget.data'||
                                            Route::currentRouteName()=='resort.budget.manning'
                                            ) ? 'caroselMenuActive': '' }}">
                                    <div class="btn-group">
                                        <a type="button" class="dropdown-toggle
                                            {{ (Route::currentRouteName()=='resort.workforceplan.dashboard' || Route::currentRouteName()=='resort.manning.index' || Route::currentRouteName()=='resort.budget.consolidatebudget' ||
                                            Route::currentRouteName()=='resort.budget.viewbudget' ||
                                            Route::currentRouteName()=='resort.budget.comparebudget' ||
                                            Route::currentRouteName()=='resort.budget.config' ||
                                            Route::currentRouteName()=='resort.budget.index' ||
                                            Route::currentRouteName()=='resort.benifitgrid.index' ||
                                            Route::currentRouteName()=='resort.benifitgrid.create' ||
                                            Route::currentRouteName()=='resort.benifitgrid.edit' ||
                                            Route::currentRouteName()=='resort.budget.index' ||
                                            Route::currentRouteName()=='resort.Add.Employee' ||
                                            Route::currentRouteName()=='resort.workforceplan.filledpositions' ||
                                            Route::currentRouteName()=='resort.workforceplan.hoddashboard' ||
                                            Route::currentRouteName()=='resort.department.wise.budget.data'||
                                            Route::currentRouteName()=='resort.budget.manning'
                                            ) ? 'active': '' }}" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            Workforce Planning
                                        </a>
                                        <div class="dropdown-menu carosel-nav-menu">
                                            <ul class="nav flex-column">
                                                @if(Common::resortHasPermission( config('settings.resort_permissions.view')))
                                                    <li>
                                                        <a class="dropdown-item" href="{{route('resort.workforceplan.dashboard')}}">
                                                            Dashboard
                                                        </a>
                                                    </li>
                                                @endif
                                                @if(Common::resortHasPermission( config('settings.resort_permissions.view')))
                                                    <li>
                                                        <a class="dropdown-item" href="{{route('resort.budget.manning') }}">
                                                            Manning
                                                        </a>
                                                    </li>
                                                @endif
                                                @if(Common::resortHasPermission( config('settings.resort_permissions.view')))
                                                    <li>
                                                        <a class="dropdown-item" href="{{route('resort.budget.viewbudget')}}">
                                                            Budget
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{route('resort.budget.consolidatebudget')}}">
                                                            Consolidated budget
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{route('resort.budget.config')}}">
                                                            Configuration
                                                        </a>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center" id="{{ (Route::currentRouteName()=='resort.recruitement.hrdashboard' || Route::currentRouteName()=='resort.recruitement.admindashboard' || Route::currentRouteName()=='resort.recruitement.hoddashboard' || Route::currentRouteName()=='resort.vacancies.add' || Route::currentRouteName()=='resort.vacancies.view' || Route::currentRouteName()=='offline-interview.index' || Route::currentRouteName()=='resort.ta.emailtemplates') ? 'caroselMenuActive': '' }}">
                                    <div class="btn-group">
                                        <a type="button" class="dropdown-toggle {{ (Route::currentRouteName()=='resort.recruitement.hrdashboard' || Route::currentRouteName()=='resort.recruitement.admindashboard' || Route::currentRouteName()=='resort.recruitement.hoddashboard' || Route::currentRouteName()=='resort.vacancies.add' || Route::currentRouteName()=='resort.vacancies.view' || Route::currentRouteName()=='offline-interview.index' || Route::currentRouteName()=='resort.ta.emailtemplates') ? 'active': '' }}" data-bs-toggle="dropdown" aria-expanded="false">
                                            Talent Acquisition
                                        </a>
                                        <div class="dropdown-menu carosel-nav-menu">
                                            <ul class="nav flex-column">
                                                @if(Common::resortHasPermission( config('settings.resort_permissions.view')))
                                                    <li>
                                                        <a class="dropdown-item" href="{{route('resort.recruitement.hrdashboard')}}">
                                                            Dashboard
                                                        </a>
                                                    </li>
                                                @endif

                                                <li><a class="dropdown-item" href="{{ route('resort.ta.emailtemplates') }}"> Email Templates</a></li>
                                                <li><a class="dropdown-item" href="#">Talent Pool</a></li>
                                                <li><a class="dropdown-item" href="{{ route('offline-interview.index') }}">Offline Interview</a>
                                                </li>
                                                </li>
                                                <li><a class="dropdown-item" href="{{route('resort.ta.configration')}}">Configuration</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center" id="{{ (Route::currentRouteName()=='resort.timeandattendance.dashboard'
                                          || Route::currentRouteName()=='resort.timeandattendance.Configration'
                                          || Route::currentRouteName()=='resort.timeandattendance.LocationHistory'
                                           || Route::currentRouteName()=='resort.timeandattendance.OverTime'
                                           || Route::currentRouteName()=='resort.timeandattendance.CreateDutyRoster'
                                          ) ? 'caroselMenuActive': '' }}">
                                    <div class="btn-group">
                                        <a type="button" class="dropdown-toggle
                                          {{ (Route::currentRouteName()=='resort.timeandattendance.dashboard'
                                          || Route::currentRouteName()=='resort.timeandattendance.Configration'
                                          || Route::currentRouteName()=='resort.timeandattendance.LocationHistory'
                                           || Route::currentRouteName()=='resort.timeandattendance.OverTime'
                                           || Route::currentRouteName()=='resort.timeandattendance.CreateDutyRoster'
                                           || Route::currentRouteName()=='resort.timeandattendance.employee'

                                           || Route::currentRouteName()=='resort.timeandattendance.AttandanceRegister'
                                           || Route::currentRouteName()=='resort.timeandattendance.LocationHistory'


                                          ) ? 'active': '' }}"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                           Time And Attendance
                                        </a>
                                        <div class="dropdown-menu carosel-nav-menu">
                                            <ul class="nav flex-column">
                                                <li><a class="dropdown-item" href="{{route('resort.timeandattendance.dashboard')}}"> Dashboard</a></li>
                                                <li><a class="dropdown-item" href="{{route('resort.timeandattendance.CreateDutyRoster')}}">Duty Roster</a></li>
                                                <li><a class="dropdown-item" href="{{route('resort.timeandattendance.employee')}}">Employee</a></li>
                                                <li><a class="dropdown-item" href="{{route('resort.timeandattendance.AttandanceRegister')}}">Attendance Register</a>
                                                </li>
                                                {{-- <li><a class="dropdown-item" href="#">Geo Fencing</a></li> --}}
                                                <li><a class="dropdown-item" href="{{ route('resort.timeandattendance.LocationHistory')}}">Location History</a></li>
                                                <li><a class="dropdown-item" href="{{ route('resort.timeandattendance.OverTime')}}">Overtime </a></li>
                                                <li><a class="dropdown-item" href="{{ route('resort.timeandattendance.Configration') }}">Configuration </a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center" id="{{ (Route::currentRouteName()=='leave.dashboard' || Route::currentRouteName()=='leave.apply' || Route::currentRouteName()=='leave.request' || Route::currentRouteName()=='leave.calendar' || Route::currentRouteName()=='resort.upcomingholiday.list' || Route::currentRouteName()=='leave.configration' || Route::currentRouteName()=='leave.hoddashboard' || Route::currentRouteName()=='leave.admindashboard' || Route::currentRouteName()=='resort.boardingpass.list'|| Route::currentRouteName()=='resort.boardingpass.apply') ? 'caroselMenuActive': '' }}">
                                    <div class="btn-group">
                                        <a type="button" class="dropdown-toggle {{ (Route::currentRouteName()=='leave.dashboard' || Route::currentRouteName()=='leave.apply' || Route::currentRouteName()=='leave.request' || Route::currentRouteName()=='leave.calendar' || Route::currentRouteName()=='resort.upcomingholiday.list' || Route::currentRouteName()=='leave.configration' || Route::currentRouteName()=='leave.hoddashboard' || Route::currentRouteName()=='leave.admindashboard' || Route::currentRouteName()=='resort.boardingpass.list'|| Route::currentRouteName()=='resort.boardingpass.apply') ? 'active': '' }}" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            Leave
                                        </a>
                                        <div class="dropdown-menu carosel-nav-menu">
                                            <ul class="nav flex-column">
                                                <li><a class="dropdown-item" href="{{route('leave.dashboard')}}">Dashboard</a></li>
                                                <li><a class="dropdown-item" href="{{route('leave.apply')}}">Apply a Leave</a></li>
                                                <li><a class="dropdown-item" href="{{route('leave.request')}}">Leave Requests</a></li>
                                                <li><a class="dropdown-item" href="{{route('leave.calendar')}}">Calendar</a></li>
                                                <li><a class="dropdown-item" href="{{route('resort.upcomingholiday.list')}}">Holidays</a></li>
                                                <li><a class="dropdown-item" href="{{route('resort.boardingpass.list')}}">Boarding Pass Request</a></li>
                                                <li><a class="dropdown-item" href="{{route('resort.boardingpass.apply')}}">Apply Boarding Pass</a></li>
                                                <li><a class="dropdown-item" href="{{route('leave.configration')}}">Configuration</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center" id="{{ (Route::currentRouteName()=='resort.accommodation.dashboard'
                                        || Route::currentRouteName()=='resort.accommodation.hoddashboard'
                                        || Route::currentRouteName()=='resort.accommodation.admindashboard'
                                        || Route::currentRouteName()=='resort.accommodation.inventory'
                                        || Route::currentRouteName()=='resort.accommodation.AssignAccommation'
                                        || Route::currentRouteName()=='resort.accommodation.AccommodationMaster'
                                        || Route::currentRouteName()=='resort.accommodation.EmployeeAccommodation'                                        || Route::currentRouteName()=='resort.accommodation.EmployeeAccommodation'
                                        || Route::currentRouteName()=='resort.accommodation.CreateMaintenanceRequest'

                                        || Route::currentRouteName()=='resort.accommodation.config.index') ? 'caroselMenuActive': '' }}">
                                    <div class="btn-group">
                                        <a type="button" class="dropdown-toggle
                                        {{ (Route::currentRouteName()=='resort.accommodation.dashboard'
                                        || Route::currentRouteName()=='resort.accommodation.hoddashboard'
                                        || Route::currentRouteName()=='resort.accommodation.admindashboard'
                                        || Route::currentRouteName()=='resort.accommodation.inventory'
                                        || Route::currentRouteName()=='resort.accommodation.AssignAccommation'
                                        || Route::currentRouteName()=='resort.accommodation.AccommodationMaster'
                                        || Route::currentRouteName()=='resort.accommodation.EmployeeAccommodation'
                                        || Route::currentRouteName()=='resort.accommodation.AvailableAccommodation'
                                        || Route::currentRouteName()=='resort.accommodation.CreateMaintenanceRequest'
                                        || Route::currentRouteName()=='resort.accommodation.HoldMaintanaceRequest'
                                        || Route::currentRouteName()=='resort.accommodation.EventController'

                                        || Route::currentRouteName()=='resort.accommodation.config.index') ? 'active': '' }}" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            Accommodation
                                        </a>
                                        <div class="dropdown-menu carosel-nav-menu">
                                            <ul class="nav flex-column">
                                                <li><a class="dropdown-item" href="{{route('resort.accommodation.hoddashboard')}}">Dashboard</a></li>


                                                <li><a class="dropdown-item" href="{{route('resort.accommodation.inventory')}}">inventory</a></li>
                                                <li><a class="dropdown-item" href="{{route('resort.accommodation.InventoryManagement')}}">inventory Management</a></li>
                                                <li><a class="dropdown-item" href="{{route('resort.accommodation.AssignAccommation')}}">Assign Accommation</a></li>

                                                <li><a class="dropdown-item" href="{{route('resort.accommodation.AccommodationMaster')}}">Accommodation Master</a></li>
                                                <li><a class="dropdown-item" href="{{route('resort.accommodation.AvailableAccommodation')}}">Available Accommodation</a></li>

                                                <li><a class="dropdown-item" href="{{route('resort.accommodation.EmployeeAccommodation')}}">Employee Accommodation </a></li>
                                                <li><a class="dropdown-item" href="{{route('resort.accommodation.CreateMaintenanceRequest')}}"> Create Maintenance Request </a></li>
                                                <li><a class="dropdown-item" href="{{route('resort.accommodation.HoldMaintanaceRequest')}}"> On Hold Request </a></li>
                                                <li><a class="dropdown-item" href="{{route('resort.accommodation.event')}}"> Event </a></li>

                                                <li><a class="dropdown-item" href="{{route('resort.accommodation.config.index')}}">Configuration</a></li>

                                            </ul>
                                        </div>

                                    </div>
                                </div>
                                <div class="text-center" id="{{ (Route::currentRouteName()=='payroll.dashboard'
                                            || Route::currentRouteName()=='payroll.admindashboard'
                                            || Route::currentRouteName()=='payroll.configration'
                                            || Route::currentRouteName()=='shopkeepers.index'
                                            || Route::currentRouteName()=='payroll.run'
                                            || Route::currentRouteName()=='payroll.get'
                                            || Route::currentRouteName()=='payroll.pension.index'
                                            || Route::currentRouteName()=='payroll.ewt.index') ? 'caroselMenuActive': '' }}">
                                    <div class="btn-group">
                                        <a type="button" class="dropdown-toggle
                                            {{ (Route::currentRouteName()=='payroll.dashboard'
                                            || Route::currentRouteName()=='payroll.admindashboard'
                                            || Route::currentRouteName()=='payroll.configration'
                                            || Route::currentRouteName()=='shopkeepers.index'
                                            || Route::currentRouteName()=='payroll.run'
                                            || Route::currentRouteName()=='payroll.get'
                                            || Route::currentRouteName()=='payroll.pension.index'
                                            || Route::currentRouteName()=='payroll.ewt.index') ? 'active': '' }}" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            Payroll
                                        </a>
                                        <div class="dropdown-menu carosel-nav-menu">
                                            <ul class="nav flex-column">
                                                <li><a class="dropdown-item" href="{{route('payroll.dashboard')}}">Dashboard</a></li>
                                                <li><a class="dropdown-item" href="{{route('shopkeepers.create')}}">Shopkeepers</a></li>
                                                <li><a class="dropdown-item" href="{{route('payroll.run')}}">Run Payroll</a></li>
                                                <li><a class="dropdown-item" href="{{route('payroll.pension.index')}}">Pension</a></li>
                                                <li><a class="dropdown-item" href="{{route('payroll.ewt.index')}}">EWT</a></li>
                                                <li><a class="dropdown-item" href="{{route('payroll.final.settlement')}}">Full & Final Settlement</a></li>
                                                <li><a class="dropdown-item" href="{{route('payroll.configration')}}">Configuration</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center" id="{{ Route::currentRouteName() == 'Performance.Hrdashboard' ? 'caroselMenuActive' : '' }}">
                                    <div class="btn-group">
                                        <a class="dropdown-toggle {{ Route::currentRouteName() == 'Performance.Hrdashboard' ? 'active' : '' }}"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                            Performance
                                        </a>
                                        <div class="dropdown-menu carosel-nav-menu">
                                            <ul class="nav flex-column">
                                                <li><a class="dropdown-item" href="{{ route('Performance.Hrdashboard') }}">Dashboard</a></li>
                                                <li><a class="dropdown-item" href="{{ route('Performance.Meeting.index') }}">Meeting </a></li>
                                                <li><a class="dropdown-item" href="{{ route('Performance.kpi.KpiList') }}">KPI List</a></li>
                                                <li><a class="dropdown-item" href="{{ route('Performance.cycle') }}">Cycle</a></li>

                                                <li><a class="dropdown-item" href="{{ route('Performance.configuration') }}">Configuration</a></li>

                                            </ul>

                                        </div>
                                    </div>
                                </div>
                                <div class="text-center" id="{{ Route::currentRouteName() == 'GrievanceAndDisciplinery.Hrdashboard' ? 'caroselMenuActive' : '' }}">
                                    <div class="btn-group">
                                        <a class="dropdown-toggle {{ Route::currentRouteName() == 'GrievanceAndDisciplinery.config.index' ? 'active' : '' }}" data-bs-toggle="dropdown" aria-expanded="false">  People Relation</a>
                                        <div class="dropdown-menu carosel-nav-menu">
                                            <ul class="nav flex-column">
                                                <li><a class="dropdown-item" href="{{ route('GrievanceAndDisciplinery.Hrdashboard') }}">Dashboard</a></li>
                                                <li><a class="dropdown-item" href="{{ route('GrievanceAndDisciplinery.config.index') }}">Configuration</a></li>
                                                <li><a class="dropdown-item" href="{{ route('GrievanceAndDisciplinery.grivance.GrivanceIndex') }}">Grievane</a></li>
                                            </ul>

                                        </div>
                                    </div>
                                </div>
                                <div class="text-center" id="{{ (Route::currentRouteName()=='learning.hr.dashboard'
                                            || Route::currentRouteName()=='learning.admin.dashboard'
                                            || Route::currentRouteName()=='learning.hod.dashboard'
                                            || Route::currentRouteName()=='learning.manager.dashboard'
                                            || Route::currentRouteName()=='learning.configration'
                                            || Route::currentRouteName()=='learning.programs.index'
                                            || Route::currentRouteName()=='learning.calendar.index'
                                            || Route::currentRouteName()=='learning.request.add') ? 'caroselMenuActive': '' }}">
                                    <div class="btn-group">
                                    <a type="button" class="dropdown-toggle
                                            {{ (Route::currentRouteName()=='learning.hr.dashboard'
                                            || Route::currentRouteName()=='learning.admin.dashboard'
                                            || Route::currentRouteName()=='learning.hod.dashboard'
                                            || Route::currentRouteName()=='learning.manager.dashboard'
                                            || Route::currentRouteName()=='learning.configration'
                                            || Route::currentRouteName()=='learning.programs.index'
                                            || Route::currentRouteName()=='learning.calendar.index'
                                            || Route::currentRouteName()=='learning.request.add') ? 'active': '' }}" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            Learning
                                        </a>
                                        <div class="dropdown-menu carosel-nav-menu">
                                            <ul class="nav flex-column">
                                                <li><a class="dropdown-item" href="{{route('learning.hr.dashboard')}}">Dashboard</a></li>
                                                <li><a class="dropdown-item" href="{{route('learning.programs.index')}}">Learning Programs</a></li>
                                                <li><a class="dropdown-item" href="{{route('learning.request.add')}}">Add Learning Request</a></li>
                                                <li><a class="dropdown-item" href="{{route('learning.request.index')}}">Learning Request</a></li>
                                                <li><a class="dropdown-item" href="{{route('learning.calendar.index')}}">Calendar</a></li>
                                                <li><a class="dropdown-item" href="{{route('learning.configration')}}">Configuration</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

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
                                        @if(Common::resortHasPermission( config('settings.resort_permissions.view')))
                                            <li>
                                                <a class="dropdown-item" href="{{route('resort.sitesettings')}}"><span class="img-box"><img
                                                            src="{{ URL::asset('resorts_assets/images/settings.svg')}}" alt=""
                                                            class="img-fluid"></span>Settings</a>
                                            </li>
                                        @endif
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
