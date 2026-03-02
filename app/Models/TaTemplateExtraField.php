<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaTemplateExtraField extends Model
{
    use HasFactory;

    protected $table = 'ta_template_extra_fields';

    protected $fillable = ['resort_id', 'field_key', 'field_value'];
}