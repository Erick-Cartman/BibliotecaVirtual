<?php

namespace App\Actions;

use App\CopyStatus;
use App\LoanStatus;
use App\Models\AuditLog;
use App\Models\Book;
use App\Models\Copy;
use App\Models\Loan;
use App\Models\User;
use App\UserRole;
use Illuminate\Validation\ValidationException;

class CreateLoan
{
    public function handle(Copy $copy, User $student, User $operator): Loan
    {
        if ($student->role !== UserRole::Student || ! $student->isActive()) {
            throw ValidationException::withMessages([
                'studentCode' => 'La cuenta no corresponde a un estudiante activo.',
            ]);
        }

        $updated = Copy::query()
            ->whereKey($copy->getKey())
            ->where('status', CopyStatus::Available->value)
            ->update(['status' => CopyStatus::Loaned->value]);

        if ($updated !== 1) {
            throw ValidationException::withMessages([
                'barcode' => 'El ejemplar ya no está disponible.',
            ]);
        }

        $book = Book::query()->findOrFail($copy->book_id);

        try {
            $loan = Loan::query()->create([
                'copy_id' => (string) $copy->getKey(),
                'book_id' => (string) $book->getKey(),
                'user_id' => (string) $student->getKey(),
                'operator_id' => (string) $operator->getKey(),
                'status' => LoanStatus::Active,
                'loaned_at' => now(),
                'due_at' => now()->addDays(14),
                'returned_at' => null,
                'renewals' => 0,
                'book_title' => $book->title,
                'copy_barcode' => $copy->barcode,
                'user_name' => $student->name,
            ]);

            Book::query()->whereKey($book->getKey())->decrement('available_copies');
            AuditLog::query()->create([
                'actor_id' => (string) $operator->getKey(),
                'action' => 'loan.created',
                'entity_type' => Loan::class,
                'entity_id' => (string) $loan->getKey(),
                'summary' => "Préstamo de {$copy->barcode} a {$student->student_code}",
            ]);

            return $loan;
        } catch (\Throwable $exception) {
            Copy::query()->whereKey($copy->getKey())->update(['status' => CopyStatus::Available->value]);

            throw $exception;
        }
    }
}
