<?php

namespace App\Imports\Admin;

use App\Models\Department;
use App\Models\Position;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class PositionImport implements ToCollection, WithHeadingRow
{
    public int $created = 0;
    public int $skipped = 0;
    public array $errors = [];

    public function collection(Collection $rows)
    {
        $deptIndex = Department::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [strtolower(trim($name)) => $id]);

        foreach ($rows as $i => $row) {
            $rowNum = $i + 2;
            $title   = trim((string) ($row['position_title'] ?? ''));
            $deptRaw = trim((string) ($row['department_name'] ?? ''));
            if ($title === '' || $deptRaw === '') { continue; }

            $deptId = $deptIndex[strtolower($deptRaw)] ?? null;
            if (!$deptId) {
                $this->errors[] = "Row {$rowNum}: department \"{$deptRaw}\" not found";
                continue;
            }

            if (Position::where('dept_id', $deptId)->where('position_title', $title)->exists()) {
                $this->skipped++;
                continue;
            }

            try {
                Position::create([
                    'dept_id'        => $deptId,
                    'position_title' => $title,
                    'code'           => trim((string) ($row['code'] ?? '')) ?: null,
                    'short_title'    => trim((string) ($row['short_title'] ?? '')) ?: null,
                    'status'         => 'active',
                ]);
                $this->created++;
            } catch (\Throwable $e) {
                $this->errors[] = "Row {$rowNum}: " . $e->getMessage();
            }
        }
    }
}
