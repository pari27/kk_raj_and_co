<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * How clients can pay the firm. Shown to clients in the "How to pay" window of the client portal.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('payment_upi_id', 100)->nullable()->after('firm_logo_path');
            $table->string('payment_bank_account_name', 120)->nullable()->after('payment_upi_id');
            $table->string('payment_bank_account_number', 40)->nullable()->after('payment_bank_account_name');
            $table->string('payment_bank_ifsc', 20)->nullable()->after('payment_bank_account_number');
            $table->string('payment_bank_branch', 160)->nullable()->after('payment_bank_ifsc');
            $table->text('payment_instructions')->nullable()->after('payment_bank_branch');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'payment_upi_id',
                'payment_bank_account_name',
                'payment_bank_account_number',
                'payment_bank_ifsc',
                'payment_bank_branch',
                'payment_instructions',
            ]);
        });
    }
};
