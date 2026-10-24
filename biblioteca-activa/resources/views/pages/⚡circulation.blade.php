<?php

use App\Actions\CreateLoan;
use App\Actions\DeleteLoan;
use App\Actions\ReturnLoan;
use App\CopyStatus;
use App\LoanStatus;
use App\Models\Book;
use App\Models\Copy;
use App\Models\Loan;
use App\Models\Reservation;
use App\Models\User;
use App\ReservationStatus;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $bookSearch = '';

    public string $selectedBookTitle = '';

    public string $barcode = '';

    public string $studentCode = '';

    public string $returnBarcode = '';

    public ?string $reservationId = null;

    public function updatedBookSearch(): void
    {
        if ($this->bookSearch !== $this->selectedBookTitle) {
            $this->reset('selectedBookTitle', 'barcode', 'reservationId');
        }

        unset($this->bookResults);
    }

    #[Computed]
    public function bookResults(): Collection
    {
        $search = trim($this->bookSearch);

        if (mb_strlen($search) < 2 || $this->selectedBookTitle !== '') {
            return new Collection;
        }

        return Book::query()
            ->where('active', true)
            ->where('available_copies', '>', 0)
            ->where('search_text', 'like', '%'.addcslashes(Str::lower(Str::ascii($search)), '%_\\').'%')
            ->orderBy('title')
            ->limit(8)
            ->get();
    }

    public function selectBook(string $bookId): void
    {
        $book = Book::query()->where('active', true)->findOrFail($bookId);
        $copy = Copy::query()
            ->where('book_id', $bookId)
            ->where('status', CopyStatus::Available->value)
            ->where('active', true)
            ->orderBy('barcode')
            ->first();

        if (! $copy) {
            $this->addError('bookSearch', 'Este título ya no tiene ejemplares disponibles.');

            return;
        }

        $this->bookSearch = $book->title;
        $this->selectedBookTitle = $book->title;
        $this->barcode = $copy->barcode;
        $this->reservationId = null;
        $this->resetErrorBag('bookSearch');
        unset($this->bookResults);
    }

    public function selectBookFromQr(string $isbn, string $title): void
    {
        $isbn = trim($isbn);
        $title = trim($title);
        $this->reset('selectedBookTitle', 'barcode', 'reservationId');
        $this->resetErrorBag('bookSearch');
        unset($this->bookResults);
        $this->bookSearch = $title;

        $book = null;
        if ($isbn !== '' && mb_strtolower($isbn) !== 'no registrado') {
            $book = Book::query()
                ->where('active', true)
                ->where('isbn', $isbn)
                ->first();
        }

        $book ??= Book::query()
            ->where('active', true)
            ->where('title', $title)
            ->first();

        if (! $book) {
            $this->addError('bookSearch', 'No se encontró el libro del QR en el catálogo.');
            $this->dispatch('loan-form-ready');

            return;
        }

        $this->selectBook((string) $book->getKey());
        $this->dispatch('loan-form-ready');
    }

    public function createLoan(): void
    {
        $this->validate([
            'barcode' => ['required', 'string'],
            'studentCode' => ['required', 'string'],
        ]);

        $copy = Copy::query()->where('barcode', trim($this->barcode))->first();
        $student = User::query()->where('student_code', trim($this->studentCode))->first();

        if (! $copy) {
            $this->addError('barcode', 'No existe un ejemplar con ese código.');

            return;
        }

        if (! $student) {
            $this->addError('studentCode', 'No existe un estudiante con ese código.');

            return;
        }

        $reservation = null;
        if ($this->reservationId) {
            $reservation = Reservation::query()
                ->whereKey($this->reservationId)
                ->where('book_id', (string) $copy->book_id)
                ->where('user_id', (string) $student->getKey())
                ->where('status', ReservationStatus::Pending->value)
                ->firstOrFail();
        }

        app(CreateLoan::class)->handle($copy, $student, auth()->user());
        $reservation?->update(['status' => ReservationStatus::Fulfilled]);

        $this->reset('bookSearch', 'selectedBookTitle', 'barcode', 'studentCode', 'reservationId');
        unset($this->activeLoans, $this->pendingReservations, $this->bookResults);
        session()->flash('message', 'Préstamo registrado correctamente.');
    }

    public function prepareReservation(string $reservationId): void
    {
        $reservation = Reservation::query()
            ->whereKey($reservationId)
            ->where('status', ReservationStatus::Pending->value)
            ->firstOrFail();
        $student = User::query()->findOrFail($reservation->user_id);
        $book = Book::query()->findOrFail($reservation->book_id);
        $copy = Copy::query()
            ->where('book_id', (string) $book->getKey())
            ->where('status', CopyStatus::Available->value)
            ->where('active', true)
            ->orderBy('barcode')
            ->first();

        if (! $copy) {
            $this->addError('reservation', 'La reservación sigue en espera porque no hay ejemplares disponibles.');

            return;
        }

        $this->reservationId = (string) $reservation->getKey();
        $this->bookSearch = $book->title;
        $this->selectedBookTitle = $book->title;
        $this->barcode = $copy->barcode;
        $this->studentCode = (string) $student->student_code;
        $this->resetErrorBag();
        $this->dispatch('loan-form-ready');
    }

    public function cancelReservation(string $reservationId): void
    {
        Reservation::query()
            ->whereKey($reservationId)
            ->where('status', ReservationStatus::Pending->value)
            ->firstOrFail()
            ->update(['status' => ReservationStatus::Cancelled]);

        if ($this->reservationId === $reservationId) {
            $this->reset('bookSearch', 'selectedBookTitle', 'barcode', 'studentCode', 'reservationId');
        }

        unset($this->pendingReservations);
        session()->flash('message', 'Reservación cancelada.');
    }

    public function returnLoan(): void
    {
        $this->validate(['returnBarcode' => ['required', 'string']]);
        $loan = Loan::query()
            ->where('copy_barcode', trim($this->returnBarcode))
            ->whereIn('status', [LoanStatus::Active->value, LoanStatus::Overdue->value])
            ->latest('loaned_at')
            ->first();

        if (! $loan) {
            $this->addError('returnBarcode', 'No hay un préstamo activo con ese código.');

            return;
        }

        app(ReturnLoan::class)->handle($loan, auth()->user());
        $this->reset('returnBarcode');
        unset($this->activeLoans);
        session()->flash('message', 'Devolución registrada correctamente.');
    }

    public function deleteLoan(string $loanId): void
    {
        $loan = Loan::query()
            ->whereKey($loanId)
            ->whereIn('status', [LoanStatus::Active->value, LoanStatus::Overdue->value])
            ->firstOrFail();

        app(DeleteLoan::class)->handle($loan, auth()->user());

        unset($this->activeLoans);
        session()->flash('message', 'Préstamo devuelto correctamente.');
    }

    #[Computed]
    public function pendingReservations(): Collection
    {
        $reservations = Reservation::query()
            ->where('status', ReservationStatus::Pending->value)
            ->orderBy('reserved_at')
            ->limit(50)
            ->get();

        $users = User::query()
            ->whereIn('id', $reservations->pluck('user_id')->filter()->unique()->values()->all())
            ->get()
            ->keyBy(fn (User $user): string => (string) $user->getKey());
        $books = Book::query()
            ->whereIn('id', $reservations->pluck('book_id')->filter()->unique()->values()->all())
            ->get()
            ->keyBy(fn (Book $book): string => (string) $book->getKey());

        return $reservations->each(function (Reservation $reservation) use ($users, $books): void {
            $user = $users->get((string) $reservation->user_id);
            $book = $books->get((string) $reservation->book_id);
            $reservation->setAttribute('student_name_display', $reservation->student_name ?: $user?->name ?: 'Estudiante');
            $reservation->setAttribute('student_code_display', $reservation->student_code ?: $user?->student_code ?: 'Sin código');
            $reservation->setAttribute('student_email_display', $user?->email ?: '');
            $reservation->setAttribute('available_copies_display', (int) ($book?->available_copies ?? 0));
        });
    }

    #[Computed]
    public function activeLoans(): Collection
    {
        return Loan::query()
            ->whereIn('status', [LoanStatus::Active->value, LoanStatus::Overdue->value])
            ->orderBy('due_at')
            ->limit(30)
            ->get();
    }
};
?>

<div
    x-data
    x-on:loan-form-ready.window="$nextTick(() => { document.getElementById('new-loan')?.scrollIntoView({ behavior: 'smooth', block: 'start' }); document.getElementById('bookSearch')?.focus({ preventScroll: true }); })"
>
    <x-slot:title>Circulación · Biblioteca Activa</x-slot:title>

    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="eyebrow">Operación bibliotecaria</p>
            <h1 class="mt-2 font-display text-4xl font-bold text-ink">Circulación</h1>
            <p class="mt-2 text-slate-600">Busca el libro por nombre, selecciona un ejemplar y registra la entrega.</p>
        </div>
        @if (auth()->user()->canManageLibrary())
            <x-qr-scanner />
        @endif
    </div>

    @if (session('message'))
        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 font-medium text-emerald-800" role="status">
            {{ session('message') }}
        </div>
    @endif

    <section class="mt-7 grid gap-5 xl:grid-cols-2">
        <form id="new-loan" wire:submit="createLoan" class="card scroll-mt-6 p-6">
            <p class="eyebrow">Salida</p>
            <h2 class="mt-2 font-display text-2xl font-bold">Nuevo préstamo</h2>

            <div class="relative mt-5">
                <label class="label" for="bookSearch">Buscar libro por nombre</label>
                <input
                    id="bookSearch"
                    class="field"
                    type="search"
                    wire:model.live.debounce.350ms="bookSearch"
                    placeholder="Escribe al menos dos letras del título o autor"
                    autocomplete="off"
                    role="combobox"
                    aria-controls="book-results"
                    aria-expanded="{{ $this->bookResults->isNotEmpty() ? 'true' : 'false' }}"
                >
                <p class="mt-1.5 text-sm text-slate-500">El sistema asignará el primer ejemplar disponible.</p>
                @error('bookSearch') <p class="mt-1 text-sm font-medium text-red-700">{{ $message }}</p> @enderror

                @if ($this->bookResults->isNotEmpty())
                    <div id="book-results" class="absolute z-20 mt-2 max-h-80 w-full overflow-y-auto rounded-xl border border-slate-200 bg-card p-2 shadow-lg" role="listbox">
                        @foreach ($this->bookResults as $book)
                            <button
                                type="button"
                                class="flex min-h-14 w-full cursor-pointer items-center justify-between gap-4 rounded-lg px-3 py-3 text-left transition hover:bg-brand-50 focus:bg-brand-50"
                                wire:click="selectBook('{{ $book->getKey() }}')"
                                wire:key="loan-book-{{ $book->getKey() }}"
                                role="option"
                            >
                                <span>
                                    <span class="block font-semibold text-slate-900">{{ $book->title }}</span>
                                    <span class="mt-0.5 block text-sm text-slate-500">{{ $book->authorLine() }}</span>
                                </span>
                                <span class="shrink-0 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800">{{ $book->available_copies }} disponibles</span>
                            </button>
                        @endforeach
                    </div>
                @elseif (mb_strlen(trim($bookSearch)) >= 2 && $selectedBookTitle === '')
                    <p class="mt-2 rounded-lg bg-slate-50 p-3 text-sm text-slate-600">No hay títulos disponibles con esa búsqueda. Prueba con menos palabras.</p>
                @endif
            </div>

            @if ($selectedBookTitle !== '')
                <div class="mt-4 rounded-xl border border-brand-100 bg-brand-50 p-4" role="status">
                    <p class="text-xs font-bold uppercase tracking-wider text-brand-700">Libro seleccionado</p>
                    <p class="mt-1 font-semibold text-slate-900">{{ $selectedBookTitle }}</p>
                    <p class="mt-1 font-mono text-xs text-slate-600">Ejemplar: {{ $barcode }}</p>
                </div>
            @endif

            <details class="mt-4 rounded-xl border border-slate-200 p-4">
                <summary class="min-h-11 cursor-pointer py-2 font-semibold text-brand-700">También puedo ingresar o escanear el código</summary>
                <div class="mt-3">
                    <label class="label" for="barcode">Código del ejemplar</label>
                    <input id="barcode" class="field uppercase" wire:model="barcode" placeholder="BA-XXXXXXXX-001" autocomplete="off">
                    @error('barcode') <p class="mt-1 text-sm font-medium text-red-700">{{ $message }}</p> @enderror
                </div>
            </details>

            <div class="mt-4">
                <label class="label" for="studentCode">Código del estudiante</label>
                <input id="studentCode" class="field uppercase" wire:model="studentCode" placeholder="EST-001" autocomplete="off">
                @error('studentCode') <p class="mt-1 text-sm font-medium text-red-700">{{ $message }}</p> @enderror
            </div>

            @if ($reservationId)
                <p class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-sm font-medium text-amber-900">Este préstamo atenderá la reservación seleccionada.</p>
            @endif

            <button class="btn-primary mt-5 cursor-pointer" type="submit" wire:loading.attr="disabled" wire:target="createLoan">
                <span wire:loading.remove wire:target="createLoan">Registrar préstamo</span>
                <span wire:loading wire:target="createLoan">Registrando…</span>
            </button>
        </form>

        <form wire:submit="returnLoan" class="card p-6">
            <p class="eyebrow">Entrada</p>
            <h2 class="mt-2 font-display text-2xl font-bold">Registrar devolución</h2>
            <details class="mt-5 rounded-xl border border-slate-200 p-4">
                <summary class="min-h-11 cursor-pointer py-2 font-semibold text-brand-700">También puedo ingresar o escanear el código</summary>
                <div class="mt-3">
                    <label class="label" for="returnBarcode">Código del ejemplar</label>
                    <input id="returnBarcode" class="field uppercase" wire:model="returnBarcode" placeholder="BA-XXXXXXXX-001" autocomplete="off">
                    @error('returnBarcode') <p class="mt-1 text-sm font-medium text-red-700">{{ $message }}</p> @enderror
                </div>
            </details>
            <button class="btn-secondary mt-5 cursor-pointer" type="submit" wire:loading.attr="disabled" wire:target="returnLoan">Confirmar devolución</button>
        </form>
    </section>

    <section class="card mt-8 overflow-hidden" aria-labelledby="reservations-title">
        <div class="border-b border-slate-200 px-5 py-4 sm:flex sm:items-end sm:justify-between sm:px-6">
            <div>
                <p class="eyebrow">Bandeja de atención</p>
                <h2 id="reservations-title" class="mt-1 font-display text-2xl font-bold">Reservaciones pendientes</h2>
                <p class="mt-1 text-sm text-slate-500">Las solicitudes de estudiantes llegan aquí, en orden de registro.</p>
            </div>
            <span class="mt-3 inline-flex rounded-full bg-brand-50 px-3 py-1 text-sm font-bold text-brand-700 sm:mt-0">{{ $this->pendingReservations->count() }} pendientes</span>
        </div>

        @error('reservation')
            <p class="m-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900">{{ $message }}</p>
        @enderror

        <div class="divide-y divide-slate-100">
            @forelse ($this->pendingReservations as $reservation)
                <article class="grid gap-4 px-5 py-5 sm:px-6 lg:grid-cols-[1fr_auto_auto] lg:items-center" wire:key="reservation-{{ $reservation->getKey() }}">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-semibold text-slate-900">{{ $reservation->book_title }}</h3>
                            <span @class([
                                'rounded-full px-2.5 py-1 text-xs font-bold',
                                'bg-emerald-50 text-emerald-800' => $reservation->available_copies_display > 0,
                                'bg-amber-50 text-amber-900' => $reservation->available_copies_display === 0,
                            ])>
                                {{ $reservation->available_copies_display > 0 ? $reservation->available_copies_display.' disponibles' : 'En espera' }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-slate-600">{{ $reservation->student_name_display }} · {{ $reservation->student_code_display }}</p>
                        <p class="mt-1 text-xs text-slate-400">Solicitada {{ $reservation->reserved_at?->format('d/m/Y H:i') }}</p>
                    </div>
                    <x-reservation-qr :reservation="$reservation" />
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="btn-primary cursor-pointer px-4"
                            wire:click="prepareReservation('{{ $reservation->getKey() }}')"
                            @disabled($reservation->available_copies_display === 0)
                        >
                            Preparar préstamo
                        </button>
                        <button type="button" class="btn-secondary cursor-pointer px-4" wire:click="cancelReservation('{{ $reservation->getKey() }}')">Cancelar</button>
                    </div>
                </article>
            @empty
                <div class="px-6 py-10 text-center">
                    <p class="font-semibold text-slate-700">No hay reservaciones pendientes.</p>
                    <p class="mt-1 text-sm text-slate-500">Las nuevas solicitudes aparecerán automáticamente en esta bandeja.</p>
                </div>
            @endforelse
        </div>
    </section>

    <section class="card mt-8 overflow-hidden">
        <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
            <h2 class="font-display text-2xl font-bold">Préstamos activos</h2>
            <p class="mt-1 text-sm text-slate-500">Ordenados por fecha de vencimiento</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[700px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                    <tr><th class="px-6 py-3">Ejemplar</th><th class="px-6 py-3">Título</th><th class="px-6 py-3">Usuario</th><th class="px-6 py-3">Vence</th><th class="px-6 py-3">Estado</th><th class="px-6 py-3 text-right">Acción</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->activeLoans as $loan)
                        <tr wire:key="active-{{ $loan->getKey() }}">
                            <td class="px-6 py-4 font-mono text-xs">{{ $loan->copy_barcode }}</td>
                            <td class="px-6 py-4 font-semibold">{{ $loan->book_title }}</td>
                            <td class="px-6 py-4">{{ $loan->user_name }}</td>
                            <td class="px-6 py-4">{{ $loan->due_at->format('d/m/Y') }}</td>
                            <td class="px-6 py-4"><span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-bold text-brand-700">{{ $loan->status->value === 'overdue' ? 'Vencido' : 'Activo' }}</span></td>
                            <td class="px-6 py-4 text-right">
                                @if (auth()->user()->canManageLibrary())
                                    <button
                                        type="button"
                                        class="cursor-pointer text-sm font-bold text-red-700 hover:text-red-900"
                                        wire:click="deleteLoan('{{ $loan->getKey() }}')"
                                        wire:confirm="¿Seguro que deseas devolver este préstamo? El ejemplar volverá a estar disponible."
                                    >
                                        Devolver
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-10 text-center text-slate-500">No hay préstamos activos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
