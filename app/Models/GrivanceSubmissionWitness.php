<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrivanceSubmissionWitness extends Model
{
    use HasFactory;
    protected $table="grivance_submission_witnesses";

    protected $fillable = [
                            'G_S_Parent_id',
                            'Witness_id',
                            'Wintness_Status',
                            'Statement',
                            'Statement',
                            'Attachement'
    ];
}
