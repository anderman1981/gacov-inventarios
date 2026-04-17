<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Tenant\Traits\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Tenant;
use App\Models\User;

/**
 * Contrato firmado digitalmente entre AMR Tech e Inversiones GACOV.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $created_by
 * @property int|null $signed_by
 * @property string $contract_number
 * @property int $sequence
 * @property string $status
 * @property Carbon $contract_date
 * @property string $provider_name
 * @property string|null $provider_document
 * @property string|null $provider_email
 * @property string|null $provider_phone
 * @property string|null $provider_address
 * @property string $client_company_name
 * @property string $client_document
 * @property string $client_legal_representative
 * @property string|null $client_legal_representative_document
 * @property string $client_email
 * @property string|null $client_phone
 * @property string|null $client_address
 * @property string $bank_name
 * @property string $bank_account_type
 * @property string $bank_account_number
 * @property string $bank_account_holder
 * @property string|null $summary
 * @property string|null $client_notes
 * @property string|null $client_signer_name
 * @property string|null $client_signer_document
 * @property string|null $client_signature_path
 * @property Carbon|null $client_signed_at
 * @property string|null $client_signed_ip
 * @property string|null $pdf_path
 * @property Carbon|null $signature_link_sent_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class ContractAgreement extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'created_by',
        'signed_by',
        'contract_number',
        'sequence',
        'status',
        'contract_date',
        'provider_name',
        'provider_document',
        'provider_email',
        'provider_phone',
        'provider_address',
        'client_company_name',
        'client_document',
        'client_legal_representative',
        'client_legal_representative_document',
        'client_email',
        'client_phone',
        'client_address',
        'bank_name',
        'bank_account_type',
        'bank_account_number',
        'bank_account_holder',
        'summary',
        'client_notes',
        'client_signer_name',
        'client_signer_document',
        'client_signature_path',
        'client_signed_at',
        'client_signed_ip',
        'pdf_path',
        'signature_link_sent_at',
    ];

    protected $casts = [
        'contract_date' => 'date',
        'client_signed_at' => 'datetime',
        'signature_link_sent_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_SIGNATURE = 'pending_signature';

    public const STATUS_SIGNED = 'signed';

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    public function scopeForTenant(Builder $query, Tenant $tenant): Builder
    {
        return $query->where('tenant_id', $tenant->id);
    }

    public function scopePendingSignature(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING_SIGNATURE);
    }

    public function scopeSigned(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SIGNED);
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_SIGNED;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Borrador',
            self::STATUS_PENDING_SIGNATURE => 'Pendiente de firma',
            self::STATUS_SIGNED => 'Firmado y bloqueado',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }
}
