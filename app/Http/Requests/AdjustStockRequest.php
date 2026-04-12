<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->can('inventory.adjust') ?? false;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'new_quantity' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Debe seleccionar un producto.',
            'warehouse_id.required' => 'Debe seleccionar una bodega.',
            'new_quantity.required' => 'La nueva cantidad es obligatoria.',
            'new_quantity.min' => 'La cantidad no puede ser negativa.',
            'reason.required' => 'El motivo del ajuste es obligatorio.',
        ];
    }
}
