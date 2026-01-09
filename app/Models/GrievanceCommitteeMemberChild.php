<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrievanceCommitteeMemberChild extends Model
{
    use HasFactory;
    protected $table="grievance_committee_member_children";

    protected $fillable = [
                            'resort_id',
                            'Parent_id',
                            'Committee_Member_Id',
                           
                        ];
}
