<?php
namespace App\Http\Controllers\Resorts\Incident;

use App\Http\Controllers\Controller;
use App\Events\ResortNotificationEvent;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Incidents;
use App\Models\ResortAdmin;
use App\Models\IncidentsMeeting;
use App\Models\IncidentCategory;
use App\Models\IncidentCommittee;
use App\Models\IncidentSubCategory;
use App\Models\IncidentConfiguration;
use App\Models\IncidentCommitteeMember;
use App\Models\IncidentResolutionTimeline;
use App\Models\IncidentsMeetingParticipants;
use App\Models\IncidentsMeetingExternalParticipants;

use DB;
use Auth;
use Common;
use DateTime;
use Carbon\Carbon;

class IncidentMeetingCalendarController extends Controller
{
    public $resort;
    public $reporting_to;
    protected $underEmp_id=[];
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if($this->resort->is_master_admin == 0){
            $this->reporting_to = $this->resort->GetEmployee->id;
            $this->underEmp_id = Common::getSubordinates($this->reporting_to);
        }
    }

    public function index()
    {
        $resort_id = $this->resort->resort_id;
        $page_title ='Incident Calendar';
        return view('resorts.incident.meeting.calendar',compact('page_title'));
    }

    // IncidentController.php
    public function getIncidentMeetings(Request $request)
    {
        $query = IncidentsMeeting::with('participant.employee')
            ->select('id', 'meeting_subject', 'meeting_date', 'meeting_time', 'location');
    
        // Optional filtering by date range (e.g., when changing calendar month)
        if ($request->has('start') && $request->has('end')) {
            $query->whereBetween('meeting_date', [$request->start, $request->end]);
        }
    
        $meetings = $query->get()->map(function ($meeting) {
            return [
                'id' => $meeting->id,
                'title' => $meeting->meeting_subject,
                'date' => $meeting->meeting_date,
                'time' => $meeting->meeting_time,
                'location' => $meeting->location ?? '',
                'participants' => $meeting->participant->map(function ($p) {
                    return [
                        'avatar' => Common::getResortUserPicture($p->employee->Admin_Parent_id)
                            ?? asset('assets/images/default-user.svg')
                    ];
                }),
            ];
        });
    
        return response()->json($meetings);
    }
    

}