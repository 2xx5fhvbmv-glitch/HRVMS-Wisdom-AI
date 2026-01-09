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
use App\Models\Earnings;

class ImportAllowance implements  ToModel, WithHeadingRow
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
        return Earnings::updateOrCreate(
            [
                'resort_id' => $this->resort->resort_id, // Unique fields to check
                'allow_name' => $row['allowance_name'],
                'allow_type' => $row['allowance_type'],
            ],
            [
                'currency' => $row['currency'], // Fields to update
            ]
        );
    }
}

