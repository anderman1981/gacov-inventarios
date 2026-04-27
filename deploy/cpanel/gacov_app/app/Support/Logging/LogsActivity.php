<?php

declare(strict_types=1);

namespace App\Support\Logging;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait para registrar automáticamente actividades en modelos
 *
 * Uso:
 * ```php
 * class Product extends Model
 * {
 *     use LogsActivity;
 *
 *     // Override these in the model:
 *     protected static array $activityLogAttributes = ['name', 'code', 'price'];
 *     protected static ?string $activityLogName = 'products';
 * }
 * ```
 */
trait LogsActivity
{
    /**
     * Boot the trait
     */
    public static function bootLogsActivity(): void
    {
        // Log creation
        static::created(function (Model $model): void {
            $attributes = static::getActivityLogAttributes($model->getAttributes());
            if (! empty($attributes)) {
                ActivityLog::logCreated($model, $attributes);
            }
        });

        // Log updates
        static::updated(function (Model $model): void {
            $oldValues = static::getActivityLogAttributes($model->getOriginal());
            $newValues = static::getActivityLogAttributes($model->getChanges());

            if (! empty($oldValues) || ! empty($newValues)) {
                ActivityLog::logUpdated($model, $oldValues, $newValues);
            }
        });

        // Log deletions
        static::deleted(function (Model $model): void {
            $attributes = static::getActivityLogAttributes($model->getAttributes());
            if (! empty($attributes)) {
                ActivityLog::logDeleted($model);
            }
        });
    }

    /**
     * Get attributes to log based on $activityLogAttributes (defaults to all if not set)
     */
    protected static function getActivityLogAttributes(array $attributes): array
    {
        if (isset(static::$activityLogAttributes) && ! empty(static::$activityLogAttributes)) {
            return array_intersect_key($attributes, array_flip(static::$activityLogAttributes));
        }

        return $attributes;
    }

    /**
     * Get the log name for the model
     */
    public function getActivityLogName(): string
    {
        return static::$activityLogName ?? strtolower(class_basename($this));
    }

    /**
     * Activity log relationship
     */
    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'loggable');
    }
}
