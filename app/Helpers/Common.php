<?php
namespace App\Helpers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Mail;
use App\Models\AdminModule;
use App\Models\AdminModulePermission;
use App\Models\AdminRoleModulePermission;
use App\Models\AdminRoles;
use App\Models\Admin;
use App\Models\EmailTemplate;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Resort;
use App\Models\ResortAdmin;
use App\Models\Settings;
use App\Models\Notification;
use App\Jobs\TaEmailSent;
use NumberFormatter;

use DateTime;
use DateTimeZone;
use Log;
use File;
use Carbon\Carbon;
use App\Models\Vacancies;
use Illuminate\Support\Str;
use App\Models\ManningandbudgetingConfigfiles;
use App\Models\ModulePages;
use App\Models\ResortPagewisePermission;
use App\Models\ResortInteralPagesPermission;
use App\Models\Employee;
use App\Models\ResortsParentNotifications;
use App\Models\ResortsChildNotifications;
use App\Models\HrReminderRequestManning;
use App\Models\ResortSiteSettings;
use App\Models\BudgetStatus;
use App\Models\Questionnaire;
use App\Models\TaEmailTemplate;
use App\Models\HiringSource;
use URL;
use App\Models\ManningResponse;
use App\Models\JobAdvertisement;
use App\Models\PositionMonthlyData;
use App\Models\DutyRoster;
use App\Models\DutyRosterEntry;
use App\Models\ParentAttendace;
use App\Models\ChildAttendace;
use GuzzleHttp\Client;
use App\Models\EmployeeLeave;
use App\Models\LeaveCategory;
use App\Models\ResortBenifitGrid;
use App\Models\ColorTheme;
use App\Models\PublicHoliday;
use App\Models\PayrollServiceCharge;
use App\Models\GrivanceSubmissionModel;
use App\Models\GrievanceCommitteeMemberChild;
use App\Models\GrievanceCommitteeMemberParent;
use App\Models\GrivanceInvestigationChildModel;
use App\Models\GrivanceInvestigationModel;
use App\Models\ResortNotification;
use App\Models\ResortModule;
use App\Models\Modules;
use App\Models\FilemangementSystem;
use App\Models\ChildFileManagement;
use App\Models\Announcement;
use App\Models\FilePermissions;
use Illuminate\Support\Facades\Storage;
use App\Models\Incidents;
use App\Models\disciplinarySubmit;
use App\Models\DisciplinaryEmailmodel;
use Illuminate\Support\Facades\Http;
use App\Models\AuditLogs;
use App\Models\FileVersion;
use App\Models\MonthlyCheckingModel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ResortBudgetCost;
use App\Models\PaymentRequest;
use App\Models\ResortPosition;
use App\Models\Position;
use App\Models\User;

class Common
{

    public static function isHrAdmin(): bool
	{
		
	    $user = auth()->guard('resort-admin')->user();

	    if (!$user || !$user->GetEmployee) {
	        return false;
	    }

	    $employee = $user->GetEmployee;

	    return
	        (int) $employee->main_rank === 3 &&
	        (int) $employee->rank === 1;
	}
	public static function getWebsiteLogo()
	{
		$settings = Settings::first();
		$logo = $settings->header_logo ? url(config('settings.site_logo_folder')).'/'.$settings->header_logo : url('admin_assets/images/logo.svg');
		return $logo;
	}

	public static function getWebsiteFavicon()
	{
		$settings = Settings::first();
		$siteFavicon = $settings->site_favicon ? url(config('settings.site_favicon_folder'))."/".$settings->site_favicon : asset('front_assets/images/favicon.png');
		return $siteFavicon;
	}

	public static function getAdminLogo()
	{
		$settings = Settings::first();
		$logo = $settings->admin_logo ? url(config('settings.site_logo_folder'))."/".$settings->admin_logo : url('admin_assets/images/logo.svg');
		return $logo;
	}

	public static function getWebsiteContact()
	{
		$settings = Settings::first();
		$data = $settings->contact_number ? $settings->contact_number : '01223 322200';
		return $data;
	}

	public static function getWebsiteEmail()
	{
		$settings = Settings::first();
		$data = $settings->email_address ? $settings->email_address : 'info@rutherfordspunting.com';
		return $data;
	}

	public static function getTwitterLink()
	{
		$settings = Settings::first();
		$data = $settings->linkedin_link ? $settings->linkedin_link : '#';
		return $data;
	}

	public static function getFacebookLink()
	{
		$settings = Settings::first();
		$data = $settings->facebook_link ? $settings->facebook_link : '#';
		return $data;
	}

	public static function getInstagramLink()
	{
		$settings = Settings::first();
		$data = $settings->instagram_link ? $settings->instagram_link : '#';
		return $data;
	}

	public static function getYoutubeLink()
	{
		$settings = Settings::first();
		$data = $settings->youtube_link ? $settings->youtube_link : '#';
		return $data;
	}

	public static function getWebsiteLink()
	{
		$settings = Settings::first();
		$data = $settings->website ? $settings->website : 'https://projects.spaculus.live/3/wisdomAI/admin';
		return $data;
	}

	public static function getDateFormateFromSettings()
	{
		$settings = Settings::first();
		$format = $settings->date_format ? $settings->date_format : 'Y-m-d';
		return $format;
	}

	public static function convertDateFormatetoDatepicker($format)
	{
		$desiredformat = str_replace(
			['d', 'm', 'y', 'Y'],
			['dd', 'mm', 'yy', 'yy'],
			$format
		);
		return $desiredformat;
	}

	public static function getDateAndSetFormateToDatepicker()
	{
		$settings = Settings::first();
		$format = $settings->date_format ? $settings->date_format : 'Y-m-d';
		$desiredformat = str_replace(
			['d', 'm', 'y', 'Y'],
			['dd', 'mm', 'yyyy', 'yyyy'],
			$format
		);
		return $desiredformat;
	}

	public static function getTimeFromSettings()
	{
		$settings = Settings::first();
		$format = $settings->time_format ? $settings->time_format : '24';
		return $format;
	}

	public static function getDateTimeFormateFromSettings()
	{
		$timeformat = Common::getTimeFromSettings();
		$dateformat = Common::getDateFormateFromSettings();

		if( $timeformat == '12' ) {
			$format = $dateformat.' h:i:s A';
		} else {
			$format = $dateformat.' H:i:s';
		}
		return $format;
	}

    public static function getTimeFromSettingsResort()
	{
		$settings = Settings::first();

		$format = $settings->time_format ? $settings->time_format : '24';
		return $format;
	}

	public static function getCurDate()
	{
		$format = Common::getDateTimeFormateFromSettings();
		return date($format);
	}

	public static function getCurDateOnly()
	{
		$format = Common::getDateFormateFromSettings();
		return date($format);
	}

	public static function getWebsiteHeaderLogo()
	{
		$settings = Settings::first();
		$logo = $settings->header_logo ? url(config('settings.site_logo_folder'))."/".$settings->header_logo : url('front_assets/images/logo.svg');
		return $logo;
	}

	public static function getWebsiteFooterLogo()
	{
		$settings = Settings::first();
		$logo = $settings->footer_logo ? url(config('settings.site_logo_folder'))."/".$settings->footer_logo : url('files/logo.png');
		return $logo;
	}

	public static function getUserPicture()
	{
		if(Auth::guard('admin')->check() && request()->route()->getPrefix() === '/admin') {
			$admin = Auth::guard('admin')->user();
			$profilePicture = $admin->admin_profile;
		} else if(Auth::guard('customer')->check() && request()->route()->getPrefix() === '/customer') {
			$customer = Auth::guard('customer')->user();

			$profilePicture = $customer->profile_pic;
		} else {
			$profilePicture = url(config('settings.default_picture'));
		}
		return $profilePicture;
	}

	public static function getLoggedAdminName()
	{
		$name = '';
		if( Auth::guard('admin')->check() ) {
			return Auth::guard('admin')->user()->full_name;
		}else if(Auth::guard('customer')->check()){
			return Auth::guard('customer')->user()->full_name;
		}
	}

	public static function getAdminFavicon()
	{
		$settings = Settings::first();
		$siteFavicon = $settings->site_favicon ? url(config('settings.site_favicon_folder'))."/".$settings->site_favicon : asset('admin_assets/images/favicon.png');
		return $siteFavicon;
	}

	public static function getUserPictureById($id)
	{
		$user = Users::where('id', $id)->first();

		if( $user->profile_pic != '' ) {
			$profilePicture = url( config('settings.user_picture_folder'))."/".$user->id."/".$user->profile_pic;;
		} else {
			$profilePicture = asset('admin_assets/files/default-pic.jpg');
		}

		return $profilePicture;
	}

	public static function getDashboardLink()
	{
		if(Auth::guard('admin')->check() && request()->route()->getPrefix() == config('settings.route_prefix.admin')) {
			$route = route('admin.dashboard');
		}else if(Auth::guard('customer')->check() && request()->route()->getPrefix() == config('settings.route_prefix.customer')) {
			$route = route('customer.dashboard');
		} else {
			$route = "#";
		}
		return $route;
	}

	public static function getEditProfileLink()
	{
		if(Auth::guard('admin')->check() && request()->route()->getPrefix() == config('settings.route_prefix.admin')) {
			$route = route('admin.editProfile');
		} else if(Auth::guard('customer')->check() && request()->route()->getPrefix() == config('settings.route_prefix.customer')) {
			$route = route('customer.editProfile');
		} else {
			$route = "#";
		}
		return $route;
	}

	public static function getChangePasswordLink()
	{
		if(Auth::guard('admin')->check() && request()->route()->getPrefix() == config('settings.route_prefix.admin')) {
			$route = route('admin.changePassword');
		} else if(Auth::guard('customer')->check() && request()->route()->getPrefix() == config('settings.route_prefix.customer')) {
			$route = route('customer.changePassword');
		} else {
			$route = "#";
		}
		return $route;
	}

	public static function getLogoutLink()
	{
		if(Auth::guard('admin')->check() && (request()->route()->getPrefix() == config('settings.route_prefix.admin'))) {
			$route = route('admin.logout');
		} else if(Auth::guard('customer')->check() && (request()->route()->getPrefix() == config('settings.route_prefix.customer') || request()->route()->getName() == "home")) {
			$route = route('customer.logout');
		} else {
			$route = "#";
		}
		return $route;
	}

	public static function getUserName()
	{
		if(Auth::guard('admin')->check() && request()->route()->getPrefix() == config('settings.route_prefix.admin')) {
			$name = Auth::guard('admin')->user()->first_name;
		}else if(Auth::guard('customer')->check() && request()->route()->getPrefix() == config('settings.route_prefix.customer')) {
			$name = Auth::guard('customer')->user()->full_name;
		} else {
			$name = "Unknown";
		}
		return $name;
	}


	public static function uploadFile($file, $name, $path)
	{
        // dd($file, $name, $path);
		try {
			// Normalize the path (especially for Windows)
			$path = str_replace('\\', '/', rtrim($path, DIRECTORY_SEPARATOR));

			if (!file_exists($path)) {
				if (!mkdir($path, 0755, true) && !is_dir($path)) {
					throw new \Exception("Failed to create directory: $path");
				}
			}
			// If $file is an instance of UploadedFile, move it
			if ($file instanceof \Illuminate\Http\UploadedFile) {
				$file->move($path, $name);
				return $path . '/' . $name; // Return the full path where the file is stored
			}

			// If $file is a string and exists, copy it
			if (is_string($file) && file_exists($file)) {
				$destination = $path . '/' . $name;

				if (copy($file, $destination)) {
					return $destination; // Return the destination path after copy
				} else {
					throw new \Exception("Failed to copy file to: $destination");
				}
			}

			throw new \InvalidArgumentException("Invalid file provided: Must be an UploadedFile instance or a valid file path.");

		} catch (\Exception $e) {
			\Log::error("File upload error: " . $e->getMessage());
			return false; // Indicate failure
		}
	}

	public static function deleteFile($path)
	{
		if (is_file($path)) {
			unlink($path);
			return true;
		}
		return false;
	}

	public static function getUserPictureHeader()
	{
		$profilePicture = "";
		if(Auth::guard('admin')->check() && request()->route()->getPrefix() == config('settings.route_prefix.admin')) {
			$admin = Admin::find(Auth::guard('admin')->user()->id);
			$profilePicture = $admin->admin_image_name_path ?? "";
		}
		return $profilePicture;
	}

	public static function makeDiractory($path)
	{
		if( !File::isDirectory($path) ) {
			File::makeDirectory($path, 0777, true, true);
		}
	}

	public static function cutString($string)
	{
		$settings = Settings::first();
		$length = $settings->tour_title_length ? $settings->tour_title_length : 20;

		if (strlen($string) > $length) {
			$string = substr($string, 0, $length);
			$string .= "...";
		}

		return ucwords($string);
	}

	public static function readMoreString($string, $rating_id)
	{
		$settings = Settings::first();
		$length = $settings->review_length ? $settings->review_length : 50;

		if (strlen($string) > $length) {
			$string = substr($string, 0, $length);
			$string .= "...";
			$string .= '<div class="show_more_review_div"><a class="show_review" data-id="'.$rating_id.'" href="javascript:void(0)">Show more <i class="fas fa-angle-right"></i></a></div>';
		}

		return ucfirst($string);
	}

	public static function isMobileDevice($userAgent)
	{
		$mobileAgents = [
			'Android',
			'webOS',
			'iPhone',
			'iPad',
			'iPod',
			'BlackBerry',
			'Windows Phone',
		];

  		// Check if the User Agent contains any of the mobile device agents
		foreach ($mobileAgents as $agent) {
			if (strpos($userAgent, $agent) !== false) {
				return true;
			}
		}

		return false;
	}

	public static function getAllowedFileType()
	{
		$data = config('settings.allowed_extensions');
		return $data;
	}

	public static function getAllowedFileTypeExtensions()
	{
		$file_types = Common::getAllowedFileType();

		$file_types_with_dot = array_map(function($type) {
			return '.' . $type;
		}, $file_types);

		$file_type = implode(",", $file_types_with_dot);

		return $file_type;
	}

	public static function getAllowedImageType()
	{
		$data = config('settings.allowed_image_types');
		return $data;
	}

	public static function getAllowedImageTypeExtensions()
	{
		$file_types = Common::getAllowedImageType();

		$file_types_with_dot = array_map(function($type) {
			return '.' . $type;
		}, $file_types);

		$file_type = implode(",", $file_types_with_dot);

		return $file_type;
	}


	public static function generateUniqueCode($length, $tablename, $column)
	{
		do {
			$timestamp = now()->format('YmdHis');
			$randomString = Str::random(4);
			$dataToHash = $timestamp . $randomString;
			$hashedData = hash('sha256', $dataToHash);
			$uniqueCode = substr($hashedData, 0, $length);
		} while (self::codeExistsInDB($tablename, $uniqueCode, $column));

		return $uniqueCode;
	}

	public static function codeExistsInDB($tablename, $code, $column)
	{
		$count = DB::table($tablename)->where($column, $code)->count();
		return $count > 0;
	}

	public static function getDateFormats()
	{
		$data = config('settings.date_formats');
		return $data;
	}

	public static function getTimeFormats()
	{
		$data = config('settings.time_formats');
		return $data;
	}

	public static function getCurrency()
	{
		$data = config('settings.currency');
		return $data;
	}

	public static function hasPermission($moduleId, $permissionId)
	{
		if(Auth::guard('admin')->user()->type === "super" && Auth::guard('admin')->check() && request()->route()->getPrefix() == config('settings.route_prefix.admin')) {
			$accessible = true;
		} else {
			$accessible = AdminRoleModulePermission::whereRoleId(Auth::guard('admin')->user()->role_id)->whereHas('module_permission',function($q) use($moduleId, $permissionId) {
				$q->whereModuleId($moduleId)->wherePermissionId($permissionId);
			})->first();
		}
		return $accessible;
	}

	public static function generateUniqueDefaultID()
	{
		$date = date('Ymd');
		$maxInspectionId = TempCasingsInspection::where('inspectionId', 'like', $date . '%')->max('inspectionId');
		$inspectionId = (int) substr($maxInspectionId, -4);
		$inspectionId = ($inspectionId >= 9999) ? 1 : $inspectionId + 1;
		$inspectionId = str_pad($inspectionId, 4, '0', STR_PAD_LEFT);
		$uniqueID = $date . $inspectionId;
		return $uniqueID;
	}

	public static function getSingleFiledValue($tablename, $column)
	{
		$data = DB::table($tablename)->groupBy($column)->pluck($column)->toArray();
		return $data;
	}

	public static function getDateAndSetFormateToSql($date)
	{
		$settings = Settings::first();
		$format = $settings->date_format ? $settings->date_format : 'Y-m-d';
		$parsedDate = Carbon::createFromFormat($format, $date);
		return $parsedDate->format('Y-m-d');
	}

	public static function getAndSetDateToFormate($date)
	{
		$dateformat = Common::getDateFormateFromSettings();
		return Carbon::parse($date)->format($dateformat);
	}

	public static function getAdminProfileImage()
	{
		$admin = Auth::guard('admin')->user();
		$logo = $admin->profile_picture ? url(config('settings.admin_folder'))."/".$admin->profile_picture : url('admin_assets/images/user-img.avif');
		return $logo;
	}

	public static function getCustomerProfileImage()
	{
		$customer = Auth::guard('customer')->user();
		$logo = $customer->profile_pic ? url(config('settings.customer_folder'))."/".$customer->profile_pic : url('admin_assets/images/user-img.avif');
		return $logo;
	}

	public static function getDays()
	{
		$data = config('settings.days');
		return $data;
	}

	public static function convertTimeToSql($time)
	{
		return Carbon::createFromFormat('h:i A', $time)->format('H:i:s');
	}

	public static function convertTimeFromSql($time)
	{
		return Carbon::createFromFormat('H:i:s', $time)->format('h:i A');
	}

	public static function getDateRange($startDate)
	{
		$carbonDate = Carbon::parse($startDate);
		$range = [];

		for ($i = 0; $i < 8; $i++) {
			$day = $carbonDate->format('D');
			$date = $carbonDate->format('jS');
			$datesql = $carbonDate->format('Y-m-d');
			$range[] = [
				'day' => $day,
				'date' => $date,
				'datesql' => $datesql,
			];
			$carbonDate->addDay();
		}

		return $range;
	}

	public static function getDayStringFromDate($dateString)
	{
		$dateTime = new DateTime($dateString);
		return $dateTime->format('l');
	}

	public static function convertTo24HourFormat($time)
	{
		$dateTime = DateTime::createFromFormat('h:i A', $time);
		$formattedTime = $dateTime->format('H:i');
		return $formattedTime;
	}

	public static function getAllAdmins()
	{
		$data = Admin::orderBy('first_name', 'ASC')->get();
		return $data;
	}

	public static function getActiveCustomers()
	{
		$data = Customers::where('status', 'active')->orderBy('full_name', 'ASC')->get();
		return $data;
	}

	public static function getCustomerById($id)
	{
		$data = Customers::where('id', $id)->first();
		return $data;
	}

	public static function formattedDate($date)
	{
		$carbonDate = Carbon::parse($date);
		$formattedDate = $carbonDate->format('D jS M Y');
		return $formattedDate;
	}

	public static function getWordpressUrl()
	{
		return env("WEB_URL");
	}

	public static function getStates()
    {
        $data = config('states.states');
        return $data;
    }

	public static function generateUniquePassword($length)
	{
		$timestamp = now()->format('YmdHis');
		$randomString = Str::random(4);
		$dataToHash = $timestamp . $randomString;
		$hashedData = hash('sha256', $dataToHash);
		$uniqueCode = substr($hashedData, 0, $length);
		return $uniqueCode;
	}

	public static function getAuthorizedSignature()
	{
		$settings = Settings::first();
		$authSign = $settings->auth_sign ? url(config('settings.auth_sign_folder'))."/".$settings->auth_sign : asset('front_assets/images/PowerLabs-logo.png');
		return $authSign;
	}

	public static function numberToWords($number)
    {
        $words = new NumberFormatter("en", NumberFormatter::SPELLOUT);
        return $words->format($number);
    }

	public static function updateResortModules()
	{
		$getdata = ResortModule::whereDate('created_at', '=', now())->first();
        $getdata = '';
        if(empty($getdata->id)){
            ResortModule::truncate();
            ResortPermission::truncate();
            ResortModulePermission::truncate();

			$resortmodule_array = [
				'Workforce Planning',
				'Budget/Payroll',
				'Talent Acquisition',
				'People',
				'Time & Attendance',
				'Leave',
				'Performance',
				'Disciplinary',
				'Learning',
				'Accommodation',
				'Pension',
				'Incident',
				'Talent Pool',
				'Survey',
				'Reports',
				'Audit',
				'Documents',
				'Billing',
				'Visa',
				'Security',
				'Special Features',
				'Settings',
				'Resort Profile',
				'Roles And Permission'
			];

			foreach($resortmodule_array as $rsmodulearray){
				$rsmodule['name']   = $rsmodulearray;
				ResortModule::create($rsmodule);
			}

			$rspermission_array     = [
				['name' => 'View','order' => 1],
				['name' => 'Create','order' => 2],
				['name' => 'Edit','order' => 3],
				['name' => 'Delete','order' => 4],
			];

			foreach($rspermission_array as $rspermissionarray){
				$rspermission['name']   = $rspermissionarray['name'];
				$rspermission['order']  = $rspermissionarray['order'];
				ResortPermission::create($rspermission);
			}

			$rsmodule_permissions_array = [
				['module_id' => 1,  'permission_id' => 1],
				['module_id' => 1,  'permission_id' => 2],
				['module_id' => 1,  'permission_id' => 3],
				['module_id' => 1,  'permission_id' => 4],

				['module_id' => 2,  'permission_id' => 1],
				['module_id' => 2,  'permission_id' => 2],
				['module_id' => 2,  'permission_id' => 3],
				['module_id' => 2,  'permission_id' => 4],

				['module_id' => 3,  'permission_id' => 1],
				['module_id' => 3,  'permission_id' => 2],
				['module_id' => 3,  'permission_id' => 3],
				['module_id' => 3,  'permission_id' => 4],

				['module_id' => 4,  'permission_id' => 1],
				['module_id' => 4,  'permission_id' => 2],
				['module_id' => 4,  'permission_id' => 3],
				['module_id' => 4,  'permission_id' => 4],

				['module_id' => 5,  'permission_id' => 1],
				['module_id' => 5,  'permission_id' => 2],
				['module_id' => 5,  'permission_id' => 3],
				['module_id' => 5,  'permission_id' => 4],

				['module_id' => 6,  'permission_id' => 1],
				['module_id' => 6,  'permission_id' => 2],
				['module_id' => 6,  'permission_id' => 3],
				['module_id' => 6,  'permission_id' => 4],

				['module_id' => 7,  'permission_id' => 1],
				['module_id' => 7,  'permission_id' => 2],
				['module_id' => 7,  'permission_id' => 3],
				['module_id' => 7,  'permission_id' => 4],

				['module_id' => 8,  'permission_id' => 1],
				['module_id' => 8,  'permission_id' => 2],
				['module_id' => 8,  'permission_id' => 3],
				['module_id' => 8,  'permission_id' => 4],

				['module_id' => 9,  'permission_id' => 1],
				['module_id' => 9,  'permission_id' => 2],
				['module_id' => 9,  'permission_id' => 3],
				['module_id' => 9,  'permission_id' => 4],

				['module_id' => 10,  'permission_id' => 1],
				['module_id' => 10,  'permission_id' => 2],
				['module_id' => 10,  'permission_id' => 3],
				['module_id' => 10,  'permission_id' => 4],

				['module_id' => 11,  'permission_id' => 1],
				['module_id' => 11,  'permission_id' => 2],
				['module_id' => 11,  'permission_id' => 3],
				['module_id' => 11,  'permission_id' => 4],

				['module_id' => 12,  'permission_id' => 1],
				['module_id' => 12,  'permission_id' => 2],
				['module_id' => 12,  'permission_id' => 3],
				['module_id' => 12,  'permission_id' => 4],

				['module_id' => 13,  'permission_id' => 1],
				['module_id' => 13,  'permission_id' => 2],
				['module_id' => 13,  'permission_id' => 3],
				['module_id' => 13,  'permission_id' => 4],

				['module_id' => 14,  'permission_id' => 1],
				['module_id' => 14,  'permission_id' => 2],
				['module_id' => 14,  'permission_id' => 3],
				['module_id' => 14,  'permission_id' => 4],

				['module_id' => 15,  'permission_id' => 1],
				['module_id' => 15,  'permission_id' => 2],
				['module_id' => 15,  'permission_id' => 3],
				['module_id' => 15,  'permission_id' => 4],

				['module_id' => 16,  'permission_id' => 1],
				['module_id' => 16,  'permission_id' => 2],
				['module_id' => 16,  'permission_id' => 3],
				['module_id' => 16,  'permission_id' => 4],

				['module_id' => 17,  'permission_id' => 1],
				['module_id' => 17,  'permission_id' => 2],
				['module_id' => 17,  'permission_id' => 3],
				['module_id' => 17,  'permission_id' => 4],

				['module_id' => 18,  'permission_id' => 1],
				['module_id' => 18,  'permission_id' => 2],
				['module_id' => 18,  'permission_id' => 3],
				['module_id' => 18,  'permission_id' => 4],

				['module_id' => 19,  'permission_id' => 1],
				['module_id' => 19,  'permission_id' => 2],
				['module_id' => 19,  'permission_id' => 3],
				['module_id' => 19,  'permission_id' => 4],

				['module_id' => 20,  'permission_id' => 1],
				['module_id' => 20,  'permission_id' => 2],
				['module_id' => 20,  'permission_id' => 3],
				['module_id' => 20,  'permission_id' => 4],

				['module_id' => 21,  'permission_id' => 1],
				['module_id' => 21,  'permission_id' => 2],
				['module_id' => 21,  'permission_id' => 3],
				['module_id' => 21,  'permission_id' => 4],

				['module_id' => 22,  'permission_id' => 3],

				['module_id' => 23,  'permission_id' => 1],
				['module_id' => 23,  'permission_id' => 2],
				['module_id' => 23,  'permission_id' => 3],
				['module_id' => 23,  'permission_id' => 4],

				['module_id' => 24,  'permission_id' => 1],
				['module_id' => 24,  'permission_id' => 2],
				['module_id' => 24,  'permission_id' => 3],
				['module_id' => 24,  'permission_id' => 4],

			];

			foreach($rsmodule_permissions_array as $rsmodulepermissionsarray){
				$rsmodulepermission['module_id']        = $rsmodulepermissionsarray['module_id'];
				$rsmodulepermission['permission_id']    = $rsmodulepermissionsarray['permission_id'];
				ResortModulePermission::create($rsmodulepermission);
			}
        }
	}

    /// Ak
    public static function GetNotifications($resortId, $type,$Msgid= 0,$Budget_id=0)
    {


        $resort =     Auth::guard('resort-admin')->user();


        if($type==1)
        {
            $notifications = Notification::join("notification_resort as t1","t1.notification_id", "=","notifications.id")
            ->orderBy('created_at', 'DESC')
            ->latest()
            ->limit( 15)
            ->groupBy("notifications.id")
            ->whereIn('t1.resort_id',$resortId)
            ->get([
                'notifications.id', 'notifications.name', 'notifications.content', 'notifications.start_date', 'notifications.end_date', 'notifications.font_color',
                'notifications.notice_color', 'notifications.sticky', 'notifications.status', 'notifications.created_at', 'notifications.updated_at','notifications.created_by'
            ]);
        }
        elseif ($type==2)
        {

            $notifications   =ResortsParentNotifications::join('resort_admins as t1','t1.id', '=', 'resorts_parent_notifications.user_id')
                            ->leftjoin('employees as t2','t2.Admin_Parent_id',"=",'t1.id')
                            ->leftjoin('resort_departments as t3','t3.id',"=",'t2.Admin_Parent_id')

                            ->where('resorts_parent_notifications.message_id',$Msgid)
                            ->first(['t3.name as DepartmentName','t1.first_name','t1.middle_name','t1.last_name','t1.id as loginid','t1.resort_id','resorts_parent_notifications.message_subject','resorts_parent_notifications.message_id']);

        }
        elseif ($type==3)
        {

            $notifications = ResortsParentNotifications::join('resort_admins as t1', 't1.id', '=', 'resorts_parent_notifications.user_id')
            ->join('employees as t2', 't2.Admin_Parent_id', '=', 't1.id')
            ->leftJoin('resort_departments as t3', 't3.id', '=', 't2.Dept_id')
            ->join('resorts_child_notifications as t4', 't4.Parent_msg_id', '=', 'resorts_parent_notifications.message_id')
            ->join('hr_reminder_request_mannings as t5', 't5.message_id', '=', 'resorts_parent_notifications.message_id')
            ->where('t4.Parent_msg_id', $Msgid)
            ->where('t4.response', "No")
            ->orderBy('t5.id', 'desc')
            ->first([
                't3.name as DepartmentName',
                't1.first_name',
                't1.middle_name',
                't1.last_name',
                't1.id as loginid',
                't1.resort_id',
                't5.reminder_message_subject',
                'resorts_parent_notifications.message_id'
            ]);

        }
        elseif ($type == 4)
        {

            // return if some department   will send response then Hr Dashboard List will update
            $PendingDepartmentResoponse=array();
            if(isset($Msgid) &&   $resortId == $resortId)
            {

                $totalPendingResponse =ResortsChildNotifications::where("Parent_msg_id", $Msgid)->where("response","No")->groupBy('Department_id')->orderBy('created_at', 'desc')->get();

                $totalsendtoDepartment =ResortsChildNotifications::where("Parent_msg_id", $Msgid)->groupBy('Department_id')->orderBy('created_at', 'desc')->get();
                $ManningPendingRequestCount = count($totalsendtoDepartment);


                $departmentIds=array();


                foreach($totalPendingResponse as $Dep)
                {
                    $PendingDepartmentResoponse[$Dep->id][]= $Dep->department->name;
                }
                $totalPendingResponse=count($totalPendingResponse);
            }
            else
            {
                $totalPendingResponse=0;
                $ManningPendingRequestCount=0;
            }


            $DepartmentIds= $resort->with('ResortDepartment')->pluck('id')->toArray();


            $Auth_departmentId = $resort->GetEmployee->Dept_id;

            $DepartmentIds = $resort->resort->ResortDepartment
            ->reject(function ($department) use ($Auth_departmentId) {
                return $department->id == $Auth_departmentId;
            })
            ->pluck('id') ->toArray();
            $totalDepartmentscount= count($DepartmentIds);
            $HODpendingResponse=$totalPendingResponse;

            $totalDepartments=count($DepartmentIds);
            $notifications['totalDepartments']= $totalDepartments;
            $notifications['totalDepartmentscount']= $totalDepartmentscount;

            $notifications['HODpendingResponse']= $HODpendingResponse;
            $notifications['totalPendingResponse']= $totalPendingResponse;
            $notifications['ManningPendingRequestCount']= $ManningPendingRequestCount;

            $notifications['PendingDepartmentResoponse']= $PendingDepartmentResoponse;

        }
        else if($type == 5)
        {

            $manningResponse1 = ManningResponse::where('id', $Budget_id)->first();


            $BudgetStatus =  BudgetStatus::where('resort_id', $resortId)
                ->where( 'Department_id',$manningResponse1->dept_id)
                ->where( 'Budget_id', $manningResponse1->id)
                ->get()
                ->toArray();

            $notifications['year']=  $manningResponse1->year;
            $notifications['BudgetStatus']=  $BudgetStatus;
        }
        else if($type == 6 )
        {
            $manningResponse1 = ManningResponse::where('id', $Budget_id)->first();

                $ResortsParentNotifications = ResortsParentNotifications::join('resort_admins as t1', 't1.id', '=', 'resorts_parent_notifications.user_id')
                ->join('employees as t2', 't2.Admin_Parent_id', '=', 't1.id')
                ->leftJoin('resort_departments as t3', 't3.id', '=', 't2.Dept_id')
                ->join('resorts_child_notifications as t4', 't4.Parent_msg_id', '=', 'resorts_parent_notifications.message_id')
                ->join('budget_statuses as t5', 't5.message_id', '=', 'resorts_parent_notifications.message_id')
                ->where('t4.Parent_msg_id', $Msgid)
                ->where('t5.resort_id', $resortId)
                ->where('t5.Department_id', $manningResponse1->dept_id)
                ->where('t4.response', "Yes")
                ->orderBy('t5.id',  'desc')
                ->first([
                    't3.name as DepartmentName',
                    't1.first_name',
                    't1.middle_name',
                    't1.last_name',
                    't1.id as loginid',
                    't1.resort_id',
                    't5.OtherComments as reminder_message_subject',
                    'resorts_parent_notifications.message_id',
                    't5.Budget_id'
                ]);




            $notifications['BudgetStatus']=  $ResortsParentNotifications;
        }
        else if($type == 7) // Talent Acquisition Module start
        {
            $config = config('settings.Position_Rank');

            $rank= $config[1];

            $rank = $resort->GetEmployee->rank;

            $notifications['FreshVacancies']=    Common::GetTheFreshVacancies($resortId,"Active",  $rank);
        }

        else if($type == 8)
        {
            $rank = $resort->GetEmployee->rank;

            if($rank == 3) // HR
            {
                $rank = 7; // Finance
            }
            // if($rank == 7 )
            // {
            //     $rank =8;
            // }
            $notifications['FreshVacancies']=    Common::GetTheFreshVacancies($resortId,"Active",  $rank);
        }
        else if($type == 9)
        {
            $notifications =  Common::GmApprovedVacancy($resortId,3,$take=""); //Hr  To show to dolist
        }


        return $notifications;
    }
    public static function GetAdminResortProfile($resortadminid)
	{
        $ResortAdmin = ResortAdmin::find($resortadminid);

        if ($ResortAdmin->profile_picture == null || $ResortAdmin->profile_picture == 0)
        {
            $ResortAdmin = url( config('settings.default_picture'));

        }
        else
        {
            $ResortAdmin = url(config('settings.ResortProfile_folder'))."/".$ResortAdmin->profile_picture;
        }

        return $ResortAdmin;
	}


    public static function GetResortLogo($resortid)
	{
        $logo = Resort::find($resortid);
        if ($logo->logo == null)
        {
            $logo = url( config('settings.default_picture'));
        }
        else{
            $logo = url(config('settings.brand_logo_folder'))."/".$logo->logo;
        }
        return $logo;
	}

    public static function nofitication($resortid,$type,$Msgid= 0,$Budget_id=0,$other='',$sendto='',$moduleName="")
    {
        if($type==1)
        {
            $getNotifications = Common::GetNotifications($resortid,$type,$Msgid);

            $view = view('resorts.renderfiles.resortnotification',compact('getNotifications'))->render();
        }
        elseif($type==2)
        {
            $getNotifications = Common::GetNotifications($resortid,$type,$Msgid);
            $response['sendto'] =$sendto;
            $view = view('resorts.renderfiles.requestmanningmsg',compact('getNotifications'))->render();
        }
        elseif($type ==3)
        {
            $getNotifications = Common::GetNotifications($resortid,$type,$Msgid);

            $view = view('resorts.renderfiles.ReminnderRequestManning',compact('getNotifications'))->render();

            $PendingDepartmentId   = ResortsChildNotifications::where("Parent_msg_id", $Msgid)
            ->orderBy('created_at', 'desc')
            ->where('response','No')
            ->get(['Department_id']);

            $departmentIds = $PendingDepartmentId->map(function ($dep) {
                return $dep->Department_id;
            });

            $departmentIdsArray = $departmentIds->toArray();

            $response['PendingDepartment_id'] =$departmentIdsArray;
        }
        else if($type == 4)
        {
            $getNotifications = Common::GetNotifications($resortid,$type,$Msgid);

            $ManningPendingRequestCount =  $getNotifications['ManningPendingRequestCount'];
            $PendingDepartmentResoponse = $getNotifications['PendingDepartmentResoponse'];
            $HODpendingResponse =  $getNotifications['HODpendingResponse'];
            $totalDepartments = $getNotifications['totalDepartments'];
            $totalDepartmentscount = $getNotifications['totalDepartmentscount'];
            $totalPendingResponse = $getNotifications['totalPendingResponse'];

            $view = view('resorts.renderfiles.HrRequestCardView',
            compact('ManningPendingRequestCount','PendingDepartmentResoponse','HODpendingResponse','totalDepartments', 'totalPendingResponse'
            ))->render();
        }
        else if($type == 5) // Hr Review Done and sent to finace department
        {

            $getNotifications = Common::GetNotifications($resortid,$type,$Msgid,$Budget_id);
             $view = view('resorts.renderfiles.manninglifecycle', compact( 'getNotifications'))->render();
        }
        else if($type == 6 )
        {
            $getNotifications = Common::GetNotifications($resortid,$type,$Msgid,$Budget_id);
             $view = view('resorts.renderfiles.Revise_budget', compact( 'getNotifications'))->render();
        }

        else if($type == 7)
        {
            $getNotifications = Common::GetNotifications($resortid,$type);
            $view = view('resorts.renderfiles.FreshVacancies', compact( 'getNotifications'))->render();
            $response['sendto'] =$sendto;

        }
        else if($type == 8)
        {
            $getNotifications = Common::GetNotifications($resortid,$type);
            $view = view('resorts.renderfiles.FreshVacancies', compact( 'getNotifications'))->render();

        }
        else if($type == 9)
        {
            $TodoData = Common::GetNotifications($resortid,$type);
            $view = view('resorts.renderfiles.TaTodoList', compact( 'TodoData'))->render();
        }
        if($type==10)
        {

            $name = $Msgid;
            $message = $Budget_id;
            $request_id = $other ?? null;
            //   dd($sendto,$name,$moduleName,$resortid,$Msgid,$Budget_id,$other);
            $message1 = ResortNotification::create([ 'type' =>  $name,'user_id'=>$sendto,'module'=>$moduleName, 'resort_id' => $resortid, 'message' => $message ,'request_id' => $request_id]);
            $view = view('resorts.renderfiles.birthday_notification',compact('name','message','other','message1'))->render();
            $response['sendto'] =$sendto;
        }
        if($type == 11)
        {
            // $getNotifications = Common::GetNotifications($resortid,$type,$Msgid);
            $type = $type;
            $shopkeepr_id = $resortid;
            $content = $Msgid;
            $payment = $Budget_id;
            $name = "Conscent Approved";
            $view = view('shopkeeper.renderfiles.resortnotification',compact('shopkeepr_id','content','type','name'))->render();
        }


        $response['html'] = $view;
        $response['type'] =$type;
        $response['resortid'] =(string)$resortid;
        $client = new Client();
        $notificationUrl = env('NOTIFICATION_URL');

        $response = $client->post($notificationUrl, [
            'json' => $response
        ]);



        return $response;
    }
    public static function GetBudgetConfigLinks($resortid)
    {
        $links=array();
        $GetConfigLinks =ManningandbudgetingConfigfiles::where("resort_id", $resortid)->first();

        $resortFloder=  ResortAdmin::where("resort_id",$resortid)->first()->resort->resort_id;

        if (!isset($GetConfigLinks))
        {
                if ($GetConfigLinks == null )
                {
                    $links['consolidatdebudget'] = url( config('settings.Nodatafoundimage'));
                }
                if($GetConfigLinks== null)
                {
                    $links['benifitgrid'] = url( config('settings.Nodatafoundimage'));

                }
                if($GetConfigLinks == null)
                {
                    $links['XPAT']=0;
					$links['LOCAL']=0;

                }

        }
        else{
            $links['consolidatdebudget'] = url(config('settings.Resort_BudgetConfigFiles'))."/".$resortFloder."/".$GetConfigLinks->consolidatdebudget;
            $links['benifitgrid'] = url(config('settings.Resort_BudgetConfigFiles'))."/".$resortFloder."/".$GetConfigLinks->benifitgrid;
            $links['xpat']=$GetConfigLinks->xpat;
			$links['local']=$GetConfigLinks->local;
        }

        return $links;
    }

    ///Permission
    public static function GetModuleWisePages($Module_id)
    {
       return  $pages = ModulePages::where('Module_Id',$Module_id)->where('status','Active')->get();
    }

    public static function GetLastEmpId($resort_id)
    {
        $emp = Employee::orderBy("id","desc")->where('resort_id',$resort_id)->where('deleted_at',"=",null)->first('Emp_id');

        $newstring='';
        if(isset($emp))
        {
            $newstring = explode("-",$emp->Emp_id);

            if(!empty($newstring) && array_key_exists(1,$newstring) && !empty($newstring[1]))
            {
                $newstring = $newstring[1]+1;
            }
            else
            {
                $newstring= 1;
            }
        }else{
            $newstring = 1;
        }
        return $newstring;

    }

    public static function getResortUserPicture($userId ,$type = 0)
	{
        if (Auth::guard('resort-admin')->check() && request()->route()->getPrefix() === '/resort' || Auth::guard('api')->check())
        {
            $admin = ResortAdmin::with('resort')->find($userId);
            if($type == 1)
            {
                $profilePicturePath =  $aws = Self::GetApplicantAWSFile($admin->signature_img);

                if ($aws['success'] == true)
                {
                    $profilePicture = $aws['NewURLshow'];
                } else {
                    $profilePicture = url(config('settings.default_picture'));
                }
            }
            else
            {
                if( isset($admin->profile_picture) &&  $admin->profile_picture)
                {
                    $aws = Self::GetApplicantAWSFile($admin->profile_picture);
                    if ($aws['success'] == true)
                    {
                        $profilePicture = $aws['NewURLshow'];
                    }
                    else
                    {
                        $profilePicture = url(config('settings.default_picture'));
                    }
                }
                else
                {
                    $profilePicture = url(config('settings.default_picture'));
                }
            }
        }
        else
        {
            $profilePicture = url(config('settings.default_picture'));
        }
        return $profilePicture;
	}

    public static function CheckResortPermissions($module_id,$pageid,$Permission_id)
    {
        $Resort = Auth::guard('resort-admin')->user();

        if (Auth::guard('resort-admin')->user()->type === "super" && Auth::guard('resort-admin')->check() && request()->route()->getPrefix() == config('settings.route_prefix.resort-admin')) {
            return true;
        }
        else
        {

            $department_id = $Resort->GetEmployee->Dept_id;
            $Position_id = $Resort->GetEmployee->Position_id;
            $Resort_id = $Resort->GetEmployee->resort_id;

            $accessible = ResortPagewisePermission::join('resort_interal_pages_permissions as t1', 't1.resort_id', '=', 'resort_pagewise_permissions.resort_id')
            ->where('resort_pagewise_permissions.resort_id', $Resort_id)
            ->where('t1.Dept_id', $department_id)
            ->where('t1.position_id', $Position_id);
            if(!empty($module_id))
            {
                $accessible->where('resort_pagewise_permissions.Module_id', $module_id);
            }
            if(!empty($pageid))
            {
                $accessible->where('resort_pagewise_permissions.page_permission_id', $pageid);
            }
            $accessible_record = $accessible->orderBy('t1.id', 'ASC')
            ->get(['resort_pagewise_permissions.Module_id', 't1.page_id', 't1.Permission_id','resort_pagewise_permissions.page_permission_id']);

            $permissions = [];
            $Module = [];

            $permissions['Resort']=$Resort;
            if ($accessible_record->isNotEmpty()) {
                foreach ($accessible_record as $value) {
                    if (!isset($permissions['Access'][$value->Module_id]))
                    {
                        $permissions['Access'][$value->Module_id] = [];
                    }
                    if( $value->page_permission_id == $value->page_id)
                    {
                        $permissions['Access'][$value->Module_id][$value->page_id] = $value->Permission_id;
                    }

                }
            }

            return $permissions;
        }
    }

    public static function resortHasPermission($module_id='',$pageid='',$Permission_id='')
    {

        if(Auth::guard('resort-admin')->user()->type === "super" && Auth::guard('resort-admin')->check())
        {
            return true;
        }
        else
        {
            $Permissions = Common::CheckResortPermissions($module_id,$pageid,$Permission_id);

            if(array_key_exists('Access',$Permissions))
            {
                $ResortPermissions = [];
                foreach ($Permissions['Access'] as $moduleId => $pages) {
                    foreach ($pages as $pageId => $permissionArray) {
                        $ResortPermissions[$moduleId][$pageId] = $permissionArray;
                    }
                }

                $Resort = $Permissions['Resort'];

                $Position_id = $Resort->GetEmployee->Position_id;
                $Resort_id = $Resort->GetEmployee->resort_id;

                foreach ($ResortPermissions as $moduleId => $pages) {
                    foreach ($pages as $pageId => $permissions) {
                        $accessible = ResortPagewisePermission::whereResortId($Resort_id);

                        if(!empty( $module_id))
                        {
                            $accessible->where('Module_id', $module_id);
                        }
                        if(!empty( $pageid))
                        {
                            $accessible->where('page_permission_id', $pageid);
                        }
                        $accessible_record =  $accessible->whereHas('Resort_internal_pages', function($q) use($Permission_id, $Position_id) {
                            $q->where('Permission_id', $Permission_id)
                            ->where('position_id', $Position_id);
                        })->first();

                        if ($accessible_record) {
                            return true;
                        }

                    }
                }
            }

        return false;
        }

    }


    public static function GetResortSiteSettings($resortid,$type)
	{
		if (Auth::guard('resort-admin')->check() && request()->route()->getPrefix() === '/resort')
        {

            $profilePicturePath = public_path(config('settings.Resort_SiteSettings')  . '/'.$resortid. '/' .$type);


            if(file_exists($profilePicturePath) && $type != '') {

                $profilePicture = url(config('settings.Resort_SiteSettings') . '/'.$resortid. '/' .$type);
            } else {

                $profilePicture = url(config('settings.default_picture'));
            }
        } else{
            $profilePicture = url(config('settings.default_picture'));
        }

        return $profilePicture;

	}

    public static function GetResortCurrentCurrency()
    {
        $resortid = optional(Auth::guard('resort-admin')->user())->resort_id;
        if(!$resortid) return config('settings.currency.MVR');
        $resortexist =  ResortSiteSettings::where('resort_id', $resortid)->first(['currency']);
        if(isset($resortexist))
        {
            $resortexist = $resortexist->currency;
        }else{
            $resortexist = config('settings.currency.MVR');
        }
        return  $resortexist;

    }

    public static function getMenuTypeByUser(){
        $resort = Auth::guard('resort-admin')->user();
       $type = 'horizontal';
        if($resort->menu_type == 'horizontal'){
            $type = 'horizontal';
        }else if($resort->menu_type == 'vertical'){
            $type = 'vertical';
        }

        return $type;
    }

    public static function GetResortCurrencyLogo()
    {
        $resortid = optional(Auth::guard('resort-admin')->user())->resort_id;
        if(!$resortid) return URL::asset(config('settings.Resort_currency').'/maldives-currency-icon-new.svg');
        $resortexist =  ResortSiteSettings::where('resort_id', $resortid)->select('currency','MVR_img','Doller_img')->first();
        if(isset($resortexist))
        {
            $img =  $resortexist->Doller_img;
        }
        else{
            $img =  'maldives-currency-icon-new.svg';
        }
        $logo =  URL::asset(config('settings.Resort_currency').'/'.$img);
        return $logo;
    }

	public static function CheckemployeeBudgetCost($employeeType, $resort_id, $basic_salary,$getformated = 0) {
		// Start with basic salary as the base cost
		$totalCost = $basic_salary;

		$data = DB::table('resort_budget_costs')
			->where('resort_id', $resort_id)
			->where('status', 'active')
			->where("particulars", "!=", "Basic Salary")
			->where('cost_title', 'Operational Cost')
			->where(function ($query) use ($employeeType) {
				if ($employeeType != 'Maldivian') {
					$query->where('details', 'Xpat Only')
						->orWhere('details', 'Both');
				} elseif ($employeeType == 'Maldivian') {
					$query->where('details', 'Locals Only')
						->orWhere('details', 'Both');
				}
			})
			->get();

		foreach ($data as $c) {
			$frequency = ucfirst(strtolower($c->frequency));
			$amount = $c->amount;
			$unit = $c->amount_unit;
			$headcount = 1;

            switch ($frequency) {
                case 'Monthly':
                    if ($unit == '%' && $basic_salary) {
                        $totalCost += (($basic_salary * $amount) / 100) * $headcount;
                    } elseif ($unit == 'USD' || $unit == 'MVR') {
                        // Convert currency if needed based on resort's base currency
                        $convertedAmount = $amount;
                        if ($unit == 'MVR' && $basic_salary) {
                            // Convert MVR to USD if resort base currency is USD
                            $convertedAmount = self::RateConversion('MVRToDoller', $amount, $resort_id);
                        } elseif ($unit == 'USD' && $basic_salary) {
                            // Convert USD to MVR if resort base currency is MVR
                            $convertedAmount = self::RateConversion('DollerToMVR', $amount, $resort_id);
                        }
                        $totalCost += $convertedAmount * $headcount;
                    } else {
                        $totalCost += $amount * $headcount;
                    }
                    break;

                case 'Yearly':
                    if ($unit == '%' && $basic_salary) {
                        $totalCost += ((($basic_salary * $amount) / 100) / 12) * $headcount;
                    } elseif ($unit == 'USD' || $unit == 'MVR') {
                        $convertedAmount = $amount;
                        if ($unit == 'MVR' && $basic_salary) {
                            $convertedAmount = self::RateConversion('MVRToDoller', $amount, $resort_id);
                        } elseif ($unit == 'USD' && $basic_salary) {
                            $convertedAmount = self::RateConversion('DollerToMVR', $amount, $resort_id);
                        }
                        $totalCost += ($convertedAmount / 12) * $headcount;
                    } else {
                        $totalCost += ($amount / 12) * $headcount;
                    }
                    break;

                case 'Quarterly':
                    if ($unit == '%' && $basic_salary) {
                        $totalCost += ((($basic_salary * $amount) / 100) / 3) * $headcount;
                    } elseif ($unit == 'USD' || $unit == 'MVR') {
                        $convertedAmount = $amount;
                        if ($unit == 'MVR' && $basic_salary) {
                            $convertedAmount = self::RateConversion('MVRToDoller', $amount, $resort_id);
                        } elseif ($unit == 'USD' && $basic_salary) {
                            $convertedAmount = self::RateConversion('DollerToMVR', $amount, $resort_id);
                        }
                        $totalCost += ($convertedAmount / 3) * $headcount;
                    } else {
                        $totalCost += ($amount / 3) * $headcount;
                    }
                    break;

                case 'Daily':
                    if ($unit == '%' && $basic_salary) {
                        $dailyRate = $basic_salary / 30;
                        $totalCost += (($dailyRate * $amount) / 100 * 30) * $headcount;
                    } elseif ($unit == 'USD' || $unit == 'MVR') {
                        $convertedAmount = $amount;
                        if ($unit == 'MVR' && $basic_salary) {
                            $convertedAmount = self::RateConversion('MVRToDoller', $amount, $resort_id);
                        } elseif ($unit == 'USD' && $basic_salary) {
                            $convertedAmount = self::RateConversion('DollerToMVR', $amount, $resort_id);
                        }
                        $totalCost += ($convertedAmount * 30) * $headcount;
                    } else {
                        $totalCost += ($amount * 30) * $headcount;
                    }
                    break;

                case 'One-time':
                    if ($unit == '%' && $basic_salary) {
                        $totalCost += (($basic_salary * $amount) / 100) / 12;
                    } elseif ($unit == 'USD' || $unit == 'MVR') {
                        $convertedAmount = $amount;
                        if ($unit == 'MVR' && $basic_salary) {
                            $convertedAmount = self::RateConversion('MVRToDoller', $amount, $resort_id);
                        } elseif ($unit == 'USD' && $basic_salary) {
                            $convertedAmount = self::RateConversion('DollerToMVR', $amount, $resort_id);
                        }
                        $totalCost += $convertedAmount / 12;
                    } else {
                        $totalCost += $amount / 12;
                    }
                    break;

                case 'Hourly':
                    $monthlyHours = 8 * 22; // Standard work hours per month
                    if ($unit == '%' && $basic_salary) {
                        $hourlyRate = $basic_salary / $monthlyHours;
                        $totalCost += ($hourlyRate * ($amount / 100)) * $monthlyHours;
                    } elseif ($unit == 'USD' || $unit == 'MVR') {
                        $convertedAmount = $amount;
                        if ($unit == 'MVR' && $basic_salary) {
                            $convertedAmount = self::RateConversion('MVRToDoller', $amount, $resort_id);
                        } elseif ($unit == 'USD' && $basic_salary) {
                            $convertedAmount = self::RateConversion('DollerToMVR', $amount, $resort_id);
                        }
                        $totalCost += $convertedAmount * $monthlyHours;
                    } else if (strpos($amount, "time of basic salary") !== false) {
                        if ($basic_salary) {
                            $multiplier = (strpos($amount, "1.25") !== false) ? 1.25 : 1.5;
                            $hourlyRate = $basic_salary / $monthlyHours;
                            $totalCost += ($hourlyRate * $multiplier) * $monthlyHours;
                        }
                    } else {
                        $totalCost += $amount * $monthlyHours;
                    }
                    break;
            }
		}

        if($getformated !=0)
        {
            return number_format($totalCost, 2);

        }
        else
        {
        	return $totalCost;
        }

	}

	public static function CheckVacantBudgetCost($vacantCount)
    {

			$resortId = Auth::guard('resort-admin')->user()->resort_id;
			$resortConfig = ManningandbudgetingConfigfiles::where('resort_id', $resortId)->first();


	    try {

			// $ratioTotal = $resortConfig->xpat + $resortConfig->local;
			// $xpatRatio = $resortConfig->xpat / $ratioTotal;
			// $localRatio = $resortConfig->local / $ratioTotal;

            // $ratioTotal = $resortConfig->xpat ;
			// $xpatRatio = $resortConfig->xpat;
			// $localRatio = $resortConfig->local ;


            $ratioTotal = $resortConfig->xpat + $resortConfig->local; // Total of xpat and local positions

            if ($ratioTotal > 0)
            {
                $xpatRatio = ($resortConfig->xpat / $ratioTotal) * 100;
                $localRatio = ($resortConfig->local / $ratioTotal) * 100;
            } else
            {
                $xpatRatio = 0;
                $localRatio = 0;
            }
			$employeeCounts = Employee::where('resort_id', $resortId)
				->selectRaw('
					COUNT(*) as total_count,
					SUM(CASE WHEN nationality = "Maldivian" THEN 1 ELSE 0 END) as local_count,
					SUM(CASE WHEN nationality != "Maldivian" THEN 1 ELSE 0 END) as xpat_count
				')
				->first();
			$currentXpatRatio = $employeeCounts->xpat_count / max(1, $employeeCounts->total_count);
			$employeeType = $currentXpatRatio < $xpatRatio ? 'other' : 'Maldivian';
			$basicSalary = DB::table('resort_budget_costs')
				->where('resort_id', $resortId)
				->where('status', 'active')
				->where(function ($query) use ($employeeType)
                {
					if ($employeeType != 'Maldivian')
                    {
						$query->where('details', 'Xpat Only')->orWhere('details', 'Both');
					}
                    else
                    {
						$query->where('details', 'Locals Only')->orWhere('details', 'Both');
					}
				})
				->where('particulars', 'like', '%Basic Salary%')
				->value('amount') ?? 520;

			// Get all applicable costs
			$costs = DB::table('resort_budget_costs')
				->where('resort_id', $resortId)
				->where('status', 'active')
				->where(function ($query) use ($employeeType) {
					if ($employeeType != 'Maldivian') {
						$query->where('details', 'Xpat Only')
							  ->orWhere('details', 'Both');
					} else {
						$query->where('details', 'Locals Only')
							  ->orWhere('details', 'Both');
					}
				})
				->get();

			$totalCost = self::calculateTotalCost($costs, $basicSalary, $vacantCount);

			return [
				'total_cost' =>$totalCost,
				'employee_type' => $employeeType,
				'basic_salary' => $basicSalary
			];
        } catch (\Exception $e) {
			\Log::error('Error in CheckVacantBudgetCost: ' . $e->getMessage());
			return [
				'total_cost' => '0.00',
				'employee_type' => null,
				'error' => 'Failed to calculate vacant budget cost'
			];
		}
	}

	private static function calculateTotalCost($costs, $basicSalary, $vacantCount) {
		$totalCost = 0;

		foreach ($costs as $cost) {
			$amount = (float)$cost->amount;
			$isPercentage = $cost->amount_unit === '%';

			$monthlyAmount = self::convertToMonthlyAmount(
				$amount,
				$cost->frequency,
				$isPercentage,
				$basicSalary
			);

			$totalCost += $monthlyAmount * $vacantCount;
		}

		return $totalCost;
	}

	private static function convertToMonthlyAmount($amount, $frequency, $isPercentage, $basicSalary) {
		if ($isPercentage) {
			$amount = ($basicSalary * $amount) / 100;
		}

		switch (ucfirst(strtolower($frequency))) {
			case 'Monthly':
				return $amount;
			case 'Yearly':
				return $amount / 12;
			case 'Quarterly':
				return $amount / 3;
			case 'Daily':
				return $amount * 30; // Assuming 30 days per month
			case 'One-time':
				return $amount / 12;
			case 'Hourly':
				return $amount * (8 * 22); // 8 hours per day, 22 working days
			default:
				return 0;
		}
	}
    public static function SliceParegraph($string)
    {

        try
        {
            $string = strip_tags($string);
            $string = preg_replace('/\s+/', "\n", $string);
            $string = wordwrap($string, 100, "\n");
            $lines = explode("\n", $string);
            $firstThreeLines = array_slice($lines, 0, 3);
            return implode("\n", $firstThreeLines);
        }
        catch (\Exception $e) {
            return " ";
        }

    }

    // Common Code to use in more then one place
    public static function GetTheFreshVacancies($resortId,$status,$rank="",$takeData="")
    {
        $config = config('settings.Position_Rank');
        if($rank !="")
        {
            $rank = (int)$rank;
        }

        $VacanciesQuery = Vacancies::join('employees as t1','t1.id','=','vacancies.reporting_to')
                                    ->join('t_anotification_parents as t2','t2.V_id','=','vacancies.id')
                                    ->join('t_anotification_children as t3','t3.Parent_ta_id','=','t2.id')
                                    ->join('resort_departments as t4','t4.id','=','vacancies.department')
                                    ->join('resort_positions as t5','t5.id','=','vacancies.position')
                                    ->join('resort_admins as t6','t6.id','=','t1.Admin_Parent_id')
                                    ->leftJoin('resort_admins as creator','creator.id','=','vacancies.created_by')
                                    ->leftJoin('employees as creator_emp','creator_emp.Admin_Parent_id','=','creator.id')
                                    ->where('vacancies.Resort_id',$resortId);
        							// dd($rank , Common::TaFinalApproval($resortId));
                                    if ($rank == Common::TaFinalApproval($resortId))
                                    {


                                        $VacanciesQuery->whereIn('t3.Approved_By',[7,8])
                                        ->where('t3.status','Approved')
                                        ->where('t3.Approved_By', '!=', Common::TaFinalApproval($resortId));
                                    //   $VacanciesQuery->whereExists(function ($query) use ($resortId) {
                                    //         $query->select(DB::raw(1))
                                    //             ->from('t_anotification_children as other_approvals')
                                    //             ->join('t_anotification_parents as other_parent', 'other_parent.id', '=', 'other_approvals.Parent_ta_id')
                                    //             ->whereColumn('other_parent.V_id', 'vacancies.id')
                                    //             ->where('other_approvals.status', 'ForwardedToNext')
                                    //             ->where(function ($subQuery) use ($resortId) {
                                    //                 $subQuery->whereIn('other_approvals.Approved_By', [7, 8])
                                    //                         ->where('other_approvals.Approved_By', '!=', Common::TaFinalApproval($resortId));
                                    //             });
                                    //     });
                                    }
                                    else
                                    {

                                        if (isset($rank))
                                        {
                                            $VacanciesQuery->where(function ($query) use ($rank,$status) {
                                                if($rank == 3) //HR
                                                {
                                                    $query->where('t3.Approved_By', '=', $rank)
                                                    ->where('t3.status',$status);
                                                }
                                                elseif($rank == 9) //Todo
                                                {
                                                    $query->where('t3.Approved_By', '=',8)
                                                    ->where('t3.status',"Approved")
                                                    ->where('t3.Approved_By',"!=",8);
                                                }
                                                elseif($rank == 8)
                                                {
                                                    $query->where('t3.Approved_By', '=',7)
                                                    ->where('t3.status',"Approved")
                                                    ->where('t3.Approved_By',"!=",8);
                                                }
                                                elseif($rank == 7)
                                                {
                                                    $query->where('t3.Approved_By', 7)
                                                    ->where('t3.status', $status);
                                                }
												elseif($rank == 2)
                                                {
                                                    $query->where('t3.Approved_By',3)
                                                    ->where('t3.status',$status);
                                                }
                                                else
                                                {
                                                    // EXCOM and other ranks: show items pending at HR level
                                                    $query->where('t3.Approved_By', '=', 3)
                                                    ->where('t3.status', $status);
                                                }

                                            });
                                        }
                                    }
                                    if(empty($takeData))
                                    {
                                        $VacanciesQuery->take(8);
                                    }
                                    $VacanciesQuery->where('vacancies.status', '=', "Active");

               $Vacancies = $VacanciesQuery->latest('vacancies.created_at')

                ->get([
                    't3.reason',
                    't1.rank',
                    'vacancies.id as V_id',
                    't2.id as ta_id',
                    't3.id as Child_ta_id',
                    't5.position_title as Position',
                    't4.name as Department',
                    'vacancies.resort_id',
                    't6.id as user_id',
                    't6.first_name',
                    't6.last_name',
					'vacancies.Total_position_required as NoOfVacnacy',
                    'vacancies.required_starting_date as Required',
                    'vacancies.budgeted as Budget',
                    'vacancies.employee_type as EmployeeType',
                    'vacancies.required_starting_date as Required',
                    't3.status',
                    't3.Approved_By',
                    'vacancies.created_at',
                    DB::raw("CONCAT(creator.first_name, ' ', creator.last_name) as created_by_name"),
                    'creator_emp.rank as creator_rank',

                ])
                ->unique('V_id')
                ->values()
                ->map(function ($vacancy) use ($config) {
                    $vacancy->rank_name = $config[$vacancy->rank] ?? 'Unknown Rank';
                    $vacancy->creator_rank_name = $config[$vacancy->creator_rank] ?? '';
                    $vacancy->ReportingTo =  $vacancy->first_name.'  ' .$vacancy->last_name;
                    // Compute approval status from notification children
                    $statusMap = ['Active' => 'Pending HR', 'Approved' => 'In Progress', 'ForwardedToNext' => 'Forwarded', 'Rejected' => 'Rejected', 'Hold' => 'On Hold'];
                    $vacancy->approval_status = $statusMap[$vacancy->status] ?? $vacancy->status;
                    return $vacancy;
                });

			return   $Vacancies ;
        //     $VacanciesQuery = ResortAdmin::join("t_anotification_parents as t5", "t5.resort_id", "=", "resort_admins.id")
        //     ->join("t_anotification_children as t6", "t6.Parent_ta_id", "=", "t5.V_id")
        //     ->join("vacancies as t1", "t1.id", "=", "t5.V_id")
        //     ->join('resort_departments as t2', 't2.id', '=', 't1.department')
        //     ->join('resort_positions as t3', 't3.id', '=', 't1.position')
        //     // ->join('employees as t4', 't4.Admin_Parent_id', '=', 't1.reporting_to')
        //     ->where("t6.status", "Active")
        //     ->where('t1.resort_id', $resortId);

        // if (isset($rank))
        // {
        //     $VacanciesQuery->where(function ($query) use ($rank)
        //     {
        //         $query->where('t6.Approved_By', '!=', $rank)
        //             ->orWhereNull('t6.Approved_By');
        //     });
        // }

        //  $Vacancies = $VacanciesQuery->latest('t6.created_at')
        //     ->take(7)
        //     ->get([
        //         't6.reason',
        //         // 't4.rank',
        //         'resort_admins.id as admin_ui',
        //         't1.id as V_id',
        //         't6.id as ta_id',
        //         't3.position_title as Position',
        //         't2.name as Department',
        //         'resort_admins.id as resort_id',

        //         'resort_admins.first_name',
        //         'resort_admins.last_name',

        //         't1.required_starting_date as Required',
        //         't1.budgeted as Budget',
        //         't1.employee_type as EmployeeType',
        //         't1.required_starting_date as Required',
        //         't1.rank'
        //     ])
        //     ->map(function ($vacancy) use ($config) {
        //         $vacancy->rank_name = $config[$vacancy->rank] ?? 'Unknown Rank';
        //         $vacancy->ReportingTo =  $vacancy->first_name.'  ' .$vacancy->last_name;
        //         return $vacancy;
        //     });
    }

    public static function TaFinalApproval($resort_id)
    {
        $final = JobAdvertisement::where("Resort_id",$resort_id)->first();

		if($final)
        	return $final->FinalApproval;

    }

    public static function GmApprovedVacancy($resort_id,$rank,$take="")
    {

        $config = config('settings.Position_Rank');
        // $rank=6;


        if(3 == $rank  )
        {


            // $TodoData = ResortAdmin::join("t_anotification_parents as t5", "t5.resort_id", "=", "resort_admins.id")
            // ->join("t_anotification_children as t6", "t6.Parent_ta_id", "=", "t5.V_id")
            // ->join("vacancies as t1", "t1.id", "=", "t5.V_id")
            // ->join('resort_departments as t2', 't2.id', '=', 't1.department')
            // ->join('resort_positions as t3', 't3.id', '=', 't1.position')
            // ->join('employees as t4', 't4.Admin_Parent_id', '=', 't1.created_by')
            // ->join('job_advertisements as t7', 't7.Resort_id', '=', 'resort_admins.id')
            // ->leftjoin('application_links as t8', 't8.ta_child_id ', '=', 't6.id')
            // ->where("t6.status", "Approved")
            // ->where('t5.resort_id', $resort_id)
            // ->where('t6.Approved_By', '=', $rank)
            // ->latest('t6.created_at')
            // ->take(7)
            // ->get([
            //     't6.reason',
            //     't4.rank',
            //     't1.id as V_id',
            //     't6.id as ta_id',
            //     't3.position_title as Position',
            //     't2.name as Department',
            //     'resort_admins.id as user_id',
            //     't7.Jobadvimg',
            //     't7.link as adv_link',
            //     't7.link_Expiry_date as ExpiryDate'
            // ])->map(function ($vacancy) use ($config,$resort_id) {
            //     $vacancy->rank_name = $config[$vacancy->rank] ?? 'Unknown Rank';


            //     $resort_id_decode =base64_encode($resort_id.'/'.$vacancy->ta_id.'/'.$vacancy->V_id);
            //     $applicant_link = route('resort.applicantForm',$resort_id_decode);
            //     if(isset($vacancy->adv_link))
            //     {
            //         $vacancy->applicant_link =$vacancy->adv_link;

            //     }
            //     else
            //     {
            //         $vacancy->applicant_link = route('resort.applicantForm',$resort_id_decode);
            //     }

            //     $vacancy->applicationUrlshow = substr($applicant_link, 0, 30).'...';
            //     $vacancy->JobAdvertisement= URL::asset(config('settings.Resort_JobAdvertisement').'/'. Auth::guard('resort-admin')->user()->resort->resort_id."/".$vacancy->Jobadvimg);
            //     return $vacancy;
            // });

            $resort_Location =  Auth::guard('resort-admin')->user()->resort->resort_id;

            $VacanciesQuery = Vacancies::join('employees as t1','t1.id','=','vacancies.reporting_to')
                ->join('t_anotification_parents as t2','t2.V_id','=','vacancies.id')
                ->join('t_anotification_children as t3','t3.Parent_ta_id','=','t2.id')
                ->join('resort_departments as t4','t4.id','=','vacancies.department')
                ->join('resort_positions as t5','t5.id','=','vacancies.position')
                ->join('resort_admins as t6','t6.id','=','t1.Admin_Parent_id')
                ->join('job_advertisements as t7', 't7.Resort_id', '=', 'vacancies.Resort_id')
                ->leftjoin('application_links as t8', 't8.ta_child_id', '=', 't3.id')
                ->leftjoin('applicant_form_data as t9', 't9.Parent_v_id', '=', 'vacancies.id')

                ->leftjoin('applicant_wise_statuses as t10', function ($join) {
                            $join->on('t10.Applicant_id', '=', 't9.id')
                                ->whereRaw('t10.id = (
                                    SELECT MAX(id)
                                    FROM applicant_wise_statuses
                                    WHERE Applicant_id = t9.id
                                )')
                                ->where('t10.status', '=', 'Sortlisted')
                                ->where('t10.As_ApprovedBy', '=',3);


                })
                ->leftjoin('applicant_inter_view_details as t11', function ($join) {
                    $join->on('t11.Applicant_id', '=', 't9.id')
                        ->whereRaw('t11.id = (
                            SELECT MAX(id)
                            FROM applicant_inter_view_details
                            WHERE Applicant_id = t9.id
                        )');


                })
				->leftJoin('job_descriptions as jd', function ($join) {
				    $join->on('jd.Resort_id', '=', 'vacancies.Resort_id')
				        ->on('jd.Position_id', '=', 'vacancies.position');
				})
                ->where('vacancies.status', '=', "Active")
                ->where('vacancies.Resort_id',$resort_id)
                ->where('t3.status',"ForwardedToNext")
                ->where('vacancies.status', '=', "Active")
                ->where('t3.Approved_By', '=',Common::TaFinalApproval($resort_id))
                ->where(function($q) {
                    $q->whereNull('t8.link')->orWhere('t8.link', '');
                })
                ->latest('t3.created_at');
            if(!isset($take))
            {
                $VacanciesQuery->take(7);
            }
                $Vacancies = $VacanciesQuery->get(
                [
                                't3.reason',
                                't1.rank',
                                't3.Approved_By',
                                'vacancies.id as V_id',
                                't3.id as ta_childid',

                                't5.position_title as Position',
								't5.id as Position_id',
                                't4.name as Department',
								't4.id as Deprt_id',
                                'vacancies.Resort_id',
                                't6.id as user_id',
                                't6.first_name',
                                't6.last_name',

                                'vacancies.required_starting_date as Required',
                                'vacancies.budgeted as Budget',
                                'vacancies.employee_type as EmployeeType',
                                'vacancies.required_starting_date as Required',
                                't7.Jobadvimg',
                                't8.link as adv_link',
                                't8.link_Expiry_date as ExpiryDate',
                                // DB::raw('COUNT(t9.Parent_v_id) as applicant_count'),
                                't9.first_name',
                                't9.last_name',
                                't10.id as ApplicantStatus',
                                't9.passport_photo',
                                't9.id As ApplicantID',
                                't10.id as ApplicantStatus_id',
                                't11.Status as InterviewLinkStatus',
                                't10.status as ApplicationStatus',
                                't10.As_ApprovedBy',
								'jd.jobdescription as JobDescription'

                ])
                // ->map(function ($vacancy) use ($config,$resort_id,$resort_Location)
                // {
                //     $vacancy->rank_name = $config[$vacancy->rank] ?? 'Unknown Rank';


                //         $resort_id_decode =base64_encode($resort_id.'/'.$vacancy->ta_childid.'/'.$vacancy->V_id);
                //         $applicant_link = route('resort.applicantForm',$resort_id_decode);
                //         $vacancy->applicant_link = route('resort.applicantForm',$resort_id_decode);
                //         $vacancy->applicationUrlshow = substr($applicant_link, 0, 30).'...';
                //         $vacancy->JobAdvertisement= URL::asset(config('settings.Resort_JobAdvertisement').'/'. Auth::guard('resort-admin')->user()->resort->resort_id."/".$vacancy->Jobadvimg);
                //         $vacancy->profileImg = URL::asset( $vacancy->passport_photo);
                //         // $vacancy->InterviewLinkStatus =  $vacancy->InterviewLinkStatus == "null" ?  "Active": $vacancy->InterviewLinkStatuss;
                //         $vacancy->ApplicationStatus = $vacancy->ApplicationStatus == null ? " " : $vacancy-> ApplicationStatus;
                //         $vacancy->As_ApprovedBy = $vacancy->As_ApprovedBy == null ? 25 : $vacancy-> As_ApprovedBy;
				// 		$Questionnaire    = Questionnaire::where('Resort_id',$resort_id)
				// 											->where('Department_id',$vacancy->Deprt_id)
				// 											->where('Position_id',$vacancy->Position_id)
				// 											->first();

				// 		if(isset($Questionnaire->id))
				// 		{
				// 			$vacancy->LinkShareOrNot = "Yes";
				// 		}
				// 		else
				// 		{
				// 			$vacancy->LinkShareOrNot = "No";

				// 		}
                //         return $vacancy;
                // });
				->map(function ($vacancy) use ($config, $resort_id, $resort_Location) {
					$vacancy->rank_name = $config[$vacancy->Approved_By] ?? 'Unknown Rank';

					// Generate base applicant link
					$resort_id_decode = base64_encode($resort_id . '/' . $vacancy->ta_childid . '/' . $vacancy->V_id);
					$applicant_link_base = route('resort.applicantForm', $resort_id_decode);

					// Add source links
					$hiringSources = HiringSource::where('resort_id', $resort_id)->get();
					$sourceLinks = [];
					foreach ($hiringSources as $source) {
						$sourceIdEncoded = base64_encode($source->id);
						$sourceLinks[] = $applicant_link_base . '?source=' . $sourceIdEncoded;
					}

					$vacancy->source_links = $sourceLinks; // Store all source links

					// Default single applicant link (if needed)
					$vacancy->applicant_link = $applicant_link_base;

					// Shortened URL for display
					$vacancy->applicationUrlshow = substr($applicant_link_base, 0, 30) . '...';

					// Generate other links
					$vacancy->JobAdvertisement = URL::asset(config('settings.Resort_JobAdvertisement') . '/' . Auth::guard('resort-admin')->user()->resort->resort_id . "/" . $vacancy->Jobadvimg);
					// All job advertisement images for this resort
					$allJobAds = JobAdvertisement::where('Resort_id', $resort_id)->get();
					$vacancy->allJobAdImages = $allJobAds->map(function($ad) use ($resort_id) {
						return URL::asset(config('settings.Resort_JobAdvertisement') . '/' . $resort_id . '/' . $ad->Jobadvimg);
					})->values()->toArray();
					$vacancy->profileImg = URL::asset($vacancy->passport_photo);
					$vacancy->ApplicationStatus = $vacancy->ApplicationStatus == null ? " " : $vacancy->ApplicationStatus;
					$vacancy->As_ApprovedBy = $vacancy->As_ApprovedBy == null ? 25 : $vacancy->As_ApprovedBy;

					// Check for questionnaire
					$Questionnaire = Questionnaire::where('Resort_id', $resort_id)
						->where('Department_id', $vacancy->Deprt_id)
						->where('Position_id', $vacancy->Position_id)
						->first();

					$vacancy->LinkShareOrNot = isset($Questionnaire->id) ? "Yes" : "No";

					return $vacancy;
				});
				return $Vacancies;
        }
        else
        {
            return collect();
        }
    }

	public static function getTimezoneByCountry($country) {
		// dd($country);
		$timezones = DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $country);
		return $timezones ?: 'Unknown Timezone';
	}

	/**
     * Send email using a template */
	public static function sendTemplateEmail($Module =null,$templateId, $recipientEmail, $dynamicData)
	{

		try {
			// Fetch the template
            if($Module == "Disciplinary")
            {
                $template =  DisciplinaryEmailmodel::findOrFail($templateId);
            // Replace placeholders in the email body and subject
                $body = self::replacePlaceholders($template->content, $dynamicData);

                $subject = self::replacePlaceholders($template->subject, $dynamicData);
            }
            if($Module=="TalentAcquisition")
            {
                $template = TaEmailTemplate::findOrFail($templateId);
                // Replace placeholders in the email body and subject
                $body = self::replacePlaceholders($template->MailTemplete, $dynamicData);

                $subject = self::replacePlaceholders($template->MailSubject, $dynamicData);
            }

			TaEmailSent::dispatch($recipientEmail, $subject, ['mainbody' => $body]);

			return true;
		} catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
			// Specific exception for when the template is not found
			\Log::error("Email template not found: " . $e->getMessage());
			return "Template not found.";
		} catch (\Exception $e) {
			// Catch all other exceptions
			\Log::error("Failed to send email: " . $e->getMessage());
			return "Failed to send email: " . $e->getMessage();
		}
	}

    /**
     * Replace placeholders in the template
     *
     * @param string $template
     * @param array $data
     * @return string
     */
    private static function replacePlaceholders($template, $data)
    {
        foreach ($data as $key => $value) {
            $template = str_replace("{{" . $key . "}}", $value, $template);
        }
        return $template;
    }

    public static function GetRosterdata($resort_id,$duty_roster_id,$Employee,$WeekstartDate, $WeekendDate, $startOfMonth,$endOfMonth,$flag)
    {

        if($flag =="weekly")
        {

            $DutyRoster = DutyRoster::join('duty_roster_entries as t2','t2.Emp_id',"=","duty_rosters.Emp_id")
             ->join('shift_settings as t1','t1.id',"=","t2.Shift_id")
             ->whereBetween('t2.date',[$WeekstartDate, $WeekendDate])
             ->where('t1.resort_id','=',$resort_id)
             ->where('duty_rosters.id','=',$duty_roster_id)
            //  ->where('duty_rosters.Year','=',date('Y'))
             ->orderBy('t2.date','asc')
             ->get(['t2.Status','t2.id as Attd_id','t2.date','t2.Shift_id','duty_rosters.DayOfDate','t1.ShiftName','OverTime','t1.StartTime','t1.EndTime','t2.DayWiseTotalHours'])
             ->map(function ($roster)  {
                 if($roster->ShiftName =="First Shift")
                 {
                     $roster->ShiftNameColor = "createDuty-green";
                 }
                 if($roster->ShiftName =="Second Shift")
                 {
                     $roster->ShiftNameColor = "createDuty-yellow";
                 }
                 if($roster->ShiftName =="General Shift")
                 {
                     $roster->ShiftNameColor = "createDuty-skyBlue";
                 }
                 if($roster->ShiftName =="Night Shift")
                 {
                     $roster->ShiftNameColor = "createDuty-purple";
                 }

                 if($roster->DayOfDate == date('D',strtotime($roster->date)))
                 {
                     $roster->DayOfDate = $roster->DayOfDate;
                 }


                 return $roster;
             });
        }
        if($flag =="Monthwise")
        {
            // $startOfMonth = Carbon::now()->startOfMonth()->format('Y-m-d'); // First day of the month
            // $endOfMonth = Carbon::now()->endOfMonth()->format('Y-m-d'); // Last day of the month
            // $startOfMonth = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');

            // // End of the previous month

            // $endOfMonth = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
                $LeaveCategory = LeaveCategory::where('resort_id', $resort_id)->get(['leave_type']);
                $DutyRoster = DutyRoster::join('duty_roster_entries as t2', 't2.Emp_id', '=', 'duty_rosters.Emp_id')
                    ->join('shift_settings as t1', 't1.id', '=', 't2.Shift_id')
                    ->whereBetween('t2.date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
                    // ->where('duty_rosters.Year', '=', $startOfMonth->format('Y'))
                    ->where('t1.resort_id', '=', $resort_id)
                    ->where('duty_rosters.id', '=', $duty_roster_id)
                    ->orderBy('t2.date', 'asc')
                    ->get([
                        't2.Status', 't2.id as Attd_id', 't2.Emp_id', 't2.date', 't2.Shift_id', 'duty_rosters.DayOfDate',
                        't1.ShiftName', 'OverTime', 't1.StartTime', 't1.EndTime', 't2.DayWiseTotalHours'
                    ])
                    ->map(function ($roster)    use ($LeaveCategory,$resort_id) {

                        // Add ShiftNameColor like in weekly format
                        if($roster->ShiftName =="First Shift")
                        {
                            $roster->ShiftNameColor = "createDuty-green";
                        }
                        if($roster->ShiftName =="Second Shift")
                        {
                            $roster->ShiftNameColor = "createDuty-yellow";
                        }
                        if($roster->ShiftName =="General Shift")
                        {
                            $roster->ShiftNameColor = "createDuty-skyBlue";
                        }
                        if($roster->ShiftName =="Night Shift")
                        {
                            $roster->ShiftNameColor = "createDuty-purple";
                        }

                        $statusCount = [ ];

                        foreach ($LeaveCategory as $leave) {
                            $statusCount[$leave->leave_type] = 0;
                        }

                        // Get Employee Leave data
                        $EmployeeLeave = EmployeeLeave::join('leave_categories as t4', 't4.id', '=', 'employees_leaves.leave_category_id')
                            ->where('employees_leaves.Emp_id', $roster->Emp_id)
                            ->where('employees_leaves.status', 'Approved')
                            ->where(function ($query) use ($roster) {
                                $query->whereDate('employees_leaves.from_date', '<=', $roster->date)
                                    ->whereDate('employees_leaves.to_date', '>=', $roster->date);
                            })
                            ->first(['t4.color', 't4.leave_type', 'employees_leaves.total_days', 'employees_leaves.from_date', 'employees_leaves.to_date', 'employees_leaves.leave_category_id']);

                        $roster->LeaveType = $EmployeeLeave->leave_type ?? $roster->Status;
                        $roster->LeaveDays = $EmployeeLeave->total_days ?? null;
                        $roster->LeaveFromDate = $EmployeeLeave->from_date ?? null;
                        $roster->LeaveToDate = $EmployeeLeave->to_date ?? null;
                        $LeaveCategorycolur  = LeaveCategory::where('resort_id', $resort_id)->where("leave_type", $roster->Status)->first(['color']);

                        if (isset($roster->Status)) {
                            if (isset($EmployeeLeave->color)) {
                                $roster->LeaveColor = $EmployeeLeave->color;
                            } elseif (isset($LeaveCategorycolur->color)) {
                                $roster->LeaveColor = $LeaveCategorycolur->color;
                            } else {

                                $roster->LeaveColor = "";
                            }
                        } else {
                            $roster->LeaveColor = "#be09af";
                        }


                            if (isset($statusCount[$roster->Status]))
                            {

                                $statusCount[$roster->Status] += 1;
                            }
                            if (isset($statusCount[$roster->leave_type]))
                            {
                                $statusCount[$roster->leave_type] += 1;
                            }

                        $roster->StatusCount = $statusCount;
                                if(isset($EmployeeLeave->leave_type)) {
                                    $roster->LeaveFirstName = substr($EmployeeLeave->leave_type, 0, 1);
                                }
                                elseif(isset($roster->Status))
                                {
                                    $roster->LeaveFirstName = substr($roster->Status, 0, 1);
                                }
                                else
                                {
                                    $roster->LeaveFirstName = "-";
                                }
                        return $roster;
                    });

        }
        return $DutyRoster;
    }

    public static function GetOverTime($resort_id,$duty_roster_id,$Employee,$WeekstartDate, $WeekendDate,$startOfMonth ,$endOfMonth,$flag)
    {

        if($flag =="weekly")
        {
            $DutyRoster = DutyRoster::join('parent_attendaces as t2','t2.Emp_id',"=","duty_rosters.Emp_id")
             ->join('shift_settings as t1','t1.id',"=","t2.Shift_id")
            //  ->whereBetween('t2.date',[$WeekstartDate, $WeekendDate])
             ->where('t1.resort_id','=',$resort_id)
             ->where('duty_rosters.id','=',$duty_roster_id)
            //  ->where('duty_rosters.Year','=',date('Y'))
             ->orderBy('t2.date','asc')
             ->whereIn('t2.Status',['Present','Absent','DayOff'])

             ->get(['t2.Status','t2.id as Attd_id','t2.date','t2.Shift_id','duty_rosters.DayOfDate','t1.ShiftName','OverTime','t1.StartTime','t1.EndTime','t2.DayWiseTotalHours'])
             ->map(function ($roster)
             {

                 if($roster->ShiftName =="First Shift")
                 {
                     $roster->ShiftNameColor = "createDuty-green";
                 }
                 if($roster->ShiftName =="Second Shift")
                 {
                     $roster->ShiftNameColor = "createDuty-yellow";
                 }
                 if($roster->ShiftName =="General Shift")
                 {
                     $roster->ShiftNameColor = "createDuty-skyBlue";
                 }
                 if($roster->ShiftName =="Night Shift")
                 {
                     $roster->ShiftNameColor = "createDuty-purple";
                 }

                 if($roster->DayOfDate == date('D',strtotime($roster->date)))
                 {
                     $roster->DayOfDate = $roster->DayOfDate;
                 }


                 $PublicHoliday= PublicHoliday::where('holiday_date',date('d-m-Y',strtotime($roster->date)))->first();

                if(isset($PublicHoliday))
                {
                    $roster->publicholiday = "yes";
                }
                else
                {

                    $roster->publicholiday = "no";
                }
                 return $roster;
             });
        }

        if($flag =="Monthwise")
        {
            $startOfMonth = Carbon::now()->startOfMonth()->format('Y-m-d'); // First day of the month
            $endOfMonth = Carbon::now()->endOfMonth()->format('Y-m-d'); // Last day of the month

                $LeaveCategory = LeaveCategory::where('resort_id', $resort_id)->get(['leave_type']);
                $DutyRoster = DutyRoster::join('parent_attendaces as t2', 't2.Emp_id', '=', 'duty_rosters.Emp_id')
                    ->join('shift_settings as t1', 't1.id', '=', 't2.Shift_id')
                    ->whereBetween('t2.date', [$startOfMonth, $endOfMonth])
                    ->where('duty_rosters.Year', '=', date('Y'))
                    ->where('t1.resort_id', '=', $resort_id)
                    ->where('duty_rosters.id', '=', $duty_roster_id)
                    ->orderBy('t2.date', 'asc')

                    ->whereIn('t2.Status',['Present','Absent','DayOff'])
                    ->get([
                        't2.Status', 't2.id as Attd_id', 't2.Emp_id', 't2.date', 't2.Shift_id', 'duty_rosters.DayOfDate',
                        't1.ShiftName', 'OverTime', 't1.StartTime', 't1.EndTime', 't2.DayWiseTotalHours'
                    ])
                    ->map(function ($roster)    use ($LeaveCategory,$resort_id) {

                        $statusCount = [ ];

                        foreach ($LeaveCategory as $leave) {
                            $statusCount[$leave->leave_type] = 0;
                        }


                        // Get Employee Leave data
                        $EmployeeLeave = EmployeeLeave::join('leave_categories as t4', 't4.id', '=', 'employees_leaves.leave_category_id')
                            ->where('employees_leaves.Emp_id', $roster->Emp_id)
                            ->where('employees_leaves.status', 'Approved')
                            ->where(function ($query) use ($roster) {
                                $query->whereDate('employees_leaves.from_date', '<=', $roster->date)
                                    ->whereDate('employees_leaves.to_date', '>=', $roster->date);
                            })
                            ->first(['t4.color', 't4.leave_type', 'employees_leaves.total_days', 'employees_leaves.from_date', 'employees_leaves.to_date', 'employees_leaves.leave_category_id']);

                        $roster->LeaveType = $EmployeeLeave->leave_type ?? $roster->Status;
                        $roster->LeaveDays = $EmployeeLeave->total_days ?? null;
                        $roster->LeaveFromDate = $EmployeeLeave->from_date ?? null;
                        $roster->LeaveToDate = $EmployeeLeave->to_date ?? null;
                        $LeaveCategorycolur  = LeaveCategory::where('resort_id', $resort_id)->where("leave_type", $roster->Status)->first(['color']);

                         $roster->LeaveColor = isset($roster->Status) ?(isset($EmployeeLeave) ? $EmployeeLeave->color : '#9E5CF726') :'#9E5CF726';

                            if (isset($statusCount[$roster->Status]))
                            {
                                $statusCount[$roster->Status] += 1;
                            }
                            if (isset($statusCount[$roster->leave_type]))
                            {
                                $statusCount[$roster->leave_type] += 1;
                            }

                        $roster->StatusCount = $statusCount;

                        if(isset($EmployeeLeave->leave_type))
                        {
                            $roster->LeaveFirstName = substr($EmployeeLeave->leave_type, 0, 1);
                            $roster->LeaveFullName = $EmployeeLeave->leave_type;
                        }
                        elseif(isset($roster->Status))
                        {
                            $roster->LeaveFirstName = $roster->Status;
                            $roster->LeaveFullName = substr($roster->Status, 0, 1);
                        }
                        else
                        {
                            $roster->LeaveFirstName = "-";
                            $roster->LeaveFullName = "-";
                        }
                        $PublicHoliday= PublicHoliday::where('holiday_date',date('d-m-Y',strtotime($roster->date)))->first();

                        if(isset($PublicHoliday))
                        {
                            $roster->publicholiday = "yes";
                        }
                        else
                        {

                            $roster->publicholiday = "no";
                        }
                        return $roster;
                    });


        }
        return $DutyRoster;
    }

    /**
     * Safely parse a time string to Carbon instance
     * Validates that the time is in valid format (HH:MM or H:MM) and hours are 0-23
     */
    private static function safeParseTime($timeString)
    {
        if (empty($timeString)) {
            return null;
        }

        // Check if it's a valid time format (HH:MM or H:MM)
        if (!preg_match('/^(\d{1,2}):(\d{2})$/', $timeString, $matches)) {
            return null;
        }

        $hours = (int)$matches[1];
        $minutes = (int)$matches[2];

        // Validate hours (0-23) and minutes (0-59)
        if ($hours < 0 || $hours > 23 || $minutes < 0 || $minutes > 59) {
            return null;
        }

        try {
            return Carbon::parse($timeString);
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function GetAttandanceRegister($resort_id,$duty_roster_id,$Employee,$WeekstartDate, $WeekendDate,$startOfMonth,$endOfMonth,$flag)
    {

        if($flag =="weekly")
        {

            $WeekstartDate = $WeekstartDate->copy()->format('Y-m-d');
            $WeekendDate= $WeekendDate->format('Y-m-d');

            $DutyRoster = DutyRoster::join('parent_attendaces as t2', 't2.Emp_id', '=', 'duty_rosters.Emp_id')
            ->join('shift_settings as t1', 't1.id', '=', 't2.Shift_id')
            ->leftJoin('child_attendaces as t3', 't3.Parent_attd_id', '=', 't2.id')
            ->whereBetween('t2.date', [$WeekstartDate, $WeekendDate])
            ->where('t1.resort_id', '=', $resort_id)
            ->where('duty_rosters.id', '=', $duty_roster_id)
            // ->where('duty_rosters.Year', '=', $WeekstartDateCarbon->format('Y'))
            ->orderBy('t2.date', 'asc')
            ->get([
                't2.OTStatus', 't2.OTApproved_By', 't3.id as Child_Attd_id', 't3.InTime_Location', 't3.OutTime_Location',
                't2.CheckingOutTime', 't2.CheckingTime', 't2.Status', 't2.id as Attd_id', 't2.Emp_id', 't2.date',
                't2.Shift_id', 'duty_rosters.DayOfDate', 't1.ShiftName', 'OverTime', 't1.StartTime', 't1.EndTime',
                't2.DayWiseTotalHours'
            ])
            ->map(function ($roster) use($WeekstartDate, $WeekendDate,$resort_id) {
                // Format times
                $checkInTimeParsed = self::safeParseTime($roster->CheckingTime);
                $roster->CheckInTime = $checkInTimeParsed ? $checkInTimeParsed->format('h:i A') : null;

                $checkOutTimeParsed = self::safeParseTime($roster->CheckingOutTime);
                $roster->CheckOutTime = $checkOutTimeParsed ? $checkOutTimeParsed->format('h:i A') : null;

                // Fetch approved name
                $approved_name = ResortAdmin::where('id', $roster->OTApproved_By)->first(['first_name', 'last_name']);
                $roster->ApprovedName = isset($approved_name) ? ucfirst($approved_name->first_name . ' ' . $approved_name->last_name): "";

                // Check internal status
                $startTimeParsed = self::safeParseTime($roster->StartTime);
                $checkInTimeParsed = self::safeParseTime($roster->CheckingTime);
                if ($checkInTimeParsed && $startTimeParsed) {
                    $difference = $startTimeParsed->diffInMinutes($checkInTimeParsed, false);
                    $roster->InternalStatus = $difference <= 10 && $difference >= 0
                        ? 'OnTime'
                        : ($difference > 10 ? 'Late' : 'Early');
                }

                $roster->Status = in_array($roster->Status, ['ShortLeave', 'HalfDayLeave', 'FullDayLeave'])
                    ? 'FullDayLeave'
                    : $roster->Status;

                $roster->DayOfDate = $roster->DayOfDate == date('D', strtotime($roster->date))
                    ? $roster->DayOfDate
                    : null;

                    $startTimeParsed = self::safeParseTime($roster->StartTime);
                    $roster->StartTimeShow = $startTimeParsed ? $startTimeParsed->format('h:i A') : null;

                    $endTimeParsed = self::safeParseTime($roster->EndTime);
                    $roster->EndTimeShow = $endTimeParsed ? $endTimeParsed->format('h:i A') : null;

                    $endTime = $endTimeParsed;

                    $overTime = $roster->OverTime ?? "00:00";

                    if ($endTime) {
                        list($hours, $minutes) = explode(':', $overTime);
                        $updatedEndTime = $endTime->copy()->addHours($hours)->addMinutes($minutes);
                    } else {
                        $updatedEndTime = null;
                    }

                    // Convert to formatted time for display
                    $formattedUpdatedEndTime = $updatedEndTime ? $updatedEndTime->format('h:i A') : null;

                    $currentTime24 = Carbon::now();
                    $currentTime24 = Carbon::now()->setTime(15, 25, 0); // Sets the time to 1:00 PM

                    $roster->msg = "PleaseCheckout";
                    if ($updatedEndTime && $updatedEndTime->lessThan($currentTime24)) {


                        // Calculate the difference
                        $differenceInMinutes = $updatedEndTime->diffInMinutes($currentTime24);
                        $roster->differenceInHours = $updatedEndTime->diff($currentTime24)->format('%h hours and %i minutes');

                    }
                    else
                    {
                        $roster->msg = "Continue";
                    }

                    $Leavevcategory = LeaveCategory::join('employees_leaves as t1', 't1.leave_category_id', '=', 'leave_categories.id')
                                                ->where('t1.Emp_id', $roster->Emp_id)
                                                ->where('leave_categories.resort_id', $resort_id)
                                                ->where(function ($query) use ($WeekstartDate, $WeekendDate) {
                                                    $query->whereBetween('t1.from_date', [$WeekstartDate, $WeekendDate]) // Leave starts in the month
                                                        ->orWhereBetween('t1.to_date', [$WeekstartDate, $WeekendDate])   // Leave ends in the month
                                                        ->orWhere(function ($query) use ($WeekstartDate, $WeekendDate) { // Leave spans the entire month
                                                            $query->where('t1.from_date', '<', $WeekstartDate)
                                                                ->where('t1.to_date', '>', $WeekendDate);
                                                        });
                                                })
                                                ->where('t1.status', 'Approved')
                                        ->where('leave_categories.resort_id', $resort_id)
                                    ->get(['t1.total_days','leave_categories.leave_type','leave_categories.id as leave_cat_id','t1.from_date','t1.to_date','t1.Emp_id','t1.status']);
                                    $transformedLeaveData = $Leavevcategory->map(function ($item) {
                                        return $item->only(['total_days', 'leave_type', 'leave_cat_id', 'from_date', 'to_date', 'Emp_id', 'status']);
                                    })->values()->toArray();
                                 $roster->LeaveData = $transformedLeaveData;
                return $roster;
            });

        }

        if($flag =="Monthwise")
        {
            // $startOfMonth = Carbon::now()->startOfMonth()->format('Y-m-d'); // First day of the month
            // $endOfMonth = Carbon::now()->endOfMonth()->format('Y-m-d'); // Last day of the month


                $DutyRoster = DutyRoster::join('parent_attendaces as t2', 't2.Emp_id', '=', 'duty_rosters.Emp_id')
                    ->join('shift_settings as t1', 't1.id', '=', 't2.Shift_id')
                    ->leftJoin('child_attendaces as t3', 't3.Parent_attd_id', '=', 't2.id')
                    ->whereBetween('t2.date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])

                    // ->where('duty_rosters.Year', '=', date('Y'))
                    ->where('t1.resort_id', '=', $resort_id)
                    ->where('duty_rosters.id', '=', $duty_roster_id)
                    ->orderBy('t2.date', 'asc')
                    ->get([
                        't2.OTStatus', 't2.OTApproved_By', 't3.id as Child_Attd_id', 't3.InTime_Location', 't3.OutTime_Location',
                        't2.CheckingOutTime', 't2.CheckingTime', 't2.Status', 't2.id as Attd_id', 't2.Emp_id', 't2.date',
                        't2.Shift_id', 'duty_rosters.DayOfDate', 't1.ShiftName', 'OverTime', 't1.StartTime', 't1.EndTime',
                        't2.DayWiseTotalHours'
                    ])
                    ->map(function ($roster) use($resort_id,$startOfMonth,$endOfMonth) {
                        // Format times
                        $checkInTimeParsed = self::safeParseTime($roster->CheckingTime);
                        $roster->CheckInTime = $checkInTimeParsed ? $checkInTimeParsed->format('h:i A') : null;

                        $checkOutTimeParsed = self::safeParseTime($roster->CheckingOutTime);
                        $roster->CheckOutTime = $checkOutTimeParsed ? $checkOutTimeParsed->format('h:i A') : null;

                        // Fetch approved name
                        $approved_name = ResortAdmin::where('id', $roster->OTApproved_By)->first(['first_name', 'last_name']);
                        $roster->ApprovedName = isset($approved_name) ? ucfirst($approved_name->first_name . ' ' . $approved_name->last_name): "";

                        // Check internal status
                        $startTimeParsed = self::safeParseTime($roster->StartTime);
                        $checkInTimeParsed = self::safeParseTime($roster->CheckingTime);
                        if ($checkInTimeParsed && $startTimeParsed) {
                            $difference = $startTimeParsed->diffInMinutes($checkInTimeParsed, false);
                            $roster->InternalStatus = $difference <= 10 && $difference >= 0
                                ? 'OnTime'
                                : ($difference > 10 ? 'Late' : 'Early');
                        }

                        $roster->Status = in_array($roster->Status, ['ShortLeave', 'HalfDayLeave', 'FullDayLeave','Abse'])
                            ? 'FullDayLeave'
                            : $roster->Status;

                        $roster->DayOfDate = $roster->DayOfDate == date('D', strtotime($roster->date))
                            ? $roster->DayOfDate
                            : null;

                            $startTimeParsed = self::safeParseTime($roster->StartTime);
                            $roster->StartTimeShow = $startTimeParsed ? $startTimeParsed->format('h:i A') : null;

                            $endTimeParsed = self::safeParseTime($roster->EndTime);
                            $roster->EndTimeShow = $endTimeParsed ? $endTimeParsed->format('h:i A') : null;

                            $endTime = $endTimeParsed;

                            $overTime = $roster->OverTime ?? "00:00";

                            if ($endTime) {

                                $time = $overTime ?? '0:0';
                            
                                $parts = explode(':', $time);
                            
                                $hours = $parts[0] ?? 0;
                                $minutes = $parts[1] ?? 0;
                            
                                $updatedEndTime = $endTime->copy()
                                    ->addHours((int)$hours)
                                    ->addMinutes((int)$minutes);
                            
                            } else {
                                $updatedEndTime = null;
                            }
                            

                            // Convert to formatted time for display
                            $formattedUpdatedEndTime = $updatedEndTime ? $updatedEndTime->format('h:i A') : null;

                            $currentTime24 = Carbon::now();
                            $currentTime24 = Carbon::now()->setTime(15, 25, 0); // Sets the time to 1:00 PM

                            $roster->msg = "PleaseCheckout";
                            if ($updatedEndTime && $updatedEndTime->lessThan($currentTime24)) {


                                // Calculate the difference
                                $differenceInMinutes = $updatedEndTime->diffInMinutes($currentTime24);
                                $roster->differenceInHours = $updatedEndTime->diff($currentTime24)->format('%h hours and %i minutes');

                            }
                            else
                            {
                                $roster->msg = "Continue";
                            }

                            // Get leave data
                            $Leavevcategory = LeaveCategory::join('employees_leaves as t1', 't1.leave_category_id', '=', 'leave_categories.id')
                                                ->where('t1.Emp_id', $roster->Emp_id)
                                                ->where('leave_categories.resort_id', $resort_id)
                                                ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                                                    $query->whereBetween('t1.from_date', [$startOfMonth, $endOfMonth]) // Leave starts in the month
                                                        ->orWhereBetween('t1.to_date', [$startOfMonth, $endOfMonth])   // Leave ends in the month
                                                        ->orWhere(function ($query) use ($startOfMonth, $endOfMonth) { // Leave spans the entire month
                                                            $query->where('t1.from_date', '<', $startOfMonth)
                                                                ->where('t1.to_date', '>', $endOfMonth);
                                                        });
                                                })
                                                ->where('t1.status', 'Approved')
                                        ->where('leave_categories.resort_id', $resort_id)
                                    ->get(['t1.total_days','leave_categories.leave_type','leave_categories.id as leave_cat_id','t1.from_date','t1.to_date','t1.Emp_id','t1.status']);
                                    $transformedLeaveData = $Leavevcategory->map(function ($item) {
                                        return $item->only(['total_days', 'leave_type', 'leave_cat_id', 'from_date', 'to_date', 'Emp_id', 'status']);
                                    })->values()->toArray();
                                 $roster->LeaveData = $transformedLeaveData;
                        return $roster;
                    });


        }
        return $DutyRoster;
    }
    public static function getWeekCountInMonth()
    {
        $month=12;
        $year = 2025;
        // Get the first and last days of the month
        $startOfMonth = Carbon::createFromDate($year, $month, 1);
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        // Get the first and last weeks of the month
        $firstWeek = $startOfMonth->weekOfYear;
        $lastWeek = $endOfMonth->weekOfYear;

        // Handle edge case for December spanning into January
        if ($firstWeek > $lastWeek) {
            return ($lastWeek + 52) - $firstWeek + 1;
        }

        return $lastWeek - $firstWeek + 1;
    }


    public static function getSubordinates($employeeId, $subordinates = [], $visited = [])
    {
        // Prevent infinite loops from circular reporting structures
        if (in_array($employeeId, $visited)) {
            return $subordinates;
        }

        // Mark this employee as visited
        $visited[] = $employeeId;

        $directSubordinates = Employee::where('reporting_to', $employeeId)->pluck('id')->toArray();

        foreach ($directSubordinates as $subordinateId) {
            // Only add if not already in subordinates list
            if (!in_array($subordinateId, $subordinates)) {
                $subordinates[] = $subordinateId;
            }
            // Pass visited array to prevent cycles
            $subordinates = self::getSubordinates($subordinateId, $subordinates, $visited);
        }

        return $subordinates;
    }

    public static function getEmpGrade($rank){
        if($rank == 1 || $rank == 3 || $rank == 7 || $rank == 8){
            $emp_grade = "1";
        }
        else if($rank == 4){
            $emp_grade = "4";
        }
        else if($rank == 2){
            $emp_grade = "2";
        }
        else if($rank == 5){
            $emp_grade = "5";
        }
        else{
            $emp_grade = "6";
        }
        return $emp_grade;
    }

    public static function getBenefitGrid($emp_grade,$resort_id){
        $benefit_grid = ResortBenifitGrid::where('emp_grade', $emp_grade)
            ->where('resort_id', $resort_id)
            ->where('status', 'active')
            ->first();
        return $benefit_grid;
    }

    public static function GetThemeColor($status)
    {
        $color = ColorTheme::where('name', $status)->first();
        if(isset($color->color))
        {
            return $color->color;
        }
        else
        {
            return "#FED049";
        }
    }



    public static function GetEmployeeDetails($emp_id)
    {

      return  ResortAdmin::join('employees as t1', 't1.Admin_Parent_id', '=', 'resort_admins.id')
        ->where('t1.id', $emp_id)
        ->first(['resort_admins.id as Parent_id','resort_admins.first_name','resort_admins.last_name']);
    }
    private function getNextApprover($leave)
    {
        $approverHierarchy = [
            '2' => '1',
            '1' => '3',
            '3' => '7',
            '7' => '8',
        ];

        $currentRank = $leave->current_approver_rank;
        $nextRank = $approverHierarchy[$currentRank] ?? null;

        if ($nextRank) {
            $nextApprover = Employee::where('rank', $nextRank)->first(); // Adjust query based on your hierarchy logic
            return [
                'id' => $nextApprover->id ?? null,
                'rank' => $nextRank,
            ];
        }

        return null; // No next approver
    }
    public static function dutyRosterMonthAndWeekWise($resort_id,$duty_roster_id,$Employee,$WeekstartDate, $WeekendDate,$startOfMonth,$endOfMonth,$year,$month,$flag)
    {
        if($flag =="weekly")
        {
            $WeekstartDate      = $WeekstartDate->copy()->format('Y-m-d');
            $WeekendDate        = $WeekendDate->format('Y-m-d');

            $datesInWeek        = [];
            $dateIterator       = Carbon::parse($WeekstartDate);
            while ($dateIterator->lte(Carbon::parse($WeekendDate))) {
                $datesInWeek[]  = $dateIterator->format('Y-m-d');
                $dateIterator->addDay();
            }
                $LeaveCategory          = LeaveCategory::where('resort_id', $resort_id)->get(['leave_type']);
                $DutyRoster             = DutyRoster::join('parent_attendaces as t2', 't2.Emp_id', '=', 'duty_rosters.Emp_id')
                                            ->join('shift_settings as t1', 't1.id', '=', 't2.Shift_id')
                                            ->join('child_attendaces as t3', 't3.Parent_attd_id', '=', 't2.id')
                                            ->whereBetween('t2.date', [$WeekstartDate, $WeekendDate])
                                            // ->where('duty_rosters.Year', '=', $startOfMonth->format('Y'))
                                            ->where('t1.resort_id', '=', $resort_id)
                                            ->where('duty_rosters.id', '=', $duty_roster_id)
                                            ->orderBy('t2.date', 'asc')
                                            ->get([
                                                't2.Status', 't2.CheckInCheckOut_Type','t2.id as Attd_id', 't2.Emp_id', 't2.date', 't2.Shift_id', 'duty_rosters.DayOfDate',
                                                't1.ShiftName', 'OverTime', 't1.StartTime', 't1.EndTime', 't2.DayWiseTotalHours','t2.CheckingTime','t2.CheckingOutTime','t3.InTime_Location','t3.OutTime_Location'
                                            ])
                                            ->map(function ($roster)    use ($LeaveCategory,$resort_id) {

                                                $roster->TotalTime = static::calculateTotalTime($roster->OverTime, $roster->CheckingTime, $roster->CheckingOutTime);

                                                if ($roster->ShiftName == "Afternoon") {
                                                    $roster->ShiftColor             = "#FED049";
                                                } elseif ($roster->ShiftName == "Morning") {
                                                    $roster->ShiftColor             = "#014653";
                                                } elseif ($roster->ShiftName == "Evening") {
                                                    $roster->ShiftColor             = "#2EACB3";
                                                } elseif ($roster->ShiftName == "Night") {
                                                    $roster->ShiftColor             = "#9E5CF7";
                                                } else {
                                                    $roster->ShiftColor             = '';
                                                }

                                                if (!empty($roster->InTime_Location) && preg_match('/center=(-?\d+\.\d+),(-?\d+\.\d+)/', $roster->InTime_Location, $matches)) {
                                                    $roster->inTime_lat             = $matches[1];  // Latitude
                                                    $roster->inTime_long            = $matches[2]; // Longitude
                                                } else {
                                                    $roster->inTime_lat             = null;
                                                    $roster->inTime_long            = null;
                                                }

                                                // Extract coordinates from OutTime_Location
                                                if (!empty($roster->OutTime_Location) && preg_match('/center=(-?\d+\.\d+),(-?\d+\.\d+)/', $roster->OutTime_Location, $matches)) {
                                                    $roster->outTime_lat            = $matches[1];  // Latitude
                                                    $roster->outTime_long           = $matches[2]; // Longitude
                                                } else {
                                                    $roster->outTime_lat            = null;
                                                    $roster->outTime_long           = null;
                                                }

                $statusCount            = [ ];

                foreach ($LeaveCategory as $leave) {
                    $statusCount[$leave->leave_type] = 0;
                }


                // Get Employee Leave data
                $EmployeeLeave          = EmployeeLeave::join('leave_categories as t4', 't4.id', '=', 'employees_leaves.leave_category_id')
                                            ->where('employees_leaves.Emp_id', $roster->Emp_id)
                                            ->where('employees_leaves.status', 'Approved')
                                            ->where(function ($query) use ($roster) {
                                                $query->whereDate('employees_leaves.from_date', '<=', $roster->date)
                                                    ->whereDate('employees_leaves.to_date', '>=', $roster->date);
                                            })
                                            ->first(['t4.color', 't4.leave_type', 'employees_leaves.total_days', 'employees_leaves.from_date', 'employees_leaves.to_date', 'employees_leaves.leave_category_id']);

                $roster->LeaveType      = $EmployeeLeave->leave_type ?? $roster->Status;
                $roster->LeaveDays      = $EmployeeLeave->total_days ?? null;
                $roster->LeaveFromDate  = $EmployeeLeave->from_date ?? null;
                $roster->LeaveToDate    = $EmployeeLeave->to_date ?? null;
                $LeaveCategorycolur     = LeaveCategory::where('resort_id', $resort_id)->where("leave_type", $roster->Status)->first(['color']);

                if (isset($roster->Status)) {
                    if (isset($EmployeeLeave->color)) {
                        $roster->LeaveColor     = $EmployeeLeave->color;
                    } elseif (isset($LeaveCategorycolur->color)) {
                        $roster->LeaveColor     = $LeaveCategorycolur->color;
                    } else {

                        $roster->LeaveColor     = "";
                    }
                } else {
                    $roster->LeaveColor         = "#be09af";
                }

                if (isset($statusCount[$roster->Status]))
                {
                    $statusCount[$roster->Status] += 1;
                }
                if (isset($statusCount[$roster->leave_type]))
                {
                    $statusCount[$roster->leave_type] += 1;
                }

                $roster->StatusCount    = $statusCount;
                    if(isset($EmployeeLeave->leave_type)) {
                        $roster->LeaveFirstName     = substr($EmployeeLeave->leave_type, 0, 1);
                    }
                    elseif(isset($roster->Status))
                    {
                        $roster->LeaveFirstName     = substr($roster->Status, 0, 1);
                    }
                    else
                    {
                        $roster->LeaveFirstName     = "-";
                    }
                    return $roster;
                });

            $existingDates = $DutyRoster->pluck('date')->toArray();
            foreach ($datesInWeek as $date) {
                if (!in_array($date, $existingDates)) {
                    $DutyRoster->push((object)[
                        'Status'            => null,
                        'Attd_id'           => null,
                        'Emp_id'            => null,
                        'date'              => $date,
                        'Shift_id'          => null,
                        'DayOfDate'         => Carbon::parse($date)->format('D'),
                        'ShiftName'         => null,
                        'OverTime'          => null,
                        'StartTime'         => null,
                        'EndTime'           => null,
                        'DayWiseTotalHours' => null,
                        'LeaveType'         => null,
                        'LeaveDays'         => null,
                        'LeaveFromDate'     => null,
                        'LeaveToDate'       => null,
                        'LeaveColor'        => "",
                        'LeaveFirstName'    => "-",
                    ]);
                }
            }
            $DutyRoster = $DutyRoster->sortBy('date')->values();


        }

        if($flag =="Monthwise")
        {
            if($year && $month){
                $startOfMonth           = Carbon::create($year, $month, 1)->startOfMonth()->format('Y-m-d');
                $endOfMonth             = Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d');
            } else {
                $startOfMonth           =    $startOfMonth->format('Y-m-d');
                $endOfMonth             =    $endOfMonth->format('Y-m-d');
            }
            $datesInMonth               = [];
            $dateIterator               = Carbon::parse($startOfMonth);
            while ($dateIterator->lte($endOfMonth)) {
                $datesInMonth[]         = $dateIterator->format('Y-m-d');
                $dateIterator->addDay();
            }

                // End of the previous month
                $LeaveCategory          = LeaveCategory::where('resort_id', $resort_id)->get(['leave_type']);
                $DutyRoster             = DutyRoster::join('duty_roster_entries as t2', 't2.Emp_id', '=', 'duty_rosters.Emp_id')
                                            ->join('shift_settings as t1', 't1.id', '=', 't2.Shift_id')
                                            ->leftJoin('child_attendaces as t3', 't3.Parent_attd_id', '=', 't2.id')
                                            ->whereBetween('t2.date', [$startOfMonth, $endOfMonth])
                                            // ->where('duty_rosters.Year', '=', $startOfMonth->format('Y'))
                                            ->where('t1.resort_id', '=', $resort_id)
                                            ->where('duty_rosters.id', '=', $duty_roster_id)
                                            ->orderBy('t2.date', 'asc')
                                            ->get([
                                                't2.Status', 't2.CheckInCheckOut_Type','t2.id as Attd_id', 't2.Emp_id', 't2.date', 't2.Shift_id', 'duty_rosters.DayOfDate',
                                                't1.ShiftName', 'OverTime', 't1.StartTime', 't1.EndTime', 't2.DayWiseTotalHours','t2.CheckingTime','t2.CheckingOutTime','t3.InTime_Location','t3.OutTime_Location'
                                            ])
                                            ->map(function ($roster)    use ($LeaveCategory,$resort_id) {

                                                $roster->TotalTime = static::calculateTotalTime($roster->OverTime, $roster->CheckingTime, $roster->CheckingOutTime);

                                                if ($roster->ShiftName == "Afternoon") {
                                                    $roster->ShiftColor             = "#FED049";
                                                } elseif ($roster->ShiftName == "Morning") {
                                                    $roster->ShiftColor             = "#014653";
                                                } elseif ($roster->ShiftName == "Evening") {
                                                    $roster->ShiftColor             = "#2EACB3";
                                                } elseif ($roster->ShiftName == "Night") {
                                                    $roster->ShiftColor             = "#9E5CF7";
                                                } else {
                                                    $roster->ShiftColor             = '';
                                                }

                                                if (!empty($roster->InTime_Location) && preg_match('/center=(-?\d+\.\d+),(-?\d+\.\d+)/', $roster->InTime_Location, $matches)) {
                                                    $roster->inTime_lat             = $matches[1];  // Latitude
                                                    $roster->inTime_long            = $matches[2]; // Longitude
                                                } else {
                                                    $roster->inTime_lat             = null;
                                                    $roster->inTime_long            = null;
                                                }

                                                // Extract coordinates from OutTime_Location
                                                if (!empty($roster->OutTime_Location) && preg_match('/center=(-?\d+\.\d+),(-?\d+\.\d+)/', $roster->OutTime_Location, $matches)) {
                                                    $roster->outTime_lat            = $matches[1];  // Latitude
                                                    $roster->outTime_long           = $matches[2]; // Longitude
                                                } else {
                                                    $roster->outTime_lat            = null;
                                                    $roster->outTime_long           = null;
                                                }

                $statusCount            = [ ];

                foreach ($LeaveCategory as $leave) {
                    $statusCount[$leave->leave_type] = 0;
                }


                        // Get Employee Leave data
                $EmployeeLeave          = EmployeeLeave::join('leave_categories as t4', 't4.id', '=', 'employees_leaves.leave_category_id')
                                            ->where('employees_leaves.Emp_id', $roster->Emp_id)
                                            ->where('employees_leaves.status', 'Approved')
                                            ->where(function ($query) use ($roster) {
                                                $query->whereDate('employees_leaves.from_date', '<=', $roster->date)
                                                    ->whereDate('employees_leaves.to_date', '>=', $roster->date);
                                            })
                                            ->first(['t4.color', 't4.leave_type', 'employees_leaves.total_days', 'employees_leaves.from_date', 'employees_leaves.to_date', 'employees_leaves.leave_category_id']);

                $roster->LeaveType      = $EmployeeLeave->leave_type ?? $roster->Status;
                $roster->LeaveDays      = $EmployeeLeave->total_days ?? null;
                $roster->LeaveFromDate  = $EmployeeLeave->from_date ?? null;
                $roster->LeaveToDate    = $EmployeeLeave->to_date ?? null;
                $LeaveCategorycolur     = LeaveCategory::where('resort_id', $resort_id)->where("leave_type", $roster->Status)->first(['color']);

                if (isset($roster->Status)) {
                    if (isset($EmployeeLeave->color)) {
                        $roster->LeaveColor     = $EmployeeLeave->color;
                    } elseif (isset($LeaveCategorycolur->color)) {
                        $roster->LeaveColor     = $LeaveCategorycolur->color;
                    } else {

                        $roster->LeaveColor     = "";
                    }
                } else {
                    $roster->LeaveColor         = "#be09af";
                }

                if (isset($statusCount[$roster->Status]))
                {
                    $statusCount[$roster->Status] += 1;
                }
                if (isset($statusCount[$roster->leave_type]))
                {
                    $statusCount[$roster->leave_type] += 1;
                }

                $roster->StatusCount    = $statusCount;
                    if(isset($EmployeeLeave->leave_type)) {
                        $roster->LeaveFirstName     = substr($EmployeeLeave->leave_type, 0, 1);
                    }
                    elseif(isset($roster->Status))
                    {
                        $roster->LeaveFirstName     = substr($roster->Status, 0, 1);
                    }
                    else
                    {
                        $roster->LeaveFirstName     = "-";
                    }
                    return $roster;
                });

            $existingDates = $DutyRoster->pluck('date')->toArray();
            foreach ($datesInMonth as $date) {
                if (!in_array($date, $existingDates)) {
                    $DutyRoster->push((object)[
                        'Status'            => null,
                        'Attd_id'           => null,
                        'Emp_id'            => null,
                        'date'              => $date,
                        'Shift_id'          => null,
                        'DayOfDate'         => Carbon::parse($date)->format('D'),
                        'ShiftName'         => null,
                        'OverTime'          => null,
                        'StartTime'         => null,
                        'EndTime'           => null,
                        'DayWiseTotalHours' => null,
                        'LeaveType'         => null,
                        'LeaveDays'         => null,
                        'LeaveFromDate'     => null,
                        'LeaveToDate'       => null,
                        'LeaveColor'        => "",
                        'LeaveFirstName'    => "-",
                    ]);
                }
            }
            $DutyRoster = $DutyRoster->sortBy('date')->values();
        }
        return $DutyRoster;
    }

    private static function calculateTotalTime($overTime, $checkingTime, $checkingOutTime)
    {
        $totalMinutes = 0;

        // Calculate difference between CheckingOutTime and CheckingTime
        if ($checkingTime && $checkingOutTime) {
            [$checkInHours, $checkInMinutes]            = explode(':', $checkingTime);
            [$checkOutHours, $checkOutMinutes]          = explode(':', $checkingOutTime);

            $checkInTotal                               = ((int)$checkInHours * 60) + (int)$checkInMinutes;
            $checkOutTotal                              = ((int)$checkOutHours * 60) + (int)$checkOutMinutes;

            // If CheckingOutTime is on the next day
            if ($checkOutTotal < $checkInTotal) {
                $checkOutTotal                          += 24 * 60; // Add 24 hours
            }

            $totalMinutes                               += $checkOutTotal - $checkInTotal;
        }

        $totalHours                                      = floor($totalMinutes / 60);
        $remainingMinutes                                = $totalMinutes % 60;

        return sprintf('%02d:%02d', $totalHours, $remainingMinutes);
    }

     public static function calculateEWT($taxableIncomeMVR)
    {
        $brackets = DB::table('ewt_tax_brackets')->orderBy('min_salary')->get();
        $ewt = 0;

        foreach ($brackets as $bracket) {
            $min = $bracket->min_salary;
            $max = is_null($bracket->max_salary) ? PHP_INT_MAX : $bracket->max_salary;
            $rate = $bracket->tax_rate;

            // Log::info("Bracket: min=$min, max=$max, rate=$rate");

            if ($taxableIncomeMVR > $min) {
                $taxableAmount = min($taxableIncomeMVR, $max) - $min;
                if ($taxableAmount > 0) {
                    $ewt += $taxableAmount * ($rate / 100);
                }
            }
        }

        return max($ewt, 0); // floor at 0
    }

    public static function calculatePension($salaryInMVR, $pensionRate = 7)
    {
        return round(($salaryInMVR * $pensionRate) / 100, 2);
    }

    public static function getServiceCharge($employee_id, $resortId,$payrollId){
        $service_charge = PayrollServiceCharge::where('payroll_id',$payrollId)->where('employee_id',$employee_id)->first();

        return $service_charge ? (float) $service_charge['service_charge_amount'] : 0;
    }

    public static function getMonthlyAllowances($employeeType, $resort_id, $basic_salary,$frequency,$getformated = 0) {
		// Start with basic salary as the base cost
		$totalCost = $basic_salary;

		$data = DB::table('resort_budget_costs')
			->where('resort_id', $resort_id)
			->where('status', 'active')
			->where("particulars", "!=", "Basic Salary")
			->where('cost_title', 'Operational Cost')
            ->where('frequency',$frequency)
			->where(function ($query) use ($employeeType) {
				if ($employeeType != 'Maldivian') {
					$query->where('details', 'Xpat Only')
						->orWhere('details', 'Both');
				} elseif ($employeeType == 'Maldivian') {
					$query->where('details', 'Locals Only')
						->orWhere('details', 'Both');
				}
			})
			->get();
        // dd($data);

		foreach ($data as $c) {
			$frequency = ucfirst(strtolower($c->frequency));
			$amount = $c->amount;
			$unit = $c->amount_unit;
			$headcount = 1;

			switch ($frequency) {
				case 'Monthly':
					if ($unit == '%' && $basic_salary) {
						$totalCost += (($basic_salary * $amount) / 100) * $headcount;
					} else {
						$totalCost += $amount * $headcount;
					}
					break;
			}
		}

        if($getformated !=0)
        {
            return number_format($totalCost, 2);
        }
        else
        {
        	return $totalCost;
        }
	}
    public static function getGriveanceID()
    {
        $predefinedCode = 0;
        $grievance = GrivanceSubmissionModel::orderBy('id', 'desc')->first();
        if (!$grievance) {
            $predefinedCode = 1;
        } else {


            $lastCode = explode("-", $grievance->Grivance_id );

            $predefinedCode = (int)$lastCode[1] + 1;
        }

        $Grivance = "GR-" . str_pad($predefinedCode, 4, "0", STR_PAD_LEFT);

        return $Grivance;
    }

    public static function getDisciplinaryID()
    {
        $predefinedCode = 0;
        $grievance = disciplinarySubmit::orderBy('id', 'desc')->first();
        if (!$grievance)
        {
            $predefinedCode = 1;
        }
        else
        {
            $lastCode = explode("-", $grievance->Disciplinary_id);
            $predefinedCode = (int)$lastCode[1] + 1;
        }
        $Grivance = "Disciplinary-" . str_pad($predefinedCode, 4, "0", STR_PAD_LEFT);
        return $Grivance;
    }
    public static function PartOfCommitteeMember($id,$resort_id)
    {

        $data = GrivanceInvestigationModel::join('grievance_committee_member_parents as t1','t1.id',"=","grivance_investigation_models.Committee_id")
                                            ->join('grievance_committee_member_children as t2','t2.Parent_id',"=","t1.id")
                                            ->where('t1.resort_id',$resort_id)
                                            ->where("t2.Committee_Member_Id",$id)
                                            ->get(['t1.id']);
                            $array=[];
                                            foreach($data as $d)
                                            {
                                                $array[]= $d->id;
                                            }

                                            return $array;
    }

    public static function ResortNotification($user_id,$resort_id)
    {
        $r = ResortNotification::join('employees as t1',"t1.id","=","resort_notifications.user_id")
        ->join('resort_admins as t2',"t2.id","=","t1.Admin_Parent_id")
        ->where("resort_notifications.user_id", $user_id)
        ->where("resort_notifications.resort_id", $resort_id)
        ->where('resort_notifications.status', 'unread')
        ->latest()
        ->take(10)
        ->get(['resort_notifications.*','t2.id as Parentid']);
        $string='';
        $time = Carbon::now()->format('Y-m-d H:i:s') ;

        if($r->isNotEmpty())
        {
            foreach($r as $ak)
            {
                $url = Common::getResortUserPicture($ak->Parentid);

                    $string .= ' <div class="notification-box active class_remove_me_'.$ak->id.'">
                                    <a href="#" class="d-flex  profile-dropdown ">
                                        <div class="flex-shrink-0 img-box " >
                                            <img src="'. $url .'" alt="..." class="img-fluid" />
                                        </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5>'.$ak->type.'</h5>
                                        <p>' .$ak->message.' </p>
                                        <br>
                                        <span>Current Date and Time:'.$time.'</span>
                                    </div>
                                </a>
                                    <a href="javascript:void(0);" class="btn-lg-icon btn-light-grey MarkNotification" data-id="'.$ak->id .'">
                                    <i class="fas fa-envelope-open" aria-hidden="true"></i>
                                </a>
                            </div>';
            }
            return $string;
        }else{
            $string .='<div class="notification-box">
                        <p>No Notification</p>
                    </div>';
                    return $string;
        }
    }

    public static function GetResortMenu($resort_id,$active_url)
    {

        $data = Modules::get(['id as ModuleId','module_name as ModuleName']);
        $menu=[];
        foreach($data as $m)
        {
            if(!in_array($m->ModuleName,   $menu))
            {

                $PageIsexit =  ModulePages::where("Module_Id",$m->ModuleId)->where("internal_route",$active_url)->first();
                if(isset($PageIsexit))
                {
                    $PageIsActive = 'active';
                }
                else{
                    $PageIsActive = 'inactive';
                }
                $menu[] =  ["ModuleId"=>$m->ModuleId,"ModuleName"=> $m->ModuleName,'PageIsActive'=>$PageIsActive];
            }
        }

       return ["menu"=>$menu,"resort_id"=>$resort_id];
    }
    public static function GetResortMenuPage($ModuleId)
    {
        $pagesList  = ModulePages::where("Module_Id",$ModuleId)
            ->where('TypeOfPage','InsideOfMenu')
            ->where('type','normal')
            ->where('status','Active')
            ->whereNull('deleted_at')
            ->orderBy('place_order','asc')
            ->get(['page_name','id as Page_id','internal_route','type','TypeOfPage'])->toArray();
        $newpagelist=[] ;
        foreach($pagesList as $p)
        {
            $newpagelist[] = [
                "Page_id"=>$p['Page_id'],
                "PageName"=>$p['page_name'],
                'route'=>$p['internal_route'],
                'Type'=>$p['type'],
                'TypeOfPage'=>$p['TypeOfPage']
            ];
        }
        return $newpagelist;
    }
    public static function GetrouteWiseModuleDetails($route)
    {
        $pagesList  = ModulePages::where("internal_route",$route)->first();

        return   $pagesList;

    }
    public static function FindResortHR($resort)
    {
        $resort_id = is_object($resort) ? $resort->resort_id : $resort;

        return Employee::where('resort_id', $resort_id)
            ->whereHas('department', function ($q) {
                $q->whereIn(DB::raw('LOWER(name)'), ['human resources', 'hr'])
                ->whereIn('rank',[1,2]);
            })
            ->first();
    }


    public static function CreateEmployeeFolder( $resortId ,$main_folder,$Folder_Name)
    {

        $main_folder = $main_folder;

        $resortId =  $resortId;


            $uniqueString = substr(md5(uniqid($Folder_Name, true)), 0, 10);


                $UnderON = 0;


                FilemangementSystem::Create([
                        'resort_id' =>$resortId ,
                        'Folder_Name' => $Folder_Name,
                        'Folder_unique_id' => $uniqueString,
                        'UnderON'=>$UnderON,
                        'Folder_Type' => 'categorized'
                ]);

                $folderPath = $main_folder . '/public/categorized/' . $uniqueString . '/.gitkeep';


                Storage::disk('s3')->put($folderPath, '');
                DB::commit();

               return true;
               DB::beginTransaction();
               try{   }
        catch (\Exception $e)
        {
                \Log::emergency("File: ".$e->getFile());
                \Log::emergency("Line: ".$e->getLine());
                \Log::emergency("Message: ".$e->getMessage());

                return  false;
        }

    }


    public static function CreateFirstTimeEmployeeFolders($resortId, $main_folder, $Folder_Name)
    {
        try {
            DB::beginTransaction();

            $uniqueString = substr(md5(uniqid($Folder_Name, true)), 0, 10);
            $UnderON = 0;

            // Define the main folder path
            $basePath = 'public/categorized/' . $uniqueString;
            $Emp_main_folder = $main_folder . '/' . $basePath . '/.gitkeep';

            // Check and create main folder in S3
            if (!Storage::disk('s3')->exists($Emp_main_folder)) {
                $s3Result = Storage::disk('s3')->put($Emp_main_folder, '');
            } else {
                $s3Result = true;
            }

            if (!$s3Result) {
                throw new \Exception("Failed to create main folder in S3");
            }

            // Create the main folder DB record
            $fileFolder = FilemangementSystem::Create([
                'resort_id' => $resortId,
                'Folder_Name' => $Folder_Name,
                'Folder_unique_id' => $uniqueString,
                'UnderON' => $UnderON,
                'Folder_Type' => 'categorized'
            ]);

            if (!$fileFolder) {
                throw new \Exception("Failed to create main folder record");
            }

            // Subfolders to create
            $folders_array = [
                'Contract_Signed',
                'Benefit_Grid_Received',
                'Job_Description_Received',
                'Work_Permit',
                'Flight_Ticket_Received',
                'Profile',
                'Signature',
                'LeaveAttachments',
                'MaintanceRequest',
                'IncidentAttatchements',
                'GrivanceAttachments',
                'DisciplinaryAttachments',
                'HousekeepingImages',
                'employeeSelfie',
                'ResignationAttachments',
                'RequestAttachments',
                'clinicMedicalCertificateAttachments',
                'clinicTreatmentAttachment',
                'clinicAttachments',
                'EmployeesDocument',
                'EmployeesChatAttachments'
            ];

            foreach ($folders_array as $folder) {
                $cleanFolder = str_replace(' ', '_', $folder); // normalize folder name
                $subFolderPath = $main_folder . '/' . $basePath . '/' . $cleanFolder . '/.gitkeep';

                // Check if subfolder already exists
                if (!Storage::disk('s3')->exists($subFolderPath)) {
                    $s3SubResult = Storage::disk('s3')->put($subFolderPath, '');

                    if (!$s3SubResult) {
                        throw new \Exception("Failed to create subfolder {$folder} in S3");
                    }

                    // Create subfolder DB record only if not exists
                    $newUniqueString = substr(md5(uniqid($folder, true)), 0, 10);
                    $subFolder = FilemangementSystem::Create([
                        'resort_id' => $resortId,
                        'Folder_Name' => $cleanFolder,
                        'Folder_unique_id' => $newUniqueString,
                        'UnderON' => $fileFolder->id,
                        'Folder_Type' => 'categorized'
                    ]);

                    if (!$subFolder) {
                        throw new \Exception("Failed to create record for subfolder {$folder}");
                    }
                }
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            \Log::error("Error creating employee folders: " . $e->getMessage());
            DB::rollBack();
            return false;
        }
    }






    public static function FilePermissions($File_id,$resort,$TypeOfFolder =null)
    {
        $array=[];
        if($resort->type == "Supper")
        {
            $hr = $this->FindResortHR($resort->resort->resort);
            $department = $hr->Dept_id;
               $Employee = Employee::where("Dept_id", $department)
                        ->where("Position_id",$hr->Position_id)
                        ->where("resort_id",$resort->resort_id)
                        ->get(['Admin_Parent_id'])->map(function($i)
                        {
                            $i->profile =  Common::getResortUserPicture($i->Admin_Parent_id);
                            return $i;
                        })->toArray();

                        $array['type']=true;
                        $array['emp']=$Employee;
            return $array;
        }
        elseif($resort->type == "sub")
        {

            if(in_array($resort->GetEmployee->rank ,[1,3,8,4,9])) // EXCOM HR MGR GM MD
            {

                $Department_id = $resort->GetEmployee->Dept_id;
                $Position_id = $resort->GetEmployee->Position_id;

                $filePermission = FilePermissions::where("file_id",$File_id)
                                                ->first();

               if($resort->GetEmployee->rank == 3 && isset($filePermission))
               {
                $Emp_id = $resort->GetEmployee->Emp_id;

                $FilePermissions = FilePermissions::where("file_id", $File_id)->get();

                // Initialize collection for Employee1
                $Employee1 = collect();

                foreach ($FilePermissions as $f) {
                    $employees = Employee::where("Dept_id", $f->Department_id)
                        ->where("Position_id", $f->Position_id)
                        ->where("resort_id", $resort->resort_id)
                        ->get(['Admin_Parent_id'])
                        ->map(function($i) {
                            $i->profile = Common::getResortUserPicture($i->Admin_Parent_id);
                            return $i;
                        });

                    // Merge this batch into the main collection
                    $Employee1 = $Employee1->merge($employees);
                }

                // Get the single employee
                $Employee2 = Employee::where("Emp_id", $Emp_id)
                    ->get(['Admin_Parent_id'])
                    ->map(function($i) {
                        $i->profile = Common::getResortUserPicture($i->Admin_Parent_id);
                        return $i;
                    });


                $Employee = $Employee1->merge($Employee2)->unique('Admin_Parent_id')->values()->toArray();

               }
               else
               {
                    $Employee = Employee::where("Dept_id",$Department_id)
                                ->where("Position_id",$Position_id)
                                ->where("resort_id",$resort->resort_id)
                                ->get(['Admin_Parent_id'])->map(function($i)
                                {
                                    $i->profile =  Common::getResortUserPicture($i->Admin_Parent_id);
                                    return $i;
                                })->toArray();
               }

                            $array['type']=true;
                            $array['emp']=$Employee;

               return $array;
            }
            elseif($resort->GetEmployee->rank)
            {


                if($TypeOfFolder == 'uncategorized')
                {

                    $Department_id = $resort->GetEmployee->Dept_id;
                    $Position_id = $resort->GetEmployee->Position_id;


                    $filePermission = FilePermissions::where("Department_id",$Department_id)
                                                    ->where("Position_id",$Position_id)
                                                    ->where("file_id",$File_id)
                                                    ->first();
                    $Employee = Employee::where("Dept_id",$Department_id)
                                            ->where("Position_id",$Position_id)
                                            ->where("resort_id",$resort->resort_id)
                                            ->get(['Admin_Parent_id'])->map(function($i)
                                            {
                                                $i->profile =  Common::getResortUserPicture($i->Admin_Parent_id);
                                                return $i;
                                            })->toArray();

                    if(isset($filePermission))
                    {
                        $array['type']=true;
                        $array['emp']=$Employee;

                        return $array;
                    }
                    else
                    {
                        return $array['type']=false;
                    }
                }
                else
                {

                    $Department_id = $resort->GetEmployee->Dept_id;
                    $Position_id = $resort->GetEmployee->Position_id;
                    $Emp_id = $resort->GetEmployee->Emp_id;
                    $FolderExits = ChildFileManagement::join('filemangement_systems as t1','t1.id',"=",'child_file_management.Parent_File_ID')
                                                        // ->where("child_file_management.Parent_File_ID",$File_structure->id)
                                                        ->where("t1.resort_id"   , $resort->resort_id)
                                                        ->where('child_file_management.unique_id', $File_id)
                                                        ->first('t1.Folder_Name');
                        if(isset($FolderExits) && $Emp_id == $FolderExits->Folder_Name)
                        {
                            $Employee = Employee::where("Dept_id",$Department_id)
                                        ->where("Position_id",$Position_id)
                                        ->where("resort_id",$resort->resort_id)
                                        ->where('Emp_id',$Emp_id)
                                        ->get(['Admin_Parent_id'])->map(function($i)
                                        {
                                            $i->profile =  Common::getResortUserPicture($i->Admin_Parent_id);
                                            return $i;
                                        })->toArray();

                            $array['type']=true;
                            $array['emp']=$Employee;
                            return $array;
                        }
                        else
                        {
                            return $array['type']= false;
                        }

                }
            }
            else
            {
                return $array['type']= false;
            }


        }
        else
        {
            return $array['type']=false;
        }

    }
    public static function generateIncidentID()
    {
        $letters = strtoupper(Str::random(4));

        $numbers = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);

        return $letters . $numbers;
    }

    public static function sendMobileNotification($resortId,$type,$feedbackFormId,$trainingId,$title,$message,$module,$sendto,$request_id = null)
    {
        // Only store in ResortNotification if type is NOT 3
        if ($type != 3) {
            $ids                        =   [];
            $statusData                 =   [];
            $time                       =   [];

            foreach($sendto as $send) {
                $resNotification        =   ResortNotification::create([
                    'type'              =>  $title,
                    'user_id'           =>  $send,
                    'module'            =>  $module,
                    'resort_id'         =>  $resortId,
                    'message'           =>  $message,
                    'status'            => 'unread',
                    'request_id'        =>  $request_id,
                ]);

                $ids[]                  =   $resNotification->id;
                $statusData[]           =   $resNotification->status;
                $time[]                 =   $resNotification->created_at;
            }
        }

        //Feedback form Assign Participant notification
        if($type == 1) {
            $payload                =   [
                'id'                =>  $ids,
                'resortid'          =>  (string) $resortId,
                'feedback_form_id'  =>  (string) $feedbackFormId,
                'title'             =>  $title,
                'message'           =>  $message,
                'status'            =>  $statusData,
                'module'            =>  $module,
                'sendto'            =>  $sendto,
                'user_id'           =>  $trainingId,
                'created_at'        =>  $time
            ];
        }

        //SOS,Resignation,Request,Monthly check-in Meeting,sos Employee and Team member,Survey,Incident request
        if($type == 2) {
            $payload                =   [
                'id'                =>  $ids,
                'resortid'          =>  (string) $resortId,
                'title'             =>  $title,
                'message'           =>  $message,
                'status'            =>  $statusData,
                'module'            =>  $module,
                'sendto'            =>  $sendto,
                'created_at'        =>  $time
            ];
        }
        //Announcement Congratulation Notification
        if($type == 3) {

            $employee_id            =   $trainingId;
            $rawPayload             =   Announcement::join('announcement_notification as an','an.announcement_id','=','announcement.id')
                                            ->where('announcement.employee_id', $employee_id)
                                            ->where('announcement.resort_id', $resortId)
                                            ->where('an.status', '!=', 'deleted')
                                            ->orderBy('an.created_at', 'desc')
                                            ->get(['announcement.*', 'an.status', 'an.id','an.employee_id']);

                $mappedPayload      =   $rawPayload->map(function ($payload) {
                $employee           =   Employee::join('resort_admins as ra', 'ra.id', '=', 'employees.Admin_Parent_id')
                                            ->where('employees.id', $payload->employee_id)
                                            ->select('ra.first_name', 'ra.last_name')
                                            ->first();

                                        return [
                                            'id'            => $payload->id,
                                            'resortid'      => $payload->resort_id,
                                            'title'         => 'You have a new message',
                                            'message'       => $employee->first_name . ' ' . $employee->last_name . ' says Congratulation',
                                            'status'        => $payload->status,
                                            'module'        => 'Announcement Wish',
                                            'sendto'        => $payload->employee_id,
                                            'created_at'    => Carbon::parse($payload->created_at)->format('d-m-Y h:i A')
                                        ];
            });

            // Build final merged payload
            $payload =
                [
                    'id'            =>  $mappedPayload->pluck('id')->toArray(),
                    'resortid'      =>  $mappedPayload->first()['resortid'] ?? null,
                    'title'         =>  $mappedPayload->first()['title'] ?? null,
                    'message'       =>  $mappedPayload->first()['message'] ?? null,
                    'status'        =>  [$mappedPayload->first()['status'] ?? null],
                    'module'        =>  $mappedPayload->first()['module'] ?? null,
                    'sendto'        =>  [$mappedPayload->first()['sendto'] ?? null],
                    'created_at'    =>  [$mappedPayload->first()['created_at'] ?? null],
                ];
        }

        //Send the maintance request notification when maintance request is created by employee and approved by
        if($type == 4) {
            $payload                =   [
                'id'                =>  $ids,
                'resortid'          =>  (string) $resortId,
                'title'             =>  $title,
                'message'           =>  $message,
                'status'            =>  $statusData,
                'module'            =>  $module,
                'sendto'            =>  $sendto,
                'request_id'        =>  $request_id,
                'created_at'        =>  $time
            ];
        }

        $baseURL                        =   env('BASE_URL');
        $response                       =   Http::post($baseURL . 'mob-send-notification', $payload);
        return $response->json();
    }

    public static function getMonthlyCheckIn()
    {
        $predefinedCode = 0;
        $MonthlyCheckingModel = MonthlyCheckingModel::orderBy('id', 'desc')->first();
        if (!$MonthlyCheckingModel)
        {
            $predefinedCode = 1;
        }
        else
        {
            $lastCode = explode("-", $MonthlyCheckingModel->Checkin_id);
            $predefinedCode = (int)$lastCode[3] + 1;
        }
        $Grivance = "M-C-I-" . str_pad($predefinedCode, 4, "0", STR_PAD_LEFT);
        return $Grivance;
    }

    public static function ordinal($number) {
        $suffixes = ['th','st','nd','rd','th','th','th','th','th','th'];
        if ((($number % 100) >= 11) && (($number % 100) <= 13))
            return $number . 'th';
        else
            return $number . $suffixes[$number % 10];
    }


    // public static function AWSEmployeeFileUpload($resort_id, $FolderFiles, $FolderName)
    // {

    //     try
    //     {
    //         $data= array();
    //         $Resort = Resort::where("id", $resort_id)->first();
    //         if(!$Resort)
    //         {
    //             return $data['status']=false;
    //         }
    //         $main_folder = $Resort->resort_id;
    //         ini_set('memory_limit', '-1');
    //         $file = $FolderFiles;
    //         $My_file_key = env('ENCRYPTION_KEY');

    //         if (!$My_file_key)
    //         {
    //             return $data['status']=false;
    //         }
    //         $File_structure = FilemangementSystem::where('resort_id', $resort_id)
    //             ->where('Folder_Name', $FolderName)
    //             ->first();
    //         if(!$File_structure)
    //         {
    //             $data['status']=false;
    //             $data['msg']="Folder does not exist";
    //             return $data;
    //         }
    //         $originalName = $file->getClientOriginalName();
    //         $extension = strtolower($file->getClientOriginalExtension());
    //         $fileSizeMB = round($file->getSize() / 1024, 2); // Convert to KB
    //         $isImage = in_array($extension, ['jpg', 'jpeg', 'png']);

    //         $tempImagePath = null;
    //         $fullImagePath = null;
    //         $tempPdfPath = null;

    //         if($isImage)
    //         {
    //             $tempImagePath = $file->store('temp', 'local');
    //             $fullImagePath = storage_path('app/' . $tempImagePath);
    //             if (file_exists($fullImagePath))
    //             {
    //                 $imageData = file_get_contents($fullImagePath);
    //                 $mimeType = mime_content_type($fullImagePath);
    //                 $base64Image = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);

    //                 $pdf = Pdf::loadView('resorts.FileManagment.scan', [
    //                     'imageBase64' => $base64Image
    //                 ])->setPaper('a4', 'portrait');

    //                 // Save PDF to temporary file
    //                 $tempPdfPath = storage_path('app/temp/') . uniqid('pdf_') . '.pdf';
    //                 $pdf->save($tempPdfPath);

    //                 // Use the PDF file for further processing
    //                 $fileContent = file_get_contents($tempPdfPath);
    //                 $originalName = pathinfo($originalName, PATHINFO_FILENAME) . '.pdf';
    //                 $extension = 'pdf';
    //                 $fileSizeMB = round(strlen($fileContent) / 1024, 2);

    //             }
    //             else
    //             {
    //                 return $data['status']=false;
    //             }
    //         }
    //         else
    //         {
    //             $fileContent = file_get_contents($file->getRealPath());
    //             if ($fileContent === false)
    //             {
    //                 return $data['status']=false;
    //             }
    //         }
    //         $uniqueString = substr(md5(uniqid($originalName, true)), 0, 10);
    //         $newFileName = $uniqueString . '.' . $extension . '.enc'; // Add .enc extension to indicate encrypted
    //         if ($File_structure->UnderON != 0)
    //         {
    //             $parentPath = FilemangementSystem::where('resort_id', $resort_id)
    //                 ->where('id', $File_structure->UnderON)
    //                 ->first();
    //             if (!$parentPath)
    //             {
    //                 return $data['status']=false;
    //             }
    //             $path = $main_folder . '/public/' . $File_structure->Folder_Type . '/' . $parentPath->Folder_unique_id . '/' . $File_structure->Folder_unique_id . '/' . $newFileName;
    //         }
    //         else
    //         {
    //             $path = $main_folder . '/public/' . $File_structure->Folder_Type . '/' . $File_structure->Folder_unique_id . '/' . $newFileName;
    //         }



    //         // AES-256-CBC Encryption setup
    //         $key = hash('sha256', env('ENCRYPTION_KEY'), true); // AES-256 key
    //         $iv = random_bytes(16); // Generate IV (16 bytes for AES-256-CBC)
    //         // For image files that were converted to PDF, use the PDF content
    //         // For other files, use the original file content
    //         $dataToEncrypt = $isImage ? $fileContent : file_get_contents($file->getRealPath());

    //         // Encrypt the file content
    //         $encrypted = $iv . openssl_encrypt(
    //             $dataToEncrypt,
    //             'aes-256-cbc',
    //             $key,
    //             OPENSSL_RAW_DATA,
    //             $iv
    //         );

    //             if ($encrypted === false)
    //             {
    //                 $data['msg']="Encryption failed: " ;
    //                 $data['status']=false;
    //                 return $data;
    //             }

    //         $uploadResult = Storage::disk('s3')->put($path, $encrypted, [
    //             'ContentType' => 'application/octet-stream',
    //             'ContentDisposition' => 'attachment; filename="' . $originalName . '"'
    //         ]);
    //         if (!$uploadResult)
    //         {
    //             return $data['status']=false;
    //         }
    //         $existingFile = ChildFileManagement::where('resort_id', $Resort->resort_id)
    //             ->where('Parent_File_ID', $File_structure->id)
    //             ->where(function ($query) use ($originalName) {
    //                 $query->where('File_Name', $originalName)
    //                     ->orWhere('NewFileName', $originalName);
    //             })
    //             ->orderBy('id', 'desc')
    //             ->first();


    //         $fileRecord = ChildFileManagement::create([
    //             'resort_id' => $resort_id,
    //             'unique_id' => $uniqueString,
    //             'Parent_File_ID' => $File_structure->id,
    //             'Folder_id' => $FolderName,
    //             'File_Name' => $originalName,
    //             'File_Type' => $extension,
    //             'File_Size' => $fileSizeMB,
    //             'File_Path' => $path,
    //             'File_Extension' => $extension,
    //         ]);

    //         if (!isset($fileRecord->id))
    //         {
    //             return $data['status']=false;
    //         }
    //         AuditLogs::create([
    //             'resort_id' => $resort_id,
    //             "file_id" => $fileRecord->id,
    //             "TypeofAction" => "Create",
    //             "file_path" => $path
    //         ]);

    //         if ($isImage)
    //         {
    //             if ($fullImagePath && file_exists($fullImagePath)) {
    //                 unlink($fullImagePath);
    //             }
    //             if ($tempPdfPath && file_exists($tempPdfPath)) {
    //                 unlink($tempPdfPath);
    //             }
    //         }
    //         $data['status']=true;
    //         $data['Chil_file_id']=$fileRecord->id;
    //         $data['path']=$path;
    //         return $data;
    //       }
    //       catch (\Exception $e)
    //       {

    //         // Log the error if needed
    //         \Log::error('AWSEmployeeFileUpload failed: ' . $e->getMessage());

    //         // Clean up temporary files in case of error
    //         if (isset($fullImagePath) && $fullImagePath && file_exists($fullImagePath)) {
    //             unlink($fullImagePath);
    //         }
    //         if (isset($tempPdfPath) && $tempPdfPath && file_exists($tempPdfPath)) {
    //             unlink($tempPdfPath);
    //         }
    //         $data['msg']=$e->getMessage() ;

    //         $data['status']=false;
    //         $data['path']="";
    //         return $data;
    //     }
    // }

    // New code with secure logic
    public static function AWSEmployeeFileUpload($resort_id, $FolderFiles, $FolderName, $SubFolder=null, $is_secure = null)
    {
        $data = [];
        if($is_secure == true)
        {
            $is_secure = 1;
        }
        else
        {
            $is_secure =0;
        }

        $Resort = Resort::where("id", $resort_id)->first();
        if (!$Resort) return ['status' => false, 'msg' => 'Resort not found'];

            $main_folder = $Resort->resort_id;
            ini_set('memory_limit', '-1');
            $file = $FolderFiles;

           $File_structure = FilemangementSystem::where('resort_id', $resort_id)
                ->where('Folder_Name', $FolderName)
                ->where('Folder_Type', 'categorized')
                ->first();

            if (!$File_structure) {
                return ['status' => false, 'msg' => "Folder does not exist"];
            }

            $originalName = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());
            $fileSizeMB = round($file->getSize() / 1024, 2);
            $isImage = in_array($extension, ['jpg', 'jpeg', 'png']);

            $tempPdfPath = null;
            $fullImagePath = null;

            // Convert image to PDF
            if ($isImage) {
                $tempImagePath = $file->store('temp', 'local');
                $fullImagePath = storage_path('app/' . $tempImagePath);

                if (file_exists($fullImagePath)) {
                    $imageData = file_get_contents($fullImagePath);
                    $mimeType = mime_content_type($fullImagePath);
                    $base64Image = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);

                    $pdf = Pdf::loadView('resorts.FileManagment.scan', [
                        'imageBase64' => $base64Image
                    ])->setPaper('a4', 'portrait');

                    $tempPdfPath = storage_path('app/temp/') . uniqid('pdf_') . '.pdf';
                    $pdf->save($tempPdfPath);

                    $fileContent = file_get_contents($tempPdfPath);
                    $originalName = pathinfo($originalName, PATHINFO_FILENAME) . '.pdf';
                    $extension = 'pdf';
                    $fileSizeMB = round(strlen($fileContent) / 1024, 2);
                } else {

                    return ['status' => false, 'msg' => 'Failed to process image'];
                }
            } else {
                $fileContent = file_get_contents($file->getRealPath());
                if ($fileContent === false)


                    return ['status' => false, 'msg' => 'Failed to read file'];
            }

            $uniqueString = substr(md5(uniqid($originalName, true)), 0, 10);
            $finalExtension = $extension;
            $uploadContent = $fileContent;

            // If encryption is required
            if ($is_secure == 1)
            {
                $key = hash('sha256', env('ENCRYPTION_KEY'), true);
                $iv = random_bytes(16);
                $encrypted = $iv . openssl_encrypt(
                    $fileContent,
                    'aes-256-cbc',
                    $key,
                    OPENSSL_RAW_DATA,
                    $iv
                );

                if ($encrypted === false)
                {
                    return ['status' => false, 'msg' => 'Encryption failed'];
                }

                $uploadContent = $encrypted;
                $finalExtension .= '.enc';
            }

            $newFileName = $uniqueString . '.' . $finalExtension;
             $Parent_id = '';
            $NewFolder_id='';
            $NewFile_structure='';
            if ($SubFolder !=null && $SubFolder != '')
            {
                $parentPath = FilemangementSystem::where('resort_id', $resort_id)
                    ->where('UnderON', $File_structure->id)
                    ->where('Folder_Name', $SubFolder)
                    ->first();

                if (!$parentPath) return ['status' => false, 'msg' => 'Parent folder missing'];

                $path = $main_folder . '/public/' . $File_structure->Folder_Type . '/' . $File_structure->Folder_unique_id . '/' . $parentPath->Folder_unique_id . '/' . $newFileName;
                $NewFolder_id=$parentPath->id;
                $NewFile_structure=$parentPath->id;
            }
            else
            {
                $path = $main_folder . '/public/' . $File_structure->Folder_Type . '/' . $File_structure->Folder_unique_id . '/' . $newFileName;
                $NewFolder_id=$File_structure->id;
                $NewFile_structure=$File_structure->id;
            }

            $uploadResult = Storage::disk('s3')->put($path, $uploadContent, [
                'ContentType' => 'application/octet-stream',
                'ContentDisposition' => 'inline; filename="' . $originalName . '"'
            ]);

            if (!$uploadResult)
            {

                return ['status' => false, 'msg' => 'Upload to S3 failed'];
            }

            $fileRecord = ChildFileManagement::create([
                'resort_id' => $resort_id,
                'unique_id' => $uniqueString,
                'Parent_File_ID' => $NewFile_structure,
                'Folder_id' => $NewFolder_id,
                'File_Name' => $originalName,
                'File_Type' => $extension,
                'File_Size' => $fileSizeMB,
                'File_Path' => $path,
                'File_Extension' => $extension,
                'is_secure' => $is_secure,
            ]);

            if (!isset($fileRecord->id)) {
                return ['status' => false, 'msg' => 'DB save failed'];
            }

            AuditLogs::create([
                'resort_id' => $resort_id,
                "file_id" => $fileRecord->id,
                "TypeofAction" => "Create",
                "file_path" => $path
            ]);

            // Cleanup
            if ($fullImagePath && file_exists($fullImagePath)) unlink($fullImagePath);
            if ($tempPdfPath && file_exists($tempPdfPath)) unlink($tempPdfPath);

            return [
                'status' => true,
                'Chil_file_id' => $fileRecord->id,
                'path' => $path
            ];
        try {  } catch (\Exception $e) {
            \Log::error('AWSEmployeeFileUpload failed: ' . $e->getMessage());

            if (isset($fullImagePath) && file_exists($fullImagePath)) unlink($fullImagePath);
            if (isset($tempPdfPath) && file_exists($tempPdfPath)) unlink($tempPdfPath);

            return [
                'status' => false,
                'msg' => $e->getMessage(),
                'path' => ""
            ];
        }
    }

    public static function GetAWSFile($id, $resort_id, $is_secure = null)
    {
        $ChildFiles = ChildFileManagement::where("id", $id)
            ->where("resort_id", $resort_id)
            ->first();

        if (!$ChildFiles || !Storage::disk('s3')->exists($ChildFiles->File_Path)) {
            return ['success' => false, 'NewURLshow' => null, 'mimeType' => null];
        }

        $filePath = $ChildFiles->File_Path;

        // Check if file should be decrypted
        if ($ChildFiles->is_secure == 1 || !empty($is_secure) && $is_secure != null) {


            $key = hash('sha256', env('ENCRYPTION_KEY'), true);
            $encryptedData = Storage::disk('s3')->get($ChildFiles->File_Path);

            if (empty($encryptedData) || strlen($encryptedData) < 16) {
                throw new \Exception('Invalid or corrupted encrypted data');
            }

            $iv = substr($encryptedData, 0, 16);
            $cipherText = substr($encryptedData, 16);
            $decryptedData = openssl_decrypt($cipherText, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

            if ($decryptedData === false) {
                $error = openssl_error_string();
                throw new \Exception("Decryption failed: {$error}");
            }

            $decryptedFileName = str_replace('.enc', '', basename($ChildFiles->File_Path));
            $extension = strtolower(pathinfo($decryptedFileName, PATHINFO_EXTENSION));

            $mimeType = self::guessMimeType($extension, $decryptedData);

            $tempFilePath = "temp/decrypted_" . time() . "_{$decryptedFileName}";

            Storage::disk('s3')->put($tempFilePath, $decryptedData, [
                'ContentType' => $mimeType
            ]);

            $newUrl = Storage::disk('s3')->temporaryUrl($tempFilePath, now()->addMinutes(30));
            if(empty($newUrl) && $$newUrl == null){
                return ['success' => false, 'NewURLshow' => null, 'mimeType' => null];
            }
            return [
                'success' => true,
                'NewURLshow' => $newUrl,
                'mimeType' => $mimeType
            ];
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeType = self::guessMimeType($extension);

        $temporaryUrl = Storage::disk('s3')->temporaryUrl(
            $filePath,
            now()->addMinutes(30),
            [
                'ResponseContentDisposition' => 'inline',
                'ResponseContentType' => $mimeType,
            ]
        );

        return [
            'success' => true,
            'NewURLshow' => $temporaryUrl,
            'mimeType' => $mimeType,
        ];
    }


    public static function GetApplicantAWSFile($path)
    {
        // Get storage driver from environment variable (s3, local, wasabi)
        $storageDriver = env('STORAGE_DRIVER', 's3');

        // Determine which disk to use
        $diskName = 's3'; // default
        if ($storageDriver === 'local') {
            $diskName = 'local';
        } elseif ($storageDriver === 'wasabi') {
            $diskName = 'wasabi';
        }

        $disk = Storage::disk($diskName);

        if (!$disk->exists($path)) {
            return ['success' => false, 'NewURLshow' => null, 'mimeType' => null];
        }

        $filePath = $path;
        $fileName = basename($filePath);
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Check if file is encrypted (you might need to adjust this logic)
        $isEncrypted = false; // Default to non-encrypted

        if ($isEncrypted) {
            // Handle encrypted file
            try {
                $fileContent = $disk->get($filePath);
                $mimeType = self::guessMimeType($extension, $fileContent);

                // Create a temporary file with decrypted content
                $tempFilePath = "temp/decrypted_" . time() . "_{$fileName}";

                $disk->put($tempFilePath, $fileContent, [
                    'ContentType' => $mimeType
                ]);

                // For local storage, return direct URL; for S3/Wasabi, use temporary URL
                if ($storageDriver === 'local') {
                    $newUrl = url('storage/' . $tempFilePath);
                } else {
                    $newUrl = $disk->temporaryUrl($tempFilePath, now()->addMinutes(30));
                }

                if (empty($newUrl)) {
                    return ['success' => false, 'NewURLshow' => null, 'mimeType' => null];
                }

                return [
                    'success' => true,
                    'NewURLshow' => $newUrl,
                    'mimeType' => $mimeType
                ];
            } catch (\Exception $e) {
                \Log::error('Error processing encrypted file: ' . $e->getMessage());
                return ['success' => false, 'NewURLshow' => null, 'mimeType' => null];
            }
        } else {
            // Handle non-encrypted file
            $mimeType = self::guessMimeType($extension);

            try {
                // For local storage, return direct URL; for S3/Wasabi, use temporary URL
                if ($storageDriver === 'local') {
                    // For local storage, create a public symlink if needed and return URL
                    $publicPath = 'public/' . $filePath;
                    if (!$disk->exists($publicPath)) {
                        // Try to copy to public storage for local access
                        $publicDisk = Storage::disk('public');
                        $directory = dirname($publicPath);
                        if (!$publicDisk->exists($directory)) {
                            $publicDisk->makeDirectory($directory);
                        }
                        $publicDisk->put($publicPath, $disk->get($filePath));
                    }
                    $temporaryUrl = url('storage/' . $filePath);
                } else {
                    $temporaryUrl = $disk->temporaryUrl(
                        $filePath,
                        now()->addMinutes(30),
                        [
                            'ResponseContentDisposition' => 'inline',
                            'ResponseContentType' => $mimeType,
                        ]
                    );
                }

                return [
                    'success' => true,
                    'NewURLshow' => $temporaryUrl,
                    'mimeType' => $mimeType,
                ];
            } catch (\Exception $e) {
                \Log::error('Error generating temporary URL: ' . $e->getMessage());
                return ['success' => false, 'NewURLshow' => null, 'mimeType' => null];
            }
        }
    }
    private static function guessMimeType(string $extension, string $fileContent = null): string
    {
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'flac' => 'audio/flac',
            'aac' => 'audio/aac',
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'mkv' => 'video/x-matroska',
            'webm' => 'video/webm',
            'wmv' => 'video/x-ms-wmv',
            'flv' => 'video/x-flv',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'tar' => 'application/x-tar',
            'gz' => 'application/gzip',
            '7z' => 'application/x-7z-compressed',
            'html' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml'
        ];

        if (isset($mimeTypes[$extension])) {
            return $mimeTypes[$extension];
        }

        // Fallback to detect from content
        if (!empty($fileContent)) {
            if (function_exists('mime_content_type')) {
                $tempFile = tempnam(sys_get_temp_dir(), 'mime');
                file_put_contents($tempFile, $fileContent);
                $mime = mime_content_type($tempFile);
                unlink($tempFile);
                return $mime ?: 'application/octet-stream';
            }

            if (class_exists('finfo')) {
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                return $finfo->buffer($fileContent) ?: 'application/octet-stream';
            }
        }

        return 'application/octet-stream';
    }

    // public static function GetAWSFile($id,$resort_id)
    // {
    //     $ChildFiles = ChildFileManagement::where("id",$id)->where("resort_id"   ,$resort_id)->first();
    //     if (isset($ChildFiles) && Storage::disk('s3')->exists($ChildFiles->File_Path))
    //     {
    //         $key = hash('sha256', env('ENCRYPTION_KEY'), true);

    //         $encryptedData = Storage::disk('s3')->get($ChildFiles->File_Path);

    //         if (empty($encryptedData) || strlen($encryptedData) < 16) {
    //             throw new \Exception('Invalid or corrupted encrypted data');
    //         }
    //             $iv = substr($encryptedData, 0, 16);
    //             $cipherText = substr($encryptedData, 16);
    //             $decryptedData = openssl_decrypt(
    //                 $cipherText,
    //                 'aes-256-cbc',
    //                 $key,
    //                 OPENSSL_RAW_DATA,  // Critical for handling binary data properly
    //                 $iv
    //             );

    //             if ($decryptedData === false) {
    //                 $error = openssl_error_string();
    //                 throw new \Exception("Decryption failed: {$error}");
    //             }

    //             $decryptedFileName = str_replace('.enc', '', basename($ChildFiles->File_Path));
    //             $tempFilePath = "temp/decrypted_" . time() . "_{$decryptedFileName}";
    //             $extension = strtolower(pathinfo($decryptedFileName, PATHINFO_EXTENSION));
    //             $mimeTypes = [
    //                 'pdf' => 'application/pdf',
    //                 'doc' => 'application/msword',
    //                 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    //                 'xls' => 'application/vnd.ms-excel',
    //                 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    //                 'ppt' => 'application/vnd.ms-powerpoint',
    //                 'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    //                 'txt' => 'text/plain',
    //                 'csv' => 'text/csv',

    //                 'jpg' => 'image/jpeg',
    //                 'jpeg' => 'image/jpeg',
    //                 'png' => 'image/png',
    //                 'gif' => 'image/gif',
    //                 'bmp' => 'image/bmp',
    //                 'svg' => 'image/svg+xml',
    //                 'webp' => 'image/webp',

    //                 'mp3' => 'audio/mpeg',
    //                 'wav' => 'audio/wav',
    //                 'ogg' => 'audio/ogg',
    //                 'flac' => 'audio/flac',
    //                 'aac' => 'audio/aac',

    //                 'mp4' => 'video/mp4',
    //                 'mov' => 'video/quicktime',
    //                 'avi' => 'video/x-msvideo',
    //                 'mkv' => 'video/x-matroska',
    //                 'webm' => 'video/webm',
    //                 'wmv' => 'video/x-ms-wmv',
    //                 'flv' => 'video/x-flv',

    //                 'zip' => 'application/zip',
    //                 'rar' => 'application/x-rar-compressed',
    //                 'tar' => 'application/x-tar',
    //                 'gz' => 'application/gzip',
    //                 '7z' => 'application/x-7z-compressed',
    //                 'html' => 'text/html',
    //                 'css' => 'text/css',
    //                 'js' => 'application/javascript',
    //                 'json' => 'application/json',
    //                 'xml' => 'application/xml'
    //             ];

    //             // Set MIME type based on extension or detect if not in our map
    //             if (isset($mimeTypes[$extension])) {
    //                 $mimeType = $mimeTypes[$extension];

    //             } else {
    //                 // Fallback to file detection - may not be accurate for all file types
    //                 // but better than nothing for unknown extensions
    //                 if (function_exists('mime_content_type')) {
    //                     // Create a temporary file to use mime_content_type
    //                     $tempFile = tempnam(sys_get_temp_dir(), 'file');
    //                     file_put_contents($tempFile, $decryptedData);
    //                     $mimeType = mime_content_type($tempFile);
    //                     unlink($tempFile); // Clean up
    //                 } else if (class_exists('finfo')) {
    //                     $finfo = new \finfo(FILEINFO_MIME_TYPE);
    //                     $mimeType = $finfo->buffer($decryptedData);
    //                 } else {
    //                     // If all detection methods fail, use binary as default
    //                     $mimeType = 'application/octet-stream';
    //                 }
    //             }

    //             // Store the decrypted file with proper content type
    //             Storage::disk('s3')->put($tempFilePath, $decryptedData, [
    //                 'ContentType' => $mimeType
    //             ]);

    //             // Generate a temporary URL with sufficient time window
    //             $fileExtension = pathinfo($ChildFiles->File_Path, PATHINFO_EXTENSION);
    //             // Get MIME type dynamically

    //             $mimeType = match (strtolower($extension)) {
    //                 'mp4'  => 'video/mp4',
    //                 'mov'  => 'video/quicktime',
    //                 'avi'  => 'video/x-msvideo',
    //                 'pdf'  => 'application/pdf',
    //                 'txt'  => 'text/plain',
    //                 'jpg'  => 'image/jpeg',
    //                 'jpeg' => 'image/jpeg',
    //                 'png'  => 'image/png',
    //                 'gif'  => 'image/gif',
    //                 'doc', 'docx' => 'application/msword',
    //                 'xls', 'xlsx' => 'application/vnd.ms-excel',
    //                 'zip'  => 'application/zip',
    //                 default => 'application/octet-stream' // Fallback for unknown types
    //             };
    //             $newUrl = Storage::disk('s3')->temporaryUrl($tempFilePath, now()->addMinutes(30));
    //         } else {
    //             $mimeType='';
    //         $newUrl = "No";
    //     }
    //     return ['success' => true,  'NewURLshow' => $newUrl,'mimeType' => $mimeType];

    // }


    // public static function AWSEmployeeFacilityCategoryImageUpload($resort_id, $FolderFiles, $FolderName)
    // {
    //     $data= array();
    //     $Resort = Resort::where("id", $resort_id)->first();
    //         if(!$Resort)
    //         {
    //             return $data['status']=false;
    //         }

    //         $main_folder = $Resort->resort_id;
    //         ini_set('memory_limit', '-1');
    //         $file = $FolderFiles;
    //         $My_file_key = env('ENCRYPTION_KEY');

    //         if (!$My_file_key)
    //         {
    //             return $data['status']=false;
    //         }
    //         $File_structure = FilemangementSystem::where('resort_id', $resort_id)
    //             ->where('Folder_Name', $FolderName)
    //             ->first();
    //         if(!$File_structure)
    //         {
    //             $data['status']=false;
    //             $data['msg']="Folder does not exist";
    //             return $data;
    //         }
    //         $extension = strtolower($file->getClientOriginalExtension());
    //         $fileSizeMB = round($file->getSize() / 1024, 2); // Convert to KB
    //         $isImage = in_array($extension, ['jpg', 'jpeg', 'png']);

    //         $tempImagePath = null;
    //         $fullImagePath = null;
    //         $tempPdfPath = null;

    //         if($isImage)
    //         {
    //             $tempImagePath = $file->store('temp', 'local');
    //             $fullImagePath = storage_path('app/' . $tempImagePath);
    //             if (file_exists($fullImagePath))
    //             {
    //                 // Just use the original image file without PDF conversion
    //                 $fileContent = file_get_contents($fullImagePath);
    //                 $originalName = $file->getClientOriginalName(); // Keep original name with original extension
    //                 // Keep original extension, no need to change to PDF
    //                 $fileSizeMB = round(strlen($fileContent) / 1024, 2);
    //             }
    //             else
    //             {
    //                 return $data['status']=false;
    //             }
    //         }
    //         else
    //         {
    //             $fileContent = file_get_contents($file->getRealPath());
    //             if ($fileContent === false)
    //             {
    //                 return $data['status']=false;
    //             }
    //         }
    //         $uniqueString = substr(md5(uniqid($originalName, true)), 0, 10);
    //         $newFileName = $uniqueString . '.' . $extension . '.enc'; // Add .enc extension to indicate encrypted
    //         if ($File_structure->UnderON != 0)
    //         {
    //             $parentPath = FilemangementSystem::where('resort_id', $resort_id)
    //                 ->where('id', $File_structure->UnderON)
    //                 ->first();
    //             if (!$parentPath)
    //             {
    //                 return $data['status']=false;
    //             }
    //             $path = $main_folder . '/public/' . $File_structure->Folder_Type . '/' . $parentPath->Folder_unique_id . '/' . $File_structure->Folder_unique_id . '/' . $newFileName;
    //         }
    //         else
    //         {
    //             $path = $main_folder . '/public/' . $File_structure->Folder_Type . '/' . $File_structure->Folder_unique_id . '/' . $newFileName;
    //         }



    //         // AES-256-CBC Encryption setup
    //         $key = hash('sha256', env('ENCRYPTION_KEY'), true); // AES-256 key
    //         $iv = random_bytes(16); // Generate IV (16 bytes for AES-256-CBC)
    //         // For image files that were converted to PDF, use the PDF content
    //         // For other files, use the original file content
    //         $dataToEncrypt = $isImage ? $fileContent : file_get_contents($file->getRealPath());

    //         // Encrypt the file content
    //         $encrypted = $iv . openssl_encrypt(
    //             $dataToEncrypt,
    //             'aes-256-cbc',
    //             $key,
    //             OPENSSL_RAW_DATA,
    //             $iv
    //         );

    //         if ($encrypted === false)
    //         {
    //             return $data['status']=false;
    //         }

    //         $uploadResult = Storage::disk('s3')->put($path, $encrypted, [
    //             'ContentType' => 'application/octet-stream',
    //             'ContentDisposition' => 'attachment; filename="' . $originalName . '"'
    //         ]);
    //         if (!$uploadResult)
    //         {
    //             return $data['status']=false;
    //         }
    //         $existingFile = ChildFileManagement::where('resort_id', $Resort->resort_id)
    //             ->where('Parent_File_ID', $File_structure->id)
    //             ->where(function ($query) use ($originalName) {
    //                 $query->where('File_Name', $originalName)
    //                     ->orWhere('NewFileName', $originalName);
    //             })
    //             ->orderBy('id', 'desc')
    //             ->first();


    //         $fileRecord = ChildFileManagement::create([
    //             'resort_id' => $resort_id,
    //             'unique_id' => $uniqueString,
    //             'Parent_File_ID' => $File_structure->id,
    //             'Folder_id' => $FolderName,
    //             'File_Name' => $originalName,
    //             'File_Type' => $extension,
    //             'File_Size' => $fileSizeMB,
    //             'File_Path' => $path,
    //             'File_Extension' => $extension,
    //         ]);

    //         if (!isset($fileRecord->id))
    //         {
    //             return $data['status']=false;
    //         }
    //         AuditLogs::create([
    //             'resort_id' => $resort_id,
    //             "file_id" => $fileRecord->id,
    //             "TypeofAction" => "Create",
    //             "file_path" => $path
    //         ]);

    //         if ($isImage)
    //         {
    //             if ($fullImagePath && file_exists($fullImagePath)) {
    //                 unlink($fullImagePath);
    //             }
    //             if ($tempPdfPath && file_exists($tempPdfPath)) {
    //                 unlink($tempPdfPath);
    //             }
    //         }
    //         $data['status']=true;
    //         $data['Chil_file_id']=$fileRecord->id;
    //         $data['path']=$path;
    //         return $data;
    //       try
    //     { }
    //       catch (\Exception $e)
    //       {

    //         // Log the error if needed
    //         \Log::error('AWSEmployeeFileUpload failed: ' . $e->getMessage());

    //         // Clean up temporary files in case of error
    //         if (isset($fullImagePath) && $fullImagePath && file_exists($fullImagePath)) {
    //             unlink($fullImagePath);
    //         }
    //         if (isset($tempPdfPath) && $tempPdfPath && file_exists($tempPdfPath)) {
    //             unlink($tempPdfPath);
    //         }

    //         $data['status']=false;
    //         $data['path']="";
    //         return $data;
    //     }
    // }

    public static function AWSEmployeeFacilityCategoryImageUpload($resort_id, $FolderFiles, $FolderName)
    {
        $data = [];

        try {
            $Resort = Resort::where("id", $resort_id)->first();
            if (!$Resort) {
                return ['status' => false];
            }

            $main_folder = $Resort->resort_id;
            $file = $FolderFiles;

            // Validate folder structure
            $File_structure = FilemangementSystem::where('resort_id', $resort_id)
                ->where('Folder_Name', $FolderName)
                ->first();

            if (!$File_structure) {
                return [
                    'status' => false,
                    'msg' => "Folder does not exist"
                ];
            }

            // File details
            $originalName = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());
            $fileSizeKB = round($file->getSize() / 1024, 2); // Size in KB
            $mimeType = $file->getMimeType();

            // Generate unique file name
            $uniqueString = substr(md5(uniqid($originalName, true)), 0, 10);
            $newFileName = $uniqueString . '.' . $extension;

            // Build S3 path
            if ($File_structure->UnderON != 0) {
                $parentPath = FilemangementSystem::where('resort_id', $resort_id)
                    ->where('id', $File_structure->UnderON)
                    ->first();

                if (!$parentPath) {
                    return ['status' => false];
                }

                $path = $main_folder . '/public/' . $File_structure->Folder_Type . '/' . $parentPath->Folder_unique_id . '/' . $File_structure->Folder_unique_id . '/' . $newFileName;
            } else {
                $path = $main_folder . '/public/' . $File_structure->Folder_Type . '/' . $File_structure->Folder_unique_id . '/' . $newFileName;
            }

            // Upload directly to S3
            $uploadResult = Storage::disk('s3')->put($path, file_get_contents($file), [
                'ContentType' => $mimeType,
                'ContentDisposition' => 'attachment; filename="' . $originalName . '"',
            ]);



            if (!$uploadResult) {
                return ['status' => false];
            }

            // Save metadata
            $fileRecord = ChildFileManagement::create([
                'resort_id' => $resort_id,
                'unique_id' => $uniqueString,
                'Parent_File_ID' => $File_structure->id,
                'Folder_id' => $FolderName,
                'File_Name' => $originalName,
                'File_Type' => $extension,
                'File_Size' => $fileSizeKB,
                'File_Path' => $path,
                'File_Extension' => $extension,
                'is_secure' => 0,
            ]);

            if (!isset($fileRecord->id)) {
                return ['status' => false];
            }

            // Audit log
            AuditLogs::create([
                'resort_id' => $resort_id,
                "file_id" => $fileRecord->id,
                "TypeofAction" => "Create",
                "file_path" => $path
            ]);

            return [
                'status' => true,
                'Chil_file_id' => $fileRecord->id,
                'path' => $path
            ];

        } catch (\Exception $e) {
            \Log::error('AWSEmployeeFileUpload failed: ' . $e->getMessage());

            return [
                'status' => false,
                'path' => ''
            ];
        }
    }


    public static function createFolderByName($resort_id, $folderName, $folderType)
    {
        $resort = Resort::find($resort_id);
        if($folderType =='categorized'){
            $emp_id = Auth::guard('resort-admin')->user()->GetEmployee->Emp_id;
            if(!$emp_id) {
                return ['status' => false, 'message' => 'Employee not found'];
            }
        }

        if (!$resort)
            {
            return ['status' => false, 'message' => 'Resort not found'];
        }

            $main_folder = $resort->resort_id;

            $uniqueString = substr(md5(uniqid($folderName, true)), 0, 10);

            $fileManagement = FilemangementSystem::create([
                'resort_id' => $resort->id,
                'Folder_unique_id' =>  $uniqueString,
                'Folder_Name' => $folderName,
                'UnderON' => 0,
                'Folder_Type' => $folderType,
            ]);

            $base_path = $main_folder . '/public'.'/'.$folderType . '/';

            if($fileManagement->UnderON !=0)
            {
                if($fileManagement->Folder_Type == 'categorized') {
                    $folderPath = $base_path .'/'.$emp_id .'/'.$fileManagement->Folder_unique_id . '/' ;
                }else{
                    $folderPath = $base_path .$fileManagement->Folder_unique_id . '/' . $uniqueString . '/';
                }
            }else
            {
                if($fileManagement->Folder_Type == 'categorized') {
                    $folderPath = $base_path .'/'.$emp_id .'/'.$uniqueString . '/';
                }else{
                    $folderPath = $base_path . $uniqueString . '/';
                }
            }
            Storage::disk('s3')->put($folderPath, '');
            DB::commit();

        return $fileManagement;
    }

    public static function FCMTokenPushNotification()
    {

        // $pri_key = "MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDKZBKBWzljY7nQ\nhYteL5Xnfr6d3lzKphW7XE1BkLKY6yA1akMkM+LUL+fm8FwU8XOfyx0kucZ09R4p\nhtX1y4OUeiFIbBcYyRvp/XKpvGJD1AusvZqkl7nVQ2aQHzEfqpQCJnyLJXpKw3vs\nUaaCsdnCpDcjhn1Gb/RJQGBm+EEDjFXpa7g/aapG7gLX61IQbi8c1XiHyHAirpD4\nQOekZ16dzo3rGgIKAXWC9SOxkcv+IH0O4TkAIDFnR2D+Jrge/WzPp/iHxhp6A+4g\nv2IkXFH9ryJQAK79T7+iQizDwfm6+RDDKJxYo62+1n+z9EJvLcU0lFw18dgLIDLF\nks/hIqN/AgMBAAECggEATMbwxDS0jREwVLvMdnLr1ZFdw2qu3ctqlBR8VEKNlfgg\nVFMW3F14j5EK0q9c5y7/c19sk1mMQSMZiZxWf3NwW3uHM7+ZdXQZTEcy39QQnPWM\nZj/ZMdZDD3WNq1/B1Wby1ev+tBSIE2OcF7aTyaGpX67HIglrbbSbwcwTpgxIMY02\n1NSK1y9QQ+45ocFqUNCoy6pAvey6scfAm/972CkWIFjjl+59/QGcGhYhYWP9x5Cd\nEWsR5zjilOSQ/o6YbD3jK4xD9yF3PMMwZMfRUoGLyA8Ye3xGVdmuBe85aAyNuEXH\nw57MkshYg5nxrlOCyjow7F7n/7r/0ADO/HfTT4xBtQKBgQDtAG3kXxHjSVdL5O+G\nnprCGuOOEs3UIJUsmtTTAQgOpRkZ2pj7I4A2BegL3/Bw7kVp4Aa1/NgAECuKIWCe\nbjHC6NuFMR76rCfZ0inK26ziXRezmEFzeC6PO0gquAtyZIHGtHhhZTDcacuL5gTi\ncB4klcElWPiBIZZrShrd7xMTOwKBgQDanWMCgmLKXuK3bErmqZmfSOQhCi2j7TEL\npe2JZRdic0OazcXrj9AjQICLOlpCDMHoV4KRCM2rktnGTbC3/8KqO1KVuf6JhtYp\nfQGjBOv6zmYyXa5mD51M+GDtuMKqhkkTZYMvET0a8kItewVyDLxX5uvsZL7l7txL\nckZ0YnpkjQKBgQCiS8v1OoFWYu/r4f+A8XXK/Hzd2tSmshVVcUXSpP8ugDKbOM3Q\nFPSEijDoiNbvjstsAGS4FUiZanlWYxr2A0ICVlGVeRjc0i2MBVZnO129ucA0VUxk\n1WUU4qS91EDKejdAqm9RSulV1wDzcXXg1qRdq0uT1sR+MVD/ccTKPCCU5wKBgF8g\nHuYHoD8YvKQ9rfXilKMXz5SE3kk3O6Eq6Upgv6UqLJ+erGGM6W99cLGkiYO2E2Yr\nNfgwEXZ35uUAB0Z5NtZmC9B050ombugMqfqqeJhg2V1PIETuxG6qoVqvi50x43ha\nMpP3d1RTV/J8VmlDG21QYRYy3FIm9pqptfiMpV89AoGALxCI/2rdUOtxCh/a3+rZ\nAouEsVpoqYYGPLoRCn8jDppnPviONnTs0I9wi9cv2s/vHh0RoytvIYEQo+LnlTRy\nh9AwzwT4rAjatnOT0L2mEjJKYwz8qMB5hNvV0iSLLlvGj3PJtqj9wOSLsA/Koc3G\nIFG75ik7dQn+fD+yqoDe5vc=";

        $pri_key = "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDKZBKBWzljY7nQ\nhYteL5Xnfr6d3lzKphW7XE1BkLKY6yA1akMkM+LUL+fm8FwU8XOfyx0kucZ09R4p\nhtX1y4OUeiFIbBcYyRvp/XKpvGJD1AusvZqkl7nVQ2aQHzEfqpQCJnyLJXpKw3vs\nUaaCsdnCpDcjhn1Gb/RJQGBm+EEDjFXpa7g/aapG7gLX61IQbi8c1XiHyHAirpD4\nQOekZ16dzo3rGgIKAXWC9SOxkcv+IH0O4TkAIDFnR2D+Jrge/WzPp/iHxhp6A+4g\nv2IkXFH9ryJQAK79T7+iQizDwfm6+RDDKJxYo62+1n+z9EJvLcU0lFw18dgLIDLF\nks/hIqN/AgMBAAECggEATMbwxDS0jREwVLvMdnLr1ZFdw2qu3ctqlBR8VEKNlfgg\nVFMW3F14j5EK0q9c5y7/c19sk1mMQSMZiZxWf3NwW3uHM7+ZdXQZTEcy39QQnPWM\nZj/ZMdZDD3WNq1/B1Wby1ev+tBSIE2OcF7aTyaGpX67HIglrbbSbwcwTpgxIMY02\n1NSK1y9QQ+45ocFqUNCoy6pAvey6scfAm/972CkWIFjjl+59/QGcGhYhYWP9x5Cd\nEWsR5zjilOSQ/o6YbD3jK4xD9yF3PMMwZMfRUoGLyA8Ye3xGVdmuBe85aAyNuEXH\nw57MkshYg5nxrlOCyjow7F7n/7r/0ADO/HfTT4xBtQKBgQDtAG3kXxHjSVdL5O+G\nnprCGuOOEs3UIJUsmtTTAQgOpRkZ2pj7I4A2BegL3/Bw7kVp4Aa1/NgAECuKIWCe\nbjHC6NuFMR76rCfZ0inK26ziXRezmEFzeC6PO0gquAtyZIHGtHhhZTDcacuL5gTi\ncB4klcElWPiBIZZrShrd7xMTOwKBgQDanWMCgmLKXuK3bErmqZmfSOQhCi2j7TEL\npe2JZRdic0OazcXrj9AjQICLOlpCDMHoV4KRCM2rktnGTbC3/8KqO1KVuf6JhtYp\nfQGjBOv6zmYyXa5mD51M+GDtuMKqhkkTZYMvET0a8kItewVyDLxX5uvsZL7l7txL\nckZ0YnpkjQKBgQCiS8v1OoFWYu/r4f+A8XXK/Hzd2tSmshVVcUXSpP8ugDKbOM3Q\nFPSEijDoiNbvjstsAGS4FUiZanlWYxr2A0ICVlGVeRjc0i2MBVZnO129ucA0VUxk\n1WUU4qS91EDKejdAqm9RSulV1wDzcXXg1qRdq0uT1sR+MVD/ccTKPCCU5wKBgF8g\nHuYHoD8YvKQ9rfXilKMXz5SE3kk3O6Eq6Upgv6UqLJ+erGGM6W99cLGkiYO2E2Yr\nNfgwEXZ35uUAB0Z5NtZmC9B050ombugMqfqqeJhg2V1PIETuxG6qoVqvi50x43ha\nMpP3d1RTV/J8VmlDG21QYRYy3FIm9pqptfiMpV89AoGALxCI/2rdUOtxCh/a3+rZ\nAouEsVpoqYYGPLoRCn8jDppnPviONnTs0I9wi9cv2s/vHh0RoytvIYEQo+LnlTRy\nh9AwzwT4rAjatnOT0L2mEjJKYwz8qMB5hNvV0iSLLlvGj3PJtqj9wOSLsA/Koc3G\nIFG75ik7dQn+fD+yqoDe5vc=\n-----END PRIVATE KEY-----\n";
        $payload = [
            'iss' => 'test-101b0@appspot.gserviceaccount.com',
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => time() + 3600,
            'iat' => time(),
        ];

        $jwtHeader = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $jwtHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($jwtHeader));
        $jwtPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));

        $signature = '';
        openssl_sign($jwtHeader . '.' . $jwtPayload, $signature, $pri_key, OPENSSL_ALGO_SHA256);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        $jwt = $jwtHeader.'.'. $jwtPayload.'.'.$base64UrlSignature;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,'https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]));

        $response = curl_exec($ch);
        curl_close($ch);

        $acc_token = json_decode($response, true)['access_token'];
        return $acc_token;

    }

     public static function sendPushNotifictionForMobile($deviceTokens, $title, $body, $module, $status, $sound,$custom_sound_channel,$mass)
    {
        // Convert to array if it's a collection
        $tokens = is_array($deviceTokens) ? $deviceTokens : $deviceTokens->toArray();

        // Remove nulls and duplicates
        $tokens = array_filter($tokens);
        $tokens = array_unique($tokens);

        $token = Common::FCMTokenPushNotification();
        $url = 'https://fcm.googleapis.com/v1/projects/test-101b0/messages:send';
        $responses = [];

        foreach ($tokens as $deviceToken) {
            $data = [
                'message' => [
                    'token' => $deviceToken,
                    'notification' => [
                        'title' => $title,
                        'body'  => $body,
                    ],
                    // 'android' => [
                    //     'notification' => [
                    //         'channel_id' => $custom_sound_channel,
                    //         'sound' => $sound,
                    //         'type'  => $mass,
                    //     ],
                    // ],
                    'data' => [
                        'title'  => $title,
                        'module' => $module,
                        'body'   => $body,
                        'status' => $status,
                        'sound'  => $sound,
                        'type'  => $mass,
                        'channel_id' => $custom_sound_channel,
                    ],
                ],
            ];

            $headers                    = [
                                            'Authorization: Bearer ' . $token,
                                            'Content-Type: application/json',
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                $error_msg = curl_error($ch);
                \Log::error('FCM cURL Error: ' . $error_msg);
                $responses[] = [
                    'deviceToken' => $deviceToken,
                    'status' => false,
                    'message' => 'cURL Error: ' . $error_msg
                ];
                continue;
            }

            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $response_arr = json_decode($response, true);

            // Log the full FCM response for debugging
            \Log::info('FCM Response:', ['http_code' => $http_code, 'response' => $response_arr]);

            if ($http_code !== 200) {
                $errorMsg = isset($response_arr['error']['message']) ? $response_arr['error']['message'] : $response;
                \Log::error('FCM Error: ' . $errorMsg);
                $responses[] = [
                    'deviceToken' => $deviceToken,
                    'status' => false,
                    'message' => 'HTTP Error: ' . $http_code,
                    'fcm_error' => $errorMsg
                ];
                continue;
            }

            if (isset($response_arr['error'])) {
                $responses[] = [
                    'deviceToken' => $deviceToken,
                    'status' => false,
                    'message' => $response_arr['error']
                ];
                continue;
            }
            $responses[] = [
                'deviceToken' => $deviceToken,
                'status' => true,
                'response' => $response_arr
            ];

        }
            // Return success and FCM response
            // return response()->json(['status' => true, 'message' => 'Notification sent successfully.', 'response' => $response_arr], 200);

            // Return all responses
        return response()->json([
            'status' => true,
            'message' => 'Notifications sent.',
            'response' => $responses
        ], 200);
    }


    public static function FindResortHODDepartment($resort_id,$department_id)
    {
        //  currently getting a static rank based on resort HOD
        $emp = Employee::where('resort_id',$resort_id)->where('Dept_id',$department_id)->where("rank",2)->first();
        return  $emp;
    }


    public static function RateConversion($type,$amt,$resort_id)
    {
        $ResortSiteSettings =  ResortSiteSettings::where('resort_id',$resort_id)->first(['MVRtoDoller','DollerToMVR']);

        if($type == "MVRToDoller")
        {
            if($ResortSiteSettings && $ResortSiteSettings->MVRtoDoller > 0)
            {
                $convertedAmount = round($amt / $ResortSiteSettings->MVRtoDoller);
                return $convertedAmount;
            }
            else
            {
                return 0;
            }
        }
        elseif($type=="DollerToMVR")
        {

            if($ResortSiteSettings && $ResortSiteSettings->DollertoMVR > 0)
            {
                $convertedAmount = round($amt * $ResortSiteSettings->DollertoMVR);
                return $convertedAmount;
            }
            else
            {
                return 0;
            }
        }


    }

    public static function VisaRenewalCost($resort_id)
    {


         $ResortBudgetCost = ResortBudgetCost::whereIn('particulars',[
                                                                    'Visa Fee',
                                                                    'visa fee',
                                                                    'VISA FEE',
                                                                    'QUOTA SLOT DEPOSIT',
                                                                    'Quota Slot Deposit',
                                                                    'quota slot deposit',
                                                                    'Quota Slot Deposit',
                                                                    'Work Permit Fee',
                                                                    'work permit fee',
                                                                    'WORK PERMIT FEE',
                                                                    'Work Visa Medical test fee',
                                                                    'Work Visa Medical Test Fee',
                                                                    'work visa medical test fee',
                                                                    'MEDICAL INSURANCE - INTERNATIONAL',
                                                                    'medical insurance - international',
                                                                    'Medical Insurance - International',
                                                                    'MEDICAL INSURANCE'])
                                                                    ->where("details","Xpat Only")
                                                                    ->where('status','active')
                                                                    ->where('resort_id',$resort_id)
                                                                    ->orderBy('updated_at', 'DESC')
                                                                    ->get(['particulars','amount','amount_unit'])
                                                                    ->map(function ($item) use($resort_id){
                                                                        $item->particulars = strtoupper(trim($item->particulars));

                                                                        if(in_array($item->amount_unit, ["$", "USD"]))
                                                                        {
                                                                            $type = 'DollerToMVR';
                                                                            $Amt_type ="MVR";
                                                                        }
                                                                        else
                                                                        {
                                                                            $Amt_type ="$";
                                                                            $type = 'MVRToDoller';
                                                                        }
                                                                        $item->Amount_unit = $Amt_type;


                                                                        $item->Newamount = self::RateConversion($type, $item->amount, $resort_id);
                                                                        return $item;
                                                                    })->mapWithKeys(function ($item) {

                                                                        $key = strtoupper(trim($item->particulars));
                                                                        return [$key => [
                                                                            'amount' =>$item->Newamount,
                                                                            'unit'   => $item->Amount_unit,
                                                                        ]];
                                                                    })->toArray();

        return $ResortBudgetCost;
    }

    public static function PaymentRequest($resort_id)
    {


        $paymentRequest = PaymentRequest::where('resort_id', $resort_id)->orderBy("id","desc")->first('Requestd_id');
        $newstring='';
        if(isset($paymentRequest))
        {
            $newstring = explode("-",$paymentRequest->Requestd_id);


            if(!empty($newstring) && array_key_exists(1,$newstring) && !empty($newstring[2]))
            {

                $newstring = $newstring[2]+1;

            }
            else
            {
                $newstring= 1;
            }
        }else{
            $newstring = 1;
        }
        return $newstring;

    }

    public static function findClinicStaff($resort_id)
    {
        $clinicStaff                                    =   Employee::where('resort_id', $resort_id)
                                                            ->where('rank', 12) // Assuming rank 12 is for clinic staff
                                                            ->select('id', 'rank')
                                                            ->first();

        return $clinicStaff;
    }

    // Resort page wise Permissions
    public static function resortHasPermissions($module_id = '', $pageid = '', $Permission_id = '')
    {
       $Resort = Auth::guard('resort-admin')->user();


        // Super admins always have access
        if ($Resort->type === "super" && Auth::guard('resort-admin')->check()) {
            return true;
        }

        // Basic validation
        if (empty($module_id) || empty($pageid) || empty($Permission_id)) {
            return false;
        }

        $Position_id = $Resort->GetEmployee->Position_id;
        $Resort_id   = $Resort->GetEmployee->resort_id;

        // Optimized: check if such permission exists directly
        return ResortPagewisePermission::where('resort_pagewise_permissions.resort_id', $Resort_id)
            ->where('resort_pagewise_permissions.Module_id', $module_id)
            ->where('resort_pagewise_permissions.page_permission_id', $pageid)
            ->whereHas('resort_internal_pages', function ($query) use ($Permission_id, $Position_id) {
                $query->where('permission_id', $Permission_id)
                    ->where('position_id', $Position_id);
            })->exists();
    }


    public static function createFolderByResort($resort_id)
    {

            $resort = Resort::find($resort_id);
            $main_folder = $resort->resort_id;
            if (!$resort) {
                return false;
            }


            $s3 = Storage::disk('s3');

            $basePath = $main_folder;
            $publicPath = $basePath . '/public';
            $categorizedPath = $publicPath . '/categorized';
            $uncategorizedPath = $publicPath . '/uncategorized';


            try
            {
                $s3->put($basePath . '/', '');
                $s3->put($publicPath . '/', '');
                $s3->put($categorizedPath . '/', '');
                $s3->put($uncategorizedPath . '/.gitkeep', '');
                $s3->put($uncategorizedPath . '/Employee_Handbook', '');

            return true;
            } catch (Exception $e) {
                \Log::error('Failed to create S3 folder structure for resort ' . $resort_id . ': ' . $e->getMessage());
                return false;
            }
    }

    public static function TalentAcquisitionFolder($resort_id,$vacancy_id,$file_name)
    {

        $data = [];

                 $resort = Resort::find($resort_id);
            if (!$resort) {
                return false;
                 $data['status'] = false;
            }
            $main_folder = $resort->resort_id;
            $s3 = Storage::disk('s3');

            // Define the base path for talent acquisition
            $basePath = $main_folder . '/public/talent_acquisition/'.base64_encode($vacancy_id);

            // Check if folder exists by listing objects with this prefix
            try { $folderExists = $s3->exists($basePath . '/.gitkeep');

            // If folder doesn't exist, create it
            if (!$folderExists) {
                // Create folder by putting an empty .gitkeep file
                $s3->put($basePath . '/.gitkeep', '');
            }

            // Store the original file object
            $uploadedFile = $file_name;

            // Generate new filename
            $newFileName = uniqid('video_', true) . '.' . $uploadedFile->getClientOriginalExtension();

            // Now upload the file to the folder
            $filePath = $basePath . '/' . $newFileName;
            $s3->put($filePath, file_get_contents($uploadedFile->getRealPath()));

            // Store file path in data array
            $data['status'] = true;
            $data['path'] = $filePath;
            $data['filename'] = $newFileName;

            return $data;
        }
        catch (\Exception $e)
        {
            \Log::error('Failed to create talent acquisition folder or upload file: ' . $e->getMessage());
            return false;
        }

    }

    public static function ApplicantWiseStorefileaws($resort_id, $vacancy_id, $file_name)
    {
        $data = [];
        try {
            $resort = Resort::find($resort_id);

            if (!$resort) {
                return ['status' => false];
            }
            $main_folder = $resort->resort_id;

            // Generate new filename
            $uploadedFile = $file_name;
            $prefix = 'applicant_';
            $randomPart = Str::random(8);
            $timestamp = time();
            $newFileName = $prefix . $timestamp . '_' . $randomPart . '.' . $uploadedFile->getClientOriginalExtension();

            $basePath = $main_folder . '/public/talent_acquisition/' . base64_encode($vacancy_id);

            $driver = config('filesystems.default', 'local');

            if ($driver === 's3') {
                $s3 = Storage::disk('s3');

                $folderExists = $s3->exists($basePath . '/.gitkeep');
                if (!$folderExists) {
                    $s3->put($basePath . '/.gitkeep', '');
                }

                $filePath = $basePath . '/' . $newFileName;
                $s3->put($filePath, file_get_contents($uploadedFile->getRealPath()));
            } else {
                $localPath = 'talent_acquisition/' . base64_encode($vacancy_id);
                $fullDir = public_path($localPath);
                if (!file_exists($fullDir)) {
                    mkdir($fullDir, 0755, true);
                }
                $uploadedFile->move($fullDir, $newFileName);
                $filePath = $localPath . '/' . $newFileName;
            }

            $data['status'] = true;
            $data['path'] = $filePath;
            $data['filename'] = $newFileName;

            return $data;
             } catch (\Exception $e) {
            \Log::error('Failed to create talent acquisition folder or upload file: ' . $e->getMessage());
            return ['status' => false];
        }
    }

    public static function checkRouteWisePermission($routeName,$permission_id){


        $Resort = Auth::guard('resort-admin')->user();
        if($Resort->type === "super" && Auth::guard('resort-admin')->check()) {
            return true;
        }

        $Position_id = $Resort->GetEmployee->Position_id;
        $Resort_id   = $Resort->GetEmployee->resort_id;
        $permission_id = $permission_id;

        $pagesList = ModulePages::where('internal_route',$routeName)
        ->where('TypeOfPage','InsideOfMenu')->where('type','normal')->first();
        $bypassRoutes = [
            'resort.Page.Permission',
            'resort.getMenuData',
        ];

       if(in_array($routeName,$bypassRoutes)){
            return true; // Bypass routes do not require permission check
       }

       if($pagesList){
           $hasViewPermission = Common::resortHasPermission($pagesList->Module_Id, $pagesList->id, $permission_id);
        }else{
            $pagesList = ModulePages::where('internal_route',$routeName)
                    ->where('TypeOfPage','InsideOfPage')->where('type','normal')->first();
            if(!$pagesList){
                return true; // No page found for this route
            }
            $hasViewPermission = Common::resortHasPermission($pagesList->Module_Id, $pagesList->id, $permission_id);;
        }

        if($hasViewPermission){
            return true;
        }else{
            return false;
        }
    }

    public static function getCurrentCutoffPeriod($cutoff_day)
    {
        $today = now();

        $cutoffStart = now()->day >= $cutoff_day
            ? now()->copy()->day($cutoff_day)
            : now()->subMonth()->copy()->day($cutoff_day);

        $cutoffEnd = $cutoffStart->copy()->addMonth()->subDay(); // e.g., 10 July → 9 August

        return [
            'start' => $cutoffStart->startOfDay(),
            'end' => $cutoffEnd->endOfDay()
        ];
    }

    public static function convertToWords($amount)
    {
        $fmt = new NumberFormatter('en', NumberFormatter::SPELLOUT);
        return ucfirst($fmt->format($amount));
    }

    public static function getNotificationCount($resort_id,$user_id){


        $resortNotificationCount =  ResortNotification::select([
                    'id', 'module', 'type', 'message', 'status', 'created_at'
                ])
                ->where('resort_id', $resort_id)->count();

        if($resortNotificationCount > 100){
            return '99+';
        }else{
            return $resortNotificationCount;
        }
    }


    public static function GetResortPositionWiseRank($position_id,$position_rank, $resort_id)
    {

        $ResortPosition = ResortPosition::where('resort_id', $resort_id)->where('id', $position_id)->first(['position_title']);
        if($ResortPosition)
        {
            if($ResortPosition->Rank != 8)   // not a gm rank
            {
                if($ResortPosition->position_title == "Security Officer") // SO
                {
                    return 10;
                }
                elseif($ResortPosition->position_title == "Director Of Finance") // DOF
                {
                    return 7;
                }
                elseif($ResortPosition->position_title == "Director Of Engineering") // DOE
                {
                    return 11;
                }
                elseif($ResortPosition->position_title == "Clinic Staff") // Clinic Staff
                {
                    return 12;
                }
                else
                {
                    if($ResortPosition)
                    {
                        $position = Position::where('position_title', $ResortPosition->position_title)->where('status', 'active')->first();
                        if($position)
                        {
                            return 3;
                        }
                        else
                        {
                            return $position_rank; // Return the original rank if no specific rank is found
                        }
                    }
                    else
                    {
                        return $position_rank; // Return the original rank if no specific rank is found
                    }
                }
            }
            else
            {
                return  $position_rank;
            }
        }
    }
    public static function UploadProfileAwsPic($basePath,$file)
    {
        $data = [];
        try {
            // Get storage driver from environment variable (s3, local, wasabi)
            $storageDriver = env('STORAGE_DRIVER', 's3');

            $newFileName = $file->getClientOriginalName();
            $mimeType = $file->getClientMimeType();
            $filePath = $basePath . '/' . $newFileName;

            // Route to appropriate storage based on environment variable
            if ($storageDriver === 'local') {
                // Upload to local storage
                $disk = Storage::disk('local');
                // Ensure directory exists
                $fullPath = $disk->path($filePath);
                $directory = dirname($fullPath);
                if (!file_exists($directory)) {
                    mkdir($directory, 0755, true);
                }
                $disk->put($filePath, file_get_contents($file->getRealPath()));
                $data['status'] = true;
                $data['path'] = $filePath;
                $data['filename'] = $newFileName;
            } elseif ($storageDriver === 'wasabi') {
                // Upload to Wasabi (S3-compatible)
                $wasabi = Storage::disk('wasabi');
                $wasabi->put($filePath, file_get_contents($file->getRealPath()));
                $data['status'] = true;
                $data['path'] = $filePath;
                $data['filename'] = $newFileName;
            } else {
                // Default to S3
                $s3 = Storage::disk('s3');
                $s3->put($filePath, file_get_contents($file->getRealPath()));
                $data['status'] = true;
                $data['path'] = $filePath;
                $data['filename'] = $newFileName;
            }

            return $data;
        } catch (\Exception $e) {
            \Log::error('Failed to upload profile image: ' . $e->getMessage());
            return ['status' => false, 'msg' => 'Failed to upload profile image: ' . $e->getMessage()];
        }
    }



    /**
     * Determine employee rank and whether user belongs to HR.
     *
     * @param  \App\Models\Employee|int|null  $employee
     * @return array  ['rank' => string|null, 'isHR' => bool]
     */
    public static function getEmployeeRank($employee = null)
    {
        // Handle employee instance or ID
        if (is_numeric($employee)) {
            $employee = Employee::find($employee);
        }

        if (!$employee) {
            return ['rank' => null, 'isHR' => false];
        }

        // === Department-based HR detection ===
        $department = $employee->department ?? null;
        $isHR = false;

        if ($department) {
            // Normalize department name (remove case & spaces)
            $deptName = strtolower(trim($department->name));

            if (in_array($deptName, ['human resources', 'hr'])) {
                $isHR = true;
            }
        }

        // === Optional: rank logic (if config still used) ===
        $rankMap = config('settings.eligibilty', []);
        $rankKey = $employee->rank ?? null;
        $rankValue = $rankMap[$rankKey] ?? null;

        return [
            'rank' => $title ?? $rankValue,
            'isHR' => $isHR,
        ];
    }

    /**
     * Determine employee rank and position (HR, Finance, GM).
     *
     * @param  \App\Models\Employee|int|null  $employee
     * @return array  [
     *     'rank' => string|null,
     *     'position' => 'HR'|'Finance'|'GM'|null,
     * ]
     */
    public static function getEmployeeRankPosition($employee = null)
    {
        // If ID is passed, fetch employee
        if (is_numeric($employee)) {
            $employee = Employee::find($employee);
        }

        if (!$employee) {
            return [
                'rank' => null,
                'position' => null
            ];
        }

        // ===== Determine Department =====
        $department = $employee->department->name ?? null;
        $dept = strtolower(trim($department));
        $position = null;

        if (in_array($dept, ['human resources', 'hr'])) {
            $position = 'HR';
        }
        elseif (in_array($dept, ['accounting','Accounting Manager','accounting manager','finance', 'finance manager'])) {
            $position = 'Finance';
        }
        elseif (in_array($dept, ['general manager', 'gm'])) {
            $position = 'GM';
        }

        // ===== Get Rank from config =====
        $rankMap = config('settings.eligibilty', []);
        $rankKey = $employee->rank ?? null;
        $rankValue = $rankMap[$rankKey] ?? null;

        return [
            'rank' => $rankValue,
            'position' => $position,
        ];
    }

    /**
     * Calculate position total budget from employees and vacant positions
     *
     * @param array $positionData Position data with employees and vacant configurations
     * @param object $resortCosts Collection of resort budget costs
     * @param int $resortId Resort ID for currency conversion
     * @return float Total budget for the position
     */
    public static function calculatePositionTotal($positionData, $resortCosts, $resortId)
    {
        // Get MVR to Dollar conversion rate
        $mvrToDollarRate = 0.065; // Default value
        $resortSettings = ResortSiteSettings::where('resort_id', $resortId)->first();
        if ($resortSettings && $resortSettings->MVRtoDoller) {
            $mvrToDollarRate = $resortSettings->MVRtoDoller;
        }

        $totalBasicSalary = 0;
        $totalCurrentSalary = 0;
        $costTotals = [];

        // Initialize cost totals array
        foreach ($resortCosts as $cost) {
            $costTotals[$cost->id] = 0;
        }

        // Sum employee salaries and costs
        if (!empty($positionData['employees'])) {
            foreach ($positionData['employees'] as $employee) {
                $totalBasicSalary += $employee->configured_basic_salary ?? 0;
                $totalCurrentSalary += $employee->configured_current_salary ?? 0;

                if (isset($employee->budget_configurations) && $employee->budget_configurations->isNotEmpty()) {
                    foreach ($employee->budget_configurations as $config) {
                        // Convert to USD if needed (value is already yearly total)
                        $valueInUSD = $config->currency === 'MVR'
                            ? $config->value * $mvrToDollarRate
                            : $config->value;
                        $costTotals[$config->resort_budget_cost_id] = ($costTotals[$config->resort_budget_cost_id] ?? 0) + $valueInUSD;
                    }
                }
            }
        }

        // Sum vacant salaries and costs
        if (!empty($positionData['max_counts']['max_vacantcount']) && $positionData['max_counts']['max_vacantcount'] > 0) {
            for ($i = 1; $i <= $positionData['max_counts']['max_vacantcount']; $i++) {
                $vacantConfig = $positionData['vacant_configurations'][$i] ?? null;
                if ($vacantConfig) {
                    $totalBasicSalary += $vacantConfig['vacant_budget_cost']->basic_salary ?? 0;
                    $totalCurrentSalary += $vacantConfig['vacant_budget_cost']->current_salary ?? 0;

                    if (isset($vacantConfig['configurations'])) {
                        foreach ($vacantConfig['configurations'] as $config) {
                            // Convert to USD if needed (value is already yearly total)
                            $valueInUSD = $config->currency === 'MVR'
                                ? $config->value * $mvrToDollarRate
                                : $config->value;
                            $costTotals[$config->resort_budget_cost_id] = ($costTotals[$config->resort_budget_cost_id] ?? 0) + $valueInUSD;
                        }
                    }
                }
            }
        }

        // Calculate grand total: basic salary + current salary + all cost totals
        $grandTotal = $totalBasicSalary + $totalCurrentSalary + array_sum($costTotals);

        return $grandTotal;
    }

    /**
     * Calculate section total budget from all positions in the section
     *
     * @param array $sectionData Section data with positions
     * @param object $resortCosts Collection of resort budget costs
     * @param int $resortId Resort ID for currency conversion
     * @return float Total budget for the section
     */
    public static function calculateSectionTotal($sectionData, $resortCosts, $resortId)
    {
        $sectionTotal = 0;

        if (!empty($sectionData['positions'])) {
            foreach ($sectionData['positions'] as $positionName => $positionData) {
                $sectionTotal += self::calculatePositionTotal($positionData, $resortCosts, $resortId);
            }
        }

        return $sectionTotal;
    }

    /**
     * Calculate department total budget from sections and direct positions
     *
     * @param array $departmentData Department data with sections and positions
     * @param object $resortCosts Collection of resort budget costs
     * @param int $resortId Resort ID for currency conversion
     * @return float Total budget for the department
     */
    public static function calculateDepartmentTotal($departmentData, $resortCosts, $resortId)
    {
        $departmentTotal = 0;

        // Sum totals from sections
        if (!empty($departmentData['sections'])) {
            foreach ($departmentData['sections'] as $sectionName => $sectionData) {
                $departmentTotal += self::calculateSectionTotal($sectionData, $resortCosts, $resortId);
            }
        }

        // Sum totals from direct positions (not in sections)
        if (!empty($departmentData['positions'])) {
            foreach ($departmentData['positions'] as $positionName => $positionData) {
                $departmentTotal += self::calculatePositionTotal($positionData, $resortCosts, $resortId);
            }
        }

        return $departmentTotal;
    }

    /**
     * Calculate division total budget from all departments
     *
     * @param array $divisionData Division data with departments
     * @param object $resortCosts Collection of resort budget costs
     * @param int $resortId Resort ID for currency conversion
     * @return float Total budget for the division
     */
    public static function calculateDivisionTotal($divisionData, $resortCosts, $resortId)
    {
        $divisionTotal = 0;

        if (!empty($divisionData['departments'])) {
            foreach ($divisionData['departments'] as $departmentName => $departmentData) {
                $divisionTotal += self::calculateDepartmentTotal($departmentData, $resortCosts, $resortId);
            }
        }

        return $divisionTotal;
    }

    /**
     * Calculate yearly total for an employee or vacant position
     * This includes: Basic Salary + Current Salary + Sum of all cost configurations (converted to USD)
     *
     * @param object $employeeOrVacant Employee or Vacant object with configured salaries and budget_configurations
     * @param int $resortId Resort ID for currency conversion
     * @return float Yearly total in USD
     */
    public static function calculateYearlyTotal($employeeOrVacant, $resortId)
    {
        // Get MVR to Dollar conversion rate
        $mvrToDollarRate = 0.065; // Default value (1 MVR = 0.065 USD)
        $resortSettings = ResortSiteSettings::where('resort_id', $resortId)->first();
        if ($resortSettings && $resortSettings->MVRtoDoller) {
            $mvrToDollarRate = $resortSettings->MVRtoDoller;
        }

        // Get salaries (yearly totals)
        $basicSalary = $employeeOrVacant->configured_basic_salary ??
                      (isset($employeeOrVacant->basic_salary) ? $employeeOrVacant->basic_salary : 0);
        $currentSalary = $employeeOrVacant->configured_current_salary ??
                        (isset($employeeOrVacant->current_salary) ? $employeeOrVacant->current_salary : 0);

        // Sum all cost configurations (already yearly aggregated)
        $totalCosts = 0;
        if (isset($employeeOrVacant->budget_configurations) && $employeeOrVacant->budget_configurations->isNotEmpty()) {
            foreach ($employeeOrVacant->budget_configurations as $config) {
                // Convert to USD if needed (value is already yearly total)
                $valueInUSD = $config->currency === 'MVR'
                    ? $config->value * $mvrToDollarRate
                    : $config->value;
                $totalCosts += $valueInUSD;
            }
        }

        // Calculate yearly total: Basic Salary + Current Salary + All Costs
        $yearlyTotal = $basicSalary + $currentSalary + $totalCosts;

        return $yearlyTotal;
    }

    /**
     * Calculate yearly total for a vacant position from vacant_configurations array
     *
     * @param array $vacantConfig Vacant configuration array with vacant_budget_cost and configurations
     * @param int $resortId Resort ID for currency conversion
     * @return float Yearly total in USD
     */
    public static function calculateVacantYearlyTotal($vacantConfig, $resortId)
    {
        if (!$vacantConfig || !isset($vacantConfig['vacant_budget_cost'])) {
            return 0;
        }

        // Get MVR to Dollar conversion rate
        $mvrToDollarRate = 0.065; // Default value
        $resortSettings = ResortSiteSettings::where('resort_id', $resortId)->first();
        if ($resortSettings && $resortSettings->MVRtoDoller) {
            $mvrToDollarRate = $resortSettings->MVRtoDoller;
        }

        // Get salaries (yearly totals)
        $basicSalary = $vacantConfig['vacant_budget_cost']->basic_salary ?? 0;
        $currentSalary = $vacantConfig['vacant_budget_cost']->current_salary ?? 0;

        // Sum all cost configurations (already yearly aggregated)
        $totalCosts = 0;
        if (isset($vacantConfig['configurations']) && $vacantConfig['configurations']->isNotEmpty()) {
            foreach ($vacantConfig['configurations'] as $config) {
                // Convert to USD if needed (value is already yearly total)
                $valueInUSD = $config->currency === 'MVR'
                    ? $config->value * $mvrToDollarRate
                    : $config->value;
                $totalCosts += $valueInUSD;
            }
        }

        // Calculate yearly total: Basic Salary + Current Salary + All Costs
        $yearlyTotal = $basicSalary + $currentSalary + $totalCosts;

        return $yearlyTotal;
    }

    /**
     * Calculate overtime entries based on check-in, check-out, shift times, and breaks
     * Handles all scenarios:
     * 1. Overtime before shift (check-in before shift start)
     * 2. Overtime after shift (check-out after shift end)
     * 3. Early check-out scenarios
     * 4. Late check-in scenarios
     * 5. Split overtime (before and after shift)
     *
     * @param string $checkInTime Check-in time (H:i format)
     * @param string $checkOutTime Check-out time (H:i format)
     * @param string $shiftStartTime Shift start time (H:i format)
     * @param string $shiftEndTime Shift end time (H:i format)
     * @param string $date Attendance date (Y-m-d format)
     * @param array $breakData Array of break records with Break_OutTime, Break_InTime, Total_Break_Time
     * @param string $expectedOvertime Expected overtime from roster (HH:MM format, e.g., "02:00")
     * @return array Array of overtime entries to be created
     */
    public static function calculateOvertimeEntries($checkInTime, $checkOutTime, $shiftStartTime, $shiftEndTime, $date, $breakData = [], $expectedOvertime = '00:00')
    {
        // #region agent log
        $logFile = 'c:\wamp64\www\Wisdom-Ai\HRVMS-Wisdom-AI\.cursor\debug.log';
        $logEntry = json_encode([
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'A',
            'location' => 'Common.php:calculateOvertimeEntries:ENTRY',
            'message' => 'Function entry with parameters',
            'data' => [
                'checkInTime' => $checkInTime,
                'checkOutTime' => $checkOutTime,
                'shiftStartTime' => $shiftStartTime,
                'shiftEndTime' => $shiftEndTime,
                'date' => $date,
                'expectedOvertime' => $expectedOvertime,
                'breakDataCount' => is_array($breakData) ? count($breakData) : (is_object($breakData) ? count((array)$breakData) : 0)
            ],
            'timestamp' => round(microtime(true) * 1000)
        ]) . "\n";
        @file_put_contents($logFile, $logEntry, FILE_APPEND);
        // #endregion

        $overtimeEntries = [];

        // Parse times to Carbon instances
        $checkInCarbon = Carbon::createFromFormat('H:i', $checkInTime);
        $checkOutCarbon = Carbon::createFromFormat('H:i', $checkOutTime);
        $shiftStartCarbon = Carbon::createFromFormat('H:i', $shiftStartTime);
        $shiftEndCarbon = Carbon::createFromFormat('H:i', $shiftEndTime);

        // Combine with date for proper day handling
        $checkInDateTime = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $checkInTime);
        $checkOutDateTime = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $checkOutTime);
        $shiftStartDateTime = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $shiftStartTime);
        $shiftEndDateTime = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $shiftEndTime);

        // Handle overnight shifts (end time < start time means next day)
        if ($shiftEndCarbon->lt($shiftStartCarbon)) {
            $shiftEndDateTime->addDay();
        }

        // Handle check-out on next day
        if ($checkOutCarbon->lt($checkInCarbon)) {
            $checkOutDateTime->addDay();
        }

        // Calculate expected shift duration in minutes
        $shiftDurationMinutes = $shiftStartDateTime->diffInMinutes($shiftEndDateTime);

        // Parse expected overtime
        $expectedOvertimeMinutes = 0;
        if (!empty($expectedOvertime) && $expectedOvertime != '00:00') {
            $otParts = explode(':', $expectedOvertime);
            $expectedOvertimeMinutes = (isset($otParts[0]) ? (int)$otParts[0] : 0) * 60 + (isset($otParts[1]) ? (int)$otParts[1] : 0);
        }

        // Calculate total break time in minutes
        $totalBreakMinutes = 0;
        $breakPeriods = [];
        if (!empty($breakData)) {
            foreach ($breakData as $break) {
                if (!empty($break->Break_OutTime) && !empty($break->Break_InTime)) {
                    $breakOut = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $break->Break_OutTime);
                    $breakIn = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $break->Break_InTime);

                    // Handle break spanning to next day
                    if ($breakIn->lt($breakOut)) {
                        $breakIn->addDay();
                    }

                    $breakMinutes = $breakOut->diffInMinutes($breakIn);
                    $totalBreakMinutes += $breakMinutes;

                    $breakPeriods[] = [
                        'start' => $breakOut,
                        'end' => $breakIn,
                        'minutes' => $breakMinutes
                    ];
                } elseif (!empty($break->Total_Break_Time)) {
                    // Fallback to Total_Break_Time if individual times not available
                    $breakParts = explode(':', $break->Total_Break_Time);
                    $totalBreakMinutes += (isset($breakParts[0]) ? (int)$breakParts[0] : 0) * 60 + (isset($breakParts[1]) ? (int)$breakParts[1] : 0);
                }
            }
        }

        // Calculate actual worked time (check-out - check-in - breaks)
        $actualWorkMinutes = $checkInDateTime->diffInMinutes($checkOutDateTime) - $totalBreakMinutes;
        if ($actualWorkMinutes < 0) {
            $actualWorkMinutes = 0;
        }

        // #region agent log
        $logEntry = json_encode([
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'A,B',
            'location' => 'Common.php:calculateOvertimeEntries:CALCULATIONS',
            'message' => 'Time calculations before overtime logic',
            'data' => [
                'shiftDurationMinutes' => $shiftDurationMinutes,
                'totalBreakMinutes' => $totalBreakMinutes,
                'actualWorkMinutes' => $actualWorkMinutes,
                'normalShiftMinutes' => $shiftDurationMinutes,
                'expectedOvertimeMinutes' => $expectedOvertimeMinutes,
                'checkInDateTime' => $checkInDateTime->format('Y-m-d H:i:s'),
                'checkOutDateTime' => $checkOutDateTime->format('Y-m-d H:i:s'),
                'shiftStartDateTime' => $shiftStartDateTime->format('Y-m-d H:i:s'),
                'shiftEndDateTime' => $shiftEndDateTime->format('Y-m-d H:i:s')
            ],
            'timestamp' => round(microtime(true) * 1000)
        ]) . "\n";
        @file_put_contents($logFile, $logEntry, FILE_APPEND);
        // #endregion

        // Calculate normal shift time (shift end - shift start)
        $normalShiftMinutes = $shiftDurationMinutes;

        // SCENARIO 1: Overtime before shift (check-in before shift start)
        // Handle work that happens entirely before shift start OR work that starts before shift
        $workEntirelyBeforeShift = false; // Initialize flag
        if ($checkInDateTime->lt($shiftStartDateTime)) {
            // Case 1: Work entirely before shift (check-out before or at shift start)
            if ($checkOutDateTime->lte($shiftStartDateTime)) {
                // #region agent log
                $logEntry = json_encode([
                    'sessionId' => 'debug-session',
                    'runId' => 'run1',
                    'hypothesisId' => 'BEFORE_SHIFT',
                    'location' => 'Common.php:calculateOvertimeEntries:WORK_BEFORE_SHIFT',
                    'message' => 'Work entirely before shift detected',
                    'data' => [
                        'checkInTime' => $checkInDateTime->format('H:i'),
                        'checkOutTime' => $checkOutDateTime->format('H:i'),
                        'shiftStartTime' => $shiftStartDateTime->format('H:i'),
                        'actualWorkMinutes' => $actualWorkMinutes
                    ],
                    'timestamp' => round(microtime(true) * 1000)
                ]) . "\n";
                @file_put_contents($logFile, $logEntry, FILE_APPEND);
                // #endregion

                // All work time is overtime before shift
                $overtimeBeforeStart = $actualWorkMinutes;

                // Adjust for breaks that occurred before shift start
                foreach ($breakPeriods as $break) {
                    if ($break['start']->lt($shiftStartDateTime) && $break['end']->lte($shiftStartDateTime)) {
                        $overtimeBeforeStart -= $break['minutes'];
                    }
                }

                if ($overtimeBeforeStart > 0) {
                    $overtimeStart = $checkInDateTime->format('H:i');
                    $overtimeEnd = $checkOutDateTime->format('H:i');

                    $overtimeEntries[] = [
                        'start_time' => $overtimeStart,
                        'end_time' => $overtimeEnd,
                        'total_time' => self::minutesToTimeFormat($overtimeBeforeStart),
                        'overtime_type' => 'before_shift',
                        'start_location' => null,
                        'end_location' => null,
                    ];

                    // Mark that we've handled work entirely before shift - SCENARIO 3 should not interfere
                    $workEntirelyBeforeShift = true;
                }
            }
            // Case 2: Work starts before shift and continues to/after shift end
            elseif ($checkOutDateTime->gte($shiftEndDateTime)) {
                // Normal case: check-in before shift, check-out at or after shift end
                // Calculate time before shift start
                $overtimeBeforeStart = $checkInDateTime->diffInMinutes($shiftStartDateTime);

                // Adjust for breaks that occurred before shift start
                foreach ($breakPeriods as $break) {
                    if ($break['start']->lt($shiftStartDateTime) && $break['end']->lte($shiftStartDateTime)) {
                        $overtimeBeforeStart -= $break['minutes'];
                    }
                }

                // Calculate excess time worked
                $excessTime = $actualWorkMinutes - $normalShiftMinutes;

                // Overtime before shift = min(time before shift, excess time)
                // This ensures we don't count more overtime than actually worked
                $actualOvertimeBefore = min($overtimeBeforeStart, max(0, $excessTime));

                if ($actualOvertimeBefore > 0) {
                    $overtimeStart = $checkInDateTime->format('H:i');
                    $overtimeEnd = $shiftStartDateTime->format('H:i');

                    $overtimeEntries[] = [
                        'start_time' => $overtimeStart,
                        'end_time' => $overtimeEnd,
                        'total_time' => self::minutesToTimeFormat($actualOvertimeBefore),
                        'overtime_type' => 'before_shift',
                        'start_location' => null,
                        'end_location' => null,
                    ];
                }
            }
            // Case 3: Work starts before shift but ends during shift (handled in SCENARIO 3)
        }

        // SCENARIO 2: Overtime after shift (check-out after shift end)
        // This will be refined in SCENARIO 4 for late check-in cases
        if ($checkOutDateTime->gt($shiftEndDateTime) && $checkInDateTime->lte($shiftStartDateTime)) {
            // Normal case: check-in at or before shift start, check-out after shift end
            // Calculate time after shift end
            $overtimeAfterEnd = $shiftEndDateTime->diffInMinutes($checkOutDateTime);

            // Adjust for breaks that occurred after shift end
            foreach ($breakPeriods as $break) {
                if ($break['start']->gte($shiftEndDateTime) && $break['end']->gt($shiftEndDateTime)) {
                    $overtimeAfterEnd -= $break['minutes'];
                }
            }

            // Calculate excess time worked (already calculated above)
            // Subtract any overtime before shift that we already calculated
            $overtimeBeforeMinutes = 0;
            foreach ($overtimeEntries as $entry) {
                if ($entry['overtime_type'] === 'before_shift') {
                    $overtimeBeforeMinutes = self::timeFormatToMinutes($entry['total_time']);
                    break;
                }
            }

            // Remaining excess time for after shift overtime
            $remainingExcess = $actualWorkMinutes - $normalShiftMinutes - $overtimeBeforeMinutes;

            // Overtime after shift = min(time after shift, remaining excess)
            $actualOvertimeAfter = min($overtimeAfterEnd, max(0, $remainingExcess));

            if ($actualOvertimeAfter > 0) {
                $overtimeStart = $shiftEndDateTime->format('H:i');
                $overtimeEnd = $checkOutDateTime->format('H:i');

                $overtimeEntries[] = [
                    'start_time' => $overtimeStart,
                    'end_time' => $overtimeEnd,
                    'total_time' => self::minutesToTimeFormat($actualOvertimeAfter),
                    'overtime_type' => 'after_shift',
                    'start_location' => null,
                    'end_location' => null,
                ];
            }
        }

        // SCENARIO 3: Early check-out handling
        // If check-out is before shift end and check-in was before shift start
        // BUT only if work extends into the shift (check-out is after shift start)
        // AND work entirely before shift was NOT already handled
        // Work entirely before shift is handled in SCENARIO 1 Case 1
        // #region agent log
        $logEntry = json_encode([
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'C',
            'location' => 'Common.php:calculateOvertimeEntries:SCENARIO3_CHECK',
            'message' => 'SCENARIO 3 guard check',
            'data' => [
                'workEntirelyBeforeShift' => $workEntirelyBeforeShift,
                'checkOutLtShiftEnd' => $checkOutDateTime->lt($shiftEndDateTime),
                'checkOutGtShiftStart' => $checkOutDateTime->gt($shiftStartDateTime),
                'checkInLtShiftStart' => $checkInDateTime->lt($shiftStartDateTime)
            ],
            'timestamp' => round(microtime(true) * 1000)
        ]) . "\n";
        @file_put_contents($logFile, $logEntry, FILE_APPEND);
        // #endregion

        if (!$workEntirelyBeforeShift) {
            if ($checkOutDateTime->lt($shiftEndDateTime) && $checkOutDateTime->gt($shiftStartDateTime) && $checkInDateTime->lt($shiftStartDateTime)) {
            // #region agent log
            $logEntry = json_encode([
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'C',
                'location' => 'Common.php:calculateOvertimeEntries:SCENARIO3_ENTRY',
                'message' => 'Early check-out scenario detected',
                'data' => [
                    'checkOutBeforeShiftEnd' => true,
                    'checkInBeforeShiftStart' => true
                ],
                'timestamp' => round(microtime(true) * 1000)
            ]) . "\n";
            @file_put_contents($logFile, $logEntry, FILE_APPEND);
            // #endregion

            // Calculate total time worked (check-out - check-in - breaks)
            $totalTimeWorked = $checkInDateTime->diffInMinutes($checkOutDateTime) - $totalBreakMinutes;
            if ($totalTimeWorked < 0) {
                $totalTimeWorked = 0;
            }

            // Calculate time before shift start
            $overtimeBeforeStart = $checkInDateTime->diffInMinutes($shiftStartDateTime);

            // If total time worked is less than normal shift, no overtime before shift
            // The time before shift is just part of normal attendance to make up for early check-out
            if ($totalTimeWorked < $normalShiftMinutes) {
                // #region agent log
                $logEntry = json_encode([
                    'sessionId' => 'debug-session',
                    'runId' => 'run1',
                    'hypothesisId' => 'C',
                    'location' => 'Common.php:calculateOvertimeEntries:SCENARIO3_NO_OT',
                    'message' => 'No overtime - total worked less than normal shift',
                    'data' => [
                        'totalTimeWorked' => $totalTimeWorked,
                        'normalShiftMinutes' => $normalShiftMinutes
                    ],
                    'timestamp' => round(microtime(true) * 1000)
                ]) . "\n";
                @file_put_contents($logFile, $logEntry, FILE_APPEND);
                // #endregion

                // Remove any before_shift overtime entries we might have added
                $overtimeEntries = array_filter($overtimeEntries, function($entry) {
                    return $entry['overtime_type'] !== 'before_shift';
                });
                $overtimeEntries = array_values($overtimeEntries); // Re-index
            } else {
                // They worked at least normal shift time
                // Calculate excess time (time worked beyond normal shift)
                $excessTime = $totalTimeWorked - $normalShiftMinutes;

                // Overtime before shift = min(time before shift, excess time)
                // Example: Check-in 2:00 AM, shift 4:00-11:00 AM, check-out 10:00 AM
                // Total worked = 8 hours, normal shift = 7 hours, excess = 1 hour
                // Time before shift = 2 hours, so overtime = min(2, 1) = 1 hour
                $actualOvertimeBefore = min($overtimeBeforeStart, $excessTime);

                // #region agent log
                $logEntry = json_encode([
                    'sessionId' => 'debug-session',
                    'runId' => 'run1',
                    'hypothesisId' => 'C',
                    'location' => 'Common.php:calculateOvertimeEntries:SCENARIO3_CALC',
                    'message' => 'Early check-out overtime calculation',
                    'data' => [
                        'totalTimeWorked' => $totalTimeWorked,
                        'normalShiftMinutes' => $normalShiftMinutes,
                        'excessTime' => $excessTime,
                        'overtimeBeforeStart' => $overtimeBeforeStart,
                        'actualOvertimeBefore' => $actualOvertimeBefore
                    ],
                    'timestamp' => round(microtime(true) * 1000)
                ]) . "\n";
                @file_put_contents($logFile, $logEntry, FILE_APPEND);
                // #endregion

                if ($actualOvertimeBefore > 0) {
                    // Update or add before_shift entry
                    $found = false;
                    foreach ($overtimeEntries as &$entry) {
                        if ($entry['overtime_type'] === 'before_shift') {
                            $entry['total_time'] = self::minutesToTimeFormat($actualOvertimeBefore);
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $overtimeEntries[] = [
                            'start_time' => $checkInDateTime->format('H:i'),
                            'end_time' => $shiftStartDateTime->format('H:i'),
                            'total_time' => self::minutesToTimeFormat($actualOvertimeBefore),
                            'overtime_type' => 'before_shift',
                            'start_location' => null,
                            'end_location' => null,
                        ];
                    }
                } else {
                    // Remove before_shift entry if no overtime
                    $overtimeEntries = array_filter($overtimeEntries, function($entry) {
                        return $entry['overtime_type'] !== 'before_shift';
                    });
                    $overtimeEntries = array_values($overtimeEntries);
                }
            }
            }
        }

        // SCENARIO 4: Late check-in handling
        // If check-in is after shift start and check-out is after shift end
        if ($checkInDateTime->gt($shiftStartDateTime) && $checkOutDateTime->gt($shiftEndDateTime)) {
            // #region agent log
            $logEntry = json_encode([
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'D',
                'location' => 'Common.php:calculateOvertimeEntries:SCENARIO4_ENTRY',
                'message' => 'Late check-in scenario detected',
                'data' => [
                    'checkInAfterShiftStart' => true,
                    'checkOutAfterShiftEnd' => true
                ],
                'timestamp' => round(microtime(true) * 1000)
            ]) . "\n";
            @file_put_contents($logFile, $logEntry, FILE_APPEND);
            // #endregion

            $timeWorkedDuringShift = $checkInDateTime->diffInMinutes($shiftEndDateTime);
            $overtimeAfterEnd = $shiftEndDateTime->diffInMinutes($checkOutDateTime);

            // If they worked less than normal shift during shift time, adjust overtime
            if ($timeWorkedDuringShift < $normalShiftMinutes) {
                $shortfall = $normalShiftMinutes - $timeWorkedDuringShift;

                // #region agent log
                $logEntry = json_encode([
                    'sessionId' => 'debug-session',
                    'runId' => 'run1',
                    'hypothesisId' => 'D',
                    'location' => 'Common.php:calculateOvertimeEntries:SCENARIO4_ADJUST',
                    'message' => 'Late check-in - adjusting overtime',
                    'data' => [
                        'timeWorkedDuringShift' => $timeWorkedDuringShift,
                        'normalShiftMinutes' => $normalShiftMinutes,
                        'shortfall' => $shortfall,
                        'overtimeAfterEnd' => $overtimeAfterEnd
                    ],
                    'timestamp' => round(microtime(true) * 1000)
                ]) . "\n";
                @file_put_contents($logFile, $logEntry, FILE_APPEND);
                // #endregion

                // Adjust after_shift overtime entries
                foreach ($overtimeEntries as &$entry) {
                    if ($entry['overtime_type'] === 'after_shift') {
                        $entryMinutes = self::timeFormatToMinutes($entry['total_time']);
                        if ($entryMinutes > $shortfall) {
                            $entry['total_time'] = self::minutesToTimeFormat($entryMinutes - $shortfall);
                        } else {
                            // Remove this entry as it's just making up for late check-in
                            $entry = null;
                        }
                    }
                }
                $overtimeEntries = array_filter($overtimeEntries);
                $overtimeEntries = array_values($overtimeEntries);
            }
        }

        // Mark as split if both before and after shift overtime exist
        if (count($overtimeEntries) > 1) {
            foreach ($overtimeEntries as &$entry) {
                $entry['overtime_type'] = 'split';
            }
        }

        // #region agent log
        $logEntry = json_encode([
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'E,F',
            'location' => 'Common.php:calculateOvertimeEntries:EXIT',
            'message' => 'Function exit with overtime entries',
            'data' => [
                'overtimeEntriesCount' => count($overtimeEntries),
                'overtimeEntries' => $overtimeEntries
            ],
            'timestamp' => round(microtime(true) * 1000)
        ]) . "\n";
        @file_put_contents($logFile, $logEntry, FILE_APPEND);
        // #endregion

        return $overtimeEntries;
    }

    /**
     * Convert minutes to HH:MM time format
     *
     * @param int $minutes Total minutes
     * @return string Time in HH:MM format
     */
    private static function minutesToTimeFormat($minutes)
    {
        if ($minutes < 0) {
            $minutes = 0;
        }
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('%02d:%02d', $hours, $mins);
    }

    /**
     * Convert HH:MM time format to minutes
     *
     * @param string $time Time in HH:MM format
     * @return int Total minutes
     */
    private static function timeFormatToMinutes($time)
    {
        if (empty($time) || $time == '00:00') {
            return 0;
        }
        $parts = explode(':', $time);
        return (isset($parts[0]) ? (int)$parts[0] : 0) * 60 + (isset($parts[1]) ? (int)$parts[1] : 0);
    }

    /**
     * Create overtime entries in database
     *
     * @param int $resortId Resort ID
     * @param int $empId Employee ID
     * @param int $shiftId Shift ID
     * @param int|null $rosterId Roster ID
     * @param int|null $parentAttendanceId Parent attendance ID
     * @param string $date Date (Y-m-d format)
     * @param array $overtimeEntries Array of overtime entry data
     * @return array Array of created EmployeeOvertime models
     */
    public static function createOvertimeEntries($resortId, $empId, $shiftId, $rosterId, $parentAttendanceId, $date, $overtimeEntries)
    {
        $createdEntries = [];

        foreach ($overtimeEntries as $entry) {
            $overtime = \App\Models\EmployeeOvertime::create([
                'resort_id' => $resortId,
                'Emp_id' => $empId,
                'Shift_id' => $shiftId,
                'roster_id' => $rosterId,
                'parent_attendance_id' => $parentAttendanceId,
                'date' => $date,
                'start_time' => $entry['start_time'],
                'end_time' => $entry['end_time'],
                'total_time' => $entry['total_time'],
                'status' => 'pending',
                'overtime_type' => $entry['overtime_type'] ?? null,
                'start_location' => $entry['start_location'] ?? null,
                'end_location' => $entry['end_location'] ?? null,
                'notes' => $entry['notes'] ?? null,
            ]);

            $createdEntries[] = $overtime;
        }

        return $createdEntries;
    }

}

?>
