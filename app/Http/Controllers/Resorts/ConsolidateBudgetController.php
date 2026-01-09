<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\ConsolidateBudgetData;
use Maatwebsite\Excel\Facades\Excel;
use Auth;

class ConsolidateBudgetController extends Controller
{
    public function ExportBudget()
    {
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