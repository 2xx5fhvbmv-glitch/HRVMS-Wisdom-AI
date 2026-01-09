<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ResortPagewisePermission;
class ResortInteralPagesPermission extends Model
{
    use HasFactory;

    protected $table = "resort_interal_pages_permissions";

    public $fillable = ['resort_id','Dept_id','position_id','page_id','Permission_id'];
    // public function resort_pagewise_permission()
    // {
    //     return $this->belongsTo(ResortPagewisePermission::class, 'page_permission_id');
    // }

    public function resortPagewisePermission()
    {
        return $this->belongsTo(ResortPagewisePermission::class, 'page_permission_id', 'id');
    }



}
