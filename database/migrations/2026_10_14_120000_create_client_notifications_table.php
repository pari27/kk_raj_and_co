<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The "Updates" a client sees in the portal. Staff have their own (app_notifications).
        Schema::create('client_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('url')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_notifications');
    }
};
