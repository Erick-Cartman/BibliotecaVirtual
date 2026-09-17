<?php

use App\Models\Book;
use App\Models\Copy;
use App\Models\Reservation;
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

    public string $newProgram = 'General';

    public ?string $editingBookId = null;

    public string $editTitle = '';

    public string $editAuthors = '';

    public string $editIsbn = '';

    public string $editPublisher = '';

    public string $editPublicationYear = '';

    public string $editDeweyCode = '';

    public string $editLearningUnit = '';

    public string $editProgram = '';

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

    public function reserve(string $bookId): void
    {
        abort_unless(auth()->user()->role->value === 'student', 403);
        $book = Book::query()->findOrFail($bookId);
        $student = auth()->user();
        $exists = Reservation::query()
            ->where('book_id', $bookId)
            ->where('user_id', (string) $student->getKey())
            ->whereIn('status', [ReservationStatus::Pending->value, ReservationStatus::Ready->value])
            ->exists();

        if (! $exists) {
            Reservation::query()->create([
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

        unset($this->myReservations, $this->reservedBookIds);
        session()->flash(
            'message',
            $exists
                ? 'Ya tienes una reservación activa para este título.'
                : 'Reservación enviada a Circulación. El personal bibliotecario la preparará.',
        );
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
        ], [
            'editTitle.required' => 'El título es obligatorio.',
            'editAuthors.required' => 'Registra al menos un autor.',
            'editPublicationYear.integer' => 'El año debe ser un número entero.',
            'editPublicationYear.min' => 'El año debe ser igual o posterior a 1400.',
            'editPublicationYear.max' => 'El año no puede ser posterior al próximo año.',
            'editProgram.required' => 'El programa es obligatorio.',
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
            'isbn' => ['nullable', 'string', 'max:20'],
            'newProgram' => ['required', 'string', 'max:120'],
        ]);
        $searchText = Str::lower(Str::ascii(implode(' ', $data)));
        $book = Book::query()->create([
            'source_key' => 'manual-'.Str::uuid(),
            'title' => $data['title'],
            'authors' => [$data['author']],
            'isbn' => $data['isbn'] ?: null,
            'publisher' => null,
            'publication_year' => null,
            'dewey_code' => null,
            'learning_unit' => null,
            'program' => $data['newProgram'],
            'search_text' => $searchText,
            'total_copies' => 1,
            'available_copies' => 1,
            'source' => ['type' => 'manual', 'created_by' => (string) auth()->id()],
            'active' => true,
        ]);
        Copy::query()->create([
            'book_id' => (string) $book->getKey(),
            'barcode' => 'BA-MAN-'.Str::upper(Str::random(8)),
            'location' => 'Por asignar',
            'condition' => 'Bueno',
            'status' => 'available',
            'active' => true,
        ]);
        $this->reset('title', 'author', 'isbn');
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

    @if (auth()->user()->role->value === 'student')
        <section class="card mt-7 overflow-hidden" aria-labelledby="my-reservations-title">
            <div class="border-b border-slate-200 bg-brand-50 px-5 py-4 sm:px-6">
                <p class="eyebrow">Seguimiento</p>
                <h2 id="my-reservations-title" class="mt-1 font-display text-2xl font-bold">Mis reservaciones</h2>
                <p class="mt-1 text-sm text-slate-600">Tus solicitudes se envían a la bandeja de Circulación para que el personal bibliotecario las atienda.</p>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($this->myReservations as $reservation)
                    <article class="grid gap-3 px-5 py-4 sm:grid-cols-[1fr_auto] sm:items-center sm:px-6" wire:key="my-reservation-{{ $reservation->getKey() }}">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $reservation->book_title }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $reservation->status->label() }} · {{ $reservation->reserved_at?->format('d/m/Y H:i') }}</p>
                        </div>
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
                <div><label class="label" for="title">Título</label><input id="title" class="field" wire:model="title">@error('title')<p class="text-sm text-red-700">{{ $message }}</p>@enderror</div>
                <div><label class="label" for="author">Autor</label><input id="author" class="field" wire:model="author">@error('author')<p class="text-sm text-red-700">{{ $message }}</p>@enderror</div>
                <div><label class="label" for="isbn">ISBN</label><input id="isbn" class="field" wire:model="isbn"></div>
                <div><label class="label" for="newProgram">Programa</label><input id="newProgram" class="field" wire:model="newProgram"></div>
                <button class="btn-primary cursor-pointer md:col-span-2 md:justify-self-start" type="submit">Guardar título</button>
            </form>
        </details>
    @endcan

    @if ($editingBookId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6" x-data x-on:keydown.escape.window="$wire.cancelEditing()">
            <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm" aria-hidden="true"></div>
            <section
                class="relative max-h-[calc(100vh-2rem)] w-full max-w-4xl overflow-y-auto rounded-2xl bg-white shadow-2xl"
                role="dialog"
                aria-modal="true"
                aria-labelledby="edit-book-title"
                aria-describedby="edit-book-description"
                x-init="$nextTick(() => $refs.editTitle.focus())"
            >
                <header class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-5 py-4 sm:px-7">
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

                    @if ($errors->hasAny(['editTitle', 'editAuthors', 'editIsbn', 'editPublisher', 'editPublicationYear', 'editDeweyCode', 'editLearningUnit', 'editProgram']))
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

    <section class="mt-7 grid gap-5 md:grid-cols-2 xl:grid-cols-3" aria-label="Resultados">
        @forelse ($this->books as $book)
            <article class="card flex flex-col p-5" wire:key="book-{{ $book->getKey() }}">
                <div class="flex items-start justify-between gap-4">
                    <span @class([
                        'rounded-full px-3 py-1 text-xs font-bold',
                        'bg-emerald-50 text-emerald-800' => $book->available_copies > 0,
                        'bg-amber-50 text-amber-800' => $book->available_copies < 1,
                    ])>{{ $book->available_copies > 0 ? 'Disponible' : 'Sin ejemplares' }}</span>
                    <span class="text-xs font-semibold text-slate-400">{{ $book->dewey_code ?: 'S/C' }}</span>
                </div>
                <h2 class="mt-4 font-display text-xl font-bold leading-snug text-ink">{{ $book->title }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $book->authorLine() }}</p>
                <dl class="mt-5 grid grid-cols-2 gap-3 border-t border-slate-100 pt-4 text-sm">
                    <div><dt class="text-slate-500">Programa</dt><dd class="mt-1 font-semibold">{{ $book->program }}</dd></div>
                    <div><dt class="text-slate-500">Ejemplares</dt><dd class="mt-1 font-semibold">{{ $book->available_copies }} de {{ $book->total_copies }}</dd></div>
                </dl>
                @can('update', $book)
                    <button type="button" class="btn-secondary mt-5 w-full cursor-pointer gap-2" wire:click="editBook('{{ $book->getKey() }}')">
                        <svg viewBox="0 0 24 24" class="size-4 fill-none stroke-current" stroke-width="2" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4z"/></svg>
                        Editar datos
                    </button>
                @endcan
                @if (auth()->user()->role->value === 'student')
                    @php($isReserved = in_array((string) $book->getKey(), $this->reservedBookIds, true))
                    <button
                        class="btn-secondary mt-5 w-full cursor-pointer"
                        wire:click="reserve('{{ $book->getKey() }}')"
                        wire:loading.attr="disabled"
                        wire:target="reserve('{{ $book->getKey() }}')"
                        @disabled($isReserved)
                        aria-pressed="{{ $isReserved ? 'true' : 'false' }}"
                    >
                        {{ $isReserved ? 'Reservación enviada' : 'Reservar título' }}
                    </button>
                @endif
            </article>
        @empty
            <div class="card p-10 text-center md:col-span-2 xl:col-span-3">
                <p class="font-display text-2xl font-bold">No encontramos coincidencias</p>
                <p class="mt-2 text-slate-500">Prueba con menos palabras o cambia el programa.</p>
            </div>
        @endforelse
    </section>

    <div class="mt-7">{{ $this->books->links() }}</div>
</div>
