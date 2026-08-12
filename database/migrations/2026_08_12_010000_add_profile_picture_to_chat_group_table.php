<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ChatController::index() already reads $group->profile_picture (silently
 * null since the column never existed) and the Chat Module spec requires
 * Group Admin to be able to change the group photo — needed so
 * updateGroup() has somewhere to store the uploaded path.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('chat_group', function (Blueprint $table) {
            $table->string('profile_picture')->nullable()->after('description');
        });
    }

    public function down()
    {
        Schema::table('chat_group', function (Blueprint $table) {
            $table->dropColumn('profile_picture');
        });
    }
};
