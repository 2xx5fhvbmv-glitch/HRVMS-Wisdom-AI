<?php

namespace App\Http\Controllers\Resorts\Payroll;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ServiceChargeExport;
use App\Exports\AllowanceTemplateExport;
use App\Exports\DeductionTemplateExport;
use App\Jobs\ImportServiceChargesJob;
use App\Jobs\ImportAllowanceJob;
use App\Jobs\ImportDeductionJob;
use App\Models\Earnings;
use App\Models\Deduction;
use App\Models\PayrollConfig;
use Illuminate\Support\Facades\Validator;
use Auth;
use Config;
use DB;

class ConfigController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
    }

    public function index()
    {
        $page_title ='Payroll Configuration';
        $resort_id = $this->resort->resort_id;
        return view('resorts.payroll.config.index',compact('page_title'));
    }

    public function exportServiceCharge(){    
        return Excel::download(new ServiceChargeExport(), 'ServiceCharge.xlsx');
    }

    public function importServiceCharge(Request $request)
    {
        $ImportServiceCharge = $request->ImportServiceCharge;
        $validator = Validator::make($request->all(), [
            'ImportServiceCharge' => 'required|file|mimes:xls,xlsx',
        ],
        [
            'ImportServiceCharge.mimes' => 'The Past Leaves data file must be a type of: xls, xlsx.',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $filePath = $request->file('ImportServiceCharge')->store('imports');
        $check =  ImportServiceChargesJob::dispatch($filePath,);
        $response['success'] = true;
        $response['message'] ="Service Charges Imported successfully";
        return response()->json($response);
    }

    public function downloadEarningsTemplate()
    {
        return Excel::download(new AllowanceTemplateExport, 'allowance_template.xlsx');
    }

    public function importEarnings(Request $request)
    {
        $ImportEarnings = $request->ImportEarnings;
        $validator = Validator::make($request->all(), [
            'ImportEarnings' => 'required|file|mimes:xls,xlsx',
        ],
        [
            'ImportEarnings.mimes' => 'The Earnings data file must be a type of: xls, xlsx.',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $filePath = $request->file('ImportEarnings')->store('imports');
        $check =  ImportAllowanceJob::dispatch($filePath,);
        $response['success'] = true;
        $response['message'] ="Earnings Imported successfully";
        return response()->json($response);
    }

    public function downloadDeductionTemplate()
    {
        return Excel::download(new DeductionTemplateExport, 'deductions_template.xlsx');
    }

    public function importDeductions(Request $request)
    {
        $ImportDeductions = $request->ImportDeductions;
        $validator = Validator::make($request->all(), [
            'ImportDeductions' => 'required|file|mimes:xls,xlsx',
        ],
        [
            'ImportDeductions.mimes' => 'The deductions data file must be a type of: xls, xlsx.',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $filePath = $request->file('ImportDeductions')->store('imports');

        $check =  ImportDeductionJob::dispatch($filePath,);

        $response['success'] = true;

        $response['message'] ="Deductions Imported successfully";
        return response()->json($response);
    }

    public function storeEarnings(Request $request)
    {
        // dd($request->all());
        $resort_id = $this->resort->resort_id;
        $validatedData = $request->validate([
            'earnings.*.allow_name' => 'required|string|max:255',
            'earnings.*.allow_type' => 'required|string|max:255',
            'earnings.*.currency' => 'required|string|in:USD,Rufiyaa',

        ]);

        foreach ($validatedData['earnings'] as $earning) {
            Earnings::create([
                'resort_id' => $resort_id,
                'allow_name' => $earning['allow_name'],
                'allow_type' => $earning['allow_type'],
                'currency' => $earning['currency'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Earnings saved successfully.',
        ]);
    }

    public function storeDeductions(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        // Validate the input
        $validatedData = $request->validate([
            'deductions.*.deduction_name' => 'required|string|max:255',
            'deductions.*.deduction_type' => 'required|string|max:255',
            'deductions.*.currency' => 'required|string|in:USD,Rufiyaa',
            'maximum_limit' => 'required|string', // Validate maximum limit
        ]);

        // Save deductions
        foreach ($validatedData['deductions'] as $deduction) {
            Deduction::create([
                'resort_id' => $resort_id,
                'deduction_name' => $deduction['deduction_name'],
                'deduction_type' => $deduction['deduction_type'],
                'currency' => $deduction['currency'],
                'maximum_limit' => $validatedData['maximum_limit'], // Save the limit
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Deductions and limit saved successfully.',
        ]);
    }

    public function storeCutoffDay(Request $request)
    {
        // dd($request['cutoff_day']);
        // Validate the input
        $request->validate([
            'cutoff_day' => 'required|integer|min:1|max:28',
        ]);

        // Save to the database
        PayrollConfig::updateOrCreate(
            [
                'resort_id' => $this->resort->resort_id, // Match condition
            ],
            [
                'cutoff_day' => $request['cutoff_day'], // Match condition
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Cutoff Day saved successfully.',
        ]);
    }
}