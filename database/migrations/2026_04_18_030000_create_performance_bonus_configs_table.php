<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceBonusConfigsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('performance_bonus_configs')) {
            Schema::create('performance_bonus_configs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('resort_id');
                $table->unsignedTinyInteger('rank'); // matches config('settings.eligibilty') keys
                $table->decimal('bonus_percentage', 6, 2)->nullable();
                $table->timestamps();
                $table->unique(['resort_id', 'rank']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('performance_bonus_configs');
    }
}
