<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['ticket_id']);
            $table->dropIndex(['ticket_id', 'paid_at']);
            $table->dropColumn('ticket_id');

            $table->foreignId('enquiry_id')->after('id')->constrained('enquiries')->cascadeOnDelete();
            $table->index(['enquiry_id', 'paid_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['enquiry_id']);
            $table->dropIndex(['enquiry_id', 'paid_at']);
            $table->dropColumn('enquiry_id');

            $table->foreignId('ticket_id')->after('id')->constrained('tickets')->cascadeOnDelete();
            $table->index(['ticket_id', 'paid_at']);
        });
    }
};
