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
        $query = Employee::with(['resortAdmin', 'department', 'position'])
            ->where('resort_id', $this->resort->resort_id)
            ->where('status', 'active');

        if ($departmentId) {
            $query->where('Dept_id', $departmentId);
        }

        return $query->get()->map(function ($employee) {
            return [
                'id' => $employee->id,
                'pid' => $employee->reporting_to,
                'name' => optional($employee->resortAdmin)->full_name ?? 'N/A',
                'position' => optional($employee->position)->position_title ?? 'N/A',
                'joinDate' => $employee->joining_date
                    ? 'Joining Date: ' . \Carbon\Carbon::parse($employee->joining_date)->format('d F Y')
                    : '',
                'img' => $this->getImageUrlForPDF($employee->Admin_Parent_id ?? null),
                'department_id' => $employee->Dept_id,
                'department_name' => optional($employee->department)->name ?? 'N/A',
                'reporting_to' => $employee->reporting_to,
            ];
        })->toArray();
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