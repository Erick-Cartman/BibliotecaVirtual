<?php

namespace Database\Seeders;

use App\CopyStatus;
use App\Models\Book;
use App\Models\Copy;
use Illuminate\Database\Seeder;

class DemoFeaturedBookSeeder extends Seeder
{
    public function run(): void
    {
        $book = Book::query()->updateOrCreate(
            ['source_key' => 'demo-el-principito'],
            [
                'title' => 'El Principito',
                'authors' => ['Antoine de Saint-Exupery'],
                'isbn' => '9780156012195',
                'publisher' => 'Mariner Books',
                'publication_year' => 2000,
                'dewey_code' => '843',
                'learning_unit' => 'Literatura',
                'program' => 'General',
                'search_text' => 'el principito antoine de saint exupery 9780156012195 literatura',
                'total_copies' => 1,
                'available_copies' => 1,
                'cover_url' => 'https://covers.openlibrary.org/b/isbn/9780156012195-L.jpg?default=false',
                'source' => ['type' => 'demo'],
                'active' => true,
            ],
        );

        Copy::query()->updateOrCreate(
            ['barcode' => 'BA-DEMO-PRINCIPITO-001'],
            [
                'book_id' => (string) $book->getKey(),
                'location' => 'Demo',
                'condition' => 'Bueno',
                'status' => CopyStatus::Available,
                'active' => true,
            ],
        );
    }
}
