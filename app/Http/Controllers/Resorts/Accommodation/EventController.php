<?php

namespace App\Http\Controllers\Resorts\Accommodation;

use Carbon\Carbon;
use DB;
use Auth;
use Common;

use Illuminate\Http\Request;
use App\Models\BuildingModel;

use App\Models\InventoryModule;
use App\Models\MaintanaceRequest;
use App\Http\Controllers\Controller;
use App\Models\ChildMaintananceRequest;
class EventController extends Controller
{

    public $resort;
    public $reporting_to;
    protected $underEmp_id=[];
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
        if($this->resort->is_master_admin == 0){
            $this->reporting_to = $this->resort->GetEmployee->id;
            $this->underEmp_id = Common::getSubordinates($this->reporting_to);
        }
    }

    public function index(Request $request)
    {
        // Get the start and end date of the current month
       

        // If the request is AJAX, return maintenance requests for the calendar

        // Pass the date-wise data to the view for display
        $page_title = 'Calendar';
        $Building = BuildingModel::where('resort_id', $this->resort->resort_id)->get();
        $InventoryModule = InventoryModule::where('resort_id', $this->resort->resort_id)->get();
        return view('resorts.Accommodation.Event.index', compact('page_title','Building','InventoryModule'));
    }
    public function getClanderData(Request $request)
    {
        if ($request->ajax()) {
            // Fetch the maintenance requests for the calendar
            $MaintanaceRequest = MaintanaceRequest::join("employees as t3","t3.id","maintanace_requests.Raised_By")
                ->join("resort_admins as t1","t1.id","t3.Admin_Parent_id")
                ->join("resort_departments as t4","t4.id","t3.Dept_id")
                ->where('maintanace_requests.resort_id', $this->resort->resort_id)
                ->whereNotIn('maintanace_requests.Status', ['Closed', 'On-Hold'])
                ->orderBy('maintanace_requests.id', 'desc')
                ->get(['t1.id as Parentid', 't1.first_name', 't1.last_name', 'maintanace_requests.*']);

            // Map the data to return it in a suitable format for the calendar
            $MaintanaceRequest = $MaintanaceRequest->map(function ($row) {
                return [
                    'id' => $row->id,
                    'title' => $row->descriptionIssues, // Title for the event
                    'start' => $row->date, // Start date for the event
                    'end' => $row->date, // End date for the event
                    'description' => $row->descriptionIssues // Description for the event
                ];
            });

            // Return the data as JSON for the calendar
            return response()->json($MaintanaceRequest);
        }

    }

    public function Sidelist(Request $request)
    {

        $startOfMonth = Carbon::now()->day(1);
        $endOfMonth = Carbon::now()->endOfMonth();
        // Get the maintenance requests for the current month
        $CurrentMonthMaintanaceRequest =    MaintanaceRequest::join("employees as t3","t3.id","maintanace_requests.Raised_By")
            ->join("resort_admins as t1","t1.id","t3.Admin_Parent_id")
            ->join("resort_departments as t4","t4.id","t3.Dept_id")
            ->join('building_models as t5', 't5.id', '=', 'maintanace_requests.building_id')
            ->where('maintanace_requests.resort_id', $this->resort->resort_id)
            ->whereNotIn('maintanace_requests.Status', ['Closed', 'On-Hold'])
            ->whereBetween('maintanace_requests.date', [$startOfMonth, $endOfMonth])
            ->orderBy('maintanace_requests.id', 'desc')
            ->orderBy('date', 'DESC')->orderBy('id', 'DESC')->get(['t1.id as Parentid', 't1.first_name', 't1.last_name', 'maintanace_requests.*','t5.BuildingName']);

        $dateWiseData = [];

        // Process each maintenance request
        $processedRequests = $CurrentMonthMaintanaceRequest->map(function ($row) {
            $formattedDate = date('D d M Y', strtotime($row->date)); // Format the date
            $row->EmployeeName = ucfirst($row->first_name . ' ' . $row->last_name); // Full name of the employee
            $row->profileImg = isset($row->Parentid) ? Common::getResortUserPicture($row->Parentid) : '-'; // Profile picture
            $row->date = $formattedDate; // Add formatted date to the request
            $row->descriptionIssues = $row->descriptionIssues;
            $row->Location = $row->BuilidngData->BuildingName . ', Room No - ' . $row->RoomNo . ', Floor No -' . $row->FloorNo;
            $string ='';
            if($row->priority == 'Low')
            {
                $string = '<span class="badge badge-blueNew border-0">Low</span>';
            }
            elseif($row->priority == 'Medium')
            {
                $string = '<span class="badge badge-themeWarning border-0">Medium</span>';
            }
            elseif($row->priority == 'High')
            {
                $string = '<span class="badge badge-danger">High</span>';
            }
            $row->priority = $string;
            return $row;
        });

        foreach ($processedRequests as $request)
        {
            $dateWiseData[$request->date][] = $request;
        }


        $html = '';
        if($dateWiseData)
        {
          

          
            foreach ($dateWiseData as $key => $items) 
            {
                // Split the date‐key into [ year, month, day ] or however your key is structured
                $parts = explode(' ', $key);
                $year  = $parts[0] ?? '';
                $month = $parts[1] ?? '';
                $day   = $parts[2] ?? '';
            
                // Open wrapper for this date
                $html .= '<div class="d-flex">';
                  // Date block
                  $html .= sprintf(
                      '<div class="date-block bg">%s <h5>%s</h5> %s</div>',
                      htmlspecialchars($day,   ENT_QUOTES, 'UTF-8'),
                      htmlspecialchars($month, ENT_QUOTES, 'UTF-8'),
                      htmlspecialchars($year,  ENT_QUOTES, 'UTF-8')
                  );
            
                  // Container for all entries on this date
                  $html .= '<div>';
            
                  foreach ($items as $d1) 
                  {
                      // Top block with description
                      $html .= '<div class="leaveUser-bgBlock success">'
                             . '<h6>' . htmlspecialchars($d1->descriptionIssues, ENT_QUOTES, 'UTF-8') . '</h6>'
                             . '</div>';
            
                      // User info block
                      $html .= '<div class="leaveUser-block">';
                        $html .= '<div class="tableUser-block">';
                          $html .= '<div class="img-circle">'
                                 .    '<img src="' . htmlspecialchars($d1->profileImg, ENT_QUOTES, 'UTF-8') . '" alt="user">'
                                 . '</div>';
                          $html .= '<span class="userApplicants-btn">'
                                 .    htmlspecialchars($d1->EmployeeName, ENT_QUOTES, 'UTF-8')
                                 . '</span>';
                        $html .= '</div>'; // .tableUser-block
            
                        // Location and priority
                        $html .= '<p>' . htmlspecialchars($d1->Location, ENT_QUOTES, 'UTF-8') . '</p>';
                        // If priority contains HTML, echo it raw; otherwise wrap in htmlspecialchars()
                        $html .= $d1->priority;
                      $html .= '</div>'; // .leaveUser-block
                  }
            
                  $html .= '</div>'; // inner container
                $html .= '</div>';   // .d-flex
            }
        }

        return response()->json(['success'=>true,'data'=>$html]);
    }
}
