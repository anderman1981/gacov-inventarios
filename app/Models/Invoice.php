<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Tenant\Traits\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Factura formal para GACOV SaaS.
 *
 * @property int $id
 * @property string $prefix
 * @property int $number
 * @property string $full_number
 * @property Carbon $issue_date
 * @property Carbon|null $due_date
 * @property Carbon|null $paid_at
 * @property string $status
 * @property string $payment_status
 * @property float $subtotal
 * @property float $tax_rate
 * @property float $tax_amount
 * @property float $discount_amount
 * @property float $total
 * @property float $paid_amount
 * @property float $balance_due
 * @property int $tenant_id
 * @property int|null $user_id
 * @property int|null $created_by
 * @property string $issuer_name
 * @property string $issuer_nit
 * @property string|null $issuer_address
 * @property string|null $issuer_phone
 * @property string|null $issuer_email
 * @property string|null $issuer_logo_path
 * @property string $client_name
 * @property string $client_nit
 * @property string|null $client_address
 * @property string|null $client_email
 * @property string|null $client_phone
 * @property string|null $notes
 * @property string|null $terms
 * @property string|null $dian_sequential_code
 * @property string|null $dian_resolution_number
 * @property Carbon|null $dian_from_date
 * @property Carbon|null $dian_to_date
 * @property string|null $pdf_path
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class Invoice extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'prefix',
        'number',
        'full_number',
        'issue_date',
        'due_date',
        'paid_at',
        'status',
        'payment_status',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'discount_amount',
        'total',
        'paid_amount',
        'balance_due',
        'tenant_id',
        'user_id',
        'created_by',
        'issuer_name',
        'issuer_nit',
        'issuer_address',
        'issuer_phone',
        'issuer_email',
        'issuer_logo_path',
        'client_name',
        'client_nit',
        'client_address',
        'client_email',
        'client_phone',
        'notes',
        'terms',
        'dian_sequential_code',
        'dian_resolution_number',
        'dian_from_date',
        'dian_to_date',
        'pdf_path',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'date',
        'dian_from_date' => 'date',
        'dian_to_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_due' => 'decimal:2',
    ];

    // ==================== CONSTANTES ====================

    public const PREFIX = 'INV';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ISSUED = 'issued';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    public const PAYMENT_PENDING = 'pending';

    public const PAYMENT_PARTIAL = 'partial';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_OVERDUE = 'overdue';

    // ==================== RELACIONES ====================

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    // ==================== SCOPES ====================

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeIssued(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ISSUED);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('payment_status', self::PAYMENT_PENDING);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('payment_status', self::PAYMENT_OVERDUE);
    }

    public function scopeForTenant(Builder $query, Tenant $tenant): Builder
    {
        return $query->where('tenant_id', $tenant->id);
    }

    // ==================== MÉTODOS ====================

    /**
     * Genera el siguiente número de factura para el tenant.
     */
    public static function generateNumber(Tenant $tenant): string
    {
        $year = date('Y');
        $prefix = self::PREFIX.'-'.$year.'-';

        $lastInvoice = self::query()
            ->where('tenant_id', $tenant->id)
            ->where('prefix', 'LIKE', self::PREFIX.'-'.$year.'%')
            ->orderByDesc('number')
            ->first();

        $nextNumber = $lastInvoice ? $lastInvoice->number + 1 : 1;

        return $prefix.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Calcula totales desde los items.
     */
    public function calculateTotals(): void
    {
        $items = $this->items;

        $this->subtotal = $items->sum('subtotal');
        $this->tax_amount = $items->sum('tax_amount');
        $this->total = $this->subtotal + $this->tax_amount - (float) $this->discount_amount;
        $this->balance_due = $this->total - (float) $this->paid_amount;

        if ($this->balance_due <= 0) {
            $this->payment_status = self::PAYMENT_PAID;
            $this->status = self::STATUS_PAID;
        } elseif ($this->paid_amount > 0) {
            $this->payment_status = self::PAYMENT_PARTIAL;
        }
    }

    /**
     * Agrega un item a la factura.
     */
    public function addItem(array $data): InvoiceItem
    {
        $item = $this->items()->create(array_merge($data, [
            'tenant_id' => $this->tenant_id,
            'sort_order' => $this->items()->max('sort_order') + 1,
        ]));

        $item->calculateTotals();
        $this->calculateTotals();
        $this->save();

        return $item;
    }

    /**
     * Registra un pago.
     */
    public function registerPayment(float $amount, string $method, ?string $reference = null): InvoicePayment
    {
        return DB::transaction(function () use ($amount, $method, $reference): InvoicePayment {
            $payment = $this->payments()->create([
                'tenant_id' => $this->tenant_id,
                'amount' => $amount,
                'payment_date' => now(),
                'payment_method' => $method,
                'reference' => $reference,
            ]);

            $this->paid_amount = (float) $this->paid_amount + $amount;
            $this->calculateTotals();
            $this->save();

            return $payment;
        });
    }

    /**
     * Emite la factura (cambia status a issued).
     */
    public function issue(): void
    {
        if ($this->status !== self::STATUS_DRAFT) {
            throw new \Exception('Solo se pueden emitir facturas en estado draft');
        }

        $this->status = self::STATUS_ISSUED;
        $this->issue_date = now();
        $this->save();
    }

    /**
     * Cancela la factura.
     */
    public function cancel(): void
    {
        if ($this->status === self::STATUS_PAID) {
            throw new \Exception('No se pueden cancelar facturas pagadas');
        }

        $this->status = self::STATUS_CANCELLED;
        $this->save();
    }

    /**
     * Verifica si está vencida.
     */
    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_ISSUED
            && $this->due_date !== null
            && $this->due_date->isPast()
            && $this->balance_due > 0;
    }

    /**
     * Genera el PDF de la factura.
     */
    public function generatePdf(): string
    {
        // Placeholder - implementar con DomPDF
        return storage_path('app/invoices/'.$this->full_number.'.pdf');
    }

    /**
     * Obtiene el nombre del cliente formateado.
     */
    public function getClientDisplayNameAttribute(): string
    {
        return $this->client_name.' ('.$this->client_nit.')';
    }

    /**
     * Obtiene el estado formateado.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Borrador',
            self::STATUS_ISSUED => 'Emitida',
            self::STATUS_PAID => 'Pagada',
            self::STATUS_CANCELLED => 'Cancelada',
            self::STATUS_EXPIRED => 'Vencida',
            default => 'Desconocido',
        };
    }

    /**
     * Obtiene el estado de pago formateado.
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_PENDING => 'Pendiente',
            self::PAYMENT_PARTIAL => 'Parcial',
            self::PAYMENT_PAID => 'Pagado',
            self::PAYMENT_OVERDUE => 'Vencido',
            default => 'Desconocido',
        };
    }
}
