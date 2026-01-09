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
use Barryvdh\DomPDF\Facade\Pdf;
class FileManageController extends Controller
{
        protected $resort;
        protected $underEmp_id=[];
        public function __construct()
        {    
            $this->resort = $resortId = auth()->guard('resort-admin')->user();
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

                  
                        Storage::disk('s3')->put($folderPath, '');
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
            $FolderList = FilemangementSystem::where('resort_id', $this->resort->resort_id)
                    ->where('UnderON', 0);
                if($Search != '')
                {
                    $FolderList->where('Folder_Name', 'like', '%' . $Search . '%');
                }
                $FolderList= $FolderList->where("Folder_Type", $flag)
                    ->orderByDesc('id')
                    ->get();

            $string = '';
            if($FolderList->isNotEmpty())
            {
                foreach ($FolderList as $f) 
                {
                    $string .= '<div class="d-flex">
                                <div class="showStructure" data-unique_id="'. htmlspecialchars($f->Folder_unique_id, ENT_QUOTES, 'UTF-8') .'">
                                    <div class="img-circle userImg-block">
                                        <img src="' . URL::asset('resorts_assets/images/folder.svg') . '" alt="image">
                                    </div>
                                    <div>
                                        <h6>' . htmlspecialchars($f->Folder_Name, ENT_QUOTES, 'UTF-8') . '</h6>
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
                        Storage::disk('s3')->put($path, $encrypted, [
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
            $File_structure = FilemangementSystem::where('resort_id', $this->resort->resort_id)
                                ->where('Folder_unique_id', $id)
                                ->where('Folder_Type',$flag)
                                ->first();
                            
            $parent_unique_id = $File_structure->Folder_unique_id;
            $mergedFiles = collect();
            $File_structure1 = FilemangementSystem::where('resort_id', $this->resort->resort_id)
                                ->where('UnderON', $File_structure->id)
                                ->orderByDesc('Folder_Name')
                                ->where('Folder_Type',$flag)

                                ->get()
                                ->map(function($ak){
                                    $img='';
                                    $ak->new_id = base64_encode($ak->id);
                                    $ak->File_Name =  htmlspecialchars($ak->Folder_Name, ENT_QUOTES, 'UTF-8');
                                    $ak->ModifiedDate = $ak->updated_at->format('d-m-Y');
                                    $ak->Permission = URL::asset('resorts_assets/images/user-4.svg');
                                    $File_Size = ChildFileManagement::where("Parent_File_ID", $ak->id)
                                                                    ->where("resort_id", $this->resort->resort_id)
                                                                    ->sum('File_Size');
                                    $ak->File_Size = $File_Size;
                                    $ak->Permission = URL::asset( 'resorts_assets/images/user-4.svg');
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
                                    $i->ModifiedDate = $i->updated_at->format('d-m-Y');
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
                                ->each(function ($file) use ($mergedFiles,$parent_unique_id,$flag ) 
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
                                $ak->ModifiedDate = $ak->updated_at->format('d-m-Y');
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
                            $i->ModifiedDate = $i->updated_at->format('d-m-Y');
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
                if (isset($ChildFiles) && Storage::disk('s3')->exists($ChildFiles->File_Path)) {
                   
                        // Generate encryption key from environment variable
                        $key = hash('sha256', env('ENCRYPTION_KEY'), true);
                        
                        // Get encrypted data from S3
                        $encryptedData = Storage::disk('s3')->get($ChildFiles->File_Path);
                        
                        // Validate encrypted data
                        if (empty($encryptedData) || strlen($encryptedData) < 16) {
                            throw new \Exception('Invalid or corrupted encrypted data');
                        }
                        
                        // Extract IV and cipherText - AES block size is 16 bytes
                        $iv = substr($encryptedData, 0, 16);
                        $cipherText = substr($encryptedData, 16);
                        
                        // Decrypt the data with OPENSSL_RAW_DATA flag to handle binary data correctly
                        $decryptedData = openssl_decrypt(
                            $cipherText,
                            'aes-256-cbc',
                            $key,
                            OPENSSL_RAW_DATA,  // Critical for handling binary data properly
                            $iv
                        );
                        
                        // Check if decryption was successful
                        if ($decryptedData === false) {
                            $error = openssl_error_string();
                            throw new \Exception("Decryption failed: {$error}");
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
                        Storage::disk('s3')->put($tempFilePath, $decryptedData, [
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
                        $newUrl = Storage::disk('s3')->temporaryUrl($tempFilePath, now()->addMinutes(30));
                    } 
                    else 
                    {
                        $mimeType='';
                    $newUrl = "No";
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


            $AllFolderList = FilemangementSystem::where('resort_id', $this->resort->resort_id)
                                            // ->where('UnderON', 0)
                                            ->where("Folder_Type", "categorized")
                                            ->orderByDesc('id')
                                            ->get();
            $FolderList = FilemangementSystem::where('resort_id', $this->resort->resort_id)
                                                                            ->where('UnderON', 0)
                                                                            ->where("Folder_Type", "categorized")
                                                                            ->orderByDesc('id')
                                                                            ->get();

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
                    
                        Storage::disk('s3')->put($folderPath, '');
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
        //                         Storage::disk('s3')->move($oldFilePath, $newFilePath);
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
        //                         Storage::disk('s3')->move($oldFolderPath, $newFilePath);
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
               
                    Storage::disk('s3')->move($oldFilePath, $newFilePath);
        
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
                    $files = Storage::disk('s3')->allFiles($oldFolderPath);
                    foreach ($files as $file) {
                        $newFilePath = str_replace($oldFolderPath, $newFolderPath, $file);
                        Storage::disk('s3')->move($file, $newFilePath);
        
                        // Update file paths in database
                        ChildFileManagement::where("resort_id", $this->resort->resort_id)
                            ->where("File_Path", $file)
                            ->update(["File_Path" => $newFilePath]);
                    }
        
                    // Move subfolders
                    $subfolders = Storage::disk('s3')->allDirectories($oldFolderPath);
                    foreach ($subfolders as $subfolder) {
                        $newSubfolderPath = str_replace($oldFolderPath, $newFolderPath, $subfolder);
                        Storage::disk('s3')->move($subfolder, $newSubfolderPath);
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
                                    $ak->ModifiedDate = $ak->updated_at->format('d-m-Y');
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
                        $i->ModifiedDate = $i->updated_at->format('d-m-Y');
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
            $ChildFiles = AuditLogs::join('child_file_management as t1', 't1.id', '=', 'audit_logs.file_id')
            ->where('audit_logs.resort_id', $this->resort->resort_id)
            // ->whereDate('audit_logs.created_at', Carbon::today()) // Filter for today's date
            ->orderByDesc('audit_logs.id')
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

