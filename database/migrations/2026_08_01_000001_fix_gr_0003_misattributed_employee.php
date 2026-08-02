<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// GrievanceController::GrievanceStore() used to trust a client-supplied
// Employee_id instead of the authenticated employee (fixed in
// app/Http/Controllers/API/GrievanceController.php). GR-0003 was filed by
// Rani Khan (employee 189) but recorded against Priya Sharma (employee 177,
// resort 26) — correcting the one row confirmed wrong.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('grivance_submission_models')
            ->where('Grivance_id', 'GR-0003')
            ->where('resort_id', 26)
            ->where('Employee_id', 177)
            ->update(['Employee_id' => 189]);
    }

    public function down(): void
    {
        DB::table('grivance_submission_models')
            ->where('Grivance_id', 'GR-0003')
            ->where('resort_id', 26)
            ->where('Employee_id', 189)
            ->update(['Employee_id' => 177]);
    }
};
