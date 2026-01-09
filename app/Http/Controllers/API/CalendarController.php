<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ResortHoliday;
use App\Models\Events;
use App\Models\ChildEvents;
use App\Models\Employee;
use App\Models\ResortAdmin;
use App\Models\EmployeeLeave;
use App\Models\MonthlyCheckingModel;
use App\Models\ParentAttendace;
use App\Models\EmployeeTravelPass;
use App\Models\disciplinarySubmit;
use App\Models\ParentSurvey;
use App\Models\HousekeepingSchedules;
use App\Models\LearningProgram;
use App\Models\Incidents;
use App\Models\EmployeeItineraries;
use App\Models\ClinicAppointment;
use App\Models\EmployeeResignation;
use App\Models\PayrollAdvance;
use App\Models\EmployeePromotion;
use App\Models\PeopleSalaryIncrement;
use App\Models\PayrollRecoverySchedule;
use App\Models\PerformanceCycle;
use App\Models\PeformanceMeeting;
use App\Models\GrivanceSubmissionModel;
use App\Models\Payroll;
use App\Models\VisaEmployeeExpiryData;
use App\Models\EmployeeTransfer;
use App\Models\Vacancies;
use Illuminate\Support\Facades\Auth;
use Validator;
use DB;
use App\Helpers\Common;

class CalendarController extends Controller
{
    protected $user;
    protected $resort_id;
    protected $underEmp_id = [];

    public function __construct()
    {

        if (Auth::guard('api')->check()) {
            $this->user             =   Auth::guard('api')->user();
            $this->resort_id        =   $this->user->resort_id;
            $this->reporting_to     =   $this->user->GetEmployee->id;
            $this->underEmp_id      =   Common::getSubordinates($this->reporting_to);
        }
    }

    public function holidays(Request $request)
    {

        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try{
                                                
            $holidays    =    ResortHoliday::select([
                                                'resortholidays.id',
                                                'resortholidays.PublicHolidaydate  as date',
                                                'resortholidays.PublicHolidayName as title',
                                            ])       
                                            ->where('resort_id',$this->resort_id)                 
                                            ->get();

            $formattedHolidays  = $holidays->map(function ($holiday) {
                return [
                    'id'        => $holiday->id,
                    'title'     => $holiday->title,
                    'date'      => $holiday->date ? \Carbon\Carbon::parse($holiday->date)->format('j M') : null,
                    'day'       => $holiday->date ? \Carbon\Carbon::parse($holiday->date)->format('D') : null,
                ];
        });
        
          return response()->json([
              'message'   => 'Holidays fetched successfully',
              'data'      => $formattedHolidays,
            ], 200);  


        }catch(\Exception $e){
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function createEvent(Request $request)
    {

        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'title'                                             =>  'required',
            'date'                                              =>  'required|date_format:Y-m-d',
            'time'                                              =>  'required',
            'description'                                       =>  'nullable',
            'location'                                          =>  'nullable',
            'reminder_days'                                     =>  'required',
            'events_for'                                        =>  'required',
            'employees'                                         =>  'required|array',
            'employees.*'                                       =>  'exists:employees,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        DB::beginTransaction();

        try {
            $data = $validator->validated();
            $event = Events::create([
                'resort_id'                                     =>  $this->resort_id,
                'title'                                         =>  $data['title'],
                'date'                                          =>  $data['date'],
                'time'                                          =>  $data['time'],
                'description'                                   =>  $data['description'] ?? null,
                'location'                                      =>  $data['location'] ?? null,
                'reminder_days'                                 =>  $data['reminder_days'],
                'events_for'                                    =>  $data['events_for'],
                'status'                                        =>  'accept'
            ]);

            foreach ($request->employees as $key => $value) {
                ChildEvents::create([
                    'event_id'                                  =>  $event->id,
                    'resort_id'                                 =>  $this->resort_id,
                    'employee_id'                               =>  $value,
                ]);
                Common::sendMobileNotification($this->resort_id,2,null,null,$data['title'] .' Event','A new event has been created by ' . $this->user->first_name . ' ' . $this->user->last_name . ' on ' . $data['date'] .  ' at ' . $data['time'] . '.','Calendar',[$value],null);
            }

            DB::commit();
            return response()->json([
                'message'                                       => 'Event created successfully',
                'data'                                          => $event,
            ], 201);


        } catch(\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function eventsCalender(Request $request)
    {

        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'year'                    => 'date_format:Y', 
            'month'                   => 'date_format:m',   
            'filter'                  => 'in:weekly,monthly,yearly',
            'type'                    => 'in:Organization Event,My Event,Public Holiday'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }
      
        $user                                                   =   Auth::guard('api')->user();
        $employee                                               =   $user->GetEmployee;
        $emp_id                                                 =   $employee->id;
      
        try{
            $data                                               =   $validator->validated();

             $queries = [
                'leaves'                                        =>  EmployeeLeave::query()->where('resort_id', $this->resort_id)
                                                                        ->where('status', 'Approved')
                                                                        ->where('emp_id',    $emp_id),
                'holidays'                                      =>  ResortHoliday::select('id', 'PublicHolidaydate as date', 'PublicHolidayName as title')
                                                                        ->where('resort_id', $this->resort_id),
                'attendance'                                    =>  ParentAttendace::query()->where('resort_id', $this->resort_id)
                                                                        ->where('Emp_id',$emp_id)
                                                                        ->where('status', 'DayOff'),
                'travel'                                        =>  EmployeeTravelPass::query()->where('resort_id', $this->resort_id)
                                                                        ->where('employee_id', $emp_id)
                                                                        ->where('status', 'Approved'),
                'monthly'                                       =>  MonthlyCheckingModel::query()->where('resort_id', $this->resort_id)
                                                                        ->where('emp_id', $emp_id)
                                                                        ->where('status', 'Confirm'),
                'disciplinary'                                  =>  disciplinarySubmit::query()->where('resort_id', $this->resort_id)
                                                                        ->where('Employee_id', $emp_id),
                'survey'                                        =>  ParentSurvey::join('survey_employees as se', 'se.Parent_survey_id', '=', 'parent_surveys.id')
                                                                        ->where('parent_surveys.resort_id', $this->resort_id)
                                                                        ->where('se.Emp_id', $emp_id)
                                                                        ->where('parent_surveys.Status', 'OnGoing'),
                                                                        
                'learning'                                      =>  LearningProgram::join("training_schedules as ts", "ts.training_id", "=", 'learning_programs.id')
                                                                        ->join('training_participants as tp', 'tp.training_schedule_id', '=', 'ts.id')
                                                                        ->where('ts.resort_id', $this->resort_id)
                                                                        ->where('tp.employee_id', $emp_id)
                                                                        ->where('ts.status', 'Ongoing'),

                'events'                                        =>  Events::join('child_events as ce', 'ce.event_id', '=', 'events.id')
                                                                    ->where('events.status', '=', 'accept')
                                                                    ->where('events.resort_id', $this->resort_id)
                                                                    ->where('ce.employee_id', $emp_id),

                'employee_probation'                            =>  Employee::query()->where('resort_id', $this->resort_id)
                                                                    ->where('employment_type','Probationary')
                                                                    ->where('probation_status','Active')
                                                                    ->where('status', 'Active')
                                                                    ->where('id', $emp_id),

                'incidents_invest_meetings'                     =>  Incidents::join('incidents_investigation_meetings as inm', 'inm.incident_id', '=', 'incidents.id')
                                                                        ->where('incidents.resort_id', $this->resort_id)
                                                                        ->where('incidents.reporter_id', $emp_id), 
                
                'housekeeping_schedule'                         =>  HousekeepingSchedules::join('child_housekeeping_schedules as chs', 'chs.housekeeping_id', '=', 'housekeeping_schedules.id')
                                                                            ->where('chs.resort_id', $this->resort_id)
                                                                            ->where('chs.ApprovedBy', $emp_id),

                'staff_airport_pick_up'                         =>  EmployeeItineraries::where('resort_id', $this->resort_id)
                                                                        ->where('pickup_employee_id', $emp_id),

                'staff_medical_check_up'                        =>  EmployeeItineraries::where('resort_id', $this->resort_id)
                                                                        ->where('accompany_medical_employee_id', $emp_id),

                'clinic_appointment'                            =>  ClinicAppointment::where('resort_id', $this->resort_id)
                                                                        ->where('employee_id', $emp_id),

                'employee_resignation'                          =>  EmployeeResignation::where('resort_id', $this->resort_id)
                                                                        ->where('employee_id', $emp_id)->where('status', 'Approved'),

                'loan_salary_track'                             =>  PayrollAdvance::join('payroll_recovery_schedule as prs', 'prs.payroll_advance_id', '=', 'payroll_advance.id')
                                                                        ->where('payroll_advance.resort_id', $this->resort_id)
                                                                        ->where('prs.employee_id', $emp_id),

                'itinerary_meeting'                             =>   EmployeeItineraries::join('employee_itineraries_meeting as eim', 'eim.employee_itinerary_id', '=', 'employee_itineraries.id')
                                                                        ->where('employee_itineraries.resort_id', $this->resort_id)
                                                                        ->where('employee_itineraries.employee_id', $emp_id),

                'employment_contract_date'                      =>  Employee::query()->where('resort_id', $this->resort_id)
                                                                        ->where('id', $emp_id)
                                                                        ->where('status', 'Active'),

                'employee_visa_expiry'                          =>  Employee::with(['VisaRenewal.VisaChild'])
                                                                    ->where("nationality", '!=', "Maldivian")
                                                                    ->where('id', $emp_id)
                                                                    ->where('resort_id', $this->resort_id),

                'employee_xpat_insurance_expiry'                =>  Employee::with(['EmployeeInsurance.InsuranceChild'])
                                                                    ->where("nationality", '!=', "Maldivian")
                                                                    ->where('id', $emp_id)
                                                                    ->where('resort_id', $this->resort_id),

                'work_permit_medical_expiry'                    =>  Employee::with(['WorkPermitMedicalRenewal.WorkPermitMedicalRenewalChild'])
                                                                    ->where("nationality", '!=', "Maldivian")
                                                                    ->where('id', $emp_id)
                                                                    ->where('resort_id', $this->resort_id),

                                                                    
                'work_permit_expiry'                            =>  Employee::with(['WorkPermit'])
                                                                    ->where("nationality", '!=', "Maldivian")
                                                                    ->where('id', $emp_id)
                                                                    ->where('resort_id', $this->resort_id)
                                                                    ->whereHas('WorkPermit', function ($q) {
                                                                        $q->where('Status', 'Unpaid');
                                                                    }),
                                                                    
                'quota_slot_expiry'                             =>   Employee::with(['QuotaSlotRenewal'])
                                                                    ->where("nationality", '!=', "Maldivian")
                                                                    ->where('id', $emp_id)
                                                                    ->where('resort_id', $this->resort_id)
                                                                    ->whereHas('WorkPermit', function ($q) {
                                                                        $q->where('Status', 'Unpaid');
                                                                    }),

                'resignation_meeting'                           =>   EmployeeResignation::join('resignation_meeting_schedule as rms', 'rms.resignationId', '=', 'employee_resignation.id')
                                                                        ->where('employee_resignation.resort_id', $this->resort_id)
                                                                        ->where('employee_resignation.employee_id', $emp_id),

                'duty_roster'                                   =>  ParentAttendace::where('resort_id', $this->resort_id)
                                                                        ->where('Emp_id', $emp_id),

                'passport_expiry'                               =>  VisaEmployeeExpiryData::where('resort_id', $this->resort_id)
                                                                        ->where('DocumentName','Passport_Copy')
                                                                        ->where('employee_id', $emp_id),

                'exit_clearance_form'                           =>   EmployeeResignation::join('exit_clearance_form_assignments as ecfa', 'ecfa.emp_resignation_id', '=', 'employee_resignation.id')
                                                                        ->join('exit_clearance_form as ecf', 'ecf.id', '=', 'ecfa.form_id')
                                                                        ->where('employee_resignation.resort_id', $this->resort_id)
                                                                        ->where('employee_resignation.employee_id', $emp_id),
            ];
  
            if($request->filled('month') && $request->filled('year') && $request->filled('type') && $request->filled('filter')) {
                $month                                      =   $request->month;
                $year                                       =   $request->year;

                $queries['leaves']                          ->where(function($q) use ($month, $year) {
                    $q                                      ->whereMonth('from_date', $month)
                                                            ->whereYear('from_date',  $year)
                                                            ->orWhere(function($q2) use ($month, $year) {
                    $q2                                     ->whereMonth('to_date', $month)
                                                            ->whereYear('to_date',  $year);
                    });
                });
              
               $queries['attendance']                       ->where(function($q) use ($month, $year) {
                    $q                                      ->whereMonth('date', $month)
                                                            ->whereYear('date',  $year);
                });

                $queries['travel']                          ->where(function($q) use ($month, $year) {
                    $q                                      ->whereMonth('departure_date', $month)
                                                            ->whereYear('departure_date',  $year)
                                                            ->orWhere(function($q2) use ($month, $year) {
                    $q2                                     ->whereMonth('arrival_date', $month)
                                                            ->whereYear('arrival_date',  $year);
                    });
                });

                $queries['holidays']                        ->whereMonth('PublicHolidaydate', (int)$data['month'])
                                                            ->whereYear('PublicHolidaydate', $data['year']);

                $queries['disciplinary']                    ->whereMonth('Expiry_date', (int)$data['month'])
                                                            ->whereYear('Expiry_date', $data['year']);

                $queries['learning']                        ->where(function($q) use ($month, $year) {
                    $q                                      ->where(function($q2) use ($month, $year) {
                    $q2                                     ->whereMonth('start_date', $month)
                                                            ->whereYear('start_date',  $year);
                    })                                      ->orWhere(function($q3) use ($month, $year) {
                    $q3                                     ->whereMonth('end_date', $month)
                                                            ->whereYear('end_date',  $year);
                    });
                });
                
                $queries['survey']                          ->where(function($q) use ($month, $year) {
                    $q                                      ->where(function($q2) use ($month, $year) {
                    $q2                                     ->whereMonth('Start_date', $month)
                                                            ->whereYear('Start_date',  $year);
                    })                                      ->orWhere(function($q3) use ($month, $year) {
                    $q3                                     ->whereMonth('End_date', $month)
                                                            ->whereYear('End_date',  $year);
                    });
                });

                $queries['events']                          ->whereMonth('date', $month)
                                                            ->whereYear('date', $year);

                $queries['employee_probation']              ->whereMonth('probation_end_date', $month)
                                                            ->whereYear('probation_end_date', $year);

                $queries['monthly']                         ->whereMonth('date_discussion', $month)
                                                            ->whereYear('date_discussion', $year);
                                                            
                $queries['incidents_invest_meetings']       ->whereMonth('meeting_date', $month)
                                                            ->whereYear('meeting_date', $year);
                                                            
                $queries['staff_airport_pick_up']           ->whereMonth('arrival_date', $month)
                                                            ->whereYear('arrival_date', $year);

                $queries['staff_medical_check_up']          ->whereMonth('medical_date', $month)
                                                            ->whereYear('medical_date', $year);

                $queries['clinic_appointment']              ->whereMonth('date', $month)
                                                            ->whereYear('date', $year);

                $queries['employee_resignation']            ->whereMonth('last_working_day', $month)
                                                            ->whereYear('last_working_day', $year);

                $queries['loan_salary_track']               ->whereMonth('prs.repayment_date', $month)
                                                            ->whereYear('prs.repayment_date', $year);

                $queries['itinerary_meeting']               ->whereMonth('eim.meeting_date', $month)
                                                            ->whereYear('eim.meeting_date', $year);

                $queries['employment_contract_date']        ->whereMonth('contract_end_date', $month)
                                                            ->whereYear('contract_end_date', $year);

                $queries['employee_visa_expiry']                ->whereHas('VisaRenewal', function ($q) use ($month, $year) {
                $q                                              ->whereMonth('end_date', $month)
                                                                ->whereYear('end_date', $year);
                });

                $queries['employee_xpat_insurance_expiry']      ->whereHas('EmployeeInsurance', function ($q) use ($month, $year) {
                $q                                              ->whereMonth('insurance_end_date', $month)
                                                                ->whereYear('insurance_end_date', $year);
                });

                $queries['work_permit_medical_expiry']          ->whereHas('WorkPermitMedicalRenewal', function ($q) use ($month, $year) {
                $q                                              ->whereMonth('end_date', $month)
                                                                ->whereYear('end_date', $year);
                });

                $queries['work_permit_expiry']                  ->whereHas('WorkPermit', function ($q) use ($month, $year) {
                $q                                              ->whereMonth('Due_Date', $month)
                                                                ->whereYear('Due_Date', $year);
                });

                $queries['quota_slot_expiry']                   ->whereHas('QuotaSlotRenewal', function ($q) use ($month, $year) {
                $q                                              ->whereMonth('Due_Date', $month)
                                                                ->whereYear('Due_Date', $year);
                });

                $queries['housekeeping_schedule']               ->whereMonth('chs.date', $month)
                                                                ->whereYear('chs.date', $year);

                $queries['resignation_meeting']                 ->whereMonth('rms.meeting_date', $month)
                                                                ->whereYear('rms.meeting_date', $year);

                $queries['duty_roster']                         ->whereMonth('date', $month)
                                                                ->whereYear('date', $year);

                $queries['passport_expiry']                     ->whereRaw("
                                                                    MONTH(STR_TO_DATE(
                                                                        JSON_UNQUOTE(JSON_EXTRACT(Ai_extracted_data, '$.extracted_fields.\"Date of Expiry\"')),
                                                                        '%d%b%Y'
                                                                    )) = ?
                                                                    AND
                                                                    YEAR(STR_TO_DATE(
                                                                        JSON_UNQUOTE(JSON_EXTRACT(Ai_extracted_data, '$.extracted_fields.\"Date of Expiry\"')),
                                                                        '%d%b%Y'
                                                                    )) = ?
                                                            ", [$month, $year]);
                                                            
                $queries['exit_clearance_form']                 ->whereMonth('ecfa.deadline_date', $month)
                                                                ->whereYear('ecfa.deadline_date', $year);

            } elseif ($request->filled('month') && $request->filled('year')) {
                $month                                      =   $request->month;
                $year                                       =   $request->year;

                $queries['leaves']                          ->where(function($q) use ($month, $year) {
                    $q                                      ->whereMonth('from_date', $month)
                                                            ->whereYear('from_date',  $year)
                                                            ->orWhere(function($q2) use ($month, $year) {
                    $q2                                     ->whereMonth('to_date', $month)
                                                            ->whereYear('to_date',  $year);
                    });
                });
              
                $queries['attendance']                       ->where(function($q) use ($month, $year) {
                    $q                                      ->whereMonth('date', $month)
                                                            ->whereYear('date',  $year);
                });

                $queries['travel']                          ->where(function($q) use ($month, $year) {
                    $q                                      ->whereMonth('departure_date', $month)
                                                            ->whereYear('departure_date',  $year)
                                                            ->orWhere(function($q2) use ($month, $year) {
                    $q2                                     ->whereMonth('arrival_date', $month)
                                                            ->whereYear('arrival_date',  $year);
                    });
                });

                $queries['holidays']                        ->whereMonth('PublicHolidaydate', $month)
                                                            ->whereYear('PublicHolidaydate', $year);

                $queries['disciplinary']                    ->whereMonth('Expiry_date', $month)
                                                            ->whereYear('Expiry_date', $year);

                $queries['learning']                        ->where(function($q) use ($month, $year) {
                    $q                                      ->where(function($q2) use ($month, $year) {
                    $q2                                     ->whereMonth('start_date', $month)
                                                            ->whereYear('start_date',  $year);
                    })                                      ->orWhere(function($q3) use ($month, $year) {
                    $q3                                     ->whereMonth('end_date', $month)
                                                            ->whereYear('end_date',  $year);
                    });
                });
                
                $queries['survey']                          ->where(function($q) use ($month, $year) {
                    $q                                      ->where(function($q2) use ($month, $year) {
                    $q2                                     ->whereMonth('Start_date', $month)
                                                            ->whereYear('Start_date',  $year);
                    })                                      ->orWhere(function($q3) use ($month, $year) {
                    $q3                                     ->whereMonth('End_date', $month)
                                                            ->whereYear('End_date',  $year);
                    });
                });

                $queries['events']                          ->whereMonth('date', $month)
                                                            ->whereYear('date', $year);

                $queries['employee_probation']              ->whereMonth('probation_end_date', $month)
                                                            ->whereYear('probation_end_date', $year);

                $queries['monthly']                         ->whereMonth('date_discussion', $month)
                                                            ->whereYear('date_discussion', $year);

                $queries['incidents_invest_meetings']       ->whereMonth('meeting_date', $month)
                                                            ->whereYear('meeting_date', $year);

                $queries['staff_airport_pick_up']           ->whereMonth('arrival_date', $month)
                                                            ->whereYear('arrival_date', $year);

                $queries['staff_medical_check_up']          ->whereMonth('medical_date', $month)
                                                            ->whereYear('medical_date', $year);

                $queries['clinic_appointment']              ->whereMonth('date', $month)
                                                            ->whereYear('date', $year);

                $queries['employee_resignation']            ->whereMonth('last_working_day', $month)
                                                            ->whereYear('last_working_day', $year);

                $queries['loan_salary_track']               ->whereMonth('prs.repayment_date', $month)
                                                            ->whereYear('prs.repayment_date', $year);

                $queries['itinerary_meeting']               ->whereMonth('eim.meeting_date', $month)
                                                            ->whereYear('eim.meeting_date', $year);

                $queries['employment_contract_date']        ->whereMonth('contract_end_date', $month)
                                                            ->whereYear('contract_end_date', $year);
                                                        
                $queries['employee_visa_expiry']            ->whereHas('VisaRenewal', function ($q) use ($month, $year) {
                $q                                          ->whereMonth('end_date', $month)
                                                            ->whereYear('end_date', $year);
                });

                $queries['employee_xpat_insurance_expiry']  ->whereHas('EmployeeInsurance', function ($q) use ($month, $year) {
                $q                                          ->whereMonth('insurance_end_date', $month)
                                                            ->whereYear('insurance_end_date', $year);
                });

                $queries['work_permit_medical_expiry']      ->whereHas('WorkPermitMedicalRenewal', function ($q) use ($month, $year) {
                $q                                          ->whereMonth('end_date', $month)
                                                            ->whereYear('end_date', $year);
                });

                $queries['work_permit_expiry']              ->whereHas('WorkPermit', function ($q) use ($month, $year) {
                $q                                          ->whereMonth('Due_Date', $month)
                                                            ->whereYear('Due_Date', $year);
                });

                $queries['quota_slot_expiry']               ->whereHas('QuotaSlotRenewal', function ($q) use ($month, $year) {
                $q                                          ->whereMonth('Due_Date', $month)
                                                            ->whereYear('Due_Date', $year);
                });

                $queries['housekeeping_schedule']           ->whereMonth('chs.date', $month)
                                                            ->whereYear('chs.date', $year);

                $queries['resignation_meeting']             ->whereMonth('rms.meeting_date', $month)
                                                            ->whereYear('rms.meeting_date', $year);

                $queries['duty_roster']                     ->whereMonth('date', $month)
                                                            ->whereYear('date', $year);

                $queries['passport_expiry']                  ->whereRaw("
                                                                MONTH(STR_TO_DATE(
                                                                    JSON_UNQUOTE(JSON_EXTRACT(Ai_extracted_data, '$.extracted_fields.\"Date of Expiry\"')),
                                                                    '%d%b%Y'
                                                                )) = ?
                                                                    AND
                                                                    YEAR(STR_TO_DATE(
                                                                        JSON_UNQUOTE(JSON_EXTRACT(Ai_extracted_data, '$.extracted_fields.\"Date of Expiry\"')),
                                                                        '%d%b%Y'
                                                                    )) = ?
                                                            ", [$month,$year]);

                $queries['exit_clearance_form']                 ->whereMonth('ecfa.deadline_date', $month)
                                                                ->whereYear('ecfa.deadline_date', $year);

            } else if($request->filled('filter')) {

                [$start, $end]                              = $this->getDateRange($data['filter']);

                $queries['leaves']                          ->where(function($q) use ($start, $end) {
                $q                                          ->whereBetween('from_date', [$start, $end])
                                                            ->orWhereBetween('to_date', [$start, $end]);
                });
                $queries['attendance']                      ->whereBetween('date', [$start, $end]);

                $queries['travel']                          ->where(function($q) use ($start, $end) {
                $q                                          ->whereBetween('departure_date', [$start, $end])
                                                            ->orWhereBetween('arrival_date', [$start, $end]);
                });

                $queries['holidays']                        ->whereBetween('PublicHolidaydate', [$start, $end]);
                $queries['disciplinary']                    ->whereBetween('Expiry_date',    [$start, $end]);

                $queries['learning']->where(function($q) use ($start, $end) {
                    $q                                      ->whereBetween('start_date',        [$start, $end])
                                                            ->orWhereBetween('end_date',        [$start, $end]);
                    });

                $queries['survey']->where(function($q) use ($start, $end) {
                    $q                                          ->whereBetween('start_date',    [$start, $end])
                                                                ->orWhereBetween('end_date',    [$start, $end]);
                });

                $queries['events']                              ->whereBetween('date',          [$start, $end]);
                $queries['employee_probation']                  ->whereBetween('probation_end_date', [$start, $end]);
                $queries['monthly']                             ->whereBetween('date_discussion',   [$start, $end]);
                $queries['incidents_invest_meetings']           ->whereBetween('meeting_date',  [$start, $end]);
                $queries['staff_airport_pick_up']               ->whereBetween('arrival_date',  [$start, $end]);
                $queries['staff_medical_check_up']              ->whereBetween('medical_date',  [$start, $end]);
                $queries['clinic_appointment']                  ->whereBetween('date',          [$start, $end]);
                $queries['employee_resignation']                ->whereBetween('last_working_day', [$start, $end]);
                $queries['loan_salary_track']                   ->whereBetween('prs.repayment_date', [$start, $end]);
                $queries['itinerary_meeting']                   ->whereBetween('eim.meeting_date', [$start, $end]);
                $queries['employment_contract_date']            ->whereBetween('contract_end_date', [$start, $end]);
                $queries['employee_visa_expiry']                ->whereHas('VisaRenewal', function ($q) use ($start, $end) {
                    $q                                          ->whereBetween('end_date', [$start, $end]);
                });
                $queries['employee_xpat_insurance_expiry']      ->whereHas('EmployeeInsurance', function ($q) use ($start, $end) {
                $q                                              ->whereBetween('insurance_end_date', [$start, $end]);
                });

                $queries['work_permit_medical_expiry']          ->whereHas('WorkPermitMedicalRenewal', function ($q) use ($start, $end) {
                $q                                              ->whereBetween('end_date', [$start, $end]);
                });

                $queries['work_permit_expiry']                  ->whereHas('WorkPermit', function ($q) use ($start, $end) {
                $q                                              ->whereBetween('Due_Date', [$start, $end]);
                });

                $queries['quota_slot_expiry']                   ->whereHas('QuotaSlotRenewal', function ($q) use ($start, $end) {
                $q                                              ->whereBetween('Due_Date', [$start, $end]);
                });

                $queries['housekeeping_schedule']               ->where(function($q) use ($start, $end) {
                    $q                                          ->whereBetween('chs.date',    [$start, $end])
                                                                ->orWhereBetween('chs.date',    [$start, $end]);
                });

                $queries['resignation_meeting']                 ->where(function($q) use ($start, $end) {
                    $q                                          ->whereBetween('rms.meeting_date',    [$start, $end])
                                                                ->orWhereBetween('rms.meeting_date',    [$start, $end]);
                });

                $queries['duty_roster']                         ->where(function($q) use ($start, $end) {
                    $q                                          ->whereBetween('date',    [$start, $end])
                                                                ->orWhereBetween('date',    [$start, $end]);
                });
               
                // For special cases like JSON fields:
                $queries['passport_expiry']                     ->whereRaw("
                                                                    STR_TO_DATE(
                                                                        JSON_UNQUOTE(JSON_EXTRACT(Ai_extracted_data, '$.extracted_fields.\"Date of Expiry\"')),
                                                                        '%d%b%Y'
                                                                    ) BETWEEN ? AND ?", 
                                                                    [$start->format('Y-m-d'), $end->format('Y-m-d')]
                                                                );

                $queries['exit_clearance_form']                 ->where(function($q) use ($start, $end) {
                    $q                                          ->whereBetween('ecfa.deadline_date',    [$start, $end])
                                                                ->orWhereBetween('ecfa.deadline_date',    [$start, $end]);
                });
            }
    
                $leaves                                     =   $queries['leaves']->get();
                $holidays                                   =   $queries['holidays']->get();
                $attendance                                 =   $queries['attendance']->get();
                $travel                                     =   $queries['travel']->get();
                $disciplinary                               =   $queries['disciplinary']->get();
                $monthly                                    =   $queries['monthly']->get();
                $learning                                   =   $queries['learning']->get();
                $survey                                     =   $queries['survey']->get();
                $events                                     =   $queries['events']->get();
                $employee_probation                         =   $queries['employee_probation']->get();
                $incidents_invest_meetings                  =   $queries['incidents_invest_meetings']->get();
                $staff_airport_pick_up                      =   $queries['staff_airport_pick_up']->get();
                $staff_medical_check_up                     =   $queries['staff_medical_check_up']->get();
                $clinic_appointment                         =   $queries['clinic_appointment']->get();
                $employee_resignation                       =   $queries['employee_resignation']->get();
                $loan_salary_track                          =   $queries['loan_salary_track']->get();
                $itinerary_meeting                          =   $queries['itinerary_meeting']->get();
                $employment_contract_date                   =   $queries['employment_contract_date']->get();
                $employee_visa_expiry                       =   $queries['employee_visa_expiry']->get();
                $employee_xpat_insurance_expiry             =   $queries['employee_xpat_insurance_expiry']->get();
                $work_permit_medical_expiry                 =   $queries['work_permit_medical_expiry']->get();
                $work_permit_expiry                         =   $queries['work_permit_expiry']->get();
                $quota_slot_expiry                          =   $queries['quota_slot_expiry']->get();
                $housekeeping_schedule                      =   $queries['housekeeping_schedule']->get();
                $resignation_meeting                        =   $queries['resignation_meeting']->get();
                $duty_roster                                =   $queries['duty_roster']->get();
                $passport_expiry                            =   $queries['passport_expiry']->get();
                $exit_clearance_form                        =   $queries['exit_clearance_form']->get();

            $merged = collect([

            ...$leaves->map(fn($item)                           => $this->employeeMapdata('My Event','Leave Departure','', $item->from_date, '', '')),
            ...$leaves->map(fn($item)                           => $this->employeeMapdata('My Event','Leave Arrival','', $item->to_date, '', '')),
            ...$holidays->map(fn($item)                         => $this->employeeMapdata('Public Holiday',$item->title,'', $item->date, '', '')),
            ...$attendance->map(fn($item)                       => $this->employeeMapdata('My Event','Day Off','', $item->date, '', '', '')),
            ...$travel->map(function($item) {
                 if($item->departure_date && $item->departure_time) {
                    return $this->employeeMapdata('My Event','Island Pass Departure','', $item->departure_date, $item->departure_time, '');
                 }
                })->filter(),
            ...$travel->map(function($item) {
                if($item->arrival_date && $item->arrival_time) {
                    return $this->employeeMapdata('My Event','Island Pass Arrival','', $item->arrival_date, '', $item->arrival_time);
                }
            })->filter(),
            ...$events->map(fn($item)                           => $this->employeeMapdata('Organization Event',$item->title, $item->location, $item->date, $item->time, '')),
            ...$disciplinary->map(fn($item)                     => $this->employeeMapdata('My Event','Disciplinary Expiry', '', $item->Expiry_date, '', '')),
            ...$learning->map(fn($item)                         => $this->employeeMapdata('My Event',$item->name, '', $item->end_date, '', '')),
            ...$survey->map(fn($item)                           => $this->employeeMapdata('My Event',$item->Surevey_title, '', $item->End_date, '', '')),
            ...$employee_probation->map(fn($item)               => $this->employeeMapdata('My Event','Probation expiry', '', $item->probation_end_date, '', '')),
            ...$monthly->map(function($item) {
                $rankConfig                                     =   config('settings.Position_Rank');
                $date                                           =   \Carbon\Carbon::parse($item->date_discussion);
                $admin                                          =   ResortAdmin::find($item->created_by);
                $rank                                           =   $admin  ? ($rankConfig[$admin->rank] ?? '')."{$admin->first_name}" : 'Staff';
                return $this->employeeMapdata('My Event',"Meeting with {$rank}",'', $item->date_discussion,$item->start_time,$item->end_time);
            }),
            ...$incidents_invest_meetings->map(fn($item)        => $this->employeeMapdata('My Event','Incident Investigation Meeting', '', $item->meeting_date, '', '')),
            ...$staff_airport_pick_up->map(fn($item)            => $this->employeeMapdata('My Event','Airport Pick Up', '', $item->arrival_date, '', '')),
            ...$staff_medical_check_up->map(fn($item)           => $this->employeeMapdata('My Event','Medical Check Up', $item->medical_center_name, $item->medical_date, '', '')),
            ...$clinic_appointment->map(fn($item)               => $this->employeeMapdata('My Event','Clinic Appointment','', $item->date, '', '')),
            ...$employee_resignation->map(fn($item)             => $this->employeeMapdata('My Event','Resignation','', $item->last_working_day, '', '')),
            ...$loan_salary_track->map(fn($item)                => $this->employeeMapdata('My Event','Loan & Salary Deduction','', $item->repayment_date, '', '')),
            ...$itinerary_meeting->flatMap(function($item) {
                $participantIds                                 =   array_filter(explode(',', $item->meeting_participant_ids));
                    return collect($participantIds)->map(function($id) use ($item) {

                        $empNames                               =   $this->employeeDetails($id);
                         if ($empNames) {
                        return $this->employeeMapdata('My Event',"Itinerary Meeting With {$empNames->first_name} {$empNames->last_name}",'',$item->meeting_date,'','');
                         }
                    });
            }),
            ...$employment_contract_date->map(fn($item)                => $this->employeeMapdata('My Event','Employment contract Expiry','', $item->contract_end_date, '', '')),
            ...$employee_visa_expiry->map(fn($emp)              => $this->employeeMapdata('My Event','Visa Expiry','', $emp->VisaRenewal->end_date,'','')),
            ...$employee_xpat_insurance_expiry->map(fn($emp)    => $this->employeeMapdata('My Event','Xpat Insurance expiry date','',$emp->EmployeeInsurance->insurance_end_date,'','')),
            ...$work_permit_medical_expiry->map(fn($emp)        => $this->employeeMapdata('My Event','Work Permit Medical expiry date ','',$emp->WorkPermitMedicalRenewal->end_date,'','')),
            ...$work_permit_expiry->flatMap(function ($emp) {
                    return collect($emp->WorkPermit)->map(function ($permit) use ($emp) {
                        return $this->employeeMapdata('My Event','Work Permit expiry date','',$permit->Due_Date,'','');
                    });
                }),

            ...$quota_slot_expiry->flatMap(function ($emp) {
                    return collect($emp->QuotaSlotRenewal)->map(function ($slot) use ($emp) {
                        return $this->employeeMapdata('My Event','Quota Slot expiry date','',$slot->Due_Date,'','');
                    });
                }),

            ...$housekeeping_schedule->map(fn($item)                => $this->employeeMapdata('My Event','Housekeeping Schedule','', $item->date, '', '')),
            ...$resignation_meeting->map(fn($item)                  => $this->employeeMapdata('My Event','Resignation '.$item->title,'', $item->meeting_date, '', '')),
            ...$duty_roster->map(fn($item)                          => $this->employeeMapdata('My Event','Duty Roster','',$item->date, '', '')),
            
            ...$passport_expiry->map(function($item) {
                    // Extract the date from JSON
                    $expiryRaw                                      =   json_decode($item->Ai_extracted_data, true)['extracted_fields']['Date of Expiry'] ?? null;
                    $expiryDate                                     =   $expiryRaw ? \Carbon\Carbon::createFromFormat('dMY', str_replace(' ', '', $expiryRaw))->format('Y-m-d') : null;
                    return $this->employeeMapdata('My Event', 'Passport Expiry', '', $expiryDate, '', '');
                }),

            ...$exit_clearance_form->map(fn($item)                  =>  $this->employeeMapdata('My Event',$item->form_name.' Form','',$item->deadline_date, '', '')),
         ])->sortBy('date')->values();

            if (!empty($data['type'])) {
                $typeFilter     =   strtolower($data['type']);
                 $merged        =   $merged->filter(function ($item) use ($typeFilter) {
                                        return is_array($item)
                                            && isset($item['type'])
                                            && strtolower($item['type']) === $typeFilter;
                                        })
                                        ->values();
            }

            if ($merged->isEmpty()) {
                return response()->json(['success' => false,'message' =>  'No events found',], 200);
            }
            
            return response()->json([
                'success'                                   =>  true,
                'message'                                   =>  'Events fetched successfully',
                'employee_calendar_data'                    =>  $merged,
            ], 200);
            
        } catch(\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }

    }

    public function managerDashboard(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'year'                                          =>  'date_format:Y', 
            'month'                                         =>  'date_format:m',   
            'filter'                                        =>  'in:weekly,monthly,yearly',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        $rankConfig                                         =   config('settings.Position_Rank');
        $currentRank                                        =   $this->user->GetEmployee->rank ?? null;
        $availableRank                                      =   $rankConfig[$currentRank] ?? '';
        $isHOD                                              =   ($availableRank === "HOD");
        $isHR                                               =   ($availableRank === "HR");

        try{
            $data                                           =   $validator->validated();
            
             $queries = [
                'leaves'                                        =>  EmployeeLeave::query()->where('resort_id', $this->resort_id)->where('status', 'Approved'),

                'holidays'                                      =>  ResortHoliday::select('id', 'PublicHolidaydate as date', 'PublicHolidayName as title')->where('resort_id', $this->resort_id),

                'attendance'                                    =>  ParentAttendace::join('employees as e', 'e.id', '=', 'parent_attendaces.Emp_id')
                                                                    ->where('parent_attendaces.resort_id', $this->resort_id)
                                                                    ->where('parent_attendaces.status', 'DayOff')
                                                                    ->where('e.status', 'Active'),

                'travel'                                        =>  EmployeeTravelPass::query()->where('resort_id', $this->resort_id)->where('status', 'Approved'),

                'monthly'                                       =>  MonthlyCheckingModel::query()->where('resort_id', $this->resort_id)->where('status', 'Confirm'),

                'disciplinary'                                  =>  disciplinarySubmit::query()->where('resort_id', $this->resort_id),
                
                'survey'                                        =>  ParentSurvey::join('survey_employees as se', 'se.Parent_survey_id', '=', 'parent_surveys.id')
                                                                        ->where('parent_surveys.resort_id', $this->resort_id)
                                                                        ->where('parent_surveys.Status', 'OnGoing'),

                'housekeeping'                                  =>  HousekeepingSchedules::where('resort_id', $this->resort_id)
                                                                    ->where('status', 'In-Progress'),

                'learning'                                      =>  LearningProgram::join("training_schedules as ts", "ts.training_id", "=", 'learning_programs.id')
                                                                        ->join('training_participants as tp', 'tp.training_schedule_id', '=', 'ts.id')
                                                                        ->where('ts.resort_id', $this->resort_id)
                                                                        ->where('ts.status', 'Ongoing'),

                'events'                                        =>  Events::join('child_events as ce', 'ce.event_id', '=', 'events.id')
                                                                        ->join('employees as e', 'e.id', '=', 'ce.employee_id')
                                                                        ->where('e.status', 'Active')
                                                                        ->where('events.status', '=', 'accept')
                                                                        ->where('events.resort_id', $this->resort_id),
                
                'employee_probation'                            =>  Employee::query()->where('resort_id', $this->resort_id)
                                                                        ->where('employment_type','Probationary')
                                                                        ->where('probation_status','Active')
                                                                        ->where('status', 'Active'),
                                                                        
                'employee_dob'                                  =>  Employee::query()->where('resort_id', $this->resort_id)
                                                                        ->where('status', 'Active'),

                'incidents_resolution_date'                     =>  Incidents::join('incidents_investigation as inc', 'inc.incident_id', '=', 'incidents.id')
                                                                        ->where('incidents.resort_id', $this->resort_id),  
                                                                    
                'incidents_invest_meetings'                     =>  Incidents::join('incidents_investigation_meetings as inm', 'inm.incident_id', '=', 'incidents.id')
                                                                        ->where('incidents.resort_id', $this->resort_id),

                'employee_resignation'                          =>   EmployeeResignation::where('resort_id', $this->resort_id)
                                                                       ->where('status', 'Approved'),

                'resignation_meeting'                           =>   EmployeeResignation::join('resignation_meeting_schedule as rms', 'rms.resignationId', '=', 'employee_resignation.id')
                                                                        ->where('employee_resignation.resort_id', $this->resort_id),

                'employee_itineraries'                          =>   EmployeeItineraries::join('resort_transportations as rt', 'rt.id', '=', 'employee_itineraries.resort_transportation_id')
                                                                        ->where('employee_itineraries.resort_id', $this->resort_id),

                'itinerary_meeting'                             =>   EmployeeItineraries::join('employee_itineraries_meeting as eim', 'eim.employee_itinerary_id', '=', 'employee_itineraries.id')
                                                                        ->where('employee_itineraries.resort_id', $this->resort_id),

                'promotion_date'                                =>   EmployeePromotion::where('resort_id', $this->resort_id)
                                                                       ->where('status', 'Approved'),

                'salary_increment'                              =>   PeopleSalaryIncrement::where('resort_id', $this->resort_id)
                                                                       ->where('status', 'Approved'),
                
                'employment_contract_date'                      =>  Employee::where('resort_id', $this->resort_id)
                                                                        ->where('status', 'Active'),

                'performance_cycle_expiry'                      =>  PerformanceCycle::where('resort_id', $this->resort_id),

                'performance_cycle_review_manager_expiry'       =>  PerformanceCycle::where('resort_id', $this->resort_id),

                'performance_cycle_review_self_expiry'          =>  PerformanceCycle::where('resort_id', $this->resort_id),

                'performance_meeting_expiry'                    =>  PeformanceMeeting::where('resort_id', $this->resort_id),

                'request_kept_on_hold'                          =>  PayrollAdvance::where('resort_id', $this->resort_id)->where('status','Approved'),

                'grievance_resolution_date'                     =>  GrivanceSubmissionModel::join('grivance_investigation_models as gim', 'gim.Grievance_s_id', '=', 'grivance_submission_models.id')
                                                                    ->where('gim.resort_id', $this->resort_id),

                'payroll_processing'                            =>  Payroll::where('resort_id', $this->resort_id)->where('status','draft'),

                'upcoming_interviews'                           =>  Vacancies::join('applicant_form_data as afd', 'afd.Parent_v_id', '=', 'vacancies.id')
                                                                        ->join('applicant_inter_view_details as aivd', 'aivd.Applicant_id', '=', 'afd.id')
                                                                        ->where('vacancies.resort_id', $this->resort_id)
                                                                        ->where('aivd.InterViewDate', '>', now()->format('Y-m-d'))
                                                                        ->where('aivd.status', 'Slot Booked'),

                'employee_visa_expiry'                          =>  Employee::with(['VisaRenewal.VisaChild'])
                                                                        ->where("nationality", '!=', "Maldivian")
                                                                        ->where('resort_id', $this->resort_id),

                'employee_xpat_insurance_expiry'                =>  Employee::with(['EmployeeInsurance.InsuranceChild'])
                                                                        ->where("nationality", '!=', "Maldivian")
                                                                        ->where('resort_id', $this->resort_id),

                'work_permit_medical_expiry'                    =>  Employee::with(['WorkPermitMedicalRenewal.WorkPermitMedicalRenewalChild'])
                                                                        ->where("nationality", '!=', "Maldivian")
                                                                        ->where('resort_id', $this->resort_id),

                                                                    
                'work_permit_expiry'                            =>  Employee::with(['WorkPermit'])
                                                                        ->where("nationality", '!=', "Maldivian")
                                                                        ->where('resort_id', $this->resort_id)
                                                                        ->whereHas('WorkPermit', function ($q) {
                                                                            $q->where('Status', 'Unpaid');
                                                                        }),
                                                                    
                'quota_slot_expiry'                             =>  Employee::with(['QuotaSlotRenewal'])
                                                                        ->where("nationality", '!=', "Maldivian")
                                                                        ->where('resort_id', $this->resort_id)
                                                                        ->whereHas('WorkPermit', function ($q) {
                                                                            $q->where('Status', 'Unpaid');
                                                                        }),

                'passport_expiry'                               =>  VisaEmployeeExpiryData::where('resort_id', $this->resort_id)
                                                                        ->where('DocumentName','Passport_Copy'),

                'exit_clearance_form'                           =>  EmployeeResignation::join('exit_clearance_form_assignments as ecfa', 'ecfa.emp_resignation_id', '=', 'employee_resignation.id')
                                                                        ->join('exit_clearance_form as ecf', 'ecf.id', '=', 'ecfa.form_id')
                                                                        ->where('employee_resignation.resort_id', $this->resort_id),

                'employee_transfer'                             =>  EmployeeTransfer::where('resort_id', $this->resort_id),

                 'interview_link_expiry'                        =>  Vacancies::join('t_anotification_parents as tap', 'tap.V_id', '=', 'vacancies.id')
                                                                        ->join('t_anotification_children as tac', 'tac.Parent_ta_id', '=', 'tap.id')
                                                                        ->join('application_links as al', 'al.ta_child_id', '=', 'tac.id')
                                                                        ->where('vacancies.resort_id', $this->resort_id)
                                                                        ->where('al.link_Expiry_date', '>', now()->format('Y-m-d'))
            ];

            if($isHR) {
                $queries['resignation_meeting']                 ->where('rms.meeting_with', 'HR');
            }

            if ($isHOD) {
                $queries['leaves']                              ->whereIn('emp_id', $this->underEmp_id);
                $queries['attendance']                          ->whereIn('parent_attendaces.Emp_id', $this->underEmp_id);
                $queries['travel']                              ->whereIn('employee_id', $this->underEmp_id);
                $queries['monthly']                             ->whereIn('emp_id', $this->underEmp_id);
                $queries['disciplinary']                        ->whereIn('Employee_id', $this->underEmp_id);
                $queries['survey']                              ->whereIn('se.Emp_id', $this->underEmp_id);
                $queries['learning']                            ->whereIn('tp.employee_id', $this->underEmp_id);
                $queries['events']                              ->whereIn('ce.employee_id', $this->underEmp_id);
                $queries['employee_probation']                  ->whereIn('id', $this->underEmp_id);
                $queries['employee_dob']                        ->whereIn('id', $this->underEmp_id);
                $queries['incidents_resolution_date']           ->whereIn('incidents.reporter_id', $this->underEmp_id);
                $queries['incidents_invest_meetings']           ->whereIn('incidents.reporter_id', $this->underEmp_id);
                $queries['employee_resignation']                ->whereIn('employee_id', $this->underEmp_id);
                $queries['resignation_meeting']                 ->where('rms.meeting_with', 'HOD');
                $queries['request_kept_on_hold']                ->whereIn('employee_id', $this->underEmp_id);
                $queries['employee_visa_expiry']                ->whereIn('id', $this->underEmp_id);
                $queries['employee_xpat_insurance_expiry']      ->whereIn('id', $this->underEmp_id);
                $queries['work_permit_medical_expiry']          ->whereIn('id', $this->underEmp_id);
                $queries['work_permit_expiry']                  ->whereIn('id', $this->underEmp_id);
                $queries['quota_slot_expiry']                   ->whereIn('id', $this->underEmp_id);
                $queries['passport_expiry']                     ->whereIn('employee_id', $this->underEmp_id);
                $queries['employee_transfer']                   ->whereIn('employee_id', $this->underEmp_id);
                $queries['upcoming_interviews']                 ->where('vacancies.department', $this->user->GetEmployee->Dept_id);
            }

            if ($request->filled('filter')) {
                [$start, $end]                                  =   $this->getDateRange($data['filter']);

                $queries['leaves']                              ->where(function ($q) use ($start, $end) {
                    $q                                          ->whereBetween('from_date', [$start, $end])
                                                                ->orWhereBetween('to_date', [$start, $end]);
                });

                $queries['attendance']                          ->whereBetween('parent_attendaces.date', [$start, $end]);
                $queries['holidays']                            ->whereBetween('PublicHolidaydate',[$start, $end]);
              
                $queries['travel']                              ->where(function ($q) use ($start, $end) {
                    $q                                          ->whereBetween('departure_date', [$start, $end])
                                                                ->orWhereBetween('arrival_date', [$start, $end]);
                });
                
                $queries['monthly']                             ->whereBetween('date_discussion', [$start, $end]);
                $queries['disciplinary']                        ->whereBetween('Expiry_date', [$start, $end]);
                
                $queries['survey']                              ->where(function ($q) use ($start, $end) {
                    $q                                          ->whereBetween('parent_surveys.Start_date', [$start, $end])
                                                                ->orWhereBetween('parent_surveys.End_date', [$start, $end]);
                });

                $queries['learning']                            ->where(function ($q) use ($start, $end) {
                    $q                                          ->whereBetween('ts.start_date', [$start, $end])
                                                                ->orWhereBetween('ts.end_date', [$start, $end]);
                });

                $queries['housekeeping']                       ->whereBetween('date',[$start, $end]);

                $queries['events']                             ->whereBetween('events.date', [$start, $end]);
                $queries['employee_probation']                 ->whereBetween('probation_end_date', [$start, $end]);
                $queries['incidents_resolution_date']          ->whereBetween('expected_resolution_date', [$start, $end]);
                $queries['incidents_invest_meetings']          ->whereBetween('meeting_date', [$start, $end]);

                $queries['employee_resignation']               ->whereBetween('last_working_day', [$start, $end]);

                $start_md                                       =   $start->format('m-d');
                $end_md                                         =   $end->format('m-d');

                if ($start_md <= $end_md) {
                    $queries['employee_dob']                   ->whereRaw("DATE_FORMAT(dob, '%m-%d') BETWEEN ? AND ?", [$start_md, $end_md]);
                } else {
                    $queries['employee_dob']->where(function ($q) use ($start_md, $end_md) {
                        $q                                      ->whereRaw("DATE_FORMAT(dob, '%m-%d') >= ?", [$start_md])
                                                                ->orWhereRaw("DATE_FORMAT(dob, '%m-%d') <= ?", [$end_md]);
                    });
                }

                $queries['resignation_meeting']                ->whereBetween('rms.meeting_date', [$start, $end]);
                $queries['employee_itineraries']               ->whereBetween('employee_itineraries.arrival_date', [$start, $end]);
                $queries['itinerary_meeting']                  ->whereBetween('eim.meeting_date', [$start, $end]);
                $queries['promotion_date']                     ->whereBetween('effective_date', [$start, $end]);
                $queries['salary_increment']                   ->whereBetween('effective_date', [$start, $end]);
                $queries['employment_contract_date']           ->whereBetween('contract_end_date', [$start, $end]);
                $queries['performance_cycle_expiry']           ->whereBetween('End_Date', [$start, $end]);
                $queries['performance_cycle_review_manager_expiry'] ->whereBetween('Manager_Activity_End_Date', [$start, $end]);
                $queries['performance_cycle_review_self_expiry'] ->whereBetween('Self_Activity_End_Date', [$start, $end]);
                $queries['performance_meeting_expiry']          ->whereBetween('date', [$start, $end]);
                $queries['request_kept_on_hold']                ->whereBetween('request_date', [$start, $end]);
                $queries['grievance_resolution_date']           ->where(function ($q) use ($start, $end) {
                $q                                              ->whereRaw(
                                                                    "STR_TO_DATE(gim.resolution_date, '%d/%m/%Y') BETWEEN ? AND ?", 
                                                                    [$start->format('Y-m-d'), $end->format('Y-m-d')]
                                                                    );
                });


                $queries['payroll_processing']                  ->whereBetween('start_date', [$start, $end]);

                $queries['employee_visa_expiry']                ->whereHas('VisaRenewal', function ($q) use ($start, $end) {
                    $q                                          ->whereBetween('end_date', [$start, $end]);
                });
                $queries['employee_xpat_insurance_expiry']      ->whereHas('EmployeeInsurance', function ($q) use ($start, $end) {
                $q                                              ->whereBetween('insurance_end_date', [$start, $end]);
                });

                $queries['work_permit_medical_expiry']          ->whereHas('WorkPermitMedicalRenewal', function ($q) use ($start, $end) {
                $q                                              ->whereBetween('end_date', [$start, $end]);
                });

                $queries['work_permit_expiry']                  ->whereHas('WorkPermit', function ($q) use ($start, $end) {
                $q                                              ->whereBetween('Due_Date', [$start, $end]);
                });

                $queries['quota_slot_expiry']                  ->whereHas('QuotaSlotRenewal', function ($q) use ($start, $end) {
                $q                                              ->whereBetween('Due_Date', [$start, $end]);
                });

                $queries['passport_expiry']                     ->whereRaw("
                                                                    STR_TO_DATE(
                                                                        JSON_UNQUOTE(JSON_EXTRACT(Ai_extracted_data, '$.extracted_fields.\"Date of Expiry\"')),
                                                                        '%d%b%Y'
                                                                    ) BETWEEN ? AND ?", 
                                                                    [$start->format('Y-m-d'), $end->format('Y-m-d')]
                                                                );

                $queries['exit_clearance_form']                 ->whereBetween('ecfa.deadline_date', [$start, $end]);
                $queries['employee_transfer']                   ->whereBetween('effective_date', [$start, $end]);
                $queries['upcoming_interviews']                 ->whereBetween('aivd.InterViewDate', [$start, $end]);
                $queries['interview_link_expiry']               ->whereBetween('al.link_Expiry_date', [$start, $end]);
               

            } elseif ($request->filled('month') && $request->filled('year')) {
                $month                                          =   $request->month;
                $year                                           =   $request->year;

                $queries['leaves']                             ->where(function($q) use ($month, $year) {
                    $q                                          ->whereMonth('from_date', $month)
                                                                ->whereYear('from_date',  $year)
                                                                ->orWhere(function($q2) use ($month, $year) {
                    $q2                                         ->whereMonth('to_date', $month)
                                                                ->whereYear('to_date',  $year);
                    });
                });

                $queries['attendance']                          ->whereMonth('parent_attendaces.date', (int)$data['month'])
                                                                ->whereYear('parent_attendaces.date', $data['year']);

                $queries['holidays']                            ->whereMonth('PublicHolidaydate', (int)$data['month'])
                                                                ->whereYear('PublicHolidaydate', $data['year']);

                $queries['travel']                              ->where(function($q) use ($month, $year) {
                    $q                                          ->whereMonth('departure_date', $month)
                                                                ->whereYear('departure_date',  $year)
                                                                ->orWhere(function($q2) use ($month, $year) {
                    $q2                                         ->whereMonth('arrival_date', $month)
                                                                ->whereYear('arrival_date',  $year);
                    });
                });

                $queries['monthly']                             ->whereMonth('date_discussion', (int)$data['month'])
                                                                ->whereYear('date_discussion', $data['year']);

                $queries['disciplinary']                        ->whereMonth('Expiry_date', (int)$data['month'])
                                                                ->whereYear('Expiry_date', $data['year']);

                $queries['survey']                              ->where(function($q) use ($month, $year) {
                    $q                                          ->whereMonth('parent_surveys.Start_date', $month)
                                                                ->whereYear('parent_surveys.Start_date',  $year)
                                                                ->orWhere(function($q2) use ($month, $year) {
                    $q2                                         ->whereMonth('parent_surveys.End_date', $month)
                                                                ->whereYear('parent_surveys.End_date',  $year);
                    });
                });

                $queries['housekeeping']                        ->whereMonth('date', $month)->whereYear('date',  $year);

                $queries['learning']                            ->where(function($q) use ($month, $year) {
                    $q                                          ->whereMonth('ts.start_date', $month)
                                                                ->whereYear('ts.start_date',  $year)
                                                                ->orWhere(function($q2) use ($month, $year) {
                    $q2                                         ->whereMonth('ts.end_date', $month)
                                                                ->whereYear('ts.end_date',  $year);
                    });
                });

                $queries['events']                              ->whereMonth('events.date', (int)$data['month'])
                                                                ->whereYear('events.date', $data['year']);

                $queries['employee_probation']                  ->whereMonth('probation_end_date', (int)$data['month'])
                                                                ->whereYear('probation_end_date', $data['year']);
                
                $queries['employee_dob']                        ->whereMonth('dob', (int)$data['month']);

                $queries['incidents_resolution_date']           ->whereMonth('expected_resolution_date', (int)$data['month'])
                                                                ->whereYear('expected_resolution_date', $data['year']);

                $queries['incidents_invest_meetings']           ->whereMonth('meeting_date', (int)$data['month'])
                                                                ->whereYear('meeting_date', $data['year']);

                $queries['employee_resignation']                ->whereMonth('last_working_day', (int)$data['month'])
                                                                ->whereYear('last_working_day', $data['year']);

                $queries['resignation_meeting']                ->whereMonth('rms.meeting_date', (int)$data['month'])
                                                                ->whereYear('rms.meeting_date', $data['year']);
                                                                
                $queries['employee_itineraries']                ->whereMonth('employee_itineraries.arrival_date', (int)$data['month'])
                                                                ->whereYear('employee_itineraries.arrival_date', $data['year']);

                $queries['itinerary_meeting']                   ->whereMonth('eim.meeting_date', (int)$data['month'])
                                                                ->whereYear('eim.meeting_date', $data['year']);

                $queries['promotion_date']                      ->whereMonth('effective_date', (int)$data['month'])
                                                                ->whereYear('effective_date', $data['year']);

                $queries['salary_increment']                    ->whereMonth('effective_date', (int)$data['month'])
                                                                ->whereYear('effective_date', $data['year']);

                $queries['employment_contract_date']            ->whereMonth('contract_end_date', (int)$data['month'])
                                                                ->whereYear('contract_end_date', $data['year']);

                $queries['performance_cycle_expiry']            ->whereMonth('End_Date', (int)$data['month'])
                                                                ->whereYear('End_Date', $data['year']);

                $queries['performance_cycle_review_self_expiry']    ->whereMonth('Self_Activity_End_Date', (int)$data['month'])
                                                                    ->whereYear('Self_Activity_End_Date', $data['year']);

                $queries['performance_meeting_expiry']          ->whereMonth('date', (int)$data['month'])
                                                                ->whereYear('date', $data['year']);

                $queries['request_kept_on_hold']                ->whereMonth('request_date', (int)$data['month'])
                                                                ->whereYear('request_date', $data['year']);

                $queries['grievance_resolution_date']        ->where(function ($q) use ($month, $year) {
                $q                                              ->whereRaw("MONTH(STR_TO_DATE(gim.resolution_date, '%d/%m/%Y')) = ? AND YEAR(STR_TO_DATE(gim.resolution_date, '%d/%m/%Y')) = ?", [$month, $year]);
                });

                $queries['payroll_processing']                  ->whereMonth('start_date', (int)$data['month'])
                                                                ->whereYear('start_date', $data['year']);

                $queries['employee_visa_expiry']                ->whereHas('VisaRenewal', function ($q) use ($month, $year) {
                $q                                              ->whereMonth('end_date', $month)
                                                                ->whereYear('end_date', $year);
                });

                $queries['employee_xpat_insurance_expiry']      ->whereHas('EmployeeInsurance', function ($q) use ($month, $year) {
                $q                                              ->whereMonth('insurance_end_date', $month)
                                                                ->whereYear('insurance_end_date', $year);
                });

                $queries['work_permit_medical_expiry']          ->whereHas('WorkPermitMedicalRenewal', function ($q) use ($month, $year) {
                $q                                              ->whereMonth('end_date', $month)
                                                                ->whereYear('end_date', $year);
                });

                $queries['work_permit_expiry']                  ->whereHas('WorkPermit', function ($q) use ($month, $year) {
                $q                                              ->whereMonth('Due_Date', $month)
                                                                ->whereYear('Due_Date', $year);
                });

                $queries['quota_slot_expiry']                   ->whereHas('QuotaSlotRenewal', function ($q) use ($month, $year) {
                $q                                              ->whereMonth('Due_Date', $month)
                                                                ->whereYear('Due_Date', $year);
                });

                $queries['passport_expiry']                     ->whereRaw("
                                                                    MONTH(STR_TO_DATE(
                                                                        JSON_UNQUOTE(JSON_EXTRACT(Ai_extracted_data, '$.extracted_fields.\"Date of Expiry\"')),
                                                                        '%d%b%Y'
                                                                    )) = ?
                                                                        AND
                                                                        YEAR(STR_TO_DATE(
                                                                            JSON_UNQUOTE(JSON_EXTRACT(Ai_extracted_data, '$.extracted_fields.\"Date of Expiry\"')),
                                                                            '%d%b%Y'
                                                                        )) = ?
                                                                ", [$month,$year]);

                $queries['exit_clearance_form']                 ->whereMonth('ecfa.deadline_date', $month)
                                                                ->whereYear('ecfa.deadline_date', $year);

                $queries['employee_transfer']                   ->whereMonth('effective_date', $month)
                                                                ->whereYear('effective_date', $year);

                $queries['upcoming_interviews']                 ->whereMonth('aivd.InterViewDate', $month)
                                                                ->whereYear('aivd.InterViewDate', $year);

                $queries['interview_link_expiry']               ->whereMonth('al.link_Expiry_date', $month)
                                                                ->whereYear('al.link_Expiry_date', $year);

            }
           
            
                $leaves                                         =   $queries['leaves']->get();
                $holidays                                       =   $queries['holidays']->get();
                $attendance                                     =   $queries['attendance']->get(['parent_attendaces.Emp_id', 'parent_attendaces.date']);
                $travel                                         =   $queries['travel']->get();
                $disciplinary                                   =   $queries['disciplinary']->get();
                $monthly                                        =   $queries['monthly']->get();
                $survey                                         =   $queries['survey']->get();
                $housekeeping                                   =   $queries['housekeeping']->get();
                $learning                                       =   $queries['learning']->get();
                $events                                         =   $queries['events']->get(['ce.employee_id','events.title','events.date','events.time']);
                $employee_probation                             =   $queries['employee_probation']->get();
                $employee_dob                                   =   $queries['employee_dob']->get();
                $incidents_resolution_date                      =   $queries['incidents_resolution_date']->get();
                $incidents_invest_meetings                      =   $queries['incidents_invest_meetings']->get();
                $employee_resignation                           =   $queries['employee_resignation']->get();
                $resignation_meeting                            =   $queries['resignation_meeting']->get();
                $employee_itineraries                           =   $queries['employee_itineraries']->get();
                $itinerary_meeting                              =   $queries['itinerary_meeting']->get();
                $promotion_date                                 =   $queries['promotion_date']->get();
                $salary_increment                               =   $queries['salary_increment']->get();
                $employment_contract_date                       =   $queries['employment_contract_date']->get();
                $performance_cycle_expiry                       =   $queries['performance_cycle_expiry']->get();
                $performance_cycle_review_manager_expiry        =   $queries['performance_cycle_review_manager_expiry']->get();
                $performance_cycle_review_self_expiry           =   $queries['performance_cycle_review_self_expiry']->get();
                $performance_meeting_expiry                     =   $queries['performance_meeting_expiry']->get();
                $request_kept_on_hold                           =   $queries['request_kept_on_hold']->get();
                $payroll_processing                             =   $queries['payroll_processing']->get();
                $employee_visa_expiry                           =   $queries['employee_visa_expiry']->get();
                $employee_xpat_insurance_expiry                 =   $queries['employee_xpat_insurance_expiry']->get();
                $work_permit_medical_expiry                     =   $queries['work_permit_medical_expiry']->get();
                $work_permit_expiry                             =   $queries['work_permit_expiry']->get();
                $quota_slot_expiry                              =   $queries['quota_slot_expiry']->get();
                $grievance_resolution_date                      =   $queries['grievance_resolution_date']->get();
                $passport_expiry                                =   $queries['passport_expiry']->get();
                $exit_clearance_form                            =   $queries['exit_clearance_form']->get();
                $employee_transfer                              =   $queries['employee_transfer']->get();
                $upcoming_interviews                            =   $queries['upcoming_interviews']->get();
                $interview_link_expiry                          =   $queries['interview_link_expiry']->get();

            $merged = collect([
            ...$leaves->map(fn($item)                           =>  $this->mapdata($this->employeeDetails($item->emp_id), 'Leave Departure', '', $item->from_date, '', '')),
            ...$leaves->map(fn($item)                           =>  $this->mapdata($this->employeeDetails($item->emp_id), 'Leave Arrival', '', $item->to_date, '', '')),
            ...$holidays->map(fn($item)                         =>  $this->mapdata('', $item->title, '', $item->date, '', '')),
            ...$attendance->map(fn($item)                       =>  $this->mapdata($this->employeeDetails($item->Emp_id), 'Day Off', '', $item->date, '', '')),
            // ...$travel->map(fn($item)                           =>  $this->mapdata($this->employeeDetails($item->employee_id), 'Island pass Departure', '', $item->departure_date, '', '')),
            // ...$travel->map(fn($item)                           =>  $this->mapdata($this->employeeDetails($item->employee_id), 'Island pass Arrival', '', $item->arrival_date, '', '')),


            ...$travel->map(function($item) {
                 if($item->departure_date && $item->departure_time) {
                    return $this->mapdata($this->employeeDetails($item->employee_id), 'Island pass Departure', '', $item->departure_date, '', $item->departure_time);
                 }
                })->filter(),
            ...$travel->map(function($item) {
                if($item->arrival_date && $item->arrival_time) {
                    return $this->mapdata($this->employeeDetails($item->employee_id), 'Island pass Arrival', '', $item->arrival_date, '', $item->arrival_time);
                }
            })->filter(),

            ...$disciplinary->map(fn($item)                     =>  $this->mapdata($this->employeeDetails($item->Employee_id), 'Disciplinary', '', $item->Expiry_date, '', '')),
            ...$monthly->map(fn($item)                          =>  $this->mapdata($this->employeeDetails($item->emp_id), $item->Area_of_Discussion, '', $item->date_discussion, $item->start_time,$item->end_time)),
            ...$survey->map(fn($item)                           =>  $this->mapdata($this->employeeDetails($item->Emp_id), 'Survey ' . $item->Survey_title, '', $item->End_date, '', '')),
            ...$housekeeping->map(fn($item)                     =>  $this->mapdata('', 'Housekeeping', '', $item->date, '', '')),
            ...$learning->map(fn($item)                         =>  $this->mapdata($this->employeeDetails($item->employee_id), $item->name, '', $item->start_date, $item->end_date, $item->end_time)),
            ...$events->map(function($item)  {
                $employee                                       =   $this->employeeDetails($item->employee_id);
                return $this->mapdata($employee,$item->title,'',$item->date,$item->time,'');
            }),
            ...$employee_probation->map(fn($item)               =>  $this->mapdata($this->employeeDetails($item->id), 'Probation expiry', '', $item->probation_end_date, '', '')),
            ...$employee_dob->map(function($item) {
                $dob_md                                         =   \Carbon\Carbon::parse($item->dob)->format('m-d');
                $dob_this_year                                  =   now()->year . '-' . $dob_md;
                return $this->mapdata($this->employeeDetails($item->id),'Birthday','',$dob_this_year,'','');
            }),
            ...$employee_probation->map(fn($item)               =>  $this->mapdata($this->employeeDetails($item->id), 'Probation expiry', '', $item->probation_end_date, '', '')),
            ...$incidents_resolution_date->map(fn($item)        =>  $this->mapdata($this->employeeDetails($item->reporter_id), 'Incident Resolution', '', $item->expected_resolution_date, '', '')),
            ...$incidents_invest_meetings->map(fn($item)        =>  $this->mapdata($this->employeeDetails($item->reporter_id), 'Incident Investigation Meeting', '', $item->meeting_date, '', '')),
            ...$employee_resignation->map(fn($item)             =>  $this->mapdata($this->employeeDetails($item->employee_id), 'Employee Resignation', '', $item->last_working_day, '', '')),
            ...$resignation_meeting->map(fn($item)              =>  $this->mapdata($this->employeeDetails($item->created_by), 'Resignation Meeting', '', $item->meeting_date, '', '')),
            ...$employee_itineraries->map(fn($item)             =>  $this->mapdata($this->employeeDetails($item->employee_id), 'Employee Arrival in '.$item->transportation_option, '', $item->arrival_date, '', '')),
            ...$itinerary_meeting->map(function($item) {
                return $this->mapdata($this->employeeDetails($item->meeting_participant_ids),'Itinerary Meeting','',$item->meeting_date,'','');
            }),
            ...$promotion_date->map(fn($item)                   =>  $this->mapdata($this->employeeDetails($item->employee_id), 'Promotion Date', '', $item->effective_date, '', '')),
            ...$salary_increment->map(fn($item)                 =>  $this->mapdata($this->employeeDetails($item->employee_id), 'Salary Increment', '', \Carbon\Carbon::parse($item->effective_date)->format('Y-m-d'), '', '')),
            ...$employment_contract_date->map(fn($item)         =>  $this->mapdata($this->employeeDetails($item->id), 'Employment Contract Date', '', $item->contract_end_date, '', '')),
            ...$performance_cycle_expiry->map(fn($item)                 =>  $this->mapdata('', 'Performance Cycle Expiry', '', $item->End_Date, '', '')),
            ...$performance_cycle_review_manager_expiry->map(fn($item)  =>  $this->mapdata('', 'Performance Cycle Review Manager Expiry', '', $item->Manager_Activity_End_Date, '', '')),
            ...$performance_cycle_review_self_expiry->map(fn($item)     =>  $this->mapdata('', 'Performance Cycle Review Self Expiry', '', $item->Self_Activity_End_Date, '', '')),
            ...$performance_meeting_expiry->map(fn($item)       =>  $this->mapdata('', 'Performance Meeting Expiry', '', $item->date, $item->start_time, $item->end_time)),
            ...$request_kept_on_hold->map(fn($item)             =>  $this->mapdata($this->employeeDetails($item->employee_id), $item->request_type.' Request Kept on Hold', '', $item->request_date, '', '')),
            ...$payroll_processing->map(fn($item)               =>  $this->mapdata('', $item->request_type.' Payroll Processing', '', $item->start_date, '', '')),
            ...$employee_visa_expiry->map(fn($emp)              => $this->mapdata($this->employeeDetails($emp->id),'Visa Expiry','', $emp->VisaRenewal->end_date,'','')),
            ...$employee_xpat_insurance_expiry->map(fn($emp)    => $this->mapdata($this->employeeDetails($emp->id),'Xpat Insurance expiry date','',$emp->EmployeeInsurance->insurance_end_date,'','')),
            ...$work_permit_medical_expiry->map(fn($emp)        => $this->mapdata($this->employeeDetails($emp->id),'Work Permit Medical expiry date ','',$emp->WorkPermitMedicalRenewal->end_date,'','')),
            ...$work_permit_expiry->flatMap(function ($emp) {
                return collect($emp->WorkPermit)->map(function ($permit) use ($emp) {
                    return $this->mapdata($this->employeeDetails($emp->id),'Work Permit expiry date','',$permit->Due_Date,'','');
                });
            }),
            
            ...$quota_slot_expiry->flatMap(function ($emp) {
                return collect($emp->QuotaSlotRenewal)->map(function ($slot) use ($emp) {
                    return $this->mapdata($this->employeeDetails($emp->id),'Quota Slot expiry date','',$slot->Due_Date,'','');
                });
            }),
            ...$grievance_resolution_date->map(function($item) {
                $date = $item->resolution_date? \Carbon\Carbon::createFromFormat('d/m/Y', $item->resolution_date)->format('Y-m-d'): null;
                    return $this->mapdata($this->employeeDetails($item->Employee_id),'Grievance Resolution date','',$date,'','');
            }),

            ...$passport_expiry->map(function($item) {
                $expiryRaw                                  =   json_decode($item->Ai_extracted_data, true)['extracted_fields']['Date of Expiry'] ?? null;
                $expiryDate                                 =   $expiryRaw ? \Carbon\Carbon::createFromFormat('dMY', str_replace(' ', '', $expiryRaw))->format('Y-m-d') : null;
                return $this->mapdata($this->employeeDetails($item->employee_id),'Passport Expiry', '', $expiryDate, '', '');
            }),

            ...$exit_clearance_form->map(fn($item)          => $this->mapdata($this->employeeDetails($item->employee_id),$item->form_name.' Form','',$item->deadline_date,'','')),
            ...$employee_transfer->map(fn($item)            => $this->mapdata($this->employeeDetails($item->employee_id),'Employee Transfer Effective Date','',$item->effective_date,'','')),
            ...$upcoming_interviews->map(fn($item)          => $this->mapdata('','Upcoming Interview Date','',$item->InterViewDate,'','')),
            ...$interview_link_expiry->map(fn($item)        => $this->mapdata('','Interview Link Expiry Date','',$item->link_Expiry_date,'','')),

        ])->sortBy('date')->values();

            if($merged->isEmpty()) {
                return response()->json(['success' => false,'message' =>  'No events found',], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'calendar Dashboard',
                'manager_calendar_data' => $merged
            ], 200);

        } catch(\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }
    
    private function getDateRange($filter)
    {
        return match ($filter) {
            'yearly'                                        =>  [now()->startOfYear(), now()->endOfYear()],
            'weekly'                                        =>  [now()->startOfWeek(), now()->endOfWeek()],
            'monthly'                                       =>  [now()->startOfMonth(), now()->endOfMonth()],
            default                                         =>  [null, null],
        };
    }

    public function employeeDetails($emp_id)
    {

        $employee                                           =   Employee::join('resort_admins as t1', "t1.id", "=", "employees.Admin_Parent_id")
                                                                ->where('employees.id', $emp_id)
                                                                ->where('employees.status', 'Active')
                                                                ->select('employees.id','t1.first_name', 't1.last_name')
                                                                ->first();
        
        return $employee;
    }

    private function mapdata($employee, $title, $location,$date,$start_time,$end_time)
    {
       
      return [
            'emp_name'                              => $employee ? ($employee->first_name . ' ' . $employee->last_name) : 'N/A',
            'title'                                 =>  $title ?? '',
            'location'                              =>  $location ?? '',
            'date'                                  =>  $date ?? '',
            'start_time'                            =>  $start_time ?? '',
            'end_time'                              =>  $end_time ?? '',
        ];

    }

    private function employeeMapdata($type,$title, $location,$date,$start_time,$end_time)
    {
      return [
            'type'                                  =>  $type,
            'title'                                 =>  $title?? '',
            'location'                              =>  $location ?? '',
            'date'                                  =>  $date ?? '',
            'start_time'                            =>  $start_time ?? '',
            'end_time'                              =>  $end_time ?? '',
        ];
    }
}
