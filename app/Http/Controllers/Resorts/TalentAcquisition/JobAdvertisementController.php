<?php

namespace App\Http\Controllers\Resorts\TalentAcquisition;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JobAdvertisement;
use Validator;
use Auth;
use App\Helpers\Common;
use DB;
use App\Models\ApplicationLink;
use App\Models\ResortDepartment;
use App\Models\Admin;
use Carbon\Carbon;
use URL;
class JobAdvertisementController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
    }

    public function index()
    {
        $page_title = "Job Ad Templates";
        return view('resorts.talentacquisition.jobadvertisement.index', compact('page_title'));
    }

    public function getList(Request $request)
    {
        $resort_id = $this->resort->resort_id;

        $jobAds = JobAdvertisement::select([
                'job_advertisements.id',
                'job_advertisements.Jobadvimg',
                'job_advertisements.Resort_id',
                'job_advertisements.created_by',
                'job_advertisements.created_at',
                'job_advertisements.updated_at',
            ])
            ->where('job_advertisements.Resort_id', $resort_id)
            ->orderBy('job_advertisements.id', 'DESC');

        return datatables()->of($jobAds)
            ->addColumn('Preview', function ($row) {
                // Driver-aware URL — was URL::asset() which 404'd on
                // every page load when STORAGE_DRIVER=wasabi (live).
                $imgUrl = \App\Helpers\Common::GetJobAdvertisementImage($row->Resort_id, $row->Jobadvimg);
                return '<a href="'.$imgUrl.'" target="_blank"><img src="'.$imgUrl.'" alt="Template" style="max-height:60px; max-width:100px;" class="img-fluid rounded"></a>';
            })
            ->addColumn('FileName', function ($row) {
                return htmlspecialchars($row->Jobadvimg, ENT_QUOTES, 'UTF-8');
            })
            ->addColumn('UploadedBy', function ($row) {
                $admin = Admin::select('first_name', 'last_name')->where('id', $row->getRawOriginal('created_by'))->first();
                if($admin) {
                    return ucwords($admin->first_name.' '.$admin->last_name);
                }
                return '-';
            })
            ->addColumn('UploadedAt', function ($row) {
                return $row->updated_at ?? $row->created_at ?? '-';
            })
            ->addColumn('action', function ($row) {
                $deleteUrl = asset('resorts_assets/images/trash-red.svg');
                $imgUrl = \App\Helpers\Common::GetJobAdvertisementImage($row->Resort_id, $row->Jobadvimg);
                return '
                    <a href="'.$imgUrl.'" target="_blank" class="btn-tableIcon btnIcon-skyblue"><i class="fa-regular fa-eye"></i></a>
                    <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn"
                       data-id="'. htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8') . '">
                        <img src="' . $deleteUrl . '" alt="Delete" class="img-fluid" />
                    </a>
                ';
            })
            ->rawColumns(['Preview', 'action'])
            ->make(true);
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $jobAd = JobAdvertisement::where('id', $id)
                ->where('Resort_id', $this->resort->resort_id)
                ->first();

            if(!$jobAd) {
                return response()->json(['success' => false, 'message' => 'Template not found.'], 404);
            }

            $path = config('settings.Resort_JobAdvertisement').'/'.$jobAd->Resort_id.'/'.$jobAd->Jobadvimg;
            if(\App\Helpers\StorageHelper::disk()->exists($path)) {
                \App\Helpers\StorageHelper::disk()->delete($path);
            }

            $jobAd->delete();
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Template removed successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to remove template.'], 500);
        }
    }

    public function StoreJobAvd(Request $request)
    {
        $validator =  Validator::make($request->all(), [
            'Jobadvimg' => 'required|file|mimes:jpg,jpeg,png,gif,svg,webp,heic,heif|max:2048',
            // Omitted/null = the resort-wide default poster (unchanged
            // behavior); a real id scopes the upload to that one vacancy.
            'vacancy_id' => 'nullable|integer|exists:vacancies,id',
        ], [
            'Jobadvimg.max' => 'The file size must not exceed 2MB.',
            'Jobadvimg.mimes' => 'The file must be an image (jpg, jpeg, png, gif, svg, webp, heic, heif)',
            'Jobadvimg.required' => 'Please select an image',
        ]);
        if($validator->fails())
        {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        $resort_id= Auth::guard('resort-admin')->user()->resort_id;
        $vacancy_id = $request->filled('vacancy_id') ? (int) $request->vacancy_id : null;
        try
        {
            DB::beginTransaction();
                if ($request->hasFile('Jobadvimg'))
                {

                    // Was Auth::...->user()->resort->resort_id — the
                    // resort's STRING slug (e.g. "87fca1b014"), not the
                    // numeric id. The DB row's Resort_id (used by the list
                    // and delete methods, and by every GetJobAdvertisementImage()
                    // caller) is the numeric $resort_id computed above —
                    // so every fresh upload was written to
                    // .../JobAdvertisement/87fca1b014/... while every read
                    // looked in .../JobAdvertisement/26/..., a guaranteed
                    // NoSuchKey on every single upload. Reuse the same
                    // numeric $resort_id the DB save already uses below.
                    $path_profile_image = config('settings.Resort_JobAdvertisement').'/'.$resort_id;

                    // A vacancy-specific poster and the resort-wide default
                    // share this same flat folder with no per-vacancy
                    // namespacing — two different vacancies uploading a
                    // same-named file would otherwise silently overwrite
                    // each other's file on disk even though they're
                    // different job_advertisements rows. Also sanitize the
                    // filename to a safe ASCII key — a macOS screenshot's
                    // default name embeds a narrow no-break space
                    // (U+202F), not a regular space, which is safest not to
                    // trust as a raw storage key.
                    $originalName = $request->file('Jobadvimg')->getClientOriginalName();
                    $extension    = strtolower($request->file('Jobadvimg')->getClientOriginalExtension());
                    $safeBaseName = preg_replace('/[^A-Za-z0-9_-]+/', '_', pathinfo($originalName, PATHINFO_FILENAME));
                    $fileName     = ($vacancy_id ?? 'default') . '_' . $safeBaseName . '.' . $extension;

                        // Common::uploadFile() only moves the file to local
                        // disk — on live (STORAGE_DRIVER=wasabi) that left
                        // the actual bucket key never created, while
                        // GetJobAdvertisementImage() (driver-aware) built a
                        // presigned URL for that same key, producing
                        // NoSuchKey on every preview. Write through the same
                        // driver-aware disk the read side uses.
                        \App\Helpers\StorageHelper::disk()->put(
                            $path_profile_image.'/'.$fileName,
                            file_get_contents($request->file('Jobadvimg')->getRealPath()),
                            ['ContentType' => $request->file('Jobadvimg')->getMimeType()]
                        );

                        // A vacancy-specific poster is one slot per vacancy —
                        // re-uploading replaces it. But the general/default
                        // template (vacancy_id null) is meant to be a real
                        // library ("View All Templates" lists every row for
                        // the resort with its own delete button) — using
                        // updateOrCreate for that case too collapsed every
                        // upload into the same single row, so a second
                        // template silently overwrote the first instead of
                        // being added alongside it.
                        if ($vacancy_id) {
                            JobAdvertisement::updateOrCreate([
                                    "Resort_id" => $resort_id,
                                    "vacancy_id" => $vacancy_id,
                            ],[
                                "Jobadvimg" => $fileName,
                            ]);
                        } else {
                            JobAdvertisement::create([
                                "Resort_id" => $resort_id,
                                "vacancy_id" => null,
                                "Jobadvimg" => $fileName,
                            ]);
                        }
                        DB::commit();
                        return response()->json(['success' => true, 'message' => 'Job Advertisement Uploaded successfully.']);
                    }

        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Upload  data'], 500);
        }


    }

    /**
     * Composite the "Job Advertisement Link" (as a QR code + text) onto the
     * poster image server-side, and serve the result as a download.
     *
     * The Vacancies "View Job Ad" modal used to do this client-side: load
     * the poster into an <img crossOrigin="anonymous">, draw it onto a
     * <canvas>, and read the canvas back out via toDataURL(). Poster URLs
     * are presigned Wasabi/S3 URLs (Common::GetJobAdvertisementImage()) —
     * without permissive CORS headers on that bucket, the canvas is
     * "tainted" and toDataURL() throws a SecurityError, which the JS
     * silently caught and fell back to a plain raw-file download with no
     * QR/link at all. That's the exact symptom reported ("Link is not
     * imprinting on the ad when downloaded"). Doing the compositing here
     * instead removes the CORS dependency entirely — the server reads the
     * poster directly off its own storage disk, no browser fetch involved.
     *
     * Deliberately takes only $vacancyId, not a poster URL or a link
     * string, from the client — both the poster file and the job link are
     * re-resolved here from data this resort-admin already has authority
     * over, the same way every other poster/link lookup in this app does,
     * rather than trusting client-supplied values for a server-side image
     * composite.
     */
    public function downloadComposedAd($vacancyId)
    {
        if (!$this->resort) {
            return abort(401);
        }
        $resortId = $this->resort->resort_id;

        // Same fallback as Common::resolveVacancyPosterImage(): this
        // vacancy's own poster, else the resort-wide default.
        $ad = JobAdvertisement::where('Resort_id', $resortId)->where('vacancy_id', $vacancyId)->first();
        if (!$ad) {
            $ad = JobAdvertisement::where('Resort_id', $resortId)->whereNull('vacancy_id')->first();
        }
        if (!$ad || empty($ad->Jobadvimg)) {
            return abort(404, 'No job advertisement image found.');
        }

        // Same relative path StoreJobAvd() writes to above — read through
        // the same driver-aware disk the write side uses.
        $relPath = config('settings.Resort_JobAdvertisement') . '/' . $resortId . '/' . $ad->Jobadvimg;
        try {
            $imageBytes = \App\Helpers\StorageHelper::get($relPath);
        } catch (\Throwable $e) {
            \Log::warning('downloadComposedAd: could not read poster file', ['path' => $relPath, 'error' => $e->getMessage()]);
            return abort(404, 'Job advertisement image not found.');
        }

        // Same application_links chain VacancyController already joins to
        // display "Job Advertisement Link" (MAX(t5.link) per vacancy) — one
        // vacancy scoped down instead of the whole grid.
        $jobLink = DB::table('vacancies')
            ->join('t_anotification_parents as t3', 't3.V_id', '=', 'vacancies.id')
            ->join('t_anotification_children as t4', 't4.Parent_ta_id', '=', 't3.id')
            ->join('application_links as t5', 't5.ta_child_id', '=', 't4.id')
            ->where('vacancies.resort_id', $resortId)
            ->where('vacancies.id', $vacancyId)
            ->max('t5.link');

        if (empty($jobLink)) {
            // No link to embed — same as the JS's original "no job link"
            // path: just download the raw poster as-is.
            $extension = pathinfo($ad->Jobadvimg, PATHINFO_EXTENSION);
            return response($imageBytes, 200)
                ->header('Content-Type', \App\Helpers\StorageHelper::mimeType($relPath) ?: 'application/octet-stream')
                ->header('Content-Disposition', 'attachment; filename="job-advertisement.' . $extension . '"');
        }

        $poster = \Image::make($imageBytes);

        $qrSize = 120;
        $padding = 20;
        $canvas = \Image::canvas($poster->width(), $poster->height() + $qrSize + $padding * 2, '#ffffff');
        $canvas->insert($poster, 'top-left', 0, 0);

        // Drawn by hand instead of via simplesoftwareio/simple-qrcode: that
        // package's PNG output needs Imagick (see the 'svg'-only QR
        // elsewhere in this app, config/services or ConfigurationController
        // — this server only has GD). bacon-qr-code (the package simple-
        // qrcode wraps) only ships SVG/EPS/Imagick renderers, none of which
        // GD can rasterize — so the raw module matrix is walked directly
        // and filled as GD rectangles, the standard workaround for
        // "QR code + GD only, no Imagick".
        $qrCode = \BaconQrCode\Encoder\Encoder::encode($jobLink, \BaconQrCode\Common\ErrorCorrectionLevel::M());
        $matrix = $qrCode->getMatrix();
        $moduleCount = $matrix->getWidth();
        $moduleSize = max(1, (int) floor($qrSize / $moduleCount));
        $qrPixelSize = $moduleSize * $moduleCount;
        $qrX = (int) (($canvas->width() - $qrPixelSize) / 2);
        $qrY = $poster->height() + $padding;

        for ($y = 0; $y < $moduleCount; $y++) {
            for ($x = 0; $x < $moduleCount; $x++) {
                if ($matrix->get($x, $y) === 1) {
                    $canvas->rectangle(
                        $qrX + $x * $moduleSize,
                        $qrY + $y * $moduleSize,
                        $qrX + ($x + 1) * $moduleSize - 1,
                        $qrY + ($y + 1) * $moduleSize - 1,
                        function ($draw) {
                            $draw->background('#000000');
                        }
                    );
                }
            }
        }

        $canvas->text('Apply now: ' . $jobLink, (int) ($canvas->width() / 2), $qrY + $qrPixelSize + 18, function ($font) {
            $font->size(14);
            $font->color('#000000');
            $font->align('center');
            $font->valign('top');
        });

        return $canvas->response('png')
            ->header('Content-Disposition', 'attachment; filename="job-advertisement.png"');
    }

    public function GenrateAdvLink(Request $request)
    {

        DB::beginTransaction();
        try
        {


            if(isset($request->link_Expiry_date))
            {
                if(isset($request->ExtendFlag ) && $request->ExtendFlag =="extendata") // Exitend Page only
                {
                    $new_link_Expiry_date = Carbon::createFromFormat('d/m/Y', $request->link_Expiry_date)->format('Y-m-d');

                    // Scope by resort_id — sibling of the Resort_id-from-client
                    // bug below; without this a client could extend/write
                    // another resort's application link by guessing its id.
                    $a = ApplicationLink::where('Resort_id', $this->resort->resort_id)->find($request->ApplicationId);
                    if (!$a) {
                        DB::rollBack();
                        return response()->json(['success' => false, 'message' => 'Application link not found.'], 404);
                    }

                    if(isset($a) &&  $a->link_Expiry_date == $new_link_Expiry_date)
                    {
                        return response()->json(['success' => false,'message' => 'Same Expiry Date You Selected.']);
                    }
                    else
                    {

                        if(isset($a->Old_ExpiryDate) && $a->Old_ExpiryDate !="0000-00-00" )
                        {
                            $json_data =  json_decode($a->Old_ExpiryDate);

                            array_push($json_data,$a->link_Expiry_date);

                        }
                        else
                        {
                            $array=array($a->link_Expiry_date);
                            {
                                $json_data = json_encode($array);
                            }
                        }

                        
                        $a->Old_ExpiryDate = $json_data;
                        $a->link_Expiry_date = $new_link_Expiry_date;
                        $a->save();
                        return response()->json([
                            'success' => true,
                            'message' => "Expiry Date Extended to {$request->link_Expiry_date}"
                        ]);
                    }
                }
                else // New Application Link Genrate
                {
                    // Resort_id must be the authenticated caller's own resort —
                    // it was previously taken straight from the request body,
                    // letting a client create/overwrite an application link
                    // (and its ta_child_id linkage) under any other resort.
                    ApplicationLink::updateOrCreate([
                        "Resort_id"=> $this->resort->resort_id,
                    "ta_child_id" =>  $request->ta_child_id,
                ],[
                    "link_Expiry_date"=> Carbon::createFromFormat('d/m/Y', $request->link_Expiry_date)->format('Y-m-d'),
                    "link"=> $request->link,
                    "Resort_id"=> $this->resort->resort_id,
                    "ta_child_id" =>  $request->ta_child_id,
                ]);

                // Persist whichever poster the user picked in the carousel as
                // this vacancy's own override — otherwise the selection has no
                // effect and the vacancy just keeps showing the resort default.
                // ta_child_id -> t_anotification_children.id -> Parent_ta_id ->
                // t_anotification_parents.id -> V_id (vacancies.id), same chain
                // Common::GmApprovedVacancy() itself joins through.
                if ($request->filled('selected_jobadvimg')) {
                    $vacancyId = DB::table('t_anotification_children as t3')
                        ->join('t_anotification_parents as t2', 't2.id', '=', 't3.Parent_ta_id')
                        ->where('t3.id', $request->ta_child_id)
                        ->value('t2.V_id');

                    if ($vacancyId) {
                        DB::table('vacancies')
                            ->where('id', $vacancyId)
                            ->where('resort_id', $this->resort->resort_id)
                            ->update(['Jobadvimg' => $request->selected_jobadvimg]);
                    }
                }

                DB::commit();

                // Return rendered views to refresh dashboard sections
                $resort_id = $this->resort->resort_id;
                $rank = (int) ($this->resort->GetEmployee->rank ?? 0);
                $effectiveRank = $rank;
                if (!in_array($rank, [3, 7, 8])) {
                    $userDeptName = ResortDepartment::where('id', $this->resort->GetEmployee->Dept_id)->value('name');
                    $userPositionTitle = $this->resort->GetEmployee->position->position_title ?? '';
                    if (stripos($userDeptName, 'Accounting') !== false || stripos($userDeptName, 'Finance') !== false
                        || stripos($userPositionTitle, 'Finance') !== false) {
                        $effectiveRank = 7;
                    } elseif (stripos($userDeptName, 'Human Resources') !== false || stripos($userPositionTitle, 'Human Resources') !== false) {
                        $effectiveRank = 3;
                    }
                }
                $getNotifications['FreshVacancies'] = Common::GetTheFreshVacancies($resort_id, 'Active', $effectiveRank);
                $view = view('resorts.renderfiles.FreshVacancies', compact('getNotifications'))->render();
                $TodoData = Common::GmApprovedVacancy($resort_id, $effectiveRank);
                $Todolistview = view('resorts.renderfiles.TaTodoList', compact('TodoData'))->render();

                return response()->json(['success' => true, 'ta_child_id'=> $request->ta_child_id, 'view' => $view, 'Todolistview' => $Todolistview, 'message' => 'Job Advertisement Link Acitivate now.']);
                }
            }
            else
            {
                return response()->json(['success' => false,'message' => 'Requirtment is started so you cant extend the  Expiry Date.']);
            }
            


        }
        catch (\Exception $e)
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['error' => 'Failed to Upload  data'], 500);
        }


    }
}
