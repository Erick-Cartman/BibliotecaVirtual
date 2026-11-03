<?php

use App\Models\User;
use App\UserRole;
use App\UserStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
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

    public string $role = UserRole::Student->value;

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function users(): LengthAwarePaginator
    {
        $query = User::query();

        if ($this->search !== '') {
            $search = addcslashes($this->search, '%_\\');
            $query->where(fn ($query) => $query
                ->where('name', 'ilike', '%'.$search.'%')
                ->orWhere('email', 'ilike', '%'.$search.'%')
                ->orWhere('student_code', 'ilike', '%'.$search.'%'));
        }

        return $query->orderBy('name')->paginate(15);
    }

    public function createUser(): void
    {
        $this->authorize('create', User::class);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', 'unique:users,email'],
            'role' => ['required', Rule::in(UserRole::creatableByAdmin())],
        ]);

        User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'status' => UserStatus::Active,
            'password' => Hash::make('Biblioteca2026!'),
            'email_verified_at' => now(),
        ]);

        $this->reset('name', 'email');
        $this->role = UserRole::Student->value;
        unset($this->users);
        session()->flash('message', 'Perfil creado. Contraseña temporal: Biblioteca2026!');
    }

    public function toggleStatus(string $userId): void
    {
        $user = User::query()->findOrFail($userId);
        $this->authorize('update', $user);
        abort_if($userId === (string) auth()->id(), 422, 'No puedes bloquear tu propia cuenta.');

        $user->update([
            'status' => $user->status === UserStatus::Active
                ? UserStatus::Blocked
                : UserStatus::Active,
        ]);
        unset($this->users);
    }
};
?>

<div>
    <x-slot:title>Usuarios · Biblioteca Virtual</x-slot:title>

    <header>
        <p class="eyebrow">Administración</p>
        <h1 class="mt-2 font-display text-4xl font-bold text-ink">Perfiles de acceso</h1>
        <p class="mt-2 max-w-2xl text-slate-600">Crea y administra los accesos de bibliotecarios y estudiantes desde un solo lugar.</p>
    </header>

    @if(session('message'))
        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 font-medium text-emerald-800" role="status">
            {{ session('message') }}
        </div>
    @endif

    <section class="card mt-7 p-5 sm:p-6" aria-labelledby="create-profile-title">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="eyebrow">Alta de usuarios</p>
                <h2 id="create-profile-title" class="mt-2 font-display text-2xl font-bold text-ink">Crear un perfil</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Este apartado está disponible únicamente para el administrador. Elige el tipo de perfil que tendrá acceso a Biblioteca Virtual.</p>
            </div>
            <span class="inline-flex min-h-11 items-center rounded-full border border-brand-200 bg-brand-50 px-4 py-2 text-sm font-semibold text-brand-900">Solo administrador</span>
        </div>

        <form wire:submit="createUser" class="mt-6 grid gap-5 md:grid-cols-2">
            <div>
                <label class="label" for="name">Nombre completo</label>
                <input id="name" class="field" wire:model.blur="name" type="text" autocomplete="name" required aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="name-help name-error">
                <p id="name-help" class="mt-1.5 text-xs text-slate-500">Usa el nombre con el que la biblioteca identificará a la persona.</p>
                @error('name')<p id="name-error" class="mt-1.5 text-sm font-medium text-red-700" role="alert">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="label" for="email">Correo electrónico</label>
                <input id="email" class="field" wire:model.blur="email" type="email" autocomplete="email" required aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" aria-describedby="email-help email-error">
                <p id="email-help" class="mt-1.5 text-xs text-slate-500">Será el correo que usará para iniciar sesión.</p>
                @error('email')<p id="email-error" class="mt-1.5 text-sm font-medium text-red-700" role="alert">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="label" for="role">Tipo de perfil</label>
                <select id="role" class="field" wire:model.live="role" required aria-invalid="{{ $errors->has('role') ? 'true' : 'false' }}" aria-describedby="role-help role-error">
                    @foreach(UserRole::creatableByAdmin() as $roleOption)
                        <option value="{{ $roleOption }}">{{ UserRole::from($roleOption)->label() }}</option>
                    @endforeach
                </select>
                <p id="role-help" class="mt-1.5 text-xs text-slate-500">Los perfiles de administrador no se pueden crear desde este apartado.</p>
                @error('role')<p id="role-error" class="mt-1.5 text-sm font-medium text-red-700" role="alert">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-end rounded-xl border border-brand-100 bg-brand-50 px-4 py-3 text-sm leading-6 text-brand-900">
                <span>Las cuentas nuevas quedan activas y reciben una contraseña temporal que podrás compartir con la persona.</span>
            </div>

            <div class="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between md:col-span-2">
                <p class="text-sm text-slate-500">El estudiante recibirá automáticamente su código institucional.</p>
                <button type="submit" class="btn-primary w-full sm:w-auto" wire:loading.attr="disabled" wire:target="createUser">
                    <span wire:loading.remove wire:target="createUser">Crear perfil</span>
                    <span wire:loading wire:target="createUser">Creando perfil…</span>
                </button>
            </div>
        </form>
    </section>

    <section class="card mt-6 overflow-hidden" aria-labelledby="users-list-title">
        <div class="border-b border-slate-200 p-5 sm:p-6">
            <p class="eyebrow">Directorio</p>
            <h2 id="users-list-title" class="mt-2 font-display text-2xl font-bold text-ink">Perfiles registrados</h2>
            <label class="label mt-5" for="user-search">Buscar perfil</label>
            <input id="user-search" class="field max-w-xl" type="search" wire:model.live.debounce.350ms="search" placeholder="Nombre, correo o código" autocomplete="off">
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <caption class="sr-only">Perfiles registrados en Biblioteca Virtual</caption>
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-3" scope="col">Persona</th>
                        <th class="px-6 py-3" scope="col">Código</th>
                        <th class="px-6 py-3" scope="col">Rol</th>
                        <th class="px-6 py-3" scope="col">Estado</th>
                        <th class="px-6 py-3 text-right" scope="col">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($this->users as $user)
                        <tr wire:key="user-{{ $user->getKey() }}">
                            <td class="px-6 py-4"><p class="font-semibold">{{ $user->name }}</p><p class="text-slate-500">{{ $user->email }}</p></td>
                            <td class="px-6 py-4">{{ $user->student_code ?: '—' }}</td>
                            <td class="px-6 py-4">{{ $user->role->label() }}</td>
                            <td class="px-6 py-4"><span @class(['rounded-full px-3 py-1 text-xs font-bold', 'bg-emerald-50 text-emerald-800' => $user->status === UserStatus::Active, 'bg-red-50 text-red-800' => $user->status === UserStatus::Blocked])>{{ $user->status === UserStatus::Active ? 'Activo' : 'Bloqueado' }}</span></td>
                            <td class="px-6 py-4 text-right">
                                @if((string) $user->getKey() !== (string) auth()->id())
                                    <button type="button" class="min-h-11 px-2 text-sm font-bold text-brand-700 hover:text-brand-900" wire:click="toggleStatus('{{ $user->getKey() }}')" wire:loading.attr="disabled" wire:target="toggleStatus('{{ $user->getKey() }}')">{{ $user->status === UserStatus::Active ? 'Bloquear' : 'Activar' }}</button>
                                @else
                                    <span class="text-xs text-slate-400">Tu cuenta</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-6 py-10 text-center text-slate-500" colspan="5">No encontramos perfiles con esa búsqueda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 p-5">{{ $this->users->links() }}</div>
    </section>
</div>
