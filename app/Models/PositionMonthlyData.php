<?php
namespace App\Models; // Adjust according to your namespace

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PositionMonthlyData extends Model
{
    use HasFactory;

    // Specify the table if it's not the plural of the model name
    protected $table = 'position_monthly_data';

    // Define fillable attributes for mass assignment
    protected $fillable = [
        'manning_response_id',
        'position_id',
        'month',
        'headcount', 'vacantcount' ,'filledcount'
    ];

    /**
     * Define the relationship to ManningResponse.
     */
    public function manningResponse()
    {
        return $this->belongsTo(ManningResponse::class);
    }

    /**
     * Define the relationship to Position.
     */
    public function position()
    {
        return $this->belongsTo(ResortPosition::class);
    }
}
?>
