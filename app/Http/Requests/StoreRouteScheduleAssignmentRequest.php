<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

final class StoreRouteScheduleAssignmentRequest extends FormRequest
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
            'assignment_date' => ['required', 'date'],
            'week_start' => ['nullable', 'date'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                if (! $this->filled('target_driver_id')) {
                    return;
                }

                $driver = User::query()->whereKey($this->integer('target_driver_id'))->first();

                if (! $driver?->hasRole('conductor')) {
                    $validator->errors()->add('target_driver_id', 'Solo puedes programar rutas para usuarios con rol de conductor.');

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
            'route_id.required' => 'Debes seleccionar una ruta.',
            'route_id.exists' => 'La ruta seleccionada no existe.',
            'target_driver_id.exists' => 'El conductor seleccionado no existe.',
            'assignment_date.required' => 'Debes indicar la fecha de la programación.',
            'assignment_date.date' => 'La fecha de programación no es válida.',
        ];
    }
}
