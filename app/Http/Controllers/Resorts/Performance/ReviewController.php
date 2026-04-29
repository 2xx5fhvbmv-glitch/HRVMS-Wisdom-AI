<?php

namespace App\Http\Controllers\Resorts\Performance;

use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PerformanceCycle;
use App\Models\PerformaChildCycle;
use App\Models\PerformanceTemplateForm;
use App\Models\NintyDayPeformanceForm;
use App\Helpers\Common;

class ReviewController extends Controller
{
    public $resort;

    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
    }

    /**
     * List all reviews where current user is the participant (Self Review)
     */
    public function mySelfReviews()
    {
        $page_title = "My Performance Reviews";
        $employee = $this->resort->GetEmployee;
        if (!$employee) {
            return view('resorts.Performance.Review.my-reviews', compact('page_title') + ['reviews' => collect()]);
        }

        $empId = $employee->id;
        $encodedId = base64_encode($empId);
        // Legacy cycle rows stored the Emp_id string (e.g. "DR-22") instead of the numeric key,
        // so include that as a third match option.
        $empCode = $employee->Emp_id;

        $reviews = PerformaChildCycle::join('performance_cycles as pc', 'pc.id', '=', 'performa_child_cycles.Parent_cycle_id')
            ->where('pc.resort_id', $this->resort->resort_id)
            ->where(function ($q) use ($empId, $encodedId, $empCode) {
                $q->where('performa_child_cycles.Emp_main_id', $empId)
                  ->orWhere('performa_child_cycles.Emp_main_id', $encodedId);
                if ($empCode) {
                    $q->orWhere('performa_child_cycles.Emp_main_id', $empCode);
                }
            })
            ->orderBy('pc.Start_Date', 'desc')
            ->get(['performa_child_cycles.*', 'pc.Cycle_Name', 'pc.Start_Date as CycleStart', 'pc.End_Date as CycleEnd', 'pc.status as CycleStatus', 'pc.Self_Activity_Start_Date', 'pc.Self_Activity_End_Date']);

        foreach ($reviews as $r) {
            $r->window = $this->getSelfReviewWindowStatus($r);
        }

        return view('resorts.Performance.Review.my-reviews', compact('page_title', 'reviews'));
    }

    /**
     * List all reviews where current user is the reporting manager
     */
    public function myTeamReviews()
    {
        $page_title = "Team Performance Reviews";
        $employee = $this->resort->GetEmployee;
        if (!$employee) {
            return view('resorts.Performance.Review.team-reviews', compact('page_title') + ['reviews' => collect()]);
        }

        $reviews = PerformaChildCycle::join('performance_cycles as pc', 'pc.id', '=', 'performa_child_cycles.Parent_cycle_id')
            ->where('pc.resort_id', $this->resort->resort_id)
            ->where('performa_child_cycles.Manager_id', $employee->id)
            ->orderBy('pc.Start_Date', 'desc')
            ->get(['performa_child_cycles.*', 'pc.Cycle_Name', 'pc.Start_Date as CycleStart', 'pc.End_Date as CycleEnd']);

        // Attach employee details
        foreach ($reviews as $r) {
            $actualId = Common::resolveEmpMainIdToNumeric($r->Emp_main_id, $this->resort->resort_id);
            $emp = Employee::with(['resortAdmin', 'position'])->find($actualId);
            $r->employee_name = $emp ? optional($emp->resortAdmin)->first_name . ' ' . optional($emp->resortAdmin)->last_name : 'N/A';
            $r->employee_position = $emp && $emp->position ? $emp->position->position_title : 'N/A';
            $r->employee_profile = $emp ? Common::getResortUserPicture(optional($emp->resortAdmin)->id) : '';
        }

        return view('resorts.Performance.Review.team-reviews', compact('page_title', 'reviews'));
    }

    /**
     * Show self review form
     */
    public function showSelfReview($id)
    {
        $id = base64_decode($id);
        $childCycle = PerformaChildCycle::join('performance_cycles as pc', 'pc.id', '=', 'performa_child_cycles.Parent_cycle_id')
            ->where('performa_child_cycles.id', $id)
            ->where('pc.resort_id', $this->resort->resort_id)
            ->first(['performa_child_cycles.*', 'pc.Cycle_Name', 'pc.Start_Date as CycleStart', 'pc.End_Date as CycleEnd', 'pc.Self_Activity_Start_Date', 'pc.Self_Activity_End_Date', 'pc.Manager_Activity_Start_Date', 'pc.Manager_Activity_End_Date', 'pc.Self_Review_Templete', 'pc.Manager_Review_Templete']);

        if (!$childCycle) {
            abort(404, 'Review not found');
        }

        // Verify current user is the participant — OR an authorized overseer viewing a completed review read-only.
        $currentEmpId = $this->resort->GetEmployee->id ?? null;
        $participantId = Common::resolveEmpMainIdToNumeric($childCycle->Emp_main_id, $this->resort->resort_id);
        // Overseer = anyone with full data access (HR / GM / HR-dept HOD/XCOM / super-admin)
        // OR a user with the cycle-view permission. The previous check required only the
        // explicit permission, which Engineering / non-HR-dept GM accounts on live were
        // missing — so they got 403 even on completed reviews.
        $isOverseer = (strtolower((string) $childCycle->self_review_status) === 'completed')
            && (Common::hasFullDataAccess()
                || Common::checkRouteWisePermission('Performance.cycle', config('settings.resort_permissions.view')));
        if ($currentEmpId != $participantId && !$isOverseer) {
            abort(403, 'You are not authorized to view this review');
        }

        // Check if self review window is open
        $windowStatus = $this->getSelfReviewWindowStatus($childCycle);

        // Template resolution — child-level first, then parent cycle's Self_Review_Templete
        $effectiveTemplateId = $childCycle->template_id;
        if (empty($effectiveTemplateId) && !empty($childCycle->Self_Review_Templete)) {
            $effectiveTemplateId = $childCycle->Self_Review_Templete;
        }
        $template = $this->getTemplate($effectiveTemplateId);
        $templateError = null;
        if (!$template) {
            if (empty($effectiveTemplateId)) {
                $templateError = 'No template was assigned when this cycle was created. Ask HR to edit the cycle and pick a review template.';
            } else {
                $templateError = 'The configured template (id ' . e($effectiveTemplateId) . ') could not be found. It may have been deleted — ask HR to re-attach a template to this cycle.';
            }
        } elseif (empty($template['structure'])) {
            $templateError = 'The configured template "' . e($template['name']) . '" has no fields. Ask HR to open it in the form builder and add at least one question.';
        }
        $existingData = $childCycle->self_review_data ? json_decode($childCycle->self_review_data, true) : [];
        $page_title = "Self Review - " . $childCycle->Cycle_Name;

        return view('resorts.Performance.Review.self-review-form', compact('page_title', 'childCycle', 'template', 'templateError', 'existingData', 'windowStatus'));
    }

    public function submitSelfReview(Request $request, $id)
    {
        $id = base64_decode($id);
        $childCycle = PerformaChildCycle::join('performance_cycles as pc', 'pc.id', '=', 'performa_child_cycles.Parent_cycle_id')
            ->where('performa_child_cycles.id', $id)
            ->first(['performa_child_cycles.*', 'pc.Cycle_Name', 'pc.Self_Activity_Start_Date', 'pc.Self_Activity_End_Date']);

        if (!$childCycle) {
            return response()->json(['success' => false, 'message' => 'Review not found'], 404);
        }

        $currentEmpId = $this->resort->GetEmployee->id ?? null;
        $participantId = Common::resolveEmpMainIdToNumeric($childCycle->Emp_main_id, $this->resort->resort_id);
        if ($currentEmpId != $participantId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Enforce self review date window
        $windowStatus = $this->getSelfReviewWindowStatus($childCycle);
        if (!$windowStatus['open']) {
            return response()->json(['success' => false, 'message' => $windowStatus['message']], 422);
        }

        // Validate all required template fields were submitted
        $effectiveTemplateId = $childCycle->template_id ?: ($childCycle->Self_Review_Templete ?? null);
        $template = $this->getTemplate($effectiveTemplateId);
        $payload = $request->except(['_token']);
        $errors = $this->validateAgainstTemplate($template, $payload);
        if (!empty($errors)) {
            return response()->json(['success' => false, 'message' => 'Please fill all required fields', 'errors' => $errors], 422);
        }

        $realChild = PerformaChildCycle::find($id);
        if (!$realChild) {
            return response()->json(['success' => false, 'message' => 'Review record disappeared mid-submit. Refresh and try again.'], 404);
        }
        $realChild->self_review_data = json_encode($payload);
        $realChild->self_review_status = 'completed';
        $realChild->Self_review_date = now()->format('Y-m-d');
        $saved = $realChild->save();
        if (!$saved) {
            \Log::warning("Self review save failed for child_id {$id}");
            return response()->json(['success' => false, 'message' => 'Failed to save review. Please try again.'], 500);
        }

        // Notify the assigned manager that the self review is done and manager review is unlocked,
        // and fan out the same alert to HR so they can track submission progress.
        try {
            $employee = $this->resort->GetEmployee;
            $empName = $employee && $employee->resortAdmin
                ? trim(($employee->resortAdmin->first_name ?? '') . ' ' . ($employee->resortAdmin->last_name ?? ''))
                : 'Your team member';

            if ($realChild->Manager_id) {
                Common::notifyEmployees(
                    $this->resort->resort_id,
                    [(int) $realChild->Manager_id],
                    'Self Review Completed',
                    $empName . ' has completed their self review for "' . $childCycle->Cycle_Name . '". Please complete the manager review.',
                    'Performance',
                    $realChild->id
                );
            }

            $hrEmpIds = Common::getResortHrEmployeeIds($this->resort->resort_id);
            // Avoid double-notifying the manager if they happen to be HR themselves.
            if ($realChild->Manager_id) {
                $hrEmpIds = array_values(array_diff($hrEmpIds, [(int) $realChild->Manager_id]));
            }
            if (!empty($hrEmpIds)) {
                Common::notifyEmployees(
                    $this->resort->resort_id,
                    $hrEmpIds,
                    'Self Review Submitted',
                    $empName . ' has submitted their self review for "' . $childCycle->Cycle_Name . '".',
                    'Performance',
                    $realChild->id
                );
            }
        } catch (\Exception $ne) {
            \Log::warning('Self review completion notification failed: ' . $ne->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'Self review submitted successfully']);
    }

    private function getSelfReviewWindowStatus($cycle)
    {
        $start = $cycle->Self_Activity_Start_Date ? Carbon::parse($cycle->Self_Activity_Start_Date) : null;
        $end = $cycle->Self_Activity_End_Date ? Carbon::parse($cycle->Self_Activity_End_Date) : null;
        $now = Carbon::now()->startOfDay();

        if (!$start || !$end) {
            return ['open' => true, 'message' => 'No review window configured', 'status' => 'no_window'];
        }
        if ($now->lt($start)) {
            return ['open' => false, 'message' => 'Self review window opens on ' . $start->format('d M Y'), 'status' => 'upcoming', 'start' => $start, 'end' => $end];
        }
        if ($now->gt($end)) {
            return ['open' => false, 'message' => 'Self review window closed on ' . $end->format('d M Y'), 'status' => 'closed', 'start' => $start, 'end' => $end];
        }
        return ['open' => true, 'message' => 'Open until ' . $end->format('d M Y'), 'status' => 'open', 'start' => $start, 'end' => $end];
    }

    private function getManagerReviewWindowStatus($cycle)
    {
        $start = $cycle->Manager_Activity_Start_Date ? Carbon::parse($cycle->Manager_Activity_Start_Date) : null;
        $end = $cycle->Manager_Activity_End_Date ? Carbon::parse($cycle->Manager_Activity_End_Date) : null;
        $now = Carbon::now()->startOfDay();

        if (!$start || !$end) {
            return ['open' => true, 'message' => 'No review window configured', 'status' => 'no_window'];
        }
        if ($now->lt($start)) {
            return ['open' => false, 'message' => 'Manager review window opens on ' . $start->format('d M Y'), 'status' => 'upcoming', 'start' => $start, 'end' => $end];
        }
        if ($now->gt($end)) {
            return ['open' => false, 'message' => 'Manager review window closed on ' . $end->format('d M Y'), 'status' => 'closed', 'start' => $start, 'end' => $end];
        }
        return ['open' => true, 'message' => 'Open until ' . $end->format('d M Y'), 'status' => 'open', 'start' => $start, 'end' => $end];
    }

    /**
     * Show manager review form (only after self review is done)
     */
    public function showManagerReview($id)
    {
        $id = base64_decode($id);
        $childCycle = PerformaChildCycle::join('performance_cycles as pc', 'pc.id', '=', 'performa_child_cycles.Parent_cycle_id')
            ->where('performa_child_cycles.id', $id)
            ->where('pc.resort_id', $this->resort->resort_id)
            ->first(['performa_child_cycles.*', 'pc.Cycle_Name', 'pc.Self_Activity_Start_Date', 'pc.Self_Activity_End_Date', 'pc.Manager_Activity_Start_Date', 'pc.Manager_Activity_End_Date', 'pc.Self_Review_Templete', 'pc.Manager_Review_Templete']);

        if (!$childCycle) {
            abort(404);
        }

        $currentEmpId = $this->resort->GetEmployee->id ?? null;
        $isOverseer = (strtolower((string) $childCycle->manager_review_status) === 'completed')
            && (Common::hasFullDataAccess()
                || Common::checkRouteWisePermission('Performance.cycle', config('settings.resort_permissions.view')));
        if ($currentEmpId != $childCycle->Manager_id && !$isOverseer) {
            abort(403, 'You are not the assigned manager for this review');
        }

        if (!$isOverseer && $childCycle->self_review_status !== 'completed') {
            abort(403, 'Employee has not completed self review yet');
        }

        $windowStatus = $this->getManagerReviewWindowStatus($childCycle);

        $actualId = Common::resolveEmpMainIdToNumeric($childCycle->Emp_main_id, $this->resort->resort_id);
        $employee = Employee::with(['resortAdmin', 'position'])->find($actualId);

        // Template resolution — child-level first, then parent cycle's Manager_Review_Templete
        $effectiveTemplateId = $childCycle->template_id;
        if (empty($effectiveTemplateId) && !empty($childCycle->Manager_Review_Templete)) {
            $effectiveTemplateId = $childCycle->Manager_Review_Templete;
        }
        $template = $this->getTemplate($effectiveTemplateId);
        $selfData = $childCycle->self_review_data ? json_decode($childCycle->self_review_data, true) : [];
        $existingData = $childCycle->manager_review_data ? json_decode($childCycle->manager_review_data, true) : [];
        $page_title = "Manager Review - " . $childCycle->Cycle_Name;

        return view('resorts.Performance.Review.manager-review-form', compact('page_title', 'childCycle', 'template', 'selfData', 'existingData', 'employee', 'windowStatus'));
    }

    public function submitManagerReview(Request $request, $id)
    {
        $id = base64_decode($id);
        $childCycle = PerformaChildCycle::join('performance_cycles as pc', 'pc.id', '=', 'performa_child_cycles.Parent_cycle_id')
            ->where('performa_child_cycles.id', $id)
            ->first(['performa_child_cycles.*', 'pc.Cycle_Name', 'pc.Manager_Activity_Start_Date', 'pc.Manager_Activity_End_Date']);

        if (!$childCycle) {
            return response()->json(['success' => false, 'message' => 'Review not found'], 404);
        }

        $currentEmpId = $this->resort->GetEmployee->id ?? null;
        if ($currentEmpId != $childCycle->Manager_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Enforce manager review date window
        $windowStatus = $this->getManagerReviewWindowStatus($childCycle);
        if (!$windowStatus['open']) {
            return response()->json(['success' => false, 'message' => $windowStatus['message']], 422);
        }

        // Validate all required template fields were submitted
        $effectiveTemplateId = $childCycle->template_id ?: ($childCycle->Manager_Review_Templete ?? null);
        $template = $this->getTemplate($effectiveTemplateId);
        $payload = $request->except(['_token']);
        $errors = $this->validateAgainstTemplate($template, $payload);
        if (!empty($errors)) {
            return response()->json(['success' => false, 'message' => 'Please fill all required fields', 'errors' => $errors], 422);
        }

        $realChild = PerformaChildCycle::find($id);
        if (!$realChild) {
            return response()->json(['success' => false, 'message' => 'Review record disappeared mid-submit. Refresh and try again.'], 404);
        }
        $realChild->manager_review_data = json_encode($payload);
        $realChild->manager_review_status = 'completed';
        $realChild->Manager_review_date = now()->format('Y-m-d');
        $saved = $realChild->save();
        if (!$saved) {
            \Log::warning("Manager review save failed for child_id {$id}");
            return response()->json(['success' => false, 'message' => 'Failed to save review. Please try again.'], 500);
        }

        // Notify the employee that their manager review is complete, and fan out
        // the same alert to HR so they can track team-review submissions.
        try {
            $participantId = Common::resolveEmpMainIdToNumeric($realChild->Emp_main_id, $this->resort->resort_id);
            if ($participantId) {
                Common::notifyEmployees(
                    $this->resort->resort_id,
                    [$participantId],
                    'Manager Review Completed',
                    'Your manager has completed the review for "' . $childCycle->Cycle_Name . '". You can view the feedback in My Reviews.',
                    'Performance',
                    $realChild->id
                );
            }

            $managerEmp = $this->resort->GetEmployee;
            $managerName = $managerEmp && $managerEmp->resortAdmin
                ? trim(($managerEmp->resortAdmin->first_name ?? '') . ' ' . ($managerEmp->resortAdmin->last_name ?? ''))
                : 'A manager';

            $hrEmpIds = Common::getResortHrEmployeeIds($this->resort->resort_id);
            // Don't double-notify the employee or the manager themselves if they fall in HR.
            $hrEmpIds = array_values(array_diff(
                $hrEmpIds,
                array_filter([$participantId, $currentEmpId], fn($v) => $v !== null)
            ));
            if (!empty($hrEmpIds)) {
                Common::notifyEmployees(
                    $this->resort->resort_id,
                    $hrEmpIds,
                    'Team Review Submitted',
                    $managerName . ' has submitted a team review for "' . $childCycle->Cycle_Name . '".',
                    'Performance',
                    $realChild->id
                );
            }
        } catch (\Exception $ne) {
            \Log::warning('Manager review completion notification failed: ' . $ne->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'Manager review submitted successfully']);
    }

    /**
     * Export GM review form (for offline corporate review)
     */
    public function exportGmReview($id)
    {
        $id = base64_decode($id);
        $childCycle = PerformaChildCycle::join('performance_cycles as pc', 'pc.id', '=', 'performa_child_cycles.Parent_cycle_id')
            ->where('performa_child_cycles.id', $id)
            ->where('pc.resort_id', $this->resort->resort_id)
            ->first(['performa_child_cycles.*', 'pc.Cycle_Name', 'pc.Start_Date as CycleStart', 'pc.End_Date as CycleEnd']);

        if (!$childCycle || !$childCycle->is_gm_review) {
            abort(404, 'GM review not found');
        }

        if ($childCycle->self_review_status !== 'completed') {
            return back()->with('error', 'GM has not completed self review yet');
        }

        $actualId = Common::resolveEmpMainIdToNumeric($childCycle->Emp_main_id, $this->resort->resort_id);
        $employee = Employee::with(['resortAdmin', 'position'])->find($actualId);

        $template = $this->getTemplate($childCycle->template_id);
        $selfData = $childCycle->self_review_data ? json_decode($childCycle->self_review_data, true) : [];

        // Mark as exported
        $childCycle->exported_at = now();
        $childCycle->save();

        $page_title = "GM Review Export - " . $childCycle->Cycle_Name;
        return view('resorts.Performance.Review.gm-export', compact('page_title', 'childCycle', 'template', 'selfData', 'employee'));
    }

    /**
     * Helper: Get template structure based on template_id format.
     * Template IDs from CycleFetchTemplate use prefixes: 'ninty_X', 'prof_X', or numeric.
     *
     * Bare numeric ids are ambiguous — they could point at any of the three form
     * tables. Legacy cycles saved Self_Review_Templete as a plain int (the result
     * of `preg_match('/(\d+)/', 'ninty_2')` → 2), so when we fall back to the
     * cycle column we must probe each table before declaring "not found".
     */
    private function getTemplate($templateId)
    {
        if (empty($templateId) || $templateId === '0' || $templateId === 0) return null;

        if (strpos($templateId, 'ninty_') === 0) {
            $realId = substr($templateId, 6);
            $form = NintyDayPeformanceForm::find($realId);
            return $form ? ['name' => $form->FormName, 'structure' => $this->decodeFormStructure($form->form_structure), 'type' => '90 Day'] : null;
        }
        if (strpos($templateId, 'prof_') === 0) {
            $realId = substr($templateId, 5);
            if (class_exists(\App\Models\Professionalform::class)) {
                $form = \App\Models\Professionalform::find($realId);
                return $form ? ['name' => $form->FormName, 'structure' => $this->decodeFormStructure($form->form_structure), 'type' => 'Professional'] : null;
            }
        }

        // Bare numeric id — try each form table in order. Custom templates first
        // (most specific); fall through to 90-day and professional forms.
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

    /**
     * Validate submitted payload against a template's required fields.
     * Returns an associative array of field_name => error_message, or [] when valid.
     */
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
                // Accept any non-empty cell (stored under name_r_c keys)
                $hasAny = false;
                foreach ($payload as $k => $v) {
                    if (strpos($k, $name . '_') === 0 && $v !== '' && $v !== null) {
                        $hasAny = true;
                        break;
                    }
                }
                if (!$hasAny) {
                    $errors[$name] = ($field['label'] ? strip_tags($field['label']) : $name) . ' is required.';
                }
                continue;
            }

            $val = $payload[$name] ?? null;
            $empty = ($val === null || $val === '' || (is_array($val) && count($val) === 0));
            if ($empty) {
                $errors[$name] = ($field['label'] ? strip_tags($field['label']) : $name) . ' is required.';
            }
        }

        return $errors;
    }

    /**
     * Decode a form_structure blob. Some rows were saved double-encoded
     * (json_encode(json_encode($arr))), so the first decode yields a JSON
     * string that needs a second pass. Returns an array or [] if neither works.
     */
    private function decodeFormStructure($raw)
    {
        $decoded = json_decode($raw, true);
        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }
        return is_array($decoded) ? $decoded : [];
    }
}
