<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\InvitadoMencionesCatalog;
use Illuminate\Foundation\Http\FormRequest;

class MobileInvitadoMencionesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->isAnfitrion();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return InvitadoMencionesCatalog::validationRules();
    }
}
