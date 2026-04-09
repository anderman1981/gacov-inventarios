<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

final class InitialInventoryTemplateExport implements FromArray, WithHeadings, WithTitle
{
    public function headings(): array
    {
        return [
            'codigo_producto',
            'cantidad_inicial',
            'observaciones',
        ];
    }

    public function array(): array
    {
        return [
            ['5', '24', 'Carga inicial de referencia'],
            ['63', '12', 'Nevera principal'],
            ['110', '3', 'Producto a granel'],
        ];
    }

    public function title(): string
    {
        return 'InventarioInicial';
    }
}
