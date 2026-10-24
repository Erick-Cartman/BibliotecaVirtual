<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table): void {
            $table->string('id', 36)->primary();
            $table->string('source_key', 160)->unique();
            $table->text('title');
            $table->jsonb('authors');
            $table->text('isbn')->nullable();
            $table->text('publisher')->nullable();
            $table->unsignedSmallInteger('publication_year')->nullable();
            $table->text('dewey_code')->nullable();
            $table->text('learning_unit')->nullable();
            $table->text('program');
            $table->text('search_text')->nullable();
            $table->unsignedInteger('total_copies')->default(0);
            $table->unsignedInteger('available_copies')->default(0);
            $table->text('cover_url')->nullable();
            $table->jsonb('source')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('isbn');
            $table->index('program');
            $table->index('active');
            $table->index('title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
