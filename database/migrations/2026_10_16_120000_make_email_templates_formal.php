<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Formal wording for the built-in emails. A template is only replaced while it still
     * has the original wording, so anything an admin has already edited is left alone.
     *
     * @return array<string, array{old_subject: string, old_body: string, subject: string, body: string}>
     */
    private function templates(): array
    {
        return [
            'employee_welcome' => [
                'old_subject' => 'Welcome to {{firm_name}} — set up your account',
                'old_body' => "Hi {{employee_name}},\n\nAn account has been created for you on {{firm_name}}. Open the link below to set your password and sign in.\n\n{{set_password_url}}\n\nThis link will expire in 7 days. If you weren't expecting this email, you can ignore it.",
                'subject' => 'Your {{firm_name}} account: please set your password',
                'body' => "Dear {{employee_name}},\n\nAn account has been created for you at {{firm_name}}. To activate it, please use the link below to set your password and sign in.\n\n{{set_password_url}}\n\nFor security reasons, this link will expire in 7 days. If you were not expecting this email, please disregard it.\n\nYours sincerely,\n{{firm_name}}",
            ],
            'customer_otp' => [
                'old_subject' => 'Your one-time login code',
                'old_body' => "Hello {{customer_name}},\n\nYour one-time login code is {{otp_code}}. It expires in 10 minutes.\n\nRegards,\n{{firm_name}}",
                'subject' => 'Your one-time login code',
                'body' => "Dear {{customer_name}},\n\nYour one-time login code is {{otp_code}}. This code is valid for 10 minutes. Please do not share it with anyone.\n\nYours sincerely,\n{{firm_name}}",
            ],
            'service_reassigned' => [
                'old_subject' => 'A service has been reassigned to you',
                'old_body' => "Hello {{employee_name}},\n\nService {{service_number}} ({{service_name}}) has been assigned to you.\n\nRegards,\n{{firm_name}}",
                'subject' => 'A service has been assigned to you',
                'body' => "Dear {{employee_name}},\n\nPlease be informed that service {{service_number}} ({{service_name}}) has been assigned to you. Kindly review it at your earliest convenience.\n\nYours sincerely,\n{{firm_name}}",
            ],
            'service_completed' => [
                'old_subject' => 'Your service has been marked completed',
                'old_body' => "Hello {{customer_name}},\n\nYour service {{service_number}} ({{service_name}}) has been marked completed.\n\nRegards,\n{{firm_name}}",
                'subject' => 'Your service has been completed',
                'body' => "Dear {{customer_name}},\n\nWe are pleased to inform you that your service {{service_number}} ({{service_name}}) has been completed. Should you have any questions, please do not hesitate to contact us.\n\nYours sincerely,\n{{firm_name}}",
            ],
            'payment_received' => [
                'old_subject' => 'Payment received — thank you',
                'old_body' => "Hello {{customer_name}},\n\nWe have received your payment of {{amount}} against enquiry {{enquiry_number}}. Thank you.\n\nRegards,\n{{firm_name}}",
                'subject' => 'Acknowledgement of your payment',
                'body' => "Dear {{customer_name}},\n\nWe acknowledge with thanks the receipt of your payment of {{amount}} against enquiry {{enquiry_number}}.\n\nYours sincerely,\n{{firm_name}}",
            ],
        ];
    }

    public function up(): void
    {
        foreach ($this->templates() as $key => $t) {
            DB::table('email_templates')
                ->where('key', $key)
                ->where('subject', $t['old_subject'])
                ->where('body', $t['old_body'])
                ->update(['subject' => $t['subject'], 'body' => $t['body'], 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        foreach ($this->templates() as $key => $t) {
            DB::table('email_templates')
                ->where('key', $key)
                ->where('subject', $t['subject'])
                ->where('body', $t['body'])
                ->update(['subject' => $t['old_subject'], 'body' => $t['old_body'], 'updated_at' => now()]);
        }
    }
};
