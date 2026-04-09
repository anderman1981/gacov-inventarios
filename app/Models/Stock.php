<?php
declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Stock extends Model
{
    protected $table = 'stock';
    public const CREATED_AT = null;
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'warehouse_id', 'product_id', 'quantity',
        'min_quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'min_quantity' => 'integer',
            'updated_at' => 'datetime',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
