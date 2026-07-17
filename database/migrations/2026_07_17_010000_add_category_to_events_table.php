<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCategoryToEventsTable extends Migration
{
    /**
     * events_for is an audience-scope enum (organization/department/employee),
     * not a display category — there was no field for the app to key an
     * event icon off. Adds a free-text category (e.g. "Sports", "Meeting",
     * "Social") the creator can set and the app can map to the right icon.
     */
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('category')->nullable()->after('events_for');
        });
    }

    public function down()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
}
