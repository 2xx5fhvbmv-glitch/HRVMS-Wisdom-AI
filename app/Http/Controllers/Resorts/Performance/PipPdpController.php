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

    public function pipIndex()
    {
        $page_title = 'Performance Improvement Plan';
        $employees = Employee::with('resortAdmin', 'position')
            ->where('resort_id', $this->resort->resort_id)
            ->where('status', 'Active')
            ->get();
        $positions = ResortPosition::where('resort_id', $this->resort->resort_id)->where('status', 'active')->get();
        $templates = Professionalform::where('resort_id', $this->resort->resort_id)
            ->where('form_type', 'pipForm')
            ->get();

        $plans = EmployeePipPlan::with('employee.resortAdmin', 'position', 'template')
            ->where('resort_id', $this->resort->resort_id)
            ->orderByDesc('id')
            ->get();

        return view('resorts.Performance.PipPdp.pip', compact('page_title', 'employees', 'positions', 'templates', 'plans'));
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
        $plan = EmployeePipPlan::where('resort_id', $this->resort->resort_id)->find($id);
        if (!$plan) {
            return response()->json(['success' => false, 'message' => 'Plan not found'], 404);
        }
        $plan->delete();
        return response()->json(['success' => true, 'message' => 'PIP plan removed']);
    }

    // ==================== PDP ====================

    public function pdpIndex()
    {
        $page_title = 'Professional Development Plan';
        $employees = Employee::with('resortAdmin', 'position')
            ->where('resort_id', $this->resort->resort_id)
            ->where('status', 'Active')
            ->get();
        $positions = ResortPosition::where('resort_id', $this->resort->resort_id)->where('status', 'active')->get();
        $templates = Professionalform::where('resort_id', $this->resort->resort_id)
            ->where('form_type', 'pdpForm')
            ->get();

        $plans = EmployeePdpPlan::with('employee.resortAdmin', 'position', 'template')
            ->where('resort_id', $this->resort->resort_id)
            ->orderByDesc('id')
            ->get();

        return view('resorts.Performance.PipPdp.pdp', compact('page_title', 'employees', 'positions', 'templates', 'plans'));
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
        $plan = EmployeePdpPlan::where('resort_id', $this->resort->resort_id)->find($id);
        if (!$plan) {
            return response()->json(['success' => false, 'message' => 'Plan not found'], 404);
        }
        $plan->delete();
        return response()->json(['success' => true, 'message' => 'PDP plan removed']);
    }
}
