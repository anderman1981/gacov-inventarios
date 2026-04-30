<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Warehouse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'origin_warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')->where(function ($query): void {
                    $query->whereIn('type', ['bodega', 'vehiculo']);
                }),
            ],
            'destination_route_warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')->where(function ($query): void {
                    $query->where('type', 'vehiculo');
                }),
            ],
            'destination_machine_warehouse_id' => [
                'nullable',
                'integer',
                Rule::exists('warehouses', 'id')->where(function ($query): void {
                    $query->where('type', 'maquina');
                }),
            ],
            'destination_warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')->where(function ($query): void {
                    $query->whereIn('type', ['vehiculo', 'maquina']);
                }),
                'different:origin_warehouse_id',
            ],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity_requested' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'origin_warehouse_id.required' => 'Debes seleccionar la bodega origen.',
            'origin_warehouse_id.exists' => 'La bodega origen no existe.',
            'destination_route_warehouse_id.required' => 'Debes seleccionar la ruta o vehículo destino.',
            'destination_route_warehouse_id.exists' => 'La ruta o vehículo destino no existe.',
            'destination_machine_warehouse_id.exists' => 'La máquina destino no existe.',
            'destination_warehouse_id.required' => 'Debes seleccionar la bodega destino.',
            'destination_warehouse_id.exists' => 'La bodega destino no existe.',
            'destination_warehouse_id.different' => 'La bodega destino debe ser diferente a la bodega origen.',
            'items.required' => 'Debes incluir al menos un producto.',
            'items.*.product_id.required' => 'El producto es requerido.',
            'items.*.product_id.exists' => 'El producto seleccionado no existe.',
            'items.*.quantity_requested.required' => 'La cantidad es requerida.',
            'items.*.quantity_requested.integer' => 'La cantidad debe ser un número entero.',
            'items.*.quantity_requested.min' => 'La cantidad no puede ser negativa.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $destinationWarehouseId = $this->input('destination_machine_warehouse_id')
            ?: $this->input('destination_route_warehouse_id');

        $this->merge([
            'destination_warehouse_id' => $destinationWarehouseId,
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $routeWarehouseId = $this->integer('destination_route_warehouse_id');
            $machineWarehouseId = $this->integer('destination_machine_warehouse_id');

            if (! $routeWarehouseId || ! $machineWarehouseId) {
                return;
            }

            $routeWarehouse = Warehouse::query()->find($routeWarehouseId);
            $machineWarehouse = Warehouse::query()->with('machine')->find($machineWarehouseId);

            if (! $routeWarehouse || ! $machineWarehouse || ! $machineWarehouse->machine) {
                return;
            }

            if ((int) $routeWarehouse->route_id !== (int) $machineWarehouse->machine->route_id) {
                $validator->errors()->add('destination_machine_warehouse_id', 'La máquina seleccionada no pertenece a la ruta o vehículo elegido.');
            }
        });
    }
}
