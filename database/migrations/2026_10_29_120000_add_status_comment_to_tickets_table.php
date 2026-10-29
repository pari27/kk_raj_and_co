<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The comment typed when a service's status is changed, and the status it was typed for,
     * so the Services list can show it after that status (and drop it when the status moves on).
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->text('status_comment')->nullable();
            $table->string('status_comment_for', 60)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['status_comment', 'status_comment_for']);
        });
    }
};
