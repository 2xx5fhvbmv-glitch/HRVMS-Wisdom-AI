<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManifestVisitor extends Model
{
    use HasFactory;
    protected $table = 'manifest_visitors';
    
    protected $fillable = [
        'manifest_id',
        'visitor_name',
    ];

    public function manifest()
    {
        return $this->belongsTo(Manifest::class);
    }
}
