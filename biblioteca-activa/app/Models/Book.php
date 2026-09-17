<?php

namespace App\Models;

use App\CopyStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MongoDB\Laravel\Eloquent\Model;

#[Fillable([
    'title', 'authors', 'isbn', 'publisher', 'publication_year', 'dewey_code',
    'learning_unit', 'program', 'search_text', 'total_copies', 'available_copies',
    'source', 'source_key', 'active',
])]
class Book extends Model
{
    /** @use HasFactory<\Database\Factories\BookFactory> */
    use HasFactory;

    protected $connection = 'mongodb';

    protected function casts(): array
    {
        return [
            'publication_year' => 'integer',
            'total_copies' => 'integer',
            'available_copies' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function copies(): HasMany
    {
        return $this->hasMany(Copy::class);
    }

    public function availableCopies(): HasMany
    {
        return $this->hasMany(Copy::class)->where('status', CopyStatus::Available->value);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function authorLine(): string
    {
        return implode(', ', $this->authors ?? []);
    }
}
