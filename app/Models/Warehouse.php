<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Tenant\Traits\BelongsToTenant;
use App\Support\Logging\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Warehouse extends Model
{
    use BelongsToTenant, HasFactory, LogsActivity;

    /** Attributes to log for activity tracking */
    protected static array $activityLogAttributes = [
        'name',
        'code',
        'type',
        'is_active',
    ];

    protected static ?string $activityLogName = 'warehouses';

    protected $fillable = [
        'tenant_id', 'name', 'code', 'type', 'route_id', 'machine_id',
        'responsible_user_id', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }
}
