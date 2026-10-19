<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('firm_name', 120)->nullable()->after('enquiry_prefix');
            $table->text('firm_address')->nullable()->after('firm_name');
            $table->string('firm_logo_path')->nullable()->after('firm_address');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['firm_name', 'firm_address', 'firm_logo_path']);
        });
    }
};
