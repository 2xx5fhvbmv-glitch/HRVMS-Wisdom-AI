<?php

namespace App\Helpers;

use App\Models\Employee;

class ResortHelper
{

    public static function GetEmployeeRank($employee = null)
    {
        // Handle employee instance or ID
        if (is_numeric($employee)) {
            $employee = Employee::find($employee);
        }
    
        if (!$employee) {
            return ['rank' => null, 'isHR' => false];
        }
    
        // === Department-based HR detection ===
        $department = $employee->department ?? null;

        // dd($department);
        $isHR = false;
    
        if ($department) {
            // Normalize department name (remove case & spaces)
            $deptName = strtolower(trim($department->name));
    
            if (in_array($deptName, ['human resources', 'hr'])) {
                $isHR = true;
            }
        }
    
        // === Position title cleanup ===
        $title = $employee->position_title ?? null;
        $normalizedTitle = strtolower(trim($title));
  
        // Map equivalent HR leadership titles
        $hrTitles = [
            'director of human resources',
            'human resources manager',
            'hr manager',
            'director hr',
        ];
    
        if (in_array($normalizedTitle, $hrTitles)) {
            $title = 'Human Resources';
            $isHR = true;
        }
    
        // === Optional: rank logic (if config still used) ===
        $rankMap = config('settings.Position_Rank', []);
        $rankKey = $employee->rank ?? null;
        $rankValue = $rankMap[$rankKey] ?? null;
    
        return [
            'rank' => $title ?? $rankValue,
            'isHR' => $isHR,
        ];
    }

}