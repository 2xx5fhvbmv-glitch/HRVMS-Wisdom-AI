<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $table = 'faqs';

    protected $fillable = [
        'resort_id',
        'question',
        'answer',
        'sort_order',
        'status',
    ];
}
