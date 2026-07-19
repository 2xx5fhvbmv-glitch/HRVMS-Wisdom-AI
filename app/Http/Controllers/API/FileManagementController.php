<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Resorts\FileManagment\FileShareController;
use App\Models\AuditLogs;
use App\Models\ChildFileManagement;
use App\Models\Employee;
use App\Models\FilemangementSystem;
use App\Helpers\Common;
use App\Helpers\StorageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Mobile parity for the web "File Management" module (FileManageController).
 * Scoped to the employee's own categorized folder tree (root + one level of
 * subfolders — the same depth the storage-path building in
 * Common::AWSEmployeeFileUpload()/FileManageController::StoreFolderFiles()
 * already assumes) plus anything explicitly shared with them via FileShare.
 * Reads/writes the same filemangement_systems/child_file_management tables
 * the web portal uses, so uploads from either side show up on both.
 */
class FileManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    private function currentEmployee()
    {
        $user = Auth::guard('api')->user();
        if (!$user || !$user->GetEmployee) {
            return null;
        }
        return $user->GetEmployee;
    }

    /**
     * Own root folder — same firstOrCreate the web/other mobile upload
     * flows rely on, so an employee who somehow predates the backfill
     * migration still gets a working folder here.
     */
    private function ownRootFolder($resortId, Employee $emp)
    {
        $folder = FilemangementSystem::firstOrCreate(
            [
                'resort_id'   => $resortId,
                'Folder_Name' => $emp->Emp_id,
                'Folder_Type' => 'categorized',
            ],
            [
                'Folder_unique_id' => substr(md5(uniqid($emp->Emp_id, true)), 0, 10),
                'UnderON'          => 0,
            ]
        );
        if ($folder->wasRecentlyCreated) {
            $mainFolder = $emp->resort->resort_id ?? $resortId;
            StorageHelper::disk()->put($mainFolder . '/public/categorized/' . $folder->Folder_unique_id . '/.gitkeep', '');
        }
        return $folder;
    }

    private function sharedFolderIds(Employee $emp): array
    {
        return FileShareController::visibleSharedFolderIdsFor($emp);
    }

    /** Same three scopes as visibleSharedFolderIdsFor(), for shareable_type='file'. */
    private function sharedFileIds(Employee $emp): array
    {
        $direct = DB::table('file_shares as fs')
            ->join('file_share_employees as fse', 'fse.share_id', '=', 'fs.id')
            ->where('fs.shareable_type', 'file')
            ->where('fse.employee_id', $emp->id)
            ->pluck('fs.shareable_id')->all();

        $dept = [];
        if ($emp->Dept_id) {
            $dept = DB::table('file_shares as fs')
                ->join('file_share_departments as fsd', 'fsd.share_id', '=', 'fs.id')
                ->where('fs.shareable_type', 'file')
                ->where('fsd.department_id', $emp->Dept_id)
                ->pluck('fs.shareable_id')->all();
        }

        $org = DB::table('file_shares')
            ->where('shareable_type', 'file')
            ->where('share_mode', 'internal')
            ->where('scope_type', 'organization')
            ->where('resort_id', $emp->resort_id)
            ->pluck('shareable_id')->all();

        return array_values(array_unique(array_merge($direct, $dept, $org)));
    }

    /** Root itself, a direct subfolder of root, a shared folder, or a subfolder of a shared folder. */
    private function folderAccessible(FilemangementSystem $folder, int $ownRootId, array $sharedFolderIds): bool
    {
        if ($folder->id === $ownRootId || (int) $folder->UnderON === $ownRootId) {
            return true;
        }
        return in_array($folder->id, $sharedFolderIds, true) || in_array((int) $folder->UnderON, $sharedFolderIds, true);
    }

    /** Write actions (create/upload/rename/delete) are restricted to the employee's own tree — shares here are read-only. */
    private function isOwnSubtree(FilemangementSystem $folder, int $ownRootId): bool
    {
        return $folder->id === $ownRootId || (int) $folder->UnderON === $ownRootId;
    }

    private function fileMeta(ChildFileManagement $file): array
    {
        return [
            'id'            => $file->id,
            'unique_id'     => $file->unique_id,
            'file_name'     => $file->NewFileName ?: $file->File_Name,
            'file_extension'=> $file->File_Extension,
            'file_size_kb'  => (float) $file->File_Size,
            'uploaded_date' => $file->File_Upload_Date,
            'uploaded_time' => $file->File_Upload_Time,
            'updated_at'    => optional($file->updated_at)->format('d M Y, h:i A'),
        ];
    }

    private function folderMeta(FilemangementSystem $folder, int $ownRootId): array
    {
        return [
            'id'        => $folder->id,
            'unique_id' => $folder->Folder_unique_id,
            'name'      => $folder->Folder_Name,
            'is_own'    => $folder->id === $ownRootId || (int) $folder->UnderON === $ownRootId,
        ];
    }

    /** Subfolders directly under this folder + files stored directly in it. Uniform for root and one-level-deep subfolders. */
    private function buildFolderContents(FilemangementSystem $folder, int $ownRootId): array
    {
        $subfolders = FilemangementSystem::where('resort_id', $folder->resort_id)
            ->where('UnderON', $folder->id)
            ->where('Folder_Type', 'categorized')
            ->withSum('children as file_count_sum', 'File_Size')
            ->withCount('children as file_count')
            ->orderBy('Folder_Name')
            ->get()
            ->map(function ($f) use ($ownRootId) {
                $meta = $this->folderMeta($f, $ownRootId);
                $meta['file_count'] = $f->file_count;
                $meta['total_size_kb'] = (float) ($f->file_count_sum ?? 0);
                return $meta;
            });

        $files = ChildFileManagement::where('resort_id', $folder->resort_id)
            ->where('Parent_File_ID', $folder->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn ($f) => $this->fileMeta($f));

        return [
            'folder'     => $this->folderMeta($folder, $ownRootId),
            'subfolders' => $subfolders,
            'files'      => $files,
        ];
    }

    /**
     * GET resort/filemanagement/my-folder
     * Employee's own root folder — its subfolders and any files stored directly at the root.
     */
    public function myFolder(Request $request)
    {
        $emp = $this->currentEmployee();
        if (!$emp) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $resortId = Auth::guard('api')->user()->resort_id;
        $root = $this->ownRootFolder($resortId, $emp);

        return response()->json([
            'success' => true,
            'data'    => $this->buildFolderContents($root, $root->id),
        ], 200);
    }

    /**
     * GET resort/filemanagement/shared-with-me
     * Folders and files explicitly shared with this employee (directly, via department, or organization-wide).
     */
    public function sharedWithMe(Request $request)
    {
        $emp = $this->currentEmployee();
        if (!$emp) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $resortId = Auth::guard('api')->user()->resort_id;
        $root = $this->ownRootFolder($resortId, $emp);

        $sharedFolderIds = $this->sharedFolderIds($emp);
        $folders = FilemangementSystem::where('resort_id', $resortId)
            ->whereIn('id', $sharedFolderIds ?: [0])
            ->withCount('children as file_count')
            ->get()
            ->map(function ($f) use ($root) {
                $meta = $this->folderMeta($f, $root->id);
                $meta['is_own'] = false;
                $meta['file_count'] = $f->file_count;
                return $meta;
            });

        $sharedFileIds = $this->sharedFileIds($emp);
        $files = ChildFileManagement::where('resort_id', $resortId)
            ->whereIn('id', $sharedFileIds ?: [0])
            ->orderByDesc('id')
            ->get()
            ->map(fn ($f) => $this->fileMeta($f));

        return response()->json([
            'success' => true,
            'data'    => ['folders' => $folders, 'files' => $files],
        ], 200);
    }

    /**
     * GET resort/filemanagement/folder/{unique_id}
     * Contents of a subfolder — own tree or shared.
     */
    public function folderContents(Request $request, $unique_id)
    {
        $emp = $this->currentEmployee();
        if (!$emp) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $resortId = Auth::guard('api')->user()->resort_id;
        $root = $this->ownRootFolder($resortId, $emp);

        $folder = FilemangementSystem::where('resort_id', $resortId)
            ->where('Folder_unique_id', $unique_id)
            ->where('Folder_Type', 'categorized')
            ->first();
        if (!$folder) {
            return response()->json(['success' => false, 'message' => 'Folder not found.'], 404);
        }
        if (!$this->folderAccessible($folder, $root->id, $this->sharedFolderIds($emp))) {
            return response()->json(['success' => false, 'message' => 'You do not have access to this folder.'], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->buildFolderContents($folder, $root->id),
        ], 200);
    }

    /**
     * POST resort/filemanagement/create-folder
     * Creates a subfolder directly under the employee's own root (write actions never touch shared folders).
     */
    public function createFolder(Request $request)
    {
        $emp = $this->currentEmployee();
        if (!$emp) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'Folder_Name' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $resortId = Auth::guard('api')->user()->resort_id;
        $root = $this->ownRootFolder($resortId, $emp);
        $name = $request->Folder_Name;

        $existing = FilemangementSystem::where('resort_id', $resortId)
            ->where('UnderON', $root->id)
            ->where('Folder_Type', 'categorized')
            ->where('Folder_Name', $name)
            ->first();
        if ($existing) {
            return response()->json([
                'success' => true,
                'message' => 'Folder already exists.',
                'data'    => $this->folderMeta($existing, $root->id),
            ], 200);
        }

        $folder = FilemangementSystem::create([
            'resort_id'        => $resortId,
            'Folder_Name'      => $name,
            'Folder_unique_id' => substr(md5(uniqid($name, true)), 0, 10),
            'UnderON'          => $root->id,
            'Folder_Type'      => 'categorized',
        ]);
        $mainFolder = $emp->resort->resort_id ?? $resortId;
        StorageHelper::disk()->put($mainFolder . '/public/categorized/' . $root->Folder_unique_id . '/' . $folder->Folder_unique_id . '/.gitkeep', '');

        return response()->json([
            'success' => true,
            'message' => 'Folder created successfully.',
            'data'    => $this->folderMeta($folder, $root->id),
        ], 200);
    }

    /**
     * POST resort/filemanagement/upload
     * file (required, multipart), folder_unique_id (optional — defaults to own root).
     * Reuses Common::AWSEmployeeFileUpload() so storage path/encryption stays
     * identical to every other mobile upload flow that already writes into
     * this same categorized-folder system (Leave, Clinic, Accommodation, etc).
     */
    public function upload(Request $request)
    {
        $emp = $this->currentEmployee();
        if (!$emp) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:20480',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $resortId = Auth::guard('api')->user()->resort_id;
        $root = $this->ownRootFolder($resortId, $emp);

        $subFolderName = null;
        if ($request->filled('folder_unique_id')) {
            $target = FilemangementSystem::where('resort_id', $resortId)
                ->where('Folder_unique_id', $request->folder_unique_id)
                ->where('Folder_Type', 'categorized')
                ->first();
            if (!$target) {
                return response()->json(['success' => false, 'message' => 'Folder not found.'], 404);
            }
            if (!$this->isOwnSubtree($target, $root->id)) {
                return response()->json(['success' => false, 'message' => 'You can only upload to your own folder.'], 403);
            }
            if ($target->id !== $root->id) {
                $subFolderName = $target->Folder_Name;
            }
        }

        $status = Common::AWSEmployeeFileUpload($resortId, $request->file('file'), $emp->Emp_id, $subFolderName, true);
        if (empty($status['status'])) {
            return response()->json(['success' => false, 'message' => 'Upload failed: ' . ($status['msg'] ?? 'Unknown error')], 400);
        }

        $fileRecord = ChildFileManagement::find($status['Chil_file_id']);

        // Notify HR — no code path notified anyone when an employee
        // uploaded a file, from either mobile or web.
        $hrEmployeeIds = array_values(array_diff(Common::getResortHrEmployeeIds($resortId), [$emp->id]));
        if (!empty($hrEmployeeIds)) {
            Common::sendMobileNotification(
                $resortId,
                2,
                null,
                null,
                'File Uploaded',
                ($emp->resortAdmin->full_name ?? $emp->Emp_id) . ' uploaded a file: ' . $request->file('file')->getClientOriginalName(),
                'File Management',
                $hrEmployeeIds,
                null,
                false,
                'file-management-upload',
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully.',
            'data'    => $fileRecord ? $this->fileMeta($fileRecord) : null,
        ], 200);
    }

    /**
     * POST resort/filemanagement/rename
     * type: 'file'|'folder', unique_id, new_name. Own tree only; the root folder itself
     * (Folder_Name == Emp_id) can't be renamed — other lookups key off that invariant.
     */
    public function rename(Request $request)
    {
        $emp = $this->currentEmployee();
        if (!$emp) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'type'      => 'required|in:file,folder',
            'unique_id' => 'required|string',
            'new_name'  => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $resortId = Auth::guard('api')->user()->resort_id;
        $root = $this->ownRootFolder($resortId, $emp);

        if ($request->type === 'folder') {
            $folder = FilemangementSystem::where('resort_id', $resortId)
                ->where('Folder_unique_id', $request->unique_id)
                ->where('Folder_Type', 'categorized')
                ->first();
            if (!$folder) {
                return response()->json(['success' => false, 'message' => 'Folder not found.'], 404);
            }
            if ($folder->id === $root->id) {
                return response()->json(['success' => false, 'message' => 'Your root folder cannot be renamed.'], 403);
            }
            if (!$this->isOwnSubtree($folder, $root->id)) {
                return response()->json(['success' => false, 'message' => 'You can only rename your own folders.'], 403);
            }
            $folder->Folder_Name = $request->new_name;
            $folder->save();
            return response()->json(['success' => true, 'message' => 'Folder renamed successfully.'], 200);
        }

        $file = ChildFileManagement::where('resort_id', $resortId)
            ->where('unique_id', $request->unique_id)
            ->first();
        if (!$file) {
            return response()->json(['success' => false, 'message' => 'File not found.'], 404);
        }
        $parent = FilemangementSystem::find($file->Parent_File_ID);
        if (!$parent || !$this->isOwnSubtree($parent, $root->id)) {
            return response()->json(['success' => false, 'message' => 'You can only rename your own files.'], 403);
        }
        $file->File_Name = $request->new_name;
        $file->save();

        AuditLogs::create([
            'resort_id'    => $resortId,
            'file_id'      => $file->id,
            'TypeofAction' => 'Rename',
            'file_path'    => $file->File_Path,
        ]);

        return response()->json(['success' => true, 'message' => 'File renamed successfully.'], 200);
    }

    /**
     * POST resort/filemanagement/delete-file
     * unique_id (file). Own tree only.
     */
    public function deleteFile(Request $request)
    {
        $emp = $this->currentEmployee();
        if (!$emp) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'unique_id' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $resortId = Auth::guard('api')->user()->resort_id;
        $root = $this->ownRootFolder($resortId, $emp);

        $file = ChildFileManagement::where('resort_id', $resortId)
            ->where('unique_id', $request->unique_id)
            ->first();
        if (!$file) {
            return response()->json(['success' => false, 'message' => 'File not found.'], 404);
        }
        $parent = FilemangementSystem::find($file->Parent_File_ID);
        if (!$parent || !$this->isOwnSubtree($parent, $root->id)) {
            return response()->json(['success' => false, 'message' => 'You can only delete your own files.'], 403);
        }

        try {
            if ($file->File_Path && StorageHelper::disk()->exists($file->File_Path)) {
                StorageHelper::disk()->delete($file->File_Path);
            }
            AuditLogs::create([
                'resort_id'    => $resortId,
                'file_id'      => $file->id,
                'TypeofAction' => 'Delete',
                'file_path'    => $file->File_Path,
            ]);
            $file->delete();
            return response()->json(['success' => true, 'message' => 'File deleted successfully.'], 200);
        } catch (\Throwable $e) {
            \Log::error('Mobile FileManagement deleteFile failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Could not delete the file.'], 500);
        }
    }

    /**
     * GET resort/filemanagement/file/{unique_id}/download
     * Own tree, shared folder cascade, or a direct file-level share.
     */
    public function downloadFile(Request $request, $unique_id)
    {
        $emp = $this->currentEmployee();
        if (!$emp) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $resortId = Auth::guard('api')->user()->resort_id;
        $root = $this->ownRootFolder($resortId, $emp);

        $file = ChildFileManagement::where('resort_id', $resortId)
            ->where('unique_id', $unique_id)
            ->first();
        if (!$file) {
            return response()->json(['success' => false, 'message' => 'File not found.'], 404);
        }

        $parent = FilemangementSystem::find($file->Parent_File_ID);
        $accessible = $parent && $this->folderAccessible($parent, $root->id, $this->sharedFolderIds($emp));
        if (!$accessible && !in_array($file->id, $this->sharedFileIds($emp), true)) {
            return response()->json(['success' => false, 'message' => 'You do not have access to this file.'], 403);
        }

        try {
            $result = Common::GetAWSFile($file->id, $resortId);
            if (empty($result['success'])) {
                return response()->json(['success' => false, 'message' => 'This file is no longer available in storage.'], 404);
            }
            return response()->json([
                'success'   => true,
                'url'       => $result['NewURLshow'],
                'mime_type' => $result['mimeType'],
            ], 200);
        } catch (\Throwable $e) {
            \Log::error('Mobile FileManagement downloadFile failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Could not generate a download link.'], 500);
        }
    }
}
