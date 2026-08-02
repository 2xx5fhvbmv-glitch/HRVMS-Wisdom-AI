<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Key-personnel "Request Identity Disclosure" flow: a key person requests to
// see the grievant's identity, the grievant approves/rejects, and only
// approved requesters can see it — tracked per-requester so approval is
// permanent for them (not a one-time flag) and re-requestable by others.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grivance_submission_models', function (Blueprint $table) {
            $table->unsignedBigInteger('Identity_Disclosure_Requested_By')->nullable()->after('Request_Identity_Disclosure');
            $table->json('Identity_Disclosed_To')->nullable()->after('Identity_Disclosure_Requested_By');
        });
    }

    public function down(): void
    {
        Schema::table('grivance_submission_models', function (Blueprint $table) {
            $table->dropColumn(['Identity_Disclosure_Requested_By', 'Identity_Disclosed_To']);
        });
    }
};
