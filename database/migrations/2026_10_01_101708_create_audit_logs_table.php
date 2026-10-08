<?php

use App\Models\Service;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('module');
            $table->nullableMorphs('subject');
            $table->string('record_label')->nullable();
            $table->string('record_url')->nullable();
            $table->text('details')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['module', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        $this->migrateExistingServiceActivity();

        Schema::dropIfExists('service_activity_logs');
    }

    /**
     * Carry over existing per-service history so nothing is lost when the
     * dedicated service_activity_logs table folds into the unified log.
     */
    private function migrateExistingServiceActivity(): void
    {
        if (! Schema::hasTable('service_activity_logs')) {
            return;
        }

        DB::table('service_activity_logs')->orderBy('id')->chunk(100, function ($rows) {
            foreach ($rows as $row) {
                $service = DB::table('services')->find($row->service_id);

                DB::table('audit_logs')->insert([
                    'user_id' => $row->user_id,
                    'action' => $this->deriveAction($row->description),
                    'module' => 'Service',
                    'subject_type' => Service::class,
                    'subject_id' => $row->service_id,
                    'record_label' => $service->name ?? null,
                    'record_url' => $service ? '/services/'.$service->id : null,
                    'details' => $row->description,
                    'created_at' => $row->created_at,
                ]);
            }
        });
    }

    private function deriveAction(string $description): string
    {
        return match (true) {
            str_contains($description, 'Service created') => 'Created',
            str_contains($description, 'Status changed to Active') => 'Activated',
            str_contains($description, 'Status changed to Inactive') => 'Deactivated',
            default => 'Updated',
        };
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('service_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('description');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::dropIfExists('audit_logs');
    }
};
