<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\ContractAgreement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

final class ContractSigningLinkMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly ContractAgreement $contract,
        public readonly string $signingUrl,
    ) {}

    public function build(): self
    {
        return $this->subject("Firma pendiente: {$this->contract->contract_number}")
            ->view('emails.contracts.sign-link')
            ->with([
                'contract' => $this->contract,
                'signingUrl' => $this->signingUrl,
            ]);
    }
}
