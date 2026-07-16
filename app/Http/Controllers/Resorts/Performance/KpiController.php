<?php

namespace App\Http\Controllers\Resorts\Performance;
use DB;
use Auth;
use Validator;
use Carbon\Carbon;
use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PerformanceKpiChild;
use App\Models\PerformanceKpiParent;
use App\Events\ResortNotificationEvent;

class KpiController extends Controller
{
    public $resort='';
    protected $underEmp_id=[];

    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
        if($this->resort->is_master_admin == 0 && isset($this->resort->GetEmployee)){
            $reporting_to = $this->resort->GetEmployee->id;
            $this->underEmp_id = Common::getSubordinates($reporting_to);
        }
    }

    private function getUserRank()
    {
        $emp = $this->resort->GetEmployee ?? null;
        return $emp ? ($emp->rank ?? null) : null;
    }

    /**
     * Create KPI page (GM only — rank 8).
     */
    public function create()
    {
        if ((int) $this->getUserRank() !== 8) {
            return abort(403, 'Only the GM can create a KPI.');
        }
        $page_title ="Create KPI";
        $kpi = null; // null => create mode
        return view('resorts.Performance.Kpi.create',compact('page_title','kpi'));
    }

    /**
     * Edit KPI page (GM only, only pending KPIs).
     */
    public function edit($id)
    {
        if ((int) $this->getUserRank() !== 8) {
            return abort(403, 'Only the GM can edit a KPI.');
        }
        $kpi = PerformanceKpiParent::where('resort_id', $this->resort->resort_id)->findOrFail($id);
        if ($kpi->status !== 'pending') {
            return redirect()->route('Performance.kpi.KpiList')
                ->with('error', 'A KPI can only be edited while it is pending (before HOD/XCOM response).');
        }
        $page_title = 'Edit KPI';
        return view('resorts.Performance.Kpi.create', compact('page_title', 'kpi'));
    }

    /**
     * Store KPI — GM creates it, then it goes to all HOD/XCOM for response.
     */
    public function PerformanceKpiStore(Request $request)
    {
        if ((int) $this->getUserRank() !== 8) {
            return response()->json(['success' => false, 'message' => 'Only the GM can create a KPI.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'goals'                => 'required|array|min:1',
            'goals.*.property_goal' => 'required|string|max:255',
            'goals.*.budget'       => 'nullable|numeric|min:1',
            'goals.*.weightage'    => 'required|numeric|min:1|max:100',
        ], [
            'goals.*.property_goal.required' => 'Property Goal is required.',
            'goals.*.weightage.required'     => 'Weightage is required.',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Validate total weightage = 100
        $totalWeightage = collect($request->goals)->sum('weightage');
        if ((float) $totalWeightage !== 100.0) {
            return response()->json([
                'success' => false,
                'errors' => ['weightage' => ['Total weightage must be exactly 100%. Current total: '.$totalWeightage.'%']],
            ], 422);
        }

        DB::beginTransaction();
        try {
            $lastKpi = null;
            $createdKpis = [];
            // Capture the resort's active currency at save time so display can
            // round-trip the entered value without USD↔MVR distortion.
            $entryCurrency = Common::getDisplayCurrency();
            foreach ($request->goals as $goal) {
                $kpi = PerformanceKpiParent::create([
                    'resort_id'              => $this->resort->resort_id,
                    'property_goal'          => $goal['property_goal'],
                    'PropertyGoalbudget'     => $goal['budget'] ?? null,
                    'budget_currency'        => $entryCurrency,
                    'PropertyGoalweightage'  => $goal['weightage'],
                    'status'                 => 'pending',
                ]);
                $createdKpis[] = $kpi;
                $lastKpi = $kpi;
            }

            // Create single Actual child row linked to the first KPI
            if ((!empty($request->actual_budget) || !empty($request->actual_weightage)) && $createdKpis) {
                PerformanceKpiChild::create([
                    'kpi_parents_id' => $createdKpis[0]->id,
                    'budget'         => $request->actual_budget,
                    'weightage'      => $request->actual_weightage,
                ]);
            }

            DB::commit();

            // Notify outside transaction; notifyHodXcom wraps each recipient individually.
            if ($lastKpi) {
                $count = count($request->goals);
                $title = $count > 1 ? $count.' New KPIs Created' : 'New KPI Created';
                $msg   = $count > 1
                    ? $count.' new KPIs have been created by GM. Please respond.'
                    : 'A new KPI "'.$lastKpi->property_goal.'" has been created by GM. Please respond.';
                $this->notifyHodXcom($lastKpi, $title, $msg);
            }

            return response()->json([
                'success' => true,
                'message' => 'KPI created and sent to HOD/XCOM for response.',
                'route'   => route('Performance.kpi.KpiList'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("KpiStore File: " . $e->getFile());
            \Log::emergency("KpiStore Line: " . $e->getLine());
            \Log::emergency("KpiStore Message: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create KPI'], 500);
        }
    }

    /**
     * Update a pending KPI (GM only).
     */
    public function updateKpi(Request $request, $id)
    {
        if ((int) $this->getUserRank() !== 8) {
            return response()->json(['success' => false, 'message' => 'Only the GM can edit a KPI.'], 403);
        }

        $kpi = PerformanceKpiParent::where('resort_id', $this->resort->resort_id)->findOrFail($id);
        if ($kpi->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Only pending KPIs can be edited.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'property_goal'         => 'required|string|max:255',
            'PropertyGoalbudget'    => 'nullable|numeric|min:1',
            'PropertyGoalweightage' => 'required|numeric|min:1|max:100',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $kpi->update([
            'property_goal'         => $request->property_goal,
            'PropertyGoalbudget'    => $request->PropertyGoalbudget,
            // Re-stamp the currency on edit so the new value is interpreted in
            // whatever the resort is set to right now.
            'budget_currency'       => Common::getDisplayCurrency(),
            'PropertyGoalweightage' => $request->PropertyGoalweightage,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'KPI updated successfully.',
            'route'   => route('Performance.kpi.KpiList'),
        ]);
    }

    /**
     * Delete a KPI (GM only). Cascades children + related records.
     */
    public function destroyKpi($id)
    {
        if ((int) $this->getUserRank() !== 8) {
            return response()->json(['success' => false, 'message' => 'Only the GM can delete a KPI.'], 403);
        }

        $kpi = PerformanceKpiParent::where('resort_id', $this->resort->resort_id)->find($id);
        if (!$kpi) {
            return response()->json(['success' => false, 'message' => 'KPI not found.'], 404);
        }

        DB::beginTransaction();
        try {
            PerformanceKpiChild::where('kpi_parents_id', $kpi->id)->delete();
            $kpi->delete();
            DB::commit();
            return response()->json(['success' => true, 'message' => 'KPI deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('destroyKpi: '.$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete KPI.'], 500);
        }
    }

    /**
     * KPI list page — all roles can view; action buttons differ by role.
     */
    public function KpiList(Request $request)
    {
        $Year = $request->Year;
        if ($Year == "All") $Year = Date("Y");
        $searchTerm = $request->searchTerm;

        $kpiList = PerformanceKpiParent::with('childrenKpi', 'responder.resortAdmin')
            ->where('resort_id', $this->resort->resort_id)
            ->when($Year, fn($q) => $q->whereYear('created_at', $Year))
            ->when($searchTerm, fn($q) => $q->where('property_goal', 'LIKE', "%{$searchTerm}%"))
            ->get()
            ->map(function ($k) {
                $k->actual_budget_sum    = (float) $k->childrenKpi->sum('budget');
                $k->actual_weightage_sum = (float) $k->childrenKpi->sum('weightage');
                return $k;
            });

        if ($request->ajax()) {
            $rank = $this->getUserRank();
            $authEmpId = optional($this->resort->GetEmployee)->id;

            return datatables()->of($kpiList)
                ->editColumn('PropertyGoals', fn($row) => ucfirst($row->property_goal))
                // Per-row currency: legacy rows (NULL budget_currency) follow the
                // payroll convention of "stored in USD" so they convert sensibly
                // when the resort switches to MVR.
                ->editColumn('budget', fn($row) => Common::formatCurrency(
                    $row->PropertyGoalbudget,
                    $row->budget_currency ?: 'USD',
                    0
                ))
                ->addColumn('Actual', fn($row) => $row->actual_budget_sum > 0
                    ? Common::formatCurrency($row->actual_budget_sum, $row->budget_currency ?: 'USD', 0)
                    : '-')
                ->addColumn('Value', fn($row) => $row->PropertyGoalweightage !== null && $row->PropertyGoalweightage !== '' ? $row->PropertyGoalweightage.'%' : '-')
                ->addColumn('Result', fn($row) => $row->actual_weightage_sum > 0 ? $row->actual_weightage_sum.'%' : '-')
                ->addColumn('status_badge', function ($row) {
                    $map = [
                        'pending'   => 'badge-themeWarning',
                        'responded' => 'badge-themeSkyblue',
                        'approved'  => 'badge-themeSuccess',
                        'rejected'  => 'badge-themeDanger',
                    ];
                    $cls = $map[$row->status] ?? 'badge-themeWarning';
                    return '<span class="badge '.$cls.'">'.ucfirst($row->status).'</span>';
                })
                ->addColumn('action', function ($row) use ($rank, $authEmpId) {
                    $html = '';
                    // HOD (2) or XCOM (1) can respond if status=pending and no one has responded yet
                    if (in_array($rank, [1, 2]) && $row->status === 'pending') {
                        $html .= '<a href="'.route('Performance.kpi.respond', $row->id).'" class="btn btn-theme btn-sm me-1">Respond</a>';
                    }
                    // HOD (2) or XCOM (1) can revise & resubmit after GM rejection
                    if (in_array($rank, [1, 2]) && $row->status === 'rejected') {
                        $html .= '<a href="'.route('Performance.kpi.respond', $row->id).'" class="btn btn-themeSkyblue btn-sm me-1">Edit Response</a>';
                    }
                    // GM (8) can approve/reject if status=responded
                    if ($rank == 8 && $row->status === 'responded') {
                        $html .= '<button class="btn btn-theme btn-sm me-1 gm-approve-btn" data-id="'.$row->id.'">Approve</button>';
                        $html .= '<button class="btn btn-danger btn-sm gm-reject-btn" data-id="'.$row->id.'">Reject</button>';
                    }
                    // View for everyone on non-pending KPIs
                    if ($row->status !== 'pending') {
                        $html .= ' <a href="'.route('Performance.kpi.view', $row->id).'" class="btn btn-themeBlue btn-sm">View</a>';
                    }
                    // GM edit (only on pending KPIs) + delete (always)
                    if ($rank == 8) {
                        if ($row->status === 'pending') {
                            $html .= ' <a href="'.route('Performance.kpi.edit', $row->id).'" class="btn btn-themeSkyblue btn-sm">Edit</a>';
                        }
                        $html .= ' <button class="btn btn-danger btn-sm kpi-delete-btn" data-id="'.$row->id.'">Delete</button>';
                    }
                    return $html ?: '-';
                })
                ->addColumn('created_at', fn($row) => $row->created_at)
                ->rawColumns(['PropertyGoals','budget','Actual','Result','Value','status_badge','action'])
                ->make(true);
        }

        $page_title = "KPI List";
        $userRank = $this->getUserRank();
        return view('resorts.Performance.Kpi.index', compact('page_title', 'userRank'));
    }

    /**
     * Response page — HOD/XCOM fills individual goals + budget + weightage for a KPI.
     * Also used to revise a response that was rejected by the GM.
     */
    public function respond($id)
    {
        $kpi = PerformanceKpiParent::with('childrenKpi', 'creator.getEmployee.position')
            ->where('resort_id', $this->resort->resort_id)
            ->findOrFail($id);

        if (!in_array((int) $this->getUserRank(), [1, 2])) {
            return redirect()->route('Performance.kpi.KpiList')
                ->with('error', 'Only HOD/XCOM can respond to a KPI.');
        }

        if (!in_array($kpi->status, ['pending', 'rejected'])) {
            return redirect()->route('Performance.kpi.KpiList')
                ->with('error', 'This KPI cannot be edited at its current stage.');
        }

        $page_title = $kpi->status === 'rejected' ? 'Edit KPI Response' : 'Response KPI';
        return view('resorts.Performance.Kpi.respond', compact('page_title', 'kpi'));
    }

    /**
     * Store KPI response from HOD/XCOM.
     */
    public function storeResponse(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'responses'                  => 'required|array|min:1',
            'responses.*.individual_goal' => 'required|string|max:500',
            'responses.*.budget'         => 'nullable|numeric|min:1',
            'responses.*.weightage'      => 'required|numeric|min:1',
        ], [
            'responses.*.individual_goal.required' => 'Individual Goal is required.',
            'responses.*.weightage.required'       => 'Weightage is required.',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $kpi = PerformanceKpiParent::where('resort_id', $this->resort->resort_id)->findOrFail($id);
        if (!in_array((int) $this->getUserRank(), [1, 2])) {
            return response()->json(['success' => false, 'message' => 'Only HOD/XCOM can respond to a KPI.'], 403);
        }
        if (!in_array($kpi->status, ['pending', 'rejected'])) {
            return response()->json(['success' => false, 'message' => 'This KPI cannot be updated.'], 422);
        }

        $wasRejected = $kpi->status === 'rejected';
        $authEmpId = optional($this->resort->GetEmployee)->id;

        // Combine multiple response entries
        $goals = collect($request->responses)->pluck('individual_goal')->implode(' | ');
        $totalBudget = collect($request->responses)->sum('budget');
        $totalWeightage = collect($request->responses)->sum('weightage');

        $entryCurrency = Common::getDisplayCurrency();
        $kpi->update([
            'individual_goal'           => $goals,
            'response_budget'           => $totalBudget,
            'response_budget_currency'  => $entryCurrency,
            'response_weightage'        => $totalWeightage,
            'response_entries'          => $request->responses,
            'responded_by'              => $authEmpId,
            'responded_at'              => now(),
            'status'                    => 'responded',
            // Clear previous GM decision so the revised response gets a fresh review.
            'gm_action'    => null,
            'gm_action_at' => null,
            'gm_remarks'   => null,
        ]);

        // Also update child "Actual" rows if sent
        if ($request->child_budget && is_array($request->child_budget)) {
            foreach ($kpi->childrenKpi as $idx => $child) {
                $child->update([
                    'budget'          => $request->child_budget[$idx] ?? $child->budget,
                    'budget_currency' => $entryCurrency,
                    'weightage'       => $request->child_weightage[$idx] ?? $child->weightage,
                ]);
            }
        }

        // Notify GM (rank 8) — wrapped so notification failure doesn't break response
        try {
            $notifTitle = $wasRejected ? 'KPI Response Re-submitted' : 'KPI Response Received';
            $notifMsg   = $wasRejected
                ? 'HOD/XCOM has revised the response to KPI "'.$kpi->property_goal.'" after rejection. Please review again.'
                : 'HOD/XCOM has responded to KPI "'.$kpi->property_goal.'". Review and approve/reject.';
            $this->notifyGm($kpi, $notifTitle, $notifMsg);
        } catch (\Exception $ne) {
            \Log::warning("KPI response notification failed: " . $ne->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Response submitted successfully.',
            'route'   => route('Performance.kpi.KpiList'),
        ]);
    }

    /**
     * GM approves a responded KPI.
     */
    public function approve(Request $request, $id)
    {
        $kpi = PerformanceKpiParent::where('resort_id', $this->resort->resort_id)->findOrFail($id);
        if ($kpi->status !== 'responded') {
            return response()->json(['success' => false, 'message' => 'KPI must be in responded state'], 422);
        }

        $kpi->update([
            'gm_action'    => 'approved',
            'gm_action_at' => now(),
            'gm_remarks'   => $request->remarks,
            'status'       => 'approved',
        ]);

        // Notify responder
        try {
            if ($kpi->responded_by) {
                $title   = 'KPI Approved';
                $msg     = 'Your response to KPI "'.$kpi->property_goal.'" has been approved by GM.';
                $module  = 'Performance';
                event(new ResortNotificationEvent(Common::nofitication($this->resort->resort_id, 10, $title, $msg, $kpi->id, $kpi->responded_by, $module)));
                // skipDbInsert=true — nofitication() above already wrote the row.
                Common::sendMobileNotification($this->resort->resort_id, 2, null, null, $title, $msg, $module, [$kpi->responded_by], $kpi->id, true, 'kpi-approved');
            }
        } catch (\Exception $ne) {
            \Log::warning("KPI approve notification failed: " . $ne->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'KPI approved']);
    }

    /**
     * GM rejects a responded KPI.
     */
    public function reject(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'remarks' => 'required|string|max:1000',
        ], ['remarks.required' => 'Please provide a reason for rejection.']);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $kpi = PerformanceKpiParent::where('resort_id', $this->resort->resort_id)->findOrFail($id);
        if ($kpi->status !== 'responded') {
            return response()->json(['success' => false, 'message' => 'KPI must be in responded state'], 422);
        }

        $kpi->update([
            'gm_action'    => 'rejected',
            'gm_action_at' => now(),
            'gm_remarks'   => $request->remarks,
            'status'       => 'rejected',
        ]);

        try {
            if ($kpi->responded_by) {
                $title   = 'KPI Rejected';
                $msg     = 'Your response to KPI "'.$kpi->property_goal.'" has been rejected by GM. Reason: '.$request->remarks;
                $module  = 'Performance';
                event(new ResortNotificationEvent(Common::nofitication($this->resort->resort_id, 10, $title, $msg, $kpi->id, $kpi->responded_by, $module)));
                Common::sendMobileNotification($this->resort->resort_id, 2, null, null, $title, $msg, $module, [$kpi->responded_by], $kpi->id, true, 'kpi-rejected');
            }
        } catch (\Exception $ne) {
            \Log::warning("KPI reject notification failed: " . $ne->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'KPI rejected']);
    }

    /**
     * View KPI details (AJAX).
     */
    public function show($id)
    {
        $kpi = PerformanceKpiParent::with('childrenKpi', 'responder.resortAdmin', 'responder.position', 'creator')
            ->where('resort_id', $this->resort->resort_id)
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $kpi]);
    }

    /**
     * View KPI details page.
     */
    public function viewPage($id)
    {
        $kpi = PerformanceKpiParent::with([
                'childrenKpi.creator.getEmployee.position',
                'responder.resortAdmin',
                'responder.position',
                'creator.getEmployee.position',
            ])
            ->where('resort_id', $this->resort->resort_id)
            ->findOrFail($id);

        $page_title = 'KPI Details';
        $canAddActual = in_array($this->getUserRank(), [1, 2, 8]); // XCOM, HOD, GM
        // HOD/XCOM can re-open the response form when the GM has rejected.
        $canEditResponse = in_array((int) $this->getUserRank(), [1, 2]) && $kpi->status === 'rejected';

        // Build Individual Goal dropdown options from response_entries JSON
        // or fall back to splitting the joined individual_goal string
        $goalOptions = [];
        if (is_array($kpi->response_entries) && count($kpi->response_entries) > 0) {
            $goalOptions = collect($kpi->response_entries)
                ->pluck('individual_goal')
                ->filter()
                ->values()
                ->all();
        } elseif ($kpi->individual_goal) {
            $goalOptions = array_values(array_filter(array_map('trim', explode('|', $kpi->individual_goal))));
        }

        $months = ['January','February','March','April','May','June','July','August','September','October','November','December'];

        return view('resorts.Performance.Kpi.view', compact('page_title', 'kpi', 'canAddActual', 'canEditResponse', 'goalOptions', 'months'));
    }

    /**
     * Store one or more Actual entries for a KPI.
     * Only GM (8), XCOM (1), HOD (2) may add.
     */
    public function storeActual(Request $request, $id)
    {
        if (!in_array($this->getUserRank(), [1, 2, 8])) {
            return response()->json(['success' => false, 'message' => 'Not authorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'entries'                      => 'required|array|min:1',
            'entries.*.individual_goal'    => 'required|string|max:500',
            'entries.*.month'              => 'required|string|max:20',
            'entries.*.budget'             => 'nullable|numeric|min:1',
            'entries.*.weightage'          => 'required|numeric|min:1',
            'entries.*.remarks'            => 'nullable|string|max:1000',
        ], [
            'entries.*.individual_goal.required' => 'Please select an Individual Goal.',
            'entries.*.month.required'           => 'Please select a month.',
            'entries.*.weightage.required'       => 'Weightage is required.',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $kpi = PerformanceKpiParent::where('resort_id', $this->resort->resort_id)->findOrFail($id);

        // Block adding actual entries when the KPI response has been rejected by the GM —
        // it must be re-submitted and approved before new actuals can be logged.
        if ($kpi->status === 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot add actual entries while this KPI is rejected. Please revise and re-submit the response first.',
            ], 422);
        }

        $entryCurrency = Common::getDisplayCurrency();
        foreach ($request->entries as $entry) {
            PerformanceKpiChild::create([
                'kpi_parents_id'  => $kpi->id,
                'individual_goal' => $entry['individual_goal'],
                'month'           => $entry['month'],
                'budget'          => $entry['budget'] ?? null,
                'budget_currency' => $entryCurrency,
                'weightage'       => $entry['weightage'],
                'remarks'         => $entry['remarks'] ?? null,
                'created_by'      => $this->resort->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Actual entries added successfully.',
            'route'   => route('Performance.kpi.view', $kpi->id),
        ]);
    }

    /**
     * Delete an Actual entry. Only GM/XCOM/HOD.
     */
    public function destroyActual($id)
    {
        if (!in_array($this->getUserRank(), [1, 2, 8])) {
            return response()->json(['success' => false, 'message' => 'Not authorized'], 403);
        }

        $child = PerformanceKpiChild::whereHas('parentKpi', function ($q) {
            $q->where('resort_id', $this->resort->resort_id);
        })->find($id);

        if (!$child) {
            return response()->json(['success' => false, 'message' => 'Entry not found'], 404);
        }

        $child->delete();
        return response()->json(['success' => true, 'message' => 'Entry removed.']);
    }

    /**
     * KPI Config page — shows all KPIs with editable Poor/Fair/Good/Superb thresholds.
     * Restricted to HR HOD only.
     */
    public function kpiConfig()
    {
        if (!Common::isHRHOD()) {
            return abort(403, 'Only the HR HOD can access KPI Config.');
        }
        $kpis = PerformanceKpiParent::where('resort_id', $this->resort->resort_id)
            ->orderByDesc('id')
            ->get();

        $page_title = 'KPI Configuration';
        return view('resorts.Performance.Kpi.config', compact('page_title', 'kpis'));
    }

    /**
     * Update a KPI's Poor/Fair/Good/Superb ranges + points. HR HOD only.
     */
    public function updateKpiConfig(Request $request, $id)
    {
        if (!Common::isHRHOD()) {
            return response()->json(['success' => false, 'message' => 'Only the HR HOD can update KPI config.'], 403);
        }
        $validator = Validator::make($request->all(), [
            'poor_range'   => 'nullable|string|max:100',
            'fair_range'   => 'nullable|string|max:100',
            'good_range'   => 'nullable|string|max:100',
            'superb_range' => 'nullable|string|max:100',
            'poor'         => 'nullable|numeric',
            'fair'         => 'nullable|numeric',
            'good'         => 'nullable|numeric',
            'superb'       => 'nullable|numeric',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $kpi = PerformanceKpiParent::where('resort_id', $this->resort->resort_id)->findOrFail($id);
        $kpi->update($request->only([
            'poor_range', 'fair_range', 'good_range', 'superb_range',
            'poor', 'fair', 'good', 'superb',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'KPI config updated successfully.',
            'data'    => $kpi->only([
                'id', 'property_goal', 'PropertyGoalbudget', 'PropertyGoalweightage',
                'poor_range', 'fair_range', 'good_range', 'superb_range',
                'poor', 'fair', 'good', 'superb',
            ]),
        ]);
    }

    /**
     * Send notification to all HOD and XCOM in this resort.
     */
    private function notifyHodXcom($kpi, $title, $msg)
    {
        $resort_id = $this->resort->resort_id;
        $module    = 'Performance';

        // rank 1 = XCOM, rank 2 = HOD — resort-wide (all departments)
        $employees = Employee::where('resort_id', $resort_id)
            ->where('status', 'Active')
            ->whereIn('rank', [1, 2])
            ->pluck('id')
            ->toArray();

        // Fire each notification independently so one failure doesn't kill the rest
        foreach ($employees as $empId) {
            try {
                event(new ResortNotificationEvent(
                    Common::nofitication($resort_id, 10, $title, $msg, $kpi->id, $empId, $module)
                ));
            } catch (\Exception $e) {
                \Log::warning("KPI notify emp {$empId} failed: ".$e->getMessage());
            }
        }

        if (!empty($employees)) {
            try {
                // skipDbInsert=true — nofitication() in the loop above already wrote rows.
                Common::sendMobileNotification($resort_id, 2, null, null, $title, $msg, $module, $employees, $kpi->id, true, 'kpi-notify-employee');
            } catch (\Exception $e) {
                \Log::warning('KPI mobile push failed: '.$e->getMessage());
            }
        }
    }

    /**
     * Send notification to GM in this resort.
     */
    private function notifyGm($kpi, $title, $msg)
    {
        $resort_id = $this->resort->resort_id;
        $module    = 'Performance';

        $gms = Employee::where('resort_id', $resort_id)
            ->where('status', 'Active')
            ->where('rank', 8)
            ->pluck('id')
            ->toArray();

        foreach ($gms as $gmId) {
            try {
                event(new ResortNotificationEvent(
                    Common::nofitication($resort_id, 10, $title, $msg, $kpi->id, $gmId, $module)
                ));
            } catch (\Exception $e) {
                \Log::warning("KPI notify GM {$gmId} failed: ".$e->getMessage());
            }
        }

        if (!empty($gms)) {
            try {
                Common::sendMobileNotification($resort_id, 2, null, null, $title, $msg, $module, $gms, $kpi->id, true, 'kpi-notify-gm');
            } catch (\Exception $e) {
                \Log::warning('KPI GM mobile push failed: '.$e->getMessage());
            }
        }
    }
}
