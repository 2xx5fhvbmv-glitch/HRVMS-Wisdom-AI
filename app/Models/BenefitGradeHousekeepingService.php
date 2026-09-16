<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BenefitGradeHousekeepingService extends Model
{
    use HasFactory;
    protected $table = 'benefit_grade_housekeeping_services';

    protected $fillable = ['resort_id', 'grade_level_id', 'housekeeping_service_id'];

    public function service()
    {
        return $this->belongsTo(HousekeepingServiceCatalog::class, 'housekeeping_service_id');
    }

    /**
     * Assign exactly the given set of eligible housekeeping services to a
     * grade level. Mirrors ResortBenefitGradeLevelRank::assignRanksToGrade()
     * — only ever touches this grade level's own rows.
     */
    public static function assignServicesToGrade(int $resortId, int $gradeLevelId, array $serviceIds): void
    {
        static::where('resort_id', $resortId)->where('grade_level_id', $gradeLevelId)->delete();

        $rows = collect($serviceIds)->unique()->map(fn ($serviceId) => [
            'resort_id'                => $resortId,
            'grade_level_id'           => $gradeLevelId,
            'housekeeping_service_id'  => (int) $serviceId,
            'created_at'               => now(),
            'updated_at'               => now(),
        ])->all();

        if ($rows) {
            static::insert($rows);
        }
    }
}
