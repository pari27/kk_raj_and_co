<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tickets are now called services. Bring the audit trail and in-app
     * notifications along: module names, numbers (TKT- to SRV-) and the links
     * that pointed at the old /tickets and /customers URLs.
     */
    public function up(): void
    {
        // The master list of services keeps its own name so the two don't mix.
        DB::table('audit_logs')->where('module', 'Service')->update(['module' => 'Master Service']);
        DB::table('audit_logs')->where('module', 'Ticket')->update(['module' => 'Service']);

        DB::table('audit_logs')->update([
            'record_label' => DB::raw("REPLACE(record_label, 'TKT-', 'SRV-')"),
            'details' => DB::raw("REPLACE(details, 'TKT-', 'SRV-')"),
            'record_url' => DB::raw("REPLACE(REPLACE(record_url, '/tickets/', '/services/'), '/customers/', '/clients/')"),
        ]);

        DB::table('app_notifications')->update([
            'body' => DB::raw("REPLACE(body, 'TKT-', 'SRV-')"),
            'link_text' => DB::raw("REPLACE(link_text, 'TKT-', 'SRV-')"),
        ]);
    }

    public function down(): void
    {
        DB::table('audit_logs')->where('module', 'Service')->update(['module' => 'Ticket']);
        DB::table('audit_logs')->where('module', 'Master Service')->update(['module' => 'Service']);

        DB::table('audit_logs')->update([
            'record_label' => DB::raw("REPLACE(record_label, 'SRV-', 'TKT-')"),
            'details' => DB::raw("REPLACE(details, 'SRV-', 'TKT-')"),
            'record_url' => DB::raw("REPLACE(REPLACE(record_url, '/services/', '/tickets/'), '/clients/', '/customers/')"),
        ]);

        DB::table('app_notifications')->update([
            'body' => DB::raw("REPLACE(body, 'SRV-', 'TKT-')"),
            'link_text' => DB::raw("REPLACE(link_text, 'SRV-', 'TKT-')"),
        ]);
    }
};
