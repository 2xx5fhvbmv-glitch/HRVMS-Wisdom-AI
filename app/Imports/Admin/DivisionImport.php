<?php

namespace App\Imports\Admin;

use App\Models\Division;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class DivisionImport implements ToCollection, WithHeadingRow
{
    public int $created = 0;
    public int $skipped = 0;
    public array $errors = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $i => $row) {
            $rowNum = $i + 2; // header is row 1
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') { continue; }

            if (Division::where('name', $name)->exists()) {
                $this->skipped++;
                continue;
            }

            try {
                Division::create([
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
