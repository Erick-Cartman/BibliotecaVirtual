<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $collection): void {
            $collection->index(['user_id' => 1, 'status' => 1]);
            $collection->index(['copy_id' => 1, 'status' => 1]);
            $collection->index(['status' => 1, 'due_at' => 1]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
