<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignLanguageToVideoQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('video_questions', function (Blueprint $table) {
            $table->unsignedBigInteger('lang_id')->nullable()->change();
            $table->string('foreign_language')->nullable()->after('lang_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('video_questions', function (Blueprint $table) {
            $table->dropColumn('foreign_language');
        });
    }
}
