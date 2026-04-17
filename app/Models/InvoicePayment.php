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
 * Pago de factura.
 *
 * @property int $id
 * @property int $invoice_id
 * @property int $tenant_id
 * @property float $amount
 * @property Carbon $payment_date
 * @property string|null $payment_method
 * @property string|null $reference
 * @property string|null $notes
 * @property int|null $recorded_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class InvoicePayment extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'invoice_id',
        'tenant_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public const METHOD_TRANSFER = 'transferencia';

    public const METHOD_PSE = 'pse';

    public const METHOD_CASH = 'efectivo';

    public const METHOD_CARD = 'tarjeta';

    public const METHOD_OTHER = 'otro';

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeForInvoice(Builder $query, Invoice $invoice): Builder
    {
        return $query->where('invoice_id', $invoice->id);
    }

    /**
     * Obtiene el monto formateado.
     */
    public function getAmountFormattedAttribute(): string
    {
        return '$'.number_format((float) $this->amount, 2);
    }

    /**
     * Obtiene el método de pago formateado.
     */
    public function getMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            self::METHOD_TRANSFER => 'Transferencia',
            self::METHOD_PSE => 'PSE',
            self::METHOD_CASH => 'Efectivo',
            self::METHOD_CARD => 'Tarjeta',
            default => 'Otro',
        };
    }
}
