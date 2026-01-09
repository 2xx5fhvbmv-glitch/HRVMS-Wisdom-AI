<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ResortHolidayImport;

class ResortHolidayImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $importfile ='';
    public $Resort_id ='';
    public function __construct($file,$Resort_id)
    {
        $this->Resort_id = $Resort_id;
        $this->importfile = $file;

    }

     public function handle()
    {
        Excel::import(new ResortHolidayImport($this->Resort_id), $this->importfile);


    }
}
