<?php

namespace Tests\Feature;

use App\Actions\CreateLoan;
use App\Actions\ReturnLoan;
use App\CopyStatus;
use App\LoanStatus;
use App\Models\Book;
use App\Models\Copy;
use App\Models\Reservation;
use App\Models\User;
use App\ReservationStatus;
use Livewire\Livewire;
use Tests\TestCase;

class CirculationTest extends TestCase
{
    public function test_librarian_can_find_a_book_by_name_and_select_an_available_copy(): void
    {
        $book = Book::factory()->create([
            'title' => 'Álgebra lineal aplicada',
            'search_text' => 'algebra lineal aplicada',
            'available_copies' => 1,
        ]);
        $copy = Copy::factory()->create([
            'book_id' => (string) $book->getKey(),
            'barcode' => 'BA-ALGEBRA-001',
        ]);
        $librarian = User::factory()->librarian()->create();

        Livewire::actingAs($librarian)
            ->test('pages::circulation')
            ->set('bookSearch', 'algebra')
            ->assertSee('Álgebra lineal aplicada')
            ->call('selectBook', (string) $book->getKey())
            ->assertSet('selectedBookTitle', 'Álgebra lineal aplicada')
            ->assertSet('barcode', $copy->barcode);
    }

    public function test_librarian_can_prepare_and_fulfill_a_student_reservation(): void
    {
        $book = Book::factory()->create([
            'title' => 'Historia de México',
            'available_copies' => 1,
        ]);
        $copy = Copy::factory()->create([
            'book_id' => (string) $book->getKey(),
            'barcode' => 'BA-HISTORIA-001',
        ]);
        $student = User::factory()->create(['student_code' => 'EST-RESERVA']);
        $librarian = User::factory()->librarian()->create();
        $reservation = Reservation::factory()->create([
            'book_id' => (string) $book->getKey(),
            'user_id' => (string) $student->getKey(),
            'book_title' => $book->title,
            'student_name' => $student->name,
            'student_code' => $student->student_code,
            'status' => ReservationStatus::Pending,
        ]);

        Livewire::actingAs($librarian)
            ->test('pages::circulation')
            ->assertSee('Historia de México')
            ->assertSee('EST-RESERVA')
            ->assertSee('1 disponibles')
            ->assertSee('reservation-qr-', false)
            ->call('prepareReservation', (string) $reservation->getKey())
            ->assertSet('barcode', $copy->barcode)
            ->assertSet('studentCode', 'EST-RESERVA')
            ->call('createLoan')
            ->assertHasNoErrors();

        $this->assertSame(ReservationStatus::Fulfilled, $reservation->refresh()->status);
        $this->assertSame(CopyStatus::Loaned, $copy->refresh()->status);
    }

    public function test_librarian_can_create_and_return_a_loan(): void
    {
        $book = Book::factory()->create();
        $copy = Copy::factory()->create(['book_id' => (string) $book->getKey(), 'barcode' => 'BA-TEST-001']);
        $student = User::factory()->create();
        $librarian = User::factory()->librarian()->create();

        $loan = app(CreateLoan::class)->handle($copy, $student, $librarian);

        $this->assertSame(LoanStatus::Active, $loan->status);
        $this->assertSame(CopyStatus::Loaned, $copy->refresh()->status);

        $returned = app(ReturnLoan::class)->handle($loan, $librarian);

        $this->assertSame(LoanStatus::Returned, $returned->status);
        $this->assertSame(CopyStatus::Available, $copy->refresh()->status);
    }

    public function test_librarian_and_admin_can_delete_active_loans(): void
    {
        foreach ([User::factory()->librarian()->create(), User::factory()->admin()->create()] as $operator) {
            $book = Book::factory()->create(['available_copies' => 0]);
            $copy = Copy::factory()->create([
                'book_id' => (string) $book->getKey(),
                'status' => CopyStatus::Loaned,
            ]);
            $student = User::factory()->create();
            $loan = Loan::factory()->create([
                'copy_id' => (string) $copy->getKey(),
                'book_id' => (string) $book->getKey(),
                'user_id' => (string) $student->getKey(),
                'operator_id' => (string) $operator->getKey(),
                'copy_barcode' => $copy->barcode,
            ]);

            Livewire::actingAs($operator)
                ->test('pages::circulation')
                ->assertSee('Devolver')
                ->call('deleteLoan', (string) $loan->getKey())
                ->assertHasNoErrors();

            $this->assertFalse(Loan::query()->whereKey($loan->getKey())->exists());
            $this->assertSame(CopyStatus::Available, $copy->refresh()->status);
            $this->assertSame(1, $book->refresh()->available_copies);
        }
    }
}
