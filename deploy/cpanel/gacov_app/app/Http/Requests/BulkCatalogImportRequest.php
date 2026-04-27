<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class BulkCatalogImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'import_file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt'],
        ];
    }

    public function messages(): array
    {
        return [
            'import_file.required' => 'Debes seleccionar un archivo para importar.',
            'import_file.file' => 'El archivo seleccionado no es válido.',
            'import_file.mimes' => 'Solo se permiten archivos Excel o CSV.',
        ];
    }
}
