<?php

namespace App\Actions;

use App\CopyStatus;
use App\LoanStatus;
use App\Models\AuditLog;
use App\Models\Book;
use App\Models\Copy;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ReturnLoan
{
    public function handle(Loan $loan, User $operator): Loan
    {
        if ($loan->status !== LoanStatus::Active && $loan->status !== LoanStatus::Overdue) {
            throw ValidationException::withMessages([
                'returnBarcode' => 'Este préstamo ya fue cerrado.',
            ]);
        }

        $loan->update([
            'status' => LoanStatus::Returned,
            'returned_at' => now(),
        ]);

        Copy::query()->whereKey($loan->copy_id)->update(['status' => CopyStatus::Available->value]);
        Book::query()->whereKey($loan->book_id)->increment('available_copies');
        AuditLog::query()->create([
            'actor_id' => (string) $operator->getKey(),
            'action' => 'loan.returned',
            'entity_type' => Loan::class,
            'entity_id' => (string) $loan->getKey(),
            'summary' => "Devolución de {$loan->copy_barcode}",
        ]);

        return $loan->refresh();
    }
}
