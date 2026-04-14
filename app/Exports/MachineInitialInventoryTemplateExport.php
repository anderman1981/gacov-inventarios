<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

final class MachineInitialInventoryTemplateExport implements FromArray, WithHeadings, WithTitle
{
    public function headings(): array
    {
        return [
            'codigo_maquina',
            'codigo_producto',
            'cantidad_inicial',
            'observaciones',
        ];
    }

    public function array(): array
    {
        return [
            ['M104', '124', '18', 'Carga inicial de la maquina M104'],
            ['M104', '63', '6', ''],
            ['M205', '220', '12', 'Inventario inicial sede norte'],
        ];
    }

    public function title(): string
    {
        return 'InventarioMaquinas';
    }
}
