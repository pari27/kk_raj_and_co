<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_documents', function (Blueprint $table) {
            $table->renameColumn('description', 'instructions');
        });

        Schema::table('service_documents', function (Blueprint $table) {
            $table->string('allowed_formats')->nullable()->after('instructions');
            $table->unsignedInteger('max_file_size_kb')->nullable()->after('allowed_formats');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_documents', function (Blueprint $table) {
            $table->dropColumn(['allowed_formats', 'max_file_size_kb']);
        });

        Schema::table('service_documents', function (Blueprint $table) {
            $table->renameColumn('instructions', 'description');
        });
    }
};
