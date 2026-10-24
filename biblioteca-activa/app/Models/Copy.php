<?php

namespace App\Models;

use App\CopyStatus;
use Database\Factories\CopyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['book_id', 'barcode', 'location', 'condition', 'status', 'active'])]
class Copy extends Model
{
    /** @use HasFactory<CopyFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'status' => CopyStatus::class,
            'active' => 'boolean',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
}
