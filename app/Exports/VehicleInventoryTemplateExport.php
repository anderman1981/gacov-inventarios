<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

final class VehicleInventoryTemplateExport implements FromArray, WithHeadings, WithTitle
{
    public function headings(): array
    {
        return [
            'codigo_ruta',
            'codigo_producto',
            'cantidad_total',
            'observaciones',
        ];
    }

    public function array(): array
    {
        return [
            ['RT1', '124', '24', 'Carga inicial del vehículo Ruta 1'],
            ['RT1', '63', '12', ''],
            ['RT2', '110', '8', 'Ajuste por diferencia encontrada al cierre de ruta'],
        ];
    }

    public function title(): string
    {
        return 'InventarioVehiculos';
    }
}
