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
        Schema::table('users', function (Blueprint $table) {
            $table->string('mobile')->nullable()->unique()->after('email');
            $table->string('gender')->nullable()->after('mobile');
            $table->foreignId('designation_id')->nullable()->after('gender')->constrained('employee_designations')->nullOnDelete();
            $table->string('photo_path')->nullable()->after('designation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('designation_id');
            $table->dropColumn(['mobile', 'gender', 'photo_path']);
        });
    }
};
