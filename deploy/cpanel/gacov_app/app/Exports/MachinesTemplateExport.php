<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

final class MachinesTemplateExport implements FromArray, WithHeadings, WithTitle
{
    public function headings(): array
    {
        return [
            'codigo_maquina',
            'codigo_worldoffice',
            'nombre_maquina',
            'ubicacion',
            'codigo_ruta',
            'email_operador',
            'tipo',
            'activa',
        ];
    }

    public function array(): array
    {
        return [
            ['M-001', 'WO-001', 'Máquina Lobby', 'Recepción piso 1', 'R-01', '', 'mixta', '1'],
            ['M-002', '', 'Máquina Cafetería', 'Zona social', 'R-02', '', 'vending_cafe', '1'],
        ];
    }

    public function title(): string
    {
        return 'Maquinas';
    }
}
