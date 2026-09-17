<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $collection): void {
            $collection->unique('email');
            $collection->index('student_code');
            $collection->index(['role' => 1, 'status' => 1]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
