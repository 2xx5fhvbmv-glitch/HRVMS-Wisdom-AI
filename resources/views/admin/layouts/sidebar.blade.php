<aside class="main-sidebar sidebar-dark-primary elevation-4">
	<a href="{{route('admin.dashboard')}}" class="brand-link">
		<img src="{{ Common::getWebsiteLogo() }}" alt="HRVMS-WisdomAI" class="img-1 brand-image elevation-3" style="float:unset;margin-left:unset">
		<img src="{{ Common::getWebsiteFooterLogo() }}" alt="HRVMS-WisdomAI" class="img-2 brand-image elevation-3 d-none mx-auto" style="float:unset;margin-left:unset">
	</a>

	
	<div class="sidebar">
		<nav class="mt-2">
			<ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
				<li class="nav-item">
					<a href="{{route('admin.dashboard')}}" class="nav-link @if( Route::currentRouteName() == 'admin.dashboard' ) active @endif">
						<i class="nav-icon fas fa-tachometer-alt"></i>
						<p>Dashboard</p>
					</a>
				</li>

				<li class="nav-item @if(in_array(Route::currentRouteName(), array('admin.index','admin.create','admin.edit',  'admin.role.index','admin.role.create','admin.role.edit','admin.role.edit_role_permissions', 'admin.employee.index','admin.employee.create','admin.employee.edit' ,'admin.Modules.index','admin.Modules.create','admin.Modules.edit','admin.ModulePages.index','admin.ModulePages.create','admin.ModulePages.edit') ) ) menu-open @endif">
					<a href="#" class="nav-link @if(in_array(Route::currentRouteName(), array('admin.index','admin.create','admin.edit', 'admin.role.index','admin.role.create','admin.role.edit','admin.role.edit_role_permissions','admin.employee.index','admin.employee.create','admin.employee.edit','admin.Modules.index','admin.Modules.create','admin.Modules.edit','admin.ModulePages.index','admin.ModulePages.create','admin.ModulePages.edit') ) ) active @endif">
						<i class="nav-icon fas fa-users"></i>
						<p>
							Admins
							<i class="fas fa-angle-left right"></i>
						</p>
					</a>
					<ul class="nav nav-treeview">
						@if(Common::hasPermission(config('settings.admin_modules.admin_users'),config('settings.permissions.view')))
							<li class="nav-item">
								<a href="{{route('admin.index')}}" class="nav-link @if(in_array(Route::currentRouteName(), array('admin.index','admin.create','admin.edit'))) active @endif">
									<i class="far fa-circle nav-icon"></i>
									<p>Admins</p>
								</a>
							</li>
						@endif

						@if(Common::hasPermission(config('settings.admin_modules.roles_permissions'),config('settings.permissions.view')))
							<li class="nav-item">
								<a href="{{route('admin.role.index')}}" class="nav-link @if(in_array(Route::currentRouteName(), array('admin.role.index','admin.role.create','admin.role.edit','admin.role.edit_role_permissions'))) active @endif">
									<i class="far fa-circle nav-icon"></i>
									<p>Roles</p>
								</a>
							</li>
						@endif
					</ul>
				</li>

				@if(Common::hasPermission(config('settings.admin_modules.resorts'),config('settings.permissions.view')))
					<li class="nav-item">
						<a href="{{route('admin.resorts.index')}}" class="nav-link @if(in_array(Route::currentRouteName(), array('admin.resorts.index','admin.resorts.create', 'admin.resorts.edit'))) active @endif">
							<i class="fa fa-building nav-icon"></i>
							<p>Resorts</p>
						</a>
					</li>
				@endif

				<li class="nav-item @if(in_array(Route::currentRouteName(), array('admin.divisions.index','admin.divisions.create', 'admin.divisions.edit','admin.department.index','admin.department.create', 'admin.department.edit','admin.sections.index','admin.sections.create', 'admin.sections.edit','admin.positions.index','admin.positions.create', 'admin.positions.edit','admin.Modules.index','admin.Modules.create','admin.Modules.edit','admin.ModulePages.index','admin.ModulePages.create','admin.ModulePages.edit'))) menu-open @endif">
					<a href="#" class="nav-link @if(in_array(Route::currentRouteName(), array('admin.divisions.index','admin.divisions.create', 'admin.divisions.edit','admin.department.index','admin.department.create', 'admin.department.edit','admin.sections.index','admin.sections.create', 'admin.sections.edit','admin.positions.index','admin.positions.create', 'admin.positions.edit','admin.Modules.index','admin.Modules.create','admin.Modules.edit','admin.ModulePages.index','admin.ModulePages.create','admin.ModulePages.edit'))) active @endif">
						<i class="nav-icon fas fa-table"></i>
						<p>Masters<i class="fas fa-angle-left right"></i></p>
					</a>
					<ul class="nav nav-treeview">
						@if(Common::hasPermission(config('settings.admin_modules.divisions'),config('settings.permissions.view')))
							<li class="nav-item">
								<a href="{{route('admin.divisions.index')}}" class="nav-link @if(in_array(Route::currentRouteName(), array('admin.divisions.index','admin.divisions.create', 'admin.divisions.edit'))) active @endif">
									<i class="far fa-circle nav-icon"></i>
									<p>Divisions</p>
								</a>
							</li>
						@endif
						@if(Common::hasPermission(config('settings.admin_modules.departments'),config('settings.permissions.view')))
							<li class="nav-item">
								<a href="{{route('admin.department.index')}}" class="nav-link @if(in_array(Route::currentRouteName(), array('admin.department.index','admin.department.create', 'admin.department.edit'))) active @endif">
									<i class="far fa-circle nav-icon"></i>
									<p>Departments</p>
								</a>
							</li>
						@endif
						@if(Common::hasPermission(config('settings.admin_modules.sections'),config('settings.permissions.view')))
							<li class="nav-item">
								<a href="{{route('admin.sections.index')}}" class="nav-link @if(in_array(Route::currentRouteName(), array('admin.sections.index','admin.sections.create', 'admin.sections.edit'))) active @endif">
									<i class="far fa-circle nav-icon"></i>
									<p>Sections</p>
								</a>
							</li>
						@endif
						@if(Common::hasPermission(config('settings.admin_modules.positions'),config('settings.permissions.view')))
							<li class="nav-item">
								<a href="{{route('admin.positions.index')}}" class="nav-link @if(in_array(Route::currentRouteName(), array('admin.positions.index','admin.positions.create', 'admin.positions.edit'))) active @endif">
									<i class="far fa-circle nav-icon"></i>
									<p>Positions</p>
								</a>
							</li>
						@endif
						@if(Common::hasPermission(config('settings.admin_modules.modules'),config('settings.permissions.view')))
							<li class="nav-item">
								<a href="{{route('admin.Modules.index')}}" class="nav-link @if(in_array(Route::currentRouteName(), array('admin.Modules.index','admin.Modules.create','admin.Modules.edit'))) active @endif">
									<i class="far fa-circle nav-icon"></i>
									<p>Resort Modules</p>
								</a>
							</li>
                        @endif
                        @if(Common::hasPermission(config('settings.admin_modules.Pages'),config('settings.permissions.view')))
							<li class="nav-item">
								<a href="{{route('admin.ModulePages.index')}}" class="nav-link @if(in_array(Route::currentRouteName(), array('admin.ModulePages.index','admin.ModulePages.create','admin.ModulePages.edit'))) active @endif">
									<i class="far fa-circle nav-icon"></i>
									<p>Resort Pages</p>
								</a>
							</li>
                        @endif
					</ul>
				</li>

				@if(Common::hasPermission(config('settings.admin_modules.public_holidays'),config('settings.permissions.view')))
					<li class="nav-item">
						<a href="{{route('admin.public_holidays.index')}}" class="nav-link @if(in_array(Route::currentRouteName(), array('admin.public_holidays.index','admin.public_holidays.create', 'admin.public_holidays.edit'))) active @endif">
							<i class="fa fa-bell nav-icon"></i>
							<p>Public Holidays</p>
						</a>
					</li>
				@endif

				@if(Common::hasPermission(config('settings.admin_modules.ewt_brackets'),config('settings.permissions.view')))
					<li class="nav-item">
						<a href="{{route('admin.ewt_brackets.index')}}" class="nav-link @if(in_array(Route::currentRouteName(), array('admin.ewt_brackets.index','admin.ewt_brackets.create', 'admin.ewt_brackets.edit'))) active @endif">
							<i class="fa fa-bell nav-icon"></i>
							<p>Ewt Tax Bracket</p>
						</a>
					</li>
				@endif

				@if(Common::hasPermission(config('settings.admin_modules.support_categories'),config('settings.permissions.view')))
					<li class="nav-item">
						<a href="{{route('admin.support_categories.index')}}" class="nav-link @if(in_array(Route::currentRouteName(), array('admin.support_categories.index','admin.support_categories.create', 'admin.support_categories.edit'))) active @endif">
							<i class="fa fa-bell nav-icon"></i>
							<p>Support Categories</p>
						</a>
					</li>
				@endif

				@if(Common::hasPermission(config('settings.admin_modules.supports'),config('settings.permissions.view')))
					<li class="nav-item">
						<a href="{{route('admin.supports.index')}}" class="nav-link @if(in_array(Route::currentRouteName(), array('admin.supports.index', 'admin.supports.edit'))) active @endif">
							<i class="fa fa-bell nav-icon"></i>
							<p>Supports</p>
						</a>
					</li>
				@endif

				@if(Common::hasPermission(config('settings.admin_modules.notifications'),config('settings.permissions.view')))
					<li class="nav-item">
						<a href="{{route('admin.notifications.index')}}" class="nav-link @if(in_array(Route::currentRouteName(), array('admin.notifications.index','admin.notifications.create', 'admin.notifications.edit'))) active @endif">
							<i class="fa fa-bell nav-icon"></i>
							<p>Notifications</p>
						</a>
					</li>
				@endif

				<li class="nav-item">
					<a href="{{route('admin.profile')}}" class="nav-link @if(in_array(Route::currentRouteName(), array('admin.profile'))) active @endif">
						<i class="nav-icon fas fa-user"></i>
						<p>My Profile</p>
					</a>
				</li>

				@if(Common::hasPermission(config('settings.admin_modules.email_templates'),config('settings.permissions.view')))
					<li class="nav-item">
						<a href="{{route('admin.emailTemplate.index')}}" class="nav-link @if(in_array(Route::currentRouteName(), array('admin.emailTemplate.index','admin.emailTemplate.create', 'admin.emailTemplate.edit'))) active @endif">
							<i class="fa fa-envelope nav-icon"></i>
							<p>Email Templates</p>
						</a>
					</li>
				@endif

				@if(Common::hasPermission(config('settings.admin_modules.settings'),config('settings.permissions.update')))
					<li class="nav-item">
						<a href="{{route('settings.update')}}" class="nav-link @if(in_array(Route::currentRouteName(), array('settings.update'))) active @endif">
							<i class="fa fa-cog nav-icon"></i>
							<p>Settings</p>
						</a>
					</li>
				@endif

				<li class="nav-item">
					<a href="{{route('admin.logout')}}" class="nav-link">
						<i class="nav-icon fas fa-unlock"></i>
						<p>Logout</p>
					</a>
				</li>
			</ul>
		</nav>
	</div>
</aside>
