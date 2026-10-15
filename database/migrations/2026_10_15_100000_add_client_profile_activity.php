<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Shown on the client's Profile page under "Security".
        Schema::table('customers', function (Blueprint $table) {
            $table->timestamp('password_changed_at')->nullable()->after('last_login_at');
            $table->string('last_login_device', 120)->nullable()->after('password_changed_at');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['password_changed_at', 'last_login_device']);
        });
    }
};
