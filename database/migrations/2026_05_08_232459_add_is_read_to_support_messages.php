<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsReadToSupportMessages extends Migration
{
    public function up()
    {
        Schema::table('support_messages', function (Blueprint $table) {
            // Unread flag for the email-reply log so the admin Supports list
            // can show a count badge per ticket and on the sidebar nav. New
            // rows default to 0 (unread by the OTHER side); existing rows
            // are backfilled to 1 to avoid an avalanche of fake-unread badges.
            $table->boolean('is_read')->default(false)->after('attachments');
            $table->index(['ticket_id', 'sender', 'is_read']);
        });

        // Backfill historical rows as already read.
        \DB::table('support_messages')->update(['is_read' => 1]);
    }

    public function down()
    {
        Schema::table('support_messages', function (Blueprint $table) {
            $table->dropIndex(['ticket_id', 'sender', 'is_read']);
            $table->dropColumn('is_read');
        });
    }
}
