<?php

declare(strict_types=1);

return [
    'empresa_nombre' => env('GACOV_EMPRESA_NOMBRE', 'INVERSIONES GACOV S.A.S.'),
    'empresa_nit' => env('GACOV_EMPRESA_NIT', '900983146'),
    'wo_tipo_documento' => env('GACOV_WO_TIPO_DOCUMENTO', 'EA'),
    'wo_nota_traslado' => env('GACOV_WO_NOTA_TRASLADO', 'Entrada por Traslado'),
    'upload_max_mb' => (int) env('GACOV_UPLOAD_MAX_MB', 10),
    'offline' => [
        'cache_version' => 'v1',
        'token_hours' => 24,
        'sync_endpoint' => '/api/stocking/sync',
        'data_endpoint' => '/api/sync/my-data',
    ],
    /*
     | Roles del sistema
     | super_admin : acceso total + desarrollo y módulos
     | admin       : gestión completa del aplicativo (users, roles, productos, categorías)
     | manager     : gestión operativa + crear productos y máquinas + reportes
     | contador    : solo lectura de cantidades + exportar reportes WorldOffice
     | conductor   : ver ruta asignada, surtir máquinas, registrar ventas
     */
    'roles' => [
        'super_admin',
        'admin',
        'manager',
        'contador',
        'conductor',
    ],

    'movement_types' => [
        'carga_inicial',    // carga desde Excel al iniciar
        'ajuste_manual',    // ajuste con motivo en bodega
        'traslado_salida',  // sale de bodega hacia vehículo
        'traslado_entrada', // llega al vehículo desde bodega
        'surtido_maquina',  // vehículo → máquina (aumenta inventario máquina)
        'venta_maquina',    // venta registrada en máquina (descuenta inventario máquina)
        'conteo_fisico',    // ajuste por diferencia en conteo físico
        'exportado_wo',     // marcado como exportado a WorldOffice
    ],
];
