<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\Position;
use App\Models\ResortModule;
use App\Models\ResortModulePermission;
use App\Models\ResortPermission;
use App\Models\ResortPositionModulePermission;
use App\Models\ResortRole;
use App\Helpers\Common;
use File;
use DB;

class PositionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = new Position;
        return view('admin.positions.index',compact('data'));
    }

    public function list()
    {
        $positions = Position::select([
                'positions.id',
                'positions.dept_id',
                'department.name as department_name',
                'positions.position_title',
                'positions.code',
                'positions.short_title',
                'positions.status',
                'positions.created_by',
                'divisions.name as division',
                'positions.created_at',
                'positions.updated_at'
            ])
            ->join('department', 'positions.dept_id', '=', 'department.id')
            ->join('divisions', 'divisions.id', '=', 'department.division_id')
            ->orderBy('positions.created_at', 'DESC')
            ->get();

        return datatables()->of($positions)
            ->addColumn('checkbox', function ($position) {
                return '<input type="checkbox" name="position_checkbox[]" class="position_checkbox" value="'.$position->id.'" />';
            })
            ->editColumn('status', function ($position) {
                $inactive_url = route('admin.positions.block', $position->id);
                $active_url = route('admin.positions.active', $position->id);
                $cls = Common::hasPermission(config('settings.admin_modules.positions'), config('settings.permissions.edit')) ? 'changeStatus' : '';

                if ($position->status == "active") {
                    return '<a href="javascript:void(0)" class="active-status '.$cls.'" data-url="'.$inactive_url.'" title="Click here to deactivate"><span class="badge badge-success">Active</span></a>';
                } else {
                    return '<a href="javascript:void(0)" class="inactive-status '.$cls.'" data-url="'.$active_url.'" title="Click here to activate"><span class="badge badge-danger">Inactive</span></a>';
                }
            })
            ->addColumn('action', function ($position) {
                $edit_url = route('admin.positions.edit', $position->id);
                $delete_url = route('admin.positions.destroy', $position->id);
                $permission_url = route('admin.positions.edit_permissions', $position->id);

                $action_links = '<div class="btn-group">';

                if(Common::hasPermission(config('settings.admin_modules.positions'), config('settings.permissions.edit'))) {
                    $action_links .= '<a title="Edit" class="btn btn-info mx-1 btn-sm" href="'.$edit_url.'"><i class="fas fa-pencil-alt"></i></a>';

                    // $action_links .= '<a title="Permission" class="btn btn-primary mx-1 btn-sm" href="'.$permission_url.'"><i class="fas fa-lock"></i></a>';
                }

                if(Common::hasPermission(config('settings.admin_modules.positions'), config('settings.permissions.delete'))) {
                    $action_links .= '<a title="Delete" class="btn btn-danger mx-1 btn-sm action-delete" href="javascript:void(0);" data-url="'.$delete_url.'"><i class="fas fa-trash"></i></a>';
                }

                $action_links .= '</div>';

                return $action_links;
            })
            ->addColumn('department', function($position) {
                return $position->department_name;
            })
            ->rawColumns(['checkbox', 'status', 'action']) // Include 'status' in rawColumns to process HTML
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        try {
            $data = new Position();
            $departments = Department::all();
            // dd($departments);
            $isNew = 1;
            return view('admin.positions.edit')->with(compact('data','isNew','departments'));
        } catch( \Exception $e ) {
            \Log::emergency( "File: ".$e->getFile() );
            \Log::emergency( "Line: ".$e->getLine() );
            \Log::emergency( "Message: ".$e->getMessage() );
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $input = $request->except(['_token']);

            $checkDuplicate = Position::where('position_title', $request->position_title)->where('dept_id', $request->dept_id)->first();

            if($checkDuplicate) {
              $response['success'] = false;
              $response['msg'] = 'The position already exists!';
              return response()->json($response);
            }

            $template = Position::create($input);

            $response['success'] = true;
            $response['msg'] = __('messages.addSuccess', ['name' => 'Position']);
            $response['redirect_url'] = route('admin.positions.index');
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
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try {
            $isNew = 0;
            $data = Position::findOrFail($id);
            $departments = Department::all();
            // dd($data);
            return view('admin.positions.edit')->with(compact('data','isNew','departments'));
        } catch( \Exception $e ) {
            \Log::emergency( "File: ".$e->getFile() );
            \Log::emergency( "Line: ".$e->getLine() );
            \Log::emergency( "Message: ".$e->getMessage() );
            // Redirect to a 404 error page
            abort(404);
            $response['success'] = false;
            $response['msg'] = $e->getMessage();
            return response()->json($response);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $input = $request->except(['_token']);

            $checkDuplicate = Position::where('id', '!=', $id)->where('position_title', $request->position_title)->where('dept_id', $request->dept_id)->first();

            if($checkDuplicate) {
              $response['success'] = false;
              $response['msg'] = 'The position title already exists!';
              return response()->json($response);
            }

            $tableData = Position::where('id', $id)->first();

            $tableData->update($input);

            $response['success'] = true;
            $response['msg'] = __('messages.updateSuccess', ['name' => 'Position']);
            $response['redirect_url'] = route('admin.positions.index');
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $data = Position::where( 'id', $id )->first();

            $data->delete();

            $response['success'] = true;
            $response['msg'] = __('messages.deleteSuccess', ['name' => 'Position']);
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
            $positions = Position::whereIn('id', $ids)->get();

            foreach ($positions as $position) {
                $position->delete();
            }

            $response['success'] = true;
            $response['msg'] = __('messages.deleteSuccess', ['name' => 'Position']);
            return response()->json($response);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());

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
        $position = Position::findOrFail($id);

        if($position){
          $this->updatemodule();

          $permissions = ResortPermission::get();
          $modules = ResortModule::with(['module_permissions'])->orderBy('name')->get();

          foreach($modules as $module) {
            $modulePermissions[$module->id] = $module->module_permissions->pluck('permission_id')->toArray();
            $modulePermissionIds[$module->id] = $module->module_permissions->pluck('id')->toArray();
          }

          $positionPermissions = ResortPositionModulePermission::where('position_id', $id)->pluck('module_permission_id')->toArray();

          return view('admin.positions.edit-permissions')->with(
            compact(
              'position',
              'permissions',
              'modules',
              'positionPermissions',
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

    public function updatePermissions(Request $request, $id)
    {
      ResortPositionModulePermission::where('position_id', $id)->delete();

      if(isset($request->module_permissions))
      {
        foreach($request->module_permissions as $permissionId)
        {
          $permissionData['position_id'] = $id;
          $permissionData['module_permission_id'] = $permissionId;
          ResortPositionModulePermission::create($permissionData);
        }
      }

      $response['success'] = true;
      $response['msg'] = __('messages.updateSuccess', ['name' => 'Permission']);
      return response()->json($response);
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

    public function block($id)
    {
        try {
            $data = Position::where('id',$id)->first();
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
            $data = Position::where('id',$id)->first();
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
