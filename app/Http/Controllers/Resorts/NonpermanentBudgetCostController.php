<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ResortNonpermanentBudgetCost;

class NonpermanentBudgetCostController extends Controller
{
    public function index()
    {
        $page_title = 'Cost Configuration for Casuals & Interns';
        $resort_id = Auth::guard('resort-admin')->user()->resort_id;

        return view('resorts.budgetcost.nonpermanent_index')->with(
            compact('page_title', 'resort_id'));
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
        $request->validate([
            'cost_title' => 'required|string|max:255',
            'particulars' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'amount_unit' => 'required|string',
            'frequency' => 'required|string|max:255',
            'applies_to' => 'required|in:Casual,Intern,Both',
        ], [
            'amount.min' => 'Amount is not accepted negative value',
        ]);

        try {
            $resort_id = Auth::guard('resort-admin')->user()->resort_id;

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
        $cost = ResortNonpermanentBudgetCost::where('resort_id', Auth::guard('resort-admin')->user()->resort_id)->find($id);

        if (!$cost) {
            return response()->json(['success' => false, 'message' => 'Cost not found.']);
        }

        $request->validate([
            'cost_title' => 'required|string|max:255',
            'particulars' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'amount_unit' => 'required',
            'frequency' => 'required|string|max:255',
            'applies_to' => 'required|in:Casual,Intern,Both',
            'status' => 'required|in:active,inactive',
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
