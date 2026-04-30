<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Tenant\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PurchaseImportBatch extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'uploaded_by',
        'warehouse_id',
        'original_file_name',
        'stored_file_path',
        'status',
        'supplier',
        'invoice_number',
        'purchase_date',
        'total_rows',
        'valid_rows',
        'error_rows',
        'total_units',
        'total_cost',
        'processed_at',
        'processed_by',
        'discarded_at',
        'discarded_by',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'total_rows' => 'integer',
            'valid_rows' => 'integer',
            'error_rows' => 'integer',
            'total_units' => 'integer',
            'total_cost' => 'decimal:2',
            'processed_at' => 'datetime',
            'discarded_at' => 'datetime',
        ];
    }

    public function rows(): HasMany
    {
        return $this->hasMany(PurchaseImportRow::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function discarder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'discarded_by');
    }

    public function canBeConfirmed(): bool
    {
        return $this->status === 'borrador'
            && (int) $this->valid_rows > 0
            && (int) $this->error_rows === 0;
    }
}
