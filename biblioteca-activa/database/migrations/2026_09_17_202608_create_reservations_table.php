<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table): void {
            $table->string('id', 36)->primary();
            $table->string('book_id', 36);
            $table->string('user_id', 36);
            $table->string('status', 32);
            $table->timestamp('reserved_at');
            $table->timestamp('expires_at')->nullable();
            $table->text('book_title');
            $table->text('student_name');
            $table->string('student_code', 32)->nullable();
            $table->timestamps();

            $table->foreign('book_id')->references('id')->on('books');
            $table->foreign('user_id')->references('id')->on('users');
            $table->index(['user_id', 'status']);
            $table->index(['book_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
