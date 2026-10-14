<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('subject');
            $table->text('body');
            $table->timestamps();
        });

        $now = now();

        DB::table('email_templates')->insert([
            [
                'key' => 'employee_welcome',
                'subject' => 'Welcome to {{firm_name}} — set up your account',
                'body' => "Hi {{employee_name}},\n\nAn account has been created for you on {{firm_name}}. Open the link below to set your password and sign in.\n\n{{set_password_url}}\n\nThis link will expire in 7 days. If you weren't expecting this email, you can ignore it.",
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'customer_otp',
                'subject' => 'Your one-time login code',
                'body' => "Hello {{customer_name}},\n\nYour one-time login code is {{otp_code}}. It expires in 10 minutes.\n\nRegards,\n{{firm_name}}",
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'service_reassigned',
                'subject' => 'A service has been reassigned to you',
                'body' => "Hello {{employee_name}},\n\nService {{service_number}} ({{service_name}}) has been assigned to you.\n\nRegards,\n{{firm_name}}",
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'service_completed',
                'subject' => 'Your service has been marked completed',
                'body' => "Hello {{customer_name}},\n\nYour service {{service_number}} ({{service_name}}) has been marked completed.\n\nRegards,\n{{firm_name}}",
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'payment_received',
                'subject' => 'Payment received — thank you',
                'body' => "Hello {{customer_name}},\n\nWe have received your payment of {{amount}} against enquiry {{enquiry_number}}. Thank you.\n\nRegards,\n{{firm_name}}",
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
