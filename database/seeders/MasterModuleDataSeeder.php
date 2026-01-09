<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Division;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Support\Facades\DB;

class MasterModuleDataSeeder extends Seeder
{
    public function run()
    {
        DB::transaction(function () {

            $userId = 1;
            $status = 'active';

            // === DIVISIONS, DEPARTMENTS, POSITIONS ===
            $divisions = [
                'Rooms' => [
                    'Management' => [
                        'Rooms Director',
                        'Operations Manager',
                        'Front Office Manager',
                        'Front Desk Manager',
                        'Assistant Front Desk Manager',
                        'Night Manager',
                        'Director Of Guest Services',
                        'Guest Services Manager',
                        'Chef Concierge',
                        'Executive Housekeeper',
                        'Director Of Housekeeping',
                        'Housekeeping Manager',
                        'Director Of Reservations',
                        'Reservations Manager',
                        'Transportation Manager',
                        'Club Floor Manager',
                    ],
                    'Front Office' => [
                        'Desk Clerk',
                        'Night Desk Clerk (Former Night Auditor)',
                        'Bell Captain',
                        'Bell/Luggage Attendant',
                        'Door Attendant',
                        'Dispatcher',
                        'Concierge',
                    ],
                    'Guest Services' => [
                        'Guest Services Representative',
                        'Activities Attendant',
                        'Guest Services Coordinator',
                    ],
                    'Housekeeping' => [
                        'Floor Supervisor',
                        'Room Attendant',
                        'House Attendant',
                        'Public Area Attendant',
                        'Turn-Down Attendant',
                        'Night Attendant',
                        'Sewing Attendant',
                        'Uniform Room Attendant',
                        'Housekeeping/Linen Runner',
                    ],
                    'Laundry' => [
                        'Linen Control Supervisor',
                        'Linen Room Attendant',
                        'Uniform Room Attendant',
                    ],
                    'Reservations' => [
                        'Reservations Agent',
                        'Guest Historian',
                    ],
                    'Transportation' => [
                        'Driver',
                    ],
                    'Complimentary Food and Beverage Club' => [
                        'Club Floor Attendant',
                        'Breakfast Attendant',
                    ],
                ],

                'Food and Beverage' => [
                    'Management—Service' => [
                        'Director Of Food And Beverage',
                        'Food And Beverage Manager',
                        'Director Of Venues',
                        'Restaurant Manager',
                        'Beverage Manager',
                        'Director Of Convention Services',
                        'Convention Services Manager',
                    ],
                    'Management—Kitchen' => [
                        'Executive Chef',
                        'Executive Sous Chef',
                        'Sous Chef',
                        'Chef De Cuisine',
                        'Pastry Chef',
                        'Kitchen Manager',
                        'Executive Steward',
                        'Stewarding Manager',
                    ],
                    'Banquet/Conference/Catering' => [
                        'Captain', 'Bartender', 'Server', 'Busperson',
                        'Porter', 'Attendant', 'Runner', 'Houseperson'
                    ],
                    'Kitchen' => [
                        'Chef', 'Garde Manager', 'Chef De Partie', 'Cook',
                        'Pastry Cook', 'Butcher', 'Baker', 'Steward', 'Cleaner'
                    ],
                    'Venues' => [
                        'Sommelier', 'Maître D’', 'Host(ess)', 'Captain',
                        'Bartender', 'Server', 'Busperson', 'Porter',
                        'Attendant', 'Runner', 'Cashier', 'Houseperson'
                    ],
                ],

                'Golf Course/Pro Shop' => [
                    'Management' => [
                        'Director Of Golf Course Maintenance',
                        'Director Of Golf',
                        'Golf Pro',
                        'Golf Pro Shop Manager',
                        'Retail Manager',
                        'Golf Course Maintenance Manager'
                    ],
                    'Golf Pros/Operations' => [
                        'Golf Instructor', 'Greens Keeper', 'Golf Course Attendant',
                        'Caddy', 'Golf Ranger', 'Golf Pro Assistant',
                        'Instructor', 'Starter'
                    ],
                    'Greens/Maintenance' => [
                        'Greens Supervisor', 'Greens Keeper', 'Gardener',
                        'General Maintenance', 'Driver', 'Mechanic',
                        'Golf Cart Maintenance', 'Repair Attendant', 'Golf Cart Storage Attendant'
                    ],
                    'Pro Shop' => [
                        'Golf Cashier', 'Locker Room Attendant',
                        'Club Storage Attendant', 'Golf Pro Shop Cashier',
                        'Golf Pro Shop Attendant', 'Sales Clerk'
                    ],
                ],

                'Administrative and General' => [
                    'Management' => [
                        'Managing Director', 'General Manager', 'Resident Manager',
                        'Hotel Manager', 'Director Of Operations', 'Quality Assurance Manager',
                        'Controller', 'Assistant Controller', 'Accounting Manager', 'Credit Manager',
                        'Financial Analyst', 'Audit Manager', 'Cost Controller',
                        'Profit Improvement Manager', 'Director Of Purchasing',
                        'Director Of Security', 'Training Director', 'Benefits Manager', 'Employee Relations Manager',
                        'Employment Manager', 'Package Room Manager', 'Security Manager'
                    ],
                    'Accounting' => [
                        'Director Of Finance', 'Assistant Director Of Finance',
                        'Chief Accountant', 'Accounts Receivable Manager', 'Accounts Payable Manager',
                        'Accounts Payable Clerk', 'Accounts Receivable Clerk',
                        'General Cashier', 'Paymaster', 'Staff Accountant',
                        'Group Billing Clerk', 'Accounting Clerk'
                    ],
                    'Human Resources' => [
                        'Director of Human Resources', 'Human Resources Manager',
                        'Human Resources Coordinator', 'Benefits Coordinator'
                    ],
                    'Purchasing and Receiving' => [
                        'Buyer', 'Clerk', 'Receiving Agent', 'Storekeeper',
                        'Purchasing Agent', 'Purchasing Coordinator', 'Storeroom And Receiving',
                        'General Storeroom Attendant', 'Receiving Clerk', 'Package Room Attendant'
                    ],
                    'Security' => ['Security Officer']
                ],
            ];

            // === INSERT DATA ===
            foreach ($divisions as $divName => $departments) {

                $division = Division::create([
                    'name' => $divName,
                    'code' => strtoupper(str_replace([' ', '/', '—'], '_', $divName)),
                    'short_name' => substr($divName, 0, 10),
                    'status' => $status,
                    'created_by' => $userId,
                    'modified_by' => $userId,
                ]);

                foreach ($departments as $deptName => $positions) {

                    $department = Department::create([
                        'division_id' => $division->id,
                        'name' => $deptName,
                        'code' => strtoupper(str_replace([' ', '/', '—'], '_', $deptName)),
                        'short_name' => substr($deptName, 0, 10),
                        'status' => $status,
                        'created_by' => $userId,
                        'modified_by' => $userId,
                    ]);

                    foreach ($positions as $posTitle) {
                        Position::create([
                            'dept_id' => $department->id,
                            'position_title' => $posTitle,
                            'code' => strtoupper(str_replace([' ', '/', '—', '’', '(', ')'], '_', $posTitle)),
                            'short_title' => substr($posTitle, 0, 10),
                            'status' => $status,
                            'created_by' => $userId,
                            'modified_by' => $userId,
                        ]);
                    }
                }
            }
        });
    }
}
