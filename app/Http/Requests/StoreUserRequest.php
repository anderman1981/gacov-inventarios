<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreUserRequest extends FormRequest
{
    /**
     * La autorización se maneja en el controlador con permisos Spatie.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para crear un usuario.
     */
    public function rules(): array
    {
        return [
            'name'                 => ['required', 'string', 'max:100'],
            'email'                => ['required', 'email', 'unique:users,email'],
            'password'             => ['required', 'string', 'min:8', 'confirmed'],
            'phone'                => ['nullable', 'string', 'max:20'],
            'role'                 => ['required', Rule::in(['super_admin', 'admin', 'manager', 'contador', 'conductor'])],
            'route_id'             => ['nullable', 'exists:routes,id'],
            'is_active'            => ['boolean'],
            'must_change_password' => ['boolean'],
        ];
    }

    /**
     * Mensajes de validación personalizados en español colombiano.
     */
    public function messages(): array
    {
        return [
            'name.required'          => 'El nombre es obligatorio.',
            'name.max'               => 'El nombre no puede superar los 100 caracteres.',
            'email.required'         => 'El correo electrónico es obligatorio.',
            'email.email'            => 'Ingrese un correo electrónico válido.',
            'email.unique'           => 'Este correo ya está registrado para otro usuario.',
            'password.required'      => 'La contraseña es obligatoria.',
            'password.min'           => 'La contraseña debe tener mínimo 8 caracteres.',
            'password.confirmed'     => 'La confirmación de contraseña no coincide.',
            'phone.max'              => 'El teléfono no puede superar los 20 caracteres.',
            'role.required'          => 'Debe seleccionar un rol.',
            'role.in'                => 'El rol seleccionado no es válido.',
            'route_id.exists'        => 'La ruta seleccionada no existe.',
        ];
    }

    /**
     * Prepara los datos antes de la validación.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active'            => $this->boolean('is_active'),
            'must_change_password' => $this->boolean('must_change_password'),
        ]);
    }

    /**
     * Valida que si el rol es conductor, se debe seleccionar una ruta.
     */
    public function withValidator(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Contracts\Validation\Validator $v) {
            if ($this->input('role') === 'conductor' && empty($this->input('route_id'))) {
                $v->errors()->add('route_id', 'La ruta es obligatoria para el rol de conductor.');
            }
        });
    }
}
