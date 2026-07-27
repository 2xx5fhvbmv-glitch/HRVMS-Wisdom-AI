<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddManifestIdToEmployeeTravelPassesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_travel_passes', function (Blueprint $table) {
            $table->unsignedBigInteger('manifest_id')->nullable()->after('status');
            $table->index('manifest_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_travel_passes', function (Blueprint $table) {
            $table->dropIndex(['manifest_id']);
            $table->dropColumn('manifest_id');
        });
    }
}
