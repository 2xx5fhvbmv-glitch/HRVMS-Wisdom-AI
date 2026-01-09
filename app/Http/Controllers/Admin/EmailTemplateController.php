<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\EmailTemplate;
use App\Helpers\Common;

use DB;

class EmailTemplateController extends Controller
{
  public function index()
  {
    $staffAccess = Auth::guard('admin')->user()->staff_only_access;
    return view('admin.email_templates.index')->with(compact('staffAccess'));
  }

  public function updateValidator($request)
  {
    return  $this->validate($request, [
      'name' => 'required|max:255',
      'subject' => 'required|max:255',
      'body' => 'required'
    ]);
  }

  public function emailTemplateList()
  {
    try {
      $email_templates = '';
      $email_templates = EmailTemplate::select('id','name','created_at','updated_at')->orderBy('created_at', 'DESC')->get();

      return datatables()->of($email_templates)
      ->editColumn('updated_at', function ($email_templates) {
        $date = strtotime($email_templates->updated_at);
        return ($email_templates->updated_at) ? '<span style="display:none">'.$date.'</span>' .date("d/m/Y",strtotime($email_templates->updated_at)) : '-';
      })
      ->addColumn('updated_at_normal', function ($email_templates) {
        return ($email_templates->updated_at) ? date("d/m/Y",strtotime($email_templates->updated_at)) : '-';
      })
      ->addColumn('action', function ($email_templates) {
        $edit_url = route('admin.emailTemplate.edit', $email_templates->id);
        if(Common::hasPermission(config('settings.admin_modules.email_templates'),config('settings.permissions.edit'))) {
          return '<a class="btn btn-info btn-sm action-edit" title="Edit" href="'.$edit_url.'"><i class="fas fa-pencil-alt"></i></a>';
        }
       
      })
      ->escapeColumns([])
      ->make(true);
    } catch( \Exception $e ) {
      \Log::emergency( "File: ".$e->getFile() );
      \Log::emergency( "Line: ".$e->getLine() );
      \Log::emergency( "Message: ".$e->getMessage() );
    }
  }

  public function create()
  {
    $data = new EmailTemplate;
    $isNew = 1;

    $admin_id = Auth::guard('admin')->id();
    $staffAccess = Auth::guard('admin')->user()->staff_only_access;
    return view('admin.email_templates.create')->with(compact('data','admin_id'));
  }

  public function store(Request $request)
  {
    try {
      $input = $request->except(['_token']);
      $input['created_at'] = date("Y-m-d H:i:s");
      $input['updated_at'] = date("Y-m-d H:i:s");

      $template = EmailTemplate::create($input);

      $response['success'] = true;
      $response['redirect_url'] = route('admin.emailTemplate.index');
      $response['msg'] = __('messages.addSuccess', ['name' => 'Email template']);
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

  public function emailTemplateEdit($id)
  {
    $data = EmailTemplate::findOrFail($id);
    $staffAccess = Auth::guard('admin')->user()->staff_only_access;
    return view('admin.email_templates.create')->with(compact('data','staffAccess'));
  }

  public function emailTemplateUpdate(Request $request, $id)
  {
    try {
      $input = $request->except(['_token','email']);
      $input['updated_at'] = now();

      $emailTemplate = EmailTemplate::findOrFail($id);

      $emailTemplate->update($input);

      $response['success'] = true;
      $response['redirect_url'] = route('admin.emailTemplate.index');
      $response['msg'] = __('messages.updateSuccess', ['name' => 'Template']);
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
