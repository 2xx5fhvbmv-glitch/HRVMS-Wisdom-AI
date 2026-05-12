<?php

namespace App\Http\Controllers\Resorts\FileManagment;

use App\Http\Controllers\Controller;
use App\Models\ChildFileManagement;
use App\Models\Employee;
use App\Models\FilemangementSystem;
use App\Models\FileShare;
use App\Models\ResortAdmin;
use App\Models\ResortDepartment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Internal sharing for files (child_file_management) and folders
 * (filemangement_systems). Three scope modes:
 *   - employees:    explicit list (file_share_employees pivot)
 *   - departments:  explicit list (file_share_departments pivot)
 *   - organization: no pivot rows; resolved as "all active employees of
 *                   the sharer's resort" at read time
 *
 * Cross-resort employee picking is allowed per project memo.
 */
class FileShareController extends Controller
{
    protected $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
    }

    /**
     * Create a share. Idempotent for the same (shareable + recipient)
     * combination: a re-share of the same file to the same person
     * reuses the existing share row rather than stacking duplicates.
     */
    public function store(Request $request)
    {
        if (!$this->resort) {
            return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        $v = Validator::make($request->all(), [
            'shareable_type'       => 'required|in:file,folder',
            'shareable_id'         => 'nullable|integer',
            'shareable_unique_id'  => 'nullable|string',
            'scope_type'           => 'required|in:employees,departments,organization',
            'employee_ids'         => 'required_if:scope_type,employees|array',
            'employee_ids.*'       => 'integer',
            'department_ids'       => 'required_if:scope_type,departments|array',
            'department_ids.*'     => 'integer',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);
        }

        $shareableType = $request->shareable_type;
        // Accept either the numeric DB id (folder path) or unique_id
        // (file path) — files don't carry their DB id in the listing
        // markup, so the share modal posts unique_id and we resolve.
        $shareableId = (int) $request->shareable_id;
        if (!$shareableId && $request->shareable_unique_id) {
            if ($shareableType === 'file') {
                $shareableId = (int) ChildFileManagement::where('unique_id', $request->shareable_unique_id)
                    ->where('resort_id', $this->resort->resort_id)
                    ->value('id');
            } else {
                $shareableId = (int) FilemangementSystem::where('Folder_unique_id', $request->shareable_unique_id)
                    ->where('resort_id', $this->resort->resort_id)
                    ->value('id');
            }
        }
        if (!$shareableId) {
            return response()->json(['success' => false, 'message' => 'Item not found'], 404);
        }

        // Verify the file/folder belongs to the sharer's resort.
        $ok = $this->ownsShareable($shareableType, $shareableId);
        if (!$ok) {
            return response()->json(['success' => false, 'message' => 'Item not found or not yours to share'], 404);
        }

        $scopeType = $request->scope_type;

        try {
            DB::beginTransaction();

            // Reuse an existing internal share for the same item +
            // scope_type if one exists (so re-opening the modal and
            // adding more recipients merges rather than duplicates).
            $share = FileShare::firstOrNew([
                'shareable_type' => $shareableType,
                'shareable_id'   => $shareableId,
                'share_mode'     => 'internal',
                'scope_type'     => $scopeType,
                'shared_by'      => $this->resort->id,
            ]);
            $share->resort_id   = $this->resort->resort_id;
            $share->permissions = ['view' => true];
            $share->save();

            if ($scopeType === 'employees') {
                // Insert ignoring duplicates — the table PK is
                // (share_id, employee_id) so duplicates would error.
                $existing = DB::table('file_share_employees')
                    ->where('share_id', $share->id)
                    ->pluck('employee_id')->all();
                $newOnes = array_diff($request->employee_ids, $existing);
                $rows = [];
                $now = now();
                foreach ($newOnes as $eid) {
                    $rows[] = [
                        'share_id'    => $share->id,
                        'employee_id' => (int) $eid,
                        'created_at'  => $now,
                    ];
                }
                if (!empty($rows)) DB::table('file_share_employees')->insert($rows);
            }

            if ($scopeType === 'departments') {
                $existing = DB::table('file_share_departments')
                    ->where('share_id', $share->id)
                    ->pluck('department_id')->all();
                $newOnes = array_diff($request->department_ids, $existing);
                $rows = [];
                $now = now();
                foreach ($newOnes as $did) {
                    $rows[] = [
                        'share_id'      => $share->id,
                        'department_id' => (int) $did,
                        'created_at'    => $now,
                    ];
                }
                if (!empty($rows)) DB::table('file_share_departments')->insert($rows);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('FileShare store failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Could not save share: ' . $e->getMessage()], 500);
        }

        return response()->json(['success' => true, 'share_id' => $share->id, 'message' => 'Share saved']);
    }

    /** Revoke (delete) a share. Owner-only. */
    public function destroy($id)
    {
        if (!$this->resort) {
            return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
        }
        $share = FileShare::where('id', $id)->where('shared_by', $this->resort->id)->first();
        if (!$share) {
            return response()->json(['success' => false, 'message' => 'Share not found or not yours'], 404);
        }
        $share->delete(); // cascade kills both junctions
        return response()->json(['success' => true, 'message' => 'Share revoked']);
    }

    /** List existing shares on a given file/folder (for the owner). */
    public function index(Request $request)
    {
        if (!$this->resort) return response()->json(['success' => false], 401);
        $type = $request->query('type');
        $id   = (int) $request->query('id');
        $uid  = $request->query('unique_id');
        if (!in_array($type, ['file', 'folder'], true)) {
            return response()->json(['success' => false, 'message' => 'Bad params'], 422);
        }
        // Resolve unique_id → id when caller (file row) only has unique_id.
        if (!$id && $uid) {
            if ($type === 'file') {
                $id = (int) ChildFileManagement::where('unique_id', $uid)
                    ->where('resort_id', $this->resort->resort_id)->value('id');
            } else {
                $id = (int) FilemangementSystem::where('Folder_unique_id', $uid)
                    ->where('resort_id', $this->resort->resort_id)->value('id');
            }
        }
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Bad params'], 422);
        }
        if (!$this->ownsShareable($type, $id)) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $shares = FileShare::where('shareable_type', $type)
            ->where('shareable_id', $id)
            ->where('shared_by', $this->resort->id)
            ->orderByDesc('id')
            ->get();

        $out = [];
        foreach ($shares as $s) {
            $recipients = [];
            if ($s->scope_type === 'employees') {
                $recipients = DB::table('file_share_employees as p')
                    ->join('employees as e', 'e.id', '=', 'p.employee_id')
                    ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
                    ->where('p.share_id', $s->id)
                    ->get(['e.id', 'ra.first_name', 'ra.last_name', 'e.Emp_id'])
                    ->map(fn ($r) => [
                        'id'   => $r->id,
                        'name' => trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? '')) ?: ($r->Emp_id ?: 'Unknown'),
                    ])->all();
            } elseif ($s->scope_type === 'departments') {
                $recipients = DB::table('file_share_departments as p')
                    ->join('resort_departments as d', 'd.id', '=', 'p.department_id')
                    ->where('p.share_id', $s->id)
                    ->get(['d.id', 'd.name'])
                    ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])->all();
            }
            $out[] = [
                'id'         => $s->id,
                'scope_type' => $s->scope_type,
                'mode'       => $s->share_mode,
                'recipients' => $recipients,
                'created_at' => optional($s->created_at)->format('d M Y, h:i A'),
            ];
        }

        return response()->json(['success' => true, 'shares' => $out]);
    }

    /**
     * Items received by the current user. Returns:
     *   - files[]   → individual files shared with this user; UI puts these
     *                 into a "Shared With Me" auto-folder
     *   - folders[] → folders shared with this user; UI shows directly
     *                 in My Drive root
     */
    public function received()
    {
        if (!$this->resort) return response()->json(['success' => false], 401);
        $emp = $this->resort->GetEmployee;
        if (!$emp) return response()->json(['success' => true, 'files' => [], 'folders' => []]);

        $shareIds = $this->resolveReceivedShareIds($emp);
        if (empty($shareIds)) {
            return response()->json(['success' => true, 'files' => [], 'folders' => []]);
        }

        // Group share records by shareable_type
        $shares = FileShare::whereIn('id', $shareIds)->get(['id', 'shareable_type', 'shareable_id', 'shared_by']);
        $fileIds   = $shares->where('shareable_type', 'file')->pluck('shareable_id')->unique()->all();
        $folderIds = $shares->where('shareable_type', 'folder')->pluck('shareable_id')->unique()->all();

        $files   = [];
        $folders = [];

        if (!empty($fileIds)) {
            $files = DB::table('child_file_management')
                ->whereIn('id', $fileIds)
                ->get(['id', 'unique_id', 'File_Name', 'File_Size', 'File_Type', 'File_Extension', 'updated_at'])
                ->map(function ($f) {
                    return [
                        'id'             => $f->id,
                        'unique_id'      => $f->unique_id,
                        'name'           => $f->File_Name,
                        'size'           => $f->File_Size,
                        'extension'      => $f->File_Extension,
                        'modified'       => $f->updated_at,
                    ];
                })->all();
        }
        if (!empty($folderIds)) {
            $folders = DB::table('filemangement_systems')
                ->whereIn('id', $folderIds)
                ->get(['id', 'Folder_unique_id', 'Folder_Name'])
                ->map(function ($f) {
                    return [
                        'id'         => $f->id,
                        'unique_id'  => $f->Folder_unique_id,
                        'name'       => $f->Folder_Name,
                    ];
                })->all();
        }

        return response()->json(['success' => true, 'files' => $files, 'folders' => $folders]);
    }

    /**
     * Employee typeahead search for the picker. Returns name + emp id +
     * department + resort label. Cross-resort allowed per spec.
     */
    public function employeesSearch(Request $request)
    {
        if (!$this->resort) return response()->json(['success' => false], 401);
        $q = trim((string) $request->query('q', ''));
        if (strlen($q) < 2) return response()->json(['success' => true, 'results' => []]);

        // Recipient picker must stay inside the sharer's own resort.
        // Without this scope, HR at resort A could type a name and pull
        // back employees from resort B, then share files cross-resort.
        $like = '%' . $q . '%';
        $rows = DB::table('employees as e')
            ->leftJoin('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->leftJoin('resort_departments as d', 'd.id', '=', 'e.Dept_id')
            ->where('e.resort_id', $this->resort->resort_id)
            ->where('e.status', 'Active')
            ->where(function ($q2) use ($like) {
                $q2->where('ra.first_name', 'like', $like)
                   ->orWhere('ra.last_name', 'like', $like)
                   ->orWhere('e.Emp_id', 'like', $like)
                   ->orWhereRaw("CONCAT(COALESCE(ra.first_name,''),' ',COALESCE(ra.last_name,'')) like ?", [$like]);
            })
            ->limit(20)
            ->get(['e.id', 'e.Emp_id', 'ra.first_name', 'ra.last_name', 'd.name as dept']);

        $out = $rows->map(function ($r) {
            $name = trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? ''));
            return [
                'id'   => $r->id,
                'name' => $name !== '' ? $name : ($r->Emp_id ?: 'Unknown'),
                'emp_id' => $r->Emp_id,
                'dept' => $r->dept,
            ];
        })->all();

        return response()->json(['success' => true, 'results' => $out]);
    }

    /** Departments at the sharer's resort, for the dept picker. */
    public function departments()
    {
        if (!$this->resort) return response()->json(['success' => false], 401);
        $rows = DB::table('resort_departments')
            ->where('resort_id', $this->resort->resort_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function ($d) {
                // Dept_id is shared across resorts in some seeds, so the
                // employee count must also scope by the sharer's resort.
                $count = DB::table('employees')
                    ->where('Dept_id', $d->id)
                    ->where('resort_id', $this->resort->resort_id)
                    ->where('status', 'Active')
                    ->count();
                return ['id' => $d->id, 'name' => $d->name, 'count' => $count];
            })->all();

        return response()->json(['success' => true, 'departments' => $rows]);
    }

    /**
     * Resolve every share_id the given employee currently has access to:
     *   - direct employee shares
     *   - department shares matching the employee's current Dept_id
     *   - organization shares originating from any resort that contains
     *     the recipient's employee record (here we just take all
     *     organization-scope shares — the recipient is always part of
     *     "their organization", so we filter by resort_id == employee
     *     resort_id and treat that as "their org")
     */
    protected function resolveReceivedShareIds(Employee $emp): array
    {
        $deptId = $emp->Dept_id;
        $empId  = $emp->id;
        $resortId = $emp->resort_id;

        $direct = DB::table('file_share_employees')
            ->where('employee_id', $empId)
            ->pluck('share_id')->all();

        $deptShareIds = [];
        if ($deptId) {
            $deptShareIds = DB::table('file_share_departments')
                ->where('department_id', $deptId)
                ->pluck('share_id')->all();
        }

        $orgShareIds = FileShare::where('share_mode', 'internal')
            ->where('scope_type', 'organization')
            ->where('resort_id', $resortId)
            ->pluck('id')->all();

        return array_values(array_unique(array_merge($direct, $deptShareIds, $orgShareIds)));
    }

    /**
     * Get the list of folder ids (filemangement_systems.id) shared with the
     * given employee. Used by the file manager to widen the sidebar
     * folder list for a recipient. Resolves direct / dept / org shares.
     */
    public static function visibleSharedFolderIdsFor(\App\Models\Employee $emp): array
    {
        $empId   = $emp->id;
        $deptId  = $emp->Dept_id;
        $resortId = $emp->resort_id;

        $direct = DB::table('file_shares as fs')
            ->join('file_share_employees as fse', 'fse.share_id', '=', 'fs.id')
            ->where('fs.shareable_type', 'folder')
            ->where('fse.employee_id', $empId)
            ->pluck('fs.shareable_id')->all();

        $dept = [];
        if ($deptId) {
            $dept = DB::table('file_shares as fs')
                ->join('file_share_departments as fsd', 'fsd.share_id', '=', 'fs.id')
                ->where('fs.shareable_type', 'folder')
                ->where('fsd.department_id', $deptId)
                ->pluck('fs.shareable_id')->all();
        }

        $org = DB::table('file_shares')
            ->where('shareable_type', 'folder')
            ->where('share_mode', 'internal')
            ->where('scope_type', 'organization')
            ->where('resort_id', $resortId)
            ->pluck('shareable_id')->all();

        return array_values(array_unique(array_merge($direct, $dept, $org)));
    }

    /** Confirm the sharer owns the shareable item via resort_id. */
    protected function ownsShareable(string $type, int $id): bool
    {
        if ($type === 'file') {
            return ChildFileManagement::where('id', $id)
                ->where('resort_id', $this->resort->resort_id)
                ->exists();
        }
        if ($type === 'folder') {
            return FilemangementSystem::where('id', $id)
                ->where('resort_id', $this->resort->resort_id)
                ->exists();
        }
        return false;
    }
}
