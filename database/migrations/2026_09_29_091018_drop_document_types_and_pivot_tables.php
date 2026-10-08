<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The shared Document Types master + pivot are being replaced by a
     * `service_documents` table owned directly by each service (see
     * create_service_documents_table).
     */
    public function up(): void
    {
        Schema::dropIfExists('service_document_types');
        Schema::dropIfExists('document_types');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('service_document_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_mandatory')->default(true);
            $table->timestamps();

            $table->unique(['service_id', 'document_type_id']);
        });
    }
};
