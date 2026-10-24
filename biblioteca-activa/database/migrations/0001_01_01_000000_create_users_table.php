<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->string('id', 36)->primary();
            $table->text('name');
            $table->text('first_name')->nullable();
            $table->text('last_name')->nullable();
            $table->string('email', 160)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->text('avatar_path')->nullable();
            $table->string('student_code', 32)->nullable()->index();
            $table->string('role', 32);
            $table->string('status', 32);
            $table->rememberToken();
            $table->timestamps();

            $table->index(['role', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
