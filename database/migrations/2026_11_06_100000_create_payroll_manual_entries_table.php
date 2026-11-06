<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Payroll G1 (founder, 2026-11-06): every payroll figure is computed on the server; the only numbers HR types or
 * uploads — the monthly service-charge pool, city ledger, "Other" deductions and one-off extra payments — are kept
 * here with who entered them, when and why. Removing one keeps the row (deleted_by / deleted_at).
 *
 * Runs not yet locked carry their already-saved city ledger / "Other" amounts over as entries, so the new
 * pre-approval check can tell them apart from computed figures.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payroll_manual_entries')) {
            Schema::create('payroll_manual_entries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('payroll_id')->index();
                $table->unsignedBigInteger('employee_id')->nullable();   // null for the service-charge pool
                $table->string('kind', 30);                               // service_charge_pool | city_ledger | other_deduction | extra_payment
                $table->string('label')->nullable();                      // deduction type / extra payment name
                $table->decimal('amount', 12, 2);
                $table->string('currency', 3);                            // USD | MVR
                $table->text('reason');
                $table->unsignedBigInteger('created_by')->nullable();     // resort_admins.id
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->timestamp('deleted_at')->nullable();
                $table->timestamps();
                $table->index(['payroll_id', 'kind']);
            });
        }

        $runs = DB::table('payroll')->where('status', '!=', 'locked')->get(['id', 'resort_id']);
        foreach ($runs as $run) {
            if (DB::table('payroll_manual_entries')->where('payroll_id', $run->id)->exists()) {
                continue;
            }
            $currency = DB::table('resort_site_settings')->where('resort_id', $run->resort_id)->value('currency') === 'MVR' ? 'MVR' : 'USD';
            $rows = [];
            foreach (DB::table('payroll_deductions')->where('payroll_id', $run->id)->get(['employee_id', 'city_ledger', 'other']) as $d) {
                foreach (['city_ledger' => 'city_ledger', 'other_deduction' => 'other'] as $kind => $column) {
                    if ((float) $d->$column > 0) {
                        $rows[] = ['payroll_id' => $run->id, 'employee_id' => $d->employee_id, 'kind' => $kind, 'amount' => round((float) $d->$column, 2),
                            'currency' => $currency, 'reason' => 'Carried over from the payroll wizard (saved before 2026-11-06)',
                            'created_at' => now(), 'updated_at' => now()];
                    }
                }
            }
            if ($rows) {
                DB::table('payroll_manual_entries')->insert($rows);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_manual_entries');
    }
};
