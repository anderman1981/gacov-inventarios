<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\User\Enums\UserRole;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) auth()->user()?->can('users.edit');
    }

    /**
     * Reglas de validación para actualizar un usuario.
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in($this->allowedRoles())],
            'route_id' => ['nullable', 'exists:routes,id'],
            'is_active' => ['boolean'],
            'must_change_password' => ['boolean'],
        ];
    }

    /**
     * Roles asignables según el nivel del usuario que realiza la acción.
     * Solo super_admin puede promover a otros super_admins.
     *
     * @return list<string>
     */
    private function allowedRoles(): array
    {
        $roles = UserRole::tenantAssignable();

        if (auth()->user()?->is_super_admin) {
            $roles[] = UserRole::SUPER_ADMIN->value;
        }

        return $roles;
    }

    /**
     * Mensajes de validación personalizados en español colombiano.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 100 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingrese un correo electrónico válido.',
            'email.unique' => 'Este correo ya está registrado para otro usuario.',
            'password.min' => 'La contraseña debe tener mínimo 8 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'phone.max' => 'El teléfono no puede superar los 20 caracteres.',
            'role.required' => 'Debe seleccionar un rol.',
            'role.in' => 'El rol seleccionado no es válido.',
            'route_id.exists' => 'La ruta seleccionada no existe.',
        ];
    }

    /**
     * Prepara los datos antes de la validación.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'must_change_password' => $this->boolean('must_change_password'),
        ]);
    }

    /**
     * Valida que si el rol es conductor, se debe seleccionar una ruta.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($this->input('role') === 'conductor' && empty($this->input('route_id'))) {
                $v->errors()->add('route_id', 'La ruta es obligatoria para el rol de conductor.');
            }
        });
    }
}
