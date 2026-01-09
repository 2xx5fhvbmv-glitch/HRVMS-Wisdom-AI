<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationLink extends Model
{
    use HasFactory;
    protected $table='application_links';
    protected $fillable = [

            'Resort_id',
            'ta_child_id',
            'link',
            'link_Expiry_date',
            'Old_ExpiryDate'
    ];

    // public function applicationlinktes()
    // {
    //     return $this->belongsTo(TAnotificationChild::class, 'ta_child_id', 'id');
    // }

    // public function TAnotificationChild()
    // {
    //     return $this->belongsTo(TAnotificationChild::class, 'ta_child_id', 'id');
    // }

    public function TAnotificationChild()
    {
        return $this->belongsTo(TAnotificationChild::class, 'ta_id', 'id');
    }
}
