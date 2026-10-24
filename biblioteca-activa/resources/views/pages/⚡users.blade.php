<?php

use App\Models\User;
use App\UserRole;
use App\UserStatus;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;
    #[Url(as: 'q')]
    public string $search = '';
    public string $name = '';
    public string $email = '';
    public string $role = 'student';

    public function updatedSearch(): void { $this->resetPage(); }

    #[Computed]
    public function users()
    {
        $query = User::query();
        if ($this->search !== '') {
            $search = addcslashes($this->search, '%_\\');
            $query->where(fn ($q) => $q->where('name', 'ilike', '%'.$search.'%')->orWhere('email', 'ilike', '%'.$search.'%')->orWhere('student_code', 'ilike', '%'.$search.'%'));
        }
        return $query->orderBy('name')->paginate(15);
    }

    public function createUser(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'max:160', 'unique:users,email'],
            'role' => ['required', 'in:student,librarian,admin'],
        ]);
        User::query()->create(['name' => $data['name'], 'email' => $data['email'], 'role' => $data['role'], 'status' => UserStatus::Active, 'password' => Hash::make('Biblioteca2026!'), 'email_verified_at' => now()]);
        $this->reset('name', 'email');
        unset($this->users);
        session()->flash('message', 'Usuario creado. Contraseña temporal: Biblioteca2026!');
    }

    public function toggleStatus(string $userId): void
    {
        abort_if($userId === (string) auth()->id(), 422, 'No puedes bloquear tu propia cuenta.');
        $user = User::query()->findOrFail($userId);
        $user->update(['status' => $user->status === UserStatus::Active ? UserStatus::Blocked : UserStatus::Active]);
        unset($this->users);
    }
};
?>

<div>
    <x-slot:title>Usuarios · Biblioteca Activa</x-slot:title>
    <div><p class="eyebrow">Administración</p><h1 class="mt-2 font-display text-4xl font-bold text-ink">Usuarios</h1><p class="mt-2 text-slate-600">Crea accesos y controla el estado de las cuentas.</p></div>
    @if(session('message'))<div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 font-medium text-emerald-800" role="status">{{ session('message') }}</div>@endif
    <details class="card mt-7 p-5"><summary class="cursor-pointer font-bold text-brand-700">Crear nuevo usuario</summary><form wire:submit="createUser" class="mt-5 grid gap-4 md:grid-cols-2"><div><label class="label" for="name">Nombre</label><input id="name" class="field" wire:model="name">@error('name')<p class="text-sm text-red-700">{{ $message }}</p>@enderror</div><div><label class="label" for="email">Correo</label><input id="email" class="field" type="email" wire:model="email">@error('email')<p class="text-sm text-red-700">{{ $message }}</p>@enderror</div><div><label class="label" for="role">Rol</label><select id="role" class="field" wire:model="role">@foreach(UserRole::cases() as $item)<option value="{{ $item->value }}">{{ $item->label() }}</option>@endforeach</select></div><div class="flex items-end rounded-xl border border-brand-100 bg-brand-50 px-3.5 py-2.5 text-sm text-brand-900">Si eliges Estudiante, el sistema asignará automáticamente el siguiente código, por ejemplo <span class="ml-1 whitespace-nowrap font-mono font-bold">EST-001</span>.</div><button class="btn-primary md:col-span-2 md:justify-self-start">Crear usuario</button></form></details>
    <section class="card mt-6 overflow-hidden"><div class="border-b border-slate-200 p-5"><label class="label" for="user-search">Buscar usuario</label><input id="user-search" class="field max-w-xl" type="search" wire:model.live.debounce.350ms="search" placeholder="Nombre, correo o código"></div><div class="overflow-x-auto"><table class="w-full min-w-[760px] text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-6 py-3">Persona</th><th class="px-6 py-3">Código</th><th class="px-6 py-3">Rol</th><th class="px-6 py-3">Estado</th><th class="px-6 py-3 text-right">Acción</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach($this->users as $user)<tr wire:key="user-{{ $user->getKey() }}"><td class="px-6 py-4"><p class="font-semibold">{{ $user->name }}</p><p class="text-slate-500">{{ $user->email }}</p></td><td class="px-6 py-4">{{ $user->student_code ?: '—' }}</td><td class="px-6 py-4">{{ $user->role->label() }}</td><td class="px-6 py-4"><span @class(['rounded-full px-3 py-1 text-xs font-bold', 'bg-emerald-50 text-emerald-800' => $user->status === UserStatus::Active, 'bg-red-50 text-red-800' => $user->status === UserStatus::Blocked])>{{ $user->status === UserStatus::Active ? 'Activo' : 'Bloqueado' }}</span></td><td class="px-6 py-4 text-right">@if((string) $user->getKey() !== (string) auth()->id())<button class="text-sm font-bold text-brand-700" wire:click="toggleStatus('{{ $user->getKey() }}')">{{ $user->status === UserStatus::Active ? 'Bloquear' : 'Activar' }}</button>@else<span class="text-xs text-slate-400">Tu cuenta</span>@endif</td></tr>@endforeach</tbody></table></div><div class="border-t border-slate-200 p-5">{{ $this->users->links() }}</div></section>
</div>
