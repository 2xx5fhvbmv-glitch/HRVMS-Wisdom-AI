<?php
namespace App\Http\Controllers\Resorts;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use  App\Models\Occuplany;
use Auth;
use Carbon;
use DateTime;
use DatePeriod;
use DateInterval;
use DB;
use App\Models\Resort;
use App\Jobs\ImportOccupancyJob;
use App\Imports\OccupnacyImport;
use Maatwebsite\Excel\Facades\Excel;
class OccupancyController extends Controller
{
    protected $Authdata ='';
    public function __construct()
    {
        $this->Authdata = Auth::guard('resort-admin')->user();
        if(!$this->Authdata) return;
    }
    public function storeOccupancy(Request $request)
    {
        // Validate incoming request
        //   dd($request->all());
        $validator = Validator::make($request->all(),[
            'occupancydate' => 'required|max:255',
            'occupancyinPer' => 'required|max:50',
            // 'occupancytotalRooms' => '|max:50',
            // 'occupancyOccupiedRooms' => 'required|max:50',
        ], [
            'occupancydate.required' => 'The occupancy date is required.',
            'occupancyinPer.required' => 'The occupancy percentage is required.',
            // 'occupancytotalRooms.required' => 'The total number of rooms is required.',
            // 'occupancyOccupiedRooms.required' => 'Please specify whether the Occupied.',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        try
        {
            $Occuplany =  Occuplany::updateOrCreate(
            [
                'resort_id' =>$this->Authdata->resort_id, // Unique attribute to identify the record
                'occupancydate' => date("Y-m-d", strtotime($request->occupancydate)) // Unique attribute to identify the record
            ],
            [
                'occupancydate' => date("y-m-d", strtotime($request->occupancydate)), //$request->occupancydate,
                'occupancyinPer' => $request->occupancyinPer,
                'resort_id' => $this->Authdata->resort_id,
                'occupancytotalRooms' => $request->occupancytotalRooms,
                'occupancyOccupiedRooms' => $request->occupancyOccupiedRooms
            ]);
            $occupancies = Occuplany::find($Occuplany->id)->first(['id','occupancyinPer','occupancydate','occupancytotalRooms','occupancyOccupiedRooms']);
            $RoomsAvailable = $occupancies->occupancytotalRooms - $occupancies->occupancyOccupiedRooms;
            $OccuplanyPercentage = $occupancies->occupancyinPer;
            
            $response['success'] = true;
            $response['data'] = [$RoomsAvailable,$OccuplanyPercentage];
            $response['updated_id'] =$Occuplany->id;
            $response['msg'] ="occupancy saved successfully";
            return response()->json($response);
        }
        catch(\Exception $e)
        {
            \Log::emergency( "File: ".$e->getFile() );
            \Log::emergency( "Line: ".$e->getLine() );
            \Log::emergency( "Message: ".$e->getMessage() );

            $response['success'] = false;
            $response['msg'] = $e->getMessage();
            return response()->json($response);
        }
    }
    public function getOccupancyData(Request $request)
    {
        // Retrieve the direction (next or previous) and the current date
        $direction = $request->input('direction');
        $currentDate = $request->input('currentDate');

        // Fetch occupancy data based on direction (next/previous day)
        if ($direction === 'next') {
            $date = \Carbon\Carbon::parse($currentDate)->addDay();
        } else if ($direction === 'previous') {
            $date = \Carbon\Carbon::parse($currentDate)->subDay();
        }

        // Fetch your occupancy data for the new date (Example: Query the DB)
        // Assuming you have an `occupancies` table or a similar structure
        $resort_id =$this->Authdata->resort_id;
        $occupancyData = Occuplany::whereDate('occupancydate', $date->toDateString())->where('resort_id',$resort_id )->first();

        $array=array();
        if(isset($occupancyData->id))
        {
            $array['date']=date('d M Y',strtotime($occupancyData->occupancydate));
            $array['occupancyPercentage']=$occupancyData->occupancyinPer;
            $array['roomsAvailable']=$occupancyData->occupancyOccupiedRooms;
        }
        if ($occupancyData) {
            return response()->json([
                'success' => true,
                'data' =>$array,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No data found for the selected date.'
            ]);
        }
    }
    // public function ImportDatas(Request $request)
    // {
    //     try{
    //       $validator = Validator::make($request->all(), [
    //         'importFile' => 'required|file|mimes:xls,xlsx,csv|max:2048' // Accept only Excel files up to 5MB
    //     ],[
    //         'importFile.required' => 'Please upload an Excel file.',
    //         'importFile.file' => 'The uploaded file must be a valid file.',
    //         'importFile.mimes' => 'The file must be an Excel sheet (xls or xlsx).',
    //     ]);
       
    //     if ($validator->fails()) {
    //         return response()->json(['success' => false, 'msg' => $validator->errors()->first()], 422);
    //     }

    //     session()->forget('import_errors');

    //     if (!$request->hasFile('importFile')) {
    //         return response()->json(['success' => false, 'msg' => 'No file uploaded'], 422);
    //     }

    //     // Store file locally (storage/app/imports) with explicit disk specification
    //     $relativePath = $request->file('importFile')->store('imports', 'local');

    //     //       full path
    //     $fullPath = storage_path('app/' . $relativePath);
        
    //     // Check if file was actually stored
    //     if (!file_exists($fullPath)) 
    //     {
    //         return response()->json(['success' => false, 'msg' => 'Failed to store uploaded file'], 500);
    //     }

    //     $import = new OccupnacyImport($this->Authdata->resort);
    //         Excel::import($import, $fullPath);
    //         $importErrors = session('import_errors');
    //          $affectedIds = $import->getAffectedIds();

    //         $response['affected_ids'] = $affectedIds;
    //         $response['success'] = true;
    //         $response['msg'] ="Occupancy Datas Imported  successfully";
    //     }
    //     catch(\Exception $e)
    //     {
    //         \Log::emergency( "File: ".$e->getFile() );
    //         \Log::emergency( "Line: ".$e->getLine() );
    //         \Log::emergency( "Message: ".$e->getMessage() );

    //         $response['success'] = false;
    //         $response['msg'] = $e->getMessage();
    //     }
    //     return response()->json($response);
    // }

    public function ImportDatas(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'importFile' => 'required|file|mimes:xls,xlsx,csv|max:2048' // 2MB max
            ], [
                'importFile.required' => 'Please upload an Excel file.',
                'importFile.file' => 'The uploaded file must be a valid file.',
                'importFile.mimes' => 'The file must be an Excel sheet (xls or xlsx or csv).',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'msg' => $validator->errors()->first()], 422);
            }

            session()->forget('import_errors');

            if (!$request->hasFile('importFile')) {
                return response()->json(['success' => false, 'msg' => 'No file uploaded'], 422);
            }

            // Save the file to /storage/app/imports
            $relativePath = $request->file('importFile')->store('imports', 'local');
            $fullPath = storage_path('app/' . $relativePath);

            if (!file_exists($fullPath)) {
                return response()->json(['success' => false, 'msg' => 'Failed to store uploaded file'], 500);
            }

            // Import using the custom import class
            $import = new OccupnacyImport();
            Excel::import($import, $fullPath);

            // Collect errors and affected IDs
            $importErrors = session('import_errors', []);
            $affectedIds = $import->getAffectedIds();

            $response = [
                'success' => empty($importErrors),
                'msg' => empty($importErrors)
                    ? 'Occupancy data imported successfully.'
                    : 'Some rows had issues. Please review errors.',
                'affected_ids' => $affectedIds,
                'import_errors' => $importErrors,
            ];
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());

            $response = [
                'success' => false,
                'msg' => 'Something went wrong: ' . $e->getMessage(),
            ];
        }

        return response()->json($response);
    }
    public function storeBulkOccupancy(Request $request)
    {
        try {            
            // Insert or update occupancy data in bulk
            foreach ($request['dates'] as $date) {
                DB::table('occuplanies')->updateOrInsert(
                    ['resort_id' => $this->Authdata->resort_id, 'occupancydate' => $date],
                    [
                        'occupancyinPer' => $request['occupancy_percentage'],
                    ]
                );
            }
            // Return success response
            return response()->json([
                'success' => true,
                'msg' => 'Occupancy data saved successfully.',
            ]);
        } catch (\Exception $e) {
            // Log the error
            \Log::error('Error saving occupancy data: ' . $e->getMessage());

            // Return error response
            return response()->json([
                'success' => false,
                'msg' => 'An error occurred while saving occupancy data. Please try again later.',
            ], 500);
        }
    }

}
