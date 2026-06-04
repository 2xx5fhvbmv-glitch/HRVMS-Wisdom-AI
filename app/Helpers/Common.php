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
use App\Models\ApplicantInterViewDetails;
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
        $resort = Resort::find($resortid);
        if (!$resort || !$resort->logo) {
            return url(config('settings.default_picture'));
        }
        // Resolve via the same disk we upload to. Read from config so the
        // value survives `php artisan config:cache` on prod — env() returns
        // null in cached-config mode, which is exactly why live was
        // rendering /public/uploads/... instead of the Wasabi URL.
        $basePath = config('settings.brand_logo_folder');
        $driver   = config('settings.storage_driver');
        $relPath  = $basePath . '/' . $resort->logo;

        if ($driver === 'local') {
            // brand_logo_folder is stored as "public/uploads/brand_logos"
            // because Common::uploadFile() uses it as a filesystem path
            // (and the Laravel app sits in a sibling of public/). For the
            // PUBLIC URL we must strip the leading "public/" — the web
            // server's document root IS the public/ directory, so
            // /public/uploads/... 404s on any standard deployment.
            $urlPath = preg_replace('#^public/#', '', $relPath);
            $url = url($urlPath);
            $stamp = optional($resort->updated_at)->getTimestamp();
            return $stamp ? $url . '?v=' . $stamp : $url;
        }

        // Wasabi / S3 — generate a pre-signed (temporary) URL.
        //
        // Why not just build the bucket URL? The Wasabi account on prod
        // has *Public Use Of Objects* disabled — bucket-served URLs
        // return `AccessDenied: Public use of objects is not allowed by
        // this account`. A pre-signed URL is signed with the bucket
        // credentials, so the request is authenticated and Wasabi serves
        // it regardless of the public-use restriction.
        //
        // Tradeoff: signed URLs change every page render, so the browser
        // can't cache the image across navigations. For a small brand
        // logo this is fine; if it ever becomes a hot path we'd cache
        // the signed URL in memory for ~23h (within the 24h validity).
        try {
            $expiresAt = \Carbon\Carbon::now()->addDay();
            return \Storage::disk($driver)->temporaryUrl($relPath, $expiresAt);
        } catch (\Throwable $e) {
            \Log::warning('[GetResortLogo] temporaryUrl() failed, falling back to manual URL', [
                'driver'    => $driver,
                'rel_path'  => $relPath,
                'exception' => $e->getMessage(),
            ]);
        }

        // Manual URL builder — fires only when temporaryUrl() throws
        // (older flysystem-aws-s3 versions, mis-configured disk). If
        // your Wasabi account is later switched to allow public reads,
        // bucket policy applied, and credentials work — this is the URL
        // shape you want.
        $diskCfg = config('filesystems.disks.' . $driver);
        $explicitUrl = $diskCfg['url'] ?? null;
        if ($explicitUrl) {
            $url = rtrim($explicitUrl, '/') . '/' . ltrim($relPath, '/');
        } else {
            $endpoint = rtrim((string) ($diskCfg['endpoint'] ?? ''), '/');
            $bucket   = (string) ($diskCfg['bucket'] ?? '');
            $usePathStyle = (bool) ($diskCfg['use_path_style_endpoint'] ?? false);
            if ($endpoint && $bucket) {
                if ($usePathStyle) {
                    $url = $endpoint . '/' . $bucket . '/' . ltrim($relPath, '/');
                } else {
                    $parts = parse_url($endpoint);
                    $scheme = $parts['scheme'] ?? 'https';
                    $host   = $parts['host']   ?? '';
                    $url    = $scheme . '://' . $bucket . '.' . $host . '/' . ltrim($relPath, '/');
                }
            } else {
                $url = \Storage::disk($driver)->url($relPath);
            }
        }
        $stamp = optional($resort->updated_at)->getTimestamp();
        return $stamp ? $url . '?v=' . $stamp : $url;
	}

    public static function nofitication($resortid,$type,$Msgid= 0,$Budget_id=0,$other='',$sendto='',$moduleName="")
    {
        if($type==1)
        {
            $getNotifications = Common::GetNotifications($resortid,$type,$Msgid);

            $view = view('resorts.renderfiles.resortnotification',compact('getNotifications'))->render();

            // Persistent fan-out: write a per-employee row into
            // resort_notifications so the admin announcement survives page
            // reloads, appears on the bell-list page, and ships to mobile.
            // Only fires when called via the new admin pathway that passes
            // the notification id as $Msgid; callers using the legacy
            // signature (no id) keep the broadcast-only behaviour.
            if (!empty($Msgid)) {
                self::fanOutAdminNotice($resortid, (int) $Msgid);
            }
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
        // Admin-fan-out paths (e.g. type=1 from NotificationController@store)
        // pass an array of resort ids; broadcaster expects a scalar in the
        // payload. Join arrays into a CSV string instead of crashing on cast.
        $response['resortid'] = is_array($resortid)
            ? implode(',', array_map('strval', $resortid))
            : (string) $resortid;

        // Outbound push to the real-time notification service is optional —
        // if NOTIFICATION_URL isn't configured (local dev, etc.), skip it
        // rather than crashing every caller of Common::nofitication().
        $notificationUrl = env('NOTIFICATION_URL');
        if (!empty($notificationUrl)) {
            try {
                $client = new Client();
                $client->post($notificationUrl, ['json' => $response]);
            } catch (\Throwable $e) {
                \Log::warning('Notification push failed: ' . $e->getMessage());
            }
        }

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
        $defaultPicture = url(config('settings.default_picture'));

        if (!$userId) {
            return $defaultPicture;
        }

        $routePrefix = null;
        try {
            if (request()->route()) {
                $routePrefix = request()->route()->getPrefix();
            }
        } catch (\Throwable $e) {
            // Ignore when route is not available
        }
        $prefixMatch = $routePrefix === 'resort' || $routePrefix === '/resort';
        $isResortContext = (Auth::guard('resort-admin')->check() && $prefixMatch) || Auth::guard('api')->check();

        if (!$isResortContext) {
            return $defaultPicture;
        }

        $admin = ResortAdmin::with('resort')->find($userId);
        if (!$admin) {
            return $defaultPicture;
        }

        if ($type == 1) {
            if (empty($admin->signature_img)) {
                return $defaultPicture;
            }
            $aws = Self::GetApplicantAWSFile($admin->signature_img);
            if ($aws['success'] == true) {
                return $aws['NewURLshow'];
            }
            return $defaultPicture;
        }

        if (empty($admin->profile_picture)) {
            return $defaultPicture;
        }

        $aws = Self::GetApplicantAWSFile($admin->profile_picture);
        if ($aws['success'] == true) {
            return $aws['NewURLshow'];
        }

        // Fallback: treat profile_picture as public path (e.g. uploads/resortprofile/filename.jpg)
        $path = $admin->profile_picture;
        if (strpos($path, 'http') === 0) {
            return $path;
        }
        $path = ltrim($path, '/');
        if (file_exists(public_path($path))) {
            return asset($path);
        }
        if (file_exists(public_path(config('settings.ResortProfile_folder', 'uploads/resortprofile') . '/' . basename($path)))) {
            return asset(config('settings.ResortProfile_folder', 'uploads/resortprofile') . '/' . basename($path));
        }

        return $defaultPicture;
	}

    /**
     * Resolve a ResortAdmin's profile picture without enforcing resort-route
     * context. Used in admin-side views (e.g. support chat) where the admin
     * legitimately needs to see the customer's avatar across tenants.
     *
     * Same resolution logic as getResortUserPicture(): tries the AWS/Wasabi
     * disk first, then public-path fallbacks, then a default image.
     *
     * @param  int|null  $userId  ResortAdmin id
     * @return string  Absolute URL to the image
     */
    public static function getUserPictureForAdmin($userId)
    {
        $defaultPicture = url(config('settings.default_picture'));

        if (!$userId) {
            return $defaultPicture;
        }

        $admin = ResortAdmin::find($userId);
        if (!$admin || empty($admin->profile_picture)) {
            return $defaultPicture;
        }

        $aws = self::GetApplicantAWSFile($admin->profile_picture);
        if (!empty($aws['success']) && !empty($aws['NewURLshow'])) {
            return $aws['NewURLshow'];
        }

        $path = $admin->profile_picture;
        if (strpos($path, 'http') === 0) {
            return $path;
        }
        $path = ltrim($path, '/');
        if (file_exists(public_path($path))) {
            return asset($path);
        }
        $folder = config('settings.ResortProfile_folder', 'uploads/resortprofile');
        if (file_exists(public_path($folder . '/' . basename($path)))) {
            return asset($folder . '/' . basename($path));
        }

        return $defaultPicture;
    }

    public static function CheckResortPermissions($module_id,$pageid,$Permission_id)
    {
        $Resort = Auth::guard('resort-admin')->user();

        if (Auth::guard('resort-admin')->user()->type === "super" && Auth::guard('resort-admin')->check() && request()->route()->getPrefix() == config('settings.route_prefix.resort-admin')) {
            return true;
        }
        else
        {

            $employee = $Resort->GetEmployee;
            if (!$employee) {
                return [];
            }
            $department_id = $employee->Dept_id;
            $Position_id = $employee->Position_id;
            $Resort_id = $employee->resort_id;

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

                $employee = $Resort->GetEmployee;
                if (!$employee) {
                    return false;
                }
                $Position_id = $employee->Position_id;
                $Resort_id = $employee->resort_id;

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
            $img = ($resortexist->currency === 'Dollar') ? $resortexist->Doller_img : $resortexist->MVR_img;
        }
        else{
            $img =  'maldives-currency-icon-new.svg';
        }
        $logo =  URL::asset(config('settings.Resort_currency').'/'.$img);
        return $logo;
    }

    public static function GetResortCurrencySymbol()
    {
        $currency = self::GetResortCurrentCurrency();
        return ($currency === 'Dollar') ? '$' : 'MVR';
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

    public static function getInterviewRoundsForPosition($vacancyRank)
    {
        $positionRounds = config('settings.PositionInterviewRounds');
        if ($vacancyRank && isset($positionRounds[(int)$vacancyRank])) {
            return $positionRounds[(int)$vacancyRank];
        }
        return config('settings.InterViewRound');
    }

    public static function getFinalRoundRank($vacancyRank)
    {
        $rounds = self::getInterviewRoundsForPosition($vacancyRank);
        $keys = array_keys($rounds);
        return (int) end($keys);
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
                                ->whereIn('t10.status', ['Sortlisted By Wisdom AI', 'Sortlisted', 'Complete']);


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
                    // No link yet (needs job advertisement) OR has HR-shortlisted applicant OR accepted invitation without meeting link
                    $q->where(function($q2) {
                        $q2->whereNull('t8.link')->orWhere('t8.link', '');
                    })->orWhereNotNull('t10.id')
                    ->orWhere(function($q3) {
                        $q3->where('t11.Status', 'Slot Booked')
                           ->where(function($q4) {
                               $q4->whereNull('t11.MeetingLink')->orWhere('t11.MeetingLink', '');
                           });
                    });
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
                                't11.MeetingLink as InterviewMeetingLink',
                                't11.id as InterviewId',
                                't10.status as ApplicationStatus',
                                't10.As_ApprovedBy',
								'jd.jobdescription as JobDescription',
                                'vacancies.rank as vacancy_rank'

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
                // Add upcoming interviews to the todo list
                $upcomingInterviews = ApplicantInterViewDetails::join('applicant_form_data as af', 'af.id', '=', 'applicant_inter_view_details.Applicant_id')
                    ->join('vacancies as v', 'v.id', '=', 'af.Parent_v_id')
                    ->join('resort_positions as rp', 'rp.id', '=', 'v.position')
                    ->where('applicant_inter_view_details.resort_id', $resort_id)
                    ->whereIn('applicant_inter_view_details.Status', ['Pending Review', 'Invitation Sent', 'Slot Booked'])
                    ->where('applicant_inter_view_details.InterViewDate', '>=', Carbon::today()->format('Y-m-d'))
                    ->orderBy('applicant_inter_view_details.InterViewDate', 'asc')
                    ->take(5)
                    ->get([
                        'af.first_name', 'af.last_name', 'af.passport_photo', 'af.id as ApplicantID',
                        'v.id as V_id', 'rp.position_title as Position', 'v.Resort_id',
                        'applicant_inter_view_details.InterViewDate',
                        'applicant_inter_view_details.ResortInterviewtime',
                        'applicant_inter_view_details.Status as InterviewStatus',
                    ])
                    ->map(function ($item) {
                        $item->is_upcoming_interview = true;
                        $item->profileImg = URL::asset($item->passport_photo);
                        return $item;
                    });

                $Vacancies = $Vacancies->concat($upcomingInterviews);

				return $Vacancies;
        }
        elseif(in_array((int)$rank, [2, 8]))
        {
            // HOD (rank 2) or GM (rank 8) - show applicants awaiting their round action
            $resort_Location = Auth::guard('resort-admin')->user()->resort->resort_id;
            $employee = Auth::guard('resort-admin')->user()->GetEmployee;
            $userDeptId = $employee ? $employee->Dept_id : null;

            // For HOD: show applicants where HR (rank 3) round is Complete
            // For GM: show applicants where HOD (rank 2) round is Complete
            $previousRoundRank = ($rank == 2) ? 3 : 2;

            $VacanciesQuery = Vacancies::join('resort_positions as t5', 't5.id', '=', 'vacancies.position')
                ->join('resort_departments as t4', 't4.id', '=', 'vacancies.department')
                ->join('applicant_form_data as t9', 't9.Parent_v_id', '=', 'vacancies.id')
                ->join('applicant_wise_statuses as t10', function ($join) use ($previousRoundRank) {
                    $join->on('t10.Applicant_id', '=', 't9.id')
                        ->whereRaw('t10.id = (SELECT MAX(id) FROM applicant_wise_statuses WHERE Applicant_id = t9.id)')
                        ->where('t10.status', '=', 'Complete')
                        ->where('t10.As_ApprovedBy', '=', $previousRoundRank);
                })
                ->where('vacancies.status', 'Active')
                ->where('vacancies.Resort_id', $resort_id);

            // HOD only sees their department's applicants
            if ($rank == 2 && $userDeptId) {
                $VacanciesQuery->where('vacancies.department', $userDeptId);
            }

            $Vacancies = $VacanciesQuery->latest('t10.created_at')
                ->take(7)
                ->get([
                    'vacancies.id as V_id',
                    't5.position_title as Position',
                    't4.name as Department',
                    'vacancies.Resort_id',
                    't9.first_name',
                    't9.last_name',
                    't9.passport_photo',
                    't9.id as ApplicantID',
                    't10.id as ApplicantStatus_id',
                    't10.status as ApplicationStatus',
                    't10.As_ApprovedBy',
                    'vacancies.rank as vacancy_rank'
                ])
                ->map(function ($vacancy) use ($config, $resort_Location) {
                    $vacancy->rank_name = $config[$vacancy->As_ApprovedBy] ?? 'Unknown Rank';
                    if ($vacancy->passport_photo) {
                        $getFile = Common::GetApplicantAWSFile($vacancy->passport_photo);
                        $vacancy->profileImg = $getFile['NewURLshow'] ?? URL::asset($vacancy->passport_photo);
                    } else {
                        $vacancy->profileImg = null;
                    }
                    // Set fields that the blade template checks but aren't relevant for HOD/GM
                    $vacancy->InterviewLinkStatus = null;
                    $vacancy->InterviewMeetingLink = null;
                    return $vacancy;
                });

            // Add upcoming interviews for HOD/GM
            $interviewQuery = ApplicantInterViewDetails::join('applicant_form_data as af', 'af.id', '=', 'applicant_inter_view_details.Applicant_id')
                ->join('vacancies as v', 'v.id', '=', 'af.Parent_v_id')
                ->join('resort_positions as rp', 'rp.id', '=', 'v.position')
                ->where('applicant_inter_view_details.resort_id', $resort_id)
                ->whereIn('applicant_inter_view_details.Status', ['Pending Review', 'Invitation Sent', 'Slot Booked'])
                ->where('applicant_inter_view_details.InterViewDate', '>=', Carbon::today()->format('Y-m-d'));

            // HOD only sees their department interviews
            if ($rank == 2 && $userDeptId) {
                $interviewQuery->where('v.department', $userDeptId);
            }

            $upcomingInterviews = $interviewQuery->orderBy('applicant_inter_view_details.InterViewDate', 'asc')
                ->take(5)
                ->get([
                    'af.first_name', 'af.last_name', 'af.passport_photo', 'af.id as ApplicantID',
                    'v.id as V_id', 'rp.position_title as Position', 'v.Resort_id',
                    'applicant_inter_view_details.InterViewDate',
                    'applicant_inter_view_details.ResortInterviewtime',
                    'applicant_inter_view_details.Status as InterviewStatus',
                ])
                ->map(function ($item) {
                    $item->is_upcoming_interview = true;
                    $item->profileImg = URL::asset($item->passport_photo);
                    return $item;
                });

            $Vacancies = $Vacancies->concat($upcomingInterviews);

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
        // Templates in the DB are inconsistent: some use {{candidate_name}},
        // some use {{candidate name}} (with a space, copy-pasted from a doc),
        // some use {{Candidate Name}} (title case). Replace all common
        // variants for each key so the email body never leaks raw placeholders.
        //
        // Word/Google Docs copy-paste also sneaks in non-breaking spaces
        // (U+00A0). Normalise them to regular spaces *inside the template*
        // before matching, so the regex below doesn't have to think about it.
        $template = str_replace("\xC2\xA0", ' ', (string) $template);

        foreach ($data as $key => $value) {
            $val          = (string) ($value ?? '');
            $underscore   = $key;                                    // candidate_name
            $spaced       = str_replace('_', ' ', $key);             // candidate name
            $titleSpaced  = ucwords($spaced);                        // Candidate Name
            $titleSnake   = ucwords($underscore, '_');               // Candidate_Name

            foreach (array_unique([$underscore, $spaced, $titleSpaced, $titleSnake]) as $variant) {
                // Build a pattern that tolerates: any whitespace inside
                // {{ }}, multi-space gaps between the words of the
                // variant, and any case (the `i` flag). Each literal
                // space in the variant becomes `\s+` in the regex.
                $core = preg_quote($variant, '/');
                $core = preg_replace('/\\\\?\s+/', '\\\\s+', $core);
                $template = preg_replace(
                    '/\{\{\s*' . $core . '\s*\}\}/i',
                    $val,
                    $template
                );
            }
        }
        return $template;
    }

    public static function GetRosterdata($resort_id,$duty_roster_id,$Employee,$WeekstartDate, $WeekendDate, $startOfMonth,$endOfMonth,$flag)
    {

        if($flag =="weekly")
        {

            $DutyRoster = DutyRoster::join('duty_roster_entries as t2', 't2.Emp_id', '=', 'duty_rosters.Emp_id')
             ->join('shift_settings as t1','t1.id',"=","t2.Shift_id")
             ->whereBetween('t2.date',[$WeekstartDate, $WeekendDate])
             ->where('t1.resort_id','=',$resort_id)
             ->where('duty_rosters.id','=',$duty_roster_id)
            //  ->where('duty_rosters.Year','=',date('Y'))
             ->orderBy('t2.date','asc')
             ->get(['t2.Status','t2.id as Attd_id','t2.date','t2.Shift_id','t2.roster_id','duty_rosters.DayOfDate','t1.ShiftName','t2.OverTime','t1.StartTime','t1.EndTime','t2.DayWiseTotalHours'])
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
                $LeaveCategory = LeaveCategory::where('resort_id', $resort_id)->get(['leave_type']);
                // Use the EMPLOYEE id to scope the entries — the previous
                // filter `where('duty_rosters.id', '=', $duty_roster_id)`
                // broke when the calling controller's groupBy('employees.id')
                // happened to pick an old/different duty_rosters row for the
                // same employee. The date range already restricts to the
                // viewed month, so widening on Emp_id is safe.
                // Join duty_rosters via the entry's roster_id (proper FK)
                // instead of the employee id — the latter cross-multiplies
                // when an employee has multiple duty_rosters rows
                // (different periods).
                $DutyRoster = \App\Models\DutyRosterEntry::from('duty_roster_entries as t2')
                    ->leftJoin('duty_rosters', 'duty_rosters.id', '=', 't2.roster_id')
                    ->join('shift_settings as t1', 't1.id', '=', 't2.Shift_id')
                    ->whereBetween('t2.date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
                    ->where('t1.resort_id', '=', $resort_id)
                    ->when(!empty($Employee), fn($q) => $q->where('t2.Emp_id', '=', $Employee))
                    ->orderBy('t2.date', 'asc')
                    ->get([
                        't2.Status', 't2.id as Attd_id', 't2.Emp_id', 't2.date', 't2.Shift_id', 't2.roster_id', 'duty_rosters.DayOfDate',
                        't1.ShiftName', 't2.OverTime', 't1.StartTime', 't1.EndTime', 't2.DayWiseTotalHours'
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

    private static $attendanceCache = [];

    public static function GetAttandanceRegister($resort_id,$duty_roster_id,$Employee,$WeekstartDate, $WeekendDate,$startOfMonth,$endOfMonth,$flag)
    {
        // Cache key to avoid duplicate queries for same employee/flag
        $cacheKey = "{$duty_roster_id}_{$Employee}_{$flag}";
        if (isset(self::$attendanceCache[$cacheKey])) {
            return self::$attendanceCache[$cacheKey];
        }

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
            ->orderBy('t2.date', 'asc')
            ->groupBy('t2.id')
            ->get([
                't2.OTStatus', 't2.OTApproved_By', 't3.id as Child_Attd_id', 't3.InTime_Location', 't3.OutTime_Location',
                't2.CheckingOutTime', 't2.CheckingTime', 't2.Status', 't2.id as Attd_id', 't2.Emp_id', 't2.date',
                't2.Shift_id', 'duty_rosters.DayOfDate', 't1.ShiftName', 'OverTime', 't1.StartTime', 't1.EndTime',
                't2.DayWiseTotalHours', 't2.note'
            ]);

            // Pre-fetch all approved names to avoid N+1 queries
            $approverIds = $DutyRoster->pluck('OTApproved_By')->filter()->unique()->values()->toArray();
            $approvedNames = collect([]);
            if (!empty($approverIds)) {
                $approvedNames = ResortAdmin::whereIn('id', $approverIds)->get(['id', 'first_name', 'last_name'])->keyBy('id');
            }

            // Pre-fetch all leave data for this employee in date range (single query)
            $empIds = $DutyRoster->pluck('Emp_id')->unique()->values()->toArray();
            $allLeaveData = collect([]);
            if (!empty($empIds)) {
                $allLeaveData = LeaveCategory::join('employees_leaves as t1', 't1.leave_category_id', '=', 'leave_categories.id')
                    ->whereIn('t1.Emp_id', $empIds)
                    ->where('leave_categories.resort_id', $resort_id)
                    ->where(function ($query) use ($WeekstartDate, $WeekendDate) {
                        $query->whereBetween('t1.from_date', [$WeekstartDate, $WeekendDate])
                            ->orWhereBetween('t1.to_date', [$WeekstartDate, $WeekendDate])
                            ->orWhere(function ($query) use ($WeekstartDate, $WeekendDate) {
                                $query->where('t1.from_date', '<', $WeekstartDate)
                                    ->where('t1.to_date', '>', $WeekendDate);
                            });
                    })
                    ->where('t1.status', 'Approved')
                    ->get(['t1.total_days','leave_categories.leave_type','leave_categories.id as leave_cat_id','t1.from_date','t1.to_date','t1.Emp_id','t1.status'])
                    ->groupBy('Emp_id');
            }

            $DutyRoster = $DutyRoster->map(function ($roster) use($WeekstartDate, $WeekendDate,$resort_id, $approvedNames, $allLeaveData) {
                // Format times
                $checkInTimeParsed = self::safeParseTime($roster->CheckingTime);
                $roster->CheckInTime = $checkInTimeParsed ? $checkInTimeParsed->format('h:i A') : null;

                $checkOutTimeParsed = self::safeParseTime($roster->CheckingOutTime);
                $roster->CheckOutTime = $checkOutTimeParsed ? $checkOutTimeParsed->format('h:i A') : null;

                // Use pre-fetched approved name
                $approved_name = $approvedNames->get($roster->OTApproved_By);
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
                        if (strpos($overTime, ':') !== false) {
                            list($hours, $minutes) = explode(':', $overTime);
                        } else {
                            $hours = intval($overTime);
                            $minutes = 0;
                        }
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

                    // Use pre-fetched leave data
                    $Leavevcategory = $allLeaveData->get($roster->Emp_id, collect([]));
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


                $startStr = $startOfMonth->format('Y-m-d');
                $endStr = $endOfMonth->format('Y-m-d');

                $DutyRoster = DutyRoster::join('parent_attendaces as t2', 't2.Emp_id', '=', 'duty_rosters.Emp_id')
                    ->join('shift_settings as t1', 't1.id', '=', 't2.Shift_id')
                    ->leftJoin('child_attendaces as t3', 't3.Parent_attd_id', '=', 't2.id')
                    ->whereBetween('t2.date', [$startStr, $endStr])
                    ->where('t1.resort_id', '=', $resort_id)
                    ->where('duty_rosters.id', '=', $duty_roster_id)
                    ->orderBy('t2.date', 'asc')
                    ->groupBy('t2.id')
                    ->get([
                        't2.OTStatus', 't2.OTApproved_By', 't3.id as Child_Attd_id', 't3.InTime_Location', 't3.OutTime_Location',
                        't2.CheckingOutTime', 't2.CheckingTime', 't2.Status', 't2.id as Attd_id', 't2.Emp_id', 't2.date',
                        't2.Shift_id', 'duty_rosters.DayOfDate', 't1.ShiftName', 'OverTime', 't1.StartTime', 't1.EndTime',
                        't2.DayWiseTotalHours', 't2.note'
                    ]);

                // Pre-fetch all approved names to avoid N+1 queries
                $approverIds = $DutyRoster->pluck('OTApproved_By')->filter()->unique()->values()->toArray();
                $approvedNames = collect([]);
                if (!empty($approverIds)) {
                    $approvedNames = ResortAdmin::whereIn('id', $approverIds)->get(['id', 'first_name', 'last_name'])->keyBy('id');
                }

                // Pre-fetch all leave data for employees in date range (single query)
                $empIds = $DutyRoster->pluck('Emp_id')->unique()->values()->toArray();
                $allLeaveData = collect([]);
                if (!empty($empIds)) {
                    $allLeaveData = LeaveCategory::join('employees_leaves as t1', 't1.leave_category_id', '=', 'leave_categories.id')
                        ->whereIn('t1.Emp_id', $empIds)
                        ->where('leave_categories.resort_id', $resort_id)
                        ->where(function ($query) use ($startStr, $endStr) {
                            $query->whereBetween('t1.from_date', [$startStr, $endStr])
                                ->orWhereBetween('t1.to_date', [$startStr, $endStr])
                                ->orWhere(function ($query) use ($startStr, $endStr) {
                                    $query->where('t1.from_date', '<', $startStr)
                                        ->where('t1.to_date', '>', $endStr);
                                });
                        })
                        ->where('t1.status', 'Approved')
                        ->get(['t1.total_days','leave_categories.leave_type','leave_categories.id as leave_cat_id','t1.from_date','t1.to_date','t1.Emp_id','t1.status'])
                        ->groupBy('Emp_id');
                }

                $DutyRoster = $DutyRoster->map(function ($roster) use($resort_id,$startOfMonth,$endOfMonth, $approvedNames, $allLeaveData) {
                        // Format times
                        $checkInTimeParsed = self::safeParseTime($roster->CheckingTime);
                        $roster->CheckInTime = $checkInTimeParsed ? $checkInTimeParsed->format('h:i A') : null;

                        $checkOutTimeParsed = self::safeParseTime($roster->CheckingOutTime);
                        $roster->CheckOutTime = $checkOutTimeParsed ? $checkOutTimeParsed->format('h:i A') : null;

                        // Use pre-fetched approved name
                        $approved_name = $approvedNames->get($roster->OTApproved_By);
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
                                if (strpos($overTime, ':') !== false) {
                                    $parts = explode(':', $overTime);
                                    $hours = $parts[0] ?? 0;
                                    $minutes = $parts[1] ?? 0;
                                } else {
                                    $hours = intval($overTime);
                                    $minutes = 0;
                                }
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

                            // Use pre-fetched leave data
                            $Leavevcategory = $allLeaveData->get($roster->Emp_id, collect([]));
                            $transformedLeaveData = $Leavevcategory->map(function ($item) {
                                        return $item->only(['total_days', 'leave_type', 'leave_cat_id', 'from_date', 'to_date', 'Emp_id', 'status']);
                                    })->values()->toArray();
                                 $roster->LeaveData = $transformedLeaveData;
                        return $roster;
                    });


        }
        self::$attendanceCache[$cacheKey] = $DutyRoster;
        return $DutyRoster;
    }
    public static function getWeekCountInMonth($startDate = null, $endDate = null)
    {
        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
        } else {
            $start = Carbon::now()->startOfMonth();
            $end = Carbon::now()->endOfMonth();
        }

        // Count Fridays (common day-off) in the period
        $count = 0;
        $current = $start->copy();
        while ($current->lte($end)) {
            if ($current->isFriday()) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
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

    /**
     * Performance module — returns the list of employee IDs the logged-in
     * resort user is allowed to see, or NULL for unrestricted (all).
     *
     * Tiers:
     *   - Super admin / master admin / GM (rank 8) / HR (rank 3) → unrestricted (null)
     *   - Any user whose department is HR (incl. HR HOD rank 2, HR EXCOM rank 1) → unrestricted
     *   - EXCOM (rank 1) / HOD (rank 2) in non-HR dept → entire department (all employees in their Dept_id)
     *   - Manager (4) / Supervisor (5) / Line Workers (6) / others → subordinates + self
     */
    /**
     * Apply role-based visibility filtering to an Incidents query, mirroring
     * the visibility rules used by IncidentController@list. Centralised here
     * so every dashboard / listing / drill-down endpoint scopes the same
     * way and other departments can't see each other's incidents:
     *
     *  - Super admin / master admin   → unrestricted (resort_id only).
     *  - HR (rank "HR")               → unrestricted within the resort.
     *  - GM (rank "GM")               → only approved incidents (approval=1).
     *  - Everyone else                → only incidents assigned to one of
     *                                   their committees OR reported by
     *                                   someone in their own department.
     *  - No employee record           → no rows (treats as zero visibility
     *                                   rather than leaking).
     *
     * The query is mutated in place AND returned so it can be chained.
     */
    /**
     * Apply strict participant-only visibility to an incident-meeting
     * query. A meeting is visible to:
     *   - HR / GM / HR-dept HOD-EXCOM / master (privileged set)
     *   - Listed participants of the meeting (ONLY)
     * The original incident reporter does NOT get implicit access — if
     * they need to see this specific meeting they must be added as a
     * participant. This matches the project decision (2026-05-12) that
     * meetings are private to invitees.
     */
    public static function scopeMeetingsForViewer($query, string $alias = 'incidents_investigation_meetings')
    {
        $user = \Auth::guard('resort-admin')->user();
        if (!$user) return $query->whereRaw('0=1');
        if (self::hasFullDataAccess()) return $query;

        $emp = $user->GetEmployee ?? null;
        if (!$emp) return $query->whereRaw('0=1');
        $empId = (int) $emp->id;

        return $query->whereExists(function ($sub) use ($empId, $alias) {
            $sub->select(\DB::raw(1))
                ->from('incidents_investigation_meetings_participants as p')
                ->whereColumn('p.meeting_id', $alias . '.id')
                ->where('p.participant_id', $empId);
        });
    }

    public static function scopeIncidentsForViewer($query)
    {
        $user = \Auth::guard('resort-admin')->user();
        if (!$user) {
            return $query->whereRaw('0=1');
        }
        $resortId = $user->resort_id;
        $query->where('resort_id', $resortId);

        // Super / master admin bypass scoping
        if (($user->type ?? null) === 'super' || ($user->is_master_admin ?? 0)) {
            return $query;
        }

        $emp = $user->GetEmployee ?? $user->getEmployee ?? null;
        if (!$emp) {
            return $query->whereRaw('0=1');
        }

        $rankMap = config('settings.Position_Rank');
        $rank = (int) ($emp->rank ?? 0);
        $availableRank = $rankMap[$emp->rank ?? null] ?? '';

        // Per the access-control spec: GM, HR, and HR-department HOD/EXCOM
        // get full resort-wide visibility. (Earlier the GM branch limited
        // to approval=1 — that contradicted the spec which says
        // "Full access across system" for GM.)
        if ($rank === 8 || $availableRank === 'GM') {
            return $query;
        }
        if ($rank === 3 || $availableRank === 'HR') {
            return $query;
        }
        if (in_array($rank, [1, 2], true) && self::isHRDepartment($emp->Dept_id ?? null)) {
            return $query;
        }

        // Per the standing dept-scope spec: other-dept HOD/EXCOM see ONLY
        // their own department's incidents on the listing. Committee
        // membership does NOT widen the listing — that bypass was letting
        // a Security HOD assigned to a resort-wide committee see FNB /
        // Accounts incidents on the index and meetings list. Per-record
        // access (canViewIncidentInvestigation) still honours committee
        // membership so a committee member can open a case file outside
        // their own dept; only the LIST scope is tightened here.
        $departmentId = $emp->Dept_id ?? null;
        if (!$departmentId) {
            return $query->whereRaw('0=1');
        }

        return $query->whereHas('reporter', function ($subQ) use ($departmentId) {
            $subQ->where('Dept_id', $departmentId);
        });
    }

    /**
     * Stricter per-record gate for the Incident Investigation page. The
     * listing scope (scopeIncidentsForViewer) lets a reporter's-dept user
     * see incidents on the index, but the investigation page contains the
     * full case file (statements, findings, follow-ups) and should be
     * restricted to: HR / HR-equivalent / GM / committee members assigned
     * to THIS specific incident.
     */
    public static function canViewIncidentInvestigation($incident): bool
    {
        $user = \Auth::guard('resort-admin')->user();
        if (!$user || !$incident) return false;
        if (($user->type ?? null) === 'super' || ($user->is_master_admin ?? 0)) return true;

        $emp = $user->GetEmployee ?? $user->getEmployee ?? null;
        if (!$emp) return false;

        $rank = (int) ($emp->rank ?? 0);
        $rankMap = config('settings.Position_Rank');
        $availableRank = $rankMap[$emp->rank ?? null] ?? '';

        // HR (rank 3), GM (rank 8), and HR-dept HOD/EXCOM see everything.
        if ($rank === 3 || $availableRank === 'HR') return true;
        if ($rank === 8 || $availableRank === 'GM') return true;
        if (in_array($rank, [1, 2], true) && self::isHRDepartment($emp->Dept_id ?? null)) return true;

        // HOD/EXCOM of the same department that REPORTED this incident.
        // If the incident already shows up on their listing (per
        // scopeIncidentsForViewer's reporter-dept rule), they should also
        // be able to open the case file — otherwise the "View" button
        // throws 403 on rows the user can plainly see, which is confusing.
        if (in_array($rank, [1, 2], true) && !empty($emp->Dept_id)) {
            $reporterDeptId = optional($incident->reporter)->Dept_id
                ?? \App\Models\Employee::where('id', $incident->reporter_id)->value('Dept_id');
            if ($reporterDeptId && (int) $reporterDeptId === (int) $emp->Dept_id) {
                return true;
            }
        }

        // Committee members assigned to THIS incident.
        $assignedCommitteeIds = [];
        $raw = $incident->assigned_to ?? null;
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) $assignedCommitteeIds = $decoded;
        } elseif (is_array($raw)) {
            $assignedCommitteeIds = $raw;
        }
        $assignedCommitteeIds = array_map('intval', $assignedCommitteeIds);
        if (empty($assignedCommitteeIds)) return false;

        return \App\Models\IncidentCommitteeMember::where('member_id', $emp->id)
            ->whereIn('commitee_id', $assignedCommitteeIds)
            ->exists();
    }

    /**
     * Persist a super-admin notifications row (table: notifications) into
     * resort_notifications, one row per employee in each targeted resort.
     *
     *  - Honours the parent's start_date/end_date window: skips the fan-out
     *    if today is before start_date or after end_date.
     *  - Honours the parent's `sticky` flag → is_sticky column on the new
     *    rows so Common::ResortNotification can pin them to the top.
     *  - Idempotent: an existing (request_id, user_id, module='Admin Notice')
     *    row is left in place so editing the parent notification can re-broadcast
     *    safely without duplicating bell entries.
     */
    public static function fanOutAdminNotice($resortIds, int $notificationId): void
    {
        $resortIds = is_array($resortIds) ? $resortIds : [$resortIds];
        $resortIds = array_values(array_filter(array_map('intval', $resortIds)));
        if (empty($resortIds) || $notificationId <= 0) return;

        $parent = \App\Models\Notification::find($notificationId);
        if (!$parent) return;

        // Window check: skip if outside [start_date, end_date].
        try {
            $today = Carbon::today();
            $start = $parent->start_date ? Carbon::parse($parent->start_date)->startOfDay() : null;
            $end   = $parent->end_date   ? Carbon::parse($parent->end_date)->endOfDay()     : null;
            if ($start && $today->lt($start)) return;
            if ($end && $today->gt($end))     return;
        } catch (\Exception $e) {
            \Log::warning('fanOutAdminNotice date parse: ' . $e->getMessage());
        }

        $isSticky = in_array(strtolower((string) $parent->sticky), ['yes', '1', 'true'], true) ? 1 : 0;
        $title    = $parent->name;
        $message  = $parent->content;

        foreach ($resortIds as $rid) {
            $employeeIds = \App\Models\Employee::where('resort_id', $rid)
                ->where('status', 'Active')
                ->pluck('id');

            foreach ($employeeIds as $empId) {
                $exists = ResortNotification::where('request_id', $notificationId)
                    ->where('user_id', $empId)
                    ->where('module', 'Admin Notice')
                    ->exists();
                if ($exists) continue;

                try {
                    ResortNotification::create([
                        'resort_id'  => $rid,
                        'user_id'    => $empId,
                        'module'     => 'Admin Notice',
                        'type'       => $title,
                        'message'    => $message,
                        'status'     => 'unread',
                        'request_id' => $notificationId,
                        'is_sticky'  => $isSticky,
                    ]);
                } catch (\Exception $e) {
                    \Log::warning("fanOutAdminNotice emp {$empId}: " . $e->getMessage());
                }
            }
        }
    }

    public static function getPerformanceScopedEmpIds()
    {
        $user = \Auth::guard('resort-admin')->user();
        if (!$user) return [];

        // Super admin / master admin bypass scoping entirely
        if (($user->type ?? null) === 'super' || ($user->is_master_admin ?? 0)) {
            return null;
        }

        $emp = $user->GetEmployee ?? null;
        if (!$emp) return null;

        $rank = (int) $emp->rank;
        $positionTitle = optional($emp->position)->position_title;

        // GM (rank 8) sees everything
        if ($rank === 8) {
            return null;
        }

        // Position titles that always get full resort-wide visibility for Learning /
        // Performance modules — L&D leadership, HR leadership, and General Manager
        // (covered by rank 8 too, kept here in case a record has the title without
        // the rank set correctly).
        $fullAccessTitles = self::fullAccessPositionTitles();
        if (in_array($positionTitle, $fullAccessTitles, true)) {
            return null;
        }

        // Anyone working in the L&D department gets full visibility for Learning /
        // Performance modules — title is often misconfigured (e.g. "Club Floor Manager"
        // assigned to the Learning and Development dept), so the dept itself is the
        // reliable signal for L&D-team membership.
        if (self::isLDDepartment($emp->Dept_id ?? null)) {
            return null;
        }

        // HR rank (3) and HR-department HOD / EXCOM (rank 1 or 2 inside HR dept)
        // are treated as HR for the L&D / Performance modules and get full
        // resort-wide visibility — same as GM and L&D Manager.
        if ($rank === 3 || (in_array($rank, [1, 2], true) && self::isHRDepartment($emp->Dept_id ?? null))) {
            return null;
        }

        // EXCOM (1) / HOD (2) → whole department
        if (in_array($rank, [1, 2]) && $emp->Dept_id) {
            $ids = \App\Models\Employee::where('resort_id', $emp->resort_id)
                ->where('Dept_id', $emp->Dept_id)
                ->pluck('id')
                ->toArray();
            $ids[] = $emp->id;
            return array_values(array_unique($ids));
        }

        // Everyone else is scoped to their subordinates + self
        $ids = self::getSubordinates($emp->id);
        if (!is_array($ids)) $ids = [];
        $ids[] = $emp->id;
        return array_values(array_unique($ids));
    }

    /**
     * Fan-out a notification to a list of employee IDs. One resort_notifications row per
     * recipient (so the notification bell's user_id filter resolves it correctly) plus a
     * single mobile push to the whole list. Each DB write is isolated so one failure doesn't
     * block the rest. `$requestId` is the entity id (cycle id, KPI id, etc.) for deep-linking.
     */
    public static function notifyEmployees($resortId, array $empIds, $title, $message, $module = 'Performance', $requestId = null)
    {
        $empIds = array_values(array_unique(array_filter($empIds)));
        if (empty($empIds)) return;

        foreach ($empIds as $empId) {
            try {
                event(new \App\Events\ResortNotificationEvent(
                    self::nofitication($resortId, 10, $title, $message, $requestId, $empId, $module)
                ));
            } catch (\Exception $e) {
                \Log::warning("notifyEmployees emp {$empId} failed: " . $e->getMessage());
            }
        }

        try {
            // Pass skipDbInsert=true — Common::nofitication() above already wrote one
            // row per recipient; sendMobileNotification would otherwise duplicate them.
            self::sendMobileNotification($resortId, 2, null, null, $title, $message, $module, $empIds, $requestId, true);
        } catch (\Exception $e) {
            \Log::warning('notifyEmployees mobile push failed: ' . $e->getMessage());
        }
    }

    /**
     * Resolve an Emp_main_id value (stored as numeric id, base64 id, or Emp_id
     * string like "DR-22") to a numeric employee primary key, or null if not found.
     * Legacy cycle rows stored the Emp_id string instead of the numeric key, so all
     * three formats need to be handled when comparing against Auth user's id.
     */
    public static function resolveEmpMainIdToNumeric($value, $resortId = null)
    {
        if ($value === null || $value === '') return null;
        if (is_numeric($value)) return (int) $value;

        $decoded = base64_decode($value, true);
        if ($decoded !== false && is_numeric($decoded)) {
            return (int) $decoded;
        }

        $query = \App\Models\Employee::where('Emp_id', $value);
        if ($resortId) $query->where('resort_id', $resortId);
        $emp = $query->first(['id']);
        return $emp ? (int) $emp->id : null;
    }

    /**
     * Returns the employee IDs in a resort that should receive HR-side notifications:
     * everyone with rank 3 (HR), plus the HOD / EXCOM (rank 1 or 2) of the HR department.
     * Used by Performance / Learning flows to fan out submission alerts to HR.
     */
    public static function getResortHrEmployeeIds($resortId)
    {
        $hrDeptIds = \App\Models\ResortDepartment::where('resort_id', $resortId)
            ->get(['id', 'name', 'short_name', 'code'])
            ->filter(function ($d) {
                $hrAliases = ['hr', 'human resources', 'human resource'];
                return in_array(strtolower(trim($d->name ?? '')), $hrAliases, true)
                    || in_array(strtolower(trim($d->short_name ?? '')), $hrAliases, true)
                    || in_array(strtolower(trim($d->code ?? '')), $hrAliases, true);
            })
            ->pluck('id')
            ->all();

        return \App\Models\Employee::where('resort_id', $resortId)
            ->where(function ($q) use ($hrDeptIds) {
                $q->where('rank', 3);
                if (!empty($hrDeptIds)) {
                    $q->orWhere(function ($qq) use ($hrDeptIds) {
                        $qq->whereIn('Dept_id', $hrDeptIds)
                           ->whereIn('rank', [1, 2]);
                    });
                }
            })
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', 'Active')->orWhere('status', 'Probationary');
            })
            ->pluck('id')
            ->map(fn($v) => (int) $v)
            ->all();
    }

    /**
     * Position titles that grant full resort-wide visibility for Learning / Performance
     * modules. Treated equivalently to GM (rank 8) and super/master admin.
     * Update this list when product confirms additional leadership roles need
     * cross-department visibility.
     */
    public static function fullAccessPositionTitles()
    {
        return [
            // L&D leadership
            'Training Director',
            'L&D Manager',
            'Learning & Development Head',
            // HR leadership
            'Human Resources Manager',
            'Director Of Human Resources',
            // GM (also covered by rank 8 — safety net for rows with title set but rank unset)
            'General Manager',
        ];
    }

    /**
     * Returns true when the given department id refers to the Learning & Development
     * department. Anyone in this department is considered an L&D Manager-equivalent
     * for module-wide visibility purposes (regardless of their actual position title).
     */
    public static function isLDDepartment($deptId)
    {
        if (!$deptId) return false;

        $dept = \App\Models\ResortDepartment::find($deptId);
        if (!$dept) return false;

        $name = strtolower(trim($dept->name ?? ''));
        $short = strtolower(trim($dept->short_name ?? ''));
        $code  = strtolower(trim($dept->code ?? ''));

        $aliases = ['l&d', 'l & d', 'l and d', 'learning and development', 'learning & development', 'training and development'];
        return in_array($name, $aliases, true)
            || in_array($short, $aliases, true)
            || in_array($code, $aliases, true);
    }

    /**
     * Active admin-broadcast notifications the current resort-admin user
     * should see in the header banner. Filters by:
     *   - notification.status = 'active'
     *   - today between start_date and end_date (inclusive)
     *   - notification is targeted at the user's resort (notification_resort pivot)
     *   - the user has NOT dismissed it in the current session
     *
     * Dismissals are SESSION-scoped (not DB-backed): the user crosses the
     * banner and it's gone for the rest of that login, but on the next
     * login the banner returns. It only stops appearing when the
     * notification's end_date passes (or admin marks it inactive).
     */
    public static function getActiveAdminNotifications()
    {
        $user = \Auth::guard('resort-admin')->user();
        if (!$user) return collect();

        $today = \Carbon\Carbon::today()->toDateString();
        $dismissedIds = (array) session('dismissed_admin_notifications', []);

        // notifications.start_date / end_date are varchar — different
        // environments save them in different formats:
        //   - local DB had "07 May 2026" (d M Y, month name)
        //   - prod DB has  "10/05/2026" (d/m/Y, slash + numeric)
        // COALESCE tries each known format so the comparison works
        // regardless of which datepicker locale was active when the
        // admin saved the notification. NULL means STR_TO_DATE rejected
        // the format; the next one is tried.
        $startExpr = "COALESCE("
            . "STR_TO_DATE(n.start_date, '%d %M %Y'),"   // 07 May 2026
            . "STR_TO_DATE(n.start_date, '%d/%m/%Y'),"   // 10/05/2026
            . "STR_TO_DATE(n.start_date, '%Y-%m-%d'),"   // 2026-05-10
            . "STR_TO_DATE(n.start_date, '%d-%m-%Y')"    // 10-05-2026
            . ")";
        $endExpr   = str_replace('start_date', 'end_date', $startExpr);

        $query = \DB::table('notifications as n')
            ->join('notification_resort as nr', 'nr.notification_id', '=', 'n.id')
            ->where('nr.resort_id', $user->resort_id)
            ->where('n.status', 'active')
            ->whereRaw("{$startExpr} <= ?", [$today])
            ->whereRaw("{$endExpr}   >= ?", [$today]);

        if (!empty($dismissedIds)) {
            $query->whereNotIn('n.id', $dismissedIds);
        }

        return $query->orderByDesc('n.id')
            ->get(['n.id', 'n.name', 'n.content', 'n.notice_color', 'n.font_color']);
    }

    /**
     * Returns true when the given department id refers to the HR / Human Resources department.
     * Used to grant HR HOD and HR EXCOM the same full-system visibility as GM.
     */
    public static function isHRDepartment($deptId)
    {
        if (!$deptId) return false;

        $dept = \App\Models\ResortDepartment::find($deptId);
        if (!$dept) return false;

        $name = strtolower(trim($dept->name ?? ''));
        $short = strtolower(trim($dept->short_name ?? ''));
        $code  = strtolower(trim($dept->code ?? ''));

        $hrAliases = ['hr', 'human resources', 'human resource'];
        return in_array($name, $hrAliases, true)
            || in_array($short, $hrAliases, true)
            || in_array($code, $hrAliases, true);
    }

    /**
     * True when the given department is the Finance department.
     * Matches common aliases — Finance, Accounting, Accounts, Acc, Fin —
     * because resorts use different names for the same function (e.g.
     * resort_id=26 calls it "Accounting" / short "Acc").
     *
     * Used to widen Finance approver pools so the Finance dept's HOD
     * (rank=2) and EXCOM (rank=1) qualify as Finance approvers in
     * Transfer / Promotion / Approval flows — not only Director of
     * Finance + Finance Manager + rank=Finance(7).
     */
    /**
     * F&F-settled employee classifier used across the Payroll module.
     *
     * An employee is "settled for a payroll period" when a final_settlements
     * row with status='finalized' exists AND the employee's effective
     * last_working_day falls inside the period.
     *
     * Effective LWD lookup priority (matches the F&F review page):
     *   1. final_settlements.last_working_date (HR-confirmed)
     *   2. employee_resignation.last_working_day (resignation record)
     *
     * Returns one of:
     *   'settled_in_period' — LWD inside the period → include + flag + lock SC + drop from cash/bank
     *   'settled_before'    — finalized but LWD < period start → exclude entirely
     *   null                 — no finalized F&F → treat normally
     *
     * Implementation note: callers typically batch this via the bulk
     * variant getFFSettlementStateMap() below to avoid an N+1 lookup
     * against final_settlements + employee_resignation.
     */
    public static function getFFSettlementState(int $employeeId, string $periodStart, string $periodEnd): ?string
    {
        $map = self::getFFSettlementStateMap([$employeeId], $periodStart, $periodEnd);
        return $map[$employeeId] ?? null;
    }

    /**
     * Bulk variant of getFFSettlementState — single query batch over a
     * list of employees. Returns [employeeId => 'settled_in_period' |
     * 'settled_before']. Employees with no finalized F&F are absent
     * from the map.
     */
    public static function getFFSettlementStateMap(array $employeeIds, string $periodStart, string $periodEnd): array
    {
        if (empty($employeeIds)) return [];

        $rows = \DB::table('final_settlements as fs')
            ->leftJoin('employee_resignation as er', 'er.employee_id', '=', 'fs.employee_id')
            ->whereIn('fs.employee_id', $employeeIds)
            ->where('fs.status', 'finalized')
            ->select(
                'fs.employee_id',
                \DB::raw('COALESCE(fs.last_working_date, er.last_working_day) as effective_lwd')
            )
            ->get();

        $out = [];
        foreach ($rows as $r) {
            if (empty($r->effective_lwd)) continue;
            $lwd = (string) $r->effective_lwd;
            if ($lwd >= $periodStart && $lwd <= $periodEnd) {
                $out[(int) $r->employee_id] = 'settled_in_period';
            } elseif ($lwd < $periodStart) {
                $out[(int) $r->employee_id] = 'settled_before';
            }
            // LWD > periodEnd → not yet settled vs. this period → leave unset
        }
        return $out;
    }

    public static function isFinanceDepartment($deptId)
    {
        if (!$deptId) return false;

        $dept = \App\Models\ResortDepartment::find($deptId);
        if (!$dept) return false;

        $name  = strtolower(trim($dept->name ?? ''));
        $short = strtolower(trim($dept->short_name ?? ''));
        $code  = strtolower(trim($dept->code ?? ''));

        $aliases = ['finance', 'accounting', 'accounts', 'account', 'acc', 'fin'];
        $matches = function ($val) use ($aliases) {
            if ($val === '') return false;
            if (in_array($val, $aliases, true)) return true;
            // Loose contains check for things like "finance & accounting".
            foreach (['finance', 'accounting', 'accounts'] as $needle) {
                if (strpos($val, $needle) !== false) return true;
            }
            return false;
        };
        return $matches($name) || $matches($short) || $matches($code);
    }

    /**
     * True if the logged-in resort user has unrestricted access to all departments
     * (Super admin, master admin, GM, or anyone in the HR department).
     */
    public static function hasFullDataAccess()
    {
        $user = \Auth::guard('resort-admin')->user();
        if (!$user) return false;

        // Explicit admin types — always full access regardless of employee link.
        if (($user->type ?? null) === 'super' || ($user->is_master_admin ?? 0)) {
            return true;
        }

        // Beyond that, an employee record is required. Without one we can't
        // verify rank or department, so default to RESTRICTED (not permissive).
        $emp = $user->GetEmployee ?? null;
        if (!$emp) return false;

        $rank = (int) $emp->rank;

        // GM (8) and HR role (3)
        if (in_array($rank, [3, 8])) return true;

        // HR department HOD / EXCOM
        if (in_array($rank, [1, 2]) && self::isHRDepartment($emp->Dept_id ?? null)) {
            return true;
        }

        // L&D Managers (rank 4 / MGR with a learning-leadership position title)
        // need resort-wide visibility for training/attendance/schedule modules.
        $positionTitle = optional($emp->position)->position_title;
        $ldManagerTitles = ['Training Director', 'L&D Manager', 'Learning & Development Head'];
        if (in_array($positionTitle, $ldManagerTitles, true)) {
            return true;
        }

        return false;
    }

    /**
     * Strict "HR HOD only" check — used by the few admin tools that should be
     * locked down to the head of the HR department (e.g. KPI Config). Does NOT
     * include GM, HR XCOM, or generic rank-3 HR users.
     */
    public static function isHRHOD()
    {
        $user = \Auth::guard('resort-admin')->user();
        if (!$user) return false;

        $emp = $user->GetEmployee ?? null;
        if (!$emp) return false;

        return ((int) $emp->rank) === 2 && self::isHRDepartment($emp->Dept_id ?? null);
    }

    /**
     * Returns the department ids the logged-in resort user is allowed to see, or NULL
     * for unrestricted (all departments). Mirrors getPerformanceScopedEmpIds() but at
     * the department level — use it to filter department-keyed tables / dropdowns.
     */
    public static function getScopedDepartmentIds()
    {
        if (self::hasFullDataAccess()) return null;

        $user = \Auth::guard('resort-admin')->user();
        if (!$user) return [];

        $emp = $user->GetEmployee ?? null;
        if (!$emp || !$emp->Dept_id) return [];

        return [(int) $emp->Dept_id];
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

    /**
     * Prorate allocated leave days based on employee's joining date.
     * If employee joined in the current year, prorate by months worked.
     * If joined before current year, return full allocation.
     */
    public static function prorateLeaveByJoiningDate($allocatedDays, $joiningDate)
    {
        if (empty($joiningDate) || empty($allocatedDays)) {
            return $allocatedDays;
        }

        $joining = \Carbon\Carbon::parse($joiningDate);
        $currentYearStart = \Carbon\Carbon::now()->startOfYear();
        $currentYearEnd = \Carbon\Carbon::now()->endOfYear();

        // If joined before current year, full allocation
        if ($joining->lt($currentYearStart)) {
            return $allocatedDays;
        }

        // If joined after current year end (future), no allocation
        if ($joining->gt($currentYearEnd)) {
            return 0;
        }

        // Prorated: count months from joining month to December (inclusive)
        $monthsWorked = 12 - $joining->month + 1;
        $prorated = round(($allocatedDays / 12) * $monthsWorked, 1);

        return $prorated;
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
                                            ->groupBy('t2.id')
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

    /**
     * Convert amount from stored currency (USD) to display currency based on resort settings.
     * @param float $amount Amount in source currency
     * @param string $sourceCurrency 'USD' or 'MVR' — the currency the amount is stored in
     * @return float Converted amount in the resort's display currency
     */
    public static function convertToDisplayCurrency($amount, $sourceCurrency = 'USD')
    {
        // Sanitize: remove commas and cast to float
        $amount = (float) str_replace(',', '', (string) $amount);

        $resortId = auth()->guard('resort-admin')->user()->resort_id ?? null;
        if (!$resortId) return $amount;

        $settings = \App\Models\ResortSiteSettings::where('resort_id', $resortId)->first();
        if (!$settings) return $amount;

        $displayCurrency = $settings->currency; // 'MVR' or 'Dollar'

        // Per the FX-rate developer reference (May 2026): DollertoMVR
        // (canonical 15.42) is the only stored rate. The MVR→USD direction
        // MUST be derived by division — not by multiplying a stored inverse
        // — to avoid float-truncation drift. The doc's worked example is
        // exactly this case: 7710 × 0.06484 = $499.92 (wrong, what users saw)
        // vs 7710 ÷ 15.42 = $500.00 (correct).
        $dollarToMvr = (float) ($settings->DollertoMVR ?: 15.42);
        if ($dollarToMvr <= 0) $dollarToMvr = 15.42;

        if ($displayCurrency === 'MVR' && strtoupper($sourceCurrency) === 'USD') {
            return round($amount * $dollarToMvr, 2);
        } elseif ($displayCurrency !== 'MVR' && strtoupper($sourceCurrency) === 'MVR') {
            return round($amount / $dollarToMvr, 2);
        }

        return round($amount, 2);
    }

    /**
     * Inverse of convertToDisplayCurrency(): convert an amount the user typed
     * in the resort's *display* currency back into the currency it is *stored*
     * in. Use this when persisting a value from a form whose input was shown
     * in the live display currency.
     *
     * @param float  $displayAmount   Amount as entered in the display currency.
     * @param string $storageCurrency 'MVR' or 'USD' — the currency the column stores.
     * @return float Amount in the storage currency.
     */
    public static function convertToStorageCurrency($displayAmount, $storageCurrency = 'MVR')
    {
        $displayAmount = (float) str_replace(',', '', (string) $displayAmount);

        $resortId = auth()->guard('resort-admin')->user()->resort_id ?? null;
        if (!$resortId) return round($displayAmount, 2);

        $settings = \App\Models\ResortSiteSettings::where('resort_id', $resortId)->first();
        if (!$settings) return round($displayAmount, 2);

        $displayCurrency = $settings->currency; // 'MVR' or 'Dollar'

        // Same canonical-rate rule as convertToDisplayCurrency: derive MVR
        // ↔ USD from a single DollertoMVR (15.42), never from a stored
        // inverse. Without this, round-tripping a value through
        // display→storage loses cents to truncation drift.
        $dollarToMvr = (float) ($settings->DollertoMVR ?: 15.42);
        if ($dollarToMvr <= 0) $dollarToMvr = 15.42;

        // Shown in USD, column stores MVR → USD → MVR.
        if ($displayCurrency !== 'MVR' && strtoupper($storageCurrency) === 'MVR') {
            return round($displayAmount * $dollarToMvr, 2);
        }
        // Shown in MVR, column stores USD → MVR → USD.
        if ($displayCurrency === 'MVR' && strtoupper($storageCurrency) === 'USD') {
            return round($displayAmount / $dollarToMvr, 2);
        }

        // Display currency already equals storage currency — no conversion.
        return round($displayAmount, 2);
    }

    /**
     * Format amount with currency symbol and conversion.
     * Converts from the stored source currency to the resort's active display
     * currency, then prefixes the current symbol ($ / MVR).
     *
     * @param mixed $amount         Numeric amount (null/empty renders as '-').
     * @param string $sourceCurrency 'USD' or 'MVR' — the currency the amount is stored in.
     * @param int $decimals         Number of decimals to render (payroll uses 2; KPI usually 0).
     */
    public static function formatCurrency($amount, $sourceCurrency = 'USD', $decimals = 2)
    {
        if ($amount === null || $amount === '' || $amount === false) {
            return '-';
        }
        $converted = self::convertToDisplayCurrency($amount, $sourceCurrency);
        return self::GetResortCurrencySymbol() . ' ' . number_format($converted, $decimals);
    }

    /**
     * Live fallback for a single resort_budget_cost row for one month —
     * mirrors BudgetController::computeBudgetCostMonthlyValue so the
     * Liability page can replicate the view-budget "no saved override
     * means compute from template" rule.
     *
     * Inputs: $cost is a row/object from resort_budget_costs.
     * Output: USD amount this cost contributes to the given month.
     */
    public static function computeBudgetCostMonthlyValue($cost, int $month, int $year, bool $isLocal, bool $isMuslim, float $basicSalary, ?int $benefitGridLevel = null): float
    {
        $details = trim((string) ($cost->details ?? 'Both'));
        if ($details === 'Locals Only' && !$isLocal)  return 0.0;
        if ($details === 'Xpat Only'   &&  $isLocal)  return 0.0;
        if ($details === 'Muslim Only' && !$isMuslim) return 0.0;

        // Benefit-grade scope (new in 2026-06). When a template is scoped
        // to specific benefit_grid_levels (e.g. Medical Insurance Int'l
        // → "1,8" = EXCOM + GM; OT → "5,6" = SUP + Line Workers), apply
        // it only to employees whose level matches. NULL/empty means
        // "all grades" — backward-compatible with rows that pre-date
        // this column.
        $gridScope = trim((string) ($cost->benefit_grid_levels ?? ''));
        if ($gridScope !== '') {
            $allowed = array_filter(array_map(
                fn($v) => (int) trim($v),
                explode(',', $gridScope)
            ));
            if (!empty($allowed) && !in_array((int) ($benefitGridLevel ?? 0), $allowed, true)) {
                return 0.0;
            }
        }

        $amount = (float) ($cost->amount ?? 0);
        $unit   = strtoupper(trim((string) ($cost->amount_unit ?? 'USD')));
        $freq   = strtolower(trim((string) ($cost->frequency ?? 'Month')));

        // Percentage costs (e.g. Pension 7%) are a % of basic salary.
        $base = ($unit === '%') ? ($basicSalary * $amount / 100) : $amount;

        if (str_contains($freq, 'year'))     return round($base / 12, 2);
        if (str_contains($freq, 'quarter'))  return round($base / 3, 2);
        if (str_contains($freq, 'dai')) {
            $daysInMonth = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
            return round($base * $daysInMonth, 2);
        }
        if (str_contains($freq, 'one time')) {
            return $month === 1 ? round($base, 2) : 0.0;
        }
        // Default: a monthly cost.
        return round($base, 2);
    }

    /**
     * Annual total of one cost (from resort_budget_costs) for one employee —
     * combines explicit saved per-month overrides from
     * resort_employee_budget_cost_configurations with the live fallback
     * (computeBudgetCostMonthlyValue) for any month that has no saved row.
     * Mirrors how Budget → View Budget renders the cell.
     */
    public static function annualCostForEmployee($resortId, int $year, $cost, $employee): float
    {
        $isLocal  = strtolower(trim((string) ($employee->nationality ?? ''))) === 'maldivian';
        $isMuslim = strtolower(trim((string) ($employee->religion    ?? ''))) === 'muslim';
        $basicForPercent = (float) ($employee->basic_salary ?? 0);
        $benefitGridLevel = isset($employee->benefit_grid_level) ? (int) $employee->benefit_grid_level : null;

        $savedByMonth = \DB::table('resort_employee_budget_cost_configurations')
            ->where('employee_id', $employee->id)
            ->where('resort_id', $resortId)
            ->where('year', $year)
            ->where('resort_budget_cost_id', $cost->id)
            ->pluck('value', 'month');

        // Cost TEMPLATES with amount_unit='MVR' return live-fallback values
        // in MVR; per project convention we always emit USD totals so the
        // display layer can do MVR display at render time. Multiply MVR
        // template values by 1/DollertoMVR to convert to USD before
        // summing — same rule view-budget JS applies via mvrToUsdRate.
        // Saved overrides are USD already and aren't touched.
        $isMvrTemplate = strtoupper(trim((string) ($cost->amount_unit ?? 'USD'))) === 'MVR';
        $mvrToUsdRate  = 1.0;
        if ($isMvrTemplate) {
            $dollarToMvr = (float) (\DB::table('resort_site_settings')
                ->where('resort_id', $resortId)
                ->value('DollertoMVR') ?: 15.42);
            if ($dollarToMvr <= 0) $dollarToMvr = 15.42;
            $mvrToUsdRate = 1.0 / $dollarToMvr;
        }

        $total = 0.0;
        for ($m = 1; $m <= 12; $m++) {
            if (isset($savedByMonth[$m])) {
                $total += (float) $savedByMonth[$m];
            } else {
                $val = self::computeBudgetCostMonthlyValue($cost, $m, $year, $isLocal, $isMuslim, $basicForPercent, $benefitGridLevel);
                if ($isMvrTemplate) $val *= $mvrToUsdRate;
                $total += $val;
            }
        }
        return $total;
    }

    /**
     * Highest budgeted MONTHLY basic salary for a position in this resort —
     * shared by Promotion (warns when new salary exceeds budget) and Salary
     * Increment (same warning when increment + current > budget). Picks the
     * max of (proposed-if-set, else current) across every salary source for
     * that position — active employees, vacant rows, and per-month overrides.
     */
    public static function computeBudgetedSalaryForPosition($resortId, int $positionId, $position = null): float
    {
        if (!$position) {
            $position = \DB::table('resort_positions')->where('id', $positionId)->first(['id', 'dept_id']);
        }
        if (!$position) return 0.0;

        $year   = (int) now()->year;
        $deptId = (int) ($position->dept_id ?? 0);
        $candidates = [];

        // 1. Active employees in this position
        $employees = \DB::table('employees')
            ->where('resort_id', $resortId)
            ->where('Position_id', $positionId)
            ->where('status', 'Active')
            ->get(['id', 'basic_salary', 'proposed_salary']);

        foreach ($employees as $emp) {
            $effective = (float) ($emp->proposed_salary > 0 ? $emp->proposed_salary : ($emp->basic_salary ?? 0));
            if ($effective > 0) $candidates[] = $effective;

            $monthly = \DB::table('resort_employee_monthly_salaries')
                ->where('employee_id', $emp->id)
                ->where('resort_id', $resortId)
                ->where('year', $year)
                ->get(['current_salary', 'proposed_salary']);
            foreach ($monthly as $m) {
                $eff = (float) ($m->proposed_salary > 0 ? $m->proposed_salary : ($m->current_salary ?? 0));
                if ($eff > 0) $candidates[] = $eff;
            }
        }

        // 2. Vacant budget rows for this position
        $vacants = \DB::table('resort_vacant_budget_costs')
            ->where('resort_id', $resortId)
            ->where('position_id', $positionId)
            ->where('department_id', $deptId)
            ->where('year', $year)
            ->get(['vacant_index', 'basic_salary', 'current_salary']);

        foreach ($vacants as $v) {
            $effective = (float) ($v->current_salary > 0 ? $v->current_salary : ($v->basic_salary ?? 0));
            if ($effective > 0) $candidates[] = $effective;

            $monthly = \DB::table('resort_vacant_monthly_salaries')
                ->where('resort_id', $resortId)
                ->where('position_id', $positionId)
                ->where('department_id', $deptId)
                ->where('year', $year)
                ->where('vacant_index', $v->vacant_index)
                ->get(['current_salary', 'proposed_salary']);
            foreach ($monthly as $m) {
                $eff = (float) ($m->proposed_salary > 0 ? $m->proposed_salary : ($m->current_salary ?? 0));
                if ($eff > 0) $candidates[] = $eff;
            }
        }

        return $candidates ? (float) max($candidates) : 0.0;
    }

    /**
     * Total annual budget across the resort — the canonical "Total Estimated
     * Liability" figure used wherever the headline appears (Initial Liability
     * Estimation page, People Dashboard Liability Tracker card, etc.).
     *
     * Source data MUST match what HR sees on Budget → View Budget:
     *   per-month employee salaries  (override → employees.proposed > current)
     * + per-month employee costs     (saved overrides ∪ live fallback from templates)
     * + per-month vacant salaries    (override → resort_vacant_budget_costs.current > basic)
     * + per-month vacant costs       (resort_vacant_budget_cost_configurations)
     *
     * Both salary buckets prefer the Proposed value when non-zero, falling
     * back to the Current value — mirrors the view-budget render logic.
     */
    /**
     * Canonical per-employee annual budget total in USD.
     *
     * Single source of truth — called by:
     *   • BudgetController::buildLiveConsolidatedArrays   (consolidated-budget page)
     *   • BudgetController::getEmployeeMonthlyData        (view-budget AJAX endpoint)
     *   • Common::computeYearlyBudgetTotal                 (Liability page + headline)
     *
     * Returns the same number regardless of caller, so the three pages
     * can't drift. Before this helper existed, each page had its own
     * aggregator and they kept diverging every time a new cost source
     * was added (allowances, MVR templates, per-month overrides, etc.).
     *
     * Components summed (all USD per project convention):
     *   1. Salary leg — resort_employee_monthly_salaries override per month,
     *      falling back to proposed_salary > 0 then current_salary then
     *      employees.basic_salary.
     *   2. Cost-template leg — saved overrides in
     *      resort_employee_budget_cost_configurations (USD), with live
     *      fallback from resort_budget_costs through
     *      computeBudgetCostMonthlyValue. MVR-unit templates converted to
     *      USD via 1/DollertoMVR.
     *   3. Per-employee allowance leg — employees_allowance rows. MVR rows
     *      converted to USD inside the SUM. Treated as monthly (matches
     *      PayrollController::fetchTimeAttendance) → added once per month.
     *
     * $employee is expected to have: id, basic_salary, proposed_salary,
     * nationality, religion. Pass a stdClass / model row from any source.
     */
    public static function annualBudgetForEmployee($resortId, int $year, $employee): float
    {
        $empId = (int) ($employee->id ?? 0);
        if (!$empId) return 0.0;

        // Used by the allowance leg below. The cost-template leg gets its
        // own MVR→USD conversion internally via annualCostForEmployee().
        $dollarToMvr = (float) (\DB::table('resort_site_settings')
            ->where('resort_id', $resortId)
            ->value('DollertoMVR') ?: 15.42);
        if ($dollarToMvr <= 0) $dollarToMvr = 15.42;

        // -- 1. Salary leg ---------------------------------------------------
        $sharedFallback = (float) (($employee->proposed_salary ?? 0) > 0
            ? $employee->proposed_salary
            : ($employee->basic_salary ?? 0));

        $monthlySalaries = \DB::table('resort_employee_monthly_salaries')
            ->where('employee_id', $empId)
            ->where('resort_id', $resortId)
            ->where('year', $year)
            ->get(['month', 'current_salary', 'proposed_salary'])
            ->keyBy('month');

        $salaryTotal = 0.0;
        for ($m = 1; $m <= 12; $m++) {
            $row = $monthlySalaries->get($m);
            if ($row) {
                $salaryTotal += (float) (($row->proposed_salary ?? 0) > 0
                    ? $row->proposed_salary
                    : (($row->current_salary ?? 0) > 0
                        ? $row->current_salary
                        : $sharedFallback));
            } else {
                $salaryTotal += $sharedFallback;
            }
        }

        // -- 2. Cost-template leg --------------------------------------------
        $resortCosts = \DB::table('resort_budget_costs')
            ->where('resort_id', $resortId)
            ->where('status', 'active')
            ->get(['id', 'particulars', 'cost_title', 'amount', 'amount_unit', 'cost_type', 'frequency', 'details']);

        $costTotal = 0.0;
        foreach ($resortCosts as $cost) {
            $costTotal += self::annualCostForEmployee($resortId, $year, $cost, $employee);
        }

        // -- 3. Per-employee allowance leg -----------------------------------
        $allowanceMonthly = (float) \DB::table('employees_allowance')
            ->where('employee_id', $empId)
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN amount_unit = 'MVR' "
              . "THEN amount * (1.0 / {$dollarToMvr}) ELSE amount END), 0) as total"
            )
            ->value('total');
        $allowanceTotal = $allowanceMonthly * 12;

        return $salaryTotal + $costTotal + $allowanceTotal;
    }

    /**
     * Canonical per-vacant-slot annual budget total in USD.
     *
     * Vacant slots have no per-employee allowance leg (they don't exist
     * as employees yet). Salary + cost configs only.
     *
     * $vacant is expected to have: id, position_id, department_id,
     * vacant_index, basic_salary, current_salary. Typically a row from
     * resort_vacant_budget_costs.
     */
    public static function annualBudgetForVacantSlot($resortId, int $year, $vacant): float
    {
        $vacantId = (int) ($vacant->id ?? 0);
        $positionId   = (int) ($vacant->position_id ?? 0);
        $departmentId = (int) ($vacant->department_id ?? 0);
        $vacantIndex  = (int) ($vacant->vacant_index ?? 0);
        if (!$vacantId || !$positionId || !$departmentId) return 0.0;

        // -- 1. Salary leg ---------------------------------------------------
        // Per legacy ResortVacantBudgetCost mapping:
        //   basic_salary  = Current
        //   current_salary = Proposed
        $sharedFallback = (float) (($vacant->current_salary ?? 0) > 0
            ? $vacant->current_salary
            : ($vacant->basic_salary ?? 0));

        $monthlySalaries = \DB::table('resort_vacant_monthly_salaries')
            ->where('resort_id', $resortId)
            ->where('position_id', $positionId)
            ->where('department_id', $departmentId)
            ->where('vacant_index', $vacantIndex)
            ->where('year', $year)
            ->get(['month', 'current_salary', 'proposed_salary'])
            ->keyBy('month');

        $salaryTotal = 0.0;
        for ($m = 1; $m <= 12; $m++) {
            $row = $monthlySalaries->get($m);
            if ($row) {
                $salaryTotal += (float) (($row->proposed_salary ?? 0) > 0
                    ? $row->proposed_salary
                    : (($row->current_salary ?? 0) > 0
                        ? $row->current_salary
                        : $sharedFallback));
            } else {
                $salaryTotal += $sharedFallback;
            }
        }

        // -- 2. Cost-template leg --------------------------------------------
        // Vacant cost configs are SAVED rows only — there's no live-template
        // fallback because the slot has no employee context (nationality /
        // religion) for the Locals/Xpat/Muslim filter. Matches view-budget's
        // getVacantMonthlyData behaviour.
        $costTotal = (float) \DB::table('resort_vacant_budget_cost_configurations')
            ->where('resort_id', $resortId)
            ->where('year', $year)
            ->where('vacant_budget_cost_id', $vacantId)
            ->sum('value');

        return $salaryTotal + $costTotal;
    }

    public static function computeYearlyBudgetTotal($resortId, int $year): float
    {
        // -- 1. Employee salaries --------------------------------------------
        // Pull nationality + religion alongside salary fields so annualCostForEmployee()
        // below can correctly apply Locals/Xpat/Muslim filtering on the template fallback.
        $activeEmployees = \DB::table('employees')
            ->where('resort_id', $resortId)
            ->where('status', 'Active')
            ->get(['id', 'basic_salary', 'proposed_salary', 'nationality', 'religion', 'benefit_grid_level']);

        $empMonthlyOverrides = \DB::table('resort_employee_monthly_salaries')
            ->where('resort_id', $resortId)
            ->where('year', $year)
            ->get(['employee_id', 'month', 'current_salary', 'proposed_salary'])
            ->groupBy('employee_id');

        $employeeSalaryTotal = 0.0;
        foreach ($activeEmployees as $emp) {
            $sharedFallback = (float) ($emp->proposed_salary > 0
                ? $emp->proposed_salary
                : ($emp->basic_salary ?? 0));

            $monthsByMonth = $empMonthlyOverrides->get($emp->id, collect())->keyBy('month');
            for ($m = 1; $m <= 12; $m++) {
                $monthly = $monthsByMonth->get($m);
                if ($monthly) {
                    $effective = (float) ($monthly->proposed_salary > 0
                        ? $monthly->proposed_salary
                        : ($monthly->current_salary > 0
                            ? $monthly->current_salary
                            : $sharedFallback));
                } else {
                    $effective = $sharedFallback;
                }
                $employeeSalaryTotal += $effective;
            }
        }

        // -- 2. Employee per-month cost configurations -----------------------
        // Sum saved overrides + live fallback (per template definition) the
        // same way view-budget renders each cell. The simple
        // sum('value') previously missed every employee × cost × month with
        // no explicit override, so the headline was massively understated.
        $resortCosts = \DB::table('resort_budget_costs')
            ->where('resort_id', $resortId)
            ->where('status', 'active')
            ->get(['id', 'particulars', 'cost_title', 'amount', 'amount_unit', 'cost_type', 'frequency', 'details']);

        $employeeCostTotal = 0.0;
        foreach ($activeEmployees as $emp) {
            foreach ($resortCosts as $cost) {
                $employeeCostTotal += self::annualCostForEmployee($resortId, $year, $cost, $emp);
            }
        }

        // -- 3. Vacant salaries ----------------------------------------------
        $vacants = \DB::table('resort_vacant_budget_costs')
            ->where('resort_id', $resortId)
            ->where('year', $year)
            ->get(['id', 'position_id', 'department_id', 'vacant_index', 'basic_salary', 'current_salary']);

        // Stale-vacant filter REMOVED at user's request to align Liability
        // headline with the Consolidated Budget total. The two pages now
        // share the same semantic: "full budgeted commitment for the
        // year" — every resort_vacant_budget_costs row HR ever created
        // counts toward the total. Stale rows (slot filled after the
        // vacant row was created) inflate the headline by their salary +
        // cost configs, which matches what consolidated already shows.
        //
        // If a user reports a vacant they expected to be dropped, the
        // resolution is to delete that orphaned resort_vacant_budget_costs
        // row at source rather than filtering it out of aggregators. The
        // consolidated view exposes the per-row breakdown so HR can drill
        // in and clean up.

        $vacantMonthlyOverrides = \DB::table('resort_vacant_monthly_salaries')
            ->where('resort_id', $resortId)
            ->where('year', $year)
            ->get(['position_id', 'department_id', 'vacant_index', 'month', 'current_salary', 'proposed_salary'])
            ->groupBy(fn($r) => $r->position_id . '|' . $r->department_id . '|' . $r->vacant_index);

        $vacantSalaryTotal = 0.0;
        foreach ($vacants as $v) {
            // Per legacy ResortVacantBudgetCost mapping: basic_salary = Current,
            // current_salary = Proposed.
            $sharedFallback = (float) ($v->current_salary > 0
                ? $v->current_salary
                : ($v->basic_salary ?? 0));

            $key = $v->position_id . '|' . $v->department_id . '|' . $v->vacant_index;
            $monthsByMonth = $vacantMonthlyOverrides->get($key, collect())->keyBy('month');
            for ($m = 1; $m <= 12; $m++) {
                $monthly = $monthsByMonth->get($m);
                if ($monthly) {
                    $effective = (float) ($monthly->proposed_salary > 0
                        ? $monthly->proposed_salary
                        : ($monthly->current_salary > 0
                            ? $monthly->current_salary
                            : $sharedFallback));
                } else {
                    $effective = $sharedFallback;
                }
                $vacantSalaryTotal += $effective;
            }
        }

        // -- 4. Vacant per-month cost configurations -------------------------
        // Same stale-filter as above — only sum configs whose parent
        // vacant row survived the headcount check.
        $survivingVacantIds = $vacants->pluck('id')->all();
        $vacantCostTotal = empty($survivingVacantIds) ? 0.0 : (float) \DB::table('resort_vacant_budget_cost_configurations')
            ->where('resort_id', $resortId)
            ->where('year', $year)
            ->whereIn('vacant_budget_cost_id', $survivingVacantIds)
            ->sum('value');

        // -- 5. Per-employee allowance leg -----------------------------------
        // Matches annualBudgetForEmployee()'s third leg. Without this, the
        // Liability headline diverges from view-budget / consolidated-budget
        // by the sum of every active employee's employees_allowance × 12.
        $dollarToMvr = (float) (\DB::table('resort_site_settings')
            ->where('resort_id', $resortId)
            ->value('DollertoMVR') ?: 15.42);
        if ($dollarToMvr <= 0) $dollarToMvr = 15.42;
        $empIds = $activeEmployees->pluck('id')->all();
        $allowanceMonthlySum = empty($empIds) ? 0.0 : (float) \DB::table('employees_allowance')
            ->whereIn('employee_id', $empIds)
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN amount_unit = 'MVR' "
              . "THEN amount * (1.0 / {$dollarToMvr}) ELSE amount END), 0) as total"
            )
            ->value('total');
        $allowanceTotal = $allowanceMonthlySum * 12;

        return $employeeSalaryTotal + $employeeCostTotal + $vacantSalaryTotal + $vacantCostTotal + $allowanceTotal;
    }

    public static function getDisplayCurrency()
    {
        $resortId = auth()->guard('resort-admin')->user()->resort_id ?? null;
        if (!$resortId) return 'USD';
        $settings = \App\Models\ResortSiteSettings::where('resort_id', $resortId)->first();
        return ($settings && $settings->currency === 'MVR') ? 'MVR' : 'USD';
    }

    public static function getUsdToMvrRate()
    {
        $resortId = auth()->guard('resort-admin')->user()->resort_id ?? null;
        if (!$resortId) return 15.42;
        $settings = \App\Models\ResortSiteSettings::where('resort_id', $resortId)->first();
        return $settings ? (float) $settings->DollertoMVR : 15.42;
    }

    /**
     * Compute the Employee Withholding Tax (EWT) deduction for a single
     * month, given the employee's monthly gross remuneration in MVR.
     *
     * Progressive — the tax is the sum of (slice × bracket rate) for
     * each band the salary crosses, NOT a flat rate on the whole amount.
     *
     * Returns 0 if salary is below the first taxable band, or if the
     * `ewt_brackets_mvr` config is empty / malformed (defensive — better
     * to deduct nothing than to error out the payroll calc).
     *
     * Currently INFORMATIONAL only — surfaced on the Employee profile
     * as an indicative figure. Payroll does not yet deduct EWT
     * automatically; wire this into PayrollController::fetchTimeAttendance
     * once the user confirms the bracket figures against the latest MIRA
     * notice and signs off on the deduction line item rendering.
     *
     * @param float $monthlyGrossMvr Employee's monthly gross salary in MVR.
     * @return float Monthly EWT deduction in MVR.
     */
    public static function computeEwtDeduction(float $monthlyGrossMvr): float
    {
        if ($monthlyGrossMvr <= 0) return 0.0;

        $brackets = config('settings.ewt_brackets_mvr', []);
        if (empty($brackets) || !is_array($brackets)) return 0.0;

        $tax = 0.0;
        $previousCeiling = 0.0;
        foreach ($brackets as $bracket) {
            $ceiling = $bracket['upto'] ?? null; // null = open-ended top band
            $rate    = (float) ($bracket['rate'] ?? 0);

            if ($ceiling === null) {
                // Top band — anything still uncovered.
                $sliceAmount = $monthlyGrossMvr - $previousCeiling;
                if ($sliceAmount > 0) {
                    $tax += $sliceAmount * $rate;
                }
                break;
            }

            $sliceTop = min((float) $ceiling, $monthlyGrossMvr);
            $sliceAmount = $sliceTop - $previousCeiling;
            if ($sliceAmount > 0) {
                $tax += $sliceAmount * $rate;
            }
            $previousCeiling = (float) $ceiling;
            if ($monthlyGrossMvr <= $ceiling) break;
        }
        return round($tax, 2);
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
        // Notifications are always scoped to the explicit recipient (user_id).
        // Broadcasts (e.g. HR/EXCOM/GM) must be created as per-recipient rows by the sender
        // so that other departments don't see notifications addressed to someone else.
        $query = ResortNotification::join('employees as t1',"t1.id","=","resort_notifications.user_id")
        ->join('resort_admins as t2',"t2.id","=","t1.Admin_Parent_id")
        ->where("resort_notifications.resort_id", $resort_id)
        ->where('resort_notifications.status', 'unread')
        ->where("resort_notifications.user_id", $user_id);

        // Sticky admin notices pinned to the top of the bell.
        $r = $query
            ->orderByDesc('resort_notifications.is_sticky')
            ->orderByDesc('resort_notifications.created_at')
            ->take(10)
            ->get(['resort_notifications.*','t2.id as Parentid']);
        $string='';

        if($r->isNotEmpty())
        {
            foreach($r as $ak)
            {
                $notifUrl = Common::getNotificationUrl($ak);
                // ResortNotification::getCreatedAtAttribute already formats
                // the timestamp into the resort's display format (e.g.
                // "30/04/2026 20:57"). Carbon::parse can't read d/m/Y, which
                // crashed the bell dropdown — pull the raw DB value via
                // getRawOriginal so we always have a parseable timestamp.
                $rawCreatedAt = method_exists($ak, 'getRawOriginal')
                    ? $ak->getRawOriginal('created_at')
                    : $ak->getOriginal('created_at');
                try {
                    $timeAgo = $rawCreatedAt
                        ? Carbon::parse($rawCreatedAt)->diffForHumans()
                        : '';
                } catch (\Exception $e) {
                    \Log::warning('ResortNotification time parse: ' . $e->getMessage());
                    $timeAgo = '';
                }

                    $stickyClass = !empty($ak->is_sticky) ? ' notification-sticky' : '';
                    $stickyBadge = !empty($ak->is_sticky) ? '<span class="badge badge-warning ms-1">Pinned</span>' : '';
                    // Profile image intentionally removed per UX request —
                    // bell items now show only the message body + meta.
                    $string .= ' <div class="notification-box active'.$stickyClass.' class_remove_me_'.$ak->id.'">
                                    <a href="'.$notifUrl.'" class="d-flex profile-dropdown">
                                        <div class="flex-grow-1">
                                            <h5>'.$ak->type.' '.$stickyBadge.'</h5>
                                            <p>' .$ak->message.' </p>
                                            <br>
                                            <span>'.$timeAgo.'</span>
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

    public static function getNotificationUrl($notification)
    {
        $module = strtolower(trim($notification->module ?? ''));
        $requestId = $notification->request_id ?? null;

        switch ($module) {
            case 'leave':
                if ($requestId) {
                    return url('resort/leave/details/' . base64_encode($requestId));
                }
                return url('resort/leave/request');

            case 'boarding pass':
                if ($requestId) {
                    return url('resort/leaves/boarding-pass/details/' . base64_encode($requestId));
                }
                return url('resort/leaves/boarding-pass');

            case 'resignation':
                if ($requestId) {
                    return url('resort/people/resignation/details/' . base64_encode($requestId));
                }
                return url('resort/people/resignation');

            case 'people management (minimum wage)':
                return url('resort/people/compliances');

            case 'people - announcement':
                return url('resort/people/announcement');

            case 'workforce planning':
                return url('resort/workforce-planning/hr-dashboard');

            case 'payroll approval':
                if ($requestId) {
                    return url('resort/payroll/run-payroll?resume=' . $requestId . '&viewonly=1');
                }
                return url('resort/payroll/hr-dashboard');

            default:
                return url('resort/mark/notification-list');
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


                StorageHelper::disk()->put($folderPath, '');
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
            if (!StorageHelper::disk()->exists($Emp_main_folder)) {
                $s3Result = StorageHelper::disk()->put($Emp_main_folder, '');
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
                if (!StorageHelper::disk()->exists($subFolderPath)) {
                    $s3SubResult = StorageHelper::disk()->put($subFolderPath, '');

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

            // Privileged set: GM (8), HR (3), MGR (4), MD (9) anywhere; plus
            // HR-department HOD (2) and EXCOM (1). Other-dept rank-1/2 fall
            // through to the per-employee folder check below — the previous
            // list let any EXCOM (rank 1) view every file regardless of dept,
            // which contradicted the standing access-control spec, and
            // dropped HR-dept HOD entirely (rank 2 was missing).
            $rank = (int) $resort->GetEmployee->rank;
            $isHrDept = self::isHRDepartment($resort->GetEmployee->Dept_id ?? null);
            $isPrivileged = in_array($rank, [3, 4, 8, 9], true)
                || (in_array($rank, [1, 2], true) && $isHrDept);
            if ($isPrivileged)
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

    public static function sendMobileNotification($resortId,$type,$feedbackFormId,$trainingId,$title,$message,$module,$sendto,$request_id = null, $skipDbInsert = false)
    {
        // Initialised up-front so an unrecognised $type can't leave $payload
        // undefined and fatal-error at the Http::post() call below.
        $payload = [];

        // Only store in ResortNotification if type is NOT 3, AND the caller didn't
        // already insert via Common::nofitication() (which would double the DB rows).
        if ($type != 3 && !$skipDbInsert) {
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
        } else {
            // Caller pre-inserted via nofitication() — fetch the freshly-created rows
            // so the mobile push payload still has ids/status/time to send.
            $ids                        =   [];
            $statusData                 =   [];
            $time                       =   [];
            $rows = ResortNotification::where('resort_id', $resortId)
                ->where('module', $module)
                ->where('request_id', $request_id)
                ->whereIn('user_id', $sendto)
                ->latest('id')
                ->take(count($sendto))
                ->get();
            foreach ($rows as $r) {
                $ids[] = $r->id;
                $statusData[] = $r->status;
                $time[] = $r->created_at;
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
                                            'created_at'    => Carbon::parse($payload->created_at)->format('d M Y h:i A')
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

        // No payload was built for this $type — nothing to push. Bail
        // cleanly instead of POSTing an empty body to the mobile service.
        if (empty($payload)) {
            return null;
        }

        // BASE_URL is optional (unset on local/dev). Skip the outbound push
        // rather than calling Http::post() with a null base — Guzzle would
        // throw an invalid-URI exception and 500 the whole request.
        $baseURL = env('BASE_URL');
        if (empty($baseURL)) {
            return null;
        }

        try {
            $response = Http::post(rtrim($baseURL, '/') . '/mob-send-notification', $payload);
            return $response->json();
        } catch (\Throwable $e) {
            \Log::warning('sendMobileNotification push failed: ' . $e->getMessage());
            return null;
        }
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

    //         $uploadResult = StorageHelper::disk()->put($path, $encrypted, [
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
        $is_secure = $is_secure ? 1 : 0;

        $Resort = Resort::where("id", $resort_id)->first();
        if (!$Resort) return ['status' => false, 'msg' => 'Resort not found'];

        $main_folder = $Resort->resort_id;
        ini_set('memory_limit', '-1');
        $file = $FolderFiles;

        $tempPdfPath = null;
        $fullImagePath = null;

        try {
            // Backfill folder row + placeholder for employees who pre-date the file-management feature.
            $File_structure = FilemangementSystem::firstOrCreate(
                [
                    'resort_id'   => $resort_id,
                    'Folder_Name' => $FolderName,
                    'Folder_Type' => 'categorized',
                ],
                [
                    'Folder_unique_id' => substr(md5(uniqid($FolderName, true)), 0, 10),
                    'UnderON'          => 0,
                ]
            );
            if ($File_structure->wasRecentlyCreated) {
                StorageHelper::disk()->put($main_folder . '/public/categorized/' . $File_structure->Folder_unique_id . '/.gitkeep', '');
            }

            $originalName = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());
            $fileSizeMB = round($file->getSize() / 1024, 2);
            $isImage = in_array($extension, ['jpg', 'jpeg', 'png']);

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
                if ($fileContent === false) {
                    return ['status' => false, 'msg' => 'Failed to read file'];
                }
            }

            $uniqueString = substr(md5(uniqid($originalName, true)), 0, 10);
            $finalExtension = $extension;
            $uploadContent = $fileContent;

            if ($is_secure == 1) {
                $key = hash('sha256', env('ENCRYPTION_KEY'), true);
                $iv = random_bytes(16);
                $encrypted = $iv . openssl_encrypt(
                    $fileContent,
                    'aes-256-cbc',
                    $key,
                    OPENSSL_RAW_DATA,
                    $iv
                );

                if ($encrypted === false) {
                    return ['status' => false, 'msg' => 'Encryption failed'];
                }

                $uploadContent = $encrypted;
                $finalExtension .= '.enc';
            }

            $newFileName = $uniqueString . '.' . $finalExtension;

            if ($SubFolder != null && $SubFolder != '') {
                $parentPath = FilemangementSystem::where('resort_id', $resort_id)
                    ->where('UnderON', $File_structure->id)
                    ->where('Folder_Name', $SubFolder)
                    ->first();

                if (!$parentPath) return ['status' => false, 'msg' => 'Parent folder missing'];

                $path = $main_folder . '/public/' . $File_structure->Folder_Type . '/' . $File_structure->Folder_unique_id . '/' . $parentPath->Folder_unique_id . '/' . $newFileName;
                $NewFolder_id = $parentPath->id;
                $NewFile_structure = $parentPath->id;
            } else {
                $path = $main_folder . '/public/' . $File_structure->Folder_Type . '/' . $File_structure->Folder_unique_id . '/' . $newFileName;
                $NewFolder_id = $File_structure->id;
                $NewFile_structure = $File_structure->id;
            }

            $uploadResult = StorageHelper::disk()->put($path, $uploadContent, [
                'ContentType' => 'application/octet-stream',
                'ContentDisposition' => 'inline; filename="' . $originalName . '"'
            ]);

            if (!$uploadResult) {
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

            return [
                'status' => true,
                'Chil_file_id' => $fileRecord->id,
                'path' => $path
            ];
        } catch (\Exception $e) {
            \Log::error('AWSEmployeeFileUpload failed: ' . $e->getMessage());
            return [
                'status' => false,
                'msg' => $e->getMessage(),
                'path' => ""
            ];
        } finally {
            if ($fullImagePath && file_exists($fullImagePath)) @unlink($fullImagePath);
            if ($tempPdfPath && file_exists($tempPdfPath)) @unlink($tempPdfPath);
        }
    }

    public static function GetAWSFile($id, $resort_id, $is_secure = null)
    {
        $ChildFiles = ChildFileManagement::where("id", $id)
            ->where("resort_id", $resort_id)
            ->first();

        if (!$ChildFiles || !StorageHelper::disk()->exists($ChildFiles->File_Path)) {
            return ['success' => false, 'NewURLshow' => null, 'mimeType' => null];
        }

        $filePath = $ChildFiles->File_Path;

        // Check if file should be decrypted
        if ($ChildFiles->is_secure == 1 || !empty($is_secure) && $is_secure != null) {


            $key = hash('sha256', env('ENCRYPTION_KEY'), true);
            $encryptedData = StorageHelper::disk()->get($ChildFiles->File_Path);

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

            StorageHelper::disk()->put($tempFilePath, $decryptedData, [
                'ContentType' => $mimeType
            ]);

            $newUrl = StorageHelper::temporaryUrl($tempFilePath, 30);
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

        $temporaryUrl = StorageHelper::isCloud()
            ? StorageHelper::disk()->temporaryUrl(
                $filePath,
                now()->addMinutes(30),
                [
                    'ResponseContentDisposition' => 'inline',
                    'ResponseContentType' => $mimeType,
                ]
            )
            : StorageHelper::url($filePath);

        return [
            'success' => true,
            'NewURLshow' => $temporaryUrl,
            'mimeType' => $mimeType,
        ];
    }


    public static function GetApplicantAWSFile($path)
    {
        // Read from config (env() returns null when prod runs
        // `php artisan config:cache`, which made every upload helper
        // silently fall back to the broken 's3' default).
        $storageDriver = config('settings.storage_driver');

        // Determine which disk to use
        $diskName = 's3'; // default
        if ($storageDriver === 'local') {
            $diskName = 'local';
        } elseif ($storageDriver === 'wasabi') {
            $diskName = 'wasabi';
        }

        $disk = Storage::disk($diskName);

        if (!$disk->exists($path)) {
            // Fallback: check if file exists as a local public asset
            if (file_exists(public_path($path))) {
                return ['success' => true, 'NewURLshow' => URL::asset($path), 'mimeType' => null];
            }
            // Also check in storage/app/public path
            if (Storage::disk('local')->exists('public/' . $path)) {
                return ['success' => true, 'NewURLshow' => url('storage/' . $path), 'mimeType' => null];
            }
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
    //     if (isset($ChildFiles) && StorageHelper::disk()->exists($ChildFiles->File_Path))
    //     {
    //         $key = hash('sha256', env('ENCRYPTION_KEY'), true);

    //         $encryptedData = StorageHelper::disk()->get($ChildFiles->File_Path);

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
    //             StorageHelper::disk()->put($tempFilePath, $decryptedData, [
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
    //             $newUrl = StorageHelper::disk()->temporaryUrl($tempFilePath, now()->addMinutes(30));
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

    //         $uploadResult = StorageHelper::disk()->put($path, $encrypted, [
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
            $uploadResult = StorageHelper::disk()->put($path, file_get_contents($file), [
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
            StorageHelper::disk()->put($folderPath, '');
            DB::commit();

        return $fileManagement;
    }

    public static function FCMTokenPushNotification()
    {
        $pri_key = env('FCM_PRIVATE_KEY');
        $payload = [
            'iss' => env('FCM_SERVICE_ACCOUNT_EMAIL'),
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

     public static function sendPushNotificationForMobile($deviceTokens, $title, $body, $module, $status, $sound,$custom_sound_channel,$mass)
    {
        // Convert to array if it's a collection
        $tokens = is_array($deviceTokens) ? $deviceTokens : $deviceTokens->toArray();

        // Remove nulls and duplicates
        $tokens = array_filter($tokens);
        $tokens = array_unique($tokens);

        $token = Common::FCMTokenPushNotification();
        $url = 'https://fcm.googleapis.com/v1/projects/' . env('FCM_PROJECT_ID') . '/messages:send';
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

        $employee = $Resort->GetEmployee;
        if (!$employee) {
            return false;
        }

        $Position_id = $employee->Position_id;
        $Resort_id   = $employee->resort_id;

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


            $s3 = StorageHelper::disk();

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
            $s3 = StorageHelper::disk();

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
            \Log::error('S3 upload failed, falling back to local storage: ' . $e->getMessage());

            // Fallback to local storage
            try {
                $localBasePath = 'public/talent_acquisition/' . $main_folder . '/' . base64_encode($vacancy_id);
                $uploadedFile = $file_name;
                $newFileName = uniqid('video_', true) . '.' . $uploadedFile->getClientOriginalExtension();
                $filePath = $uploadedFile->storeAs($localBasePath, $newFileName, 'local');

                $data['status'] = true;
                $data['path'] = $filePath;
                $data['filename'] = $newFileName;

                return $data;
            } catch (\Exception $localException) {
                \Log::error('Local storage fallback also failed: ' . $localException->getMessage());
                return false;
            }
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
            $uploadedToS3 = false;

            if ($driver === 's3') {
                try {
                    $s3 = StorageHelper::disk();

                    $folderExists = $s3->exists($basePath . '/.gitkeep');
                    if (!$folderExists) {
                        $s3->put($basePath . '/.gitkeep', '');
                    }

                    $filePath = $basePath . '/' . $newFileName;
                    $s3->put($filePath, file_get_contents($uploadedFile->getRealPath()));
                    $uploadedToS3 = true;
                } catch (\Exception $e) {
                    \Log::warning('S3 upload failed, falling back to local storage: ' . $e->getMessage());
                }
            }

            if (!$uploadedToS3) {
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

        $employee = $Resort->GetEmployee;
        if (!$employee) {
            return false;
        }
        $Position_id = $employee->Position_id;
        $Resort_id   = $employee->resort_id;
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

        // Cutoff day = last day of previous period
        // Period starts on cutoff_day + 1 and ends on next month's cutoff_day
        // e.g., if cutoff=25: period = 26th prev month to 25th current month
        if ($today->day > $cutoff_day) {
            // We're past the cutoff — current period started on cutoff+1 of this month
            $cutoffStart = $today->copy()->day($cutoff_day)->addDay(); // 26th this month
            $cutoffEnd = $today->copy()->addMonthNoOverflow()->day(min($cutoff_day, $today->copy()->addMonthNoOverflow()->daysInMonth));
        } else {
            // We're before or on the cutoff — current period started on cutoff+1 of last month
            $cutoffStart = $today->copy()->subMonthNoOverflow()->day(min($cutoff_day, $today->copy()->subMonthNoOverflow()->daysInMonth))->addDay();
            $cutoffEnd = $today->copy()->day(min($cutoff_day, $today->daysInMonth));
        }

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

        // Always scope notifications to the explicit recipient — see ResortNotification()
        // for the rationale. Broadcasts must be created as per-recipient rows.
        $query = ResortNotification::where('resort_id', $resort_id)
                ->where('status', 'unread')
                ->where('user_id', $user_id);

        $resortNotificationCount = $query->count();

        if($resortNotificationCount > 100){
            return '99+';
        }else if($resortNotificationCount == 0){
            return '';
        }else{
            return $resortNotificationCount;
        }
    }


    /**
     * Check if $delegateEmpId has delegation authority for $absentEmpId right now.
     * Returns true if $absentEmpId has an Approved leave covering today
     * with task_delegation = $delegateEmpId.
     *
     * @param int $delegateEmpId  The employee claiming delegation power
     * @param int $absentEmpId    The employee who is on leave (the original approver)
     * @param int|null $resortId  Optional resort filter
     * @return bool
     */
    public static function hasDelegationAuthority($delegateEmpId, $absentEmpId, $resortId = null)
    {
        if (empty($delegateEmpId) || empty($absentEmpId) || $delegateEmpId == $absentEmpId) {
            return false;
        }

        $today = \Carbon\Carbon::today()->format('Y-m-d');

        $query = \App\Models\EmployeeLeave::where('emp_id', $absentEmpId)
            ->where('task_delegation', $delegateEmpId)
            ->where('status', 'Approved')
            ->where('from_date', '<=', $today)
            ->where('to_date', '>=', $today);

        if ($resortId) {
            $query->where('resort_id', $resortId);
        }

        return $query->exists();
    }

    /**
     * Get all employee IDs that the given delegate is currently acting on behalf of.
     * Returns array of employee IDs who are on approved leave with task_delegation = $delegateEmpId.
     *
     * @param int $delegateEmpId
     * @param int|null $resortId
     * @return array
     */
    public static function getDelegatedEmployeeIds($delegateEmpId, $resortId = null)
    {
        if (empty($delegateEmpId)) {
            return [];
        }

        $today = \Carbon\Carbon::today()->format('Y-m-d');

        $query = \App\Models\EmployeeLeave::where('task_delegation', $delegateEmpId)
            ->where('status', 'Approved')
            ->where('from_date', '<=', $today)
            ->where('to_date', '>=', $today);

        if ($resortId) {
            $query->where('resort_id', $resortId);
        }

        return $query->pluck('emp_id')->unique()->toArray();
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
            // Read from config — env() returns null when prod runs
            // `php artisan config:cache`, which made this helper silently
            // route to the 's3' default disk. That's the source of the
            // 403 InvalidAccessKeyId error on profile-picture uploads on
            // live (resort uses Wasabi for storage, but the AWS_* keys
            // were never set, so the 's3' fallback hit Amazon with bad
            // credentials).
            $storageDriver = config('settings.storage_driver');

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
                $s3 = StorageHelper::disk();
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
        // Canonical path: per-employee and per-vacant yearly_total have been
        // pre-computed by BudgetController::viewConsolidatedBudget via
        // annualBudgetForEmployee / annualBudgetForVacantSlot. Summing those
        // here keeps the consolidated badges aligned with view-budget. We
        // still fall back to the legacy aggregation for any caller that
        // doesn't set yearly_total on the rows.

        $grandTotal = 0.0;
        $missingYearlyTotal = false;

        if (!empty($positionData['employees'])) {
            foreach ($positionData['employees'] as $employee) {
                if (isset($employee->yearly_total)) {
                    $grandTotal += (float) $employee->yearly_total;
                } else {
                    $missingYearlyTotal = true;
                    break;
                }
            }
        }

        if (!$missingYearlyTotal &&
            !empty($positionData['max_counts']['max_vacantcount']) &&
            $positionData['max_counts']['max_vacantcount'] > 0) {
            for ($i = 1; $i <= $positionData['max_counts']['max_vacantcount']; $i++) {
                $vacantConfig = $positionData['vacant_configurations'][$i] ?? null;
                if ($vacantConfig) {
                    if (isset($vacantConfig['yearly_total'])) {
                        $grandTotal += (float) $vacantConfig['yearly_total'];
                    } else {
                        $missingYearlyTotal = true;
                        break;
                    }
                }
            }
        }

        if (!$missingYearlyTotal) {
            return $grandTotal;
        }

        // -- Legacy fallback (only when canonical yearly_total absent) -------
        $mvrToDollarRate = 0.065;
        $resortSettings = ResortSiteSettings::where('resort_id', $resortId)->first();
        if ($resortSettings && $resortSettings->MVRtoDoller) {
            $mvrToDollarRate = $resortSettings->MVRtoDoller;
        }

        $totalBasicSalary = 0;
        $totalCurrentSalary = 0;
        $costTotals = [];

        foreach ($resortCosts as $cost) {
            $costTotals[$cost->id] = 0;
        }

        if (!empty($positionData['employees'])) {
            foreach ($positionData['employees'] as $employee) {
                $totalBasicSalary += $employee->configured_basic_salary ?? 0;
                $totalCurrentSalary += $employee->configured_current_salary ?? 0;

                if (isset($employee->budget_configurations) && $employee->budget_configurations->isNotEmpty()) {
                    foreach ($employee->budget_configurations as $config) {
                        $valueInUSD = $config->currency === 'MVR'
                            ? $config->value * $mvrToDollarRate
                            : $config->value;
                        $costTotals[$config->resort_budget_cost_id] = ($costTotals[$config->resort_budget_cost_id] ?? 0) + $valueInUSD;
                    }
                }
            }
        }

        if (!empty($positionData['max_counts']['max_vacantcount']) && $positionData['max_counts']['max_vacantcount'] > 0) {
            for ($i = 1; $i <= $positionData['max_counts']['max_vacantcount']; $i++) {
                $vacantConfig = $positionData['vacant_configurations'][$i] ?? null;
                if ($vacantConfig) {
                    $totalBasicSalary += $vacantConfig['vacant_budget_cost']->basic_salary ?? 0;
                    $totalCurrentSalary += $vacantConfig['vacant_budget_cost']->current_salary ?? 0;

                    if (isset($vacantConfig['configurations'])) {
                        foreach ($vacantConfig['configurations'] as $config) {
                            $valueInUSD = $config->currency === 'MVR'
                                ? $config->value * $mvrToDollarRate
                                : $config->value;
                            $costTotals[$config->resort_budget_cost_id] = ($costTotals[$config->resort_budget_cost_id] ?? 0) + $valueInUSD;
                        }
                    }
                }
            }
        }

        return $totalBasicSalary + $totalCurrentSalary + array_sum($costTotals);
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

    /**
     * Resolve all template placeholders for offer letter / contract
     */
    public static function resolveTemplatePlaceholders($applicantId, $resortId)
    {
        $applicant = \App\Models\Applicant_form_data::find($applicantId);
        if (!$applicant) return [];

        $resort = \App\Models\Resort::find($resortId);

        // Vacancy + position + department
        $vacancy = \App\Models\Vacancies::join('resort_positions as rp', 'rp.id', '=', 'vacancies.position')
            ->leftJoin('resort_departments as rd', 'rd.id', '=', 'rp.dept_id')
            ->where('vacancies.id', $applicant->Parent_v_id)
            ->selectRaw('vacancies.*, rp.position_title, rp.rank as position_rank, rd.name as department_name')
            ->first();

        // Reporting manager info
        $reportingManagerName = '';
        $reportingManagerTitle = '';
        if ($vacancy && $vacancy->reporting_to) {
            $manager = \App\Models\Employee::join('resort_admins as ra', 'ra.id', '=', 'employees.Admin_Parent_id')
                ->leftJoin('resort_positions as rp2', 'rp2.id', '=', 'employees.Position_id')
                ->where('employees.id', $vacancy->reporting_to)
                ->selectRaw("CONCAT(ra.first_name, ' ', ra.last_name) as manager_name, rp2.position_title as manager_title")
                ->first();
            if ($manager) {
                $reportingManagerName = $manager->manager_name ?? '';
                $reportingManagerTitle = $manager->manager_title ?? '';
            }
        }

        // Extra fields from ta_template_extra_fields
        $extraFields = \App\Models\TaTemplateExtraField::where('resort_id', $resortId)
            ->pluck('field_value', 'field_key')
            ->toArray();

        // Resolve country ID to country name
        $candidateCountryName = '';
        if ($applicant->country) {
            $countryRecord = \DB::table('countries')->where('id', $applicant->country)->first();
            $candidateCountryName = $countryRecord->name ?? $applicant->country;
        }

        // Build candidate address
        $addressParts = array_filter([
            $applicant->address_line_one,
            $applicant->address_line_two,
            $applicant->city,
            $applicant->state,
            $applicant->pin_code,
            $candidateCountryName,
        ]);
        $candidateAddress = implode(', ', $addressParts);

        // Build company address
        $companyAddressParts = array_filter([
            $resort->address1 ?? '',
            $resort->address2 ?? '',
            $resort->city ?? '',
            $resort->state ?? '',
            $resort->zip ?? '',
            $resort->country ?? '',
        ]);
        $companyAddress = implode(', ', $companyAddressParts);

        // Fetch applicant salary allocation if available
        $salaryAllocation = \App\Models\ApplicantSalaryAllocation::where('applicant_id', $applicantId)
            ->where('resort_id', $resortId)
            ->first();

        $allocatedSalary = ($salaryAllocation && $salaryAllocation->basic_salary > 0)
            ? $salaryAllocation->basic_salary
            : ($vacancy->propsed_salary ?? $vacancy->salary ?? '');

        $allocatedCurrency = ($salaryAllocation && $salaryAllocation->currency)
            ? $salaryAllocation->currency
            : ($extraFields['currency'] ?? '');

        $allocatedAllowancesTotal = 0;
        if ($salaryAllocation && is_array($salaryAllocation->allowances)) {
            foreach ($salaryAllocation->allowances as $al) {
                $allocatedAllowancesTotal += (float)($al['value'] ?? 0);
            }
        }
        $allowancesTotal = $allocatedAllowancesTotal > 0 ? $allocatedAllowancesTotal : ($vacancy->allowance ?? '');

        $grossSalary = (is_numeric($allocatedSalary) && is_numeric($allowancesTotal))
            ? ((float)$allocatedSalary + (float)$allowancesTotal)
            : ($vacancy->salary ?? '');

        $placeholders = [
            // Personal Information
            '{{candidate_full_name}}' => ucfirst($applicant->first_name) . ' ' . ucfirst($applicant->last_name),
            '{{candidate_first_name}}' => ucfirst($applicant->first_name ?? ''),
            '{{candidate_last_name}}' => ucfirst($applicant->last_name ?? ''),
            '{{candidate_gender}}' => ucfirst($applicant->gender ?? ''),
            '{{candidate_date_of_birth}}' => $applicant->dob ?? '',
            '{{candidate_nationality}}' => $candidateCountryName,
            '{{candidate_passport_number}}' => $applicant->passport_no ?? '',
            '{{candidate_national_id_number}}' => $extraFields['candidate_national_id_number'] ?? '',
            '{{candidate_address}}' => $candidateAddress,
            '{{candidate_email}}' => $applicant->email ?? '',
            '{{candidate_phone_number}}' => ($applicant->country_phone_code ?? '') . ' ' . ($applicant->mobile_number ?? ''),

            // Job Details
            '{{job_title}}' => $vacancy->position_title ?? '',
            '{{department_name}}' => $vacancy->department_name ?? '',
            '{{reporting_manager_name}}' => $reportingManagerName,
            '{{reporting_manager_title}}' => $reportingManagerTitle,
            '{{employment_type}}' => $vacancy->employee_type ?? '',
            '{{work_location_name}}' => $resort->resort_name ?? '',
            '{{work_location_address}}' => $companyAddress,
            '{{probation_period_months}}' => $extraFields['probation_period_months'] ?? '',
            '{{employment_start_date}}' => $vacancy->required_starting_date ?? '',
            '{{contract_end_date}}' => $extraFields['contract_end_date'] ?? '',

            // Company Details
            '{{company_name}}' => $resort->resort_name ?? '',
            '{{company_registration_number}}' => $extraFields['company_registration_number'] ?? '',
            '{{company_address}}' => $companyAddress,

            // Working Hours
            '{{working_hours_per_day}}' => $extraFields['working_hours_per_day'] ?? '',
            '{{working_days_per_week}}' => $extraFields['working_days_per_week'] ?? '',
            '{{weekly_off_days}}' => $extraFields['weekly_off_days'] ?? '',
            '{{overtime_rate}}' => $extraFields['overtime_rate'] ?? '',
            '{{benefit_grid_list}}' => $extraFields['benefit_grid_list'] ?? '',
            '{{termination_notice_period_days}}' => $extraFields['termination_notice_period_days'] ?? '',
            '{{termination_notice_during_probation_days}}' => $extraFields['termination_notice_during_probation_days'] ?? '',

            // Compensation
            '{{basic_salary_amount}}' => $allocatedSalary,
            '{{currency}}' => $allocatedCurrency,
            '{{salary_frequency}}' => $extraFields['salary_frequency'] ?? 'Monthly',
            '{{service_charge_eligible}}' => $extraFields['service_charge_eligible'] ?? '',
            '{{estimated_service_charge_range}}' => $extraFields['estimated_service_charge_range'] ?? '',
            '{{allowances_total}}' => $allowancesTotal,
            '{{gross_salary_amount}}' => $grossSalary,
            '{{offer_issue_date}}' => now()->format('d/m/Y'),
            '{{offer_expiry_date}}' => '',
            '{{background_verification_required}}' => $extraFields['background_verification_required'] ?? '',
            '{{medical_clearance_required}}' => $extraFields['medical_clearance_required'] ?? '',
            '{{work_permit_required}}' => $extraFields['work_permit_required'] ?? '',
            '{{documents_required_list}}' => $extraFields['documents_required_list'] ?? '',
            '{{accommodation_provided}}' => ($vacancy->accomodation ?? '') == 'yes' ? 'Yes' : ($extraFields['accommodation_provided'] ?? ''),
            '{{meals_provided}}' => ($vacancy->food ?? '') == 'yes' ? 'Yes' : ($extraFields['meals_provided'] ?? ''),
            '{{uniform_provided}}' => $extraFields['uniform_provided'] ?? '',
            '{{offer_acceptance_deadline}}' => '',
            '{{candidate_signature_placeholder}}' => '____________________________',
            '{{offer_signature_date}}' => '____________________________',
        ];

        return $placeholders;
    }

    /**
     * Letterhead & E-signature data for a resort's document/letter PDFs.
     *
     * Returns a normalised array that letter PDF templates (Transfer today;
     * Probation / Promotion later) can consume directly. Image values are
     * ABSOLUTE filesystem paths (DomPDF embeds local files reliably this way)
     * or null when not configured / missing.
     *
     * When no letterhead row exists for the resort, `configured` is false and
     * the caller should fall back to the legacy logo + typed-signature layout.
     *
     * @param  int  $resortId  resorts.id
     * @return array{
     *   configured: bool, headerImage: ?string, footerImage: ?string,
     *   signatureImage: ?string, signatoryName: ?string, signatoryTitle: ?string,
     *   addressLine1: ?string, addressLine2: ?string, contactPhone: ?string,
     *   contactEmail: ?string, website: ?string
     * }
     */
    public static function getLetterheadData($resortId)
    {
        $setting = \App\Models\LetterheadSetting::where('resort_id', $resortId)->first();

        if (!$setting) {
            return [
                'configured'     => false,
                'headerImage'    => null,
                'footerImage'    => null,
                'signatureImage' => null,
                'signatoryName'  => null,
                'signatoryTitle' => null,
                'addressLine1'   => null,
                'addressLine2'   => null,
                'contactPhone'   => null,
                'contactEmail'   => null,
                'website'        => null,
            ];
        }

        return [
            'configured'     => true,
            'headerImage'    => $setting->imageAbsolutePath('header_image'),
            'footerImage'    => $setting->imageAbsolutePath('footer_image'),
            'signatureImage' => $setting->imageAbsolutePath('signature_image'),
            'signatoryName'  => $setting->signatory_name,
            'signatoryTitle' => $setting->signatory_title,
            'addressLine1'   => $setting->address_line1,
            'addressLine2'   => $setting->address_line2,
            'contactPhone'   => $setting->contact_phone,
            'contactEmail'   => $setting->contact_email,
            'website'        => $setting->website,
        ];
    }

}

?>
