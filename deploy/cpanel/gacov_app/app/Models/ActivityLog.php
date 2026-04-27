<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Activity Log - Sistema de auditoría para GACOV Inventarios
 *
 * Registra todos los cambios importantes en el sistema para cumplimiento
 * y trazabilidad de operaciones.
 *
 * @property int $id
 * @property string $loggable_type
 * @property int $loggable_id
 * @property string $action (created|updated|deleted|viewed|exported|imported|approved|rejected|completed|cancelled)
 * @property array|null $old_values
 * @property array|null $new_values
 * @property string|null $description
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property int|null $user_id
 * @property Carbon $created_at
 */
final class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    public const UPDATED_AT = null;

    protected $fillable = [
        'loggable_type',
        'loggable_id',
        'action',
        'old_values',
        'new_values',
        'description',
        'ip_address',
        'user_agent',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Actions estándar del sistema
     */
    public const ACTION_CREATED = 'created';

    public const ACTION_UPDATED = 'updated';

    public const ACTION_DELETED = 'deleted';

    public const ACTION_VIEWED = 'viewed';

    public const ACTION_EXPORTED = 'exported';

    public const ACTION_IMPORTED = 'imported';

    public const ACTION_APPROVED = 'approved';

    public const ACTION_REJECTED = 'rejected';

    public const ACTION_COMPLETED = 'completed';

    public const ACTION_CANCELLED = 'cancelled';

    public const ACTION_LOGIN = 'login';

    public const ACTION_LOGOUT = 'logout';

    /**
     * Get the user that performed the action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the model that was affected
     */
    public function loggable(): MorphTo|Model
    {
        return $this->morphTo();
    }

    /**
     * Log a creation action
     */
    public static function logCreated(Model $model, ?array $attributes = null, ?string $description = null): self
    {
        return self::createLog($model, self::ACTION_CREATED, null, $attributes ?? $model->getAttributes(), $description);
    }

    /**
     * Log an update action
     */
    public static function logUpdated(Model $model, array $oldValues, array $newValues, ?string $description = null): self
    {
        return self::createLog($model, self::ACTION_UPDATED, $oldValues, $newValues, $description);
    }

    /**
     * Log a deletion action
     */
    public static function logDeleted(Model $model, ?string $description = null): self
    {
        return self::createLog($model, self::ACTION_DELETED, $model->getAttributes(), null, $description);
    }

    /**
     * Log a custom action
     */
    public static function logCustom(
        Model $model,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null
    ): self {
        return self::createLog($model, $action, $oldValues, $newValues, $description);
    }

    /**
     * Create the log entry
     */
    private static function createLog(
        Model $model,
        string $action,
        ?array $oldValues,
        ?array $newValues,
        ?string $description
    ): self {
        $user = auth()->user();

        return self::create([
            'loggable_type' => get_class($model),
            'loggable_id' => $model->getKey(),
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description ?? self::generateDescription($action, $model),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => $user?->getKey(),
        ]);
    }

    /**
     * Generate a default description based on action and model
     */
    private static function generateDescription(string $action, Model $model): string
    {
        $modelName = class_basename($model);
        $modelIdentifier = self::getModelIdentifier($model);

        return match ($action) {
            self::ACTION_CREATED => "Creó {$modelName}: {$modelIdentifier}",
            self::ACTION_UPDATED => "Actualizó {$modelName}: {$modelIdentifier}",
            self::ACTION_DELETED => "Eliminó {$modelName}: {$modelIdentifier}",
            self::ACTION_APPROVED => "Aprobó {$modelName}: {$modelIdentifier}",
            self::ACTION_REJECTED => "Rechazó {$modelName}: {$modelIdentifier}",
            self::ACTION_COMPLETED => "Completó {$modelName}: {$modelIdentifier}",
            self::ACTION_CANCELLED => "Canceló {$modelName}: {$modelIdentifier}",
            self::ACTION_EXPORTED => "Exportó {$modelName}",
            self::ACTION_IMPORTED => "Importó datos a {$modelName}",
            self::ACTION_LOGIN => 'Inició sesión',
            self::ACTION_LOGOUT => 'Cerró sesión',
            default => "Acción '{$action}' en {$modelName}: {$modelIdentifier}",
        };
    }

    /**
     * Get a human-readable identifier for the model
     */
    private static function getModelIdentifier(Model $model): string
    {
        // Try common identifier fields
        $identifierFields = ['name', 'code', 'title', 'email', 'id'];

        foreach ($identifierFields as $field) {
            if (isset($model->{$field}) && ! empty($model->{$field})) {
                return (string) $model->{$field};
            }
        }

        return (string) $model->getKey();
    }

    /**
     * Scope to filter by user
     */
    public function scopeForUser($query, int $userId): mixed
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by action type
     */
    public function scopeOfAction($query, string $action): mixed
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to filter by model type
     */
    public function scopeForModel($query, string $modelType): mixed
    {
        return $query->where('loggable_type', $modelType);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeBetweenDates($query, string $from, string $to): mixed
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }
}
