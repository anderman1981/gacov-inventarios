<?php
declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Route extends Model
{
    protected $fillable = [
        'name', 'code', 'driver_user_id', 'vehicle_plate', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function machines(): HasMany
    {
        return $this->hasMany(Machine::class);
    }
}
