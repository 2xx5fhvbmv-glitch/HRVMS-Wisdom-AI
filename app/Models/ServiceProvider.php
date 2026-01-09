<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use Carbon\Carbon;
use App\Helpers\Common;

class ServiceProvider extends Model
{
    use HasFactory;
    protected $table = 'service_providers';
    protected $fillable = [
        'resort_id','name'
    ];
}