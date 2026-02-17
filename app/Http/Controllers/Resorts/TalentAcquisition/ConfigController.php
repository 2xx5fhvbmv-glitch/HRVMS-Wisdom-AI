<?php

namespace App\Http\Controllers\Resorts\TalentAcquisition;

use App\Http\Controllers\Controller;
use Common;
use Auth;
use App\Models\ResortDivision;
use App\Models\ResortDepartment;
use App\Models\ResortSection;
use App\Models\ResortPosition;
use App\Models\TicketAgent;
use App\Models\HiringSource;
use App\Models\TAnotificationChild;
use App\Models\TAnotificationParent;
use App\Models\JobAdvertisement;
use App\Models\ApplicationLink;
use App\Models\ResortAdmin;
use App\Models\TermsAndCondition;
use App\Models\ApplicantInterViewDetails;
use App\Events\ResortNotificationEvent;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Validator;
use DB;
use App\Models\Employee;

class ConfigController extends Controller
{
    public $resort;
    public $rank;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;


        $this->rank = isset($this->resort->GetEmployee) ? $this->resort->GetEmployee->id:3;

    }

    public function index()
    {
        try
        {
            $page_title = 'Configuration';
            $TicketAgent = TicketAgent::where('resort_id', $this->resort->resort_id)->orderBy('id', 'desc')->pluck('agents_email');
            $configset = JobAdvertisement::where('resort_id', $this->resort->resort_id)->first();
            $resort_divisions = ResortDivision::where('status', 'active')->where('resort_id',$this->resort->resort_id)->get();
            $termsAndCondition = TermsAndCondition::where('Resort_id', Auth::guard('resort-admin')->user()->resort_id)->first();


            return view('resorts.talentacquisition.config.index',compact('page_title','termsAndCondition','configset','resort_divisions','TicketAgent'));
        }
        catch( \Exception $e )
        {
            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());

            return view('resorts.talentacquisition.config.index',compact('resort_divisions','TicketAgent'));
        }
    }

    public function PositionSections(Request $request)
    {


        try
        {
            $ResortSection  = ResortSection::where('resort_id', $this->resort->resort_id)->where("dept_id",$request->deptId)->get();
            $ResortPosition  = ResortPosition::where('resort_id', $this->resort->resort_id)->where("dept_id",$request->deptId)->get();

            return response()->json(['success' => true, 'data' => ["ResortSection"=>$ResortSection,"ResortPosition"=>$ResortPosition]], 200);
        }
        catch( \Exception $e )
        {
            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to add Get the data.'], 500);

        }

    }

    public function AddTicketAgent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'agents_email' => 'required|unique:ticket_agents,agents_email,NULL,id,resort_id,' . $this->resort->resort_id,
            'name' => 'required',
        ], [
            'agents_email.required' => 'Please Enter Email Address.',
            'agents_email.unique' => 'Please Enter Unique Email Address.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        DB::beginTransaction();
        try
        {

            TicketAgent::create(['name'=> $request->name,'agents_email' => $request->agents_email,'Resort_id' => $this->resort->resort_id]);
            DB::commit();
            return response()->json(['success' => true, 'msg' => 'Ticket Agent added successfully.'], 200);

        }
        catch( \Exception $e )
        {

            DB::rollBack();
            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to add Agent Email.'], 500);

        }
    }

    public function GetAgentTicketList()
    {

        $TicketAgent = TicketAgent::where('resort_id', $this->resort->resort_id)->orderBy('id', 'desc')->get();

            return datatables()->of($TicketAgent)

                ->addColumn('Name', function ($row) {
                                    return $row->name;
                })
                
                ->addColumn('Email', function ($row) 
                {
                                return $row->agents_email;
                })
                ->addColumn('Email', function ($row) 
                {
                                return $row->agents_email;
                })
                ->addColumn('Action', function ($row) {
                    $editUrl = asset('resorts_assets/images/edit.svg');
                    $deleteUrl = asset('resorts_assets/images/trash-red.svg');

                    return '
                        <div class="d-flex align-items-center">
                            <a href="#" class="btn-lg-icon icon-bg-red edit-row-btn"
                           data-agent-id="'. htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8') . '" data-agent-id="' . htmlspecialchars($row->child_id, ENT_QUOTES, 'UTF-8') . '">
                                <img src="' . $editUrl . '" alt="Edit" class="img-fluid" />
                            </a>
                            <a href="#" class="btn-lg-icon icon-bg-red delete-row-btn"
                           data-center-id="'. htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8') . '" data-dept-id="' . htmlspecialchars($row->child_id, ENT_QUOTES, 'UTF-8') . '">
                                <img src="' . $deleteUrl . '" alt="Delete" class="img-fluid" />
                            </a>
                        </div>';
                })
                ->rawColumns(['Name', 'Email', 'Action'])
                ->make(true);

            try {  } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch data'], 500);
        }

    }

    public function inlineUpdateAgent(Request $request, $id){
        // Find the division by ID
        $agent = TicketAgent::find($id);
        // dd($request);

        if (!$agent) {
            return response()->json(['success' => false, 'message' => 'Agent not found.']);
        }

        $validator = Validator::make($request->all(), [
            'agents_email' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', // Enforce valid TLD
                Rule::unique('ticket_agents')
                    ->where(function ($query) {
                        return $query->where('Resort_id', $this->resort->resort_id);
                    })
                    ->ignore($id), // Ignore the current record by ID
            ],
            'name' => 'required|string|max:255',
        ], [
            'agents_email.unique' => 'This agent email already exists for the selected resort.',
            'agents_email.regex' => 'Please enter a valid email address with a proper domain (e.g., .com, .in).',
        ]);


        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        try {
            // Update the division's attributes
            $agent->name = $request->input('name');
            $agent->agents_email = $request->input('agents_email');
            
            // agent the changes
            $agent->save();

            // Return a JSON response
            return response()->json(['success' => true, 'message' => 'Agent updated successfully.']);
        } catch( \Exception $e ) {
            \Log::emergency( "File: ".$e->getFile() );
            \Log::emergency( "Line: ".$e->getLine() );
            \Log::emergency( "Message: ".$e->getMessage() );

            return response()->json(['success' => false, 'message' => 'Failed to update agent.']);
        }
    }

    public function DestroyAgentList($id)
    {
        DB::beginTransaction();
        try{

            TicketAgent::find($id)->delete();
            DB::commit();
            return response()->json(['success' => true, 'msg' => 'Ticket Agent Deleted successfully.'], 200);


        }
        catch( \Exception $e )
        {

            DB::rollBack();
            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to add Agent Email.'], 500);

        }

    }

    public function AddHiringSource(Request $request)
    {
        DB::beginTransaction();
        try
        {
            HiringSource::create(['source_name'=> $request->source_name,'colour'=> $request->color,'resort_id' => $this->resort->resort_id]);
            DB::commit();
             return response()->json(['success' => true, 'msg' => 'Hiring Source added successfully.'], 200);

        }
        catch( \Exception $e )
        {
            DB::rollBack();
            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to add Hiring Source.'], 500);

        }
    }

    public function GetHiringSource()
    {
        try {
            // Get hiring sources with proper ordering
            $hiring_sources = HiringSource::where('resort_id', $this->resort->resort_id)
                ->orderBy('id', 'desc')
                ->get();
            
            return datatables()->of($hiring_sources)
                ->addColumn('action', function ($row) {
                    $deleteUrl = asset('resorts_assets/images/trash-red.svg');
                    
                    return '
                        <div class="d-flex align-items-center">
                            <a href="#" class="btn-lg-icon icon-bg-red delete-source-btn" data-source-id="' . htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8') . '">
                                <img src="' . $deleteUrl . '" alt="Delete" class="img-fluid" />
                            </a>
                        </div>';
                })
                ->rawColumns(['source_name', 'colour', 'action'])
                ->make(true);
        } catch (\Exception $e) {
            // Log the error
            \Log::error('DataTables error: ' . $e->getMessage());
            
            // Return a proper JSON response for DataTables
            return response()->json([
                'error' => 'An error occurred while processing your request',
                'details' => $e->getMessage()
            ], 500);
        }

    }

    public function DestroyHiringSource($id)
    {
        DB::beginTransaction();
        try{
            HiringSource::find($id)->delete();
            DB::commit();
            return response()->json(['success' => true, 'msg' => 'Hiring Source Deleted successfully.'], 200);
        }
        catch( \Exception $e )
        {
            DB::rollBack();
            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete Hiring Source.'], 500);
        }
    }

    public function TaHoldVcanciesNotification(Request $request)
    {



        //     dd($request->all());
        DB::beginTransaction();
        try
        {
            $taupdaet = TAnotificationChild::where("id",$request->ta_id)
                                            ->update([
                                                                    "status"=>"Hold",
                                                                    "holding_date" => $request->HoldDate,
                                                                ]);
                                                                $rank = $this->resort->GetEmployee->rank;


            $getNotifications['FreshVacancies'] = Common::GetTheFreshVacancies($this->resort->resort_id,"Active", $rank);
            $view = view('resorts.renderfiles.FreshVacancies', compact( 'getNotifications'))->render();
            DB::commit();
            return response()->json(['success' => true,"view"=>$view , 'message' => 'Hire Requests is on Hold Now.'],200);

        }
        catch( \Exception $e )
        {

            DB::rollBack();
            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to Update Status.'], 500);

        }



    }

    public function TaRejectionVcanciesNotification(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            ['New_Vacancy_Rejected' => 'required'],
            ['New_Vacancy_Rejected.required' => 'Please Enter a reason for rejecting the new vacancy.']
        );
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        DB::beginTransaction();
        try
        {
            $taupdaet = TAnotificationChild::find($request->Rejectio_ta_id)->update(['status'=>"Rejected","reason"=>$request->New_Vacancy_Rejected]);
            $rank = $this->resort->GetEmployee->rank;
            $getNotifications['FreshVacancies'] = Common::GetTheFreshVacancies($this->resort->resort_id,"Active", $rank);
            $view = view('resorts.renderfiles.FreshVacancies', compact( 'getNotifications'))->render();



            // $TalentPool = Common::GetTheFreshVacancies($this->resort->resort_id,"Rejected");
            // $talentPoolview = view('resorts.renderfiles.talentPool', compact( 'TalentPool'))->render();

            DB::commit();

            return response()->json(['success' => true,"view"=>$view , 'message' => ' Your response has been Updated  successfully.'],200);

        }
        catch( \Exception $e )
        {

            DB::rollBack();
            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
            return response()->json(['success' => false, 'msg' => 'Failed to to Rejeact hiring.'], 500);

        }
    }

    public function TaApprovedVcanciesNotification(Request $request)
    {
        $type = config('settings.Notifications');
        // DB::beginTransaction();
        // try
        // {
            $rank = (int) $this->resort->GetEmployee->rank;
            $config = config('settings.Position_Rank');
            $parentNotification = TAnotificationParent::find($request->ta_id);
            $taupdaet = TAnotificationChild::where("Parent_ta_id", $request->ta_id)->where("Approved_By", $rank)->first();
            $childstatus =  TAnotificationChild::find($request->Child_ta_id);
            $childstatus->update(["status"=>"ForwardedToNext"]);
            if( $rank == Common::TaFinalApproval($this->resort->resort_id))
            {
                $taupdaet->update(["status"=>"ForwardedToNext"]);
                $ApplicationLink = ApplicationLink::updateOrCreate(["ta_child_id"=> $taupdaet->id,"Resort_id"=>$this->resort->resort_id],["ta_child_id"=> $taupdaet->id,"Resort_id"=>$this->resort->resort_id]);
            }
            else
            {
                $taupdaet->update(["status"=>"Approved" ,"Approved_By"=>$rank]);
            }
            if($taupdaet->Approved_By == 3 )
            {
                $newRank = 7; 
            }
            if($taupdaet->Approved_By == 7)
            {
                $newRank = 8;
            }
            else
            {
                $newRank = 8;
            }


                $sentto =  Employee::where('resort_id',$this->resort->resort_id)->where("rank",$newRank)->first();
               
                $msg = "New Vacancy has been Approved by ".$config[$rank]." and forwarded to ".$config[$newRank]." for further processing.";
                event(new ResortNotificationEvent(Common::nofitication(
                                                                        $this->resort->resort_id, // Make sure resort_id exists on the meetings table
                                                                        10,
                                                                        'Upcoming Investigation Meeting Reminder',
                                                                        $msg,
                                                                        0,
                                                                        $sentto->id,
                                                                        'Talent Acquisition'
                                                                    )));
            // }


            $getNotifications['FreshVacancies'] = Common::GetTheFreshVacancies($this->resort->resort_id,'Active',$rank);
            $view = view('resorts.renderfiles.FreshVacancies', compact( 'getNotifications'))->render();

            // Apprved Vacancy GM approved Or Rank With set in specific resort
            $TodoData = Common::GmApprovedVacancy($this->resort->resort_id,$rank);
            $Todolistview = view('resorts.renderfiles.TaTodoList', compact( 'TodoData'))->render();
            DB::commit();
            return response()->json(['success' => true,"view"=>$view ,'Todolistview'=>$Todolistview, 'message' => ' Your response has been Approved.'],200);

        // }
        // catch( \Exception $e )
        // {

        //     DB::rollBack();
        //     \Log::emergency("File: ".$e->getFile());
        //     \Log::emergency("Line: ".$e->getLine());
        //     \Log::emergency("Message: ".$e->getMessage());
        //     return response()->json(['success' => false, 'msg' => 'Failed to to Approved hiring.'], 500);
        // }
    }

    public function FianlApproval(Request $request)
    {
        // DB::beginTransaction();
        // try
        // {


            JobAdvertisement::UpdateOrCreate(["Resort_id"=>$this->resort->resort_id],["Resort_id"=>$this->resort->resort_id,
                                        "FinalApproval"=>(int)$request->FinalApproval]
                                    );
            return response()->json(['success' => true, 'msg' => ' Your Final Approval has been Updated successfully.'],200);
            // DB::commit();
        // }
        // catch( \Exception $e )
        // {

        //     DB::rollBack();
        //     \Log::emergency("File: ".$e->getFile());
        //     \Log::emergency("Line: ".$e->getLine());
        //     \Log::emergency("Message: ".$e->getMessage());
        //     return response()->json(['success' => false, 'msg' => 'Failed to to Update Final Approval .'], 500);
        // }
    }

    public function storeOrUpdateTC(Request $request)
    {
        // Validate the input
        $request->validate([
            'terms_and_condition' => 'required|string|max:65535',
        ]);

        try {
            // Retrieve or create the TermsAndCondition record for the logged-in resort admin
            $termsAndCondition = TermsAndCondition::updateOrCreate(
                ['Resort_id' => Auth::guard('resort-admin')->user()->resort_id], // Find by Resort_id
                ['terms_and_condition' => $request->input('terms_and_condition')] // Update or create this field
            );

            return response()->json([
                'success' => true,
                'message' => 'Terms and Conditions saved successfully!',
                'data' => $termsAndCondition,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving Terms and Conditions.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function saveFinalApproval(Request $request)
    {
        $request->validate([
            'FinalApproval' => 'required|string|max:255',
        ]);

        $resort_id = Auth::guard('resort-admin')->user()->resort_id;

        $job = JobAdvertisement::updateOrCreate(
            ['Resort_id' => $resort_id], // condition: one record per resort
            ['FinalApproval' => $request->FinalApproval] // update or create this field
        );

        return response()->json([
            'success' => true,
            'message' => 'Final Approval saved successfully',
        ]);
    }
}