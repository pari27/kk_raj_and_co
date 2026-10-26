<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Errors the software team has marked as resolved, so they stop showing on the System page.
     * The log file itself is never changed.
     */
    public function up(): void
    {
        Schema::create('dismissed_errors', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint', 64)->unique();
            $table->string('message', 300)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dismissed_errors');
    }
};
