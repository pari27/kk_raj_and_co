<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->unique()->after('role')->constrained('employees')->nullOnDelete();
        });

        foreach (DB::table('employees')->get() as $employee) {
            DB::table('users')->where('id', $employee->user_id)->update([
                'employee_id' => $employee->id,
            ]);
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
            $table->dropConstrainedForeignId('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->cascadeOnDelete();
        });

        foreach (DB::table('users')->whereNotNull('employee_id')->get() as $user) {
            DB::table('employees')->where('id', $user->employee_id)->update([
                'user_id' => $user->id,
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_id');
        });
    }
};
