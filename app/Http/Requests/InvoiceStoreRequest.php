<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para crear/editar facturas.
 */
final class InvoiceStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La autorización se maneja en el controller
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Datos de factura
            'prefix' => ['nullable', 'string', 'max:10'],
            'issue_date' => ['required', 'date', 'date_format:Y-m-d'],
            'due_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:issue_date'],
            'status' => ['nullable', 'in:draft,issued'],

            // Emisor
            'issuer_name' => ['required', 'string', 'max:200'],
            'issuer_nit' => ['required', 'string', 'max:50'],
            'issuer_address' => ['nullable', 'string', 'max:500'],
            'issuer_phone' => ['nullable', 'string', 'max:50'],
            'issuer_email' => ['nullable', 'email', 'max:100'],

            // Cliente
            'client_name' => ['required', 'string', 'max:200'],
            'client_nit' => ['required', 'string', 'max:50'],
            'client_address' => ['nullable', 'string', 'max:500'],
            'client_email' => ['nullable', 'email', 'max:100'],
            'client_phone' => ['nullable', 'string', 'max:50'],

            // Impuestos y descuentos
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],

            // Notas
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],

            // Items
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.product_key' => ['nullable', 'string', 'max:100'],
            'items.*.unit' => ['nullable', 'string', 'max:20'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.billing_period' => ['nullable', 'string', 'max:50'],
            'items.*.module_key' => ['nullable', 'string', 'max:100'],
            'items.*.plan_name' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'issuer_name.required' => 'El nombre del emisor es requerido.',
            'issuer_nit.required' => 'El NIT del emisor es requerido.',
            'client_name.required' => 'El nombre del cliente es requerido.',
            'client_nit.required' => 'El NIT del cliente es requerido.',
            'items.required' => 'Debe agregar al menos un ítem a la factura.',
            'items.min' => 'Debe agregar al menos un ítem a la factura.',
            'items.*.description.required' => 'La descripción de cada ítem es requerida.',
            'items.*.quantity.required' => 'La cantidad es requerida.',
            'items.*.unit_price.required' => 'El precio unitario es requerido.',
        ];
    }
}
