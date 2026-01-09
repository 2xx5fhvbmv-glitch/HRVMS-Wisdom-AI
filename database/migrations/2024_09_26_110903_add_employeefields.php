<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmployeefields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
        public function up()
        {
            Schema::table('employees', function (Blueprint $table) {

                if (Schema::hasColumn('employees', 'first_name')) {
                    $table->dropColumn('first_name');
                }
                if (Schema::hasColumn('employees', 'middle_name')) {
                    $table->dropColumn('middle_name');
                }
                if (Schema::hasColumn('employees', 'last_name')) {
                    $table->dropColumn('last_name');
                }
                if (Schema::hasColumn('employees', 'email')) {
                    $table->dropColumn('email');
                }
                if (Schema::hasColumn('employees', 'password')) {
                    $table->dropColumn('password');
                }
                if (Schema::hasColumn('employees', 'gender')) {
                    $table->dropColumn('gender');
                }
                if (Schema::hasColumn('employees', 'city')) {
                    $table->dropColumn('city');
                }
                if (Schema::hasColumn('employees', 'state')) {
                    $table->dropColumn('state');
                }
                if (Schema::hasColumn('employees', 'zip')) {
                    $table->dropColumn('zip');
                }
                if (Schema::hasColumn('employees', 'profile_photo')) {
                    $table->dropColumn('profile_photo');
                }
                if (Schema::hasColumn('employees', 'address_line_1')) {
                    $table->dropColumn('address_line_1');
                }
                if (Schema::hasColumn('employees', 'address_line_2')) {
                    $table->dropColumn('address_line_2');
                }
                if (Schema::hasColumn('employees', 'country')) {
                    $table->dropColumn('country');
                }

                if (Schema::hasColumn('employees', 'Resort_role_id')) {
                    $table->dropColumn('Resort_role_id');
                }


                if (!Schema::hasColumn('employees', 'Admin_Parent_id')) {
                    $table->integer('Admin_Parent_id')->after('id')->nullable();

                }

                if (!Schema::hasColumn('employees', 'nationality')) {
                    $table->string('nationality')->after('rank')->nullable();

                }


            });
        }

        public function down()
        {
            Schema::table('employees', function (Blueprint $table) {
                // Rollback the addition of Admin_Parent_id if needed
                $table->dropColumn('Admin_Parent_id');
            });
        }

}
