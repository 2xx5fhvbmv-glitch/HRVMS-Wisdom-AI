<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\NintyDayPeformanceForm;
use App\Models\PerformaChildCycle;
use App\Models\PerformanceTemplateForm;
use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Mobile parity for the web Appraisal / Performance Review flow
 * (Resorts\Performance\ReviewController). Reads/writes the same
 * performa_child_cycles rows so a form filled on either side shows up
 * on both. Covers the two roles a mobile user can be assigned:
 *   - 'self'    — the employee being reviewed
 *   - 'manager' — the reporting manager doing the review
 */
class PerformanceReviewController extends Controller
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

    /**
     * GET performance/my-reviews
     * Every appraisal form assigned to me — as the reviewee (self) and/or as the
     * reporting manager (manager) — with a "Pending Action" / "Submitted" status.
     */
    public function myReviews(Request $request)
    {
        if (!$this->employee) return $this->unauthorized();

        $empId = $this->employee->id;
        $empCode = $this->employee->Emp_id;
        $encodedId = base64_encode($empId);

        $selfRows = PerformaChildCycle::join('performance_cycles as pc', 'pc.id', '=', 'performa_child_cycles.Parent_cycle_id')
            ->where('pc.resort_id', $this->resort_id)
            ->where(function ($q) use ($empId, $encodedId, $empCode) {
                $q->where('performa_child_cycles.Emp_main_id', $empId)
                  ->orWhere('performa_child_cycles.Emp_main_id', $encodedId);
                if ($empCode) $q->orWhere('performa_child_cycles.Emp_main_id', $empCode);
            })
            ->orderBy('pc.Start_Date', 'desc')
            ->get(['performa_child_cycles.*', 'pc.Cycle_Name', 'pc.Start_Date as CycleStart', 'pc.End_Date as CycleEnd', 'pc.Self_Activity_Start_Date', 'pc.Self_Activity_End_Date']);

        $managerRows = PerformaChildCycle::join('performance_cycles as pc', 'pc.id', '=', 'performa_child_cycles.Parent_cycle_id')
            ->where('pc.resort_id', $this->resort_id)
            ->where('performa_child_cycles.Manager_id', $empId)
            ->orderBy('pc.Start_Date', 'desc')
            ->get(['performa_child_cycles.*', 'pc.Cycle_Name', 'pc.Start_Date as CycleStart', 'pc.End_Date as CycleEnd', 'pc.Manager_Activity_Start_Date', 'pc.Manager_Activity_End_Date']);

        $reviews = [];
        foreach ($selfRows as $r) {
            $window = $this->getWindowStatus($r->Self_Activity_Start_Date ?? null, $r->Self_Activity_End_Date ?? null);
            $reviews[] = [
                'id'          => $r->id,
                'role'        => 'self',
                'cycle_name'  => $r->Cycle_Name,
                'cycle_start' => $r->CycleStart,
                'cycle_end'   => $r->CycleEnd,
                'status'      => $r->self_review_status === 'completed' ? 'Submitted' : 'Pending Action',
                'submitted_at'=> $r->Self_review_date,
                'window'      => $window,
            ];
        }
        foreach ($managerRows as $r) {
            $selfDone = $r->self_review_status === 'completed';
            $status = 'Waiting for Self Review';
            if ($r->manager_review_status === 'completed') {
                $status = 'Submitted';
            } elseif ($selfDone) {
                $status = 'Pending Action';
            }
            $window = $this->getWindowStatus($r->Manager_Activity_Start_Date ?? null, $r->Manager_Activity_End_Date ?? null);
            $reviews[] = [
                'id'          => $r->id,
                'role'        => 'manager',
                'cycle_name'  => $r->Cycle_Name,
                'cycle_start' => $r->CycleStart,
                'cycle_end'   => $r->CycleEnd,
                'status'      => $status,
                'submitted_at'=> $r->Manager_review_date,
                'window'      => $window,
            ];
        }

        return response()->json(['success' => true, 'data' => $reviews], 200);
    }

    /**
     * GET performance/review/{id}
     * Template + any existing saved answers for one assigned form, role auto-detected.
     */
    public function reviewDetail(Request $request, $id)
    {
        if (!$this->employee) return $this->unauthorized();

        $childCycle = PerformaChildCycle::join('performance_cycles as pc', 'pc.id', '=', 'performa_child_cycles.Parent_cycle_id')
            ->where('performa_child_cycles.id', $id)
            ->where('pc.resort_id', $this->resort_id)
            ->first(['performa_child_cycles.*', 'pc.Cycle_Name', 'pc.Self_Activity_Start_Date', 'pc.Self_Activity_End_Date', 'pc.Manager_Activity_Start_Date', 'pc.Manager_Activity_End_Date', 'pc.Self_Review_Templete', 'pc.Manager_Review_Templete']);

        if (!$childCycle) {
            return response()->json(['success' => false, 'message' => 'Review not found.'], 404);
        }

        $role = $this->resolveRole($childCycle);
        if (!$role) {
            return response()->json(['success' => false, 'message' => 'You are not assigned to this review.'], 403);
        }

        if ($role === 'manager') {
            $window = $this->getWindowStatus($childCycle->Manager_Activity_Start_Date, $childCycle->Manager_Activity_End_Date);
            $effectiveTemplateId = $childCycle->template_id ?: ($childCycle->Manager_Review_Templete ?? null);
        } else {
            $window = $this->getWindowStatus($childCycle->Self_Activity_Start_Date, $childCycle->Self_Activity_End_Date);
            $effectiveTemplateId = $childCycle->template_id ?: ($childCycle->Self_Review_Templete ?? null);
        }

        $template = $this->getTemplate($effectiveTemplateId);
        $template = $this->applyRoleGatingToTemplate($template, $role === 'manager' ? 'Manager' : 'Self');

        $selfData = $childCycle->self_review_data ? json_decode($childCycle->self_review_data, true) : [];
        $managerData = $childCycle->manager_review_data ? json_decode($childCycle->manager_review_data, true) : [];

        return response()->json([
            'success' => true,
            'data' => [
                'id'                    => $childCycle->id,
                'role'                  => $role,
                'cycle_name'            => $childCycle->Cycle_Name,
                'template'              => $template,
                'existing_data'         => $role === 'manager' ? $managerData : $selfData,
                'self_review_data'      => $selfData,
                'self_review_status'    => $childCycle->self_review_status,
                'manager_review_status' => $childCycle->manager_review_status,
                'window'                => $window,
            ],
        ], 200);
    }

    /**
     * POST performance/review/{id}/submit
     * Submits whichever role the current employee holds on this cycle
     * (self review if they're the reviewee, manager review if they're the
     * reporting manager — a self review must already be completed first).
     */
    public function submitReview(Request $request, $id)
    {
        if (!$this->employee) return $this->unauthorized();

        $childCycle = PerformaChildCycle::join('performance_cycles as pc', 'pc.id', '=', 'performa_child_cycles.Parent_cycle_id')
            ->where('performa_child_cycles.id', $id)
            ->where('pc.resort_id', $this->resort_id)
            ->first(['performa_child_cycles.*', 'pc.Cycle_Name', 'pc.Self_Activity_Start_Date', 'pc.Self_Activity_End_Date', 'pc.Manager_Activity_Start_Date', 'pc.Manager_Activity_End_Date', 'pc.Self_Review_Templete', 'pc.Manager_Review_Templete']);

        if (!$childCycle) {
            return response()->json(['success' => false, 'message' => 'Review not found.'], 404);
        }

        $role = $this->resolveRole($childCycle, forSubmit: true);
        if (!$role) {
            return response()->json(['success' => false, 'message' => 'You are not assigned to this review.'], 403);
        }

        return $role === 'manager'
            ? $this->submitManagerReview($childCycle, $request)
            : $this->submitSelfReview($childCycle, $request);
    }

    private function submitSelfReview(PerformaChildCycle $childCycle, Request $request)
    {
        $window = $this->getWindowStatus($childCycle->Self_Activity_Start_Date, $childCycle->Self_Activity_End_Date);
        if (!$window['open']) {
            return response()->json(['success' => false, 'message' => $window['message']], 422);
        }

        $effectiveTemplateId = $childCycle->template_id ?: ($childCycle->Self_Review_Templete ?? null);
        $template = $this->getTemplate($effectiveTemplateId);
        $payload = $request->except(['_token']);

        [$payload, $rejectedLabels] = $this->gateSubmittedPayloadByRole($template, $payload, 'Self');
        if (!empty($rejectedLabels)) {
            return response()->json(['success' => false, 'message' => 'You are not authorised to fill these fields: ' . implode(', ', $rejectedLabels)], 422);
        }

        $errors = $this->validateAgainstTemplate($template, $payload);
        if (!empty($errors)) {
            return response()->json(['success' => false, 'message' => 'Please fill all required fields', 'errors' => $errors], 422);
        }

        $realChild = PerformaChildCycle::find($childCycle->id);
        $existing = $realChild->self_review_data ? (json_decode($realChild->self_review_data, true) ?: []) : [];
        $realChild->self_review_data = json_encode(array_merge($existing, $payload));
        $realChild->self_review_status = 'completed';
        $realChild->Self_review_date = now()->format('Y-m-d');
        $realChild->save();

        try {
            $empName = $this->employee->resortAdmin
                ? trim(($this->employee->resortAdmin->first_name ?? '') . ' ' . ($this->employee->resortAdmin->last_name ?? ''))
                : 'Your team member';

            if ($realChild->Manager_id) {
                Common::notifyEmployees($this->resort_id, [(int) $realChild->Manager_id], 'Self Review Completed',
                    $empName . ' has completed their self review for "' . $childCycle->Cycle_Name . '". Please complete the manager review.',
                    'Performance', $realChild->id, 'appraisal-manager-review-assigned');
            }
            $hrEmpIds = Common::getResortHrEmployeeIds($this->resort_id);
            if ($realChild->Manager_id) {
                $hrEmpIds = array_values(array_diff($hrEmpIds, [(int) $realChild->Manager_id]));
            }
            if (!empty($hrEmpIds)) {
                Common::notifyEmployees($this->resort_id, $hrEmpIds, 'Self Review Submitted',
                    $empName . ' has submitted their self review for "' . $childCycle->Cycle_Name . '".',
                    'Performance', $realChild->id, 'appraisal-self-review-submitted');
            }
        } catch (\Exception $ne) {
            \Log::warning('Mobile self review completion notification failed: ' . $ne->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'Self review submitted successfully.'], 200);
    }

    private function submitManagerReview(PerformaChildCycle $childCycle, Request $request)
    {
        if ($childCycle->self_review_status !== 'completed') {
            return response()->json(['success' => false, 'message' => 'The employee has not completed their self review yet.'], 422);
        }

        $window = $this->getWindowStatus($childCycle->Manager_Activity_Start_Date, $childCycle->Manager_Activity_End_Date);
        if (!$window['open']) {
            return response()->json(['success' => false, 'message' => $window['message']], 422);
        }

        $effectiveTemplateId = $childCycle->template_id ?: ($childCycle->Manager_Review_Templete ?? null);
        $template = $this->getTemplate($effectiveTemplateId);
        $payload = $request->except(['_token']);

        [$payload, $rejectedLabels] = $this->gateSubmittedPayloadByRole($template, $payload, 'Manager');
        if (!empty($rejectedLabels)) {
            return response()->json(['success' => false, 'message' => 'You are not authorised to fill these fields: ' . implode(', ', $rejectedLabels)], 422);
        }

        $errors = $this->validateAgainstTemplate($template, $payload);
        if (!empty($errors)) {
            return response()->json(['success' => false, 'message' => 'Please fill all required fields', 'errors' => $errors], 422);
        }

        $realChild = PerformaChildCycle::find($childCycle->id);
        $existing = $realChild->manager_review_data ? (json_decode($realChild->manager_review_data, true) ?: []) : [];
        $realChild->manager_review_data = json_encode(array_merge($existing, $payload));
        $realChild->manager_review_status = 'completed';
        $realChild->Manager_review_date = now()->format('Y-m-d');
        $realChild->save();

        try {
            $participantId = Common::resolveEmpMainIdToNumeric($realChild->Emp_main_id, $this->resort_id);
            if ($participantId) {
                Common::notifyEmployees($this->resort_id, [$participantId], 'Manager Review Completed',
                    'Your manager has completed the review for "' . $childCycle->Cycle_Name . '". You can view the feedback in My Reviews.',
                    'Performance', $realChild->id, 'appraisal-manager-review-completed');
            }
            $managerName = $this->employee->resortAdmin
                ? trim(($this->employee->resortAdmin->first_name ?? '') . ' ' . ($this->employee->resortAdmin->last_name ?? ''))
                : 'A manager';
            $hrEmpIds = Common::getResortHrEmployeeIds($this->resort_id);
            $hrEmpIds = array_values(array_diff($hrEmpIds, array_filter([$participantId, $this->employee->id], fn ($v) => $v !== null)));
            if (!empty($hrEmpIds)) {
                Common::notifyEmployees($this->resort_id, $hrEmpIds, 'Team Review Submitted',
                    $managerName . ' has submitted a team review for "' . $childCycle->Cycle_Name . '".',
                    'Performance', $realChild->id, 'appraisal-team-review-submitted');
            }
        } catch (\Exception $ne) {
            \Log::warning('Mobile manager review completion notification failed: ' . $ne->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'Manager review submitted successfully.'], 200);
    }

    /** 'self' if I'm the reviewee, 'manager' if I'm the assigned manager, null if neither. */
    private function resolveRole(PerformaChildCycle $childCycle, bool $forSubmit = false): ?string
    {
        $empId = $this->employee->id;
        $participantId = Common::resolveEmpMainIdToNumeric($childCycle->Emp_main_id, $this->resort_id);

        if ((int) $empId === (int) $participantId) return 'self';
        if ($childCycle->Manager_id && (int) $empId === (int) $childCycle->Manager_id) return 'manager';

        return null;
    }

    private function getWindowStatus($startRaw, $endRaw): array
    {
        $start = $startRaw ? Carbon::parse($startRaw) : null;
        $end = $endRaw ? Carbon::parse($endRaw) : null;
        $now = Carbon::now()->startOfDay();

        if (!$start || !$end) {
            return ['open' => true, 'message' => 'No review window configured', 'status' => 'no_window'];
        }
        if ($now->lt($start)) {
            return ['open' => false, 'message' => 'Review window opens on ' . $start->format('d M Y'), 'status' => 'upcoming'];
        }
        if ($now->gt($end)) {
            return ['open' => false, 'message' => 'Review window closed on ' . $end->format('d M Y'), 'status' => 'closed'];
        }
        return ['open' => true, 'message' => 'Open until ' . $end->format('d M Y'), 'status' => 'open'];
    }

    private function applyRoleGatingToTemplate(?array $template, string $viewerRole): ?array
    {
        if (!$template || empty($template['structure']) || !is_array($template['structure'])) {
            return $template;
        }
        foreach ($template['structure'] as &$field) {
            $roles = (array) ($field['responder_roles'] ?? []);
            $allowed = empty($roles) || $this->roleMatchesViewer($viewerRole, $roles);
            $field['_readonly'] = !$allowed;
            $field['_assigned_to'] = $allowed ? '' : implode(', ', $roles);
        }
        unset($field);
        return $template;
    }

    private function gateSubmittedPayloadByRole(?array $template, array $payload, string $viewerRole): array
    {
        if (!$template || empty($template['structure'])) {
            return [$payload, []];
        }
        $rejected = [];
        $byName = [];
        foreach ($template['structure'] as $field) {
            if (!empty($field['name'])) $byName[$field['name']] = $field;
        }
        $kept = [];
        foreach ($payload as $name => $value) {
            $baseName = $name;
            if (!isset($byName[$baseName])) {
                foreach ($byName as $fname => $f) {
                    if (strpos($name, $fname . '_') === 0) { $baseName = $fname; break; }
                }
            }
            $field = $byName[$baseName] ?? null;
            $roles = $field ? (array) ($field['responder_roles'] ?? []) : [];
            $allowed = empty($roles) || $this->roleMatchesViewer($viewerRole, $roles);

            if ($allowed) {
                $kept[$name] = $value;
                continue;
            }
            $hasRealValue = is_array($value)
                ? count(array_filter($value, fn ($v) => $v !== null && $v !== ''))
                : ($value !== null && $value !== '');
            if ($hasRealValue) {
                $rejected[] = $field['label'] ?? $baseName;
            }
        }
        return [$kept, array_values(array_unique($rejected))];
    }

    private function roleMatchesViewer(string $viewerRole, array $fieldRoles): bool
    {
        if ($viewerRole === '') return false;
        foreach ($fieldRoles as $r) {
            if (strcasecmp((string) $r, $viewerRole) === 0) return true;
        }
        return false;
    }

    private function getTemplate($templateId)
    {
        if (empty($templateId) || $templateId === '0' || $templateId === 0) return null;

        if (is_string($templateId) && strpos($templateId, 'ninty_') === 0) {
            $form = NintyDayPeformanceForm::find(substr($templateId, 6));
            return $form ? ['name' => $form->FormName, 'structure' => $this->decodeFormStructure($form->form_structure), 'type' => '90 Day'] : null;
        }
        if (is_string($templateId) && strpos($templateId, 'prof_') === 0 && class_exists(\App\Models\Professionalform::class)) {
            $form = \App\Models\Professionalform::find(substr($templateId, 5));
            return $form ? ['name' => $form->FormName, 'structure' => $this->decodeFormStructure($form->form_structure), 'type' => 'Professional'] : null;
        }
        if (is_numeric($templateId)) {
            $form = PerformanceTemplateForm::find($templateId);
            if ($form) return ['name' => $form->FormName, 'structure' => $this->decodeFormStructure($form->form_structure), 'type' => 'Template'];

            $form = NintyDayPeformanceForm::find($templateId);
            if ($form) return ['name' => $form->FormName, 'structure' => $this->decodeFormStructure($form->form_structure), 'type' => '90 Day'];

            if (class_exists(\App\Models\Professionalform::class)) {
                $form = \App\Models\Professionalform::find($templateId);
                if ($form) return ['name' => $form->FormName, 'structure' => $this->decodeFormStructure($form->form_structure), 'type' => 'Professional'];
            }
        }
        return null;
    }

    private function validateAgainstTemplate($template, array $payload)
    {
        $errors = [];
        if (!$template || empty($template['structure']) || !is_array($template['structure'])) {
            return $errors;
        }
        foreach ($template['structure'] as $idx => $field) {
            if (empty($field['required'])) continue;
            $type = $field['type'] ?? null;
            if (in_array($type, ['header', 'paragraph'])) continue;

            $name = $field['name'] ?? ('field_' . $idx);

            if ($type === 'ratingTable') {
                $hasAny = false;
                foreach ($payload as $k => $v) {
                    if (strpos($k, $name . '_') === 0 && $v !== '' && $v !== null) { $hasAny = true; break; }
                }
                if (!$hasAny) $errors[$name] = ($field['label'] ? strip_tags($field['label']) : $name) . ' is required.';
                continue;
            }

            $val = $payload[$name] ?? null;
            $empty = ($val === null || $val === '' || (is_array($val) && count($val) === 0));
            if ($empty) $errors[$name] = ($field['label'] ? strip_tags($field['label']) : $name) . ' is required.';
        }
        return $errors;
    }

    private function decodeFormStructure($raw)
    {
        $decoded = json_decode($raw, true);
        if (is_string($decoded)) $decoded = json_decode($decoded, true);
        return is_array($decoded) ? $decoded : [];
    }
}
