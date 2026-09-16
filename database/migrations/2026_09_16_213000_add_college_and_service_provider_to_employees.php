<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCollegeAndServiceProviderToEmployees extends Migration
{
    /**
     * §23 — "Mark as Recruited/Hired" collects a college/institute name
     * for Intern hires and a vendor/service-provider name for Casual
     * hires; neither had a home on employees before.
     */
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'college_institute_name')) {
                $table->string('college_institute_name')->nullable()->after('employment_type');
            }
            if (!Schema::hasColumn('employees', 'service_provider_name')) {
                $table->string('service_provider_name')->nullable()->after('college_institute_name');
            }
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'service_provider_name')) {
                $table->dropColumn('service_provider_name');
            }
            if (Schema::hasColumn('employees', 'college_institute_name')) {
                $table->dropColumn('college_institute_name');
            }
        });
    }
}
