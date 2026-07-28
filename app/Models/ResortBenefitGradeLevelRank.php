<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResortBenefitGradeLevelRank extends Model
{
    protected $table = 'resort_benefit_grade_level_ranks';
    protected $fillable = ['resort_id', 'grade_level_id', 'rank'];

    public function gradeLevel()
    {
        return $this->belongsTo(ResortBenefitGradeLevel::class, 'grade_level_id');
    }

    /**
     * Assign exactly the given rank set to a grade level. A rank belongs to
     * at most one active grade at a time (unique on resort_id+rank), so
     * this releases whatever this grade level currently owns and
     * re-assigns the new set, evicting any other grade level that
     * currently owns a requested rank. Shared by both the Benefit Grid
     * form (assign inline while saving a grid) and the standalone Benefit
     * Grade Levels config screen, so the two never drift apart.
     */
    public static function assignRanksToGrade(int $resortId, int $gradeLevelId, array $ranks): void
    {
        static::where('resort_id', $resortId)->where('grade_level_id', $gradeLevelId)->delete();

        foreach ($ranks as $rank) {
            static::updateOrCreate(
                ['resort_id' => $resortId, 'rank' => (int) $rank],
                ['grade_level_id' => $gradeLevelId]
            );
        }
    }
}
