<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Resort Data Setup ledger: every row an import created, changed or removed,
 * with before/after values and the source file + row. Drives "Undo import",
 * the re-import change list and per-record history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resort_data_import_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('import_id')->index();
            $table->string('kind', 30);          // divisions | departments | … | staff | attendance | access
            $table->string('table_name', 64);
            $table->unsignedBigInteger('record_id')->nullable();
            $table->string('action', 20);        // created | created_where | updated | deleted
            $table->json('data')->nullable();    // updated: {field: [old, new]} · created_where: where · deleted: full row
            $table->string('label')->nullable(); // e.g. "HAY-0433 RAJAKUMARA MANICKAM"
            $table->string('source_file')->nullable();
            $table->unsignedInteger('source_row')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['table_name', 'record_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resort_data_import_records');
    }
};
