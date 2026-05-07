<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        Schema::create('disciplinary_followup_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('resort_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamps();
            $table->index(['resort_id', 'status']);
            $table->unique(['resort_id', 'name']);
        });

        // Seed each existing resort with the same defaults the disciplinary
        // investigation form has hardcoded today, so the dropdown isn't
        // empty for resorts that haven't configured anything yet.
        $defaults = [
            'Gather Witness Statements',
            'Inspect Site',
            'Review Documents',
            'CCTV Footage Review',
            'Check Access Logs',
            'Gather Physical Evidence',
        ];
        $resortIds = DB::table('resorts')->pluck('id');
        $now = now();
        $rows = [];
        foreach ($resortIds as $rid) {
            foreach ($defaults as $name) {
                $rows[] = [
                    'resort_id' => $rid,
                    'name'      => $name,
                    'status'    => 'active',
                    'created_at'=> $now,
                    'updated_at'=> $now,
                ];
            }
        }
        if (!empty($rows)) {
            // Skip duplicates if any partial seed exists.
            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('disciplinary_followup_actions')->insertOrIgnore($chunk);
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('disciplinary_followup_actions');
    }
};
