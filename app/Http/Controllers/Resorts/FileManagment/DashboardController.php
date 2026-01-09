<?php

namespace App\Http\Controllers\Resorts\FileManagment;


use DB;
use URL;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\FilemangementSystem;
use Illuminate\Http\Request;
use App\Models\ChildFileManagement;
use App\Models\FilePermissions;
use App\Models\AuditLogs;
use App\Models\FileVersion;



class DashboardController extends Controller
{
    protected $resort;
    protected $underEmp_id=[];
    public function __construct()
    {
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        $reporting_to  = isset($this->globalUser->GetEmployee) ? $this->globalUser->GetEmployee->id:3;

        $this->underEmp_id = Common::getSubordinates($reporting_to);
    }


    public function Admin_Dashobard(Request $request)
    {
        $page_title ="File Managment";
        
        $FolderList = FilemangementSystem::where('resort_id', $this->resort->resort_id)
        // ->where('UnderON', 0)
        ->where("Folder_Type", "uncategorized")
        ->orderByDesc('id')
        ->get();

        $UnassignedDocumentsCounts = ChildFileManagement::join("filemangement_systems as fp", "fp.id", "=", "child_file_management.Parent_File_ID")
                                                        ->leftJoin("file_permissions as perms", "perms.file_id", "=", "child_file_management.unique_id") // Check if file has permissions
                                                        ->where('fp.resort_id', $this->resort->resort_id)
                                                        ->where("fp.Folder_Type", "uncategorized")
                                                        ->whereNull("perms.file_id") // Ensure the file is unassigned
                                                        ->orderByDesc('fp.id')
                                                        ->count();
        $FolderCount = FilemangementSystem::where('resort_id', $this->resort->resort_id)
                                                    ->orderByDesc('id')
                                                    ->count();
        $TotalDocument = ChildFileManagement::join("filemangement_systems as fp", "fp.id", "=", "child_file_management.Parent_File_ID")
                                                    ->where('fp.resort_id', $this->resort->resort_id)
                                                    ->orderByDesc('fp.id')
                                                    ->count();
        $colors = ['#014653','#53CAFF','#EFB408','#2EACB3','#333333','#8DC9C9','#7AD45A','#FF4B4B','#F5738D', '#0E8509'];
        $i=0;
        $FolderFiles = FilemangementSystem::where('resort_id', $this->resort->resort_id)
                                            ->orderByDesc('id')
                                            ->where("Folder_Type", "uncategorized")
                                            ->get(['Folder_Name','id'])->map(function($ak) use(&$i,$colors)
                                            {
                                                if ($i < count($colors)) 
                                                {
                                                    $ak->color = $colors[$i]; // Assign color if available
                                                }
                                                else 
                                                {
                                                    $i=0; 
                                                    $ak->color = $colors[$i]; 
                                                }
                                                $i++; 
                                                $ak->Folder_Name;
                                                $ak->Folder_Files_count = ChildFileManagement::where('Parent_File_ID', $ak->id)->where('resort_id', $this->resort->resort_id)->count();
                                                return $ak;
                                            });
        return view('resorts.FileManagment.dashboard.admindashboard',compact('page_title','FolderList','UnassignedDocumentsCounts','FolderCount','TotalDocument','FolderFiles'));
    
    }

    
    public function HR_Dashobard()
    {
       
        $page_title ="File Managment";
        
        $FolderList = FilemangementSystem::where('resort_id', $this->resort->resort_id)
        // ->where('UnderON', 0)
        ->where("Folder_Type", "uncategorized")
        ->orderByDesc('id')
        ->get();

        $UnassignedDocumentsCounts = ChildFileManagement::join("filemangement_systems as fp", "fp.id", "=", "child_file_management.Parent_File_ID")
                                                        ->leftJoin("file_permissions as perms", "perms.file_id", "=", "child_file_management.unique_id") // Check if file has permissions
                                                        ->where('fp.resort_id', $this->resort->resort_id)
                                                        ->where("fp.Folder_Type", "uncategorized")
                                                        ->whereNull("perms.file_id") // Ensure the file is unassigned
                                                        ->orderByDesc('fp.id')
                                                        ->count();
        $FolderCount = FilemangementSystem::where('resort_id', $this->resort->resort_id)
                                                    ->orderByDesc('id')
                                                    ->count();
        $TotalDocument = ChildFileManagement::join("filemangement_systems as fp", "fp.id", "=", "child_file_management.Parent_File_ID")
                                                    ->where('fp.resort_id', $this->resort->resort_id)
                                                    ->orderByDesc('fp.id')
                                                    ->count();
        $colors = ['#014653','#53CAFF','#EFB408','#2EACB3','#333333','#8DC9C9','#7AD45A','#FF4B4B','#F5738D', '#0E8509'];
        $i=0;
        $FolderFiles = FilemangementSystem::where('resort_id', $this->resort->resort_id)
                                            ->orderByDesc('id')
                                            ->where("Folder_Type", "uncategorized")
                                            ->get(['Folder_Name','id'])->map(function($ak) use(&$i,$colors)
                                            {
                                                if ($i < count($colors)) 
                                                {
                                                    $ak->color = $colors[$i]; // Assign color if available
                                                }
                                                else 
                                                {
                                                    $i=0; 
                                                    $ak->color = $colors[$i]; 
                                                }
                                                $i++; 
                                                $ak->Folder_Name;
                                                $ak->Folder_Files_count = ChildFileManagement::where('Parent_File_ID', $ak->id)->where('resort_id', $this->resort->resort_id)->count();
                                                return $ak;
                                            });
        return view('resorts.FileManagment.dashboard.hrdashboard',compact('page_title','FolderList','UnassignedDocumentsCounts','FolderCount','TotalDocument','FolderFiles'));
    }
    public function GetUncategorizedDoc(Request $request)
    {
        $mergedFiles = collect();
        $flag='uncategorized'; 
        $ChildFiles = ChildFileManagement::join('filemangement_systems as t1', 't1.id', '=', 'child_file_management.Parent_File_ID')
            ->where('t1.Folder_Type', 'uncategorized')
            ->where('t1.resort_id', $this->resort->resort_id)
            ->get(['child_file_management.*'])
            ->map(function ($i) {
                $imgExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];
                $docExtensions = ['doc', 'docx', 'xls', 'xlsx', 'csv', 'ppt', 'pptx', 'txt', 'rtf'];
                $zipExtensions = ['zip', 'rar', '7z', 'tar', 'gz'];
                $audioExtensions = ['mp3', 'wav', 'ogg', 'm4a'];
                $videoExtensions = ['mp4', 'avi', 'mkv', 'mov', 'wmv'];

                $i->new_id = base64_encode($i->id);
                $i->File_Name = htmlspecialchars($i->File_Name, ENT_QUOTES, 'UTF-8');
                $i->created_by = \Carbon\Carbon::parse($i->created_by)->format('d-m-Y');
                $i->Permission = URL::asset('resorts_assets/images/user-4.svg');
                $i->File_Size = $i->File_Size . ' KB';

                $ext = strtolower($i->File_Extension);
                if (in_array($ext, $imgExtensions)) {
                    $img = URL::asset('resorts_assets/images/image.svg');
                } elseif (in_array($ext, $docExtensions)) {
                    $img = URL::asset('resorts_assets/images/word.svg');
                } elseif (in_array($ext, $zipExtensions)) {
                    $img = URL::asset('resorts_assets/images/zip.svg');
                } elseif (in_array($ext, $audioExtensions)) {
                    $img = URL::asset('resorts_assets/images/audio.svg');
                } elseif (in_array($ext, $videoExtensions)) {
                    $img = URL::asset('resorts_assets/images/video.svg');
                } elseif ($ext === 'pdf') {
                    $img = URL::asset('resorts_assets/images/pdf1.svg');
                } else {
                    $img = URL::asset('resorts_assets/images/default.svg');
                }

                $i->NewURL = 'InternaFile'; // Placeholder
                $i->File_img = $img;

                return $i;
            })
            ->each(function ($file) use ($mergedFiles, $flag) {
                
                $filePermission = Common::FilePermissions($file->unique_id,$this->resort);
                if (isset($filePermission['type']) && $filePermission['type'] === true) {
                    $emp = '<div class="user-ovImg user-ovImgTable">';
                    if (!empty($filePermission['emp'])) {
                        foreach ($filePermission['emp'] as $f) {
                            $emp .= '<div class="img-circle"> <img src="' . $f['profile'] . '"></div>';
                        }
                    }
                    $emp .= '</div>';

                    $mergedFiles->push([
                        'id' => $file->id,
                        'unique_id' => $file->unique_id,
                        'new_id' => $file->new_id,
                        'File_Name' => $file->File_Name,
                        'File_Size' => $file->File_Size,
                        'created_by' => $file->created_by,
                        'Permission' => $emp,
                        'File_img' => $file->File_img,
                        'Type' => 'file',
                        'created_at' => Carbon::parse($file->created_at)->format('d-m-Y H:i:s'),
                    ]);
                }
            });

        $mergedFiles = $mergedFiles->values();

        if ($request->ajax()) {
            return datatables()
                ->of($mergedFiles)
                ->editColumn('FileName', fn($row) => $row['File_Name'])
                ->editColumn('UploadDate', fn($row) => $row['created_by'])
                ->editColumn('Permission', fn($row) => $row['Permission']) 
                ->rawColumns(['File_Name', 'UploadDate', 'Permission'])
                ->make(true);
        }
      
    }
    public function AuditLogsDashboardList(Request $request)
    {
        
        $ChildFiles = AuditLogs::join('child_file_management as t1', 't1.id', '=', 'audit_logs.file_id')
        ->where('audit_logs.resort_id', $this->resort->resort_id)
        ->whereDate('audit_logs.created_at', Carbon::today())
        ->orderBy('audit_logs.id', 'DESC')
        ->limit(20)
        ->groupBy('audit_logs.id')
            ->get(['t1.File_Name as FileName', 'audit_logs.*'])
            ->map(function($i) {
                $i->ModifiedBy = Common::getResortUserPicture($i->created_by); 
                $i->Time = $i->created_at->format('H:i:s');
                $i->LastModified = $i->created_at->format('d-m-Y');
                $i->ActionType = $i->TypeofAction;
                return $i;
            });
            if ($request->ajax()) 
            {
                return datatables()->of($ChildFiles)
                    ->editColumn('ActionType', function ($row) {
                        return $row->TypeofAction;
                    })
                    ->editColumn('FileName', function ($row) {
                        return $row->FileName;
                    })
                    ->editColumn('ModifiedBy', function ($row) 
                    {
                        $imgUrl = $row->ModifiedBy ?? asset('resorts_assets/images/user-2.svg');
                        
                        return '<div class="user-ovImg user-ovImgTable"><div class="img-circle">
                                    <img src="'.$imgUrl.'" alt="user">
                                </div></div>';
                    })
                    ->editColumn('LastModified', function ($row) {
                        return $row->LastModified;
                    })
                    ->editColumn('Time', function ($row) {
                        return $row->Time;
                    })
                    ->rawColumns(['ActionType', 'FileName', 'ModifiedBy', 'LastModified', 'Time'])
                    ->make(true);
            }
            $page_title = "Audit Logs";

    }
    public function FileVersionDashboardList(Request $request)
    {
        
        $existingVersion = FileVersion::join('child_file_management as t1', 't1.id', '=', 'file_versions.file_id')
                                        ->join('resort_admins as t2', 't2.id', '=', 'file_versions.created_by')
                                        ->where('file_versions.resort_id', $this->resort->resort_id)
                                        ->whereDate('file_versions.created_at', Carbon::today())
                                        ->orderBy('file_versions.version_number', 'desc')
                                        ->limit(20)
                                        ->get(['t2.first_name','t2.last_name',
                                        't1.Parent_File_ID',
                                        't1.File_Name',
                                        't1.File_Type',
                                        't1.File_Size',
                                        't1.File_Path',
                                        't1.File_Extension',
                                        't1.File_Name as FileName',
                                        't1.NewFileName',
                                        'file_versions.*'])
                                        ->map(function($i)
                                        {
                                            $i->FileName =  !empty($i->NewFileName) ?  $i->NewFileName : $i->FileName;
                                            $i->ModifiedBy = $i->first_name.' '.$i->last_name; 
                                            $i->Time = $i->created_at->format('H:i:s');
                                            $i->Timestamp = $i->created_at->format('d M Y H:i A');
                                            $i->Size = $i->File_Size .' KB';
                                            $imgExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];
                                            $docExtensions = ['doc', 'docx',  'xls', 'xlsx', 'csv', 'ppt', 'pptx', 'txt', 'rtf'];
                                            $zipExtensions = ['zip', 'rar', '7z', 'tar', 'gz'];
                                            $audioExtensions = ['mp3', 'wav', 'ogg', 'm4a'];
                                            $videoExtensions = ['mp4', 'avi', 'mkv', 'mov', 'wmv'];
                                            $img='';
                                            if (in_array($i->File_Extension, $imgExtensions)) 
                                            {
                                                $img = URL::asset('resorts_assets/images/image.svg'); // Image icon
                                            } 
                                            elseif (in_array($i->File_Extension, haystack: $docExtensions)) 
                                            {
                                                $img = URL::asset('resorts_assets/images/word.svg'); // Document icon
                                            } elseif (in_array($i->File_Extension, $zipExtensions)) {
                                                $img = URL::asset('resorts_assets/images/zip.svg'); // Archive icon
                                            } 
                                            elseif (in_array($i->File_Extension, $audioExtensions)) 
                                            {
                                                $img = URL::asset('resorts_assets/images/audio.svg'); // Audio file icon
                                            } elseif (in_array($i->File_Extension, $videoExtensions)) 
                                            {
                                                $img = URL::asset('resorts_assets/images/video.svg'); // Video file icon
                                            } 
                                            elseif ($i->File_Extension ==  "pdf") {
                                                $img = URL::asset('resorts_assets/images/pdf1.svg'); // Video file icon
                                            } 
                                            
                                            else {
                                                $img = URL::asset('resorts_assets/images/default.svg'); // Default icon
                                            }
                                        
                                                $i->NewURL = "InternaFile";// URL valid for 10 minutes
                                        
                                            $i->unique_id = $i->unique_id;
                                            $i->File_img = $img;
                                            return $i;
                                        });
               
                                if ($request->ajax()) {
                                    return datatables()->of($existingVersion)
                                   
                                        ->editColumn('FileName', function ($row) {
                                            return '<img src="'.$row->File_img.'" alt="images" class="me-2"> '. $row->FileName;
                                        })
                                        ->editColumn('ModifiedBy', function ($row) 
                                        {
                                           return $row->ModifiedBy;
                                        })
                                        ->editColumn('Timestamp', function ($row) {
                                            return $row->Timestamp;
                                        })
                                        ->editColumn('Size', function ($row) {
                                            return $row->Size;
                                        })
                                        ->rawColumns(['FileName', 'ModifiedBy', 'Timestamp', 'Size'])
                                        ->make(true);
                                }
                                $page_title = "File Version History";
        
    }

    
}
