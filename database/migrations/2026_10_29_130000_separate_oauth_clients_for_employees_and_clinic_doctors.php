<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Security audit W1-NV02. One personal-access client with provider NULL issued both employee ('api', resort-admins)
 * and temporary clinic doctor tokens, and Passport accepts a NULL-provider client's token on every guard: an
 * employee token resolved as the doctor with the same id and the other way round. Passport 13 also no longer picks
 * a NULL client for the doctors' provider, so doctor login failed ("Personal access client not found").
 *
 * - the NULL-provider personal-access client(s) become the employees' client (provider resort-admins);
 * - a personal-access client is added for temporary-clinic-doctors;
 * - doctor tokens issued by the shared client are revoked (they would now count as employee tokens); doctors log in again.
 */
return new class extends Migration
{
    public function up(): void
    {
        $shared = DB::table('oauth_clients')->whereNull('provider')->where('revoked', 0)
            ->when(Schema::hasColumn('oauth_clients', 'personal_access_client'), fn ($q) => $q->where('personal_access_client', 1))
            ->pluck('id');

        DB::table('oauth_clients')->whereIn('id', $shared)->update(['provider' => config('auth.guards.api.provider', 'resort-admins')]);

        DB::table('oauth_access_tokens')->whereIn('client_id', $shared)->where('name', 'TemporaryClinicDoctorToken')->update(['revoked' => 1]);

        $hasDoctorClient = DB::table('oauth_clients')->where('provider', 'temporary-clinic-doctors')->where('revoked', 0)->exists();
        if (!$hasDoctorClient) {
            $row = ['name' => 'WisdomAi Clinic Doctor Personal Access Client', 'secret' => Hash::make(Str::random(40)), 'provider' => 'temporary-clinic-doctors',
                'revoked' => 0, 'created_at' => now(), 'updated_at' => now()];
            $optional = ['user_id' => null, 'redirect' => 'http://localhost', 'redirect_uris' => '[]', 'personal_access_client' => 1, 'password_client' => 0,
                'grant_types' => json_encode(['personal_access']), 'owner_type' => null, 'owner_id' => null];
            foreach ($optional as $column => $value) {
                if (Schema::hasColumn('oauth_clients', $column)) {
                    $row[$column] = $value;
                }
            }
            if (!in_array(Schema::getColumnType('oauth_clients', 'id'), ['integer', 'bigint', 'int'], true)) {
                $row['id'] = (string) Str::uuid();
            }
            DB::table('oauth_clients')->insert($row);
        }
    }

    public function down(): void
    {
        DB::table('oauth_clients')->where('name', 'WisdomAi Clinic Doctor Personal Access Client')->delete();
        // The shared client's provider and the revoked doctor tokens are left as they are: going back reopens W1-NV02.
    }
};
