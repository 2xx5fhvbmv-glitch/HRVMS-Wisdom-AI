<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddIncidentInvestigationfields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('incidents_investigation', function (Blueprint $table) 
        {
            $columns = [
                'police_date',
                'police_time',
                'mndf_date',
                'mndf_time',
                'fire_rescue_date',
                'fire_rescue_time',
                'added_by_member_id',
                'created_by',
                'follow_up_actions',
                'approval',
                'police_notified',
                'police_notified_at',
                'mdf_notified',
                'mdf_notified_at',
                'fire_rescue_notified',
                'fire_rescue_notified_at',
            ];
            
            // First, check for and drop foreign keys if they exist
            if (Schema::hasColumn('incidents_investigation', 'added_by_member_id')) {
                // Get the actual constraint name from the database
                $foreignKeys = $this->getForeignKeyName('incidents_investigation', 'added_by_member_id');
                foreach ($foreignKeys as $foreignKey) {
                    $table->dropForeign($foreignKey);
                }
            }
            
            if (Schema::hasColumn('incidents_investigation', 'folloup_action')) {
                // Get the actual constraint name from the database
                $foreignKeys = $this->getForeignKeyName('incidents_investigation', 'folloup_action');
                foreach ($foreignKeys as $foreignKey) {
                    $table->dropForeign($foreignKey);
                }
            }
            
            // Then drop columns if they exist
            foreach ($columns as $column) {
                if (Schema::hasColumn('incidents_investigation', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            // Also drop the misspelled column if it exists
            if (Schema::hasColumn('incidents_investigation', 'folloup_action')) {
                $table->dropColumn('folloup_action');
            }
        });

        Schema::table('incidents_investigation', function (Blueprint $table) {
            $table->date('police_date')->nullable();
            $table->time('police_time')->nullable();
            $table->date('mndf_date')->nullable();
            $table->time('mndf_time')->nullable();
            $table->date('fire_rescue_date')->nullable();
            $table->time('fire_rescue_time')->nullable();
            $table->unsignedBigInteger('added_by_member_id')->nullable();
            $table->integer('created_by')->nullablle();
            $table->unsignedBigInteger('folloup_action')->nullable(); // Fixed the spelling here
            
            // Recreate foreign keys
            $table->foreign('added_by_member_id')
                  ->references('id')
                  ->on('incident_committee_members')
                  ->onDelete('cascade');
                  
            $table->foreign('folloup_action') // Fixed the spelling here
                  ->references('id')
                  ->on('incident_followup_actions')
                  ->onDelete('cascade');
        });        
    }

    /**
     * Get the actual foreign key constraint name from the database
     * 
     * @param string $table
     * @param string $column
     * @return array
     */
    private function getForeignKeyName($table, $column)
    {
        $foreignKeys = [];
        
        // This works for MySQL
        $constraints = DB::select(
            "SELECT CONSTRAINT_NAME 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = '{$table}' 
            AND COLUMN_NAME = '{$column}' 
            AND REFERENCED_TABLE_NAME IS NOT NULL"
        );
        
        foreach ($constraints as $constraint) {
            $foreignKeys[] = $constraint->CONSTRAINT_NAME;
        }
        
        return $foreignKeys;
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('incidents_investigation', function (Blueprint $table) {
            // Drop the new foreign keys
            $table->dropForeign(['added_by_member_id']);
            $table->dropForeign(['folloup_action']);
            
            // Drop the new columns
            $table->dropColumn([
                'police_date',
                'police_time',
                'mndf_date',
                'mndf_time',
                'fire_rescue_date',
                'fire_rescue_time',
                'added_by_member_id',
                'folloup_action'
            ]);
        });
    }
}