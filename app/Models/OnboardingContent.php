<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnboardingContent extends Model
{
    protected $table = 'onboarding_contents';

    protected $fillable = [
        'resort_id',
        'content_type',
        'title',
        'content',
    ];
}
