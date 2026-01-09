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
use App\Models\ServiceCharges;

class ImportServiceCharges implements  ToModel, WithHeadingRow
{
    protected $resort;

    public function __construct()
    {
        $this->resort= Auth::guard('resort-admin')->user();
    }

    public function model(array $row)
    {
        // Convert month name to month number
        $monthName = $row['month']; // Example: 'January', 'February', etc.
        $monthNumber = \DateTime::createFromFormat('F', $monthName)->format('n');
    
        // Update or create the record
        return ServiceCharges::updateOrCreate(
            [
                'resort_id' => $this->resort->resort_id, // Unique fields to check
                'month'     => $monthNumber,
                'year'      => $row['year'],
            ],
            [
                'service_charge' => $row['service_charge_in'], // Fields to update
            ]
        );
    }
}

