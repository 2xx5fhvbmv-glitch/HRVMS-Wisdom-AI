<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApplicantWiseStatusField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()

        {
            Schema::table('applicant_wise_statuses', function (Blueprint $table) {
                if (Schema::hasColumn('applicant_wise_statuses', 'status')) {
                    $table->dropColumn('status');
                }
            });
            Schema::table('applicant_wise_statuses', function (Blueprint $table)
            {
                $table->enum('status', [
                                        'Sortlisted By Wisdom AI',
                                        'Rejected By Wisdom AI',
                                        'Sortlisted',
                                        'Round',
                                        'Rejected',
                                        'Selected',
                                        'Complete',
                                        'Pending'
                                    ])->nullable();
            });
        }

        public function down()
        {
            Schema::table('applicant_wise_statuses', function (Blueprint $table) {
                $table->dropColumn('status');


            });
        }

}
