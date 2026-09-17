<?php

namespace Database\Seeders;

use App\CopyStatus;
use App\Models\Book;
use App\Models\Copy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class AcervoSeeder extends Seeder
{
    public function run(): void
    {
        $records = json_decode(
            File::get(database_path('seeders/data/books.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        foreach ($records as $record) {
            $locations = $record['locations'];
            unset($record['locations']);

            $book = Book::query()->updateOrCreate(
                ['source_key' => $record['source_key']],
                [
                    ...$record,
                    'available_copies' => $record['total_copies'],
                    'active' => true,
                ],
            );

            foreach ($locations as $index => $location) {
                Copy::query()->updateOrCreate(
                    ['barcode' => sprintf('BA-%s-%03d', strtoupper(substr($record['source_key'], -8)), $index + 1)],
                    [
                        'book_id' => (string) $book->getKey(),
                        'location' => $location,
                        'condition' => 'Bueno',
                        'status' => CopyStatus::Available,
                        'active' => true,
                    ],
                );
            }
        }
    }
}
