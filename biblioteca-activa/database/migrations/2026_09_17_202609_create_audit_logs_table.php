<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->string('id', 36)->primary();
            $table->string('actor_id', 36)->nullable();
            $table->string('action', 80);
            $table->string('entity_type', 160);
            $table->string('entity_id', 36);
            $table->text('summary');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['actor_id', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
