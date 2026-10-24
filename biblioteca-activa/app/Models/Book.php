<?php

namespace App\Models;

use App\CopyStatus;
use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title', 'authors', 'isbn', 'publisher', 'publication_year', 'dewey_code',
    'learning_unit', 'program', 'search_text', 'total_copies', 'available_copies',
    'cover_url', 'source', 'source_key', 'active',
])]
class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'authors' => 'array',
            'source' => 'array',
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

    public function coverUrl(): ?string
    {
        if (filled($this->cover_url)) {
            return $this->cover_url;
        }

        $isbn = preg_replace('/[^0-9X]/i', '', (string) $this->isbn);

        if (! in_array(strlen($isbn), [10, 13], true)) {
            return null;
        }

        return 'https://covers.openlibrary.org/b/isbn/'.rawurlencode(strtoupper($isbn)).'-L.jpg?default=false';
    }
}
