<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use App\Models\ResortAdmin;
use App\Models\ResortRole;
use App\Models\ResortModule;
use App\Models\ResortPermission;
use App\Models\ResortModulePermission;
use App\Models\ResortRoleModulePermission;
use App\Models\ResortBudgetCost;
use App\Helpers\Common;

class BudgetCostController extends Controller
{
    public function index()
    {
        
        $page_title = 'Budget Cost';
        $resort_id = Auth::guard('resort-admin')->user()->resort_id;
        
        return view('resorts.budgetcost.index')->with(
            compact('page_title','resort_id'));
    }

    public function costlist(Request $request)
    {
        if ($request->ajax()) 
        {
            $search =  $request->input('search');
            $resort_id = Auth::guard('resort-admin')->user()->resort_id;

            $tableData = ResortBudgetCost::where('resort_id', $resort_id)
                ->when($search, function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('cost_title', 'LIKE', '%' . $search . '%')
                        ->orWhere('particulars', 'LIKE', '%' . $search . '%')
                        ->orWhere('amount', 'LIKE', '%' . $search . '%')
                        ->orWhere('amount_unit', 'LIKE', '%' . $search . '%')
                        ->orWhere('cost_type', 'LIKE', '%' . $search . '%')
                        ->orWhere('details', 'LIKE', '%' . $search . '%')
                        ->orWhere('cost_type', 'LIKE', '%' . $search . '%')
                        ->orWhere('frequency', 'LIKE', '%' . $search . '%');
                    });
                })
                ->orderBy('updated_at', 'DESC')
                ->get();
                // dd($tableData);

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
                ->editColumn('is_payroll_allowance', function ($row) {
                    $isPayrollAllowanceClass = $row->is_payroll_allowance === 1 ? 'text-success' : 'text-danger';
                    $isPayrollAllowanceLabel = ucfirst($row->is_payroll_allowance === 1 ? 'yes' : 'no');
                    return '<span class="' . $isPayrollAllowanceClass . '">' . $isPayrollAllowanceLabel . '</span>';
                })
                ->escapeColumns([])
                ->make(true);
        }
    }
    public function store_costs(Request $request)
    {
        try {
            $resort_id = Auth::guard('resort-admin')->user()->resort_id;
            // Check if the user selected an existing division
            if ($request->filled('cost')) {
                // Existing division selected
                $cost = new ResortBudgetCost();
                $cost->resort_id = $resort_id;
                $cost->cost_title = $request->cost;
                $cost->particulars = ucwords($request->particulars);
                $cost->amount = $request->amount;
                $cost->amount_unit = $request->amount_unit;
                $cost->cost_type = $request->cost_type;
                $cost->frequency = $request->frequency;
                $cost->details = $request->details;
                $cost->status = $request->status;
                $cost->is_payroll_allowance = $request->is_payroll_allowance ?? 0;
                $cost->save();
            } else {
                // No division selected, so create a new division
                $cost = new ResortBudgetCost();
                $cost->resort_id = $resort_id;
                $cost->cost_title = $request->cost_name;
                $cost->particulars = ucwords($request->particulars);
                $cost->amount = $request->amount;
                $cost->amount_unit = $request->amount_unit;
                $cost->cost_type = $request->cost_type;
                $cost->frequency = $request->frequency;
                $cost->details = $request->details;
                $cost->status = $request->status;
                $cost->is_payroll_allowance = $request->is_payroll_allowance ?? 0;
                $cost->save();
            }

            return response()->json(['success' => true, 'message' => 'Cost added successfully.']);
                
        } catch( \Exception $e ) {
            \Log::emergency( "File: ".$e->getFile() );
            \Log::emergency( "Line: ".$e->getLine() );
            \Log::emergency( "Message: ".$e->getMessage() );
      
            return response()->json(['success' => false, 'message' => 'Failed to add cost.']);
        }
    }

    public function inlinecostUpdate(Request $request, $id)
    {
        // Find the division by ID
        $cost = ResortBudgetCost::find($id);

        if (!$cost) {
            return response()->json(['success' => false, 'message' => 'Cost not found.']);
        }
        
        // Validate incoming request
        $request->validate([
            'cost_title' => 'required|string|max:255',
            'particulars' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'amount_unit' => 'required',
            'cost_type' => 'required|string|max:255',
            'frequency' => 'required|string|max:255',
            'details' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
            'is_payroll_allowance' => 'required|in:0,1',
        ], [
            'amount.min' => 'Amount is not accepted negative value',
        ]);

        try {
            // Update the division's attributes
            $cost->cost_title = $request->input('cost_title');
            $cost->particulars = ucwords($request->input('particulars'));
            $cost->amount = $request->input('amount');
            $cost->amount_unit = $request->input('amount_unit');
            $cost->cost_type = $request->input('cost_type');
            $cost->frequency = $request->input('frequency');
            $cost->details = $request->input('details');
            $cost->status = $request->input('status');
            $cost->is_payroll_allowance = $request->input('is_payroll_allowance', 0); // Default to 0 if not provided

            // Save the changes
            $cost->save();

            // Return a JSON response
            return response()->json(['success' => true, 'message' => 'Cost updated successfully.']);
        } catch( \Exception $e ) {
            \Log::emergency( "File: ".$e->getFile() );
            \Log::emergency( "Line: ".$e->getLine() );
            \Log::emergency( "Message: ".$e->getMessage() );
      
            return response()->json(['success' => false, 'message' => 'Failed to update cost.']);
        }
    }

    public function destroy_costs($id)
    {
        try {
            $cost = ResortBudgetCost::findOrFail($id);
            $cost->delete();  // Soft delete if you're using soft deletes, otherwise use forceDelete()

            return response()->json(['success' => true, 'message' => 'Cost deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete cost.']);
        }
    }
}
?>