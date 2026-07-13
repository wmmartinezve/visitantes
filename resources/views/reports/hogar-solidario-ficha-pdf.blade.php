<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Ficha hogar solidario — {{ $hogar->codigo }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1a1a1a;
            margin: 0;
            padding: 24px;
        }
        .header {
            border-bottom: 4px solid #002776;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .header h1 {
            margin: 0 0 4px;
            font-size: 17px;
            color: #002776;
        }
        .header p {
            margin: 2px 0;
            color: #555;
        }
        .tricolor {
            width: 100%;
            height: 4px;
            margin: 10px 0 0;
            border-collapse: collapse;
        }
        .tricolor td {
            height: 4px;
            padding: 0;
        }
        .tricolor .c-yellow { background: #FFCC00; width: 33%; }
        .tricolor .c-blue { background: #002776; width: 34%; }
        .tricolor .c-red { background: #CF142B; width: 33%; }
        h2 {
            font-size: 12px;
            color: #002776;
            border-bottom: 1px solid #d8deea;
            padding-bottom: 4px;
            margin: 18px 0 8px;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        table.data th,
        table.data td {
            border: 1px solid #d1d5db;
            padding: 5px 6px;
            text-align: left;
            vertical-align: top;
        }
        table.data th {
            background: #f4f6fb;
            color: #002776;
            font-size: 9px;
            width: 28%;
        }
        table.data td {
            background: #fff;
        }
        table.grid th {
            background: #002776;
            color: #fff;
            font-size: 9px;
        }
        table.grid tr:nth-child(even) td {
            background: #f9fafb;
        }
        .foto-box {
            text-align: center;
            margin: 8px 0 12px;
        }
        .foto-box img {
            max-width: 140px;
            max-height: 160px;
            border: 2px solid #002776;
            border-radius: 4px;
        }
        .foto-caption {
            font-size: 8px;
            color: #666;
            margin-top: 4px;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-activo { background: #d1fae5; color: #065f46; }
        .badge-egresado { background: #e5e7eb; color: #374151; }
        .muted { color: #666; font-size: 9px; }
        .footer {
            margin-top: 20px;
            font-size: 8px;
            color: #888;
            text-align: center;
            border-top: 1px solid #e5e7eb;
            padding-top: 8px;
        }
        .sin-dato { color: #999; font-style: italic; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Ficha de hogar solidario — Visitantes · {{ config('visitantes.estado') }}</h1>
        <p>Código: <strong>{{ $hogar->codigo ?? '—' }}</strong></p>
        <p>{{ config('visitantes.estado') }}, {{ config('visitantes.pais') }}</p>
        <p>Generado: {{ $generadoEn }}</p>
        <table class="tricolor" cellspacing="0" cellpadding="0">
            <tr>
                <td class="c-yellow"></td>
                <td class="c-blue"></td>
                <td class="c-red"></td>
            </tr>
        </table>
    </div>

    <h2>1. Datos del hogar solidario</h2>
    <table class="data">
        <tr>
            <th>Código</th>
            <td>{{ $hogar->codigo ?? '—' }}</td>
        </tr>
        <tr>
            <th>Tipo de vivienda</th>
            <td>{{ $hogar->tipo_vivienda?->label() ?? '—' }}</td>
        </tr>
        <tr>
            <th>Tipo de acogida</th>
            <td>
                {{ $hogar->tipo_anfitrion?->label() ?? '—' }}
                @if ($hogar->tipo_anfitrion?->value === 'familiar' && filled($hogar->parentesco_anfitrion))
                    · Parentesco: {{ $hogar->parentesco_anfitrion }}
                @endif
            </td>
        </tr>
        <tr>
            <th>Estado</th>
            <td>{{ $hogar->parroquia?->municipio?->estado?->nombre ?? config('visitantes.estado') }}</td>
        </tr>
        <tr>
            <th>Municipio</th>
            <td>{{ $hogar->parroquia?->municipio?->nombre ?? '—' }}</td>
        </tr>
        <tr>
            <th>Parroquia</th>
            <td>{{ $hogar->parroquia?->nombre ?? '—' }}</td>
        </tr>
        <tr>
            <th>Comuna</th>
            <td>{{ $hogar->comuna?->nombre ?? '—' }}</td>
        </tr>
        <tr>
            <th>Dirección exacta</th>
            <td>{{ $hogar->direccion_exacta ?? '—' }}</td>
        </tr>
        <tr>
            <th>Coordenadas</th>
            <td>
                @if ($hogar->latitud && $hogar->longitud)
                    {{ number_format((float) $hogar->latitud, 6) }}, {{ number_format((float) $hogar->longitud, 6) }}
                @else
                    <span class="sin-dato">No registradas</span>
                @endif
            </td>
        </tr>
        <tr>
            <th>Fecha de registro</th>
            <td>{{ $hogar->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
        </tr>
    </table>

    <h2>2. Persona responsable del hogar anfitrión</h2>
    <table class="data">
        <tr>
            <th>Nombre</th>
            <td>{{ $hogar->responsable_nombre ?? '—' }}</td>
        </tr>
        <tr>
            <th>Cédula</th>
            <td>{{ $hogar->responsable_cedula ?? '—' }}</td>
        </tr>
        <tr>
            <th>Teléfono</th>
            <td>{{ $hogar->responsable_telefono ?? '—' }}</td>
        </tr>
    </table>

    @if ($hogar->anfitriones->isNotEmpty())
        <h2>3. Usuario anfitrión vinculado</h2>
        <table class="grid data">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Correo</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($hogar->anfitriones as $anfitrion)
                    <tr>
                        <td>{{ $anfitrion->name }}</td>
                        <td>{{ $anfitrion->email }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>{{ $hogar->anfitriones->isNotEmpty() ? '4' : '3' }}. Núcleo familiar hospedado</h2>

    @if ($jefe === null)
        <p class="muted">Este hogar solidario aún no tiene un núcleo familiar registrado.</p>
    @else
        @if ($fotoBase64)
            <div class="foto-box">
                <img src="{{ $fotoBase64 }}" alt="Foto testigo jefe de familia">
                <div class="foto-caption">Foto testigo de ingreso — jefe de familia</div>
            </div>
        @endif

        <h2 style="font-size: 11px; margin-top: 12px;">Jefe de familia</h2>
        <table class="data">
            <tr>
                <th>Nombre completo</th>
                <td>{{ $jefe->nombreCompleto() }}</td>
            </tr>
            <tr>
                <th>Cédula</th>
                <td>{{ $jefe->cedula ?? '—' }}</td>
            </tr>
            <tr>
                <th>Teléfono</th>
                <td>{{ $jefe->telefono ?? '—' }}</td>
            </tr>
            <tr>
                <th>Fecha de nacimiento</th>
                <td>
                    {{ $jefe->fecha_nacimiento?->format('d/m/Y') ?? '—' }}
                    @if ($jefe->fecha_nacimiento)
                        ({{ $jefe->fecha_nacimiento->age }} años)
                    @endif
                </td>
            </tr>
            <tr>
                <th>Procedencia</th>
                <td>
                    @php
                        $procedencia = array_filter([
                            $jefe->procedenciaEstado?->nombre,
                            $jefe->procedenciaMunicipio?->nombre,
                            $jefe->procedenciaParroquia?->nombre,
                        ]);
                    @endphp
                    {{ $procedencia !== [] ? implode(' · ', $procedencia) : '—' }}
                </td>
            </tr>
            <tr>
                <th>Situación laboral</th>
                <td>{{ $jefe->situacion_jefe?->label() ?? '—' }}</td>
            </tr>
            <tr>
                <th>Condición</th>
                <td>{{ $jefe->condicion?->label() ?? '—' }}</td>
            </tr>
            <tr>
                <th>Estatus</th>
                <td>
                    <span class="badge badge-{{ $jefe->estatus?->value === 'activo' ? 'activo' : 'egresado' }}">
                        {{ $jefe->estatus?->label() ?? '—' }}
                    </span>
                </td>
            </tr>
            <tr>
                <th>Registrado</th>
                <td>{{ $jefe->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
            </tr>
        </table>

        <h2 style="font-size: 11px; margin-top: 14px;">Menciones opcionales (jefe de familia)</h2>
        <p class="muted" style="margin-bottom: 8px;">
            Etiquetado informativo. No genera requerimientos ni trámites formales.
        </p>
        @if ($mencionesJefe === null || (
            empty($mencionesJefe['menciones']['ayudas'])
            && empty($mencionesJefe['menciones']['salud'])
            && empty($mencionesJefe['menciones']['tramites'])
            && blank($mencionesJefe['menciones_nota'] ?? null)
        ))
            <p class="muted sin-dato">Sin menciones registradas.</p>
        @else
            <table class="data">
                <tr>
                    <th>Ayudas</th>
                    <td>
                        @if (! empty($mencionesJefe['menciones']['ayudas']))
                            {{ collect($mencionesJefe['menciones']['ayudas'])->pluck('label')->implode(', ') }}
                        @else
                            <span class="sin-dato">—</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>Salud</th>
                    <td>
                        @if (! empty($mencionesJefe['menciones']['salud']))
                            {{ collect($mencionesJefe['menciones']['salud'])->pluck('label')->implode(', ') }}
                        @else
                            <span class="sin-dato">—</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>Trámites documentales</th>
                    <td>
                        @if (! empty($mencionesJefe['menciones']['tramites']))
                            {{ collect($mencionesJefe['menciones']['tramites'])->pluck('label')->implode(', ') }}
                        @else
                            <span class="sin-dato">—</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>Nota breve</th>
                    <td>{{ filled($mencionesJefe['menciones_nota'] ?? null) ? $mencionesJefe['menciones_nota'] : '—' }}</td>
                </tr>
            </table>
        @endif

        <h2 style="font-size: 11px; margin-top: 14px;">Integrantes del núcleo familiar ({{ $miembros->count() }})</h2>

        @if ($miembros->isEmpty())
            <p class="muted">Sin integrantes adicionales registrados.</p>
        @else
            <table class="grid data">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Parentesco</th>
                        <th>Nombre completo</th>
                        <th>Cédula</th>
                        <th>Teléfono</th>
                        <th>Fecha nac.</th>
                        <th>Edad</th>
                        <th>Condición</th>
                        <th>Estatus</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($miembros as $index => $miembro)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $miembro->parentesco ?? '—' }}</td>
                            <td>{{ $miembro->nombreCompleto() }}</td>
                            <td>{{ $miembro->cedula ?? '—' }}</td>
                            <td>{{ $miembro->telefono ?? '—' }}</td>
                            <td>{{ $miembro->fecha_nacimiento?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $miembro->fecha_nacimiento ? $miembro->fecha_nacimiento->age : '—' }}</td>
                            <td>{{ $miembro->condicion?->label() ?? '—' }}</td>
                            <td>{{ $miembro->estatus?->label() ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <p class="muted" style="margin-top: 10px;">
            Total personas en el núcleo: {{ 1 + $miembros->count() }}
            (jefe de familia + integrantes).
        </p>
    @endif

    <div class="footer">
        Visitantes · {{ config('visitantes.estado') }} — Documento generado desde el panel administrativo.
        Uso institucional · {{ $generadoEn }}
    </div>
</body>
</html>
