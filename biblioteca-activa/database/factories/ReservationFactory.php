<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Reservation;
use App\Models\User;
use App\ReservationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Reservation> */
class ReservationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'book_id' => Book::factory(),
            'user_id' => User::factory(),
            'status' => ReservationStatus::Pending,
            'reserved_at' => now(),
            'expires_at' => null,
            'book_title' => fake()->sentence(4),
            'student_name' => fake()->name(),
            'student_code' => 'EST-'.fake()->numerify('######'),
        ];
    }
}
