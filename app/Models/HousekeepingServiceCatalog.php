<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HousekeepingServiceCatalog extends Model
{
    use HasFactory;
    protected $table = 'housekeeping_service_catalog';

    protected $fillable = ['resort_id', 'name', 'status'];
}
