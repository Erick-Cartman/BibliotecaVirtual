<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:160'],
            'password' => ['nullable', 'string', 'min:8'],
            'avatar' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg', 'extensions:jpg,jpeg', 'max:2048'],
        ], [
            'avatar.mimes' => 'La imagen debe estar en formato JPG o JPEG.',
            'avatar.extensions' => 'La imagen debe tener extensión JPG o JPEG.',
            'avatar.max' => 'La imagen no puede pesar más de 2 MB.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        $validator->after(function ($validator) use ($user, $request): void {
            $email = strtolower(trim((string) $request->input('email')));
            $existingUser = User::query()->where('email', $email)->first();

            if ($existingUser !== null && (string) $existingUser->getKey() !== (string) $user->getKey()) {
                $validator->errors()->add('email', 'Ese correo ya está registrado.');
            }
        });

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('profile_modal_open', true);
        }

        $data = $validator->validated();
        $oldAvatarPath = $user->avatar_path;
        $newAvatarPath = null;

        if ($request->hasFile('avatar')) {
            $newAvatarPath = $request->file('avatar')->store('avatars', 'public');
        }

        $user->first_name = trim($data['first_name']);
        $user->last_name = trim($data['last_name']);
        $user->name = trim($user->first_name.' '.$user->last_name);
        $user->email = strtolower(trim($data['email']));

        if (filled($data['password'] ?? null)) {
            $user->password = $data['password'];
        }

        if ($newAvatarPath !== null) {
            $user->avatar_path = $newAvatarPath;
        }

        $user->save();

        if ($newAvatarPath !== null && filled($oldAvatarPath)) {
            Storage::disk('public')->delete($oldAvatarPath);
        }

        return back()->with('profile_message', 'Tus datos personales se actualizaron correctamente.');
    }
}
