<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use Carbon\Carbon;
use App\Helpers\Common;
class temp_language_video_store extends Model
{
    use HasFactory;

    protected $table = 'temp_language_video_store';

    public $fillable = ['video','os','ipAddress','resort_id'];
    public $timestamps = false; // Disable timestamps

}
