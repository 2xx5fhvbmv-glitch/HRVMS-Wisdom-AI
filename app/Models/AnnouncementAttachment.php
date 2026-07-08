<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnouncementAttachment extends Model
{
    protected $table = 'announcement_attachments';

    protected $fillable = [
        'resort_id',
        'announcement_id',
        'child_file_id',
    ];
}
