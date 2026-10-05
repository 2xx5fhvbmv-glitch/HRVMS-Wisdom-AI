<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Common;
use App\Helpers\StorageHelper;
use App\Http\Controllers\Controller;
use App\Jobs\RunResortDataImport;
use App\Models\Employee;
use App\Models\LeaveCategory;
use App\Models\Resort;
use App\Models\ResortDataImport;
use App\Models\ShiftSettings;
use App\Services\ResortDataSetup\ResortDataImporter;
use App\Services\ResortDataSetup\SheetMapper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Super-admin "Resort Data Setup": upload a new client resort's exports
 * from their previous HR system, map them (AI once per layout, then
 * cached), validate with a full rolled-back dry run, then import.
 *
 * Reading files, validating and importing run on the queue
 * (RunResortDataImport); requests only store files / options and dispatch.
 * Every POST renders the page directly instead of redirecting — flash data
 * does not survive a redirect in this app (see CLAUDE.md).
 */
class ResortDataSetupController extends Controller
{
    const EXTENSIONS = ['xls', 'xlsx', 'csv'];
    const MAX_FILE_BYTES = 20 * 1024 * 1024;
    const STALE_MINUTES = 65; // a running job older than this died (job timeout is 30 min)

    public function index()
    {
        $this->authorizeResorts('view');

        $resorts = Resort::orderBy('resort_name')->get(['id', 'resort_id', 'resort_name', 'status', 'created_at']);
        $employees = Employee::selectRaw('resort_id, count(*) c')->groupBy('resort_id')->pluck('c', 'resort_id');
        $lastImport = ResortDataImport::orderByDesc('id')->get(['resort_id', 'status', 'job_status', 'imported_at'])->unique('resort_id')->keyBy('resort_id');

        return view('admin.resort_data_setup.index', compact('resorts', 'employees', 'lastImport'));
    }

    public function show(Resort $resort)
    {
        $this->authorizeResorts('view');
        return $this->render($resort);
    }

    public function upload(Request $request, Resort $resort)
    {
        $this->authorizeResorts('edit');

        $import = $this->draft($resort);
        if ($import->busy()) {
            return $this->render($resort, [['warning', 'A job is still running for this resort — wait for it to finish.']]);
        }
        $files = $request->file('files', []);
        if (!$files || !is_array($files)) {
            return $this->render($resort, [['danger', 'Choose at least one file to upload.']]);
        }

        $import->save();
        $entries = collect($import->files ?? [])->keyBy('name');
        $notices = [];
        foreach (array_slice($files, 0, 20) as $file) {
            $name = $file->getClientOriginalName();
            $ext = strtolower($file->getClientOriginalExtension());
            if (!$file->isValid() || !in_array($ext, self::EXTENSIONS, true) || $file->getSize() > self::MAX_FILE_BYTES) {
                $notices[] = ['danger', "{$name}: only .xls, .xlsx or .csv files up to 20 MB."];
                continue;
            }
            $path = $resort->resort_id . '/resort-data-setup/' . $import->id . '/' . Str::uuid() . '.' . $ext;
            if (!StorageHelper::put($path, file_get_contents($file->getRealPath()))) {
                $notices[] = ['danger', "{$name}: could not be stored, try again."];
                continue;
            }
            // Re-uploading a file with the same name replaces it.
            if ($old = $entries->get($name)) {
                StorageHelper::delete($old['path']);
            }
            $entries->put($name, ['id' => Str::random(12), 'name' => $name, 'path' => $path, 'pending' => true]);
        }

        $import->files = $entries->values()->all();
        $import->report = null;
        $import->save();
        if ($entries->contains('pending', true)) {
            $this->dispatchJob($import, 'analyse');
        }

        return $this->render($resort, $notices);
    }

    public function mapping(Request $request, Resort $resort, string $fileId)
    {
        $this->authorizeResorts('edit');

        $import = $this->draft($resort);
        $files = $import->files ?? [];
        $i = collect($files)->search(fn ($f) => $f['id'] === $fileId);
        abort_if($i === false, 404);
        if ($import->busy()) {
            return $this->render($resort, [['warning', 'A job is still running for this resort — wait for it to finish.']]);
        }

        $type = (string) $request->input('type');
        if (!isset(SheetMapper::FIELDS[$type])) {
            return $this->render($resort, [['danger', 'Choose the file type.']]);
        }
        $files[$i]['pending'] = true;
        $files[$i]['requested'] = [
            'type'         => $type,
            'header_row'   => (int) $request->input('header_row'),
            'columns'      => array_map(fn ($v) => $v === null || $v === '' ? null : (int) $v, (array) $request->input("columns.{$type}", [])),
            'group_column' => $request->filled('group_column') ? (int) $request->input('group_column') : null,
        ];
        $import->files = $files;
        $import->report = null;
        $import->save();
        $this->dispatchJob($import, 'analyse');

        return $this->render($resort);
    }

    public function remove(Resort $resort, string $fileId)
    {
        $this->authorizeResorts('edit');

        $import = $this->draft($resort);
        $files = collect($import->files ?? []);
        $file = $files->firstWhere('id', $fileId);
        abort_unless($file, 404);
        if ($import->busy()) {
            return $this->render($resort, [['warning', 'A job is still running for this resort — wait for it to finish.']]);
        }

        StorageHelper::delete($file['path']);
        $import->files = $files->reject(fn ($f) => $f['id'] === $fileId)->values()->all();
        $import->report = null;
        $import->save();

        return $this->render($resort, [['success', "{$file['name']} removed."]]);
    }

    public function options(Request $request, Resort $resort)
    {
        $this->authorizeResorts('edit');

        $import = $this->draft($resort);
        if ($import->busy()) {
            return $this->render($resort, [['warning', 'A job is still running for this resort — wait for it to finish.']]);
        }
        $ranks = array_map('strval', array_keys(config('settings.Position_Rank')));
        $departments = ResortDataImporter::options($import)['departments'];
        $notices = [];

        $domain = strtolower(trim((string) $request->input('email_domain')));
        if (!preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$/', $domain)) {
            $notices[] = ['danger', 'Email domain must look like resortname.wisdom.local'];
            $domain = $import->options['email_domain'] ?? null;
        }
        $periodStart = (string) $request->input('period_start');
        if ($periodStart !== '' && ResortDataImporter::parseDate($periodStart) === false) {
            $notices[] = ['danger', 'Attendance period start is not a valid date.'];
            $periodStart = '';
        }
        $shiftId = $request->input('shift_id');
        if ($shiftId && !ShiftSettings::where('resort_id', $resort->id)->where('id', $shiftId)->exists()) {
            $shiftId = '';
        }
        $categoryIds = LeaveCategory::where('resort_id', $resort->id)->pluck('id')->map(fn ($id) => (string) $id)->all();

        $import->options = array_filter([
            'email_domain' => $domain,
            'level_ranks'  => array_map(fn ($r) => in_array((string) $r, $ranks, true) ? (string) $r : '', (array) $request->input('level_ranks', [])),
            'roles'        => array_map(fn ($d) => in_array($d, $departments, true) ? $d : '', array_intersect_key((array) $request->input('roles', []), ResortDataImporter::ROLE_MAIN_RANK)),
            'codes'        => array_map(fn ($a) => in_array($a, ['present', 'dayoff', 'skip'], true)
                || (str_starts_with((string) $a, 'leave:') && in_array(substr($a, 6), $categoryIds, true)) ? $a : '', (array) $request->input('codes', [])),
            'period_start' => $periodStart,
            'shift_id'     => $shiftId ? (int) $shiftId : '',
        ], fn ($v) => $v !== null);
        $import->report = null;
        $import->save();

        return $this->render($resort, $notices ?: [['success', 'Options saved.']]);
    }

    public function validateImport(Resort $resort)
    {
        return $this->runJob($resort, 'validate');
    }

    public function import(Resort $resort)
    {
        return $this->runJob($resort, 'import');
    }

    /** The temporary passwords are kept (encrypted) only until the admin has saved them. */
    public function clearCredentials(Resort $resort)
    {
        $this->authorizeResorts('edit');
        ResortDataImport::where('resort_id', $resort->id)->whereNotNull('credentials')->update(['credentials' => null]);
        return $this->render($resort, [['success', 'Temporary passwords cleared.']]);
    }

    private function runJob(Resort $resort, string $action)
    {
        $this->authorizeResorts('edit');

        $import = $this->draft($resort);
        if (!$import->exists || empty($import->files)) {
            return $this->render($resort, [['danger', 'Upload files first.']]);
        }
        if ($import->busy()) {
            return $this->render($resort, [['warning', 'A job is still running for this resort — wait for it to finish.']]);
        }
        if ($action === 'import' && !$this->validated($import)) {
            return $this->render($resort, [['danger', 'Run a validation with no errors first.']]);
        }
        $this->dispatchJob($import, $action);

        return $this->render($resort);
    }

    private function dispatchJob(ResortDataImport $import, string $action): void
    {
        $import->forceFill(['job_action' => $action, 'job_status' => 'queued', 'job_message' => null, 'job_started_at' => null])->save();
        RunResortDataImport::dispatch($import->id);
    }

    private function validated(ResortDataImport $import): bool
    {
        $report = $import->report;
        return $import->status === 'draft' && $report && ($report['mode'] ?? '') === 'dry-run' && empty($report['errors']);
    }

    private function render(Resort $resort, array $notices = [])
    {
        $import = $this->draft($resort);
        if ($import->job_status === 'running' && $import->job_started_at?->lt(now()->subMinutes(self::STALE_MINUTES))) {
            $import->forceFill(['job_status' => 'failed', 'job_message' => 'The job stopped unexpectedly (worker restarted?) — nothing was saved. Run it again.'])->save();
        }
        // An import closes its batch: show that batch's result until a new upload starts the next one.
        $last = ResortDataImport::where('resort_id', $resort->id)->orderByDesc('id')->first();
        $shown = !$import->exists && $last ? $last : $import;

        return view('admin.resort_data_setup.show', [
            'resort'      => $resort,
            'import'      => $import,
            'shown'       => $shown,
            'canImport'   => $this->validated($import) && !$import->busy(),
            'options'     => ResortDataImporter::options($import),
            'notices'     => $notices,
            'credentials' => ResortDataImport::where('resort_id', $resort->id)->whereNotNull('credentials')->orderByDesc('id')->first()?->credentials ?? [],
            'fields'      => SheetMapper::FIELDS,
            'ranks'       => config('settings.Position_Rank'),
            'shifts'      => ShiftSettings::where('resort_id', $resort->id)->get(['id', 'ShiftName', 'StartTime', 'EndTime']),
            'categories'  => LeaveCategory::where('resort_id', $resort->id)->pluck('leave_type', 'id'),
            'existing'    => [
                'divisions'   => DB::table('resort_divisions')->where('resort_id', $resort->id)->count(),
                'departments' => DB::table('resort_departments')->where('resort_id', $resort->id)->count(),
                'positions'   => DB::table('resort_positions')->where('resort_id', $resort->id)->count(),
                'employees'   => Employee::where('resort_id', $resort->id)->count(),
            ],
        ]);
    }

    /** The resort's open batch; a new one starts with the last batch's options. */
    private function draft(Resort $resort): ResortDataImport
    {
        $last = ResortDataImport::where('resort_id', $resort->id)->orderByDesc('id')->first();
        if ($last && $last->status === 'draft') {
            return $last;
        }
        return new ResortDataImport([
            'resort_id' => $resort->id, 'status' => 'draft', 'files' => [], 'options' => $last->options ?? [],
            'created_by' => Auth::guard('admin')->id(),
        ]);
    }

    private function authorizeResorts(string $permission): void
    {
        abort_unless(Common::hasPermission(config('settings.admin_modules.resorts'), config('settings.permissions.' . $permission)), 403);
    }
}
