<?php
declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MachineSaleItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'machine_sale_id', 'product_id', 'quantity_sold', 'unit_price', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_sold' => 'integer',
            'unit_price'    => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(MachineSale::class, 'machine_sale_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
