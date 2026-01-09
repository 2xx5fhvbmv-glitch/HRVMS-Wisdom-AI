<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Manifest extends Model
{
    use HasFactory;
    protected $table = 'manifest';

    protected $fillable = [
        'resort_id',
        'transportation_mode',
        'transportation_name',
        'date',
        'time',
        'manifest_type', // 'arrival' or 'departure'
    ];


    public function visitors()
    {
        return $this->hasMany(ManifestVisitor::class);
    }

    public function employees()
    {
        return $this->hasMany(ManifestEmployee::class, 'manifest_id');
    }

    public function transportationMode()
    {
        return $this->belongsTo(ResortTransportation::class,'transportation_mode', 'id');
    }
}
