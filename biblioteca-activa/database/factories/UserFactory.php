<?php

namespace Database\Factories;

use App\Models\User;
use App\UserRole;
use App\UserStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'student_code' => 'EST-'.fake()->unique()->numerify('######'),
            'role' => UserRole::Student,
            'status' => UserStatus::Active,
        ];
    }

    public function librarian(): static
    {
        return $this->state(fn (): array => [
            'role' => UserRole::Librarian,
            'student_code' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (): array => [
            'role' => UserRole::Admin,
            'student_code' => null,
        ]);
    }

    public function blocked(): static
    {
        return $this->state(fn (): array => ['status' => UserStatus::Blocked]);
    }
}
