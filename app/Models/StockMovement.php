<?php
declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public $timestamps = false;
    const CREATED_AT = 'created_at';

    protected $fillable = [
        'movement_type', 'origin_warehouse_id', 'destination_warehouse_id',
        'product_id', 'quantity', 'unit_cost',
        'reference_code', 'notes', 'performed_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity'   => 'integer',
            'unit_cost'  => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'origin_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }
}
