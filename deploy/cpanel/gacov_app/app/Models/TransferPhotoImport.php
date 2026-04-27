<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TransferPhotoImport extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'source_files',
        'detected_rows',
        'applied_rows',
        'detected_machine_columns',
        'payload',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'source_files' => 'array',
            'detected_rows' => 'integer',
            'applied_rows' => 'integer',
            'detected_machine_columns' => 'array',
            'payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
