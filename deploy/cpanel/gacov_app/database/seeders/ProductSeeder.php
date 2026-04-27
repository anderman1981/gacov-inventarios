<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $products = [
            ['5', '5', 'BOLIQUESO', 'snack', 'Und.'],
            ['6', '6', 'CHEESE TRIZ QUESO', 'snack', 'Und.'],
            ['7', '7', 'CHOCLITOS', 'snack', 'Und.'],
            ['8', '8', 'GALLETA CHOKIES', 'snack', 'Und.'],
            ['14', '14', 'GTA MINICHIPS', 'snack', 'Und.'],
            ['16', '16', 'GALLETA TOSH', 'snack', 'Und.'],
            ['26', '26', 'GALLETA DUX QUESO', 'snack', 'Und.'],
            ['40', '40', 'CHOCOLATINA GOL', 'snack', 'Und.'],
            ['48', '48', 'MANI KRAKS', 'snack', 'Und.'],
            ['51', '51', 'BARRA TOSH', 'snack', 'Und.'],
            ['54', '54', 'TOSTIAREPAS', 'snack', 'Und.'],
            ['124', '124', 'COCOSETTE WAFER', 'snack', 'Und.'],
            ['130', '130', 'DORITOS MEGAQUESO', 'snack', 'Und.'],
            ['497', '497', 'MANI KRAKS LIMON', 'snack', 'Und.'],
            ['508', '508', 'CHOKS BOLITA', 'snack', 'Und.'],
            ['623', '623', 'MANI NATURAL 45G', 'snack', 'Und.'],
            ['687', '687', 'CHOCORAMO BARRITA', 'snack', 'Und.'],
            ['694', '694', 'MILO GALLETAS ANILLOS', 'snack', 'Und.'],
            ['705', '705', 'TOCINETAS', 'snack', 'Und.'],
            ['714', '714', 'GALLETA BISCOTTO', 'snack', 'Und.'],
            ['716', '716', 'GALLETA FESTIVAL', 'snack', 'Und.'],
            ['721', '721', 'WAFER JET MINI', 'snack', 'Und.'],
            ['722', '722', 'PAPAS BUNGA SURTIDAS 25GR', 'snack', 'Und.'],
            ['723', '723', 'BUNGA PLATANO VERDE 40GR', 'snack', 'Und.'],
            ['724', '724', 'BUNGA PLATANO MADURO 40GR', 'snack', 'Und.'],
            ['725', '725', 'PAPAS BUNGA PIMIENTA 25GR', 'snack', 'Und.'],
            ['730', '730', 'GOLDEN PAPAS 35GR', 'snack', 'Und.'],
            ['731', '731', 'GOLDEN PLATANOS 35GR', 'snack', 'Und.'],
            ['732', '732', 'PAN ROSCA 30GR (ROSCAS BUNGA)', 'snack', 'Und.'],
            ['63', '63', 'MALTA MINI 200ML', 'bebida_fria', 'Und.'],
            ['64', '64', 'MALTA 330ML', 'bebida_fria', 'Und.'],
            ['65', '65', 'AGUA PEQUEÑA 300ML', 'bebida_fria', 'Und.'],
            ['66', '66', 'AGUA 600ML', 'bebida_fria', 'Und.'],
            ['68', '68', 'SPORADE 500ML', 'bebida_fria', 'Und.'],
            ['73', '73', 'VIVE 100-380ML', 'bebida_fria', 'Und.'],
            ['82', '82', 'HIT 500ML', 'bebida_fria', 'Und.'],
            ['86', '86', 'MR TEA 500ML', 'bebida_fria', 'Und.'],
            ['87', '87', 'HIT CAJA 200ML', 'bebida_fria', 'Und.'],
            ['102', '102', 'COCA-COLA 250ML', 'bebida_fria', 'Und.'],
            ['104', '104', 'COCA-COLA 400ML', 'bebida_fria', 'Und.'],
            ['126', '126', 'SODA SCHWEPPES 400ML', 'bebida_fria', 'Und.'],
            ['317', '317', 'BRISA MANZANA 280ML', 'bebida_fria', 'Und.'],
            ['486', '486', 'VIVE 100 LATA 269ML', 'bebida_fria', 'Und.'],
            ['621', '621', 'GOMAS TROLLY', 'bebida_fria', 'Und.'],
            ['637', '637', 'BRISA MARACUYA-MANZANA 600ML', 'bebida_fria', 'Und.'],
            ['718', '718', 'SOTONICO INN TROPICAL 600ML', 'bebida_fria', 'Und.'],
            ['720', '720', 'AGUA SABORIZADA INN', 'bebida_fria', 'Und.'],
            ['795', '795', 'PASTEL AREQUIPE', 'bebida_fria', 'Und.'],
            ['110', '110', 'CAFÉ 2,5KG', 'insumo', 'Kg'],
            ['113', '113', 'LECHE VENDING 3000GR', 'insumo', 'Kg'],
            ['114', '114', 'CHOCOLATE CORONA 1000GR', 'insumo', 'Kg'],
            ['115', '115', 'CAPPUCCINO VAINILLA 1000GR', 'insumo', 'Kg'],
            ['117', '117', 'CAPPUCCINO AMARETTO', 'insumo', 'Kg'],
            ['118', '118', 'AROMATICA - TE JENGIBRE', 'insumo', 'Und.'],
            ['119', '119', 'VASO PLASTICO 7OZ 25UND', 'insumo', 'Und.'],
            ['121', '121', 'VASO CARTON 7OZ 25UND', 'insumo', 'Und.'],
            ['640', '640', 'CAFÉ 2,5KG (ALT)', 'insumo', 'Kg'],
            ['660', '660', 'AZUCAR BOLSA 2,5KG', 'insumo', 'Kg'],
            ['711', '711', 'MEZCLADORES 500', 'insumo', 'Und.'],
        ];
        foreach ($products as [$code,$wo,$name,$cat,$unit]) {
            DB::table('products')->updateOrInsert(['code' => $code], [
                'worldoffice_code' => $wo, 'name' => $name, 'category' => $cat,
                'unit_of_measure' => $unit, 'is_active' => true,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        $this->command->info(count($products).' productos cargados.');
    }
}
