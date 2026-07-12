<?php

declare(strict_types=1);

return [
    'estado' => 'Anzoátegui',
    'estado_capital' => 'Barcelona',
    'pais' => 'Venezuela',

    'unidades_medida' => [
        'unidad',
        'caja',
        'kg',
        'litro',
        'paquete',
        'bolsa',
    ],

    'insumos_catalogo' => [
        'Alimentos y bebidas' => [
            'Agua embotellada',
            'Alimentos no perecederos',
            'Arroz, pasta y harina',
            'Enlatados',
            'Leche en polvo / fórmula',
            'Galletas y snacks',
            'Café / té / achocolatado',
        ],
        'Higiene personal' => [
            'Kit de higiene',
            'Jabón / shampoo',
            'Pasta dental y cepillo',
            'Pañales',
            'Toallas sanitarias',
            'Papel higiénico',
            'Toallas húmedas',
        ],
        'Vestimenta' => [
            'Pantalón',
            'Camisa / blusa',
            'Ropa interior',
            'Calzado',
            'Ropa de dormir',
            'Ropa para niños',
            'Ropa para bebé',
        ],
        'Abrigo y descanso' => [
            'Frazada / cobija',
            'Colchoneta',
            'Colchón',
            'Sábanas',
            'Almohada',
            'Mosquitero',
            'Lona / carpeta',
        ],
        'Salud' => [
            'Medicamentos básicos',
            'Botiquín de primeros auxilios',
            'Material médico desechable',
            'Repelente / protector solar',
        ],
        'Limpieza del hogar' => [
            'Detergente',
            'Cloro / desinfectante',
            'Bolsas de basura',
            'Escoba / trapeador',
        ],
        'Bebés y niños' => [
            'Pañales (bebé)',
            'Fórmula infantil',
            'Kit escolar',
            'Juguetes educativos',
        ],
        'Equipamiento' => [
            'Linterna',
            'Pilas / baterías',
            'Cocina / hornilla',
            'Bidón / garrafa',
            'Utensilios de cocina',
        ],
    ],

    'parentescos' => [
        'Cónyuge',
        'Hijo(a)',
        'Padre',
        'Madre',
        'Hermano(a)',
        'Abuelo(a)',
        'Nieto(a)',
        'Tío(a)',
        'Sobrino(a)',
        'Cuñado(a)',
        'Yerno / Nuera',
        'Suegro(a)',
        'Otro',
    ],

    /*
    | Colores institucionales — bandera de Venezuela
    | Amarillo · Azul · Rojo
    */
    'brand' => [
        'yellow' => '#FFCC00',
        'blue' => '#002776',
        'red' => '#CF142B',
        'logo' => 'images/visitantes-icon.png',
        'favicon' => 'images/favicon.png',
    ],

    /*
    | Disco Laravel para fotos de ingreso de Invitados (privadas).
    | local = storage/app/private · s3 = bucket AWS (producción Railway)
    */
    'invitado_fotos_disk' => env('INVITADO_FOTOS_DISK', 'local'),

    /*
    | Módulos opcionales. Desactivados por defecto: centros de acopio,
    | inventario, requerimientos y entregas.
    */
    'features' => [
        'logistica' => env('VISITANTES_LOGISTICA', false),
    ],
];
