<?php
namespace App\Http\Controllers\Resorts\People\OrgChart;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Events\ResortNotificationEvent;
use App\Models\Resort;
use App\Models\Employee;
use App\Models\resortAdmin;
use App\Models\ResortDepartment;
use Auth;
use Config;
use Common;
use DB;
use Carbon\Carbon;

class OrganizationChartController extends Controller 
{
    public $resort;
    
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
    }

    public function index()
    {
        $page_title = 'Organization Chart';
        $resort_id = $this->resort->resort_id;
        $departments = ResortDepartment::where('status', 'active')
            ->where('resort_id', $resort_id)
            ->get();
                
        $fallbackImage = asset('admin_assets/files/user-image.png');
                    
        return view('resorts.people.orgchart.index', compact(
            'page_title', 
            'resort_id', 
            'departments',
            'fallbackImage'
        ));
    }

    public function getEmployees(Request $request)
    {
        $request->validate([
            'department_id' => 'nullable|exists:resort_departments,id'
        ]);

        $employees = $this->getEmployeesData($request->department_id);
        return response()->json($employees);
    }

    private function getEmployeesData($departmentId = null)
    {
        $resortId = $this->resort->resort_id;
        $scopedDeptIds = \App\Helpers\Common::getScopedDepartmentIds();

        $employeeQuery = Employee::with(['resortAdmin', 'department', 'position'])
            ->where('resort_id', $resortId)
            ->where('status', 'Active')
            ->when(is_array($scopedDeptIds), fn($q) => $q->whereIn('Dept_id', $scopedDeptIds));

        if ($departmentId) {
            $employeeQuery->where('Dept_id', $departmentId);
        }

        $employees = $employeeQuery->get();

        // Visible employee IDs and a quick (id → Dept_id) lookup so we can
        // decide whether to chain to a manager or anchor to a department.
        $visibleEmployeeIds = $employees->pluck('id')->map(fn($v) => (int) $v)->all();
        $employeeDeptById = $employees->mapWithKeys(fn($e) => [(int)$e->id => (int)$e->Dept_id])->all();

        // ── Department layer ─────────────────────────────────────────────
        // Build a department node per visible dept so the org chart reads as
        //   Organization → Department → Manager → Employee
        // rather than dumping every manager-less employee directly under
        // Organization.
        $deptIdsInPlay = $employees->pluck('Dept_id')->filter()->unique();
        $deptQuery = ResortDepartment::where('resort_id', $resortId)->where('status', 'active');
        if ($departmentId) {
            $deptQuery->where('id', $departmentId);
        } else {
            if (is_array($scopedDeptIds)) $deptQuery->whereIn('id', $scopedDeptIds);
        }
        $departments = $deptQuery->get();

        $fallbackImg = url('admin_assets/files/user-image.png');
        $deptNodes = [];
        foreach ($departments as $d) {
            // Only emit a dept node when at least one employee or vacant
            // position will hang off it — otherwise the chart shows empty
            // dept boxes that confuse the layout.
            $deptNodes[] = [
                'id' => 'dept_' . $d->id,
                // pid stays null — these become root children of the
                // synthetic "Organization" node added by the frontend.
                'pid' => null,
                'name' => $d->name,
                'position' => 'Department',
                'joinDate' => '',
                'img' => $fallbackImg,
                'department_id' => $d->id,
                'department_name' => $d->name,
                'reporting_to' => null,
                'is_vacant' => false,
                'is_department' => true,
                'employee_id' => null,
            ];
        }

        $employeeNodes = $employees->map(function ($employee) use ($visibleEmployeeIds, $employeeDeptById) {
            // Chain to manager ONLY when the manager belongs to the same
            // department. Cross-department reporting (e.g. Accounting HOD
            // reports to the GM in Executive Office) shouldn't make
            // Accounting employees appear under Executive Office on the
            // chart — they should sit under their own department node.
            $managerId = (int) ($employee->reporting_to ?? 0);
            $managerVisible = $managerId > 0 && in_array($managerId, $visibleEmployeeIds, true);
            $sameDept = $managerVisible
                && isset($employeeDeptById[$managerId])
                && (int) $employeeDeptById[$managerId] === (int) $employee->Dept_id;
            $pid = $sameDept ? 'emp_' . $managerId : 'dept_' . $employee->Dept_id;

            return [
                'id' => 'emp_' . $employee->id,
                'pid' => $pid,
                'name' => optional($employee->resortAdmin)->full_name ?? 'N/A',
                'position' => optional($employee->position)->position_title ?? 'N/A',
                'joinDate' => $employee->joining_date
                    ? 'Joining Date: ' . \Carbon\Carbon::parse($employee->joining_date)->format('d M Y')
                    : '',
                'img' => $this->getImageUrlForPDF($employee->Admin_Parent_id ?? null),
                'department_id' => $employee->Dept_id,
                'department_name' => optional($employee->department)->name ?? 'N/A',
                'reporting_to' => $employee->reporting_to,
                'is_vacant' => false,
                'is_department' => false,
                'employee_id' => $employee->id,
            ];
        })->toArray();

        // ── Vacant positions ─────────────────────────────────────────────
        // Surface every active ResortPosition that has NO active employee in
        // it (respecting the same dept-scope / dept-filter) as a "Vacant"
        // node. Anchored under the dept head when one exists, otherwise
        // directly under the dept node.
        $occupiedPositionIds = $employees->pluck('Position_id')->filter()->unique()->all();

        $positionQuery = \App\Models\ResortPosition::where('resort_id', $resortId)
            ->where('status', 'active');
        if ($departmentId) {
            $positionQuery->where('dept_id', $departmentId);
        } elseif (is_array($scopedDeptIds)) {
            $positionQuery->whereIn('dept_id', $scopedDeptIds);
        }
        $vacantPositions = $positionQuery
            ->whereNotIn('id', $occupiedPositionIds)
            ->with('department')
            ->get();

        // Per-department head — HOD (rank=2) first, then EXCOM (rank=1).
        $deptHeads = [];
        foreach ($vacantPositions->pluck('dept_id')->filter()->unique() as $deptId) {
            $head = $employees->first(function ($emp) use ($deptId) {
                return (int)$emp->Dept_id === (int)$deptId && in_array((int)$emp->rank, [2, 1], true);
            });
            if ($head) $deptHeads[$deptId] = $head->id;
        }

        $vacantNodes = [];
        foreach ($vacantPositions as $pos) {
            $vacantNodes[] = [
                'id' => 'vacant_' . $pos->id,
                'pid' => isset($deptHeads[$pos->dept_id])
                    ? ('emp_' . $deptHeads[$pos->dept_id])
                    : ('dept_' . $pos->dept_id),
                'name' => 'Vacant',
                'position' => $pos->position_title ?? '—',
                'joinDate' => 'Open Position',
                'img' => $fallbackImg,
                'department_id' => $pos->dept_id,
                'department_name' => optional($pos->department)->name ?? 'N/A',
                'reporting_to' => null,
                'is_vacant' => true,
                'is_department' => false,
                'employee_id' => null,
            ];
        }

        // Department nodes only emit if at least one downstream child
        // exists — drop the empties.
        $usedDeptIds = collect($employeeNodes)->pluck('department_id')
            ->merge(collect($vacantNodes)->pluck('department_id'))
            ->filter()->unique()->map(fn($v) => (int)$v)->all();
        $deptNodes = array_values(array_filter($deptNodes, function ($d) use ($usedDeptIds) {
            return in_array((int)$d['department_id'], $usedDeptIds, true);
        }));

        return array_merge($deptNodes, $employeeNodes, $vacantNodes);
    }

    /**
     * Enhanced image URL method with PDF support
     */
    private function getImageUrlForPDF($adminParentId)
    {
        $imagePath = \Common::getResortUserPicture($adminParentId);
        
        if (empty($imagePath)) {
            return $this->getAbsoluteUrl('admin_assets/files/user-image.png');
        }
        
        // If it's already a URL, ensure it's absolute
        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            return $this->ensureAbsoluteUrl($imagePath);
        }
        
        // If it's a local path, convert to absolute URL
        $fallbackUrl = $this->getAbsoluteUrl('admin_assets/files/user-image.png');
        
        try {
            $fullPath = public_path($imagePath);
            if (file_exists($fullPath)) {
                return $this->getAbsoluteUrl($imagePath);
            }
        } catch (\Exception $e) {
            \Log::warning('Error accessing image file: ' . $e->getMessage());
        }
        
        return $fallbackUrl;
    }

    /**
     * Get absolute URL for assets
     */
    private function getAbsoluteUrl($path)
    {
        return url($path);
    }

    /**
     * Ensure URL is absolute (includes domain)
     */
    private function ensureAbsoluteUrl($url)
    {
        if (strpos($url, '://') === false) {
            return url($url);
        }
        return $url;
    }

    /**
     * Convert image to base64 for PDF export (server-side method)
     */
    public function getImageAsBase64(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id'
        ]);

        $employee = Employee::find($request->employee_id);
        $imagePath = \Common::getResortUserPicture($employee->Admin_Parent_id);
        
        if (empty($imagePath)) {
            $imagePath = 'admin_assets/files/user-image.png';
        }

        try {
            $fullPath = public_path($imagePath);
            
            if (file_exists($fullPath)) {
                $imageData = file_get_contents($fullPath);
                $base64 = 'data:' . mime_content_type($fullPath) . ';base64,' . base64_encode($imageData);
                
                return response()->json([
                    'success' => true,
                    'base64' => $base64
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Error converting image to base64: ' . $e->getMessage());
        }

        // Return fallback image as base64
        $fallbackPath = public_path('admin_assets/files/user-image.png');
        if (file_exists($fallbackPath)) {
            $imageData = file_get_contents($fallbackPath);
            $base64 = 'data:' . mime_content_type($fallbackPath) . ';base64,' . base64_encode($imageData);
            
            return response()->json([
                'success' => true,
                'base64' => $base64
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Image not found'
        ], 404);
    }

    /**
     * Bulk convert images to base64 for PDF export
     */
    public function getBulkImagesAsBase64(Request $request)
    {
        $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id'
        ]);

        $results = [];
        
        foreach ($request->employee_ids as $employeeId) {
            $employee = Employee::find($employeeId);
            $imagePath = \Common::getResortUserPicture($employee->Admin_Parent_id);
            
            if (empty($imagePath)) {
                $imagePath = 'admin_assets/files/user-image.png';
            }

            try {
                $fullPath = public_path($imagePath);
                
                if (file_exists($fullPath)) {
                    $imageData = file_get_contents($fullPath);
                    $base64 = 'data:' . mime_content_type($fullPath) . ';base64,' . base64_encode($imageData);
                    
                    $results[$employeeId] = $base64;
                } else {
                    $results[$employeeId] = $this->getFallbackImageBase64();
                }
            } catch (\Exception $e) {
                \Log::error('Error converting image to base64 for employee ' . $employeeId . ': ' . $e->getMessage());
                $results[$employeeId] = $this->getFallbackImageBase64();
            }
        }

        return response()->json([
            'success' => true,
            'images' => $results
        ]);
    }

    /**
     * Get fallback image as base64
     */
    private function getFallbackImageBase64()
    {
        $fallbackPath = public_path('admin_assets/files/user-image.png');
        if (file_exists($fallbackPath)) {
            $imageData = file_get_contents($fallbackPath);
            return 'data:' . mime_content_type($fallbackPath) . ';base64,' . base64_encode($imageData);
        }
        return '';
    }
}