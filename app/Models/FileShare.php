<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Share record for files (child_file_management) and folders
 * (filemangement_systems). Phase-1 surface; external token + expiry
 * columns exist on the table but are unused until phase 2 wires the
 * public /share/{token} resolver.
 */
class FileShare extends Model
{
    protected $table = 'file_shares';

    protected $fillable = [
        'shareable_type',
        'shareable_id',
        'share_mode',
        'scope_type',
        'shared_by',
        'resort_id',
        'token',
        'expires_at',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
        'expires_at'  => 'datetime',
    ];

    /** Recipient employees (only set when scope_type='employees'). */
    public function employees()
    {
        return $this->belongsToMany(
            Employee::class,
            'file_share_employees',
            'share_id',
            'employee_id'
        );
    }

    /** Recipient departments (only set when scope_type='departments'). */
    public function departments()
    {
        return $this->belongsToMany(
            ResortDepartment::class,
            'file_share_departments',
            'share_id',
            'department_id'
        );
    }

    /** Owner — the resort-admin who created the share. */
    public function sharer()
    {
        return $this->belongsTo(ResortAdmin::class, 'shared_by');
    }
}
