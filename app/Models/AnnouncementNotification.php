<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnouncementNotification extends Model
{
    use HasFactory;
    protected $table = 'announcement_notification';
    protected $fillable = [
        'resort_id',
        'announcement_id',
        'employee_id',
        'status',
    ];
}
