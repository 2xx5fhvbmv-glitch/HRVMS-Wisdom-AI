<?php

namespace App\Http\Controllers\Resorts\TalentAcquisition;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JobDescription;
use App\Models\ResortSiteSettings;
use App\Models\Resort;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Validation\Rule;
use DB;
use App\Helpers\Common;
use Illuminate\Support\Str;
class JobDescriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public $resort;
     public function __construct()
     {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
     }
    public function index()
    {
        // if(Common::checkRouteWisePermission('resort.ta.jobdescription.index',config('settings.resort_permissions.view')) == false){
        //     return abort(403, 'Unauthorized access');
        // }
        $page_title="Job Description";

        return view('resorts.talentacquisition.jobdescription.index',compact('page_title'));
    }
    public function GetJobDescList(Request $request)
    {
            $complianceStatus = $request->get('compliance_status');
            $searchTerm = $request->get('searchTerm');

            $JobDescription = JobDescription::select([
                'job_descriptions.id',
                'job_descriptions.compliance',
                'job_descriptions.slug',
                't2.name as Division',
                't2.code as Division_Code',
                't3.name as Department',
                't3.code as Departmet_Code',
                't4.position_title as Position',

                't5.name as Positionname',
                't4.code as Position_Code',
                    't5.code as Section_Code',

                'job_descriptions.jobdescription',
                'job_descriptions.Resort_id',
                'job_descriptions.created_at' // ✅ ADD THIS LINE


            ])
            ->join('resort_divisions as t2', 't2.id', '=', 'job_descriptions.Division_id')
            ->join('resort_departments as t3', 't3.id', '=', 'job_descriptions.Department_id')
            ->leftjoin('resort_positions as t4', 't4.id', '=', 'job_descriptions.Position_id')
            ->leftjoin('resort_sections as t5', 't5.id', '=', 'job_descriptions.Section_id')
            ->where('job_descriptions.Resort_id', $this->resort->resort_id)
            ->where("job_descriptions.compliance",$complianceStatus)
            ->orderBy('job_descriptions.id', 'DESC');
            if ($searchTerm) {
                $JobDescription->where(function($query) use ($searchTerm) {
                    $query->where('t2.name', 'like', "%$searchTerm%")
                          ->orWhere('t3.name', 'like', "%$searchTerm%")
                          ->orWhere('t4.position_title', 'like', "%$searchTerm%")
                          ->orWhere('t5.name', 'like', "%$searchTerm%")

                          ->orWhere('t2.code', 'like', "%$searchTerm%")
                          ->orWhere('t3.code', 'like', "%$searchTerm%")
                          ->orWhere('t4.code', 'like', "%$searchTerm%")

                          ->orWhere('t5.code', 'like', "%$searchTerm%")
                          ->orWhere('job_descriptions.jobdescription', 'like', "%$searchTerm%"); // Job Description
                });
            }
            $JobDescription ->get();
            $edit_class = '';
            $delete_class = '';


            if(Common::checkRouteWisePermission('resort.ta.jobdescription.index',config('settings.resort_permissions.edit')) == false){
                $edit_class = 'd-none';
            }
            if(Common::checkRouteWisePermission('resort.ta.jobdescription.index',config('settings.resort_permissions.delete')) == false){
                $delete_class = 'd-none';
            }
            
            return datatables()->of($JobDescription)
                ->addColumn('action', function ($row) use ($edit_class, $delete_class) {
                    $editUrl = asset('resorts_assets/images/edit.svg');
                    $deleteUrl = asset('resorts_assets/images/trash-red.svg');
                    $redirectToMe = route('resort.ta.jobdescription.download', $row->slug);
                    return '
                            <a href="javscript:void(0)" class="btn-tableIcon btnIcon-orange viewJobDesc " data-id="' . htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8').'"><i class="fa-regular fa-eye"></i></a>
                            <a target="_blank" href="'.$redirectToMe.'" class="btn-tableIcon btnIcon-skyblue"><i class="fa-regular fa-download"></i></a>
                            <a href="javscript:void(0)" class="btn-lg-icon icon-bg-green me-1 edit-row-btn '.$edit_class.'"
                            data-id="' . htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8') . '">
                                <img src="' . $editUrl . '" alt="Edit" class="img-fluid" />
                            </a>
                            <a href="javscript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn '.$delete_class.'"
                           data-id="'. htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8') . '" >
                                <img src="' . $deleteUrl . '" alt="Delete" class="img-fluid" />
                            </a>
                        ';
                })
                ->addColumn('Division', function ($row) {
                      return  $row->Division.'   '.'<span class="badge badge-themeLight">'. htmlspecialchars($row->Division_Code, ENT_QUOTES, 'UTF-8') . '</span>';
                })

                ->addColumn('Department', function ($row) {
                    return  $row->Department.'   '.'<span class="badge badge-themeLight">'. htmlspecialchars($row->Departmet_Code, ENT_QUOTES, 'UTF-8') . '</span>';
                })

                ->addColumn('Position', function ($row) {
                    return  $row->Position.'   '.'<span class="badge badge-themeLight">'. htmlspecialchars($row->Position_Code, ENT_QUOTES, 'UTF-8') . '</span>';
                })

                ->addColumn('Section', function ($row) {
                    $sectionStr='';
                    if(isset($row->Position_Code) && !empty($row->Position_Code)){
                            $sectionStr= htmlspecialchars($row->Section_Code, ENT_QUOTES, 'UTF-8');
                         }else{
                            $sectionStr= "-";
                        }
                    return  $row->Section_Code.'   '.'<span class="badge badge-themeLight">'.$sectionStr. '</span>';
                })

                ->addColumn('JobDescription', function ($row)
                {

                    return   nl2br(Common::SliceParegraph($row->jobdescription)) ;
                })

                ->addColumn('Compliance', function ($row) {

                    if($row->compliance == "Approved")
                    {
                        return '<span class="text-successTheme"><i class="fa-solid fa-circle-check  me-2"></i>Compliance Passed</span>';

                    }
                    else
                    {
                        return '<span class="text-danger"><i class="fa-solid fa-circle-xmark  me-2"></i>Compliance Rejected</span>';

                    }

                })

                ->rawColumns(['Division', 'Department', 'Position', 'Section','Compliance','JobDescription','action'])
                ->make(true);

        //    } catch (\Exception $e) {
        //     \Log::emergency("File: " . $e->getFile());
        //     \Log::emergency("Line: " . $e->getLine());
        //     \Log::emergency("Message: " . $e->getMessage());
        //     return response()->json(['error' => 'Failed to fetch data'], 500);
        // }
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'jobdescription' => [
                'required',
                Rule::unique('job_descriptions')
                    ->where(function ($query) use ($request) {
                        return $query
                            ->where('resort_id', $this->resort->resort_id)
                            ->where('Division_id', $request->Division_id)
                            ->where('Department_id', $request->Department_id)
                            ->where('Position_id', $request->Position_id);
                            // ->where('jobdescription', $request->jobdescription);
                    })
            ],
        ], [
            'jobdescription.required' => 'Please Enter Job Description.',
            'jobdescription.unique' => 'This job description already exists for this position.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
   
            $slug = Str::slug("JD-".Str::random(8));

  
            $jobDescription =JobDescription::create([
                "Resort_id"=>$this->resort->resort_id,
                'Division_id' => $request->Division_id,
                'Department_id'=> $request->Department_id,
                'Position_id'=> $request->Position_id,
                'Section_id '=> $request->Section_id,
                'jobdescription'=> $request->jobdescription,
                "compliance"=>"Rejected",
                "slug"=> $slug
            ]);
            // dd($jobDescription);
          
            // Set up PDF configuration

            // Generate PDF
            $pdf = \PDF::loadView('resorts.talentacquisition.jobdescription.download', [
                'j' => $jobDescription,
                'sitesettings' => ResortSiteSettings::where('resort_id', $this->resort->resort_id)->first(['resort_id','header_img','footer_img','Footer']),
                'ResortData' => Resort::find($this->resort->resort_id)
            ]);

            // Set PDF properties
            $pdf->setPaper('a4', 'portrait');
            $filename = 'JobDescription_' . $slug . '.pdf';

            // Save PDF to storage
            $storagePath = config('settings.jd_path');
            $dynamic_path = $storagePath.'/'.$filename;
            
            $absolute_path = public_path($dynamic_path);
            // Create directory if it doesn't exist
            if (!file_exists(dirname($absolute_path))) {
                mkdir(dirname($absolute_path), 0755, true);
            }

            $pdf->save($absolute_path);

            // Call extraction API with the PDF content
            // try {
                // $url = env('AI_URL').'check_agreement_compliance'; 
                // $curl = curl_init();
                // $postFields = [
                //     'contract' => new \CURLFile($absolute_path, 'application/pdf', $filename),
                // ];
                //     curl_setopt_array($curl, [
                //         CURLOPT_URL => $url,
                //         CURLOPT_RETURNTRANSFER => true,
                //         CURLOPT_POST => true,
                //         CURLOPT_POSTFIELDS => $postFields,
                //         CURLOPT_HTTPHEADER => [
                //             'Accept: application/json',
                //         ],
                //     ]);
                //     $response = curl_exec($curl);
                //     $err = curl_error($curl);
                //     curl_close($curl);
                //     if($err) 
                //     {
                //         return response()->json(['status' => false, 'message' =>  $err]);
                //     } 
                //     $AI_Data = json_decode($response, true); 
                    
                //     dd($AI_Data);
            // } catch (\Exception $e) {
            //     \Log::error("PDF extraction API error: " . $e->getMessage());
            // }

            return response()->json(['success' => true, 'message' => 'Job Description Added successfully.'],200);
             DB::beginTransaction();
        try{}
        catch( \Exception $e )
        {

            DB::rollBack();
            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to add Agent Email.'], 500);

        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        try{

            $j = JobDescription::find($id);
            return response()->json(['success' => true, 'data'=> strip_tags($j->jobdescription)],200);

        }
        catch( \Exception $e )
        {


            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to Get job description  .'], 500);

        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try{

            $j = JobDescription::find($id);
            return response()->json(['success' => true, 'data'=> $j->jobdescription],200);

        }
        catch( \Exception $e )
        {


            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to Get job description  .'], 500);

        }

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try{

            $j = JobDescription::find($id);
            $j->jobdescription = $request->jobdescription;
            $j->save();
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Suceessfully Updated Job description  .'],200);

        }
        catch( \Exception $e )
        {

            DB::rollBack();
            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to Update job description  .'], 500);

        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try{

            $j = JobDescription::find($id);
            $j->delete();
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Suceessfully Removed Job description  .'],200);

        }
        catch( \Exception $e )
        {

            DB::rollBack();
            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to Remove the job description  .'], 500);

        }
    }

    public function download($slug)
    {
        $j = JobDescription::where("slug",$slug)->first();
        $sitesettings = ResortSiteSettings::where('resort_id', $this->resort->resort_id)->first(['resort_id','header_img','footer_img','Footer']);
        $ResortData = Resort::find($this->resort->resort_id);

        $resort_id = $ResortData->resort_id;
        return view('resorts.talentacquisition.jobdescription.download',compact('sitesettings','j','ResortData',));
    }

   
    public function fetchByPosition(Request $request, $positionId)
    {
        // $positionId = $request->input('position_id');
    
        $j = JobDescription::where('Position_id', $positionId)
            ->where('Resort_id', auth()->user()->resort_id)
            ->first();
    
        if (!$j) {
            return response()->json(['html' => '<p>No job description found.</p>']);
        }
    
        $sitesettings = ResortSiteSettings::where('resort_id', $this->resort->resort_id)->first(['resort_id','header_img','footer_img','Footer']);
        $ResortData = Resort::find($this->resort->resort_id);

        $resort_id = $ResortData->resort_id;
        return view('resorts.talentacquisition.jobdescription.download',compact('sitesettings','j','ResortData',));
    }

}
