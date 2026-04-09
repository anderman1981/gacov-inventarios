<?php
declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Machine extends Model
{
    protected $fillable = [
        'code', 'worldoffice_code', 'name', 'location',
        'route_id', 'operator_user_id', 'type', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_user_id');
    }

    public function stockings(): HasMany
    {
        return $this->hasMany(MachineStockingRecord::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(MachineSale::class);
    }
}
