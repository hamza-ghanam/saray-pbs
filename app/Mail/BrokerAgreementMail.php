<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BrokerAgreementMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $fileName;

    public function __construct($user, $fileName)
    {
        $this->user = $user;
        $this->fileName = $fileName;
    }

    public function build()
    {
        return $this->subject('Your Broker Agreement')
            ->view('emails.broker_agreement')
            ->attach(storage_path("app/private/agreements/{$this->fileName}"), [
                'as'   => $this->fileName,
                'mime' => 'application/pdf',
            ]);
    }
}
