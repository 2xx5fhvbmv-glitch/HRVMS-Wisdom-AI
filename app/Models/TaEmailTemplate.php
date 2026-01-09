<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaEmailTemplate extends Model
{
    use HasFactory;
    protected $table = 'ta_email_templates';

    protected $fillable = ['Resort_id','TempleteName','MailTemplete','MailSubject','Placeholders'];

    protected $casts = [
        'Placeholders' => 'array',  // Casting the Placeholders column to an array
    ];

    public static function extractPlaceholders($body)
    {
        preg_match_all('/{{(.*?)}}/', $body, $matches);
        return $matches[1]; // Return array of placeholders
    }

    public function resortemailtemplate()
    {
        return $this->belongsTo(Resort::class, 'Resort_id');
    }

}
