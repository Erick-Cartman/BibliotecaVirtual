<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $collection): void {
            $collection->unique('source_key');
            $collection->index('isbn');
            $collection->index('program');
            $collection->index('active');
            $collection->index('title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
