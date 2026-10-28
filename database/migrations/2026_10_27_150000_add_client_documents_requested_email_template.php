<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The email that asks a client for the documents their new services need. Admins can edit it
     * under Settings > Email Templates; {{documents_list}} must stay in it.
     */
    public function up(): void
    {
        if (DB::table('email_templates')->where('key', 'client_documents_requested')->exists()) {
            return;
        }

        DB::table('email_templates')->insert([
            'key' => 'client_documents_requested',
            'subject' => 'Documents required for your enquiry {{enquiry_number}}',
            'body' => "Dear {{customer_name}},\n\nThank you for choosing {{firm_name}}. We have registered your enquiry {{enquiry_number}}.\n\nTo begin the work, we kindly request you to send us the following documents:\n\n{{documents_list}}\n\nYou can upload them securely through our client portal using the button below. Documents marked as optional may be sent if you have them.\n\nShould you need any assistance, please do not hesitate to contact us.\n\nYours sincerely,\n{{firm_name}}",
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('email_templates')->where('key', 'client_documents_requested')->delete();
    }
};
