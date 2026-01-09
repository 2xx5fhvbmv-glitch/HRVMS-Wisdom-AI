<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdminsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('email',191)->unique();
            $table->string('password');
            $table->integer('role_id')->nullable();
            $table->string('profile_picture')->nullable();
            $table->string('home_phone')->nullable();
            $table->string('cell_phone')->nullable();
            $table->timestamp('start_date')->nullable();
            $table->longText('address')->nullable();
            $table->boolean('sms')->default(0);
            $table->boolean('allow_login')->default(0);
            $table->longText('notes')->nullable();
            $table->enum('status', ['active', 'inactive'])->nullable();
            $table->enum('type',['super','sub'])->nullable();
            $table->bigInteger('added_by')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('admins');
    }
}
