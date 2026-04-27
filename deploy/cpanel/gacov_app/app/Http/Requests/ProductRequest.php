<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ProductRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true; // La autorización se maneja en el controlador con policies/permisos
    }

    /**
     * Reglas de validación para el formulario de productos.
     */
    public function rules(): array
    {
        $product = $this->route('product');
        $productId = is_object($product) ? $product->id : (is_numeric($product) ? (int) $product : null);

        return [
            'name' => ['required', 'string', 'max:150'],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('products', 'code')->ignore($productId),
            ],
            'worldoffice_code' => ['nullable', 'string', 'max:20'],
            'category' => ['required', Rule::in(['bebida_fria', 'bebida_caliente', 'snack', 'insumo', 'otro'])],
            'unit_of_measure' => ['required', Rule::in(['Und.', 'Kg', 'Lt', 'Caja', 'Paquete', 'Bolsa'])],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'min_sale_price' => ['nullable', 'numeric', 'min:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'min_stock_alert' => ['nullable', 'numeric', 'min:0'],
            'supplier' => ['nullable', 'string', 'max:150'],
            'supplier_sku' => ['nullable', 'string', 'max:60'],
            'expiration_date' => ['nullable', 'date'],
            'purchase_date' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Mensajes de validación personalizados en español colombiano.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del producto es obligatorio.',
            'name.max' => 'El nombre no puede superar los 150 caracteres.',
            'code.required' => 'El código o SKU es obligatorio.',
            'code.max' => 'El código o SKU no puede superar los 20 caracteres.',
            'code.unique' => 'Este código o SKU ya está registrado para otro producto.',
            'worldoffice_code.max' => 'El código WorldOffice no puede superar los 20 caracteres.',
            'category.required' => 'Debe seleccionar una categoría.',
            'category.in' => 'La categoría seleccionada no es válida.',
            'unit_of_measure.required' => 'Debe seleccionar una unidad de medida.',
            'unit_of_measure.in' => 'La unidad seleccionada no es válida.',
            'cost.numeric' => 'El costo debe ser un número.',
            'cost.min' => 'El costo no puede ser negativo.',
            'min_sale_price.numeric' => 'El precio de venta mínimo debe ser un número.',
            'min_sale_price.min' => 'El precio de venta mínimo no puede ser negativo.',
            'unit_price.required' => 'El precio de venta es obligatorio.',
            'unit_price.numeric' => 'El precio de venta debe ser un número.',
            'unit_price.min' => 'El precio de venta no puede ser negativo.',
            'min_stock_alert.numeric' => 'La alerta mínima debe ser un número.',
            'min_stock_alert.min' => 'La alerta mínima no puede ser negativa.',
            'supplier.max' => 'El proveedor no puede superar los 150 caracteres.',
            'supplier_sku.max' => 'El SKU del proveedor no puede superar los 60 caracteres.',
            'expiration_date.date' => 'La fecha de vencimiento no es válida.',
            'purchase_date.date' => 'La fecha de compra no es válida.',
        ];
    }

    /**
     * Prepara los datos antes de la validación.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
