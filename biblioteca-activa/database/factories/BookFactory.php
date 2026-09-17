<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Book> */
class BookFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'source_key' => 'test-'.Str::uuid(),
            'title' => $title,
            'authors' => [fake()->name()],
            'isbn' => fake()->isbn13(),
            'publisher' => fake()->company(),
            'publication_year' => fake()->numberBetween(1980, 2026),
            'dewey_code' => (string) fake()->randomFloat(2, 0, 999),
            'learning_unit' => 'Formación académica',
            'program' => 'General',
            'search_text' => Str::lower($title),
            'total_copies' => 1,
            'available_copies' => 1,
            'source' => ['type' => 'test'],
            'active' => true,
        ];
    }
}
