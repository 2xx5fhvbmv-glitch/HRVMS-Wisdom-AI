<?php
namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class ImportProducts implements ToCollection
{
    public $rows;

    public function collection(Collection $rows)
    {
        // Skip header
        $this->rows = $rows->skip(1)->values();
    }
}
