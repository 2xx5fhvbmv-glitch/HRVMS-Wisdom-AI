<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE applicant_wise_statuses MODIFY COLUMN status ENUM(
            'Sortlisted By Wisdom AI',
            'Rejected By Wisdom AI',
            'Sortlisted',
            'Round',
            'Rejected',
            'Selected',
            'Complete',
            'Pending',
            'Offer Letter Sent',
            'Offer Letter Accepted',
            'Offer Letter Rejected',
            'Contract Sent',
            'Contract Accepted',
            'Contract Rejected'
        ) NOT NULL DEFAULT 'Pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE applicant_wise_statuses MODIFY COLUMN status ENUM(
            'Sortlisted By Wisdom AI',
            'Rejected By Wisdom AI',
            'Sortlisted',
            'Round',
            'Rejected',
            'Selected',
            'Complete',
            'Pending'
        ) NOT NULL DEFAULT 'Pending'");
    }
};
