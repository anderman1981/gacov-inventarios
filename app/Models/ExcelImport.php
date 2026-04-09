<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ExcelImport extends Model
{
    protected $fillable = [
        'file_name',
        'file_path',
        'import_type',
        'status',
        'total_rows',
        'processed_rows',
        'error_rows',
        'error_log',
        'imported_by',
    ];

    protected function casts(): array
    {
        return [
            'total_rows' => 'integer',
            'processed_rows' => 'integer',
            'error_rows' => 'integer',
            'error_log' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
