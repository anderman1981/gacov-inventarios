<?php

declare(strict_types=1);

namespace App\Application\UseCase\Inventory;

use App\Models\Product;
use App\Models\PurchaseImportBatch;
use App\Models\PurchaseImportRow;
use Illuminate\Support\Carbon;
use RuntimeException;

final class EnsureMissingPurchaseProductHandler
{
    private const PRODUCT_CODE_MAX_LENGTH = 20;

    public function handle(
        PurchaseImportBatch $batch,
        PurchaseImportRow $row,
        string $productCode,
        ?string $productName,
        float $unitCost,
        ?string $supplier,
        ?Carbon $purchaseDate,
    ): Product {
        if ($productCode === '') {
            throw new RuntimeException('Para crear el producto, el código no puede estar vacío.');
        }

        if (mb_strlen($productCode) > self::PRODUCT_CODE_MAX_LENGTH) {
            throw new RuntimeException(
                "El código {$productCode} supera el máximo de ".self::PRODUCT_CODE_MAX_LENGTH.' caracteres para crear un producto nuevo.'
            );
        }

        $existingProduct = Product::query()
            ->where('code', $productCode)
            ->first();

        if ($existingProduct instanceof Product) {
            if ((bool) $existingProduct->is_active) {
                return $existingProduct;
            }

            throw new RuntimeException("El código {$productCode} ya existe como producto inactivo. Actívalo en Catálogo y vuelve a verificar.");
        }

        $newProduct = new Product();
        $newProduct->code = $productCode;
        $newProduct->name = $productName
            ?? $this->cleanNullableString($row->product_name)
            ?? "Producto {$productCode}";
        $newProduct->category = 'otro';
        $newProduct->unit_of_measure = 'Und.';
        $newProduct->cost = max($unitCost, 0);
        $newProduct->min_sale_price = max($unitCost, 0);
        $newProduct->unit_price = max($unitCost, 0);
        $newProduct->supplier = $supplier;
        $newProduct->purchase_date = $purchaseDate;
        $newProduct->is_active = true;

        if ($batch->tenant_id !== null) {
            $newProduct->tenant_id = (int) $batch->tenant_id;
        }

        $newProduct->save();
        $newProduct->refresh();

        return $newProduct;
    }

    private function cleanNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $cleaned = trim((string) $value);

        return $cleaned !== '' ? $cleaned : null;
    }
}
