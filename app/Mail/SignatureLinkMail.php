<?php

namespace App\Mail;

use App\Enums\DocumentType;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SignatureLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Model $signable,
        public DocumentType $documentType,
        public string $signingUrl,
        public string $downloadUrl,
        public ?string $recipientName = null,
        public ?string $documentTitle = null,
    ) {}

    public function build()
    {
        $subject = match ($this->documentType->value) {
            'RF' => 'Reservation Form - Signature Required',
            'SPA' => 'Sale & Purchase Agreement - Signature Required',
            'broker_agreement' => 'Broker Agreement - Signature Required',
            default => 'Document Signature Required',
        };

        return $this->subject($subject)
            ->view('emails.signature_link')
            ->with([
                'signable'       => $this->signable,
                'documentType'   => $this->documentType,
                'documentTitle'  => $this->documentTitle,
                'signingUrl'     => $this->signingUrl,
                'recipientName'  => $this->recipientName,
            ]);
    }
}