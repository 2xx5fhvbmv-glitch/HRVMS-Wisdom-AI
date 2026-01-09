<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisciplinaryInvestigationParent extends Model
{
    use HasFactory;

    public $table="disciplinary_investigation_parents";
    public $fillable = [
                            'resort_id',
                            'Disciplinary_id',
                            'Employee_id',
                            'Committee_member_id',
                            'invesigation_date',
                            'resolution_date',
                            'investigation_file',
                            'outcome_type'
                        ];
}
