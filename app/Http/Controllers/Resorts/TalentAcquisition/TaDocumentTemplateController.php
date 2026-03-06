<?php

namespace App\Http\Controllers\Resorts\TalentAcquisition;

use App\Http\Controllers\Controller;
use App\Models\TaDocumentTemplate;
use Illuminate\Http\Request;
use Auth;
use DB;
use Illuminate\Support\Facades\Storage;

class TaDocumentTemplateController extends Controller
{
    public $resort;

    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if (!$this->resort) return;
    }

    /**
     * Display listing page for templates of given type.
     */
    public function index($type)
    {
        if (!in_array($type, ['offer_letter', 'contract'])) {
            abort(404);
        }

        $page_title = $type === 'offer_letter' ? 'Offer Letter Templates' : 'Employment Contract Templates';

        return view('resorts.talentacquisition.documenttemplates.index', compact('page_title', 'type'));
    }

    /**
     * Return DataTables JSON for templates of given type.
     */
    public function getList(Request $request, $type)
    {
        if (!in_array($type, ['offer_letter', 'contract'])) {
            return response()->json(['error' => 'Invalid type'], 400);
        }

        $resort_id = $this->resort->resort_id;

        $data = TaDocumentTemplate::select(
                'ta_document_templates.*',
                DB::raw("CONCAT(resort_admins.first_name, ' ', resort_admins.last_name) as uploaded_by_name")
            )
            ->leftJoin('resort_admins', 'ta_document_templates.created_by', '=', 'resort_admins.id')
            ->where('ta_document_templates.resort_id', $resort_id)
            ->where('ta_document_templates.type', $type);

        return datatables()->of($data)
            ->addColumn('file_name', function ($row) {
                if ($row->file_path) {
                    return '<i class="fa-solid fa-file-word text-primary me-1"></i>' . e(basename($row->file_path));
                }
                return '<span class="text-muted">No file</span>';
            })
            ->editColumn('created_at', function ($row) {
                return $row->created_at ? $row->created_at->format('d M Y, h:i A') : '-';
            })
            ->addColumn('default_badge', function ($row) {
                if ($row->is_default) {
                    return '<span class="badge bg-success">Default</span>';
                }
                return '<a href="javascript:void(0)" class="btn btn-sm btn-outline-primary set-default-btn" data-id="' . $row->id . '">Set as Default</a>';
            })
            ->addColumn('action', function ($row) {
                $deleteBtn = '<a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn" data-id="' . $row->id . '"><img src="' . asset('resorts_assets/images/trash-red.svg') . '" alt="Delete" class="img-fluid" /></a>';
                $downloadBtn = '';
                if ($row->file_path) {
                    $downloadUrl = null;
                    $configDisk = config('filesystems.default', 'local');
                    $disk = Storage::disk($configDisk);
                    try {
                        if ($disk->exists($row->file_path)) {
                            if (method_exists($disk, 'temporaryUrl') && $configDisk === 's3') {
                                $downloadUrl = $disk->temporaryUrl($row->file_path, now()->addMinutes(30));
                            } else {
                                $downloadUrl = $disk->url($row->file_path);
                            }
                        }
                    } catch (\Exception $e) {}
                    if (!$downloadUrl && Storage::disk('public')->exists($row->file_path)) {
                        $downloadUrl = asset('storage/' . $row->file_path);
                    }
                    if (!$downloadUrl && file_exists(public_path($row->file_path))) {
                        $downloadUrl = asset($row->file_path);
                    }
                    if (!$downloadUrl && Storage::disk('local')->exists('public/' . $row->file_path)) {
                        $downloadUrl = url('storage/' . $row->file_path);
                    }
                    if ($downloadUrl) {
                        $downloadBtn = '<a href="' . $downloadUrl . '" download class="btn-lg-icon icon-bg-green me-1"><i class="fa-solid fa-download"></i></a>';
                    }
                }
                return '<div class="d-flex align-items-center">' . $downloadBtn . $deleteBtn . '</div>';
            })
            ->rawColumns(['file_name', 'default_badge', 'action'])
            ->make(true);
    }

    /**
     * Upload a new DOCX template.
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:offer_letter,contract',
            'name' => 'required|string|max:255',
            'template_file' => 'required|file|max:10240',
        ]);

        // Validate .docx extension
        $ext = strtolower($request->file('template_file')->getClientOriginalExtension());
        if ($ext !== 'docx') {
            return response()->json(['success' => false, 'message' => 'Please upload a .docx file.'], 422);
        }

        try {
            $resort_id = $this->resort->resort_id;
            $file = $request->file('template_file');
            $storagePath = 'ta_templates/' . $resort_id;
            $fileName = $request->type . '_' . time() . '_' . uniqid() . '.docx';
            $filePath = $file->storeAs($storagePath, $fileName, 'public');

            // Check if any default exists for this type
            $hasDefault = TaDocumentTemplate::where('resort_id', $resort_id)
                ->where('type', $request->type)
                ->where('is_default', true)
                ->exists();

            $template = TaDocumentTemplate::create([
                'resort_id' => $resort_id,
                'type' => $request->type,
                'name' => $request->name,
                'file_path' => $filePath,
                'is_default' => !$hasDefault, // First upload becomes default
                'created_by' => $this->resort->id,
                'modified_by' => $this->resort->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Template uploaded successfully!',
            ]);
        } catch (\Exception $e) {
            \Log::emergency("TaDocumentTemplateController@store error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to upload template.'], 500);
        }
    }

    /**
     * Set a template as the default for its type.
     */
    public function setDefault($id)
    {
        try {
            $resort_id = $this->resort->resort_id;

            $template = TaDocumentTemplate::where('id', $id)
                ->where('resort_id', $resort_id)
                ->firstOrFail();

            // Unset all defaults for this type
            TaDocumentTemplate::where('resort_id', $resort_id)
                ->where('type', $template->type)
                ->update(['is_default' => false]);

            // Set this one as default
            $template->update(['is_default' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Template set as default successfully!',
            ]);
        } catch (\Exception $e) {
            \Log::emergency("TaDocumentTemplateController@setDefault error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to set default.'], 500);
        }
    }

    /**
     * Delete a template and its file.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $resort_id = $this->resort->resort_id;

            $template = TaDocumentTemplate::where('id', $id)
                ->where('resort_id', $resort_id)
                ->first();

            if (!$template) {
                return response()->json(['success' => false, 'message' => 'Template not found.'], 404);
            }

            $wasDefault = $template->is_default;
            $type = $template->type;

            // Delete file from storage (check configured disk first, then fallbacks)
            if ($template->file_path) {
                $configDisk = config('filesystems.default', 'local');
                $disk = Storage::disk($configDisk);
                try {
                    if ($disk->exists($template->file_path)) {
                        $disk->delete($template->file_path);
                    }
                } catch (\Exception $e) {}
                if (Storage::disk('public')->exists($template->file_path)) {
                    Storage::disk('public')->delete($template->file_path);
                }
            }

            $template->delete();

            // If deleted template was default, promote the latest one
            if ($wasDefault) {
                $nextTemplate = TaDocumentTemplate::where('resort_id', $resort_id)
                    ->where('type', $type)
                    ->orderBy('id', 'DESC')
                    ->first();

                if ($nextTemplate) {
                    $nextTemplate->update(['is_default' => true]);
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Template deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("TaDocumentTemplateController@destroy error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete template.'], 500);
        }
    }
}