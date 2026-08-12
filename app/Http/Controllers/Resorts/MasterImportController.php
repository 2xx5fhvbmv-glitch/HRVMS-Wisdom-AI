<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Helpers\Common;
use App\Models\ImportHistory;
use Illuminate\Support\Facades\Auth;

class MasterImportController extends Controller
{
    /**
     * Landing page linking out to each module's existing import/config
     * page. Deliberately not a merged upload — each module's data structure
     * is different (see the Master Import investigation doc), so this is
     * just a single, clearer entry point instead of one buried inside the
     * Budget Config tabs.
     */
    public function index()
    {
        if (Common::checkRouteWisePermission('resort.masterimport.index', config('settings.resort_permissions.view')) == false) {
            return abort(403, 'Unauthorized access');
        }

        $page_title = 'Master Import';
        $resort_id = Auth::guard('resort-admin')->user()->resort_id;

        $tiles = [
            ['label' => 'Employee Profiles', 'description' => 'Bulk-create or update employee profiles from Excel.', 'route' => 'resort.Add.Employee', 'module' => 'employee'],
            ['label' => 'Workforce Planning Budget', 'description' => 'Import past consolidated budget data.', 'route' => 'resort.budget.config', 'module' => 'workforce_planning_budget'],
            ['label' => 'Payroll Configuration', 'description' => 'Import service charge, deduction, and earnings data.', 'route' => 'payroll.configration', 'module' => 'payroll_configuration'],
            ['label' => 'Time & Attendance', 'description' => 'Import historical attendance records.', 'route' => 'resort.timeandattendance.AttandanceRegister', 'module' => 'time_and_attendance'],
            ['label' => 'Leave', 'description' => 'Import past employee leave history.', 'route' => 'leave.configration', 'module' => 'leave'],
            ['label' => 'Accommodation', 'description' => 'Import previous employee accommodation records.', 'route' => 'resort.accommodation.config.index', 'module' => 'accommodation'],
            ['label' => 'Visa', 'description' => 'Import foreign employee deposit rate data.', 'route' => 'visa.config', 'module' => 'visa'],
        ];

        // import_history.module was already designed to hold any module's
        // name for exactly this ("future modules reuse this column") — only
        // the Employee import actually writes to it yet, so every other
        // tile will show "no import history yet" until its own
        // controller/job is wired up to log here too.
        $lastImportByModule = ImportHistory::where('resort_id', $resort_id)
            ->whereIn('module', array_column($tiles, 'module'))
            ->latest('created_at')
            ->get()
            ->unique('module')
            ->keyBy('module');

        foreach ($tiles as &$tile) {
            $last = $lastImportByModule->get($tile['module']);
            $tile['last_updated'] = $last ? $last->created_at->diffForHumans() : null;
            $tile['last_status'] = $last ? $last->status : null;
        }

        return view('resorts.masterimport.index', compact('page_title', 'tiles'));
    }
}
