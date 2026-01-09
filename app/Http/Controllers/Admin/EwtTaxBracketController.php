<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use App\Models\EwtTaxBracket;
use App\Helpers\Common;
use File;
use DB;

class EwtTaxBracketController extends Controller
{

    public function index()
    {
        // return view('admin.manufecturers.index');
        $data = new EwtTaxBracket;
        // dd($data);
        return view('admin.ewt.index',compact('data'));
    }

    public function list()
    {
        $taxes = EwtTaxBracket::select(['id', 'min_salary', 'max_salary', 'tax_rate', 'created_by', 'created_at', 'updated_at'])
            ->orderBy('created_at', 'DESC')
            ->get();

        return datatables()->of($taxes)
            ->addColumn('checkbox', function ($tax) {
                return '<input type="checkbox" name="ewt_checkbox[]" class="ewt_checkbox" value="'.$tax->id.'" />';
            })
            ->addColumn('action', function ($tax) {
                $edit_url = route('admin.ewt_brackets.edit', $tax->id);
                $delete_url = route('admin.ewt_brackets.destroy', $tax->id);

                $action_links = '<div class="btn-group">';

                if (Common::hasPermission(config('settings.admin_modules.ewt_brackets'), config('settings.permissions.edit'))) {
                    $action_links .= '<a title="Edit" class="btn btn-info btn-sm mx-1" href="'.$edit_url.'"><i class="fas fa-pencil-alt"></i></a>';
                }

                if (Common::hasPermission(config('settings.admin_modules.ewt_brackets'), config('settings.permissions.delete'))) {
                    $action_links .= '<a title="Delete" class="btn btn-danger btn-sm mx-1 action-delete" href="javascript:void(0);" data-url="'.$delete_url.'"><i class="fas fa-trash"></i></a>';
                }

                $action_links .= '</div>';

                return $action_links;
            })
            ->rawColumns(['checkbox', 'action']) // Ensure these columns render HTML properly
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
            $data = new EwtTaxBracket();
            $isNew = 1;
            return view('admin.ewt.edit')->with(compact('data','isNew'));
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
                'min_salary' => 'required|numeric|min:0',
                'max_salary' => 'nullable|numeric|min:0',
                'tax_rate' => 'required|numeric|min:0|max:100',
            ]);
            $input = $request->except(['_token']);

            $template = EwtTaxBracket::create($input);

            $response['success'] = true;
            $response['msg'] = __('messages.addSuccess', ['name' => 'Ewt Brackets']);
            $response['redirect_url'] = route('admin.ewt_brackets.index');
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
            $data = EwtTaxBracket::findOrFail($id);
            return view('admin.ewt.edit')->with(compact('data','isNew'));
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
                'min_salary' => 'required|numeric|min:0',
                'max_salary' => 'nullable|numeric|min:0',
                'tax_rate' => 'required|numeric|min:0|max:100',
            ]);
            $input = $request->except(['_token']);

            $tableData = EwtTaxBracket::where('id', $id)->first();

            $tableData->update($input);

            $response['success'] = true;
            $response['msg'] = __('messages.updateSuccess', ['name' => 'Ewt Brackets']);
            $response['redirect_url'] = route('admin.ewt_brackets.index');
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
            $data = EwtTaxBracket::where( 'id', $id )->first();

            $data->delete();

            $response['success'] = true;
            $response['msg'] = __('messages.deleteSuccess', ['name' => 'Ewt Brackets']);
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
            $data = EwtTaxBracket::whereIn('id', $ids)->get();

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
