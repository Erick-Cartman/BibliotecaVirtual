<?php

namespace Tests\Feature;

use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class UsersTest extends TestCase
{
    public function test_admin_creates_a_student_with_the_next_code(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['student_code' => 'EST-001']);

        Livewire::actingAs($admin)
            ->test('pages::users')
            ->set('name', 'Nuevo estudiante')
            ->set('email', 'nuevo.estudiante@example.com')
            ->set('role', 'student')
            ->call('createUser')
            ->assertHasNoErrors();

        $student = User::query()->where('email', 'nuevo.estudiante@example.com')->firstOrFail();

        $this->assertSame('EST-002', $student->student_code);
    }

    public function test_a_new_student_without_a_code_receives_one_automatically(): void
    {
        User::factory()->create(['student_code' => 'EST-001']);

        $student = User::factory()->create(['student_code' => null]);

        $this->assertSame('EST-002', $student->student_code);
    }

    public function test_existing_students_without_a_code_can_be_backfilled(): void
    {
        $student = User::withoutEvents(fn (): User => User::factory()->create(['student_code' => null]));

        User::backfillMissingStudentCodes();

        $this->assertSame('EST-001', $student->fresh()->student_code);
    }

    public function test_student_code_is_visible_in_the_profile_data(): void
    {
        $student = User::factory()->create(['student_code' => 'EST-042']);

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Código de estudiante')
            ->assertSee('EST-042');
    }
}
