<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD_BODY = "Dear {{admin_name}},\n\nPlease be informed that {{actor_name}} has recorded a new enquiry ({{enquiry_number}}) for {{customer_name}}, with a total of {{total}}.\n\nYours sincerely,\n{{firm_name}}";

    private const NEW_BODY = "Dear {{admin_name}},\n\nPlease be informed that {{actor_name}} has recorded a new enquiry ({{enquiry_number}}) for {{customer_name}}, with a total of {{total}}.\n\nThe services in this enquiry, and who each has been assigned to, are:\n\n{{services_list}}\n\nYours sincerely,\n{{firm_name}}";

    /**
     * Admins now get ONE email when an enquiry is recorded: the enquiry and its services together.
     * The separate "services assigned" email for admins is no longer sent. An email template an admin
     * has already edited is left alone.
     */
    public function up(): void
    {
        DB::table('email_templates')
            ->where('key', 'admin_new_enquiry')
            ->where('body', self::OLD_BODY)
            ->update(['body' => self::NEW_BODY, 'updated_at' => now()]);

        DB::table('email_templates')->where('key', 'admin_services_assigned')->delete();
    }

    public function down(): void
    {
        DB::table('email_templates')
            ->where('key', 'admin_new_enquiry')
            ->where('body', self::NEW_BODY)
            ->update(['body' => self::OLD_BODY, 'updated_at' => now()]);
    }
};
