<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Invitado;
use Illuminate\Foundation\Http\FormRequest;

class MobileInvitadoFotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $invitado = $this->route('invitado');

        if (! $user || ! $invitado instanceof Invitado) {
            return false;
        }

        return $user->can('update', $invitado) && $invitado->esJefeDeFamilia();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'foto_base64' => ['required', 'string', 'max:12000000'],
            'foto_mime' => ['required', 'string', 'in:image/jpeg,image/png,image/webp'],
        ];
    }
}
