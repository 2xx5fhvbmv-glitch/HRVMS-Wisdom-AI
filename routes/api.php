<?php

use Google\Service\Adsense\Row;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::namespace('API')->group(function () {

	Route::post('login', [App\Http\Controllers\API\LoginController::class, 'apiLogin'])->name('api.resort.login');
	Route::post('forgotpassword', [App\Http\Controllers\API\LoginController::class, 'apiForgotPassword'])->name('api.resort.forgotpassword');

	Route::middleware(['auth:api', 'applyResortSmtp'])->group(function () {

		Route::get('on-boarding/get-onboarding-virtual-facility', [App\Http\Controllers\API\OnBoardingController::class, 'getOnboardingVirtualFacility']);

		Route::post('add-device-token', [App\Http\Controllers\API\LoginController::class, 'addDeviceToken']);
		Route::post('remove-device-token', [App\Http\Controllers\API\LoginController::class, 'removeDeviceToken']);
		Route::post('logout', [App\Http\Controllers\API\LoginController::class, 'apiLogout'])->name('api.resort.logout');

		//Employees
		Route::get('resort/employees/{resort_id}', [App\Http\Controllers\API\EmployeeController::class, 'getEmployees']);
		Route::get('resort/employee-leave/{emp_id}', [App\Http\Controllers\API\EmployeeController::class, 'getEmployeesLeaves']);


		//get division, departments, sections and potions of resort
		Route::get('resort/divisions', [App\Http\Controllers\API\ManningController::class, 'getDivisions']);
		Route::get('resort/departments', [App\Http\Controllers\API\ManningController::class, 'getDepartments']);
		Route::get('resort/sections', [App\Http\Controllers\API\ManningController::class, 'getSections']);
		Route::get('resort/positions', [App\Http\Controllers\API\ManningController::class, 'getPositions']);

		//benefit grid and costs for budget calculation
		Route::get('resort/budget-costs', [App\Http\Controllers\API\BudgetCostController::class, 'getBudgetCosts']);
		Route::get('resort/benefit-grids', [App\Http\Controllers\API\BenefitGridController::class, 'getBenefitGrids']);
		Route::get('resort/benefit-grids-by-rank', [App\Http\Controllers\API\BenefitGridController::class, 'getBenefitGridsByRank']);

		//profile
		Route::get('resort/profile', [App\Http\Controllers\API\ProfileController::class, 'getProfile']);
		Route::post('resort/profile-personal-update', [App\Http\Controllers\API\ProfileController::class, 'profilePersonalUpdate']);
		Route::post('resort/profile-employee-update', [App\Http\Controllers\API\ProfileController::class, 'profileEmployeeUpdate']);
		Route::get('resort/countries', [App\Http\Controllers\API\ProfileController::class, 'getnationality']);
		Route::post('resort/profile-img-update', [App\Http\Controllers\API\ProfileController::class, 'changeProfileImage']);
		Route::get('profile/visa-category', [App\Http\Controllers\API\ProfileController::class, 'getVisaCategory']);
		Route::get('profile/visa-data/{visa_category}', [App\Http\Controllers\API\ProfileController::class, 'getVisaData']);

		//Employees Document
		Route::post('resort/employees-docs', [App\Http\Controllers\API\EmployeeDocumentController::class, 'employeeDocument']);
		Route::post('resort/get-employees-docs', [App\Http\Controllers\API\EmployeeDocumentController::class, 'getEmployeeDocument']);

		//Performance Review / Appraisal Forms
		Route::get('performance/my-reviews', [App\Http\Controllers\API\PerformanceReviewController::class, 'myReviews']);
		Route::get('performance/review/{id}', [App\Http\Controllers\API\PerformanceReviewController::class, 'reviewDetail']);
		Route::post('performance/review/{id}/submit', [App\Http\Controllers\API\PerformanceReviewController::class, 'submitReview']);

		//PIP / PDP (self-service — an employee's own plans, any rank)
		Route::get('performance/my-pip-plans', [App\Http\Controllers\API\PipPdpController::class, 'myPipPlans']);
		Route::get('performance/pip-plan/{id}', [App\Http\Controllers\API\PipPdpController::class, 'pipPlanDetail']);
		Route::post('performance/pip-plan/{id}/submit', [App\Http\Controllers\API\PipPdpController::class, 'submitPipPlan']);
		Route::get('performance/pip-plan/{id}/file/{field}', [App\Http\Controllers\API\PipPdpController::class, 'pipPlanFile']);
		Route::get('performance/my-pdp-plans', [App\Http\Controllers\API\PipPdpController::class, 'myPdpPlans']);
		Route::get('performance/pdp-plan/{id}', [App\Http\Controllers\API\PipPdpController::class, 'pdpPlanDetail']);
		Route::post('performance/pdp-plan/{id}/submit', [App\Http\Controllers\API\PipPdpController::class, 'submitPdpPlan']);
		Route::get('performance/pdp-plan/{id}/file/{field}', [App\Http\Controllers\API\PipPdpController::class, 'pdpPlanFile']);

		//File Management (mobile parity for the web File Management module)
		Route::get('resort/filemanagement/my-folder', [App\Http\Controllers\API\FileManagementController::class, 'myFolder']);
		Route::get('resort/filemanagement/shared-with-me', [App\Http\Controllers\API\FileManagementController::class, 'sharedWithMe']);
		Route::get('resort/filemanagement/folder/{unique_id}', [App\Http\Controllers\API\FileManagementController::class, 'folderContents']);
		Route::post('resort/filemanagement/create-folder', [App\Http\Controllers\API\FileManagementController::class, 'createFolder']);
		Route::post('resort/filemanagement/upload', [App\Http\Controllers\API\FileManagementController::class, 'upload']);
		Route::post('resort/filemanagement/rename', [App\Http\Controllers\API\FileManagementController::class, 'rename']);
		Route::post('resort/filemanagement/delete-file', [App\Http\Controllers\API\FileManagementController::class, 'deleteFile']);
		Route::get('resort/filemanagement/file/{unique_id}/download', [App\Http\Controllers\API\FileManagementController::class, 'downloadFile']);

		//Leave
		Route::get('resort/leave-dashboard', [App\Http\Controllers\API\LeaveController::class, 'leaveDashboard']);
		Route::post('resort/leave-add', [App\Http\Controllers\API\LeaveController::class, 'leaveAdd']);
		Route::post('resort/leave-update', [App\Http\Controllers\API\LeaveController::class, 'leaveUpdate']);
		Route::get('resort/leave-category', [App\Http\Controllers\API\LeaveController::class, 'leaveCategory']);
		Route::post('resort/leave-request', [App\Http\Controllers\API\LeaveController::class, 'leaveRequestList']);
		Route::get('resort/view-leave-request/{leave_id}', [App\Http\Controllers\API\LeaveController::class, 'viewLeaveRequest']);
		Route::post('resort/leave-history', [App\Http\Controllers\API\LeaveController::class, 'leaveHistoryList']);
		Route::get('resort/view-leave-history/{leave_id}', [App\Http\Controllers\API\LeaveController::class, 'viewLeaveHistory']);
		Route::post('resort/employee-leave-request-list', [App\Http\Controllers\API\LeaveController::class, 'employeeLeaveRequestListHRHOD']);
		Route::post('resort/employee-leave-request-view/{leave_id}', [App\Http\Controllers\API\LeaveController::class, 'leaveRequestViewHRHOD']);
		Route::post('resort/handle-leave-action', [App\Http\Controllers\API\LeaveController::class, 'handleLeaveAction']);


		//Task Delegation
		Route::get('resort/task-delegation', [App\Http\Controllers\API\LeaveController::class, 'taskDelegation']);
		Route::post('leave/check-combine', [App\Http\Controllers\API\LeaveController::class, 'checkCombineLeave']);

		//Transportations
		Route::get('resort/transportations', [App\Http\Controllers\API\LeaveController::class, 'transportations']);

		//Boarding Pass
		Route::post('boarding/boarding-pass-add', [App\Http\Controllers\API\BoardingPassController::class, 'boardingPassAdd']);
		Route::get('boarding/boarding-emp-dashboard', [App\Http\Controllers\API\BoardingPassController::class, 'boardingEmpDashboard']);
		Route::get('resort/upcoming-birthday-list', [App\Http\Controllers\API\LeaveController::class, 'getUpcomingBirthdaysList']);
		Route::get('boarding/boarding-pass-view/{pass_id}', [App\Http\Controllers\API\BoardingPassController::class, 'boardingPassView']);

		// Mobile-audit P2: alias for the employee-side "Track Request Pass" screen —
		// boardingPassView() already handles both plain int and base64 ids.
		Route::get('boarding/track-pass/{pass_id}', [App\Http\Controllers\API\BoardingPassController::class, 'boardingPassView']);
		Route::post('boarding/boarding-pass-approve-action', [App\Http\Controllers\API\BoardingPassController::class, 'boardingPassApprovedAction']);
		Route::get('boarding/boarding-pass-approve-list', [App\Http\Controllers\API\BoardingPassController::class, 'bordingPassApprovedList']);
		Route::post('boarding/emp-leaving-arriving', [App\Http\Controllers\API\BoardingPassController::class, 'employeeLeavingOrArriving']);
		Route::post('boarding/emergency-cancel-pass', [App\Http\Controllers\API\BoardingPassController::class, 'emergencyCancelBoardingPass']);
		Route::post('boarding/transportation-date-emp', [App\Http\Controllers\API\BoardingPassController::class, 'transportationDateBasedEmp']);
		Route::post('boarding/manifest-store', [App\Http\Controllers\API\BoardingPassController::class, 'manifestStore']);
		Route::post('boarding/pass-time-update', [App\Http\Controllers\API\BoardingPassController::class, 'passTimeupdateHRAndSM']);
		Route::post('boarding/boarding-pass-update', [App\Http\Controllers\API\BoardingPassController::class, 'boardingPassUpdate']);
		Route::post('boarding/boarding-pass-cancel', [App\Http\Controllers\API\BoardingPassController::class, 'boardingPassCancel']);
		Route::post('boarding/manifest-listing', [App\Http\Controllers\API\BoardingPassController::class, 'manifestListing']);

		// New: precise mode+date lookup so Apply Leave / Departure Pass can
		// auto-populate departure/arrival time from the manifest instead of
		// leaving it freely editable.
		Route::get('boarding/manifest-schedule', [App\Http\Controllers\API\BoardingPassController::class, 'manifestScheduleLookup']);
		Route::get('boarding/manifest-details/{manifest_id}', [App\Http\Controllers\API\BoardingPassController::class, 'manifestDetails']);
		Route::get('boarding/manifest-details-pdf/{manifest_id}', [App\Http\Controllers\API\BoardingPassController::class, 'manifestDetailsPDFWithEmployees']);
		Route::post('boarding/boarding-pass-status-update', [App\Http\Controllers\API\BoardingPassController::class, 'boardingPassStatusUpdate']);

		// Time & Attendance HR Dashboard (auth only; rank/department check done in controller)
		Route::get('timeandattendance/time-attendance-hr-dashboard', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'timeAttendanceHRDashboard']);
		Route::post('timeandattendance/time-attendance-hr-dashboard', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'timeAttendanceHRDashboard']);

		// GM needs the same "Leave for HOD" dashboard visibility as HOD/EXCOM
		// (GM getting a plain 403 "Insufficient rank" on their own Leave
		// dashboard). Split into its own group rather than adding GM to the
		// whole HOD Middleware group below, which also covers Accommodation/
		// Employee Management/Boarding Pass — none of which were part of
		// this report, so widening GM's access there wasn't asked for.
		Route::middleware(['auth:api', 'check.rank:HOD,EXCOM,GM'])->group(function () {
			Route::get('resort/leave-dashboard-hod', [App\Http\Controllers\API\LeaveController::class, 'leaveDashboardHOD']);
			Route::get('resort/island-pass-view-hod', [App\Http\Controllers\API\LeaveController::class, 'islandPassViewHOD']);
			Route::get('resort/islandpass-requestview/{pass_id}', [App\Http\Controllers\API\LeaveController::class, 'islandPassRequestViewHODAndHR']);
			Route::post('resort/hod-upcoming-employee-leave-list', [App\Http\Controllers\API\LeaveController::class, 'hodUpcomingEmployeeLeaveList']);
			Route::get('resort/hod-who-is-on-leave', [App\Http\Controllers\API\LeaveController::class, 'hodWhoIsOnLeave']);

			// GM also needs the To-Do List (same 403-for-GM gap as leave-dashboard-hod
			// above) — kept in this GM-inclusive group rather than added to the whole
			// HOD Middleware group below, which covers Accommodation/Employee
			// Management/Boarding Pass routes not part of this report.
			Route::get('timeandattendance/todo-list', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'mobileTodoList']);
		});

		//HOD Middleware
		Route::middleware(['auth:api', 'check.rank:HOD,EXCOM'])->group(function () {

			//Time and Attendance HOD
			Route::get('timeandattendance/employee-attendance-summary', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'employeeAttendanceSummary']);
			Route::get('timeandattendance/employee-attendance-summary-list', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'employeeAttendanceSummaryList']);
			Route::get('timeandattendance/time-attendance-hod-dashboard', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'timeAttendanceHODDashboard']);
			Route::post('timeandattendance/time-attendance-hod-dashboard', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'timeAttendanceHODDashboard']);
			Route::post('timeandattendance/hod-time-attendance', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'hodTimeAttendance']);
			Route::post('timeandattendance/hod-view-duty-roster', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'hodViewDutyRoster']);
			Route::get('timeandattendance/under-emp-hod', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'underEmpHOD']);
			Route::get('timeandattendance/duty-roster-employee-list', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'dutyRosterEmployeeList']);
			Route::post('timeandattendance/pre-planned-ot', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'mobilePrePlannedOT']);
			//Accommodation HOD
			Route::post('accommodation/hod-housekeeping-dashboard', [App\Http\Controllers\API\AccommodationController::class, 'hodHouseKeepingDashboard']);
			Route::post('accommodation/available-staff-underhod', [App\Http\Controllers\API\AccommodationController::class, 'availableStaffUnderHOD']);
			Route::post('accommodation/housekeeping-assign-hodtoemp', [App\Http\Controllers\API\AccommodationController::class, 'houseKeepingAssingHODtoEmp']);
			Route::post('accommodation/housekeeping-assign-deadline/{emp_id}', [App\Http\Controllers\API\AccommodationController::class, 'hodAssignDeadline']);
			Route::get('accommodation/housekeeping-assigned-cleaning', [App\Http\Controllers\API\AccommodationController::class, 'hodAssignedCleaning']);

			//Employee Management
			Route::get('employee-management/hod-employee-overview', [App\Http\Controllers\API\EmployeeManagementController::class, 'hodEmployeeOverview']);
			Route::get('employee-management/hod-organization-overview', [App\Http\Controllers\API\EmployeeManagementController::class, 'hodOrganizationOverview']);


			//Boarding Pass
			Route::get('boarding/boarding-hod-dashboard', [App\Http\Controllers\API\BoardingPassController::class, 'boardingHODDashboard']);
			Route::get('boarding/boarding-hod-list', [App\Http\Controllers\API\BoardingPassController::class, 'boardingHODListByStatus']);

		});

		// Mark Attendance: GM needs the same access HOD already has here (GM
		// wasn't in the HOD-only group above, so the app's GM login got
		// "Forbidden: Insufficient rank" on every mark-attendance call).
		Route::middleware(['auth:api', 'check.rank:HOD,EXCOM,GM'])->group(function () {
			Route::get('timeandattendance/hod-mark-attendance', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'hodMarkAttendance']);
			Route::post('timeandattendance/hod-mark-attendance', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'hodMarkAttendancePresent']);
			Route::post('timeandattendance/hod-mark-attendance-present', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'hodMarkAttendancePresent']);
		});


		//HR Middleware (HR, GM, and HOD/EXCOM for HR department access)
		Route::middleware(['auth:api', 'check.rank:HR,GM,HOD,EXCOM'])->group(function () {

			//Leave for HR
			Route::get('resort/leave-dashboard-hr', [App\Http\Controllers\API\LeaveController::class, 'leaveDashboardHR']);
			Route::get('resort/who-is-on-leave', [App\Http\Controllers\API\LeaveController::class, 'whoIsOnLeave']);
			Route::post('resort/upcoming-employee-leave-list', [App\Http\Controllers\API\LeaveController::class, 'hrUpcomingEmployeeLeaveList']);
			Route::get('resort/island-pass-view-hr', [App\Http\Controllers\API\LeaveController::class, 'islandPassViewHR']);

			//Time and Attendance HR
			Route::get('timeandattendance/employee-attendance-summary', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'employeeAttendanceSummary']);
			Route::get('timeandattendance/employee-attendance-summary-list', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'employeeAttendanceSummaryList']);
			Route::post('timeandattendance/hr-time-attendance', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'hrTimeAttendance']);
			//Accommodation HR
			Route::get('accommodation/accommodation-hr-dashboard', [App\Http\Controllers\API\AccommodationController::class, 'HR_Dashobard']);
			Route::post('accommodation/get-building-wise-employee', [App\Http\Controllers\API\AccommodationController::class, 'getBuildingWiseEmployee']);
			Route::get('accommodation/accommodation-employee-details/{id}', [App\Http\Controllers\API\AccommodationController::class, 'accommodationEmployeeDetails']);
			Route::get('accommodation/hr-maintenance-req-dashboard', [App\Http\Controllers\API\AccommodationController::class, 'hrMaintenanceRequestDashboard']);
			Route::get('accommodation/hr-bed-assign/{emp_id}', [App\Http\Controllers\API\AccommodationController::class, 'hrBedAssign']);
			Route::post('accommodation/hr-room-info', [App\Http\Controllers\API\AccommodationController::class, 'hrRoomInfo']);
			Route::post('accommodation/assign-accommodation-to-emp', [App\Http\Controllers\API\AccommodationController::class, 'assignAccommodationToEmp']);
			Route::post('accommodation/emp-list-with-available-bed', [App\Http\Controllers\API\AccommodationController::class, 'empListWithAvailableBed']);
			Route::post('accommodation/housekeeping-add-schedules', [App\Http\Controllers\API\AccommodationController::class, 'houseKeepingAddSchedules']);
			Route::post('accommodation/hr-housekeeping-dashboard', [App\Http\Controllers\API\AccommodationController::class, 'hrHouseKeepingDashboard']);
			Route::post('accommodation/housekeeping-assign-hrtohod', [App\Http\Controllers\API\AccommodationController::class, 'houseKeepingAssingHRtoHOD']);
			Route::post('accommodation/hr-maintenance-req-action', [App\Http\Controllers\API\AccommodationController::class, 'handleMaintananceAction']);
			Route::post('accommodation/hr-maintenance-req-sendto-staff-emp', [App\Http\Controllers\API\AccommodationController::class, 'completeTaskHRSendToStaffAccEmp']);

			//Employee Managament
			Route::post('employee-management/hr-employee-overview', [App\Http\Controllers\API\EmployeeManagementController::class, 'hrEmployeeOverview']);
			Route::get('employee-management/hr-organization-overview', [App\Http\Controllers\API\EmployeeManagementController::class, 'hrOrganizationOverview']);
			Route::get('employee-management/leave-request-list', [App\Http\Controllers\API\EmployeeManagementController::class, 'leaveRequestList']);

			//Boarding Pass
			Route::get('boarding/boarding-hr-dashboard', [App\Http\Controllers\API\BoardingPassController::class, 'boardingHRDashboard']);
		});

		//MGR Middleware for SecurityManager
		Route::middleware(['auth:api', 'check.rank:MGR'])->group(function () {
			Route::get('boarding/boarding-sm-dashboard', [App\Http\Controllers\API\BoardingPassController::class, 'boardingSecurityManagerDashboard']);
			Route::get('boarding/so-employee-list', [App\Http\Controllers\API\BoardingPassController::class, 'SOEmployeeList']);
			Route::post('boarding/so-pass-assign', [App\Http\Controllers\API\BoardingPassController::class, 'SOPassAssign']);
		});

		// Route::middleware(['auth:api', 'check.rank:SO'])->group(function () {
			Route::post('boarding/so-dashboard', [App\Http\Controllers\API\BoardingPassController::class, 'SODashboard']);
			Route::post('boarding/so-confirm-arrival-dept', [App\Http\Controllers\API\BoardingPassController::class, 'SOConfirmArrivalDept']);
		// });

		// Mobile-audit P2: alias (GET, filter as query param) + new detail endpoint
		Route::get('boarding/security-officer-dashboard', [App\Http\Controllers\API\BoardingPassController::class, 'SODashboard']);
		Route::get('boarding/security-officer-details/{assign_id}', [App\Http\Controllers\API\BoardingPassController::class, 'SOAssignmentDetails']);


		//Time and attendance Employee
		Route::get('timeandattendance/time-attendance-dashboard', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'timeAttendanceDashboard']);
		Route::get('timeandattendance/resort-base-shift', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'resortBaseShift']);
		Route::post('timeandattendance/employee-duty-roster', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'employeeDutyRoster']);
		Route::post('timeandattendance/manual-check-in', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'manualCheckIn']);
		Route::post('timeandattendance/break-check-in-out', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'breakCheckInCheckOut']);
		Route::post('timeandattendance/manual-check-out', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'manualCheckOut']);
		// Called by the app's background geofence monitoring on zone
		// enter/exit (native OS geofencing, not a manual button tap).
		Route::post('timeandattendance/geofence-event', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'geofenceEvent']);
		// Home screen: given the employee's current location, return the
		// duty roster's assigned geofence zone + whether they're inside it.
		Route::post('timeandattendance/my-geofence-zone', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'myGeofenceZone']);
		Route::get('timeandattendance/emp-checkinout-time/{date}', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'employeeCheckinCheckoutTime']);
		Route::get('timeandattendance/time-attendance-employee-leave', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'timeAttendanceEmployeeLeave']);
		Route::post('timeandattendance/approve-reject-ot', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'approveRejectOT']);
		Route::get('timeandattendance/check-employee-all-times', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'checkEmployeeAllTimes']);
		Route::post('timeandattendance/get-employees-day-month-data-list', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'getEmployeesDayAndMonthDataList']);
		Route::post('timeandattendance/get-employee-month-data-preview', [App\Http\Controllers\API\TimeAndAttendanceController::class, 'getEmployeeMonthDataPreviewList']);


		//Accommodation
		Route::get('accommodation/get-building', [App\Http\Controllers\API\AccommodationController::class, 'getBuilding']);
		Route::post('accommodation/get-floor', [App\Http\Controllers\API\AccommodationController::class, 'getBuildingWiseFloor']);
		Route::post('accommodation/get-rooms', [App\Http\Controllers\API\AccommodationController::class, 'getFloorWiseRooms']);
		Route::get('accommodation/get-accommodation-type', [App\Http\Controllers\API\AccommodationController::class, 'getAccommodationType']);
		Route::get('accommodation/maintenance-request-details/{id}', [App\Http\Controllers\API\AccommodationController::class, 'mainRequestDetails']);
		Route::get('accommodation/housekeeping-schedule-view/{schedule_id}', [App\Http\Controllers\API\AccommodationController::class, 'houseKeepingScheView']);
		Route::post('accommodation/housekeeping-emp',[App\Http\Controllers\API\AccommodationController::class, 'housekeepingEmployee']);
		Route::get('accommodation/housekeeping-emp-accept/{room_id}',[App\Http\Controllers\API\AccommodationController::class, 'empAcceptHousekeeping']);
		Route::post('accommodation/housekeeping-emp-add/{room_id}',[App\Http\Controllers\API\AccommodationController::class, 'empAddTaskHousekeeping']);

		//Employee List in Accommodation
		Route::get('accommodation/employee-list', [App\Http\Controllers\API\AccommodationController::class, 'employeeList']);

		//Staff accommodation
		Route::get('accommodation/staff-accommodation-dashboard', [App\Http\Controllers\API\StaffAccommodationController::class, 'staffAccommodationDashboard']);
		Route::get('accommodation/inventory-items', [App\Http\Controllers\API\StaffAccommodationController::class, 'inventoryItems']);
		Route::post('accommodation/create-maintenance-requests', [App\Http\Controllers\API\StaffAccommodationController::class, 'createMaintenanceRequests']);
		Route::get('accommodation/staff-accommodation-details/{accommodation_id}', [App\Http\Controllers\API\StaffAccommodationController::class, 'staffAccommodationDetails']);
		Route::get('accommodation/staff-maintenance-req-list', [App\Http\Controllers\API\StaffAccommodationController::class, 'staffMaintenanceReqList']);
		Route::get('accommodation/view-maintenance-request/{maintanaceId}', [App\Http\Controllers\API\StaffAccommodationController::class, 'viewMaintenanceRequest']);
		Route::post('accommodation/staff-accommodation-complete-task-status', [App\Http\Controllers\API\StaffAccommodationController::class, 'completeTaskStatus']);
		Route::post('accommodation/edit-maintenance-requests', [App\Http\Controllers\API\StaffAccommodationController::class, 'editMaintenanceRequests']);

		//Engineering Department HOD
		// Route::middleware(['auth:api', 'check.rank:EDHOD'])->group(function () {
			Route::get('accommodation/engi-department-hod-main-req-dashboard', [App\Http\Controllers\API\AccommodationController::class, 'engDepartmentHODMaintenanceReqDashboard']);
			Route::get('accommodation/engi-department-hod-main-req-list', [App\Http\Controllers\API\AccommodationController::class, 'engDepartmentHODMaintenanceReqList']);
			Route::get('accommodation/engi-department-hod-under-emp', [App\Http\Controllers\API\AccommodationController::class, 'getEmployeesUnderEngHOD']);
			Route::post('accommodation/engi-department-hod-assign-emp', [App\Http\Controllers\API\AccommodationController::class, 'engHODAssignEmployees']);
			Route::get('accommodation/engi-department-hod-assign-req-list', [App\Http\Controllers\API\AccommodationController::class, 'engDepartmentHODMaintenanceReqAssignList']);
			Route::post('accommodation/engi-department-hod-complete-sendto-hr', [App\Http\Controllers\API\AccommodationController::class, 'engHODCompleteSendToHR']);
		// });

		//Engineering Department Staff
		Route::get('accommodation/engi-department-staff-main-req-dashboard', [App\Http\Controllers\API\AccommodationController::class, 'engDepartmentStaffMaintenanceReqDashboard']);
		Route::post('accommodation/engi-department-staff-accept-req', [App\Http\Controllers\API\AccommodationController::class, 'engDepartmentStaffMaintenanceReqAccept']);
		Route::post('accommodation/engi-department-staff-complete-req', [App\Http\Controllers\API\AccommodationController::class, 'engDepartmentStaffMaintenanceReqComplete']);

		//Survey
		Route::get('survey/survey-emp-dashboard', [App\Http\Controllers\API\SurveyController::class, 'employeeSurveyDashboard']);
		Route::get('survey/survey-emp-quetions/{survey_id}', [App\Http\Controllers\API\SurveyController::class, 'employeeSurveyQuestions']);
		Route::post('survey/survey-emp-quetions-store', [App\Http\Controllers\API\SurveyController::class, 'employeeQuestionsAnsStore']);
		Route::get('survey/survey-emp-responses/{survey_id}', [App\Http\Controllers\API\SurveyController::class, 'employeeSurveyResponses']);

		//Chat
		Route::get('chat/check-connection/{user_id}', [App\Http\Controllers\API\EmployeeChatController::class, 'checkConnection']);
		Route::post('chat/send-emp-message', [App\Http\Controllers\API\EmployeeChatController::class, 'sendMessage']);

		//Payroll
		Route::get('payroll/payroll-dashboard', [App\Http\Controllers\API\PayrollController::class, 'payrollDashboard']);
		Route::get('payroll/payslip-years', [App\Http\Controllers\API\PayrollController::class, 'payslipYears']);
		Route::post('payroll/payslip-list', [App\Http\Controllers\API\PayrollController::class, 'paySlipList']);
		Route::post('payroll/payslip-details', [App\Http\Controllers\API\PayrollController::class, 'paySlipDetails']);
		Route::post('payroll/payslip-pdf-download', [App\Http\Controllers\API\PayrollController::class, 'downloadPayslip']);
		Route::post('payroll/payslip-share-email', [App\Http\Controllers\API\PayrollController::class, 'shareEmailPayslip']);

		Route::middleware(['auth:api', 'check.rank:EXCOM'])->group(function () {

			//Learning(L&D)
			Route::post('learning/manager-training-calendar', [App\Http\Controllers\API\LearningController::class, 'managerTrainingCalendar']);
			Route::get('learning/training-list', [App\Http\Controllers\API\LearningController::class, 'trainingList']);
			Route::get('learning/training-based-participant/{schedule_id}', [App\Http\Controllers\API\LearningController::class, 'trainingBasedParticipant']);
			Route::post('learning/mark-attendance', [App\Http\Controllers\API\LearningController::class, 'markAttendance']);
			Route::post('learning/participant-feedback-from-list', [App\Http\Controllers\API\LearningController::class, 'participantFeedbackFromList']);
			Route::get('learning/feedback-from-res-view/{form_res_id}', [App\Http\Controllers\API\LearningController::class, 'feedbackFormResView']);

		});

		//Learning(L&D)
		Route::get('learning/training-details/{schedule_id}', [App\Http\Controllers\API\LearningController::class, 'trainingDetails']);
		Route::post('learning/employee-training-calendar', [App\Http\Controllers\API\LearningController::class, 'employeeTrainingCalendar']);
		Route::get('learning/employee-learning-dashboard', [App\Http\Controllers\API\LearningController::class, 'employeeLearningDashbaord']);

		// Mobile-audit P2: empLT (legacy) alias — training-calendar and
		// training-details already match the spec's expected route names exactly.
		Route::get('learning/emp-lt-dashboard', [App\Http\Controllers\API\LearningController::class, 'employeeLearningDashbaord']);
		Route::get('learning/feedback-from-list', [App\Http\Controllers\API\LearningController::class, 'feedbackformListing']);
		Route::post('learning/feedback-data-store', [App\Http\Controllers\API\LearningController::class, 'feedbackStore']);

		//L&D Manager module + HR onboarding dashboard (position/department gated, not rank)
		Route::middleware(['ld.manager'])->group(function () {
			Route::get('ld-manager/dashboard', [App\Http\Controllers\API\LearningController::class, 'ldManagerDashboard']);
			Route::get('ld-manager/training-calendar', [App\Http\Controllers\API\LearningController::class, 'ldManagerTrainingCalendar']);
			Route::get('ld-manager/mark-attendance/trainings', [App\Http\Controllers\API\LearningController::class, 'ldManagerMarkAttendanceTrainings']);
			Route::get('ld-manager/mark-attendance/participants/{training_schedule_id}', [App\Http\Controllers\API\LearningController::class, 'ldManagerMarkAttendanceParticipants']);
			Route::post('ld-manager/mark-attendance', [App\Http\Controllers\API\LearningController::class, 'ldManagerMarkAttendanceStore']);
			Route::get('learning/manager-request-list', [App\Http\Controllers\API\LearningController::class, 'managerRequestList']);
		});

		// HR onboarding dashboard — self-gated on HR department inside the controller.
		Route::get('on-boarding/hr-dashboard', [App\Http\Controllers\API\OnBoardingController::class, 'hrDashboard']);

		//Incident
		Route::post('incident/add-incident', [App\Http\Controllers\API\IncidentController::class, 'AddIncident']);
		Route::get('incident/get-categories', [App\Http\Controllers\API\IncidentController::class, 'getCategories']);
		Route::post('incident/get-subcategories', [App\Http\Controllers\API\IncidentController::class, 'getSubCategories']);
		Route::get('incident/dashboard', [App\Http\Controllers\API\IncidentController::class, 'incidentDashboard']);
		Route::get('incident/incident-detail/{incident_id}', [App\Http\Controllers\API\IncidentController::class, 'incidentDetails']);
		Route::post('incident/incident-calender', [App\Http\Controllers\API\IncidentController::class, 'incidentCalender']);
		Route::get('incident/statement-request/{incident_id}', [App\Http\Controllers\API\IncidentController::class, 'getStatementRequest']);
		Route::post('incident/incident-statement', [App\Http\Controllers\API\IncidentController::class, 'provideStatement']);
			Route::get('incident/my-statements', [App\Http\Controllers\API\IncidentController::class, 'myStatements']);
		Route::get('incident/insights/{incident_id}', [App\Http\Controllers\API\IncidentController::class, 'getPreventiveInsights']);

		//calendar
		Route::get('calendar/get-holidays', [App\Http\Controllers\API\CalendarController::class, 'holidays']);
		Route::post('calendar/manager-dashboard', [App\Http\Controllers\API\CalendarController::class, 'managerDashboard']);

		Route::middleware(['auth:api', 'check.rank:HR,HOD'])->group(function () {
			Route::post('calendar/create-event', [App\Http\Controllers\API\CalendarController::class, 'createEvent']);
		});

		Route::post('calendar/event-calender', [App\Http\Controllers\API\CalendarController::class, 'eventsCalender']);
		Route::get('calendar/event/{id}', [App\Http\Controllers\API\CalendarController::class, 'eventDetails']);

		// 3rd party shop (Employee)
		Route::get('shop/employee-dashboard', [App\Http\Controllers\API\ShopController::class, 'employeeDashboard']);
		Route::get('shop/consent-request-view/{consent_request_id}', [App\Http\Controllers\API\ShopController::class, 'consentRequestview']);
		Route::post('shop/consent-request-handle', [App\Http\Controllers\API\ShopController::class, 'consentRequestHandle']);
		Route::get('shop/consent-request-history', [App\Http\Controllers\API\ShopController::class, 'consentRequestHistory']);
		Route::get('shop/product-details/{product_id}', [App\Http\Controllers\API\ShopController::class, 'productDetails']);

		//Monthly-Check-In
		Route::get('monthlycheckin/manager-dashboard', [App\Http\Controllers\API\MonthlyCheckInController::class, 'managerDashboard']);
		Route::get('monthlycheckin/learning-program', [App\Http\Controllers\API\MonthlyCheckInController::class, 'learningProgram']);
		Route::get('monthlycheckin/learning-manager', [App\Http\Controllers\API\MonthlyCheckInController::class, 'learingManager']);
		Route::post('monthlycheckin/monthly-checkin-store', [App\Http\Controllers\API\MonthlyCheckInController::class, 'monthlyCheckInStore']);
		Route::post('monthlycheckin/monthly-checkin-reschedule', [App\Http\Controllers\API\MonthlyCheckInController::class, 'monthlyCheckInRescheduleMeeting']);
		Route::get('monthlycheckin/employee-monthly-dashboard', [App\Http\Controllers\API\MonthlyCheckInController::class, 'employeeMonthlyCheckinDashboard']);
		Route::get('monthlycheckin/monthly-checkin-meeting-details/{meeting_id}', [App\Http\Controllers\API\MonthlyCheckInController::class, 'MonthlyCheckinMeetingDetails']);
		Route::post('monthlycheckin/employee-confirm-meeting', [App\Http\Controllers\API\MonthlyCheckInController::class, 'employeeConfirmMeeting']);
		Route::post('monthlycheckin/employee-approve-request', [App\Http\Controllers\API\MonthlyCheckInController::class, 'employeeApproveRequest']);
		Route::post('monthlycheckin/employee-reject-request', [App\Http\Controllers\API\MonthlyCheckInController::class, 'employeeRejectRequest']);
		Route::post('monthlycheckin/post-meeting-employee-comment', [App\Http\Controllers\API\MonthlyCheckInController::class, 'postMeetingEmployeeComment']);
		Route::get('monthlycheckin/monthly-checkin-history', [App\Http\Controllers\API\MonthlyCheckInController::class, 'monthlyCheckInHistory']);

		//Grievance
		Route::post('grievance/get-employee-details', [App\Http\Controllers\API\GrievanceController::class, 'GetEmployeeDetails']);
		Route::get('grievance/get-grievance-cat', [App\Http\Controllers\API\GrievanceController::class, 'GetGrievanceCat']);
		Route::post('grievance/get-grievance-sub-cat', [App\Http\Controllers\API\GrievanceController::class, 'GetGrievanceSubCat']);
		Route::post('grievance/grievance-store', [App\Http\Controllers\API\GrievanceController::class, 'GrievanceStore']);
		Route::post('grievance/informal-resolution', [App\Http\Controllers\API\GrievanceController::class, 'InformalResolution']);
		Route::get('grievance/my-grievances', [App\Http\Controllers\API\GrievanceController::class, 'myGrievances']);
		Route::post('grievance/identity-disclosure-respond', [App\Http\Controllers\API\GrievanceController::class, 'respondIdentityDisclosure']);
		Route::get('grievance/witness-statement-request/{grievance_id}', [App\Http\Controllers\API\GrievanceController::class, 'witnessStatementRequest']);
		Route::post('grievance/witness-statement', [App\Http\Controllers\API\GrievanceController::class, 'submitWitnessStatement']);
		Route::get('grievance/{id}', [App\Http\Controllers\API\GrievanceController::class, 'grievanceDetail']);


		//Disciplinary
		Route::get('disciplinary/disciplinary-dashboard', [App\Http\Controllers\API\DisciplinaryController::class, 'disciplinaryDashboard']);
		Route::get('disciplinary/disciplinary-details/{disciplinary_id}', [App\Http\Controllers\API\DisciplinaryController::class, 'disciplinaryDetails']);
		Route::post('disciplinary/acknowledgment-submit', [App\Http\Controllers\API\DisciplinaryController::class, 'AcknowledgmentSubmit']);

		//Clinic
		Route::middleware(['auth:api', 'check.rank:CLINIC_STAFF'])->group(function () {
			Route::post('clinic/appointment-categories-store', [App\Http\Controllers\API\ClinicController::class, 'appointmentCategoriesStore']);
			Route::get('clinic/clinic-staff-dashboard', [App\Http\Controllers\API\ClinicController::class, 'clinicStaffDashboard']);
			Route::post('clinic/appointment-list-based-filter', [App\Http\Controllers\API\ClinicController::class, 'appointmentListBasedonFilter']);
			Route::get('clinic/appointment-and-leave-list', [App\Http\Controllers\API\ClinicController::class, 'appointmentAndLeaveList']);
			Route::post('clinic/treatment-add', [App\Http\Controllers\API\ClinicController::class, 'treatmentAdd']);
			Route::get('clinic/medical-history-list', [App\Http\Controllers\API\ClinicController::class, 'medicalHistoryList']);
			Route::get('clinic/medical-history-details/{emp_id}', [App\Http\Controllers\API\ClinicController::class, 'medicalHistoryDetails']);
			Route::post('clinic/treatment-additional-note-update', [App\Http\Controllers\API\ClinicController::class, 'treatmentAdditionalNoteUpdate']);
			Route::post('clinic/medical-certificate-store', [App\Http\Controllers\API\ClinicController::class, 'medicalCertificateStore']);
			Route::post('clinic/clinic-staff-leave-action', [App\Http\Controllers\API\ClinicController::class, 'clinicStaffLeaveAction']);

		});

		// appointment-categories, appointment-details/{id} and
		// treatment-details/{id} used to sit here too, widened to
		// 'auth:api,temp-clinic-doctor' — but this whole block is inside
		// the outer Route::middleware(['auth:api', 'applyResortSmtp'])
		// group opened above (search "'applyResortSmtp'])->group"). Group
		// middleware ADDS to a route's own middleware, it doesn't get
		// replaced by it — so the outer group's plain 'auth:api' still
		// ran first and 401'd any temp-clinic-doctor token unconditionally,
		// no matter how the route's own guard list was widened. Moved to
		// after this group closes (see "Deliberately unrestricted" comment
		// there), which is the only way a temp-doctor token can actually
		// reach them.
		Route::post('clinic/appointment-store', [App\Http\Controllers\API\ClinicController::class, 'appointmentStore']);
		Route::get('clinic/employee-clinic-dashboard', [App\Http\Controllers\API\ClinicController::class, 'employeeClinicDashboard']);
		// clinic/appointment-status-update moved to the Clinic Manager tier
		// group below (clinic.capability:can_manage_treatment) — this is
		// the doctor's own "approve/reject/move to treatment" action on
		// an appointment, not a self-service action any employee should
		// be able to call on any appointment_id. It had no rank check at
		// all here (any authenticated employee could approve/reject
		// someone else's appointment) AND a TemporaryClinicDoctor 401'd
		// before ever reaching it, since this whole block sits inside the
		// outer auth:api-only group.
		Route::get('clinic/medical-certificate-details/{medical_cert_id}', [App\Http\Controllers\API\ClinicController::class, 'medicalCertificateDetail']);
		Route::get('clinic/past-medical-history', [App\Http\Controllers\API\ClinicController::class, 'pastMedicalHistory']);

		//Request
		Route::get('request/request-dashboard', [App\Http\Controllers\API\RequestController::class, 'requestDashboard']);
		Route::post('request/request-store', [App\Http\Controllers\API\RequestController::class, 'RequestStore']);
		Route::get('request/request-guarantor-list', [App\Http\Controllers\API\RequestController::class, 'PeopleGuarantorRequestList']);
		Route::post('request/guarantor-request-handle', [App\Http\Controllers\API\RequestController::class, 'PeopleGuarantorRequestHandleAction']);
		Route::get('request/salary-advance-details/{id}', [App\Http\Controllers\API\RequestController::class, 'salaryAdvanceDetails']);
		Route::get('request/guarantor-employee-list', [App\Http\Controllers\API\RequestController::class, 'GuarantorEmployeeList']);

		//SOS — general employee-facing (any authenticated employee)
		Route::get('sos/emergency-types', [App\Http\Controllers\API\SOSController::class, 'getEmergencyTypes']);
		Route::post('sos/sos-store', [App\Http\Controllers\API\SOSController::class, 'SOSStore']);
		Route::get('sos/sos-team-listing', [App\Http\Controllers\API\SOSController::class, 'SOSTeamListing']);
		Route::post('sos/sos-safe-status', [App\Http\Controllers\API\SOSController::class, 'SOSSafeStatus']);
		Route::get('sos/employee-team-location/{sos_id}', [App\Http\Controllers\API\SOSController::class, 'employeeAndTeamLocation']);
		Route::get('sos/sos-details/{sos_id}', [App\Http\Controllers\API\SOSController::class, 'SOSDetails']);
		Route::post('sos/sos-acknowledge', [App\Http\Controllers\API\SOSController::class, 'SOSAcknowledge']);
		Route::get('sos/sos-history-listing', [App\Http\Controllers\API\SOSController::class, 'SOSHistoryListing']);
		Route::get('sos/sos-history-details/{sos_id}', [App\Http\Controllers\API\SOSController::class, 'SOSHistoryDetails']);
		Route::get('sos/get-any-sos-emergency', [App\Http\Controllers\API\SOSController::class, 'getAnySOSEmergency']);
		Route::get('sos/get-team-acknowledged/{sos_id}', [App\Http\Controllers\API\SOSController::class, 'getTeamAcknowledged']);
		Route::post('sos/location-update', [App\Http\Controllers\API\SOSController::class, 'SOSLocationUpdate']);
		Route::get('sos/fire-team-members', [App\Http\Controllers\API\SOSController::class, 'fireTeamMembers']);
		Route::get('sos/chat-logs/{sos_id}', [App\Http\Controllers\API\SOSController::class, 'sosChatLogs']);
		Route::post('sos/send-chat-message', [App\Http\Controllers\API\SOSController::class, 'sosSendChatMessage']);

		// SOS — Security Manager only (approve/reject/dispatch/complete an
		// incident). Previously reachable by any authenticated employee.
		Route::middleware(['sos.manager'])->group(function () {
			Route::post('sos/handle-sos-action-with-team', [App\Http\Controllers\API\SOSController::class, 'handleSOSActionWithTeam']);
			Route::post('sos/drill-real-sos', [App\Http\Controllers\API\SOSController::class, 'drillRealSOS']);
			Route::post('sos/complete-sos-update-status', [App\Http\Controllers\API\SOSController::class, 'completeSOSUpdateStatus']);
			Route::get('sos/manager-dashboard', [App\Http\Controllers\API\SOSController::class, 'managerDashboard']);
		});

		// SOS — Security department staff (view-only incident roster).
		Route::middleware(['sos.security'])->group(function () {
			Route::get('sos/security-staff-dashboard', [App\Http\Controllers\API\SOSController::class, 'securityStaffDashboard']);
		});

		// Mobile-audit P1: aliases for already-implemented methods, under the
		// route names the mobile team's audit doc expects — avoids duplicating
		// logic that already exists under a different route name.
		Route::post('sos/mark-safe', [App\Http\Controllers\API\SOSController::class, 'SOSSafeStatus']);
		Route::get('sos/details/{sos_id}', [App\Http\Controllers\API\SOSController::class, 'SOSHistoryDetails']);
		Route::get('sos/history-list', [App\Http\Controllers\API\SOSController::class, 'SOSHistoryListing']);
		Route::get('sos/history-details/{sos_id}', [App\Http\Controllers\API\SOSController::class, 'SOSHistoryDetails']);
		Route::get('sos/employee-location/{sos_id}', [App\Http\Controllers\API\SOSController::class, 'employeeAndTeamLocation']);
		Route::get('sos/track/{sos_id}', [App\Http\Controllers\API\SOSController::class, 'employeeAndTeamLocation']);

		//Resignation
		Route::get('resignation/resignation-dashboard', [App\Http\Controllers\API\ResignationController::class, 'resignationDashboard']);
		Route::post('resignation/resignation-store', [App\Http\Controllers\API\ResignationController::class, 'resignationStore']);
		Route::post('resignation/resignation-withdraw', [App\Http\Controllers\API\ResignationController::class, 'resignationWithdraw']);
		Route::post('resignation/form-submit', [App\Http\Controllers\API\ResignationController::class, 'formSubmit']);
		Route::post('resignation/emp-confirm-meeting', [App\Http\Controllers\API\ResignationController::class, 'empConfirmMeeting']);

		// Mobile-audit P1: new endpoints
		Route::get('resignation/exit-interview-questions', [App\Http\Controllers\API\ResignationController::class, 'exitInterviewQuestions']);
		Route::get('resignation/rejection-details', [App\Http\Controllers\API\ResignationController::class, 'rejectionDetails']);

		// Mobile-audit P1: alias for the already-generic form submission —
		// formSubmit() already accepts assignment_id + response_data and works
		// for any exit_clearance_form type, exit_interview included.
		Route::post('resignation/exit-interview-submit', [App\Http\Controllers\API\ResignationController::class, 'formSubmit']);

		//On Boarding
		Route::get('on-boarding/on-boarding-dashboard', [App\Http\Controllers\API\OnBoardingController::class, 'onBoardingDashboard']);
		Route::get('on-boarding/assigned-staff-dashboard', [App\Http\Controllers\API\OnBoardingController::class, 'AssignedStaffDashboard']);

		// Mobile-audit P2: new content endpoints
		Route::get('on-boarding/harassment-prevention-content', [App\Http\Controllers\API\OnBoardingController::class, 'harassmentPreventionContent']);
		Route::get('on-boarding/access-details', [App\Http\Controllers\API\OnBoardingController::class, 'accessDetails']);
		Route::post('on-boarding/schedule-task-calendar', [App\Http\Controllers\API\OnBoardingController::class, 'scheduleTaskCalender']);
		Route::post('on-boarding/send-selfie-image', [App\Http\Controllers\API\OnBoardingController::class, 'sendSelfiImage']);
		Route::post('on-boarding/store-acknowledgement', [App\Http\Controllers\API\OnBoardingController::class, 'storeAcknowledgement']);
		Route::get('on-boarding/acknowledgement-view-files', [App\Http\Controllers\API\OnBoardingController::class, 'acknowledgementViewFiles']);

		// Mobile onboarding P3: remaining Figma screens — thin reads over the
		// same employee_itineraries / employee_itineraries_meeting /
		// onboarding_contents rows above, plus task actions for assigned staff.
		Route::get('on-boarding/accommodation-detail', [App\Http\Controllers\API\OnBoardingController::class, 'accommodationDetail']);
		Route::get('on-boarding/key-contacts', [App\Http\Controllers\API\OnBoardingController::class, 'keyContacts']);
		Route::get('on-boarding/hotel-details', [App\Http\Controllers\API\OnBoardingController::class, 'hotelDetails']);
		Route::get('on-boarding/cultural-insights', [App\Http\Controllers\API\OnBoardingController::class, 'culturalInsights']);
		Route::get('on-boarding/employee-handbook', [App\Http\Controllers\API\OnBoardingController::class, 'employeeHandbookList']);
		Route::get('on-boarding/policy-content/{type}', [App\Http\Controllers\API\OnBoardingController::class, 'policyContent']);
		Route::get('on-boarding/itinerary-timeline', [App\Http\Controllers\API\OnBoardingController::class, 'itineraryTimeline']);
		Route::post('on-boarding/task-action', [App\Http\Controllers\API\OnBoardingController::class, 'taskAction']);
		Route::get('on-boarding/meetings', [App\Http\Controllers\API\OnBoardingController::class, 'meetings']);
		Route::post('on-boarding/meetings/add-link', [App\Http\Controllers\API\OnBoardingController::class, 'addMeetingLink']);

		//Announcement
		Route::get('announcement/announcement-list', [App\Http\Controllers\API\AnnouncementController::class, 'announcementListing']);
		Route::get('announcement/send-congratulation/{announcementId}', [App\Http\Controllers\API\AnnouncementController::class, 'sendCongratulation']);

		// Mobile-audit P1: new detail endpoint + alias for the existing list
		// under the route name the mobile team's audit doc expects.
		// announcement/list MUST be registered before announcement/{id} —
		// otherwise the {id} wildcard swallows "list" as its parameter.
		Route::get('announcement/list', [App\Http\Controllers\API\AnnouncementController::class, 'announcementListing']);
		Route::get('announcement/{id}', [App\Http\Controllers\API\AnnouncementController::class, 'announcementDetail']);


		//employeeInAppNotification
		Route::get('notification/employee-in-app-notification', [App\Http\Controllers\API\InAppNotificationController::class, 'employeeInAppNotification']);
		Route::post('notification/delete-message-read', [App\Http\Controllers\API\InAppNotificationController::class, 'deleteMessageRead']);
		Route::get('notification/active-list', [App\Http\Controllers\API\InAppNotificationController::class, 'activeNotifications']);
		Route::get('notification/inactive-list', [App\Http\Controllers\API\InAppNotificationController::class, 'inactiveNotifications']);
		Route::post('notification/mark-read', [App\Http\Controllers\API\InAppNotificationController::class, 'markRead']);




		// Chat Module

		Route::get('chat/list', [App\Http\Controllers\API\ChatBoat\ChatController::class, 'index']);

		// Mobile-audit P3: FAQ content (not chat-service infra — Chat
		// Group/Chat Boat/HR Chat are a separate backend per the audit doc)
		Route::get('chat/faq-list', [App\Http\Controllers\API\ChatBoat\ChatController::class, 'faqList']);
		Route::get('chat/start-new/chat', [App\Http\Controllers\API\ChatBoat\ChatController::class, 'newChat']);
		Route::get('group/new-employee/list/{type_id}', [App\Http\Controllers\API\ChatBoat\ChatController::class, 'newEmployeeList']);
		Route::get('group/new-group-member/list', [App\Http\Controllers\API\ChatBoat\ChatController::class, 'groupMemberCandidates']);
		Route::post('create/group-chat', [App\Http\Controllers\API\ChatBoat\ChatController::class, 'createGroupChat']);
		Route::post('group/add-member/{type_id}', [App\Http\Controllers\API\ChatBoat\ChatController::class, 'addMember']);
		Route::post('group/remove-member/{type_id}', [App\Http\Controllers\API\ChatBoat\ChatController::class, 'removeMember']);
		Route::post('group/remove/{type_id}', [App\Http\Controllers\API\ChatBoat\ChatController::class, 'deleteGroup']);
		Route::post('group/update/{type_id}', [App\Http\Controllers\API\ChatBoat\ChatController::class, 'updateGroup']);

		Route::get('chat/view/{type}/{type_id}', [App\Http\Controllers\API\ChatBoat\ConversationController::class, 'chatView']);
		Route::post('chat/send-message', [App\Http\Controllers\API\ChatBoat\ConversationController::class, 'sendMessage']);
		Route::get('chat/get-messages/{type}/{type_id}', [App\Http\Controllers\API\ChatBoat\ConversationController::class, 'chatView']);
		Route::get('chat/messages/mark-read', [App\Http\Controllers\API\ChatBoat\ConversationController::class, 'markAsRead']);

		// Pusher private/presence channel auth for mobile (Passport/api guard) —
		// the default Broadcast::routes() auth endpoint only works under the
		// web/session guard, which mobile requests never carry.
		Route::post('broadcasting/auth', function (\Illuminate\Http\Request $request) {
			return \Illuminate\Support\Facades\Broadcast::auth($request);
		});

	});

	// Temporary Clinic Doctor (third-party/agency) — mobile-app only,
	// separate Passport guard from the ResortAdmin-backed 'api' guard above
	// (config/auth.php: 'temp-clinic-doctor' / 'temporary-clinic-doctors').
	// Login can't require auth, so it stays outside the auth:api group.
	Route::post('clinic-doctor/login', [App\Http\Controllers\API\TemporaryClinicDoctorAuthController::class, 'login']);
	Route::post('clinic-doctor/logout', [App\Http\Controllers\API\TemporaryClinicDoctorAuthController::class, 'logout'])->middleware('auth:temp-clinic-doctor');
	Route::get('clinic-doctor/profile', [App\Http\Controllers\API\TemporaryClinicDoctorAuthController::class, 'profile'])->middleware('auth:temp-clinic-doctor');

	// add-device-token used to sit only inside the outer auth:api-only
	// group below — every account (including a TemporaryClinicDoctor)
	// calls this right after login, so a doctor's very first post-login
	// request 401'd, and the app surfaced that as a forced logout even
	// though the login itself succeeded. Same "must live outside the
	// outer group" reasoning as the 3 routes above.
	Route::post('add-device-token', [App\Http\Controllers\API\LoginController::class, 'addDeviceToken'])->middleware('auth:api,temp-clinic-doctor');

	// Deliberately unrestricted by rank — any employee needs to view
	// categories to self-book an appointment, or their own appointment/
	// treatment record — AND a temp-clinic-doctor needs the same three.
	// Must live out here, genuinely outside the outer auth:api-only
	// group above (not just re-widened in place inside it — that alone
	// doesn't work, see the comment left where these used to be), or a
	// temp-clinic-doctor's token can never reach them.
	Route::middleware(['auth:api,temp-clinic-doctor'])->get('clinic/appointment-categories', [App\Http\Controllers\API\ClinicController::class, 'appointmentCategories']);
	Route::middleware(['auth:api,temp-clinic-doctor'])->get('clinic/appointment-details/{appointment_id}', [App\Http\Controllers\API\ClinicController::class, 'appointmentDetails']);
	Route::middleware(['auth:api,temp-clinic-doctor'])->get('clinic/treatment-details/{treatment_id}', [App\Http\Controllers\API\ClinicController::class, 'treatmentDetails']);

	// Clinic Manager tier, reachable by either identity — 'clinic.manager'
	// accepts a real rank-12 employee (unrestricted, same as before) OR an
	// active TemporaryClinicDoctor, further limited per-endpoint by
	// 'clinic.capability' to whatever HR granted that account. These
	// re-register the SAME URIs as the auth:api-only group above; Laravel
	// keeps only the last-registered route per URI+method, so real
	// employee callers are unaffected (their ResortAdmin token still
	// matches 'auth:api' first) — this purely ADDS the temp-doctor path.
	// appointment-categories-store (category/taxonomy config) and
	// clinic-staff-leave-action (leave-approval chain) are deliberately
	// NOT re-registered here — a third-party doctor has no employee/
	// approver identity to act as, and shouldn't be editing the resort's
	// permanent category list. Those two stay auth:api-only (real
	// employees, unchanged).
	Route::middleware(['auth:api,temp-clinic-doctor', 'applyResortSmtp', 'clinic.manager'])->group(function () {
		// clinic/appointment-categories moved to the unrestricted
		// registration above (its own guard now covers temp-clinic-doctor
		// too) — it was duplicated here under the rank-12-only
		// clinic.manager gate, which shadowed the open route and 403'd
		// every non-clinic-staff employee trying to self-book.

		Route::middleware(['clinic.capability:can_view_appointments'])->group(function () {
			Route::get('clinic/clinic-staff-dashboard', [App\Http\Controllers\API\ClinicController::class, 'clinicStaffDashboard']);
			Route::post('clinic/appointment-list-based-filter', [App\Http\Controllers\API\ClinicController::class, 'appointmentListBasedonFilter']);
			Route::get('clinic/appointment-and-leave-list', [App\Http\Controllers\API\ClinicController::class, 'appointmentAndLeaveList']);
			// clinic/appointment-details moved to the unrestricted
			// registration above — see the comment there.
		});

		Route::middleware(['clinic.capability:can_manage_treatment'])->group(function () {
			Route::post('clinic/treatment-add', [App\Http\Controllers\API\ClinicController::class, 'treatmentAdd']);
			Route::post('clinic/treatment-additional-note-update', [App\Http\Controllers\API\ClinicController::class, 'treatmentAdditionalNoteUpdate']);
			Route::post('clinic/appointment-status-update', [App\Http\Controllers\API\ClinicController::class, 'appointmentStatusUpdate']);
			// clinic/treatment-details moved to the unrestricted
			// registration above — see the comment there.
		});

		Route::middleware(['clinic.capability:can_view_medical_history'])->group(function () {
			Route::get('clinic/medical-history-list', [App\Http\Controllers\API\ClinicController::class, 'medicalHistoryList']);
			Route::get('clinic/medical-history-details/{emp_id}', [App\Http\Controllers\API\ClinicController::class, 'medicalHistoryDetails']);
		});

		Route::middleware(['clinic.capability:can_issue_medical_certificate'])->group(function () {
			Route::post('clinic/medical-certificate-store', [App\Http\Controllers\API\ClinicController::class, 'medicalCertificateStore']);
			Route::get('clinic/medical-certificate-details/{medical_cert_id}', [App\Http\Controllers\API\ClinicController::class, 'medicalCertificateDetail']);
		});
	});

// });
