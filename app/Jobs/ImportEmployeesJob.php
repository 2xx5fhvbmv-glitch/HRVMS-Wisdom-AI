<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Imports\EmployeeImport;
use Maatwebsite\Excel\Facades\Excel;

class ImportEmployeesJob implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $file;
    protected $departmentId;
    protected $positionId;

    public function __construct( $file)
    {
        $this->file = $file;
        // $this->departmentId = $departmentId;
        // $this->positionId = $positionId;
    }

    public function handle()
    {
            Excel::import(new EmployeeImport(), $this->file);

    }
}
