<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SosMassInstruction extends Model
{
    protected $table = 'sos_mass_instructions';

    protected $fillable = ['resort_id', 'sos_history_id', 'message', 'created_by'];

    public function sender()
    {
        return $this->belongsTo(ResortAdmin::class, 'created_by');
    }
}
