<?php

namespace App\Http\Controllers\Resorts\TimeAndAttendance;

use DB;
use Auth;
use Illuminate\Http\Request;
use App\Models\ShiftSettings;
use App\Models\PublicHoliday;
use App\Models\ResortGeoLocation;
use App\Models\ResortHoliday;
use App\Models\ColorTheme;
use App\Helpers\Common;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\ResortDivision;
use App\Models\ResortDepartment;
use App\Models\ResortSection;
use App\Models\ResortPosition;
use App\Exports\EmployeeAttendanceExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Jobs\ResortHolidayImportJob;
use Carbon\Carbon;
use DateTime;
class ConfigrationController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
    }
    public function index()
    {
            $page_title = 'Configuration';
            $resort_id          = $this->resort->resort_id;
            $ShiftSettings      = ShiftSettings::where("resort_id",$resort_id)->get();
            $ResortGeoLocation  = ResortGeoLocation::where("resort_id",$resort_id)->first();
            $PublicHoliday      = PublicHoliday::orderByDesc("id")->get();
            $resort_divisions   = ResortDivision::where('status', 'active')->where('resort_id',$this->resort->resort_id)->get();
            $resort_departments = ResortDepartment::where('status','active')->where('resort_id',$resort_id)->get();
            $colorThemes        = ColorTheme::where('resort_id', $resort_id)->get();
            
            return view('resorts.timeandattendance.Configration.index',compact('page_title','resort_divisions','resort_departments','ResortGeoLocation','resort_id','ShiftSettings','PublicHoliday','colorThemes'));
    }
    public function ShiftStore(Request $request)
    {

        $ShiftName = $request->ShiftName;
        $StartTime = $request->StartTime;
        $EndTime = $request->EndTime;
        $resort_id = $this->resort->resort_id;
        $Shiftid = $request->id ?? [];
        $validator = Validator::make($request->all(), [
            'ShiftName.*' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request, $Shiftid, $resort_id) {
                    $index = explode('.', $attribute)[1];
                    $startTime = $request->input('StartTime.' . $index);
                    $endTime = $request->input('EndTime.' . $index);
                    $ShiftName = $value;  // Use the current ShiftName value for this index

                    // Skip validation if any time or shift name is empty
                    if (empty($startTime) || empty($endTime) || empty($ShiftName)) {
                        return; // No validation if any of these fields are empty
                    }

                    $query = ShiftSettings::where('ShiftName', $ShiftName)
                        // ->Orwhere('StartTime', $startTime)
                        // ->Orwhere('EndTime', $endTime)
                        ->where('resort_id', $resort_id);

                    if (isset($Shiftid[$index]) && !empty($Shiftid[$index])) {
                        $query->where('id', '!=', $Shiftid[$index]);  // Exclude current shift ID if editing
                    }
                    $existingShift = $query->first();
                    if ($existingShift) {
                        $fail("A shift with this name, start time, and end time already exists for this resort.");
                    }
                }
            ],

        ]);
        foreach ($request->input('ShiftName') as $index => $shiftName)
        {
            $totalHours =0;
            $start = new DateTime($request->input('StartTime.' . $index));
            $end = new DateTime($request->input('EndTime.' . $index));
            if ($end < $start)
            {
                $end->modify('+1 day');
            }
            $interval = $start->diff($end);
            $totalHours = $interval->h + ($interval->days * 24);
            $totalMinutes = $interval->i;
            $TotalHours =  $totalHours . ":" . $totalMinutes;
            $shiftData = [
                'ShiftName' => $shiftName,
                'StartTime' => $request->input('StartTime.' . $index),
                'EndTime' => $request->input('EndTime.' . $index),
                'resort_id' => $request->input('resort_id'),
                'TotalHours' => $TotalHours
            ];

            // Check if this is an update or a new record
            if (!empty($Shiftid) && isset($Shiftid[$index])) {
                // Update existing shift
                $shift = ShiftSettings::find($Shiftid[$index]);
                $shift->update($shiftData);
            } 
            else
            {
                $ShiftSettings = ShiftSettings::where('ShiftName', $shiftName)->where('resort_id', $resort_id)->first();  
                if(!$ShiftSettings) 
                {
                      ShiftSettings::create($shiftData);
                }
            }
        }
        return response()->json(['success'=>true,'message' => 'Shift settings updated successfully.']);
    }
    public function removeshift(Request $request)
    {
        try
        {
            DB::beginTransaction();
            $id = $request->id;
            ShiftSettings::find($id)->delete();

            DB::commit();
            return response()->json([
                'success' => false,
                'message' => 'Shift removed successfully.'
            ]);
        }
        catch (\Exception $e) {

            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            DB:Rollback();
            return response()->json([
                'success' => false,
                'message' => 'Shift Not Delete.'
            ]);
        }
    }
    //    Geo Location
    public function GeoLocation(Request $request)
    {
        try
        {
            DB::beginTransaction();
            $page_title = "Geo Location";
            $resort_id = $request->resort_id;
            $latitude = $request->latitude;
            $longitude = $request->longitude;

            $GeoLocation = ResortGeoLocation::updateOrCreate(["resort_id"=>$resort_id],["resort_id"=>$resort_id,'latitude'=>$latitude,'longitude'=>$longitude]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Geo Loction Updated'
            ]);
        }
        catch (\Exception $e)
        {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            DB::Rollback();
            return response()->json([
                'success' => false,
                'message' => 'Geo Loction Not Updated'
            ]);
        }
    }
    public function Publicholidaylist(Request $request)
    {
        if(Common::checkRouteWisePermission('resort.upcomingholiday.list',config('settings.resort_permissions.view')) == false) {
            return abort(403, 'Unauthorized action.');
        }
        $ResortHoliday = ResortHoliday::where('resort_id', $this->resort->resort_id)->whereYear('PublicHolidaydate', date('Y'))->get();

        if ($request->ajax()) {
            return datatables()->of($ResortHoliday)
                ->addColumn('action', function ($row) {
                    return '
                        <div class="d-flex justify-content-start">
                            <a href="javascript:void(0)"
                                class="btn-lg-icon icon-bg-green me-1 edit-row-btn"
                                data-id="' . e($row->id) . '">
                                <img src="' . asset('resorts_assets/images/edit.svg') .'" alt="Edit" data-id="' . e($row->id) . '" data-publicholidaydate="'. e($row->PublicHolidaydate) .'" data-publicholidescription="'. e($row->description) .'"   data-publicholidayname="'. e($row->PublicHolidayName) .'" data-holidayid="'. e($row->HolidayId) .'" class="img-fluid AddPublicHolidays" />
                            </a>
                            <a href="javascript:void(0)"
                                class="btn-lg-icon icon-bg-red delete-row-btn"
                                data-id="' . e($row->id) . '">
                                <img src="' . asset('resorts_assets/images/trash-red.svg') . '" alt="Delete" class="img-fluid" />
                            </a>
                        </div>
                    ';
                })
                ->editColumn('PublicHolidaydate', function ($row) {
                    return $row->PublicHolidaydate ? Carbon::parse($row->PublicHolidaydate)->format('d-m-Y') :'--';
                })
                    ->addColumn('day', function ($row) {
                        return $row->PublicHolidaydate ? Carbon::parse($row->PublicHolidaydate)->format('l') : '--';
                    })
                ->rawColumns(['action'])
                ->make(true);
        }

        $page_title = "Public Holiday List";
        $PublicHoliday = PublicHoliday::orderByDesc("id")->get();
        $resort_id = $this->resort->resort_id;
        return view('resorts.timeandattendance.Configration.publicholidaylist', compact('ResortHoliday', 'PublicHoliday','page_title','resort_id'));
    }
    public function ResortHoliday(Request $request)
    {
            $resort_id = $request->resort_id;
            $PublicHoliday = $request->PublicHoliday; // Default Super Admin Resort data
            $ResortPublicHolidayDate = $request->ResortPublicHolidayDate;  // Default Super Admin Resort data
            $holiday_date = $request->holiday_date;

            if (isset($request->PublicHoliday))
            {
                $data = PublicHoliday::find($request->PublicHoliday);
                $PublicHolidaydate = date('Y-m-d',strtotime($data->holiday_date));
                $PublicHolidayName = $data->name;
                $HolidayId = $data->id;
                $request->merge(['PublicHolidayName' => $data->name]);
            }
            else
            {
                $PublicHolidaydate = $request->PublicHolidaydate;
                $PublicHolidayName = $request->PublicHolidayName;
                $request->merge(['PublicHolidayName' => $PublicHolidayName]);
                $HolidayId = 0;

            }


            $validator = Validator::make($request->all(), [
                'PublicHolidayName' => 'required|exists:resortholidays,id',
                'PublicHolidayName' => [
                    'required',
                    'string',
                    'max:255',
                    function ($attribute, $value, $fail) use ($request, $resort_id) {
                        $query = ResortHoliday::where('PublicHolidayName', $value)
                            ->where('resort_id', $resort_id);

                        if ($request->id) {
                            $query->where('id', '!=', $request->id);
                        }

                        $existingHoliday = $query->first();

                        if ($existingHoliday) {
                            $fail("A holiday with this name already exists for this resort.");
                        }
                    }
                ]
            ], [
                'PublicHolidayName.unique' => 'A holiday with this name already exists for this resort.'
            ]);

            // If validation fails
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }



                $holidayData = [
                    'resort_id' => $resort_id,
                    'PublicHolidayName' => $PublicHolidayName,
                    'PublicHolidaydate' => $PublicHolidaydate,
                    'HolidayId'=>$HolidayId,
                    'description'=>$request->description,
                ];

                if ($request->id) {
                    $PublicHoliday = ResortHoliday::where('id', $request->id)
                        ->where('resort_id', $resort_id)
                        ->update($holidayData);
                } else {
                    $PublicHoliday = ResortHoliday::create($holidayData);
                }
                try {
                    DB::beginTransaction();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Holiday Added or Updated Successfully',
                    'data' => $PublicHoliday
                ]);
                } catch (\Exception $e) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => 'Error processing holiday',
                        'error' => $e->getMessage()
                    ], 500);
                }

    }
    public function ResortHolidaydelete(Request $request)
    {

        try
        {
            DB::beginTransaction();
            $id = $request->id;
            $resortHoliday = ResortHoliday::find($id)->delete();
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Holiday Removed Succesfully.']);


        }
        catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error processing holiday',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function ResortHolidayUpdate(Request $request)
    {
            $resort_id = $request->resort_id;
            $PublicHoliday = $request->PublicHoliday; // Default Super Admin Resort data
            $ResortPublicHolidayDate = $request->ResortPublicHolidayDate;  // Default Super Admin Resort data
            $holiday_date = $request->holiday_date;
            $id = $request->id;
            $resort_id = $request->resort_id;
            $validator = Validator::make($request->all(), [
                'PublicHolidayName' => [
                    'required',
                    'string',
                    'max:255',
                    // Unique validation logic
                    function ($attribute, $value, $fail) use ($request, $id, $resort_id) {
                        $query = ResortHoliday::where('PublicHolidayName', $value)
                            ->where('resort_id', $resort_id);

                        if ($id) {
                            $query->where('id', '!=', $id);
                        }

                        $existingHoliday = $query->first();

                        if ($existingHoliday) {
                            $fail("A holiday with this name already exists for this resort.");
                        }
                    }
                ]
            ], [
                'PublicHolidayName.required' => 'The holiday name field is required.',
                'PublicHolidayName.string' => 'The holiday name must be a string.',
                'PublicHolidayName.max' => 'The holiday name must not exceed 255 characters.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors()
                ], 422);
            }


            try{
                DB::beginTransaction();

                // Proceed with the update logic
                $resortHoliday = ResortHoliday::find($id);
                if (!$resortHoliday)
                {
                    return response()->json(['success' => false, 'message' => 'Holiday not found.']);
                }
                $resortHoliday->PublicHolidayName = $request->PublicHolidayName;
                $resortHoliday->description = $request->description;
                $resortHoliday->save();
                DB::commit();
                return response()->json(['success' => true, 'message' => 'Holiday updated successfully.']);

            } catch (\Exception $e) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Error processing holiday',
                    'error' => $e->getMessage()
                ], 500);
            }

    }
    public function HolidayfileUpload(Request $request)
    {
        $file= $request->file('fileUpload')->getClientOriginalName();
        $ResortLocation =  $this->resort->resort->resort_id;

        $logo =  config('settings.Resort_Holidays').'/'.$ResortLocation;



        $validator = Validator::make($request->all(), [
            'fileUpload' => 'required|mimes:csv,xls,xlsx,ods,xlsb,xlt,xltx,xltm|max:2048',
        ], [
            'fileUpload.required' => 'Import file is required.',
            'fileUpload.mimes' => 'The file must be a file of type: csv, xls, xlsx, ods, xlsb, xlt, xltx, xltm.',
            'fileUpload.max' => 'The file may not be greater than 2MB.',
        ]);

        if ($validator->fails())
        {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
        }

        try{


                $file = $request->file('fileUpload')->store('imports');


                $check =  ResortHolidayImportJob::dispatch($file,$this->resort->resort_id);
                $response['success'] = true;
                $response['message'] ="Holiday  Datas Imported  successfully";


            }
            catch(\Exception $e)
            {
            \Log::emergency( "File: ".$e->getFile() );
            \Log::emergency( "Line: ".$e->getLine() );
            \Log::emergency( "Message: ".$e->getMessage() );

            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }


        return response()->json($response);

    }
    //color theme
    public function saveColorThemes(Request $request)
    {
        $resort_id = $this->resort->resort_id;

        // Validate the request data
        $request->validate([
            'themes' => 'required|array',
            'themes.*.name' => 'required|string',
            'themes.*.color' => 'required|string',
        ]);

        // Loop through themes to check for color uniqueness
        foreach ($request->themes as $theme) {
            // Check if the color already exists for the resort (excluding the current theme being updated)
            $existingTheme = ColorTheme::where('resort_id', $resort_id)
                ->where('color', $theme['color'])
                ->where('name', '!=', $theme['name'])  // Exclude current theme by name
                ->first();

            if ($existingTheme) {
                return response()->json([
                    'success' => false,
                    'message' => 'The color for "' . $theme['name'] . '" is already in use for this resort.Please select different color.'
                ], 400);
            }

            // Save or update the theme
            ColorTheme::updateOrCreate(
                [
                    'resort_id' => $resort_id,  // Matching criteria (condition to find existing record)
                    'name' => $theme['name'],   // Make sure to match by resort_id and name
                ],
                [
                    'color' => $theme['color'],  // Data to update or insert
                ]
            );
        }

        // Fetch all saved themes to display
        $savedThemes = ColorTheme::where('resort_id', $resort_id)->get();

        return response()->json([
            'success' => true,
            'message' => 'Themes saved successfully.',
            'themes' => $savedThemes
        ]);
    }
    public function delete(Request $request)
    {
        $themeId = $request->input('theme_id');
        // dd($themeId);
        // Check if theme exists and delete it
        $theme = ColorTheme::find($themeId);

        if ($theme) {
            $theme->delete();
            return response()->json(['success' => true, 'message' => 'Theme deleted successfully.']);
        }

        return response()->json(['success' => false, 'message' => 'Theme not found.']);
    }
    public function exportAttendance(Request $request)
    {

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

            // Return Excel download
        return Excel::download(new EmployeeAttendanceExport($startDate, $endDate), 'attendance.xlsx');
    }
}