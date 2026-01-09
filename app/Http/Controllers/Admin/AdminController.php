<?php


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\Admin;
use App\Models\Role;
use DB;
use File;
use App\Exports\AdminExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\Common;

class AdminController extends Controller
{
  public function index()
  {
    return view('admin.admins.index');
  }

  public function profile()
  {
    $data = Auth::user();
    $role_name = 'Super Admin';

    if($data->type == 'sub') {
      $role = Role::where('id', $data->role_id)->first();
      $role_name = $role->name;
    }
    return view('admin.admins.profile')->with(compact('data','role_name'));
  }

  public function profileUpdate( Request $request )
  {
    try {
      $input = $request->except('_token','img_path');
      $admin = Admin::where( "id", $request->id )->first();

      if( isset( $request->profile_picture ) ) {
        $path = config('settings.admin_folder');

        // Remove previous image
        if( $admin->profile_picture != '' ) {
          $imgpath = $path."/".$admin->profile_picture;

          if( \File::exists( $imgpath ) ) {
            unlink($imgpath);
          }
        }

        $imgName = "admin_profile_".$admin->id."_" . date('mdYHis') . uniqid() . "." . $request->profile_picture->getClientOriginalExtension();

        if( !File::isDirectory($path) ) {
          Common::makeDiractory($path);
        }

        $path = config('settings.admin_folder');
        Common::uploadFile($request->profile_picture, $imgName, $path);
        $input['profile_picture'] = $imgName;
      }

      $admin->update( $input );

      $response['success'] = true;
      $response['msg'] = "Profile updated";
      return response()->json($response);
    } catch( \Exception $e ) {
      \Log::emergency( 'File: '. $e->getFile() );
      \Log::emergency( 'Line: '. $e->getLine() );
      \Log::emergency( 'Message: '. $e->getMessage() );

      $response['success'] = false;
      $response['msg'] = $e->getMessage();
      return response()->json($response);
    }
  }

  public function list(Request $request)
  {
      try {
          $perPage = $request->length;
          $skip = $request->start;
          $take = $request->length;

          // Base query excluding current user and Super Admin
          $query = Admin::where('id', '!=', Auth::guard('admin')->user()->id)
              ->where('id', '!=', 1);

          // Handle ordering from DataTables
          if ($request->has('order')) {
              $columnIndex = $request->input('order.0.column');
              $columnName = $request->input("columns.$columnIndex.data");
              $direction = $request->input('order.0.dir', 'desc');

              // Whitelisted sortable columns (must be DB fields)
              $sortable = ['first_name', 'last_name', 'email', 'status', 'created_by', 'created_at', 'updated_at'];

              if (in_array($columnName, $sortable)) {
                  $query->orderBy($columnName, $direction);
              } else {
                  $query->orderBy('created_at', 'desc');
              }
          } else {
              $query->orderBy('created_at', 'desc');
          }

          // Get total records before pagination
          $total = $query->count();

          // Apply pagination
          $admins = $query->skip($skip)->take($take)->get();

          // Return DataTable response
          return datatables()->of($admins)
              ->addColumn('checkbox', function ($data) {
                  return '<input type="checkbox" name="student_checkbox[]" class="student_checkbox" value="' . $data->id . '" />';
              })
              ->editColumn('status', function ($data) {
                  $inactive_url = route('admin.block', $data->id);
                  $active_url = route('admin.active', $data->id);
                  $cls = Common::hasPermission(config('settings.admin_modules.admin_users'), config('settings.permissions.edit')) ? 'changeStatus' : '';

                  if ($data->status == "active") {
                      return '<a href="javascript:void(0)" class="active-status ' . $cls . '" data-url="' . $inactive_url . '" title="Click here to deactivate"><span class="badge badge-success">Active</span></a>';
                  } else {
                      return '<a href="javascript:void(0)" class="inactive-status ' . $cls . '" data-url="' . $active_url . '" title="Click here to activate"><span class="badge badge-danger">Inactive</span></a>';
                  }
              })
              ->addColumn('name', function ($data) {
                  return ucwords($data->first_name . ' ' . $data->last_name);
              })
              ->addColumn('role_name', function ($data) {
                  $role = Role::find($data->role_id);
                  return $role ? $role->name : 'Super Admin';
              })
              ->addColumn('action', function ($data) {
                  $edit_url = route('admin.edit', $data->id);
                  $delete_url = route('admin.destroy', $data->id);

                  $actions = '<div class="btn-group">';
                  if (Common::hasPermission(config('settings.admin_modules.admin_users'), config('settings.permissions.edit'))) {
                      $actions .= '<a title="Edit" class="btn btn-info mx-1 btn-sm" href="' . $edit_url . '"><i class="fas fa-pencil-alt"></i></a>';
                  }
                  if (Common::hasPermission(config('settings.admin_modules.admin_users'), config('settings.permissions.delete'))) {
                      $actions .= '<a title="Delete" class="btn btn-danger mx-1 btn-sm action-delete" href="JavaScript:void(0);" data-url="' . $delete_url . '"><i class="fas fa-trash"></i></a>';
                  }
                  $actions .= '</div>';
                  return $actions;
              })
              ->escapeColumns([]) // Allow HTML
              ->setTotalRecords($total)
              ->make(true);

      } catch (\Exception $e) {
          \Log::emergency("File: " . $e->getFile());
          \Log::emergency("Line: " . $e->getLine());
          \Log::emergency("Message: " . $e->getMessage());
          return response()->json(['error' => 'Failed to load admin data'], 500);
      }
  }


  public function checkEmailExists( Request $request )
  {
    try {
      if( isset( $request->id ) ) {
        $admin = Admin::withTrashed()->where([
          [ 'email', $request->email ],
          [ 'id', '!=', $request->id ]
        ])->first();
      } else {
        $admin = Admin::withTrashed()->where( 'email', $request->email )->first();
      }

      if( !empty( $admin ) ) {
        echo "false";
      } else {
        echo "true";
      }
    } catch( \Exception $e ) {
      \Log::emergency( "File: ".$e->getFile() );
      \Log::emergency( "Line: ".$e->getLine() );
      \Log::emergency( "Message: ".$e->getMessage() );
    }
  }

  public function create()
  {
    try {
      $admin = new Admin();
      $isNew = 1;
      $roles = Role::where('status', 'active')->get();
      return view('admin.admins.create')->with(compact(
        'admin',
        'isNew',
        'roles'
      ));
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

      $input['password'] = bcrypt($input['password']);
      $input['status'] = 'active';
      $admin = Admin::create($input);

      if($admin){
        $admin->sendAdminRegistrationEmail($admin,($request['password']));
      }

      $response['success'] = true;
      $response['redirect_url'] = route('admin.index');
      $response['msg'] = __('messages.addSuccess', ['name' => 'Admin']);
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

  public function changePassword(Request $request)
  {
    try {
      $admin = Admin::where( 'id', $request->admin_id )->first();
      $admin->password = bcrypt($request->password);
      $admin->save();

      Auth::guard('admin')->logout();

      $response['success'] = true;
      $response['redirect_url'] = route('admin.loginindex');
      $response['msg'] = __('messages.changeSuccess', ['name' => 'Password']);
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
    try {
      $admin = Admin::where('id','!=',1)->findOrFail($id);
      $isNew = 0;
      $roles = Role::where('status', 'active')->get();
      return view('admin.admins.create')->with(compact('admin','isNew','roles'));
    } catch( \Exception $e ) {
      \Log::emergency( "File: ".$e->getFile() );
      \Log::emergency( "Line: ".$e->getLine() );
      \Log::emergency( "Message: ".$e->getMessage() );
      // Redirect to a 404 error page
      abort(404);
    }
  }

  public function update(Request $request, $id)
  {
    try {
      $input = $request->except(['_token']);
      $admin = Admin::where('id', $id)->first();

      $input['password'] = (isset($input['password']) && !empty($input['password'])) ? bcrypt($input['password']) : $admin->password;

      $admin->update($input);

      $response['success'] = true;
      $response['msg'] = __('messages.updateSuccess', ['name' => 'Admin']);
      $response['redirect_url'] = route('admin.index');
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

  public function checkPassword(Request $request)
  {
    try {
      $admin = Admin::where( 'id', $request->id )->first();

      if( Hash::check( $request->old_pass, $admin->password ) ) {
        echo "true";
      } else {
        echo "false";
      }
    } catch( \Exception $e ) {
      \Log::emergency( "File: ".$e->getFile() );
      \Log::emergency( "Line: ".$e->getLine() );
      \Log::emergency( "Message: ".$e->getMessage() );
    }
  }

  public function destroy($id)
  {
    try {
      $admin = Admin::where('id','!=',1)->findOrFail($id);
      $admin->email = base64_encode($admin->email);
      $admin->save();
      $admin->delete();

      $response['success'] = true;
      $response['msg'] = __('messages.deleteSuccess', ['name' => 'Admin']);
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
      $data = Admin::where('id',$id)->first();
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
      $data = Admin::where('id',$id)->first();
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

  public function massremove(Request $request)
  {
    try {
      $ids = $request->input('id');
      $data = Admin::where('id','!=',1)->whereIn('id', $ids)->get();

      foreach ($data as $key => $value) {
        $value->email = Common::generateUniqueCode(35, 'admins', 'email');
        $value->save();
        $value->delete();
      }

      $response['success'] = true;
      $response['msg'] = 'Admins deleted';
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
