<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

trait ManagesFieldOperatorProfile
{
    public string $profileName = '';

    public string $profileEmail = '';

    public string $currentPassword = '';

    public string $newPassword = '';

    public string $new_password_confirmation = '';

    public function mountFieldOperatorProfile(): void
    {
        $user = auth()->user();

        if ($user === null) {
            return;
        }

        $this->profileName = $user->name;
        $this->profileEmail = $user->email;
    }

    public function updateProfile(): void
    {
        $user = auth()->user();

        $data = $this->validate([
            'profileName' => ['required', 'string', 'max:255'],
            'profileEmail' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
        ], [], [
            'profileName' => 'nombre',
            'profileEmail' => 'correo',
        ]);

        $user?->update([
            'name' => $data['profileName'],
            'email' => $data['profileEmail'],
        ]);

        session()->flash('profile_status', 'Perfil actualizado correctamente.');
    }

    public function updatePassword(): void
    {
        $this->validate([
            'currentPassword' => ['required', 'current_password'],
            'newPassword' => ['required', 'confirmed', Password::defaults()],
        ], [], [
            'currentPassword' => 'contraseña actual',
            'newPassword' => 'nueva contraseña',
        ]);

        auth()->user()?->update([
            'password' => Hash::make($this->newPassword),
        ]);

        $this->reset(['currentPassword', 'newPassword', 'new_password_confirmation']);
        session()->flash('profile_status', 'Contraseña actualizada correctamente.');
    }
}
