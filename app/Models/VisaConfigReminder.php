<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisaConfigReminder extends Model
{
    use HasFactory;

    protected  $table = 'visa_config_reminders';
    public  $fillable = ["resort_id" ,'Work_Permit_Fee','Work_Permit_Fee_reminder','Slot_Fee','Slot_Fee_reminder','Insurance','Insurance_reminder','Medical','Medical_reminder','Visa','Visa_reminder','Passport','Passport_reminder'];
}
