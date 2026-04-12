<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MachineStockingItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'stocking_record_id', 'product_id', 'quantity_loaded',
        'physical_count_before', 'physical_count_after', 'difference', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_loaded' => 'integer',
            'physical_count_before' => 'integer',
            'physical_count_after' => 'integer',
            'difference' => 'integer',
        ];
    }

    public function stockingRecord(): BelongsTo
    {
        return $this->belongsTo(MachineStockingRecord::class, 'stocking_record_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
