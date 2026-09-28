<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\ConsolidateBudgetData;
use App\Helpers\Common;
use Maatwebsite\Excel\Facades\Excel;
use Auth;

class ConsolidateBudgetController extends Controller
{
    public function ExportBudget()
    {
        // W-03: whole-resort consolidated budget export — HR/Finance only.
        if (Common::budgetAccessLevel() !== 'full') {
            abort(403, 'Unauthorized access');
        }

        try {
            $resortId = Auth::guard('resort-admin')->user()->resort_id;

        return Excel::download(new ConsolidateBudgetData($resortId), 'consolidated_budget.xlsx');
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => 'Error generating Excel file: ' . $e->getMessage()
            ], 500);
        }
    }
}