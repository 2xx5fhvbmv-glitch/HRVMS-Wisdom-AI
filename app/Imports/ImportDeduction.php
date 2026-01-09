<?php

namespace App\Imports;

use DB;
use Auth;
use Hash;
use App\Helpers\Common;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;
use DateTime;
use App\Models\Deduction;

class ImportDeduction implements  ToModel, WithHeadingRow
{
    protected $resort;

    public function __construct()
    {
        $this->resort= Auth::guard('resort-admin')->user();
    }

    public function model(array $row)
    {   
        // dd($row);
        // Update or create the record
        return Deduction::updateOrCreate(
            [
                'resort_id' => $this->resort->resort_id, // Unique fields to check
                'deduction_name' => $row['deduction_name'],
                'deduction_type' => $row['deduction_type'],
            ],
            [
                'currency' => $row['currency'], // Fields to update
                'maximum_limit' => $row['maximum_limit_in']
            ]
        );
    }
}

