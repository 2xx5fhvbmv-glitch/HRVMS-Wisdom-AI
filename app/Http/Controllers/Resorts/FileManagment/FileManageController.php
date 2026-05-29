<?php

namespace App\Http\Controllers\Resorts\FileManagment;

use Str;
use DB;
use URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;
use App\Helpers\StorageHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FilemangementSystem;
use App\Models\ChildFileManagement;
use App\Models\FilePermissions;
use App\Models\Employee;
use App\Models\AuditLogs;
use App\Models\FileVersion;
use App\Models\ResortDepartment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Aws\S3\Exception\S3Exception;
use Exception;
use Barryvdh\DomPDF\Facade\Pdf;
class FileManageController extends Controller
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
    // ================================= Start of Uncategorized Folder ==========================//

        public function CreateFolder(Request $request)
        {
            $main_folder = $this->resort->resort->resort_id;
            $Folder_Name = $request->Folder_Name;
            $id          = isset($request->id) ?  base64_decode($request->id) : 0; 
            $resortId    = $this->resort->resort_id;

            
            if(!isset($id))
            {
                $validator = Validator::make($request->all(), [
                                        'Folder_Name' => [
                                            'required',
                                            'string',
                                            'max:255',
                                            Rule::unique('filemangement_systems')->where(function ($query) {
                                                return $query->where('resort_id', $this->resort->resort_id)
                                                            ->where('Folder_Type', 'uncategorized');
                                            }),
                                        ],
                                    ], [
                                        'Folder_Name.required' => 'The folder name is required.',
                                        'Folder_Name.string' => 'The folder name must be a valid string.',
                                        'Folder_Name.max' => 'The folder name must not exceed 255 characters.',
                                        'Folder_Name.unique' => 'The folder name already exists for this resort and folder type.',
                                   
                                    ]);
                                }
            else
            {
                $validator = Validator::make($request->all(), [
                                                        'Folder_Name' => [
                                                            'required',
                                                            'string',
                                                            'max:255',
                                                            Rule::unique('filemangement_systems')->ignore($id)->where(function ($query) {
                                                                return $query->where('resort_id', $this->resort->resort_id)
                                                                            ->where('Folder_Type', 'uncategorized');
                                                            }),
                                                        ],
                                                    ], [
                                                        'Folder_Name.required' => 'The folder name is required.',
                                                        'Folder_Name.string' => 'The folder name must be a valid string.',
                                                        'Folder_Name.max' => 'The folder name must not exceed 255 characters.',
                                                        'Folder_Name.unique' => 'The folder name already exists for this resort and folder type.',
                                                        'Folder_Type.in' => 'The folder type must be "uncategorized".',
                                                    ]);
                
            }
        
        
            

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            DB::beginTransaction();
            try{ 
                    $uniqueString = substr(md5(uniqid($request->Folder_Name, true)), 0, 10);

                    $flag  = $request->flag;
                    if($flag == 'Main')
                    {
                        $UnderON = 0;
                    }              
                    else
                    {
                        $UnderON = base64_decode($flag);
                        $FilemangementSystem = FilemangementSystem::find($UnderON);

                    }  
                    
                    DB::beginTransaction();
                    try 
                    {
                        $filesystem =    FilemangementSystem::updateOrCreate(["id"=>$id],[
                                'resort_id' =>$resortId ,
                                'Folder_Name' => $Folder_Name,
                                'Folder_unique_id' => $uniqueString,
                                'UnderON'=>$UnderON,
                                'Folder_Type' => 'uncategorized'
                            ]);
                            if($UnderON !=0)
                            {
                                $folderPath = $main_folder . '/public/uncategorized' .$FilemangementSystem->Folder_unique_id . '/' . $uniqueString . '/.gitkeep';
                            }
                            else
                            {
                                $folderPath = $main_folder . '/public/uncategorized/' . $uniqueString . '/.gitkeep';

                            }

                  
                        StorageHelper::disk()->put($folderPath, '');
                        DB::commit();
                    }
                    catch (S3Exception $e) 
                    {
                        Log::error('AWS S3 Exception: ' . $e->getAwsErrorMessage());
                         DB::rollBack();

                        return response()->json([
                            'status' => 'error',
                            'message' => 'AWS S3 error: ' . $e->getAwsErrorMessage(),
                        ], 500);
                    } catch (Exception $e) {
                        Log::error('General S3 Storage Error: ' . $e->getMessage());
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Storage error: ' . $e->getMessage(),
                        ], 500);
                    }
                    $FolderList = FilemangementSystem::where('resort_id', $this->resort->resort_id)
                                                    // ->where('UnderON', 0)
                                                    ->where("Folder_Type", "uncategorized")
                                                    ->orderByDesc('id')
                                                    ->get();
                    $string = '';
                    foreach ($FolderList as $f) 
                    {
                        $string .= '<div class="selectFolderLocation-block">
                                        <img src="' . URL::asset('resorts_assets/images/folder.svg') . '" alt="image">
                                        <div>
                                            <input type="text" class="form-control d-none" placeholder="New Folder |" />
                                            <h5>' . htmlspecialchars($f->Folder_Name, ENT_QUOTES, 'UTF-8') . '</h5>
                                        </div>
                                    
                                        <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green selFolLoc-edit" data-name="'.$f->Folder_Name.'" data-id="' . base64_encode($f->id) . '">
                                            <img src="' . URL::asset('resorts_assets/images/edit.svg') . '" alt="" class="img-fluid" />
                                        </a>
                                    </div>';
                    }
                    $msg ='';
                    if($id !=0)
                    {
                        $msg = 'Folder updated successfully';
                    }
                    else
                    {
                        $msg = 'folder created successfully';
                    }
                        
                  return response()->json(['success' => true, 'message' => $msg,'data'=>$string], 200);
              } catch (\Exception $e) {
                \Log::emergency("File: ".$e->getFile());
                \Log::emergency("Line: ".$e->getLine());
                \Log::emergency("Message: ".$e->getMessage());

                return response()->json(['success' => false, 'message' => 'Failed to add division.'.$e->getMessage()], 500);
            }

        }
        
        public function UnCategoriesDocuments(Request $request)
        {
            if(Common::checkRouteWisePermission('Categories.Documents',config('settings.resort_permissions.view')) == false){
                return abort(403, 'Unauthorized action.');
            }
            $page_title = 'Uncategories Documents'; 
                
            $AllFolderList = FilemangementSystem::where('resort_id', $this->resort->resort_id)
                                            // ->where('UnderON', 0)
                                            ->where("Folder_Type", "uncategorized")
                                            ->orderByDesc('id')
                                            ->get();
            $FolderList = FilemangementSystem::where('resort_id', $this->resort->resort_id)
                                            ->where('UnderON', 0)
                                            ->where("Folder_Type", "uncategorized")
                                            ->orderByDesc('id')
                                            ->get();
            $department = ResortDepartment::where('resort_id', $this->resort->resort_id)->get();

            return view('resorts.FileManagment.FolderMangement.UnCategoriesDocuments',compact('department','page_title','FolderList','AllFolderList'));

        }
    
        public function GetFolder(Request $request)
        {
            $Search = $request->Search;

            $flag= $request->flag;
            // Sidebar search/refresh uses the same per-user filter the
            // initial page load uses — otherwise typing in Search would
            // re-surface all the folders a non-privileged user shouldn't
            // see.
            $allowedFolderIds = $this->visibleFolderIdsForCurrentUser();
            $FolderList = FilemangementSystem::where('resort_id', $this->resort->resort_id)
                    ->where('UnderON', 0);
                if($Search != '')
                {
                    // Match either the raw Folder_Name (Emp_id like "DR-31") or the
                    // employee's full name — sidebar shows the latter, so users
                    // searching by name expect it to work.
                    $matchingEmpIds = DB::table('employees as e')
                        ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                        ->where('e.resort_id', $this->resort->resort_id)
                        ->where(function ($q) use ($Search) {
                            $q->where('ra.first_name', 'like', '%' . $Search . '%')
                              ->orWhere('ra.last_name', 'like', '%' . $Search . '%')
                              ->orWhereRaw("CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,'')) like ?", ['%' . $Search . '%']);
                        })
                        ->pluck('e.Emp_id')
                        ->filter()
                        ->all();
                    $FolderList->where(function ($q) use ($Search, $matchingEmpIds) {
                        $q->where('Folder_Name', 'like', '%' . $Search . '%');
                        if (!empty($matchingEmpIds)) {
                            $q->orWhereIn('Folder_Name', $matchingEmpIds);
                        }
                    });
                }
                if (is_array($allowedFolderIds)) {
                    $FolderList->whereIn('id', $allowedFolderIds ?: [0]);
                }
                $FolderList= $FolderList->where("Folder_Type", $flag)
                    ->orderByDesc('id')
                    ->get();

            // Match the EmployeesFolderMangement initial render: resolve
            // each folder's Folder_Name (= Emp_id) to "Full Name (Emp_id)"
            // so the sidebar text stays the same after search/clear.
            $empIds = $FolderList->pluck('Folder_Name')->unique()->filter()->values();
            $empNameByEmpId = [];
            if ($empIds->isNotEmpty()) {
                $empRows = DB::table('employees as e')
                    ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                    ->where('e.resort_id', $this->resort->resort_id)
                    ->whereIn('e.Emp_id', $empIds)
                    ->get(['e.Emp_id', 'ra.first_name', 'ra.last_name']);
                foreach ($empRows as $row) {
                    $empNameByEmpId[$row->Emp_id] = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
                }
            }

            $string = '';
            if($FolderList->isNotEmpty())
            {
                foreach ($FolderList as $f)
                {
                    $displayName = isset($empNameByEmpId[$f->Folder_Name]) && $empNameByEmpId[$f->Folder_Name] !== ''
                        ? $empNameByEmpId[$f->Folder_Name] . ' (' . $f->Folder_Name . ')'
                        : $f->Folder_Name;

                    $string .= '<div class="d-flex">
                                <div class="showStructure" data-unique_id="'. htmlspecialchars($f->Folder_unique_id, ENT_QUOTES, 'UTF-8') .'">
                                    <div class="img-circle userImg-block">
                                        <img src="' . URL::asset('resorts_assets/images/folder.svg') . '" alt="image">
                                    </div>
                                    <div>
                                        <h6>' . htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') . '</h6>
                                    </div>
                                </div>
                                <div class="form-check no-label">
                                    <input class="form-check-input FolderName internacheck checkCheck d-none"
                                        type="checkbox"
                                        name="FolderName[]"
                                        data-id="'. htmlspecialchars($f->Folder_unique_id, ENT_QUOTES, 'UTF-8') .'"
                                        value="'. htmlspecialchars($f->Folder_unique_id, ENT_QUOTES, 'UTF-8') .'">
                                </div>
                            </div>';

                }

            }
            else
            {
                $string = '<div class="d-flex">
                                <div class="showStructure">

                                   <h6>No record found ..<h6>
                                </div>

                            </div>
                           ';
            }

            return response()->json(['success' => true,'data'=>$string], 200);
        }
        
        public function StoreFolderFiles(Request $request)
        {
            ini_set('memory_limit', '-1');
        
                $id = $request->id;
                $FolderFiles = $request->FolderFiles;
                $FolderName = base64_decode($request->FolderName);
                $My_file_key = env('ENCRYPTION_KEY');
                $File_structure = FilemangementSystem::where('resort_id', $this->resort->resort_id)->where('id', $FolderName)->first();
                                        
                $main_folder = $this->resort->resort->resort_id;
                foreach ($FolderFiles as $file) 
                {
                    // Get file details
                    $originalName = $file->getClientOriginalName();
                    $extension = strtolower($file->getClientOriginalExtension());
                    $fileSizeMB = round($file->getSize() / 1024, 2); // Convert to KB
                    $isImage = in_array($extension, ['jpg', 'jpeg', 'png']);
        
                    if ($isImage) {
                        // Store the file temporarily
                        $tempImagePath = $file->store('temp', 'local'); 
                        $fullImagePath = storage_path('app/' . $tempImagePath);
                    
                        // Get mime type and convert to base64
                        if (file_exists($fullImagePath)) {
                            $imageData = file_get_contents($fullImagePath);
                            $mimeType = mime_content_type($fullImagePath);
                            $base64Image = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                            
                            // Generate PDF with base64 image - use proper configuration
                            $pdf = Pdf::loadView('resorts.FileManagment.scan', [
                                'imageBase64' => $base64Image
                            ])->setPaper('a4', 'portrait');
                            
                            // Save PDF to temporary file
                            $tempPdfPath = storage_path('app/temp/') . uniqid('pdf_') . '.pdf';
                            $pdf->save($tempPdfPath);
                            
                            // Use the PDF file for further processing
                            $fileContent = file_get_contents($tempPdfPath);
                            $originalName = pathinfo($originalName, PATHINFO_FILENAME) . '.pdf';
                            $extension = 'pdf';
                            $fileSizeMB = round(strlen($fileContent) / 1024, 2);
                        } else {
                            throw new \Exception("Temporary image file not found");
                        }
                    } else {
                        // For non-image files, use the original file
                        $fileContent = file_get_contents($file->getRealPath());
                    }
        
                    $uniqueString = substr(md5(uniqid($originalName, true)), 0, 10);
                    $newFileName = $uniqueString . '.' . $extension . '.enc'; // Add .enc extension to indicate encrypted
        
                    if ($File_structure->UnderON != 0) {
                        $parentPath = FilemangementSystem::where('resort_id', $this->resort->resort_id)
                            ->where('id', $File_structure->UnderON)
                            ->first();
        
                        $path = $main_folder . '/public/' . $File_structure->Folder_Type . '/' . $parentPath->Folder_unique_id . '/' . $File_structure->Folder_unique_id . '/' . $newFileName;
                    } 
                    else
                    {
                        $path = $main_folder . '/public/' . $File_structure->Folder_Type . '/' . $File_structure->Folder_unique_id . '/' . $newFileName;
                    }
        
                 
                        // AES-256-CBC Encryption setup
                        $key = hash('sha256', env('ENCRYPTION_KEY'), true); // AES-256 key
                        $iv = random_bytes(16); // Generate IV (16 bytes for AES-256-CBC)
        
                        // For image files that were converted to PDF, use the PDF content
                        // For other files, use the original file content
                        $dataToEncrypt = $isImage ? $fileContent : file_get_contents($file->getRealPath());
                        // dd($path,$dataToEncrypt);
                        // Encrypt the file content
                        $encrypted = $iv . openssl_encrypt(
                            $dataToEncrypt,
                            'aes-256-cbc',
                            $key,
                            OPENSSL_RAW_DATA,
                            $iv
                        );
        
                        if ($encrypted === false) {
                            throw new \Exception("Encryption failed: " . openssl_error_string());
                        }
        
                        // Upload to S3 with proper metadata
                        StorageHelper::disk()->put($path, $encrypted, [
                            'ContentType' => 'application/octet-stream',
                            'ContentDisposition' => 'attachment; filename="' . $originalName . '"'
                        ]);
        
                        $existingFile = ChildFileManagement::where('resort_id', $this->resort->resort_id)
                            ->where('Parent_File_ID', $File_structure->id)
                            ->where(function ($query) use ($originalName) {
                                $query->where('File_Name', $originalName)
                                    ->orWhere('NewFileName', $originalName);
                            })
                            ->orderBy('id', 'desc')
                            ->first();
        
                        $fileRecord = ChildFileManagement::create([
                            'resort_id' => $this->resort->resort_id,
                            'unique_id' => $uniqueString,
                            'Parent_File_ID' => $File_structure->id,
                            'Folder_id' => $FolderName,
                            'File_Name' => $originalName,
                            'File_Type' => $extension,
                            'File_Size' => $fileSizeMB,
                            'File_Path' => $path,
                            'File_Extension' => $extension,
                        ]);
        
                        if ($existingFile && $File_structure->Folder_Type == "uncategorized") {
                            $fileVersion = $this->CreateFileVersion($existingFile->id, $fileRecord->id);
                        }
    
                        AuditLogs::create([
                            'resort_id' => $this->resort->resort_id,
                            "file_id"   => $fileRecord->id,
                            "TypeofAction" => "Create",
                            "file_path" => $path
                        ]);
        
                        // Clean up temporary files
                        if ($isImage) {
                            if (file_exists($fullImagePath)) {
                                unlink($fullImagePath);
                            }
                            if (file_exists($tempPdfPath)) {
                                unlink($tempPdfPath);
                            }
                        }
        
                  
                }
        
                return response()->json(['success' => true, 'message' => 'File Uploaded successfully'], 200);
           
        }
        
        public function FolderList(Request $request)
        {
            $FolderList = FilemangementSystem::where('resort_id', $this->resort->resort_id)
            // ->where('UnderON', 0)
            // ->where("Folder_Type", "uncategorized")
            ->orderByDesc('id')
            ->get()->map(function ($FolderList) {
                
                $FolderList->new_id = base64_encode($FolderList->id);
                $FolderList->Folder_Name = htmlspecialchars($FolderList->Folder_Name, ENT_QUOTES, 'UTF-8');

                return $FolderList;
            });
            $string ='<option value=""></option>';
            if($FolderList->isNotEmpty())
            {
                
                    foreach($FolderList as $f)
                    {
                        $string .="<option value='".$f->new_id."'>".$f->Folder_Name."</option>";
                    }
            }
            return response()->json(['success' => true, 'data' => $string], 200);

        }
        public function GetFolderFiles(Request $request)
        {
            $id =  $request->id;
            $flag=$request->flag;

            // The "Shared With Me" sidebar entry is a virtual folder injected
            // client-side (data-unique_id="__shared_with_me__") and its contents
            // are rendered by JS without a server call. The generic
            // .showStructure click handler still fires this endpoint, though,
            // so short-circuit cleanly instead of crashing on the null lookup.
            if ($id === '__shared_with_me__') {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'breadcrumb' => '',
                    'virtual' => 'shared_with_me',
                ], 200);
            }

            $File_structure = FilemangementSystem::where('resort_id', $this->resort->resort_id)
                                ->where('Folder_unique_id', $id)
                                ->where('Folder_Type',$flag)
                                ->first();

            if (!$File_structure) {
                return response()->json([
                    'success' => false,
                    'message' => 'Folder not found or no longer accessible.',
                ], 404);
            }

            $parent_unique_id = $File_structure->Folder_unique_id;
            $mergedFiles = collect();
            // withSum aggregates child file sizes per folder in ONE query —
            // was firing one SUM per folder on every drill-in.
            $File_structure1 = FilemangementSystem::where('resort_id', $this->resort->resort_id)
                                ->where('UnderON', $File_structure->id)
                                ->where('Folder_Type',$flag)
                                ->withSum(['children as children_size_sum' => function ($q) {
                                    $q->where('resort_id', $this->resort->resort_id);
                                }], 'File_Size')
                                ->orderByDesc('Folder_Name')
                                ->get()
                                ->map(function($ak){
                                    $img='';
                                    $ak->new_id = base64_encode($ak->id);
                                    $ak->File_Name =  htmlspecialchars($ak->Folder_Name, ENT_QUOTES, 'UTF-8');
                                    $ak->ModifiedDate = $ak->updated_at->format('d M Y');
                                    $ak->Permission = URL::asset('resorts_assets/images/user-4.svg');
                                    $ak->File_Size = (float) ($ak->children_size_sum ?? 0);
                                    $ak->File_img =  URL::asset('resorts_assets/images/folder.svg');
                                    $ak->unique_id = $ak->Folder_unique_id;
                                    return $ak;
                                })->each(function ($folder) use ($mergedFiles ,$parent_unique_id ) {
                                $mergedFiles->push([
                                    'id' => $folder->id,
                                    'Parent_File_ID'=>$parent_unique_id,
                                    'unique_id'=>$folder->unique_id,
                                    'new_id' => $folder->new_id,
                                    'File_Name' => $folder->File_Name,
                                    'File_Size' => $folder->File_Size ? $folder->File_Size . ' KB' : '0 KB',
                                    'ModifiedDate' => $folder->ModifiedDate,
                                    'Permission' => '',
                                    'File_img' => $folder->File_img,
                                    'Type' => 'folder', // To distinguish folders from files
                                    'NewURL'=>"FolderFile",
                                ]);
                            });
                                
                // Folder-level share short-circuit: when the user reached
                // this folder via a share (folder id is in their allowed
                // set), grant access to every file in the folder without
                // running the per-file FilePermissions check — otherwise
                // recipients see an empty "shared" folder.
                $allowedFolderIds = $this->visibleFolderIdsForCurrentUser();
                $folderGrantedByShare = is_array($allowedFolderIds)
                    && in_array($File_structure->id, $allowedFolderIds, true);

                // FiL Structure FilePermissions
                $ChildFiles = ChildFileManagement::where("Parent_File_ID"   , $File_structure->id)
                                ->where("resort_id"   , $this->resort->resort_id)
                                ->orderByDesc('id')

                                ->get()->map(function($i) {
            
                                    // FoLderStructure
                                
                                    $imgExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];
                                    $docExtensions = ['doc', 'docx',  'xls', 'xlsx', 'csv', 'ppt', 'pptx', 'txt', 'rtf'];
                                    $zipExtensions = ['zip', 'rar', '7z', 'tar', 'gz'];
                                    $audioExtensions = ['mp3', 'wav', 'ogg', 'm4a'];
                                    $videoExtensions = ['mp4', 'avi', 'mkv', 'mov', 'wmv'];
                                    // FiLes Structure
                                    $img='';
                                    $i->new_id = base64_encode($i->id);
                                    $i->File_Name = !empty($i->NewFileName) ?   htmlspecialchars($i->NewFileName, ENT_QUOTES, 'UTF-8') : htmlspecialchars($i->File_Name, ENT_QUOTES, 'UTF-8');
                                    $i->ModifiedDate = $i->updated_at->format('d M Y');
                                    $i->Permission = URL::asset(path: 'resorts_assets/images/user-4.svg');
                                    $i->File_Size = $i->File_Size.' KB';
            
                                
                                    if (in_array($i->File_Extension, $imgExtensions)) {
                                        $img = URL::asset('resorts_assets/images/image.svg'); // Image icon
                                    } elseif (in_array($i->File_Extension, haystack: $docExtensions)) {
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
                                })
                                ->each(function ($file) use ($mergedFiles,$parent_unique_id,$flag,$folderGrantedByShare )
                                {
                                        $resort =  $this->resort;
                                        $filePermission = $folderGrantedByShare
                                            ? ['type' => true, 'emp' => []]
                                            : Common::FilePermissions($file->unique_id, $resort, $flag);
                                        if(isset($filePermission['type']) && $filePermission['type'] == true)
                                        {
                                            $emp='<div class="user-ovImg user-ovImgTable">';
                                            if(array_key_exists('emp',$filePermission))
                                            {
                                                foreach($filePermission['emp'] as $f)
                                                {
                                                    $emp.='<div class="img-circle"> <img src="'.$f['profile'].'"></div>';
                                                }
                                            }

                                            $emp.="</div>";
                                                $mergedFiles->push([
                                                    'id' => $file->id,
                                                    'Parent_File_ID'=>$parent_unique_id,
                                                    'unique_id'=>$file->unique_id,
                                                    'new_id' => $file->new_id,
                                                    'File_Name' => $file->File_Name,
                                                    'File_Size' => $file->File_Size,
                                                    'ModifiedDate' => $file->ModifiedDate,
                                                    'Permission' => $emp,
                                                    'File_img' => $file->File_img,
                                                    'Type' => 'file', // To distinguish folders from files
                                                    'NewURL' => $file->NewURL// File URL if available
                                                ]);
                                        }
                                    
                                });
                $tr='';
                $mergedFiles = $mergedFiles->values();
                if($mergedFiles->isNotEmpty())
                {
                    foreach( $mergedFiles as $f)
                    {
                        $tr .= '<tr>
                                    <td>
                                            <div class="form-check no-label">
                                                <input class="form-check-input internacheck checkCheck d-none" type="checkbox" name="FilesName[]" data-id="'.$f['Parent_File_ID'].'" value="'.$f['unique_id'].'" >
                                            </div>
                                    <td> <a href="javascript:void(0)" class="OpenFileorFolder" data-unique_id = "'. $f['unique_id'].'" data-url = "'. $f['NewURL'].'"> <img src="' . $f['File_img'] . '" alt="images"> ' . $f['File_Name'] . '</a></td>
                                    <td>' . $f['File_Size'] . ' </td>
                                    <td>' . $f['ModifiedDate'] . '</td>
                                    <td>'.$f['Permission'].'</td>
                                    <td>
                                        <div class="context-btn" data-name="'.$f['File_Name'].'" data-id="'.$f['unique_id'].'" > <i class="fa-solid fa-ellipsis"></i></div>
                                    </td>
                                </tr>';

                    }
                }
            
                else
                {
                    $tr = '<tr><td colspan="8" style="text-align: center;">No record found </td></tr>';

        
                }

                $breadcrumb = "<li class='breadcrumb-item active'><a class='OpenFileorFolder active'  data-url='FolderFile' data-unique_id='{$File_structure->Folder_unique_id}' href='javascript:void(0)'>".$File_structure->Folder_Name."</a></li>";

                
                return response()->json(['success' => true, 'data' => $tr,"breadcrumb"=>$breadcrumb], 200);

        }
        public function RenameFile(Request $request)
        {
        
            $file_id  = $request->file_id;
            $renameFile  = $request->renameFile;

            $File = ChildFileManagement::where('resort_id', $this->resort->resort_id)
                                        ->where('unique_id',  $request->file_id)->first();
            if($File)
            {
                $File->File_Name = $renameFile;
                $File->save();

                $id = AuditLogs::create([
                    'resort_id' => $this->resort->resort_id,
                    "file_id"   => $File->id,
                    "TypeofAction" => "Rename",
                    "file_path" => $File->File_Path,
                    ]);
                return response()->json(['success' => true, 'message' => 'File renamed successfully'], 200);
            }
            else
            {
                
                $File_structure = FilemangementSystem::where('resort_id', $this->resort->resort_id)
                                                    ->where('Folder_unique_id',  $request->file_id)
                                                    ->first();
                if($File_structure)
                {
                    $File_structure->Folder_name = $renameFile;
                    $File_structure->save();

                    
                    return response()->json(['success' => true, 'message' => 'File renamed successfully'], 200);

                }
                else
                {
                    return response()->json(['success' => false, 'message' => 'Failed to rename file.'], 500);
                }
            }
            return response()->json(['success' => false, 'message' => 'Failed to rename file.'], 500);

        }

        /**
         * Delete a file from the user's resort. Removes the encrypted blob
         * from storage AND the ChildFileManagement row, with an audit-log
         * entry pointing at the deleted path.
         */
        public function DeleteFile(Request $request)
        {
            $file_id = $request->file_id;
            $File = ChildFileManagement::where('resort_id', $this->resort->resort_id)
                ->where('unique_id', $file_id)
                ->first();
            if (!$File) {
                return response()->json(['success' => false, 'message' => 'File not found.'], 404);
            }
            try {
                if ($File->File_Path && StorageHelper::disk()->exists($File->File_Path)) {
                    StorageHelper::disk()->delete($File->File_Path);
                }
                AuditLogs::create([
                    'resort_id'    => $this->resort->resort_id,
                    'file_id'      => $File->id,
                    'TypeofAction' => 'Delete',
                    'file_path'    => $File->File_Path,
                ]);
                $File->delete();
                return response()->json(['success' => true, 'message' => 'File deleted successfully.'], 200);
            } catch (\Throwable $e) {
                \Log::error('DeleteFile failed: ' . $e->getMessage());
                return response()->json(['success' => false, 'message' => 'Could not delete the file: ' . $e->getMessage()], 500);
            }
        }

        /**
         * Build a short-lived shareable link for a file. We decrypt to a
         * temp object on the same disk and hand back a presigned URL (or a
         * regular URL on local disk). Same temp-decrypt pattern as the
         * inline-view path in ShowthefolderWiseData() — but no iframe
         * rendering, just the URL.
         */
        public function ShareFile(Request $request)
        {
            $file_id = $request->file_id;
            $File = ChildFileManagement::where('resort_id', $this->resort->resort_id)
                ->where('unique_id', $file_id)
                ->first();
            if (!$File) {
                return response()->json(['success' => false, 'message' => 'File not found.'], 404);
            }
            if (!$File->File_Path || !StorageHelper::disk()->exists($File->File_Path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This file is no longer available in storage.'
                ], 404);
            }
            try {
                $key = hash('sha256', env('ENCRYPTION_KEY'), true);
                $encryptedData = StorageHelper::disk()->get($File->File_Path);
                if (empty($encryptedData) || strlen($encryptedData) < 16) {
                    throw new \Exception('Invalid or corrupted encrypted data');
                }
                $iv = substr($encryptedData, 0, 16);
                $cipherText = substr($encryptedData, 16);
                $decryptedData = openssl_decrypt($cipherText, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
                if ($decryptedData === false) {
                    throw new \Exception('Decryption failed: ' . openssl_error_string());
                }
                $decryptedFileName = str_replace('.enc', '', basename($File->File_Path));
                $tempFilePath = 'temp/share_' . time() . '_' . $decryptedFileName;
                StorageHelper::disk()->put($tempFilePath, $decryptedData);
                $url = StorageHelper::temporaryUrl($tempFilePath, 30);
                return response()->json([
                    'success'    => true,
                    'url'        => $url,
                    'expires_at' => now()->addMinutes(30)->format('d M Y, h:i A'),
                ], 200);
            } catch (\Throwable $e) {
                \Log::error('ShareFile failed: ' . $e->getMessage());
                return response()->json(['success' => false, 'message' => 'Could not generate share link: ' . $e->getMessage()], 500);
            }
        }

        /**
         * Folder ids the current resort-admin can see in the sidebar.
         * Returns NULL for "unrestricted" (the privileged set: GM / HR /
         * MGR / MD / HR-dept HOD-EXCOM / master admin) and an array of
         * filemangement_systems.id otherwise. Non-privileged users see
         * only their own categorized folder (named after their Emp_id)
         * plus any folders explicitly shared with them.
         */
        protected function visibleFolderIdsForCurrentUser(): ?array
        {
            $user = $this->resort;
            if (!$user) return [];

            // Super / master always sees everything.
            if (($user->type ?? null) === 'super' || ($user->is_master_admin ?? 0)) {
                return null;
            }

            $emp = $user->GetEmployee ?? null;
            if (!$emp) return [];

            $rank     = (int) ($emp->rank ?? 0);
            $isHrDept = \App\Helpers\Common::isHRDepartment($emp->Dept_id ?? null);

            // Privileged set mirrors Common::FilePermissions:
            //   - rank 3 (HR), 4 (MGR), 8 (GM), 9 (MD) anywhere
            //   - rank 1 (EXCOM), 2 (HOD) only if they're in the HR dept
            $isPrivileged = in_array($rank, [3, 4, 8, 9], true)
                || (in_array($rank, [1, 2], true) && $isHrDept);
            if ($isPrivileged) return null;

            // Non-privileged: own folder (Emp_id) + shared folders.
            $allowed = [];

            if (!empty($emp->Emp_id)) {
                $ownFolderId = (int) FilemangementSystem::where('resort_id', $user->resort_id)
                    ->where('Folder_Type', 'categorized')
                    ->where('Folder_Name', $emp->Emp_id)
                    ->value('id');
                if ($ownFolderId) $allowed[] = $ownFolderId;
            }

            $sharedIds = \App\Http\Controllers\Resorts\FileManagment\FileShareController::visibleSharedFolderIdsFor($emp);
            $allowed = array_values(array_unique(array_merge($allowed, $sharedIds)));
            return $allowed;
        }

        /**
         * Does the current resort-admin user have an active FileShare
         * granting them access to this specific file id?
         * Resolves the three internal scope types:
         *   - explicit employees (file_share_employees)
         *   - employee's current Dept_id matches a department share
         *   - organization-wide share in the user's resort
         */
        protected function userHasReceivedShareForFile(int $fileId): bool
        {
            $emp = $this->resort->GetEmployee ?? null;
            if (!$emp) return false;

            // All share records pointing at this file
            $shareIds = \DB::table('file_shares')
                ->where('shareable_type', 'file')
                ->where('shareable_id', $fileId)
                ->where('share_mode', 'internal')
                ->pluck('id', 'scope_type');
            if ($shareIds->isEmpty()) return false;

            // Org-wide → recipient just needs to be in the same resort
            // as one of these shares.
            $orgShareIds = \DB::table('file_shares')
                ->where('shareable_type', 'file')
                ->where('shareable_id', $fileId)
                ->where('share_mode', 'internal')
                ->where('scope_type', 'organization')
                ->where('resort_id', $emp->resort_id)
                ->exists();
            if ($orgShareIds) return true;

            // Specific-employee shares
            $directHit = \DB::table('file_shares as fs')
                ->join('file_share_employees as fse', 'fse.share_id', '=', 'fs.id')
                ->where('fs.shareable_type', 'file')
                ->where('fs.shareable_id', $fileId)
                ->where('fse.employee_id', $emp->id)
                ->exists();
            if ($directHit) return true;

            // Department shares — recipient's current Dept_id wins
            if ($emp->Dept_id) {
                $deptHit = \DB::table('file_shares as fs')
                    ->join('file_share_departments as fsd', 'fsd.share_id', '=', 'fs.id')
                    ->where('fs.shareable_type', 'file')
                    ->where('fs.shareable_id', $fileId)
                    ->where('fsd.department_id', $emp->Dept_id)
                    ->exists();
                if ($deptHit) return true;
            }

            return false;
        }

        public function ShowthefolderWiseData(Request $request)
        {

            $unique_id = $request->unique_id;
            $TypeOfFile = $request->Location;
            if( $TypeOfFile == "FolderFile")
            {
                $File_structure = FilemangementSystem::where('resort_id', $this->resort->resort_id)
                                                        ->where('Folder_unique_id', $unique_id)
                                                        ->first();
                $flag = $File_structure->Folder_Type;                          
                $mergedFiles = collect();
                $File_structure1 = FilemangementSystem::where('resort_id', $this->resort->resort_id)
                            ->where('UnderON', $File_structure->id)
                            ->orderByDesc('Folder_Name')
                            ->get()
                            ->map(function($ak){
                                $img='';
                                $ak->new_id = base64_encode($ak->id);
                                $ak->File_Name = htmlspecialchars($ak->Folder_Name, ENT_QUOTES, 'UTF-8');
                                $ak->ModifiedDate = $ak->updated_at->format('d M Y');
                                $ak->Permission = URL::asset('resorts_assets/images/user-4.svg');
                                $File_Size = ChildFileManagement::where("Parent_File_ID", $ak->id)
                                                                ->where("resort_id", $this->resort->resort_id)
                                                                ->sum('File_Size');
                                $ak->File_Size = $File_Size;
                                $ak->Permission = URL::asset( 'resorts_assets/images/user-4.svg');
                                $ak->File_img =  URL::asset('resorts_assets/images/folder.svg');
                                $ak->unique_id = $ak->Folder_unique_id;
                                return $ak;
                            })->each(function ($folder) use ($mergedFiles) 
                            {
                              
                                    $mergedFiles->push([
                                        'id' => $folder->id,
                                        'unique_id'=>$folder->unique_id,
                                        'new_id' => $folder->new_id,
                                        'File_Name' => $folder->File_Name,
                                        'File_Size' => $folder->File_Size ? $folder->File_Size . ' KB' : '0 KB',
                                        'ModifiedDate' => $folder->ModifiedDate,
                                        'Permission' => '',
                                        'File_img' => $folder->File_img,
                                        'Type' => 'folder', // To distinguish folders from files
                                        'NewURL'=>"FolderFile",
                                    ]);
                        });

                        $ChildFiles = ChildFileManagement::where("Parent_File_ID",$File_structure->id)
                        ->where("resort_id"   , $this->resort->resort_id)
                        ->get()->map(function($i) 
                        {

                            // FoLderStructure
                        
                            $imgExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];
                            $docExtensions = ['doc', 'docx',  'xls', 'xlsx', 'csv', 'ppt', 'pptx', 'txt', 'rtf'];
                            $zipExtensions = ['zip', 'rar', '7z', 'tar', 'gz'];
                            $audioExtensions = ['mp3', 'wav', 'ogg', 'm4a'];
                            $videoExtensions = ['mp4', 'avi', 'mkv', 'mov', 'wmv'];

                                        

                            // FiLes Structure
                            $img='';
                            $i->new_id = base64_encode($i->id);
                            $i->File_Name =  !empty($i->NewFileName) ?   htmlspecialchars($i->NewFileName, ENT_QUOTES, 'UTF-8') : htmlspecialchars($i->File_Name, ENT_QUOTES, 'UTF-8');
                            $i->ModifiedDate = $i->updated_at->format('d M Y');
                            $i->Permission = URL::asset(path: 'resorts_assets/images/user-4.svg');
                            $i->File_Size = $i->File_Size.' KB';

                        
                            if (in_array($i->File_Extension, $imgExtensions)) {
                                $img = URL::asset('resorts_assets/images/image.svg'); // Image icon
                            } elseif (in_array($i->File_Extension, haystack: $docExtensions)) {
                                $img = URL::asset('resorts_assets/images/word.svg'); // Document icon
                            } elseif (in_array($i->File_Extension, $zipExtensions)) {
                                $img = URL::asset('resorts_assets/images/zip.svg'); // Archive icon
                            } elseif (in_array($i->File_Extension, $audioExtensions)) {
                                $img = URL::asset('resorts_assets/images/audio.svg'); // Audio file icon
                            } elseif (in_array($i->File_Extension, $videoExtensions)) {
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
                        })
                        ->each(function ($file) use ($mergedFiles, $flag) {

                            $resort =  $this->resort;
                            $filePermission = Common::FilePermissions($file->unique_id, $resort, $flag);              
                            if(isset($filePermission['type']) && $filePermission['type'] == true)
                            {
                                    $emp='<div class="user-ovImg user-ovImgTable">';
                                    if(array_key_exists('emp',$filePermission))
                                    {
                                        foreach($filePermission['emp'] as $f)
                                        {
                                            $emp.='<div class="img-circle"> <img src="'.$f['profile'].'"></div>';
                                        }
                                    }

                                    $emp.="</div>";
                                        $mergedFiles->push([
                                            'id' => $file->id,
                                            'unique_id'=>$file->unique_id,
                                            'new_id' => $file->new_id,
                                            'File_Name' => $file->File_Name,
                                            'File_Size' => $file->File_Size,
                                            'ModifiedDate' => $file->ModifiedDate,
                                            'Permission' => $emp,
                                            'File_img' => $file->File_img,
                                            'Type' => 'file', // To distinguish folders from files
                                            'NewURL' => $file->NewURL// File URL if available
                                        ]);
                                }
                        });

                        $tr='';
                        $mergedFiles = $mergedFiles->values();
                        if($mergedFiles->isNotEmpty())
                        {
                            foreach( $mergedFiles as $f)
                            {
                                $tr .= '<tr>
                                            <td>
                                                    <div class="form-check no-label">
                                                        <input class="form-check-input internacheck checkCheck d-none" type="checkbox" name="FilesName[]" value="'.$f['unique_id'].'" >
                                                    </div>
                                            <td> <a href="javascript:void(0)" class="OpenFileorFolder" data-unique_id = "'. $f['unique_id'].'" data-url = "'. $f['NewURL'].'"> <img src="' . $f['File_img'] . '" alt="images"> ' . $f['File_Name'] . '</a></td>
                                            <td>' . $f['File_Size'] . ' </td>
                                            <td>' . $f['ModifiedDate'] . '</td>
                                            <td>' . $f['Permission']. '</td>
                                            <td>
                                                <div class="context-btn" data-name="'.$f['File_Name'].'" data-id="'.$f['unique_id'].'" > <i class="fa-solid fa-ellipsis"></i></div>
                                            </td>
                                        </tr>';
                            }
                        }
                        else 
                        {
                            $tr = '<tr><td colspan="8" style="text-align: center;">No record found </td></tr>';

                        }
                        $breadcrumb = "";
                        $breadcrumbs = [];
                        
                        $File_structure = FilemangementSystem::where('resort_id', $this->resort->resort_id)
                                                            ->where('Folder_unique_id', $unique_id)
                                                            ->first();
                        if ($File_structure) {
                            $current_folder = $File_structure;
                            
                            while ($current_folder) 
                            {
                                $breadcrumbs[] = "<li class='breadcrumb-item '><a class='OpenFileorFolder' data-url='FolderFile' data-unique_id='{$current_folder->Folder_unique_id}' href='javascript:void(0)'>{$current_folder->Folder_Name}</a></li>";
                                $current_folder = FilemangementSystem::where('id', $current_folder->UnderON)->first();
                            }
                        }
                        $breadcrumbs = array_reverse($breadcrumbs);

                        if (!empty($breadcrumbs)) {
                    
                            $lastIndex = count($breadcrumbs) - 1;
                    

                    
                                $breadcrumbs[$lastIndex] = str_replace(
                                    "<li class='breadcrumb-item '>", // Match exact structure
                                    "<li class='breadcrumb-item active'>", // Replace with active class
                                    $breadcrumbs[$lastIndex]
                                );
                        
                            
                        }

                        $breadcrumb = implode("", $breadcrumbs);
                    return response()->json(['success' => true, 'data' => $tr ,"newUrL"=>"No",'breadcrumb'=>$breadcrumb], 200);
            }
            else
            {
                $ChildFiles = ChildFileManagement::where("unique_id"   , $unique_id)
                ->where("resort_id"   , $this->resort->resort_id)->first();
                $tr="";

                // Per-user access gate. Without this, any logged-in user at
                // the resort could open any file (including other employees'
                // personal folders) just by passing the file's unique_id —
                // which is rendered in the file list HTML, so it leaks.
                // FilePermissions() returns ['type' => true|false] depending
                // on the viewer's rank + dept + whether they own the
                // categorized folder. Non-HR HOD/EXCOM/Line-Worker viewing
                // someone else's folder file fails this check.
                if (!isset($ChildFiles)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'File not found.'
                    ], 404);
                }
                // Look up the parent folder to derive the folder type
                // (categorized vs uncategorized) — FilePermissions branches
                // on it.
                $parentFolder = FilemangementSystem::where('id', $ChildFiles->Parent_File_ID)
                    ->where('resort_id', $this->resort->resort_id)
                    ->first(['Folder_Type']);
                $accessFlag = $parentFolder->Folder_Type ?? 'categorized';
                $accessCheck = Common::FilePermissions($ChildFiles->unique_id, $this->resort, $accessFlag);
                if (!is_array($accessCheck) || empty($accessCheck['type']) || $accessCheck['type'] !== true) {
                    // Also accept access if this file/folder has been
                    // explicitly shared with the current user via the new
                    // FileShare feature (phase 1). Otherwise hard-deny.
                    if (!$this->userHasReceivedShareForFile($ChildFiles->id)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'You do not have permission to view this file.'
                        ], 403);
                    }
                }

                if (StorageHelper::disk()->exists($ChildFiles->File_Path)) {

                        $rawData = StorageHelper::disk()->get($ChildFiles->File_Path);

                        if (empty($rawData)) {
                            return response()->json([
                                'success' => false,
                                'message' => 'This file is empty and cannot be opened.'
                            ], 422);
                        }

                        // Files saved through the AES-256-CBC pipeline carry a
                        // `.enc` suffix and a 16-byte IV prepended to the cipher
                        // text. Earlier uploads (pre-encryption rollout) sit on
                        // disk as plain bytes — serve them as-is rather than
                        // trying to decrypt random binary, which 500s with
                        // "wrong final block length".
                        $isEncrypted = substr($ChildFiles->File_Path, -4) === '.enc';

                        if ($isEncrypted) {
                            if (strlen($rawData) < 16) {
                                return response()->json([
                                    'success' => false,
                                    'message' => 'This file is corrupted and cannot be opened. Please re-upload it.'
                                ], 422);
                            }

                            $key = hash('sha256', env('ENCRYPTION_KEY'), true);
                            $iv  = substr($rawData, 0, 16);
                            $cipherText = substr($rawData, 16);

                            $decryptedData = openssl_decrypt(
                                $cipherText,
                                'aes-256-cbc',
                                $key,
                                OPENSSL_RAW_DATA,
                                $iv
                            );

                            if ($decryptedData === false) {
                                // Don't 500 — log + show a clean message. This
                                // usually means the file was encrypted with a
                                // different ENCRYPTION_KEY than the current one.
                                \Log::warning('FileManage: decryption failed', [
                                    'file_id'       => $ChildFiles->id,
                                    'path'          => $ChildFiles->File_Path,
                                    'openssl_error' => openssl_error_string(),
                                ]);
                                return response()->json([
                                    'success' => false,
                                    'message' => 'This file cannot be opened. It may have been uploaded with a different encryption key. Please re-upload it.'
                                ], 422);
                            }
                        } else {
                            // Legacy plain file — no decryption needed.
                            $decryptedData = $rawData;
                        }
                        
                        // Generate decrypted filename
                        $decryptedFileName = str_replace('.enc', '', basename($ChildFiles->File_Path));
        
                        $tempFilePath = "temp/decrypted_" . time() . "_{$decryptedFileName}";
                        
                        $extension = strtolower(pathinfo($decryptedFileName, PATHINFO_EXTENSION));
                        $mimeTypes = [
                            // Documents
                            'pdf' => 'application/pdf',
                            'doc' => 'application/msword',
                            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'xls' => 'application/vnd.ms-excel',
                            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'ppt' => 'application/vnd.ms-powerpoint',
                            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                            'txt' => 'text/plain',
                            'csv' => 'text/csv',
                            
                            'jpg' => 'image/jpeg',
                            'jpeg' => 'image/jpeg',
                            'png' => 'image/png',
                            'gif' => 'image/gif',
                            'bmp' => 'image/bmp',
                            'svg' => 'image/svg+xml',
                            'webp' => 'image/webp',
                            
                            'mp3' => 'audio/mpeg',
                            'wav' => 'audio/wav',
                            'ogg' => 'audio/ogg',
                            'flac' => 'audio/flac',
                            'aac' => 'audio/aac',
                            
                            'mp4' => 'video/mp4',
                            'mov' => 'video/quicktime',
                            'avi' => 'video/x-msvideo',
                            'mkv' => 'video/x-matroska',
                            'webm' => 'video/webm',
                            'wmv' => 'video/x-ms-wmv',
                            'flv' => 'video/x-flv',
                            
                            'zip' => 'application/zip',
                            'rar' => 'application/x-rar-compressed',
                            'tar' => 'application/x-tar',
                            'gz' => 'application/gzip',
                            '7z' => 'application/x-7z-compressed',
                            'html' => 'text/html',
                            'css' => 'text/css',
                            'js' => 'application/javascript',
                            'json' => 'application/json',
                            'xml' => 'application/xml'
                        ];
                        
                        // Set MIME type based on extension or detect if not in our map
                        if (isset($mimeTypes[$extension])) {
                            $mimeType = $mimeTypes[$extension];
                
                        } else {
                            // Fallback to file detection - may not be accurate for all file types
                            // but better than nothing for unknown extensions
                            if (function_exists('mime_content_type')) {
                                // Create a temporary file to use mime_content_type
                                $tempFile = tempnam(sys_get_temp_dir(), 'file');
                                file_put_contents($tempFile, $decryptedData);
                                $mimeType = mime_content_type($tempFile);
                                unlink($tempFile); // Clean up
                            } else if (class_exists('finfo')) {
                                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                                $mimeType = $finfo->buffer($decryptedData);
                            } else {
                                // If all detection methods fail, use binary as default
                                $mimeType = 'application/octet-stream';
                            }
                        }
                        
                        // Store the decrypted file with proper content type
                        StorageHelper::disk()->put($tempFilePath, $decryptedData, [
                            'ContentType' => $mimeType
                        ]);
                        
                        // Generate a temporary URL with sufficient time window
                        $fileExtension = pathinfo($ChildFiles->File_Path, PATHINFO_EXTENSION);
                        // Get MIME type dynamically
      
                        $mimeType = match (strtolower($extension)) {
                            'mp4'  => 'video/mp4',
                            'mov'  => 'video/quicktime',
                            'avi'  => 'video/x-msvideo',
                            'pdf'  => 'application/pdf',
                            'txt'  => 'text/plain',
                            'jpg'  => 'image/jpeg',
                            'jpeg' => 'image/jpeg',
                            'png'  => 'image/png',
                            'gif'  => 'image/gif',
                            'doc', 'docx' => 'application/msword',
                            'xls', 'xlsx' => 'application/vnd.ms-excel',
                            'zip'  => 'application/zip',
                            default => 'application/octet-stream' // Fallback for unknown types
                        };
                        $newUrl = StorageHelper::temporaryUrl($tempFilePath, 30);
                    }
                    else
                    {
                        // File row exists in DB but the encrypted file is
                        // missing from storage (s3/wasabi/local). Was setting
                        // $newUrl = "No" — JS would then put "No" into the
                        // iframe src and the browser resolved it as a
                        // relative path → /resort/file-manage/No → 404.
                        // Return an explicit failure so the JS can toast a
                        // proper "File not found" message.
                        return response()->json([
                            'success' => false,
                            'message' => 'This file is no longer available in storage. It may have been deleted or never finished uploading.'
                        ], 404);
                    }
                return response()->json(['success' => true, 'data' => $tr, 'NewURLshow' => $newUrl,    'mimeType' => $mimeType], 200);
            }
        }
        public function EmployeesFolderMangement(Request $request)
        {

        if(Common::checkRouteWisePermission('Employees.Documents',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized action.');
        }
            $page_title = 'Employees File Management';


            // Sidebar folder list scoping. Privileged users (HR / GM /
            // MGR / MD / HR-dept HOD-EXCOM / master) see every folder at
            // the resort. Everyone else sees ONLY their own categorized
            // folder (matched by Folder_Name == their Emp_id) PLUS any
            // folders explicitly shared with them (via FileShare).
            // Without this filter, a Chief Engineer (rank 1 / 2 in a
            // non-HR dept) saw every other employee's folder name in the
            // sidebar even though they couldn't open the files inside.
            $allowedFolderIds = $this->visibleFolderIdsForCurrentUser();

            $allBuilder = FilemangementSystem::where('resort_id', $this->resort->resort_id)
                                            ->where("Folder_Type", "categorized")
                                            ->orderByDesc('id');
            $rootBuilder = FilemangementSystem::where('resort_id', $this->resort->resort_id)
                                            ->where('UnderON', 0)
                                            ->where("Folder_Type", "categorized")
                                            ->orderByDesc('id');
            if (is_array($allowedFolderIds)) {
                // empty array → user has no folders → show nothing
                $allBuilder->whereIn('id', $allowedFolderIds ?: [0]);
                $rootBuilder->whereIn('id', $allowedFolderIds ?: [0]);
            }
            // Per-employee filter — used when the Employee Detail page
            // "File Management" tab forwards ?emp_code=<Emp_id>. Each
            // active employee's categorized folder is named after their
            // Emp_id (e.g. "DR-22"; see Employee::created hook), so a
            // Folder_Name match scopes the listing to just that folder.
            // Authorization filter above still wins — privileged users
            // see the folder, restricted users only if they had access.
            if ($request->filled('emp_code')) {
                $empCode = (string) $request->emp_code;
                $allBuilder->where('Folder_Name', $empCode);
                $rootBuilder->where('Folder_Name', $empCode);
            }
            $AllFolderList = $allBuilder->get();
            $FolderList    = $rootBuilder->get();

            // Folders for employees are auto-created with Folder_Name =
            // Employee Emp_id (e.g. "DR-19") — see EmployeeController:403.
            // The DB key stays the Emp_id (other lookups depend on that),
            // but the UI should show the name too. Build a one-shot
            // Emp_id → "Name (Emp_id)" map and decorate each folder's
            // Display_Name. Folders that don't match an Emp_id keep their
            // raw Folder_Name (manually-created folders / non-employee
            // categories).
            $empIds = $FolderList->pluck('Folder_Name')->merge($AllFolderList->pluck('Folder_Name'))->unique()->filter()->values();
            $empNameByEmpId = [];
            if ($empIds->isNotEmpty()) {
                // Use a real SELECT (not pluck-with-DB::raw — that misreads
                // the SQL alias as a property name and throws "Undefined
                // property" on rows where the join nullified the value).
                $empRows = DB::table('employees as e')
                    ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                    ->where('e.resort_id', $this->resort->resort_id)
                    ->whereIn('e.Emp_id', $empIds)
                    ->get(['e.Emp_id', 'ra.first_name', 'ra.last_name']);
                foreach ($empRows as $row) {
                    $empNameByEmpId[$row->Emp_id] = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
                }
            }
            // Identify which of the visible folders the current user got
            // through a share (vs. their own) so the view can mark them
            // with a SHARED badge. Privileged users get an empty set
            // (everything they see is "theirs to govern", not shared TO
            // them).
            $sharedFolderIdSet = [];
            $currentEmp = optional($this->resort)->GetEmployee;
            if ($currentEmp && is_array($allowedFolderIds)) {
                $sharedFolderIdSet = array_flip(\App\Http\Controllers\Resorts\FileManagment\FileShareController::visibleSharedFolderIdsFor($currentEmp));
            }

            $decorate = function ($folder) use ($empNameByEmpId, $sharedFolderIdSet) {
                $folder->Is_Shared = isset($sharedFolderIdSet[$folder->id]);
                $name = $empNameByEmpId[$folder->Folder_Name] ?? null;
                $folder->Display_Name = $name
                    ? trim($name) . ' (' . $folder->Folder_Name . ')'
                    : $folder->Folder_Name;
                return $folder;
            };
            $FolderList    = $FolderList->map($decorate);
            $AllFolderList = $AllFolderList->map($decorate);

            $department = ResortDepartment::where('resort_id', $this->resort->resort_id)->get();

            return view('resorts.FileManagment.FolderMangement.EmployeeDocuments',compact('department','page_title','FolderList','AllFolderList'));
        }

        public function CreateEmployeeFolder(Request $request)
        {

            $main_folder = $this->resort->resort->resort_id;
            $Folder_Name = $request->Folder_Name;
            $id          = isset($request->id) ?  base64_decode($request->id) : 0; 
            $resortId = $this->resort->resort_id;
            if(!isset($id))
            {
                $validator = Validator::make($request->all(), [
                                        'Folder_Name' => [
                                            'required',
                                            'string',
                                            'max:255',
                                            Rule::unique('filemangement_systems')->where(function ($query) {
                                                return $query->where('resort_id', $this->resort->resort_id)
                                                            ->where('Folder_Type', 'categorized');
                                            }),
                                        ],
                                    ], [
                                        'Folder_Name.required' => 'The folder name is required.',
                                        'Folder_Name.string' => 'The folder name must be a valid string.',
                                        'Folder_Name.max' => 'The folder name must not exceed 255 characters.',
                                        'Folder_Name.unique' => 'The folder name already exists for this resort and folder type.',
                                   
                                    ]);
                                }
            else
            {
                $validator = Validator::make($request->all(), [
                                                        'Folder_Name' => [
                                                            'required',
                                                            'string',
                                                            'max:255',
                                                            Rule::unique('filemangement_systems')->ignore($id)->where(function ($query) {
                                                                return $query->where('resort_id', $this->resort->resort_id)
                                                                            ->where('Folder_Type', 'categorized');
                                                            }),
                                                        ],
                                                    ], [
                                                        'Folder_Name.required' => 'The folder name is required.',
                                                        'Folder_Name.string' => 'The folder name must be a valid string.',
                                                        'Folder_Name.max' => 'The folder name must not exceed 255 characters.',
                                                        'Folder_Name.unique' => 'The folder name already exists for this resort and folder type.',
                                                        'Folder_Type.in' => 'The folder type must be "categorized".',
                                                    ]);
                
            }
        
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
           
                $uniqueString = substr(md5(uniqid($request->Folder_Name, true)), 0, 10);
                $flag  = $request->flag;
                if($flag == 'Main')
                {
                    $UnderON = 0;
                }              
                else
                {
                    $UnderON = base64_decode($flag);
                    $FilemangementSystem = FilemangementSystem::find($UnderON);
                }  
                    DB::beginTransaction();
                    try 
                    {
                        FilemangementSystem::updateOrCreate(["id"=>$id],[
                                'resort_id' =>$resortId ,
                                'Folder_Name' => $Folder_Name,
                                'Folder_unique_id' => $uniqueString,
                                'UnderON'=>$UnderON,
                                'Folder_Type' => 'categorized'
                        ]);
                    
                        
                        if($UnderON !=0)
                        { 
                            $folderPath = $main_folder . '/public/categorized/' .$FilemangementSystem->Folder_unique_id . '/' . $uniqueString . '/.gitkeep';
                        }
                        else
                        {
                            $folderPath = $main_folder . '/public/categorized/' . $uniqueString . '/.gitkeep';
                        }
                    
                        StorageHelper::disk()->put($folderPath, '');
                        DB::commit();
                    } 
                    catch (S3Exception $e) 
                    {
                        Log::error('AWS S3 Exception: ' . $e->getAwsErrorMessage());
                         DB::rollBack();

                        return response()->json([
                            'status' => 'error',
                            'message' => 'AWS S3 error: ' . $e->getAwsErrorMessage(),
                        ], 500);
                    } catch (Exception $e) {
                        Log::error('General S3 Storage Error: ' . $e->getMessage());
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Storage error: ' . $e->getMessage(),
                        ], 500);
                    }
                    $FolderList = FilemangementSystem::where('resort_id', $this->resort->resort_id)
                                                // ->where('UnderON', 0)
                                                ->where("Folder_Type", "uncategorized")
                                                ->orderByDesc('id')
                                                ->get();
                    $string = '';

                    foreach ($FolderList as $f) 
                    {
                        $string .= '<div class="selectFolderLocation-block">
                                        <img src="' . URL::asset('resorts_assets/images/folder.svg') . '" alt="image">
                                        <div>
                                            <input type="text" class="form-control d-none" placeholder="New Folder |" />
                                            <h5>' . htmlspecialchars($f->Folder_Name, ENT_QUOTES, 'UTF-8') . '</h5>
                                        </div>
                                        <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green selFolLoc-edit" data-name="'.$f->Folder_Name.'" data-id="' . base64_encode($f->id) . '">
                                            <img src="' . URL::asset('resorts_assets/images/edit.svg') . '" alt="" class="img-fluid" />
                                        </a>
                                    </div>';
                    }
                    $msg ='';
                    if($id !=0)
                    {
                        $msg = 'Folder updated successfully';
                    }
                    else
                    {
                        $msg = 'Folder created successfully';
                    }
                return response()->json(['success' => true, 'message' => $msg,'data'=>$string], 200);
           
             DB::beginTransaction();
            try
            {  } 
            catch (\Exception $e) 
            {
                    \Log::emergency("File: ".$e->getFile());
                    \Log::emergency("Line: ".$e->getLine());
                    \Log::emergency("Message: ".$e->getMessage());
                    return response()->json(['success' => false, 'message' => 'Failed to add Folder.'], 500);
            }

        }
        // ================================= End of Employee File  =====================================//
        // public function MoveFolder(Request $request)
        // {
        //     $FolderName = $request->FolderName[0] ?? null;
        //     $FilesName = $request->FilesName ?? null;
        //     if (!$FolderName || !$FilesName) {
        //         return response()->json(['success' => false, 'message' => 'Invalid folder or file selection.'], 400);
        //     }
        //     $parent = FilemangementSystem::where("resort_id", $this->resort->resort_id)
        //         ->where("Folder_unique_id", $FolderName)
        //         ->first();
        //         $main_folder = $this->resort->resort->resort_id;
        //     if (!$parent) {
        //         return response()->json(['success' => false, 'message' => 'Parent folder not found.'], 404);
        //     }
        //     $main_folder = $this->resort->resort->resort_id;
        //     $newFolderPath = "{$main_folder}/public/{$parent->Folder_unique_id}/";
        //     if (is_array($FilesName)) {
        //         if (is_array($FilesName)) {
        //             foreach ($FilesName as $fileUniqueId) {
        //                 $child = ChildFileManagement::where("resort_id", $this->resort->resort_id)
        //                     ->where('unique_id', $fileUniqueId)
        //                     ->first();
        //                 if ($child) {
        //                     $oldFilePath = $child->File_Path;
        //                     $newFilePath = $newFolderPath . basename($oldFilePath);
        //                     try {
        //                         StorageHelper::disk()->move($oldFilePath, $newFilePath);
        //                         $child->update([
        //                             "Parent_File_ID" => $parent->id,
        //                             "File_Path" => $newFilePath
        //                         ]);
        //                     } catch (\Exception $e) {
        //                         return response()->json(['success' => false, 'message' => 'Error moving file: ' . $e->getMessage()], 500);
        //                     }
        //                 }
        //                 else
        //                 {
        //                     $parent1 = FilemangementSystem::where("resort_id", $this->resort->resort_id)
        //                     ->where("Folder_unique_id", $FilesName)
        //                     ->first();
        //                     if(isset( $parent1->UnderON))
        //                     {
        //                         $oldfolderPath = FilemangementSystem::where("resort_id", $this->resort->resort_id)->where('id', $parent1->UnderON)->first();
        //                         $oldFolderPath = $newFolderPath = "{$main_folder}/public/{$oldfolderPath->Folder_unique_id}/";
        //                         $newFolderPath = $newFolderPath = "{$main_folder}/public/{$parent->Folder_unique_id}/";
        //                         dd($newFolderPath, $oldFolderPath);
        //                         StorageHelper::disk()->move($oldFolderPath, $newFilePath);
        //                     }
        //                 }
        //             }
        //         }    
        //     } 
        //     else 
        //     {
        //         $child = ChildFileManagement::where("resort_id", $this->resort->resort_id)
        //             ->where('unique_id', $FilesName)
        //             ->first();
                
        //         if ($child) {
        //             $child->Parent_File_ID = $parent->id;
        //             $child->save();
        //         }
        //     }
        //     $parent1 = FilemangementSystem::where("resort_id", $this->resort->resort_id)
        //         ->where("Folder_unique_id", $FilesName)
        //         ->first();
        //     if ($parent1) {
        //         FilemangementSystem::where("resort_id", $this->resort->resort_id)
        //             ->where("UnderON", $parent1->id)
        //             ->update(["UnderON" => $parent->id]);
        //         $parent1->UnderON = $parent->id;
        //         $parent1->save();
        //     }
        //     return response()->json(['success' => true,'message' => 'Successfully moved folder and selected files.'], 200);
        // }


        public function MoveFolder(Request $request)
        {
            $FolderName = $request->FolderName[0] ?? null;
            $FilesName = $request->FilesName ?? null;
        
            if (!$FolderName || !$FilesName) {
                return response()->json(['success' => false, 'message' => 'Invalid folder or file selection.'], 400);
            }
        
            // Get the parent folder where the files/folders should be moved
            $parent = FilemangementSystem::where("resort_id", $this->resort->resort_id)
                ->where("Folder_unique_id", $FolderName)
                ->first();
        
            if (!$parent) {
                return response()->json(['success' => false, 'message' => 'Parent folder not found.'], 404);
            }
        
            $main_folder = $this->resort->resort->resort_id;
            $newFolderPath = "{$main_folder}/public/{$parent->Folder_unique_id}/";
        
            if (is_array($FilesName)) {
                foreach ($FilesName as $fileUniqueId) 
                {
                    $this->moveFileOrFolder($fileUniqueId, $parent, $main_folder);
                }
            } else {
                $this->moveFileOrFolder($FilesName, $parent, $main_folder);
            }
        
            return response()->json(['success' => true, 'message' => 'Successfully moved folder and selected files.'], 200);
        }
        
        /**
         * Handles moving either a file or a folder to a new location.
         */
        private function moveFileOrFolder($fileUniqueId, $parent, $main_folder)
        {
            // Check if it's a file
            $child = ChildFileManagement::where("resort_id", $this->resort->resort_id)
                ->where('unique_id', $fileUniqueId)
                ->first();
        
            if ($child) {
                // Move the file to the new folder
                $oldFilePath = $child->File_Path;
                $newFilePath = "{$main_folder}/public/{$parent->Folder_Type}/{$parent->Folder_unique_id}/" . basename($oldFilePath);
               
                    StorageHelper::disk()->move($oldFilePath, $newFilePath);
        
                    // Update file path in database
                    $child->update([
                        "Parent_File_ID" => $parent->id,
                        "File_Path" => $newFilePath
                    ]);
                 try {} catch (\Exception $e) {
                    return response()->json(['success' => false, 'message' => 'Error moving file: ' . $e->getMessage()], 500);
                }
            } else {
                // It's a folder, move the entire folder
                $this->moveFolderWithContents($fileUniqueId, $parent, $main_folder);
            }
        }
        
        /**
         * Moves a folder and all its contents to a new parent folder.
         */
        private function moveFolderWithContents($folderUniqueId, $parent, $main_folder)
        {
            $folder = FilemangementSystem::where("resort_id", $this->resort->resort_id)
                ->where("Folder_unique_id", $folderUniqueId)
                ->first();
        
            if ($folder) {
                $oldParentFolder = FilemangementSystem::where("resort_id", $this->resort->resort_id)
                    ->where('id', $folder->UnderON)
                    ->first();
        
                if ($oldParentFolder) {
                    $oldFolderPath = "{$main_folder}/public/{$oldParentFolder->Folder_unique_id}/{$folder->Folder_unique_id}/";
                    $newFolderPath = "{$main_folder}/public/{$parent->Folder_unique_id}/{$folder->Folder_unique_id}/";
        
                    // Get all files inside the folder and move them
                    $files = StorageHelper::disk()->allFiles($oldFolderPath);
                    foreach ($files as $file) {
                        $newFilePath = str_replace($oldFolderPath, $newFolderPath, $file);
                        StorageHelper::disk()->move($file, $newFilePath);
        
                        // Update file paths in database
                        ChildFileManagement::where("resort_id", $this->resort->resort_id)
                            ->where("File_Path", $file)
                            ->update(["File_Path" => $newFilePath]);
                    }
        
                    // Move subfolders
                    $subfolders = StorageHelper::disk()->allDirectories($oldFolderPath);
                    foreach ($subfolders as $subfolder) {
                        $newSubfolderPath = str_replace($oldFolderPath, $newFolderPath, $subfolder);
                        StorageHelper::disk()->move($subfolder, $newSubfolderPath);
                    }
        
                    // Update child folders' `UnderON`
                    FilemangementSystem::where("resort_id", $this->resort->resort_id)
                        ->where("UnderON", $folder->id)
                        ->update(["UnderON" => $parent->id]);
        
                    // Update moved folder reference
                    $folder->UnderON = $parent->id;
                    $folder->save();
                }
            }
        }

        public function AdvanceSearch(Request $request)
        {
            $Folder_id = $request->Folder_id;
            $file_name = $request->file_name;
            $MainFolderType = $request->MainFolderType;
            $file_type = $request->file_type;
            $date_modified = $request->date_modified;
            $folder_type = $request->folder_id;
            $department   = $request->department;
            $flag        = $request->flag;
                $parent = FilemangementSystem::where("resort_id", $this->resort->resort_id)
                            ->where("Folder_unique_id", $Folder_id)
                            ->first();
            
                if (!$parent) {
                    return response()->json(['success' => false, 'message' => 'Folder not found.'], 404);
                }

              $parent_unique_id = $parent->Folder_unique_id;
              $mergedFiles = collect();

                $File_structure1 = FilemangementSystem::where('resort_id', $this->resort->resort_id)
                                ->where('UnderON', $parent->id)
                                ->where('Folder_Type',$flag)
                                ->orderByDesc('Folder_Name')

                                ->get()
                                ->map(function($ak)
                                {
                                    $img='';
                                    $ak->new_id = base64_encode($ak->id);
                                    $ak->File_Name = htmlspecialchars($ak->Folder_Name, ENT_QUOTES, 'UTF-8');
                                    $ak->ModifiedDate = $ak->updated_at->format('d M Y');
                                    $ak->Permission = URL::asset('resorts_assets/images/user-4.svg');
                                    $File_Size = ChildFileManagement::where("Parent_File_ID", $ak->id)
                                                                    ->where("resort_id", $this->resort->resort_id)
                                                                    ->sum('File_Size');
                                    $ak->File_Size = $File_Size;
                                    $ak->Permission = URL::asset( 'resorts_assets/images/user-4.svg');
                                    $ak->File_img =  URL::asset('resorts_assets/images/folder.svg');
                                    $ak->unique_id = $ak->Folder_unique_id;
                                    return $ak;
                                })->each(function ($folder) use ($mergedFiles ,$parent_unique_id ) 
                                {
                                        $mergedFiles->push([
                                            'id' => $folder->id,
                                            'Parent_File_ID'=>$parent_unique_id,
                                            'unique_id'=>$folder->unique_id,
                                            'new_id' => $folder->new_id,
                                            'File_Name' => $folder->File_Name,
                                            'File_Size' => $folder->File_Size ? $folder->File_Size . 'KB' : '0 KB',
                                            'ModifiedDate' => $folder->ModifiedDate,
                                            'Permission' => '',
                                            'File_img' => $folder->File_img,
                                            'Type' => 'folder', // To distinguish folders from files
                                            'NewURL'=>"FolderFile",
                                        ]);
                                });
                  
                $childQuery = ChildFileManagement::where("resort_id", $this->resort->resort_id)
                    ->where("Parent_File_ID", $parent->id);
                    if (!empty($file_name)) {
                        $childQuery->where("file_name", "LIKE", "%{$file_name}%")
                        ->orWhere("NewFileName", "LIKE", "%{$file_name}%");
                    }
                    
                    if (!empty($file_type)) 
                    {
       
                        $childQuery->where("file_type", "LIKE", "%{$file_type}%");
                    }
                    
                    if (!empty($date_modified)) 
                    {
                        $previousDate = Carbon::now()->subDays($date_modified)->toDateString(); 
                        $childQuery->whereDate("updated_at", "=", $previousDate);
                    }
                    $childQuery = $childQuery->get()
                    ->map(function($i) {
                        $imgExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];
                        $docExtensions = ['doc', 'docx',  'xls', 'xlsx', 'csv', 'ppt', 'pptx', 'txt', 'rtf'];
                        $zipExtensions = ['zip', 'rar', '7z', 'tar', 'gz'];
                        $audioExtensions = ['mp3', 'wav', 'ogg', 'm4a'];
                        $videoExtensions = ['mp4', 'avi', 'mkv', 'mov', 'wmv'];

                        $img='';
                        $i->new_id = base64_encode($i->id);
                        $i->File_Name =  !empty($i->NewFileName) ?   htmlspecialchars($i->NewFileName, ENT_QUOTES, 'UTF-8') : htmlspecialchars($i->File_Name, ENT_QUOTES, 'UTF-8');
                        $i->ModifiedDate = $i->updated_at->format('d M Y');
                        $i->Permission = URL::asset(path: 'resorts_assets/images/user-4.svg');
                        $i->File_Size = $i->File_Size.' KB';
                        if (in_array($i->File_Extension, $imgExtensions)) {
                            $img = URL::asset('resorts_assets/images/image.svg'); // Image icon
                        } elseif (in_array($i->File_Extension, haystack: $docExtensions)) {
                            $img = URL::asset('resorts_assets/images/word.svg'); // Document icon
                        } elseif (in_array($i->File_Extension, $zipExtensions)) {
                            $img = URL::asset('resorts_assets/images/zip.svg'); // Archive icon
                        } elseif (in_array($i->File_Extension, $audioExtensions)) {
                            $img = URL::asset('resorts_assets/images/audio.svg'); // Audio file icon
                        } elseif (in_array($i->File_Extension, $videoExtensions)) {
                            $img = URL::asset('resorts_assets/images/video.svg'); // Video file icon
                        } 
                        elseif ($i->File_Extension ==  "pdf") {
                            $img = URL::asset('resorts_assets/images/pdf1.svg'); // Video file icon
                        } 
                        else
                        {
                            $img = URL::asset('resorts_assets/images/default.svg'); // Default icon
                        }
                    
                            $i->NewURL = "InternaFile";// URL valid for 10 minutes
                    
                        $i->unique_id = $i->unique_id;
                        $i->File_img = $img;
                        return $i;    
                    })
                    ->each(function ($file) use ($mergedFiles,$parent_unique_id ,$flag)
                    {
                
                    
                        $resort =  $this->resort;
                        $filePermission = Common::FilePermissions($file->unique_id, $resort, $flag);
                        
                        if(isset($filePermission['type']) && $filePermission['type'] == true)
                        {
                            $emp='<div class="user-ovImg user-ovImgTable">';
                            if(array_key_exists('emp',$filePermission))
                            {
                                foreach($filePermission['emp'] as $f)
                                {
                                    $emp.='<div class="img-circle"> <img src="'.$f['profile'].'"></div>';
                                }
                            }
                            $emp.="</div>";
                                $mergedFiles->push([
                                    'id' => $file->id,
                                    'Parent_File_ID'=>$parent_unique_id,
                                    'unique_id'=>$file->unique_id,
                                    'new_id' => $file->new_id,
                                    'File_Name' => $file->File_Name,
                                    'File_Size' => $file->File_Size,
                                    'ModifiedDate' => $file->ModifiedDate,
                                    'Permission' => $emp,
                                    'File_img' => $file->File_img,
                                    'Type' => 'file', // To distinguish folders from files
                                    'NewURL' => $file->NewURL// File URL if available
                                ]);
                        }

                        
                    });
                $mergedFiles = $mergedFiles->values();

                $tr ='';
                
             
                if($mergedFiles->isNotEmpty())
                {
                    foreach( $mergedFiles as $f)
                    {              
                        $tr .= '<tr>
                                    <td>
                                            <div class="form-check no-label">
                                                <input class="form-check-input internacheck checkCheck d-none" type="checkbox" name="FilesName[]" data-id="'.$f['Parent_File_ID'].'" value="'.$f['unique_id'].'" >
                                            </div>
                                    <td> <a href="javascript:void(0)" class="OpenFileorFolder " data-unique_id = "'. $f['unique_id'].'" data-url = "'. $f['NewURL'].'"> <img src="' . $f['File_img'] . '" alt="images"> ' . $f['File_Name'] . '</a></td>
                                    <td>' . $f['File_Size'] . ' </td>
                                    <td>' . $f['ModifiedDate'] . '</td>
                                    <td>'.$f['Permission'].'</td>
                                    <td>
                                        <div class="context-btn" data-name="'.$f['File_Name'].'" data-id="'.$f['unique_id'].'" > <i class="fa-solid fa-ellipsis"></i></div>
                                    </td>
                                </tr>';
                    }
                }
                else
                {
                    $tr = '<tr><td colspan="8" style="text-align: center;">No record found </td></tr>';
                }
                return response()->json(['success' => true, 'data' => $tr], 200);
        }


        public function  AuditlogStore(Request $request)
        {

            $unqiue_id =  $request->unqiue_id;
            $child = ChildFileManagement::where("resort_id", $this->resort->resort_id)
                                ->where('unique_id', $unqiue_id)
                                ->first();
             $id = AuditLogs::create([
                'resort_id' => $this->resort->resort_id,
                "file_id"   => $child->id,
                "TypeofAction" => "Download",
                "file_path" => $child->File_Path,
                ]);
        }


        public function AuditLogsList(Request $request)
        {
            // Was setting ModifiedBy to the avatar URL only, with no join to
            // resort_admins, so the column rendered just an avatar img and
            // the name never showed up. Join resort_admins to pull the name
            // and render avatar + name together.
            $ChildFiles = AuditLogs::join('child_file_management as t1', 't1.id', '=', 'audit_logs.file_id')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'audit_logs.created_by')
            ->where('audit_logs.resort_id', $this->resort->resort_id)
            ->orderByDesc('audit_logs.id')
            ->groupBy('audit_logs.id')
            ->get([
                't1.File_Name as FileName',
                'ra.first_name as ModifierFirstName',
                'ra.last_name  as ModifierLastName',
                'audit_logs.*'
            ])
            ->map(function($i) {
                $i->ModifierName  = trim(($i->ModifierFirstName ?? '') . ' ' . ($i->ModifierLastName ?? '')) ?: 'Unknown user';
                $i->ModifierPic   = Common::getResortUserPicture($i->created_by);
                $i->Time          = $i->created_at->format('H:i:s');
                $i->LastModified  = $i->created_at->format('d M Y');
                $i->ActionType    = $i->TypeofAction;
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
                                                $imgUrl = $row->ModifierPic ?: asset('resorts_assets/images/user-2.svg');
                                                $name   = e($row->ModifierName);

                                                return '<div class="d-flex align-items-center gap-2">
                                                            <div class="user-ovImg user-ovImgTable"><div class="img-circle">
                                                                <img src="'.$imgUrl.'" alt="'.$name.'">
                                                            </div></div>
                                                            <span>'.$name.'</span>
                                                        </div>';
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
            return view('resorts.FileManagment.AuditLog.index',compact('page_title'));
        }


        public function FileVersionList(Request $request)
        {
            $existingVersion = FileVersion::join('child_file_management as t1', 't1.id', '=', 'file_versions.file_id')
                                            ->join('resort_admins as t2', 't2.id', '=', 'file_versions.created_by')
                                            ->where('file_versions.resort_id', $this->resort->resort_id)
                                            ->whereDate('file_versions.created_at', Carbon::today())
                                            ->orderBy('file_versions.version_number', 'desc')
                                            ->get(['t2.first_name','t2.last_name',
                                            't1.Parent_File_ID',
                                            't1.File_Name',
                                            't1.File_Type',
                                            't1.File_Size',
                                            't1.File_Path',
                                            't1.File_Extension',
                                            't1.File_Name as FileName',
                                            't1.NewFileName'                                           
                                            ,'file_versions.*'])
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
                                                // FiLes Structure
                                                $img='';
                                            
                                                if (in_array($i->File_Extension, $imgExtensions)) {
                                                    $img = URL::asset('resorts_assets/images/image.svg'); // Image icon
                                                } elseif (in_array($i->File_Extension, haystack: $docExtensions)) {
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
            return view('resorts.FileManagment.Version.FileVersionHistory',compact('page_title'));
        }
        
        public function CreateFileVersion($OldFile_id,$NewFile_id)
        {
            $child = ChildFileManagement::where("resort_id", $this->resort->resort_id)
                                        ->where('id', $OldFile_id)
                                        ->first();


            $existingVersion = FileVersion::where('resort_id', $this->resort->resort_id)
                                          ->where('file_id', $OldFile_id)
                                          ->orderBy('version_number', 'desc')
                                          ->first();
            $version_number = isset($existingVersion->version_number) ? $existingVersion->version_number + 1 : 1;
      

                $id = FileVersion::create([
                                        'version_number' => $version_number,
                                        'resort_id'      => $this->resort->resort_id,
                                        'file_id'        => $NewFile_id,
                                        'file_path'     =>  $child->File_Path,
                                    ]);
                // Update filename with version suffix
                $fileExt = pathinfo($child->File_Name, PATHINFO_EXTENSION);
                $fileBase = pathinfo($child->File_Name, PATHINFO_FILENAME);

                $newFileName = $fileBase . '_v' . $version_number . '.' . $child->File_Extension;
               
                $data = ChildFileManagement::where("resort_id", $this->resort->resort_id)
                                    ->where('id', $NewFile_id)
                                    ->update(['NewFileName'=>$newFileName]);
                return true;
          
            // else: no existing version, so you can skip creating a new one
        }

}

