<?php

namespace Tests\Feature;

use App\Models\ResortAdmin;
use App\Models\TemporaryClinicDoctor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/** Security audit W1-NV02: employee and clinic doctor tokens only work on their own guard; doctors can log in again. */
class ClinicTokenSeparationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_tokens_are_bound_to_their_own_login()
    {
        (require database_path('migrations/2026_10_29_130000_separate_oauth_clients_for_employees_and_clinic_doctors.php'))->up();

        $doctor = TemporaryClinicDoctor::first() ?? $this->markTestSkipped('No temporary clinic doctor in this database.');
        $doctor->forceFill(['status' => 'Active', 'expires_at' => now()->addYear()])->save();   // an expired contract is refused anyway
        $employee = ResortAdmin::where('status', 'Active')->whereHas('GetEmployee')->firstOrFail();

        $doctorToken = $doctor->createToken('TemporaryClinicDoctorToken')->accessToken;   // failed before: no client for this provider
        $employeeToken = $employee->createToken('ResortAdminToken')->accessToken;

        $get = fn ($url, $token) => $this->withHeader('Authorization', 'Bearer ' . $token)->getJson($url)->status();
        $this->assertSame(200, $get('/api/clinic-doctor/profile', $doctorToken));
        app('auth')->forgetGuards();
        $this->assertSame(401, $get('/api/clinic-doctor/profile', $employeeToken));   // employee token is not a doctor token
        app('auth')->forgetGuards();
        $this->assertSame(401, $get('/api/resort/profile', $doctorToken));            // doctor token is not an employee token
        app('auth')->forgetGuards();
        $this->assertNotSame(401, $get('/api/resort/profile', $employeeToken));
    }
}
