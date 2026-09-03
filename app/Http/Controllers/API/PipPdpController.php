<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeePipPlan;
use App\Models\EmployeePdpPlan;
use App\Models\Professionalform;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Mobile self-service for PIP (Performance Improvement Plan) and PDP
 * (Professional Development Plan) — reads/writes the same
 * employee_pip_plans / employee_pdp_plans rows as the web
 * Resorts\Performance\PipPdpController, so a plan created on the web
 * shows up here and a response submitted here shows up there.
 *
 * Scope: an employee's own plans only, any rank (Line Worker through
 * GM) — matches the ticket's ask for a self-service listing screen.
 * The web controller's manager/HR oversight (viewing or submitting on
 * behalf of a report) is NOT replicated here; that stays web-only.
 */
class PipPdpController extends Controller
{
    protected $user;
    protected $resort_id;
    protected $employee;

    public function __construct()
    {
        if (Auth::guard('api')->check()) {
            $this->user      = Auth::guard('api')->user();
            $this->resort_id = $this->user->resort_id;
            $this->employee  = $this->user->GetEmployee;
        }
    }

    private function unauthorized()
    {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    private function planModel(string $kind)
    {
        return $kind === 'pip' ? EmployeePipPlan::class : EmployeePdpPlan::class;
    }

    /**
     * GET performance/my-pip-plans
     */
    public function myPipPlans()
    {
        return $this->myPlans('pip');
    }

    /**
     * GET performance/my-pdp-plans
     */
    public function myPdpPlans()
    {
        return $this->myPlans('pdp');
    }

    private function myPlans(string $kind)
    {
        if (!$this->employee) return $this->unauthorized();

        $model = $this->planModel($kind);
        $plans = $model::with('position', 'template')
            ->where('resort_id', $this->resort_id)
            ->where('employee_id', $this->employee->id)
            ->orderByDesc('id')
            ->get();

        $data = $plans->map(function ($plan) {
            return [
                'id'            => $plan->id,
                'duration'      => $plan->duration,
                'factors'       => $plan->factors,
                'position'      => optional($plan->position)->position_title,
                'template_name' => optional($plan->template)->FormName,
                'status'        => $plan->status,
                'submitted_at'  => $plan->submitted_at,
                // "Pending Action" once assigned and not yet submitted, so
                // the listing screen can badge it the same way My Reviews does.
                'action_status' => $plan->submitted_at ? 'Submitted' : 'Pending Action',
                'created_at'    => $plan->created_at,
            ];
        });

        return response()->json(['success' => true, 'data' => $data], 200);
    }

    /**
     * GET performance/pip-plan/{id}
     */
    public function pipPlanDetail($id)
    {
        return $this->planDetail('pip', $id);
    }

    /**
     * GET performance/pdp-plan/{id}
     */
    public function pdpPlanDetail($id)
    {
        return $this->planDetail('pdp', $id);
    }

    private function planDetail(string $kind, $id)
    {
        if (!$this->employee) return $this->unauthorized();

        $model = $this->planModel($kind);
        $plan = $model::with('position', 'template')
            ->where('resort_id', $this->resort_id)
            ->where('employee_id', $this->employee->id)
            ->find($id);

        if (!$plan) {
            return response()->json(['success' => false, 'message' => ucfirst($kind) . ' plan not found.'], 404);
        }

        $structure = $this->decodeFormStructure(optional($plan->template)->form_structure);
        $existingData = $plan->response_data ? json_decode($plan->response_data, true) : [];
        if (!is_array($existingData)) $existingData = [];

        return response()->json([
            'success' => true,
            'data' => [
                'id'            => $plan->id,
                'kind'          => $kind,
                'duration'      => $plan->duration,
                'factors'       => $plan->factors,
                'position'      => optional($plan->position)->position_title,
                'template_name' => optional($plan->template)->FormName,
                'template'      => $structure,
                'existing_data' => $existingData,
                'status'        => $plan->status,
                'can_edit'      => !$plan->submitted_at,
                'submitted_at'  => $plan->submitted_at,
            ],
        ], 200);
    }

    /**
     * POST performance/pip-plan/{id}/submit
     */
    public function submitPipPlan(Request $request, $id)
    {
        return $this->submitPlan('pip', $request, $id);
    }

    /**
     * POST performance/pdp-plan/{id}/submit
     */
    public function submitPdpPlan(Request $request, $id)
    {
        return $this->submitPlan('pdp', $request, $id);
    }

    private function submitPlan(string $kind, Request $request, $id)
    {
        if (!$this->employee) return $this->unauthorized();

        $model = $this->planModel($kind);
        $plan = $model::where('resort_id', $this->resort_id)
            ->where('employee_id', $this->employee->id)
            ->find($id);

        if (!$plan) {
            return response()->json(['success' => false, 'message' => ucfirst($kind) . ' plan not found.'], 404);
        }

        if ($plan->submitted_at) {
            return response()->json(['success' => false, 'message' => 'This plan has already been submitted.'], 422);
        }

        $structure = $this->decodeFormStructure(optional($plan->template)->form_structure);

        $payload = $request->except(['_token']);
        $existing = $plan->response_data ? json_decode($plan->response_data, true) : [];
        if (!is_array($existing)) $existing = [];

        foreach ($structure as $idx => $field) {
            if (($field['type'] ?? null) !== 'file') continue;
            $name = $field['name'] ?? ('field_' . $idx);

            if ($request->hasFile($name)) {
                $file = $request->file($name);
                $dir = 'pip_pdp_responses/' . $kind . '/' . $plan->id;
                $payload[$name] = $file->storeAs($dir, time() . '_' . $file->getClientOriginalName());
            } elseif (isset($existing[$name]) && empty($payload[$name])) {
                $payload[$name] = $existing[$name];
            }
        }

        $errors = $this->validateAgainstTemplate($structure, $payload);
        if (!empty($errors)) {
            return response()->json(['success' => false, 'message' => 'Please fill all required fields', 'errors' => $errors], 422);
        }

        $plan->update([
            'response_data' => json_encode($payload),
            'submitted_at'  => now(),
            'submitted_by'  => $this->employee->id,
        ]);

        try {
            $recipients = [];
            if ($plan->created_by) {
                $creatorEmp = Employee::where('Admin_Parent_id', $plan->created_by)->value('id');
                if ($creatorEmp) $recipients[] = (int) $creatorEmp;
            }
            if ($this->employee->reporting_to) {
                $recipients[] = (int) $this->employee->reporting_to;
            }
            $recipients = array_values(array_unique(array_filter($recipients)));
            if (!empty($recipients)) {
                Common::notifyEmployees(
                    $this->resort_id,
                    $recipients,
                    strtoupper($kind) . ' Submitted',
                    optional($this->employee->resortAdmin)->full_name . ' has submitted their ' . strtoupper($kind) . ' form.',
                    'Performance',
                    $plan->id,
                    'pip-pdp-submitted'
                );
            }
        } catch (\Exception $ne) {
            \Log::warning(strtoupper($kind) . ' mobile submit notification failed: ' . $ne->getMessage());
        }

        return response()->json(['success' => true, 'message' => strtoupper($kind) . ' submitted successfully.'], 200);
    }

    /**
     * GET performance/pip-plan/{id}/file/{field}
     */
    public function pipPlanFile($id, $field)
    {
        return $this->planFile('pip', $id, $field);
    }

    /**
     * GET performance/pdp-plan/{id}/file/{field}
     */
    public function pdpPlanFile($id, $field)
    {
        return $this->planFile('pdp', $id, $field);
    }

    private function planFile(string $kind, $id, $field)
    {
        if (!$this->employee) return $this->unauthorized();

        $model = $this->planModel($kind);
        $plan = $model::where('resort_id', $this->resort_id)
            ->where('employee_id', $this->employee->id)
            ->find($id);

        if (!$plan) {
            return response()->json(['success' => false, 'message' => ucfirst($kind) . ' plan not found.'], 404);
        }

        $data = $plan->response_data ? json_decode($plan->response_data, true) : [];
        $path = is_array($data) ? ($data[$field] ?? null) : null;
        if (!$path || !Storage::disk('local')->exists($path)) {
            return response()->json(['success' => false, 'message' => 'File not found.'], 404);
        }

        return Storage::disk('local')->download($path, preg_replace('/^\d+_/', '', basename($path)));
    }

    private function validateAgainstTemplate(array $structure, array $payload)
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

    private function decodeFormStructure($raw)
    {
        if (empty($raw)) return [];
        $decoded = json_decode($raw, true);
        if (is_string($decoded)) $decoded = json_decode($decoded, true);
        return is_array($decoded) ? $decoded : [];
    }
}
