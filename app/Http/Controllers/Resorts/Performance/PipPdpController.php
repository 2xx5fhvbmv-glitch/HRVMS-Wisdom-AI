<?php

namespace App\Http\Controllers\Resorts\Performance;

use Auth;
use Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\ResortPosition;
use App\Models\Professionalform;
use App\Models\EmployeePipPlan;
use App\Models\EmployeePdpPlan;
use App\Helpers\Common;

class PipPdpController extends Controller
{
    public $resort;

    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
    }

    // ==================== PIP ====================

    public function pipIndex(Request $request)
    {
        $archivedView = (bool) $request->boolean('archived');
        $page_title = $archivedView ? 'PIP Archive' : 'Performance Improvement Plan';
        $scopedIds = Common::getPerformanceScopedEmpIds();

        $employees = Employee::with('resortAdmin', 'position')
            ->where('resort_id', $this->resort->resort_id)
            ->where('status', 'Active')
            ->when(is_array($scopedIds), fn($q) => $q->whereIn('id', $scopedIds))
            ->get();
        $positions = ResortPosition::where('resort_id', $this->resort->resort_id)->where('status', 'active')->get();
        $templates = Professionalform::where('resort_id', $this->resort->resort_id)
            ->where('form_type', 'pipForm')
            ->get();

        $plans = EmployeePipPlan::with('employee.resortAdmin', 'position', 'template')
            ->where('resort_id', $this->resort->resort_id)
            ->where('status', $archivedView ? 'archived' : 'active')
            ->when(is_array($scopedIds), fn($q) => $q->whereIn('employee_id', $scopedIds))
            ->orderByDesc('id')
            ->get();

        return view('resorts.Performance.PipPdp.pip', compact('page_title', 'employees', 'positions', 'templates', 'plans', 'archivedView'));
    }

    public function pipStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'position_id' => 'nullable|exists:resort_positions,id',
            'template_id' => 'nullable|exists:professionalforms,id',
            'duration' => 'required|string|max:100',
            'factors' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        EmployeePipPlan::create([
            'resort_id' => $this->resort->resort_id,
            'employee_id' => $request->employee_id,
            'position_id' => $request->position_id,
            'template_id' => $request->template_id,
            'duration' => $request->duration,
            'factors' => $request->factors,
            'status' => 'active',
            'created_by' => $this->resort->id,
        ]);

        return response()->json(['success' => true, 'message' => 'Employee added to PIP successfully']);
    }

    public function pipDestroy($id)
    {
        // Kept for route-compat — treat as archive (delete removed by design).
        return $this->pipArchive($id);
    }

    public function pipArchive($id)
    {
        $plan = EmployeePipPlan::where('resort_id', $this->resort->resort_id)->find($id);
        if (!$plan) {
            return response()->json(['success' => false, 'message' => 'Plan not found'], 404);
        }
        $plan->update(['status' => 'archived']);
        return response()->json(['success' => true, 'message' => 'PIP plan archived']);
    }

    public function pipRestore($id)
    {
        $plan = EmployeePipPlan::where('resort_id', $this->resort->resort_id)->find($id);
        if (!$plan) {
            return response()->json(['success' => false, 'message' => 'Plan not found'], 404);
        }
        $plan->update(['status' => 'active']);
        return response()->json(['success' => true, 'message' => 'PIP plan restored']);
    }

    // ==================== PDP ====================

    public function pdpIndex(Request $request)
    {
        $archivedView = (bool) $request->boolean('archived');
        $page_title = $archivedView ? 'PDP Archive' : 'Professional Development Plan';
        $scopedIds = Common::getPerformanceScopedEmpIds();

        $employees = Employee::with('resortAdmin', 'position')
            ->where('resort_id', $this->resort->resort_id)
            ->where('status', 'Active')
            ->when(is_array($scopedIds), fn($q) => $q->whereIn('id', $scopedIds))
            ->get();
        $positions = ResortPosition::where('resort_id', $this->resort->resort_id)->where('status', 'active')->get();
        $templates = Professionalform::where('resort_id', $this->resort->resort_id)
            ->where('form_type', 'pdpForm')
            ->get();

        $plans = EmployeePdpPlan::with('employee.resortAdmin', 'position', 'template')
            ->where('resort_id', $this->resort->resort_id)
            ->where('status', $archivedView ? 'archived' : 'active')
            ->when(is_array($scopedIds), fn($q) => $q->whereIn('employee_id', $scopedIds))
            ->orderByDesc('id')
            ->get();

        return view('resorts.Performance.PipPdp.pdp', compact('page_title', 'employees', 'positions', 'templates', 'plans', 'archivedView'));
    }

    public function pdpStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'position_id' => 'nullable|exists:resort_positions,id',
            'template_id' => 'nullable|exists:professionalforms,id',
            'duration' => 'required|string|max:100',
            'factors' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        EmployeePdpPlan::create([
            'resort_id' => $this->resort->resort_id,
            'employee_id' => $request->employee_id,
            'position_id' => $request->position_id,
            'template_id' => $request->template_id,
            'duration' => $request->duration,
            'factors' => $request->factors,
            'status' => 'active',
            'created_by' => $this->resort->id,
        ]);

        return response()->json(['success' => true, 'message' => 'Employee added to PDP successfully']);
    }

    public function pdpDestroy($id)
    {
        // Kept for route-compat — treat as archive.
        return $this->pdpArchive($id);
    }

    public function pdpArchive($id)
    {
        $plan = EmployeePdpPlan::where('resort_id', $this->resort->resort_id)->find($id);
        if (!$plan) {
            return response()->json(['success' => false, 'message' => 'Plan not found'], 404);
        }
        $plan->update(['status' => 'archived']);
        return response()->json(['success' => true, 'message' => 'PDP plan archived']);
    }

    public function pdpRestore($id)
    {
        $plan = EmployeePdpPlan::where('resort_id', $this->resort->resort_id)->find($id);
        if (!$plan) {
            return response()->json(['success' => false, 'message' => 'Plan not found'], 404);
        }
        $plan->update(['status' => 'active']);
        return response()->json(['success' => true, 'message' => 'PDP plan restored']);
    }
}
