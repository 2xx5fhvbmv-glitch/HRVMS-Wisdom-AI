<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Modules;
use App\Helpers\Common;
use Auth;

class ModuleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        if ($request->ajax())
        {
            $Modules = Modules::orderBy('created_at', 'DESC')
            ->get();
            return datatables()->of($Modules)
                ->addColumn('checkbox', function ($Modules) {
                return '<input type="checkbox" name="Module_checkbox[]" class="Module_checkbox" value="'.$Modules->id.'" />';
            })
            ->editColumn('status', function ($row) {
                $inactive_url = route('admin.Modules.block', $row->id);
                $active_url = route('admin.Modules.active', $row->id);
                $cls ='changeStatus' ;
                if ($row->status == "Active") {
                    return '<a href="javascript:void(0)" class="active-status '.$cls.'" data-url="'.$inactive_url.'" title="Click here to deactivate"><span class="badge badge-success">Active</span></a>';
                } else {
                    return '<a href="javascript:void(0)" class="inactive-status '.$cls.'" data-url="'.$active_url.'" title="Click here to activate"><span class="badge badge-danger">Inactive</span></a>';
                }
            })
            ->addColumn('action', function ($row) {
                $edit_url = route('admin.Modules.edit', $row->id);
                $delete_url = route('admin.Modules.destroy', $row->id);

                $action_links = '<div class="btn-group">';

                    if(Common::hasPermission(config('settings.admin_modules.modules'),config('settings.permissions.edit'))) 
                    {
                        $action_links .= '<a title="Edit" class="btn btn-info mx-1 btn-sm" href="'.$edit_url.'"><i class="fas fa-pencil-alt"></i></a>';
                    }
                    if(Common::hasPermission(config('settings.admin_modules.modules'),config('settings.permissions.delete')))
                    {
                        $action_links .= '<a title="Delete" class="btn btn-danger mx-1 btn-sm action-delete" href="javascript:void(0);" data-url="'.$delete_url.'"><i class="fas fa-trash"></i></a>';
                    }
                   
                $action_links .= '</div>';

                return $action_links;
            })

            ->rawColumns(['checkbox', 'status', 'action']) // Include 'status' in rawColumns to process HTML
            ->make(true);


        }

        return view('admin.Modules.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        return view('admin.Modules.edit');

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

            $checkDuplicate = Modules::where('module_name', $request->module_name)->first();

            if($checkDuplicate) {
              $response['success'] = false;
              $response['msg'] = 'The Modules already exists!';
              return response()->json($response);
            }

            $template = Modules::create($input);

            $response['success'] = true;
            $response['msg'] = __('messages.addSuccess', ['name' => 'Modules']);
            $response['redirect_url'] = route('admin.Modules.index');
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
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
        $data = Modules::where('id',$id)->first();
        return view('admin.Modules.edit',compact('data'));
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

            $checkDuplicate = Modules::where('id', '!=',   $id)->where('module_name', $request->module_name)->first();

            if($checkDuplicate) {
              $response['success'] = false;
              $response['msg'] = 'The Modules already exists!';
              return response()->json($response);
            }

            $tableData = Modules::where('id', $id)->first();

            $tableData->update($input);

            $response['success'] = true;
            $response['msg'] = __('messages.updateSuccess', ['name' => 'Modules']);
            $response['redirect_url'] = route('admin.Modules.index');
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
            // Find the notification by ID, or fail
            $notification = Modules::findOrFail($id);


            // Delete the notification
            $notification->delete();

            // Return a successful response
            $response['success'] = true;
            $response['msg'] = __('messages.deleteSuccess', ['name' => 'Modules']);
            return response()->json($response);
        } catch (\Exception $e) {
            // Log the error details
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());

            // Return an error response
            $response['success'] = false;
            $response['msg'] = __('messages.deleteFailed', ['name' => 'Modules']);
            return response()->json($response);
        }
    }
    public function block($id)
    {
      try {
        $data = Modules::where('id',$id)->first();
        $data->status ='Inactive';
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
        $data = Modules::where('id',$id)->first();
        $data->status ='Active';
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
            $data = Modules::whereIn('id', $ids)->get();

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
}
