<?php

namespace Database\Factories;

use App\LoanStatus;
use App\Models\Book;
use App\Models\Copy;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Loan> */
class LoanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'copy_id' => Copy::factory(),
            'book_id' => Book::factory(),
            'user_id' => User::factory(),
            'operator_id' => User::factory()->librarian(),
            'status' => LoanStatus::Active,
            'loaned_at' => now(),
            'due_at' => now()->addDays(14),
            'returned_at' => null,
            'renewals' => 0,
            'book_title' => fake()->sentence(4),
            'copy_barcode' => fake()->uuid(),
            'user_name' => fake()->name(),
        ];
    }
}
