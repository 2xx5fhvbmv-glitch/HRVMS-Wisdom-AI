<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisciplineryCommitteeMembers extends Model
{
    use HasFactory;
    public $table='disciplinery_committee_members';
    public $fillable=['Parent_committee_id','MemberId'];
}
