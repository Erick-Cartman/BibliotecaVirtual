<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('copies', function (Blueprint $table): void {
            $table->string('id', 36)->primary();
            $table->string('book_id', 36);
            $table->string('barcode', 64)->unique();
            $table->text('location')->nullable();
            $table->text('condition')->nullable();
            $table->string('status', 32);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('book_id')->references('id')->on('books');
            $table->index(['book_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('copies');
    }
};
