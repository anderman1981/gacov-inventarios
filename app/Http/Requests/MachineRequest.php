<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class MachineRequest extends FormRequest
{
    /**
     * La autorización se maneja en el controlador con permisos Spatie.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para el formulario de máquinas.
     */
    public function rules(): array
    {
        $machineId = $this->route('machine')?->id;

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('machines', 'code')->ignore($machineId),
            ],
            'worldoffice_code'  => ['nullable', 'string', 'max:20'],
            'name'              => ['required', 'string', 'max:150'],
            'location'          => ['nullable', 'string', 'max:255'],
            'route_id'          => ['nullable', 'integer', 'exists:routes,id'],
            'operator_user_id'  => ['nullable', 'integer', 'exists:users,id'],
            'type'              => ['nullable', 'string', 'max:50'],
            'is_active'         => ['boolean'],
        ];
    }

    /**
     * Mensajes de validación personalizados en español colombiano.
     */
    public function messages(): array
    {
        return [
            'code.required'    => 'El código de la máquina es obligatorio.',
            'code.max'         => 'El código no puede superar los 20 caracteres.',
            'code.unique'      => 'Este código ya está registrado para otra máquina.',
            'name.required'    => 'El nombre de la máquina es obligatorio.',
            'name.max'         => 'El nombre no puede superar los 150 caracteres.',
            'location.max'     => 'La ubicación no puede superar los 255 caracteres.',
            'worldoffice_code.max' => 'El código WorldOffice no puede superar los 20 caracteres.',
            'route_id.exists'      => 'La ruta seleccionada no existe.',
            'operator_user_id.exists' => 'El operador seleccionado no existe.',
            'type.max'         => 'El tipo no puede superar los 50 caracteres.',
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
