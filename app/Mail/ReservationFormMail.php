<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationFormMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $booking;
    public $customer;
    public $fileName;

    public $tries = 3;
    public $backoff = [30,120];

    public $signedFilePath;

    public function __construct($booking, $customer, $fileName, $signedFilePath)
    {
        $this->booking = $booking;
        $this->customer = $customer;
        $this->fileName = $fileName;
        $this->signedFilePath = $signedFilePath;
    }

    public function build()
    {
        return $this->subject('Your Reservation Form')
            ->view('emails.reservation_form')
            ->attach(storage_path("app/private/{$this->signedFilePath}"), [
                'as'   => $this->fileName,
                'mime' => 'application/pdf',
            ]);
    }
}
