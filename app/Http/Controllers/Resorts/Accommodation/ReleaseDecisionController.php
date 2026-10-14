<?php

namespace App\Http\Controllers\Resorts\Accommodation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AccommodationReleaseDecision as Decision;
use App\Models\AssingAccommodation;
use App\Helpers\Common;
use Auth;

/**
 * HR's Yes (vacate now) / Keep answer for accommodation still held by an
 * Inactive employee — prompted by accommodation:release-follow-up.
 */
class ReleaseDecisionController extends Controller
{
    public $resort;

    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
    }

    public function index()
    {
        abort_unless(Common::canManageEmployeeRecords(), 403, 'Unauthorized access');

        $decisions = Decision::where('resort_id', $this->resort->resort_id)
            ->whereIn('decision', Decision::OPEN)
            ->with('employee.resortAdmin', 'employee.department')
            ->orderBy('id')
            ->get()
            ->each(function ($d) {
                $d->units = AssingAccommodation::where('resort_id', $d->resort_id)->where('emp_id', $d->employee_id)
                    ->with('availableAccommodation.building')->get()
                    ->map(fn ($a) => trim(($a->availableAccommodation->building->BuildingName ?? '') . ' '
                        . ($a->availableAccommodation->RoomNo ?? '' ? 'Room ' . $a->availableAccommodation->RoomNo : '') . ' ' . $a->BedNo))
                    ->implode(', ');
            });

        return view('resorts.Accommodation.release-decisions', [
            'page_title' => 'Accommodation Release Decisions',
            'decisions'  => $decisions,
        ]);
    }

    public function decide(Request $request, $id)
    {
        if (!Common::canManageEmployeeRecords()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }
        $d = Decision::where('resort_id', $this->resort->resort_id)->whereIn('decision', Decision::OPEN)->find($id);
        if (!$d) {
            return response()->json(['success' => false, 'message' => 'This decision is no longer open.'], 404);
        }
        $deciderId = optional($this->resort->GetEmployee)->id;

        if ($request->action === 'keep') {
            $d->update(['decision' => 'keep', 'next_check_on' => now()->addDays(3)->toDateString(),
                'decided_by' => $deciderId, 'decided_at' => now()]);
            return response()->json(['success' => true, 'message' => 'Kept. HR will be asked again in 3 days.']);
        }
        if ($request->action !== 'vacate') {
            return response()->json(['success' => false, 'message' => 'Invalid action.'], 422);
        }

        $emp = $d->employee;
        if (!$emp || $emp->status !== 'Inactive') {
            return response()->json(['success' => false, 'message' => 'The employee is no longer Inactive.'], 422);
        }
        if (!$emp->finalSettlements()->where('status', 'finalized')->exists()) {
            return response()->json(['success' => false, 'message' => 'Full & Final Settlement is not finalized yet.'], 422);
        }

        try {
            // One transaction inside; notifies HR "now vacant" after commit.
            $units = Common::releaseAccommodationForEmployee($emp);
        } catch (\Throwable $e) {
            \Log::error("Accommodation release failed for emp #{$emp->id}: " . $e->getMessage());
            $d->update(['decision' => 'pending', 'last_error' => substr($e->getMessage(), 0, 190)]);
            return response()->json(['success' => false, 'message' => 'Release failed; nothing was changed. It stays open here.'], 500);
        }

        $d->update(['decision' => 'vacate', 'decided_by' => $deciderId, 'decided_at' => now(), 'last_error' => null]);
        return response()->json(['success' => true, 'message' => 'Vacated: ' . (implode(', ', $units) ?: 'no unit was held') . '.']);
    }
}
