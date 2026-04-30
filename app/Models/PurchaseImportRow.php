<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Tenant\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PurchaseImportRow extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'purchase_import_batch_id',
        'row_number',
        'product_id',
        'product_code',
        'product_name',
        'quantity',
        'unit_cost',
        'supplier',
        'invoice_number',
        'purchase_date',
        'notes',
        'status',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'row_number' => 'integer',
            'quantity' => 'integer',
            'unit_cost' => 'decimal:2',
            'purchase_date' => 'date',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PurchaseImportBatch::class, 'purchase_import_batch_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
