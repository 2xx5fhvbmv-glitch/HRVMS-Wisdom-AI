<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Imports\ImportAttandance;
use Maatwebsite\Excel\Facades\Excel;
class ImportAttandanceJob implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $file;
    protected $departmentId;
    protected $positionId;

    public function __construct( $file)
    {
        $this->file = $file;
    }

    public function handle()
    {
        Excel::import(new ImportAttandance(), $this->file);
    }
}
