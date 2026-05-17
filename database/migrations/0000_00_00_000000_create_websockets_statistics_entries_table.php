<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebSocketsStatisticsEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Idempotency guard — the table is created by the production DB
        // import before this migration gets recorded in the `migrations`
        // table, so a plain Schema::create() makes `php artisan migrate`
        // abort with "table already exists". Skip when it already exists.
        if (Schema::hasTable('websockets_statistics_entries')) {
            return;
        }

        Schema::create('websockets_statistics_entries', function (Blueprint $table) {
            $table->increments('id');
            $table->string('app_id');
            $table->integer('peak_connection_count');
            $table->integer('websocket_message_count');
            $table->integer('api_message_count');
            $table->nullableTimestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('websockets_statistics_entries');
    }
}
