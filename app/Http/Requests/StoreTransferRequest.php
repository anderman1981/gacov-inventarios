<?php
declare(strict_types=1);
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'origin_warehouse_id'      => ['required', 'integer', 'exists:warehouses,id'],
            'destination_warehouse_id' => ['required', 'integer', 'exists:warehouses,id', 'different:origin_warehouse_id'],
            'notes'                    => ['nullable', 'string', 'max:500'],
            'items'                    => ['required', 'array'],
            'items.*.product_id'       => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity_requested' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'origin_warehouse_id.required'           => 'Debes seleccionar la bodega origen.',
            'origin_warehouse_id.exists'             => 'La bodega origen no existe.',
            'destination_warehouse_id.required'      => 'Debes seleccionar la bodega destino.',
            'destination_warehouse_id.exists'        => 'La bodega destino no existe.',
            'destination_warehouse_id.different'     => 'La bodega destino debe ser diferente a la bodega origen.',
            'items.required'                         => 'Debes incluir al menos un producto.',
            'items.*.product_id.required'            => 'El producto es requerido.',
            'items.*.product_id.exists'              => 'El producto seleccionado no existe.',
            'items.*.quantity_requested.required'    => 'La cantidad es requerida.',
            'items.*.quantity_requested.integer'     => 'La cantidad debe ser un número entero.',
            'items.*.quantity_requested.min'         => 'La cantidad no puede ser negativa.',
        ];
    }
}
