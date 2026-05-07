<?php

namespace App\Imports\Admin;

use App\Models\Department;
use App\Models\Section;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class SectionImport implements ToCollection, WithHeadingRow
{
    public int $created = 0;
    public int $skipped = 0;
    public array $errors = [];

    public function collection(Collection $rows)
    {
        $deptIndex = Department::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [strtolower(trim($name)) => $id]);

        foreach ($rows as $i => $row) {
            $rowNum = $i + 2;
            $name    = trim((string) ($row['name'] ?? ''));
            $deptRaw = trim((string) ($row['department_name'] ?? ''));
            if ($name === '' || $deptRaw === '') { continue; }

            $deptId = $deptIndex[strtolower($deptRaw)] ?? null;
            if (!$deptId) {
                $this->errors[] = "Row {$rowNum}: department \"{$deptRaw}\" not found";
                continue;
            }

            if (Section::where('dept_id', $deptId)->where('name', $name)->exists()) {
                $this->skipped++;
                continue;
            }

            try {
                Section::create([
                    'dept_id'    => $deptId,
                    'name'       => $name,
                    'code'       => trim((string) ($row['code'] ?? '')) ?: null,
                    'short_name' => trim((string) ($row['short_name'] ?? '')) ?: null,
                    'status'     => 'active',
                ]);
                $this->created++;
            } catch (\Throwable $e) {
                $this->errors[] = "Row {$rowNum}: " . $e->getMessage();
            }
        }
    }
}
