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
        $user = Auth::guard('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            // W-01: resort_id used to come straight from the query string
            // with only an exists:resorts,id check — any authenticated
            // mobile employee of any resort could read another resort's
            // whole cost structure (amounts, allowances, benefits) by
            // changing the id. Always use the caller's own resort instead;
            // a mismatched resort_id in the request is refused outright
            // rather than silently ignored, so the app finds out instead
            // of quietly seeing someone else's data.
            if ($request->filled('resort_id') && (int) $request->query('resort_id') !== (int) $user->resort_id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $resortId = $user->resort_id;

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
