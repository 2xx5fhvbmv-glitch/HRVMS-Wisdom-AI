<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Admin;
use App\Models\BusinessHour;
use App\Models\Resort;
use App\Models\ResortAdmin;
use App\Models\ResortPosition;
use App\Models\ResortDepartment;
// use App\Models\ResortSettings;
use App\Models\ResortRole;
use App\Models\ResortPositionModulePermission;
use App\Models\ResortPermission;
use App\Models\ResortModulePermission;
use App\Models\ResortRoleModulePermission;

use App\Models\ResortPagewisePermission;
use App\Models\ResortModule;
use App\Models\Modules;

use App\Models\Position;
use App\Helpers\Common;
use File;
use Illuminate\Support\Facades\DB;
use Config;

class ResortsController extends Controller
{
  public function index()
  {
    $data = new Resort;
    return view('admin.resorts.index',compact('data'));
  }

  public function archived()
  {
    return view('admin.resorts.archived'); // Points to the archived listing view
  }

  public function list()
  {
      $query = Resort::query(); // Initialize the base query

      // Handle column-based ordering from DataTables request
      if (request()->has('order')) {
          $columnIndex = request()->input('order.0.column');
          $columnName = request()->input("columns.$columnIndex.data");
          $direction = request()->input('order.0.dir');

          // Define columns that are allowed to be sorted
          $sortableColumns = ['resort_id', 'resort_name', 'resort_email', 'status', 'payment_status', 'created_by', 'created_at', 'updated_at'];

          if (in_array($columnName, $sortableColumns)) {
              $query->orderBy($columnName, $direction);
          } else {
              $query->orderBy('updated_at', 'DESC'); // default fallback
          }
      } else {
          $query->orderBy('updated_at', 'DESC'); // default fallback
      }

      return datatables()->of($query)
          ->addColumn('checkbox', '<input type="checkbox" name="resort_checkbox[]" class="resort_checkbox" value="{{$id}}" />')

          ->editColumn('status', function ($data) {
              $inactive_url = route('admin.resorts.block', $data->id);
              $active_url = route('admin.resorts.active', $data->id);
              $cls = Common::hasPermission(config('settings.admin_modules.resorts'), config('settings.permissions.edit')) ? 'changeStatus' : '';

              if ($data->status == "active") {
                  return '<a href="javascript:void(0)" class="active-status ' . $cls . '" data-url="' . $inactive_url . '" title="Click here to deactivate" ><span class="badge badge-success">Active</span></a>';
              } else {
                  return '<a href="javascript:void(0)" class="inactive-status ' . $cls . '" data-url="' . $active_url . '" title="Click here to activate" ><span class="badge badge-danger">Inactive</span></a>';
              }
          })

          ->editColumn('payment_status', function ($data) {
              if ($data->payment_status == "Pending") {
                  return '<a href="javascript:void(0)" class="inactive-status" title="' . $data->payment_status . '" ><span class="badge badge-danger">' . $data->payment_status . '</span></a>';
              } else {
                  return '<a href="javascript:void(0)" class="active-status" title="' . $data->payment_status . '" ><span class="badge badge-success">' . $data->payment_status . '</span></a>';
              }
          })

          ->addColumn('action', function ($tableData) {
              $edit_url = route('admin.resorts.edit', $tableData->id);
              $delete_url = route('admin.resorts.destroy', $tableData->id);
              $permission_url = route('admin.resorts.edit_permissions', $tableData->id);
              $action_links = '<div class="btn-group">';

              if (Common::hasPermission(config('settings.admin_modules.resorts'), config('settings.permissions.edit'))) {
                  $action_links .= '
                      <form class="d-inline" ID="ResortLogin">
                          ' . csrf_field() . '
                          <input type="hidden" name="resort_id" value="' . htmlspecialchars($tableData->id, ENT_QUOTES, 'UTF-8') . '">
                          <button type="submit" data-toggle="tooltip" data-title="Impersonate User" class="btn btn-success btn-sm">
                              <i class="fas fa-sign-in-alt"></i>
                          </button>
                      </form>';

                  $action_links .= '<a data-toggle="tooltip" data-title="Edit" class="btn btn-info btn-sm mx-1" href="' . $edit_url . '"><i class="fas fa-pencil-alt"></i></a>';
                  $action_links .= '<a title="Permission" class="btn btn-primary mx-1 btn-sm" href="' . $permission_url . '"><i class="fas fa-lock"></i></a>';
              }

              if (Common::hasPermission(config('settings.admin_modules.resorts'), config('settings.permissions.delete'))) {
                  $action_links .= '<a data-toggle="tooltip" data-title="Archive" class="btn btn-danger btn-sm action-delete mx-1" href="JavaScript:void(0);" data-url="' . $delete_url . '"><i class="fas fa-archive"></i></a>';
              }

              $action_links .= '</div>';
              return $action_links;
          })

          ->escapeColumns([]) // Allow HTML rendering in all columns
          ->make(true);
  }


  public function archivedlist()
  {
    $tableData = '';
    $tableData = Resort::onlyTrashed()->orderBy('deleted_at', 'desc')->get();

    return datatables()->of($tableData)
    ->addColumn('checkbox', '<input type="checkbox" name="resort_checkbox[]" class="resort_checkbox" value="{{$id}}" />')
    ->editColumn('status', function ($data) {
      $inactive_url = route('admin.resorts.block', $data->id);
      $active_url = route('admin.resorts.active', $data->id);
      $cls = Common::hasPermission(config('settings.admin_modules.resorts'),config('settings.permissions.edit')) ? 'changeStatus' : '';
      if( $data->status == "active") {
        return '<a href="javascript:void(0)" class="active-status '.$cls.'" data-url="'.$inactive_url.'" title="Click here to deactivate" ><span class="badge badge-success">Active</span></a>';
      } else {
        return '<a href="javascript:void(0)" class="inactive-status '.$cls.'" data-url="'.$active_url.'" title="Click here to activate" ><span class="badge badge-danger">Inactive</span></a>';
      }
    })
    ->editColumn('payment_status', function ($data) {
      if( $data->payment_status == "Pending") {
        return '<a href="javascript:void(0)" class="inactive-status" title="'.$data->payment_status.'" ><span class="badge badge-danger">'.$data->payment_status.'</span></a>';
      } else {
        return '<a href="javascript:void(0)" class="active-status" title="'.$data->payment_status.'" ><span class="badge badge-success">'.$data->payment_status.'</span></a>';
      }
    })
    ->addColumn('action', function ($tableData) {
      $edit_url = route('admin.resorts.edit', $tableData->id);
      $restore_url = route('admin.resorts.restore', $tableData->id);

      $action_links = '<div class="btn-group">';

      if(Common::hasPermission(config('settings.admin_modules.resorts'),config('settings.permissions.delete'))) {
        $action_links .= '<a data-toggle="tooltip" data-title="Restore" class="btn btn-success btn-sm action-restore" href="JavaScript:void(0);" data-url="'.$restore_url.'"><i class="fas fa-undo"></i></a>';
      }

      $action_links .= '</div>';

      return $action_links;
    })
    ->escapeColumns([])
    ->make(true);
  }

  public function create()
  {


    try {
      $data = new Resort();
      $resort_admin = new ResortAdmin();
      $states = Common::getStates();
      $resort_id = Common::generateUniqueCode(10, 'resorts', 'resort_id');
      $statuses = Config::get('settings.status');
      $paymentStatuses = Config::get('settings.payment_status');
      $invoiceStatuses = Config::get('settings.invoice_status');
      $servicePackages = Config::get('settings.service_packages');
      $support_channels = Config::get('settings.support_channels');
      $isNew = 1;
      // dd($resort_id);
      $Position_access = Position::where('status','Active')->whereIn('position_title',['Director Of Human Resources','Human Resources Manager'])->get();

      return view('admin.resorts.edit')->with(compact('Position_access','data','isNew','states','resort_admin','resort_id','statuses','paymentStatuses','invoiceStatuses','servicePackages','support_channels'));
    } catch( \Exception $e ) {
      \Log::emergency( "File: ".$e->getFile() );
      \Log::emergency( "Line: ".$e->getLine() );
      \Log::emergency( "Message: ".$e->getMessage() );
    }
  }

  public function store(Request $request)
  {
        
      try {
          // Validate business hours if Support SLA is 'Business Hours only'
          if ($request->Support_SLA === 'Business Hours only') {
              $businessHours = $request->input('business_hours', []);
              foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day) {
                  if (empty($businessHours[$day]['start']) || empty($businessHours[$day]['end'])) {
                      return response()->json([
                          'success' => false,
                          'msg' => "Business hours must have both start and end times for all days of the week."
                      ]);
                  }
              }
          }

          // Validate resort email for uniqueness
          $resort_email_validator = Validator::make($request->all(), [
              'resort_email' => 'email:rfc,dns|unique:resorts,resort_email'
          ]);

          if ($resort_email_validator->fails()) {
              return response()->json(['success' => false, 'msg' => 'The resort email must be a valid and unique email address.']);
          }

          // Validate resort IT email for uniqueness
          $resort_it_email_validator = Validator::make($request->all(), [
              'resort_it_email' => 'email:rfc,dns|unique:resorts,resort_it_email'
          ]);

          if ($resort_it_email_validator->fails()) {
              return response()->json(['success' => false, 'msg' => 'The resort IT email must be a valid and unique email address.']);
          }

          // Validate personal email for uniqueness
          $email_validator = Validator::make($request->all(), [
              'email' => 'email:rfc,dns|unique:resort_admins,email'
          ]);

          if ($email_validator->fails()) {
              return response()->json(['success' => false, 'msg' => 'The personal email must be a valid and unique email address.']);
          }

          // Validate invoice email for uniqueness
          $invoice_email_validator = Validator::make($request->all(), [
              'invoice_email' => 'email:rfc,dns|unique:resorts,invoice_email'
          ]);

          if ($invoice_email_validator->fails()) {
              return response()->json(['success' => false, 'msg' => 'The invoice email must be a valid and unique email address.']);
          }

          // Define file upload paths
          $path_logo = config('settings.brand_logo_folder');
          $path_profile_image = config('settings.ResortProfile_folder');

          $support_preference = implode(",", $request->support_preference);

          // Store resort details
          $resort = new Resort;
          $resort->resort_name = $request->resort_name;
          $resort->resort_prefix = $request->resort_prefix;
          $resort->resort_id = $request->resort_id;
          $resort->resort_email = $request->resort_email;
          $resort->resort_phone = $request->resort_phone;
          $resort->resort_it_email = $request->resort_it_email;
          $resort->resort_it_phone = $request->resort_it_phone;
          $resort->status = $request->resort_status;
          $resort->address1 = $request->address1;
          $resort->address2 = $request->address2;
          $resort->city = $request->resort_city;
          $resort->state = $request->resort_state;
          $resort->country = $request->resort_country;
          $resort->zip = $request->zip;
          $resort->headoffice_address1 = $request->headoffice_address1;
          $resort->headoffice_address2 = $request->headoffice_address2;
          $resort->headoffice_city = $request->headoffice_city;
          $resort->headoffice_state = $request->headoffice_state;
          $resort->headoffice_country = $request->headoffice_country;
          $resort->headoffice_pincode = $request->headoffice_zip;
          $resort->same_billing_address = $request->same_billing_address;

          if ($request->same_billing_address == 'No') {
              $resort->billing_address1 = $request->billing_address1;
              $resort->billing_address2 = $request->billing_address2;
              $resort->billing_city = $request->billing_city;
              $resort->billing_state = $request->billing_state;
              $resort->billing_country = $request->billing_country;
              $resort->billing_pincode = $request->billing_pincode;
          }

          $resort->tin = $request->tin;
          $resort->payment_method = $request->payment_method;
          $resort->invoice_email = $request->invoice_email;
          $resort->payment_status = $request->payment_status;
          $resort->invoice_status = $request->invoice_status;
          $resort->due_date = $request->due_date;
          $resort->service_package = $request->service_package;
          $resort->contract_start_date = $request->contract_start_date;
          $resort->contract_end_date = $request->contract_end_date;
          $resort->no_of_users = $request->no_of_users;
          $resort->support_preference = $support_preference;
          $resort->Support_SLA = $request->Support_SLA;
          $resort->Position_access = $request->Position_access;
          if (isset($request->logo)) {
              $fileName = "brand_logo." . $request->logo->getClientOriginalExtension();
              Common::uploadFile($request->logo, $fileName, $path_logo);
              $resort->logo = $fileName;
          }

          $saveResort = $resort->save();

          // Store the admin details
           if ($saveResort) 
           {
              $resortAdmin = new ResortAdmin;
              $resortAdmin->resort_id = $resort->id;
              $resortAdmin->first_name = $request->first_name;
              $resortAdmin->middle_name = $request->middle_name;
              $resortAdmin->last_name = $request->last_name;
              $resortAdmin->gender = $request->gender;
              $resortAdmin->email = $request->email;
              $resortAdmin->personal_phone = $request->personal_phone;
              $resortAdmin->address_line_1 = $request->address_line_1;
              $resortAdmin->address_line_2 = $request->address_line_2;
              $resortAdmin->city = $request->city;
              $resortAdmin->state = $request->state;
              $resortAdmin->country = $request->country;
              $resortAdmin->zip = $request->pincode;
              $resortAdmin->status = $request->status;
              $resortAdmin->Position_access = $request->Position_access;
              $password = Common::generateUniquePassword(8);

              $resortAdmin->role_id = "0";
              $resortAdmin->is_master_admin = 1;
              $resortAdmin->is_employee = 0;
              $resortAdmin->password = Hash::make($password);
              $resortAdmin->type = "super";

              if (isset($request->profile_picture)) {
                  $fileName = $request->profile_picture->getClientOriginalName();
                  Common::uploadFile($request->file('profile_picture'), $fileName, $path_profile_image);
                  $resortAdmin->profile_picture = $fileName;
              }

              $saveResortAdmin = $resortAdmin->save();

              if ($saveResortAdmin) {
                  $resortAdmin->sendResortRegistrationEmail($resort, $resortAdmin, $password);
              }

              // Handle business hours if Support SLA is 'Business Hours only'
            if ($request->Support_SLA === 'Business Hours only' && isset($request->business_hours)) 
            {
                foreach ($request->business_hours as $day => $hours) 
                {
                    BusinessHour::create([
                        'resort_id' => $resort->id,
                        'day_of_week' => $day,
                        'start_time' => $hours['start'] ?? null,
                        'end_time' => $hours['end'] ?? null,
                    ]);
                }
            }
          }


        //   create default folder in aws
        $folder = Common::createFolderByResort($resort->id);

          $response['success'] = true;
          $response['msg'] = __('messages.addSuccess', ['name' => 'Resort']);
          $response['redirect_url'] = route('admin.resorts.index');
          return response()->json($response);

      } catch (\Exception $e) {
          \Log::emergency("File: " . $e->getFile());
          \Log::emergency("Line: " . $e->getLine());
          \Log::emergency("Message: " . $e->getMessage());

          return response()->json(['success' => false, 'msg' => $e->getMessage()]);
      }
  }

  public function edit($id)
  {

      $resort_admin = ResortAdmin::where('resort_id', $id)->where("is_master_admin",1)->where('type', 'super')->first();

    try {
      $isNew = 0;
      $data = Resort::with('businessHours')->where('id', $id)->first();
      $resort_admin = ResortAdmin::where('resort_id', $id)->where("is_master_admin",1)->where('type', 'super')->first();
      $states = Common::getStates();
      $statuses = Config::get('settings.status');
      $paymentStatuses = Config::get('settings.payment_status');
      $invoiceStatuses = Config::get('settings.invoice_status');
      $servicePackages = Config::get('settings.service_packages');
      $support_channels = Config::get('settings.support_channels');
      $Position_access = Position::where('status','Active')->whereIn('position_title',['Director Of Human Resources','Human Resources Manager'])->get();
      $selected_channels = explode(",",$data->support_preference);
      return view('admin.resorts.edit')->with(compact('data','Position_access','isNew','states','resort_admin','statuses','paymentStatuses','invoiceStatuses','servicePackages','support_channels','selected_channels'));
    } catch( \Exception $e ) {
      \Log::emergency( "File: ".$e->getFile() );
      \Log::emergency( "Line: ".$e->getLine() );
      \Log::emergency( "Message: ".$e->getMessage() );

      $response['success'] = false;
      $response['msg'] = $e->getMessage();
      return response()->json($response);
    }
  }

  public function update(Request $request, $id)
  {    
    try {
        // Validate business hours if Support SLA is 'Business Hours only'
        if ($request->Support_SLA === 'Business Hours only') {
            $businessHours = $request->input('business_hours', []);
            foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day) {
                if (empty($businessHours[$day]['start']) || empty($businessHours[$day]['end'])) {
                    return response()->json([
                        'success' => false,
                        'msg' => "Business hours must have both start and end times for all days of the week."
                    ]);
                }
            }
        }

        // Find the existing Resort
        $resort = Resort::findOrFail($id);
        // Find the related ResortAdmin
        $resortAdmin = ResortAdmin::where('resort_id', $resort->id)->first();
        // Validate resort email for uniqueness, excluding the current resort
        $resort_email_validator = Validator::make($request->all(), [
            'resort_email' => 'email:rfc,dns|unique:resorts,resort_email,' . $resort->id
        ]);

        if ($resort_email_validator->fails()) {
            return response()->json(['success' => false, 'msg' => 'The resort email must be a valid and unique email address.']);
        }

        // Validate resort IT email for uniqueness, excluding the current resort
        $resort_it_email_validator = Validator::make($request->all(), [
            'resort_it_email' => 'email:rfc,dns|unique:resorts,resort_it_email,' . $resort->id
        ]);

        if ($resort_it_email_validator->fails()) {
            return response()->json(['success' => false, 'msg' => 'The resort IT email must be a valid and unique email address.']);
        }

        // Validate personal email for uniqueness, excluding the current resort admin
        $email_validator = Validator::make($request->all(), [
            'email' => 'email:rfc,dns|unique:resort_admins,email,' . $resortAdmin->id
        ]);

        if ($email_validator->fails()) {
            return response()->json(['success' => false, 'msg' => 'The personal email must be a valid and unique email address.']);
        }

        // Validate invoice email for uniqueness, excluding the current resort
        $invoice_email_validator = Validator::make($request->all(), [
            'invoice_email' => 'email:rfc,dns|unique:resorts,invoice_email,' . $resort->id
        ]);

        if ($invoice_email_validator->fails()) {
            return response()->json(['success' => false, 'msg' => 'The invoice email must be a valid and unique email address.']);
        }

        // Proceed with storing the file if validation passes
        $path_logo = config('settings.brand_logo_folder');
        $path_profile_image = config('settings.ResortProfile_folder');
        $support_preference = implode(",", $request->support_preference);

        // Update resort details
        $resort->resort_name = $request->resort_name;
        $resort->resort_prefix = $request->resort_prefix;
        $resort->resort_id = $request->resort_id;
        $resort->resort_email = $request->resort_email;
        $resort->resort_phone = $request->resort_phone;
        $resort->resort_it_email = $request->resort_it_email;
        $resort->resort_it_phone = $request->resort_it_phone;
        $resort->status = $request->resort_status;
        $resort->address1 = $request->address1;
        $resort->address2 = $request->address2;
        $resort->city = $request->resort_city;
        $resort->state = $request->resort_state;
        $resort->country = $request->resort_country;
        $resort->zip = $request->zip;
        $resort->headoffice_address1 = $request->headoffice_address1;
        $resort->headoffice_address2 = $request->headoffice_address2;
        $resort->headoffice_city = $request->headoffice_city;
        $resort->headoffice_state = $request->headoffice_state;
        $resort->headoffice_country = $request->headoffice_country;
        $resort->headoffice_pincode = $request->headoffice_zip;
        $resort->same_billing_address = $request->same_billing_address;

        if ($request->same_billing_address == 'No') {
            $resort->billing_address1 = $request->billing_address1;
            $resort->billing_address2 = $request->billing_address2;
            $resort->billing_city = $request->billing_city;
            $resort->billing_state = $request->billing_state;
            $resort->billing_country = $request->billing_country;
            $resort->billing_pincode = $request->billing_pincode;
        } else {
            $resort->billing_address1 = null;
            $resort->billing_address2 = null;
            $resort->billing_city = null;
            $resort->billing_state = null;
            $resort->billing_country = null;
            $resort->billing_pincode = null;
        }

        $resort->tin = $request->tin;
        $resort->payment_method = $request->payment_method;
        $resort->invoice_email = $request->invoice_email;
        $resort->payment_status = $request->payment_status;
        $resort->invoice_status = $request->invoice_status;
        $resort->due_date = $request->due_date;
        $resort->service_package = $request->service_package;
        $resort->contract_start_date = $request->contract_start_date;
        $resort->contract_end_date = $request->contract_end_date;
        $resort->no_of_users = $request->no_of_users;
        $resort->support_preference = $support_preference;
        $resort->Support_SLA = $request->Support_SLA;
        $resort->Position_access = $request->Position_access;
        if (isset($request->logo)) {
            $fileName = "brand_logo." . $request->logo->getClientOriginalExtension();
            Common::uploadFile($request->logo, $fileName, $path_logo);
            $resort->logo = $fileName;
        }

        $saveResort = $resort->save();

        // Update the admin details if the resort is saved
        if ($saveResort) {
            $resortAdmin->first_name = $request->first_name;
            $resortAdmin->middle_name = $request->middle_name;
            $resortAdmin->last_name = $request->last_name;
            $resortAdmin->gender = $request->gender;
            $resortAdmin->email = $request->email;
            $resortAdmin->personal_phone = $request->personal_phone;
            $resortAdmin->address_line_1 = $request->address_line_1;
            $resortAdmin->address_line_2 = $request->address_line_2;
            $resortAdmin->city = $request->city;
            $resortAdmin->state = $request->state;
            $resortAdmin->country = $request->country;
            $resortAdmin->zip = $request->pincode;
            $resortAdmin->status = $request->status;
            $resortAdmin->Position_access = $request->Position_access;

            if (isset($request->profile_picture)) {
                $fileName = $request->profile_picture->getClientOriginalName();
                Common::uploadFile($request->file('profile_picture'), $fileName, $path_profile_image);
                $resortAdmin->profile_picture = $fileName;
            }

            $resortAdmin->save();
        }

        // Update business hours if Support_SLA is 'Business Hours only'
        if ($request->Support_SLA === 'Business Hours only' && isset($request->business_hours)) {
          // Delete existing business hours for the resort
          BusinessHour::where('resort_id', $resort->id)->delete();

          // Insert new business hours
          foreach ($request->business_hours as $day => $hours) {
              BusinessHour::create([
                  'resort_id' => $resort->id,
                  'day_of_week' => $day,
                  'start_time' => $hours['start'] ?? null,
                  'end_time' => $hours['end'] ?? null,
              ]);
          }
      }

        $response['success'] = true;
        $response['msg'] = __('messages.updateSuccess', ['name' => 'Resort']);
        $response['redirect_url'] = route('admin.resorts.index');
        return response()->json($response);

    } catch (\Exception $e) {
        \Log::emergency("File: " . $e->getFile());
        \Log::emergency("Line: " . $e->getLine());
        \Log::emergency("Message: " . $e->getMessage());

        return response()->json(['success' => false, 'msg' => $e->getMessage()]);
    }
  }

  public function destroy($id)
  {
    try {
        $data = Resort::findOrFail($id);
        // Soft delete the resort
        $data->delete();
        $response['success'] = true;
        $response['msg'] = __('messages.archiveSuccess', ['name' => 'Resort']);
    } catch (\Exception $e) {
        \Log::emergency("File: " . $e->getFile());
        \Log::emergency("Line: " . $e->getLine());
        \Log::emergency("Message: " . $e->getMessage());
        $response['success'] = false;
        $response['msg'] = $e->getMessage();
    }
    return response()->json($response);
  }

  public function restore($id)
  {
    try {
        // Find the resort with the given ID in the trashed items
        $resort = Resort::onlyTrashed()->findOrFail($id);
        // Restore the resort
        $resort->restore();
        $response['success'] = true;
        $response['msg'] = __('messages.restoreSuccess', ['name' => 'Resort']);
    } catch (\Exception $e) {
        \Log::emergency("File: " . $e->getFile());
        \Log::emergency("Line: " . $e->getLine());
        \Log::emergency("Message: " . $e->getMessage());
        $response['success'] = false;
        $response['msg'] = __('messages.restoreFailed', ['name' => 'Resort']);
    }
    return response()->json($response);
  }

  public function massremove(Request $request)
  {
    try {
      $ids = $request->input('id');
      $data = Resort::whereIn('id', $ids)->get();
      $selectedTotal = count($ids);
      $ignored = 0;
      foreach ($data as $key => $value) {
        $value->delete();
      }
      if($ignored == 0) {
        $response['success'] = true;
        $response['msg'] = __('messages.deleteSuccess', ['name' => 'Resort']);
      }
      return response()->json($response);
    } catch( \Exception $e ) {
      \Log::emergency( "File: ".$e->getFile() );
      \Log::emergency( "Line: ".$e->getLine() );
      \Log::emergency( "Message: ".$e->getMessage() );

      $response['success'] = false;
      $response['msg'] = $e->getMessage();
      return response()->json($response);
    }
  }

  public function block($id)
  {
    try {
      $data = Resort::where('id',$id)->first();
      $data->status ='inactive';
      $data->save();
      $response['success'] = true;
      return response()->json($response);
    } catch( \Exception $e ) {
      \Log::emergency( "File: ".$e->getFile() );
      \Log::emergency( "Line: ".$e->getLine() );
      \Log::emergency( "Message: ".$e->getMessage() );

      $response['success'] = true;
      return response()->json($response);
    }
  }

  public function active($id)
  {
    try {
      $data = Resort::where('id',$id)->first();
      $data->status ='active';
      $data->save();

      $response['success'] = true;
      return response()->json($response);
    } catch( \Exception $e ) {
      \Log::emergency( "File: ".$e->getFile() );
      \Log::emergency( "Line: ".$e->getLine() );
      \Log::emergency( "Message: ".$e->getMessage() );

      $response['success'] = false;
      $response['msg'] = $e->getMessage();
      return response()->json($response);
    }
  }

  public function editPermissions($id)
  {
    try {
        $page_title = "Permissions";
        $page_header = '<span class="arca-font">Assign</span> Permissions';
        $resort_id = $id;
        $Resortmodules = Modules::where('status','Active')->orderBy("id","asc")->get();
        $existingPermissions = ResortPagewisePermission::where('resort_id', $resort_id)->orderBy("id","asc")->get(['Module_id','page_permission_id'])->toArray();
        $ModuleWisePermission=array();
        foreach($existingPermissions as $per)
        {
            $ModuleWisePermission[$per['Module_id']][]=$per['page_permission_id'];
        }


        return view('admin.resorts.edit-permissions', compact('resort_id','Resortmodules','ModuleWisePermission'));
    } catch (\Exception $e) {
        \Log::emergency("File: ".$e->getFile());
        \Log::emergency("Line: ".$e->getLine());
        \Log::emergency("Message: ".$e->getMessage());
        abort(404);
    }
  }

  public function getPositionsByDepartment(Request $request)
  {
    $departmentId = $request->department_id;
    $positions = Position::where('dept_id', $departmentId)->get();
    $permissions = []; // Get the default permissions for these positions
    // Fetch permissions for each position
    foreach ($positions as $position) {
        $permissions[$position->id] = PositionPermission::where('position_id', $position->id)->pluck('permission_id')->toArray();
    }
    return response()->json([
        'positions' => $positions,
        'permissions' => $permissions
    ]);
  }

  public function updatemodule()
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

  public function updatePermissions(Request $request, $resort_id)
  {
      // Retrieve all submitted permissions
      DB::beginTransaction();

      try
      {
        $submittedPermissions = $request->input('module_permissions', []);

        if (empty($submittedPermissions)) {
            // Optionally handle the case where no permissions are submitted
            $response['success'] = false;
            $response['msg'] = __('Please Select Module wise Pages to  give resort permission', ['name' => 'Permission']);
            return response()->json($response);
        }
        $existingPermissions = ResortPagewisePermission::where('resort_id', $resort_id)
            ->get()
            ->groupBy( 'Module_id');


            $Module_id = array_keys($submittedPermissions);
            ResortPagewisePermission::where('resort_id', $resort_id)
            ->whereNotIn('page_permission_id',$Module_id )
            ->delete();


                foreach ($submittedPermissions as $Module_id=>$modulePermissionId)
                {
                    foreach($modulePermissionId as $permissionId)
                    {
                        ResortPagewisePermission::create([
                            'resort_id' => $resort_id,
                            'Module_id' => $Module_id, // role_id refers to position_id
                            'page_permission_id' => $permissionId,
                        ]);
                    }
                }
                DB::commit();
                $response['success'] = true;
                $response['msg'] = __('Resort Permission granted successfully', ['name' => 'Permission']);
                return response()->json($response);


      }
      catch (\Exception $e)
      {
        DB::rollBack();
          $response['success'] = false;
          $response['msg'] = __('Somthing Wrong ', ['name' => 'Permission']);
          return response()->json($response);
      }
    }

  // public function loginAsResortAdmin($resort_id)
  // {
  //     // Find the company admin by ID
  //     $resortAdmin = ResortAdmin::where('resort_id', $resort_id)->first();

  //     // Log out the current admin (super admin)
  //     Auth::guard('admin')->logout();

  //     // Log in as the resort admin
  //     Auth::guard('resort-admin')->login($resortAdmin);

  //     // Redirect to the company admin dashboard or desired route
  //     return redirect()->route('resort.workforceplan.dashboard');
  // }
}
