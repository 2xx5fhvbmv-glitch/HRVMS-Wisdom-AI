<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('offenses_models', function (Blueprint $table) {
            $table->unsignedBigInteger('default_severity_id')->nullable()->after('disciplinary_cat_id');
            $table->unsignedBigInteger('default_action_id')->nullable()->after('default_severity_id');
        });
    }

    public function down()
    {
        Schema::table('offenses_models', function (Blueprint $table) {
            $table->dropColumn(['default_severity_id', 'default_action_id']);
        });
    }
};
