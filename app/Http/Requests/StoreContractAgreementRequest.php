<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreContractAgreementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'contract_date' => ['required', 'date', 'date_format:Y-m-d'],
            'provider_name' => ['required', 'string', 'max:200'],
            'provider_document' => ['nullable', 'string', 'max:50'],
            'provider_email' => ['nullable', 'email', 'max:150'],
            'provider_phone' => ['nullable', 'string', 'max:50'],
            'provider_address' => ['nullable', 'string', 'max:255'],
            'client_company_name' => ['required', 'string', 'max:200'],
            'client_document' => ['required', 'string', 'max:50'],
            'client_legal_representative' => ['required', 'string', 'max:200'],
            'client_legal_representative_document' => ['nullable', 'string', 'max:50'],
            'client_email' => ['required', 'email', 'max:150'],
            'client_phone' => ['nullable', 'string', 'max:50'],
            'client_address' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['required', 'string', 'max:120'],
            'bank_account_type' => ['required', 'string', 'max:60'],
            'bank_account_number' => ['required', 'string', 'max:60'],
            'bank_account_holder' => ['required', 'string', 'max:200'],
            'summary' => ['nullable', 'string'],
            'client_notes' => ['nullable', 'string'],
        ];
    }
}
