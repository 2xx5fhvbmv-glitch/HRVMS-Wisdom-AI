<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\PublicHoliday;
use App\Helpers\Common;
use File;
use DB;


class PublicHolidayController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = new PublicHoliday;
        return view('admin.public_holidays.index',compact('data'));
    }

    public function list()
    {
        $public_holidays = PublicHoliday::orderBy('updated_at', 'DESC')->get();

        return datatables()->of($public_holidays)
            ->addColumn('checkbox', function ($public_holiday) {
                return '<input type="checkbox" name="holiday_checkbox[]" class="holiday_checkbox" value="'.$public_holiday->id.'" />';
            })
            ->editColumn('status', function ($public_holiday) {
                $inactive_url = route('admin.public_holidays.block', $public_holiday->id);
                $active_url = route('admin.public_holidays.active', $public_holiday->id);
                $cls = Common::hasPermission(config('settings.admin_modules.public_holidays'), config('settings.permissions.edit')) ? 'changeStatus' : '';

                if ($public_holiday->status == "active") {
                    return '<a href="javascript:void(0)" class="active-status '.$cls.'" data-url="'.$inactive_url.'" title="Click here to deactivate"><span class="badge badge-success">Active</span></a>';
                } else {
                    return '<a href="javascript:void(0)" class="inactive-status '.$cls.'" data-url="'.$active_url.'" title="Click here to activate"><span class="badge badge-danger">Inactive</span></a>';
                }
            })
            ->addColumn('action', function ($public_holiday) {
                $edit_url = route('admin.public_holidays.edit', $public_holiday->id);
                $delete_url = route('admin.public_holidays.destroy', $public_holiday->id);

                $action_links = '<div class="btn-group">';

                if(Common::hasPermission(config('settings.admin_modules.public_holidays'), config('settings.permissions.edit'))) {
                    $action_links .= '<a title="Edit" class="btn btn-info mx-1 btn-sm" href="'.$edit_url.'"><i class="fas fa-pencil-alt"></i></a>';
                }

                if(Common::hasPermission(config('settings.admin_modules.public_holidays'), config('settings.permissions.delete'))) {
                    $action_links .= '<a title="Delete" class="btn btn-danger mx-1 btn-sm action-delete" href="javascript:void(0);" data-url="'.$delete_url.'"><i class="fas fa-trash"></i></a>';
                }

                $action_links .= '</div>';

                return $action_links;
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
            $data = new PublicHoliday();
            $isNew = 1;
            return view('admin.public_holidays.edit')->with(compact('data','isNew'));
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

            $checkDuplicate = PublicHoliday::where('name', $request->name)->first();

            if($checkDuplicate) {
              $response['success'] = false;
              $response['msg'] = 'The holiday name already exists!';
              return response()->json($response);
            }

            $template = PublicHoliday::create($input);

            $response['success'] = true;
            $response['msg'] = __('messages.addSuccess', ['name' => 'Public Holiday']);
            $response['redirect_url'] = route('admin.public_holidays.index');
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
            $data = PublicHoliday::findOrFail($id);
            return view('admin.public_holidays.edit')->with(compact('data','isNew'));
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

            $checkDuplicate = PublicHoliday::where('id', '!=', $id)->where('name', $request->name)->first();

            if($checkDuplicate) {
              $response['success'] = false;
              $response['msg'] = 'The holiday name already exists!';
              return response()->json($response);
            }

            $tableData = PublicHoliday::where('id', $id)->first();

            $tableData->update($input);

            $response['success'] = true;
            $response['msg'] = __('messages.updateSuccess', ['name' => 'Public Holiday']);
            $response['redirect_url'] = route('admin.public_holidays.index');
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
            $data = PublicHoliday::where( 'id', $id )->first();

            $data->delete();

            $response['success'] = true;
            $response['msg'] = __('messages.deleteSuccess', ['name' => 'Public Holiday']);
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
            $holidays = PublicHoliday::whereIn('id', $ids)->get();

            foreach ($holidays as $holiday) {
                $holiday->delete();
            }

            $response['success'] = true;
            $response['msg'] = __('messages.deleteSuccess', ['name' => 'Public Holiday']);
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

    public function block($id)
    {
        try {
            $data = PublicHoliday::where('id',$id)->first();
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
            $data = PublicHoliday::where('id',$id)->first();
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
