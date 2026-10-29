<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One signed-in device per account: the token of the session that currently owns
     * the account, and when that session was last seen.
     */
    public function up(): void
    {
        foreach (['users', 'customers'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('login_token', 64)->nullable();
                $table->timestamp('session_seen_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['users', 'customers'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn(['login_token', 'session_seen_at']);
            });
        }
    }
};
