<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDocumentFileSizeFieldToEmployeesDocuments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees_documents', function (Blueprint $table) {
            $table->string('document_file_size')->after('document_category');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees_documents', function (Blueprint $table) {
            $table->dropColumn('document_file_size');
        });
    }
}
