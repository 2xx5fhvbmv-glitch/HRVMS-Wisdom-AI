<?php
use App\Http\Controllers\Resorts\LoginController;

/** Routes without login ***/
Route::prefix('resort')->namespace('Resort')->group(function () {
  Route::get('/',  ['App\Http\Controllers\Resorts\ResortLoginController','showLoginForm'])->name('resort.loginindex');
  Route::post('/do-login', ['App\Http\Controllers\Resorts\ResortLoginController','login'])->name('resort.login');
  Route::get('/access-deined',  ['App\Http\Controllers\Resorts\ResortLoginController','AccessDeined'])->name('resort.AccessDeined');


  Route::get('/request-password', ['App\Http\Controllers\Resorts\ResortforgotPasswordController','requestPassword'])->name('resort.password.request');
  Route::post('/request-password-submit', ['App\Http\Controllers\Resorts\ResortforgotPasswordController','requestPasswordSubmit'])->name('resort.password.request-submit');
  Route::get('/reset-password/{token}', ['App\Http\Controllers\Resorts\ResortforgotPasswordController','resetPassword'])->name('resort.password.reset');
  Route::post('/reset-password-submit', ['App\Http\Controllers\Resorts\ResortforgotPasswordController','resetPasswordSubmit'])->name('resort.password.reset-submit');
  Route::post('/check-email-exists', ['App\Http\Controllers\Resorts\ResortforgotPasswordController','checkEmailExists'])->name('resort.emailExistForgotPassword');
  Route::get('/permission-denied', ['App\Http\Controllers\Resorts\LoginController@','permissionDenied'])->name('resort.permission.denied');

  Route::get('/applicant-form/{id?}', ['App\Http\Controllers\Resorts\ApplicantController','showapplicantForm'])->name('resort.applicantForm');

  //save session draft data for each step
  Route::post('/applicant-form/save-draft', ['App\Http\Controllers\Resorts\ApplicantController','saveDraft'])->name('save.applicantinfo.draft');
  Route::post('/applicant-form/get-draft', ['App\Http\Controllers\Resorts\ApplicantController', 'getDraftStepData'])->name('get.applicantinfo.draft');

  Route::post('/applicant_form/store', ['App\Http\Controllers\Resorts\ApplicantController','applicant_formStore'])->name('resort.applicantFormstore');

  Route::post('/applicant_temp/video-remove', ['App\Http\Controllers\Resorts\ApplicantController','applicant_tempVideoremove'])->name('resort.applicant_tempVideoremove');

  Route::post('/applicant_temp/video-store', ['App\Http\Controllers\Resorts\ApplicantController','applicant_tempVideoStore'])->name('resort.applicant_tempVideoStore');
  Route::get('/talent-pool', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','TalentPool'])->name('resort.ta.TalentPool');

  Route::post('/get/talent-pool/applicant', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','getTalentPoolGridApplicant'])->name('resort.ta.getTalentPoolApplicant');
  Route::post('/revert-back', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','RevertBack'])->name('resort.ta.RevertBack');
});

/*** Admin Authenticated Routes ***/
Route::prefix('resort')->middleware(['auth:resort-admin','revalidate','checkResortPermission'])->namespace('Resorts')->group(function () {

    /*** Logout ***/
    Route::get( '/logout', ['App\Http\Controllers\Resorts\ResortLoginController','logout'] )->name('resort.logout');

    /*** Notifications ***/
    Route::middleware('redirectIfNotCorrect.dashboard')->group(function () {
      Route::get('workforce-planning/hr-dashboard', 'WorkforcePlanningDashboardController@hr_dashboard')->name('resort.workforceplan.dashboard');
      Route::get('workforce-planning/hod-dashboard', 'WorkforcePlanningDashboardController@hod_dashboard')->name('resort.workforceplan.hoddashboard');
      Route::get('workforce-planning/admin-dashboard', 'WorkforcePlanningDashboardController@admin_dashboard')->name('resort.workforceplan.resortadmindashboard');

        // Talent Acquisition
        Route::get('talent-acquisition/hr-dashboard', 'TalentAcquisitionDashboardController@hr_dashboard')->name('resort.recruitement.hrdashboard');
        Route::get('talent-acquisition/hod-dashboard', 'TalentAcquisitionDashboardController@hod_dashboard')->name('resort.recruitement.hoddashboard');
        Route::get('talent-acquisition/admin-dashboard', 'TalentAcquisitionDashboardController@admin_dashboard')->name('resort.recruitement.admindashboard');
        Route::post('talent-acquisition/get/position-wise-top-applicants', 'TalentAcquisitionDashboardController@getTopCountriesPositionData')->name('resort.ta.GePositionWiseTopAppliants');



        //   Time and Attandance
        Route::get('time-and-attendance/hr-dashboard', 'TimeandAttendanceDashboardController@HrDashobard')->name('resort.timeandattendance.dashboard');
        Route::get('time-and-attendance/hod-dashboard', 'TimeandAttendanceDashboardController@hod_dashboard')->name('resort.timeandattendance.hoddashboard');
        Route::get('time-and-attendance/admin-dashboard', 'TimeandAttendanceDashboardController@admin_dashboard')->name('resort.timeandattendance.admindashboard');

        //Leave Module
        Route::get('leave/hr-dashboard', 'Leave\DashboardController@HR_Dashobard')->name('leave.dashboard');
        Route::get('leave/hod-dashboard', 'Leave\DashboardController@hod_dashboard')->name('leave.hoddashboard');
        Route::get('leave/admin-dashboard', 'Leave\DashboardController@admin_dashboard')->name('leave.admindashboard');


         //Accommodation Module
         Route::get('accommodation/hr-dashboard', 'Accommodation\AccommodationDashboardController@HR_Dashobard')->name('resort.accommodation.dashboard');
         Route::get('accommodation/hod-dashboard', 'Accommodation\AccommodationDashboardController@Hod_dashboard')->name('resort.accommodation.hoddashboard');
         Route::get('accommodation/admin-dashboard', 'Accommodation\AccommodationDashboardController@Admin_dashboard')->name('resort.accommodation.admindashboard');

         //Payroll Module
         Route::get('payroll/hr-dashboard', 'Payroll\DashboardController@HR_Dashobard')->name('payroll.dashboard');
         Route::get('payroll/admin-dashboard', 'Payroll\DashboardController@admin_dashboard')->name('payroll.admindashboard');
         //Performance Module
         Route::get('performance/hr-dashboard', 'Performance\PerformanceDashboardController@HR_Dashobard')->name('Performance.Hrdashboard');
         Route::get('performance/hod-dashboard', 'Performance\PerformanceDashboardController@Hod_dashboard')->name('Performance.HODdashboard');
         Route::get('performance/admin-dashboard', 'Performance\PerformanceDashboardController@Admin_dashboard')->name('Performance.admindashboard');


            Route::get('grievance-and-disciplinary/admin-dashboard', 'GrievanceAndDisciplinery\DashboardController@Admin_Dashobard')->name('GrievanceAndDisciplinery.Admindashboard');

         //Learning Module
         Route::get('learning/hr-dashboard', 'Learning\DashboardController@HR_Dashobard')->name('learning.hr.dashboard');
         Route::get('learning/hod-dashboard', 'Learning\DashboardController@hod_dashboard')->name('learning.hod.dashboard');
         Route::get('learning/admin-dashboard', 'Learning\DashboardController@admin_dashboard')->name('learning.admin.dashboard');
         Route::get('learning/manager-dashboard', 'Learning\DashboardController@manager_dashboard')->name('learning.manager.dashboard');


        //  GrievanceAndDisciplinery

         Route::get('grievance-and-disciplinary/hr-dashboard', 'GrievanceAndDisciplinery\DashboardController@HR_Dashobard')->name('GrievanceAndDisciplinery.Hrdashboard');
         Route::get('grievance-and-disciplinary/admin-dashboard', 'GrievanceAndDisciplinery\DashboardController@Admin_Dashobard')->name('GrievanceAndDisciplinery.Admindashboard');

        //  Survey
         Route::get('survey/hr-dashboard', 'Survey\DashboardController@HR_Dashobard')->name('Survey.hr.dashboard');
         Route::get('survey/admin-dashboard', 'Survey\DashboardController@Admin_Dashobard')->name('Survey.Admindashboard');



        //  Visa
         Route::get('visa/admin-dashboard', 'Visa\DashboardController@Admin_Dashobard')->name('visa.Admindashboard');
         Route::get('visa/hr-dashboard', 'Visa\DashboardController@HR_Dashobard')->name('visa.hr.dashboard');


        // File Management

         Route::get('file-managment/admin-dashboard', 'FileManagment\DashboardController@Admin_Dashobard')->name('FileManagment.Admindashboard');
         Route::get('file-managment/hr-dashboard', 'FileManagment\DashboardController@HR_Dashobard')->name('FileManagment.hr.dashboard');



          //Incident Module
          Route::get('incident/hr-dashboard', 'Incident\DashboardController@HR_Dashobard')->name('incident.hr.dashboard');
          Route::get('incident/hod-dashboard', 'Incident\DashboardController@Hod_dashboard')->name('incident.hod.dashboard');
          Route::get('incident/admin-dashboard', 'Incident\DashboardController@Admin_dashboard')->name('incident.admin.dashboard');

            //People module
            Route::get('/people/dashboard', 'People\DashboardController@HR_Dashobard')->name('people.hr.dashboard');
            Route::get('/people/admin-dashboard', 'People\DashboardController@admin_dashboard')->name('people.admin.dashboard');

        Route::get('/master/hr-dashboard','MasterDashboardController@hr_dashboard')->name('resort.master.hr_dashboard');
        Route::get('/master/hod-dashboard','MasterDashboardController@hod_dashboard')->name('resort.master.hod_dashboard');
        Route::get('/master/gm-dashboard','MasterDashboardController@gm_dashboard')->name('resort.master.gm_dashboard');
        Route::get('/master/admin-dashboard','MasterDashboardController@admin_dashboard')->name('resort.master.admin_dashboard');

    });

    Route::get('/people/exit/clearance-staticstics', 'People\DashboardController@exitClearanceStaticstics')->name('people.exit-interview.staticstics');
    Route::post('talent-acquisition/attandances', 'TalentAcquisitionDashboardController@getAttandanceData')->name('resort.recruitement.getAttandanceData');
    Route::get('talent-acquisition/send/birthday-notification/{emp_id}', 'TalentAcquisitionDashboardController@sendBirthdayNotification')->name('resort.recruitement.send.birthday-notification');
    /** Filled position page */
    Route::get('/workforce-planning/filled-position', 'WorkforcePlanningDashboardController@filledpositions')->name('resort.workforceplan.filledpositions');
    // Listing all filled postions with DataTables
    Route::get('/workforce-planning/get-filled-positions', 'WorkforcePlanningDashboardController@get_filledpositions')->name('workforceplan.filledpositions.data');
    Route::get('/workforce-planning/employee/names', 'WorkforcePlanningDashboardController@getEmployeeNames')->name('workforceplan.employee.names');
    Route::get( '/workforce-planning/get-positions', 'WorkforcePlanningDashboardController@getPositions')->name('resort.manning.positions');

    Route::get( '/workforce-planning/pending/department', 'WorkforcePlanningDashboardController@PendingDeartment')->name('resort.pendingDepartment');
    Route::post( '/workforce-planning/manning/budget-monthwise', 'WorkforcePlanningDashboardController@ManningBudgetMonthWise')->name('resort.ManningBudget.MonthWise');

    Route::get( '/user/profile', ['App\Http\Controllers\Resorts\ResortLoginController','ResortProfile'] )->name('resort.user.profile');
    Route::post( '/update/user/profile', ['App\Http\Controllers\Resorts\ResortLoginController','UpdateResortProfile'] )->name('resort.Updateuser.profile');
    Route::post( '/change/password', ['App\Http\Controllers\Resorts\ResortLoginController','changePassword'] )->name('resort.profile.changePassword');
    /***Manning page  */
    Route::get('/manning', 'ManningController@index')->name('resort.manning.index');

    // Listing all divisions with DataTables
    Route::get('/manning/getdivisions', 'ManningController@get_divisions')->name('manning.divisions.data');
    // Store a new division
    Route::post('manning/storedivisions', 'ManningController@store_divisions')->name('manning.divisions.store');
    // Inline update for divisions
    Route::put('/manning/inlinedivisionupdate/{id}', 'ManningController@inlineDivisionUpdate')->name('manning.divisions.inlineUpdate');
    // Delete a division
    Route::delete('manning/destroydivision/{id}', 'ManningController@destroy_division')->name('manning.divisons.destroy');


    // Listing all departments with DataTables
    Route::get('/manning/getdepartments', 'ManningController@get_departments')->name('manning.departments.data');
    // Store a new department
    Route::post('manning/storedepartments', 'ManningController@store_departments')->name('manning.departments.store');
    // Inline update for department
    Route::put('/manning/inlinedepartmentupdate/{id}', 'ManningController@inlineDepartmentUpdate')->name('manning.departments.inlineUpdate');
    // Delete a department
    Route::delete('manning/destroydepartment/{id}', 'ManningController@destroy_department')->name('manning.departments.destroy');

    // Listing all sections with DataTables
    Route::get('/manning/getsections', 'ManningController@get_sections')->name('manning.sections.data');
    // Store a new sections
    Route::post('manning/storesections', 'ManningController@store_sections')->name('manning.sections.store');
    // Inline update for sections
    Route::post('/manning/inlinesectionupdate', 'ManningController@inlinesectionsUpdate')->name('manning.sections.inlineUpdate');
    // Delete a sections
    Route::delete('manning/destroysection/{id}', 'ManningController@destroy_sections')->name('manning.sections.destroy');

    // Listing all positions with DataTables
    Route::get('/manning/getpositions', 'ManningController@get_positions')->name('manning.positions.data');
    // Store a new positions
    Route::post('manning/storepositions', 'ManningController@store_positions')->name('manning.positions.store');
    // Inline update for positions
    Route::put('/manning/inlinepositionupdate/{id}', 'ManningController@inlinePositionUpdate')->name('manning.positions.inlineUpdate');
    // Delete a positions
    Route::delete('manning/destroyposition/{id}', 'ManningController@destroy_position')->name('manning.positions.destroy');

    /*manning response */
    Route::post('/manning/responses', 'ManningResponseController@store')->name('manning.responses.store');
    Route::post('/manning/fetchemployees', 'ManningResponseController@fetchEmployees')->name('manning.fetch.employees');
    Route::post('/manning/fetch/currentyeardata', 'ManningResponseController@fetchCurrentYearData')->name('manning.fetch.currentYearData');
    Route::post('/manning/responses/saveDraft', 'ManningResponseController@saveDraft')->name('manning.responses.saveDraft');
    Route::get('/manning/responses/get-draft-data/{resortId}/{deptId}/{year}', 'ManningResponseController@getDraft')->name('manning.responses.getDraft');
    Route::post( '/manning/responses/show/department/wise-budget-data', 'ManningResponseController@ShowDepartmentWiseBudgetData')->name('resort.department.wise.budget.data');
    Route::put( '/manning/responses/update-budget-data/{id}', 'ManningResponseController@updateBudgetData')->name('resort.budget.update');
    Route::put( '/manning/responses/update-grand-total', 'ManningResponseController@updateParentTotal')->name('resort.budget.updateParentTotal');

    /** Budget **/
    Route::get( '/budget/view-manning','BudgetController@ViewManning')->name('resort.budget.manning');
    Route::get( '/budget/view-budget','BudgetController@ViewBudget')->name('resort.budget.viewbudget');
    Route::get('/resort/budget/get', 'BudgetController@ajaxViewBudget')->name('budget.view.ajax');

    // Hierarchical Budget API Routes
    Route::get('/budget/hierarchy/department', 'BudgetController@getDepartmentHierarchy')->name('resort.budget.hierarchy.department');
    Route::get('/budget/hierarchy/position/employees', 'BudgetController@getPositionEmployees')->name('resort.budget.hierarchy.position.employees');
    Route::get('/budget/hierarchy/employee/monthly', 'BudgetController@getEmployeeMonthlyData')->name('resort.budget.hierarchy.employee.monthly');
    Route::get('/budget/hierarchy/vacant/monthly', 'BudgetController@getVacantMonthlyData')->name('resort.budget.hierarchy.vacant.monthly');
    Route::get('/budget/holiday-hours', 'BudgetController@getHolidayHoursForMonth')->name('resort.budget.holiday.hours');
    Route::post('/budget/hierarchy/employee/update', 'BudgetController@updateEmployeeMonthlyBudget')->name('resort.budget.hierarchy.employee.update');
    Route::post('/budget/hierarchy/vacant/update', 'BudgetController@updateVacantMonthlyBudget')->name('resort.budget.hierarchy.vacant.update');

    Route::get( '/budget/consolidated-budget','BudgetController@ConsolidateBudget')->name('resort.budget.consolidatebudget');
    Route::get( '/budget/compare-budget/{id}/{budgetid}','BudgetController@CompareBudget')->name('resort.budget.comparebudget');
    Route::get( '/budget/config','BudgetController@config')->name('resort.budget.config');

    Route::post( '/budget/upload/config-files','BudgetController@UploadconfigFiles')->name('resort.budget.UploadconfigFiles');
    Route::post( '/budget/resort-all-department-wise','BudgetController@UpdateResortBudgetPositionWise')->name('resort.UpdateResortPositionWise');

    // New route for approving the budget
    Route::post('/budget/approve', 'BudgetController@approveBudget')->name('resort.budget.approve');

    Route::get('/budget/cost/', 'BudgetCostController@index')->name('resort.budget.index');
    Route::get( '/budget/cost/list','BudgetCostController@costlist')->name('resort.budget.costlist');
    Route::post('/budget/cost/store', 'BudgetCostController@store_costs')->name('resort.budget.storecost');
    // Inline update for sections
    Route::put('/budget/cost/inlineupdate/{id}', 'BudgetCostController@inlinecostUpdate')->name('resort.budget.inlinecostupdate');
    // Delete a sections
    Route::delete('budget/cost/destroy/{id}', 'BudgetCostController@destroy_costs')->name('resort.budget.destroycost');

    /** Benifit grid  */
    Route::get('/benifit-grid', 'BenifitGridController@index')->name('resort.benifitgrid.index');
    Route::get('/benifit-grid/list', 'BenifitGridController@list')->name('resort.benifitgrid.list');
    Route::get('/benifit-grid/create', 'BenifitGridController@create')->name('resort.benifitgrid.create');
    Route::post('/benifit-grid/store', 'BenifitGridController@store')->name('resort.benifitgrid.store');
    Route::get('/benifit-grid/edit/{id}', 'BenifitGridController@edit')->name('resort.benifitgrid.edit');
    Route::get('/benifit-grid/view/{id}', 'BenifitGridController@view')->name('resort.benifitgrid.view');
    Route::post('/benifit-grid/update/{id}', 'BenifitGridController@update')->name('resort.benifitgrid.update');
    Route::delete('/benifit-grid/delete/{id}', 'BenifitGridController@destroy')->name('resort.benifitgrid.delete');
    Route::get('/benifit-grid/pdf/{id}', 'BenifitGridController@pdf')->name('resort.benefitgrid.pdf');

    /**Occupancy **/
    Route::post('/store-occupancy', ['App\Http\Controllers\Resorts\OccupancyController','storeOccupancy'])->name('resort.occupancy.store');
    Route::post('/store-import-datas', ['App\Http\Controllers\Resorts\OccupancyController','ImportDatas'])->name('resort.occupancy.ImportDatas');
    Route::post('/store-bulk-occupancy', ['App\Http\Controllers\Resorts\OccupancyController','storeBulkOccupancy'])->name('resort.bulk-occupancy.store');

    /* End of occupancy */
    /* Employee  */
    Route::get('/get-employee/list', ['App\Http\Controllers\Resorts\EmployeeController','index'])->name('resort.employeelist');
    Route::get('/employee/nationality-data', 'EmployeeController@getEmployeeNationalityData')->name('employee.nationality.data');
    Route::get( '/employee/add', 'EmployeeController@AddEmployee')->name('resort.Add.Employee');

    Route::post('/employee/import', ['App\Http\Controllers\Resorts\EmployeeController','ImportEmployee'])->name('resort.Depat_Position_Emp_Import');
    Route::get( '/employee/export', 'EmployeeController@exportRelatedDepartment')->name('resort.export.Employee');

    /* salary */
    Route::get('/salary-increment/get-increment-details', 'SalaryIncrementController@getIncrementDetails')->name('employee.salaryincrement.get');
    Route::post('/salary-increment/save', 'SalaryIncrementController@saveSalaryIncrement')->name('employee.salaryincrement.save');
    Route::post('/salary-increment/bulk-save', 'SalaryIncrementController@saveBulkSalaryIncrement')->name('employee.bulksalaryincrement.save');

    Route::get( '/budget/export','ConsolidateBudgetController@ExportBudget')->name('resort.budget.GetConsolidateFile');
    Route::get('/resorts/{resortId}/budget/consolidated', 'BudgetController@viewConsolidatedBudget')
    ->name('resort.budget.viewconsolidated');
    Route::post('/resorts/{resortId}/budget/save-cost-assignment', 'BudgetController@saveBudgetCostAssignment')
    ->name('resort.budget.saveCostAssignment');
    Route::post('/resorts/{resortId}/budget/get-configuration', 'BudgetController@getConfiguration')
    ->name('resort.budget.getConfiguration');

    // Route::get('/get-HodEmployeelist', ['App\Http\Controllers\Resorts\EmployeeController','HodEmployeelist'])->name('resort.employeelist');

    /* end of employee */
    /* Resort Internal Page Permission */
    Route::get( '/get/permission-page', ['App\Http\Controllers\Resorts\ResortInternalPermission','Permissionpage'])->name('resort.Page.Permission');
    Route::post( '/update_internal_permissions', ['App\Http\Controllers\Resorts\ResortInternalPermission','UpdateInternalPermissions'])->name('resorts.update_internal_permissions');
    Route::post( '/search/permissions', ['App\Http\Controllers\Resorts\ResortInternalPermission','SearchPermissions'])->name('resorts.searchPermissions');
    // resort internal page permission end

    // others
    Route::post( '/get/resort-division', ['App\Http\Controllers\Resorts\ResortInternalPermission','GetDivisionWiseDepartment'])->name('resort.get.ResortDivision');

    // others
    Route::post( '/get/position', ['App\Http\Controllers\Resorts\ResortInternalPermission','GetDepartmentWisePosition'])->name('resort.get.position');
    Route::post( '/get/internal-permissions', ['App\Http\Controllers\Resorts\ResortInternalPermission','InternalPermissiones'])->name('resort.get.InternalPermissiones');
    Route::post('/get/occupancy-data', ['App\Http\Controllers\Resorts\OccupancyController', 'getOccupancyData'])->name('occupancy.getData');

    // Notifications
    Route::post('workforce-planning/requestmanning', 'ResortAllNotificationController@ManningNotification')->name('resort.manning.notification');
    Route::post('workforce-planning/reminder/request-manning', 'ResortAllNotificationController@ReminderRequestManning')->name('resort.reminder.manning.notification');
    Route::post('workforce-planning/send-to-finance', 'ResortAllNotificationController@SendToFinance')->name('resort.SendToFinance.manning.notification');
    Route::post('workforce-planning/revise-budget', 'ResortAllNotificationController@ReviseBudget')->name('resort.ReviseBudget.manning.notification');
    // End of noftications

    // SiteSettings
    Route::get(  '/site/setting', 'SitesettignsController@index')->name('resort.sitesettings');
    Route::post(   '/site/setting', 'SitesettignsController@UpdateSiteSettings')->name('resort.updatesitesettings');
    Route::post('/site/setting/currency-update','SitesettignsController@UpdateCurrency')->name('sitesetting.UpdateCurrency');

    Route::post('update/resort-menutype', 'SitesettignsController@UpdateResortMenuType')->name('resort.update.menu-type');
    // End
    Route::post('/get/year-base-positions','WorkforcePlanningDashboardController@GetYearBasePositions')->name('hod.getYearBasePositions');
    Route::get('/get-currency-rates/{resortId}', 'SitesettignsController@getCurrencyRates')->name('getCurrencyRates');;

    //2. Talent Acquisition Module

    Route::get( '/talent-acquisition/view-vacancies', 'TalentAcquisition\VacancyController@index')->name('resort.vacancies.index');
    Route::get( '/talent-acquisition/add-vacancies', 'TalentAcquisition\VacancyController@create')->name('resort.vacancies.create');
    Route::post( '/talent-acquisition/store-vacancies', 'TalentAcquisition\VacancyController@store')->name('resort.vacancies.store');
    Route::get('/talent-acquisition/get-rank', 'TalentAcquisition\VacancyController@getRank')->name('resort.getRank');
    // Route::get('/talent-acquisition/FreshApplicant', 'TalentAcquisition\VacancyController@GetAllApplicatioWiseVacancies')->name('');

    Route::get('/talent-acquisition/vacancies-grid-view', 'TalentAcquisition\VacancyController@GridViewData')->name('ta.vacancies.GirdData');

    Route::get('/talent-acquisition/get-applicant-list', 'TalentAcquisition\VacancyController@GetAllApplicatioWiseVacancies')->name('resort.vacancies.GetapplicantList');

    Route::get( 'talent-acquisition/view-all-vacancies', ['App\Http\Controllers\Resorts\TalentAcquisition\VacancyController','GetViewVacancies'])->name('resort.ta.ViewVacancies');
    Route::get( 'talent-acquisition/all-vacancies', ['App\Http\Controllers\Resorts\TalentAcquisition\VacancyController','GetAllVacancies'])->name('resort.ta.GetAllVacancies');
    Route::post( '/talent-acquisition/get-vacancy-status', 'TalentAcquisition\VacancyController@getVacancyStatus')->name('resort.vacancies.getstatus');

    Route::get( 'talent-acquisition/alltodolist', ['App\Http\Controllers\Resorts\TalentAcquisition\VacancyController','ViewAllToDo'])->name('resort.ta.alltodolist');
    Route::get( 'talent-acquisition/shortlisted/applicants', ['App\Http\Controllers\Resorts\TalentAcquisition\VacancyController','shortlistedapplicantsShareLink'])->name('resort.ta.shortlistedapplicants');
    Route::get( 'talent-acquisition/shortlisted/{id}', ['App\Http\Controllers\Resorts\TalentAcquisition\VacancyController','shortlisted'])->name('resort.ta.shortlisted');
    Route::get( 'talent-acquisition/shortlisted', ['App\Http\Controllers\Resorts\TalentAcquisition\VacancyController','shortlisted'])->name('resort.ta.shortlistedIndex');
    Route::get( 'talent-acquisition/upcoming-applicants', ['App\Http\Controllers\Resorts\TalentAcquisition\VacancyController','UpcomingApplicants'])->name('resort.ta.UpcomingApplicants');
    Route::post( 'talent-acquisition/get/add-interview-link', ['App\Http\Controllers\Resorts\TalentAcquisition\VacancyController','AddInterViewLink'])->name('resort.ta.AddInterViewLink');

    Route::get( '/get/talent-acquisition/configration', ['App\Http\Controllers\Resorts\TalentAcquisition\ConfigController','index'])->name('resort.ta.configration');

    Route::post( '/get/talent-acquisition/final-approval', ['App\Http\Controllers\Resorts\TalentAcquisition\ConfigController','FianlApproval'])->name('resort.ta.FinalApproval');
    Route::post('/talent-acquisition//final-approval/save', ['App\Http\Controllers\Resorts\TalentAcquisition\ConfigController', 'saveFinalApproval'])
     ->name('ta.finalApproval.save');
    // Questionnaire Controller
    Route::get( '/get/talent-acquisition/questionnaire', ['App\Http\Controllers\Resorts\TalentAcquisition\QuestionnaireController','index'])->name('resort.ta.Questionnaire');

    Route::get( '/get/talent-acquisition/add-questionnaire', ['App\Http\Controllers\Resorts\TalentAcquisition\QuestionnaireController','create'])->name('resort.ta.add.Questionnaire');
    Route::post('/talent-acquisition/store.',['App\Http\Controllers\Resorts\TalentAcquisition\QuestionnaireController','store'])->name('resort.ta.store.Questionnaire');
    Route::get('/talent-acquisition/get-resort-wise-question', ['App\Http\Controllers\Resorts\TalentAcquisition\QuestionnaireController','getResortWiseQuestion'])->name('resort.ta.getResortWiseQuestion');
    Route::post('/talent-acquisition/inline-question-update', ['App\Http\Controllers\Resorts\TalentAcquisition\QuestionnaireController','update'])->name('resort.ta.update.Questionnaire');

    Route::post('talent-acquisition/destroy-questions/', ['App\Http\Controllers\Resorts\TalentAcquisition\QuestionnaireController','destroy'])->name('resort.ta.destroyQuestions');
    Route::get( '/talent-acquisition/edit-questions/{id}/edit', ['App\Http\Controllers\Resorts\TalentAcquisition\QuestionnaireController','edit'])->name('resort.ta.Questions.edit');

    Route::post('talent-acquisition/position-sections/', ['App\Http\Controllers\Resorts\TalentAcquisition\ConfigController','PositionSections'])->name('resort.ta.PositionSections');
    Route::post('talent-acquisition/add-agent/', ['App\Http\Controllers\Resorts\TalentAcquisition\ConfigController','AddTicketAgent'])->name('resort.ta.add.Agent');
    Route::put('/talent-acquisition/inline-agent-update/{id}', ['App\Http\Controllers\Resorts\TalentAcquisition\ConfigController','inlineUpdateAgent'])->name('resort.ta.inlineUpdateAgent');

    Route::get( '/get/talent-acquisition/get-resort-agent-ticket-list', ['App\Http\Controllers\Resorts\TalentAcquisition\ConfigController','GetAgentTicketList'])->name('resort.ta.getResortAgentTicketList');
    Route::delete('/talent-acquisition/destroy-agentList/{id}', ['App\Http\Controllers\Resorts\TalentAcquisition\ConfigController','DestroyAgentList'])->name('resort.ta.destroyAgentList');

    //Hiring source
    Route::post('talent-acquisition/add-hiring-source/', ['App\Http\Controllers\Resorts\TalentAcquisition\ConfigController','AddHiringSource'])->name('resort.ta.add.source');
    Route::get( 'talent-acquisition/get-hiring-source/', ['App\Http\Controllers\Resorts\TalentAcquisition\ConfigController','GetHiringSource'])->name('resort.ta.get.source');
    Route::delete('talent-acquisition/delete-hiring-source/{id}', ['App\Http\Controllers\Resorts\TalentAcquisition\ConfigController','DestroyHiringSource'])->name('resort.ta.delete.source');

    Route::post('talent-acquisition/terms-and-conditions/store', ['App\Http\Controllers\Resorts\TalentAcquisition\ConfigController','storeOrUpdateTC'])->name('resort.ta.termscondition.storeOrUpdate');

    Route::post('/talent-acquisition/job-description', ['App\Http\Controllers\Resorts\TalentAcquisition\JobDescriptionController','store'])->name('resort.ta.jobdescription.store');

    Route::get( '/talent-acquisition/get/job-description', ['App\Http\Controllers\Resorts\TalentAcquisition\JobDescriptionController','index'])->name('resort.ta.jobdescription.index');
    Route::get( '/talent-acquisition/get/job-description/list', ['App\Http\Controllers\Resorts\TalentAcquisition\JobDescriptionController','GetJobDescList'])->name('resort.ta.jobdescription.GetJobDescList');
    Route::get( '/talent-acquisition/job-description/{id}/edit', ['App\Http\Controllers\Resorts\TalentAcquisition\JobDescriptionController','edit'])->name('resort.ta.jobdescription.edit');

    Route::put( '/talent-acquisition/job-description/update/{id}/', ['App\Http\Controllers\Resorts\TalentAcquisition\JobDescriptionController','update'])->name('resort.ta.jobdescription.update');
    Route::delete( '/talent-acquisition/job-description/destroy/{id}/', ['App\Http\Controllers\Resorts\TalentAcquisition\JobDescriptionController','destroy'])->name('resort.ta.jobdescription.destroy');
    Route::get( '/talent-acquisition/job-description/show/{id}/', ['App\Http\Controllers\Resorts\TalentAcquisition\JobDescriptionController','show'])->name('resort.ta.jobdescription.show');
    Route::get( '/talent-acquisition/job-description/download/{slug}/', ['App\Http\Controllers\Resorts\TalentAcquisition\JobDescriptionController','download'])->name('resort.ta.jobdescription.download');
    // Job Advertisement
    Route::post('/talent-acquisition/job-advertisement/upload', ['App\Http\Controllers\Resorts\TalentAcquisition\JobAdvertisementController','StoreJobAvd'])->name('resort.ta.jobadvertisment.upload');

    Route::post('/talent-acquisition/job-advertisement/upload', ['App\Http\Controllers\Resorts\TalentAcquisition\JobAdvertisementController','StoreJobAvd'])->name('resort.ta.jobadvertisment.upload');

    Route::Post('/talent-acquisition/hold-vcancies', ['App\Http\Controllers\Resorts\TalentAcquisition\ConfigController','TaHoldVcanciesNotification'])->name('resort.ta.HiringNotification');

    Route::Post('/talent-acquisition/rejection-vcancies', ['App\Http\Controllers\Resorts\TalentAcquisition\ConfigController','TaRejectionVcanciesNotification'])->name('resort.ta.RejectionVcancies');

    Route::Post('/talent-acquisition/approved-vcancies', ['App\Http\Controllers\Resorts\TalentAcquisition\ConfigController','TaApprovedVcanciesNotification'])->name('resort.ta.ApprovedVcancies');

    Route::Post('/talent-acquisition/generate-adv-link', ['App\Http\Controllers\Resorts\TalentAcquisition\JobAdvertisementController','GenrateAdvLink'])->name('resort.ta.GenrateAdvLink');

    // show the Applicants
    Route::get('/talent-acquisition/applicants/{id}', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','VacnacyWiseApplicants'])->name('resort.ta.Applicants');
    Route::get('/talent-acquisition/fresh-applicant', 'TalentAcquisition\VacancyController@GetAllApplicatioWiseVacancies')->name('resort.vacancies.FreshApplicant');

    Route::get('/talent-acquisition/vacancies/fresh-applicant', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','GetVacnacyWiseApplicants'])->name('resort.ta.getApplicant');
    Route::Post('/talent-acquisition/applicant-note', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','ApplicantNote'])->name('resort.ta.ApplicantNote');

    Route::post('/talent-acquisition/applicant-status', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','ApplicantWiseStatus'])->name('resort.ta.ApplicantWiseStatus');
    Route::get('/talent-acquisition/user/applicant-side-bar/{id}', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','TaUserApplicantsSideBar'])->name('resort.ta.TaUserApplicantsSideBar');
    Route::post('/talent-acquisition/applicant-file-download', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','GetAwsFiles'])->name('resort.ta.DownloadFile');


    Route::post('/talent-acquisition/get-applicant-wise-grid-wise', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','getApplicantWiseGridWise'])->name('resort.ta.getApplicantWiseGridWise');
    Route::get('/talent-acquisition/get-applicant-wise-notes/{id}', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','getApplicantWiseNotes'])->name('resort.ta.getApplicantWiseNotes');
    Route::get('/talent-acquisition/sortlisted-applicants/{id}', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','SortlistedApplicants'])->name('resort.ta.SortlistedApplicants');
    Route::post('/talent-acquisition/interview-request', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','InterviewRequest'])->name('resort.ta.InterviewRequest');

    Route::post('/talent-acquisition/get-slots', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','ApplicantTimeZoneget'])->name('resort.ta.ApplicantTimeZoneget');
    Route::post('/talent-acquisition/approved-or-sort-applicant-wise-status', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','ApprovedOrSortApplicantWiseStatus'])->name('resort.ta.ApprovedOrSortApplicantWiseStatus');
    Route::post('/talent-acquisition/round-wise-form', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','RoundWiseForm'])->name('resort.ta.RoundWiseForm');
    Route::post('/talent-acquisition/destroy-applicant', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','destoryApplicant'])->name('resort.ta.destoryApplicant');
    Route::post('/talent-acquisition/get-date-click-wise-upcoming-interview', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','GetDateclickWiseUpcomingInterview'])->name('resort.ta.GetDateclickWiseUpcomingInterview');

    // Email Tamplate start

    Route::get('/talent-acquisition/email-templates', ['App\Http\Controllers\Resorts\TalentAcquisition\TaEmailTemplateController','index'])->name('resort.ta.emailtemplates');
    Route::get('/talent-acquisition/get-email-template/{id}', ['App\Http\Controllers\Resorts\TalentAcquisition\TaEmailTemplateController','Show'])->name('resort.ta.EmailTemplate');
    Route::put('/talent-acquisition/store-email-template/{id}', ['App\Http\Controllers\Resorts\TalentAcquisition\TaEmailTemplateController','Store'])->name('resort.ta.StoreEmailTemplate');

    //Interview Assessment Form builder
    Route::get('/interview-assessment/create', 'TalentAcquisition\InterviewAssessmentController@create')->name('interview-assessment.create');
    Route::post('/interview-assessment/store', 'TalentAcquisition\InterviewAssessmentController@store')->name('interview-assessment.store');
    Route::get('/interview-assessment', 'TalentAcquisition\InterviewAssessmentController@index')->name('interview-assessment.index');
    Route::get('/interview-assessment/list', 'TalentAcquisition\InterviewAssessmentController@list')->name('interview-assessment.list');
    Route::get('/interview-assessment/edit/{id}', 'TalentAcquisition\InterviewAssessmentController@edit')->name('interview-assessment.edit');
    Route::post('/interview-assessment/update/{id}', 'TalentAcquisition\InterviewAssessmentController@update')->name('interview-assessment.update');
    Route::delete('/interview-assessment/delete/{id}', 'TalentAcquisition\InterviewAssessmentController@delete')->name('interview-assessment.delete');

    Route::get('/interview-assessment/{position_id}/{applicant_id}','TalentAcquisition\InterviewAssessmentController@show')->name('interview-assessment.show');
    Route::post('/interview-assessment/{id}/response', 'TalentAcquisition\InterviewAssessmentController@saveResponse')->name('interview-assessment.saveResponse');
    Route::get('/interview-assessment/view/{formId}/{responseId}', 'TalentAcquisition\InterviewAssessmentController@viewResponse')->name('interview-assessment.viewResponse');

    //offline interview
    Route::get('/get-departments-by-divisions/{divid}','TalentAcquisition\OfflineInterviewController@getDepartmentsByDivision')->name('departments.get');
    Route::get('/get-sections-by-department/{deptid}','TalentAcquisition\OfflineInterviewController@getSectionsByDept')->name('sections.get');
    Route::get('/get-positions-by-department/{deptid}','TalentAcquisition\OfflineInterviewController@getPositionByDept')->name('positions.get');
    Route::get('/get-reporting-employess-by-department/{deptid}','TalentAcquisition\OfflineInterviewController@getReportingEmployess')->name('reporting.employees.get');

    Route::get('/offline-interview', 'TalentAcquisition\OfflineInterviewController@index')->name('offline-interview.index');

    Route::post('/create-job-ad', 'TalentAcquisitionDashboardController@createJobAd')->name('job-advertise.create');
    Route::post( '/top-hiring-sources', 'TalentAcquisitionDashboardController@topHiringSources')->name('resort.ta.topHiringSources');
    Route::get( '/talent-acquisition/get-offline-interview', ['App\Http\Controllers\Resorts\TalentAcquisition\OfflineInterviewController','create'])->name('offline-interview.create');

    //End of Talent Acquisition

    // Time and Attendance Dashboard
    Route::get('/time-and-attendance/configration', 'TimeAndAttendance\ConfigrationController@index')->name('resort.timeandattendance.Configration');
    Route::post( '/time-and-attendance/shift-store', 'TimeAndAttendance\ConfigrationController@ShiftStore')->name('resort.timeandattendance.ShiftStore');
    Route::post( '/time-and-attendance/remove-shift', 'TimeAndAttendance\ConfigrationController@removeshift')->name('resort.timeandattendance.removeShift');
    Route::post( '/time-and-attendance/geo-fencing', 'TimeAndAttendance\ConfigrationController@GeoLocation')->name('resort.timeandattendance.GeoFencing');
    Route::post( '/time-and-attendance/resort-holiday', 'TimeAndAttendance\ConfigrationController@ResortHoliday')->name('resort.timeandattendance.ResortHoliday');
    Route::get('/time-and-attendance/public-holiday-list', 'TimeAndAttendance\ConfigrationController@Publicholidaylist')->name('resort.timeandattendance.publicholidaylist');
    Route::post( '/time-and-attendance/resort-holiday-update', 'TimeAndAttendance\ConfigrationController@ResortHolidayUpdate')->name('resort.timeandattendance.ResortHolidayUpdate');
    Route::post( '/time-and-attendance/resort-holiday-delete', 'TimeAndAttendance\ConfigrationController@ResortHolidaydelete')->name('resort.timeandattendance.ResortHolidaydelete');
    Route::post( '/time-and-attendance/holiday-file-upload', 'TimeAndAttendance\ConfigrationController@HolidayfileUpload')->name('resort.timeandattendance.HolidayfileUpload');
    Route::post('/time-and-attendance/color-themes/save', 'TimeAndAttendance\ConfigrationController@saveColorThemes')->name('colorThemes.save');
    Route::post('/time-and-attendance/color-themes/delete', 'TimeAndAttendance\ConfigrationController@delete')->name('colorThemes.delete');
    Route::post('/time-and-attendance/export', 'TimeAndAttendance\ConfigrationController@exportAttendance')->name('attendance.export');
    Route::get('/time-and-attendance/create-duty-roster', 'TimeAndAttendance\DutyRosterController@CreateDutyRoster')->name('resort.timeandattendance.CreateDutyRoster');
    Route::post('/time-and-attendance/duty-roster-and-leave', 'TimeAndAttendance\DutyRosterController@DutyRosterandLeave')->name('resort.timeandattendance.DutyRosterandLeave');
    Route::post('/time-and-attendance/store-duty-roster', 'TimeAndAttendance\DutyRosterController@StoreDutyRoster')->name('resort.timeandattendance.StoreDutyRoster');
    Route::post('/time-and-attendance/update-duty-roster', 'TimeAndAttendance\DutyRosterController@UpdateDutyRoster')->name('resort.timeandattendance.UpdateDutyRoster');
    Route::get('/time-and-attendance/view-duty-roster', 'TimeAndAttendance\DutyRosterController@ViewDutyRoster')->name('resort.timeandattendance.ViewDutyRoster');

    Route::get('/time-and-attendance/dutyroster/dashboard-list', 'TimeandAttendanceDashboardController@HodDutyRosterdashboardTable')->name('resort.timeandattendance.DutyRosterdashboardTable');
    Route::get('/time-and-attendance/month/over-time-chart/{date}', 'TimeandAttendanceDashboardController@MonthOverTimeChart')->name('resort.timeandattendance.MonthOverTimeChart');
    Route::get('/time-and-attendance/get/year-wise-attandance-data/{year}/{date}', 'TimeandAttendanceDashboardController@GetYearWiseAttandanceData')->name('resort.timeandattendance.GetYearWiseAttandanceData');
    Route::post('/time-and-attendance/dutyroster/search', 'TimeAndAttendance\DutyRosterController@DutyRosterSearch')->name('resort.timeandattendance.DutyRosterSearch');
    Route::get('/time-and-attendance/location-history', 'TimeAndAttendance\DutyRosterController@LocationHistory')->name('resort.timeandattendance.LocationHistory');
    Route::get('/time-and-attendance/overtime', 'TimeAndAttendance\DutyRosterController@OverTime')->name('resort.timeandattendance.OverTime');
    Route::get('/time-and-attendance/overtime-filter', 'TimeAndAttendance\DutyRosterController@OverTimeFilter')->name('resort.timeandattendance.OverTimeFilter');
    Route::post('/time-and-attendance/store-overtime', 'TimeAndAttendance\DutyRosterController@StoreOverTime')->name('resort.timeandattendance.StoreOverTime');
    Route::get('/time-and-attendance/employee', 'TimeAndAttendance\EmployeeController@index')->name('resort.timeandattendance.employee');
    Route::get('/time-and-attendance/search-employeegird', 'TimeAndAttendance\EmployeeController@SearchEmployeegird')->name('resort.timeandattendance.SearchEmployeegird');
    Route::get('/time-and-attendance/employee-list', 'TimeAndAttendance\EmployeeController@EmployeeList')->name('resort.timeandattendance.EmployeeList');
    Route::get('/time-and-attendance/employee-details/{id}', 'TimeAndAttendance\EmployeeController@EmployeeDetails')->name('resort.timeandattendance.employee.details');
    Route::post('/time-and-attendance/update-attendance-history', 'TimeAndAttendance\EmployeeController@HistoryUpdate')->name('resort.timeandattendance.updateattandancehistory');
    Route::get('/time-and-attendance/attendance-history/{id}', 'TimeAndAttendance\EmployeeController@AttandanceHisotry')->name('resort.timeandattendance.employee.AttandanceHisotry');


    Route::get('/time-and-attendance/register', 'TimeAndAttendance\AttandanceRegisterController@Index')->name('resort.timeandattendance.AttandanceRegister');
    Route::get('/time-and-attendance/todolist', 'TimeandAttendanceDashboardController@Todolist')->name('resort.timeandattendance.todolist');
    Route::get('/time-and-attendance/get-overtime-entries', 'TimeandAttendanceDashboardController@GetOvertimeEntries')->name('resort.timeandattendance.GetOvertimeEntries');
    Route::post('/time-and-attendance/update-overtime-status', 'TimeandAttendanceDashboardController@UpdateOvertimeStatus')->name('resort.timeandattendance.UpdateOvertimeStatus');
    Route::post('/time-and-attendance/overtime/status-update', 'TimeAndAttendance\EmployeeController@OTStatusUpdate')->name('resort.timeandattendance.OTStatusUpdate');
    Route::post('/time-and-attendance/manual-check-in-out', 'TimeandAttendanceDashboardController@ManualCheckInOut')->name('resort.timeandattendance.ManualCheckInOut');
    Route::post('/time-and-attendance/employee-details-print', 'TimeAndAttendance\EmployeeController@EmpDetailsPrint')->name('resort.timeandattendance.EmpDetailsPrint');

    Route::post('/time-and-attendance/employee-details-filter', 'TimeAndAttendance\EmployeeController@EmpDetailsFilters')->name('resort.timeandattendance.EmpDetailsFilters');

    // Hr Dashboard
    Route::get('/time-and-attendance/hr-duty-roster/dashboard-list', 'TimeandAttendanceDashboardController@HrDutyRosterdashboardTable')->name('resort.timeandattendance.HrDutyRosterdashboardTable');
    Route::get('/time-and-attendance/hr-month-over-time-chart/{Dept_id}/{date}', 'TimeandAttendanceDashboardController@HRMonthOverTimeChart')->name('resort.timeandattendance.HRMonthOverTimeChart');
    Route::get('/time-and-attendance/get-year-hr-wise-attendance-data/{Year}/{Dept_id}/{date}', 'TimeandAttendanceDashboardController@GetYearHrWiseAttandanceData')->name('resort.timeandattendance.GetYearHrWiseAttandanceData');
    Route::get('/time-and-attendance/hr-dashboard-count/{date}', 'TimeandAttendanceDashboardController@HrDashboardCount')->name('resort.timeandattendance.HrDashboardCount');
    Route::get('/time-and-attendance/hod-dashboard-count/{date}', 'TimeandAttendanceDashboardController@HodDashboardCount')->name('resort.timeandattendance.HodDashboardCount');

    Route::post('/time-and-attendance/checkout/time-missing', 'TimeAndAttendance\AttandanceRegisterController@CheckoutTimeMissing')->name('resort.timeandattendance.CheckoutTimeMissing');
    Route::get('/time-and-attendance/register-roster-search', 'TimeAndAttendance\AttandanceRegisterController@ResigterRosterSearch')->name('resort.timeandattendance.ResigterRosterSearch');
    Route::get('/time-and-attendance/download-attendance-template', 'TimeAndAttendance\AttandanceRegisterController@downloadTemplate')->name('resort.timeandattendance.DownloadAttendanceTemplate');
    Route::post('/time-and-attendance/import-attendance', 'TimeAndAttendance\AttandanceRegisterController@ImportAttandance')->name('resort.timeandattendance.ImportAttandance');

    //end of time Attendance Module

   //Leave Module Start

    Route::get('/leave/configuration', 'Leave\ConfigController@index')->name('leave.configration');
    Route::post('/leaves/category/store', 'Leave\ConfigController@store_leaves_category')->name('leaves.category.store');
    Route::post('/leaves/categories/update/{id}', 'Leave\ConfigController@update_leaves_category')->name('leaves.category.update');
    Route::delete('/leaves/categories/delete/{id}', 'Leave\ConfigController@delete_leaves_category')->name('leaves.category.delete');
    Route::post('/leaves/get-eligible', 'Leave\ConfigController@getEligibleLeaves')->name('leaves.getEligible');

    Route::get('/upcoming-holidays', 'Leave\DashboardController@get_upcomimg_holidays')->name('resort.upcomingholiday.list');
    Route::get('/upcoming-birthdays', 'Leave\DashboardController@getUpcomingBirthdays')->name('resort.upcomingBirthdays');
    Route::get('/upcoming-birthdays/list', 'Leave\DashboardController@getUpcomingBirthdaysList')->name('resort.upcomingBirthdays.list');
    Route::post('/send-birthday-notification', 'Leave\DashboardController@sendBirthdayNotification')->name('birthday.notification.send');

    Route::post('/transportation-options/submit', 'Leave\ConfigController@submitTransportationOptions')->name('submit.transportation');

    Route::get('/leave/apply','Leave\LeaveController@index')->name('leave.apply');
    Route::get('/leave/request','Leave\LeaveController@request')->name('leave.request');
    Route::get('/leave/filter/grid', 'Leave\LeaveController@filterLeaveGridRequests')->name('leave.filter.grid');

    Route::get('/leave/details/{leave_id}', 'Leave\LeaveController@details')->name('leave.details');
    Route::get('/leave/leave-histroy', 'Leave\LeaveController@getLeaveHistory')->name('leave.history');
    Route::get('/leave-history/download-pdf/{empID}', 'Leave\LeaveController@downloadPdf')->name('leave.history.download-pdf');
    Route::post('/leave/application/store','Leave\LeaveController@store')->name('leave-applications.store');

    Route::get('/get/employees-on-leave', 'Leave\LeaveController@getEmployeesOnLeave')->name('getEmployeesOnLeave');
    Route::get('/get/upcoming-leaves', 'Leave\LeaveController@getUpcomingLeaves')->name('getUpcomingLeaves');
    Route::get('/get/leave-requests', 'Leave\DashboardController@getLeaveRequests')->name('leave-requests.get');

    Route::post('/get/leave-chart', 'Leave\DashboardController@getLeaveChartData')->name('leave-chart-data');
    Route::get('/get/combine-info', 'Leave\LeaveController@getCombineInfo')->name('leaves.combineInfo.get');
    Route::get('/get/used-leave','Leave\LeaveController@getLeaveCount')->name('leaves.used.leave');

    Route::post('/leaves/handle-leave-action', 'Leave\LeaveController@handleLeaveAction')->name('leave.handleAction');
    Route::post('/leaves/recommend-alternative-dates','Leave\LeaveController@recommendAlternativeDate')->name('leave.recommendAlternativeDate');
    Route::post('/leaves/send-email-to-travel-partner', 'Leave\LeaveController@sendEmailToTravelPartner')
     ->name('send.email.to.travel.partner');

    Route::get('/leave/calendar','Leave\LeaveCalenadarController@index')->name('leave.calendar');
    Route::get('/leaves/calendar-leaves', 'Leave\LeaveCalenadarController@getLeaves')->name('calendar.leaves.get');

    Route::get('/leaves/boarding-pass-requests', 'Leave\BoardingPassController@index')->name('resort.boardingpass.list');
    Route::get('/leaves/boarding-pass-application', 'Leave\BoardingPassController@apply')->name('resort.boardingpass.apply');
    Route::post('/leaves/boarding-pass-submit', 'Leave\BoardingPassController@store')->name('resort.boardingpass.store');
    Route::post('/leaves/handle-pass-action', 'Leave\BoardingPassController@handlePassAction')->name('pass.handleAction');
    Route::get('/leaves/boarding-pass/filter/grid', 'Leave\BoardingPassController@filterPassGridRequests')->name('pass.filter.grid');
    Route::get('/leaves/boarding-pass', 'Leave\BoardingPassController@index')->name('resort.boarding-pass');
    Route::post('/leaves/boarding/status-update', 'Leave\BoardingPassController@BoardingPassStatusUpdate')->name('resort.BoardingStatusUpdate');


    Route::post('/leave/export', 'Leave\ConfigController@exportLeave')->name('leave.export');
    Route::post('/leave/import', 'Leave\ConfigController@ImportLeave')->name('leave.import');

    //  Accommodation

    // Dashboard
    Route::get('accommodation/hr-get/maintanance-request', 'Accommodation\AccommodationDashboardController@HR_Dashobard')->name('resort.accommodation.HrGetMaintananceRequest');
    Route::get('accommodation/aminities', 'Accommodation\AccommodationDashboardController@Aminities')->name('resort.accommodation.Aminities');
    Route::get('accommodation/hod-get/maintanance-request', 'Accommodation\AccommodationDashboardController@Hod_dashboard')->name('resort.accommodation.HODGetMaintananceRequest');

    Route::get('accommodation/hr-complition-request', 'Accommodation\AccommodationDashboardController@AccomComplitionRequest')->name('resort.accommodation.HraccomComplitionRequest');

    Route::get('accommodation/hod-tableassign-task', 'Accommodation\AccommodationDashboardController@HODtableassignTask')->name('resort.accommodation.HODtableassignTask');

    Route::get('accommodation/configuration', 'Accommodation\ConfigrationController@index')->name('resort.accommodation.config.index');
    Route::post('accommodation/inventory/category', 'Accommodation\ConfigrationController@InvenptoryCategoryStore')->name('resort.accommodation.StoreInvCategory');
    Route::get('accommodation/inventory/category/index', 'Accommodation\ConfigrationController@InvenotryCatIndex')->name('resort.accommodation.config.InvenotryCatIndex');
    Route::put('/accommodation/inventory/category/inline-update/{id}', 'Accommodation\ConfigrationController@CategoryUpdate')->name('resort.accommodation.inlineCatUpdate');
    Route::delete('/accommodation/inventory/category/inline-destroy/{id}', 'Accommodation\ConfigrationController@Catdestory')->name('resort.accommodation.Catdestory');
    Route::post('accommodation/check-room-exists', 'Accommodation\ConfigrationController@checkRoomIsExits')->name('resort.accommodation.config.checkRoomIsExits');



    Route::post('accommodation/type-store', 'Accommodation\ConfigrationController@AccommodationTypeStore')->name('resort.accommodation.AccommodationTypeStore');
    Route::get('accommodation/index', 'Accommodation\ConfigrationController@AccommodationIndex')->name('resort.accommodation.config.AccommodationIndex');
    Route::put('/accommodation/update/{id}', 'Accommodation\ConfigrationController@AccommodationUpdate')->name('resort.accommodation.AccommodationUpdate');
    Route::delete('/accommodation/destroy/{id}', 'Accommodation\ConfigrationController@Accommodationdestory')->name('resort.accommodation.Accommodationdestory');

    Route::post('accommodation/building/store', 'Accommodation\ConfigrationController@StoreBuilding')->name('resort.accommodation.StoreBuilding');
    Route::get('accommodation/building/index', 'Accommodation\ConfigrationController@BuildingIndex')->name('resort.accommodation.config.BuildingIndex');
    Route::put('/accommodation/building/update/{id}', 'Accommodation\ConfigrationController@BuildingUpdate')->name('resort.accommodation.BuildingUpdate');
    Route::delete('/accommodation/building/destroy/{id}', 'Accommodation\ConfigrationController@BuildingDestory')->name('resort.accommodation.BuildingDestory');

    Route::post('accommodation/floor-and-room/store', 'Accommodation\ConfigrationController@StoreFloorandroom')->name('resort.accommodation.StoreFloorandroom');
    Route::get('accommodation/floor-and-room/index', 'Accommodation\ConfigrationController@FloorRoomIndex')->name('resort.accommodation.config.FloorRoomIndex');
    Route::put('/accommodation/floor-and-room/update/{id}', 'Accommodation\ConfigrationController@FloorAndRoomUpdate')->name('resort.accommodation.FloorAndRoomUpdate');
    Route::get('accommodation/get-floor', 'Accommodation\ConfigrationController@GetBuildingWiseFloor')->name('resort.accommodation.config.getFloor');
    Route::get('accommodation/get-floor-wise-rooms', 'Accommodation\ConfigrationController@GetFloorWiseRooms')->name('resort.accommodation.config.GetFloorWiseRooms');
    Route::post('accommodation/occupancy/threshold-form', 'Accommodation\ConfigrationController@OccupancyThreshold')->name('resort.accommodation.config.OccupancyThresholdForm');
    Route::post('accommodation/escalation-day', 'Accommodation\ConfigrationController@EscalationDay')->name('resort.accommodation.config.EscalationDay');
    Route::get('accommodation/accommodation-template', 'Accommodation\ConfigrationController@AccommodationTemplete')->name('resort.accommodation.config.AccommodationTemplete');
    Route::get('accommodation/quick-assignment-template', 'Accommodation\ConfigrationController@QuickAssignmentTemplete')->name('resort.accommodation.config.QuickAssignmentTemplete');

    Route::post('accommodation/available-file-store', 'Accommodation\ConfigrationController@AccommodationAvailableFileStore')->name('resort.accommodation.AccommodationAvailableFileStore');
    Route::post('accommodation/quick-assignment-file-store', 'Accommodation\ConfigrationController@QuickAssignmentFileStore')->name('resort.accommodation.QuickAssignmentFileStore');

    // Accommodataion Master
    Route::get('accommodation/master', 'Accommodation\AccommodationMasterController@index')->name('resort.accommodation.AccommodationMaster');
    Route::get('accommodation/get-building-wise-collapsed-data', 'Accommodation\AccommodationMasterController@GetBuildingWiseCollpasedData')->name('resort.accommodation.GetBuildingWiseCollpasedData');
    Route::get('accommodation/employee-accommodation', 'Accommodation\AccommodationMasterController@EmployeeAccommodation')->name('resort.accommodation.EmployeeAccommodation');
    Route::get('accommodation/search-emp-accommodation-grid', 'Accommodation\AccommodationMasterController@SearchEmpAccommodationgird')->name('resort.accommodation.SearchEmpAccommodationgird');
    Route::get('accommodation/emp-accommodation-list', 'Accommodation\AccommodationMasterController@EmpAccommodationList')->name('resort.accommodation.EmpAccommodationList');
    Route::get('accommodation/accommodation-employee-details/{id}', 'Accommodation\AccommodationMasterController@AccommodationEmployeeDetails')->name('resort.accommodation.AccommodationEmployeeDetails');
    Route::post('accommodation/assign-to-employee', 'Accommodation\AccommodationMasterController@AssignMoreAccommodationToEmp')->name('resort.accommodation.AssignMoreAccommodationToEmp');

    Route::get('accommodation/available-accommodation', 'Accommodation\AccommodationMasterController@GetBuilidingWiseAvailableAccommodation')->name('resort.accommodation.AvailableAccommodation');
    Route::get('accommodation/get-building-wise-available-acc-data', 'Accommodation\AccommodationMasterController@GetBuildingWiseAvailableCollpasedData')->name('resort.accommodation.GetBuildingWiseAvailableAccData');


    // Accommodation Manitanance
    Route::get('accommodation/maintenance-request/create', 'Accommodation\MaintananceContorller@CreateMaintenance')->name('resort.accommodation.CreateMaintenanceRequest');
    Route::post('accommodation/maintenance-request/store', 'Accommodation\MaintananceContorller@CreateMaintenanceRequest')->name('resort.accommodation.StoreMaintenanceRequest');


    Route::get('accommodation/inventory', 'Accommodation\InventoryController@index')->name('resort.accommodation.inventory');
    Route::post('accommodation/inventory/store', 'Accommodation\InventoryController@StoreInventory')->name('resort.accommodation.StoreInventory');
    Route::get('accommodation/inventory/management', 'Accommodation\InventoryController@InventoryManagement')->name('resort.accommodation.InventoryManagement');

    Route::get('accommodation/historical-inventory', 'Accommodation\InventoryController@HistoricalInventory')->name('resort.accommodation.HisotricalInvenotry');
    Route::put('/accommodation/inventory-update/{id}', 'Accommodation\InventoryController@Inventoryupdated')->name('resort.accommodation.Inventoryupdated');

    Route::post('accommodation/available-accommodation-store', 'Accommodation\ConfigrationController@AvailableAccommodationStore')->name('resort.accommodation.AvailableAccommodationStore');
    Route::get('accommodation/assign-accommodation', 'Accommodation\AssignAccommodationController@AssignAccommation')->name('resort.accommodation.AssignAccommation');
    Route::get('accommodation/buildingwise-accommodation', 'Accommodation\AssignAccommodationController@BuildingwiseAccommodation')->name('resort.accommodation.BuildingwiseAccommodation');
    Route::post('accommodation/get-assigned-bed', 'Accommodation\AssignAccommodationController@GetAssignedBed')->name('resort.accommodation.GetAssignedBed');
    Route::post('accommodation/assign-accommodation-to-emp', 'Accommodation\AssignAccommodationController@AssignAccommodationToEmp')->name('resort.accommodation.AssignAccommodationToEmp');
    Route::post('accommodation/get-accommodationwise-employee', 'Accommodation\AssignAccommodationController@GetAccmmodationwiseEmployee')->name('resort.accommodation.GetAccmmodationwiseEmployee');
    Route::post('accommodation/move-to-next', 'Accommodation\AssignAccommodationController@MoveToNext')->name('resort.accommodation.MoveToNext');


    Route::post('accommodation/hr-forward-to-hod-maintenance-request', 'Accommodation\MaintananceContorller@HrForwardToHODManitenanceRequest')->name('resort.accommodation.HrForwardToHODManitenanceRequest');

    Route::post('accommodation/hr-rejected-request', 'Accommodation\MaintananceContorller@HrRejeactedRequest')->name('resort.accommodation.HrRejeactedRequest');
    Route::post('accommodation/maintenance-request/forward-to-emp', 'Accommodation\AccommodationDashboardController@MainRequestForwordToEmp')->name('resort.accommodation.MainRequestForwordToEmp');

    Route::get('accommodation/maintenance-request/list', 'Accommodation\MaintananceContorller@MaintanaceRequestlist')->name('resort.accommodation.MaintanaceRequestlist');
    Route::get('accommodation/maintenance-request/on-hold', 'Accommodation\MaintananceContorller@MainRequestOnHold')->name('resort.accommodation.MainRequestOnHold');

    Route::get('accommodation/maintenance-request/details/{id}', 'Accommodation\MaintananceContorller@MainRequestDetails')->name('resort.accommodation.MainRequestDetails');
    Route::get('accommodation/maintenance-request/hold', 'Accommodation\MaintananceContorller@HoldMaintanaceRequest')->name('resort.accommodation.HoldMaintanaceRequest');

    Route::post('accommodation/hr-assign-to-emp', 'Accommodation\MaintananceContorller@HodAssignToEmp')->name('resort.accommodation.HodAssignToEmp');
    Route::get('accommodation/hod-main-request-details/{id}', 'Accommodation\MaintananceContorller@HODMainRequestDetails')->name('resort.accommodation.HODMainRequestDetails');

    Route::get('accommodation/maintanace-request/hod-hold/', 'Accommodation\MaintananceContorller@HODHoldMaintanaceRequest')->name('resort.accommodation.HODHoldMaintanaceRequest');
    Route::get('accommodation/maintanace-request/hod-list/', 'Accommodation\MaintananceContorller@HODMaintanaceRequestlist')->name('resort.accommodation.HODMaintanaceRequestlist');
    Route::get('accommodation/hod-assign-task-list/', 'Accommodation\MaintananceContorller@HODAssignTaskList')->name('resort.accommodation.HODAssignTaskList');

    Route::post('accommodation/unassign-item', 'Accommodation\InventoryController@UnassignItem')->name('resort.accommodation.UnassignItem');


    //Mataintanace Event
    Route::get('accommodation/event', 'Accommodation\EventController@index')->name('resort.accommodation.event');
    Route::get('accommodation/get/calender/data', 'Accommodation\EventController@getClanderData')->name('resort.accommodation.getClanderData');
    Route::get('accommodation/sidelist', 'Accommodation\EventController@Sidelist')->name('resort.accommodation.event.sidelist');
    // End of Accommodation


    //Payroll Module Start
    Route::get('payroll/configuration', 'Payroll\ConfigController@index')->name('payroll.configration');
    Route::get('payroll/export-service-charges', 'Payroll\ConfigController@exportServiceCharge')->name('export.service-charges');
    Route::post('payroll/import-service-charges', 'Payroll\ConfigController@importServiceCharge')->name('import.service-charges');
    Route::post('payroll/resort-service-charges-chart', 'Payroll\DashboardController@getServiceCharges')->name('chart.service-charges');
    Route::post('payroll/get-eligible-service-charge-employees', 'Payroll\PayrollController@getEligibleEmployees')->name('payroll.getEligibleEmployees');

    Route::post('payroll/earnings/submit', 'Payroll\ConfigController@storeEarnings')->name('earnings.submit');
    Route::get('payroll/earnings/download-template', 'Payroll\ConfigController@downloadEarningsTemplate')->name('allowances.download-template');
    Route::post('payroll/import-earnings', 'Payroll\ConfigController@importEarnings')->name('import.earnings');
    Route::post('payroll/deductions/submit', 'Payroll\ConfigController@storeDeductions')->name('deductions.submit');
    Route::get('payroll/deductions/download-template', 'Payroll\ConfigController@downloadDeductionTemplate')->name('deductions.download-template');
    Route::post('payroll/import-deductions', 'Payroll\ConfigController@importDeductions')->name('import.deductions');
    Route::post('payroll/save-cutoff-day', 'Payroll\ConfigController@storeCutoffDay')->name('save.cutoff.day');
    Route::get('payroll/shopkeepers/create', 'Payroll\ShopkeeperController@create')->name('shopkeepers.create');
    Route::post('payroll/shopkeepers/submit', 'Payroll\ShopkeeperController@store')->name('shopkeepers.save');
    Route::get('payroll/shopkeepers', 'Payroll\ShopkeeperController@index')->name('shopkeepers.index');
    Route::get('payroll/shopkeepers/list','Payroll\ShopkeeperController@list')->name('shopkeepers.list');
    Route::put('payroll/shopkeepers/inlineUpdate/{id}', 'Payroll\ShopkeeperController@inlineUpdate')->name('shopkeeper.inlineUpdate');
    Route::delete('payroll/shopkeepers/destroy/{id}', 'Payroll\ShopkeeperController@destroy')->name('shopkeeper.destroy');

    //Run Payroll
    Route::get('payroll/run-payroll', 'Payroll\PayrollController@index')->name('payroll.run');
    Route::get('/payroll/employees', 'Payroll\PayrollController@getEmployees')->name('payroll.employee.list');
    Route::post('/payroll/get-draft', 'Payroll\PayrollController@getDraftStepData')->name('get.payroll.draft');

    // run payroll step save start
    Route::post('/payroll/save-draft', 'Payroll\PayrollController@saveDraftPayroll')->name('payroll.save.draft');
    Route::post('/payroll/save-employees', 'Payroll\PayrollController@saveEmployeesToPayroll')->name('payroll.saveEmployees');
    Route::post('/payroll/save-attendance', 'Payroll\PayrollController@saveAttendanceToPayroll')->name('payroll.saveAttendance');
    Route::post('/payroll/save-attendance-note', 'Payroll\PayrollController@saveAttendanceNote')->name('payroll.saveAttendanceNote');
    Route::post('/payroll/save-service-charges', 'Payroll\PayrollController@saveServiceChargesToPayroll')->name('payroll.saveServiceCharges');
    Route::post('/payroll/save-deductions', 'Payroll\PayrollController@saveDeductionsToPayroll')->name('payroll.saveDeductions');
    Route::post('/payroll/save-reviews', 'Payroll\PayrollController@saveReviewsToPayroll')->name('payroll.saveReviews');
    Route::post('/payroll/save-summary', 'Payroll\PayrollController@saveSummaryToPayroll')->name('payroll.saveSummary');

    Route::post('payroll/fetch-time-attendance', 'Payroll\PayrollController@fetchTimeAttendance')->name('fetch.time.attendance');
    Route::post('payroll/fetch-service-charges', 'Payroll\PayrollController@fetchServiceCharge')->name('fetch.service.charge');
    Route::get('payroll/city-ladger/download-template', 'Payroll\PayrollController@downloadCityLadgerTemplate')->name('payroll.download-cityladger.template');
    Route::post('payroll/fetch-staff-shop', 'Payroll\PayrollController@fetchStaffShop')->name('payroll.fetch.staffshop');
    Route::post('payroll/fetch-pension-ewt', 'Payroll\PayrollController@calculatePensionAndEWT')->name('payroll.calculate.pensionandewt');
    Route::post('payroll/fetch-advanced-recovery', 'Payroll\PayrollController@fetchAdvanceRecovery')->name('fetch.advance.recovery');
    Route::post('/payroll/total-amount-from-review', 'Payroll\PayrollController@fetchTotalPayrollAmount')->name('fetch.totalPayroll.data');

    Route::get('/payroll/view/{payroll_id}', 'Payroll\PayrollController@viewPayroll')->name('payroll.view');
    Route::get('/payroll/data/{payroll_id}', 'Payroll\PayrollController@getPayrollData')->name('payroll.getData');

    Route::get('/payroll/view-payroll', 'Payroll\DashboardController@viewPayrollData')->name('payroll.view.all');
    Route::get('/payroll/data-payroll', 'Payroll\DashboardController@getPayroll')->name('payroll.data');

    Route::get('/payroll/columns/{payroll_id}', 'Payroll\PayrollController@getPayrollColumns')->name('payroll.getColumns');

    Route::get('/payroll/activity-log/{payroll_id}', 'Payroll\PayrollController@getActivityLog')->name('payroll.getActivityLog');
    Route::get('/payroll/{payroll_id}/activity-log', 'Payroll\PayrollController@showActivityLog')->name('payroll.activity-log');
    Route::get('/payroll/notes/{payroll_id}', 'Payroll\PayrollController@getNotes')->name('payroll.getNotes');
    Route::get('/payroll/download/{payroll_id}', 'Payroll\PayrollController@downloadPayroll')->name('payroll.download');

    Route::get('payroll/pension', 'Payroll\PensionController@index')->name('payroll.pension.index');
    Route::get('payroll/ewt', 'Payroll\EWTController@index')->name('payroll.ewt.index');
    Route::get('payroll/pension/former-employees', 'Payroll\PensionController@formerEmployees')->name('payroll.pension.former-employees');
    Route::get('payroll/get-EWT-Data/{year}', 'Payroll\EWTController@getEWTData')->name('payroll.ewt.getdata');
    Route::get('payroll/get-Pension-Data', 'Payroll\PensionController@getPensionData')->name('payroll.pension.getdata');
    Route::get('payroll/get-EWT-Data/former-employees/{year?}', 'Payroll\EWTController@getformerEmployeesEWTData')->name('payroll.ewt.getdata.former-employees');


    Route::get('payroll/payslip', 'Payroll\PayslipController@index')->name('payroll.payslip.index');
    Route::get('payroll/payslip/getEmployee', 'Payroll\PayslipController@getEmployees')->name('payslip.employee.list');

    Route::post('payroll/payslip/view', 'Payroll\PayslipController@viewPayslip')->name('payroll.payslip.view');
    Route::get('/payslip/show', 'Payroll\PayslipController@showPayslip')->name('payslip.show');
    Route::post('payroll/payslip/share', 'Payroll\PayslipController@sharePayslip')->name('payroll.payslip.share');
    Route::get('payroll/get-employee-details', 'Payroll\PayslipController@getEmployeeDetails')->name('employee.get.details');

    Route::get('/final-settlement', 'Payroll\PayslipController@finalsettlement')->name('payroll.final.settlement');
    Route::post('/final-settlement/store','Payroll\PayslipController@store')->name('final.settlement.store');
    Route::get('/final-settlement/review/{finalsettlementID}', 'Payroll\PayslipController@review')->name('final.settlement.review');
    Route::post('/final-settlement/submit', 'Payroll\PayslipController@submit')->name('final.settlement.submit');
    Route::get('/final-settlement/list', 'Payroll\PayslipController@settlementList')->name('final.settlement.list');
    Route::get('/final-settlement/getdata', 'Payroll\PayslipController@getSettlements')->name('final.settlement.getdata');

     Route::get('payroll/shopkeeper/payment-consent/{employee_id}','Payroll\PaymentConsentController@index')->name('payroll.payment.consent');
    Route::post('payroll/shopkeeper/payment-consent/confirm','Payroll\PaymentConsentController@confirmPurchased')->name('payroll.payment.consent-confirm');

    // payroll dashboard chart
    Route::get('/payroll/ot-trend-data', 'Payroll\DashboardController@getOtTrendData')->name('payroll.otTrendData');
    Route::get('/payroll/getPayrollExpenseChart', 'Payroll\DashboardController@getPayrollExpenses')->name('payroll.getExpenses');
    Route::get('/payroll/getPayrollComaprision', 'Payroll\DashboardController@getPayrollComparison')->name('payroll.getPayrollComparison');
    Route::get('/payroll/payroll-distribution','Payroll\DashboardController@getPayrollDistribution')->name('payroll.distribution');
    Route::get('/payroll/payroll-distribution-department','Payroll\DashboardController@getDepartmentDistribution')->name('payroll.departmentDistribution');
    Route::get('/payroll/get-pension-chartdata','Payroll\DashboardController@getMonthlyPensionData')->name('payroll.getMonthlyPensionData');
    Route::get('/payroll/bank-cash-sheet/download/{id}', 'Payroll\PayrollController@downloadBankAndCashSheets')->name('payroll.bankcashsheet.download');
    Route::get('/payroll/ewt-tax-bracket-chart', 'Payroll\DashboardController@getTaxBracketDistribution')->name('payroll.ewtBracketChart');

    //Payroll Module End


    // Perfromance Module Start
    Route::get('performance/configuration', 'Performance\ConfigurationController@index')->name('Performance.configuration');
    Route::get('performance/dashboard', 'Performance\PerformanceDashboardController@HR_Dashobard')->name('Performance.Hrdashboard');
    Route::get('performance/hod-dashboard', 'Performance\PerformanceDashboardController@Hod_dashboard')->name('Performance.HODdashboard');


    Route::post('performance/review-types', 'Performance\ConfigurationController@ReviewTypes')->name('Performance.config.ReviewTypes');
    Route::put('performance/review-types/inline-update/{id}', 'Performance\ConfigurationController@inlineReviewTypesUpdate')->name('Performance.config.ReviewTypes.inlineUpdate');
    Route::delete('performance/review-types/destroy/{id}', 'Performance\ConfigurationController@DestroyReviewTypes')->name('Performance.DestroyReviewTypes');

    // form Templete  Genrate
    Route::post('performance/template-form', 'Performance\ConfigurationController@PerformanceTemplateFormStore')->name('Performance.config.PerformanceTemplateFormStore');
    Route::get('performance/template-form/list', 'Performance\ConfigurationController@PerformanceTemplateFormList')->name('Performance.config.PerformanceTemplateFormList');
    Route::post('performance/template-form/update', 'Performance\ConfigurationController@PerformanceTemplateFormUpdate')->name('Performance.config.PerformanceTemplateFormUpdate');
    Route::get('performance//template-form/get/{id}', 'Performance\ConfigurationController@GetPerformanceTemplateForm')->name('Performance.config.GetPerformanceTemplateForm');
    Route::delete('performance/template-form/destroy/{id}', 'Performance\ConfigurationController@DestroyPerformanceTemplateForm')->name('Performance.DestroyPerformanceTemplateForm');
    Route::post('performance/schedule-meeting-email', 'Performance\ConfigurationController@ScheduleMeetingEmail')->name('Performance.config.ScheduleMeetingEmail');


    Route::post('performance/ninty-day-peformance-form/store', 'Performance\ConfigurationController@NintyDayPeformanceFormStore')->name('Performance.config.NintyDayPeformanceFormStore');
    Route::get('performance/ninty-day-peformance-form/list', 'Performance\ConfigurationController@NitnyPerformanceFormList')->name('Performance.config.NintyPerformanceFormList');
    Route::get('performance/ninty-day-peformance-form/get/{id}', 'Performance\ConfigurationController@GetNintyPerformanceForm')->name('Performance.config.GetNintyPerformanceForm');
    Route::post('performance/ninty-day-peformance-form/update', 'Performance\ConfigurationController@NintyDayPerformanceFormUpdate')->name('Performance.config.NintyDayPerformanceFormUpdate');
    Route::delete('performance/ninty-day-peformance-form/destroy/{id}', 'Performance\ConfigurationController@DestroyNintyDayPerformanceForm')->name('Performance.DestroyNintyDayPerformanceForm');

    Route::get('performance/professional-form/List', 'Performance\ConfigurationController@ProfessionalFormList')->name('Performance.config.ProfessionalFormList');
    Route::post('performance/professional-form/store', 'Performance\ConfigurationController@ProfessionalFormStore')->name('Performance.config.ProfessionalFormStore');
    Route::get('performance/professional-form/get/{id}', 'Performance\ConfigurationController@GetProfessionalForm')->name('Performance.config.GetProfessionalForm');
    Route::post('performance/professional-form/update', 'Performance\ConfigurationController@ProfessionalFormUpdate')->name('Performance.config.ProfessionalFormUpdate');
    Route::delete('performance/professional-form/destroy/{id}', 'Performance\ConfigurationController@DestroyProfessionalForm')->name('Performance.DestroyProfessionalForm');


    Route::get('performance/meeting', 'Performance\PerformanceMeetingController@index')->name('Performance.Meeting.index');
    Route::post('performance/schedule-meeting-employee', 'Performance\PerformanceMeetingController@SendMeetingLink')->name('Performance.Meeting.ScheduleMeetingEmp');
    Route::post('performance/get-performance-employee', 'Performance\PerformanceMeetingController@GetPerformanceEmp')->name('Performance.Meeting.GetPerformanceEmp');

    Route::get('performance/kpi/create', 'Performance\KpiController@create')->name('Performance.kpi.create');
    Route::post('performance/kpi/store', 'Performance\KpiController@PerformanceKpiStore')->name('Performance.kpi.store');
    Route::get('performance/kpi/list', 'Performance\KpiController@KpiList')->name('Performance.kpi.KpiList');

    Route::get('performance/cycle', 'Performance\CycleController@index')->name('Performance.cycle');
    Route::get('performance/create', 'Performance\CycleController@create')->name('Performance.create');

    Route::get('performance-cycle/fetch-employees', 'Performance\CycleController@CycleFetchEmployees')->name('Performance.cycle.FetchEmployees');
    Route::post('performance-cycle/fetch-template', 'Performance\CycleController@CycleFetchTemplate')->name('Performance.cycle.Template');
    Route::post('performance-cycle/store', 'Performance\CycleController@CycleStore')->name('Performance.cycle.store');
    Route::delete('performance-cycle/destroy/{id}', 'Performance\CycleController@Destroy')->name('Performance.cycle.destory');

    Route::get('performance/monthly-check-in', 'Performance\MonthlyCheckingController@index')->name('Performance.MonltyCheckIn');
    Route::get('performance/monthly-check-in/create', 'Performance\MonthlyCheckingController@create')->name('Performance.CreateMonltyCheckIn');
    Route::post('performance/monthly-check-in/store', 'Performance\MonthlyCheckingController@MonltyCheckInStore')->name('Performance.MonltyCheckInStore');
    Route::get('performance/monthly-check-in/get-employee-details', 'Performance\MonthlyCheckingController@GetEmployeeDetails')->name('Performance.GetEmployeeDetailsMonthlyChecking');
    Route::get('performance/monthly-check-in/details/{id}', 'Performance\MonthlyCheckingController@GetMonthlyCheckInDetails')->name('Performance.GetMonthlyCheckInDetails');
    Route::get('performance/monthly-check-in/details-page-list', 'Performance\MonthlyCheckingController@MonltyCheckInDetailsPageList')->name('Performance.MonltyCheckInDetailsPageList');



    // Perfromance Module End


    // People and Relation (Disiciplinery)
    Route::get('grievance-and-disciplinary/configuration', 'GrievanceAndDisciplinery\ConfigurationController@index')->name('GrievanceAndDisciplinery.config.index');

    Route::get('grievance-and-disciplinary', 'GrievanceAndDisciplinery\ConfigurationController@IndexDisciplineryCategory')->name('GrievanceAndDisciplinery.config.Disciplineryindex');
    Route::post('grievance-and-disciplinary/disciplinery-category/store', 'GrievanceAndDisciplinery\ConfigurationController@StoreDisciplineryCategory')->name('GrievanceAndDisciplinery.config.Disciplinerystore');
    Route::put('grievance-and-disciplinary/disciplinery-category/inline-update/{id}', 'GrievanceAndDisciplinery\ConfigurationController@DisciplineryCategoryinlineUpdate')->name('GrievanceAndDisciplinery.config.DisciplineryCategoryinlineUpdate');
    Route::delete('grievance-and-disciplinary/disciplinery-category/destroy/{id}', 'GrievanceAndDisciplinery\ConfigurationController@DisciplineryCategoryDestory')->name('GrievanceAndDisciplinery.config.DisciplineryCategoryDestory');

    Route::get('grievance-and-disciplinary/offenses', 'GrievanceAndDisciplinery\ConfigurationController@IndexOffenses')->name('GrievanceAndDisciplinery.config.Offensesindex');
    Route::post('grievance-and-disciplinary/offenses/add', 'GrievanceAndDisciplinery\ConfigurationController@StoreOffenses')->name('GrievanceAndDisciplinery.config.StoreOffenses');
    Route::put('grievance-and-disciplinary/offenses/inline-update/{id}', 'GrievanceAndDisciplinery\ConfigurationController@OffensesinlineUpdate')->name('GrievanceAndDisciplinery.config.OffensesinlineUpdate');
    Route::delete('grievance-and-disciplinary/offenses/destroy/{id}', 'GrievanceAndDisciplinery\ConfigurationController@OffensesDestory')->name('GrievanceAndDisciplinery.config.OffensesDestory');

    Route::post('grievance-and-disciplinary/offenses/get-categories', 'GrievanceAndDisciplinery\ConfigurationController@GetCategoryOffenses')->name('GrievanceAndDisciplinery.config.GetCategoryOffenses');

    Route::post('grievance-and-disciplinary/action/store', 'GrievanceAndDisciplinery\ConfigurationController@ActionStore')->name('GrievanceAndDisciplinery.config.ActionStore');
    Route::get('grievance-and-disciplinary/action/index', 'GrievanceAndDisciplinery\ConfigurationController@IndexAction')->name('GrievanceAndDisciplinery.config.IndexAction');
    Route::put('grievance-and-disciplinary/action/inline-update/{id}', 'GrievanceAndDisciplinery\ConfigurationController@ActioninlineUpdate')->name('GrievanceAndDisciplinery.config.ActioninlineUpdate');
    Route::delete('grievance-and-disciplinary/action/destroy/{id}', 'GrievanceAndDisciplinery\ConfigurationController@ActionDestory')->name('GrievanceAndDisciplinery.config.ActionDestory');

    Route::get('grievance-and-disciplinary/severity/index', 'GrievanceAndDisciplinery\ConfigurationController@IndexSeverity')->name('GrievanceAndDisciplinery.config.IndexSeverity');
    Route::post('grievance-and-disciplinary/severity/store', 'GrievanceAndDisciplinery\ConfigurationController@SeverityStore')->name('GrievanceAndDisciplinery.config.SeverityStore');
    Route::put('grievance-and-disciplinary/severity/inline-update/{id}', 'GrievanceAndDisciplinery\ConfigurationController@SeverityInlineUpdate')->name('GrievanceAndDisciplinery.config.SeverityinlineUpdate');
    Route::delete('grievance-and-disciplinary/severity/destroy/{id}', 'GrievanceAndDisciplinery\ConfigurationController@SeverityDestory')->name('GrievanceAndDisciplinery.config.SeverityDestory');

    Route::post('grievance-and-disciplinary/code-of-conduct', 'GrievanceAndDisciplinery\ConfigurationController@CodeOfCounduct')->name('GrievanceAndDisciplinery.config.CodeOfCounduct');
    Route::get('grievance-and-disciplinary/code-of-conduct/index', 'GrievanceAndDisciplinery\ConfigurationController@IndexCodeOfCounduct')->name('GrievanceAndDisciplinery.config.IndexCodeOfCounduct');
    Route::post('grievance-and-disciplinary/code-of-conduct/update', 'GrievanceAndDisciplinery\ConfigurationController@CodeOfCounductUpdate')->name('GrievanceAndDisciplinery.config.CodeOfCounductUpdate');
    Route::delete('grievance-and-disciplinary/code-of-conduct/destroy/{id}', 'GrievanceAndDisciplinery\ConfigurationController@CodeOfConductDestory')->name('GrievanceAndDisciplinery.config.CodeOfConductDestory');
    Route::post('grievance-and-disciplinary/discriplinery-approval-role', 'GrievanceAndDisciplinery\ConfigurationController@DiscriplineryApprovalRole')->name('GrievanceAndDisciplinery.config.DiscriplineryApprovalRole');

    Route::post('grievance-and-disciplinary/latter-templete/store', 'GrievanceAndDisciplinery\ConfigurationController@LatterTemletestore')->name('GrievanceAndDisciplinery.config.LatterTemletestore');
    Route::get('grievance-and-disciplinary/latter-templete/index', 'GrievanceAndDisciplinery\ConfigurationController@IndexLatterTemplete')->name('GrievanceAndDisciplinery.config.IndexLatterTemplete');
    Route::get('grievance-and-disciplinary/latter-templete/edit/{id}', 'GrievanceAndDisciplinery\ConfigurationController@LatterTempleteEdit')->name('GrievanceAndDisciplinery.config.LatterTempleteEdit');
    Route::delete('grievance-and-disciplinary/latter-templete/destroy/{id}', 'GrievanceAndDisciplinery\ConfigurationController@LatterTempleteDestory')->name('GrievanceAndDisciplinery.config.LatterTempleteDestory');

    Route::post('grievance-and-disciplinary/disciplinary-committees', 'GrievanceAndDisciplinery\ConfigurationController@DisciplinaryCommittees')->name('GrievanceAndDisciplinery.config.DisciplinaryCommittees');
    Route::get('grievance-and-disciplinary/disciplinary-committees/index', 'GrievanceAndDisciplinery\ConfigurationController@IndexDisciplinaryCommittees')->name('GrievanceAndDisciplinery.config.IndexDisciplinaryCommittees');
    Route::put('grievance-and-disciplinary/disciplinary-committees/update/{id}', 'GrievanceAndDisciplinery\ConfigurationController@CommitteeinlineUpdate')->name('GrievanceAndDisciplinery.config.CommitteeinlineUpdate');
    Route::delete('grievance-and-disciplinary/disciplinary-committees/destroy/{id}', 'GrievanceAndDisciplinery\ConfigurationController@DisciplinaryCommitteesDestory')->name('GrievanceAndDisciplinery.config.DisciplinaryCommitteesDestory');
    Route::post('grievance-and-disciplinary/investing-hearing-templete/store', 'GrievanceAndDisciplinery\ConfigurationController@InvestingHearingTempleteStore')->name('GrievanceAndDisciplinery.config.InvestingHearingTempleteStore');


    Route::get('grievance-and-disciplinary/code-of-conduct', 'GrievanceAndDisciplinery\ConfigurationController@CodeOfConduct')->name('GrievanceAndDisciplinery.config.CodeOfConduct');

    Route::post('grievance-and-disciplinary/delegation-rules/store', 'GrievanceAndDisciplinery\ConfigurationController@DelegationRulesStore')->name('GrievanceAndDisciplinery.config.DelegationRulesStore');
    Route::get('grievance-and-disciplinary/index-disciplinary-delegation-rule', 'GrievanceAndDisciplinery\ConfigurationController@IndexDisciplinaryDelegationRule')->name('GrievanceAndDisciplinery.config.IndexDisciplinaryDelegationRule');
    Route::put('grievance-and-disciplinary/disciplinery-delegation-rule/inline-update/{id}', 'GrievanceAndDisciplinery\ConfigurationController@DisciplineryDeletgationRuleinlineUpdate')->name('GrievanceAndDisciplinery.config.DisciplineryDeletgationRuleinlineUpdate');
    Route::delete('grievance-and-disciplinary/disciplinery-delegation-rule/destroy/{id}', 'GrievanceAndDisciplinery\ConfigurationController@DisciplineryDeletegationRuleDestory')->name('GrievanceAndDisciplinery.config.DisciplineryDeletegationRuleDestory');

    Route::post('grievance-and-disciplinary/disciplinery-appeal-type-wise-data', 'GrievanceAndDisciplinery\ConfigurationController@DisciplineryAppealTypeWiseData')->name('GrievanceAndDisciplinery.config.DisciplineryAppealTypeWiseData');
    Route::post('grievance-and-disciplinary/disciplinary-appeal/store', 'GrievanceAndDisciplinery\ConfigurationController@DisciplinaryAppealStore')->name('GrievanceAndDisciplinery.config.DisciplinaryAppealStore');
    Route::post('grievance-and-disciplinary/right-to-be-accompanied', 'GrievanceAndDisciplinery\ConfigurationController@RightToBeAccompanied')->name('GrievanceAndDisciplinery.config.RightToBeAccompanied');

    Route::get('grievance-and-disciplinary/index-grievance-category', 'GrievanceAndDisciplinery\ConfigurationController@IndexGrievanceCategory')->name('GrievanceAndDisciplinery.config.IndexGrievanceCategory');
    Route::post('grievance-and-disciplinary/category-grievance/store', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceCategoryStore')->name('GrievanceAndDisciplinery.config.GrievanceCategoryStore');
    Route::put('grievance-and-disciplinary/category-grievance/inline-update/{id}', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceCategorinlineUpdate')->name('GrievanceAndDisciplinery.config.GrievanceCategorinlineUpdate');
    Route::delete('grievance-and-disciplinary/category-grievance/destroy/{id}', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceCategoryDestory')->name('GrievanceAndDisciplinery.config.GrievanceCategoryDestory');

    Route::post('grievance-and-disciplinary/grievance-sub-category/store', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceSubCategoryStore')->name('GrievanceAndDisciplinery.config.GrievanceSubCategoryStore');
    Route::get('grievance-and-disciplinary/grievance-sub-category/index', 'GrievanceAndDisciplinery\ConfigurationController@IndexGrievanceSubCategory')->name('GrievanceAndDisciplinery.config.IndexGrievanceSubCategory');
    Route::put('grievance-and-disciplinary/grievance-sub-category/inline-update/{id}', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceSubCategorinlineUpdate')->name('GrievanceAndDisciplinery.config.GrievanceSubCategorinlineUpdate');
    Route::delete('grievance-and-disciplinary/grievance-sub-category/destroy/{id}', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceSubCategoryDestory')->name('GrievanceAndDisciplinery.config.GrievanceSubCategoryDestory');

    Route::post('grievance-and-disciplinary/grievance-category/GrievanceCategoryWiseSubCategoryData', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceCategoryWiseSubCategoryData')->name('GrievanceAndDisciplinery.config.GrievanceCategoryWiseSubCategoryData');
    Route::post('grievance-and-disciplinary/grievance-category/store', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceCatAndSubCategoryStore')->name('GrievanceAndDisciplinery.config.GrievanceCatAndSubCategoryStore');
    Route::get('grievance-and-disciplinary/grievance-category/index', 'GrievanceAndDisciplinery\ConfigurationController@IndexGrievanceCatAndSubCategory')->name('GrievanceAndDisciplinery.config.IndexGrievanceCatAndSubCategory');
    Route::put('grievance-and-disciplinary/grievance-category/inline-update/{id}', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceCatAndSubCategoryinlineUpdate')->name('GrievanceAndDisciplinery.config.GrievanceCatAndSubCategoryinlineUpdate');
    Route::delete('grievance-and-disciplinary/grievance-category/destroy/{id}', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceCatAndSubCategoryDestory')->name('GrievanceAndDisciplinery.config.GrievanceCatAndSubCategoryDestory');

   Route::post('grievance-and-disciplinary/grievance-delegation-rule-store',  'GrievanceAndDisciplinery\ConfigurationController@GrievanceDelegationRuleStore')->name('GrievanceAndDisciplinery.config.GrieDelegationRuleStore');
    Route::get('grievance-and-disciplinary/index-grievance-delegation-rule', 'GrievanceAndDisciplinery\ConfigurationController@IndexGrievanceDelegationRule')->name('GrievanceAndDisciplinery.config.IndexGrievanceDelegationRule');
    Route::put('grievance-and-disciplinary/grievance-delegation-rule-inline-update/{id}','GrievanceAndDisciplinery\ConfigurationController@GrievanceDelegeationRuleinlineUpdate')->name('GrievanceAndDisciplinery.config.GrievanceDelegeationRuleinlineUpdate');
    Route::delete('grievance-and-disciplinary/grievance-delegation-rule-destroy/{id}', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceDelegeationRuleDestory')->name('GrievanceAndDisciplinery.config.GrievanceDelegeationRuleDestory');
    Route::post('grievance-and-disciplinary/grievance-right-to-be-accompanied', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceRightToBeAccompanied')->name('GrievanceAndDisciplinery.config.GrievanceRightToBeAccompanied');

    Route::post('grievance-and-disciplinary/grievance-non-retaliation', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceNonRetaliation')->name('GrievanceAndDisciplinery.config.GrievanceNonRetaliation');
    Route::post('grievance-and-disciplinary/grievance-appeal-store', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceAppealStore')->name('GrievanceAndDisciplinery.config.GrievanceAppealStore');
    Route::post('grievance-and-disciplinary/grievance-template-store', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceTempleteStore')->name('GrievanceAndDisciplinery.config.GrievanceTempleteStore');
    Route::get('grievance-and-disciplinary/index-grievance-template', 'GrievanceAndDisciplinery\ConfigurationController@IndexGrievanceTemplete')->name('GrievanceAndDisciplinery.config.IndexGrievanceTemplete');
    Route::post('grievance-and-disciplinary/grievance-template-edit', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceTempleteEdit')->name('GrievanceAndDisciplinery.config.GrievanceTempleteEdit');
    Route::delete('grievance-and-disciplinary/grievance-template-destroy/{id}', 'GrievanceAndDisciplinery\ConfigurationController@GrivanceTempleteDestory')->name('GrievanceAndDisciplinery.config.grivanceTempleteDestory');

     Route::post('grievance-and-disciplinary/grievance-resolution-timeline-store', 'GrievanceAndDisciplinery\ConfigurationController@GrivanceResoultionTimeLineStore')->name('GrievanceAndDisciplinery.config.GrivanceResoultionTimeLineStore');
    Route::post('grievance-and-disciplinary/grievance-escalation-store', 'GrievanceAndDisciplinery\ConfigurationController@GrivanceEscaltionStore')->name('GrievanceAndDisciplinery.config.GrivanceEscaltionStore');
    Route::get('grievance-and-disciplinary/index-grievance-escalation', 'GrievanceAndDisciplinery\ConfigurationController@IndexGrievanceEscaltion')->name('GrievanceAndDisciplinery.config.IndexGrievanceEscaltion');
    Route::put('grievance-and-disciplinary/grievance-escalation-inline-update/{id}', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceEscalationinlineUpdate')->name('GrievanceAndDisciplinery.config.GrievanceEscalationinlineUpdate');
    Route::delete('grievance-and-disciplinary/grievance-escalation-destroy/{id}', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceEscalationDestory')->name('GrievanceAndDisciplinery.config.GrievanceEscalationDestory');


Route::post('grievance-and-disciplinary/grievance-committee-store', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceCommitteeStore')->name('GrievanceAndDisciplinery.config.GrievanceCommitteeStore');
    Route::get('grievance-and-disciplinary/grievance-committee-index', 'GrievanceAndDisciplinery\ConfigurationController@GrivanceCommitteeIndex')->name('GrievanceAndDisciplinery.config.GrivanceCommitteeIndex');
    Route::put('grievance-and-disciplinary/grievance-committee-inline-update/{id}', 'GrievanceAndDisciplinery\ConfigurationController@GrivanceCommitteeinlineUpdate')->name('GrievanceAndDisciplinery.config.GrivanceCommitteeinlineUpdate');
    Route::delete('grievance-and-disciplinary/grievance-committees-destroy/{id}', 'GrievanceAndDisciplinery\GrivanceController@GrivevanceCommitteesDestory')->name('GrievanceAndDisciplinery.config.GrivevanceCommitteesDestory');



    Route::get('grievance-and-disciplinary/grievance-index', 'GrievanceAndDisciplinery\GrivanceController@GrivanceIndex')->name('GrievanceAndDisciplinery.grivance.GrivanceIndex');
    Route::get('grievance-and-disciplinary/create-grievance', 'GrievanceAndDisciplinery\GrivanceController@CreateGrivance')->name('GrievanceAndDisciplinery.grivance.CreateGrivance');
    Route::post('grievance-and-disciplinary/grievance-submit-store', 'GrievanceAndDisciplinery\GrivanceController@GrievanceSubmiteStore')->name('GrievanceAndDisciplinery.grivance.GrievanceSubmiteStore');
    Route::get('grievance-and-disciplinary/get-employee-details', 'GrievanceAndDisciplinery\GrivanceController@GetEmployeeDetails')->name('GrievanceAndDisciplinery.grivance.GetEmployeeDetails');
    Route::get('grievance-and-disciplinary/investigation/{id}', 'GrievanceAndDisciplinery\GrivanceController@InvestigationReport')->name('GrievanceAndDisciplinery.config.Investigation');
    Route::post('grievance-and-disciplinary/investigation-report-store', 'GrievanceAndDisciplinery\GrivanceController@InvestigationReportStore')->name('GrievanceAndDisciplinery.grivance.InvestigationReportStore');
    Route::delete('grievance-and-disciplinary/grievance-destroy/{id}', 'GrievanceAndDisciplinery\GrivanceController@GrivnanceDestory')->name('GrievanceAndDisciplinery.grivance.DeleteGrivance');

    Route::post('grievance-and-disciplinary/request-identity', 'GrievanceAndDisciplinery\GrivanceController@RequestIdentity')->name('GrievanceAndDisciplinery.grivance.RequestIdentity');
    Route::get('grievance-and-disciplinary/investigation-info/{id}', 'GrievanceAndDisciplinery\GrivanceController@Investigationinfo')->name('GrievanceAndDisciplinery.config.Investigationinfo');


    Route::post('grievance-and-disciplinary/request-for-statement', 'GrievanceAndDisciplinery\GrivanceController@RequestForStatement')->name('GrievanceAndDisciplinery.grivance.RequestForStatement');
    Route::get('grievance-and-disciplinary/get-grivance-sub-cat', 'GrievanceAndDisciplinery\GrivanceController@GetGrivanceSubCat')->name('GrievanceAndDisciplinery.grivance.GetGrivanceSubCat');
    Route::delete('grievance-and-disciplinary/get-grivance/{id}', 'GrievanceAndDisciplinery\GrivanceController@GrivnanceDestory')->name('GrievanceAndDisciplinery.grivance.DeleteGrivance');


    Route::post('mark/as-read', 'SitesettignsController@NotificationMark')->name('resort.Mark.Notification');
    Route::get('mark/notification-list', 'SitesettignsController@NotificationList')->name('resort.Mark.NotificationList');
    Route::delete('notification/notification-destroy/{id}', 'SitesettignsController@NotificationDestory')->name('resort.Mark.NotificationDestory');


    Route::get('grievance-and-disciplinary/disciplinary-index', 'GrievanceAndDisciplinery\DisciplinaryController@DisciplinaryIndex')->name('GrievanceAndDisciplinery.Disciplinary.DisciplinaryIndex');
    Route::get('grievance-and-disciplinary/create-disciplinary', 'GrievanceAndDisciplinery\DisciplinaryController@CreateDisciplinary')->name('GrievanceAndDisciplinery.Disciplinary.CreateDisciplinary');
    Route::post('grievance-and-disciplinary/get-category-wise-offence', 'GrievanceAndDisciplinery\DisciplinaryController@GetCategoryWiseOffence')->name('GrievanceAndDisciplinery.Disciplinary.GetCategoryWiseOffence');
    Route::post('grievance-and-disciplinary/key-personnel', 'GrievanceAndDisciplinery\ConfigurationController@KeyPersonnel')->name('GrievanceAndDisciplinery.config.KeyPersonnel');

    // Disciplinary Module start

      Route::post('grievance-and-disciplinary/disciplinary/store', 'GrievanceAndDisciplinery\DisciplinaryController@StoreDisciplinary')->name('GrievanceAndDisciplinery.Disciplinary.StoreDisciplinary');
      Route::get('grievance-and-disciplinary/disciplinery/investigation/{id}', 'GrievanceAndDisciplinery\DisciplinaryController@DisciplineryInvestigation')->name('GrievanceAndDisciplinery.Disciplinary.Investigation');
      Route::get('grievance-and-disciplinary/disciplinery/open-offence', 'GrievanceAndDisciplinery\DisciplinaryController@DisciplineryOpenOffence')->name('GrievanceAndDisciplinery.DisciplineryOpenOffence');
      Route::post('grievance-and-disciplinary/disciplinery/investigation-report/store', 'GrievanceAndDisciplinery\DisciplinaryController@InvestigationReportStore')->name('GrievanceAndDisciplinery.Disciplinary.InvestigationReportStore');
      Route::post('grievance-and-disciplinary/disciplinery/requestForStatement', 'GrievanceAndDisciplinery\DisciplinaryController@RequestForStatement')->name('GrievanceAndDisciplinery.Disciplinary.RequestForStatement');


      Route::post('grievance-and-disciplinary/disciplinary/email-template', 'GrievanceAndDisciplinery\ConfigurationController@DisciplineryEmailTamplate')->name('GrievanceAndDisciplinery.Disciplinary.DisciplineryEmailTamplate');
      Route::get('grievance-and-disciplinary/disciplinary/email-template/index', 'GrievanceAndDisciplinery\ConfigurationController@DisciplineryEmailTamplateIndex')->name('GrievanceAndDisciplinery.Disciplinery.EmailTamplateIndex');
      Route::post('grievance-and-disciplinary/disciplinary/email-template/get', 'GrievanceAndDisciplinery\ConfigurationController@GetEmailTamplate')->name('GrievanceAndDisciplinery.Disciplinary.GetEmailTamplate');
      Route::get('grievance-and-disciplinary/disciplinary/history-and-logs', 'GrievanceAndDisciplinery\GrivanceController@HistoryAndLogs')->name('GrievanceAndDisciplinery.Disciplinary.HistoryAndLogs');



    //Laerning Module
    Route::get('learning/configuration', 'Learning\ConfigurationController@index')->name('learning.configration');
    Route::post('learning/categories/store', 'Learning\ConfigurationController@saveCategories')->name('learning.categories.save');
    Route::get('learning/categories/list', 'Learning\ConfigurationController@listCategories')->name('learning.categories.list');
    Route::put('learning/category/inline-update/{id}', 'Learning\ConfigurationController@inlineCategoryUpdate')->name('learning.category.inlineUpdatecategory');
    Route::delete('learning/category/destroy/{id}', 'Learning\ConfigurationController@destroyCategory')->name('learning.category.destroy');

    Route::post('learning/program/store', 'Learning\LearningProgramController@save')->name('learning.programs.save');
    Route::get('learning/program/index', 'Learning\LearningProgramController@index')->name('learning.programs.index');
    Route::get('learning/program/list', 'Learning\LearningProgramController@list')->name('learning.programs.list');

    Route::get('learning/program/calendar', 'Learning\LearningCalendarController@index')->name('learning.calendar.index');
    Route::post('learning/program/calendar/add-session', 'Learning\LearningCalendarController@store')->name('learning.calendar.save');
    Route::get('/learning/program/details',  'Learning\LearningProgramController@getProgramDetails')->name('learning.program.details');
    Route::get('learning/get-sessions', 'Learning\LearningCalendarController@getSessions')->name('get.learning.sessions');

    Route::post('learning/mandatory-program/save', 'Learning\ConfigurationController@save_mandatory_program')->name('mandatory.learning.save');
    Route::get('/get-mandatory-positions', 'Learning\ConfigurationController@getMandatoryPositions')->name('get.mandatory.positions');

    Route::get('learning/mandatory-program/get', 'Learning\ConfigurationController@get_mandatory_program')->name('mandatory.learning.get');
    Route::get('learning/mandatory-program/list', 'Learning\ConfigurationController@list_mandatory_program')->name('learning.mandatory-programs.list');

    Route::get('learning/probationary-program/get', 'Learning\ConfigurationController@get_probationary_program')->name('probationary.learning.get');
    Route::get('learning/probationary-program/list', 'Learning\ConfigurationController@list_probationary_program')->name('learning.probationary-programs.list');
    Route::post('learning/probationary-program/save', 'Learning\ConfigurationController@save_probationary_program')->name('probationary.learning.save');

    Route::get('learning/request/add', 'Learning\LearningController@index')->name('learning.request.add');
    Route::post('learning/request/store', 'Learning\LearningController@store')->name('learning.request.store');
    Route::get('learning/request', 'Learning\LearningController@request')->name('learning.request.index');
    Route::get('learning/request/list', 'Learning\LearningController@list')->name('learning.request.list');
    Route::get('learning/request/details/{id}', 'Learning\DashboardController@details')->name('learning.request.details');

    Route::post('/learning/request/update-status', 'Learning\LearningController@updateStatus')->name('learning.request.updateStatus');

    Route::get('learning/schedule', 'Learning\TrainingScheduleController@schedule')->name('learning.schedule');
    Route::post('learning/schedule', 'Learning\TrainingScheduleController@store')->name('learning.schedule.store');
    Route::get('learning/schedule/index', 'Learning\TrainingScheduleController@index')->name('learning.schedule.index');
    Route::get('learning/schedule/list', 'Learning\TrainingScheduleController@list')->name('learning.schedule.list');

    Route::post('learning/employees/get-dept-wise','Learning\TrainingScheduleController@getEmployeesDeptwise')->name('get.employees.deptwise');
    Route::post('learning/schedule/inline-update','Learning\TrainingScheduleController@inlineUpdate')->name('learning.schedule.update');

    Route::get('learning/schedule/attendance', 'Learning\AttendanceController@index')->name('learning.schedule.attendance');
    Route::get('learning/schedule/attendance/list', 'Learning\AttendanceController@list')->name('learning.schedule.attendance.list');

   Route::post('learning/schedule/attendance/mark', 'Learning\AttendanceController@markAttendanceBulk')->name('attendance.mark');
   Route::get('learning/schedule/attendance/history/{employee_id}', 'Learning\AttendanceController@attendanceHistoryPage')->name('attendance.history.page');
   Route::get('learning/schedule/attendance/history/data/{employee_id}', 'Learning\AttendanceController@getAttendanceHistoryData')->name('attendance.history.data');
   Route::post('learning/schedule/attendance/save', 'Learning\AttendanceController@saveAttendance')->name('attendance.save');

      Route::post('learning/evaluation-reminder/store', 'Learning\ConfigurationController@saveEvaluationReminder')->name('learning.evaluation-reminder.save');

   Route::post('learning/attendance-parameters/store', 'Learning\ConfigurationController@saveAttendanceParameters')->name('learning.attendance-parameters.save');
    Route::get('learning/attendance/chart-data', 'Learning\AttendanceController@getAttendanceChartData')->name('learning.attendance.chart-data');
    Route::get('learning/attendance/participation-chart-data', 'Learning\AttendanceController@getTrainingParticipationData')->name('learning.participation.chart-data');
    Route::get('learning/get-all-absentees', 'Learning\DashboardController@getAbsentees')->name('learning.absentees.getall');
    Route::get('learning/get-all-absentees-data', 'Learning\DashboardController@getAllAbsenteesData')->name('learning.absentees.getdata');

    Route::get('learning/training-history', 'Learning\TrainingScheduleController@history')->name('training.history');


    //Feedback form builder
    Route::get('/feedback-form/create', 'Learning\FeedbackFormController@create')->name('feedback-form.create');
    Route::post('/feedback-form/store', 'Learning\FeedbackFormController@store')->name('feedback-form.store');
    Route::get('/feedback-form', 'Learning\FeedbackFormController@index')->name('feedback-form.index');
    Route::get('/feedback-form/list', 'Learning\FeedbackFormController@list')->name('feedback-form.list');
    Route::get('/feedback-form/edit/{id}', 'Learning\FeedbackFormController@edit')->name('feedback-form.edit');
    Route::post('/feedback-form/update/{id}', 'Learning\FeedbackFormController@update')->name('feedback-form.update');
    Route::delete('/feedback-form/delete/{id}', 'Learning\FeedbackFormController@delete')->name('feedback-form.delete');

    Route::get('/feedback-form/{position_id}/{applicant_id}','Learning\FeedbackFormController@show')->name('feedback-form.show');
    Route::post('/feedback-form/{id}/response', 'Learning\FeedbackFormController@saveResponse')->name('feedback-form.saveResponse');
    Route::get('/feedback-form/view/{formId}/{responseId}', 'Learning\FeedbackFormController@viewResponse')->name('feedback-form.viewResponse');

    Route::get('/evaluation-form/create', 'Learning\EvaluationFormController@create')->name('evaluation-form.create');
    Route::post('/evaluation-form/store', 'Learning\EvaluationFormController@store')->name('evaluation-form.store');
    Route::get('/evaluation-form', 'Learning\EvaluationFormController@index')->name('evaluation-form.index');
    Route::get('/evaluation-form/list', 'Learning\EvaluationFormController@list')->name('evaluation-form.list');
    Route::get('/evaluation-form/edit/{id}', 'Learning\EvaluationFormController@edit')->name('evaluation-form.edit');
    Route::post('/evaluation-form/update/{id}', 'Learning\EvaluationFormController@update')->name('evaluation-form.update');
    Route::delete('/evaluation-form/delete/{id}', 'Learning\EvaluationFormController@delete')->name('evaluation-form.delete');

    //  Survey Module
    Route::get('survey/schedule', 'Survey\SurveyController@index')->name('Survey.index');
    Route::get('survey/create', 'Survey\SurveyController@index')->name('Survey.create');
    Route::post('/survey/store', 'Survey\SurveyController@SaveSurvey')->name('Survey.store');
    Route::get('/survey/view/{id}', 'Survey\SurveyController@SurveyView')->name('Survey.view');
    Route::get('survey/list', 'Survey\SurveyController@Surveylist')->name('Survey.Surveylist');
    Route::delete('survey/destroy/{id}', 'Survey\SurveyController@SurveyDestory')->name('Survey.Destory');
    Route::post('/survey/change-status', 'Survey\SurveyController@changeStatus')->name('Survey.changeStatus');
    Route::get('/survey/total-applicant/{id}', 'Survey\SurveyController@TotalApplicant')->name('Survey.TotalApplicant');

    Route::get('survey/complete-survey', 'Survey\SurveyController@CompleteSurvey')->name('Survey.CompleteSurvey');
    Route::get('survey/darft-survey', 'Survey\SurveyController@DarftSurvey')->name('Survey.DarftSurvey');

    Route::post('survey/notify-to-participants', 'Survey\SurveyController@NotifyToParticipants')->name('Survey.notifyToParticipants');
    Route::get('survey/get/pending-participants', 'Survey\SurveyController@GetPendingParticipants')->name('Survey.getPendingParticipants');

    Route::get('survey/get/near-to-deadline-survey', 'Survey\SurveyController@Getneartodeadlinesurvey')->name('Survey.Getneartodeadlinesurvey');
    Route::get('survey/raise-ticket', 'Support\SupportController@raiseTicket')->name('support.raise.ticket');
    Route::get('/survey/get/survey-results/{id}', 'Survey\SurveyController@GetSurveyResults')->name('Survey.GetSurveyResults');
    Route::get('survey/result-export', 'Survey\SurveyController@SurveyReultExport')->name('Survey.SurveyReultExport');
    Route::get('survey/download-question-ans/{id}', 'Survey\SurveyController@DownloadQuestionAndAns')->name('Survey.DownloadQuestionAndAns');

      //Support Module
      Route::get('support/index', 'Support\SupportController@index')->name('support.index');
      Route::get('support/get-data', 'Support\SupportController@getSupportData')->name('support.get.data');
      Route::post('support/store', 'Support\SupportController@store')->name('support.store');

      Route::get('support/chat/{support_id}', 'Support\SupportChatController@index')->name('support.chat.system');
      Route::post('support/chat/send-message', 'Support\SupportChatController@sendMessage')->name('support.chat.sendMessage');
      Route::get('support/chat/messages/{support_id}', 'Support\SupportChatController@fetchMessages')->name('support.chat.fetchMessage');

      Route::get('/support/view/{support_id}', 'Support\SupportController@view')->name('resort.supports.view');
      Route::get('support/support-email-replyPage/{support_id}', 'Support\SupportController@replyEMail')->name('resort.email.replypage');

      Route::post('support/support-email-reply', 'Support\SupportController@sendReply')->name('resort.support-email.reply');


      //   File management
      Route::post('file-manage/create-folder', 'FileManagment\FileManageController@CreateFolder')->name('FileManage.CreateFolder');
      Route::get('file-manage/uncategorized-documents', 'FileManagment\FileManageController@UnCategoriesDocuments')->name('Categories.Documents');
      Route::get('file-manage/employees-folder',  'FileManagment\FileManageController@EmployeesFolderMangement')->name('Employees.Documents');
      Route::get('file-manage/get-folder', 'FileManagment\FileManageController@GetFolder')->name('FileManage.GetFolder');
      Route::post('file-manage/store-folder-files', 'FileManagment\FileManageController@StoreFolderFiles')->name('FileManage.StoreFolderFiles');
      Route::post( 'file-manage/folder-list', 'FileManagment\FileManageController@FolderList')->name('FileManage.FolderList');

      Route::post('file-manage/get/folder-files', 'FileManagment\FileManageController@GetFolderFiles')->name('FileManage.GetFolderFiles');
      Route::post('file-manage/rename-file', 'FileManagment\FileManageController@RenameFile')->name('FileManage.RenameFile');
      Route::post('file-manage/show-the-folder-wise-data', 'FileManagment\FileManageController@ShowthefolderWiseData')->name('FileManage.ShowthefolderWiseData');
      Route::get('file-manage/permission', 'FileManagment\FilePermissionController@index')->name('FileManage.Permission');
      Route::post('file-manage/get/permission-file', 'FileManagment\FilePermissionController@GetPermissionfile')->name('FileManage.GetPermissionfile');
      Route::post('file-manage/file-permission/store', 'FileManagment\FilePermissionController@StoreFilePermission')->name('FileManage.StoreFilePermission');
      Route::post('file-manage/file-permission/search', 'FileManagment\FilePermissionController@SearchPermissionfile')->name('FileManage.SearchPermissionfile');
      Route::post('file-manage/employee-folder/create', 'FileManagment\FileManageController@CreateEmployeeFolder')->name('FileManage.CreateEmployeeFolder');
      Route::post('file-manage/move-folder', 'FileManagment\FileManageController@MoveFolder')->name('FileManage.MoveFolder');
      Route::post('file-manage/advance-search', 'FileManagment\FileManageController@AdvanceSearch')->name('FileManage.AdvanceSearch');
      Route::get('file-manage/get-uncategorized-doc', 'FileManagment\DashboardController@GetUncategorizedDoc')->name('FileManage.GetUncategorizedDoc');
      Route::post('file-manage/auditlog', 'FileManagment\FileManageController@Auditlog')->name('FileManage.Auditlog');

      Route::get('file-manage/audit-logs/dashboard-list', 'FileManagment\DashboardController@AuditLogsDashboardList')->name('FileManage.AuditLogsDashboardList');
      Route::get('file-manage/audit-logs/list', 'FileManagment\FileManageController@AuditLogsList')->name('FileManage.AuditLogsList');

      Route::post('file-manage/audit-log/store', 'FileManagment\FileManageController@AuditlogStore')->name('FileManage.AuditlogStore');



      Route::get('file-manage/file-version-dashboard-list', 'FileManagment\DashboardController@FileVersionDashboardList')->name('FileManage.FileVersionDashboardList');
      Route::get('file-manage/file-version-list', 'FileManagment\FileManageController@FileVersionList')->name('FileManage.FileVersionList');



      // Visa

      Route::get('visa/configuration', 'Visa\ConfigurationController@index')->name('visa.config');
      Route::post('visa/nationality', 'Visa\ConfigurationController@NationalityStore')->name('resort.visa.nationality.store');
      Route::get('visa/nationality/index', 'Visa\ConfigurationController@NationalityIndex')->name('resort.visa.NationalityIndex');
      Route::put('/visa/nationality/update/{id}', 'Visa\ConfigurationController@NationalityUpdate')->name('resort.visa.nationality.update');
      Route::delete('visa/nationality/destroy/{id}', 'Visa\ConfigurationController@Destroy')->name('resort.visa.nationality.destroy');
      Route::get('visa/nationality/export', 'Visa\ConfigurationController@Nationalityexport')->name('visa.natioanlity.export');
      Route::post('visa/nationality/import', 'Visa\ConfigurationController@NationalityImport')->name('resort.visa.nationality.Import');
      Route::post('visa/amt-form', 'Visa\ConfigurationController@VisaAmtForm')->name('resort.visa.VisaAmtForm');
      Route::post('visa/deposit-refund', 'Visa\ConfigurationController@DepositRefundStore')->name('resort.visa.DepositRefundStore');
      Route::post('visa/reminder-alert', 'Visa\ConfigurationController@Reminderalert')->name('resort.visa.Reminderalert');
      Route::get('visa/document-type/index', 'Visa\ConfigurationController@DocumentTypeIndex')->name('resort.visa.DocumentTypeIndex');
      Route::post('visa/document-type', 'Visa\ConfigurationController@DocumentType')->name('resort.visa.DocumentType');
      Route::put('/visa/document-type/{id}', 'Visa\ConfigurationController@DocumentTypeUpdate')->name('resort.visa.DocumentType.update');
      Route::delete('visa/document-type/delete/{id}', 'Visa\ConfigurationController@DocumentTypeDelete')->name('resort.visa.DocumentTypeDelete');

      Route::post('visa/wallet/store', 'Visa\ConfigurationController@VisaWalletsStore')->name('resort.visa.VisaWalletsStore');
      Route::get('visa/wallet/index', 'Visa\ConfigurationController@WalletIndex')->name('resort.visa.WalletIndex');
      Route::put('/visa/wallet/update/{id}', 'Visa\ConfigurationController@UpdateWallet')->name('resort.visa.UpdateWallet');
      Route::delete('visa/wallet/destroy/{id}', 'Visa\ConfigurationController@WalletDestroy')->name('resort.visa.WalletDestroy');


      Route::post('visa/document-segmentation', 'Visa\ConfigurationController@DocumentSegmentationStore')->name('resort.visa.DocumentSegmentationStore');

      Route::get('visa/document-management', 'Visa\DocumentController@index')->name('resort.visa.DocumentManage');
      Route::get('visa/xpatsync', 'Visa\DocumentController@Xpatsync')->name('resort.visa.Xpatsync');

      Route::post('visa/xpact-edit-amt', 'Visa\DashboardController@VisaXpactUpdateAmt')->name('resort.visa.VisaXpactEditAmt');


      //  Visa Renewal
      Route::get('visa/renewal', 'Visa\RenewalController@index')->name('resort.visa.RenewalView');
      Route::post('visa/getemployee/details', 'Visa\RenewalController@GetEmployeeDetails')->name('resorts.visa.renewal.getEmployeeDetails');
      Route::post('visa/upload-separate-file-using-ai', 'Visa\RenewalController@UploadSeparetFileUsingAi')->name('resorts.visa.renewal.UploadSeparetFileUsingAi');
      Route::post('visa/upload-quota-slot', 'Visa\RenewalController@UploadQuotaSlot')->name('resorts.visa.renewal.UploadQuotaSlot');
      Route::get('visa/renewal', 'Visa\RenewalController@index')->name('resort.visa.RenewalView');
      Route::get('visa/overview-dashboard-expiry', 'Visa\RenewalController@OrverviewDashbordExpiry')->name('resort.visa.OrverviewDashbordExpiry');




      // Expat Employee
      Route::get('visa/xpact-employee', 'Visa\XpactEmployeeController@XpactEmpIndex')->name('resort.visa.xpactEmployee');
      Route::get('visa/xpact-employee/details/{id}', 'Visa\XpactEmployeeController@XpactEmpDetails')->name('resort.visa.XpactEmpDetails');
      Route::get('visa/quota-slot/pending-fee', 'Visa\XpactEmployeeController@XpactEmpBudgetCost')->name('resort.visa.Quota_Slot_PendingFee');
      Route::post('visa/quota-slot/makr-as-paid', 'Visa\XpactEmployeeController@QuotaSlotMakrasPaid')->name('resort.visa.Quota_Slot_MakrasPaid');
      Route::get('visa/past-transection-history', 'Visa\XpactEmployeeController@PastTransectionHistory')->name('resort.visa.PastTransectionHistory');
      Route::post('visa/employee-wise-file-upload', 'Visa\XpactEmployeeController@EmployeeWiseVisaDocumentUpload')->name('resort.visa.EmployeeWiseVisaDocumentUpload');
      Route::get('visa/xpact-employee-file-download/{id}', 'Visa\XpactEmployeeController@XpactEmpFileDownload')->name('resort.visa.XpactEmpFileDownload');
    // VerifyDetails

     Route::get('visa/verify-details','Visa\RenewalController@VerifyDetails')->name('resort.visa.VerifyDetails');


      // Visa Dashboard Routes

      Route::post('visa/wallet-to-wallet-transfer', 'Visa\FundTransferController@VisaWalletToWalletTransfer')->name('resort.visa.VisaWalletToWalletTransfer');
      Route::get('visa/transection-history', 'Visa\FundTransferController@TransectionHistory')->name('resort.visa.TransectionHistory');
      Route::get('visa/nationality-wise-employee-deposit-and-count', 'Visa\DashboardController@NatioanlityWiseEmployeeDepositAndCount')->name('resort.visa.NatioanlityWiseEmployeeDepositAndCount');

      Route::get('visa/nationality-wise-employee-list', 'Visa\DashboardController@NatioanlityWiseEmployeeList')->name('resort.visa.NatioanlityWiseEmployeeList');

       Route::get('visa/nationality-wise-employee-deposit-and-count-details', 'Visa\DashboardController@NatioanlityWiseEmployeeDepositAndCountDetails')->name('resort.visa.NatioanlityWiseEmployeeDepositAndCountDetails');


      Route::get('visa/liability-breakdown', 'Visa\DashboardController@LiabilityBreakDown')->name('resort.visa.LiabilityBreakDown');
      Route::get('visa/nationality-wise-employee-breakdown-chart', 'Visa\DashboardController@NatioanlityWiseEmployeeBreakDownChart')->name('resort.visa.NatioanlityWiseEmployeeBreakDownChart');
      Route::get('visa/dashboard-flag-wise-get-data', 'Visa\DashboardController@DasbhoardFlagWiseGetData')->name('resort.visa.DasbhoardFlagWiseGetData');

      //  Xpact Sync
      Route::get('visa/xpact-sync', 'Visa\FetchDataAiController@index')->name('resort.visa.XpactSync');
      Route::post('visa/store', 'Visa\FetchDataAiController@store')->name('resorts.visa.xpactsync.store');

      //  Visa Expiry page

      Route::get('visa/expiry','Visa\ExpiryController@index')->name('resort.visa.Expiry');

      //Steps
      Route::post('visa/passport-expiry','Visa\RenewalController@PassportExpiry')->name('visa.passport.Checkexpiry');
      Route::post('visa/check-cv','Visa\RenewalController@CheckCv')->name('resort.visa.CheckCv');
      Route::post('visa/education','Visa\RenewalController@Education')->name('resort.visa.Education');
      Route::post('visa/experience','Visa\RenewalController@Experience')->name('resort.visa.Experience');
      Route::post('visa/create-employee','Visa\DocumentController@CreateEmployee')->name('resort.visa.CreateEmployee');



      // Payment Request
      Route::get('visa/payment-request','Visa\PaymentRequestController@Create')->name('resort.visa.PaymentRequest');
      Route::post('visa/payment-request/submit', 'Visa\PaymentRequestController@PaymentRequestSubmit')->name('resort.visa.PaymentRequestSubmit');
      Route::get('visa/payment-request/index','Visa\PaymentRequestController@index')->name('resort.visa.PaymentRequestIndex');
      Route::get('visa/payment-request/renewal/{id}','Visa\PaymentRequestController@UsingPaymentRequestRenewal')->name('resort.visa.UsingPaymentRequestRenewal');
      Route::get('visa/payment-request/details/{id}','Visa\PaymentRequestController@PaymentRequestDetails')->name('resort.visa.PaymentRequestDetails');

      Route::post('visa/payment-request/rejected', 'Visa\PaymentRequestController@PaymentRequestRejected')->name('resort.visa.PaymentRequestRejected');
      Route::get('visa/payment-request/download/{id}','Visa\PaymentRequestController@DownloadPymentRequest')->name('resort.visa.DownloadPymentRequest');
      Route::get('visa/payment-request/throw-renewal/{id}/{childid}','Visa\PaymentRequestController@PaymentRequestThrowRenewal')->name('resort.visa.PaymentRequestThrowRenewal');


    // PaymentDepositRequestController
      Route::get('visa/deposit-request','Visa\PaymentDepositRequestController@index')->name('resort.visa.DepositRequest');
      Route::post('visa/deposit-request/refund', 'Visa\PaymentDepositRequestController@DepositeRefundStore')->name('visa.deposit.refund.store');
      Route::post('visa/deposit-request/search','Visa\PaymentDepositRequestController@DepositRequestSearch')->name('visa.deposit.refund.search');
      Route::get('visa/deposit-request/dashboard','Visa\PaymentDepositRequestController@DashboardDepositRequest')->name('visa.deposit.DashboardDepositRequest');

      // Liabilities
      Route::get('visa/liabilities','Visa\LiabilitiesController@Index')->name('resort.visa.Liabilities');
      Route::get('visa/fetch-total-employees','Visa\LiabilitiesController@FetchTotalEmployees')->name('resort.visa.FetchTotalEmployees');

    //Incident Module

    Route::get('incident/configuration', 'Incident\ConfigurationController@index')->name('incident.configration');
    Route::get('incident/categories/view', 'Incident\ConfigurationController@viewCategories')->name('incident.categories.view');
    Route::get('incident/categories/list', 'Incident\ConfigurationController@categoriesList')->name('incident.categories.list');
    Route::post('incident/categories/store', 'Incident\ConfigurationController@storeCategories')->name('incident.categories.store');
    Route::put('incident/categories/inline-upadte/{id}', 'Incident\ConfigurationController@CategoryinlineUpdate')->name('incident.categories.inlineUpdate');
    Route::delete('incident/categories/destroy/{id}', 'Incident\ConfigurationController@categoryDestory')->name('incident.categories.destory');

    Route::get('incident/sub-categories/view', 'Incident\ConfigurationController@viewSubCategories')->name('incident.subcategories.view');
    Route::get('incident/sub-categories/list', 'Incident\ConfigurationController@subcategoriesList')->name('incident.subcategories.list');
    Route::post('incident/sub-categories/store', 'Incident\ConfigurationController@storeSubCategories')->name('incident.subcategories.store');
    Route::put('incident/sub-categories/inline-upadte/{id}', 'Incident\ConfigurationController@subcategoryinlineUpdate')->name('incident.subcategories.inlineUpdate');
    Route::delete('incident/sub-categories/destroy/{id}', 'Incident\ConfigurationController@subcategoryDestory')->name('incident.subcategories.destory');

    Route::get('incident/committees/view', 'Incident\ConfigurationController@viewCommittees')->name('incident.committees.view');
    Route::get('incident/committees/list', 'Incident\ConfigurationController@committeeList')->name('incident.committees.list');
    Route::post('incident/committees/store', 'Incident\ConfigurationController@storeCommittees')->name('incident.committees.store');
    Route::put('incident/committees/inline-upadte/{id}', 'Incident\ConfigurationController@committeeinlineUpdate')->name('incident.committees.inlineUpdate');
    Route::delete('incident/committees/destroy/{id}', 'Incident\ConfigurationController@committeeDestory')->name('incident.committees.destory');

   Route::post('incident/resolution-timeline/store', 'Incident\ConfigurationController@storeResolutionTimeline')->name('incident.resolution-timeline.store');
   Route::post('incident/meeting-reminder/store', 'Incident\ConfigurationController@storeMeetingReminder')->name('incident.meeting-reminder.store');
   Route::post('incident/severity-levels/store', 'Incident\ConfigurationController@saveSeverityLevels')->name('incident.severity.store');
   Route::post('incident/status/store', 'Incident\ConfigurationController@saveStatus')->name('incident.status.store');

  Route::get('incident/followup-actions/view', 'Incident\ConfigurationController@viewFolloupActions')->name('incident.followup-actions.view');
  Route::get('incident/followup-actions/list', 'Incident\ConfigurationController@FolloupActionsList')->name('incident.followup-actions.list');
  Route::post('incident/followup-actions/store', 'Incident\ConfigurationController@storeFolloupActions')->name('incident.followup-actions.store');
  Route::put('incident/followup-actions/inline-upadte/{id}', 'Incident\ConfigurationController@folloupActionsinlineUpdate')->name('incident.followup-actions.inlineUpdate');
  Route::delete('incident/followup-actions/destroy/{id}', 'Incident\ConfigurationController@folloupActionsDestory')->name('incident.followup-actions.destory');

  Route::get('incident/outcome-type/view', 'Incident\ConfigurationController@viewOutcomeType')->name('incident.outcome-type.view');
  Route::get('incident/outcome-type/list', 'Incident\ConfigurationController@outcomeTypeList')->name('incident.outcome-type.list');
  Route::post('incident/outcome-type/store', 'Incident\ConfigurationController@storeOutcomeType')->name('incident.outcome-type.store');
  Route::put('incident/outcome-type/inline-upadte/{id}', 'Incident\ConfigurationController@outcomeTypeinlineUpdate')->name('incident.outcome-type.inlineUpdate');
  Route::delete('incident/outcome-type/destroy/{id}', 'Incident\ConfigurationController@outcomeTypeDestory')->name('incident.outcome-type.destory');

  Route::get('incident/action-taken/view', 'Incident\ConfigurationController@viewActionTaken')->name('incident.action-taken.view');
  Route::get('incident/action-taken/list', 'Incident\ConfigurationController@ActionTakenList')->name('incident.action-taken.list');
  Route::post('incident/action-taken/store', 'Incident\ConfigurationController@storeActionTaken')->name('incident.action-taken.store');
  Route::put('incident/action-taken/inline-upadte/{id}', 'Incident\ConfigurationController@actionTakeninlineUpdate')->name('incident.action-taken.inlineUpdate');
  Route::delete('incident/action-taken/destroy/{id}', 'Incident\ConfigurationController@actionTakenDestory')->name('incident.action-taken.destory');


   Route::get('incidents/', 'Incident\IncidentController@index')->name('incident.index');
   Route::get('incidents/list', 'Incident\IncidentController@list')->name('incident.list');
   Route::get('incidents/resolved-list', 'Incident\IncidentController@resolvedList')->name('incident.resolved.list');

   Route::get('incidents/view-details/{id}', 'Incident\IncidentController@view')->name('incident.view');
   Route::get('incidents/investigation/{id}', 'Incident\IncidentController@investigation')->name('incident.investigation');

   Route::post('incidents/destroy', 'Incident\IncidentController@destroy')->name('incident.delete');
   Route::post('incidents/assign', 'Incident\IncidentController@assign')->name('incident.assign');
   Route::post('incidents/investigation/store', 'Incident\IncidentController@storeInvestigation')->name('incident.investigation.store');

   Route::get('incidents/meeting', 'Incident\IncidentMeetingController@index')->name('incident.meeting');
   Route::get('incidents/meeting/list', 'Incident\IncidentMeetingController@list')->name('incident.meeting.list');
   Route::get('incidents/meeting/create/{id}', 'Incident\IncidentMeetingController@create')->name('incident.meeting.create');
   Route::post('incidents/meeting/store', 'Incident\IncidentMeetingController@store')->name('incident.meeting.store');
   Route::get('incidents/meeting/view-details/{id}', 'Incident\IncidentMeetingController@details')->name('incident.meeting.detail');

   Route::post('incidents/meeting/inline-update','Incident\IncidentMeetingController@inlineUpdate')->name('incident.meeting.inlineUpdate');
   Route::delete('incidents/meeting/delete/{id}','Incident\IncidentMeetingController@delete')->name('incident.meeting.delete');

   Route::get('incidents/calendar', 'Incident\IncidentMeetingCalendarController@index')->name('incident.calendar');
   Route::get('incidents/calendar/get-incidents', 'Incident\IncidentMeetingCalendarController@getIncidentMeetings')->name('incident.calendar.get-meetings');

   Route::post('incidents/request-statement', 'Incident\IncidentController@requestEmployeeStatements')->name('incident.request-statement');
   Route::get('incidents/get/department-wise-participation', 'Incident\DashboardController@getDepartmentWiseParticipation')->name('incident.chart.getDepartmentWiseParticipation');
   Route::get('incidents/get-trends', 'Incident\DashboardController@getIncidentTrends')->name('incident.chart.getTrends');

   Route::get('incidents/hod/get-trends', 'Incident\DashboardController@gethodIncidentTrends')->name('incident.hod-chart.getTrends');

   Route::get('incidents/get-resolution-stats','Incident\DashboardController@getResolutionTimelineStats')->name('incident.getResolutionTimelineStats');
   Route::get('incidents/hod/get-resolution-stats','Incident\DashboardController@getResolutionTimelineData')->name('incident.hod.getResolutionTimelineStats');

   Route::get('incidents/get-upcoming-meetings', 'Incident\DashboardController@getUpcomingMeetings')->name('incident.getUpcomingMeetings');
   Route::get('incidents/preventive/list', 'Incident\DashboardController@getPreventiveActions')
   ->name('incident.preventive.list');
   Route::get('/incidents/preventive-measures', 'Incident\DashboardController@preventiveMeasuresList')->name('incident.preventive');
  Route::get('/incidents/pending-approvals', 'Incident\DashboardController@pendingApprovalsList')->name('incident.pending-approvals');

   Route::get('incidents/pending-resolutions', 'Incident\DashboardController@getPendingResolutionApprovals')->name('incident.pendingResolutions');
   Route::get('/incidents/incident-status-chart', 'Incident\DashboardController@getIncidentStatusStats')->name('incident.getIncident.chartdata');

   Route::get('incidents/todo-list', 'Incident\DashboardController@getIncidentTodoList')->name('incident.todoList');

   Route::get('incidents/hod-preventive/list', 'Incident\DashboardController@gethodPreventiveActions')
   ->name('incident.preventive.hodlist');
   Route::get('/incidents/hod-preventive-measures', 'Incident\DashboardController@hodpreventiveMeasuresList')->name('incident.hod.preventive');
   Route::get('/incidents/hod-pending-approvals', 'Incident\DashboardController@getPendingResolutionApprovalsforHOD')->name('incident.hod-pending-approvals');
   Route::post('/incidents/approve', 'Incident\IncidentController@approve')->name('incident.investigation.approve');
   Route::post('/incidents/approve-or-reject', 'Incident\IncidentController@approveOrReject')->name('incident.investigation.approvedorreject');

       //People module start
       Route::get('/division-department-data/{id?}', 'People\DashboardController@getDepartmentCounts')->name('get.division-by-dept');
       Route::get('/get-employee-stats', 'People\DashboardController@getEmployeeStats')->name('get.employeeStats');
        Route::get('employees/all-ids', 'People\Employee\EmployeeController@getAllEmployeeIds')->name('people.employees.all-ids');

       //Announcement
       Route::get('people/announcement/create', 'People\AnnouncementController@create')->name('people.announcement.create');
       Route::get('people/announcements', 'People\AnnouncementController@index')->name('people.announcements');
       Route::post('people/announcements/store', 'People\AnnouncementController@store')->name('people.announcements.store');
       Route::get('people/announcements/view/{id}', 'People\AnnouncementController@view')->name('people.announcements.view');
       Route::get('people/announcements/edit/{id}', 'People\AnnouncementController@edit')->name('people.announcements.edit');
       Route::post('people/announcements/update/{id}', 'People\AnnouncementController@update')->name('people.announcements.update');
       Route::get('people/announcements/employee-details', 'People\AnnouncementController@getEmployeeDetails')->name('people.announcements.employee-detail');

       Route::post('/people/announcements/archive',  'People\AnnouncementController@archive')->name('people.announcements.archive');
       Route::post('/people/announcements/restore', 'People\AnnouncementController@restore')->name('people.announcements.restore');

       //promotion start
        //Promotion Dasboard
        Route::get('/people/promotion/dashboard', 'People\Promotion\DashboardController@index')->name('people.promotion.dashboard');
       Route::get('/people/promotion/initiate-promotion', 'People\Promotion\PromotionController@index')->name('people.promotion.initiate');
       Route::get('people/job-description/by-position/{posId}', 'TalentAcquisition\JobDescriptionController@fetchByPosition')->name('job.description.by.position');
       Route::get('people/benefit-grid/view/{level}', 'BenifitGridController@viewByLevel')
       ->name('benefit.grid.view');
       Route::post('people/promotion/submit', 'People\Promotion\PromotionController@submitPromotion')->name('promotion.submit');
       Route::get('/people/promotion/list', 'People\Promotion\PromotionController@list')->name('people.promotion.list');
       Route::get('/people/promotion/filter', 'People\Promotion\DashboardController@filter')->name('people.promotion.filter');
       Route::get('/people/promotion/get-poition-details', 'People\Promotion\PromotionController@getPosDetails')->name('position.get.details');
      Route::get('/people/promotion/approval/{id}', 'People\Promotion\PromotionController@approval')->name('promotion.approval');
      Route::get('/people/promotion/detail/{id}', 'People\Promotion\PromotionController@detail')->name('promotion.details');
      Route::post('people/promotion/{id}/{action}', 'People\Promotion\PromotionController@handlePromotionApproval')->name('promotion.review.action');
      Route::post('/people/promotion/send-letter', 'People\Promotion\PromotionController@sendPromotionLetter')->name('promotion.send-letter');
      Route::post('/people/promotion/confirm-promotion','People\Promotion\PromotionController@confirmPromotion')->name('promotion.confirm');
      Route::get('/people/promotion/history', 'People\Promotion\PromotionController@getHistory')->name('people.promotion.history');
      Route::get('/promotion-history/export/excel', 'People\Promotion\PromotionController@exportExcel')->name('promotion.history.export.excel');
      Route::get('/promotion-history/export/pdf', 'People\Promotion\PromotionController@exportPDF')->name('promotion.history.export.pdf');
      Route::get('/promotion/basic-salary-data', 'People\Promotion\DashboardController@getBasicSalaryData')->name('promotion.basic-salary.data');
      Route::post('/people/promotion/inline-update', 'People\Promotion\PromotionController@inlineUpdate')->name('promotion.inlineUpdate');
      Route::post('/people/promotion/get-employee-wise-filter-data', 'People\Promotion\PromotionController@GetEmployeeWiseFilterData')->name('resort.promotion.getFilteredEmployees');

       //Promotion End

       //People Configuration
       Route::get('/people/configuration', 'People\ConfigController@index')->name('people.config');
       Route::post('/people/announcement-category', 'People\ConfigController@storeCategory')->name('announcement.category.store');
       Route::get('/people/announcement-category/index', 'People\ConfigController@announcementCategories')->name('announcement.categories');
       Route::put('people/announcement-category/inline-update/{id}', 'People\ConfigController@CategoryinlineUpdate')->name('announcement.categories.inlineUpdate');
       Route::delete('people/announcement-category/destroy/{id}', 'People\ConfigController@categoryDestory')->name('announcement.category.destory');

       Route::post('/people/configuration/resignation-withdrawal', 'People\ConfigController@EmployeeResignationWithdrawalConfigStore')->name('people.config.resignation-withdrawal-config');

      //  Exit Clearance Module configuration
      Route::get('/people/exit-clearance-forms', 'People\configuration\ExitClearanceController@index')->name('people.exit-clearance.index');
      Route::get('/people/exit-clearance/list', 'People\configuration\ExitClearanceController@list')->name('people.exit-clearance.list');
      Route::get('/people/exit-clearance/create', 'People\configuration\ExitClearanceController@create')->name('people.exit-clearance.create');
      Route::post('/people/exit-clearance/store', 'People\configuration\ExitClearanceController@store')->name('people.exit-clearance.store');
      Route::get('/people/exit-clearance/edit/{id}', 'People\configuration\ExitClearanceController@edit')->name('people.exit-clearance.edit');
      Route::post('/people/exit-clearance/update/{id}', 'People\configuration\ExitClearanceController@update')->name('people.exit-clearance.update');
      Route::delete('/people/exit-clearance/destroy/{id}', 'People\configuration\ExitClearanceController@destroy')->name('people.exit-clearance.destroy');

      //  Resignation Reason Module configuration
      Route::get('/people/resignation-reasons','People\configuration\ResignationReasonController@index')->name('people.resignation-reason.index');
      Route::get('/people/resignation-reason/list','People\configuration\ResignationReasonController@list')->name('people.resignation-reason.list');
      Route::post('/people/resignation-reason/store','People\configuration\ResignationReasonController@store')->name('people.resignation_reasons.store');
      Route::post('/people/resignation-reason/update/{id}','People\configuration\ResignationReasonController@update')->name('people.resignation-reasons.inlineUpdate');
      Route::delete('/people/resignation-reason/destroy/{id}','People\configuration\ResignationReasonController@destroy')->name('people.resignation-reasons.destroy');


      //Employee Reminder Module configuration
      Route::get('/people/reminder','People\configuration\EmployeeReminderController@index')->name('people.reminders.index');
      Route::get('/people/reminder/list','People\configuration\EmployeeReminderController@list')->name('people.reminders.list');
      Route::post('/people/reminder/store','People\configuration\EmployeeReminderController@store')->name('people.reminders.store');
      Route::post('/people/reminder/update/{id}','People\configuration\EmployeeReminderController@update')->name('people.reminders.update');
      Route::delete('/people/reminder/destroy/{id}','People\configuration\EmployeeReminderController@destroy')->name('people.reminders.destroy');

      // Employee Increment Type Module
      Route::get('/people/increment-types','People\configuration\IncrementTypeController@index')->name('people.increment-types.index');
      Route::get('/people/increment-types/list','People\configuration\IncrementTypeController@list')->name('people.increment-types.list');
      Route::post('/people/increment-types/store','People\configuration\IncrementTypeController@store')->name('people.increment-types.store');
      Route::post('/people/increment-types/update/{id}','People\configuration\IncrementTypeController@update')->name('people.increment-types.update');
      Route::delete('/people/increment-types/destroy/{id}','People\configuration\IncrementTypeController@destroy')->name('people.increment-types.destroy');


      // Employee Salary Increment Module
      // Sub List Routes
      Route::get('/people/salary-increment/list','People\SalaryIncrementController@index')->name('people.salary-increment.index');
      Route::get('people/salary-increment/employee/list', 'People\SalaryIncrementController@list')->name('people.salary-increment.employee.list');

      // grid Routes
      Route::get('/people/salary-increment','People\SalaryIncrementController@gridIndex')->name('people.salary-increment.grid-index');
      Route::get('people/salary-increment/employee/grid-view', 'People\SalaryIncrementController@employeeGridView')->name('people.salary-increment.employee.grid-view');

      Route::get('people/salary-increment/summary-view', 'People\SalaryIncrementController@summaryView')->name('people.salary-increment.summary-view');
      Route::post('people/salary-increment/summary-store', 'People\SalaryIncrementController@summaryStore')->name('people.salary-increment.summary-store');
      Route::post('people/salary-increment/bulk-action', 'People\SalaryIncrementController@bulkStore')->name('people.salary-increment.bulk-store');
      Route::post('people/salary-increment/bulk-update', 'People\SalaryIncrementController@bulkUpdate')->name('people.salary-increment.bulk-update');
      Route::post('people/salary-increment/bulk-update-status', 'People\SalaryIncrementController@bulkUpdateStatus')->name('people.salary-increment.bulk-update-status');
      Route::get('people/salary-increment/edit/{id}', 'People\SalaryIncrementController@edit')->name('people.salary-increment.edit');
      Route::post('people/salary-increment/update/{id}', 'People\SalaryIncrementController@update')->name('people.salary-increment.update');

      // Employee Salary Increment Module
      // Finance and Gm Module
      Route::get('people/salary-increment/summary-list', 'People\SalaryIncrementController@summaryIndex')->name('people.salary-increment.summary-list');
      Route::post('people/salary-increment/update', 'People\SalaryIncrementController@updateStatus')->name('people.salary-increment.update-status');
      Route::post('people/salary-increment/request-change', 'People\SalaryIncrementController@requestChange')->name('people.salary-increment.request-change');
      Route::post('people/salary-increment/request-hold', 'People\SalaryIncrementController@holdRequest')->name('people.salary-increment.hold-request');
      Route::get('people/salary-increment/download', 'People\SalaryIncrementController@downloadByFormate')->name('people.salary-increment.download');

      Route::get('people/salary-increment-history', 'People\SalaryIncrementController@incrementHistory')->name('people.salary-increment.history-list');

      // Employee Advance Salary  Module
      Route::get('people/advance-salary','People\Employee\AdvanceSalaryController@index')->name('people.advance-salary.index');
      Route::get('people/advance-salary/list','People\Employee\AdvanceSalaryController@list')->name('people.advance-salary.list');
      Route::get('people/advance-salary/show/{id}','People\Employee\AdvanceSalaryController@show')->name('people.advance-salary.show');
      Route::post('people/advance-salary/payment-reschedule','People\Employee\AdvanceSalaryController@paymentReschedule')->name('people.advance-salary.payment-reschedule');
      Route::post('people/advance-salary/payment-interest-calculate','People\Employee\AdvanceSalaryController@paymentRescheduleCalculate')->name('people.advance-salary.payment-interest-calculate');
      Route::post('people/advance-salary/payment-reschedule-store','People\Employee\AdvanceSalaryController@paymentRescheduleStore')->name('people.advance-salary.payment-reschedule-store');
      Route::post('people/advance-salary/update-status','People\Employee\AdvanceSalaryController@updateStatus')->name('people.advance-salary.update-status');


      // Employee Advance Salary Repayment Schedule  Module
      Route::get('people/advance-salary-repayment-tracker','People\Employee\AdvanceSalaryRepaymentTrackerController@index')->name('people.advance-salary-repayment-tracker.index');
      Route::get('people/advance-salary-repayment-tracker/list','People\Employee\AdvanceSalaryRepaymentTrackerController@list')->name('people.advance-salary-repayment-tracker.list');
      Route::get('people/advance-salary-repayment-tracker/show/{id}','People\Employee\AdvanceSalaryRepaymentTrackerController@show')->name('people.advance-salary-repayment-tracker.show');

      Route::post('people/advance-salary-repayment-tracker/update-status','People\Employee\AdvanceSalaryRepaymentTrackerController@updateStatus')->name('people.advance-salary-repayment-tracker.update-status');
      Route::post('people/advance-salary-repayment-tracker/update','People\Employee\AdvanceSalaryRepaymentTrackerController@update')->name('people.advance-salary-repayment-tracker.update');
      Route::post('people/advance-salary-repayment-tracker/addNote','People\Employee\AdvanceSalaryRepaymentTrackerController@addNote')->name('people.advance-salary-repayment-tracker.addNotes');
      Route::get('people/advance-salary-repayment-tracker/mark-as-complete/{id}','People\Employee\AdvanceSalaryRepaymentTrackerController@markAsComplete')->name('people.advance-salary-repayment-tracker.markAsComplete');


      Route::get('advance-salary-repayment-tracker/{id}/download-pdf', 'People\Employee\AdvanceSalaryRepaymentTrackerController@downloadPdf')
    ->name('people.advance-salary-repayment-tracker.downloadPdf');

      // Employee Notice Period configuration
      Route::get('/people/notice-period','People\configuration\NoticePeriodController@index')->name('people.notice-period.index');
      Route::get('/people/notice-period/list','People\configuration\NoticePeriodController@list')->name('people.notice-period.list');
      Route::post('/people/notice-period/store','People\configuration\NoticePeriodController@store')->name('people.notice-period.store');
      Route::post('/people/notice-period/update/{id}','People\configuration\NoticePeriodController@update')->name('people.notice-period.update');
      Route::delete('/people/notice-period/delete/{id}','People\configuration\NoticePeriodController@destroy')->name('people.notice-period.destroy');

       //Employees management
       Route::get('/people/employees', 'People\Employee\EmployeeController@index')->name('people.employees');
       Route::get('/people/employees/create', 'People\Employee\EmployeeController@create')->name('people.employees.create');
       Route::post('/people/employees/store', 'People\Employee\EmployeeController@store')->name('people.employees.store');
      Route::get('/people/employees/grid', 'People\Employee\EmployeeController@fetchEmployeesGrid')->name('people.employees.grid');
       Route::get('/people/employees/list', 'People\Employee\EmployeeController@fetchEmployeesList')->name('people.employees.list');
       Route::get('/people/employees/details/{id}', 'People\Employee\EmployeeController@details')->name('people.employees.details');
       Route::post('/people/employees/extract-details', 'People\Employee\EmployeeController@extractDetails')->name('people.employees.extract-details');
       Route::post('/people/employees/setp-save', 'People\Employee\EmployeeController@saveStep')->name('save.employeeinfo.step');
       Route::post('/people/employees/draft', 'People\Employee\EmployeeController@getDraft')->name('get.employeeinfo.draft');
       Route::post('/people/employees/delete', 'People\Employee\EmployeeController@delete')->name('employee.delete');
      Route::post('/people/employees/bulk-delete', 'People\Employee\EmployeeController@bulkDelete')->name('employee.bulk.delete');
      Route::post('/people/employees/send-credentials', 'People\Employee\EmployeeController@sendCredentials')->name('people.employee.send-credentials');



       Route::get('people/employee/department-by-dision', 'People\Employee\EmployeeController@getDepartmentByDivision')->name('people.getDepartmentsByDivision');
       Route::get('people/employee/section-by-department', 'People\Employee\EmployeeController@getSectionByDepartment')->name('people.getSectionByDepartment');
       Route::get('people/employee/position-by-section', 'People\Employee\EmployeeController@getPositionBySection')->name('people.getPositionBySection');
       Route::get('people/employee/get-reporting-person', 'People\Employee\EmployeeController@getReportingPerson')->name('people.getReportingPerson');
       Route::get('people/employee/get-benifit-grid', 'People\Employee\EmployeeController@getBenefitGridByPosition')->name('people.getBenefitGridByPosition');



       Route::post('/people/employees/export-selected', 'People\Employee\EmployeeController@exportSelected')->name('people.employees.export');
       Route::post('/people/employee/assign-team', 'People\Employee\EmployeeController@assignToTeam')->name('people.employees.assign-team');
       Route::post('/people/employee/change-status', 'People\Employee\EmployeeController@changeStatus')->name('people.employee.change-status');
       Route::post('/people/employee/update-personal', 'People\Employee\EmployeeController@updatePersonal')->name('employee.update.personal');
       Route::post('/people/employee/update-contacts', 'People\Employee\EmployeeController@updateContacts')->name('employee.update.contacts');
       Route::post('/people/employee/update-emergency-contacts', 'People\Employee\EmployeeController@updateEmergencyContacts')->name('employee.update.emergency-contacts');
       Route::post('/people/employee/update-additional-info', 'People\Employee\EmployeeController@updateAdditionalInfo')->name('employee.update.additional-info');
       Route::post('/people/employee/update-employment-data', 'People\Employee\EmployeeController@updateEmploymentData')->name('employee.update.employment-data');
      Route::post('/people/employee/update-salary', 'People\Employee\EmployeeController@updateSalary')->name('employee.update.salary');

       Route::post('/people/employee/update-education/{id}', 'People\Employee\EmployeeController@updateEducationDetails')->name('employee.update.eduDetails');
       Route::post('/people/employee/add-education', 'People\Employee\EmployeeController@addEducationDetails')->name('employee.store.eduDetails');
       Route::post('/people/employee/update-experiance/{id}', 'People\Employee\EmployeeController@updateExperianceDetails')->name('employee.update.expDetails');
       Route::post('/people/employee/add-experiance', 'People\Employee\EmployeeController@addExperianceDetails')->name('employee.store.expDetails');
       Route::post('/people/employee/update-expiry-doc-details', 'People\Employee\EmployeeController@updateExpiryDocuments')->name('employee.update.expiryDocuments');
      Route::post('/people/employee/add-bank-details', 'People\Employee\EmployeeController@addBankDetails')->name('employee.store.bankDetails');
       Route::post('/people/employee/update-bank-details/{id}', 'People\Employee\EmployeeController@updateBankDetails')->name('employee.update.bankDetails');

       //Probation Sub Module Start
       Route::get('/people/probation', 'People\Probation\ProbationController@index')->name('people.probation');
       Route::get('/people/probation/details/{id}', 'People\Probation\ProbationController@details')->name('people.probation.details');
       Route::post('/people/probation/confirm-probation/{id}',  'People\Probation\ProbationController@confirmProbation')->name('confirm.probation');
       Route::post('/people/probation/fail-probation/{id}', 'People\Probation\ProbationController@failProbation')->name('fail.probation');
       Route::post('/people/probation/extend-probation/{id}', 'People\Probation\ProbationController@extendProbation')->name('extend.probation');
       Route::post('/people/probation/send-letter', 'People\Probation\ProbationController@sendProbationLetter')->name('probation.send-letter');
       Route::post('/people/probation/letter-template', 'People\Probation\ProbationController@letterTamplate')->name('probation.letter-template');
       Route::get('/people/probation/email-template-index', 'People\Probation\ProbationController@ProbationEmailTamplateIndex')->name('probation.EmailTamplateIndex');
       Route::post('/people/probation/get-probation-letter-template', 'People\Probation\ProbationController@GetEmailTamplate')->name('probation.GetEmailTamplate');
       Route::get('/people/probation/{id}/probation-export', 'People\Probation\ProbationController@exportProbationPdf')->name('employee.probation.export');

       //Probation Sub Module End

         //Transfer Management
         Route::get('/people/transfer/request-initiate', 'People\Transfer\TransferController@index')->name('people.transfer.initiate');
         Route::post('/people/transfer/store-transfer', 'People\Transfer\TransferController@store')->name('people.transfer.store');
         Route::get('/people/transfer/list', 'People\Transfer\TransferController@list')->name('people.transfer.list');
         Route::post('people/employee/transfer-history', 'People\Transfer\TransferController@getEmployeeTransferHistory')->name('employee.transfer.history');
         Route::post('people/employee/transfer-stats', 'People\Transfer\TransferController@getTransferStats')->name('employee.transfer.stats');
         Route::post('people/employee/transfer-type-chart', 'People\Transfer\TransferController@getTransferTypeStats')->name('employee.transfer.type.chart');
         Route::post('people/transfer/{id}/{action}', 'People\Transfer\TransferController@handleApproval')->name('people.transfer.handle-approval');
         Route::post('people/transfer/check-budget', 'People\Transfer\TransferController@checkBudget')->name('transfer.checkBudget');
         Route::post('people/transfer/get-reporting-managers', 'People\Transfer\TransferController@getReportingManagers')->name('resort.get.reporting_managers');
         Route::get('/people/transfer/history', 'People\Transfer\TransferController@history')->name('people.transfer.history');



         //Transfer management End

        //  Info Update Module Start
          Route::get('people/info-update/index','People\Employee\InfoUpdateController@index')->name('people.info-update.index');
          Route::get('people/info-update/list','People\Employee\InfoUpdateController@list')->name('people.info-update.list');
          Route::get('people/info-update/show/{id}','People\Employee\InfoUpdateController@show')->name('people.info-update.show');
          Route::get('people/info-update/status','People\Employee\InfoUpdateController@statusChange')->name('people.info-update.status-change');
          Route::post('people/info-update/rejected','People\Employee\InfoUpdateController@rejectRequest')->name('people.info-update.request-rejected');

        //  Info Update Module  End


        //OnBoarding start
        //Template creation
        Route::get('people/onboarding/config','People\Onboarding\OnboardingController@config')->name('people.onboarding.config');
        Route::get('people/onboarding/create','People\Onboarding\OnboardingController@index')->name('people.onboarding.index');
        Route::get('people/onboarding/itinerary-template/create', 'People\Onboarding\OnboardingController@create')->name('onboarding.itinerary-template.create');
        Route::post('people/onboarding/itinerary-template/store', 'People\Onboarding\OnboardingController@store')->name('onboarding.itinerary-template.store');
        Route::get('people/onboarding/itinerary-template/list', 'People\Onboarding\OnboardingController@list')->name('onboarding.itinerary-template.list');
        Route::get('people/onboarding/itinerary-template/edit/{id}', 'People\Onboarding\OnboardingController@edit')->name('onboarding.itinerary-template.edit');
        Route::post('people/onboarding/itinerary-template/update/{id}', 'People\Onboarding\OnboardingController@update')->name('onboarding.itinerary-template.update');
        Route::delete('people/onboarding/itinerary-template/destroy/{id}', 'People\Onboarding\OnboardingController@destroy')->name('onboarding.itinerary-template.destroy');

       //notification events
      Route::get('/people/onboarding/events','People\Onboarding\OnboardingController@events')->name('onboarding.events');
      Route::post('/people/onboarding/events/store','People\Onboarding\OnboardingController@storeEvents')->name('onboarding.events.store');
      Route::post('/people/onboarding/events/update/{id}','People\Onboarding\OnboardingController@updateEvent')->name('onboarding.events.inlineUpdate');
      Route::delete('/people/onboarding/events/destroy/{id}','People\Onboarding\OnboardingController@destroyEvent')->name('onboarding.events.destroy');

      Route::post('people/onboarding/cultuarl-insights/store', 'People\Onboarding\OnboardingController@storeOrUpdateCI')->name('onboarding.cultural_insights.storeOrUpdate');

      //Itinerary Creation
      Route::get('people/onboarding/itinerary/create', 'People\Onboarding\OnboardingController@createItinerary')->name('onboarding.itinerary.create');
      Route::get('people/onboarding/itinerary/get-upcoming-employees', 'People\Onboarding\OnboardingController@getupcomingEmployees')->name('people.onboarding.upcoming_employees');
      Route::post('/get-templates-for-employees', 'People\Onboarding\OnboardingController@getTemplatesForEmployees')->name('people.onboarding.getTemplatesForEmployees');

      Route::post('/people/onboarding/get-employee-details', 'People\Onboarding\OnboardingController@getEmployeeDetails')
    ->name('people.onboarding.getEmployeeDetails');

    Route::post('/people/onboarding/get-participant-details', 'People\Onboarding\OnboardingController@getParticipantDetails')
    ->name('people.onboarding.getParticipantDetails');

    Route::post('/people/onboarding/itinerary/store', 'People\Onboarding\OnboardingController@storeItinerary')
    ->name('people.onboarding.itinerary.store');
    Route::get('/people/onboarding/itinerary/list', 'People\Onboarding\OnboardingController@itiernaries')
    ->name('people.onboarding.itinerary.list');

    Route::get('/people/onboarding/itinerary/viewDetails/{id}', 'People\Onboarding\OnboardingController@viewItineraryDetails')
    ->name('people.onboarding.itinerary.viewDetails');

    Route::get('/people/onboarding/itinerary/edit/{id}', 'People\Onboarding\OnboardingController@editItinerary')
    ->name('people.onboarding.itinerary.edit');
    Route::post('/people/onboarding/itinerary/update/{id}', 'People\Onboarding\OnboardingController@updateItinerary')
    ->name('people.onboarding.itinerary.update');
    Route::post('/people/onboarding/check-meeting-conflicts',
    'People\Onboarding\OnboardingController@checkMeetingConflicts')
    ->name('people.onboarding.checkMeetingConflicts');

      //OnBoarding  end

      //Organization Chart
      Route::get('/people/organization-chart', 'People\OrgChart\OrganizationChartController@index')->name('people.org-chart');
      Route::get('/people/organization-chart/get-employees', 'People\OrgChart\OrganizationChartController@getEmployees')->name('people.org-chart.getEmployees');

    // New routes for enhanced PDF support
      Route::get('/people/organization-chart/image-base64', 'People\OrgChart\OrganizationChartController@getImageAsBase64')->name('people.org-chart.getImageBase64');
      Route::post('/people/organization-chart/bulk-images-base64', 'People\OrgChart\OrganizationChartController@getBulkImagesAsBase64')->name('people.org-chart.getBulkImagesBase64');
    //Organization Chart Export End

    //Employee Resignation
      Route::get('/people/employee-resignation', 'People\Employee\EmployeeResignationController@index')->name('people.employee-resignation.index');
      Route::get('/people/employee-resignation/show/{id}', 'People\Employee\EmployeeResignationController@show')->name('people.employee-resignation.show');
      Route::post('people/employee-resignation/status-update', 'People\Employee\EmployeeResignationController@updateStatus')->name('people.employee-resignation.status-update');
      Route::post('/people/employee-resignation/schedule-meeting', 'People\Employee\EmployeeResignationController@scheduleMeeting')->name('people.employee-resignation.schedule-meeting');


    //Exit Clearance
      Route::get('/people/exit-clearance', 'People\ExitClearance\ExitClearanceController@index')->name('people.exit-clearance');
      Route::get('/people/exit-clearance/view-details/{id}', 'People\ExitClearance\ExitClearanceController@viewDetails')->name('people.exit-clearance.viewDetails');
      Route::post('people/exit-clearance/assignments-submit-department', 'People\ExitClearance\ExitClearanceController@assignmentSubmitDepartment')->name('people.exit-clearance.assignmentSubmitDepartment');

      Route::get('people/exit-clearance/employee-form-assign/{id}', 'People\ExitClearance\ExitClearanceController@employeeFormAssignment')->name('people.exit-clearance.employeeFormAssignment');
      Route::post('people/exit-clearance/assignments-submit-employee', 'People\ExitClearance\ExitClearanceController@assignmentSubmitEmployee')->name('people.exit-clearance.assignmentSubmitEmployee');

      Route::get('people/exit-clearance/employee-form-assigned-view/{id}', 'People\ExitClearance\ExitClearanceController@employeeFormAssignmentShow')->name('people.exit-clearance.employeeFormAssignmentShow');
      Route::post('people/exit-clearance/employee-departure-arrangment/{id}', 'People\ExitClearance\ExitClearanceController@employeeDepartureArrangement')->name('people.exit-clearance.employeeDepartureArrangement');
      Route::post('people/exit-clearance/mark-as-complete/{id}', 'People\ExitClearance\ExitClearanceController@markAsComplete')->name('people.exit-clearance.markAsComplete');

      Route::get('people/exit-clearance/department-form/{id}', 'People\ExitClearance\ExitClearanceController@departmentForm')->name('people.exit-clearance.department-form');
      Route::post('people/exit-clearance/department-response-store', 'People\ExitClearance\ExitClearanceController@departmentFormResponseStore')->name('people.exit-clearance.department-response-store');

      Route::get('people/exit-clearance/employee-certificate/{id}', 'People\ExitClearance\ExitClearanceController@employementCertificate')->name('people.exit-clearance.employement-certificate');
      Route::get('people/exit-clearance/send-reminder/{id}', 'People\ExitClearance\ExitClearanceController@sendReminder')->name('people.exit-clearance.sendReminder');

      Route::get('people/exit-clearance/get-status', 'People\ExitClearance\ExitClearanceController@getStatus')->name('people.exit-clearance.get-status');
    // Exit Clearance End


      //Initial Liability Estimation
      Route::get('/people/initial-liability-estimation', 'People\Liability\LiabilityEstimationController@index')->name('people.liability.index');

      Route::get('/people/initial-liability-estimation/add-cost', 'People\Liability\LiabilityEstimationController@addCost')->name('people.liability.addCost');
      Route::get('/people/initial-liability-estimation/get-data', 'People\Liability\LiabilityEstimationController@getLiabilityData')->name('people.liabilities.data');

      Route::get('/people/initial-liability-estimation/get-employee-data/{empId}', 'People\Liability\LiabilityEstimationController@getLiabilityEmployeeData')->name('people.liabilities.emp-data');
      //End of Initial Liability Estimation

      //SOS Module Start
    Route::get('sos/configuration', 'SOS\ConfigurationController@index')->name('sos.config.index');

    // SOS roles and permission
    Route::post('sos/roles-permission/store', 'SOS\ConfigurationController@SOSRolesAndPermissionStore')->name('sos.config.SOSRolesAndPermissionStore');
    Route::get('sos/roles-permission/index', 'SOS\ConfigurationController@IndexSOSRolesAndPermission')->name('sos.config.IndexSOSRolesAndPermission');
    Route::put('sos/roles-permission/update/{id}', 'SOS\ConfigurationController@SOSRoleAndPerminlineUpdate')->name('sos.config.SOSRoleAndPerminlineUpdate');
    Route::delete('sos/roles-permission/destroy/{id}', 'SOS\ConfigurationController@SOSRolesAndPermissionDestory')->name('sos.config.SOSRolesAndPermissionDestory');

    // SOS team management
    Route::post('sos/team-management/store', 'SOS\ConfigurationController@SOSTeamStore')->name('sos.config.SOSTeamStore');
    Route::get('sos/team-management/index', 'SOS\ConfigurationController@IndexSOSTeamManagement')->name('sos.config.IndexSOSTeamManagement');
    Route::put('sos/team-management/inline-update/{id}', 'SOS\ConfigurationController@SOSTeamManagementinlineUpdate')->name('sos.config.SOSTeamManagementinlineUpdate');
    Route::delete('sos/team-management/destroy/{id}', 'SOS\ConfigurationController@SOSTeamManagementDestory')->name('sos.config.SOSTeamManagementDestory');
    Route::get('sos/team-management/view-details/{id}', 'SOS\ConfigurationController@team_details')->name('sos.team.detail');
    Route::get('sos/team-management/list-details/{id}', 'SOS\ConfigurationController@get_team_details')->name('sos.team.getTeamDetails');
    Route::put('sos/team-member/inline-update/{id}', 'SOS\ConfigurationController@SOSTeamMemberinlineUpdate')->name('sos.config.SOSTeamMemberinlineUpdate');
    Route::delete('sos/team-member/destroy/{id}', 'SOS\ConfigurationController@SOSTeamMemberDestory')->name('sos.config.SOSTeamMemberDestory');
    Route::get('sos/team-member/{id}/edit', 'SOS\ConfigurationController@getTeamData')->name('sos.team.edit');
    Route::post('sos/team-member/update/{id}', 'SOS\ConfigurationController@update_team_details')->name('sos.team.update');

    //SOS Emergency types
    Route::post('sos/emergency-type/store', 'SOS\ConfigurationController@SOSEmergencyTypeStore')->name('sos.config.SOSEmergencyTypeStore');
    Route::get('sos/emergency-type/index', 'SOS\ConfigurationController@IndexSOSEmergencyTypes')->name('sos.config.IndexSOSEmergencyTypes');
    Route::delete('sos/emergency-type/destroy/{id}', 'SOS\ConfigurationController@SOSEmergencyTypesDestory')->name('sos.config.SOSEmergencyTypesDestory');
    Route::post('sos/emergency-type/update/{id}', 'SOS\ConfigurationController@updateEmergencyTypes')->name('sos.emergencyType.update');

    // SOS Dashboard
    Route::get('sos/dashboard', 'SOS\DashboardController@index')->name('sos.dashboard.index');
    Route::delete('sos/destroy/{id}', 'SOS\DashboardController@destroy')->name('sos.emergency.destroy');
    Route::post('sos/update-status', 'SOS\DashboardController@updateStatus')->name('sos.emergency.update.status');
    Route::get('sos/view-details/{id}', 'SOS\DashboardController@view')->name('sos.emergency.view');
    Route::get('sos/view-team-activity/{id}', 'SOS\DashboardController@viewTeamActivityDetails')->name('sos.viewTeamActivityDetails');
    // Route::get('sos/team-activity-get', 'SOS\DashboardController@viewTeamActivityDetails')->name('sos.viewTeamActivityDetails');
    Route::post('sos/filter-team-activity/{id}', 'SOS\DashboardController@filterTeamActivityDetails')->name('sos.filterTeamActivityDetails');
    Route::get('sos/view-employee-safety-status/{id}', 'SOS\DashboardController@viewEmployeeSafetyDetails')->name('sos.viewEmployeeSafetyDetails');
    Route::post('sos/filter-employee-status/{id}', 'SOS\DashboardController@filterEmployeeSafetyDetails')->name('sos.filterEmployeeSafetyDetails');
    Route::post('sos/update-mass-instruction', 'SOS\DashboardController@updateMassInstruction')->name('sos.updateMassInstruction');
    // Employee live location
    Route::get('sos/employees-live-location/{id}', 'SOS\DashboardController@showMap')->name('sos.showMap');
    Route::post('sos/filter-map-employee-list/{id}', 'SOS\DashboardController@filterMapEmployeeList')->name('sos.filterMapEmployeeList');
    // API route for live employee locations (used for auto-refresh)
    Route::get('sos/all-employee-locations/{id}', 'SOS\DashboardController@getLiveEmployeeLocations')->name('sos.employeeLiveLocations');


    //SOS Module end


    Route::get('report/index', 'ReportController@index')->name('resort.report.index');

    Route::get('report/get-table-columns', 'ReportController@getTableColumns')->name('resort.reports.get-columns');

    Route::get('report/create', 'ReportController@create')->name('reports.create');

    Route::post('report', 'ReportController@store')->name('reports.store');

    Route::get('report/show/{id}', 'ReportController@show')->name('reports.show');
    Route::post('report/fetch-data', 'ReportController@FetchReportData')->name('reports.FetchReportData');

     Route::get('report/export', 'ReportController@export')->name('report.export');


    Route::get('report/edit/{id}', 'ReportController@edit')->name('reports.edit');

    Route::put('report/{report}', 'ReportController@update')->name('resort.report.update');
    Route::post('report/ai-inside', 'ReportController@AiInsideReport')->name('reports.AiInsideReport');


    // Route::delete('Report/{report}', 'ReportController@destroy')->name('report.destroy');

    Route::get('/get/updated-data','WorkforcePlanningDashboardController@getUpdatedData')->name('resort.occupancy.getUpdatedData');

    Route::get('/people/compliance', 'People\Compliances\ComplianceController@index')->name('people.compliance.index');
    Route::get('/people/compliance/list', 'People\Compliances\ComplianceController@list')->name('people.compliance.list');
    Route::get('/people/compliance/run','People\Compliances\ComplianceController@checkCompliance')->name('people.compliance.run');
    Route::get('/people/compliance/dismiss/{id}','People\Compliances\ComplianceController@DismissCompliance')->name('people.compliances.dismiss');
    Route::get('/people/compliance/download','People\Compliances\ComplianceController@download')->name('people.compliance.download');
    Route::get('/people/compliance/test','People\Compliances\ComplianceController@test')->name('people.compliance.test');

    Route::get('/people/Calendar', 'People\Compliances\ComplianceController@Calendar')->name('people.compliance.Calendar');

    // Curd route for Facility Tour Categories Controller name FacilityTourCategoryController
    Route::get('people/onboarding/facility-tour-categories', 'People\Onboarding\FacilityTourCategoryController@index')->name('people.onboarding.facility-tour-categories.index');
    Route::get('people/onboarding/facility-tour-categories/create', 'People\Onboarding\FacilityTourCategoryController@create')->name('people.onboarding.facility-tour-categories.create');
    Route::post('people/onboarding/facility-tour-categories/store', 'People\Onboarding\FacilityTourCategoryController@store')->name('people.onboarding.facility-tour-categories.store');
    Route::get('people/onboarding/facility-tour-categories/view/{id}', 'People\Onboarding\FacilityTourCategoryController@show')->name('people.onboarding.facility-tour-categories.show');
    Route::get('people/onboarding/facility-tour-categories/edit/{id}', 'People\Onboarding\FacilityTourCategoryController@edit')->name('people.onboarding.facility-tour-categories.edit');
    Route::post('people/onboarding/facility-tour-categories/update/{id}', 'People\Onboarding\FacilityTourCategoryController@update')->name('people.onboarding.facility-tour-categories.update');
    Route::delete('people/onboarding/facility-tour-categories/destroy/{id}', 'People\Onboarding\FacilityTourCategoryController@destroy')->name('people.onboarding.facility-tour-categories.destroy');
    Route::post('people/onboarding/facility-tour-categories/image-update', 'People\Onboarding\FacilityTourCategoryController@imageUpdate')->name('people.onboarding.facility-tour-categories.image-update');
    Route::delete('people/onboarding/facility-tour-categories/image-delete', 'People\Onboarding\FacilityTourCategoryController@imageDelete')->name('people.onboarding.facility-tour-categories.image-delete');


    Route::get('resort/menu', 'MasterDashboardController@getMenuData')->name('resort.getMenuData');

    Route::get('resort/search','MasterSearchController@index')->name('resort.search.index');
      Route::get('workforce-planning/dashboard/ai-insights', 'WorkforcePlanningDashboardController@getAiInsights')->name('resort.workforceplan.dashboard.ai-insights');


      Route::get('people/approvel','People\ApprovalController@getApprovedRequests')->name('people.approvel.index');

  });
