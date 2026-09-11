<?php

namespace App\Http\Controllers\Resorts\Performance;

use Auth;
use Validator;
use Illuminate\Validation\Rule;
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
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('resort_id', $this->resort->resort_id)],
            'position_id' => ['nullable', Rule::exists('resort_positions', 'id')->where('resort_id', $this->resort->resort_id)],
            'template_id' => ['nullable', Rule::exists('professionalforms', 'id')->where('resort_id', $this->resort->resort_id)],
            'duration' => 'required|string|max:100',
            'factors' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $plan = EmployeePipPlan::create([
            'resort_id' => $this->resort->resort_id,
            'employee_id' => $request->employee_id,
            'position_id' => $request->position_id,
            'template_id' => $request->template_id,
            'duration' => $request->duration,
            'factors' => $request->factors,
            'status' => 'active',
            'created_by' => $this->resort->id,
        ]);

        $this->notifyPlanAssignment('pip', $plan);

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
        $this->notifyPlanStatusChange('pip', $plan, 'archived');
        return response()->json(['success' => true, 'message' => 'PIP plan archived']);
    }

    public function pipRestore($id)
    {
        $plan = EmployeePipPlan::where('resort_id', $this->resort->resort_id)->find($id);
        if (!$plan) {
            return response()->json(['success' => false, 'message' => 'Plan not found'], 404);
        }
        $plan->update(['status' => 'active']);
        $this->notifyPlanStatusChange('pip', $plan, 'restored');
        return response()->json(['success' => true, 'message' => 'PIP plan restored']);
    }

    public function pipView($id)
    {
        return $this->renderPlanView('pip', $id);
    }

    public function pipSubmit(Request $request, $id)
    {
        return $this->storePlanResponse('pip', $request, $id);
    }

    public function pipFile($id, $field)
    {
        return $this->streamPlanFile('pip', $id, $field);
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
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('resort_id', $this->resort->resort_id)],
            'position_id' => ['nullable', Rule::exists('resort_positions', 'id')->where('resort_id', $this->resort->resort_id)],
            'template_id' => ['nullable', Rule::exists('professionalforms', 'id')->where('resort_id', $this->resort->resort_id)],
            'duration' => 'required|string|max:100',
            'factors' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $plan = EmployeePdpPlan::create([
            'resort_id' => $this->resort->resort_id,
            'employee_id' => $request->employee_id,
            'position_id' => $request->position_id,
            'template_id' => $request->template_id,
            'duration' => $request->duration,
            'factors' => $request->factors,
            'status' => 'active',
            'created_by' => $this->resort->id,
        ]);

        $this->notifyPlanAssignment('pdp', $plan);

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
        $this->notifyPlanStatusChange('pdp', $plan, 'archived');
        return response()->json(['success' => true, 'message' => 'PDP plan archived']);
    }

    public function pdpRestore($id)
    {
        $plan = EmployeePdpPlan::where('resort_id', $this->resort->resort_id)->find($id);
        if (!$plan) {
            return response()->json(['success' => false, 'message' => 'Plan not found'], 404);
        }
        $plan->update(['status' => 'active']);
        $this->notifyPlanStatusChange('pdp', $plan, 'restored');
        return response()->json(['success' => true, 'message' => 'PDP plan restored']);
    }

    public function pdpView($id)
    {
        return $this->renderPlanView('pdp', $id);
    }

    public function pdpSubmit(Request $request, $id)
    {
        return $this->storePlanResponse('pdp', $request, $id);
    }

    public function pdpFile($id, $field)
    {
        return $this->streamPlanFile('pdp', $id, $field);
    }

    // ==================== SHARED ====================

    private function planModel($kind)
    {
        return $kind === 'pip' ? EmployeePipPlan::class : EmployeePdpPlan::class;
    }

    /**
     * Load a plan + decode its template form structure and render the view blade.
     * Authorization: the assigned employee, their reporting manager, and anyone in
     * the logged-in user's performance scope (HR/HOD/GM) can view.
     */
    private function renderPlanView($kind, $id)
    {
        $model = $this->planModel($kind);
        $plan = $model::with('employee.resortAdmin', 'employee.position', 'position', 'template')
            ->where('resort_id', $this->resort->resort_id)
            ->find($id);

        if (!$plan) abort(404, ucfirst($kind) . ' plan not found.');

        if (!$this->canAccessPlan($plan)) {
            abort(403, 'You are not authorized to view this plan.');
        }

        $structure = [];
        if ($plan->template && $plan->template->form_structure) {
            $decoded = json_decode($plan->template->form_structure, true);
            if (is_string($decoded)) $decoded = json_decode($decoded, true);
            $structure = is_array($decoded) ? $decoded : [];
        }

        $existingData = $plan->response_data ? json_decode($plan->response_data, true) : [];
        if (!is_array($existingData)) $existingData = [];

        $currentEmpId = optional($this->resort->GetEmployee)->id;
        $canEdit = !$plan->submitted_at
            && ($currentEmpId == $plan->employee_id || $this->canManagePlan($plan));

        $page_title = strtoupper($kind) . ' Form — ' . optional(optional($plan->employee)->resortAdmin)->full_name;

        return view('resorts.Performance.PipPdp.view', compact(
            'page_title', 'plan', 'structure', 'existingData', 'canEdit', 'kind'
        ));
    }

    /**
     * Save submitted responses. Only the assigned employee, their manager, or an
     * authorized overseer (HR/HOD) may submit. Once submitted, plan becomes read-only.
     */
    private function storePlanResponse($kind, Request $request, $id)
    {
        $model = $this->planModel($kind);
        $plan = $model::where('resort_id', $this->resort->resort_id)->find($id);

        if (!$plan) return response()->json(['success' => false, 'message' => ucfirst($kind) . ' plan not found'], 404);

        if ($plan->submitted_at) {
            return response()->json(['success' => false, 'message' => 'This plan has already been submitted.'], 422);
        }

        $currentEmpId = optional($this->resort->GetEmployee)->id;
        $isOwnPlan = $currentEmpId && $currentEmpId == $plan->employee_id;
        if (!$isOwnPlan && !$this->canManagePlan($plan)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to submit this plan.'], 403);
        }

        // Decode template structure (handles double-encoded blobs)
        $structure = [];
        if ($plan->template && $plan->template->form_structure) {
            $decoded = json_decode($plan->template->form_structure, true);
            if (is_string($decoded)) $decoded = json_decode($decoded, true);
            $structure = is_array($decoded) ? $decoded : [];
        }

        // Build payload of scalar/array fields, then handle file uploads separately —
        // store each uploaded file on the local disk and write the saved path into the
        // payload so the same field name resolves to a path on read-back.
        $payload = $request->except(['_token']);
        $existing = $plan->response_data ? json_decode($plan->response_data, true) : [];
        if (!is_array($existing)) $existing = [];

        foreach ($structure as $idx => $field) {
            if (($field['type'] ?? null) !== 'file') continue;
            $name = $field['name'] ?? ('field_' . $idx);

            if ($request->hasFile($name)) {
                $file = $request->file($name);
                $dir  = 'pip_pdp_responses/' . $kind . '/' . $plan->id;
                $stored = $file->storeAs($dir, time() . '_' . $file->getClientOriginalName());
                $payload[$name] = $stored;
            } elseif (isset($existing[$name]) && empty($payload[$name])) {
                // No new upload — preserve any path stored on a previous draft.
                $payload[$name] = $existing[$name];
            }
        }

        $errors = $this->validatePlanAgainstTemplate($structure, $payload);
        if (!empty($errors)) {
            return response()->json(['success' => false, 'message' => 'Please fill all required fields', 'errors' => $errors], 422);
        }

        $plan->update([
            'response_data' => json_encode($payload),
            'submitted_at'  => now(),
            'submitted_by'  => $currentEmpId,
        ]);

        // Notify the creator / managers that the plan was submitted.
        try {
            $recipients = array_filter([$plan->created_by ? $this->adminToEmpId($plan->created_by) : null]);
            if ($plan->employee && $plan->employee->reporting_to) {
                $recipients[] = (int) $plan->employee->reporting_to;
            }
            // If the employee submitted, notify those above. If a manager submitted on their
            // behalf, also notify the employee so they know a plan entry was recorded.
            if (!$isOwnPlan && $plan->employee_id) {
                $recipients[] = (int) $plan->employee_id;
            }
            Common::notifyEmployees(
                $this->resort->resort_id,
                $recipients,
                strtoupper($kind) . ' Submitted',
                optional(optional($plan->employee)->resortAdmin)->full_name
                    . ' has submitted their ' . strtoupper($kind) . ' form.',
                'Performance',
                $plan->id,
                'pip-pdp-submitted'
            );
        } catch (\Exception $ne) {
            \Log::warning(strtoupper($kind) . ' submit notification failed: ' . $ne->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => strtoupper($kind) . ' submitted successfully.',
            'redirect' => route('Performance.' . $kind . '.view', $plan->id),
        ]);
    }

    /**
     * Can the logged-in user view this plan? Assigned employee, their reporting
     * manager, or any user whose performance scope includes the employee.
     */
    private function canAccessPlan($plan)
    {
        $currentEmpId = optional($this->resort->GetEmployee)->id;
        if ($currentEmpId && $currentEmpId == $plan->employee_id) return true;
        if ($this->canManagePlan($plan)) return true;

        $scopedIds = Common::getPerformanceScopedEmpIds();
        if (is_null($scopedIds)) return true; // full access
        return in_array((int) $plan->employee_id, (array) $scopedIds);
    }

    /**
     * Can the logged-in user submit on behalf of the employee? Reporting manager,
     * HR/HOD within scope, or GM / master admin.
     */
    private function canManagePlan($plan)
    {
        $currentEmpId = optional($this->resort->GetEmployee)->id;
        if (!$currentEmpId) return false;
        // Reporting manager
        if ($plan->employee && (int) $plan->employee->reporting_to === (int) $currentEmpId) return true;
        // Scope check (HR / HOD / EXCOM / GM cover)
        $scopedIds = Common::getPerformanceScopedEmpIds();
        if (is_null($scopedIds)) return true;
        return in_array((int) $plan->employee_id, (array) $scopedIds) && $currentEmpId != $plan->employee_id;
    }

    private function validatePlanAgainstTemplate(array $structure, array $payload)
    {
        $errors = [];
        foreach ($structure as $idx => $field) {
            if (empty($field['required'])) continue;
            $type = $field['type'] ?? null;
            if (in_array($type, ['header', 'paragraph'])) continue;

            $name = $field['name'] ?? ('field_' . $idx);
            if ($type === 'ratingTable') {
                $hasAny = false;
                foreach ($payload as $k => $v) {
                    if (strpos($k, $name . '_') === 0 && $v !== '' && $v !== null) { $hasAny = true; break; }
                }
                if (!$hasAny) $errors[$name] = strip_tags($field['label'] ?? $name) . ' is required.';
                continue;
            }
            $val = $payload[$name] ?? null;
            $empty = ($val === null || $val === '' || (is_array($val) && count($val) === 0));
            if ($empty) $errors[$name] = strip_tags($field['label'] ?? $name) . ' is required.';
        }
        return $errors;
    }

    /**
     * Stream a file uploaded against a PIP/PDP form field.
     * Access is gated by the same canAccessPlan() check used by the view page.
     */
    private function streamPlanFile($kind, $id, $field)
    {
        $model = $this->planModel($kind);
        $plan = $model::where('resort_id', $this->resort->resort_id)->find($id);
        if (!$plan) abort(404, ucfirst($kind) . ' plan not found.');

        if (!$this->canAccessPlan($plan)) {
            abort(403, 'You do not have access to this file.');
        }

        $data = $plan->response_data ? json_decode($plan->response_data, true) : [];
        $path = is_array($data) ? ($data[$field] ?? null) : null;
        if (!$path) abort(404, 'File not found in this response.');

        if (!\Illuminate\Support\Facades\Storage::disk('local')->exists($path)) {
            abort(404, 'File missing from storage.');
        }

        return \Illuminate\Support\Facades\Storage::disk('local')->download(
            $path,
            preg_replace('/^\d+_/', '', basename($path))
        );
    }

    /**
     * The plans' `created_by` stores a ResortAdmin id — map it back to an Employee id.
     */
    private function adminToEmpId($adminId)
    {
        $emp = Employee::where('Admin_Parent_id', $adminId)->first(['id']);
        return $emp ? (int) $emp->id : null;
    }

    /**
     * Notify the assigned employee + their manager that a PIP/PDP plan was created.
     */
    private function notifyPlanAssignment($kind, $plan)
    {
        try {
            $employee = Employee::find($plan->employee_id);
            $recipients = [(int) $plan->employee_id];
            if ($employee && $employee->reporting_to) {
                $recipients[] = (int) $employee->reporting_to;
            }
            Common::notifyEmployees(
                $this->resort->resort_id,
                $recipients,
                strtoupper($kind) . ' Assigned',
                'A ' . strtoupper($kind) . ' plan has been assigned. Please review and complete the form.',
                'Performance',
                $plan->id,
                'pip-pdp-assigned'
            );
        } catch (\Exception $ne) {
            \Log::warning(strtoupper($kind) . ' assign notification failed: ' . $ne->getMessage());
        }
    }

    private function notifyPlanStatusChange($kind, $plan, $status)
    {
        try {
            $employee = Employee::find($plan->employee_id);
            $recipients = [(int) $plan->employee_id];
            if ($employee && $employee->reporting_to) {
                $recipients[] = (int) $employee->reporting_to;
            }
            Common::notifyEmployees(
                $this->resort->resort_id,
                $recipients,
                strtoupper($kind) . ' Plan ' . ucfirst($status),
                'Your ' . strtoupper($kind) . ' plan has been ' . $status . '.',
                'Performance',
                $plan->id,
                'pip-pdp-' . $status
            );
        } catch (\Exception $ne) {
            \Log::warning(strtoupper($kind) . ' ' . $status . ' notification failed: ' . $ne->getMessage());
        }
    }
}
