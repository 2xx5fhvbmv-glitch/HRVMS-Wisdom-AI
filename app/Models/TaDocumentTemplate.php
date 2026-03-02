<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaDocumentTemplate extends Model
{
    use HasFactory;

    protected $table = 'ta_document_templates';

    protected $fillable = [
        'resort_id', 'type', 'name', 'subject', 'content', 'file_path',
        'is_default', 'created_by', 'modified_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];
}