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
     * Assign exactly the given rank set to a grade level. Multiple grade
     * levels may now share the same rank (e.g. "HOD L1" and "HOD L2" both
     * rank=HOD) — which specific one an employee actually gets is decided
     * per-employee via employees.benefit_grid_level (Common::resolveEmpGrade()),
     * not by rank exclusivity. This only ever touches this grade level's
     * own rows (resort_id+grade_level_id) — it no longer evicts a rank
     * from any OTHER grade level that also holds it. Shared by both the
     * Benefit Grid form (assign inline while saving a grid) and the
     * standalone Benefit Grade Levels config screen, so the two never
     * drift apart.
     */
    public static function assignRanksToGrade(int $resortId, int $gradeLevelId, array $ranks): void
    {
        static::where('resort_id', $resortId)->where('grade_level_id', $gradeLevelId)->delete();

        $rows = collect($ranks)->unique()->map(fn ($rank) => [
            'resort_id'      => $resortId,
            'grade_level_id' => $gradeLevelId,
            'rank'           => (int) $rank,
            'created_at'     => now(),
            'updated_at'     => now(),
        ])->all();

        if ($rows) {
            static::insert($rows);
        }
    }
}
