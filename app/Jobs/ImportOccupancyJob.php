<?php

namespace App\Jobs;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Imports\OccupnacyImport;
use Maatwebsite\Excel\Facades\Excel;

class ImportOccupancyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $importfile ='';


    public function __construct( $file)
    {
        $this->importfile = $file;

    }

    public function handle()
    {

            Excel::import(new OccupnacyImport(), $this->importfile);

    }
}
