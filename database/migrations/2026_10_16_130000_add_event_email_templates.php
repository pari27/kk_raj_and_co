<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Formal wording for the emails sent when something happens: to admins, employees and clients.
     * Admins can edit any of these under Settings > Email Templates.
     *
     * @return array<string, array{subject: string, body: string}>
     */
    private function templates(): array
    {
        return [
            'admin_new_enquiry' => [
                'subject' => 'New enquiry recorded: {{enquiry_number}}',
                'body' => "Dear {{admin_name}},\n\nPlease be informed that {{actor_name}} has recorded a new enquiry ({{enquiry_number}}) for {{customer_name}}, with a total of {{total}}.\n\nYours sincerely,\n{{firm_name}}",
            ],
            'admin_payment_received' => [
                'subject' => 'Payment received: {{amount}}',
                'body' => "Dear {{admin_name}},\n\nPlease be informed that a payment of {{amount}} ({{mode}}) has been received from {{customer_name}} against enquiry {{enquiry_number}}.\n\nYours sincerely,\n{{firm_name}}",
            ],
            'admin_payment_deleted' => [
                'subject' => 'A recorded payment has been deleted',
                'body' => "Dear {{admin_name}},\n\nPlease be informed that {{actor_name}} has deleted a recorded payment of {{amount}} ({{mode}}) from {{customer_name}} against enquiry {{enquiry_number}}. Kindly review it if this was not expected.\n\nYours sincerely,\n{{firm_name}}",
            ],
            'admin_service_assigned' => [
                'subject' => 'Service {{service_number}} has been assigned',
                'body' => "Dear {{admin_name}},\n\nPlease be informed that {{actor_name}} has assigned service {{service_number}} ({{service_name}}) for {{customer_name}} to {{employee_name}}.\n\nYours sincerely,\n{{firm_name}}",
            ],
            'admin_client_added' => [
                'subject' => 'New client added: {{customer_name}}',
                'body' => "Dear {{admin_name}},\n\nPlease be informed that {{actor_name}} has added a new client, {{customer_name}}, to the system.\n\nYours sincerely,\n{{firm_name}}",
            ],
            'employee_document_uploaded' => [
                'subject' => 'A document has been uploaded for service {{service_number}}',
                'body' => "Dear {{employee_name}},\n\nPlease be informed that the document \"{{document_name}}\" has been uploaded for service {{service_number}} ({{service_name}}) for {{customer_name}}. Kindly review it at your earliest convenience.\n\nYours sincerely,\n{{firm_name}}",
            ],
            'client_document_rejected' => [
                'subject' => 'Action required: please re-upload your document',
                'body' => "Dear {{customer_name}},\n\nWe have reviewed the document \"{{document_name}}\" submitted for {{service_name}} and regret that we are unable to accept it, for the following reason:\n\n{{reason}}\n\nKindly upload a corrected copy through your client portal at your earliest convenience.\n\nYours sincerely,\n{{firm_name}}",
            ],
        ];
    }

    public function up(): void
    {
        foreach ($this->templates() as $key => $template) {
            if (DB::table('email_templates')->where('key', $key)->exists()) {
                continue;
            }

            DB::table('email_templates')->insert([
                'key' => $key,
                'subject' => $template['subject'],
                'body' => $template['body'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('email_templates')->whereIn('key', array_keys($this->templates()))->delete();
    }
};
