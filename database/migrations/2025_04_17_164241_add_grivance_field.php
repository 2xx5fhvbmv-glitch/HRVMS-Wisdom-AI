<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGrivanceField extends Migration
{
    public function up()
    {
        Schema::table('grivance_submission_models', function (Blueprint $table) 
        {
            $table->enum('RequestforStatment',["Yes","No"])->default("No");
            $table->renameColumn('Grivance_offence_id', 'Grivance_Sub_cat');
        });
    } 

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('grivance_submission_models', function (Blueprint $table) {
            $table->dropColumn('RequestforStatment')->default(false);
            $table->renameColumn('Grivance_Sub_cat', 'Grivance_offence_id');

        });
    }
}
