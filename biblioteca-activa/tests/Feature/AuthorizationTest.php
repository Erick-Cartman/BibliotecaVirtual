<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    public function test_student_cannot_access_staff_areas(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->get('/circulacion')->assertForbidden();
        $this->actingAs($student)->get('/usuarios')->assertForbidden();
    }

    public function test_librarian_can_access_circulation_but_not_users(): void
    {
        $librarian = User::factory()->librarian()->create();

        $this->actingAs($librarian)->get('/circulacion')->assertOk();
        $this->actingAs($librarian)->get('/usuarios')->assertForbidden();
    }

    public function test_admin_can_access_every_management_area(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/circulacion')->assertOk();
        $this->actingAs($admin)->get('/usuarios')->assertOk();
    }
}
