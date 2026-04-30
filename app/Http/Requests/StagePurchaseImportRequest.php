<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StagePurchaseImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->can('inventory.load_excel') ?? false;
    }

    public function rules(): array
    {
        return [
            'purchase_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'supplier' => ['nullable', 'string', 'max:150'],
            'invoice_number' => ['nullable', 'string', 'max:80'],
            'purchase_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'purchase_file.required' => 'Debes seleccionar el CSV de compra.',
            'purchase_file.file' => 'El archivo seleccionado no es válido.',
            'purchase_file.mimes' => 'La compra debe cargarse en formato CSV.',
            'purchase_file.max' => 'El archivo no puede superar los 10 MB.',
            'purchase_date.date' => 'La fecha de compra no es válida.',
        ];
    }
}
