<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * There is now a single Admin role (stored as 0, the old Super Admin).
     * Anyone who had the old Admin role (1) becomes that Admin: they already had the same access.
     */
    public function up(): void
    {
        DB::table('users')->where('role', 1)->update(['role' => 0]);

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('role')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('role')->default(1)->change();
        });
    }
};
