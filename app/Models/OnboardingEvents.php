<?php
// app/Models/ItineraryTemplate.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnboardingEvents extends Model
{
    protected $fillable = ['resort_id', 'event_name', 'notification_time'];

}
