<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de operación — {{ config('visitantes.estado') }}</title>
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
            font-size: 18px;
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
        .filtros {
            background: #f4f6fb;
            border: 1px solid #d8deea;
            border-radius: 6px;
            padding: 10px 12px;
            margin-bottom: 16px;
        }
        .filtros strong { color: #002776; }
        .filtros ul { margin: 6px 0 0; padding-left: 16px; }
        h2 {
            font-size: 12px;
            color: #002776;
            border-bottom: 1px solid #d8deea;
            padding-bottom: 4px;
            margin: 18px 0 8px;
        }
        .kpi-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .kpi-grid td {
            width: 25%;
            padding: 8px;
            vertical-align: top;
            border: 1px solid #e5e7eb;
        }
        .kpi-label {
            font-size: 8px;
            text-transform: uppercase;
            color: #666;
            letter-spacing: 0.03em;
        }
        .kpi-value {
            font-size: 16px;
            font-weight: bold;
            color: #002776;
            margin-top: 2px;
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
        }
        table.data th {
            background: #002776;
            color: #fff;
            font-size: 9px;
        }
        table.data tr:nth-child(even) td {
            background: #f9fafb;
        }
        .footer {
            margin-top: 20px;
            font-size: 8px;
            color: #888;
            text-align: center;
            border-top: 1px solid #e5e7eb;
            padding-top: 8px;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-pendiente { background: #FFCC00; color: #333; }
        .badge-asignado { background: #002776; color: #fff; }
        .badge-entregado { background: #CF142B; color: #fff; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de operación — Visitantes · {{ config('visitantes.estado') }}</h1>
        <p>{{ config('visitantes.estado') }}, {{ config('visitantes.pais') }}</p>
        <p>Generado: {{ $generado_en }}</p>
        <table class="tricolor" cellspacing="0" cellpadding="0">
            <tr>
                <td class="c-yellow"></td>
                <td class="c-blue"></td>
                <td class="c-red"></td>
            </tr>
        </table>
    </div>

    <div class="filtros">
        <strong>Filtros aplicados</strong>
        <ul>
            @foreach ($etiquetas_filtros as $etiqueta)
                <li>{{ $etiqueta }}</li>
            @endforeach
        </ul>
    </div>

    <h2>Indicadores generales</h2>
    <table class="kpi-grid">
        @php
            $chunks = array_chunk($kpi_filas, 4);
        @endphp
        @foreach ($chunks as $row)
            <tr>
                @foreach ($row as [$label, $value])
                    <td>
                        <div class="kpi-label">{{ $label }}</div>
                        <div class="kpi-value">{{ $value }}</div>
                    </td>
                @endforeach
                @for ($i = count($row); $i < 4; $i++)
                    <td></td>
                @endfor
            </tr>
        @endforeach
    </table>

    @if ($logistica_habilitada)
        <h2>Requerimientos por estatus (período)</h2>
        <table class="data">
            <thead>
                <tr>
                    <th>Estatus</th>
                    <th>Cantidad</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($requerimientos_por_estatus as $estatus => $total)
                    <tr>
                        <td>{{ $estatus }}</td>
                        <td>{{ $total }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($resumen_por_municipio->isNotEmpty())
        <h2>Indicadores por municipio</h2>
        <table class="data">
            <thead>
                <tr>
                    <th>Municipio</th>
                    <th>Hogares</th>
                    <th>Con núcleo</th>
                    <th>Hogares nuevos</th>
                    <th>Anfitriones</th>
                    <th>Invitados activos</th>
                    <th>Registrados período</th>
                    <th>Con menciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($resumen_por_municipio as $fila)
                    <tr>
                        <td>{{ $fila->municipio }}</td>
                        <td>{{ $fila->hogares_solidarios }}</td>
                        <td>{{ $fila->hogares_con_nucleo }}</td>
                        <td>{{ $fila->hogares_nuevos_periodo }}</td>
                        <td>{{ $fila->anfitriones_desplegados }}</td>
                        <td>{{ $fila->invitados_activos }}</td>
                        <td>{{ $fila->invitados_registrados }}</td>
                        <td>{{ $fila->invitados_con_menciones }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Menciones opcionales por opción (Invitados activos)</h2>
    <p style="font-size: 9px; color: #666; margin: 0 0 8px;">
        Etiquetado informativo registrado por anfitriones. Un Invitado puede aparecer en varias filas.
    </p>
    <table class="data">
        <thead>
            <tr>
                <th>Categoría</th>
                <th>Opción</th>
                <th>Invitados activos</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($menciones_por_opcion as $fila)
                <tr>
                    <td>{{ $fila->categoria_label }}</td>
                    <td>{{ $fila->label }}</td>
                    <td>{{ $fila->total }}</td>
                </tr>
            @empty
                <tr><td colspan="3">Sin datos de menciones.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Hogares solidarios con más Invitados activos</h2>
    <table class="data">
        <thead>
            <tr>
                <th>#</th>
                <th>Hogar solidario</th>
                <th>Invitados activos</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($top_refugios as $index => $refugio)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $refugio->nombre }}</td>
                    <td>{{ $refugio->total }}</td>
                </tr>
            @empty
                <tr><td colspan="3">Sin datos para los filtros seleccionados.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($logistica_habilitada)
        <h2>Centros de acopio con más entregas (período)</h2>
        <table class="data">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Centro</th>
                    <th>Entregas</th>
                    <th>Unidades</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($top_centros as $index => $centro)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $centro->nombre }}</td>
                        <td>{{ $centro->entregados }}</td>
                        <td>{{ $centro->unidades }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Sin entregas en el período.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <div class="page-break"></div>

    @if ($logistica_habilitada)
        <h2>Inventario con stock bajo (≤ 5 unidades)</h2>
        <table class="data">
            <thead>
                <tr>
                    <th>Centro</th>
                    <th>Municipio</th>
                    <th>Ítem</th>
                    <th>Cantidad</th>
                    <th>Unidad</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($stock_bajo as $item)
                    <tr>
                        <td>{{ $item->centroAcopio?->nombre ?? '—' }}</td>
                        <td>{{ $item->centroAcopio?->parroquia?->municipio?->nombre ?? '—' }}</td>
                        <td>{{ $item->item_nombre }}</td>
                        <td>{{ $item->cantidad }}</td>
                        <td>{{ $item->unidad_medida }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No hay ítems con stock bajo.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <h2>Invitados registrados en el período (jefes de familia)</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Cédula</th>
                <th>Hogar solidario</th>
                <th>Municipio</th>
                <th>Menciones</th>
                <th>Registrado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($invitados_recientes as $invitado)
                <tr>
                    <td>{{ $invitado->nombreCompleto() }}</td>
                    <td>{{ $invitado->cedula ?? '—' }}</td>
                    <td>{{ $invitado->refugio?->nombre ?? '—' }}</td>
                    <td>{{ $invitado->refugio?->parroquia?->municipio?->nombre ?? '—' }}</td>
                    <td>{{ \App\Support\InvitadoMencionesCatalog::resumenTexto($invitado) }}</td>
                    <td>{{ $invitado->created_at?->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Sin registros en el período.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($logistica_habilitada)
        <h2>Requerimientos del período</h2>
        <table class="data">
            <thead>
                <tr>
                    <th>Invitado</th>
                    <th>Hogar solidario</th>
                    <th>Ítem</th>
                    <th>Cant.</th>
                    <th>Estatus</th>
                    <th>Centro</th>
                    <th>Solicitado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requerimientos_recientes as $req)
                    <tr>
                        <td>{{ $req->invitado?->nombreCompleto() ?? '—' }}</td>
                        <td>{{ $req->invitado?->refugio?->nombre ?? '—' }}</td>
                        <td>{{ $req->item_solicitado }}</td>
                        <td>{{ $req->cantidad }}</td>
                        <td>
                            <span class="badge badge-{{ $req->estatus?->value }}">
                                {{ $req->estatus?->label() }}
                            </span>
                        </td>
                        <td>{{ $req->centroAcopio?->nombre ?? '—' }}</td>
                        <td>{{ $req->created_at?->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">Sin requerimientos en el período.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <div class="footer">
        Visitantes · {{ config('visitantes.estado') }} — Reporte institucional generado automáticamente.
        @if ($logistica_habilitada)
            Los datos de entregas usan la fecha de actualización del requerimiento al marcarse como entregado.
        @endif
    </div>
</body>
</html>
