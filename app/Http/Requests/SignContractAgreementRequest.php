<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SignContractAgreementRequest extends FormRequest
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
            'client_signature' => ['required', 'string', 'starts_with:data:image/png;base64,'],
            'client_signer_name' => ['required', 'string', 'max:200'],
            'client_signer_document' => ['required', 'string', 'max:50'],
        ];
    }
}
