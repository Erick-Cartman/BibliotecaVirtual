<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $collection): void {
            $collection->index(['actor_id' => 1, 'created_at' => -1]);
            $collection->index(['entity_type' => 1, 'entity_id' => 1]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
