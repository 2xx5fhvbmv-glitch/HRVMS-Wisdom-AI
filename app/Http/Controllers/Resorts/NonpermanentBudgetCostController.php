<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ResortNonpermanentBudgetCost;
use App\Models\ResortPosition;

class NonpermanentBudgetCostController extends Controller
{
    public function index()
    {
        $page_title = 'Cost Configuration for Casuals & Interns';
        $resort_id = Auth::guard('resort-admin')->user()->resort_id;

        // WP2.3 — without this, a cost line has no way to be tied to
        // specific positions at creation time, so it silently applies to
        // every position of its category (D2's "empty = all positions"
        // default is meant to be a deliberate choice, not the only option).
        $positions = ResortPosition::where('resort_id', $resort_id)
            ->where('status', 'active')
            ->whereIn('employee_category', ['Casual', 'Intern'])
            ->orderBy('employee_category')
            ->orderBy('position_title')
            ->get(['id', 'position_title', 'employee_category']);

        return view('resorts.budgetcost.nonpermanent_index')->with(
            compact('page_title', 'resort_id', 'positions'));
    }

    public function costlist(Request $request)
    {
        if ($request->ajax()) {
            $search = $request->input('search');
            $resort_id = Auth::guard('resort-admin')->user()->resort_id;

            $tableData = ResortNonpermanentBudgetCost::where('resort_id', $resort_id)
                ->when($search, function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('cost_title', 'LIKE', '%' . $search . '%')
                        ->orWhere('particulars', 'LIKE', '%' . $search . '%')
                        ->orWhere('amount', 'LIKE', '%' . $search . '%')
                        ->orWhere('amount_unit', 'LIKE', '%' . $search . '%')
                        ->orWhere('cost_type', 'LIKE', '%' . $search . '%')
                        ->orWhere('applies_to', 'LIKE', '%' . $search . '%')
                        ->orWhere('details', 'LIKE', '%' . $search . '%')
                        ->orWhere('frequency', 'LIKE', '%' . $search . '%');
                    });
                })
                ->orderBy('updated_at', 'DESC')
                ->get();

            return datatables()->of($tableData)
                ->addColumn('action', function ($row) {
                    return '
                        <div class="d-flex align-items-center">
                            <a href="#" class="btn-lg-icon icon-bg-green me-1 edit-row-btn"
                            data-cost-id="' . htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8') . '">
                                <img src="' . asset('resorts_assets/images/edit.svg') . '" alt="" class="img-fluid" />
                            </a>
                            <a href="#" class="btn-lg-icon icon-bg-red delete-row-btn"
                            data-cost-id="' . htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8') . '">
                                <img src="' . asset('resorts_assets/images/trash-red.svg') . '" alt="" class="img-fluid" />
                            </a>
                        </div>';
                })
                ->editColumn('status', function ($row) {
                    $statusClass = $row->status === "active" ? 'text-success' : 'text-danger';
                    $statusLabel = ucfirst($row->status);
                    return '<span class="' . $statusClass . '">' . $statusLabel . '</span>';
                })
                ->escapeColumns([])
                ->make(true);
        }
    }

    public function store_costs(Request $request)
    {
        $resort_id = Auth::guard('resort-admin')->user()->resort_id;

        // WP2.3 — a position tie must belong to this resort AND to the
        // category (or Both) this cost line applies to; otherwise a
        // Casual-only line could be tied to an Intern position (or vice
        // versa), silently costing nothing for anyone.
        $appliesToCategories = $request->input('applies_to') === 'Both'
            ? ['Casual', 'Intern']
            : [$request->input('applies_to')];

        $request->validate([
            'cost_title' => 'required|string|max:255',
            'particulars' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'amount_unit' => 'required|string',
            'frequency' => 'required|string|max:255',
            'applies_to' => 'required|in:Casual,Intern,Both',
            'position_ids' => 'nullable|array',
            'position_ids.*' => [
                'integer',
                \Illuminate\Validation\Rule::exists('resort_positions', 'id')
                    ->where('resort_id', $resort_id)
                    ->whereIn('employee_category', $appliesToCategories),
            ],
        ], [
            'amount.min' => 'Amount is not accepted negative value',
        ]);

        try {

            $cost = new ResortNonpermanentBudgetCost();
            $cost->resort_id = $resort_id;
            $cost->cost_title = $request->cost_title;
            $cost->particulars = $request->filled('particulars') ? ucwords($request->particulars) : null;
            $cost->amount = $request->amount;
            $cost->amount_unit = $request->amount_unit;
            $cost->cost_type = $request->cost_type;
            $cost->frequency = $request->frequency;
            $cost->applies_to = $request->applies_to;
            $cost->details = $request->details;
            $cost->status = $request->status ?? 'active';
            $cost->save();

            // WP2 (D2) — empty/omitted = applies to every position of this
            // cost's own category (unrestricted, today's behavior).
            if ($request->filled('position_ids')) {
                $cost->positions()->sync($request->input('position_ids'));
            }

            return response()->json(['success' => true, 'message' => 'Cost added successfully.']);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to add cost.']);
        }
    }

    public function inlinecostUpdate(Request $request, $id)
    {
        $resort_id = Auth::guard('resort-admin')->user()->resort_id;
        $cost = ResortNonpermanentBudgetCost::where('resort_id', $resort_id)->find($id);

        if (!$cost) {
            return response()->json(['success' => false, 'message' => 'Cost not found.']);
        }

        $appliesToCategories = $request->input('applies_to') === 'Both'
            ? ['Casual', 'Intern']
            : [$request->input('applies_to')];

        $request->validate([
            'cost_title' => 'required|string|max:255',
            'particulars' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'amount_unit' => 'required',
            'frequency' => 'required|string|max:255',
            'applies_to' => 'required|in:Casual,Intern,Both',
            'status' => 'required|in:active,inactive',
            'position_ids' => 'nullable|array',
            'position_ids.*' => [
                'integer',
                \Illuminate\Validation\Rule::exists('resort_positions', 'id')
                    ->where('resort_id', $resort_id)
                    ->whereIn('employee_category', $appliesToCategories),
            ],
        ], [
            'amount.min' => 'Amount is not accepted negative value',
        ]);

        try {
            $cost->cost_title = $request->input('cost_title');
            $cost->particulars = $request->filled('particulars') ? ucwords($request->input('particulars')) : null;
            $cost->amount = $request->input('amount');
            $cost->amount_unit = $request->input('amount_unit');
            $cost->cost_type = $request->input('cost_type');
            $cost->frequency = $request->input('frequency');
            $cost->applies_to = $request->input('applies_to');
            $cost->details = $request->input('details');
            $cost->status = $request->input('status');
            $cost->save();

            // WP2 (D2) — sync() with an empty array clears all ties (back to
            // "applies to every position"), matching the field being blanked
            // out on edit; the key just needs to be present in the request.
            if ($request->has('position_ids')) {
                $cost->positions()->sync($request->input('position_ids', []));
            }

            return response()->json(['success' => true, 'message' => 'Cost updated successfully.']);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to update cost.']);
        }
    }

    public function destroy_costs($id)
    {
        try {
            $cost = ResortNonpermanentBudgetCost::where('resort_id', Auth::guard('resort-admin')->user()->resort_id)->findOrFail($id);
            $cost->delete();

            return response()->json(['success' => true, 'message' => 'Cost deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete cost.']);
        }
    }
}
