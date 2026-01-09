<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use App\Models\Resort;
use App\Models\Notification;
use App\Helpers\Common;
use File;
use DB;
use Config;
use Event;
use App\Notifications\NewMessageNotification;
use App\Events\ResortNotificationEvent;
class NotificationController extends Controller
{

    public function index()
    {
        // return view('admin.manufecturers.index');
        $data = new Notification;
        // dd($data);
        return view('admin.notifications.index',compact('data'));
    }

    public function list()
    {
        $notifications = Notification::select([
            'id', 'name', 'content', 'start_date', 'end_date', 'font_color',
            'notice_color', 'sticky', 'status', 'created_at', 'updated_at','created_by'
        ])
        ->orderBy('created_at', 'DESC')
        ->get();

        return datatables()->of($notifications)
            // Checkbox column for multi-select
            ->addColumn('checkbox', function ($notification) {
                return '<input type="checkbox" name="notice_checkbox[]" class="notice_checkbox" value="'.$notification->id.'" />';
            })

            // Theme column: Text with font color and notice background
            ->editColumn('theme', function ($notification) {
                return '<a href="javascript:void(0)" class="inactive-status" title="Notification Theme" >
                            <span class="badge" style="color:'.$notification->font_color.';background:'.$notification->notice_color.'">Texts</span>
                        </a>';
            })

            // Sticky column: Display badge for sticky status
            ->editColumn('sticky', function ($notification) {
                if ($notification->sticky === "no") {
                    return '<a href="javascript:void(0)" class="inactive-status" title="'.$notification->sticky.'" >
                                <span class="badge badge-danger">'.$notification->sticky.'</span>
                            </a>';
                } else {
                    return '<a href="javascript:void(0)" class="active-status" title="'.$notification->sticky.'" >
                                <span class="badge badge-success">'.$notification->sticky.'</span>
                            </a>';
                }
            })

            // Status column: Change status links (active/inactive)
            ->editColumn('status', function ($notification) {
                $inactive_url = route('admin.notifications.block', $notification->id);
                $active_url = route('admin.notifications.active', $notification->id);
                $cls = Common::hasPermission(config('settings.admin_modules.notifications'), config('settings.permissions.edit')) ? 'changeStatus' : '';

                if ($notification->status === "active") {
                    return '<a href="javascript:void(0)" class="active-status '.$cls.'" data-url="'.$inactive_url.'" title="Click here to deactivate">
                                <span class="badge badge-success">Active</span>
                            </a>';
                } else {
                    return '<a href="javascript:void(0)" class="inactive-status '.$cls.'" data-url="'.$active_url.'" title="Click here to activate">
                                <span class="badge badge-danger">Inactive</span>
                            </a>';
                }
            })

            // Action column: Edit and Delete buttons
            ->addColumn('action', function ($notification) {
                $edit_url = route('admin.notifications.edit', $notification->id);
                $delete_url = route('admin.notifications.destroy', $notification->id);
                $action_links = '<div class="btn-group">';

                // Edit Permission Check
                if (Common::hasPermission(config('settings.admin_modules.notifications'), config('settings.permissions.edit'))) {
                    $action_links .= '<a title="Edit" class="btn btn-info btn-sm mx-1" href="'.$edit_url.'">
                                        <i class="fas fa-pencil-alt"></i>
                                    </a>';
                }

                // Delete Permission Check
                if (Common::hasPermission(config('settings.admin_modules.notifications'), config('settings.permissions.delete'))) {
                    $action_links .= '<a title="Delete" class="btn btn-danger btn-sm mx-1 action-delete" href="javascript:void(0);" data-url="'.$delete_url.'">
                                        <i class="fas fa-trash"></i>
                                    </a>';
                }

                $action_links .= '</div>';
                return $action_links;
            })

            // Specify which columns contain HTML
            ->rawColumns(['checkbox', 'theme', 'sticky', 'status', 'action'])

            // Generate the response
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
            $data = new Notification();
            $isNew = 1;
            $resorts = Resort::all()->where('status','active');
            // dd($resorts);
            return view('admin.notifications.edit')->with(compact('data','isNew','resorts'));
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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string|max:1000',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'notice_color' => 'required|string', // Color inputs are string in hex format
            'font_color' => 'required|string',
            // 'sticky' => 'required|in:yes,no',
            'status' => 'required|in:active,inactive',
            'resorts' => 'required|array',
            'resorts.*' => 'exists:resorts,id', // Ensures resorts are valid
        ]);

        try {

            $checkDuplicate = Notification::where('name', $request->name)->first();

            if($checkDuplicate) {
              $response['success'] = false;
              $response['msg'] = 'The notification already exists!';
              return response()->json($response);
            }

            // Create a new notification
            $notification = new Notification();
            $notification->name = $request->name;
            $notification->content = $request->content;
            $notification->start_date = $request->start_date;
            $notification->end_date = $request->end_date;
            $notification->notice_color = $request->notice_color;
            $notification->font_color = $request->font_color;
            $notification->sticky = (isset($request->sticky)) ? $request->sticky:'No';
            $notification->status = $request->status;
            $notification->save();

            // Save associated resorts
            $notification->resorts()->sync($request->resorts);
            event(new ResortNotificationEvent(  Common::nofitication($request->input('resorts'),1)));

            $response['success'] = true;
            $response['msg'] = __('messages.addSuccess', ['name' => 'Notification']);
            $response['redirect_url'] = route('admin.notifications.index');
            return response()->json($response);
        }
        catch( \Exception $e ) {
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
            $data = Notification::findOrFail($id);
            $resorts = Resort::all()->where('status','active');
            return view('admin.notifications.edit')->with(compact('data','isNew','resorts'));
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
        // try {
            // Validate the request
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'content' => 'required|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date',
                'notice_color' => 'required|string',
                'font_color' => 'required|string',
                // 'sticky' => 'required|in:yes,no',
                'status' => 'required|in:active,inactive',
                'resorts' => 'required|array', // Expecting array of resort IDs
            ]);

            // Find the notification and update it
            $notification = Notification::findOrFail($id);
            $notification->update($validatedData);
            // Sync the resorts (many-to-many)

                    $notification->resorts()->sync($request->input('resorts'));

            event(new ResortNotificationEvent( Common::nofitication($request->input('resorts'),1)));

            $response['success'] = true;
            $response['msg'] = __('messages.updateSuccess', ['name' => 'Notification']);
            $response['redirect_url'] = route('admin.notifications.index');
            return response()->json($response);
        // } catch (\Exception $e) {
        //     \Log::emergency( "File: ".$e->getFile() );
        //     \Log::emergency( "Line: ".$e->getLine() );
        //     \Log::emergency( "Message: ".$e->getMessage() );

        //     $response['success'] = false;
        //     $response['msg'] = $e->getMessage();
        //     return response()->json($response);
        // }
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
            $notification = Notification::findOrFail($id);

            // Detach related resorts from the notification
            $notification->resorts()->detach();

            // Delete the notification
            $notification->delete();

            // Return a successful response
            $response['success'] = true;
            $response['msg'] = __('messages.deleteSuccess', ['name' => 'Notification']);
            return response()->json($response);
        } catch (\Exception $e) {
            // Log the error details
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());

            // Return an error response
            $response['success'] = false;
            $response['msg'] = __('messages.deleteFailed', ['name' => 'Notification']);
            return response()->json($response);
        }
    }

    public function massremove(Request $request)
    {
        try {
            $ids = $request->input('id');

            // Fetch notifications by their IDs
            $data = Notification::whereIn('id', $ids)->get();

            // Loop through each notification
            foreach ($data as $notification) {
                // Detach related resorts (if any)
                $notification->resorts()->detach();

                // Delete the notification
                $notification->delete();
            }

            $response['success'] = true;
            $response['msg'] = __('messages.deleteSuccess', ['name' => 'Notifications']);
            return response()->json($response);
        } catch (\Exception $e) {
            // Log error details
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());

            $response['success'] = false;
            $response['msg'] = __('messages.deleteFailed', ['name' => 'Notifications']);
            return response()->json($response);
        }
    }

    public function block($id)
  {
    try {
      $data = Notification::where('id',$id)->first();
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
      $data = Notification::where('id',$id)->first();
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
