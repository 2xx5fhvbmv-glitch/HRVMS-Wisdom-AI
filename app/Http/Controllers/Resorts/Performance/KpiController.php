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
     * Create KPI page (GM only)
     */
    public function create()
    {
        if(Common::checkRouteWisePermission('Performance.kpi.KpiList',config('settings.resort_permissions.create')) == false){
            return abort(403, 'Unauthorized access');
        }
        $page_title ="Create KPI";
        return view('resorts.Performance.Kpi.create',compact('page_title'));
    }

    /**
     * Store KPI — GM creates it, then it goes to all HOD/XCOM for response.
     */
    public function PerformanceKpiStore(Request $request)
    {
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
            foreach ($request->goals as $goal) {
                $kpi = PerformanceKpiParent::create([
                    'resort_id'              => $this->resort->resort_id,
                    'property_goal'          => $goal['property_goal'],
                    'PropertyGoalbudget'     => $goal['budget'] ?? null,
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

            // Notify outside transaction so notification failure doesn't rollback KPI creation
            try {
                if ($lastKpi) {
                    $count = count($request->goals);
                    $title = $count > 1 ? $count.' New KPIs Created' : 'New KPI Created';
                    $msg   = $count > 1
                        ? $count.' new KPIs have been created by GM. Please respond.'
                        : 'A new KPI "'.$lastKpi->property_goal.'" has been created by GM. Please respond.';
                    $this->notifyHodXcom($lastKpi, $title, $msg);
                }
            } catch (\Exception $ne) {
                \Log::warning("KPI notification failed: " . $ne->getMessage());
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
            ->get();

        if ($request->ajax()) {
            $rank = $this->getUserRank();
            $authEmpId = optional($this->resort->GetEmployee)->id;

            return datatables()->of($kpiList)
                ->editColumn('PropertyGoals', fn($row) => ucfirst($row->property_goal))
                ->editColumn('budget', fn($row) => $row->PropertyGoalbudget !== null && $row->PropertyGoalbudget !== '' ? number_format((float)$row->PropertyGoalbudget) : '-')
                ->addColumn('Actual', fn($row) => $row->response_budget !== null && $row->response_budget !== '' ? number_format((float)$row->response_budget) : '-')
                ->addColumn('Value', fn($row) => $row->PropertyGoalweightage !== null && $row->PropertyGoalweightage !== '' ? $row->PropertyGoalweightage.'%' : '-')
                ->addColumn('Result', fn($row) => $row->response_weightage !== null && $row->response_weightage !== '' ? $row->response_weightage.'%' : '-')
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
                    // GM (8) can approve/reject if status=responded
                    if ($rank == 8 && $row->status === 'responded') {
                        $html .= '<button class="btn btn-theme btn-sm me-1 gm-approve-btn" data-id="'.$row->id.'">Approve</button>';
                        $html .= '<button class="btn btn-danger btn-sm gm-reject-btn" data-id="'.$row->id.'">Reject</button>';
                    }
                    // View for everyone
                    if ($row->status !== 'pending') {
                        $html .= ' <a href="'.route('Performance.kpi.view', $row->id).'" class="btn btn-themeBlue btn-sm">View</a>';
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
     */
    public function respond($id)
    {
        $kpi = PerformanceKpiParent::with('childrenKpi', 'creator.getEmployee.position')
            ->where('resort_id', $this->resort->resort_id)
            ->findOrFail($id);

        if ($kpi->status !== 'pending') {
            return redirect()->route('Performance.kpi.KpiList')->with('error', 'This KPI has already been responded to.');
        }

        $page_title = 'Response KPI';
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
        if ($kpi->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Already responded'], 422);
        }

        $authEmpId = optional($this->resort->GetEmployee)->id;

        // Combine multiple response entries
        $goals = collect($request->responses)->pluck('individual_goal')->implode(' | ');
        $totalBudget = collect($request->responses)->sum('budget');
        $totalWeightage = collect($request->responses)->sum('weightage');

        $kpi->update([
            'individual_goal'    => $goals,
            'response_budget'    => $totalBudget,
            'response_weightage' => $totalWeightage,
            'response_entries'   => $request->responses,
            'responded_by'       => $authEmpId,
            'responded_at'       => now(),
            'status'             => 'responded',
        ]);

        // Also update child "Actual" rows if sent
        if ($request->child_budget && is_array($request->child_budget)) {
            foreach ($kpi->childrenKpi as $idx => $child) {
                $child->update([
                    'budget'    => $request->child_budget[$idx] ?? $child->budget,
                    'weightage' => $request->child_weightage[$idx] ?? $child->weightage,
                ]);
            }
        }

        // Notify GM (rank 8) — wrapped so notification failure doesn't break response
        try {
            $this->notifyGm($kpi, 'KPI Response Received', 'HOD/XCOM has responded to KPI "'.$kpi->property_goal.'". Review and approve/reject.');
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
                Common::sendMobileNotification($this->resort->resort_id, 2, null, null, $title, $msg, $module, [$kpi->responded_by], $kpi->id);
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
                Common::sendMobileNotification($this->resort->resort_id, 2, null, null, $title, $msg, $module, [$kpi->responded_by], $kpi->id);
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

        return view('resorts.Performance.Kpi.view', compact('page_title', 'kpi', 'canAddActual', 'goalOptions', 'months'));
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
        ], [
            'entries.*.individual_goal.required' => 'Please select an Individual Goal.',
            'entries.*.month.required'           => 'Please select a month.',
            'entries.*.weightage.required'       => 'Weightage is required.',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $kpi = PerformanceKpiParent::where('resort_id', $this->resort->resort_id)->findOrFail($id);

        foreach ($request->entries as $entry) {
            PerformanceKpiChild::create([
                'kpi_parents_id'  => $kpi->id,
                'individual_goal' => $entry['individual_goal'],
                'month'           => $entry['month'],
                'budget'          => $entry['budget'] ?? null,
                'weightage'       => $entry['weightage'],
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
     * Send notification to all HOD and XCOM in this resort.
     */
    private function notifyHodXcom($kpi, $title, $msg)
    {
        $resort_id = $this->resort->resort_id;
        $module    = 'Performance';

        // rank 1 = XCOM, rank 2 = HOD
        $employees = Employee::where('resort_id', $resort_id)
            ->where('status', 'Active')
            ->whereIn('rank', [1, 2])
            ->pluck('id')
            ->toArray();

        foreach ($employees as $empId) {
            event(new ResortNotificationEvent(
                Common::nofitication($resort_id, 10, $title, $msg, $kpi->id, $empId, $module)
            ));
        }

        if (!empty($employees)) {
            Common::sendMobileNotification($resort_id, 2, null, null, $title, $msg, $module, $employees, $kpi->id);
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
            event(new ResortNotificationEvent(
                Common::nofitication($resort_id, 10, $title, $msg, $kpi->id, $gmId, $module)
            ));
        }

        if (!empty($gms)) {
            Common::sendMobileNotification($resort_id, 2, null, null, $title, $msg, $module, $gms, $kpi->id);
        }
    }
}
