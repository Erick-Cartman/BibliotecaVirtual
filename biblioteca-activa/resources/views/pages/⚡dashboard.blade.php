<?php

use App\LoanStatus;
use App\Models\Book;
use App\Models\Copy;
use App\Models\Loan;
use App\Models\Reservation;
use App\Models\User;
use App\ReservationStatus;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    #[Computed]
    public function stats(): array
    {
        $user = auth()->user();

        if ($user->canManageLibrary()) {
            return [
                ['label' => 'Títulos activos', 'value' => Book::query()->where('active', true)->count(), 'tone' => 'brand'],
                ['label' => 'Ejemplares', 'value' => Copy::query()->where('active', true)->count(), 'tone' => 'emerald'],
                ['label' => 'Préstamos activos', 'value' => Loan::query()->whereIn('status', [LoanStatus::Active->value, LoanStatus::Overdue->value])->count(), 'tone' => 'amber'],
                ['label' => 'Usuarios activos', 'value' => User::query()->where('status', 'active')->count(), 'tone' => 'sky'],
            ];
        }

        return [
            ['label' => 'Préstamos activos', 'value' => Loan::query()->where('user_id', (string) $user->getKey())->whereIn('status', [LoanStatus::Active->value, LoanStatus::Overdue->value])->count(), 'tone' => 'brand'],
            ['label' => 'Reservaciones', 'value' => Reservation::query()->where('user_id', (string) $user->getKey())->where('status', ReservationStatus::Pending->value)->count(), 'tone' => 'amber'],
            ['label' => 'Títulos disponibles', 'value' => Book::query()->where('available_copies', '>', 0)->count(), 'tone' => 'emerald'],
        ];
    }

    #[Computed]
    public function recentLoans()
    {
        $query = Loan::query()->latest('loaned_at');
        if (! auth()->user()->canManageLibrary()) {
            $query->where('user_id', (string) auth()->id());
        }

        return $query->limit(6)->get();
    }
};
?>

<div>
    <x-slot:title>Resumen · Biblioteca Virtual</x-slot:title>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div><p class="eyebrow">Panel principal</p><h1 class="mt-2 font-display text-4xl font-bold text-ink">Hola, {{ str(auth()->user()->name)->before(' ') }}</h1><p class="mt-2 text-slate-600">Este es el estado de tu biblioteca hoy.</p></div>
        <a href="{{ route('catalog') }}" wire:navigate class="btn-primary">Buscar en el catálogo</a>
    </div>

    <section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Indicadores">
        @foreach($this->stats as $stat)
            <article class="card p-5" wire:key="stat-{{ $stat['label'] }}"><p class="text-sm font-semibold text-slate-500">{{ $stat['label'] }}</p><p class="mt-3 font-display text-4xl font-bold text-ink">{{ number_format($stat['value']) }}</p><div class="mt-4 h-1.5 w-12 rounded-full bg-brand-600"></div></article>
        @endforeach
    </section>

    <section class="card mt-8 overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 sm:px-6"><div><h2 class="font-display text-2xl font-bold text-ink">Actividad reciente</h2><p class="mt-1 text-sm text-slate-500">Últimos préstamos registrados</p></div>@if(auth()->user()->canManageLibrary())<a class="text-sm font-bold text-brand-700" href="{{ route('circulation') }}" wire:navigate>Gestionar</a>@endif</div>
        @forelse($this->recentLoans as $loan)
            <div class="grid gap-2 border-b border-slate-100 px-5 py-4 last:border-0 sm:grid-cols-[1fr_auto] sm:px-6" wire:key="loan-{{ $loan->getKey() }}">
                <div><p class="font-semibold text-slate-900">{{ $loan->book_title }}</p><p class="mt-1 text-sm text-slate-500">{{ $loan->copy_barcode }} · {{ $loan->user_name }}</p></div>
                <div class="sm:text-right"><span class="inline-flex rounded-full bg-brand-50 px-3 py-1 text-xs font-bold text-brand-700">{{ $loan->status->label() }}</span><p class="mt-1 text-xs text-slate-500">Vence {{ $loan->due_at?->format('d/m/Y') }}</p></div>
            </div>
        @empty
            <div class="px-6 py-12 text-center"><p class="font-semibold text-slate-700">Todavía no hay movimientos.</p><p class="mt-1 text-sm text-slate-500">La actividad aparecerá aquí cuando se registre.</p></div>
        @endforelse
    </section>
</div>
