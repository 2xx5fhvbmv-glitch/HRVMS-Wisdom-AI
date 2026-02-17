<?php

namespace App\Http\Controllers\Resorts\Payroll;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shopkeeper;
use App\Models\PayrollConfig;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Helpers\Common;
use Auth;
use Config;
use DB;

class ShopkeeperController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
    }

    public function index()
    {
        if(Common::checkRouteWisePermission('shopkeepers.create',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        $page_title ='Shopkeeper';
        $resort_id = $this->resort->resort_id;
        return view('resorts.payroll.shopkeeper.index',compact('page_title'));
    }

    public function create()
    {
        if(Common::checkRouteWisePermission('shopkeepers.create',config('settings.resort_permissions.create')) == false){

            if(Common::checkRouteWisePermission('shopkeepers.create',config('settings.resort_permissions.view'))){
                return redirect()->route('shopkeepers.index');
            }else{

                return abort(403, 'Unauthorized access');
            }
        }

        $page_title ='Create Shopkeeper';
        $resort_id = $this->resort->resort_id;
        return view('resorts.payroll.shopkeeper.create',compact('page_title'));
    }

    public function list(Request $request)
    {
        if ($request->ajax()) {
            $resort_id = $this->resort->resort_id;
            $query = Shopkeeper::where('resort_id', $resort_id)
                ->orderBy('updated_at', 'DESC');

            if ($request->searchTerm && $request->searchTerm != '') {

                $query->where(function($q) use ($request) {
                    $q->where('name', 'like', '%'.$request->searchTerm.'%')
                          ->orWhere('email', 'like', '%'.$request->searchTerm.'%')
                          ->orWhere('contact_no', 'like', '%'.$request->searchTerm.'%');
                });
            }
            $tableData = $query->get();

            $edit_class = '';
            $delete_class = '';
            if(Common::checkRouteWisePermission('shopkeepers.create',config('settings.resort_permissions.edit')) == false){
                $edit_class = 'd-none';
            }
            if(Common::checkRouteWisePermission('shopkeepers.create',config('settings.resort_permissions.delete')) == false){
                $delete_class = 'd-none';
            }

            return datatables()->of($tableData)
            ->addColumn('action', function ($row) use ($edit_class,$delete_class) {
                return '
                    <div class="d-flex align-items-center">
                        <a href="#" class="btn-lg-icon icon-bg-green me-1 edit-row-btn '.$edit_class.'"
                        data-shopkeeper-id="' . htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8') . '">
                            <img src="' . asset('resorts_assets/images/edit.svg') . '" alt="" class="img-fluid" />
                        </a>
                        <a href="#" class="btn-lg-icon icon-bg-red delete-row-btn '.$delete_class.'"
                        data-shopkeeper-id="' . htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8') . '">
                            <img src="' . asset('resorts_assets/images/trash-red.svg') . '" alt="" class="img-fluid" />
                        </a>
                    </div>';
            })
            ->escapeColumns(['action'])
            ->make(true);
        }
    }

    public function store(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        $validator = Validator::make($request->all(), 
        [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'contact_no' => 'required|string|regex:/^\+?[0-9]{7,15}$/',
        ],
        [
            'name.required' => 'Please enter name',
            'email.required' => 'Please enter email',
             'contact_no.required' => 'Please enter contact no'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $generatedPassword = Str::random(10); // Generate a 10-character random string
        // Hash the password before saving it to the database
        $hashedPassword = Hash::make($generatedPassword);
        $check = Shopkeeper::where('resort_id', $resort_id)
            ->where('email', $request->email)
            ->first();
        if($check) {
            return response()->json(['success' => false, 'msg' => 'Shopkeeper with this email already exists.']);
        }

        $shopkeeper = Shopkeeper::create([
            'resort_id' => $resort_id,
            'name' => $request->name,
            'email' => $request->email,
            'password'=> $hashedPassword,
            'contact_no' => $request->contact_no,
        ]);

        if($shopkeeper)
        {
            $shopkeeper->sendShopkeeperRegistrationEmail($shopkeeper, $generatedPassword);
        }

        return response()->json([
            'success' => true,
            'msg' => 'Shopkeeper Created Successfully and login credentials sent to shopkeeper.',
            'redirect_url' => route('shopkeepers.index')
        ]);
    }

    public function inlineUpdate(Request $request, $id)
    {
        // Find the division by ID
        $shopkeeper = Shopkeeper::find($id);

        if (!$shopkeeper) {
            return response()->json(['success' => false, 'message' => 'shopkeeper not found.']);
        }

        // Validate incoming request
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'contact_no' => 'required|string|regex:/^\+?[0-9]{7,15}$/',
        ]);

        try {
            // Update the division's attributes
            $shopkeeper->name = $request->input('name');
            $shopkeeper->email = ucwords($request->input('email'));
            $shopkeeper->contact_no = $request->input('contact_no');
            
            // Save the changes
            $shopkeeper->save();

            // Return a JSON response
            return response()->json(['success' => true, 'message' => 'Shopkeeper updated successfully.']);
        } catch( \Exception $e ) {
            \Log::emergency( "File: ".$e->getFile() );
            \Log::emergency( "Line: ".$e->getLine() );
            \Log::emergency( "Message: ".$e->getMessage() );
      
            return response()->json(['success' => false, 'message' => 'Failed to update shopkeeper.']);
        }
    }

    public function destroy($id)
    {
        try {
            $shopkeeper = Shopkeeper::findOrFail($id);
            $shopkeeper->delete();  // Soft delete if you're using soft deletes, otherwise use forceDelete()

            return response()->json(['success' => true, 'message' => 'Shopkeeper deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete shopkeeper.']);
        }
    }
}