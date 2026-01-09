<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use App\Models\ResortPagewisePermission;
use App\Models\ResortDepartment;
use App\Models\ResortPosition;
use App\Models\ResortModule;
use App\Models\ResortDivision;
use App\Models\ResortInteralPagesPermission;
use App\Models\ModulePages;
use App\Models\Modules;
use DB;
use App\Imports\EmpoyeeImport;
use Validator;
class ResortInternalPermission extends Controller
{
    public $resortdata = '';

    public function __construct()
    {
        $this->resortdata = Auth::guard('resort-admin')->user();

    }
    public function Permissionpage()
    {
        try {
            $page_title = 'resorts Permission';
            $resort_id =$this->resortdata->resort_id;
            $ResortDivision = ResortDivision::where('resort_id',$resort_id)->where('status', 'active')->orderBy("id","desc")->get(['id', 'name']);
            return view('resorts.Permission.index',compact('ResortDivision'));
        } catch( \Exception $e ) {
            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
        }
        return view('resorts.Permission.index');
    }
    public function GetDivisionWiseDepartment(Request $request)
    {

        try{
            $resort_id = $this->resortdata->resort_id;

            $ResortDepartment = ResortDepartment::where('resort_id', $resort_id   )->where('division_id',$request->division_id)->where('status', 'active')->orderBy("id","desc")->get(['id', 'name']);

                    $response['success'] = ($ResortDepartment->isNotEmpty()) ? true : false;
                    $response['msg'] =($ResortDepartment->isNotEmpty()) ? 'Department found succefully' : 'Position Not found';  ;
                    $response['data'] = $ResortDepartment;



        }catch( \Exception $e ) {
            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
            $response['success'] = false;

            $response['msg'] = 'Please Select Department';
        }
        return response()->json($response);


    }
    public function GetDepartmentWisePosition(Request $request)
    {

        try{
            $resort_id = $this->resortdata->resort_id;




            $Position = ResortPosition::where('resort_id', $resort_id   )->where('dept_id',$request->deptId)->where('status', 'active')->orderBy("id","desc")->get(['id', 'position_title']);


                    $response['success'] = ($Position->isNotEmpty()) ? true : false;
                    $response['msg'] =($Position->isNotEmpty()) ? 'Position found succefully' : 'Position Not found';  ;
                    $response['data'] = $Position;



        }catch( \Exception $e ) {
            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
            $response['success'] = false;

            $response['msg'] = 'Please Select Department';
        }
        return response()->json($response);


    }

    public function InternalPermissiones(Request $request)
    {
        $deptId= $request->deptId;
        $position_id = $request->position_id;
        $resort_id= $this->resortdata->resort_id;


        // Get modules that the resort has access to (has at least one page permission)
        $resortModules = ResortPagewisePermission::where('resort_id', $resort_id)
            ->distinct()
            ->pluck('Module_id')
            ->toArray();

        // Get all active pages for these modules from ModulePages
        $allModulePages = ModulePages::whereIn('Module_Id', $resortModules)
            ->where('status', 'Active')
            ->whereNull('deleted_at')
            ->with('Modules')
            ->orderBy('Module_Id')
            ->orderBy('place_order')
            ->get();

        // Build ModuleWisePermission array from all module pages
        $ModuleWisePermission = [];
        foreach ($allModulePages as $page) {
            if(isset($page->Modules)) {
                $ModuleWisePermission[$page->Module_Id]['module'] = $page->Modules;
                $ModuleWisePermission[$page->Module_Id]['permissions'][] = [
                    'id' => $page->id,
                    'module_page' => $page
                ];
            }
        }
            //  check exiting Permission
        $existingPermissions = ResortInteralPagesPermission::where('resort_id', $resort_id)
        ->where('Dept_id', $deptId)
        ->where('position_id', $position_id)
        ->orderBy("id", "asc")->get();

        $ModuleWiseExitingPermissions=array();
        foreach($existingPermissions as $per)
        {
            $ModuleWiseExitingPermissions[$per->page_id][]=$per->Permission_id;
        }
        $html= view('resorts.renderfiles.ResortPermissionRender',compact('ModuleWisePermission','ModuleWiseExitingPermissions'))->render();
        $response['success'] = true;
        $response['msg'] ='Data ';
        $response['html'] = $html;
        return response()->json($response);
    }

    public function UpdateInternalPermissions(Request $request)
    {

        DB::beginTransaction();
        try
        {
            $department = $request->department;
            $position = $request->position;
            $Resort_page_permissions = $request->input('Resort_page_permissions', []);

            if (empty($Resort_page_permissions))
            {
                // Optionally handle the case where no permissions are submitted
                $response['success'] = false;
                $response['msg'] = __('Please Select Page give Poisition permission', ['name' => 'Permission']);
                return response()->json($response);
            }
            $resort_id = $this->resortdata->resort_id;
            $existingPermissions = ResortInteralPagesPermission::where('resort_id', $resort_id)
                ->where('Dept_id', $department)
                ->where('position_id', $position)
                ->get()
                ->groupBy( 'Module_id');

            $pageid = array_keys($Resort_page_permissions);

            if($existingPermissions->isNotEmpty())
            {
                $ResortInteralPagesPermission =  ResortInteralPagesPermission::where('resort_id', $resort_id)
                ->where("Dept_id", $department)
                ->where('position_id', $position)
                ->delete();

            }

            foreach ($Resort_page_permissions as $pageId=>$modulePermissionId)
            {
                foreach($modulePermissionId as $permissionId)
                {
                    ResortInteralPagesPermission::create([
                        'resort_id' => $resort_id,
                        'Dept_id' => $department,
                        'position_id'=>$position,
                        'page_id' => $pageId,
                        'Permission_id' => $permissionId,
                    ]);
                }

            }



            DB::commit();
            $response['success'] = true;
            $response['msg'] = __('Resort Internal Page Permission granted successfully', ['name' => 'Permission']);
            return response()->json($response);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            $response['success'] = false;
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            $response['msg'] = __('Somthing Wrong ', ['name' => 'Permission']);
            return response()->json($response);
        }
    }

    public function SearchPermissions(Request $request)
    {

        $validator = Validator::make($request->all(), [

            'department' => 'required',
            'position' => 'required',
        ],
            [
            'department.required' => 'Please  Select department.',
            'position.required' => 'Please  Select  position.',
        ]);


        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $resort_id= $this->resortdata->resort_id;
        $SearchValue = $request->SearchValue;
        // $divisionId = $request->divisionId;
        $department_id = $request->department;
        $Position_id = $request->position;


        // Get modules that the resort has access to (has at least one page permission)
        $resortModules = ResortPagewisePermission::where('resort_id', $resort_id)
            ->distinct()
            ->pluck('Module_id')
            ->toArray();

        // Get all active pages for these modules from ModulePages that match search
        $allModulePages = ModulePages::whereIn('Module_Id', $resortModules)
            ->where('status', 'Active')
            ->whereNull('deleted_at')
            ->where(function($query) use ($SearchValue) {
                $query->where('page_name', 'LIKE', '%' . $SearchValue . '%')
                      ->orWhereHas('Modules', function ($q) use ($SearchValue) {
                          $q->where('module_name', 'LIKE', '%' . $SearchValue . '%');
                      });
            })
            ->with('Modules')
            ->orderBy('Module_Id')
            ->orderBy('place_order')
            ->get();

        // Build ModuleWisePermission array from all module pages
        $ModuleWisePermission = [];
        foreach ($allModulePages as $page) {
            if(isset($page->Modules)) {
                $ModuleWisePermission[$page->Module_Id]['module'] = $page->Modules;
                $ModuleWisePermission[$page->Module_Id]['permissions'][] = [
                    'id' => $page->id,
                    'module_page' => $page
                ];
            }
        }
            //  check exiting Permission
        $existingPermissions = ResortInteralPagesPermission::where('resort_id', $resort_id)
            ->where('Dept_id', $department_id)
            ->where('position_id', $Position_id)
            ->orderBy("id", "asc")->get();

            $ModuleWiseExitingPermissions=array();
            foreach($existingPermissions as $per)
            {

                $ModuleWiseExitingPermissions[$per->page_id][]=$per->Permission_id;
            }
        $html= view( 'resorts.renderfiles.SearchPermission',compact( 'ModuleWisePermission','ModuleWiseExitingPermissions'))->render();
        $response['success'] = true;
        $response['msg'] ='Data ';
        $response['html'] = $html;
        return response()->json($response);
    }
}
