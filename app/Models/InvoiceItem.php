<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Tenant\Traits\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ítem de factura.
 *
 * @property int $id
 * @property int $invoice_id
 * @property int $tenant_id
 * @property string $description
 * @property string|null $product_key
 * @property string $unit
 * @property float $quantity
 * @property float $unit_price
 * @property float $discount_rate
 * @property float $subtotal
 * @property float $tax_rate
 * @property float $tax_amount
 * @property float $total
 * @property string|null $billing_period
 * @property Carbon|null $service_start
 * @property Carbon|null $service_end
 * @property string|null $module_key
 * @property string|null $plan_name
 * @property int $sort_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class InvoiceItem extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'invoice_id',
        'tenant_id',
        'description',
        'product_key',
        'unit',
        'quantity',
        'unit_price',
        'discount_rate',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'billing_period',
        'service_start',
        'service_end',
        'module_key',
        'plan_name',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'service_start' => 'date',
        'service_end' => 'date',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeForInvoice(Builder $query, Invoice $invoice): Builder
    {
        return $query->where('invoice_id', $invoice->id);
    }

    /**
     * Calcula los totales del ítem.
     */
    public function calculateTotals(): void
    {
        $quantity = (float) $this->quantity;
        $unitPrice = (float) $this->unit_price;
        $discountRate = (float) $this->discount_rate;
        $taxRate = (float) $this->tax_rate;

        // Subtotal antes de descuento
        $subtotalBeforeDiscount = $quantity * $unitPrice;

        // Descuento
        $discountAmount = $subtotalBeforeDiscount * ($discountRate / 100);
        $this->subtotal = $subtotalBeforeDiscount - $discountAmount;

        // Impuesto
        $this->tax_amount = $this->subtotal * ($taxRate / 100);

        // Total
        $this->total = $this->subtotal + $this->tax_amount;

        $this->saveQuietly();
    }

    /**
     * Obtiene el precio formateado.
     */
    public function getUnitPriceFormattedAttribute(): string
    {
        return '$'.number_format((float) $this->unit_price, 2);
    }

    /**
     * Obtiene el total formateado.
     */
    public function getTotalFormattedAttribute(): string
    {
        return '$'.number_format((float) $this->total, 2);
    }
}
