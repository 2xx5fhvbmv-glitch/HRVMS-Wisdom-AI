<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Imports\ConsolidateBudgetImport;
use Maatwebsite\Excel\Facades\Excel;

class ConsolidateBudgetImportJob implements ShouldQueue
{


        use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        protected $file;
        protected $requestCollection=[];
        protected $positionId;

        public function __construct( $file,$data)
        {
            $this->file = $file;
            $this->requestCollection = $data;


            // $this->departmentId = $departmentId;
            // $this->positionId = $positionId;
        }

        public function handle()
        {
                Excel::import(new ConsolidateBudgetImport( $this->requestCollection), $this->file);

        }
}
