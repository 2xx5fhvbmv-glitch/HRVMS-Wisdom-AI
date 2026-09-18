<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Helpers\Common;
use App\Models\ResortDivision;
use App\Models\ResortPosition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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

        $validator = Validator::make($request->all(), [
            'employee_category' => 'required|in:Casual,Intern',
            'dept_id' => 'required|integer',
            'section_id' => 'nullable|integer',
            'position_title' => 'required|string|max:191',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {
            $position = ResortPosition::create([
                'resort_id' => $this->resort->resort_id,
                'dept_id' => $request->dept_id,
                'section_id' => $request->section_id ?: null,
                'position_title' => trim($request->position_title),
                'employee_category' => $request->employee_category,
                'status' => 'active',
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
