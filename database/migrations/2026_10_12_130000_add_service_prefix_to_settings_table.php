<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('service_prefix', 10)->default('SRV')->after('notifications_enabled');
        });

        // Tickets are now called services: carry the existing numbers over to the new prefix.
        DB::table('tickets')
            ->where('number', 'like', 'TKT-%')
            ->orderBy('id')
            ->each(function ($ticket) {
                DB::table('tickets')
                    ->where('id', $ticket->id)
                    ->update(['number' => 'SRV'.substr($ticket->number, 3)]);
            });
    }

    public function down(): void
    {
        DB::table('tickets')
            ->where('number', 'like', 'SRV-%')
            ->orderBy('id')
            ->each(function ($ticket) {
                DB::table('tickets')
                    ->where('id', $ticket->id)
                    ->update(['number' => 'TKT'.substr($ticket->number, 3)]);
            });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('service_prefix');
        });
    }
};
