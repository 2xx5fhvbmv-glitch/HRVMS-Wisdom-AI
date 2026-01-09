<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\Section;
use App\Helpers\Common;
use File;
use DB;

class SectionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = new Section;
        return view('admin.sections.index',compact('data'));
    }

    public function list()
    {
        $sections = Section::select(['sections.id', 'sections.dept_id', 'department.name as department_name', 'sections.code','sections.name','sections.short_name','sections.status','sections.created_by','sections.created_at', 'sections.updated_at'])
            ->join('department', 'sections.dept_id', '=', 'department.id')
            ->orderBy('sections.created_at', 'DESC')
            ->get();

        return datatables()->of($sections)
            ->addColumn('checkbox', function ($section) {
                return '<input type="checkbox" name="section_checkbox[]" class="section_checkbox" value="'.$section->id.'" />';
            })
            ->editColumn('status', function ($section) {
                $inactive_url = route('admin.sections.block', $section->id);
                $active_url = route('admin.sections.active', $section->id);
                $cls = Common::hasPermission(config('settings.admin_modules.sections'), config('settings.permissions.edit')) ? 'changeStatus' : '';
        
                if ($section->status == "active") {
                    return '<a href="javascript:void(0)" class="active-status '.$cls.'" data-url="'.$inactive_url.'" title="Click here to deactivate"><span class="badge badge-success">Active</span></a>';
                } else {
                    return '<a href="javascript:void(0)" class="inactive-status '.$cls.'" data-url="'.$active_url.'" title="Click here to activate"><span class="badge badge-danger">Inactive</span></a>';
                }
            })
            ->addColumn('action', function ($section) {
                $edit_url = route('admin.sections.edit', $section->id);
                $delete_url = route('admin.sections.destroy', $section->id);
                
                $action_links = '<div class="btn-group">';

                if (Common::hasPermission(config('settings.admin_modules.sections'), config('settings.permissions.edit'))) {
                    $action_links .= '<a title="Edit" class="btn btn-info btn-sm mx-1" href="'.$edit_url.'"><i class="fas fa-pencil-alt"></i></a>';
                }

                if (Common::hasPermission(config('settings.admin_modules.sections'), config('settings.permissions.delete'))) {
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
            $data = new Section();
            $departments = Department::where('status','active')->get();
            $isNew = 1;
            return view('admin.sections.edit')->with(compact('data','isNew','departments'));
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
      
            $checkDuplicate = Section::where('name', $request->name)->where('dept_id', $request->dept_id)->first();
            
            if($checkDuplicate) {
              $response['success'] = false;
              $response['msg'] = 'The section already exists!';
              return response()->json($response);
            }
      
            $template = Section::create($input);
      
            $response['success'] = true;
            $response['msg'] = __('messages.addSuccess', ['name' => 'Section']);
            $response['redirect_url'] = route('admin.sections.index');
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
            $data = Section::findOrFail($id);
            $departments = Department::where('status','active')->get();
            // dd($data);
            return view('admin.sections.edit')->with(compact('data','isNew','departments'));
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
      
            $checkDuplicate = Section::where('id', '!=', $id)->where('name', $request->name)->first();
      
            if($checkDuplicate) {
              $response['success'] = false;
              $response['msg'] = 'The section title already exists!';
              return response()->json($response);
            }
      
            $tableData = Section::where('id', $id)->first();
      
            $tableData->update($input);
      
            $response['success'] = true;
            $response['msg'] = __('messages.updateSuccess', ['name' => 'Section']);
            $response['redirect_url'] = route('admin.sections.index');
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
            $data = Section::where( 'id', $id )->first();
      
            $data->delete();
      
            $response['success'] = true;
            $response['msg'] = __('messages.deleteSuccess', ['name' => 'Section']);
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
            $data = Section::whereIn('id', $ids)->get();

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
        $data = Section::where('id',$id)->first();
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
        $data = Section::where('id',$id)->first();
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
