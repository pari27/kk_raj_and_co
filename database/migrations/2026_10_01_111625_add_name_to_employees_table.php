<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
        });

        foreach (DB::table('users')->whereNotNull('employee_id')->get(['id', 'name', 'employee_id']) as $user) {
            DB::table('employees')->where('id', $user->employee_id)->update(['name' => $user->name]);
        }
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
