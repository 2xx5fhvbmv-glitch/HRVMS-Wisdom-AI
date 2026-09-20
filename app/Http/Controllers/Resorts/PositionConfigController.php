<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Helpers\Common;
use App\Models\ResortDivision;
use App\Models\ResortPosition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * "Position Configuration — Casuals & Interns" — HR creates positions that
 * only exist for the Casual or Intern manning categories (e.g. "Gardener",
 * "Marketing Intern"), scoped to a division/department/section. These are
 * plain resort_positions rows with employee_category set (see the
 * 2026_09_19_090000 migration) — no rank/grade/code applies to them.
 */
class PositionConfigController extends Controller
{
    public function __construct()
    {
        $this->resort = auth()->guard('resort-admin')->user();
    }

    public function index()
    {
        if (Common::checkRouteWisePermission('resort.positionconfig.index', config('settings.resort_permissions.view')) == false) {
            return abort(403, 'Unauthorized access');
        }

        $page_title = 'Position Configuration — Casuals & Interns';
        $resort_id = $this->resort->resort_id;

        $resort_divisions = ResortDivision::where('resort_id', $resort_id)->where('status', 'active')->get();

        $positions = ResortPosition::with(['department', 'section'])
            ->where('resort_id', $resort_id)
            ->whereIn('employee_category', ['Casual', 'Intern'])
            ->orderBy('employee_category')
            ->orderBy('position_title')
            ->get();

        return view('resorts.workforce_planning.position_config', compact('page_title', 'resort_divisions', 'positions'));
    }

    public function store(Request $request)
    {
        if (Common::checkRouteWisePermission('resort.positionconfig.index', config('settings.resort_permissions.create')) == false) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        $resortId = $this->resort->resort_id;

        // Non-HR/GM HOD/XCOM only ever manages their own department, same
        // rule every other list/dashboard in this app applies.
        $scopedDeptIds = Common::getScopedDepartmentIds();
        if (is_array($scopedDeptIds) && !in_array((int) $request->dept_id, $scopedDeptIds, true)) {
            return response()->json(['success' => false, 'message' => 'You do not have access to this department.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'employee_category' => 'required|in:Casual,Intern',
            'dept_id' => ['required', 'integer', Rule::exists('resort_departments', 'id')->where('resort_id', $resortId)],
            'section_id' => ['nullable', 'integer', Rule::exists('resort_sections', 'id')->where('dept_id', $request->dept_id)],
            'position_title' => [
                'required', 'string', 'max:191',
                Rule::unique('resort_positions', 'position_title')
                    ->where('resort_id', $resortId)
                    ->where('dept_id', $request->dept_id)
                    ->where('employee_category', $request->employee_category),
            ],
        ], [
            'dept_id.exists' => 'That department does not belong to your resort.',
            'section_id.exists' => 'That section does not belong to the chosen department.',
            'position_title.unique' => 'This position already exists for this department and category.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            $position = ResortPosition::create([
                'resort_id' => $resortId,
                'dept_id' => $request->dept_id,
                'section_id' => $request->section_id ?: null,
                'position_title' => trim($request->position_title),
                'employee_category' => $request->employee_category,
                'status' => 'active',
                // WP1 (D1, supersedes the earlier Rank=6 decision) —
                // Casual/Intern never carry a Permanent rank (1-12) and
                // never reach a benefit grid. 0 is the sentinel:
                // resolveEmpGrade() short-circuits on it, and it matches no
                // Position_Rank config key, so anywhere a rank badge is
                // shown it falls through to Common::rankLabel()'s
                // "Casual"/"Intern" text instead of a Permanent rank name.
                'Rank' => 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => $request->employee_category . ' position created successfully.',
                'position' => $position->load(['department', 'section']),
            ]);
        } catch (\Exception $e) {
            \Log::emergency('File: ' . $e->getFile());
            \Log::emergency('Line: ' . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }
}
