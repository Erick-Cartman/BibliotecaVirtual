<?php

namespace Database\Seeders;

use App\Models\User;
use App\UserRole;
use App\UserStatus;
use Illuminate\Database\Seeder;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['name' => 'Estudiante Demo', 'email' => 'estudiante@biblioteca.test', 'student_code' => 'EST-001', 'role' => UserRole::Student],
            ['name' => 'Bibliotecaria Demo', 'email' => 'bibliotecario@biblioteca.test', 'student_code' => null, 'role' => UserRole::Librarian],
            ['name' => 'Administración Demo', 'email' => 'admin@biblioteca.test', 'student_code' => null, 'role' => UserRole::Admin],
        ];

        foreach ($accounts as $account) {
            User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    ...$account,
                    'password' => 'Biblioteca2026!',
                    'status' => UserStatus::Active,
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
