<?php

/** Routes without login ***/
Route::prefix('admin')->namespace('Admin')->group(function () {
  Route::get('/', 'LoginController@showLoginForm')->name('admin.loginindex');
  Route::post('/do-login', 'LoginController@login')->name('admin.login');
  Route::get('/request-password', 'ForgotPasswordController@requestPassword')->name('admin.password.request');
  Route::post('/request-password-submit', 'ForgotPasswordController@requestPasswordSubmit')->name('admin.password.request-submit');
  Route::get('/reset-password/{token}', 'ForgotPasswordController@resetPassword')->name('admin.password.reset');
  Route::post('/reset-password-submit', 'ForgotPasswordController@resetPasswordSubmit')->name('admin.password.reset-submit');
  Route::post('/check-email-exists', 'ForgotPasswordController@checkEmailExists')->name('admin.emailExistForgotPassword');
  Route::get('/permission-denied', 'LoginController@permissionDenied')->name('admin.permission.denied');
});

/*** Admin Authenticated Routes ***/
    Route::prefix('admin')->middleware(['auth:admin','revalidate'])->namespace('Admin')->group(function () {

    /*** Logout ***/
    Route::get('/logout', 'LoginController@logout')->name('admin.logout');


    /***** Start Admin to Resort Login ****/
    Route::post('/admin-to-resort', 'LoginController@AdminToResort')->name('AdminToResort');
    Route::get('/admin/end-impersonation','LoginController@endImpersonation')->name('endImpersonation');

    /**** End of Resort Direct Login ****/
    /*** Dashboard ***/
    Route::get('/dashboard', 'AdminDashboardController@dashboard')->name('admin.dashboard');

    Route::group(['middleware' => ['hasModuleAccess']], function () {
        /** Admin Module **/
        Route::get('/admins', 'AdminController@index')->name('admin.index');
        Route::get('/admins/create', 'AdminController@create')->name('admin.create');
        Route::post('/admins/store', 'AdminController@store')->name('admin.store');
        Route::get('/admins/list', 'AdminController@list')->name('admin.list');
        Route::get('/admins/edit/{id}', 'AdminController@edit')->name('admin.edit');
        Route::post('/admins/update/{id}', 'AdminController@update')->name('admin.update');
        Route::delete('/admins/destroy/{id}', 'AdminController@destroy')->name('admin.destroy');
        Route::get('/admins/inactive/{id}', 'AdminController@block')->name('admin.block');
        Route::get('/admins/active/{id}', 'AdminController@active')->name('admin.active');
        Route::get('/admins/massremove', 'AdminController@massremove')->name('admin.massremove');

        Route::post('/admins/check-email-exists', 'AdminController@checkEmailExists')->name('admin.checkEmailExists');
        Route::get('/admins/block/{id}', 'AdminController@block')->name('admin.block');
        Route::get('/admins/active/{id}', 'AdminController@active')->name('admin.active');

        /*** Settings ***/
        Route::get('/settings/update', 'SettingsController@updateSettings')->name('settings.update');
        Route::post('/settings/save', 'SettingsController@saveSettings')->name('settings.save');

        /** Email template Module **/
        Route::get('/email-templates', 'EmailTemplateController@index')->name('admin.emailTemplate.index');
        Route::get('/email-templates/list', 'EmailTemplateController@emailTemplateList')->name('admin.emailTemplate.list');
        Route::get('/email-templates/edit/{id}', 'EmailTemplateController@emailTemplateEdit')->name('admin.emailTemplate.edit');
        Route::post('/email-templates/update/{id}', 'EmailTemplateController@emailTemplateUpdate')->name('admin.emailTemplate.update');
        Route::get('/email-templates/create', 'EmailTemplateController@create')->name('admin.emailTemplate.create');
        Route::post('/email-templates/store', 'EmailTemplateController@store')->name('admin.emailTemplate.store');

        /** Admin profile **/
        Route::get('/profile/edit', 'AdminController@profile')->name('admin.profile');
        Route::post('/profile/update', 'AdminController@profileUpdate')->name('admin.updateProfile');
        Route::post('/change-password', 'AdminController@changePassword')->name('admin.changePassword');
        Route::post('/check-password', 'AdminController@checkPassword')->name('admin.checkPassword');

        /**Role Module **/
        Route::get('/roles', 'RolePermissionController@index')->name('admin.role.index');
        Route::get('/roles/list', 'RolePermissionController@list')->name('admin.role.list');
        Route::get('/roles/create', 'RolePermissionController@create')->name('admin.role.create');
        Route::post('/roles/store', 'RolePermissionController@store')->name('admin.role.store');
        Route::get('/roles/edit/{id}', 'RolePermissionController@edit')->name('admin.role.edit');
        Route::delete('/roles/destroy/{id}', 'RolePermissionController@destroy')->name('admin.role.destroy');
        Route::post('/roles/update/{id}', 'RolePermissionController@update')->name('admin.role.update');
        Route::get('/roles/edit-permissions/{id}', 'RolePermissionController@editRolePermissions')->name('admin.role.edit_role_permissions');
        Route::post('/roles/update-permissions/{id}', 'RolePermissionController@updateRolePermissions')->name('admin.role.update_role_permissions');
        Route::get('/roles/massremove', 'RolePermissionController@massremove')->name('admin.role.massremove');
        Route::get('/roles/block/{id}', 'RolePermissionController@block')->name('admin.role.block');
        Route::get('/roles/active/{id}', 'RolePermissionController@active')->name('admin.role.active');


        /**Resorts Module**/
        Route::get('/resorts', 'ResortsController@index')->name('admin.resorts.index');
        Route::get('resorts/archived', 'ResortsController@archived')->name('admin.resorts.archived');
        Route::get('/resorts/create', 'ResortsController@create')->name('admin.resorts.create');
        Route::post('/resorts/store', 'ResortsController@store')->name('admin.resorts.store');
        Route::get('/resorts/list', 'ResortsController@list')->name('admin.resorts.list');
        Route::get('/resorts/archived-list', 'ResortsController@archivedlist')->name('admin.resorts.archivedList');
        Route::get('/resorts/edit/{id}', 'ResortsController@edit')->name('admin.resorts.edit');
        Route::post('/resorts/update/{id}', 'ResortsController@update')->name('admin.resorts.update');
        Route::delete('/resorts/destroy/{id}', 'ResortsController@destroy')->name('admin.resorts.destroy');
        Route::post('/resorts/restore/{id}', 'ResortsController@restore')->name('admin.resorts.restore');
        Route::get('/resorts/inactive/{id}', 'ResortsController@block')->name('admin.resorts.block');
        Route::get('/resorts/active/{id}', 'ResortsController@active')->name('admin.resorts.active');
        Route::get('/resorts/mass-remove', 'ResortsController@massremove')->name('admin.resorts.massremove');
        Route::get('/resorts/block/{id}', 'ResortsController@block')->name('admin.resorts.block');
        Route::get('/resorts/active/{id}', 'ResortsController@active')->name('admin.resorts.active');
        Route::post('/resorts/store/image', 'ResortsController@storeImage')->name('admin.resorts.store.image');
        Route::post('/resorts/delete/image', 'ResortsController@deleteImage')->name('admin.resorts.delete.image');
        Route::post('/resorts/check-email-exists', 'ResortsController@checkEmailExists')->name('admin.resorts.checkEmailExists');
        Route::get('/resort/login/{id}', 'ResortsController@loginAsResortAdmin')->name('admin.resorts.login');
        Route::get('/resort/edit-permissions/{id}', 'ResortsController@editPermissions')->name('admin.resorts.edit_permissions');
        Route::post('/resort/update-permissions/{id}', 'ResortsController@updatePermissions')->name('admin.resorts.update_permissions');

        // AJAX Route
        Route::get('/get-positions-by-department', 'ResortController@getPositionsByDepartment');

        /**Divisions Module **/
        Route::get('/divisions', 'DivisionController@index')->name('admin.divisions.index');
        Route::get('/divisions/list', 'DivisionController@list')->name('admin.divisions.list');
        Route::get('/divisions/create', 'DivisionController@create')->name('admin.divisions.create');
        Route::post('/divisions/store', 'DivisionController@store')->name('admin.divisions.store');
        Route::get('/divisions/edit/{id}', 'DivisionController@edit')->name('admin.divisions.edit');
        Route::delete('/divisions/destroy/{id}', 'DivisionController@destroy')->name('admin.divisions.destroy');
        Route::post('/divisions/update/{id}', 'DivisionController@update')->name('admin.divisions.update');
        Route::get('/divisions/massremove', 'DivisionController@massremove')->name('admin.divisions.massremove');
        Route::get('/divisions/inactive/{id}', 'DivisionController@block')->name('admin.divisions.block');
        Route::get('/divisions/active/{id}', 'DivisionController@active')->name('admin.divisions.active');

        /**Departments Module **/
        Route::get('/department', 'DepartmentController@index')->name('admin.department.index');
        Route::get('/department/list', 'DepartmentController@list')->name('admin.department.list');
        Route::get('/department/create', 'DepartmentController@create')->name('admin.department.create');
        Route::post('/department/store', 'DepartmentController@store')->name('admin.department.store');
        Route::get('/department/edit/{id}', 'DepartmentController@edit')->name('admin.department.edit');
        Route::delete('/department/destroy/{id}', 'DepartmentController@destroy')->name('admin.department.destroy');
        Route::post('/department/update/{id}', 'DepartmentController@update')->name('admin.department.update');
        Route::get('/department/massremove', 'DepartmentController@massremove')->name('admin.department.massremove');
        Route::get('/department/inactive/{id}', 'DepartmentController@block')->name('admin.department.block');
        Route::get('/department/active/{id}', 'DepartmentController@active')->name('admin.department.active');

        /**Sections Module **/
        Route::get('/sections', 'SectionController@index')->name('admin.sections.index');
        Route::get('/sections/list', 'SectionController@list')->name('admin.sections.list');
        Route::get('/sections/create', 'SectionController@create')->name('admin.sections.create');
        Route::post('/sections/store', 'SectionController@store')->name('admin.sections.store');
        Route::get('/sections/edit/{id}', 'SectionController@edit')->name('admin.sections.edit');
        Route::delete('/sections/destroy/{id}', 'SectionController@destroy')->name('admin.sections.destroy');
        Route::post('/sections/update/{id}', 'SectionController@update')->name('admin.sections.update');
        Route::get('/sections/massremove', 'SectionController@massremove')->name('admin.sections.massremove');
        Route::get('/sections/inactive/{id}', 'SectionController@block')->name('admin.sections.block');
        Route::get('/sections/active/{id}', 'SectionController@active')->name('admin.sections.active');

        /**Positions Module **/
        Route::get('/positions', 'PositionController@index')->name('admin.positions.index');
        Route::get('/positions/list', 'PositionController@list')->name('admin.positions.list');
        Route::get('/positions/create', 'PositionController@create')->name('admin.positions.create');
        Route::post('/positions/store', 'PositionController@store')->name('admin.positions.store');
        Route::get('/positions/edit/{id}', 'PositionController@edit')->name('admin.positions.edit');
        Route::delete('/positions/destroy/{id}', 'PositionController@destroy')->name('admin.positions.destroy');
        Route::post('/positions/update/{id}', 'PositionController@update')->name('admin.positions.update');
        Route::get('/positions/massremove', 'PositionController@massremove')->name('admin.positions.massremove');
        Route::get('/positions/edit-permissions/{id}', 'PositionController@editPermissions')->name('admin.positions.edit_permissions');
        Route::post('/positions/update-permissions/{id}', 'PositionController@updatePermissions')->name('admin.positions.update_permissions');
        Route::get('/positions/inactive/{id}', 'PositionController@block')->name('admin.positions.block');
        Route::get('/positions/active/{id}', 'PositionController@active')->name('admin.positions.active');

        /** Notification Module */
        Route::get('/notifications', 'NotificationController@index')->name('admin.notifications.index');
        Route::get('/notifications/list', 'NotificationController@list')->name('admin.notifications.list');
        Route::get('/notifications/create', 'NotificationController@create')->name('admin.notifications.create');
        Route::post('/notifications/store', 'NotificationController@store')->name('admin.notifications.store');
        Route::get('/notifications/edit/{id}', 'NotificationController@edit')->name('admin.notifications.edit');
        Route::delete('/notifications/destroy/{id}', 'NotificationController@destroy')->name('admin.notifications.destroy');
        Route::post('/notifications/update/{id}', 'NotificationController@update')->name('admin.notifications.update');
        Route::get('/notifications/massremove', 'NotificationController@massremove')->name('admin.notifications.massremove');
        Route::get('/notifications/block/{id}', 'NotificationController@block')->name('admin.notifications.block');
        Route::get('/notifications/active/{id}', 'NotificationController@active')->name('admin.notifications.active');

        /**Public Holidays Module **/
        Route::get('/public_holidays', 'PublicHolidayController@index')->name('admin.public_holidays.index');
        Route::get('/public_holidays/list', 'PublicHolidayController@list')->name('admin.public_holidays.list');
        Route::get('/public_holidays/create', 'PublicHolidayController@create')->name('admin.public_holidays.create');
        Route::post('/public_holidays/store', 'PublicHolidayController@store')->name('admin.public_holidays.store');
        Route::get('/public_holidays/edit/{id}', 'PublicHolidayController@edit')->name('admin.public_holidays.edit');
        Route::delete('/public_holidays/destroy/{id}', 'PublicHolidayController@destroy')->name('admin.public_holidays.destroy');
        Route::post('/public_holidays/update/{id}', 'PublicHolidayController@update')->name('admin.public_holidays.update');
        Route::get('/public_holidays/massremove', 'PublicHolidayController@massremove')->name('admin.public_holidays.massremove');
        Route::get('/public_holidays/inactive/{id}', 'PublicHolidayController@block')->name('admin.public_holidays.block');
        Route::get('/public_holidays/active/{id}', 'PublicHolidayController@active')->name('admin.public_holidays.active');

        //Permission module


        Route::get('/modules/list', ['App\Http\Controllers\Admin\ModuleController','index'])->name('admin.Modules.index');
        Route::get('/modules/create', ['App\Http\Controllers\Admin\ModuleController','create'])->name('admin.Modules.create');
        Route::post('/modules/store', ['App\Http\Controllers\Admin\ModuleController','store'])->name('admin.Modules.store');
        Route::get('/modules/edit/{id}', ['App\Http\Controllers\Admin\ModuleController','edit'])->name('admin.Modules.edit');
        Route::delete('/modules/destroy/{id}', ['App\Http\Controllers\Admin\ModuleController','destroy'])->name('admin.Modules.destroy');
        Route::post('/modules/update/{id}', ['App\Http\Controllers\Admin\ModuleController','update'])->name('admin.Modules.update');
        Route::get('/modules/block/{id}', ['App\Http\Controllers\Admin\ModuleController','block'])->name('admin.Modules.block');
        Route::get('/modules/active/{id}', ['App\Http\Controllers\Admin\ModuleController','active'])->name('admin.Modules.active');
        Route::get('/admins/modules/massremove', ['App\Http\Controllers\Admin\ModuleController','massremove'])->name( 'admin.Modules.massremove');

        Route::get('/module-pages/list', ['App\Http\Controllers\Admin\ModulesPagesController','index'])->name('admin.ModulePages.index');
        Route::get('/module-pages/create', ['App\Http\Controllers\Admin\ModulesPagesController','create'])->name('admin.ModulePages.create');
        Route::post('/module-pages/store', ['App\Http\Controllers\Admin\ModulesPagesController','store'])->name('admin.ModulePages.store');
        Route::get('/module-pages/edit/{id}', ['App\Http\Controllers\Admin\ModulesPagesController','edit'])->name('admin.ModulePages.edit');
        Route::delete('/module-pages/destroy/{id}', ['App\Http\Controllers\Admin\ModulesPagesController','destroy'])->name('admin.ModulePages.destroy');
        Route::post('/module-pages/update/{id}', ['App\Http\Controllers\Admin\ModulesPagesController','update'])->name('admin.ModulePages.update');
        Route::get('/module-pages/block/{id}', ['App\Http\Controllers\Admin\ModulesPagesController','block'])->name('admin.ModulePages.block');
        Route::get('/module-pages/active/{id}', ['App\Http\Controllers\Admin\ModulesPagesController','active'])->name('admin.ModulePages.active');
        Route::get('/admins/module-pages/massremove', ['App\Http\Controllers\Admin\ModulesPagesController','ModulesPagesmassremove'])->name( 'admin.ModulePages.massremove');


        //End Permission module

      //EWT Brackets module

      Route::get('/ewt_brackets', 'EwtTaxBracketController@index')->name('admin.ewt_brackets.index');
      Route::get('/ewt_brackets/list', 'EwtTaxBracketController@list')->name('admin.ewt_brackets.list');
      Route::get('/ewt_brackets/create', 'EwtTaxBracketController@create')->name('admin.ewt_brackets.create');
      Route::post('/ewt_brackets/store', 'EwtTaxBracketController@store')->name('admin.ewt_brackets.store');
      Route::get('/ewt_brackets/edit/{id}', 'EwtTaxBracketController@edit')->name('admin.ewt_brackets.edit');
      Route::delete('/ewt_brackets/destroy/{id}', 'EwtTaxBracketController@destroy')->name('admin.ewt_brackets.destroy');
      Route::post('/ewt_brackets/update/{id}', 'EwtTaxBracketController@update')->name('admin.ewt_brackets.update');
      Route::get('/ewt_brackets/massremove', 'EwtTaxBracketController@massremove')->name('admin.ewt_brackets.massremove');
        
      //Support Categories module

      Route::get('/support-categories', 'SupportCategoryController@index')->name('admin.support_categories.index');
      Route::get('/support-categories/list', 'SupportCategoryController@list')->name('admin.support_categories.list');
      Route::get('/support-categories/create', 'SupportCategoryController@create')->name('admin.support_categories.create');
      Route::post('/support-categories/store', 'SupportCategoryController@store')->name('admin.support_categories.store');
      Route::get('/support-categories/edit/{id}', 'SupportCategoryController@edit')->name('admin.support_categories.edit');
      Route::delete('/support-categories/destroy/{id}', 'SupportCategoryController@destroy')->name('admin.support_categories.destroy');
      Route::post('/support-categories/update/{id}', 'SupportCategoryController@update')->name('admin.support_categories.update');
      Route::get('/support-categories/massremove', 'SupportCategoryController@massremove')->name('admin.support_categories.massremove');
      Route::get('/support-categories/block/{id}','SupportCategoryController@block')->name('admin.support_categories.block');
      Route::get('/support-categories/active/{id}', 'SupportCategoryController@active')->name('admin.support_categories.active');
      
      //Support module

      Route::get('/supports', 'SupportController@index')->name('admin.supports.index');
      Route::get('/supports/list', 'SupportController@list')->name('admin.supports.list');
      Route::get('/supports/edit/{id}', 'SupportController@edit')->name('admin.supports.edit');
      Route::get('/admin/supports/view/{id}', 'SupportController@view')->name('admin.supports.view');
      Route::post('/supports/update-ticket-status', 'SupportController@updateStatus')->name('admin.supports.updateStatus');
      Route::post('/supports/{id}/assign', 'SupportController@assignTicket')->name('admin.supports.assign');
      Route::get('/supports/chat/{support_id}', 'SupportChatController@index')->name('admin.support.chat');
      Route::post('/supports/chat/send-message', 'SupportChatController@sendMessage')->name('admin.chat.sendMessage');
      Route::get('supports/chat/messages/{support_id}', 'SupportChatController@fetchMessages')->name('support.chat.fetchMessage');

      Route::post('supports/support-email-reply', 'SupportController@replyStore')->name('support.email.reply');

    });
    });
?>
