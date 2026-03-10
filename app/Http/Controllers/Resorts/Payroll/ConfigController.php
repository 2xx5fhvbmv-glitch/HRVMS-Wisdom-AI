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

    public function deductionsIndex()
    {
        $page_title = 'Deductions';
        return view('resorts.payroll.config.deductions', compact('page_title'));
    }

    public function exportServiceCharge(){    
        return Excel::download(new ServiceChargeExport(), 'ServiceCharge.xlsx');
    }

    public function importServiceCharge(Request $request)
    {
        $ImportServiceCharge = $request->ImportServiceCharge;
        $file = $request->file('ImportServiceCharge');
        $allowedExtensions = ['csv', 'xls', 'xlsx', 'ods', 'xlsb', 'xlt', 'xltx', 'xltm'];
        $validator = Validator::make($request->all(), [
            'ImportServiceCharge' => 'required|file',
        ]);
        if ($file && !in_array(strtolower($file->getClientOriginalExtension()), $allowedExtensions)) {
            $validator->after(function ($validator) {
                $validator->errors()->add('ImportServiceCharge', 'The Service Charge file must be a type of: csv, xls, xlsx, ods.');
            });
        }
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $fileName = time() . '_service_charge.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs('imports', $fileName);
        try {
            ImportServiceChargesJob::dispatchSync($filePath);
        } catch (\Exception $e) {
            \Storage::delete($filePath);
            return response()->json(['success' => false, 'message' => 'Failed to read the file. Please make sure it is a valid Excel file and not corrupted.'], 422);
        }
        return response()->json(['success' => true, 'message' => 'Service Charges Imported successfully']);
    }

    public function downloadEarningsTemplate()
    {
        return Excel::download(new AllowanceTemplateExport, 'allowance_template.xlsx');
    }

    public function importEarnings(Request $request)
    {
        $file = $request->file('ImportEarnings');
        $allowedExtensions = ['csv', 'xls', 'xlsx', 'ods', 'xlsb', 'xlt', 'xltx', 'xltm'];
        $validator = Validator::make($request->all(), [
            'ImportEarnings' => 'required|file',
        ]);
        if ($file && !in_array(strtolower($file->getClientOriginalExtension()), $allowedExtensions)) {
            $validator->after(function ($validator) {
                $validator->errors()->add('ImportEarnings', 'The Earnings file must be a type of: csv, xls, xlsx, ods.');
            });
        }
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $fileName = time() . '_earnings.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs('imports', $fileName);
        try {
            ImportAllowanceJob::dispatchSync($filePath);
        } catch (\Exception $e) {
            \Storage::delete($filePath);
            return response()->json(['success' => false, 'message' => 'Failed to read the file. Please make sure it is a valid Excel file and not corrupted.'], 422);
        }
        return response()->json(['success' => true, 'message' => 'Earnings Imported successfully']);
    }

    public function downloadDeductionTemplate()
    {
        return Excel::download(new DeductionTemplateExport, 'deductions_template.xlsx');
    }

    public function importDeductions(Request $request)
    {
        $file = $request->file('ImportDeductions');
        $allowedExtensions = ['csv', 'xls', 'xlsx', 'ods', 'xlsb', 'xlt', 'xltx', 'xltm'];
        $validator = Validator::make($request->all(), [
            'ImportDeductions' => 'required|file',
        ]);
        if ($file && !in_array(strtolower($file->getClientOriginalExtension()), $allowedExtensions)) {
            $validator->after(function ($validator) {
                $validator->errors()->add('ImportDeductions', 'The Deductions file must be a type of: csv, xls, xlsx, ods.');
            });
        }
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $fileName = time() . '_deductions.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs('imports', $fileName);
        try {
            ImportDeductionJob::dispatchSync($filePath);
        } catch (\Exception $e) {
            \Storage::delete($filePath);
            return response()->json(['success' => false, 'message' => 'Failed to read the file. Please make sure it is a valid Excel file and not corrupted.'], 422);
        }
        return response()->json(['success' => true, 'message' => 'Deductions Imported successfully']);
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
        $validatedData = $request->validate([
            'deductions.*.deduction_name' => 'required|string|max:255',
            'deductions.*.deduction_type' => 'required|string|max:255',
            'deductions.*.currency' => 'required|string|in:USD,Rufiyaa',
            'maximum_limit' => 'required|string',
            'maximum_limit_type' => 'required|string|in:percentage,fixed',
        ]);

        foreach ($validatedData['deductions'] as $deduction) {
            Deduction::create([
                'resort_id' => $resort_id,
                'deduction_name' => $deduction['deduction_name'],
                'deduction_type' => $deduction['deduction_type'],
                'currency' => $deduction['currency'],
                'maximum_limit' => $validatedData['maximum_limit'],
                'maximum_limit_type' => $validatedData['maximum_limit_type'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Deductions and limit saved successfully.',
        ]);
    }

    public function getDeductions()
    {
        $resort_id = $this->resort->resort_id;
        $deductions = Deduction::where('resort_id', $resort_id)->orderBy('id', 'desc')->get();
        return response()->json(['success' => true, 'data' => $deductions]);
    }

    public function updateDeduction(Request $request, $id)
    {
        $resort_id = $this->resort->resort_id;
        $deduction = Deduction::where('id', $id)->where('resort_id', $resort_id)->firstOrFail();

        $validatedData = $request->validate([
            'deduction_name' => 'required|string|max:255',
            'deduction_type' => 'required|string|max:255',
            'currency' => 'required|string|in:USD,Rufiyaa',
            'maximum_limit' => 'required|string',
            'maximum_limit_type' => 'required|string|in:percentage,fixed',
        ]);

        $deduction->update($validatedData);

        return response()->json(['success' => true, 'message' => 'Deduction updated successfully.']);
    }

    public function deleteDeduction($id)
    {
        $resort_id = $this->resort->resort_id;
        $deduction = Deduction::where('id', $id)->where('resort_id', $resort_id)->firstOrFail();
        $deduction->delete();

        return response()->json(['success' => true, 'message' => 'Deduction deleted successfully.']);
    }

    public function storeCutoffDay(Request $request)
    {
        // dd($request['cutoff_day']);
        // Validate the input
        $request->validate([
            'cutoff_day' => 'required|integer|min:1|max:31',
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