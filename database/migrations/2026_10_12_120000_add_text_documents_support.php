<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_documents', function (Blueprint $table) {
            $table->string('input_type', 10)->default('file')->after('instructions');
        });

        Schema::table('ticket_documents', function (Blueprint $table) {
            $table->string('file_path')->nullable()->change();
            $table->string('original_filename')->nullable()->change();
            $table->text('text_value')->nullable()->after('original_filename');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_documents', function (Blueprint $table) {
            $table->dropColumn('text_value');
        });

        Schema::table('service_documents', function (Blueprint $table) {
            $table->dropColumn('input_type');
        });
    }
};
