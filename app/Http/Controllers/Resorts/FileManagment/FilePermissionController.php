<?php

namespace App\Http\Controllers\Resorts\FileManagment;


use DB;
use URL;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FilemangementSystem;
use App\Models\ResortDivision;
use App\Models\ChildFileManagement;
use App\Models\FilePermissions;
use App\Models\ResortPosition;

class FilePermissionController extends Controller
{

    protected $resort;
    protected $underEmp_id=[];
    public function __construct()
    {
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        if(!$this->resort) return;
        $reporting_to  = isset($this->globalUser->GetEmployee) ? $this->globalUser->GetEmployee->id:3;
        $this->underEmp_id = Common::getSubordinates($reporting_to);
    }

   
    public function index()
    {
        if(Common::checkRouteWisePermission('FileManage.Permission',config('settings.resort_permissions.create')) == false){
            return abort(403, 'Unauthorized action.');
        }
        $FolderList = FilemangementSystem::where('resort_id', $this->resort->resort_id)
        // ->where('UnderON', 0)
        ->where("Folder_Type","uncategorized")
        ->orderByDesc('id')
        ->orderBY('Folder_name')
        ->get();
        $page_title="File Permission";
        $ResortDivision = ResortDivision::where('resort_id',$this->resort->resort_id)->where('status', 'active')->orderBy("id","desc")->get(['id', 'name']);
        return view('resorts.FileManagment.FilePermission.index',compact('page_title','FolderList','ResortDivision'));
    }
    public function GetPermissionfile(Request $request)
    {
        $folder_id = $request->folder_id;
        $position =  $request->position;
        $department =  $request->department;
        $FolderList = FilemangementSystem::where('resort_id', $this->resort->resort_id)->where("Folder_unique_id",$folder_id)->first();
        $files      = ChildFileManagement::where("Parent_File_ID",$FolderList->id)->get(['unique_id','File_Name','File_Size','updated_at'])->map(function($ak)
        {
            $ak->LastModified =  $ak->updated_at->format('d/m/Y');
            $ak->File_Size    =  $ak->File_Size.'  KB';
            return $ak;
        });
        
        $tr ='';

        if($files->isNotEmpty())
        {
            // dd($files);
            foreach($files as $file)
            {
                $FilePermissions = FilePermissions::where('resort_id',$this->resort->resort_id);
                if(isset($position))
                {
                    $FilePermissions->orwhereIn('Position_id',$position);
                }
                
                $FilePermissions  = $FilePermissions->where('Department_id',$department)
                ->where("file_id",$file->unique_id)
                ->first();
                $string='';
                if(isset($FilePermissions->id))
                {
                    $string="checked";
                }
                $tr.='<tr>
                        <td>
                            <div class="form-check">
                                <input class="form-check-input Resort_parent_checkbox"  name="Permission[]" type="checkbox" value="'.$file->unique_id.'"  '.$string.'>
                            </div>
                        </td>
                        <td>'.$file->File_Name.'</td>
                        <td>'.$file->File_Size.'</td>
                        <td>'.$file->LastModified.'</td>';
            }
        }
        else
        {
            $tr ="<tr><td colspan='4' style='text-align:center'>No Record Found..</td></tr> ";
        }
        return response()->json(['success' => true,'d' => $tr], 200);
    }

    public function StoreFilePermission(Request $request)
    {
       
        $department = $request->department;
        
        $department = $request->department;
        if(isset($request->position))
        {
            $positions = $request->position;
        }
        else
        {
            $positions = ResortPosition::where('resort_id',$this->resort->resort_id)->where("dept_id",$department)->pluck('id')->toArray();
        }
        $permissions =  $request->Permission;
        $FilePermissions = FilePermissions::where('resort_id',$this->resort->resort_id)
                        ->whereIn('Position_id',$positions)
                        ->where('Department_id',$department)
                        ->delete();
        $resortid = $this->resort->resort_id;
            foreach ($positions as $position_id) {
                foreach ($permissions as $permission_id) {
                    FilePermissions::create([
                        "resort_id"=>$resortid,
                        "Department_id"=>$department,
                        'Position_id' => $position_id,
                        'file_id' => $permission_id,
                    ]);
                }
            }

            return response()->json(['success' => true, 'message' => 'File Permission Updated successfully'], 200);

    }

    public function SearchPermissionfile(Request $request)
    {
      

        $searchTerm = $request->filename; // Single input for Filename, LastModified, or FileSize
        $position = $request->position;
        $department = $request->department;
        $FilePermissions = FilePermissions::join("child_file_management as t1", 't1.unique_id', "=", 'file_permissions.file_id')
        ->where('file_permissions.resort_id', $this->resort->resort_id)
        ->whereIn('file_permissions.Position_id', $position)
        ->where('file_permissions.Department_id', $department)
        ->when(!empty($searchTerm), function ($query) use ($searchTerm) {
            // Check if input is a date range (e.g., "01/03/2024 - 10/03/2024")
            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}\s*-\s*\d{1,2}\/\d{1,2}\/\d{4}$/', $searchTerm)) {
                list($startDate, $endDate) = explode('-', str_replace(' ', '', $searchTerm)); // Remove spaces
                $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($startDate))->format('Y-m-d');
                $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($endDate))->format('Y-m-d');
                return $query->whereBetween('t1.updated_at', [$startDate, $endDate]);
            }
            // Check if input is an exact date (e.g., "26/03/2024")
            elseif (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $searchTerm)) {
                $date = \Carbon\Carbon::createFromFormat('d/m/Y', $searchTerm)->format('Y-m-d');
                return $query->whereRaw("DATE_FORMAT(t1.updated_at, '%d/%m/%Y') LIKE ?", ["%{$searchTerm}%"]);
            }
            // Check if input is a numeric value (assume file size in KB)
            elseif (is_numeric($searchTerm)) {
                return $query->whereRaw("CAST(t1.File_Size AS DECIMAL(10,2)) LIKE ?", ["%{$searchTerm}%"]);
            }
            // Otherwise, assume it's a filename search
            else {
                return $query->where('t1.File_Name', 'LIKE', "%{$searchTerm}%");
            }
        })
        ->get()
        ->map(function ($ak) {
            $ak->LastModified = $ak->updated_at->format('d/m/Y');
            $ak->File_Size = $ak->File_Size . ' KB';
            return $ak;
        });
    
            $tr ='';
            if($FilePermissions->isNotEmpty())
            {
                foreach($FilePermissions as $file)
                {
                    $FilePermissions = FilePermissions::where('resort_id',$this->resort->resort_id)
                    ->whereIn('Position_id',$position)
                    ->where('Department_id',$department)
                    ->where("file_id",$file->unique_id)
                    ->first();
                    $string='';
                    if(isset($FilePermissions->id))
                    {
                        $string="checked";
                    }
                    $tr.='<tr>
                            <td>
                                <div class="form-check">
                                    <input class="form-check-input Resort_parent_checkbox"  name="Permission[]" type="checkbox" value="'.$file->unique_id.'"  '.$string.'>
                                </div>
                            </td>
                            <td>'.$file->File_Name.'</td>
                            <td>'.$file->File_Size.'</td>
                            <td>'.$file->LastModified.'</td>';
                }
            }
            else
            {
                $tr ="<tr><td colspan='4' style='text-align:center'>No Record Found..</td></tr> ";
            }
          
            return response()->json(['success' => true, 'd' => $tr], 200);     
    }
}

