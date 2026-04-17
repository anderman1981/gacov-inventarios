<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->can('drivers.assign_routes') ?? false;
    }

    public function rules(): array
    {
        $tenantId = $this->user()?->tenant_id;
        $routeId = $this->route('route');

        return [
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required',
                'string',
                'max:40',
                Rule::unique('routes', 'code')
                    ->ignore(is_numeric($routeId) ? (int) $routeId : null)
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'vehicle_plate' => ['nullable', 'string', 'max:40'],
            'driver_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Debes indicar el nombre de la ruta.',
            'code.required' => 'Debes indicar el ID de la ruta.',
            'code.unique' => 'Ya existe otra ruta con ese ID en este cliente.',
            'driver_user_id.exists' => 'El conductor seleccionado no existe en este cliente.',
        ];
    }
}
