<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para pagos de factura.
 */
final class InvoicePaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,

            // Pago
            'amount' => $this->amount,
            'amount_formatted' => $this->amount_formatted,
            'payment_date' => $this->payment_date?->format('Y-m-d'),
            'payment_method' => $this->payment_method,
            'method_label' => $this->method_label,
            'reference' => $this->reference,
            'notes' => $this->notes,

            // Usuario que registró
            'recorded_by' => $this->recorded_by,
            'recorder' => $this->whenLoaded('recorder', fn () => [
                'id' => $this->recorder->id,
                'name' => $this->recorder->name,
            ]),

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
