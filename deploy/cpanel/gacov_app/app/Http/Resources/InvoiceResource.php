<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para API de facturas.
 */
final class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_number' => $this->full_number,
            'prefix' => $this->prefix,
            'number' => $this->number,

            // Fechas
            'issue_date' => $this->issue_date?->format('Y-m-d'),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'paid_at' => $this->paid_at?->format('Y-m-d'),

            // Estados
            'status' => $this->status,
            'status_label' => $this->status_label,
            'payment_status' => $this->payment_status,
            'payment_status_label' => $this->payment_status_label,

            // Totales
            'subtotal' => $this->subtotal,
            'tax_rate' => $this->tax_rate,
            'tax_amount' => $this->tax_amount,
            'discount_amount' => $this->discount_amount,
            'total' => $this->total,
            'paid_amount' => $this->paid_amount,
            'balance_due' => $this->balance_due,

            // Formatted
            'total_formatted' => '$'.number_format((float) $this->total, 2),
            'paid_amount_formatted' => '$'.number_format((float) $this->paid_amount, 2),
            'balance_due_formatted' => '$'.number_format((float) $this->balance_due, 2),

            // Emisor
            'issuer' => [
                'name' => $this->issuer_name,
                'nit' => $this->issuer_nit,
                'address' => $this->issuer_address,
                'phone' => $this->issuer_phone,
                'email' => $this->issuer_email,
            ],

            // Cliente
            'client' => [
                'name' => $this->client_name,
                'nit' => $this->client_nit,
                'address' => $this->client_address,
                'email' => $this->client_email,
                'phone' => $this->client_phone,
                'display_name' => $this->client_display_name,
            ],

            // DIAN
            'dian' => [
                'sequential_code' => $this->dian_sequential_code,
                'resolution_number' => $this->dian_resolution_number,
                'from_date' => $this->dian_from_date?->format('Y-m-d'),
                'to_date' => $this->dian_to_date?->format('Y-m-d'),
            ],

            // Notas
            'notes' => $this->notes,
            'terms' => $this->terms,

            // Relaciones
            'tenant_id' => $this->tenant_id,
            'user_id' => $this->user_id,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),

            // Items y pagos
            'items_count' => $this->whenCounted('items'),
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'payments' => InvoicePaymentResource::collection($this->whenLoaded('payments')),

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
