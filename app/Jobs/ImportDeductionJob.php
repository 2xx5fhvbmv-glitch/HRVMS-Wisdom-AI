<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Imports\ImportDeduction;
use Maatwebsite\Excel\Facades\Excel;
class ImportDeductionJob implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $file;

    public function __construct( $file)
    {
        $this->file = $file;
    }

    public function handle()
    {
        Excel::import(new ImportDeduction(), $this->file);
    }
}
