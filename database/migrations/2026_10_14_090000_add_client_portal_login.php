<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('password')->nullable()->after('email');
            $table->rememberToken()->after('password');
            $table->timestamp('last_login_at')->nullable()->after('portal_status');
        });

        // One-time codes for the client portal (first login and forgot password).
        // Only a hash of the code is stored, never the code itself.
        Schema::create('otps', function (Blueprint $table) {
            $table->id();
            $table->morphs('owner');
            $table->string('purpose', 20);
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamps();

            $table->index(['owner_type', 'owner_id', 'purpose']);
        });

        Schema::table('ticket_documents', function (Blueprint $table) {
            $table->foreignId('uploaded_by_customer_id')->nullable()->after('uploaded_by')->constrained('customers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ticket_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('uploaded_by_customer_id');
        });

        Schema::dropIfExists('otps');

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['password', 'remember_token', 'last_login_at']);
        });
    }
};
