<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;

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
        $page_title = 'Master Import';

        $tiles = [
            ['label' => 'Employee Profiles', 'description' => 'Bulk-create or update employee profiles from Excel.', 'route' => 'resort.Add.Employee'],
            ['label' => 'Workforce Planning Budget', 'description' => 'Import past consolidated budget data.', 'route' => 'resort.budget.config'],
            ['label' => 'Payroll Configuration', 'description' => 'Import service charge, deduction, and earnings data.', 'route' => 'payroll.configration'],
            ['label' => 'Time & Attendance', 'description' => 'Import historical attendance records.', 'route' => 'resort.timeandattendance.AttandanceRegister'],
            ['label' => 'Leave', 'description' => 'Import past employee leave history.', 'route' => 'leave.configration'],
            ['label' => 'Accommodation', 'description' => 'Import previous employee accommodation records.', 'route' => 'resort.accommodation.config.index'],
            ['label' => 'Visa', 'description' => 'Import foreign employee deposit rate data.', 'route' => 'visa.config'],
        ];

        return view('resorts.masterimport.index', compact('page_title', 'tiles'));
    }
}
