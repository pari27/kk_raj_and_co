<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * When several services of one enquiry go to the same employee, they get one email listing them all;
     * admins get one email for the whole enquiry. Admins can edit both under Settings > Email Templates.
     *
     * @return array<string, array{subject: string, body: string}>
     */
    private function templates(): array
    {
        return [
            'services_assigned' => [
                'subject' => 'Services have been assigned to you',
                'body' => "Dear {{employee_name}},\n\nPlease be informed that the following services have been assigned to you:\n\n{{services_list}}\n\nKindly review them at your earliest convenience.\n\nYours sincerely,\n{{firm_name}}",
            ],
            'admin_services_assigned' => [
                'subject' => 'Services assigned for {{customer_name}}',
                'body' => "Dear {{admin_name}},\n\nPlease be informed that {{actor_name}} has assigned the following services for {{customer_name}}:\n\n{{services_list}}\n\nYours sincerely,\n{{firm_name}}",
            ],
        ];
    }

    public function up(): void
    {
        foreach ($this->templates() as $key => $template) {
            if (DB::table('email_templates')->where('key', $key)->exists()) {
                continue;
            }

            DB::table('email_templates')->insert($template + ['key' => $key, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('email_templates')->whereIn('key', array_keys($this->templates()))->delete();
    }
};
