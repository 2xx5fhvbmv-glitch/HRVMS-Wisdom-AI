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

        $reviews = PerformaChildCycle::join('performance_cycles as pc', 'pc.id', '=', 'performa_child_cycles.Parent_cycle_id')
            ->where('pc.resort_id', $this->resort->resort_id)
            ->where(function ($q) use ($empId, $encodedId) {
                $q->where('performa_child_cycles.Emp_main_id', $empId)
                  ->orWhere('performa_child_cycles.Emp_main_id', $encodedId);
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
            $actualId = is_numeric($r->Emp_main_id) ? $r->Emp_main_id : base64_decode($r->Emp_main_id);
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

        // Verify current user is the participant
        $currentEmpId = $this->resort->GetEmployee->id ?? null;
        $participantId = is_numeric($childCycle->Emp_main_id) ? $childCycle->Emp_main_id : base64_decode($childCycle->Emp_main_id);
        if ($currentEmpId != $participantId) {
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
        $existingData = $childCycle->self_review_data ? json_decode($childCycle->self_review_data, true) : [];
        $page_title = "Self Review - " . $childCycle->Cycle_Name;

        return view('resorts.Performance.Review.self-review-form', compact('page_title', 'childCycle', 'template', 'existingData', 'windowStatus'));
    }

    public function submitSelfReview(Request $request, $id)
    {
        $id = base64_decode($id);
        $childCycle = PerformaChildCycle::join('performance_cycles as pc', 'pc.id', '=', 'performa_child_cycles.Parent_cycle_id')
            ->where('performa_child_cycles.id', $id)
            ->first(['performa_child_cycles.*', 'pc.Self_Activity_Start_Date', 'pc.Self_Activity_End_Date']);

        if (!$childCycle) {
            return response()->json(['success' => false, 'message' => 'Review not found'], 404);
        }

        $currentEmpId = $this->resort->GetEmployee->id ?? null;
        $participantId = is_numeric($childCycle->Emp_main_id) ? $childCycle->Emp_main_id : base64_decode($childCycle->Emp_main_id);
        if ($currentEmpId != $participantId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Enforce self review date window
        $windowStatus = $this->getSelfReviewWindowStatus($childCycle);
        if (!$windowStatus['open']) {
            return response()->json(['success' => false, 'message' => $windowStatus['message']], 422);
        }

        $realChild = PerformaChildCycle::find($id);
        $realChild->self_review_data = json_encode($request->except(['_token']));
        $realChild->self_review_status = 'completed';
        $realChild->Self_review_date = now()->format('Y-m-d');
        $realChild->save();

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
        if ($currentEmpId != $childCycle->Manager_id) {
            abort(403, 'You are not the assigned manager for this review');
        }

        if ($childCycle->self_review_status !== 'completed') {
            abort(403, 'Employee has not completed self review yet');
        }

        $windowStatus = $this->getManagerReviewWindowStatus($childCycle);

        $actualId = is_numeric($childCycle->Emp_main_id) ? $childCycle->Emp_main_id : base64_decode($childCycle->Emp_main_id);
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
            ->first(['performa_child_cycles.*', 'pc.Manager_Activity_Start_Date', 'pc.Manager_Activity_End_Date']);

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

        $realChild = PerformaChildCycle::find($id);
        $realChild->manager_review_data = json_encode($request->except(['_token']));
        $realChild->manager_review_status = 'completed';
        $realChild->Manager_review_date = now()->format('Y-m-d');
        $realChild->save();

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

        $actualId = is_numeric($childCycle->Emp_main_id) ? $childCycle->Emp_main_id : base64_decode($childCycle->Emp_main_id);
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
     * Helper: Get template structure based on template_id format
     * Template IDs from CycleFetchTemplate use prefixes: 'ninty_X', 'prof_X', or numeric
     */
    private function getTemplate($templateId)
    {
        if (!$templateId) return null;

        if (strpos($templateId, 'ninty_') === 0) {
            $realId = substr($templateId, 6);
            $form = NintyDayPeformanceForm::find($realId);
            return $form ? ['name' => $form->FormName, 'structure' => json_decode($form->form_structure, true), 'type' => '90 Day'] : null;
        }
        if (strpos($templateId, 'prof_') === 0) {
            $realId = substr($templateId, 5);
            if (class_exists(\App\Models\Professionalform::class)) {
                $form = \App\Models\Professionalform::find($realId);
                return $form ? ['name' => $form->FormName, 'structure' => json_decode($form->form_structure, true), 'type' => 'Professional'] : null;
            }
        }
        $form = PerformanceTemplateForm::find($templateId);
        return $form ? ['name' => $form->FormName, 'structure' => json_decode($form->form_structure, true), 'type' => 'Template'] : null;
    }
}
