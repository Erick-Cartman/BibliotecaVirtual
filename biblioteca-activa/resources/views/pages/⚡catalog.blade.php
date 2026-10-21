<?php

use App\Models\Book;
use App\Models\Copy;
use App\Models\Reservation;
use App\Models\User;
use App\ReservationStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use MongoDB\BSON\Regex;

new class extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $program = '';

    public string $title = '';

    public string $author = '';

    public string $isbn = '';

    public string $newPublicationYear = '';

    public string $newDeweyCode = '';

    public string $newCopyCount = '1';

    public string $newProgram = 'General';

    public string $coverUrl = '';

    public ?string $selectedBookId = null;

    public ?string $editingBookId = null;

    public string $editTitle = '';

    public string $editAuthors = '';

    public string $editIsbn = '';

    public string $editPublisher = '';

    public string $editPublicationYear = '';

    public string $editDeweyCode = '';

    public string $editLearningUnit = '';

    public string $editProgram = '';

    public string $editCoverUrl = '';

    /** @var array<string, string>|null */
    public ?array $reservationConfirmation = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedProgram(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function books()
    {
        $query = Book::query()->where('active', true);

        if ($this->search !== '') {
            $query->where('search_text', 'regex', new Regex(preg_quote(Str::lower(Str::ascii($this->search))), 'i'));
        }

        if ($this->program !== '') {
            $query->where('program', $this->program);
        }

        return $query->orderBy('title')->paginate(12);
    }

    #[Computed]
    public function programs(): Collection
    {
        return Book::query()
            ->where('active', true)
            ->get(['program'])
            ->pluck('program')
            ->filter()
            ->reject(fn ($program) => in_array(Str::lower(trim((string) $program)), [
                '2',
                '2 copias',
                '3',
                '4',
                '13',
                '2 duplicados',
                '202',
                '3 duplicados',
                '4 copias',
                '6 copias',
            ], true))
            ->unique()
            ->sort()
            ->values();
    }

    #[Computed]
    public function myReservations(): Collection
    {
        if (auth()->user()->role->value !== 'student') {
            return new Collection;
        }

        return Reservation::query()
            ->where('user_id', (string) auth()->id())
            ->whereIn('status', [ReservationStatus::Pending->value, ReservationStatus::Ready->value])
            ->latest('reserved_at')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function reservedBookIds(): array
    {
        return $this->myReservations
            ->pluck('book_id')
            ->map(fn (mixed $bookId): string => (string) $bookId)
            ->all();
    }

    #[Computed]
    public function selectedBook(): ?Book
    {
        if (! $this->selectedBookId) {
            return null;
        }

        return Book::query()->find($this->selectedBookId);
    }

    public function viewBook(string $bookId): void
    {
        $book = Book::query()->findOrFail($bookId);
        $this->authorize('view', $book);

        $this->selectedBookId = (string) $book->getKey();
        unset($this->selectedBook);
    }

    public function closeBookDetails(): void
    {
        $this->selectedBookId = null;
        unset($this->selectedBook);
    }

    public function closeReservationConfirmation(): void
    {
        $this->reservationConfirmation = null;
    }

    public function reserve(string $bookId): void
    {
        abort_unless(auth()->user()->role->value === 'student', 403);
        $book = Book::query()->findOrFail($bookId);
        /** @var User $student */
        $student = auth()->user();
        $reservation = Reservation::query()
            ->where('book_id', $bookId)
            ->where('user_id', (string) $student->getKey())
            ->whereIn('status', [ReservationStatus::Pending->value, ReservationStatus::Ready->value])
            ->first();

        if ($reservation === null) {
            $reservation = Reservation::query()->create([
                'book_id' => $bookId,
                'user_id' => (string) $student->getKey(),
                'status' => ReservationStatus::Pending,
                'reserved_at' => now(),
                'expires_at' => null,
                'book_title' => $book->title,
                'student_name' => $student->name,
                'student_code' => $student->student_code,
            ]);
        }

        $this->closeBookDetails();
        $this->reservationConfirmation = $this->buildReservationConfirmation($reservation, $book, $student);
        unset($this->myReservations, $this->reservedBookIds);
        $exists = ! $reservation->wasRecentlyCreated;
        session()->flash(
            'message',
            $exists
                ? 'Ya tienes una reservación activa para este título.'
                : 'Reservación enviada a Circulación. El personal bibliotecario la preparará.',
        );
    }

    /**
     * @return array<string, string>
     */
    private function buildReservationConfirmation(Reservation $reservation, Book $book, User $student): array
    {
        return [
            'reservation_id' => (string) $reservation->getKey(),
            'student_name' => (string) $student->name,
            'student_email' => (string) $student->email,
            'student_code' => (string) ($student->student_code ?: 'Sin codigo'),
            'book_title' => (string) $book->title,
            'book_isbn' => (string) ($book->isbn ?: 'No registrado'),
            'reserved_at' => $reservation->reserved_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i'),
        ];
    }

    public function cancelReservation(string $reservationId): void
    {
        Reservation::query()
            ->whereKey($reservationId)
            ->where('user_id', (string) auth()->id())
            ->where('status', ReservationStatus::Pending->value)
            ->firstOrFail()
            ->update(['status' => ReservationStatus::Cancelled]);

        unset($this->myReservations, $this->reservedBookIds);
        session()->flash('message', 'Reservación cancelada.');
    }

    public function editBook(string $bookId): void
    {
        $book = Book::query()->findOrFail($bookId);
        $this->authorize('update', $book);

        $this->editingBookId = (string) $book->getKey();
        $this->editTitle = $book->title;
        $this->editAuthors = $book->authorLine();
        $this->editIsbn = (string) ($book->isbn ?? '');
        $this->editPublisher = (string) ($book->publisher ?? '');
        $this->editPublicationYear = (string) ($book->publication_year ?? '');
        $this->editDeweyCode = (string) ($book->dewey_code ?? '');
        $this->editLearningUnit = (string) ($book->learning_unit ?? '');
        $this->editProgram = (string) ($book->program ?? 'General');
        $this->editCoverUrl = (string) ($book->cover_url ?? '');
        $this->closeBookDetails();
        $this->resetValidation();
    }

    public function cancelEditing(): void
    {
        $this->reset(
            'editingBookId',
            'editTitle',
            'editAuthors',
            'editIsbn',
            'editPublisher',
            'editPublicationYear',
            'editDeweyCode',
            'editLearningUnit',
            'editProgram',
            'editCoverUrl',
        );
        $this->resetValidation();
    }

    public function updateBook(): void
    {
        abort_unless($this->editingBookId, 404);
        $book = Book::query()->findOrFail($this->editingBookId);
        $this->authorize('update', $book);

        $data = $this->validate([
            'editTitle' => ['required', 'string', 'max:240'],
            'editAuthors' => ['required', 'string', 'max:500'],
            'editIsbn' => ['nullable', 'string', 'max:20'],
            'editPublisher' => ['nullable', 'string', 'max:160'],
            'editPublicationYear' => ['nullable', 'integer', 'min:1400', 'max:'.(now()->year + 1)],
            'editDeweyCode' => ['nullable', 'string', 'max:40'],
            'editLearningUnit' => ['nullable', 'string', 'max:180'],
            'editProgram' => ['required', 'string', 'max:120'],
            'editCoverUrl' => ['nullable', 'url:http,https', 'max:2000'],
        ], [
            'editTitle.required' => 'El título es obligatorio.',
            'editAuthors.required' => 'Registra al menos un autor.',
            'editPublicationYear.integer' => 'El año debe ser un número entero.',
            'editPublicationYear.min' => 'El año debe ser igual o posterior a 1400.',
            'editPublicationYear.max' => 'El año no puede ser posterior al próximo año.',
            'editProgram.required' => 'El programa es obligatorio.',
            'editCoverUrl.url' => 'La portada debe usar una dirección web válida con http o https.',
        ]);

        $searchText = Str::lower(Str::ascii(implode(' ', [
            $data['editTitle'],
            $data['editAuthors'],
            $data['editIsbn'] ?? '',
            $data['editPublisher'] ?? '',
            $data['editDeweyCode'] ?? '',
            $data['editLearningUnit'] ?? '',
            $data['editProgram'],
        ])));

        $book->update([
            'title' => $data['editTitle'],
            'authors' => [$data['editAuthors']],
            'isbn' => filled($data['editIsbn'] ?? null) ? $data['editIsbn'] : null,
            'publisher' => filled($data['editPublisher'] ?? null) ? $data['editPublisher'] : null,
            'publication_year' => filled($data['editPublicationYear'] ?? null) ? (int) $data['editPublicationYear'] : null,
            'dewey_code' => filled($data['editDeweyCode'] ?? null) ? $data['editDeweyCode'] : null,
            'learning_unit' => filled($data['editLearningUnit'] ?? null) ? $data['editLearningUnit'] : null,
            'program' => $data['editProgram'],
            'cover_url' => filled($data['editCoverUrl'] ?? null) ? $data['editCoverUrl'] : null,
            'search_text' => $searchText,
        ]);

        $this->cancelEditing();
        unset($this->books, $this->programs);
        session()->flash('message', 'Los datos del libro se actualizaron correctamente.');
    }

    public function createBook(): void
    {
        $this->authorize('create', Book::class);
        $data = $this->validate([
            'title' => ['required', 'string', 'max:240'],
            'author' => ['required', 'string', 'max:240'],
            'isbn' => ['required', 'string', 'max:20'],
            'newPublicationYear' => ['required', 'integer', 'min:1400', 'max:'.(now()->year + 1)],
            'newDeweyCode' => ['required', 'string', 'max:40'],
            'newCopyCount' => ['required', 'integer', 'min:1', 'max:50'],
            'newProgram' => ['required', 'string', 'max:120'],
            'coverUrl' => ['nullable', 'url:http,https', 'max:2000'],
        ], [
            'isbn.required' => 'El ISBN es obligatorio.',
            'newPublicationYear.required' => 'El año de edición es obligatorio.',
            'newPublicationYear.integer' => 'El año debe ser un número entero.',
            'newPublicationYear.min' => 'El año debe ser igual o posterior a 1400.',
            'newPublicationYear.max' => 'El año no puede ser posterior al próximo año.',
            'newDeweyCode.required' => 'El Dewey es obligatorio.',
            'newCopyCount.required' => 'Selecciona la cantidad de ejemplares.',
            'newCopyCount.integer' => 'La cantidad de ejemplares debe ser un número entero.',
            'newCopyCount.min' => 'Debe registrarse al menos un ejemplar.',
            'newCopyCount.max' => 'La cantidad máxima es de 50 ejemplares.',
            'newProgram.required' => 'El programa es obligatorio.',
            'coverUrl.url' => 'La portada debe usar una dirección web válida con http o https.',
        ]);
        $copyCount = (int) $data['newCopyCount'];
        $searchText = Str::lower(Str::ascii(implode(' ', [
            $data['title'],
            $data['author'],
            $data['isbn'] ?? '',
            $data['newDeweyCode'] ?? '',
            $data['newProgram'],
        ])));
        $book = Book::query()->create([
            'source_key' => 'manual-'.Str::uuid(),
            'title' => $data['title'],
            'authors' => [$data['author']],
            'isbn' => $data['isbn'] ?: null,
            'publisher' => null,
            'publication_year' => filled($data['newPublicationYear'] ?? null) ? (int) $data['newPublicationYear'] : null,
            'dewey_code' => filled($data['newDeweyCode'] ?? null) ? $data['newDeweyCode'] : null,
            'learning_unit' => null,
            'program' => $data['newProgram'],
            'cover_url' => filled($data['coverUrl'] ?? null) ? $data['coverUrl'] : null,
            'search_text' => $searchText,
            'total_copies' => $copyCount,
            'available_copies' => $copyCount,
            'source' => ['type' => 'manual', 'created_by' => (string) auth()->id()],
            'active' => true,
        ]);
        $barcodePrefix = 'BA-MAN-'.Str::upper(Str::random(8));

        for ($copyNumber = 1; $copyNumber <= $copyCount; $copyNumber++) {
            Copy::query()->create([
                'book_id' => (string) $book->getKey(),
                'barcode' => $barcodePrefix.'-'.str_pad((string) $copyNumber, 3, '0', STR_PAD_LEFT),
                'location' => 'Por asignar',
                'condition' => 'Bueno',
                'status' => 'available',
                'active' => true,
            ]);
        }
        $this->reset('title', 'author', 'isbn', 'newPublicationYear', 'newDeweyCode', 'newCopyCount', 'coverUrl');
        unset($this->books, $this->programs);
        session()->flash('message', 'Título agregado al catálogo.');
    }
};
?>

<div>
    <x-slot:title>Catálogo · Biblioteca Activa</x-slot:title>

    <div>
        <p class="eyebrow">Acervo escolar</p>
        <h1 class="mt-2 font-display text-4xl font-bold text-ink">Catálogo</h1>
        <p class="mt-2 text-slate-600">Busca por título, autor, ISBN, clasificación o unidad de aprendizaje.</p>
    </div>

    @if (session('message'))
        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 font-medium text-emerald-800" role="status">
            {{ session('message') }}
        </div>
    @endif

    @if ($reservationConfirmation)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 sm:p-6" x-data x-on:keydown.escape.window="$wire.closeReservationConfirmation()">
            <button type="button" class="absolute inset-0 cursor-default bg-slate-950/70 backdrop-blur-sm" wire:click="closeReservationConfirmation" aria-label="Cerrar confirmación de reservación"></button>
            <section class="relative max-h-[calc(100vh-2rem)] w-full max-w-2xl overflow-y-auto rounded-3xl bg-card shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="reservation-qr-title">
                <header class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5 sm:px-8">
                    <div>
                        <p class="eyebrow">Reserva confirmada</p>
                        <h2 id="reservation-qr-title" class="mt-1 font-display text-3xl font-bold text-ink">Código de reservación</h2>
                        <p class="mt-2 text-sm text-slate-600">Guarda o muestra este código al personal bibliotecario.</p>
                    </div>
                    <button type="button" class="grid min-h-11 min-w-11 cursor-pointer place-items-center rounded-xl text-slate-500 transition hover:bg-slate-100 hover:text-slate-900" wire:click="closeReservationConfirmation" aria-label="Cerrar código de reservación">
                        <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
                    </button>
                </header>
                <div class="grid gap-6 p-6 sm:p-8 md:grid-cols-[minmax(16rem,18rem)_1fr] md:items-start">
                    <div class="rounded-2xl border border-slate-200 bg-card p-4 text-center shadow-sm" wire:ignore>
                        @php
                            $reservationQrPayload = implode("\n", [
                                'Biblioteca Activa',
                                'Reservacion: '.$reservationConfirmation['reservation_id'],
                                'Alumno: '.$reservationConfirmation['student_name'],
                                'Correo: '.$reservationConfirmation['student_email'],
                                'Codigo de alumno: '.$reservationConfirmation['student_code'],
                                'Libro: '.$reservationConfirmation['book_title'],
                                'ISBN: '.$reservationConfirmation['book_isbn'],
                                'Fecha: '.$reservationConfirmation['reserved_at'],
                            ]);
                        @endphp
                        <canvas x-ref="reservationQr" class="mx-auto size-64 max-w-full" x-init="const renderQr = () => { window.QRCode?.toCanvas($refs.reservationQr, @js($reservationQrPayload), { width: 256, margin: 4, errorCorrectionLevel: 'L', color: { dark: '#29211D', light: '#FFF8EF' } }); }; window.QRCode ? renderQr() : window.addEventListener('qrcode:ready', renderQr, { once: true });" aria-label="Código QR con los datos de la reservación"></canvas>
                        <p class="mt-3 text-xs font-semibold text-slate-500">Escanéalo para consultar esta reserva.</p>
                    </div>
                    <dl class="divide-y divide-slate-100 rounded-2xl border border-slate-200">
                        <div class="px-4 py-3"><dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Alumno</dt><dd class="mt-1 font-semibold text-slate-900">{{ $reservationConfirmation['student_name'] }}</dd></div>
                        <div class="px-4 py-3"><dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Correo</dt><dd class="mt-1 break-all font-semibold text-slate-900">{{ $reservationConfirmation['student_email'] }}</dd></div>
                        <div class="px-4 py-3"><dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Código de alumno</dt><dd class="mt-1 font-semibold text-slate-900">{{ $reservationConfirmation['student_code'] }}</dd></div>
                        <div class="px-4 py-3"><dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Libro</dt><dd class="mt-1 font-semibold text-slate-900">{{ $reservationConfirmation['book_title'] }}</dd></div>
                        <div class="px-4 py-3"><dt class="text-xs font-bold uppercase tracking-wider text-slate-500">ISBN</dt><dd class="mt-1 font-semibold text-slate-900">{{ $reservationConfirmation['book_isbn'] }}</dd></div>
                        <div class="px-4 py-3"><dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Fecha</dt><dd class="mt-1 font-semibold text-slate-900">{{ $reservationConfirmation['reserved_at'] }}</dd></div>
                    </dl>
                </div>
                <footer class="border-t border-slate-200 px-6 py-5 sm:px-8">
                    <button type="button" class="btn-primary w-full cursor-pointer" wire:click="closeReservationConfirmation">Entendido</button>
                </footer>
            </section>
        </div>
    @endif

    @if (auth()->user()->role->value === 'student')
        <section class="card mt-7 overflow-hidden" aria-labelledby="my-reservations-title">
            <div class="border-b border-slate-200 bg-brand-50 px-5 py-4 sm:px-6">
                <p class="eyebrow">Seguimiento</p>
                <h2 id="my-reservations-title" class="mt-1 font-display text-2xl font-bold">Mis reservaciones</h2>
                <p class="mt-1 text-sm text-slate-600">Tus solicitudes se envían a la bandeja de Circulación para que el personal bibliotecario las atienda.</p>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($this->myReservations as $reservation)
                    <article class="grid gap-3 px-5 py-4 sm:grid-cols-[1fr_auto_auto] sm:items-center sm:px-6" wire:key="my-reservation-{{ $reservation->getKey() }}">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $reservation->book_title }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $reservation->status->label() }} · {{ $reservation->reserved_at?->format('d/m/Y H:i') }}</p>
                        </div>
                        <x-reservation-qr :reservation="$reservation" />
                        @if ($reservation->status === ReservationStatus::Pending)
                            <button type="button" class="btn-secondary cursor-pointer px-4" wire:click="cancelReservation('{{ $reservation->getKey() }}')">Cancelar</button>
                        @else
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-bold text-emerald-800">Lista para recoger</span>
                        @endif
                    </article>
                @empty
                    <div class="px-6 py-8 text-center">
                        <p class="font-semibold text-slate-700">Todavía no tienes reservaciones.</p>
                        <p class="mt-1 text-sm text-slate-500">Selecciona “Reservar título” en cualquier libro para enviar una solicitud.</p>
                    </div>
                @endforelse
            </div>
        </section>
    @endif

    <section class="card mt-7 p-4 sm:p-5" aria-label="Filtros del catálogo">
        <div class="grid gap-4 md:grid-cols-[1fr_16rem]">
            <div>
                <label class="label" for="catalog-search">Buscar</label>
                <input id="catalog-search" class="field" type="search" wire:model.live.debounce.350ms="search" placeholder="Ej. metodología, Hernández, 978…">
            </div>
            <div>
                <label class="label" for="program">Programa</label>
                <select id="program" class="field" wire:model.live="program">
                    <option value="">Todos los programas</option>
                    @foreach ($this->programs as $item)
                        <option value="{{ $item }}">{{ $item }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <p class="mt-3 text-sm text-slate-500" wire:loading.delay wire:target="search,program">Actualizando resultados…</p>
    </section>

    @can('create', App\Models\Book::class)
        <details class="card mt-5 p-5">
            <summary class="min-h-11 cursor-pointer py-2 font-bold text-brand-700">Agregar un título manualmente</summary>
            <form wire:submit="createBook" class="mt-5 grid gap-4 md:grid-cols-2">
                <div><label class="label" for="title">Título</label><input id="title" class="field" wire:model="title" required>@error('title')<p class="text-sm text-red-700">{{ $message }}</p>@enderror</div>
                <div><label class="label" for="author">Autor</label><input id="author" class="field" wire:model="author" required>@error('author')<p class="text-sm text-red-700">{{ $message }}</p>@enderror</div>
                <div><label class="label" for="isbn">ISBN</label><input id="isbn" class="field" wire:model="isbn" required>@error('isbn')<p class="text-sm text-red-700">{{ $message }}</p>@enderror</div>
                <div><label class="label" for="newPublicationYear">Año de edición</label><input id="newPublicationYear" class="field" type="number" min="1400" max="{{ now()->year + 1 }}" wire:model="newPublicationYear" required>@error('newPublicationYear')<p class="text-sm text-red-700">{{ $message }}</p>@enderror</div>
                <div><label class="label" for="newDeweyCode">Dewey</label><input id="newDeweyCode" class="field" wire:model="newDeweyCode" placeholder="Ej. 005.12" required>@error('newDeweyCode')<p class="text-sm text-red-700">{{ $message }}</p>@enderror</div>
                <div><label class="label" for="newProgram">Programa</label><input id="newProgram" class="field" wire:model="newProgram" required></div>
                <div>
                    <label class="label" for="newCopyCount">Cantidad de ejemplares</label>
                    <select id="newCopyCount" class="field" wire:model="newCopyCount" required>
                        @foreach (range(1, 50) as $copyCount)
                            <option value="{{ $copyCount }}">{{ $copyCount }}</option>
                        @endforeach
                    </select>
                    @error('newCopyCount')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label class="label" for="coverUrl">URL de portada <span class="font-normal text-slate-500">(opcional)</span></label>
                    <input id="coverUrl" class="field" type="url" wire:model="coverUrl" placeholder="https://ejemplo.com/portada.jpg" aria-describedby="coverUrl-help coverUrl-error">
                    <p id="coverUrl-help" class="mt-1.5 text-sm text-slate-500">Si se deja vacío, intentaremos localizar la portada mediante el ISBN.</p>
                    @error('coverUrl')<p id="coverUrl-error" class="mt-1.5 text-sm font-medium text-red-700">{{ $message }}</p>@enderror
                </div>
                <button class="btn-primary cursor-pointer md:col-span-2 md:justify-self-start" type="submit">Guardar título</button>
            </form>
        </details>
    @endcan

    @if ($selectedBook = $this->selectedBook)
        <div class="fixed inset-0 z-40 flex items-center justify-center p-4 sm:p-6" x-data x-on:keydown.escape.window="$wire.closeBookDetails()">
            <button type="button" class="absolute inset-0 cursor-default bg-slate-950/70 backdrop-blur-sm" wire:click="closeBookDetails" aria-label="Cerrar detalles del libro"></button>
            <section
                class="relative max-h-[calc(100vh-2rem)] w-full max-w-5xl overflow-y-auto rounded-3xl bg-card shadow-2xl"
                role="dialog"
                aria-modal="true"
                aria-labelledby="book-detail-title"
                aria-describedby="book-detail-description"
                wire:key="book-detail-{{ $selectedBook->getKey() }}"
            >
                <button type="button" class="absolute right-4 top-4 z-10 grid min-h-11 min-w-11 cursor-pointer place-items-center rounded-xl bg-card/95 text-slate-600 shadow-sm transition hover:bg-card hover:text-slate-950" wire:click="closeBookDetails" x-ref="closeDetails" x-init="$nextTick(() => $refs.closeDetails.focus())" aria-label="Cerrar detalles">
                    <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
                </button>

                <div class="grid md:grid-cols-[minmax(15rem,20rem)_1fr]">
                    <div class="bg-slate-100 p-6 sm:p-8">
                        <x-book-cover :book="$selectedBook" loading="eager" class="mx-auto aspect-[3/4] w-full max-w-64 rounded-2xl shadow-xl ring-1 ring-slate-900/10" />
                        <div class="mx-auto mt-6 w-full max-w-64">
                            <x-book-qr :book="$selectedBook" />
                        </div>
                    </div>

                    <div class="flex flex-col p-6 sm:p-8 md:p-10">
                        <div class="flex flex-wrap items-center gap-2">
                            <span @class([
                                'rounded-full px-3 py-1 text-xs font-bold',
                                'bg-emerald-50 text-emerald-800' => $selectedBook->available_copies > 0,
                                'bg-amber-50 text-amber-800' => $selectedBook->available_copies < 1,
                            ])>{{ $selectedBook->available_copies > 0 ? 'Disponible' : 'Sin ejemplares' }}</span>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $selectedBook->program }}</span>
                        </div>

                        <p class="eyebrow mt-6">Ficha bibliográfica</p>
                        <h2 id="book-detail-title" class="mt-2 font-display text-3xl font-bold leading-tight text-ink sm:text-4xl">{{ $selectedBook->title }}</h2>
                        <p id="book-detail-description" class="mt-3 text-lg leading-7 text-slate-600">{{ $selectedBook->authorLine() }}</p>

                        <dl class="mt-7 grid gap-x-8 gap-y-5 border-y border-slate-200 py-6 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Editorial</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ $selectedBook->publisher ?: 'No registrada' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Año de edición</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ $selectedBook->publication_year ?: 'No registrado' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">ISBN</dt>
                                <dd class="mt-1 break-all font-semibold text-slate-900">{{ $selectedBook->isbn ?: 'No registrado' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Clasificación</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ $selectedBook->dewey_code ?: 'Sin clasificación' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Unidad de aprendizaje</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ $selectedBook->learning_unit ?: 'No registrada' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Ejemplares disponibles</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ $selectedBook->available_copies }} de {{ $selectedBook->total_copies }}</dd>
                            </div>
                        </dl>

                        <footer class="mt-7 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                            @can('update', $selectedBook)
                                <button type="button" class="btn-primary cursor-pointer gap-2" wire:click="editBook('{{ $selectedBook->getKey() }}')">
                                    <svg viewBox="0 0 24 24" class="size-4 fill-none stroke-current" stroke-width="2" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4z"/></svg>
                                    Editar datos
                                </button>
                            @endcan
                            @if (auth()->user()->role->value === 'student')
                                @php($isSelectedBookReserved = in_array((string) $selectedBook->getKey(), $this->reservedBookIds, true))
                                <button
                                    type="button"
                                    class="btn-primary cursor-pointer"
                                    wire:click="reserve('{{ $selectedBook->getKey() }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="reserve('{{ $selectedBook->getKey() }}')"
                                    @disabled($isSelectedBookReserved)
                                    aria-pressed="{{ $isSelectedBookReserved ? 'true' : 'false' }}"
                                >
                                    {{ $isSelectedBookReserved ? 'Reservación enviada' : 'Reservar título' }}
                                </button>
                            @endif
                            <button type="button" class="btn-secondary cursor-pointer" wire:click="closeBookDetails">Cerrar</button>
                        </footer>
                    </div>
                </div>
            </section>
        </div>
    @endif

    @if ($editingBookId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6" x-data x-on:keydown.escape.window="$wire.cancelEditing()">
            <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm" aria-hidden="true"></div>
            <section
                class="relative max-h-[calc(100vh-2rem)] w-full max-w-4xl overflow-y-auto rounded-2xl bg-card shadow-2xl"
                role="dialog"
                aria-modal="true"
                aria-labelledby="edit-book-title"
                aria-describedby="edit-book-description"
                x-init="$nextTick(() => $refs.editTitle.focus())"
            >
                <header class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-card px-5 py-4 sm:px-7">
                    <div>
                        <p class="eyebrow">Catálogo</p>
                        <h2 id="edit-book-title" class="mt-1 font-display text-2xl font-bold text-ink">Editar libro</h2>
                        <p id="edit-book-description" class="mt-1 text-sm text-slate-500">Actualiza la ficha bibliográfica. Los ejemplares y préstamos no se modificarán.</p>
                    </div>
                    <button type="button" class="grid min-h-11 min-w-11 cursor-pointer place-items-center rounded-xl text-slate-500 transition hover:bg-slate-100 hover:text-slate-900" wire:click="cancelEditing" aria-label="Cerrar edición">
                        <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
                    </button>
                </header>

                <form wire:submit="updateBook" class="p-5 sm:p-7">
                    <p class="mb-5 text-sm text-slate-500">Los campos marcados con <span class="font-bold text-red-700">*</span> son obligatorios.</p>

                    @if ($errors->hasAny(['editTitle', 'editAuthors', 'editIsbn', 'editPublisher', 'editPublicationYear', 'editDeweyCode', 'editLearningUnit', 'editProgram', 'editCoverUrl']))
                        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800" role="alert">
                            Revisa los campos señalados antes de guardar los cambios.
                        </div>
                    @endif

                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="label" for="editTitle">Título <span class="text-red-700" aria-hidden="true">*</span></label>
                            <input x-ref="editTitle" id="editTitle" class="field" wire:model="editTitle" aria-invalid="{{ $errors->has('editTitle') ? 'true' : 'false' }}" aria-describedby="editTitle-error">
                            @error('editTitle') <p id="editTitle-error" class="mt-1.5 text-sm font-medium text-red-700">{{ $message }}</p> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="label" for="editAuthors">Autor o autores <span class="text-red-700" aria-hidden="true">*</span></label>
                            <input id="editAuthors" class="field" wire:model="editAuthors" aria-invalid="{{ $errors->has('editAuthors') ? 'true' : 'false' }}" aria-describedby="editAuthors-error">
                            @error('editAuthors') <p id="editAuthors-error" class="mt-1.5 text-sm font-medium text-red-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="editIsbn">ISBN</label>
                            <input id="editIsbn" class="field" wire:model="editIsbn" inputmode="numeric" aria-invalid="{{ $errors->has('editIsbn') ? 'true' : 'false' }}" aria-describedby="editIsbn-error">
                            @error('editIsbn') <p id="editIsbn-error" class="mt-1.5 text-sm font-medium text-red-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="editPublisher">Editorial</label>
                            <input id="editPublisher" class="field" wire:model="editPublisher" aria-invalid="{{ $errors->has('editPublisher') ? 'true' : 'false' }}" aria-describedby="editPublisher-error">
                            @error('editPublisher') <p id="editPublisher-error" class="mt-1.5 text-sm font-medium text-red-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="editPublicationYear">Año de edición</label>
                            <input id="editPublicationYear" class="field" type="number" min="1400" max="{{ now()->year + 1 }}" wire:model="editPublicationYear" aria-invalid="{{ $errors->has('editPublicationYear') ? 'true' : 'false' }}" aria-describedby="editPublicationYear-error">
                            @error('editPublicationYear') <p id="editPublicationYear-error" class="mt-1.5 text-sm font-medium text-red-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="editDeweyCode">Clasificación</label>
                            <input id="editDeweyCode" class="field" wire:model="editDeweyCode" aria-invalid="{{ $errors->has('editDeweyCode') ? 'true' : 'false' }}" aria-describedby="editDeweyCode-error">
                            @error('editDeweyCode') <p id="editDeweyCode-error" class="mt-1.5 text-sm font-medium text-red-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="editLearningUnit">Unidad de aprendizaje</label>
                            <input id="editLearningUnit" class="field" wire:model="editLearningUnit" aria-invalid="{{ $errors->has('editLearningUnit') ? 'true' : 'false' }}" aria-describedby="editLearningUnit-error">
                            @error('editLearningUnit') <p id="editLearningUnit-error" class="mt-1.5 text-sm font-medium text-red-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="editProgram">Programa <span class="text-red-700" aria-hidden="true">*</span></label>
                            <input id="editProgram" class="field" wire:model="editProgram" aria-invalid="{{ $errors->has('editProgram') ? 'true' : 'false' }}" aria-describedby="editProgram-error">
                            @error('editProgram') <p id="editProgram-error" class="mt-1.5 text-sm font-medium text-red-700">{{ $message }}</p> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="label" for="editCoverUrl">URL de portada <span class="font-normal text-slate-500">(opcional)</span></label>
                            <input id="editCoverUrl" class="field" type="url" wire:model="editCoverUrl" placeholder="https://ejemplo.com/portada.jpg" aria-invalid="{{ $errors->has('editCoverUrl') ? 'true' : 'false' }}" aria-describedby="editCoverUrl-help editCoverUrl-error">
                            <p id="editCoverUrl-help" class="mt-1.5 text-sm text-slate-500">Al dejarla vacía, la portada se buscará por ISBN.</p>
                            @error('editCoverUrl') <p id="editCoverUrl-error" class="mt-1.5 text-sm font-medium text-red-700">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <footer class="mt-7 flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                        <button type="button" class="btn-secondary cursor-pointer" wire:click="cancelEditing">Cancelar</button>
                        <button type="submit" class="btn-primary cursor-pointer" wire:loading.attr="disabled" wire:target="updateBook">
                            <span wire:loading.remove wire:target="updateBook">Guardar cambios</span>
                            <span wire:loading wire:target="updateBook">Guardando…</span>
                        </button>
                    </footer>
                </form>
            </section>
        </div>
    @endif

    <section class="mt-7 grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-3 2xl:grid-cols-4" aria-label="Resultados del catálogo">
        @forelse ($this->books as $book)
            <article class="card group flex min-w-0 flex-col overflow-hidden transition duration-200 hover:-translate-y-1 hover:border-brand-200 hover:shadow-lg motion-reduce:transform-none" wire:key="book-{{ $book->getKey() }}">
                <button
                    type="button"
                    class="relative block cursor-pointer text-left focus-visible:z-10"
                    wire:click="viewBook('{{ $book->getKey() }}')"
                    aria-label="Ver detalles de {{ $book->title }}"
                >
                    <x-book-cover :book="$book" class="aspect-[3/4] w-full" />
                    <span @class([
                        'absolute left-2 top-2 rounded-full px-2.5 py-1 text-[0.6875rem] font-bold shadow-sm sm:left-3 sm:top-3 sm:text-xs',
                        'bg-emerald-50 text-emerald-800' => $book->available_copies > 0,
                        'bg-amber-50 text-amber-900' => $book->available_copies < 1,
                    ])>{{ $book->available_copies > 0 ? 'Disponible' : 'No disponible' }}</span>
                    <span class="absolute bottom-2 right-2 inline-flex items-center gap-1 rounded-lg bg-slate-950/80 px-2.5 py-1.5 text-xs font-bold text-white backdrop-blur-sm transition group-hover:bg-brand-700 sm:bottom-3 sm:right-3">
                        Ver ficha
                        <svg viewBox="0 0 24 24" class="size-3.5 fill-none stroke-current" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                    </span>
                </button>

                <div class="flex flex-1 flex-col p-3 sm:p-5">
                    <button type="button" class="cursor-pointer text-left" wire:click="viewBook('{{ $book->getKey() }}')">
                        <h2 class="line-clamp-2 font-display text-base font-bold leading-snug text-ink group-hover:text-brand-700 sm:text-xl">{{ $book->title }}</h2>
                        <p class="mt-2 line-clamp-2 text-xs leading-5 text-slate-600 sm:text-sm sm:leading-6">{{ $book->authorLine() }}</p>
                    </button>
                    <div class="mt-auto flex items-end justify-between gap-2 pt-4 text-xs sm:text-sm">
                        <span class="line-clamp-1 font-semibold text-slate-500">{{ $book->program }}</span>
                        <span class="shrink-0 font-bold text-slate-700" aria-label="{{ $book->available_copies }} de {{ $book->total_copies }} ejemplares disponibles">{{ $book->available_copies }}/{{ $book->total_copies }}</span>
                    </div>
                </div>
            </article>
        @empty
            <div class="card col-span-full p-10 text-center">
                <p class="font-display text-2xl font-bold">No encontramos coincidencias</p>
                <p class="mt-2 text-slate-500">Prueba con menos palabras o cambia el programa.</p>
            </div>
        @endforelse
    </section>

    <div class="mt-7">{{ $this->books->links() }}</div>
</div>
