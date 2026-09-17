<?php

namespace Database\Factories;

use App\CopyStatus;
use App\Models\Book;
use App\Models\Copy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Copy> */
class CopyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'book_id' => Book::factory(),
            'barcode' => 'BA-'.Str::upper(Str::random(10)),
            'location' => 'Por asignar',
            'condition' => 'Bueno',
            'status' => CopyStatus::Available,
            'active' => true,
        ];
    }
}
