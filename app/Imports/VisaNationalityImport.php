<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;

use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Auth;
use App\Models\Employee;
use App\Models\ResortAdmin;
use Illuminate\Support\Str;
use App\Helpers\Common;
use Hash;
use DB;
use App\Models\VisaNationality;
class VisaNationalityImport implements ToModel, WithHeadingRow
{
   
    protected $departmentId;
    protected $positionId;
    protected $resort;
    protected $rowNumber = 0;

    public function __construct()
    {
        $this->resort= Auth::guard('resort-admin')->user();
    }

    public function startRow(): int
    {
        return 2; // Assuming row 1 is headers
    }
    public function model(array $row)
    {
        $this->rowNumber++;
        $excelRowNumber = $this->rowNumber + $this->startRow() - 1;
        static $errors = [];
        
        if (!empty($row['nationality']) && !empty($row['amount'])) 
        {
            $nationality = config('settings.nationalities');

            // Validate nationality
            if (!in_array($row['nationality'], $nationality)) 
            { 
                $errors[] = [
                    'row' => $excelRowNumber,
                    'name' => trim($row['nationality']),
                    'Amount' => $row['amount'],
                    'error' => "Nationality  does not match with the internal link."
                ];
            }

            // Validate numeric value (only numbers or decimal)
            if (!is_numeric(trim($row['amount']))) 
            {
                $errors[] = [
                    'row' => $excelRowNumber,
                    'name' => trim($row['nationality']),
                    'Amount' => $row['amount'],
                    'error' => "Please Enter Amount in numerical Value."
                ];
            }

            if (!empty($errors)) {
                session(['import_errors' => $errors]);
                return null;
            }

            DB::beginTransaction();
            try {
                $VisaNationalitydata = [
                    'resort_id' =>  $this->resort->resort_id,
                    'nationality' => $row['nationality'],
                    'amt' => $row['amount'],
                ];
                $existingEmployee = VisaNationality::where('resort_id', $this->resort->resort_id)
                                                ->where("nationality", $row['nationality'])->first();
                if ($existingEmployee) 
                {
                    $existingEmployee->update($VisaNationalitydata);
                } 
                else
                {
                    VisaNationality::create($VisaNationalitydata);
                }
                DB::commit();
            } 
            catch (\Exception $e) 
            {
                DB::rollBack();
                $errors[] = [
                    'row' => $excelRowNumber,
                    'nationality' => $row['nationality'],
                    'Amount' => $row['amount'],
                    'error' => $e->getMessage()
                ];
                session(['import_errors' => $errors]);
            }

            return null; // Always return null for import
        }
    }

}
