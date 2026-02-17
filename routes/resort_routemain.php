<?php
/** Routes without login ***/
Route::prefix('resort')->namespace('Resort')->group(function () {
  Route::get('/',  ['App\Http\Controllers\Resorts\ResortLoginController','showLoginForm'])->name('resort.loginindex');
  Route::post('/do-login', ['App\Http\Controllers\Resorts\ResortLoginController','login'])->name('resort.login');
  Route::get('/AccessDeined',  ['App\Http\Controllers\Resorts\ResortLoginController','AccessDeined'])->name('resort.AccessDeined');

  
  Route::get('/request-password', ['App\Http\Controllers\Resorts\ResortforgotPasswordController','requestPassword'])->name('resort.password.request');
  Route::post('/request-password-submit', ['App\Http\Controllers\Resorts\ResortforgotPasswordController','requestPasswordSubmit'])->name('resort.password.request-submit');
  Route::get('/reset-password/{token}', ['App\Http\Controllers\Resorts\ResortforgotPasswordController','resetPassword'])->name('resort.password.reset');
  Route::post('/reset-password-submit', ['App\Http\Controllers\Resorts\ResortforgotPasswordController','resetPasswordSubmit'])->name('resort.password.reset-submit');
  Route::post('/check-email-exists', ['App\Http\Controllers\Resorts\ResortforgotPasswordController','checkEmailExists'])->name('resort.emailExistForgotPassword');
  Route::get('/permission-denied', ['App\Http\Controllers\Resorts\ResortLoginController','permissionDenied'])->name('resort.permission.denied');

  Route::get('/applicant-form/{id?}', ['App\Http\Controllers\Resorts\ApplicantController','showapplicantForm'])->name('resort.applicantForm');

  //save session draft data for each step
  Route::post('/applicant-form/save-draft', ['App\Http\Controllers\Resorts\ApplicantController','saveDraft'])->name('save.applicantinfo.draft');
  Route::post('/applicant-form/get-draft', ['App\Http\Controllers\Resorts\ApplicantController', 'getDraftStepData'])->name('get.applicantinfo.draft');

  Route::post('/applicant_formStore', ['App\Http\Controllers\Resorts\ApplicantController','applicant_formStore'])->name('resort.applicantFormstore');

  Route::post('/applicant_tempVideoremove', ['App\Http\Controllers\Resorts\ApplicantController','applicant_tempVideoremove'])->name('resort.applicant_tempVideoremove');

  Route::post('/applicant_tempVideoStore', ['App\Http\Controllers\Resorts\ApplicantController','applicant_tempVideoStore'])->name('resort.applicant_tempVideoStore');
  Route::get('/TalentPool', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','TalentPool'])->name('resort.ta.TalentPool');

  Route::post('/getTalentPoolApplicant', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','getTalentPoolGridApplicant'])->name('resort.ta.getTalentPoolApplicant');
  Route::post('/RevertBack', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','RevertBack'])->name('resort.ta.RevertBack');
});

/*** Admin Authenticated Routes ***/
Route::prefix('resort')->middleware(['auth:resort-admin','revalidate','checkResortPermission'])->namespace('Resorts')->group(function () {

    /*** Logout ***/
    Route::get( '/logout', ['App\Http\Controllers\Resorts\ResortLoginController','logout'] )->name('resort.logout');

    /*** Notifications ***/
    Route::middleware('redirectIfNotCorrect.dashboard')->group(function () {
      Route::get('workforce-planning/HR-dashboard', 'WorkforcePlanningDashboardController@hr_dashboard')->name('resort.workforceplan.dashboard');
      Route::get('workforce-planning/HOD-dashboard', 'WorkforcePlanningDashboardController@hod_dashboard')->name('resort.workforceplan.hoddashboard');
      Route::get('workforce-planning/admin-dashboard', 'WorkforcePlanningDashboardController@admin_dashboard')->name('resort.workforceplan.resortadmindashboard');
      
        // Talent Acquisition
        Route::get('talent-acquisition/HR-dashboard', 'TalentAcquisitionDashboardController@hr_dashboard')->name('resort.recruitement.hrdashboard');
        Route::get('talent-acquisition/HOD-dashboard', 'TalentAcquisitionDashboardController@hod_dashboard')->name('resort.recruitement.hoddashboard');
        Route::get('talent-acquisition/admin-dashboard', 'TalentAcquisitionDashboardController@admin_dashboard')->name('resort.recruitement.admindashboard');
        Route::post('ta/GePositionWiseTopAppliants', 'TalentAcquisitionDashboardController@getTopCountriesPositionData')->name('resort.ta.GePositionWiseTopAppliants');
        


        //   Time and Attandance
        Route::get('timeandattendance/Hr-Dashboard', 'TimeandAttendanceDashboardController@HrDashobard')->name('resort.timeandattendance.dashboard');
        Route::get('timeandattendance/HOD-dashboard', 'TimeandAttendanceDashboardController@hod_dashboard')->name('resort.timeandattendance.hoddashboard');
        Route::get('timeandattendance/admin-dashboard', 'TimeandAttendanceDashboardController@admin_dashboard')->name('resort.timeandattendance.admindashboard');

        //Leave Module
        Route::get('Leave/HR-Dashboard', 'Leave\DashboardController@HR_Dashobard')->name('leave.dashboard');
        Route::get('Leave/HOD-Dashboard', 'Leave\DashboardController@hod_dashboard')->name('leave.hoddashboard');
        Route::get('Leave/Admin-Dashboard', 'Leave\DashboardController@admin_dashboard')->name('leave.admindashboard');


         //Accommodation Module
         Route::get('Accommodation/HR-Dashboard', 'Accommodation\AccommodationDashboardController@HR_Dashobard')->name('resort.accommodation.dashboard');
         Route::get('Accommodation/HOD-Dashboard', 'Accommodation\AccommodationDashboardController@Hod_dashboard')->name('resort.accommodation.hoddashboard');
         Route::get('Accommodation/Admin-Dashboard', 'Accommodation\AccommodationDashboardController@Admin_dashboard')->name('resort.accommodation.admindashboard');

         //Payroll Module
         Route::get('Payroll/HR-Dashboard', 'Payroll\DashboardController@HR_Dashobard')->name('payroll.dashboard');
         Route::get('Payroll/Admin-Dashboard', 'Payroll\DashboardController@admin_dashboard')->name('payroll.admindashboard');
         //Performance Module
         Route::get('Performance/HR-Dashboard', 'Performance\PerformanceDashboardController@HR_Dashobard')->name('Performance.Hrdashboard');
         Route::get('Performance/HOD-Dashboard', 'Performance\PerformanceDashboardController@Hod_dashboard')->name('Performance.HODdashboard');
         Route::get('Performance/Admin-Dashboard', 'Performance\PerformanceDashboardController@Admin_dashboard')->name('Performance.admindashboard');
    

            Route::get('GrievanceAndDisciplinary/Admin-Dashboard', 'GrievanceAndDisciplinery\DashboardController@Admin_Dashobard')->name('GrievanceAndDisciplinery.Admindashboard');

         //Learning Module
         Route::get('Learning/HR-Dashboard', 'Learning\DashboardController@HR_Dashobard')->name('learning.hr.dashboard');
         Route::get('Learning/HOD-Dashboard', 'Learning\DashboardController@hod_dashboard')->name('learning.hod.dashboard');
         Route::get('Learning/Admin-Dashboard', 'Learning\DashboardController@admin_dashboard')->name('learning.admin.dashboard');
         Route::get('Learning/Manager-Dashboard', 'Learning\DashboardController@manager_dashboard')->name('learning.manager.dashboard');


        //  GrievanceAndDisciplinery

         Route::get('GrievanceAndDisciplinary/HR-Dashboard', 'GrievanceAndDisciplinery\DashboardController@HR_Dashobard')->name('GrievanceAndDisciplinery.Hrdashboard');
         Route::get('GrievanceAndDisciplinary/Admin-Dashboard', 'GrievanceAndDisciplinery\DashboardController@Admin_Dashobard')->name('GrievanceAndDisciplinery.Admindashboard');

        //  Survey
         Route::get('Survey/HR-Dashboard', 'Survey\DashboardController@HR_Dashobard')->name('Survey.hr.dashboard');
         Route::get('Survey/Admin-Dashboard', 'Survey\DashboardController@Admin_Dashobard')->name('Survey.Admindashboard');



        //  Visa
         Route::get('visa/Admin-Dashboard', 'Visa\DashboardController@Admin_Dashobard')->name('visa.Admindashboard');
         Route::get('visa/HR-Dashboard', 'Visa\DashboardController@HR_Dashobard')->name('visa.hr.dashboard');
         
         
        // File Management 

         Route::get('FileManagment/Admin-Dashboard', 'FileManagment\DashboardController@Admin_Dashobard')->name('FileManagment.Admindashboard');
         
         Route::get('FileManagment/HR-Dashboard', 'FileManagment\DashboardController@HR_Dashobard')->name('FileManagment.hr.dashboard');



          //Incident Module
          Route::get('Incident/HR-Dashboard', 'Incident\DashboardController@HR_Dashobard')->name('incident.hr.dashboard');
          Route::get('Incident/HOD-Dashboard', 'Incident\DashboardController@Hod_dashboard')->name('incident.hod.dashboard');
          Route::get('Incident/Admin-Dashboard', 'Incident\DashboardController@Admin_dashboard')->name('incident.admin.dashboard');

            //People module
            Route::get('/people/dashboard', 'People\DashboardController@HR_Dashobard')->name('people.hr.dashboard');
            Route::get('/people/admin-dashboard', 'People\DashboardController@admin_dashboard')->name('people.admin.dashboard');

        Route::get('/master/HR-Dashboard','MasterDashboardController@hr_dashboard')->name('resort.master.hr_dashboard');
        Route::get('/master/HOD-Dashboard','MasterDashboardController@hod_dashboard')->name('resort.master.hod_dashboard');
        Route::get('/master/GM-Dashboard','MasterDashboardController@gm_dashboard')->name('resort.master.gm_dashboard');
        Route::get('/master/Admin-Dashboard','MasterDashboardController@admin_dashboard')->name('resort.master.admin_dashboard');

    });
      Route::get('/people/exitClearanceStaticstics', 'People\DashboardController@exitClearanceStaticstics')->name('people.exit-interview.staticstics');
      Route::post('talent-acquisition/attandances', 'TalentAcquisitionDashboardController@getAttandanceData')->name('resort.recruitement.getAttandanceData');
      Route::get('talent-acquisition/send/birthday-notification/{emp_id}', 'TalentAcquisitionDashboardController@sendBirthdayNotification')->name('resort.recruitement.send.birthday-notification');
    /** Filled position page */
    Route::get('/workforce-planning/filled-position', 'WorkforcePlanningDashboardController@filledpositions')->name('resort.workforceplan.filledpositions');
    // Listing all filled postions with DataTables
    Route::get('/workforce-planning/get-filled-positions', 'WorkforcePlanningDashboardController@get_filledpositions')->name('workforceplan.filledpositions.data');
    Route::get('/workforce-planning/employee/names', 'WorkforcePlanningDashboardController@getEmployeeNames')->name('workforceplan.employee.names');
    Route::get( '/workforce-planning/get-positions', 'WorkforcePlanningDashboardController@getPositions')->name('resort.manning.positions');

    Route::get( '/workforce-planning/pendingDepartment', 'WorkforcePlanningDashboardController@PendingDeartment')->name('resort.pendingDepartment');
    Route::post( '/workforce-MonthWise/ManningBudgetMonthWise', 'WorkforcePlanningDashboardController@ManningBudgetMonthWise')->name('resort.ManningBudget.MonthWise');

    Route::get( '/UserProfile', ['App\Http\Controllers\Resorts\ResortLoginController','ResortProfile'] )->name('resort.user.profile');
    Route::post( '/UpdateUserProfile', ['App\Http\Controllers\Resorts\ResortLoginController','UpdateResortProfile'] )->name('resort.Updateuser.profile');

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
    Route::post('/manning/manningresponses', 'ManningResponseController@store')->name('manning.responses.store');
    Route::post('/manning/fetchemployees', 'ManningResponseController@fetchEmployees')->name('manning.fetch.employees');
    Route::post('/manning/fetch/currentyeardata', 'ManningResponseController@fetchCurrentYearData')->name('manning.fetch.currentYearData');
    Route::post('/manning/responses/saveDraft', 'ManningResponseController@saveDraft')->name('manning.responses.saveDraft');
    Route::get('/manning/responses/getDraftData/{resortId}/{deptId}/{year}', 'ManningResponseController@getDraft')->name('manning.responses.getDraft');
    Route::post( '/manning/responses/ShowDepartmentWiseBudgetData', 'ManningResponseController@ShowDepartmentWiseBudgetData')->name('resort.department.wise.budget.data');
    Route::put( '/manning/responses/updatebudgetdata/{id}', 'ManningResponseController@updateBudgetData')->name('resort.budget.update');
    Route::put( '/manning/responses/updategrandtotal', 'ManningResponseController@updateParentTotal')->name('resort.budget.updateParentTotal');

    /** Budget **/
    Route::get( '/budget/view-manning','BudgetController@ViewManning')->name('resort.budget.manning');
    Route::get( '/budget/view-budget','BudgetController@ViewBudget')->name('resort.budget.viewbudget');
    Route::get('/resort/budget/view/ajax', 'BudgetController@ajaxViewBudget')->name('budget.view.ajax');

    Route::get( '/budget/consolidated-budget','BudgetController@ConsolidateBudget')->name('resort.budget.consolidatebudget');
    Route::get( '/budget/comparebudget/{id}/{budgetid}','BudgetController@CompareBudget')->name('resort.budget.comparebudget');
    Route::get( '/budget/config','BudgetController@config')->name('resort.budget.config');

    Route::post( '/budget/UploadconfigFiles','BudgetController@UploadconfigFiles')->name('resort.budget.UploadconfigFiles');
    Route::post( '/budget/ResortAllDepartmentWise','BudgetController@UpdateResortBudgetPositionWise')->name('resort.UpdateResortPositionWise');

    Route::get('/budget/cost/', 'BudgetCostController@index')->name('resort.budget.index');
  Route::get( '/budget/cost-list','BudgetCostController@costlist')->name('resort.budget.costlist');
    Route::post('/budget/storecosts', 'BudgetCostController@store_costs')->name('resort.budget.storecost');
    // Inline update for sections
    Route::put('/budget/inlinecostupdate/{id}', 'BudgetCostController@inlinecostUpdate')->name('resort.budget.inlinecostupdate');
    // Delete a sections
    Route::delete('budget/destroysection/{id}', 'BudgetCostController@destroy_costs')->name('resort.budget.destroycost');

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
    Route::post('/store-ImportDatas', ['App\Http\Controllers\Resorts\OccupancyController','ImportDatas'])->name('resort.occupancy.ImportDatas');
    Route::post('/store-bulk-occupancy', ['App\Http\Controllers\Resorts\OccupancyController','storeBulkOccupancy'])->name('resort.bulk-occupancy.store');

    /* End of occupancy */
    /* Employee  */
    Route::get('/get-employeelist', ['App\Http\Controllers\Resorts\EmployeeController','index'])->name('resort.employeelist');
    Route::get('/employee-nationality-data', 'EmployeeController@getEmployeeNationalityData')->name('employee.nationality.data');
    Route::get( '/employee-add', 'EmployeeController@AddEmployee')->name('resort.Add.Employee');

    Route::post('/ImportEmployee', ['App\Http\Controllers\Resorts\EmployeeController','ImportEmployee'])->name('resort.Depat_Position_Emp_Import');
    Route::get( '/Export-employee', 'EmployeeController@exportRelatedDepartment')->name('resort.export.Employee');

    /* salary */
    Route::get('/get-employee-increment-details', 'SalaryIncrementController@getIncrementDetails')->name('employee.salaryincrement.get');
    Route::post('/save-salary-increment', 'SalaryIncrementController@saveSalaryIncrement')->name('employee.salaryincrement.save');
    Route::post('/save-bulk-salary-increment', 'SalaryIncrementController@saveBulkSalaryIncrement')->name('employee.bulksalaryincrement.save');

    Route::get( '/ExportBudget','ConsolidateBudgetController@ExportBudget')->name('resort.budget.GetConsolidateFile');
    Route::get('/resorts/{resortId}/budget/consolidated', 'BudgetController@viewConsolidatedBudget')
    ->name('resort.budget.viewconsolidated');

    // Route::get('/get-HodEmployeelist', ['App\Http\Controllers\Resorts\EmployeeController','HodEmployeelist'])->name('resort.employeelist');

    /* end of employee */
    /* Resort Internal Page Permission */
    Route::get( '/get-Permissionpage', ['App\Http\Controllers\Resorts\ResortInternalPermission','Permissionpage'])->name('resort.Page.Permission');
    Route::post( '/resorts-update_internal_permissions', ['App\Http\Controllers\Resorts\ResortInternalPermission','UpdateInternalPermissions'])->name('resorts.update_internal_permissions');
    Route::post( '/resorts-searchPermissions', ['App\Http\Controllers\Resorts\ResortInternalPermission','SearchPermissions'])->name('resorts.searchPermissions');
    // resort internal page permission end

    // others
    Route::post( '/get-ResortDivision', ['App\Http\Controllers\Resorts\ResortInternalPermission','GetDivisionWiseDepartment'])->name('resort.get.ResortDivision');

    // others
    Route::post( '/get-position', ['App\Http\Controllers\Resorts\ResortInternalPermission','GetDepartmentWisePosition'])->name('resort.get.position');
    Route::post( '/get-InternalPermissiones', ['App\Http\Controllers\Resorts\ResortInternalPermission','InternalPermissiones'])->name('resort.get.InternalPermissiones');
    Route::post('/get-occupancy-data', ['App\Http\Controllers\Resorts\OccupancyController', 'getOccupancyData'])->name('occupancy.getData');

    // Notifications
    Route::post('workforce-planning/requestmanning', 'ResortAllNotificationController@ManningNotification')->name('resort.manning.notification');
    Route::post('workforce-planning/ReminderRequestManning', 'ResortAllNotificationController@ReminderRequestManning')->name('resort.reminder.manning.notification');
    Route::post('workforce-planning/SendToFinance', 'ResortAllNotificationController@SendToFinance')->name('resort.SendToFinance.manning.notification');
    Route::post('workforce-planning/ReviseBudget', 'ResortAllNotificationController@ReviseBudget')->name('resort.ReviseBudget.manning.notification');
    // End of noftications

    // SiteSettings
    Route::get(  '/sitesetting', 'SitesettignsController@index')->name('resort.sitesettings');
    Route::post(   '/sitesetting', 'SitesettignsController@UpdateSiteSettings')->name('resort.updatesitesettings');
    Route::post('/sitesetting/Currencyupdate','SitesettignsController@UpdateCurrency')->name('sitesetting.UpdateCurrency');

    Route::post('update/resort-menutype', 'SitesettignsController@UpdateResortMenuType')->name('resort.update.menu-type');
    // End
    Route::post('/getYearBasePositions','WorkforcePlanningDashboardController@GetYearBasePositions')->name('hod.getYearBasePositions');
    Route::get('/get-currency-rates/{resortId}', 'SitesettignsController@getCurrencyRates')->name('getCurrencyRates');;

    //2. Talent Acquisition Module

    Route::get( '/talent-acquisition/view-vacancies', 'TalentAcquisition\VacancyController@index')->name('resort.vacancies.index');
    Route::get( '/talent-acquisition/add-vacancies', 'TalentAcquisition\VacancyController@create')->name('resort.vacancies.create');
    Route::post( '/talent-acquisition/store-vacancies', 'TalentAcquisition\VacancyController@store')->name('resort.vacancies.store');
    Route::get('/talent-acquisition/get-rank', 'TalentAcquisition\VacancyController@getRank')->name('resort.getRank');
    // Route::get('/talent-acquisition/FreshApplicant', 'TalentAcquisition\VacancyController@GetAllApplicatioWiseVacancies')->name('');

    Route::get('/talent-acquisition/Vacancies-GridView', 'TalentAcquisition\VacancyController@GridViewData')->name('ta.vacancies.GirdData');

    Route::get('/talent-acquisition/GetapplicantList', 'TalentAcquisition\VacancyController@GetAllApplicatioWiseVacancies')->name('resort.vacancies.GetapplicantList');

    Route::get( 'ta/Viweallvacancies', ['App\Http\Controllers\Resorts\TalentAcquisition\VacancyController','GetViewVacancies'])->name('resort.ta.ViewVacancies');
    Route::get( 'ta/allvacancies', ['App\Http\Controllers\Resorts\TalentAcquisition\VacancyController','GetAllVacancies'])->name('resort.ta.GetAllVacancies');
    Route::post( '/talent-acquisition/get-vacancy-status', 'TalentAcquisition\VacancyController@getVacancyStatus')->name('resort.vacancies.getstatus');

    Route::get( 'ta/alltodolist', ['App\Http\Controllers\Resorts\TalentAcquisition\VacancyController','ViewAllToDo'])->name('resort.ta.alltodolist');
    Route::get( 'ta/shortlistedapplicants', ['App\Http\Controllers\Resorts\TalentAcquisition\VacancyController','shortlistedapplicantsShareLink'])->name('resort.ta.shortlistedapplicants');
    Route::get( 'ta/shortlisted/{id}', ['App\Http\Controllers\Resorts\TalentAcquisition\VacancyController','shortlisted'])->name('resort.ta.shortlisted');
    Route::get( 'ta/shortlisted', ['App\Http\Controllers\Resorts\TalentAcquisition\VacancyController','shortlisted'])->name('resort.ta.shortlistedIndex');
    Route::get( 'ta/UpcomingApplicants', ['App\Http\Controllers\Resorts\TalentAcquisition\VacancyController','UpcomingApplicants'])->name('resort.ta.UpcomingApplicants');
    Route::post( '/get-ta-AddInterViewLink', ['App\Http\Controllers\Resorts\TalentAcquisition\VacancyController','AddInterViewLink'])->name('resort.ta.AddInterViewLink');

    Route::get( '/get-ta-configration', ['App\Http\Controllers\Resorts\TalentAcquisition\ConfigController','index'])->name('resort.ta.configration');

    Route::post( '/get-ta-finalApproval', ['App\Http\Controllers\Resorts\TalentAcquisition\ConfigController','FianlApproval'])->name('resort.ta.FinalApproval');

    // Questionnaire Controller
    Route::get( '/get-ta-Questionnaire', ['App\Http\Controllers\Resorts\TalentAcquisition\QuestionnaireController','index'])->name('resort.ta.Questionnaire');

    Route::get( '/get-ta-add-Questionnaire', ['App\Http\Controllers\Resorts\TalentAcquisition\QuestionnaireController','create'])->name('resort.ta.add.Questionnaire');
    Route::post('/resort-ta-store.',['App\Http\Controllers\Resorts\TalentAcquisition\QuestionnaireController','store'])->name('resort.ta.store.Questionnaire');
    Route::get('/resort-ta-getResortWiseQuestion', ['App\Http\Controllers\Resorts\TalentAcquisition\QuestionnaireController','getResortWiseQuestion'])->name('resort.ta.getResortWiseQuestion');
    Route::post('/resort-ta/inlinequestionUpdate', ['App\Http\Controllers\Resorts\TalentAcquisition\QuestionnaireController','update'])->name('resort.ta.update.Questionnaire');

    Route::post('resort-ta/destroyQuestions/', ['App\Http\Controllers\Resorts\TalentAcquisition\QuestionnaireController','destroy'])->name('resort.ta.destroyQuestions');
    Route::get( '/ta/edit-Questions/{id}/edit', ['App\Http\Controllers\Resorts\TalentAcquisition\QuestionnaireController','edit'])->name('resort.ta.Questions.edit');

    Route::post('resort-ta/PositionSections/', ['App\Http\Controllers\Resorts\TalentAcquisition\ConfigController','PositionSections'])->name('resort.ta.PositionSections');
    Route::post('resort-ta/addagent/', ['App\Http\Controllers\Resorts\TalentAcquisition\ConfigController','AddTicketAgent'])->name('resort.ta.add.Agent');
    Route::put('/resort-ta/inline-agent-update/{id}', ['App\Http\Controllers\Resorts\TalentAcquisition\ConfigController','inlineUpdateAgent'])->name('resort.ta.inlineUpdateAgent');

    Route::get( '/get-ta-getResortAgentTicketList', ['App\Http\Controllers\Resorts\TalentAcquisition\ConfigController','GetAgentTicketList'])->name('resort.ta.getResortAgentTicketList');
    Route::delete('/ta/destroyAgentList/{id}', ['App\Http\Controllers\Resorts\TalentAcquisition\ConfigController','DestroyAgentList'])->name('resort.ta.destroyAgentList');

    //Hiring source
    Route::post('resort-ta/add-hiring-source/', ['App\Http\Controllers\Resorts\TalentAcquisition\ConfigController','AddHiringSource'])->name('resort.ta.add.source');
    Route::get( 'resort-ta/get-hiring-source/', ['App\Http\Controllers\Resorts\TalentAcquisition\ConfigController','GetHiringSource'])->name('resort.ta.get.source');
    Route::delete('resort-ta/delete-hiring-source/{id}', ['App\Http\Controllers\Resorts\TalentAcquisition\ConfigController','DestroyHiringSource'])->name('resort.ta.delete.source');

    Route::post('resort-ta/terms-and-conditions/store', ['App\Http\Controllers\Resorts\TalentAcquisition\ConfigController','storeOrUpdateTC'])->name('resort.ta.termscondition.storeOrUpdate');

    Route::post('/ta/jobdescription', ['App\Http\Controllers\Resorts\TalentAcquisition\JobDescriptionController','store'])->name('resort.ta.jobdescription.store');

    Route::get( '/ta/getjobdescription', ['App\Http\Controllers\Resorts\TalentAcquisition\JobDescriptionController','index'])->name('resort.ta.jobdescription.index');
    Route::get( '/ta/GetJobDescList', ['App\Http\Controllers\Resorts\TalentAcquisition\JobDescriptionController','GetJobDescList'])->name('resort.ta.jobdescription.GetJobDescList');
    Route::get( '/ta/job-description/{id}/edit', ['App\Http\Controllers\Resorts\TalentAcquisition\JobDescriptionController','edit'])->name('resort.ta.jobdescription.edit');

    Route::put( '/ta/job-descriptionupdate/{id}/', ['App\Http\Controllers\Resorts\TalentAcquisition\JobDescriptionController','update'])->name('resort.ta.jobdescription.update');
    Route::delete( '/ta/job-descriptiondestroy/{id}/', ['App\Http\Controllers\Resorts\TalentAcquisition\JobDescriptionController','destroy'])->name('resort.ta.jobdescription.destroy');
    Route::get( '/ta/job-descriptionshow/{id}/', ['App\Http\Controllers\Resorts\TalentAcquisition\JobDescriptionController','show'])->name('resort.ta.jobdescription.show');
    Route::get( '/ta/job-descriptiondownload/{slug}/', ['App\Http\Controllers\Resorts\TalentAcquisition\JobDescriptionController','download'])->name('resort.ta.jobdescription.download');
    // Job Advaertisment
    Route::post('/ta/jobadvertismentupload', ['App\Http\Controllers\Resorts\TalentAcquisition\JobAdvertisementController','StoreJobAvd'])->name('resort.ta.jobadvertisment.upload');

    Route::post('/ta/jobadvertismentupload', ['App\Http\Controllers\Resorts\TalentAcquisition\JobAdvertisementController','StoreJobAvd'])->name('resort.ta.jobadvertisment.upload');

    Route::Post('/ta/HoldVcancies', ['App\Http\Controllers\Resorts\TalentAcquisition\ConfigController','TaHoldVcanciesNotification'])->name('resort.ta.HiringNotification');

    Route::Post('/ta/RejectionVcancies', ['App\Http\Controllers\Resorts\TalentAcquisition\ConfigController','TaRejectionVcanciesNotification'])->name('resort.ta.RejectionVcancies');

    Route::Post('/ta/ApprovedVcancies', ['App\Http\Controllers\Resorts\TalentAcquisition\ConfigController','TaApprovedVcanciesNotification'])->name('resort.ta.ApprovedVcancies');

    Route::Post('/ta/GenrateAdvLink', ['App\Http\Controllers\Resorts\TalentAcquisition\JobAdvertisementController','GenrateAdvLink'])->name('resort.ta.GenrateAdvLink');

    // show the Applicants
    Route::get('/ta/applicants/{id}', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','VacnacyWiseApplicants'])->name('resort.ta.Applicants');
    Route::get('/talent-acquisition/FreshApplicant', 'TalentAcquisition\VacancyController@GetAllApplicatioWiseVacancies')->name('resort.vacancies.FreshApplicant');

    Route::get('/ta/apresort.vacancies.FreshApplicantplicants', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','GetVacnacyWiseApplicants'])->name('resort.ta.getApplicant');
    Route::Post('/ta/ApplicantNote', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','ApplicantNote'])->name('resort.ta.ApplicantNote');

    Route::post('/ta/Applicantstatus', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','ApplicantWiseStatus'])->name('resort.ta.ApplicantWiseStatus');
    Route::get('/ta/TaUserApplicantsSideBar/{id}', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','TaUserApplicantsSideBar'])->name('resort.ta.TaUserApplicantsSideBar');

    Route::post('/ta/getApplicantWiseGridWise', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','getApplicantWiseGridWise'])->name('resort.ta.getApplicantWiseGridWise');
    Route::get('/ta/getApplicantWiseNotes/{id}', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','getApplicantWiseNotes'])->name('resort.ta.getApplicantWiseNotes');
    Route::get('/ta/SortlistedApplicants/{id}', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','SortlistedApplicants'])->name('resort.ta.SortlistedApplicants');
    Route::post('/ta/InterviewRequest', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','InterviewRequest'])->name('resort.ta.InterviewRequest');

    Route::post('/ta/GetSlots', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','ApplicantTimeZoneget'])->name('resort.ta.ApplicantTimeZoneget');
    Route::post('/ta/ApprovedOrSortApplicantWiseStatus', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','ApprovedOrSortApplicantWiseStatus'])->name('resort.ta.ApprovedOrSortApplicantWiseStatus');
    Route::post('/ta/RoundWiseForm', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','RoundWiseForm'])->name('resort.ta.RoundWiseForm');
    Route::post('/ta/destoryApplicant', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','destoryApplicant'])->name('resort.ta.destoryApplicant');
    Route::post('/ta/GetDateclickWiseUpcomingInterview', ['App\Http\Controllers\Resorts\TalentAcquisition\ApplicantsController','GetDateclickWiseUpcomingInterview'])->name('resort.ta.GetDateclickWiseUpcomingInterview');

    // Email Tamplate start

    Route::get('/ta/emailtemplates', ['App\Http\Controllers\Resorts\TalentAcquisition\TaEmailTemplateController','index'])->name('resort.ta.emailtemplates');
    Route::get('/ta/GetEmailTemplate/{id}', ['App\Http\Controllers\Resorts\TalentAcquisition\TaEmailTemplateController','Show'])->name('resort.ta.EmailTemplate');
    Route::put('/ta/storeEmailTemplate/{id}', ['App\Http\Controllers\Resorts\TalentAcquisition\TaEmailTemplateController','Store'])->name('resort.ta.StoreEmailTemplate');

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
    Route::post( '/topHiringSources', 'TalentAcquisitionDashboardController@topHiringSources')->name('resort.ta.topHiringSources');
    Route::get( '/get-ta-offline-interview', ['App\Http\Controllers\Resorts\TalentAcquisition\OfflineInterviewController','create'])->name('offline-interview.create');

    //End of Talent Acquisition

    // Time and Attendance Dashboard
    Route::get('/timeandattendance/configration', 'TimeAndAttendance\ConfigrationController@index')->name('resort.timeandattendance.Configration');
    Route::post( '/timeandattendance//ShiftSotre', 'TimeAndAttendance\ConfigrationController@ShiftStore')->name('resort.timeandattendance.ShiftSotre');
    Route::post( '/timeandattendance/removeshift', 'TimeAndAttendance\ConfigrationController@removeshift')->name('resort.timeandattendance.removeshift');
    Route::post( '/timeandattendance/GeoFencing', 'TimeAndAttendance\ConfigrationController@GeoLocation')->name('resort.timeandattendance.GeoFencing');
    Route::post( '/timeandattendance/ResortHoliday', 'TimeAndAttendance\ConfigrationController@ResortHoliday')->name('resort.timeandattendance.ResortHoliday');
    Route::get('/timeandattendance/publicholidaylist', 'TimeAndAttendance\ConfigrationController@Publicholidaylist')->name('resort.timeandattendance.publicholidaylist');
    Route::post( '/timeandattendance/ResortHolidayUpdate', 'TimeAndAttendance\ConfigrationController@ResortHolidayUpdate')->name('resort.timeandattendance.ResortHolidayUpdate');
    Route::post( '/timeandattendance/ResortHolidaydelete', 'TimeAndAttendance\ConfigrationController@ResortHolidaydelete')->name('resort.timeandattendance.ResortHolidaydelete');
    Route::post( '/timeandattendance/HolidayfileUpload', 'TimeAndAttendance\ConfigrationController@HolidayfileUpload')->name('resort.timeandattendance.HolidayfileUpload');
    Route::post('/timeandattendance/color-themes/save', 'TimeAndAttendance\ConfigrationController@saveColorThemes')->name('colorThemes.save');
    Route::post('/timeandattendance/color-themes/delete', 'TimeAndAttendance\ConfigrationController@delete')->name('colorThemes.delete');
    Route::post('/attendance/export', 'TimeAndAttendance\ConfigrationController@exportAttendance')->name('attendance.export');
    Route::get('/timeandattendance/CreateDutyRoster', 'TimeAndAttendance\DutyRosterController@CreateDutyRoster')->name('resort.timeandattendance.CreateDutyRoster');
    Route::post('/timeandattendance/DutyRosterandLeave', 'TimeAndAttendance\DutyRosterController@DutyRosterandLeave')->name('resort.timeandattendance.DutyRosterandLeave');
    Route::post('/timeandattendance/StoreDutyRoster', 'TimeAndAttendance\DutyRosterController@StoreDutyRoster')->name('resort.timeandattendance.StoreDutyRoster');
    Route::post('/timeandattendance/UpdateDutyRoster', 'TimeAndAttendance\DutyRosterController@UpdateDutyRoster')->name('resort.timeandattendance.UpdateDutyRoster');

    Route::get('/timeandattendance/DutyRosterdashboardTable', 'TimeandAttendanceDashboardController@HodDutyRosterdashboardTable')->name('resort.timeandattendance.DutyRosterdashboardTable');
    Route::get('/timeandattendance/MonthOverTimeChart/{date}', 'TimeandAttendanceDashboardController@MonthOverTimeChart')->name('resort.timeandattendance.MonthOverTimeChart');
    Route::get('/timeandattendance/GetYearWiseAttandanceData/{year}/{date}', 'TimeandAttendanceDashboardController@GetYearWiseAttandanceData')->name('resort.timeandattendance.GetYearWiseAttandanceData');
    Route::post('/timeandattendance/DutyRosterSearch', 'TimeAndAttendance\DutyRosterController@DutyRosterSearch')->name('resort.timeandattendance.DutyRosterSearch');
    Route::get('/timeandattendance/LocationHistory', 'TimeAndAttendance\DutyRosterController@LocationHistory')->name('resort.timeandattendance.LocationHistory');
    Route::get('/timeandattendance/Overtime', 'TimeAndAttendance\DutyRosterController@OverTime')->name('resort.timeandattendance.OverTime');
    Route::get('/timeandattendance/OverTimeFilter', 'TimeAndAttendance\DutyRosterController@OverTimeFilter')->name('resort.timeandattendance.OverTimeFilter');
    Route::post('/timeandattendance/StoreOverTime', 'TimeAndAttendance\DutyRosterController@StoreOverTime')->name('resort.timeandattendance.StoreOverTime');
    Route::get('/timeandattendance/employee', 'TimeAndAttendance\EmployeeController@index')->name('resort.timeandattendance.employee');
    Route::get('/timeandattendance/SearchEmployeegird', 'TimeAndAttendance\EmployeeController@SearchEmployeegird')->name('resort.timeandattendance.SearchEmployeegird');
    Route::get('/timeandattendance/EmployeeList', 'TimeAndAttendance\EmployeeController@EmployeeList')->name('resort.timeandattendance.EmployeeList');
    Route::get('/timeandattendance/employeedetails/{id}', 'TimeAndAttendance\EmployeeController@EmployeeDetails')->name('resort.timeandattendance.employee.details');
    Route::post('/timeandattendance/updateattandancehistory', 'TimeAndAttendance\EmployeeController@HistoryUpdate')->name('resort.timeandattendance.updateattandancehistory');
    Route::get('/timeandattendance/AttandanceHisotry/{id}', 'TimeAndAttendance\EmployeeController@AttandanceHisotry')->name('resort.timeandattendance.employee.AttandanceHisotry');


    Route::get('/timeandattendance/AttandanceRegister', 'TimeAndAttendance\AttandanceRegisterController@Index')->name('resort.timeandattendance.AttandanceRegister');
    Route::get('/timeandattendance/todolist', 'TimeandAttendanceDashboardController@Todolist')->name('resort.timeandattendance.todolist');
    Route::post('/timeandattendance/OTStatusUpdate', 'TimeAndAttendance\EmployeeController@OTStatusUpdate')->name('resort.timeandattendance.OTStatusUpdate');
    Route::post('/timeandattendance/EmpDetailsPrint', 'TimeAndAttendance\EmployeeController@EmpDetailsPrint')->name('resort.timeandattendance.EmpDetailsPrint');


    // Hr Dashboard
    Route::get('/timeandattendance/HrDutyRosterdashboardTable', 'TimeandAttendanceDashboardController@HrDutyRosterdashboardTable')->name('resort.timeandattendance.HrDutyRosterdashboardTable');
    Route::get('/timeandattendance/HRMonthOverTimeChart/{Dept_id}/{date}', 'TimeandAttendanceDashboardController@HRMonthOverTimeChart')->name('resort.timeandattendance.HRMonthOverTimeChart');
    Route::get('/timeandattendance/GetYearHrWiseAttandanceData/{Year}/{Dept_id}/{date}', 'TimeandAttendanceDashboardController@GetYearHrWiseAttandanceData')->name('resort.timeandattendance.GetYearHrWiseAttandanceData');
    Route::get('/timeandattendance/HrDashboardCount/{date}', 'TimeandAttendanceDashboardController@HrDashboardCount')->name('resort.timeandattendance.HrDashboardCount');
    Route::get('/timeandattendance/HodDashboardCount/{date}', 'TimeandAttendanceDashboardController@HodDashboardCount')->name('resort.timeandattendance.HodDashboardCount');

    Route::post('/timeandattendance/CheckoutTimeMissing', 'TimeAndAttendance\AttandanceRegisterController@CheckoutTimeMissing')->name('resort.timeandattendance.CheckoutTimeMissing');
    Route::get('/timeandattendance/ResigterRosterSearch', 'TimeAndAttendance\AttandanceRegisterController@ResigterRosterSearch')->name('resort.timeandattendance.ResigterRosterSearch');
    Route::post('/timeandattendance/ImportAttandance', 'TimeAndAttendance\AttandanceRegisterController@ImportAttandance')->name('resort.timeandattendance.ImportAttandance');




    //end of time Attendance Module

   //Leave Module Start
    //Leave Module Start
    Route::get('/Leave/Configration', 'Leave\ConfigController@index')->name('leave.configration');
    Route::post('/leaves/category/store', 'Leave\ConfigController@store_leaves_category')->name('leaves.category.store');
    Route::post('/leaves/categories/update/{id}', 'Leave\ConfigController@update_leaves_category')->name('leaves.category.update');
    Route::delete('/leaves/categories/delete/{id}', 'Leave\ConfigController@delete_leaves_category')->name('leaves.category.delete');
    Route::post('/get-eligible-leaves', 'Leave\ConfigController@getEligibleLeaves')->name('leaves.getEligible');

    Route::get('/upcoming-holidays', 'Leave\DashboardController@get_upcomimg_holidays')->name('resort.upcomingholiday.list');
    Route::get('/upcoming-birthdays', 'Leave\DashboardController@getUpcomingBirthdays')->name('resort.upcomingBirthdays');
    Route::get('/upcoming-birthdays/list', 'Leave\DashboardController@getUpcomingBirthdaysList')->name('resort.upcomingBirthdays.list');
    Route::post('/send-birthday-notification', 'Leave\DashboardController@sendBirthdayNotification')->name('birthday.notification.send');

    Route::post('/submit-transportation-options', 'Leave\ConfigController@submitTransportationOptions')->name('submit.transportation');

    Route::get('/leave/apply','Leave\LeaveController@index')->name('leave.apply');
    Route::get('/leave/request','Leave\LeaveController@request')->name('leave.request');
    Route::get('/leave/filter/grid', 'Leave\LeaveController@filterLeaveGridRequests')->name('leave.filter.grid');

    Route::get('/leave/details/{leave_id}', 'Leave\LeaveController@details')->name('leave.details');
    Route::get('/leave/leave-histroy', 'Leave\LeaveController@getLeaveHistory')->name('leave.history');
    Route::get('/leave-history/download-pdf/{empID}', 'Leave\LeaveController@downloadPdf')->name('leave.history.download-pdf');
    Route::post('/leave/application/store','Leave\LeaveController@store')->name('leave-applications.store');

    Route::get('/get-employees-on-leave', 'Leave\LeaveController@getEmployeesOnLeave')->name('getEmployeesOnLeave');
    Route::get('/get-upcoming-leaves', 'Leave\LeaveController@getUpcomingLeaves')->name('getUpcomingLeaves');
    Route::get('/get-leave-requests', 'Leave\DashboardController@getLeaveRequests')->name('leave-requests.get');

    Route::post('/get-leave-chart', 'Leave\DashboardController@getLeaveChartData')->name('leave-chart-data');
    Route::get('/get-combine-info', 'Leave\LeaveController@getCombineInfo')->name('leaves.combineInfo.get');
    Route::get('/get-used-leave','Leave\LeaveController@getLeaveCount')->name('leaves.used.leave');

    Route::post('/leaves/handle-leave-action', 'Leave\LeaveController@handleLeaveAction')->name('leave.handleAction');
    Route::post('leaves/recommend-alternative-dates','Leave\LeaveController@recommendAlternativeDate')->name('leave.recommendAlternativeDate');
    Route::post('leaves/send-email-to-travel-partner', 'Leave\LeaveController@sendEmailToTravelPartner')
     ->name('send.email.to.travel.partner');

    Route::get('/leave/calendar','Leave\LeaveCalenadarController@index')->name('leave.calendar');
    Route::get('/leaves/calendar-leaves', 'Leave\LeaveCalenadarController@getLeaves')->name('calendar.leaves.get');

    Route::get('/leaves/boarding-pass-requests', 'Leave\BoardingPassController@index')->name('resort.boardingpass.list');
    Route::get('/leaves/boarding-pass-application', 'Leave\BoardingPassController@apply')->name('resort.boardingpass.apply');
    Route::post('/leaves/boarding-pass-submit', 'Leave\BoardingPassController@store')->name('resort.boardingpass.store');
    Route::post('/leaves/handle-pass-action', 'Leave\BoardingPassController@handlePassAction')->name('pass.handleAction');
    Route::get('/boarding-pass/filter/grid', 'Leave\BoardingPassController@filterPassGridRequests')->name('pass.filter.grid');
    Route::get('/leaves/boarding-pass', 'Leave\BoardingPassController@index')->name('resort.boarding-pass'); 
    Route::post('/leaves/boarding-StatusUpdate', 'Leave\BoardingPassController@BoardingPassStatusUpdate')->name('resort.BoardingStatusUpdate'); 


    Route::post('/leave/export', 'Leave\ConfigController@exportLeave')->name('leave.export');
    Route::post('/leave/Import', 'Leave\ConfigController@ImportLeave')->name('leave.import');

    //  Accommodation

    // Dashboard
    Route::get('Accommodation/HR-GetMaintananceRequest', 'Accommodation\AccommodationDashboardController@HR_Dashobard')->name('resort.accommodation.HrGetMaintananceRequest');
    Route::get('Accommodation/Aminities', 'Accommodation\AccommodationDashboardController@Aminities')->name('resort.accommodation.Aminities');
    Route::get('Accommodation/HOD-GetMaintananceRequest', 'Accommodation\AccommodationDashboardController@Hod_dashboard')->name('resort.accommodation.HODGetMaintananceRequest');

    Route::get('Accommodation/HR-HraccomComplitionRequest', 'Accommodation\AccommodationDashboardController@AccomComplitionRequest')->name('resort.accommodation.HraccomComplitionRequest');

    Route::get('Accommodation/HODtableassignTask', 'Accommodation\AccommodationDashboardController@HODtableassignTask')->name('resort.accommodation.HODtableassignTask');

    Route::get('Accommodation/configuration', 'Accommodation\ConfigrationController@index')->name('resort.accommodation.config.index');
    Route::post('Accommodation/InvenptoryCategory', 'Accommodation\ConfigrationController@InvenptoryCategoryStore')->name('resort.accommodation.StoreInvCategory');
    Route::get('Accommodation/InvenotryCatIndex', 'Accommodation\ConfigrationController@InvenotryCatIndex')->name('resort.accommodation.config.InvenotryCatIndex');
    Route::put('/Accommodation/inlineCatUpdate/{id}', 'Accommodation\ConfigrationController@CategoryUpdate')->name('resort.accommodation.inlineCatUpdate');
    Route::delete('/Accommodation/inlineCatdestory/{id}', 'Accommodation\ConfigrationController@Catdestory')->name('resort.accommodation.Catdestory');
    Route::post('Accommodation/checkRoomIsExits', 'Accommodation\ConfigrationController@checkRoomIsExits')->name('resort.accommodation.config.checkRoomIsExits');



    Route::post('Accommodation/AccommodationTypeStore', 'Accommodation\ConfigrationController@AccommodationTypeStore')->name('resort.accommodation.AccommodationTypeStore');
    Route::get('Accommodation/AccommodationIndex', 'Accommodation\ConfigrationController@AccommodationIndex')->name('resort.accommodation.config.AccommodationIndex');
    Route::put('/Accommodation/AccommodationUpdate/{id}', 'Accommodation\ConfigrationController@AccommodationUpdate')->name('resort.accommodation.AccommodationUpdate');
    Route::delete('/Accommodation/inlineAccommodationdestory/{id}', 'Accommodation\ConfigrationController@Accommodationdestory')->name('resort.accommodation.Accommodationdestory');

    Route::post('Accommodation/StoreBuilding', 'Accommodation\ConfigrationController@StoreBuilding')->name('resort.accommodation.StoreBuilding');
    Route::get('Accommodation/BuildingIndex', 'Accommodation\ConfigrationController@BuildingIndex')->name('resort.accommodation.config.BuildingIndex');
    Route::put('/Accommodation/BuildingUpdate/{id}', 'Accommodation\ConfigrationController@BuildingUpdate')->name('resort.accommodation.BuildingUpdate');
    Route::delete('/Accommodation/BuildingDestory/{id}', 'Accommodation\ConfigrationController@BuildingDestory')->name('resort.accommodation.BuildingDestory');

    Route::post('Accommodation/StoreFloorandroom', 'Accommodation\ConfigrationController@StoreFloorandroom')->name('resort.accommodation.StoreFloorandroom');
    Route::get('Accommodation/FloorRoomIndex', 'Accommodation\ConfigrationController@FloorRoomIndex')->name('resort.accommodation.config.FloorRoomIndex');
    Route::put('/Accommodation/FloorAndRoomUpdate/{id}', 'Accommodation\ConfigrationController@FloorAndRoomUpdate')->name('resort.accommodation.FloorAndRoomUpdate');
    Route::get('Accommodation/getFloor', 'Accommodation\ConfigrationController@GetBuildingWiseFloor')->name('resort.accommodation.config.getFloor');
    Route::get('Accommodation/GetFloorWiseRooms', 'Accommodation\ConfigrationController@GetFloorWiseRooms')->name('resort.accommodation.config.GetFloorWiseRooms');
    Route::post('Accommodation/OccupancyThresholdForm', 'Accommodation\ConfigrationController@OccupancyThreshold')->name('resort.accommodation.config.OccupancyThresholdForm');
    Route::post('Accommodation/EscalationDay', 'Accommodation\ConfigrationController@EscalationDay')->name('resort.accommodation.config.EscalationDay');
    Route::get('Accommodation/AccommodationTemplete', 'Accommodation\ConfigrationController@AccommodationTemplete')->name('resort.accommodation.config.AccommodationTemplete');
    Route::get('Accommodation/QuickAssignmentTemplete', 'Accommodation\ConfigrationController@QuickAssignmentTemplete')->name('resort.accommodation.config.QuickAssignmentTemplete');

    Route::post('Accommodation/AccommodationAvailableFileStore', 'Accommodation\ConfigrationController@AccommodationAvailableFileStore')->name('resort.accommodation.AccommodationAvailableFileStore');
    Route::post('Accommodation/QuickAssignmentFileStore', 'Accommodation\ConfigrationController@QuickAssignmentFileStore')->name('resort.accommodation.QuickAssignmentFileStore');

    // Accommodataion Master
    Route::get('Accommodation/AccommodationMaster', 'Accommodation\AccommodationMasterController@index')->name('resort.accommodation.AccommodationMaster');
    Route::get('Accommodation/GetBuildingWiseCollpasedData', 'Accommodation\AccommodationMasterController@GetBuildingWiseCollpasedData')->name('resort.accommodation.GetBuildingWiseCollpasedData');
    Route::get('Accommodation/EmployeeAccommodation', 'Accommodation\AccommodationMasterController@EmployeeAccommodation')->name('resort.accommodation.EmployeeAccommodation');
    Route::get('Accommodation/SearchEmpAccommodationgird', 'Accommodation\AccommodationMasterController@SearchEmpAccommodationgird')->name('resort.accommodation.SearchEmpAccommodationgird');
    Route::get('Accommodation/EmpAccommodationList', 'Accommodation\AccommodationMasterController@EmpAccommodationList')->name('resort.accommodation.EmpAccommodationList');
    Route::get('Accommodation/AccommodationEmployeeDetails/{id}', 'Accommodation\AccommodationMasterController@AccommodationEmployeeDetails')->name('resort.accommodation.AccommodationEmployeeDetails');
    Route::post('Accommodation/AssignMoreAccommodationToEmp', 'Accommodation\AccommodationMasterController@AssignMoreAccommodationToEmp')->name('resort.accommodation.AssignMoreAccommodationToEmp');

    Route::get('Accommodation/AvailableAccommodation', 'Accommodation\AccommodationMasterController@GetBuilidingWiseAvailableAccommodation')->name('resort.accommodation.AvailableAccommodation');
    Route::get('Accommodation/GetBuildingWiseAvailableAccData', 'Accommodation\AccommodationMasterController@GetBuildingWiseAvailableCollpasedData')->name('resort.accommodation.GetBuildingWiseAvailableAccData');

    // Accommodation Manitanance
    Route::get('Accommodation/CreateMaintenanceRequest', 'Accommodation\MaintananceContorller@CreateMaintenance')->name('resort.accommodation.CreateMaintenanceRequest');
    Route::post('Accommodation/CreateMaintenanceRequest', 'Accommodation\MaintananceContorller@CreateMaintenanceRequest')->name('resort.accommodation.CreateMaintenanceRequest');


    Route::get('Accommodation/inventory', 'Accommodation\InventoryController@index')->name('resort.accommodation.inventory');
    Route::post('Accommodation/StoreInventory', 'Accommodation\InventoryController@StoreInventory')->name('resort.accommodation.StoreInventory');
    Route::get('Accommodation/InventoryManagement', 'Accommodation\InventoryController@InventoryManagement')->name('resort.accommodation.InventoryManagement');
    

    Route::get('Accommodation/HistoricalInventory', 'Accommodation\InventoryController@HistoricalInventory')->name('resort.accommodation.HisotricalInvenotry');
    Route::put('/Accommodation/Inventoryupdate/{id}', 'Accommodation\InventoryController@Inventoryupdated')->name('resort.accommodation.Inventoryupdated');



    Route::post('Accommodation/AvailableAccommodationStore', 'Accommodation\ConfigrationController@AvailableAccommodationStore')->name('resort.accommodation.AvailableAccommodationStore');
    Route::get('Accommodation/AssignAccommodation', 'Accommodation\AssignAccommodationController@AssignAccommation')->name('resort.accommodation.AssignAccommation');
    Route::get('Accommodation/BuildingwiseAccommodation', 'Accommodation\AssignAccommodationController@BuildingwiseAccommodation')->name('resort.accommodation.BuildingwiseAccommodation');
    Route::post('Accommodation/GetAssignedBed', 'Accommodation\AssignAccommodationController@GetAssignedBed')->name('resort.accommodation.GetAssignedBed');
    Route::post('Accommodation/AssignAccommodationToEmp', 'Accommodation\AssignAccommodationController@AssignAccommodationToEmp')->name('resort.accommodation.AssignAccommodationToEmp');
    Route::post('Accommodation/GetAccmmodationwiseEmployee', 'Accommodation\AssignAccommodationController@GetAccmmodationwiseEmployee')->name('resort.accommodation.GetAccmmodationwiseEmployee');
    Route::post('Accommodation/MoveToNext', 'Accommodation\AssignAccommodationController@MoveToNext')->name('resort.accommodation.MoveToNext');


    Route::post('Accommodation/HrForwardToHODManitenanceRequest', 'Accommodation\MaintananceContorller@HrForwardToHODManitenanceRequest')->name('resort.accommodation.HrForwardToHODManitenanceRequest');

    Route::post('Accommodation/HrRejeactedRequest', 'Accommodation\MaintananceContorller@HrRejeactedRequest')->name('resort.accommodation.HrRejeactedRequest');
    Route::post('Accommodation/MainRequestForwordToEmp', 'Accommodation\AccommodationDashboardController@MainRequestForwordToEmp')->name('resort.accommodation.MainRequestForwordToEmp');

    Route::get('Accommodation/MaintanaceRequestlist', 'Accommodation\MaintananceContorller@MaintanaceRequestlist')->name('resort.accommodation.MaintanaceRequestlist');
    Route::get('Accommodation/MainRequestOnHold', 'Accommodation\MaintananceContorller@MainRequestOnHold')->name('resort.accommodation.MainRequestOnHold');

    Route::get('Accommodation/MainRequestDetails/{id}', 'Accommodation\MaintananceContorller@MainRequestDetails')->name('resort.accommodation.MainRequestDetails');
    Route::get('Accommodation/HoldMaintanaceRequest', 'Accommodation\MaintananceContorller@HoldMaintanaceRequest')->name('resort.accommodation.HoldMaintanaceRequest');

    Route::post('Accommodation/HodAssignToEmp', 'Accommodation\MaintananceContorller@HodAssignToEmp')->name('resort.accommodation.HodAssignToEmp');
    Route::get('Accommodation/HODMainRequestDetails/{id}', 'Accommodation\MaintananceContorller@HODMainRequestDetails')->name('resort.accommodation.HODMainRequestDetails');

    Route::get('Accommodation/HODHoldMaintanaceRequest/', 'Accommodation\MaintananceContorller@HODHoldMaintanaceRequest')->name('resort.accommodation.HODHoldMaintanaceRequest');
    Route::get('Accommodation/HODMaintanaceRequestlist/', 'Accommodation\MaintananceContorller@HODMaintanaceRequestlist')->name('resort.accommodation.HODMaintanaceRequestlist');
    Route::get('Accommodation/HODAssignTaskList/', 'Accommodation\MaintananceContorller@HODAssignTaskList')->name('resort.accommodation.HODAssignTaskList');

    Route::post('Accommodation/UnassignItem', 'Accommodation\InventoryController@UnassignItem')->name('resort.accommodation.UnassignItem');


    //Mataintanace Event
    Route::get('Accommodation/event', 'Accommodation\EventController@index')->name('resort.accommodation.event');
    Route::get('Accommodation/getClanderData', 'Accommodation\EventController@getClanderData')->name('resort.accommodation.getClanderData');
    Route::get('Accommodation/sidelist', 'Accommodation\EventController@Sidelist')->name('resort.accommodation.event.sidelist');
    // End of Accommodation

   
    //Payroll Module Start
    Route::get('payroll/Configuration', 'Payroll\ConfigController@index')->name('payroll.configration');
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
    Route::post('/payroll/saveEmployees', 'Payroll\PayrollController@saveEmployeesToPayroll')->name('payroll.saveEmployees');
    Route::post('/payroll/saveAttendance', 'Payroll\PayrollController@saveAttendanceToPayroll')->name('payroll.saveAttendance');
    Route::post('/payroll/save-attendance-note', 'Payroll\PayrollController@saveAttendanceNote')->name('payroll.saveAttendanceNote');
    Route::post('/payroll/saveServiceCharges', 'Payroll\PayrollController@saveServiceChargesToPayroll')->name('payroll.saveServiceCharges');
    Route::post('/payroll/saveDeductions', 'Payroll\PayrollController@saveDeductionsToPayroll')->name('payroll.saveDeductions');
    Route::post('/payroll/saveReviews', 'Payroll\PayrollController@saveReviewsToPayroll')->name('payroll.saveReviews');
    Route::post('/payroll/saveSummary', 'Payroll\PayrollController@saveSummaryToPayroll')->name('payroll.saveSummary');

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
    Route::get('Performance/Configuration', 'Performance\ConfigurationController@index')->name('Performance.configuration');
    Route::get('Performance/PerformanceDashboard', 'Performance\PerformanceDashboardController@HR_Dashobard')->name('Performance.Hrdashboard');
    Route::get('Performance/HOD-Dashboard', 'Performance\PerformanceDashboardController@Hod_dashboard')->name('Performance.HODdashboard');


    Route::post('Performance/ReviewTypes', 'Performance\ConfigurationController@ReviewTypes')->name('Performance.config.ReviewTypes');
    Route::put('Performance/ReviewTypes/inlineUpdate/{id}', 'Performance\ConfigurationController@inlineReviewTypesUpdate')->name('Performance.config.ReviewTypes.inlineUpdate');
    Route::delete('Performance/destroyReviewTypes/{id}', 'Performance\ConfigurationController@DestroyReviewTypes')->name('Performance.DestroyReviewTypes');

    // form Templete  Genrate
    Route::post('Performance/PerformanceTemplateForm', 'Performance\ConfigurationController@PerformanceTemplateFormStore')->name('Performance.config.PerformanceTemplateFormStore');
    Route::get('Performance/PerformanceTemplateFormList', 'Performance\ConfigurationController@PerformanceTemplateFormList')->name('Performance.config.PerformanceTemplateFormList');
    Route::post('Performance/PerformanceTemplateFormUpdate', 'Performance\ConfigurationController@PerformanceTemplateFormUpdate')->name('Performance.config.PerformanceTemplateFormUpdate');
    Route::get('Performance/GetPerformanceTemplateForm/{id}', 'Performance\ConfigurationController@GetPerformanceTemplateForm')->name('Performance.config.GetPerformanceTemplateForm');
    Route::delete('Performance/destroyPerformanceTemplateForm/{id}', 'Performance\ConfigurationController@DestroyPerformanceTemplateForm')->name('Performance.DestroyPerformanceTemplateForm');
    Route::post('Performance/ScheduleMeetingEmail', 'Performance\ConfigurationController@ScheduleMeetingEmail')->name('Performance.config.ScheduleMeetingEmail');


    Route::post('Performance/NintyDayPeformanceFormStore', 'Performance\ConfigurationController@NintyDayPeformanceFormStore')->name('Performance.config.NintyDayPeformanceFormStore');
    Route::get('Performance/NitnyPerformanceFormList', 'Performance\ConfigurationController@NitnyPerformanceFormList')->name('Performance.config.NintyPerformanceFormList');
    Route::get('Performance/GetNintyPerformanceForm/{id}', 'Performance\ConfigurationController@GetNintyPerformanceForm')->name('Performance.config.GetNintyPerformanceForm');
    Route::post('Performance/NitnyDayPerformanceFormUpdate', 'Performance\ConfigurationController@NintyDayPerformanceFormUpdate')->name('Performance.config.NintyDayPerformanceFormUpdate');
    Route::delete('Performance/destroyNintyDayPerformanceForm/{id}', 'Performance\ConfigurationController@DestroyNintyDayPerformanceForm')->name('Performance.DestroyNintyDayPerformanceForm');

    Route::get('Performance/ProfessionalFormList', 'Performance\ConfigurationController@ProfessionalFormList')->name('Performance.config.ProfessionalFormList');
    Route::post('Performance/ProfessionalFormStore', 'Performance\ConfigurationController@ProfessionalFormStore')->name('Performance.config.ProfessionalFormStore');
    Route::get('Performance/GetProfessionalForm/{id}', 'Performance\ConfigurationController@GetProfessionalForm')->name('Performance.config.GetProfessionalForm');
    Route::post('Performance/ProfessionalFormUpdate', 'Performance\ConfigurationController@ProfessionalFormUpdate')->name('Performance.config.ProfessionalFormUpdate');
    Route::delete('Performance/destroyProfessionalForm/{id}', 'Performance\ConfigurationController@DestroyProfessionalForm')->name('Performance.DestroyProfessionalForm');


    Route::get('Performance/Meeting', 'Performance\PerformanceMeetingController@index')->name('Performance.Meeting.index');
    Route::post('Performance/ScheduleMeetingEmp', 'Performance\PerformanceMeetingController@SendMeetingLink')->name('Performance.Meeting.ScheduleMeetingEmp');
    Route::post('Performance/GetPerformanceEmp', 'Performance\PerformanceMeetingController@GetPerformanceEmp')->name('Performance.Meeting.GetPerformanceEmp');

    Route::get('Performance/kpicreate', 'Performance\KpiController@create')->name('Performance.kpi.create');
    Route::post('Performance/kpistore', 'Performance\KpiController@PerformanceKpiStore')->name('Performance.kpi.store');
    Route::get('Performance/KpiList', 'Performance\KpiController@KpiList')->name('Performance.kpi.KpiList');

    Route::get('Performance/cycle', 'Performance\CycleController@index')->name('Performance.cycle');
    Route::get('Performance/create', 'Performance\CycleController@create')->name('Performance.create');

    Route::get('Performance/CycleFetchEmployees', 'Performance\CycleController@CycleFetchEmployees')->name('Performance.cycle.FetchEmployees');
    Route::post('Performance/CycleFetchTemplate', 'Performance\CycleController@CycleFetchTemplate')->name('Performance.cycle.Template');
    Route::post('Performance/Cyclestore', 'Performance\CycleController@CycleStore')->name('Performance.cycle.store');
    Route::delete('Performance/destroyCycle/{id}', 'Performance\CycleController@Destroy')->name('Performance.cycle.destory');

    Route::get('Performance/MonltyCheckIn', 'Performance\MonthlyCheckingController@index')->name('Performance.MonltyCheckIn');
    Route::get('Performance/CreateMonthlyCheckIn', 'Performance\MonthlyCheckingController@create')->name('Performance.CreateMonltyCheckIn');
    Route::post('Performance/MonltyCheckInStore', 'Performance\MonthlyCheckingController@MonltyCheckInStore')->name('Performance.MonltyCheckInStore');
    Route::get('Performance/GetEmployeeDetailsMonthlyChecking', 'Performance\MonthlyCheckingController@GetEmployeeDetails')->name('Performance.GetEmployeeDetailsMonthlyChecking');
    Route::get('Performance/MonthlycheckInDetails/{id}', 'Performance\MonthlyCheckingController@GetMonthlyCheckInDetails')->name('Performance.GetMonthlyCheckInDetails');
    Route::get('Performance/MonltyCheckInDetailsPageList', 'Performance\MonthlyCheckingController@MonltyCheckInDetailsPageList')->name('Performance.MonltyCheckInDetailsPageList');

    
    
    // Perfromance Module End


    // People and Relation (Disiciplinery)
    Route::get('GrievanceAndDisciplinary/Configuration', 'GrievanceAndDisciplinery\ConfigurationController@index')->name('GrievanceAndDisciplinery.config.index');

    Route::get('GrievanceAndDisciplinery', 'GrievanceAndDisciplinery\ConfigurationController@IndexDisciplineryCategory')->name('GrievanceAndDisciplinery.config.Disciplineryindex');    
    Route::post('GrievanceAndDisciplinary/storeDisciplineryCategory', 'GrievanceAndDisciplinery\ConfigurationController@StoreDisciplineryCategory')->name('GrievanceAndDisciplinery.config.Disciplinerystore');
    Route::put('GrievanceAndDisciplinary/DisciplineryCategoryinlineUpdate/{id}', 'GrievanceAndDisciplinery\ConfigurationController@DisciplineryCategoryinlineUpdate')->name('GrievanceAndDisciplinery.config.DisciplineryCategoryinlineUpdate');
    Route::delete('GrievanceAndDisciplinary/DisciplineryCategoryDestory/{id}', 'GrievanceAndDisciplinery\ConfigurationController@DisciplineryCategoryDestory')->name('GrievanceAndDisciplinery.config.DisciplineryCategoryDestory');

    Route::get('GrievanceAndDisciplinary/Offenses', 'GrievanceAndDisciplinery\ConfigurationController@IndexOffenses')->name('GrievanceAndDisciplinery.config.Offensesindex');
    Route::post('GrievanceAndDisciplinary/addOffenses', 'GrievanceAndDisciplinery\ConfigurationController@StoreOffenses')->name('GrievanceAndDisciplinery.config.StoreOffenses');
    Route::put('GrievanceAndDisciplinary/OffensesinlineUpdate/{id}', 'GrievanceAndDisciplinery\ConfigurationController@OffensesinlineUpdate')->name('GrievanceAndDisciplinery.config.OffensesinlineUpdate');
    Route::delete('GrievanceAndDisciplinary/OffensesDestory/{id}', 'GrievanceAndDisciplinery\ConfigurationController@OffensesDestory')->name('GrievanceAndDisciplinery.config.OffensesDestory');

    Route::post('GrievanceAndDisciplinary/GetCategoryOffenses', 'GrievanceAndDisciplinery\ConfigurationController@GetCategoryOffenses')->name('GrievanceAndDisciplinery.config.GetCategoryOffenses');

    Route::post('GrievanceAndDisciplinary/ActionStore', 'GrievanceAndDisciplinery\ConfigurationController@ActionStore')->name('GrievanceAndDisciplinery.config.ActionStore');
    Route::get('GrievanceAndDisciplinary/ActionIndex', 'GrievanceAndDisciplinery\ConfigurationController@IndexAction')->name('GrievanceAndDisciplinery.config.IndexAction');
    Route::put('GrievanceAndDisciplinary/ActioninlineUpdate/{id}', 'GrievanceAndDisciplinery\ConfigurationController@ActioninlineUpdate')->name('GrievanceAndDisciplinery.config.ActioninlineUpdate');
    Route::delete('GrievanceAndDisciplinary/ActionDestory/{id}', 'GrievanceAndDisciplinery\ConfigurationController@ActionDestory')->name('GrievanceAndDisciplinery.config.ActionDestory');

    Route::get('GrievanceAndDisciplinary/IndexSeverity', 'GrievanceAndDisciplinery\ConfigurationController@IndexSeverity')->name('GrievanceAndDisciplinery.config.IndexSeverity');
    Route::post('GrievanceAndDisciplinary/SeverityStore', 'GrievanceAndDisciplinery\ConfigurationController@SeverityStore')->name('GrievanceAndDisciplinery.config.SeverityStore');
    Route::put('GrievanceAndDisciplinary/SeveritylineUpdate/{id}', 'GrievanceAndDisciplinery\ConfigurationController@SeverityInlineUpdate')->name('GrievanceAndDisciplinery.config.SeverityinlineUpdate');
    Route::delete('GrievanceAndDisciplinary/SeverityDestory/{id}', 'GrievanceAndDisciplinery\ConfigurationController@SeverityDestory')->name('GrievanceAndDisciplinery.config.SeverityDestory');

    Route::post('GrievanceAndDisciplinary/CodeOfCounduct', 'GrievanceAndDisciplinery\ConfigurationController@CodeOfCounduct')->name('GrievanceAndDisciplinery.config.CodeOfCounduct');
    Route::get('GrievanceAndDisciplinary/IndexCodeOfCounduct', 'GrievanceAndDisciplinery\ConfigurationController@IndexCodeOfCounduct')->name('GrievanceAndDisciplinery.config.IndexCodeOfCounduct');
    Route::post('GrievanceAndDisciplinary/CodeOfCounductUpdate', 'GrievanceAndDisciplinery\ConfigurationController@CodeOfCounductUpdate')->name('GrievanceAndDisciplinery.config.CodeOfCounductUpdate');
    Route::delete('GrievanceAndDisciplinary/CodeOfConductDestory/{id}', 'GrievanceAndDisciplinery\ConfigurationController@CodeOfConductDestory')->name('GrievanceAndDisciplinery.config.CodeOfConductDestory');
    Route::post('GrievanceAndDisciplinary/DiscriplineryApprovalRole', 'GrievanceAndDisciplinery\ConfigurationController@DiscriplineryApprovalRole')->name('GrievanceAndDisciplinery.config.DiscriplineryApprovalRole');

    Route::post('GrievanceAndDisciplinary/LatterTemletestore', 'GrievanceAndDisciplinery\ConfigurationController@LatterTemletestore')->name('GrievanceAndDisciplinery.config.LatterTemletestore');
    Route::get('GrievanceAndDisciplinary/IndexLatterTemplete', 'GrievanceAndDisciplinery\ConfigurationController@IndexLatterTemplete')->name('GrievanceAndDisciplinery.config.IndexLatterTemplete');
    Route::get('GrievanceAndDisciplinary/LatterTempleteEdit/{id}', 'GrievanceAndDisciplinery\ConfigurationController@LatterTempleteEdit')->name('GrievanceAndDisciplinery.config.LatterTempleteEdit');
    Route::delete('GrievanceAndDisciplinary/LatterTempleteDestory/{id}', 'GrievanceAndDisciplinery\ConfigurationController@LatterTempleteDestory')->name('GrievanceAndDisciplinery.config.LatterTempleteDestory');

    Route::post('GrievanceAndDisciplinery/DisciplinaryCommittees', 'GrievanceAndDisciplinery\ConfigurationController@DisciplinaryCommittees')->name('GrievanceAndDisciplinery.config.DisciplinaryCommittees');
    Route::get('GrievanceAndDisciplinary/IndexDisciplinaryCommittees', 'GrievanceAndDisciplinery\ConfigurationController@IndexDisciplinaryCommittees')->name('GrievanceAndDisciplinery.config.IndexDisciplinaryCommittees');
    Route::put('GrievanceAndDisciplinery/CommitteeinlineUpdate/{id}', 'GrievanceAndDisciplinery\ConfigurationController@CommitteeinlineUpdate')->name('GrievanceAndDisciplinery.config.CommitteeinlineUpdate');
    Route::delete('GrievanceAndDisciplinery/DisciplinaryCommitteesDestory/{id}', 'GrievanceAndDisciplinery\ConfigurationController@DisciplinaryCommitteesDestory')->name('GrievanceAndDisciplinery.config.DisciplinaryCommitteesDestory');
    Route::post('GrievanceAndDisciplinary/InvestingHearingTempleteStore', 'GrievanceAndDisciplinery\ConfigurationController@InvestingHearingTempleteStore')->name('GrievanceAndDisciplinery.config.InvestingHearingTempleteStore');


    Route::get('GrievanceAndDisciplinary/CodeOfConduct', 'GrievanceAndDisciplinery\ConfigurationController@CodeOfConduct')->name('GrievanceAndDisciplinery.config.CodeOfConduct');

    Route::post('GrievanceAndDisciplinery/DelegationRulesStore', 'GrievanceAndDisciplinery\ConfigurationController@DelegationRulesStore')->name('GrievanceAndDisciplinery.config.DelegationRulesStore');
    Route::get('GrievanceAndDisciplinery/IndexDisciplinaryDelegationRule', 'GrievanceAndDisciplinery\ConfigurationController@IndexDisciplinaryDelegationRule')->name('GrievanceAndDisciplinery.config.IndexDisciplinaryDelegationRule');
    Route::put('GrievanceAndDisciplinery/DisciplineryDeletgationRuleinlineUpdate/{id}', 'GrievanceAndDisciplinery\ConfigurationController@DisciplineryDeletgationRuleinlineUpdate')->name('GrievanceAndDisciplinery.config.DisciplineryDeletgationRuleinlineUpdate');
    Route::delete('GrievanceAndDisciplinery/DisciplineryDeletegationRuleDestory/{id}', 'GrievanceAndDisciplinery\ConfigurationController@DisciplineryDeletegationRuleDestory')->name('GrievanceAndDisciplinery.config.DisciplineryDeletegationRuleDestory');

    Route::post('GrievanceAndDisciplinary/DisciplineryAppealTypeWiseData', 'GrievanceAndDisciplinery\ConfigurationController@DisciplineryAppealTypeWiseData')->name('GrievanceAndDisciplinery.config.DisciplineryAppealTypeWiseData');
    Route::post('GrievanceAndDisciplinary/DisciplinaryAppealStore', 'GrievanceAndDisciplinery\ConfigurationController@DisciplinaryAppealStore')->name('GrievanceAndDisciplinery.config.DisciplinaryAppealStore');
    Route::post('GrievanceAndDisciplinary/RightToBeAccompanied', 'GrievanceAndDisciplinery\ConfigurationController@RightToBeAccompanied')->name('GrievanceAndDisciplinery.config.RightToBeAccompanied');

    
    
    Route::get('GrievanceAndDisciplinary/IndexGrievanceCategory', 'GrievanceAndDisciplinery\ConfigurationController@IndexGrievanceCategory')->name('GrievanceAndDisciplinery.config.IndexGrievanceCategory');
    Route::post('GrievanceAndDisciplinary/GrievanceCategoryStore', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceCategoryStore')->name('GrievanceAndDisciplinery.config.GrievanceCategoryStore');
    Route::put('GrievanceAndDisciplinary/GrievanceCategorinlineUpdate/{id}', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceCategorinlineUpdate')->name('GrievanceAndDisciplinery.config.GrievanceCategorinlineUpdate');
    Route::delete('GrievanceAndDisciplinary/GrievanceCategoryDestory/{id}', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceCategoryDestory')->name('GrievanceAndDisciplinery.config.GrievanceCategoryDestory');

    Route::post('GrievanceAndDisciplinary/GrievanceSubCategoryStore', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceSubCategoryStore')->name('GrievanceAndDisciplinery.config.GrievanceSubCategoryStore');
    Route::get('GrievanceAndDisciplinary/IndexGrievanceSubCategory', 'GrievanceAndDisciplinery\ConfigurationController@IndexGrievanceSubCategory')->name('GrievanceAndDisciplinery.config.IndexGrievanceSubCategory');
    Route::put('GrievanceAndDisciplinary/GrievanceSubCategorinlineUpdate/{id}', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceSubCategorinlineUpdate')->name('GrievanceAndDisciplinery.config.GrievanceSubCategorinlineUpdate');
    Route::delete('GrievanceAndDisciplinary/GrievanceSubCategoryDestory/{id}', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceSubCategoryDestory')->name('GrievanceAndDisciplinery.config.GrievanceSubCategoryDestory');

    Route::post('GrievanceAndDisciplinary/GrievanceCategoryWiseSubCategoryData', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceCategoryWiseSubCategoryData')->name('GrievanceAndDisciplinery.config.GrievanceCategoryWiseSubCategoryData');
    Route::post('GrievanceAndDisciplinary/GrievanceCatAndSubCategoryStore', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceCatAndSubCategoryStore')->name('GrievanceAndDisciplinery.config.GrievanceCatAndSubCategoryStore');
    Route::get('GrievanceAndDisciplinary/IndexGrievanceCatAndSubCategory', 'GrievanceAndDisciplinery\ConfigurationController@IndexGrievanceCatAndSubCategory')->name('GrievanceAndDisciplinery.config.IndexGrievanceCatAndSubCategory');
    Route::put('GrievanceAndDisciplinary/GrievanceCatAndSubCategoryinlineUpdate/{id}', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceCatAndSubCategoryinlineUpdate')->name('GrievanceAndDisciplinery.config.GrievanceCatAndSubCategoryinlineUpdate');
    Route::delete('GrievanceAndDisciplinary/GrievanceCatAndSubCategoryDestory/{id}', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceCatAndSubCategoryDestory')->name('GrievanceAndDisciplinery.config.GrievanceCatAndSubCategoryDestory');
    
    Route::post('GrievanceAndDisciplinery/GrieDelegationRuleStore', 'GrievanceAndDisciplinery\ConfigurationController@GrieDelegationRuleStore')->name('GrievanceAndDisciplinery.config.GrieDelegationRuleStore');
    Route::get('GrievanceAndDisciplinery/IndexGrievanceDelegationRule', 'GrievanceAndDisciplinery\ConfigurationController@IndexGrievanceDelegationRule')->name('GrievanceAndDisciplinery.config.IndexGrievanceDelegationRule');
    Route::put('GrievanceAndDisciplinery/GrievanceDelegeationRuleinlineUpdate/{id}', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceDelegeationRuleinlineUpdate')->name('GrievanceAndDisciplinery.config.GrievanceDelegeationRuleinlineUpdate');
    Route::delete('GrievanceAndDisciplinery//GrievanceDelegeationRuleDestory/{id}', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceDelegeationRuleDestory')->name('GrievanceAndDisciplinery.config.GrievanceDelegeationRuleDestory');
    Route::post('GrievanceAndDisciplinery/GrievanceRightToBeAccompanied', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceRightToBeAccompanied')->name('GrievanceAndDisciplinery.config.GrievanceRightToBeAccompanied');

    
    Route::post('GrievanceAndDisciplinery/GrievanceNonRetaliation', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceNonRetaliation')->name('GrievanceAndDisciplinery.config.GrievanceNonRetaliation');
    Route::post('GrievanceAndDisciplinery/GrievanceAppealStore', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceAppealStore')->name('GrievanceAndDisciplinery.config.GrievanceAppealStore');
    Route::post('GrievanceAndDisciplinery/GrievanceTempleteStore', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceTempleteStore')->name('GrievanceAndDisciplinery.config.GrievanceTempleteStore');
    Route::get('GrievanceAndDisciplinery/IndexGrievanceTemplete', 'GrievanceAndDisciplinery\ConfigurationController@IndexGrievanceTemplete')->name('GrievanceAndDisciplinery.config.IndexGrievanceTemplete');
    Route::post('GrievanceAndDisciplinery/GrievanceTempleteEdit', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceTempleteEdit')->name('GrievanceAndDisciplinery.config.GrievanceTempleteEdit');
    Route::delete('GrievanceAndDisciplinery//grivanceTempleteDestory/{id}', 'GrievanceAndDisciplinery\ConfigurationController@GrivanceTempleteDestory')->name('GrievanceAndDisciplinery.config.grivanceTempleteDestory');

    Route::post('GrievanceAndDisciplinary/GrivanceResoultionTimeLineStore', 'GrievanceAndDisciplinery\ConfigurationController@GrivanceResoultionTimeLineStore')->name('GrievanceAndDisciplinery.config.GrivanceResoultionTimeLineStore');
    Route::post('GrievanceAndDisciplinary/GrivanceEscaltionStore', 'GrievanceAndDisciplinery\ConfigurationController@GrivanceEscaltionStore')->name('GrievanceAndDisciplinery.config.GrivanceEscaltionStore');
    Route::get('GrievanceAndDisciplinary/IndexGrievanceEscaltion', 'GrievanceAndDisciplinery\ConfigurationController@IndexGrievanceEscaltion')->name('GrievanceAndDisciplinery.config.IndexGrievanceEscaltion');
    Route::put('GrievanceAndDisciplinary/GrievanceEscalationinlineUpdate/{id}', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceEscalationinlineUpdate')->name('GrievanceAndDisciplinery.config.GrievanceEscalationinlineUpdate');
    Route::delete('GrievanceAndDisciplinary/GrievanceEscalationDestory/{id}', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceEscalationDestory')->name('GrievanceAndDisciplinery.config.GrievanceEscalationDestory');

    Route::post('GrievanceAndDisciplinary/GrievanceCommitteeStore', 'GrievanceAndDisciplinery\ConfigurationController@GrievanceCommitteeStore')->name('GrievanceAndDisciplinery.config.GrievanceCommitteeStore');
    Route::get('GrievanceAndDisciplinary/GrivanceCommitteeIndex', 'GrievanceAndDisciplinery\ConfigurationController@GrivanceCommitteeIndex')->name('GrievanceAndDisciplinery.config.GrivanceCommitteeIndex');
    Route::put('GrievanceAndDisciplinary/GrivanceCommitteeinlineUpdate/{id}', 'GrievanceAndDisciplinery\ConfigurationController@GrivanceCommitteeinlineUpdate')->name('GrievanceAndDisciplinery.config.GrivanceCommitteeinlineUpdate');
    Route::delete('GrievanceAndDisciplinary/GrivevanceCommitteesDestory/{id}', 'GrievanceAndDisciplinery\GrivanceController@GrivevanceCommitteesDestory')->name('GrievanceAndDisciplinery.config.GrivevanceCommitteesDestory');


    
    
    Route::get('GrievanceAndDisciplinary/GrivanceIndex', 'GrievanceAndDisciplinery\GrivanceController@GrivanceIndex')->name('GrievanceAndDisciplinery.grivance.GrivanceIndex');
    Route::get('GrievanceAndDisciplinary/CreateGrivance', 'GrievanceAndDisciplinery\GrivanceController@CreateGrivance')->name('GrievanceAndDisciplinery.grivance.CreateGrivance');
    Route::post('GrievanceAndDisciplinary/GrievanceSubmiteStore', 'GrievanceAndDisciplinery\GrivanceController@GrievanceSubmiteStore')->name('GrievanceAndDisciplinery.grivance.GrievanceSubmiteStore');
    Route::get('GrievanceAndDisciplinary/CreGetEmployeeDetailsateGrivance', 'GrievanceAndDisciplinery\GrivanceController@GetEmployeeDetails')->name('GrievanceAndDisciplinery.grivance.GetEmployeeDetails');
    Route::get('GrievanceAndDisciplinary/Investigation/{id}', 'GrievanceAndDisciplinery\GrivanceController@InvestigationReport')->name('GrievanceAndDisciplinery.config.Investigation');
    Route::post('GrievanceAndDisciplinary/InvestigationReportStore', 'GrievanceAndDisciplinery\GrivanceController@InvestigationReportStore')->name('GrievanceAndDisciplinery.grivance.InvestigationReportStore');
    Route::delete('GrievanceAndDisciplinary/Grivance/{id}', 'GrievanceAndDisciplinery\GrivanceController@GrivnanceDestory')->name('GrievanceAndDisciplinery.grivance.DeleteGrivance');


    Route::post('GrievanceAndDisciplinary/RequestIdentity', 'GrievanceAndDisciplinery\GrivanceController@RequestIdentity')->name('GrievanceAndDisciplinery.grivance.RequestIdentity');
    Route::get('GrievanceAndDisciplinary/Investigationinfo/{id}', 'GrievanceAndDisciplinery\GrivanceController@Investigationinfo')->name('GrievanceAndDisciplinery.config.Investigationinfo');
    


    
    Route::post('GrievanceAndDisciplinary/RequestForStatement', 'GrievanceAndDisciplinery\GrivanceController@RequestForStatement')->name('GrievanceAndDisciplinery.grivance.RequestForStatement');
    Route::get('GrievanceAndDisciplinary/GetGrivanceSubCat', 'GrievanceAndDisciplinery\GrivanceController@GetGrivanceSubCat')->name('GrievanceAndDisciplinery.grivance.GetGrivanceSubCat');

    
    Route::post('mark/asread', 'SitesettignsController@NotificationMark')->name('resort.Mark.Notification');
    Route::get('mark/NotificationList', 'SitesettignsController@NotificationList')->name('resort.Mark.NotificationList');
    Route::delete('Notification/NotificationDestory/{id}', 'SitesettignsController@NotificationDestory')->name('resort.Mark.NotificationDestory');


    Route::get('GrievanceAndDisciplinary/DisciplinaryIndex', 'GrievanceAndDisciplinery\DisciplinaryController@DisciplinaryIndex')->name('GrievanceAndDisciplinery.Disciplinary.DisciplinaryIndex');
    Route::get('GrievanceAndDisciplinary/CreateDisciplinary', 'GrievanceAndDisciplinery\DisciplinaryController@CreateDisciplinary')->name('GrievanceAndDisciplinery.Disciplinary.CreateDisciplinary');
    Route::post('GrievanceAndDisciplinary/GetCategoryWiseOffence', 'GrievanceAndDisciplinery\DisciplinaryController@GetCategoryWiseOffence')->name('GrievanceAndDisciplinery.Disciplinary.GetCategoryWiseOffence');
    Route::post('GrievanceAndDisciplinary/KeyPersonnel', 'GrievanceAndDisciplinery\ConfigurationController@KeyPersonnel')->name('GrievanceAndDisciplinery.config.KeyPersonnel');


 
    // Disciplinary Module start
    
      Route::post('GrievanceAndDisciplinary/StoreDisciplinary', 'GrievanceAndDisciplinery\DisciplinaryController@StoreDisciplinary')->name('GrievanceAndDisciplinery.Disciplinary.StoreDisciplinary');
      Route::get('GrievanceAndDisciplinary/DisciplineryInvestigation/{id}', 'GrievanceAndDisciplinery\DisciplinaryController@DisciplineryInvestigation')->name('GrievanceAndDisciplinery.Disciplinary.Investigation');
      Route::get('GrievanceAndDisciplinary/DisciplineryOpenOffence', 'GrievanceAndDisciplinery\DisciplinaryController@DisciplineryOpenOffence')->name('GrievanceAndDisciplinery.DisciplineryOpenOffence');
      Route::post('GrievanceAndDisciplinary/DisciplineryInvestigationReportStore', 'GrievanceAndDisciplinery\DisciplinaryController@InvestigationReportStore')->name('GrievanceAndDisciplinery.Disciplinary.InvestigationReportStore');
      Route::post('GrievanceAndDisciplinary/DisciplineryRequestForStatement', 'GrievanceAndDisciplinery\DisciplinaryController@RequestForStatement')->name('GrievanceAndDisciplinery.Disciplinary.RequestForStatement');


      Route::post('GrievanceAndDisciplinary/DisciplineryEmailTamplate', 'GrievanceAndDisciplinery\ConfigurationController@DisciplineryEmailTamplate')->name('GrievanceAndDisciplinery.Disciplinary.DisciplineryEmailTamplate');
      Route::get('GrievanceAndDisciplinary/DisciplineryEmailTamplateIndex', 'GrievanceAndDisciplinery\ConfigurationController@DisciplineryEmailTamplateIndex')->name('GrievanceAndDisciplinery.Disciplinery.EmailTamplateIndex');
      Route::post('GrievanceAndDisciplinary/GetEmailTamplate', 'GrievanceAndDisciplinery\ConfigurationController@GetEmailTamplate')->name('GrievanceAndDisciplinery.Disciplinary.GetEmailTamplate');
      Route::get('GrievanceAndDisciplinary/HistoryAndLogs', 'GrievanceAndDisciplinery\GrivanceController@HistoryAndLogs')->name('GrievanceAndDisciplinery.Disciplinary.HistoryAndLogs');


      
    //Laerning Module
    Route::get('Learning/Configuration', 'Learning\ConfigurationController@index')->name('learning.configration');
    Route::post('Learning/categories/store', 'Learning\ConfigurationController@saveCategories')->name('learning.categories.save');
    Route::get('Learning/categories/list', 'Learning\ConfigurationController@listCategories')->name('learning.categories.list');
    Route::put('Learning/inlineCategoryUpdate/{id}', 'Learning\ConfigurationController@inlineCategoryUpdate')->name('learning.category.inlineUpdatecategory');
    Route::delete('Learning/destroyCategory/{id}', 'Learning\ConfigurationController@destroyCategory')->name('learning.category.destroy');

    Route::post('Learning/program/store', 'Learning\LearningProgramController@save')->name('learning.programs.save');
    Route::get('Learning/program/index', 'Learning\LearningProgramController@index')->name('learning.programs.index');
    Route::get('Learning/program/list', 'Learning\LearningProgramController@list')->name('learning.programs.list');

    Route::get('Learning/program/calendar', 'Learning\LearningCalendarController@index')->name('learning.calendar.index');
    Route::post('Learning/program/calendar/add-session', 'Learning\LearningCalendarController@store')->name('learning.calendar.save');
    Route::get('/learning-program/details',  'Learning\LearningProgramController@getProgramDetails')->name('learning.program.details');
    Route::get('Learning/get-learning-sessions', 'Learning\LearningCalendarController@getSessions')->name('get.learning.sessions');

    Route::post('Learning/mandatory-program/save', 'Learning\ConfigurationController@save_mandatory_program')->name('mandatory.learning.save');
    Route::get('/get-mandatory-positions', 'Learning\ConfigurationController@getMandatoryPositions')->name('get.mandatory.positions');

    Route::get('Learning/mandatory-program/get', 'Learning\ConfigurationController@get_mandatory_program')->name('mandatory.learning.get');
    Route::get('Learning/mandatory-program/list', 'Learning\ConfigurationController@list_mandatory_program')->name('learning.mandatory-programs.list');

    Route::get('Learning/probationary-program/get', 'Learning\ConfigurationController@get_probationary_program')->name('probationary.learning.get');
    Route::get('Learning/probationary-program/list', 'Learning\ConfigurationController@list_probationary_program')->name('learning.probationary-programs.list');
    Route::post('Learning/probationary-program/save', 'Learning\ConfigurationController@save_probationary_program')->name('probationary.learning.save');

    Route::get('Learning/request/add', 'Learning\LearningController@index')->name('learning.request.add');
    Route::post('Learning/request/store', 'Learning\LearningController@store')->name('learning.request.store');
    Route::get('Learning/request', 'Learning\LearningController@request')->name('learning.request.index');
    Route::get('Learning/request/list', 'Learning\LearningController@list')->name('learning.request.list');
    Route::get('Learning/request/details/{id}', 'Learning\DashboardController@details')->name('learning.request.details');

    Route::post('/Learning/request/updateStatus', 'Learning\LearningController@updateStatus')->name('learning.request.updateStatus');

    Route::get('Learning/schedule', 'Learning\TrainingScheduleController@schedule')->name('learning.schedule');
    Route::post('Learning/schedule', 'Learning\TrainingScheduleController@store')->name('learning.schedule.store');
    Route::get('Learning/schedule/index', 'Learning\TrainingScheduleController@index')->name('learning.schedule.index');
    Route::get('Learning/schedule/list', 'Learning\TrainingScheduleController@list')->name('learning.schedule.list');

    Route::post('Learning/employees/get-dept-wise','Learning\TrainingScheduleController@getEmployeesDeptwise')->name('get.employees.deptwise');
    Route::post('Learning/schedule/inlineUpdate','Learning\TrainingScheduleController@inlineUpdate')->name('learning.schedule.update');
 
    Route::get('Learning/schedule/attendance', 'Learning\AttendanceController@index')->name('learning.schedule.attendance');
    Route::get('Learning/schedule/attendance/list', 'Learning\AttendanceController@list')->name('learning.schedule.attendance.list');
 
   Route::post('Learning/schedule/attendance/mark', 'Learning\AttendanceController@markAttendanceBulk')->name('attendance.mark');
   Route::get('Learning/schedule/attendance/history/{employee_id}', 'Learning\AttendanceController@attendanceHistoryPage')->name('attendance.history.page');
   Route::get('Learning/schedule/attendance/history/data/{employee_id}', 'Learning\AttendanceController@getAttendanceHistoryData')->name('attendance.history.data');
   Route::post('Learning/schedule/attendance/save', 'Learning\AttendanceController@saveAttendance')->name('attendance.save');
 
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

    Route::post('Learning/attendance-parameters/store', 'Learning\ConfigurationController@saveAttendanceParameters')->name('learning.attendance-parameters.save');
    Route::get('Learning/attendance/chart-data', 'Learning\AttendanceController@getAttendanceChartData')->name('learning.attendance.chart-data');
    Route::get('Learning/attendance/participation-chart-data', 'Learning\AttendanceController@getTrainingParticipationData')->name('learning.participation.chart-data');
    Route::get('Learning/get-all-absentees', 'Learning\DashboardController@getAbsentees')->name('learning.absentees.getall');
    Route::get('Learning/get-all-absentees-data', 'Learning\DashboardController@getAllAbsenteesData')->name('learning.absentees.getdata');

    Route::get('Learning/training-history', 'Learning\TrainingScheduleController@history')->name('training.history');


    //  Survey Module
    Route::get('Survey/schedule', 'Survey\SurveyController@index')->name('Survey.index');
    Route::get('Survey/create', 'Survey\SurveyController@index')->name('Survey.create');
    Route::post('/Survey/store', 'Survey\SurveyController@SaveSurvey')->name('Survey.store');
    Route::get('/Survey/view/{id}', 'Survey\SurveyController@SurveyView')->name('Survey.view');
    Route::get('Survey/Surveylist', 'Survey\SurveyController@Surveylist')->name('Survey.Surveylist');
    Route::delete('Survey/SurveyDestory/{id}', 'Survey\SurveyController@SurveyDestory')->name('Survey.Destory');
    Route::post('/Survey/changeStatus', 'Survey\SurveyController@changeStatus')->name('Survey.changeStatus');
    Route::get('/Survey/TotalApplicant/{id}', 'Survey\SurveyController@TotalApplicant')->name('Survey.TotalApplicant');

    Route::get('Survey/CompleteSurvey', 'Survey\SurveyController@CompleteSurvey')->name('Survey.CompleteSurvey');
    Route::get('Survey/DarftSurvey', 'Survey\SurveyController@DarftSurvey')->name('Survey.DarftSurvey');

    Route::post('Survey/notifyToParticipants', 'Survey\SurveyController@NotifyToParticipants')->name('Survey.notifyToParticipants');
    Route::get('Survey/getPendingParticipants', 'Survey\SurveyController@GetPendingParticipants')->name('Survey.getPendingParticipants');

    Route::get('Survey/Getneartodeadlinesurvey', 'Survey\SurveyController@Getneartodeadlinesurvey')->name('Survey.Getneartodeadlinesurvey');
    Route::get('Survey/raise-ticket', 'Support\SupportController@raiseTicket')->name('support.raise.ticket');
    Route::get('/Survey/GetSurveyResults/{id}', 'Survey\SurveyController@GetSurveyResults')->name('Survey.GetSurveyResults');
    Route::get('Survey/SurveyReultExport', 'Survey\SurveyController@SurveyReultExport')->name('Survey.SurveyReultExport');
    Route::get('Survey/DownloadQuestionAndAns/{id}', 'Survey\SurveyController@DownloadQuestionAndAns')->name('Survey.DownloadQuestionAndAns');

      //Support Module
      Route::get('Support/index', 'Support\SupportController@index')->name('support.index');
      Route::get('Support/get-data', 'Support\SupportController@getSupportData')->name('support.get.data');
      Route::post('Support/store', 'Support\SupportController@store')->name('support.store');
  
      Route::get('Support/chat/{support_id}', 'Support\SupportChatController@index')->name('support.chat.system');
      Route::post('Support/chat/send-message', 'Support\SupportChatController@sendMessage')->name('support.chat.sendMessage');
      Route::get('Support/chat/messages/{support_id}', 'Support\SupportChatController@fetchMessages')->name('support.chat.fetchMessage');
 
      Route::get('/Support/view/{support_id}', 'Support\SupportController@view')->name('resort.supports.view');
      Route::get('supports/support-email-replyPage/{support_id}', 'Support\SupportController@replyEMail')->name('resort.email.replypage');

      Route::post('supports/support-email-reply', 'Support\SupportController@sendReply')->name('resort.support-email.reply');

    
      //   File management 
      Route::post('FileManage/CreateFolder', 'FileManagment\FileManageController@CreateFolder')->name('FileManage.CreateFolder');
      Route::get('FileManage/UncategoriesDocuments', 'FileManagment\FileManageController@UnCategoriesDocuments')->name('Categories.Documents');
      Route::get('FileManage/EmployeesFolder',  'FileManagment\FileManageController@EmployeesFolderMangement')->name('Employees.Documents');
      Route::get('FileManage/GetFolder', 'FileManagment\FileManageController@GetFolder')->name('FileManage.GetFolder');
      Route::post('FileManage/StoreFolderFiles', 'FileManagment\FileManageController@StoreFolderFiles')->name('FileManage.StoreFolderFiles');
      Route::post( 'FileManage/FolderList', 'FileManagment\FileManageController@FolderList')->name('FileManage.FolderList');

      Route::post(  'FileManage/GetFolderFiles', 'FileManagment\FileManageController@GetFolderFiles')->name('FileManage.GetFolderFiles');
      Route::post( 'FileManage/RenameFile', 'FileManagment\FileManageController@RenameFile')->name('FileManage.RenameFile');
      Route::post( 'FileManage/ShowthefolderWiseData', 'FileManagment\FileManageController@ShowthefolderWiseData')->name('FileManage.ShowthefolderWiseData');
      Route::get('FileManage/Permission', 'FileManagment\FilePermissionController@index')->name('FileManage.Permission');
      Route::post('FileManage/GetPermissionfile', 'FileManagment\FilePermissionController@GetPermissionfile')->name('FileManage.GetPermissionfile');
      Route::post('FileManage/StoreFilePermission', 'FileManagment\FilePermissionController@StoreFilePermission')->name('FileManage.StoreFilePermission');
      Route::post('FileManage/SearchPermissionfile', 'FileManagment\FilePermissionController@SearchPermissionfile')->name('FileManage.SearchPermissionfile');
      Route::post('FileManage/CreateEmployeeFolder', 'FileManagment\FileManageController@CreateEmployeeFolder')->name('FileManage.CreateEmployeeFolder');
      Route::post('FileManage/MoveFolder', 'FileManagment\FileManageController@MoveFolder')->name('FileManage.MoveFolder');
      Route::post('FileManage/AdvanceSearch', 'FileManagment\FileManageController@AdvanceSearch')->name('FileManage.AdvanceSearch');
      Route::get('FileManage/GetUncategorizedDoc', 'FileManagment\DashboardController@GetUncategorizedDoc')->name('FileManage.GetUncategorizedDoc');
      Route::post('FileManage/Auditlog', 'FileManagment\FileManageController@Auditlog')->name('FileManage.Auditlog');
      
      Route::get('FileManage/AuditLogsDashboardList', 'FileManagment\DashboardController@AuditLogsDashboardList')->name('FileManage.AuditLogsDashboardList');
      Route::get('FileManage/AuditLogsList', 'FileManagment\FileManageController@AuditLogsList')->name('FileManage.AuditLogsList');

      Route::post('FileManage/AuditlogStore', 'FileManagment\FileManageController@AuditlogStore')->name('FileManage.AuditlogStore');

      
   
      Route::get('FileManage/FileVersionDashboardList', 'FileManagment\DashboardController@FileVersionDashboardList')->name('FileManage.FileVersionDashboardList');
      Route::get('FileManage/FileVersionList', 'FileManagment\FileManageController@FileVersionList')->name('FileManage.FileVersionList');



      // Visa

      Route::get('Visa/Configuration', 'Visa\ConfigurationController@index')->name('visa.config');
      Route::post('Visa/nationality', 'Visa\ConfigurationController@NationalityStore')->name('resort.visa.nationality.store');
      Route::get('Visa/NationalityIndex', 'Visa\ConfigurationController@NationalityIndex')->name('resort.visa.NationalityIndex');
      Route::put('/Visa/NationalityUpdate/{id}', 'Visa\ConfigurationController@NationalityUpdate')->name('resort.visa.nationality.update');
      Route::delete('Visa/destroyNationality/{id}', 'Visa\ConfigurationController@Destroy')->name('resort.visa.nationality.destroy');
      Route::get('Visa/Nationalityexport', 'Visa\ConfigurationController@Nationalityexport')->name('visa.natioanlity.export');
      Route::post('Visa/NationalityImport', 'Visa\ConfigurationController@NationalityImport')->name('resort.visa.nationality.Import');
      Route::post('Visa/VisaAmtForm', 'Visa\ConfigurationController@VisaAmtForm')->name('resort.visa.VisaAmtForm');
      Route::post('Visa/DepositRefund', 'Visa\ConfigurationController@DepositRefundStore')->name('resort.visa.DepositRefundStore');
      Route::post('Visa/Reminderalert', 'Visa\ConfigurationController@Reminderalert')->name('resort.visa.Reminderalert');
      Route::get('Visa/DocumentTypeIndex', 'Visa\ConfigurationController@DocumentTypeIndex')->name('resort.visa.DocumentTypeIndex');
      Route::post('Visa/DocumentType', 'Visa\ConfigurationController@DocumentType')->name('resort.visa.DocumentType');
      Route::put('/Visa/DocumentType/{id}', 'Visa\ConfigurationController@DocumentTypeUpdate')->name('resort.visa.DocumentType.update');
      Route::delete('Visa/DocumentTypeDelete/{id}', 'Visa\ConfigurationController@DocumentTypeDelete')->name('resort.visa.DocumentTypeDelete');

      Route::post('Visa/VisaWalletsStore', 'Visa\ConfigurationController@VisaWalletsStore')->name('resort.visa.VisaWalletsStore');
      Route::get('Visa/WalletIndex', 'Visa\ConfigurationController@WalletIndex')->name('resort.visa.WalletIndex');
      Route::put('/Visa/Walletupdate/{id}', 'Visa\ConfigurationController@UpdateWallet')->name('resort.visa.UpdateWallet');
      Route::delete('Visa/DestoryWallet/{id}', 'Visa\ConfigurationController@WalletDestroy')->name('resort.visa.WalletDestroy');

      
      
      Route::post('Visa/document_segmentation', 'Visa\ConfigurationController@DocumentSegmentationStore')->name('resort.visa.DocumentSegmentationStore');
      
      Route::get('Visa/DocumentManagement', 'Visa\DocumentController@index')->name('resort.visa.DocumentManage');
      Route::get('Visa/Xpatsync', 'Visa\DocumentController@Xpatsync')->name('resort.visa.Xpatsync');

      Route::post('Visa/VisaXpactEditAmt', 'Visa\DashboardController@VisaXpactUpdateAmt')->name('resort.visa.VisaXpactEditAmt');

    
      //  Visa Renvew
      Route::get('Visa/renewal', 'Visa\RenewalController@index')->name('resort.visa.RenewalView');
      Route::post('Visa/GetEmployeeDetails', 'Visa\RenewalController@GetEmployeeDetails')->name('resorts.visa.renewal.getEmployeeDetails');
      Route::post('Visa/UploadSeparetFileUsingAi', 'Visa\RenewalController@UploadSeparetFileUsingAi')->name('resorts.visa.renewal.UploadSeparetFileUsingAi');
      Route::post('Visa/UploadQuotaSlot', 'Visa\RenewalController@UploadQuotaSlot')->name('resorts.visa.renewal.UploadQuotaSlot');
      Route::get('Visa/renewal', 'Visa\RenewalController@index')->name('resort.visa.RenewalView');
      Route::get('Visa/OrverviewDashbordExpiry', 'Visa\RenewalController@OrverviewDashbordExpiry')->name('resort.visa.OrverviewDashbordExpiry');


      
      
      // Expat Employee 
      Route::get('Visa/XpactEmployee', 'Visa\XpactEmployeeController@XpactEmpIndex')->name('resort.visa.xpactEmployee');
      Route::get('Visa/XpactEmployee/details/{id}', 'Visa\XpactEmployeeController@XpactEmpDetails')->name('resort.visa.XpactEmpDetails');
      Route::get('Visa/QuotaSlotPendingFee', 'Visa\XpactEmployeeController@XpactEmpBudgetCost')->name('resort.visa.Quota_Slot_PendingFee');
      Route::post('Visa/Quota_Slot_MakrasPaid', 'Visa\XpactEmployeeController@QuotaSlotMakrasPaid')->name('resort.visa.Quota_Slot_MakrasPaid');
      Route::get('Visa/PastTransectionHistory', 'Visa\XpactEmployeeController@PastTransectionHistory')->name('resort.visa.PastTransectionHistory');
      Route::post('Visa/Emloyeewisefileupload', 'Visa\XpactEmployeeController@EmployeeWiseVisaDocumentUpload')->name('resort.visa.EmployeeWiseVisaDocumentUpload');
      Route::get('Visa/XpactEmpFileDownload/{id}', 'Visa\XpactEmployeeController@XpactEmpFileDownload')->name('resort.visa.XpactEmpFileDownload');
    // VerifyDetails

     Route::get('Visa/VerifyDetails','Visa\RenewalController@VerifyDetails')->name('resort.visa.VerifyDetails');
      

      
      

      // Visa Dashboard Routes

      Route::post('Visa/VisaWalletToWalletTransfer', 'Visa\FundTransferController@VisaWalletToWalletTransfer')->name('resort.visa.VisaWalletToWalletTransfer');
      Route::get('Visa/TransectionHistory', 'Visa\FundTransferController@TransectionHistory')->name('resort.visa.TransectionHistory');
      Route::get('Visa/NatioanlityWiseEmployeeDepositAndCount', 'Visa\DashboardController@NatioanlityWiseEmployeeDepositAndCount')->name('resort.visa.NatioanlityWiseEmployeeDepositAndCount');

      Route::get('Visa/NatioanlityWiseEmployeeList', 'Visa\DashboardController@NatioanlityWiseEmployeeList')->name('resort.visa.NatioanlityWiseEmployeeList');

       Route::get('Visa/NatioanlityWiseEmployeeDepositAndCountDetails', 'Visa\DashboardController@NatioanlityWiseEmployeeDepositAndCountDetails')->name('resort.visa.NatioanlityWiseEmployeeDepositAndCountDetails');


      Route::get('Visa/LiabilityBreakDown', 'Visa\DashboardController@LiabilityBreakDown')->name('resort.visa.LiabilityBreakDown');
      Route::get('Visa/NatioanlityWiseEmployeeBreakDownChart', 'Visa\DashboardController@NatioanlityWiseEmployeeBreakDownChart')->name('resort.visa.NatioanlityWiseEmployeeBreakDownChart');
      Route::get('Visa/DasbhoardFlagWiseGetData', 'Visa\DashboardController@DasbhoardFlagWiseGetData')->name('resort.visa.DasbhoardFlagWiseGetData');

      //  Xpact Sync
      Route::get('Visa/XpactSync', 'Visa\FetchDataAiController@index')->name('resort.visa.XpactSync');
      Route::post('Visa/store', 'Visa\FetchDataAiController@store')->name('resorts.visa.xpactsync.store');
      
      //  Visa Expiry page 
      
      Route::get('Visa/Expiry','Visa\ExpiryController@index')->name('resort.visa.Expiry');
    
      //Steps
      Route::post('Visa/PassportExpiry','Visa\RenewalController@PassportExpiry')->name('visa.passport.Checkexpiry');
      Route::post('Visa/CheckCv','Visa\RenewalController@CheckCv')->name('resort.visa.CheckCv');
      Route::post('Visa/Education','Visa\RenewalController@Education')->name('resort.visa.Education');
      Route::post('Visa/CreateEmployee','Visa\DocumentController@CreateEmployee')->name('resort.visa.CreateEmployee');


      
      // Payment Request
      Route::get('Visa/PaymentRequest','Visa\PaymentRequestController@Create')->name('resort.visa.PaymentRequest');
      Route::post('Visa/PaymentRequestSubmit', 'Visa\PaymentRequestController@PaymentRequestSubmit')->name('resort.visa.PaymentRequestSubmit');
      Route::get('Visa/PaymentRequestIndex','Visa\PaymentRequestController@index')->name('resort.visa.PaymentRequestIndex');
      Route::get('Visa/UsingPaymentRequestRenewal/{id}','Visa\PaymentRequestController@UsingPaymentRequestRenewal')->name('resort.visa.UsingPaymentRequestRenewal');
      Route::get('Visa/PaymentRequestDetails/{id}','Visa\PaymentRequestController@PaymentRequestDetails')->name('resort.visa.PaymentRequestDetails');

      Route::post('Visa/PaymentRequestRejected', 'Visa\PaymentRequestController@PaymentRequestRejected')->name('resort.visa.PaymentRequestRejected');
      Route::get('Visa/DownloadPymentRequest/{id}','Visa\PaymentRequestController@DownloadPymentRequest')->name('resort.visa.DownloadPymentRequest');
      Route::get('Visa/PaymentRequestThrowRenewal/{id}/{childid}','Visa\PaymentRequestController@PaymentRequestThrowRenewal')->name('resort.visa.PaymentRequestThrowRenewal');


    // PaymentDepositRequestController
      Route::get('Visa/DepositRequest','Visa\PaymentDepositRequestController@index')->name('resort.visa.DepositRequest');
      Route::post('Visa/DepositRefund', 'Visa\PaymentDepositRequestController@DepositeRefundStore')->name('visa.deposit.refund.store');
      Route::post('Visa/DepositRequestSearch','Visa\PaymentDepositRequestController@DepositRequestSearch')->name('visa.deposit.refund.search');
      Route::get('Visa/DashboardDepositRequest','Visa\PaymentDepositRequestController@DashboardDepositRequest')->name('visa.deposit.DashboardDepositRequest');

      // Liabilities
      Route::get('Visa/Liabilities','Visa\LiabilitiesController@Index')->name('resort.visa.Liabilities');
      Route::get('Visa/FetchTotalEmployees','Visa\LiabilitiesController@FetchTotalEmployees')->name('resort.visa.FetchTotalEmployees');
      
    //Incident Module

    Route::get('Incident/Configuration', 'Incident\ConfigurationController@index')->name('incident.configration');
    Route::get('Incident/Categories/view', 'Incident\ConfigurationController@viewCategories')->name('incident.categories.view');
    Route::get('Incident/Categories/list', 'Incident\ConfigurationController@categoriesList')->name('incident.categories.list');
    Route::post('Incident/Categories/store', 'Incident\ConfigurationController@storeCategories')->name('incident.categories.store');
    Route::put('Incident/Categories/inline-upadte/{id}', 'Incident\ConfigurationController@CategoryinlineUpdate')->name('incident.categories.inlineUpdate');
    Route::delete('Incident/Categories/destroy/{id}', 'Incident\ConfigurationController@categoryDestory')->name('incident.categories.destory');

    Route::get('Incident/Sub-Categories/view', 'Incident\ConfigurationController@viewSubCategories')->name('incident.subcategories.view');
    Route::get('Incident/Sub-Categories/list', 'Incident\ConfigurationController@subcategoriesList')->name('incident.subcategories.list');
    Route::post('Incident/Sub-Categories/store', 'Incident\ConfigurationController@storeSubCategories')->name('incident.subcategories.store');
    Route::put('Incident/Sub-Categories/inline-upadte/{id}', 'Incident\ConfigurationController@subcategoryinlineUpdate')->name('incident.subcategories.inlineUpdate');
    Route::delete('Incident/Sub-Categories/destroy/{id}', 'Incident\ConfigurationController@subcategoryDestory')->name('incident.subcategories.destory');
 
    Route::get('Incident/committees/view', 'Incident\ConfigurationController@viewCommittees')->name('incident.committees.view');
    Route::get('Incident/committees/list', 'Incident\ConfigurationController@committeeList')->name('incident.committees.list');
    Route::post('Incident/committees/store', 'Incident\ConfigurationController@storeCommittees')->name('incident.committees.store');
    Route::put('Incident/committees/inline-upadte/{id}', 'Incident\ConfigurationController@committeeinlineUpdate')->name('incident.committees.inlineUpdate');
    Route::delete('Incident/committees/destroy/{id}', 'Incident\ConfigurationController@committeeDestory')->name('incident.committees.destory');
 
   Route::post('Incident/resolution-timeline/store', 'Incident\ConfigurationController@storeResolutionTimeline')->name('incident.resolution-timeline.store');
   Route::post('Incident/meeting-reminder/store', 'Incident\ConfigurationController@storeMeetingReminder')->name('incident.meeting-reminder.store');
   Route::post('Incident/severity-levels/store', 'Incident\ConfigurationController@saveSeverityLevels')->name('incident.severity.store');
   Route::post('Incident/status/store', 'Incident\ConfigurationController@saveStatus')->name('incident.status.store');

  Route::get('Incident/Folloup-actions/view', 'Incident\ConfigurationController@viewFolloupActions')->name('incident.followup-actions.view');
  Route::get('Incident/Folloup-actions/list', 'Incident\ConfigurationController@FolloupActionsList')->name('incident.followup-actions.list');
  Route::post('Incident/Folloup-actions/store', 'Incident\ConfigurationController@storeFolloupActions')->name('incident.followup-actions.store');
  Route::put('Incident/Folloup-actions/inline-upadte/{id}', 'Incident\ConfigurationController@folloupActionsinlineUpdate')->name('incident.followup-actions.inlineUpdate');
  Route::delete('Incident/Folloup-actions/destroy/{id}', 'Incident\ConfigurationController@folloupActionsDestory')->name('incident.followup-actions.destory');
    
  Route::get('Incident/outcome-type/view', 'Incident\ConfigurationController@viewOutcomeType')->name('incident.outcome-type.view');
  Route::get('Incident/outcome-type/list', 'Incident\ConfigurationController@outcomeTypeList')->name('incident.outcome-type.list');
  Route::post('Incident/outcome-type/store', 'Incident\ConfigurationController@storeOutcomeType')->name('incident.outcome-type.store');
  Route::put('Incident/outcome-type/inline-upadte/{id}', 'Incident\ConfigurationController@outcomeTypeinlineUpdate')->name('incident.outcome-type.inlineUpdate');
  Route::delete('Incident/outcome-type/destroy/{id}', 'Incident\ConfigurationController@outcomeTypeDestory')->name('incident.outcome-type.destory');

  Route::get('Incident/action-taken/view', 'Incident\ConfigurationController@viewActionTaken')->name('incident.action-taken.view');
  Route::get('Incident/action-taken/list', 'Incident\ConfigurationController@ActionTakenList')->name('incident.action-taken.list');
  Route::post('Incident/action-taken/store', 'Incident\ConfigurationController@storeActionTaken')->name('incident.action-taken.store');
  Route::put('Incident/action-taken/inline-upadte/{id}', 'Incident\ConfigurationController@actionTakeninlineUpdate')->name('incident.action-taken.inlineUpdate');
  Route::delete('Incident/action-taken/destroy/{id}', 'Incident\ConfigurationController@actionTakenDestory')->name('incident.action-taken.destory');


   Route::get('Incidents', 'Incident\IncidentController@index')->name('incident.index');
   Route::get('Incidents/list', 'Incident\IncidentController@list')->name('incident.list');
   Route::get('Incidents/resolved-list', 'Incident\IncidentController@resolvedList')->name('incident.resolved.list');

   Route::get('Incidents/view-details/{id}', 'Incident\IncidentController@view')->name('incident.view');
   Route::get('Incidents/investigation/{id}', 'Incident\IncidentController@investigation')->name('incident.investigation');

   Route::post('Incidents/assign', 'Incident\IncidentController@assign')->name('incident.assign');
   Route::post('Incident/investigation/store', 'Incident\IncidentController@storeInvestigation')->name('incident.investigation.store');

   Route::get('Incidents/meeting', 'Incident\IncidentMeetingController@index')->name('incident.meeting');
   Route::get('Incidents/meeting/list', 'Incident\IncidentMeetingController@list')->name('incident.meeting.list');
   Route::get('Incidents/meeting/create/{id}', 'Incident\IncidentMeetingController@create')->name('incident.meeting.create');
   Route::post('Incidents/meeting/store', 'Incident\IncidentMeetingController@store')->name('incident.meeting.store');
   Route::get('Incidents/meeting/view-details/{id}', 'Incident\IncidentMeetingController@details')->name('incident.meeting.detail');

   Route::post('Incidents/meeting/inlineUpdate','Incident\IncidentMeetingController@inlineUpdate')->name('incident.meeting.inlineUpdate');
   Route::delete('Incidents/meeting/delete/{id}','Incident\IncidentMeetingController@delete')->name('incident.meeting.delete');

   Route::get('Incidents/calendar', 'Incident\IncidentMeetingCalendarController@index')->name('incident.calendar');
   Route::get('Incidents/calendar/get-incidents', 'Incident\IncidentMeetingCalendarController@getIncidentMeetings')->name('incident.calendar.get-meetings');

   Route::post('Incidents/request-statement', 'Incident\IncidentController@requestEmployeeStatements')->name('incident.request-statement');
   Route::get('Incidents/getDepartmentWiseParticipation', 'Incident\DashboardController@getDepartmentWiseParticipation')->name('incident.chart.getDepartmentWiseParticipation');
   Route::get('Incidents/getTrends', 'Incident\DashboardController@getIncidentTrends')->name('incident.chart.getTrends');

   Route::get('Incidents/hod/getTrends', 'Incident\DashboardController@gethodIncidentTrends')->name('incident.hod-chart.getTrends');

   Route::get('Incidents/get-resolution-stats','Incident\DashboardController@getResolutionTimelineStats')->name('incident.getResolutionTimelineStats');
   Route::get('Incidents/hod/get-resolution-stats','Incident\DashboardController@getResolutionTimelineData')->name('incident.hod.getResolutionTimelineStats');

   Route::get('Incidents/get-upcoming-meetings', 'Incident\DashboardController@getUpcomingMeetings')->name('incident.getUpcomingMeetings');
   Route::get('Incidents/preventive/list', 'Incident\DashboardController@getPreventiveActions')
   ->name('incident.preventive.list');
   Route::get('/Incidents/preventive-measures', 'Incident\DashboardController@preventiveMeasuresList')->name('incident.preventive');
   Route::get('/Incidents/pending-approvals', 'Incident\DashboardController@pendingApprovalsList')->name('incident.pending-approvals');

   Route::get('incidents/pending-resolutions', 'Incident\DashboardController@getPendingResolutionApprovals')->name('incident.pendingResolutions');
   Route::get('/incidents/incident-status-chart', 'Incident\DashboardController@getIncidentStatusStats')->name('incident.getIncident.chartdata');

   Route::get('incident/todo-list', 'Incident\DashboardController@getIncidentTodoList')->name('incident.todoList');

   Route::get('Incidents/hod-preventive/list', 'Incident\DashboardController@gethodPreventiveActions')
   ->name('incident.preventive.hodlist');
   Route::get('/Incidents/hod-preventive-measures', 'Incident\DashboardController@hodpreventiveMeasuresList')->name('incident.hod.preventive');
   Route::get('/Incidents/hod-pending-approvals', 'Incident\DashboardController@getPendingResolutionApprovalsforHOD')->name('incident.hod-pending-approvals');
   Route::post('/incidents/approve', 'Incident\IncidentController@approve')->name('incident.investigation.approve');
   Route::post('/incident/approve-or-reject', 'Incident\IncidentController@approveOrReject')->name('incident.investigation.approvedorreject');

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
      Route::post('/people/promotion/GetEmployeeWiseFilterData', 'People\Promotion\PromotionController@GetEmployeeWiseFilterData')->name('resort.promotion.getFilteredEmployees');

       //Promotion End

       //People Configuration
       Route::get('/people/configurtaion', 'People\ConfigController@index')->name('people.config');
       Route::post('/people/announcement-category', 'People\ConfigController@storeCategory')->name('announcement.category.store');
       Route::get('/people/announcement-category/index', 'People\ConfigController@announcementCategories')->name('announcement.categories');
       Route::put('people/announcement-category/inline-upadte/{id}', 'People\ConfigController@CategoryinlineUpdate')->name('announcement.categories.inlineUpdate');
       Route::delete('people/announcement-category/destroy/{id}', 'People\ConfigController@categoryDestory')->name('announcement.category.destory');
   
       Route::post('/people/configurtaion/resignation-withdrawal', 'People\ConfigController@EmployeeResignationWithdrawalConfigStore')->name('people.config.resignation-withdrawal-config');

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
       Route::get('/people/probation/ProbationEmailTamplateIndex', 'People\Probation\ProbationController@ProbationEmailTamplateIndex')->name('probation.EmailTamplateIndex');
       Route::post('/people/probation/get-probationLetter-Tamplate', 'People\Probation\ProbationController@GetEmailTamplate')->name('probation.GetEmailTamplate');
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

      //create route for liability data 
      Route::get('/people/initial-liability-estimation/get-data', 'People\Liability\LiabilityEstimationController@getLiabilityData')->name('people.liabilities.data');      

      Route::get('/people/initial-liability-estimation/get-employee-data/{empId}', 'People\Liability\LiabilityEstimationController@getLiabilityEmployeeData')->name('people.liabilities.emp-data');      
      //End of Initial Liability Estimation
    
      //SOS Module Start
    Route::get('sos/Configuration', 'SOS\ConfigurationController@index')->name('sos.config.index');
    
    // SOS roles and permission
    Route::post('sos/SOSRolesAndPermissionStore', 'SOS\ConfigurationController@SOSRolesAndPermissionStore')->name('sos.config.SOSRolesAndPermissionStore');
    Route::get('sos/IndexSOSRolesAndPermission', 'SOS\ConfigurationController@IndexSOSRolesAndPermission')->name('sos.config.IndexSOSRolesAndPermission');
    Route::put('sos/SOSRoleAndPerminlineUpdate/{id}', 'SOS\ConfigurationController@SOSRoleAndPerminlineUpdate')->name('sos.config.SOSRoleAndPerminlineUpdate');
    Route::delete('sos/SOSRolesAndPermissionDestory/{id}', 'SOS\ConfigurationController@SOSRolesAndPermissionDestory')->name('sos.config.SOSRolesAndPermissionDestory');

    // SOS team management
    Route::post('sos/SOSTeamStore', 'SOS\ConfigurationController@SOSTeamStore')->name('sos.config.SOSTeamStore');
    Route::get('sos/IndexSOSTeamManagement', 'SOS\ConfigurationController@IndexSOSTeamManagement')->name('sos.config.IndexSOSTeamManagement');
    Route::put('sos/SOSTeamManagementinlineUpdate/{id}', 'SOS\ConfigurationController@SOSTeamManagementinlineUpdate')->name('sos.config.SOSTeamManagementinlineUpdate');
    Route::delete('sos/SOSTeamManagementDestory/{id}', 'SOS\ConfigurationController@SOSTeamManagementDestory')->name('sos.config.SOSTeamManagementDestory');
    Route::get('sos/team/view-details/{id}', 'SOS\ConfigurationController@team_details')->name('sos.team.detail');
    Route::get('sos/team/list-details/{id}', 'SOS\ConfigurationController@get_team_details')->name('sos.team.getTeamDetails');
    Route::put('sos/SOSTeamMemberinlineUpdate/{id}', 'SOS\ConfigurationController@SOSTeamMemberinlineUpdate')->name('sos.config.SOSTeamMemberinlineUpdate');
    Route::delete('sos/SOSTeamMemberDestory/{id}', 'SOS\ConfigurationController@SOSTeamMemberDestory')->name('sos.config.SOSTeamMemberDestory');
    Route::get('sos/team/{id}/edit', 'SOS\ConfigurationController@getTeamData')->name('sos.team.edit');
    Route::post('sos/team/update/{id}', 'SOS\ConfigurationController@update_team_details')->name('sos.team.update');

    //SOS Emergency types
    Route::post('sos/SOSEmergencyTypeStore', 'SOS\ConfigurationController@SOSEmergencyTypeStore')->name('sos.config.SOSEmergencyTypeStore');
    Route::get('sos/IndexSOSEmergencyTypes', 'SOS\ConfigurationController@IndexSOSEmergencyTypes')->name('sos.config.IndexSOSEmergencyTypes');
    Route::delete('sos/SOSEmergencyTypesDestory/{id}', 'SOS\ConfigurationController@SOSEmergencyTypesDestory')->name('sos.config.SOSEmergencyTypesDestory');
    Route::post('sos/emergency-type/update/{id}', 'SOS\ConfigurationController@updateEmergencyTypes')->name('sos.emergencyType.update');

    // SOS Dashboard
    Route::get('sos/Dashboard', 'SOS\DashboardController@index')->name('sos.dashboard.index');
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


    Route::get('Report/index', 'ReportController@index')->name('resort.report.index');

    Route::get('Report/get-table-columns', 'ReportController@getTableColumns')->name('resort.reports.get-columns');

    Route::get('Report/create', 'ReportController@create')->name('reports.create');

    Route::post('Report', 'ReportController@store')->name('reports.store');

    Route::get('Report/show/{id}', 'ReportController@show')->name('reports.show');
    Route::post('FetchReportData', 'ReportController@FetchReportData')->name('reports.FetchReportData');

     Route::get('Report/export', 'ReportController@export')->name('report.export');


    Route::get('Report/edit/{id}', 'ReportController@edit')->name('reports.edit');

    Route::put('Report/{report}', 'ReportController@update')->name('resort.report.update');
    Route::post('AiInsideReport', 'ReportController@AiInsideReport')->name('reports.AiInsideReport');

    
    // Route::delete('Report/{report}', 'ReportController@destroy')->name('report.destroy');

    Route::get('/getUpdatedData','WorkforcePlanningDashboardController@getUpdatedData')->name('resort.occupancy.getUpdatedData');

    Route::get('/people/compliance', 'People\Compliances\ComplianceController@index')->name('people.compliance.index');
    Route::get('/people/compliance/list', 'People\Compliances\ComplianceController@list')->name('people.compliance.list');
    Route::get('/people/compliance/run','People\Compliances\ComplianceController@checkCompliance')->name('people.compliance.run');
    Route::get('/people/compliance/dismiss','People\Compliances\ComplianceController@dismiss')->name('people.compliances.dismiss');
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


  });
