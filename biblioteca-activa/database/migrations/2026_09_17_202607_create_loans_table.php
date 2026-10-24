<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table): void {
            $table->string('id', 36)->primary();
            $table->string('copy_id', 36);
            $table->string('book_id', 36);
            $table->string('user_id', 36);
            $table->string('operator_id', 36)->nullable();
            $table->string('status', 32);
            $table->timestamp('loaned_at');
            $table->timestamp('due_at');
            $table->timestamp('returned_at')->nullable();
            $table->unsignedInteger('renewals')->default(0);
            $table->text('book_title');
            $table->string('copy_barcode', 64);
            $table->text('user_name');
            $table->timestamps();

            $table->foreign('copy_id')->references('id')->on('copies');
            $table->foreign('book_id')->references('id')->on('books');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('operator_id')->references('id')->on('users');
            $table->index(['user_id', 'status']);
            $table->index(['copy_id', 'status']);
            $table->index(['status', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
