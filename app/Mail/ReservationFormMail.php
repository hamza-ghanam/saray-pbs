<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationFormMail extends Mailable
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
        return $this->subject('Your Reservation Form')
            ->view('emails.reservation_form')
            ->attach(storage_path("app/private/reservation_forms/{$this->fileName}"), [
                'as'   => $this->fileName,
                'mime' => 'application/pdf',
            ]);
    }
}
