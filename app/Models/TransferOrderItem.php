<?php
declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TransferOrderItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'transfer_order_id',
        'product_id',
        'quantity_requested',
        'quantity_dispatched',
        'quantity_received',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_requested'  => 'integer',
            'quantity_dispatched' => 'integer',
            'quantity_received'   => 'integer',
        ];
    }

    public function transferOrder(): BelongsTo
    {
        return $this->belongsTo(TransferOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
