<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use App\Models\Role;
use App\Models\AdminModule;
use App\Models\Permission;
use App\Models\AdminModulePermission;
use App\Models\AdminRoleModulePermission;
use App\Helpers\Common;

class RolePermissionController extends Controller
{
  public function index()
  {
    $data = new Role;
    return view('admin.roles.index',compact('data'));
  }

  public function list()
  {
    $tableData = '';

    $tableData = Role::orderBy('created_at', 'desc')->get();

    return datatables()->of($tableData)
    ->addColumn('checkbox', '<input type="checkbox" name="student_checkbox[]" class="student_checkbox" value="{{$id}}" />')
    ->editColumn('status', function ($data) {
      $inactive_url = route('admin.role.block', $data->id);
      $active_url = route('admin.role.active', $data->id);
      $cls = Common::hasPermission(config('settings.admin_modules.roles_permissions'),config('settings.permissions.edit')) ? 'changeStatus' : '';

      if( $data->status == "active") {
        return '<a href="javascript:void(0)" class="active-status '.$cls.'" data-url="'.$inactive_url.'" title="Click here to deactivate" ><span class="badge badge-success">Active</span></a>';
      } else {
        return '<a href="javascript:void(0)" class="inactive-status '.$cls.'" data-url="'.$active_url.'" title="Click here to activate" ><span class="badge badge-danger">Inactive</span></a>';
      }
    })
    ->addColumn('action', function ($tableData) {
      $edit_url = route('admin.role.edit', $tableData->id);
      $delete_url = route('admin.role.destroy', $tableData->id);
      $permission_url = route('admin.role.edit_role_permissions', $tableData->id);

      $action_links = '<div class="btn-group">';

      if(Common::hasPermission(config('settings.admin_modules.roles_permissions'),config('settings.permissions.edit'))) {
        $action_links .= '<a title="Edit" class="btn btn-info mx-1 btn-sm" href="'.$edit_url.'"><i class="fas fa-pencil-alt"></i></a>';

        $action_links .= '<a title="Permission" class="btn btn-primary mx-1 btn-sm" href="'.$permission_url.'"><i class="fas fa-lock"></i></a>';
      }

      if(Common::hasPermission(config('settings.admin_modules.roles_permissions'),config('settings.permissions.delete'))) {
        $action_links .= '<a title="Delete" class="btn btn-danger mx-1 btn-sm action-delete" href="JavaScript:void(0);" data-url="'.$delete_url.'"><i class="fas fa-trash"></i></a>';
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
      $data = new Role();
      $isNew = 1;
      return view('admin.roles.edit')->with(compact('data','isNew'));
    } catch( \Exception $e ) {
      \Log::emergency( "File: ".$e->getFile() );
      \Log::emergency( "Line: ".$e->getLine() );
      \Log::emergency( "Message: ".$e->getMessage() );
    }
  }

  public function store(Request $request)
  {
    try {
      $input = $request->except(['_token']);

      $checkDuplicate = Role::where('name', $request->name)->first();

      if($checkDuplicate) {
        $response['success'] = false;
        $response['msg'] = 'The role already exists!';
        return response()->json($response);
      }

      $template = Role::create($input);

      $response['success'] = true;
      $response['msg'] = __('messages.addSuccess', ['name' => 'Role']);
      $response['redirect_url'] = route('admin.role.index');
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

  public function edit($id)
  {
      try
      {
          $isNew = 0;
          $data = Role::findOrFail($id); // Use findOrFail to automatically handle 404 if the role is not found
          return view('admin.roles.edit', compact('data', 'isNew'));
      }
      catch (\Exception $e)
      {
          \Log::emergency("File: " . $e->getFile());
          \Log::emergency("Line: " . $e->getLine());
          \Log::emergency("Message: " . $e->getMessage());

          // Redirect to a 404 error page
          abort(404);
      }
  }

  public function update(Request $request, $id)
  {
    try {
      $input = $request->except(['_token']);

      $checkDuplicate = Role::where('id', '!=', $id)->where('name', $request->name)->first();

      if($checkDuplicate) {
        $response['success'] = false;
        $response['msg'] = 'The name already exists!';
        return response()->json($response);
      }

      $tableData = Role::where('id', $id)->first();

      $tableData->update($input);

      $response['success'] = true;
      $response['msg'] = __('messages.updateSuccess', ['name' => 'Role']);
      $response['redirect_url'] = route('admin.role.index');
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

  public function destroy($id)
  {
    try {
      $data = Role::where( 'id', $id )->first();

      // Check if user exists with role
      $checkUser = Admin::where('id', '!=', 1)->where('role_id', $id)->count();

      if($checkUser > 0) {
        $response['success'] = false;
        $response['msg'] = 'Cannot delete, Admins with current roles exists!';
        return response()->json($response);
      }
      $data->delete();

      $response['success'] = true;
      $response['msg'] = __('messages.deleteSuccess', ['name' => 'Role']);
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

  public function editRolePermissions($id)
  {
    try {
      $page_title = "Permissions";
      $page_header = '<span class="arca-font">Assign</span> Permissions';
      $role = Role::findOrFail($id);

      if($role){
        $this->updatemodule();

        $permissions = Permission::get();
        $modules = AdminModule::with(['module_permissions'])->orderBy('name')->get();

        foreach($modules as $module) {
          $modulePermissions[$module->id] = $module->module_permissions->pluck('permission_id')->toArray();
          $modulePermissionIds[$module->id] = $module->module_permissions->pluck('id')->toArray();
        }

        $rolePermissions = AdminRoleModulePermission::where('role_id', $id)->pluck('module_permission_id')->toArray();

        return view('admin.roles.edit-permissions')->with(
          compact(
            'role',
            'permissions',
            'modules',
            'rolePermissions',
            'modulePermissionIds',
            'modulePermissions',
            'page_title',
            'page_header'
          )
        );
      }
      else{
        abort(404);
      }

    } catch( \Exception $e ) {
      \Log::emergency("File: ".$e->getFile());
      \Log::emergency("Line: ".$e->getLine());
      \Log::emergency("Message: ".$e->getMessage());
      // Redirect to a 404 error page
      abort(404);
    }
  }

  public function updateRolePermissions(Request $request, $id)
  {
    AdminRoleModulePermission::where('role_id', $id)->delete();

    if(isset($request->module_permissions))
    {
        foreach($request->module_permissions as $permissionId)
        {
            $permissionData['role_id'] = $id;
            $permissionData['module_permission_id'] = $permissionId;
            AdminRoleModulePermission::create($permissionData);
        }
    }

    $response['success'] = true;
    $response['msg'] = __('messages.updateSuccess', ['name' => 'Permission']);
    return response()->json($response);
  }

  public function massremove(Request $request)
  {
    try {
      $ids = $request->input('id');
      $data = Role::whereIn('id', $ids)->get();

      foreach ($data as $key => $value) {
        $checkUser = Admin::where('id', '!=', 1)->where('role_id', $value->id)->count();
        if($checkUser > 0) {
          continue;
        }
        $value->delete();
      }

      $response['success'] = true;
      $response['msg'] = 'Deleted';
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

  public function updatemodule()
  {
    $getdata = AdminModule::whereDate('created_at', '=', now())->first();
    $getdata = '';
    if(empty($getdata->id)){
      AdminModule::truncate();
      Permission::truncate();
      AdminModulePermission::truncate();

      $camodule_array = [
        'Admin Users',
        'Settings',
        'Roles Permissions',
        'Email Templates',
        'Resorts',
        'Divisions',
        'Departments',
        'Sections',
        'Positions',
        'Notifications',
        'Resort Modules',
        'Resort Pages Module',
        'Public Holidays',
        'EWT Brackets',
        'Support Categories',
        'Support'
      ];

      foreach($camodule_array as $camodulearray){
        $camodule['name']   = $camodulearray;
        AdminModule::create($camodule);
      }

      $capermission_array     = [
        ['name' => 'View','order' => 1],
        ['name' => 'Create','order' => 2],
        ['name' => 'Edit','order' => 3],
        ['name' => 'Delete','order' => 4],
      ];

      foreach($capermission_array as $capermissionarray){
        $capermission['name']   = $capermissionarray['name'];
        $capermission['order']  = $capermissionarray['order'];
        Permission::create($capermission);
      }

      $camodule_permissions_array = [
        ['module_id' => 1,  'permission_id' => 1],
        ['module_id' => 1,  'permission_id' => 2],
        ['module_id' => 1,  'permission_id' => 3],
        ['module_id' => 1,  'permission_id' => 4],

        // ['module_id' => 2,  'permission_id' => 1],
        ['module_id' => 2,  'permission_id' => 3],

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
        ['module_id' => 16,  'permission_id' => 3],
        ['module_id' => 16,  'permission_id' => 4],

      ];

        foreach($camodule_permissions_array as $camodulepermissionsarray){
            $camodulepermission['module_id']        = $camodulepermissionsarray['module_id'];
            $camodulepermission['permission_id']    = $camodulepermissionsarray['permission_id'];
            AdminModulePermission::create($camodulepermission);
        }
    }
  }

  public function block($id)
  {
    try {
      $data = Role::where('id',$id)->first();
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
      $data = Role::where('id',$id)->first();
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
}
