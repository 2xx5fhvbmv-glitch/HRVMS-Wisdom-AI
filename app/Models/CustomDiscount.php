<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomDiscount extends Model
{
    use HasFactory;

    // Define the table name if it's not the default 'custom_leaves'
    protected $table = 'custom_discounts';

    // Define the fillable properties
    protected $fillable = [
        'benefit_grid_id',
        'discount_name',
        'discount_rate'
    ];

    // Relationship to the BenefitGrid model
    public function benefitGrid()
    {
        return $this->belongsTo(ResortBenifitGrid::class,"benefit_grid_id");
    }
}
?>
