<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('mobile')->nullable()->unique();
            $table->string('gender')->nullable();
            $table->foreignId('designation_id')->nullable()->constrained('employee_designations')->nullOnDelete();
            $table->string('photo_path')->nullable();
            $table->timestamps();
        });

        $this->moveUserFieldsIntoEmployees();

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['mobile']);
            $table->dropConstrainedForeignId('designation_id');
            $table->dropColumn(['mobile', 'gender', 'photo_path']);
        });
    }

    private function moveUserFieldsIntoEmployees(): void
    {
        DB::table('users')
            ->where('role', 2) // UserRole::Employee
            ->orderBy('id')
            ->chunk(100, function ($users) {
                foreach ($users as $user) {
                    DB::table('employees')->insert([
                        'user_id' => $user->id,
                        'mobile' => $user->mobile,
                        'gender' => $user->gender,
                        'designation_id' => $user->designation_id,
                        'photo_path' => $user->photo_path,
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mobile')->nullable()->unique()->after('email');
            $table->string('gender')->nullable()->after('mobile');
            $table->foreignId('designation_id')->nullable()->after('gender')->constrained('employee_designations')->nullOnDelete();
            $table->string('photo_path')->nullable()->after('designation_id');
        });

        foreach (DB::table('employees')->get() as $employee) {
            DB::table('users')->where('id', $employee->user_id)->update([
                'mobile' => $employee->mobile,
                'gender' => $employee->gender,
                'designation_id' => $employee->designation_id,
                'photo_path' => $employee->photo_path,
            ]);
        }

        Schema::dropIfExists('employees');
    }
};
