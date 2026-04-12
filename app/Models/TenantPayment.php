<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TenantPayment extends Model
{
    public const TYPE_LABELS = [
        'development' => 'Desarrollo',
        'operations' => 'Mensualidad operativa',
        'extra_hours' => 'Horas adicionales',
        'extraordinary' => 'Cobro extraordinario',
    ];

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'recorded_by_user_id',
        'type',
        'phase',
        'description',
        'invoice_number',
        'amount',
        'counts_toward_project_total',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'phase' => 'integer',
        'amount' => 'decimal:2',
        'counts_toward_project_total' => 'boolean',
        'paid_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    /**
     * @return array<string,string>
     */
    public static function typeOptions(): array
    {
        return self::TYPE_LABELS;
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? ucfirst($this->type);
    }
}
