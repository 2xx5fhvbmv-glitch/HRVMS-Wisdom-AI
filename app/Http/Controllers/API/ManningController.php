<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ResortDivision;
use App\Models\ResortDepartment;
use App\Models\ResortSection;
use App\Models\ResortPosition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use DB;
class ManningController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api'); // This will protect all methods in this controller
    }
    public function getDivisions(Request $request)
    {
        
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate the input
        $request->validate([
            'resort_id' => 'required|integer|exists:resorts,id',
        ]);

        try {
            // Fetch resort_id from the query parameter
            $resortId = $request->query('resort_id');
        
            // Check if resort_id is provided
            if (!$resortId) {
                return response()->json(['success' => false, 'message' => 'resort_id is required'], 400);
            }
        
            // Fetch the employees for the specified resort
            $divisions = ResortDivision::where('resort_id', $resortId)->get();
        
            // Debugging line, you can remove this after checking
            // dd($employees);
        
            return response()->json(['success' => true, 'divisions' => $divisions]);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
        
    }
    public function getDepartments(Request $request)
    {
        
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        
        // Validate the input
        $request->validate([
            'resort_id' => 'required|integer|exists:resorts,id',
        ]);

        try {
            // Fetch resort_id from the query parameter
            $resortId = $request->query('resort_id');
            // dd($resortId);
            // Check if resort_id is provided
            if (!$resortId) {
                return response()->json(['success' => false, 'message' => 'resort_id is required'], 400);
            }
        
            // Fetch the employees for the specified resort
            $departments = ResortDepartment::select([
                'resort_departments.id',
                'resort_divisions.name as division',
                'resort_departments.code',
                'resort_departments.name',
                'resort_departments.short_name',
                'resort_departments.status',
                'resort_departments.created_by',
                'resort_departments.created_at',
                'resort_departments.updated_at'
            ])
            ->join('resort_divisions', 'resort_departments.division_id', '=', 'resort_divisions.id')
            ->where('resort_departments.resort_id',$resortId)
            ->orderBy('resort_departments.created_at', 'DESC')
            ->get();
        
            // Debugging line, you can remove this after checking
            // dd($departments);
        
            return response()->json(['success' => true, 'departments' => $departments]);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
        
    }

    public function getSections(Request $request)
    {
        
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate the input
        $request->validate([
            'resort_id' => 'required|integer|exists:resorts,id',
        ]);

        try {
            // Fetch resort_id from the query parameter
            $resortId = $request->query('resort_id');
        
            // Check if resort_id is provided
            if (!$resortId) {
                return response()->json(['success' => false, 'message' => 'resort_id is required'], 400);
            }
        
            // Fetch the employees for the specified resort
            $sections = ResortSection::select([
                'resort_sections.id',
                'resort_sections.dept_id',
                'resort_departments.name as department',
                'resort_sections.name',
                'resort_sections.code',
                'resort_sections.short_name',
                'resort_sections.status',
                'resort_sections.created_by',
                'resort_divisions.name as division',
                'resort_sections.created_at',
                'resort_sections.updated_at'
            ])
            ->join('resort_departments', 'resort_sections.dept_id', '=', 'resort_departments.id')
            ->join('resort_divisions', 'resort_divisions.id', '=', 'resort_departments.division_id')
            ->where('resort_sections.resort_id',$resortId)
            ->orderBy('resort_sections.created_at', 'DESC')
            ->get();
        
            // Debugging line, you can remove this after checking
            // dd($employees);
        
            return response()->json(['success' => true, 'sections' => $sections]);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
        
    }

    public function getPositions(Request $request)
    {
        
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate the input
        $request->validate([
            'resort_id' => 'required|integer|exists:resorts,id',
        ]);

        try {
            // Fetch resort_id from the query parameter
            $resortId = $request->query('resort_id');
        
            // Check if resort_id is provided
            if (!$resortId) {
                return response()->json(['success' => false, 'message' => 'resort_id is required'], 400);
            }
        
            // Fetch the employees for the specified resort
            $positions = ResortPosition::select([
                'resort_positions.id',
                'resort_positions.dept_id',
                'resort_departments.name as department',
                'resort_positions.position_title',
                'resort_positions.code',
                'resort_positions.short_title',
                'resort_positions.status',
                'resort_positions.created_by',
                'resort_positions.Rank',
                'resort_divisions.name as division',
                'resort_positions.created_at',
                'resort_positions.updated_at'
            ])
            ->leftJoin('resort_departments', 'resort_positions.dept_id', '=', 'resort_departments.id')
            ->leftJoin('resort_divisions', 'resort_divisions.id', '=', 'resort_departments.division_id')
            ->where('resort_positions.resort_id', $resortId)
            ->groupBy('resort_positions.id', 'resort_positions.dept_id', 'resort_positions.position_title', 'resort_positions.code', 'resort_positions.short_title', 'resort_positions.status', 'resort_positions.created_by', 'resort_divisions.name', 'resort_positions.created_at', 'resort_positions.updated_at')
            ->orderBy('resort_positions.created_at', 'DESC')
            ->get();
        
            // Debugging line, you can remove this after checking
            // dd($positions);
        
            return response()->json(['success' => true, 'positions' => $positions]);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
        
    }
}
?>