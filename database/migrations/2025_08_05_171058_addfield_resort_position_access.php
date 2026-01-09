<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddfieldResortPositionAccess extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('resorts', function (Blueprint $table) {
            //update the message column to text type
            $table->integer('Position_access')->nullable()->after('resort_prefix');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
       Schema::table('resorts', function (Blueprint $table) {
            //update the message column to text type
            $table->DropColumn('Position_access');
        });
    }
}
