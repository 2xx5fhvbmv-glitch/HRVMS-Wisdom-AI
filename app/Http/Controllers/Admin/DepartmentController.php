<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use App\Models\Department;
use App\Models\Division;
use App\Helpers\Common;
use File;
use DB;

class DepartmentController extends Controller
{
   
    public function index()
    {
        // return view('admin.manufecturers.index');
        $data = new Department;
        // dd($data);
        return view('admin.departments.index',compact('data'));
    }

    public function list()
    {
        $departments = Department::select([
                'department.id',
                'divisions.name as division',
                'department.code',
                'department.name',
                'department.short_name',
                'department.status',
                'department.created_by',
                'department.created_at',
                'department.updated_at'
            ])
            ->join('divisions', 'department.division_id', '=', 'divisions.id')
            ->orderBy('department.created_at', 'DESC')
            ->get();

        return datatables()->of($departments)
            ->addColumn('checkbox', function ($department) {
                return '<input type="checkbox" name="department_checkbox[]" class="department_checkbox" value="'.$department->id.'" />';
            })
            ->editColumn('status', function ($department) {
                $inactive_url = route('admin.department.block', $department->id);
                $active_url = route('admin.department.active', $department->id);
                $cls = Common::hasPermission(config('settings.admin_modules.departments'), config('settings.permissions.edit')) ? 'changeStatus' : '';
        
                if ($department->status == "active") {
                    return '<a href="javascript:void(0)" class="active-status '.$cls.'" data-url="'.$inactive_url.'" title="Click here to deactivate"><span class="badge badge-success">Active</span></a>';
                } else {
                    return '<a href="javascript:void(0)" class="inactive-status '.$cls.'" data-url="'.$active_url.'" title="Click here to activate"><span class="badge badge-danger">Inactive</span></a>';
                }
            })
            ->addColumn('action', function ($department) {
                $edit_url = route('admin.department.edit', $department->id);
                $delete_url = route('admin.department.destroy', $department->id);
                
                $action_links = '<div class="btn-group">';

                if (Common::hasPermission(config('settings.admin_modules.departments'), config('settings.permissions.edit'))) {
                    $action_links .= '<a title="Edit" class="btn btn-info btn-sm mx-1" href="'.$edit_url.'"><i class="fas fa-pencil-alt"></i></a>';
                }

                if (Common::hasPermission(config('settings.admin_modules.departments'), config('settings.permissions.delete'))) {
                    $action_links .= '<a title="Delete" class="btn btn-danger btn-sm mx-1 action-delete" href="javascript:void(0);" data-url="'.$delete_url.'"><i class="fas fa-trash"></i></a>';
                }
                
                $action_links .= '</div>';

                return $action_links;
            })
            ->rawColumns(['checkbox', 'status', 'action']) // Ensure the HTML is processed
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
            $data = new Department();
            $divisions = Division::where('status','active')->get();
            $isNew = 1;
            return view('admin.departments.edit')->with(compact('data','isNew','divisions'));
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
      
            $checkDuplicate = Department::where('name', $request->name)->where('division_id', $request->division_id)->first();
            
            if($checkDuplicate) {
              $response['success'] = false;
              $response['msg'] = 'The department already exists!';
              return response()->json($response);
            }
      
            $template = Department::create($input);
      
            $response['success'] = true;
            $response['msg'] = __('messages.addSuccess', ['name' => 'Department']);
            $response['redirect_url'] = route('admin.department.index');
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
            $data = Department::findOrFail($id);
            $divisions = Division::where('status','active')->get();
            return view('admin.departments.edit')->with(compact('data','isNew','divisions'));
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
      
            // $checkDuplicate = Department::where('id', '!=', $id)->where('name', $request->name)->first();
            
            $checkDuplicate = Department::where('id', '!=', $id)->where('name', $request->name)->where('division_id', $request->division_id)->first();

            if($checkDuplicate) {
              $response['success'] = false;
              $response['msg'] = 'The department already exists!';
              return response()->json($response);
            }
      
            $tableData = Department::where('id', $id)->first();
      
            $tableData->update($input);
      
            $response['success'] = true;
            $response['msg'] = __('messages.updateSuccess', ['name' => 'Department']);
            $response['redirect_url'] = route('admin.department.index');
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
            $data = Department::where( 'id', $id )->first();
            $data->delete();
            $response['success'] = true;
            $response['msg'] = __('messages.deleteSuccess', ['name' => 'Department']);
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
            $data = Department::whereIn('id', $ids)->get();
            foreach ($data as $key => $value) {
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

    public function block($id)
    {
        try {
        $data = Department::where('id',$id)->first();
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
        $data = Department::where('id',$id)->first();
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
