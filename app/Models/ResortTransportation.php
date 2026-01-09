<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\Common;
use Auth;
use Carbon\Carbon;
class ResortTransportation extends Model
{
    use HasFactory;

    protected $table='resort_transportations';
    protected $fillable = [
        'resort_id',
        'transportation_option',
    ];

    public function resort()
    {
        return $this->belongsTo(Resort::class, 'resort_id');
    }


}
