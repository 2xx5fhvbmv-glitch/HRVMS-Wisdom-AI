<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use App\Models\SupportCategory;
use App\Helpers\Common;
use File;
use DB;

class SupportCategoryController extends Controller
{

    public function index()
    {
        // return view('admin.manufecturers.index');
        $data = new SupportCategory;
        // dd($data);
        return view('admin.support_categories.index',compact('data'));
    }

    public function list()
    {
        $categories = SupportCategory::orderBy('created_at', 'DESC')->get();
        // dd($categories);
        return datatables()->of($categories)
            ->addColumn('checkbox', function ($category) {
                return '<input type="checkbox" name="support_category_checkbox[]" class="support_category_checkbox" value="'.$category->id.'" />';
            })
            ->addColumn('name', function ($category) {
                return $category->name;
            })
            ->editColumn('status', function ($row) {
                $inactive_url = route('admin.support_categories.block', $row->id);
                $active_url = route('admin.support_categories.active', $row->id);
                $cls ='changeStatus';
    
                if ($row->status == "active") {
                    return '<a href="javascript:void(0)" class="active-status '.$cls.'" data-url="'.$inactive_url.'" title="Click here to deactivate">
                                <span class="badge badge-success">Active</span>
                            </a>';
                } else {
                    return '<a href="javascript:void(0)" class="inactive-status '.$cls.'" data-url="'.$active_url.'" title="Click here to activate">
                                <span class="badge badge-danger">Inactive</span>
                            </a>';
                }
            })
            ->addColumn('action', function ($category) {
                $edit_url = route('admin.support_categories.edit', $category->id);
                $delete_url = route('admin.support_categories.destroy', $category->id);
    
                $action_links = '<div class="btn-group">';
    
                if (Common::hasPermission(config('settings.admin_modules.support_categories'), config('settings.permissions.edit'))) {
                    $action_links .= '<a title="Edit" class="btn btn-info btn-sm mx-1" href="'.$edit_url.'">
                                        <i class="fas fa-pencil-alt"></i>
                                      </a>';
                }
    
                if (Common::hasPermission(config('settings.admin_modules.support_categories'), config('settings.permissions.delete'))) {
                    $action_links .= '<a title="Delete" class="btn btn-danger btn-sm mx-1 action-delete" href="javascript:void(0);" 
                                        data-url="'.$delete_url.'">
                                        <i class="fas fa-trash"></i>
                                      </a>';
                }
    
                $action_links .= '</div>';
    
                return $action_links;
            })
            ->rawColumns(['checkbox', 'status', 'action']) // Ensure these columns render HTML properly
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
            $data = new SupportCategory();
            $isNew = 1;
            return view('admin.support_categories.edit')->with(compact('data','isNew'));
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
            $request->validate([
                'name' => 'required|string',
                'status' => 'required|string',
            ]);
            $input = $request->except(['_token']);
            // dd($input);
            $template = SupportCategory::create($input);

            $response['success'] = true;
            $response['msg'] = __('messages.addSuccess', ['name' => 'Support Categories']);
            $response['redirect_url'] = route('admin.support_categories.index');
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
            $data = SupportCategory::findOrFail($id);
            return view('admin.support_categories.edit')->with(compact('data','isNew'));
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
            $request->validate([
               'name' => 'required|string',
                'status' => 'required|string',
            ]);
            $input = $request->except(['_token']);

            $tableData = SupportCategory::where('id', $id)->first();

            $tableData->update($input);

            $response['success'] = true;
            $response['msg'] = __('messages.updateSuccess', ['name' => 'Support Categories']);
            $response['redirect_url'] = route('admin.support_categories.index');
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
            $data = SupportCategory::where( 'id', $id )->first();

            $data->delete();

            $response['success'] = true;
            $response['msg'] = __('messages.deleteSuccess', ['name' => 'Support Categories']);
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
            $data = SupportCategory::whereIn('id', $ids)->get();

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
        $data = SupportCategory::where('id',$id)->first();
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
        $data = SupportCategory::where('id',$id)->first();
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
