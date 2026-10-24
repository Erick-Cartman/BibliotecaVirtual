<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    public function test_authenticated_user_can_update_personal_data_and_jpeg_avatar(): void
    {
        $avatarDisk = (string) config('filesystems.avatar_disk');
        Storage::fake($avatarDisk);
        $user = User::factory()->create([
            'name' => 'Nombre Original',
            'email' => 'original@example.com',
        ]);

        $this->actingAs($user)
            ->post(route('profile.update'), [
                'first_name' => 'María',
                'last_name' => 'López',
                'email' => 'maria.lopez@example.com',
                'password' => 'NuevaClave123',
                'avatar' => UploadedFile::fake()->createWithContent(
                    'avatar.jpg',
                    file_get_contents(base_path('vendor/livewire/livewire/src/Features/SupportFileUploads/browser_test_image_big.jpg')),
                ),
            ])
            ->assertRedirect();

        $updatedUser = $user->fresh();

        $this->assertSame('María López', $updatedUser->name);
        $this->assertSame('María', $updatedUser->first_name);
        $this->assertSame('López', $updatedUser->last_name);
        $this->assertSame('maria.lopez@example.com', $updatedUser->email);
        $this->assertTrue(Hash::check('NuevaClave123', $updatedUser->password));
        Storage::disk($avatarDisk)->assertExists($updatedUser->avatar_path);
    }

    public function test_profile_rejects_non_jpeg_avatar(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('profile.update'), [
                'first_name' => 'Nombre',
                'last_name' => 'Apellido',
                'email' => $user->email,
                'avatar' => UploadedFile::fake()->createWithContent(
                    'avatar.png',
                    file_get_contents(base_path('vendor/livewire/livewire/src/Features/SupportFileUploads/browser_test_image.png')),
                ),
            ])
            ->assertSessionHasErrors('avatar');
    }
}
