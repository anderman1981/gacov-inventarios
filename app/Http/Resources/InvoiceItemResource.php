<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para ítems de factura.
 */
final class InvoiceItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,

            // Concepto
            'description' => $this->description,
            'product_key' => $this->product_key,
            'unit' => $this->unit,

            // Cantidades
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'discount_rate' => $this->discount_rate,

            // Totales
            'subtotal' => $this->subtotal,
            'tax_rate' => $this->tax_rate,
            'tax_amount' => $this->tax_amount,
            'total' => $this->total,

            // Formatted
            'unit_price_formatted' => $this->unit_price_formatted,
            'total_formatted' => $this->total_formatted,

            // Servicio
            'billing_period' => $this->billing_period,
            'service_start' => $this->service_start?->format('Y-m-d'),
            'service_end' => $this->service_end?->format('Y-m-d'),

            // Módulo/Plan
            'module_key' => $this->module_key,
            'plan_name' => $this->plan_name,

            // Orden
            'sort_order' => $this->sort_order,
        ];
    }
}
