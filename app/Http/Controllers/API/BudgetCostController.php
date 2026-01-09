<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ResortBudgetCost; // Ensure you import your model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BudgetCostController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api'); // This will protect all methods in this controller
    }
    public function getBudgetCosts(Request $request)
    {
        
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate the input
        $request->validate([
            'resort_id' => 'required|integer|exists:resorts,id',
        ]);

        try {
            $resortId = $request->query('resort_id');

            // Fetch the budget costs for the specified resort
            $budgetCosts = ResortBudgetCost::where('resort_id', $resortId)->get();

            return response()->json(['success' => true, 'budget_costs' => $budgetCosts]);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }
}
?>
