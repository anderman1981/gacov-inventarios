<?php
declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TransferOrder extends Model
{
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
            'approved_at'  => 'datetime',
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
