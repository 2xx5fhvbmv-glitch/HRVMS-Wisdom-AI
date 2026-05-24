<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfflineInterviewDocument extends Model
{
    protected $table = 'offline_interview_documents';

    protected $fillable = [
        'offline_interview_id', 'category', 'original_name', 'file_path',
        'mime_type', 'size_bytes', 'uploaded_by',
    ];

    public function interview()
    {
        return $this->belongsTo(OfflineInterview::class, 'offline_interview_id');
    }
}
