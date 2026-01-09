<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModulePagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('module_pages', function (Blueprint $table) {
            $table->id();
            $table->string('page_name', 250)->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key for Module_Id

            // Foreign key constraint
            $table->foreignId('Module_Id') // Automatically creates an unsigned integer
            ->constrained('modules') // References 'id' in 'modules' table
            ->onDelete('cascade')    // Optional: cascade on delete
            ->onUpdate('cascade'); // Optional: Cascade update
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('module_pages');
    }
}
