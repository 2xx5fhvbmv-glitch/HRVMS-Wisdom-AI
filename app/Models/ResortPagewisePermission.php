<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Modules;
use App\Models\ResortInteralPagesPermission;
class ResortPagewisePermission extends Model
{
    use HasFactory;
    protected $table = "resort_pagewise_permissions";

    protected $fillable = [
        'resort_id','Module_id','page_permission_id'
    ];

    // Relationship to the Modules table

    public function getResortModules()
    {
        return $this->belongsTo(Modules::class, 'Module_id', 'id');
    }
   
    public function getPagePermission()
    {
        return $this->hasMany(ModulePages::class, 'Module_Id', 'Module_id');
    }


    public function modulePage()
    {
        return $this->belongsTo(ModulePages::class, 'page_permission_id', 'id');
    }

    public function Resort_internal_pages()
    {
        return $this->hasMany(ResortInteralPagesPermission::class,'page_id','page_permission_id');
    }


    public function resort_internal_pages_permissions()
    {
        return $this->hasMany(ResortInteralPagesPermission::class, 'page_id', 'page_permission_id');
    }


}
