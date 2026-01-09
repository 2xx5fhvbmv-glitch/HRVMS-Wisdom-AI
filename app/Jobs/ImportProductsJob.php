<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Imports\ImportProducts;
use Maatwebsite\Excel\Facades\Excel;
class ImportProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $file;
    protected $shopkeeperId;

    public function __construct($file, $shopkeeperId)
    {
        $this->file = $file;
        $this->shopkeeperId = $shopkeeperId;
    }

    public function handle()
    {
        Excel::import(new ImportProducts($this->shopkeeperId), $this->file);
    }
}