<?php
namespace App\Exports;

use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ExportEmployees implements WithMultipleSheets
{
    use Exportable;

    public function sheets(): array
    {
        $resortId = Auth::guard('resort-admin')->user()->resort_id;
        
        return [
            new ExportEmployeeMainSheet($resortId),
            new ExportEmployeeDropdownSheet($resortId),
        ];
    }
}