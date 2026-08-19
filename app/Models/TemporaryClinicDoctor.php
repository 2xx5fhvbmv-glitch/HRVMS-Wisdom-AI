<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use App\Notifications\TemporaryClinicDoctorCredentials;

/**
 * A third-party/agency doctor account — never an Employee row (see the
 * create_temporary_clinic_doctors_table migration for why). Mobile-app-only:
 * authenticates via Passport on the 'temp-clinic-doctor' guard
 * (config/auth.php), same createToken() pattern LoginController::apiLogin()
 * uses for ResortAdmin. HR manages the account (create/deactivate/reset
 * password/toggle capabilities) from the web portal — see
 * Resorts/Clinic/TemporaryDoctorController.
 */
class TemporaryClinicDoctor extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'temporary_clinic_doctors';

    protected $fillable = [
        'resort_id', 'name', 'email', 'password', 'contact_no', 'agency_name',
        'can_view_appointments', 'can_manage_treatment',
        'can_view_medical_history', 'can_issue_medical_certificate',
        'status', 'expires_at', 'created_by',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'can_view_appointments' => 'boolean',
        'can_manage_treatment' => 'boolean',
        'can_view_medical_history' => 'boolean',
        'can_issue_medical_certificate' => 'boolean',
        'expires_at' => 'date',
    ];

    public function resort()
    {
        return $this->belongsTo(Resort::class, 'resort_id', 'id');
    }

    // ClinicController's notification text uses $this->user->first_name/
    // last_name (a ResortAdmin-shaped assumption) — this account only has
    // a single `name` column, so these split it for compatibility rather
    // than touching every call site.
    public function getFirstNameAttribute()
    {
        return trim(strtok($this->name ?? '', ' '));
    }

    public function getLastNameAttribute()
    {
        $first = $this->getFirstNameAttribute();
        return trim(substr($this->name ?? '', strlen($first)));
    }

    public function isActive(): bool
    {
        return $this->status === 'Active' && (!$this->expires_at || $this->expires_at->isFuture());
    }

    public function sendCredentialsEmail($plainPassword)
    {
        $this->notify(new TemporaryClinicDoctorCredentials($plainPassword));
    }
}
