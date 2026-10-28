<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The email a new client gets, telling them about the client portal. Admins can edit it
     * under Settings > Email Templates.
     */
    public function up(): void
    {
        if (DB::table('email_templates')->where('key', 'client_welcome')->exists()) {
            return;
        }

        DB::table('email_templates')->insert([
            'key' => 'client_welcome',
            'subject' => 'Welcome to {{firm_name}}',
            'body' => "Dear {{customer_name}},\n\nWelcome to {{firm_name}}. We have created a client account for you.\n\nThrough our client portal you can follow the progress of your services, upload your documents and see your payments.\n\nTo sign in for the first time, please use the button below and enter this email address. We will send a one-time code to it, which you then use to set your own password.\n\nShould you need any assistance, please do not hesitate to contact us.\n\nYours sincerely,\n{{firm_name}}",
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('email_templates')->where('key', 'client_welcome')->delete();
    }
};
