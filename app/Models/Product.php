<?php
declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Product extends Model
{
    protected $fillable = [
        'code',
        'worldoffice_code',
        'name',
        'category',
        'unit_of_measure',
        'unit_price',
        'min_stock_alert',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'unit_price'  => 'decimal:2',
            'min_stock_alert' => 'integer',
            'is_active'   => 'boolean',
        ];
    }

    protected function sku(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->code,
        );
    }

    protected function unit(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->unit_of_measure,
        );
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
