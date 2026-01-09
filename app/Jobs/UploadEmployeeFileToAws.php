<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Helpers\Common;

class UploadEmployeeFileToAws implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $resortId;
    protected $file;
    protected $empId;

    public function __construct($resortId, $file, $empId)
    {
        $this->resortId = $resortId;
        $this->file = $file;
        $this->empId = $empId;
    }

    public function handle()
    {
        
        return  Common::AWSEmployeeFileUpload($this->resortId, $this->file, $this->empId);
    }
}
