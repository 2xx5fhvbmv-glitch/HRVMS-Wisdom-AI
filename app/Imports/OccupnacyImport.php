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
use DateTime;
use App\Models\ResortDepartment;
use  App\Models\Occuplany;
use PhpOffice\PhpSpreadsheet\Shared\Date;


use Illuminate\Support\Facades\Session;

class OccupnacyImport implements ToModel, WithHeadingRow
{
    protected $resort;
    protected $affectedIds = [];
    protected $rowCount = 1;

    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
    }

    public function model(array $row)
    {
        $this->rowCount++;

        // Trim and normalize values
        $dateRaw = trim($row['date'] ?? '');
        $occupiedRaw = trim($row['total_occupied_room'] ?? '');
        $totalRoomsRaw = trim($row['total_room'] ?? '');

        // Validate required fields
        if (empty($dateRaw)) {
            $this->addImportError("Row {$this->rowCount}: Date is required.");
            return null;
        }

        if (empty($occupiedRaw) || !is_numeric($occupiedRaw)) {
            $this->addImportError("Row {$this->rowCount}: Total Occupied Room must be a number and not empty.");
            return null;
        }

        if (empty($totalRoomsRaw) || !is_numeric($totalRoomsRaw)) {
            $this->addImportError("Row {$this->rowCount}: Total Room must be a number and not empty.");
            return null;
        }

        // Convert date
        $convertedDate = $this->convertDate($dateRaw);
        if (!$convertedDate) {
            $this->addImportError("Row {$this->rowCount}: Invalid date format.");
            return null;
        }

        // Calculate
        $occupiedRooms = (float) $occupiedRaw;
        $totalRooms = (int) $totalRoomsRaw;
        $percentage = ($totalRooms > 0) ? ($occupiedRooms / $totalRooms) * 100 : 0;

        $data = [
            'occupancydate' => $convertedDate,
            'occupancyinPer' => number_format($percentage, 2),
            'occupancyOccupiedRooms' => $occupiedRooms,
            'occupancytotalRooms' => $totalRooms,
        ];

        $existing = Occuplany::where('resort_id', $this->resort->resort_id)
            ->where('occupancydate', $convertedDate)
            ->first();

        if ($existing) {
            $existing->update($data);
            $this->affectedIds[] = $existing->id;
        } else {
            $data['resort_id'] = $this->resort->resort_id;
            $created = Occuplany::create($data);
            $this->affectedIds[] = $created->id;
        }

        return null; // You can return $created if needed
    }

    public function getAffectedIds()
    {
        return array_unique($this->affectedIds);
    }

    protected function addImportError($msg)
    {
        $errors = session()->get('import_errors', []);
        $errors[] = $msg;
        session()->put('import_errors', $errors);

        
    }

    protected function convertDate($dateString)
    {
        if (is_numeric($dateString)) {
            return Date::excelToDateTimeObject($dateString)->format('Y-m-d');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
            return $dateString;
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dateString)) {
            $date = DateTime::createFromFormat('d/m/Y', $dateString);
            return $date ? $date->format('Y-m-d') : null;
        }

        return null;
    }
}
