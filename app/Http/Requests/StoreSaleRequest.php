<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->can('sales.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'route_id' => ['nullable', 'integer', 'exists:routes,id'],
            'machine_id' => ['required', 'integer', 'exists:machines,id'],
            'items' => ['required', 'array'],
            'items.*.quantity' => ['nullable', 'integer', 'min:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'route_id.exists' => 'La ruta seleccionada no existe.',
            'machine_id.required' => 'Debe seleccionar una máquina.',
            'machine_id.exists' => 'La máquina seleccionada no existe.',
            'items.required' => 'Debe incluir los productos vendidos.',
        ];
    }
}
