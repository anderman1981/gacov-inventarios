<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

final class ReassignRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->can('drivers.assign_routes') ?? false;
    }

    public function rules(): array
    {
        return [
            'route_id' => ['required', 'integer', 'exists:routes,id'],
            'target_driver_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                if (! $this->filled('target_driver_id')) {
                    return;
                }

                $driver = User::query()
                    ->where('tenant_id', $this->user()?->tenant_id)
                    ->whereKey($this->integer('target_driver_id'))
                    ->first();

                if (! $driver?->hasRole('conductor')) {
                    $validator->errors()->add('target_driver_id', 'Solo puedes asignar rutas a usuarios con rol de conductor.');

                    return;
                }

                if (! $driver->is_active) {
                    $validator->errors()->add('target_driver_id', 'El conductor seleccionado está inactivo.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'route_id.required' => 'Debes seleccionar una ruta para reasignar.',
            'route_id.exists' => 'La ruta seleccionada no existe.',
            'target_driver_id.exists' => 'El conductor seleccionado no existe.',
        ];
    }
}
