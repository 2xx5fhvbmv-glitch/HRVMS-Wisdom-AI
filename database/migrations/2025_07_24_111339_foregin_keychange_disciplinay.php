<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ForeginKeychangeDisciplinay extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disciplinary_investigation_children', function (Blueprint $table) {
            // First, drop the existing foreign key
            $table->dropForeign('disciplinary_investigation_children_disciplinary_p_id_foreign');

            // Then, add the new self-referencing foreign key
            $table->foreign('Disciplinary_P_id')
                ->references('id')
                ->on('disciplinary_investigation_parents')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    public function down()
    {
        Schema::table('disciplinary_investigation_children', function (Blueprint $table) {
            // Drop the self-referencing key
            $table->dropForeign('disciplinary_investigation_children_disciplinary_p_id_foreign');

       
            $table->foreign('Disciplinary_P_id')
                ->references('id')
                ->on('disciplinary_investigation_parents')
                ->onDelete('cascade');
        });
    }
}
