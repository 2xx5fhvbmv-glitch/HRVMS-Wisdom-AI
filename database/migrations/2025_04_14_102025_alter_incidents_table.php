<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterIncidentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->unsignedBigInteger('outcome_type')->nullable()->after('severity');
            $table->longText('preventive_measures')->nullable()->after('outcome_type');
            $table->unsignedBigInteger('action_taken')->nullable()->after('preventive_measures');
            $table->boolean('approval')->default(false)->after('action_taken');
            $table->integer('approved_by')->nullable()->after('approval');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('approval_remarks')->nullable()->after('approved_at');
            $table->integer('created_by')->after('status');
            $table->integer('modified_by')->after('created_by');
            $table->integer('resolved_by')->nullable()->after('modified_by');
            $table->timestamp('resolved_at')->nullable()->after('resolved_by');

            $table->foreign('outcome_type')->references('id')->on('incident_outcome_types')->onDelete('cascade');
            $table->foreign('action_taken')->references('id')->on('incident_actions_taken')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropForeign(['outcome_type']);
            $table->dropForeign(['action_taken']);

            $table->dropColumn([
                'outcome_type',
                'preventive_measures',
                'action_taken',
                'approval',
                'approved_by',
                'approved_at',
                'approval_remarks',
                'created_by',
                'modified_by',
                'resolved_by',
                'resolved_at',
            ]);
        });
    }
}
