<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CompleteTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Items are optional - controller uses existing transfer items
            'items' => ['nullable', 'array'],
            'items.*.quantity_received' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Debes registrar las cantidades recibidas.',
            'items.*.quantity_received.required' => 'La cantidad recibida es requerida.',
            'items.*.quantity_received.integer' => 'La cantidad recibida debe ser un número entero.',
            'items.*.quantity_received.min' => 'La cantidad recibida no puede ser negativa.',
        ];
    }
}
