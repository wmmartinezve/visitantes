<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\InvitadoEstatus;
use App\Enums\RequerimientoEstatus;
use App\Enums\TipoAnfitrionHogar;
use App\Enums\UserRole;
use App\Enums\TipoViviendaHogar;
use App\Models\CentroAcopio;
use App\Models\Comuna;
use App\Models\Inventario;
use App\Models\Invitado;
use App\Models\Parroquia;
use App\Models\HogarSolidario;
use App\Models\Requerimiento;
use App\Models\User;
use App\Support\InsumoCatalog;
use Illuminate\Database\Seeder;

class DemoOperacionSeeder extends Seeder
{
    public function run(): void
    {
        $plc = $this->parroquia('Puerto La Cruz');
        if ($plc === null) {
            return;
        }

        $refugioPlc = $this->hogarSolidario(
            $plc,
            10.21380000,
            -64.63280000,
            'Av. Municipal, Puerto La Cruz, Anzoátegui',
        );

        $centroPlc = $this->centroAcopio(
            'Centro de Acopio Pozuelos Norte',
            $this->parroquia('Pozuelos') ?? $plc,
            10.24500000,
            -64.65500000,
            'Sector Pozuelos, Juan Antonio Sotillo, Anzoátegui',
        );

        $this->inventarioBase($centroPlc);

        $anfitrion = User::query()->updateOrCreate(
            ['email' => 'anfitrion@visitantes.test'],
            [
                'name' => 'Anfitrión Demo (Puerto La Cruz)',
                'password' => 'password',
                'rol' => UserRole::Anfitrion,
                'hogar_solidario_id' => $refugioPlc->id,
                'centro_acopio_id' => null,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'acopio@visitantes.test'],
            [
                'name' => 'Operador Acopio Demo',
                'password' => 'password',
                'rol' => UserRole::CentroAcopio,
                'hogar_solidario_id' => null,
                'centro_acopio_id' => $centroPlc->id,
            ],
        );

        $invitado = Invitado::query()->firstOrCreate(
            [
                'nombre' => 'Carlos',
                'apellido' => 'Demo',
                'hogar_solidario_id' => $refugioPlc->id,
            ],
            [
                'cedula' => 'V-12345678',
                'telefono' => '0414-1111111',
                'fecha_nacimiento' => '1985-06-15',
                'estatus' => InvitadoEstatus::Activo,
            ],
        );

        Requerimiento::query()->firstOrCreate(
            [
                'invitado_id' => $invitado->id,
                'categoria' => 'Abrigo y descanso',
                'subcategoria' => 'Colchoneta',
                'estatus' => RequerimientoEstatus::Pendiente,
            ],
            array_merge(
                $this->requerimientoPair('Abrigo y descanso', 'Colchoneta'),
                [
                    'anfitrion_id' => $anfitrion->id,
                    'cantidad' => 5,
                    'centro_acopio_id' => null,
                ],
            ),
        );

        $this->seedCiudadBarcelona();
        $this->seedCiudadElTigre();
        $this->seedCiudadLecheria();
    }

    private function seedCiudadBarcelona(): void
    {
        $parroquia = $this->parroquia('El Carmen');
        if ($parroquia === null) {
            return;
        }

        $refugio = $this->hogarSolidario(
            $parroquia,
            10.13640000,
            -64.68640000,
            'Av. 5 de Julio, Barcelona, Anzoátegui',
        );

        $centro = $this->centroAcopio(
            'Centro de Acopio Barcelona Centro',
            $parroquia,
            10.13800000,
            -64.68400000,
            'Centro de Barcelona, Anzoátegui',
        );

        Inventario::query()->updateOrCreate(
            [
                'centro_acopio_id' => $centro->id,
                'categoria' => 'Abrigo y descanso',
                'subcategoria' => 'Colchoneta',
            ],
            array_merge(
                $this->insumoPair('Abrigo y descanso', 'Colchoneta'),
                ['cantidad' => 35, 'unidad_medida' => 'unidad'],
            ),
        );

        Inventario::query()->updateOrCreate(
            [
                'centro_acopio_id' => $centro->id,
                'categoria' => 'Higiene personal',
                'subcategoria' => 'Kit de higiene',
            ],
            array_merge(
                $this->insumoPair('Higiene personal', 'Kit de higiene'),
                ['cantidad' => 80, 'unidad_medida' => 'kit'],
            ),
        );

        $anfitrion = User::query()->updateOrCreate(
            ['email' => 'anfitrion.barcelona@visitantes.test'],
            [
                'name' => 'Anfitrión Demo (Barcelona)',
                'password' => 'password',
                'rol' => UserRole::Anfitrion,
                'hogar_solidario_id' => $refugio->id,
                'centro_acopio_id' => null,
            ],
        );

        $invitado = Invitado::query()->firstOrCreate(
            [
                'nombre' => 'María',
                'apellido' => 'Rivas',
                'hogar_solidario_id' => $refugio->id,
            ],
            [
                'cedula' => 'V-87654321',
                'telefono' => '0416-2222222',
                'fecha_nacimiento' => '1992-03-20',
                'estatus' => InvitadoEstatus::Activo,
            ],
        );

        Requerimiento::query()->firstOrCreate(
            [
                'invitado_id' => $invitado->id,
                'categoria' => 'Alimentos y bebidas',
                'subcategoria' => 'Agua embotellada',
                'estatus' => RequerimientoEstatus::Pendiente,
            ],
            array_merge(
                $this->requerimientoPair('Alimentos y bebidas', 'Agua embotellada'),
                [
                    'anfitrion_id' => $anfitrion->id,
                    'cantidad' => 10,
                    'centro_acopio_id' => null,
                ],
            ),
        );
    }

    private function seedCiudadElTigre(): void
    {
        $parroquia = $this->parroquia('Edmundo Barrios');
        if ($parroquia === null) {
            return;
        }

        $refugio = $this->hogarSolidario(
            $parroquia,
            8.88920000,
            -64.24560000,
            'Av. Intercomunal, El Tigre, Anzoátegui',
        );

        $centro = $this->centroAcopio(
            'Centro de Acopio El Tigre',
            $parroquia,
            8.89100000,
            -64.24300000,
            'Zona industrial, El Tigre, Anzoátegui',
        );

        Inventario::query()->updateOrCreate(
            [
                'centro_acopio_id' => $centro->id,
                'categoria' => 'Abrigo y descanso',
                'subcategoria' => 'Frazada / cobija',
            ],
            array_merge(
                $this->insumoPair('Abrigo y descanso', 'Frazada / cobija'),
                ['cantidad' => 60, 'unidad_medida' => 'unidad'],
            ),
        );

        User::query()->updateOrCreate(
            ['email' => 'anfitrion.tigre@visitantes.test'],
            [
                'name' => 'Anfitrión Demo (El Tigre)',
                'password' => 'password',
                'rol' => UserRole::Anfitrion,
                'hogar_solidario_id' => $refugio->id,
                'centro_acopio_id' => null,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'acopio.tigre@visitantes.test'],
            [
                'name' => 'Operador Acopio El Tigre',
                'password' => 'password',
                'rol' => UserRole::CentroAcopio,
                'hogar_solidario_id' => null,
                'centro_acopio_id' => $centro->id,
            ],
        );
    }

    private function seedCiudadLecheria(): void
    {
        $parroquia = $this->parroquia('Lechería');
        if ($parroquia === null) {
            return;
        }

        $refugio = $this->hogarSolidario(
            $parroquia,
            10.18530000,
            -64.67920000,
            'Av. El Morro, Lechería, Anzoátegui',
        );

        $centro = $this->centroAcopio(
            'Centro de Acopio Lechería',
            $parroquia,
            10.18700000,
            -64.67700000,
            'Sector El Morro, Lechería, Anzoátegui',
        );

        Inventario::query()->updateOrCreate(
            [
                'centro_acopio_id' => $centro->id,
                'categoria' => 'Alimentos y bebidas',
                'subcategoria' => 'Agua embotellada',
            ],
            array_merge(
                $this->insumoPair('Alimentos y bebidas', 'Agua embotellada'),
                ['cantidad' => 200, 'unidad_medida' => 'caja'],
            ),
        );

        Inventario::query()->updateOrCreate(
            [
                'centro_acopio_id' => $centro->id,
                'categoria' => 'Abrigo y descanso',
                'subcategoria' => 'Colchoneta',
            ],
            array_merge(
                $this->insumoPair('Abrigo y descanso', 'Colchoneta'),
                ['cantidad' => 15, 'unidad_medida' => 'unidad'],
            ),
        );
    }

    private function parroquia(string $nombre): ?Parroquia
    {
        return Parroquia::query()->where('nombre', $nombre)->first();
    }

    private function hogarSolidario(Parroquia $parroquia, float $lat, float $lng, string $direccion): HogarSolidario
    {
        $comuna = Comuna::query()->firstOrCreate(
            ['parroquia_id' => $parroquia->id, 'nombre' => 'Comuna demo '.$parroquia->nombre],
        );

        return HogarSolidario::query()->firstOrCreate(
            [
                'parroquia_id' => $parroquia->id,
                'direccion_exacta' => $direccion,
            ],
            [
                'comuna_id' => $comuna->id,
                'tipo_vivienda' => TipoViviendaHogar::Casa,
                'tipo_anfitrion' => TipoAnfitrionHogar::Familiar,
                'parentesco_anfitrion' => 'Padre/Madre',
                'responsable_nombre' => 'Responsable demo',
                'responsable_cedula' => 'V-10000000',
                'responsable_telefono' => '0414-0000000',
                'habitantes' => [],
                'latitud' => $lat,
                'longitud' => $lng,
            ],
        );
    }

    private function centroAcopio(string $nombre, Parroquia $parroquia, float $lat, float $lng, string $direccion): CentroAcopio
    {
        return CentroAcopio::query()->firstOrCreate(
            ['nombre' => $nombre],
            [
                'parroquia_id' => $parroquia->id,
                'latitud' => $lat,
                'longitud' => $lng,
                'direccion_exacta' => $direccion,
                'contacto' => '0414-0000000',
                'activo' => true,
            ],
        );
    }

    private function inventarioBase(CentroAcopio $centro): void
    {
        Inventario::query()->updateOrCreate(
            [
                'centro_acopio_id' => $centro->id,
                'categoria' => 'Abrigo y descanso',
                'subcategoria' => 'Colchoneta',
            ],
            array_merge(
                $this->insumoPair('Abrigo y descanso', 'Colchoneta'),
                ['cantidad' => 50, 'unidad_medida' => 'unidad'],
            ),
        );

        Inventario::query()->updateOrCreate(
            [
                'centro_acopio_id' => $centro->id,
                'categoria' => 'Alimentos y bebidas',
                'subcategoria' => 'Agua embotellada',
            ],
            array_merge(
                $this->insumoPair('Alimentos y bebidas', 'Agua embotellada'),
                ['cantidad' => 120, 'unidad_medida' => 'caja'],
            ),
        );
    }

    /** @return array{categoria: string, subcategoria: string, item_nombre: string} */
    private function insumoPair(string $categoria, string $subcategoria): array
    {
        return [
            'categoria' => $categoria,
            'subcategoria' => $subcategoria,
            'item_nombre' => InsumoCatalog::etiqueta($categoria, $subcategoria),
        ];
    }

    /** @return array{categoria: string, subcategoria: string, item_solicitado: string} */
    private function requerimientoPair(string $categoria, string $subcategoria): array
    {
        return [
            'categoria' => $categoria,
            'subcategoria' => $subcategoria,
            'item_solicitado' => InsumoCatalog::etiqueta($categoria, $subcategoria),
        ];
    }
}
