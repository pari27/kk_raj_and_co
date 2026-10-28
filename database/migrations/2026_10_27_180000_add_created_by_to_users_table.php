<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Who added each staff account, shown on the employee page as "Added by".
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('employee_id')->constrained('users')->nullOnDelete();
        });

        // Fill it in for accounts whose creation is already in the Audit Log.
        $created = DB::table('audit_logs')
            ->where('subject_type', 'App\\Models\\User')
            ->where('action', 'Created')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->get(['subject_id', 'user_id']);

        foreach ($created as $entry) {
            DB::table('users')->where('id', $entry->subject_id)->whereNull('created_by')->update(['created_by' => $entry->user_id]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
