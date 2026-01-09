<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSurveyFileds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('parent_surveys', function (Blueprint $table) {
            
          
                DB::statement("ALTER TABLE parent_surveys MODIFY COLUMN Status ENUM('Publish', 'SaveAsDraft', 'OnGoing', 'Complete') NULL DEFAULT 'SaveAsDraft';");
           
            

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('parent_surveys', function (Blueprint $table) {
            DB::statement("ALTER TABLE parent_surveys MODIFY COLUMN Status ENUM('Publish', 'SaveAsDraft') NULL;");
        });
    }
}
