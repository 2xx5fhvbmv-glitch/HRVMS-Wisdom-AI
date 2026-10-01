<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\ConsolidateBudgetData;
use App\Helpers\Common;
use Maatwebsite\Excel\Facades\Excel;

class ConsolidateBudgetController extends Controller
{
    public function ExportBudget(Request $request, BudgetController $budgetController)
    {
        // W-03: whole-resort consolidated budget export — HR/Finance only.
        if (Common::budgetAccessLevel() !== 'full') {
            abort(403, 'Unauthorized access');
        }

        try {
            $exportData = $budgetController->assembleConsolidatedExportData($request);

            return Excel::download(new ConsolidateBudgetData($exportData), 'consolidated_budget.xlsx');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => 'Error generating Excel file: ' . $e->getMessage()
            ], 500);
        }
    }
}