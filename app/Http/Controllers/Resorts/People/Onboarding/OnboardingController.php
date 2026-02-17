<?php
namespace App\Http\Controllers\Resorts\People\Onboarding;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Events\ResortNotificationEvent;
use App\Models\ItineraryTemplate;
use App\Models\OnboardingEvents;
use App\Models\EmployeeItineraries;
use App\Models\EmployeeItinerariesMeeting;
use App\Models\Resort;
use App\Models\Employee;
use App\Models\CulturalInsights;
use App\Models\ResortTransportation;
use Auth;
use Config;
use Common;
use DB;
use Carbon\Carbon;
class OnboardingController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
    }

    public function index()
    {
        $page_title ='Create Onboarding';
        $resort_id = $this->resort->resort_id;
        $employees = Employee::with(['resortAdmin', 'department', 'position'])
            ->where('resort_id', $resort_id)->where('status','Active')
            ->where('rank', 6)
            ->get();
        $participants = Employee::with(['resortAdmin', 'department', 'position'])
            ->where('resort_id', $resort_id)->where('status','Active')
            ->whereIn('rank', [1, 2, 3, 8])
            ->get();
        $transportations = ResortTransportation::where('resort_id', $resort_id)
            ->whereNotIn('transportation_option', ['International Flight'])
            ->pluck('transportation_option', 'id')
            ->toArray();
        // dd($transportations);
        return view('resorts.people.onboarding.index',compact('page_title','resort_id','employees','participants','transportations'));
    }

    public function getupcomingEmployees(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        $search = $request->search;

        $query = Employee::with(['resortAdmin', 'department', 'position'])
            ->where('resort_id', $resort_id)
            ->whereDate('joining_date', '>', \Carbon\Carbon::today());

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('resortAdmin', function ($q2) use ($search) {
                    $q2->where('first_name', 'like', "%$search%")
                        ->orWhere('last_name', 'like', "%$search%");
                })
                ->orWhere('joining_date', 'like', "%$search%")
                ->orWhereHas('position', function ($q3) use ($search) {
                    $q3->where('position_title', 'like', "%$search%");
                })
                ->orWhereHas('department', function ($q4) use ($search) {
                    $q4->where('name', 'like', "%$search%"); // Use correct column
                });
            });
        }

        $upcoming_employees = $query->get();

        $view = view('resorts.renderfiles.upcoming-employees', compact('upcoming_employees'))->render();

        return response()->json(['html' => $view]);
    }

    public function getTemplatesForEmployees(Request $request)
    {
        $employeeId = $request->input('employee_id');
        $employee = Employee::with('position')->find($employeeId);
        if (!$employee) {
            return response()->json(['templates' => []]);
        }

        $grade = $employee->position->rank ?? null;

        // Example logic — you can modify thresholds
        if ($grade == 5 || $grade == 6) {
            $type = 'supervisor_line';
        } else {
            $type = 'manager_above';
        }

        $template = ItineraryTemplate::where('template_type', $type)->where('resort_id',$this->resort->resort_id)->first();

        return response()->json([
            'id' => $template->id,
            'name' => $template->name,
            'description' => $template->description,
        ]);
    }

    public function config()
    {
        $page_title ='Onboarding Configuration';
        $resort_id = $this->resort->resort_id;
        $notificationTimings = config('settings.notificationTiming'); // access the config array
        return view('resorts.people.onboarding.config',compact('page_title','resort_id','notificationTimings'));
    }

    public function create()
    {
        $page_title ='Onboarding Itinerary Template';
        $resort_id = $this->resort->resort_id;
        return view('resorts.people.onboarding.create-template',compact('page_title','resort_id'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'template_type' => 'required|in:supervisor_line,manager_above',
            'template_name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'form_structure' => 'required|json', // If it's sent as JSON string
        ]);

        $template = ItineraryTemplate::updateOrCreate(
            [
                'resort_id' => $this->resort->resort_id,
                'template_type' => $validatedData['template_type'],
            ],
            [
                'name' => $validatedData['template_name'],
                'description' => $validatedData['description'],
                'fields' => $validatedData['form_structure'],
            ]
        );

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Itinerary Template ' . ($template->wasRecentlyCreated ? 'Created' : 'Updated') . ' Successfully.',
            'redirect_url' => route('onboarding.itinerary-template.list'),
        ]);
    }

    public function list(Request $request){
        $page_title ='Itinerary Template List';
        if($request->ajax())        
        {
            $query = ItineraryTemplate::where('resort_id', $this->resort->resort_id);

            if ($request->filled('searchTerm')) {
                $searchTerm = $request->searchTerm;

                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('template_type', 'LIKE', "%{$searchTerm}%"); // ✅ FIXED HERE
                });
            }

            $templates = $query->get();


            return datatables()->of($templates)
                ->addColumn('action', function ($row) {
                     $id = base64_encode($row->id);
                    $edit_url = route('onboarding.itinerary-template.edit', $id);
                    $editimg = asset('resorts_assets/images/edit.svg');
                    $deleteimg = asset('resorts_assets/images/trash-red.svg');
        
                    return "<a href='$edit_url' class='edit-row-btn'><img src='$editimg' alt='Edit'></a>
                            <a href='#' class='delete-row-btn' data-id='$id'><img src='$deleteimg' alt='Delete'></a>";
                })
                ->rawColumns(['action']) // Ensure buttons are rendered as HTML
                ->make(true);
        }
        return view('resorts.people.onboarding.template-list',compact('page_title'));
    }

    public function edit($id){
        $page_title ='Edit Itinerary Template';
        $resort_id = $this->resort->resort_id;

        $templates = ItineraryTemplate::find(base64_decode($id));
        if(!$templates){
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Template not found.',
            ]);

        }
        return view('resorts.people.onboarding.edit-template',compact('page_title','templates'));
    }

    public function update(Request $request,$id){
        if(!is_numeric($id)){
            $id = base64_decode($id);
        }
        $template = ItineraryTemplate::find($id);

        if($template){
            $template->template_type = $request->template_type;
            $template->name = $request->template_name;
            $template->description = $request->description;
            $template->fields = $request->form_structure;
            $template->save();
        }

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Template updated successfully.',
            'redirect_url' => route('onboarding.itinerary-template.list'),
        ]);
    }
   
    public function destroy($id)
    {
        if(!is_numeric($id)){
            $id = base64_decode($id);
        }
        $template = ItineraryTemplate::find($id);

        if ($template) {
            $template->delete();
            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Template deleted successfully.',
            ]);
        }
        return response()->json([
            'success' => false,
            'status' => 'error',
            'message' => 'Template not found.',
        ]);
    }

    public function events(Request $request){
        
        $page_title ='Onboarding Events List';
        $notificationTimings = config('settings.notificationTiming'); // access the config array

        if($request->ajax())        
        {
            $query = OnboardingEvents::where('resort_id', $this->resort->resort_id);

            if ($request->filled('searchTerm')) {
                $searchTerm = $request->searchTerm;

                $query->where(function ($q) use ($searchTerm) {
                    $q->where('event_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('notification_time', 'LIKE', "%{$searchTerm}%"); // ✅ FIXED HERE
                });
            }

            $events = $query->orderBy('created_at','desc')->get();


            return datatables()->of($events)
                ->addColumn('action', function ($row) {
                    $id = base64_encode($row->id);
                    $editimg = asset('resorts_assets/images/edit.svg');
                    $deleteimg = asset('resorts_assets/images/trash-red.svg');
        
                    return "<a href='#' data-id=".$id." class='edit-row-btn'><img src='$editimg' alt='Edit'></a>
                            <a href='#' class='delete-row-btn' data-id='$id'><img src='$deleteimg' alt='Delete'></a>";
                })
                ->rawColumns(['action']) // Ensure buttons are rendered as HTML
                ->make(true);
        }
        return view('resorts.people.onboarding.event-list',compact('page_title','notificationTimings'));

    }

    public function storeEvents(Request $request)
    {
        $this->validate($request, [
            'events' => 'required|array',
            'events.*' => 'required|string|max:255',
            'notification_timing' => 'required|array',
            'notification_timing.*' => 'nullable|string|max:255'
        ]);

        foreach ($request->events as $key => $eventName) {
            $checkEvent = OnboardingEvents::where('resort_id', $this->resort->resort_id)
                ->where('event_name', $eventName)   
                ->first();
                
            if ($checkEvent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event "' . $eventName . '" already exists.',
                ], 400);
            }

            OnboardingEvents::create([
                'resort_id' => $this->resort->resort_id,
                'event_name' => $eventName,
                'notification_time' => $request->notification_timing[$key] ?? null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Events created successfully.',
            'redirect_url' => route('onboarding.events'),
        ]);
    }

    public function updateEvent(Request $request, $id)
    {
        if(!is_numeric($id)){
            $id = base64_decode($id);
        }
        $event = OnboardingEvents::where('resort_id', $this->resort->resort_id)->findOrFail($id);
        $event->update([
            'event_name' => $request->event_name,
            'notification_time' => $request->notification_timing
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully.'
        ]);
    }

    public function destroyEvent($id)
    {
        if(!is_numeric($id)){
            $id = base64_decode($id);
        }
        $event = OnboardingEvents::where('resort_id', $this->resort->resort_id)->findOrFail($id);
        $event->delete();

        return response()->json([
            'success' => true,
            'message' => 'Event deleted successfully.'
        ]);
    }

    public function storeOrUpdateCI(Request $request)
    {
        // Validate the input
        $request->validate([
            'cultural_insights' => 'required|string|max:65535',
        ]);

        try {
            // Retrieve or create the TermsAndCondition record for the logged-in resort admin
            $CI = CulturalInsights::updateOrCreate(
                ['resort_id' => $this->resort->resort_id], // Find by Resort_id
                ['cultural_insights' => $request->input('cultural_insights')] // Update or create this field
            );

            return response()->json([
                'success' => true,
                'message' => 'Cultural Insights saved successfully!',
                'data' => $CI,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving Cultural Insights.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getEmployeeDetails(Request $request)
    {
        try {
            $employeeId = $request->input('employee_id');
            
            // Adjust this query based on your actual database structure
            $employee = Employee::with(['resortAdmin','position', 'department'])
                ->where('id', $employeeId)
                ->first();
            
            if (!$employee) {
                return response()->json(['error' => 'Employee not found'], 404);
            }
            
            // Adjust field names based on your actual database structure
            $employeeData = [
                'id' => $employee->id,
                'emp_id' => $employee->Emp_id ?? 'N/A',
                'full_name' => $employee->resortAdmin->full_name ?? 'Unknown',
                'position' => $employee->position->position_title ?? 'Unknown Position',
                'department' => $employee->department->name ?? 'Unknown Department',
                'profile_image' => Common::getResortUserPicture($employee->Admin_Parent_id ?? null),
                'admin_parent_id' => $employee->resortAdmin->id ?? null,
                'joining_date' => $employee->joining_date, // Make sure this is included
            ];
            
            return response()->json($employeeData);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch employee details'], 500);
        }
    }
    
    /**
     * Get participant details for meetings
     */
    public function getParticipantDetails(Request $request)
    {
        try {
            $participantIds = $request->input('participant_ids', []);
            
            if (empty($participantIds)) {
                return response()->json([]);
            }
            
            // Adjust this query based on your actual database structure
            $participants = Employee::with(['resortAdmin', 'position', 'department'])
                ->whereIn('id', $participantIds)
                ->get();
            
            $participantsData = $participants->map(function ($participant) {
                return [
                    'id' => $participant->id,
                    'emp_id' => $participant->Emp_id ?? 'N/A',
                    'full_name' => $participant->resortAdmin->full_name ?? 'Unknown',
                    'position' => $participant->position->position_title ?? 'Unknown Position',
                    'department' => $participant->department->name ?? 'Unknown Department',
                    'profile_image' => Common::getResortUserPicture($participant->Admin_Parent_id ?? null)
                ];
            });
            
            return response()->json($participantsData);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch participant details'], 500);
        }
    }

    // public function storeItinerary(Request $request)
    // {
    //     // Remove this line once testing is complete
    //     // dd($request->all());
        
    //     try {
    //         $employee = Employee::find($request->input('employee_id')); // Changed from 'selected_employee'

    //         if (!$employee) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Employee not found.',
    //             ], 404);
    //         }

    //         // Custom validation for participants structure
    //         $request->validate([
    //             'participants' => 'required|array',
    //             'participants.*' => 'required|array|min:1',
    //             'participants.*.*' => 'required|exists:employees,id',
    //         ], [
    //             'participants.required' => 'Meeting participants are required.',
    //             'participants.*.required' => 'Each meeting must have participants.',
    //             'participants.*.min' => 'Each meeting must have at least one participant.',
    //             'participants.*.*.exists' => 'One or more selected participants do not exist.',
    //         ]);

    //         $validatedData = $request->validate([
    //             'employee_id' => 'required|exists:employees,id', // Changed from 'selected_employee'
    //             'template_id' => 'required|exists:itinerary_templates,id',
    //             'greeting_message' => 'required|string|max:500',

    //             'arrival_date' => ['required', 'date_format:d/m/Y', function ($attribute, $value, $fail) use ($employee) {
    //                 // Check if value is empty or null
    //                 if (empty($value) || $value === null) {
    //                     $fail('Arrival date is required and cannot be empty.');
    //                     return;
    //                 }
                    
    //                 try {
    //                     $arrivalDate = Carbon::createFromFormat('d/m/Y', $value);
    //                     if (!$arrivalDate) {
    //                         $fail('Arrival date must be in dd/mm/yyyy format.');
    //                         return;
    //                     }
                        
    //                     if ($arrivalDate->gte(Carbon::parse($employee->joining_date))) {
    //                         $fail('Arrival date must be before the joining date (' . Carbon::parse($employee->joining_date)->format('d/m/Y') . ').');
    //                     }
    //                 } catch (\Exception $e) {
    //                     $fail('Invalid arrival date format. Please use dd/mm/yyyy format.');
    //                 }
    //             }],

    //             'domestic_flight_date' => ['nullable|date_format:d/m/Y', function ($attribute, $value, $fail) use ($employee) {
    //                 // Check if value is empty or null
                    
    //                 try {
    //                     $domesticDate = Carbon::createFromFormat('d/m/Y', $value);
    //                     if (!$domesticDate) {
    //                         $fail('Domestic flight date must be in dd/mm/yyyy format.');
    //                         return;
    //                     }
                        
    //                     if ($domesticDate->gte(Carbon::parse($employee->joining_date))) {
    //                         $fail('Flight date must be before the joining date (' . Carbon::parse($employee->joining_date)->format('d/m/Y') . ').');
    //                     }
    //                 } catch (\Exception $e) {
    //                     $fail('Invalid domestic flight date format. Please use dd/mm/yyyy format.');
    //                 }
    //             }],

    //             'speedboat_date' => ['nullable|date_format:d/m/Y', function ($attribute, $value, $fail) use ($employee) {
    //                 // Check if value is empty or null
                    
    //                 try {
    //                     $speedboatDate = Carbon::createFromFormat('d/m/Y', $value);
    //                     if (!$speedboatDate) {
    //                         $fail('Speedboat date must be in dd/mm/yyyy format.');
    //                         return;
    //                     }
                        
    //                     if ($speedboatDate->gte(Carbon::parse($employee->joining_date))) {
    //                         $fail('Speedboat date must be before the joining date (' . Carbon::parse($employee->joining_date)->format('d/m/Y') . ').');
    //                     }
    //                 } catch (\Exception $e) {
    //                     $fail('Invalid Speedboat flight date format. Please use dd/mm/yyyy format.');
    //                 }
    //             }],

    //             'seaplane_date' => ['nullable|date_format:d/m/Y', function ($attribute, $value, $fail) use ($employee) {
    //                 // Check if value is empty or null
                    
    //                 try {
    //                     $seaplaneDate = Carbon::createFromFormat('d/m/Y', $value);
    //                     if (!$seaplaneDate) {
    //                         $fail('Seaplane date must be in dd/mm/yyyy format.');
    //                         return;
    //                     }
                        
    //                     if ($seaplaneDate->gte(Carbon::parse($employee->joining_date))) {
    //                         $fail('Seaplane date must be before the joining date (' . Carbon::parse($employee->joining_date)->format('d/m/Y') . ').');
    //                     }
    //                 } catch (\Exception $e) {
    //                     $fail('Invalid Seaplane flight date format. Please use dd/mm/yyyy format.');
    //                 }
    //             }],

    //             // Also update the meeting date validation:
    //             'meeting_date.*' => ['required', 'date_format:d/m/Y', function ($attribute, $value, $fail) {
    //                 if (empty($value) || $value === null) {
    //                     $fail('Meeting date is required and cannot be empty.');
    //                     return;
    //                 }
                    
    //                 try {
    //                     $meetingDate = Carbon::createFromFormat('d/m/Y', $value);
    //                     if (!$meetingDate) {
    //                         $fail('Meeting date must be in dd/mm/yyyy format.');
    //                     }
    //                 } catch (\Exception $e) {
    //                     $fail('Invalid meeting date format. Please use dd/mm/yyyy format.');
    //                 }
    //             }],


    //             'arrival_time' => 'required|date_format:H:i',
    //             'domestic_departure_time' => 'nullable|date_format:H:i',
    //             'domestic_arrival_time' => 'nullable|date_format:H:i',
    //             'domestic_arrival_time' => 'nullable|date_format:H:i',

    //             'resort_transportaion_id' => 'required|exists:resort_transportations,id',
    //             'speedboat_name' => 'nullable|string|max:255',
    //             'speedboat_departure_time'=>'nullable|date_format:H:i',
    //             'speedboat_arrival_time'=>'nullable|date_format:H:i',
    //             'captain_number' => 'nullable|string|max:255',
    //             'location' => 'nullable|string|max:255',

    //             'seaplane_name' => 'nullable|string|max:255',
    //             'seaplane_departure_time'   => 'nullable|date_format:H:i',
    //             'seaplane_arrival_time'  => 'nullable|date_format:H:i',

    //             'hotel_id' => 'required|string|max:255',
    //             'hotel_name' => 'required|string|max:255',
    //             'hotel_contact_no' => 'required|string|max:20',
    //             'booking_reference' => 'required|string|max:255',
    //             'hotel_address' => 'required|string|max:1000',

    //             'medical_center_name' => 'required|string|max:255',
    //             'medical_center_contact_no' => 'required|string|max:20',
    //             'medical_type' => 'required|string|max:255',
    //             'medical_time' => 'required|string|max:255',
    //             'approx_time' => 'required|date_format:H:i',

    //             'pickup_employee_id' => 'required|exists:employees,id',
    //             'accompany_medical_employee_id' => 'required|exists:employees,id',

    //             'entry_pass_file' => 'required|file|mimes:pdf|max:5120',
    //             'flight_ticket_file' => 'required|file|mimes:pdf|max:5120',
    //             'domestic_flight_ticket'=> 'required|file|mimes:pdf|max:5120',

    //             'meeting_title.*' => 'required|string|max:255',
    //             'meeting_time.*' => 'required|date_format:H:i',
    //             'meeting_link.*' => 'required|string|max:500',
    //         ], [
    //             'arrival_date.date_format' => 'Arrival date must be in dd/mm/yyyy format.',
    //             'domestic_flight_date.date_format' => 'Domestic flight date must be in dd/mm/yyyy format.',
    //             'arrival_time.date_format' => 'Arrival time must be in HH:MM format.',
    //             'domestic_departure_time.date_format' => 'Domestic departure time must be in HH:MM format.',
    //             'domestic_arrival_time.date_format' => 'Domestic arrival time must be in HH:MM format.',
    //             'approx_time.date_format' => 'Approximate time must be in HH:MM format.',
                
    //             'employee_id.required' => 'Employee selection is required.',
    //             'employee_id.exists' => 'Selected employee does not exist.',
    //             'template_id.required' => 'Template selection is required.',
    //             'template_id.exists' => 'Selected template does not exist.',
    //             'greeting_message.required' => 'Greeting message is required.',
    //             'greeting_message.max' => 'Greeting message cannot exceed 500 characters.',
                
    //             'entry_pass_file.required' => 'Entry pass file is required.',
    //             'entry_pass_file.mimes' => 'Entry pass file must be a PDF.',
    //             'entry_pass_file.max' => 'Entry pass file cannot exceed 5MB.',
    //             'flight_ticket_file.required' => 'Flight ticket file is required.',
    //             'flight_ticket_file.mimes' => 'Flight ticket file must be a PDF.',
    //             'flight_ticket_file.max' => 'Flight ticket file cannot exceed 5MB.',
                
    //             'pickup_employee_id.required' => 'Pickup employee is required.',
    //             'pickup_employee_id.exists' => 'Selected pickup employee does not exist.',
    //             'accompany_medical_employee_id.required' => 'Medical accompaniment employee is required.',
    //             'accompany_medical_employee_id.exists' => 'Selected medical accompaniment employee does not exist.',
                
    //             'meeting_title.*.required' => 'Meeting title is required for all meetings.',
    //             'meeting_time.*.required' => 'Meeting time is required for all meetings.',
    //             'meeting_time.*.date_format' => 'Meeting time must be in HH:MM format.',
    //             'meeting_link.*.required' => 'Meeting link is required for all meetings.',
    //         ]);

    //         DB::beginTransaction();
         
    //         // Convert dates from d/m/Y to Y-m-d format
    //         $arrivalDate = Carbon::createFromFormat('d/m/Y', $validatedData['arrival_date'])->format('Y-m-d');
    //         $domesticFlightDate = Carbon::createFromFormat('d/m/Y', $validatedData['domestic_flight_date'])->format('Y-m-d');

    //         // Create the main itinerary record
    //         $onboardingItinerary = EmployeeItineraries::create([
    //             'resort_id' => $employee->resort_id,
    //             'employee_id' => $validatedData['employee_id'], // Updated field name
    //             'template_id' => $validatedData['template_id'],
    //             'greeting_message' => $validatedData['greeting_message'],
    //             'arrival_date' => $arrivalDate,
    //             'arrival_time' => $validatedData['arrival_time'],
    //             'domestic_flight_date' => $domesticFlightDate,
    //             'domestic_departure_time' => $validatedData['domestic_departure_time'],
    //             'domestic_arrival_time' => $validatedData['domestic_arrival_time'],
    //             'resort_transportation' => $validatedData['resort_transportaion'],
    //             'speedboat_name' => $validatedData['transporation_name'],
    //             'captain_number' => $validatedData['captain_number'],
    //             'location' => $validatedData['location'],

    //             'hotel_id' => $validatedData['hotel_id'],
    //             'hotel_name' => $validatedData['hotel_name'],
    //             'hotel_contact_no' => $validatedData['hotel_contact_no'],
    //             'booking_reference' => $validatedData['booking_reference'],
    //             'hotel_address' => $validatedData['hotel_address'],

    //             'medical_center_name' => $validatedData['medical_center_name'],
    //             'medical_center_contact_no' => $validatedData['medical_center_contact_no'],
    //             'medical_type' => $validatedData['medical_type'],
    //             'approx_time' => $validatedData['approx_time'],

    //             'pickup_employee_id' => $validatedData['pickup_employee_id'],
    //             'accompany_medical_employee_id' => $validatedData['accompany_medical_employee_id'],
    //         ]);

    //         $entryPassPath = $request->file('entry_pass_file');
    //         $awsPassPath =  Common::AWSEmployeeFileUpload($this->resort->resort_id,$entryPassPath,$employee->Emp_id); 

    //         if($awsPassPath['status'] == true)
    //         {
    //             $onboardingItinerary->entry_pass_file =$awsPassPath['path'];
    //         }

    //         $flightTicketPath = $request->file('flight_ticket_file');
    //         $awsflightTicketPath =  Common::AWSEmployeeFileUpload($this->resort->resort_id,$flightTicketPath,$employee->Emp_id); 

    //         if($awsflightTicketPath['status'] == true)
    //         {
    //             $onboardingItinerary->flight_ticket_file =$awsflightTicketPath['path'];
    //         }

    //         // Create meeting records with proper participant handling
    //         if (isset($validatedData['meeting_title']) && is_array($validatedData['meeting_title'])) {
    //             foreach ($validatedData['meeting_title'] as $index => $meetingTitle) {
    //                 $meetingDate = Carbon::createFromFormat('d/m/Y', $validatedData['meeting_date'][$index])->format('Y-m-d');
                    
    //                 // Handle participants properly
    //                 $participants = $request->input("participants.{$index}", []);
    //                 $participantIds = '';
                    
    //                 if (is_array($participants) && !empty($participants)) {
    //                     // Remove any empty values and ensure all are valid
    //                     $validParticipants = array_filter($participants, function($id) {
    //                         return !empty($id) && is_numeric($id);
    //                     });
                        
    //                     if (!empty($validParticipants)) {
    //                         $participantIds = implode(',', $validParticipants);
    //                     }
    //                 }

    //                 // Log for debugging
    //                 \Log::info("Creating meeting {$index}", [
    //                     'title' => $meetingTitle,
    //                     'date' => $meetingDate,
    //                     'time' => $validatedData['meeting_time'][$index],
    //                     'participants_raw' => $participants,
    //                     'participants_processed' => $participantIds
    //                 ]);

    //                 EmployeeItinerariesMeeting::create([
    //                     'employee_itinerary_id' => $onboardingItinerary->id,
    //                     'meeting_title' => $meetingTitle,
    //                     'meeting_date' => $meetingDate,
    //                     'meeting_time' => $validatedData['meeting_time'][$index],
    //                     'meeting_link' => $validatedData['meeting_link'][$index],
    //                     'meeting_participant_ids' => $participantIds, // This should now have comma-separated IDs
    //                 ]);
    //             }
    //         }

    //         DB::commit();

    //         // Send notifications (assuming this method exists)
    //         if (method_exists($this, 'sendOnboardingNotifications')) {
    //             $this->sendOnboardingNotifications($onboardingItinerary);
    //         }

    //         // Log successful creation
    //         \Log::info('Onboarding itinerary created successfully', [
    //             'itinerary_id' => $onboardingItinerary->id,
    //             'employee_id' => $validatedData['employee_id'],
    //             'template_id' => $validatedData['template_id'],
    //             'meetings_created' => isset($validatedData['meeting_title']) ? count($validatedData['meeting_title']) : 0,
    //         ]);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Onboarding itinerary created successfully!',
    //             'data' => [
    //                 'id' => $onboardingItinerary->id,
    //                 'employee_name' => $onboardingItinerary->employee->resortAdmin->full_name ?? 
    //                                 $onboardingItinerary->employee->full_name ?? 'Unknown',
    //                 'arrival_date' => $arrivalDate,
    //                 'domestic_flight_date' => $domesticFlightDate,
    //                 'meetings_count' => isset($validatedData['meeting_title']) ? count($validatedData['meeting_title']) : 0,
    //             ]
    //         ], 201);

    //     } catch (\Illuminate\Validation\ValidationException $e) {
    //         DB::rollBack();
            
    //         // Log validation errors for debugging
    //         \Log::warning('Validation failed for onboarding itinerary', [
    //             'errors' => $e->errors(),
    //             'input' => $request->except(['entry_pass_file', 'flight_ticket_file']) // Exclude files from log
    //         ]);
            
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Validation failed',
    //             'errors' => $e->errors(),
    //         ], 422);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
            
    //         // Delete uploaded files if they exist
    //         if (isset($entryPassPath) && Storage::disk('public')->exists($entryPassPath)) {
    //             Storage::disk('public')->delete($entryPassPath);
    //         }
    //         if (isset($flightTicketPath) && Storage::disk('public')->exists($flightTicketPath)) {
    //             Storage::disk('public')->delete($flightTicketPath);
    //         }

    //         \Log::error('Onboarding itinerary creation failed: ' . $e->getMessage(), [
    //             'employee_id' => $request->input('employee_id'),
    //             'template_id' => $request->input('template_id'),
    //             'trace' => $e->getTraceAsString(),
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to create onboarding itinerary. Please try again.',
    //             'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
    //         ], 500);
    //     }
    // }

    public function storeItinerary(Request $request)
    {
        try {
            // dd($request->all()); // For debugging purposes, remove this line in production
            $employee = Employee::find($request->input('employee_id'));

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found.',
                ], 404);
            }

            // Custom validation for participants structure
            $request->validate([
                'participants' => 'required|array',
                'participants.*' => 'required|array|min:1',
                'participants.*.*' => 'required|exists:employees,id',
            ], [
                'participants.required' => 'Meeting participants are required.',
                'participants.*.required' => 'Each meeting must have participants.',
                'participants.*.min' => 'Each meeting must have at least one participant.',
                'participants.*.*.exists' => 'One or more selected participants do not exist.',
            ]);

            // Main validation rules
            $validatedData = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'template_id' => 'required|exists:itinerary_templates,id',
                'greeting_message' => 'required|string|max:500',
                'resort_transportaion_id' => 'required|exists:resort_transportations,id', // Fixed spelling

                // Arrival details (always required)
                'arrival_date' => ['required', 'date_format:d/m/Y', function ($attribute, $value, $fail) use ($employee) {
                    if (empty($value)) {
                        $fail('Arrival date is required.');
                        return;
                    }
                    
                    try {
                        $arrivalDate = Carbon::createFromFormat('d/m/Y', $value);
                        if (!$arrivalDate) {
                            $fail('Invalid arrival date format (dd/mm/yyyy).');
                            return;
                        }
                        
                        if ($arrivalDate->gte(Carbon::parse($employee->joining_date))) {
                            $fail('Arrival date must be before the joining date (' . 
                                Carbon::parse($employee->joining_date)->format('d/m/Y') . ').');
                        }
                    } catch (\Exception $e) {
                        $fail('Invalid arrival date format. Please use dd/mm/yyyy format.');
                    }
                }],
                'arrival_time' => 'required|date_format:H:i',

                // Domestic flight details (conditionally required)
                'domestic_flight_date' => [
                    'nullable', 
                    'date_format:d/m/Y', 
                    function ($attribute, $value, $fail) use ($employee, $request) {
                        if ($request->input('resort_transportaion_id') == config('settings.ReverseTransportations.domestic_flight') && empty($value)) {
                            $fail('Domestic flight date is required for this transportation type.');
                            return;
                        }
                        
                        if (!empty($value)) {
                            try {
                                $date = Carbon::createFromFormat('d/m/Y', $value);
                                if (!$date) {
                                    $fail('Invalid domestic flight date format (dd/mm/yyyy).');
                                    return;
                                }
                                
                                if ($date->gte(Carbon::parse($employee->joining_date))) {
                                    $fail('Domestic flight date must be before joining date.');
                                }
                            } catch (\Exception $e) {
                                $fail('Invalid domestic flight date format.');
                            }
                        }
                    }
                ],
                'domestic_departure_time' => 'nullable|date_format:H:i|required_if:resort_transportation_id,'.config('settings.ReverseTransportations.domestic_flight'),
                'domestic_arrival_time' => 'nullable|date_format:H:i|required_if:resort_transportation_id,'.config('settings.ReverseTransportations.domestic_flight'),

                // Speedboat details (conditionally required)
                'speedboat_date' => [
                    'nullable', 
                    'date_format:d/m/Y', 
                    function ($attribute, $value, $fail) use ($employee, $request) {
                        if ($request->input('resort_transportaion_id') == config('settings.ReverseTransportations.speedboat') && empty($value)) {
                            $fail('Speedboat date is required for this transportation type.');
                            return;
                        }
                        
                        if (!empty($value)) {
                            try {
                                $date = Carbon::createFromFormat('d/m/Y', $value);
                                if (!$date) {
                                    $fail('Invalid speedboat date format (dd/mm/yyyy).');
                                    return;
                                }
                                
                                if ($date->gte(Carbon::parse($employee->joining_date))) {
                                    $fail('Speedboat date must be before joining date.');
                                }
                            } catch (\Exception $e) {
                                $fail('Invalid speedboat date format.');
                            }
                        }
                    }
                ],
                'speedboat_name' => 'nullable|string|max:255|required_if:resort_transportation_id,'.config('settings.ReverseTransportations.speedboat'),
                'speedboat_departure_time' => 'nullable|date_format:H:i|required_if:resort_transportation_id,'.config('settings.ReverseTransportations.speedboat'),
                'speedboat_arrival_time' => 'nullable|date_format:H:i|required_if:resort_transportation_id,'.config('settings.ReverseTransportations.speedboat'),
                'captain_number' => 'nullable|string|max:255|required_if:resort_transportation_id,'.config('settings.ReverseTransportations.speedboat'),

                // Seaplane details (conditionally required)
                'seaplane_date' => [
                    'nullable', 
                    'date_format:d/m/Y', 
                    function ($attribute, $value, $fail) use ($employee, $request) {
                        if ($request->input('resort_transportaion_id') == config('settings.ReverseTransportations.seaplane') && empty($value)) {
                            $fail('Seaplane date is required for this transportation type.');
                            return;
                        }
                        
                        if (!empty($value)) {
                            try {
                                $date = Carbon::createFromFormat('d/m/Y', $value);
                                if (!$date) {
                                    $fail('Invalid seaplane date format (dd/mm/yyyy).');
                                    return;
                                }
                                
                                if ($date->gte(Carbon::parse($employee->joining_date))) {
                                    $fail('Seaplane date must be before joining date.');
                                }
                            } catch (\Exception $e) {
                                $fail('Invalid seaplane date format.');
                            }
                        }
                    }
                ],
                'seaplane_name' => 'nullable|string|max:255|required_if:resort_transportation_id,'.config('settings.ReverseTransportations.seaplane'),
                'seaplane_departure_time' => 'nullable|date_format:H:i|required_if:resort_transportation_id,'.config('settings.ReverseTransportations.seaplane'),
                'seaplane_arrival_time' => 'nullable|date_format:H:i|required_if:resort_transportation_id,'.config('settings.ReverseTransportations.seaplane'),

                // Hotel details (always required)
                'hotel_id' => 'required|string|max:255',
                'hotel_name' => 'required|string|max:255',
                'hotel_contact_no' => 'required|string|max:20',
                'booking_reference' => 'required|string|max:255',
                'hotel_address' => 'required|string|max:1000',

                // Medical details (always required)
                'medical_date' => [
                    'required', 
                    'date_format:d/m/Y', 
                    function ($attribute, $value, $fail) use ($employee, $request) {                        
                        if (!empty($value)) {
                            try {
                                $date = Carbon::createFromFormat('d/m/Y', $value);
                                if (!$date) {
                                    $fail('Invalid medical date format (dd/mm/yyyy).');
                                    return;
                                }
                                
                                if ($date->gte(Carbon::parse($employee->joining_date))) {
                                    $fail('Medical date must be before joining date.');
                                }
                            } catch (\Exception $e) {
                                $fail('Invalid medical date format.');
                            }
                        }
                    }
                ],
                'medical_center_name' => 'required|string|max:255',
                'medical_center_contact_no' => 'required|string|max:20',
                'medical_type' => 'required|string|max:255',
                'medical_time' => 'required|string|max:255',
                'approx_time' => 'required|date_format:H:i',

                // Employee assignments (always required)
                'pickup_employee_id' => 'required|exists:employees,id',
                'accompany_medical_employee_id' => 'required|exists:employees,id',

                // Files (always required)
                'entry_pass_file' => 'required|file|mimes:pdf',
                'flight_ticket_file' => 'required|file|mimes:pdf',
                'domestic_flight_ticket' => 'nullable|file|mimes:pdf|required_if:resort_transportation_id,'.config('settings.ReverseTransportations.domestic_flight'),

                // Meetings
                'meeting_date.*' => ['required', 'date_format:d/m/Y'],
                'meeting_title.*' => 'required|string|max:255',
                'meeting_time.*' => 'required|date_format:H:i',
                'meeting_link.*' => 'required|string|max:500',
            ]);

            DB::beginTransaction();
            // dd($validatedData); // Debugging line, remove after testing
            // Create the main itinerary record
            $onboardingItinerary = EmployeeItineraries::create([
                'resort_id' => $employee->resort_id,
                'employee_id' => $validatedData['employee_id'],
                'template_id' => $validatedData['template_id'],
                'greeting_message' => $validatedData['greeting_message'],
                
                // Arrival details
                'arrival_date' => Carbon::createFromFormat('d/m/Y', $validatedData['arrival_date'])->format('Y-m-d'),
                'arrival_time' => $validatedData['arrival_time'],
                
                // Transportation details
                'resort_transportation_id' => $validatedData['resort_transportaion_id'],
                
                // Domestic flight details (if provided)
                'domestic_flight_date' => isset($validatedData['domestic_flight_date']) 
                    ? Carbon::createFromFormat('d/m/Y', $validatedData['domestic_flight_date'])->format('Y-m-d') 
                    : null,
                'domestic_departure_time' => $validatedData['domestic_departure_time'] ?? null,
                'domestic_arrival_time' => $validatedData['domestic_arrival_time'] ?? null,
                
                // Speedboat details (if provided)
                'speedboat_name' => $validatedData['speedboat_name'] ?? null,
                'speedboat_date' => isset($validatedData['speedboat_date']) 
                    ? Carbon::createFromFormat('d/m/Y', $validatedData['speedboat_date'])->format('Y-m-d') 
                    : null,
                'speedboat_departure_time' => $validatedData['speedboat_departure_time'] ?? null,
                'speedboat_arrival_time' => $validatedData['speedboat_arrival_time'] ?? null,
                'captain_number' => $validatedData['captain_number'] ?? null,
                
                // Seaplane details (if provided)
                'seaplane_name' => $validatedData['seaplane_name'] ?? null,
                'seaplane_date' => isset($validatedData['seaplane_date']) 
                    ? Carbon::createFromFormat('d/m/Y', $validatedData['seaplane_date'])->format('Y-m-d') 
                    : null,
                'seaplane_departure_time' => $validatedData['seaplane_departure_time'] ?? null,
                'seaplane_arrival_time' => $validatedData['seaplane_arrival_time'] ?? null,
                
                // Hotel details
                'hotel_id' => $validatedData['hotel_id'],
                'hotel_name' => $validatedData['hotel_name'],
                'hotel_contact_no' => $validatedData['hotel_contact_no'],
                'booking_reference' => $validatedData['booking_reference'],
                'hotel_address' => $validatedData['hotel_address'],
                
                // Medical details
                'medical_center_name' => $validatedData['medical_center_name'],
                'medical_center_contact_no' => $validatedData['medical_center_contact_no'],
                'medical_type' => $validatedData['medical_type'],
                'medical_date' => isset($validatedData['medical_date']) 
                    ? Carbon::createFromFormat('d/m/Y', $validatedData['medical_date'])->format('Y-m-d') 
                    : null,
                'medical_time' => $validatedData['medical_time'],
                'approx_time' => $validatedData['approx_time'],
                
                // Employee assignments
                'pickup_employee_id' => $validatedData['pickup_employee_id'],
                'accompany_medical_employee_id' => $validatedData['accompany_medical_employee_id'],
            ]);

            // dd($request->file('entry_pass_file'));

            // Handle file uploads
            $entryPassPath = $this->uploadFile($request->file('entry_pass_file'), $employee);

            $flightTicketPath = $this->uploadFile($request->file('flight_ticket_file'), $employee);
            $domestic_flight_ticket_path = $request->hasFile('domestic_flight_ticket') 
                ? $this->uploadFile($request->file('domestic_flight_ticket'), $employee)
                : null;
            // dd($validatedData);
            $onboardingItinerary->update([
                'entry_pass_file' => $entryPassPath,
                'flight_ticket_file' => $flightTicketPath,
                'domestic_flight_ticket' => $domestic_flight_ticket_path,
            ]);
            // Create meeting records
            if (isset($validatedData['meeting_title']) && is_array($validatedData['meeting_title'])) {
                foreach ($validatedData['meeting_title'] as $index => $meetingTitle) {
                    $meetingDate = Carbon::createFromFormat('d/m/Y', $validatedData['meeting_date'][$index])->format('Y-m-d');
                    
                    // Handle participants properly
                    $participants = $request->input("participants.{$index}", []);
                    $participantIds = '';
                    
                    if (is_array($participants) && !empty($participants)) {
                        // Remove any empty values and ensure all are valid
                        $validParticipants = array_filter($participants, function($id) {
                            return !empty($id) && is_numeric($id);
                        });
                        
                        if (!empty($validParticipants)) {
                            $participantIds = implode(',', $validParticipants);
                        }
                    }

                    // Log for debugging
                    \Log::info("Creating meeting {$index}", [
                        'title' => $meetingTitle,
                        'date' => $meetingDate,
                        'time' => $validatedData['meeting_time'][$index],
                        'participants_raw' => $participants,
                        'participants_processed' => $participantIds
                    ]);

                    EmployeeItinerariesMeeting::create([
                        'employee_itinerary_id' => $onboardingItinerary->id,
                        'meeting_title' => $meetingTitle,
                        'meeting_date' => $meetingDate,
                        'meeting_time' => $validatedData['meeting_time'][$index],
                        'meeting_link' => $validatedData['meeting_link'][$index],
                        'meeting_participant_ids' => $participantIds, // This should now have comma-separated IDs
                    ]);
                }
            }

            DB::commit();

            // Send notifications
            $this->sendOnboardingNotifications($onboardingItinerary);

            return response()->json([
                'success' => true,
                'message' => 'Onboarding itinerary created successfully!',
                'data' => [
                    'id' => $onboardingItinerary->id,
                    'employee_name' => $employee->full_name,
                    'arrival_date' => $validatedData['arrival_date'],
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Itinerary creation failed: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create itinerary. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    private function uploadFile($file, $employee)
    {
        // dd($employee,$file);
        if (!$file) {
            return null;
        }
        // dd( $employee->resort_id, $file, $employee->Emp_id);
        $path = Common::AWSEmployeeFileUpload(
            $employee->resort_id,
            $file,
            $employee->Emp_id
        );

        return $path['status'] ? $path['Chil_file_id'] : null;
    }

    private function sendOnboardingNotifications($onboardingItinerary)
    {
        try {
            // Notify the main onboarded employee
            $employee = $onboardingItinerary->employee;
            if ($employee && $employee->resortAdmin) {
                $msg = "📢 Your onboarding itinerary has been created.\n" .
                    "👤 Name: {$employee->resortAdmin->full_name}\n" .
                    "📅 Arrival Date: " . Carbon::parse($onboardingItinerary->arrival_date)->format('d M Y') . "\n" .
                    "🕒 Arrival Time: {$onboardingItinerary->arrival_time}\n";

                event(new ResortNotificationEvent(Common::nofitication(
                    $this->resort->resort_id,
                    10,
                    'Your Onboarding Itinerary is Ready',
                    $msg,
                    0,
                    $employee->id,
                    'People'
                )));
            }

            // Notify pickup assigned employee
            $pickupEmployee = Employee::find($onboardingItinerary->pickup_employee_id);
            if ($pickupEmployee && $pickupEmployee->resortAdmin) {
                $msg = "🚐 You have been assigned to pick up a new employee.\n" .
                    "👤 Employee: {$employee->resortAdmin->full_name}\n" .
                    "📅 Arrival Date: " . Carbon::parse($onboardingItinerary->arrival_date)->format('d M Y'). "\n" .
                    "🕒 Arrival Time: {$onboardingItinerary->arrival_time}\n";

                event(new ResortNotificationEvent(Common::nofitication(
                    $this->resort->resort_id,
                    10,
                    'Pickup Assignment Notification',
                    $msg,
                    0,
                    $pickupEmployee->id,
                    'People'
                )));
            }

            // Notify medical accompany employee
            $medicalEmployee = Employee::find($onboardingItinerary->accompany_medical_employee_id);
            if ($medicalEmployee && $medicalEmployee->resortAdmin) {
                $msg = "🏥 You have been assigned to medically accompany a new employee.\n" .
                    "👤 Employee: {$employee->resortAdmin->full_name}\n" .
                    "📅 Arrival Date: " . Carbon::parse($onboardingItinerary->arrival_date)->format('d M Y'). "\n" .
                    "🕒 Arrival Time: {$onboardingItinerary->arrival_time}\n";

                event(new ResortNotificationEvent(Common::nofitication(
                    $this->resort->resort_id,
                    10,
                    'Medical Accompaniment Assignment',
                    $msg,
                    0,
                    $medicalEmployee->id,
                    'People'
                )));
            }

            // Notify meeting participants (from comma-separated string)
            $meetings = $onboardingItinerary->meetings;
            foreach ($meetings as $meeting) {
                $participantIds = explode(',', $meeting->meeting_participant_ids); // assuming participants are stored as comma-separated IDs

                foreach ($participantIds as $participantId) {
                    $participant = Employee::find($participantId);
                    if ($participant && $participant->resortAdmin) {
                        $msg = "📅 You are invited to a meeting.\n" .
                            "📝 Title: {$meeting->meeting_title}\n" .
                            "📆 Date: " . Carbon::parse($meeting->meeting_date)->format('d M Y') . "\n" .
                            "⏰ Time: " . $meeting->meeting_time . "\n" .
                            "🔗 Link: {$meeting->meeting_link}";

                        event(new ResortNotificationEvent(Common::nofitication(
                            $this->resort->resort_id,
                            10,
                            'Onboarding Meeting Invitation',
                            $msg,
                            0,
                            $participant->id,
                            'People'
                        )));
                    }
                }
            }

        } catch (\Exception $e) {
            \Log::error('Failed to send onboarding notifications: ' . $e->getMessage());
        }
    }

    /**
     * Get file URL for display
     */
    public function getFileUrl($filePath)
    {
        if (!$filePath) {
            return null;
        }

        return asset('storage/' . $filePath);
    }

    public function checkMeetingConflicts(Request $request)
    {
        $request->validate([
            'participant_ids' => 'required|array',
            'participant_ids.*' => 'exists:employees,id',
            'meeting_date' => 'required|date_format:Y-m-d',
            'meeting_time' => 'required|date_format:H:i',
            'current_meeting_id' => 'nullable|exists:employee_itineraries_meetings,id'
        ]);

        $conflicts = [];
        $meetingDate = $request->meeting_date;
        $meetingTime = $request->meeting_time;

        foreach ($request->participant_ids as $participantId) {
            $query = EmployeeItinerariesMeeting::whereHas('itinerary', function($q) use ($participantId) {
                    $q->where('pickup_employee_id', $participantId)
                    ->orWhere('accompany_medical_employee_id', $participantId);
                })
                ->orWhereRaw("FIND_IN_SET(?, meeting_participant_ids)", [$participantId])
                ->where('meeting_date', $meetingDate)
                ->where('meeting_time', $meetingTime);

            // Exclude current meeting if editing
            if ($request->current_meeting_id) {
                $query->where('id', '!=', $request->current_meeting_id);
            }

            $existingMeetings = $query->get();

            foreach ($existingMeetings as $meeting) {
                $conflicts[] = [
                    'employee_id' => $participantId,
                    'employee_name' => Employee::find($participantId)->full_name ?? 'Unknown',
                    'date' => $meeting->meeting_date,
                    'time' => $meeting->meeting_time,
                    'existing_meeting' => $meeting->meeting_title,
                    'itinerary_id' => $meeting->employee_itinerary_id
                ];
            }
        }

        return response()->json([
            'has_conflicts' => !empty($conflicts),
            'conflicts' => $conflicts
        ]);
    }

    public function itiernaries(Request $request)
    {
        
        $page_title = 'Onboarding Itineraries List';

        if ($request->ajax()) {
            $query = EmployeeItineraries::with(['meetings', 'employee.resortAdmin'])
                ->where('resort_id', $this->resort->resort_id);

            if ($request['searchTerm']&& !empty($request['searchTerm'])) {
                $search = $request['searchTerm'];
                $query->whereHas('employee.resortAdmin', function ($q) use ($search) {
                    $q->where('first_name', 'like', '%' . $search . '%')
                        ->orWhere('last_name', 'like', '%' . $search . '%')
                        ->orWhere(DB::raw('CONCAT(first_name, " ", last_name)'), 'like', '%' . $search . '%')
                        ->orWhere('Emp_id', 'like', '%' . $search . '%');
                });
            }
            
            $itineraries = $query->get();

                $edit_class = '';
                $delete_class = '';

                if(Common::checkRouteWisePermission('people.onboarding.itinerary.list',config('settings.resort_permissions.edit')) == false){
                    $edit_class = 'd-none';
                }
                if(Common::checkRouteWisePermission('people.onboarding.itinerary.list',config('settings.resort_permissions.delete')) == false){
                    $delete_class = 'd-none';
                }


                return datatables()->of($itineraries)
                ->addColumn('employee_name', function ($row) {
                    $image = Common::getResortUserPicture($row->employee->resortAdmin->id ?? null);
                    $name = $row->employee->resortAdmin->full_name ?? 'Unknown';

                    return '<div class="tableUser-block">
                                <div class="img-circle"><img src="' . $image . '" alt="user"></div>
                                <span class="userApplicants-btn">' . $name . '</span>
                            </div>';
                })
                ->addColumn('arrival_date', function ($row) {
                    return Carbon::parse($row->arrival_date)->format('d M Y');
                })
                ->addColumn('arrival_time', function ($row) {
                    return $row->arrival_time;
                })
                ->addColumn('action', function ($row) use ($edit_class, $delete_class) {
                    $id = base64_encode($row->id);
                    $editimg = asset('resorts_assets/images/edit.svg');
                    $deleteimg = asset('resorts_assets/images/trash-red.svg');
                    $viewUrl = route('people.onboarding.itinerary.viewDetails', $id);
                    $editUrl = route('people.onboarding.itinerary.edit', $id);
                    return '<a href="' . $viewUrl . '" class="edit-row-btn"><i class="fa fa-eye"></i></a>
                            <a href="'.$editUrl.'" class="edit-row-btn '.$edit_class.'"><img src="' . $editimg . '" alt="Edit"></a>
                            <a href="#" class="delete-row-btn '.$delete_class.'" data-id="' . $id . '"><img src="' . $deleteimg . '" alt="Delete"></a>';
                })
                ->rawColumns(['action', 'employee_name'])
                ->make(true);
        }

        return view('resorts.people.onboarding.onboarding-list', compact('page_title'));
    }

    public function viewItineraryDetails($id)
    {
        if(Common::checkRouteWisePermission('people.onboarding.itinerary.list',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized action.');
        }
        try {

            // Decode ID if it’s base64-encoded
            $decodedId = base64_decode($id);

            // Fetch itinerary with relationships
            $itinerary = EmployeeItineraries::with(['employee.resortAdmin', 'meetings','template','pickupemployee.resortAdmin', 'accompanyMedicalEmployee.resortAdmin'])
                ->where('resort_id', $this->resort->resort_id)
                ->findOrFail($decodedId);
            // dd($itinerary);

            $page_title = 'View Itinerary Details';

            return view('resorts.people.onboarding.itinerary-details', compact('itinerary', 'page_title'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Unable to find itinerary.');
        }
    }

    public function editItinerary($id)
    {
        if(Common::checkRouteWisePermission('people.onboarding.itinerary.list',config('settings.resort_permissions.edit')) == false){
            $edit_class = 'd-none';
        }
        try {
            // Decode ID if it’s base64-encoded
            $decodedId = base64_decode($id);
            $resort_id = $this->resort->resort_id;
            $employees = Employee::with(['resortAdmin', 'department', 'position'])
            ->where('resort_id', $resort_id)->where('status','Active')
            ->where('rank', 6)
            ->get();
            $participants = Employee::with(['resortAdmin', 'department', 'position'])
            ->where('resort_id', $resort_id)->where('status','Active')
            ->whereIn('rank', [1, 2, 3, 8])
            ->get();
            // Fetch itinerary with relationships
            $itinerary = EmployeeItineraries::with(['employee.resortAdmin', 'meetings','template','pickupemployee.resortAdmin', 'accompanyMedicalEmployee.resortAdmin'])
                ->where('resort_id', $this->resort->resort_id)
                ->findOrFail($decodedId);
            $entry_pass_file = null;
            $flight_ticket_file = null;
            $domestic_flight_ticket = null;
            if (!empty($itinerary->entry_pass_file)) {
                $entry_pass_file = Common::GetAWSFile($itinerary->entry_pass_file, $itinerary->resort_id);
            }
            if (!empty($itinerary->flight_ticket_file)) {
                $flight_ticket_file = Common::GetAWSFile($itinerary->flight_ticket_file, $itinerary->resort_id);
            }
            if (!empty($itinerary->domestic_flight_ticket)) {
                $domestic_flight_ticket = Common::GetAWSFile($itinerary->domestic_flight_ticket, $itinerary->resort_id);
            }
            $transportations = ResortTransportation::where('resort_id', $resort_id)
            ->whereNotIn('transportation_option', ['International Flight'])
            ->pluck('transportation_option', 'id')
            ->toArray();

            $page_title = 'Edit Itinerary';

            return view('resorts.people.onboarding.itinerary-edit', compact('itinerary', 'page_title','employees','participants','entry_pass_file','flight_ticket_file','domestic_flight_ticket','transportations'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Unable to find itinerary.');
        }
    }

    public function updateItinerary(Request $request, $id)
    {
        try {
            $employee = Employee::find($request->input('employee_id'));

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found.',
                ], 404);
            }

            // Custom validation for participants structure
            $request->validate([
                'participants' => 'required|array',
                'participants.*' => 'required|array|min:1',
                'participants.*.*' => 'required|exists:employees,id',
            ], [
                'participants.required' => 'Meeting participants are required.',
                'participants.*.required' => 'Each meeting must have participants.',
                'participants.*.min' => 'Each meeting must have at least one participant.',
                'participants.*.*.exists' => 'One or more selected participants do not exist.',
            ]);

            // Main validation rules
            $validatedData = $request->validate([
                'greeting_message' => 'required|string|max:500',
                'resort_transportaion_id' => 'required|exists:resort_transportations,id',
                
                // Arrival details (always required)
                'arrival_date' => ['required', 'date_format:Y-m-d', function ($attribute, $value, $fail) use ($employee) {
                    try {
                        $arrivalDate = Carbon::createFromFormat('Y-m-d', $value);
                        if ($arrivalDate->gte(Carbon::parse($employee->joining_date))) {
                            $fail('Arrival date must be before the joining date (' . 
                                Carbon::parse($employee->joining_date)->format('Y-m-d') . ').');
                        }
                    } catch (\Exception $e) {
                        $fail('Invalid arrival date format. Please use YYYY-MM-DD format.');
                    }
                }],
                'arrival_time' => 'required',

                // Domestic flight details (conditionally required)
                'domestic_flight_date' => [
                    'nullable', 
                    'date_format:Y-m-d', 
                    function ($attribute, $value, $fail) use ($employee, $request) {
                        if ($request->input('resort_transportaion_id') == config('settings.ReverseTransportations.domestic_flight') && empty($value)) {
                            $fail('Domestic flight date is required for this transportation type.');
                            return;
                        }
                        
                        if (!empty($value)) {
                            try {
                                $date = Carbon::createFromFormat('Y-m-d', $value);
                                if ($date->gte(Carbon::parse($employee->joining_date))) {
                                    $fail('Domestic flight date must be before joining date.');
                                }
                            } catch (\Exception $e) {
                                $fail('Invalid domestic flight date format.');
                            }
                        }
                    }
                ],
                'domestic_departure_time' => 'nullable|required_if:resort_transportaion_id,'.config('settings.ReverseTransportations.domestic_flight'),
                'domestic_arrival_time' => 'nullable|required_if:resort_transportaion_id,'.config('settings.ReverseTransportations.domestic_flight'),

                // Speedboat details (conditionally required)
                'speedboat_date' => [
                    'nullable', 
                    'date_format:Y-m-d', 
                    function ($attribute, $value, $fail) use ($employee, $request) {
                        if ($request->input('resort_transportaion_id') == config('settings.ReverseTransportations.speedboat') && empty($value)) {
                            $fail('Speedboat date is required for this transportation type.');
                            return;
                        }
                        
                        if (!empty($value)) {
                            try {
                                $date = Carbon::createFromFormat('Y-m-d', $value);
                                if ($date->gte(Carbon::parse($employee->joining_date))) {
                                    $fail('Speedboat date must be before joining date.');
                                }
                            } catch (\Exception $e) {
                                $fail('Invalid speedboat date format.');
                            }
                        }
                    }
                ],
                'speedboat_name' => 'nullable|string|max:255|required_if:resort_transportaion_id,'.config('settings.ReverseTransportations.speedboat'),
                'speedboat_departure_time' => 'nullable|required_if:resort_transportaion_id,'.config('settings.ReverseTransportations.speedboat'),
                'speedboat_arrival_time' => 'nullable|required_if:resort_transportaion_id,'.config('settings.ReverseTransportations.speedboat'),
                'captain_number' => 'nullable|string|max:255|required_if:resort_transportaion_id,'.config('settings.ReverseTransportations.speedboat'),
                'location' => 'nullable|string|max:255|required_if:resort_transportaion_id,'.config('settings.ReverseTransportations.speedboat'),

                // Seaplane details (conditionally required)
                'seaplane_date' => [
                    'nullable',
                    'date_format:Y-m-d',
                    function ($attribute, $value, $fail) use ($employee, $request) {
                        if ($request->input('resort_transportaion_id') == config('settings.ReverseTransportations.seaplane') && empty($value)) {
                            $fail('Seaplane date is required for this transportation type.');
                            return;
                        }
                        
                        if (!empty($value)) {
                            try {
                                $date = Carbon::createFromFormat('Y-m-d', $value);
                                if ($date->gte(Carbon::parse($employee->joining_date))) {
                                    $fail('Seaplane date must be before joining date.');
                                }
                            } catch (\Exception $e) {
                                $fail('Invalid seaplane date format.');
                            }
                        }
                    }
                ],
                'seaplane_departure_time' => 'nullable|required_if:resort_transportaion_id,'.config('settings.ReverseTransportations.seaplane'),
                'seaplane_arrival_time' => 'nullable|required_if:resort_transportaion_id,'.config('settings.ReverseTransportations.seaplane'),

                // Hotel details (always required)
                'hotel_id' => 'required|string|max:255',
                'hotel_name' => 'required|string|max:255',
                'hotel_contact_no' => 'required|string|max:20',
                'booking_reference' => 'required|string|max:255',
                'hotel_address' => 'required|string|max:1000',

                // Medical details (always required)
                'medical_date' => [
                    'required', 
                    'date_format:Y-m-d', 
                    function ($attribute, $value, $fail) use ($employee) {                        
                        try {
                            $date = Carbon::createFromFormat('Y-m-d', $value);
                            if ($date->gte(Carbon::parse($employee->joining_date))) {
                                $fail('Medical date must be before joining date.');
                            }
                        } catch (\Exception $e) {
                            $fail('Invalid medical date format.');
                        }
                    }
                ],
                'medical_center_name' => 'required|string|max:255',
                'medical_center_contact_no' => 'required|string|max:20',
                'medical_type' => 'required|string|max:255',
                'medical_time' => 'required|string|max:255',
                'approx_time' => 'required',

                // Employee assignments (always required)
                'pickup_employee_id' => 'required|exists:employees,id',
                'accompany_medical_employee_id' => 'required|exists:employees,id',

                // Files (conditionally required)
                'entry_pass_file' => 'sometimes|file|mimes:pdf',
                'flight_ticket_file' => 'sometimes|file|mimes:pdf',
                'domestic_flight_ticket' => 'nullable|file|mimes:pdf|required_if:resort_transportaion_id,'.config('settings.ReverseTransportations.domestic_flight'),

                // Meetings
                'meeting_date.*' => ['required', 'date_format:Y-m-d'],
                'meeting_title.*' => 'required|string|max:255',
                'meeting_time.*' => 'required',
                'meeting_link.*' => 'required|string|max:500',
            ]);

            DB::beginTransaction();
            
            // Find the itinerary
            $itinerary = EmployeeItineraries::findOrFail($id);

            // Handle file uploads - only if new files are provided
            $entryPassFile = $request->hasFile('entry_pass_file') 
                ? $this->handleFileUpload($request, 'entry_pass_file', $itinerary->entry_pass_file)
                : $itinerary->entry_pass_file;
                
            $flightTicketFile = $request->hasFile('flight_ticket_file') 
                ? $this->handleFileUpload($request, 'flight_ticket_file', $itinerary->flight_ticket_file)
                : $itinerary->flight_ticket_file;
                
            $domesticFlightTicket = $request->hasFile('domestic_flight_ticket') 
                ? $this->handleFileUpload($request, 'domestic_flight_ticket', $itinerary->domestic_flight_ticket)
                : $itinerary->domestic_flight_ticket;

            // Update itinerary
            $updateData = [
                'greeting_message' => $request->greeting_message,
                'arrival_date' => $request->arrival_date,
                'arrival_time' => $request->arrival_time,
                'resort_transportation_id' => $request->resort_transportaion_id,
                'hotel_id' => $request->hotel_id,
                'hotel_name' => $request->hotel_name,
                'hotel_contact_no' => $request->hotel_contact_no,
                'booking_reference' => $request->booking_reference,
                'hotel_address' => $request->hotel_address,
                'medical_center_name' => $request->medical_center_name,
                'medical_center_contact_no' => $request->medical_center_contact_no,
                'medical_type' => $request->medical_type,
                'medical_date' => $request->medical_date,
                'medical_time' => $request->medical_time,
                'approx_time' => $request->approx_time,
                'pickup_employee_id' => $request->pickup_employee_id,
                'accompany_medical_employee_id' => $request->accompany_medical_employee_id,
                'entry_pass_file' => $entryPassFile,
                'flight_ticket_file' => $flightTicketFile,
            ];

            // Add transportation-specific fields
            $transportationType = $request->resort_transportaion_id;
            
            if ($transportationType == config('settings.ReverseTransportations.domestic_flight')) {
                $updateData['domestic_flight_date'] = $request->domestic_flight_date;
                $updateData['domestic_departure_time'] = $request->domestic_departure_time;
                $updateData['domestic_arrival_time'] = $request->domestic_arrival_time;
                $updateData['domestic_flight_ticket'] = $domesticFlightTicket;
            } 
            elseif ($transportationType == config('settings.ReverseTransportations.speedboat')) {
                $updateData['speedboat_name'] = $request->speedboat_name;
                $updateData['captain_number'] = $request->captain_number;
                $updateData['location'] = $request->location;
                $updateData['speedboat_date'] = $request->speedboat_date;
                $updateData['speedboat_departure_time'] = $request->speedboat_departure_time;
                $updateData['speedboat_arrival_time'] = $request->speedboat_arrival_time;
            }
            elseif ($transportationType == config('settings.ReverseTransportations.seaplane')) {
                $updateData['seaplane_date'] = $request->seaplane_date;
                $updateData['seaplane_departure_time'] = $request->seaplane_departure_time;
                $updateData['seaplane_arrival_time'] = $request->seaplane_arrival_time;
            }

            $itinerary->update($updateData);

            // Handle meetings
            $this->handleMeetings($request, $itinerary);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Itinerary updated successfully!'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString() // Only for debugging, remove in production
            ], 500);
        }
    }

    // Helper method to get file icon based on extension
    private function getFileIcon($filename)
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'pdf':
                return 'fas fa-file-pdf text-danger';
            case 'doc':
            case 'docx':
                return 'fas fa-file-word text-primary';
            case 'jpg':
            case 'jpeg':
            case 'png':
                return 'fas fa-file-image text-success';
            default:
                return 'fas fa-file text-secondary';
        }
    }

    private function handleFileUpload($request, $fieldName, $currentFile)
    {
        if ($request->hasFile($fieldName)) {
            // Delete old file if exists
            if ($currentFile && Storage::exists($currentFile)) {
                Storage::delete($currentFile);
            }
            // Store new file
            return $request->file($fieldName)->store('itinerary_files');
        }
        return $currentFile;
    }

    private function handleMeetings($request, $itinerary)
    {
        // Handle deleted meetings
        if ($request->has('deleted_meetings')) {
            Meeting::whereIn('id', $request->deleted_meetings)->delete();
        }

        // Update or create meetings
        if ($request->has('meeting_title')) {
            foreach ($request->meeting_title as $index => $title) {
                $meetingData = [
                    'title' => $title,
                    'meeting_date' => $request->meeting_date[$index],
                    'meeting_time' => $request->meeting_time[$index],
                    'meeting_link' => $request->meeting_link[$index],
                    'meeting_participant_ids' => implode(',', $request->participants[$index]),
                ];

                if (!empty($request->meeting_id[$index])) {
                    // Update existing meeting
                    $meeting = EmployeeItinerariesMeeting::find($request->meeting_id[$index]);
                    if ($meeting) {
                        $meeting->update($meetingData);
                    }
                } else {
                    // Create new meeting
                    $itinerary->meetings()->create($meetingData);
                }
            }
        }
    }
}