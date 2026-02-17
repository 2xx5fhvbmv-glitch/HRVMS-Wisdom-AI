<?php
namespace App\Http\Controllers\Resorts\Support;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\SupportEmail;
use App\Mail\SupportReplyEmail;
use App\Models\SupportCategory;
use App\Models\Support;
use App\Models\SupportMessages;
use App\Models\Resort;
use App\Models\Settings;
use App\Helpers\Common;
use Config;
use Auth;
use DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule; 
class SupportController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
    }

    public function index(Request $request)
    {
        
        $resort = Resort::findOrFail( $this->resort->resort_id);
        $supportPreferences = explode(",",$resort->support_preference);

        $page_title ="Support List";
        $categories = SupportCategory::where('status','active')->get();
        return view('resorts.support.index',compact('page_title','categories','supportPreferences'));
    }

    public function getSupportData(Request $request)
    {
        $loggedInEmployee = $this->resort->getEmployee;
        if (!$loggedInEmployee) {
            abort(403, "Access Denied");
        }

        // SLA & Business hours logic
        $currentDay = now()->format('l');
        $currentTime = now()->format('H:i:s');
        $resort = Resort::findOrFail($this->resort->resort_id);
        $is24x7 = $resort->Support_SLA === '24/7 support';

        $isWithinBusinessHours = $is24x7;
        if (!$is24x7) {
            $businessHour = $resort->businessHours->firstWhere('day_of_week', $currentDay);
            if ($businessHour) {
                $isWithinBusinessHours = $currentTime >= $businessHour->start_time && $currentTime <= $businessHour->end_time;
            }
        }

        $settings = Settings::first();
        $supportEmail = $settings->support_email;

        $rank = config('settings.Position_Rank');
        $current_rank = $loggedInEmployee->rank ?? null;
        $available_rank = $rank[$current_rank] ?? '';
        $isHR = ($available_rank === "HR");

        // Define sortable columns (DataTables index => DB column)
        $columns = [
            0 => 'ticketID',
            1 => null, // employee_name is not sortable
            2 => 'support_categories.name',
            3 => 'subject',
            4 => 'created_at',
            5 => 'status',
        ];

        // Build query
        $query = Support::with(['support_category', 'createdBy']);

        if (!$isHR) {
            $query->where('created_by', $loggedInEmployee->id);
        }

        // Apply filters
        if ($request->filled('searchTerm')) {
            $query->where(function ($q) use ($request) {
                $q->where('subject', 'like', '%' . $request->searchTerm . '%')
                ->orWhereHas('support_category', function ($q2) use ($request) {
                    $q2->where('name', 'like', '%' . $request->searchTerm . '%');
                });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        // Handle ordering
        $orderColumnIndex = $request->input('order.0.column');
        $orderDirection = $request->input('order.0.dir', 'desc');

        if (isset($columns[$orderColumnIndex]) && $columns[$orderColumnIndex] !== null) {
            $orderColumn = $columns[$orderColumnIndex];

            if ($orderColumn === 'support_categories.name') {
                $query->leftJoin('support_categories', 'support_categories.id', '=', 'support_tickets.category_id')
                    ->select('support_tickets.*', 'support_categories.name as category_name');
            }

            $query->orderBy($orderColumn, $orderDirection);
        } else {
            // Default order
            $query->orderBy('created_at', 'desc');
        }

        return datatables()->of($query)
            ->editColumn('ticketID', fn($support) => $support->ticketID ?? 'N/A')

            ->addColumn('employee_name', function ($support) {
                $image = Common::getResortUserPicture($support->createdBy->id);
                $name = optional($support->createdBy)->first_name
                    ? ucwords($support->createdBy->first_name . ' ' . $support->createdBy->last_name)
                    : 'N/A';
                return '<div class="tableUser-block">
                            <div class="img-circle"><img src="'.$image.'" alt="user"></div>
                            <span class="userApplicants-btn">'.$name.'</span>
                        </div>';
            })

            ->editColumn('category', fn($support) => $support->support_category->name ?? ($support->category_name ?? 'N/A'))

            ->editColumn('subject', fn($support) => $support->subject)

            ->editColumn('created_at', fn($support) => \Carbon\Carbon::parse($support->created_at)->format('d M Y'))

            ->editColumn('status', function ($support) {
                $statusClasses = [
                    'New' => 'badge-themeSuccess',
                    'On Hold' => 'badge-warning',
                    'In Progress' => 'badge-info',
                    'Close' => 'badge-danger'
                ];
                $class = $statusClasses[$support->status] ?? 'badge-secondary';
                return '<span class="badge ' . $class . '">' . $support->status . '</span>';
            })

            ->addColumn('action', function ($support) use ($supportEmail, $isWithinBusinessHours) {
                if (!$isWithinBusinessHours) {
                    return '<span class="badge badge-warning">Support available during business hours</span>';
                }

                $buttons = '';
                if ($support->support_preference == "LiveChat") {
                    $chat_url = route('support.chat.system', base64_encode($support->id));
                    $buttons .= '<a href="'.$chat_url.'" title="Open Chat" class="btn-lg-icon icon-bg-blue mx-1">
                                    <i class="fas fa-comments"></i>
                                </a>';
                }

                if ($support->support_preference == "Email" && !empty($supportEmail)) {
                    $email_url = route('resort.email.replypage', base64_encode($support->id));
                    $view_url = route('resort.supports.view', base64_encode($support->id));
                    $buttons .= '<a href="'.$email_url.'" title="Reply via Email" class="btn-lg-icon icon-bg-yellow mx-1 reply-email">
                                    <i class="fas fa-envelope"></i> 
                                </a>';
                    $buttons .= '<a href="'.$view_url.'" class="btn-lg-icon icon-bg-skyblue">
                                    <img src="'.asset('resorts_assets/images/eye.svg').'" alt="icon">
                                </a>';
                }

                return $buttons;
            })

            ->rawColumns(['employee_name', 'status', 'action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $currentDay = now()->format('l');
        $currentTime = now()->format('H:i:s');
        $resort = Resort::findOrFail($this->resort->resort_id);
        $supportPreferences = explode(",", $resort->support_preference);
        $is24x7 = $resort->Support_SLA === '24/7 support';
        
        $businessHour = optional($resort->businessHours->firstWhere('day_of_week', $currentDay));
        $isWithinBusinessHours = $is24x7 
        ? true 
        : ($businessHour 
            ? ($currentTime >= $businessHour->start_time && $currentTime <= $businessHour->end_time) 
            : false);
        if (!$isWithinBusinessHours) {
            return response()->json(['success' => false, 'message' => 'Support available during business hours.'], 403);
        }
       
        $validator = Validator::make($request->all(), [
            'category' => [
                'required',
            ],
            'subject' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'max:255',
                'string',
            ],
            'attachments.*' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:51200', // 50MB max size
            ],
        ], [
            'category.required' => 'Please  Select Category.',
            'subject.required' => 'Please Enter subject.',
            'description.max' => 'The description  must not exceed 255 characters.',
            'attachments.in' => 'Please select file as jpg,jpeg,png,pdf".',
        ]);

        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }


        $employee = $this->resort->getEmployee;
        
        if (!$employee) 
        {
            return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
        }
        // $encoded_employee_id = base64_encode($employee->id);
        // $baseUploadPath = config('settings.support_ticket_attachments'); 
        // $uploadPath = $baseUploadPath . '/' . $encoded_employee_id . '/';
        // if (!file_exists(public_path($uploadPath))) {
        //     mkdir(public_path($uploadPath), 0755, true);
        // }
        // $uploadedFiles = [];
       
        do {
            $ticketId = 'TKT-' . strtoupper(Str::random(8)); // Example: TKT-A1B2C3D4
        } 
        while (Support::where('ticketID', $ticketId)->exists());
        // Store support ticket

        $ticket = Support::create([
            'resort_id' => $this->resort->resort_id,
            'ticketID' => $ticketId,
            'support_preference'=>$request->supportPreference,
            'category_id' => $request->category,
            'subject' => $request->subject,
            'description' => $request->description,
            'status' => 'New',
        ]);

        $uploadedFiles=[];
        if($ticket)
        {
            if ($request->hasFile('attachments')) 
            {
                foreach ($request->file('attachments') as $file) 
                {
                    $status =   Common::AWSEmployeeFileUpload($this->resort->resort_id, $file, $employee->Emp_id,true );
                  
                    if ($status['status'] == false) 
                    {
                        break;
                    }
                    else
                    {
                        if($status['status'] == true && isset($status['Chil_file_id']) && !empty($status['Chil_file_id']))
                        {

                            $filename = $file->getClientOriginalName();
                            $uploadedFiles[] = ['Filename' => $filename, 'Child_id' => $status['Chil_file_id']];
                        }
                       

                    }
                }

            }
      
            if(!empty($uploadedFiles))
            {
                $newticket = Support::findOrFail($ticket->id);
                $newticket->attachments = json_encode($uploadedFiles);
                $newticket->save();
            }

        }

        if($request->supportPreference == "Email")
        {
            SupportMessages::create([
                'ticket_id' => $ticket->id,
                'sender' => 'employee',
                'sender_id' => $employee->id,
                'message' => $request->description,
                'attachments' => count($uploadedFiles) > 0 ? json_encode($uploadedFiles) : null,
            ]);
    
            // Send Email Notification if enabled
            $settings = Settings::first();

            $supportEmail = $settings->support_email;
            
            Mail::to($supportEmail)->send(new SupportEmail($ticket, $resort));
        }
        return response()->json(['success' => true, 'message' => 'Ticket submitted successfully!', 'data' => $ticket]);
    }

    public function view($ticketId){
        $resort = Resort::findOrFail( $this->resort->resort_id);
        $loggedInEmployee = $this->resort->getEmployee->id;
        $page_title ="Support Email View";
        $support = Support::findOrFail(base64_decode($ticketId));
        $supportEmails = SupportMessages::where('ticket_id',base64_decode($ticketId))->get();
        return view('resorts.support.email-ticket',compact('page_title','support','supportEmails','loggedInEmployee'));
    }

    public function replyEMail($ticketId){
        $ticketId = base64_decode($ticketId);
        $resort = Resort::findOrFail( $this->resort->resort_id);
        $loggedInEmployee = $this->resort->getEmployee->id;
        $page_title ="Support Email View";
        $support = Support::findOrFail($ticketId);
        $supportEmails = SupportMessages::where('ticket_id',$ticketId)->get();
         // Send Email Notification if enabled
        $settings = Settings::first();
        $supportEmail = $settings->support_email;
        // dd($support);
        return view('resorts.support.email-reply',compact('page_title','support','supportEmails','loggedInEmployee','ticketId','supportEmail'));
    }

    public function sendReply(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'ticket_id' => 'required|exists:support,id',
            'to_email' => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'attachments.*' => 'nullable|file|mimes:pdf,xlsx|max:2048',
        ]);
        $ticket = Support::findOrFail($request->ticket_id);
        $resort = Resort::findorFail($ticket->resort_id);
        $uploadedFiles = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('support_attachments', $fileName, 'public');
                $uploadedFiles[] = $filePath;
            }
        }
        // Store the reply in the database
        $message = SupportMessages::create([
            'ticket_id' => $ticket->id,
            'message' => strip_tags($request->message), // Remove HTML for security
            'attachments' => count($uploadedFiles) > 0 ? json_encode($uploadedFiles) : null,
            'sender' => 'employee', // Assuming employee is replying
            'sender_id' =>  Auth::guard('resort-admin')->user()->GetEmployee->id,
        ]);
        $recipientEmail =$request->to_email;
        $replyBy = Auth::guard('resort-admin')->user()->first_name." ".Auth::guard('resort-admin')->user()->last_name;
        Mail::to($recipientEmail)->send(new SupportReplyEmail(
            $ticket,         // Ticket Information
            $resort,         // Resort Information
            $request->message, // Reply Message
            $replyBy         // Admin (Reply Sender)
        ));
        return redirect()->back()->with('success', 'Reply sent successfully!');
    }
}