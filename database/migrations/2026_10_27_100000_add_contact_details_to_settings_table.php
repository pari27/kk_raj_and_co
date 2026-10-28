<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * How clients can reach the firm. Shown on the client dashboard and in the client portal's side menu.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('firm_phone', 30)->nullable()->after('firm_address');
            $table->string('firm_email', 190)->nullable()->after('firm_phone');
            $table->string('firm_hours', 120)->nullable()->after('firm_email');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['firm_phone', 'firm_email', 'firm_hours']);
        });
    }
};
