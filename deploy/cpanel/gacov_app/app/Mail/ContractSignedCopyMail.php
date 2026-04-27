<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\ContractAgreement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

final class ContractSignedCopyMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly ContractAgreement $contract,
        public readonly string $pdfPath,
    ) {}

    public function build(): self
    {
        return $this->subject("Contrato firmado: {$this->contract->contract_number}")
            ->view('emails.contracts.signed-copy')
            ->with([
                'contract' => $this->contract,
            ])
            ->attach($this->pdfPath, [
                'as' => 'contrato-'.$this->contract->contract_number.'.pdf',
                'mime' => 'application/pdf',
            ]);
    }
}
