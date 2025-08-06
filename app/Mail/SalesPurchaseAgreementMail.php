<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SalesPurchaseAgreementMail extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;
    public $fileName;

    public function __construct($booking, $fileName)
    {
        $this->booking = $booking;
        $this->fileName = $fileName;
    }

    public function build()
    {
        if ($this->fileName !== '') {
            return $this->subject('Your Sales and Purchase Agreement (SPA)')
                ->view('emails.spa_form')
                ->attach(storage_path("app/private/spa_forms/{$this->fileName}"), [
                    'as' => $this->fileName,
                    'mime' => 'application/pdf',
                ]);
        } else {
            return $this->subject('Approved Sales and Purchase Agreement (SPA)')
                ->view('emails.approved_spa_form');
        }
    }
}
