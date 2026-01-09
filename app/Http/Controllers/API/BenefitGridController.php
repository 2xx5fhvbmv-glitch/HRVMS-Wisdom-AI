<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ResortBenifitGrid; // Ensure you import your model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BenefitGridController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api'); // This will protect all methods in this controller
    }
    public function getBenefitGrids(Request $request)
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
            $benefitgrids = ResortBenifitGrid::where('resort_id', $resortId)->get();

            return response()->json(['success' => true, 'benefitgrids' => $benefitgrids]);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }
    public function getBenefitGridsByRank(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate incoming parameters
        $request->validate([
            'resort_id' => 'nullable|integer|exists:resorts,id',
            'rank' => 'nullable|string',
        ]);

        try {
            // Start with the base query
            $query = ResortBenifitGrid::query();

            // Apply filters if provided
            if ($request->has('resort_id')) {
                $query->where('resort_id', $request->resort_id);
            }

            if ($request->has('rank')) {
                $query->where('rank', $request->rank);
            }

            // Retrieve benefit grids
            $benefitGrids = $query->get();

            return response()->json([
                'success' => true,
                'data' => $benefitGrids,
            ], 200);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }
}
?>