<?php

namespace App\Http\Controllers\Resorts\People;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\ResortBenefitGradeLevel;
use App\Models\ResortBenefitGradeLevelRank;
use App\Models\ResortBenifitGrid;
use App\Models\ResortBenifitGridChild;

class BenefitGradeLevelController extends Controller
{
    public $resort;

    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if (!$this->resort) return;
    }

    public function index()
    {
        $page_title = 'Benefit Grade Levels';
        $rankConfig = config('settings.Position_Rank');
        return view('resorts.people.config.benefit-grade-level', compact('page_title', 'rankConfig'));
    }

    public function list(Request $request)
    {
        if (!$request->ajax()) {
            return;
        }
        $resort_id = $this->resort->resort_id;
        $rankConfig = config('settings.Position_Rank');

        $levels = ResortBenefitGradeLevel::where('resort_id', $resort_id)->orderBy('name')->get();

        return datatables()->of($levels)
            ->addColumn('ranks', function ($level) use ($rankConfig) {
                $ranks = ResortBenefitGradeLevelRank::where('grade_level_id', $level->id)->pluck('rank');
                if ($ranks->isEmpty()) {
                    return '<span class="text-muted">Not mapped</span>';
                }
                return $ranks->map(function ($rank) use ($rankConfig) {
                    return e($rankConfig[$rank] ?? $rank);
                })->implode(', ');
            })
            ->addColumn('action', function ($level) {
                $id = base64_encode($level->id);
                return '
                    <div class="d-flex align-items-center">
                        <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-grade-id="' . e($id) . '">
                            <img src="' . asset('resorts_assets/images/edit.svg') . '" alt="Edit" class="img-fluid">
                        </a>
                        <a href="javascript:void(0)" class="btn btn-sm btn-outline-primary me-1 text-nowrap map-rank-btn" data-grade-id="' . e($id) . '">
                            Map Ranks
                        </a>
                        <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-grade-id="' . e($id) . '">
                            <img src="' . asset('resorts_assets/images/trash-red.svg') . '" alt="Delete" class="img-fluid">
                        </a>
                    </div>';
            })
            ->rawColumns(['ranks', 'action'])
            ->make(true);
    }

    private function validateName(Request $request, $ignoreId = null)
    {
        $resort_id = $this->resort->resort_id;
        return Validator::make($request->all(), [
            'name' => [
                'required',
                'max:100',
                Rule::unique('resort_benefit_grade_levels')->where(function ($query) use ($resort_id) {
                    return $query->where('resort_id', $resort_id);
                })->ignore($ignoreId),
            ],
        ], [
            'name.required' => 'The Grade Name field is required.',
            'name.unique' => 'This grade name already exists for this resort.',
            'name.max' => 'The maximum allowed length for the Grade Name is 100 characters.',
        ]);
    }

    public function store(Request $request)
    {
        $validator = $this->validateName($request);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $level = ResortBenefitGradeLevel::create([
            'resort_id' => $this->resort->resort_id,
            'name' => $request->name,
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Grade level added successfully.',
            'id' => $level->id,
            'name' => $level->name,
        ]);
    }

    public function inlineUpdate(Request $request, $id)
    {
        $mainId = (int) base64_decode($request->Main_id);
        $validator = $this->validateName($request, $mainId);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            ResortBenefitGradeLevel::where('resort_id', $this->resort->resort_id)
                ->where('id', $mainId)
                ->update(['name' => $request->name]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Grade level updated successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update grade level.'], 500);
        }
    }

    public function destroy($id)
    {
        $id = (int) base64_decode($id);
        $resort_id = $this->resort->resort_id;

        $inUseByGrid = ResortBenifitGrid::where('resort_id', $resort_id)->where('emp_grade', (string) $id)->exists();
        if ($inUseByGrid) {
            return response()->json([
                'success' => false,
                'message' => 'This grade level is used by an existing Benefit Grid and cannot be deleted. Deactivate it instead, or remove the grid first.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            ResortBenefitGradeLevelRank::where('resort_id', $resort_id)->where('grade_level_id', $id)->delete();
            ResortBenefitGradeLevel::where('resort_id', $resort_id)->where('id', $id)->delete();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Grade level deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete grade level.'], 500);
        }
    }

    public function ranksFor($id)
    {
        $id = (int) base64_decode($id);
        $ranks = ResortBenefitGradeLevelRank::where('resort_id', $this->resort->resort_id)
            ->where('grade_level_id', $id)
            ->pluck('rank');

        return response()->json(['success' => true, 'ranks' => $ranks]);
    }

    /**
     * "Select the ranking for it" — map one or more ranks to this grade
     * level. A rank belongs to at most one active grade level at a time
     * (unique on resort_id+rank), so assigning it here evicts it from
     * whichever grade level currently holds it.
     */
    public function updateRanks(Request $request, $id)
    {
        $id = (int) base64_decode($id);
        $resort_id = $this->resort->resort_id;

        $validator = Validator::make($request->all(), [
            'ranks' => 'nullable|array',
            'ranks.*' => 'integer',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $level = ResortBenefitGradeLevel::where('resort_id', $resort_id)->where('id', $id)->first();
        if (!$level) {
            return response()->json(['success' => false, 'message' => 'Grade level not found.'], 404);
        }

        DB::beginTransaction();
        try {
            // Release every rank this grade level currently owns, then
            // re-assign exactly the submitted set — updateOrInsert on the
            // unique (resort_id, rank) pair evicts any other grade level
            // that currently owns a requested rank.
            ResortBenefitGradeLevelRank::where('resort_id', $resort_id)->where('grade_level_id', $id)->delete();

            $newRankArray = [];
            foreach ($request->input('ranks', []) as $rank) {
                ResortBenefitGradeLevelRank::updateOrCreate(
                    ['resort_id' => $resort_id, 'rank' => $rank],
                    ['grade_level_id' => $id]
                );
                $newRankArray[] = (int) $rank;
            }

            // Benefit Grid's "Select Employee Grade" no longer picks a rank
            // directly (store()/update() resolve it from this mapping table
            // at save time) — so a grid created/last-saved before this rank
            // was mapped is stuck with whatever rank set was in effect back
            // then. Resync any existing grid for this grade level now, the
            // same way store()/update() would, so remapping here has the
            // same effect as re-saving the grid.
            $affectedGrid = ResortBenifitGrid::where('resort_id', $resort_id)
                ->where('emp_grade', (string) $id)
                ->first();

            if ($affectedGrid) {
                $affectedGrid->rank = implode(',', $newRankArray);
                $affectedGrid->save();

                // Preserve each leave category's day-allocation/eligible-type
                // (identical across every rank row it was saved with) and
                // just re-tag them onto the new rank set — don't invent data
                // for a leave category that never had any.
                $existingByLeaveCat = ResortBenifitGridChild::where('benefit_grid_id', $affectedGrid->id)
                    ->get()
                    ->groupBy('leave_cat_id')
                    ->map(function ($rows) {
                        return $rows->first();
                    });

                ResortBenifitGridChild::where('benefit_grid_id', $affectedGrid->id)->delete();

                foreach ($existingByLeaveCat as $leaveCatId => $representative) {
                    foreach ($newRankArray as $rank) {
                        ResortBenifitGridChild::create([
                            'benefit_grid_id' => $affectedGrid->id,
                            'leave_cat_id' => $leaveCatId,
                            'rank' => $rank,
                            'allocated_days' => $representative->allocated_days,
                            'eligible_emp_type' => $representative->eligible_emp_type,
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Rank mapping updated successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update rank mapping.'], 500);
        }
    }
}
