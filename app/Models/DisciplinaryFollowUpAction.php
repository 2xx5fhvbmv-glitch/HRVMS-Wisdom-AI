<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class DisciplinaryFollowUpAction extends Model
{
    use HasFactory;

    protected $table = 'disciplinary_followup_actions';
    protected $fillable = ['resort_id','name','description','status','created_by','modified_by'];

    public static function boot()
    {
        parent::boot();
        self::saving(function ($model) {
            if (Auth::guard('resort-admin')->check()) {
                if (!$model->exists) {
                    $model->created_by = Auth::guard('resort-admin')->user()->id;
                }
                $model->modified_by = Auth::guard('resort-admin')->user()->id;
            }
        });
    }
}
