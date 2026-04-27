<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Tenant\Traits\BelongsToTenant;
use App\Support\Logging\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TransferOrder extends Model
{
    use BelongsToTenant, HasFactory, LogsActivity;

    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (TransferOrder $order): void {
            if (empty($order->code)) {
                $order->code = self::generateCode();
            }
        });
    }

    /**
     * Genera un código único para el traslado.
     */
    public static function generateCode(): string
    {
        $prefix = 'TRF';
        $date = now()->format('ymd');
        $random = strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 4));

        return "{$prefix}-{$date}-{$random}";
    }

    /** Attributes to log for activity tracking */
    protected static array $activityLogAttributes = [
        'code',
        'status',
        'notes',
    ];

    protected static ?string $activityLogName = 'transfers';

    protected $fillable = [
        'code',
        'origin_warehouse_id',
        'destination_warehouse_id',
        'status',
        'requested_by',
        'approved_by',
        'completed_by',
        'notes',
        'approved_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function originWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'origin_warehouse_id');
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransferOrderItem::class);
    }
}
