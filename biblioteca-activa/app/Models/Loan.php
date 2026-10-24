<?php

namespace App\Models;

use App\LoanStatus;
use Database\Factories\LoanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'copy_id', 'book_id', 'user_id', 'operator_id', 'status', 'loaned_at', 'due_at',
    'returned_at', 'renewals', 'book_title', 'copy_barcode', 'user_name',
])]
class Loan extends Model
{
    /** @use HasFactory<LoanFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'status' => LoanStatus::class,
            'loaned_at' => 'datetime',
            'due_at' => 'datetime',
            'returned_at' => 'datetime',
            'renewals' => 'integer',
        ];
    }

    public function copy(): BelongsTo
    {
        return $this->belongsTo(Copy::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
