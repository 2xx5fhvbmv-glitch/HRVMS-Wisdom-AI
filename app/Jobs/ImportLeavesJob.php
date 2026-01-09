<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Imports\ImportLeaves;
use Maatwebsite\Excel\Facades\Excel;
class ImportLeavesJob implements ShouldQueue
{ use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $file;
    protected $resort_id;

    public function __construct($file, $resort_id)
    {
        $this->file = $file;
        $this->resort_id = $resort_id;
    }

    public function handle()
    {
        try {
            Excel::import(new ImportLeaves($this->resort_id), $this->file);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            \Log::error('Validation errors during leave import.', ['errors' => $e->failures()]);
        } catch (\Exception $e) {
            \Log::error('General error during leave import.', ['message' => $e->getMessage()]);
        }
    }
}
