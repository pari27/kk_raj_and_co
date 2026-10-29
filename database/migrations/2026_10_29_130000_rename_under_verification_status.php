<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * "Under Verification" was what a service showed once every mandatory document was verified,
     * which reads wrongly. The status is now "Documents Verified".
     */
    public function up(): void
    {
        DB::table('tickets')->where('status', 'Under Verification')->update(['status' => 'Documents Verified']);
    }

    public function down(): void
    {
        DB::table('tickets')->where('status', 'Documents Verified')->update(['status' => 'Under Verification']);
    }
};
