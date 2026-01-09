<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Auth;
use App\Models\Employee;
use App\Models\ResortAdmin;
use Illuminate\Support\Str;
use App\Helpers\Common;
use App\Models\ResortHoliday;
use PhpOffice\PhpSpreadsheet\Shared\Date;

use Hash;
use DB;
class ResortHolidayImport implements ToModel, WithHeadingRow
{
    protected $collection=[];
    protected $resort;

    public function __construct($resort_id)
    {
           $this->resort= $resort_id;
    }
    public function model(array $row)
    {


        $holidayDate = is_numeric($row['holidaydate']) ? Date::excelToDateTimeObject($row['holidaydate'])->format('Y-m-d') : $row[0];
        $ResortHoliday = ResortHoliday::where("PublicHolidaydate",$holidayDate)->where("PublicHolidayName",$row['holidayname'])->where("resort_id",$this->resort)->first();
        if($ResortHoliday ==null)
        {
            ResortHoliday::create(["PublicHolidaydate"=>$holidayDate,"PublicHolidayName"=>$row['holidayname'],"resort_id"=>$this->resort]);
        }
        else{

            $this->collection[]= ["PublicHolidaydate"=>$row['holidaydate'],"PublicHolidayName"=>$row['holidayname'],"resort_id"=>$this->resort];
        }


    }
}
