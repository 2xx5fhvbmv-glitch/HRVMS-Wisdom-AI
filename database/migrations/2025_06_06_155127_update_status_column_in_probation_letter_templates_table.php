<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateStatusColumnInProbationLetterTemplatesTable extends Migration
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
                MODIFY COLUMN type ENUM('success', 'failed', 'promotion', 'experience', 'offer') 
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
            MODIFY COLUMN type ENUM('success', 'failed', 'promotion', 'experinace', 'offer') 
            NOT NULL");
        });
    }
}
