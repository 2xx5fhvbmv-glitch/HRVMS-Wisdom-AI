<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateManifestEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('manifest_employees', function (Blueprint $table) {
           $table->id();
            $table->unsignedBigInteger('manifest_id');
            $table->unsignedInteger('employee_id');
            $table->timestamps();

            $table->foreign('manifest_id')->references('id')->on('manifest')->onDelete('cascade');           
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('manifest_employees');
    }
}
