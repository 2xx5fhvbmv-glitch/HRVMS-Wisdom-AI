<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// employees.device_token now stores a JSON-encoded array of FCM tokens (an
// employee can be logged into the app on more than one device) instead of a
// single scalar token — varchar(191) can't fit even 2 real FCM tokens
// (~150-180 chars each) once JSON-encoded together.
return new class extends Migration
{
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->text('device_token')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('device_token', 191)->nullable()->change();
        });
    }
};
