<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

final class RoutesTemplateExport implements FromArray, WithHeadings, WithTitle
{
    public function headings(): array
    {
        return [
            'codigo_ruta',
            'nombre_ruta',
            'placa_vehiculo',
            'email_conductor',
            'activa',
        ];
    }

    public function array(): array
    {
        return [
            ['R-01', 'Ruta Norte', 'ABC123', 'conductor1@empresa.com', '1'],
            ['R-02', 'Ruta Centro', 'XYZ987', '', '1'],
        ];
    }

    public function title(): string
    {
        return 'Rutas';
    }
}
