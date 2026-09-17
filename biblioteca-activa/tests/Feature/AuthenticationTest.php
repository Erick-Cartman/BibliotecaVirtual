<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    public function test_landing_and_login_pages_are_available(): void
    {
        $this->get('/')->assertOk()->assertSee('Biblioteca Activa')->assertSee('Iniciar sesión');
        $this->get('/login')->assertOk()->assertSee('Inicia sesión');
    }

    public function test_active_user_can_authenticate(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_blocked_user_cannot_authenticate(): void
    {
        $user = User::factory()->blocked()->create(['password' => 'secret-password']);

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
