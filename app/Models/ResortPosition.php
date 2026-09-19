<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


use Illuminate\Support\Facades\Auth;
use App\Models\ResortAdmin;
use Carbon\Carbon;
use App\Helpers\Common;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
class ResortPosition extends Model
{
    use HasFactory,HasSlug;
    protected $table = 'resort_positions';
    protected $fillable = [
        'resort_id','dept_id','section_id','position_title','code','short_title','is_reserved','status','Rank','slug','benefit_grid_level','employee_category'
    ];

    public static function boot(){
        parent::boot();

        self::saving(function ($model) {
            if (!$model->exists) {
                $model->created_by = Auth::guard('resort-admin')->user()->id;
            }

            if(Auth::guard('resort-admin')->check()) {
                $model->modified_by = Auth::guard('resort-admin')->user()->id;
            }
        });
    }

    public function getCreatedAtAttribute($value): ?string {
      if($value == '') {
        return '';
      } else {
        $dateFormat = Common::getDateFormateFromSettings();
        $timezone = config('app.timezone');
        $timeFormat = Common::getTimeFromSettings() == '12' ? 'h:i A' : 'H:i';
        $format = $dateFormat . ' ' . $timeFormat;
        return Carbon::parse($value)->setTimezone($timezone)->format($format);
      }
    }

    public function getUpdatedAtAttribute($value): ?string {
      if($value == '') {
        return '';
      } else {
        $dateFormat = Common::getDateFormateFromSettings();
        $timezone = config('app.timezone');
        $timeFormat = Common::getTimeFromSettings() == '12' ? 'h:i A' : 'H:i';
        $format = $dateFormat . ' ' . $timeFormat;
        return Carbon::parse($value)->setTimezone($timezone)->format($format);
      }
    }

    public function getCreatedByAttribute($value): ?string {
        $admin = ResortAdmin::select('first_name', 'last_name')->where('id', $this->attributes['created_by'])->first();

        $createdby = '';

        if($admin) {
            $createdby = ucwords($admin->first_name.' '.$admin->last_name);
        }

        return $createdby;
    }

    public function department()
    {
        return $this->belongsTo(ResortDepartment::class, 'dept_id', 'id');
    }

    public function section()
    {
        return $this->belongsTo(ResortSection::class, 'section_id', 'id');
    }

    public function getslugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('position_title') // The attribute to base the slug on
            ->saveSlugsTo('slug');      // The column where the slug is saved
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'Position_id', 'id');
    }

    public function payConfig()
    {
        return $this->hasOne(CasualPositionPayConfig::class, 'position_id', 'id');
    }

    /**
     * employee_category NULL means Permanent — every position that existed
     * before Casual/Intern positions could be created at all. Use this
     * anywhere a list/count is meant for Permanent purposes only, so
     * Casual/Intern positions (same table, tagged 'Casual'/'Intern') don't
     * leak in.
     */
    public function scopePermanent($query)
    {
        return $query->whereNull('employee_category');
    }

    /**
     * $category: 'Permanent' (-> whereNull) or 'Casual'/'Intern' (-> exact
     * match). Use where the category is already known/selected, e.g. a
     * vacancy form scoped to its own employee_type.
     */
    public function scopeForCategory($query, $category)
    {
        if ($category === 'Permanent' || empty($category)) {
            return $query->whereNull('employee_category');
        }
        return $query->where('employee_category', $category);
    }

}
