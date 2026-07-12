<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\CondicionInvitado;
use App\Services\AnfitrionMobileProfileService;
use App\Support\HogarSolidarioValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class MobileInvitadoStoreRequest extends FormRequest
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
        $user = $this->user();
        $profile = app(AnfitrionMobileProfileService::class);
        $registrarNuevoHogar = $this->boolean('registrar_nuevo_hogar');

        $rules = [
            'registrar_nuevo_hogar' => ['sometimes', 'boolean'],
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'cedula' => ['nullable', 'string', 'max:20'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'fecha_nacimiento' => ['required', 'date', 'before_or_equal:today'],
            'procedencia_estado_id' => ['required', 'integer', 'exists:estados,id'],
            'procedencia_municipio_id' => ['required', 'integer', 'exists:municipios,id'],
            'procedencia_parroquia_id' => ['required', 'integer', 'exists:parroquias,id'],
            'situacion_jefe' => ['required', 'string', 'in:trabajando,desempleado,pensionado,estudiante,otro'],
            'condicion' => CondicionInvitado::validationRules(),
            'familiares' => ['array'],
            'familiares.*.nombre' => ['required_with:familiares.*.apellido', 'string', 'max:255'],
            'familiares.*.apellido' => ['required_with:familiares.*.nombre', 'string', 'max:255'],
            'familiares.*.cedula' => ['nullable', 'string', 'max:20'],
            'familiares.*.telefono' => ['nullable', 'string', 'max:30'],
            'familiares.*.parentesco' => ['required_with:familiares.*.nombre', 'string', 'max:50'],
            'familiares.*.condicion' => array_merge(
                ['required_with:familiares.*.nombre'],
                CondicionInvitado::validationRules(false),
            ),
            'familiares.*.fecha_nacimiento' => ['required_with:familiares.*.nombre', 'date', 'before_or_equal:today'],
            'foto_base64' => ['nullable', 'string', 'max:12000000'],
            'foto_mime' => ['nullable', 'string', 'in:image/jpeg,image/png,image/webp'],
        ];

        if ($profile->debeEnviarDatosHogar($user, $registrarNuevoHogar)) {
            $rules = array_merge($rules, HogarSolidarioValidationRules::forPayload('hogar'));
        } elseif ($this->has('hogar')) {
            $rules['hogar'] = ['prohibited'];
        }

        return $rules;
    }
}
