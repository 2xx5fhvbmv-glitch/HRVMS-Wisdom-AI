<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddSalaryIncrementToProbationLetterTemplatesType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('probation_letter_templates', function (Blueprint $table) {
            DB::statement("ALTER TABLE probation_letter_templates
                MODIFY COLUMN type ENUM('success', 'failed', 'promotion', 'experience', 'offer', 'salary_increment')
                NOT NULL");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('probation_letter_templates', function (Blueprint $table) {
            DB::statement("ALTER TABLE probation_letter_templates
                MODIFY COLUMN type ENUM('success', 'failed', 'promotion', 'experience', 'offer')
                NOT NULL");
        });
    }
}
