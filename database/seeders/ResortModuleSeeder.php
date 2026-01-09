<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Modules;
use Carbon\Carbon;

class ResortModuleSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $modules = [
            'Workforce Planning',
            'Payroll',
            'Talent Acquisition',
            'People',
            'Time and Attendance',
            'Leave',
            'Performance',
            'Learning',
            'Accommodation',
            'Incident',
            'Survey',
            'Reports',
            'Support',
            'Visa',
            'Grievance and Disciplinary',
            'File Management',
            'SOS',
            'Compliance',
            'Settings',
        ];

        foreach ($modules as $module) {
            Modules::updateOrCreate(
                ['module_name' => $module],
                [
                    'status' => 'Active',
                    'created_by' => 1,
                    'modified_by' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $this->command->info('✅ Resort modules seeded successfully.');
    }
}
