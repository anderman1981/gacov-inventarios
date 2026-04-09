<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ImportInitialInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inventory_file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv,txt',
                'max:10240',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'inventory_file.required' => 'Debes seleccionar un archivo para importar.',
            'inventory_file.file' => 'El archivo seleccionado no es válido.',
            'inventory_file.mimes' => 'El archivo debe estar en formato Excel o CSV.',
            'inventory_file.max' => 'El archivo no puede superar los 10 MB.',
        ];
    }
}
