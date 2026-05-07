<?php

namespace App\Imports\Admin;

use App\Models\Department;
use App\Models\Division;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class DepartmentImport implements ToCollection, WithHeadingRow
{
    public int $created = 0;
    public int $skipped = 0;
    public array $errors = [];

    public function collection(Collection $rows)
    {
        // Cache divisions by lowercased name to skip per-row queries.
        $divIndex = Division::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [strtolower(trim($name)) => $id]);

        foreach ($rows as $i => $row) {
            $rowNum = $i + 2;
            $name        = trim((string) ($row['name'] ?? ''));
            $divisionRaw = trim((string) ($row['division_name'] ?? ''));
            if ($name === '' || $divisionRaw === '') { continue; }

            $divisionId = $divIndex[strtolower($divisionRaw)] ?? null;
            if (!$divisionId) {
                $this->errors[] = "Row {$rowNum}: division \"{$divisionRaw}\" not found";
                continue;
            }

            if (Department::where('division_id', $divisionId)->where('name', $name)->exists()) {
                $this->skipped++;
                continue;
            }

            try {
                Department::create([
                    'division_id' => $divisionId,
                    'name'        => $name,
                    'code'        => trim((string) ($row['code'] ?? '')) ?: null,
                    'short_name'  => trim((string) ($row['short_name'] ?? '')) ?: null,
                    'status'      => 'active',
                ]);
                $this->created++;
            } catch (\Throwable $e) {
                $this->errors[] = "Row {$rowNum}: " . $e->getMessage();
            }
        }
    }
}
