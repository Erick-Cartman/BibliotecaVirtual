<?php
namespace App\Actions;
use App\CopyStatus;
use App\LoanStatus;
use App\Models\AuditLog;
use App\Models\Book;
use App\Models\Copy;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
class DeleteLoan
{
    public function handle(Loan $loan, User $operator): void
    {
        if (! $operator->canManageLibrary()) {
            throw new AuthorizationException('No tienes permiso para eliminar préstamos.');
        }
        if (! in_array($loan->status, [LoanStatus::Active, LoanStatus::Overdue], true)) {
            throw ValidationException::withMessages(['loan' => 'Este préstamo ya fue cerrado.']);
        }
        $loanId = (string) $loan->getKey();
        $loan->delete();
        Copy::query()->whereKey($loan->copy_id)->update(['status' => CopyStatus::Available->value]);
        Book::query()->whereKey($loan->book_id)->increment('available_copies');
        AuditLog::query()->create([
            'actor_id' => (string) $operator->getKey(),
            'action' => 'loan.deleted',
            'entity_type' => Loan::class,
            'entity_id' => $loanId,
            'summary' => 'Eliminación del préstamo de '.$loan->copy_barcode,
        ]);
    }
}
